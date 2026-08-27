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
 * Strings for component 'enrol_authorizedotnet', language 'en'.
 *
 * @package    enrol_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2021 DualCube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['adminmessage'] = ' Hello,<br /> A new student has enrolled in a course. <br />Student Name: {$a->username}<br /> Course Name: {$a->course}<br /> Best regards,<br /> {$a->sitename} Team';
$string['assignrole'] = 'Assign role';
$string['authorising'] = 'Authorising ...';
$string['authorizedotnet:config'] = 'Configure Authorize.net enrol instances';
$string['authorizedotnet:manage'] = 'Manage enrolled users';
$string['authorizedotnet:unenrol'] = 'Unenrol users from course';
$string['authorizedotnet:unenrolself'] = 'Unenrol self from the course';
$string['canntenrolearly'] = "Can't enrol early";
$string['canntenrollate'] = 'Can not enrol late';
$string['checkproductionmode'] = 'Enable test Mode';
$string['cost'] = 'Enrol cost';
$string['costerror'] = 'The enrolment cost is not numeric';
$string['currency'] = 'Currency';
$string['currencycannotchange'] = 'Currency can not be change ';
$string['defaultrole'] = 'Default role assignment';
$string['defaultrole_desc'] = 'Select role which should be assigned to users during Authorize.net enrolments';
$string['enrolenddate'] = 'End date';
$string['enrolenddate_help'] = 'If enabled, users can be enrolled until this date only.';
$string['enrolenddaterror'] = 'Enrolment end date cannot be earlier than start date';
$string['enrolmentnew'] = 'New Student Enrollment: {$a->username} in {$a->course}';
$string['enrolmentuser'] = 'You"re Enrolled: {$a} Awaits You.';
$string['enrolnow'] = 'Enrol now';
$string['enrolnowin'] = 'Enrol now in';
$string['enrolperiod'] = 'Enrolment duration';
$string['enrolperiod_desc'] = 'Default length of time that the enrolment is valid. If set to zero, the enrolment duration will be unlimited by default.';
$string['enrolperiod_help'] = 'Length of time that the enrolment is valid, starting with the moment the user is enrolled. If disabled, the enrolment duration will be unlimited.';
$string['enrolstartdate'] = 'Start date';
$string['enrolstartdate_help'] = 'If enabled, users can be enrolled from this date onward only.';
$string['expiredaction'] = 'Enrolment expiration action';
$string['expiredaction_help'] = 'Select action to carry out when user enrolment expires. Please note that some user data and settings are purged from course during course unenrolment.';
$string['loginid'] = 'Authorize.net Login ID';
$string['mailadmins'] = 'Notify admin';
$string['mailstudents'] = 'Notify students';
$string['mailteachers'] = 'Notify teachers';
$string['messageprovider:authorizedotnet_enrolment'] = 'authorizedotnet enrolment notifications';
$string['newenrolment'] = 'New Student Enrollment in {$a}';
$string['nocost'] = 'No cost';
$string['noresponse'] = 'No response from Authorize.Net';
$string['pay'] = 'Pay';
$string['pluginname'] = 'Authorize.net';
$string['pluginname_desc'] = 'The Authorize.net module allows you to set up paid courses.  If the cost for any course is zero, then students are not asked to pay for entry.  There is a site-wide cost that you set here as a default for the whole site and then a course setting that you can set for each course individually. The course cost overrides the site cost.';
$string['publicclientkey'] = 'Public client key';
$string['status'] = 'Allow Authorize.net enrolments';
$string['status_desc'] = 'Allow users to use Authorize.net to enrol into a course by default.';
$string['totaldue'] = 'Total due';
$string['transactionfailmsg'] = 'Transaction failed! No response returned';
$string['transactionkey'] = 'Authorize.net Transaction Key';
$string['unenrolselfconfirm'] = 'Do you really want to unenrol yourself from course "{$a}"?';
$string['welcometocoursetext'] = 'Welcome to our course {$a->course}! We hope you enjoy it.';
