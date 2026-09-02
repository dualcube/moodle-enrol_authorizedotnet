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
 * External function: process a payment and enrol the user.
 *
 * @package    enrol_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2026 DualCube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_authorizedotnet\external;

use context;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use enrol_authorizedotnet\authorizedotnet_helper;
use enrol_authorizedotnet\enrolment_notifier;
use Exception;
use moodle_exception;
use stdClass;

/**
 * Processes a payment and enrols the user if successful.
 *
 * @package    enrol_authorizedotnet
 * @category   external
 */
class process_payment extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
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
    public static function execute(int $instanceid, int $userid, string $opaquedata): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'instanceid' => $instanceid,
            'userid' => $userid,
            'opaquedata' => $opaquedata,
        ]);
        $instanceid = $params['instanceid'];
        $userid = $params['userid'];

        [$instance, $course, $context] = util::require_enrol_instance($instanceid);

        self::validate_payment_eligibility($instance, $userid);

        if ($DB->record_exists('user_enrolments', ['userid' => $userid, 'enrolid' => $instanceid])) {
            return ['success' => false, 'message' => get_string('alreadyenrolled', 'enrol_authorizedotnet')];
        }

        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $plugin = enrol_get_plugin('authorizedotnet');
        $cost = self::get_payment_cost($instance, $plugin);

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

        return self::complete_enrolment($instance, $course, $context, $user, $instanceid, $cost, $plugin, $response);
    }

    /**
     * Validates that a payment attempt is currently permitted for this instance/user.
     *
     * @param stdClass $instance Enrolment instance.
     * @param int $userid User ID attempting to pay.
     */
    private static function validate_payment_eligibility(stdClass $instance, int $userid): void {
        global $USER;

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
    }

    /**
     * Resolves the amount to charge for this instance, falling back to the site default.
     *
     * @param stdClass $instance Enrolment instance.
     * @param enrol_authorizedotnet_plugin $plugin Plugin instance.
     * @return float
     */
    private static function get_payment_cost(stdClass $instance, $plugin): float {
        $cost = (float) $instance->cost;

        return $cost > 0 ? $cost : (float) $plugin->get_config('cost');
    }

    /**
     * Records the transaction and enrols the user after a successful charge.
     *
     * The card has already been captured by the time this runs, so a failure here is
     * surfaced as a loud debugging() call for an admin to reconcile manually rather than
     * silently losing track of the payment.
     *
     * @param stdClass $instance Enrolment instance.
     * @param stdClass $course Course record.
     * @param context $context Course context.
     * @param stdClass $user User being enrolled.
     * @param int $instanceid Enrolment instance ID.
     * @param float $cost Amount charged.
     * @param enrol_authorizedotnet_plugin $plugin Plugin instance.
     * @param array $response Result from authorizedotnet_helper::create_transaction().
     * @return array {success: bool, message: string}
     */
    private static function complete_enrolment(
        stdClass $instance,
        stdClass $course,
        context $context,
        stdClass $user,
        int $instanceid,
        float $cost,
        $plugin,
        array $response
    ): array {
        global $DB;

        try {
            $transactiondata = self::build_transaction_record($response, $course, $user, $instanceid, $cost, $plugin);
            $DB->insert_record('enrol_authorizedotnet', $transactiondata);

            enrolment_notifier::enrol_and_notify($plugin, $instance, $course, $context, $user);

            return ['success' => true, 'message' => ''];
        } catch (Exception $e) {
            debugging(
                'Authorize.net transaction ' . ($response['transactionid'] ?? '') .
                    ' succeeded but enrolment failed for user ' . $user->id . ' in course ' . $course->id .
                    ': ' . $e->getMessage(),
                DEBUG_NORMAL
            );
            return ['success' => false, 'message' => get_string('enrolmentfailedaftercharge', 'enrol_authorizedotnet')];
        }
    }

    /**
     * Builds the enrol_authorizedotnet transaction record for a successful charge.
     *
     * @param array $response Result from authorizedotnet_helper::create_transaction().
     * @param stdClass $course Course record.
     * @param stdClass $user User being enrolled.
     * @param int $instanceid Enrolment instance ID.
     * @param float $cost Amount charged.
     * @param enrol_authorizedotnet_plugin $plugin Plugin instance.
     * @return stdClass
     */
    private static function build_transaction_record(
        array $response,
        stdClass $course,
        stdClass $user,
        int $instanceid,
        float $cost,
        $plugin
    ): stdClass {
        $responsecode = (int) ($response['responsecode'] ?? 0);

        $transactiondata = new stdClass();
        $transactiondata->itemname = $course->fullname;
        $transactiondata->courseid = $course->id;
        $transactiondata->userid = $user->id;
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

        return $transactiondata;
    }

    /**
     * Returns description of method return value.
     *
     * @return external_function_parameters
     */
    public static function execute_returns(): external_function_parameters {
        return new external_function_parameters([
            'success' => new external_value(PARAM_BOOL, 'Whether everything was successful or not.'),
            'message' => new external_value(PARAM_RAW, 'Message (usually the error message).'),
        ]);
    }
}
