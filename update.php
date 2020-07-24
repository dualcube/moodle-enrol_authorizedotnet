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
//require_once($CFG->libdir.'/eventslib.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->libdir . '/filelib.php');

global $DB, $CFG;

$id = required_param('id', PARAM_INT);

$response = $DB->get_record('enrol_authorizedotnet', array('id' => $id));
$responsearray = json_decode($response->auth_json, true);


// Check if the response is from authorize.net.




$loginid = get_config('enrol_authorizedotnet', 'loginid');
$transactionid = $responsearray['transId'];
$amount = $responsearray['amount'];
//$generatemd5hash = strtoupper(md5($merchantmd5hash.$loginid.$transactionid.$amount));
$arraycourseinstance = explode('-', $responsearray['x_cust_id']);

// Required for message_send.
$PAGE->set_context(context_system::instance());

/*
if ($generatemd5hash != $responsearray['x_MD5_Hash']) {
    print_error("We can't validate your transaction. Please try again!!"); die;
}
*/

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
$enrolauthorizedotnet->amount = $responsearray['amount'];
// $enrolauthorizedotnet->tax = $responsearray['x_tax'];
// $enrolauthorizedotnet->duty = $responsearray['x_duty'];


if ($responsearray['responseCode'] == 1) {
    $enrolauthorizedotnet->payment_status = 'Approved';
    
    $PAGE->set_context($context);
    $coursecontext = context_course::instance($course->id, IGNORE_MISSING);

    if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
                                         '', '', '', '', false, true)) {
        $users = sort_by_roleassignment_authority($users, $context);
        $teacher = array_shift($users);
    } else {
        $teacher = false;
    }

    $plugin = enrol_get_plugin('authorizedotnet');

    $mailstudents = $plugin->get_config('mailstudents');
    $mailteachers = $plugin->get_config('mailteachers');
    $mailadmins   = $plugin->get_config('mailadmins');
    $shortname = format_string($course->shortname, true, array('context' => $context));

    if (!empty($mailstudents)) {
        $a = new stdClass();
        $a->coursename = format_string($course->fullname, true, array('context' => $coursecontext));
        $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id";

        if ($CFG->version >= 2015051100) {
            $eventdata = new \core\message\message();
        } else {
            $eventdata = new stdClass();
        }
        $eventdata->component         = 'enrol_authorizedotnet';
        $eventdata->name              = 'authorizedotnet_enrolment';
        //$eventdata->courseid          = $course->id;
        $eventdata->userfrom          = empty($teacher) ? core_user::get_noreply_user() : $teacher;
        $eventdata->userto            = $user;
        $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
        $eventdata->fullmessage       = get_string('welcometocoursetext', '', $a);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = '';
        message_send($eventdata);

    }

    if (!empty($mailteachers) && !empty($teacher)) {
        $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
        $a->user = fullname($user);

        if ($CFG->version >= 2015051100) {
            $eventdata = new \core\message\message();
        } else {
            $eventdata = new stdClass();
        }
        $eventdata->component         = 'enrol_authorizedotnet';
        $eventdata->name              = 'authorizedotnet_enrolment';
        //$eventdata->courseid          = $course->id;
        $eventdata->userfrom          = $user;
        $eventdata->userto            = $teacher;
        $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
        $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = '';
        message_send($eventdata);
    }

    if (!empty($mailadmins)) {
        $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
        $a->user = fullname($user);
        $admins = get_admins();
        foreach ($admins as $admin) {
        
            if ($CFG->version >= 2015051100) {
                $eventdata = new \core\message\message();
            } else {
                $eventdata = new stdClass();
            }
            $eventdata->component         = 'enrol_authorizedotnet';
            $eventdata->name              = 'authorizedotnet_enrolment';
            //$eventdata->courseid          = $course->id;
            $eventdata->userfrom          = $user;
            $eventdata->userto            = $admin;
            $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
            $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml   = '';
            $eventdata->smallmessage      = '';
            message_send($eventdata);
        }
    }
}
if ($responsearray['responseCode'] == 2) {
    $enrolauthorizedotnet->payment_status = 'Declined';
}
if ($responsearray['responseCode'] == 3) {
    $enrolauthorizedotnet->payment_status = 'Error';
}
if ($responsearray['responseCode'] == 4) {
    $enrolauthorizedotnet->payment_status = 'Held for Review';
}


$enrolauthorizedotnet->response_code = $responsearray['responseCode'];
// $enrolauthorizedotnet->response_reason_code = $responsearray['x_response_reason_code'];
// $enrolauthorizedotnet->response_reason_text = $responsearray['x_response_reason_text'];
$enrolauthorizedotnet->auth_code = $responsearray['authCode'];
$enrolauthorizedotnet->trans_id = $responsearray['transId'];
// $enrolauthorizedotnet->method = $responsearray['x_method'];
$enrolauthorizedotnet->account_number = isset($responsearray['accountNumber']) ? $responsearray['accountNumber'] : '';
$enrolauthorizedotnet->card_type = isset($responsearray['accountType']) ? $responsearray['accountType'] : '';
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
// $enrolauthorizedotnet->test_request = ($responsearray['x_test_request'] == 'true') ? '1' : '0';
$enrolauthorizedotnet->timeupdated = time();
/* Inserting value to enrol_authorizedotnet table */
$ret1 = $DB->update_record("enrol_authorizedotnet", $enrolauthorizedotnet, false);

if ($plugininstance->enrolperiod) {
   $timestart = time();
   $timeend   = $timestart + $plugininstance->enrolperiod;
} else {
    $timestart = 0;
    $timeend   = 0;
}

/* Enrol User */
$plugin->enrol_user($plugininstance, $user->id, $plugininstance->roleid, $timestart, $timeend);

echo '<script type="text/javascript">
     window.location.href="'.$CFG->wwwroot.'/enrol/authorizedotnet/return.php?id='.$arraycourseinstance[0].'";
     </script>';
die;
