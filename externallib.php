<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * External library for authorizedotnet.
 *
 * @package    enrol_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2021 DualCube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use enrol_authorizedotnet\authorizedotnet_helper;

require_once("$CFG->libdir/externallib.php");

/**
 * External functions for the Authorize.net enrolment plugin.
 *
 * Defines the external functions exposed via web services.
 *
 * @package    enrol_authorizedotnet
 * @category   external
 */
class enrol_authorizedotnet_externallib extends external_api {
    /**
     * Loads an Authorize.net enrolment instance for a webservice call.
     *
     * Confirms the instance belongs to this plugin and validates the system context for
     * the current webservice protocol. Deliberately does not require_login() against the
     * course: the caller is, by definition, not yet enrolled in it (that's the whole point
     * of this payment flow), and require_login($course, ..., preventredirect: true) throws
     * a require_login_exception for exactly that reason - there is no enrol page left to
     * redirect an AJAX caller to. Login itself is already enforced before this code runs,
     * via 'loginrequired' => true in db/services.php. Shared by every external function
     * here that operates on a specific instance, so the checks can't drift between them.
     *
     * @param int $instanceid Enrolment instance ID.
     * @return array [instance, course, context] for the enrolment instance.
     */
    private static function require_enrol_instance(int $instanceid): array {
        global $DB;

        self::validate_context(context_system::instance());

        $instance = $DB->get_record('enrol', ['id' => $instanceid, 'enrol' => 'authorizedotnet'], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);
        $context = context_course::instance($course->id);

        return [$instance, $course, $context];
    }

