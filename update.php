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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Listens for Instant Payment Notification from Authorize.net
 *
 * This script waits for Payment notification from Authorize.net, 
 * then it sets up the enrolment for that user.
 *
 * @package    enrol_authorizedotnet
 * @copyright  2015 Dualcube, Moumita Ray, Parthajeet Chakraborty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Disable moodle specific debug messages and any errors in output,
// comment out when debugging or better look into error log!
//define('NO_DEBUG_DISPLAY', true);

require("../../config.php");
require_once("lib.php");
require_once($CFG->libdir.'/eventslib.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->libdir . '/filelib.php');

global $DB, $CFG;

$id = required_param('id', PARAM_INT);

$response = $DB->get_record('enrol_authorizedotnet', array('id' => $id));
$responsearray = json_decode($response->auth_json, true);

// Check if the response is from authorize.net.
$merchantmd5hash = get_config('enrol_authorizedotnet', 'merchantmd5hash');
$loginid = get_config('enrol_authorizedotnet', 'loginid');
$transactionid = $responsearray['x_trans_id'];
$amount = $responsearray['x_amount'];
$generatemd5hash = strtoupper(md5($merchantmd5hash.$loginid.$transactionid.$amount));
$arraycourseinstance = explode('-', $responsearray['x_cust_id']);


if ($generatemd5hash != $responsearray['x_MD5_Hash']) {
    print_error("We can't validate your transaction. Please try again!!"); die;
}

$arraycourseinstance = explode('-', $responsearray['x_cust_id']);
if (empty($arraycourseinstance) || count($arraycourseinstance) < 4) {
    print_error("Received an invalid payment notification!! (Fake payment?)"); die;
}

if (! $user = $DB->get_record("user", array("id" => $arraycourseinstance[1]))) {
    print_error("Not a valid user id"); die;
}

if (! $course = $DB->get_record("course", array("id" => $arraycourseinstance[0]))) {
    print_error("Not a valid course id"); die;
}

if (! $context = context_course::instance($arraycourseinstance[0], IGNORE_MISSING)) {
    print_error("Not a valid context id"); die;
}

if (! $plugininstance = $DB->get_record("enrol", array("id" => $arraycourseinstance[2], "status" => 0))) {
    print_error("Not a valid instance id"); die;
}



$enrolauthorizedotnet = $userenrolments = $roleassignments = new stdClass();

$enrolauthorizedotnet->id = $id;
$enrolauthorizedotnet->item_name = $responsearray['x_description'];
$enrolauthorizedotnet->courseid = $arraycourseinstance[0];
$enrolauthorizedotnet->userid = $arraycourseinstance[1];
$enrolauthorizedotnet->instanceid = $arraycourseinstance[2];
$enrolauthorizedotnet->amount = $responsearray['x_amount'];
$enrolauthorizedotnet->tax = $responsearray['x_tax'];
$enrolauthorizedotnet->duty = $responsearray['x_duty'];



if ($responsearray['x_response_code'] == 1) {
    $enrolauthorizedotnet->payment_status = 'Approved';
}
if ($responsearray['x_response_code'] == 2) {
    $enrolauthorizedotnet->payment_status = 'Declined';
}
if ($responsearray['x_response_code'] == 3) {
    $enrolauthorizedotnet->payment_status = 'Error';
}
if ($responsearray['x_response_code'] == 4) {
    $enrolauthorizedotnet->payment_status = 'Held for Review';
}


$enrolauthorizedotnet->response_code = $responsearray['x_response_code'];
$enrolauthorizedotnet->response_reason_code = $responsearray['x_response_reason_code'];
$enrolauthorizedotnet->response_reason_text = $responsearray['x_response_reason_text'];
$enrolauthorizedotnet->auth_code = $responsearray['x_auth_code'];
$enrolauthorizedotnet->trans_id = $responsearray['x_trans_id'];
$enrolauthorizedotnet->method = $responsearray['x_method'];
$enrolauthorizedotnet->account_number = isset($responsearray['x_account_number']) ? $responsearray['x_account_number'] : '';
$enrolauthorizedotnet->card_type = isset($responsearray['x_card_type']) ? $responsearray['x_card_type'] : '';
$enrolauthorizedotnet->first_name = isset($responsearray['x_first_name']) ? $responsearray['x_first_name'] : '';
$enrolauthorizedotnet->last_name = isset($responsearray['x_last_name']) ? $responsearray['x_last_name'] : '';
$enrolauthorizedotnet->company = isset($responsearray['x_company']) ? $responsearray['x_company'] : '';
$enrolauthorizedotnet->phone = isset($responsearray['x_phone']) ? $responsearray['x_phone'] : '';
$enrolauthorizedotnet->fax = isset($responsearray['x_fax']) ? $responsearray['x_fax'] : '';
$enrolauthorizedotnet->address = isset($responsearray['x_address']) ? $responsearray['x_address'] : '';
$enrolauthorizedotnet->city = isset($responsearray['x_city']) ? $responsearray['x_city'] : '';
$enrolauthorizedotnet->state = isset($responsearray['x_state']) ? $responsearray['x_state'] : '';
$enrolauthorizedotnet->zip = isset($responsearray['x_zip']) ? $responsearray['x_zip'] : '';
$enrolauthorizedotnet->country = isset($responsearray['x_country']) ? $responsearray['x_country'] : '';
$enrolauthorizedotnet->email = isset($responsearray['x_email']) ? $responsearray['x_email'] : '';
$enrolauthorizedotnet->invoice_num = $responsearray['x_invoice_num'];
$enrolauthorizedotnet->test_request = ($responsearray['x_test_request'] == 'true') ? '1' : '0';
$enrolauthorizedotnet->timeupdated = time();
/* Inserting value to enrol_authorizedotnet table */
$ret1 = $DB->update_record("enrol_authorizedotnet", $enrolauthorizedotnet, false);


if ($responsearray['x_response_code'] == 1) {
    /* Inserting value to user_enrolments table */
    $userenrolments->status = 0;
    $userenrolments->enrolid = $arraycourseinstance[2];
    $userenrolments->userid = $arraycourseinstance[1];
    $userenrolments->timestart = time();
    $userenrolments->timeend = 0;
    $userenrolments->modifierid = 2;
    $userenrolments->timecreated = time();
    $userenrolments->timemodified = time();
    $ret2 = $DB->insert_record("user_enrolments", $userenrolments, false);
    /* Inserting value to role_assignments table */
    $roleassignments->roleid = 5;
    $roleassignments->contextid = $arraycourseinstance[3];
    $roleassignments->userid = $arraycourseinstance[1];
    $roleassignments->timemodified = time();
    $roleassignments->modifierid = 2;
    $roleassignments->component = '';
    $roleassignments->itemid = 0;
    $roleassignments->sortorder = 0;
    $ret3 = $DB->insert_record('role_assignments', $roleassignments, false);
}
echo '<script type="text/javascript">
     window.location.href="'.$CFG->wwwroot.'/enrol/authorizedotnet/return.php?id='.$arraycourseinstance[0].'";
     </script>';
die;
