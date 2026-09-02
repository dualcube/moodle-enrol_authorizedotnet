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
 * Builds the manage-enrolments row actions for the Authorize.net plugin.
 *
 * @package    enrol_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2026 DualCube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_authorizedotnet;

use course_enrolment_manager;
use moodle_url;
use pix_icon;
use user_enrolment_action;

/**
 * Builds the unenrol/edit row actions shown for a user enrolment in the
 * manage-enrolments UI.
 *
 * Kept separate from enrol_authorizedotnet_plugin purely to keep that class's
 * dependency count down; this is UI row-action construction, not enrolment logic.
 */
class enrolment_action_builder {
    /**
     * Builds the row actions for one user enrolment.
     *
     * @param \enrol_authorizedotnet_plugin $plugin The authorizedotnet enrol plugin instance.
     * @param course_enrolment_manager $manager
     * @param \stdClass $ue user enrolment
     * @return user_enrolment_action[]
     */
    public static function build($plugin, course_enrolment_manager $manager, $ue): array {
        $actions = [];
        $context = $manager->get_context();
        $instance = $ue->enrolmentinstance;
        $params = $manager->get_moodlepage()->url->params();
        $params['ue'] = $ue->id;

        if ($plugin->allow_unenrol($instance) && has_capability('enrol/authorizedotnet:unenrol', $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $actions[] = new user_enrolment_action(
                new pix_icon('t/delete', ''),
                get_string('unenrol', 'enrol'),
                $url,
                ['class' => 'unenrollink', 'rel' => $ue->id]
            );
        }

        if ($plugin->allow_manage($instance) && has_capability('enrol/authorizedotnet:manage', $context)) {
            $url = new moodle_url('/enrol/editenrolment.php', $params);
            $actions[] = new user_enrolment_action(
                new pix_icon('t/edit', ''),
                get_string('edit'),
                $url,
                ['class' => 'editenrollink', 'rel' => $ue->id]
            );
        }

        return $actions;
    }
}