    /**
     * Returns parameters for get_config_for_js.
     *
     * @return external_function_parameters
     */
    public static function get_config_for_js_parameters() {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'Enrolment instance ID'),
        ]);
    }

    /**
     * Returns configuration data needed for client-side JS.
     *
     * @param int $instanceid Enrolment instance ID
     * @return array
     */
    public static function get_config_for_js($instanceid) {
        $params = self::validate_parameters(self::get_config_for_js_parameters(), ['instanceid' => $instanceid]);
        self::require_enrol_instance($params['instanceid']);

        $plugin = enrol_get_plugin('authorizedotnet');

        return [
            'apiloginid' => $plugin->get_config('loginid'),
            'publicclientkey' => $plugin->get_config('publicclientkey'),
            'environment' => $plugin->get_config('checkproductionmode') ? 'sandbox' : 'production',
        ];
    }

    /**
     * Returns description of get_config_for_js return values.
     *
     * @return external_function_parameters
     */
    public static function get_config_for_js_returns() {
        return new external_function_parameters([
            'apiloginid' => new external_value(PARAM_RAW, 'The API login ID for the gateway.'),
            'publicclientkey' => new external_value(PARAM_RAW, 'The public client key for the gateway.'),
            'environment' => new external_value(PARAM_RAW, 'The environment (sandbox or production).'),
        ]);
    }

    /**
     * Returns parameters for process_payment.
     *
     * @return external_function_parameters
     */
    public static function process_payment_parameters() {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'The enrolment instance ID'),
            'userid' => new external_value(PARAM_INT, 'The user ID'),
            'opaquedata' => new external_value(PARAM_RAW, 'The opaque data from Authorize.net'),
        ]);
    }

    /**
     * Maps an Authorize.Net transaction response code to a stored payment status.
     *
     * @param int $responsecode Authorize.Net transaction response code.
     * @return string
     */
    private static function get_payment_status(int $responsecode): string {
        return match ($responsecode) {
            1 => 'approved',
            2 => 'declined',
            3 => 'error',
            4 => 'held',
            default => 'unknown',
        };
    }

    /**
     * Processes a payment and enrols the user if successful.
     *
     * The enrolment instance, its status and window, and any existing enrolment are all
     * validated before the card is charged, so a rejected or replayed request never results
     * in a charge without a matching enrolment.
     *
     * @param int $instanceid Enrolment instance ID
     * @param int $userid User ID
     * @param string $opaquedata Opaque data from Authorize.net
     * @return array {
     *     success => bool Whether the payment succeeded,
     *     message => string Message or error description
     * }
     */
    public static function process_payment(int $instanceid, int $userid, string $opaquedata): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::process_payment_parameters(), [
            'instanceid' => $instanceid,
            'userid' => $userid,
            'opaquedata' => $opaquedata,
        ]);
        $instanceid = $params['instanceid'];
        $userid = $params['userid'];

        [$instance, $course, $context] = self::require_enrol_instance($instanceid);

        if ($userid !== (int) $USER->id) {
            throw new moodle_exception('invaliduserid', 'enrol_authorizedotnet');
        }

        if ((int) $instance->status !== ENROL_INSTANCE_ENABLED) {
            throw new moodle_exception('enrolmentnotavailable', 'enrol_authorizedotnet');
        }

        $now = time();
        if (!empty($instance->enrolstartdate) && $instance->enrolstartdate > $now) {
            throw new moodle_exception('canntenrolearly', 'enrol_authorizedotnet', '', userdate($instance->enrolstartdate));
        }
        if (!empty($instance->enrolenddate) && $instance->enrolenddate < $now) {
            throw new moodle_exception('canntenrollate', 'enrol_authorizedotnet', '', userdate($instance->enrolenddate));
        }

        if ($DB->record_exists('user_enrolments', ['userid' => $userid, 'enrolid' => $instanceid])) {
            return ['success' => false, 'message' => get_string('alreadyenrolled', 'enrol_authorizedotnet')];
        }

        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $plugin = enrol_get_plugin('authorizedotnet');
        $cost = (float) $instance->cost;
        if ($cost <= 0) {
            $cost = (float) $plugin->get_config('cost');
        }

        $helper = new authorizedotnet_helper(
            $plugin->get_config('loginid'),
            $plugin->get_config('transactionkey'),
            (bool) $plugin->get_config('checkproductionmode')
        );

        $response = $helper->create_transaction($cost, json_decode($params['opaquedata']));

        if (!$response['success']) {
            return ['success' => false, 'message' => $response['message']];
        }

        debugging('Authorize.net response: ' . var_export($response, true), DEBUG_DEVELOPER);

        try {
            $responsecode = (int) ($response['responsecode'] ?? 0);

            // Save transaction record to the database.
            $transactiondata = new stdClass();
            $transactiondata->itemname = $course->fullname;
            $transactiondata->courseid = $course->id;
            $transactiondata->userid = $userid;
            $transactiondata->instanceid = $instanceid;
            $transactiondata->amount = (string) $cost;
            $transactiondata->paymentstatus = self::get_payment_status($responsecode);
            $transactiondata->responsecode = $responsecode;
            $transactiondata->responsereasoncode = (int) ($response['responsereasoncode'] ?? 0);
            $transactiondata->responsereasontext = $response['responsereasontext'] ?? '';
            $transactiondata->authcode = substr($response['authcode'] ?? '', 0, 30);
            $transactiondata->transid = $response['transactionid'];
            $transactiondata->invoicenum = $response['invoicenum'] ?? '';
            $transactiondata->testrequest = (int) $plugin->get_config('checkproductionmode');
            $transactiondata->firstname = $user->firstname ?? '';
            $transactiondata->lastname = $user->lastname ?? '';
            $transactiondata->company = $user->institution ?? '';
            $transactiondata->phone = $user->phone1 ?? '';
            $transactiondata->email = $user->email ?? '';
            $transactiondata->address = $user->address ?? '';
            $transactiondata->city = $user->city ?? '';
            $transactiondata->zip = $user->zip ?? '';
            $transactiondata->country = $user->country ?? '';
            $transactiondata->authjson = json_encode($response);
            $transactiondata->timeupdated = time();
            debugging('Authorize.net transaction data: ' . var_export($transactiondata, true), DEBUG_DEVELOPER);

            $DB->insert_record('enrol_authorizedotnet', $transactiondata);

            // Enrol the user and send notifications.
            $enrolmentdata = new stdClass();
            $enrolmentdata->courseid = $course->id;
            $enrolmentdata->userid = $user->id;
            $enrolmentdata->instanceid = $instanceid;
            $enrolmentdata->amount = $cost;
            $enrolmentdata->transid = $response['transactionid'];
            $enrolmentdata->timeupdated = time();
            $plugin->enroll_user_and_send_notifications($instance, $course, $context, $user, $enrolmentdata);

            return ['success' => true, 'message' => ''];
        } catch (Exception $e) {
            // The card has already been captured at this point, so surface this loudly for an
            // admin to reconcile manually rather than silently losing track of the payment.
            debugging(
                'Authorize.net transaction ' . ($response['transactionid'] ?? '') .
                    ' succeeded but enrolment failed for user ' . $userid . ' in course ' . $course->id .
                    ': ' . $e->getMessage(),
                DEBUG_NORMAL
            );
            return ['success' => false, 'message' => get_string('enrolmentfailedaftercharge', 'enrol_authorizedotnet')];
        }
    }

    /**
     * Returns description of process_payment return values.
     *
     * @return external_function_parameters
     */
    public static function process_payment_returns() {
        return new external_function_parameters([
            'success' => new external_value(PARAM_BOOL, 'Whether everything was successful or not.'),
            'message' => new external_value(PARAM_RAW, 'Message (usually the error message).'),
        ]);
    }
}
