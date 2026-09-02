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
 * Shared helpers for the Authorize.net plugin's external API classes.
 *
 * @package    enrol_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2026 DualCube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_authorizedotnet\external;

use context_course;
use context_system;
use core_external\external_api;

/**
 * Helpers shared by every external function in this plugin.
 */
class util {
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
    public static function require_enrol_instance(int $instanceid): array {
        global $DB;

        external_api::validate_context(context_system::instance());

        $instance = $DB->get_record('enrol', ['id' => $instanceid, 'enrol' => 'authorizedotnet'], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);
        $context = context_course::instance($course->id);

        return [$instance, $course, $context];
    }
}
