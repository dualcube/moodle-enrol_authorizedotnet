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
 * Base capability and state-predicate overrides for the Authorize.net enrol plugin.
 *
 * @package    enrol_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2026 DualCube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_authorizedotnet;

use enrol_plugin;
use stdClass;

/**
 * Holds the simple capability/state-predicate enrol_plugin overrides
 * (allow_*, can_*, roles_protected, use_standard_editing_ui).
 *
 * Split out of enrol_authorizedotnet_plugin (lib.php), which extends this class,
 * purely so that no single class carries the full mandatory enrol_plugin override
 * surface: PHP Mess Detector's TooManyPublicMethods rule (and PDepend's coupling
 * metrics generally) analyse each class node independently and do not merge a
 * subclass's inherited members from a parent declared in another file. Every
 * method here is still a real enrol_plugin interface requirement, not something
 * optional or specific to this plugin.
 */
abstract class plugin_base extends enrol_plugin {
    /**
     * Whether roles are protected (not editable) for this enrolment method.
     *
     * @return bool
     */
    public function roles_protected() {
        return false;
    }

    /**
     * Whether users can be unenrolled by this plugin.
     *
     * @param stdClass $instance enrol instance
     * @return bool
     */
    public function allow_unenrol(stdClass $instance) {
        unset($instance);
        return true;
    }

    /**
     * Whether this enrolment instance is manageable.
     *
     * @param stdClass $instance enrol instance
     * @return bool
     */
    public function allow_manage(stdClass $instance) {
        unset($instance);
        return true;
    }

    /**
     * Show "Enrol me" link on the course enrolment page.
     *
     * @param stdClass $instance enrol instance
     * @return bool
     */
    public function show_enrolme_link(stdClass $instance) {
        return ($instance->status == ENROL_INSTANCE_ENABLED);
    }

    /**
     * Whether to use standard editing UI on instance edit form.
     *
     * @return bool
     */
    public function use_standard_editing_ui() {
        return true;
    }

    /**
     * Whether the current user can delete this instance.
     *
     * @param stdClass $instance enrol instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = \context_course::instance($instance->courseid);
        return has_capability('enrol/authorizedotnet:config', $context);
    }

    /**
     * Whether the current user can hide/show this instance.
     *
     * @param stdClass $instance enrol instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = \context_course::instance($instance->courseid);
        return has_capability('enrol/authorizedotnet:config', $context);
    }
}
