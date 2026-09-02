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
 * Post-payment enrolment and notification handling for the Authorize.net plugin.
 *
 * @package    enrol_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2026 DualCube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_authorizedotnet;

use context_course;
use core_user;
use moodle_url;
use stdClass;

/**
 * Enrols a user and sends the configured enrolment notifications.
 *
 * Kept separate from enrol_authorizedotnet_plugin since notification delivery is
 * its own concern, distinct from the enrol_plugin interface that class implements.
 */
class enrolment_notifier {
    /**
     * Enrols the user on the given instance and sends enrolment notifications.
     *
     * @param \enrol_plugin $plugin The authorizedotnet enrol plugin instance.
     * @param stdClass $plugininstance Enrol instance.
     * @param stdClass $course Course record.
     * @param \context $context Course context.
     * @param stdClass $user User record.
     * @return bool
     */
    public static function enrol_and_notify($plugin, $plugininstance, $course, $context, $user): bool {
        $timestart = time();
        $timeend = $plugininstance->enrolperiod ? $timestart + $plugininstance->enrolperiod : 0;
        $plugin->enrol_user($plugininstance, $user->id, $plugininstance->roleid, $timestart, $timeend);
        self::send_enrollment_notifications($course, $context, $user, $plugin);

        return true;
    }

    /**
     * Send enrolment notifications to student, teacher, and admins (based on config).
     *
     * @param stdClass $course
     * @param \context $context
     * @param stdClass $user
     * @param \enrol_plugin $plugin
     */
    private static function send_enrollment_notifications($course, $context, $user, $plugin): void {
        global $CFG;
        $teacher = false;
        if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC', '', '', '', '', false, true)) {
            $users = sort_by_roleassignment_authority($users, $context);
            $teacher = array_shift($users);
        }

        $mailstudents = $plugin->get_config('mailstudents');
        $mailteachers = $plugin->get_config('mailteachers');
        $mailadmins   = $plugin->get_config('mailadmins');

        $shortname = format_string($course->shortname, true, ['context' => $context]);
        $coursecontext = context_course::instance($course->id);
        $orderdetails = new stdClass();
        $orderdetails->coursename = format_string($course->fullname, true, ['context' => $coursecontext]);

        $sitename = $CFG->sitename;

        if (!empty($mailstudents)) {
            $userfrom = empty($teacher) ? core_user::get_noreply_user() : $teacher;
            $fullmessage = get_string('welcometocoursetext', 'enrol_authorizedotnet', [
                'course' => $course->fullname,
                'sitename' => $sitename,
            ]);
            $subject = get_string('enrolmentuser', 'enrol_authorizedotnet', $shortname);
            self::send_message_custom(
                $course,
                $userfrom,
                $user,
                $subject,
                $orderdetails,
                $shortname,
                $fullmessage,
                '<p>' . $fullmessage . '</p>'
            );
        }

        if (!empty($mailteachers) && !empty($teacher)) {
            $fullmessage = get_string('adminmessage', 'enrol_authorizedotnet', [
                'username' => fullname($user),
                'course' => $course->fullname,
                'sitename' => $sitename,
            ]);
            $subject = get_string('enrolmentnew', 'enrol_authorizedotnet', [
                'username' => fullname($user),
                'course' => $course->fullname,
            ]);
            self::send_message_custom(
                $course,
                $user,
                $teacher,
                $subject,
                $orderdetails,
                $shortname,
                $fullmessage,
                '<p>' . $fullmessage . '</p>'
            );
        }

        if (!empty($mailadmins)) {
            $admins = get_admins();
            $fullmessage = get_string('adminmessage', 'enrol_authorizedotnet', [
                'username' => fullname($user),
                'course' => $course->fullname,
                'sitename' => $sitename,
            ]);
            $subject = get_string('enrolmentnew', 'enrol_authorizedotnet', [
                'username' => fullname($user),
                'course' => $course->fullname,
            ]);
            self::send_message_custom(
                $course,
                $user,
                $admins,
                $subject,
                $orderdetails,
                $shortname,
                $fullmessage,
                '<p>' . $fullmessage . '</p>'
            );
        }
    }

    /**
     * Send a message to one or more recipients.
     *
     * @param stdClass $course course
     * @param stdClass $userfrom sender
     * @param stdClass|array $userto recipient or list of recipients
     * @param string $subject subject
     * @param stdClass $orderdetails order details object
     * @param string $shortname course shortname
     * @param string $fullmessage plain message
     * @param string $fullmessagehtml html message
     */
    private static function send_message_custom(
        $course,
        $userfrom,
        $userto,
        $subject,
        $orderdetails,
        $shortname,
        $fullmessage,
        $fullmessagehtml
    ): void {
        $recipients = is_array($userto) ? $userto : [$userto];
        foreach ($recipients as $recipient) {
            $message = new \core\message\message();
            $message->courseid = $course->id;
            $message->component = 'enrol_authorizedotnet';
            $message->name = 'authorizedotnet_enrolment';
            $message->userfrom = $userfrom;
            $message->userto = $recipient;
            $message->subject = $subject;
            $message->fullmessage = $fullmessage;
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = $fullmessagehtml;
            $message->smallmessage = get_string('newenrolment', 'enrol_authorizedotnet', $shortname);
            $message->notification = 1;
            $message->contexturl = new moodle_url('/course/view.php', ['id' => $course->id]);
            $message->contexturlname = $orderdetails->coursename;
            message_send($message);
        }
    }
}
