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
 * Authorize.net enrolment plugin.
 *
 * @package    enrol_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2021 DualCube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_enrol\output\enrol_page;
use enrol_authorizedotnet\authorizedotnet_helper;

/**
 * Enrolment plugin class for Authorize.net.
 *
 * Provides UI hooks, capabilities checks, and helper utilities
 * for handling enrolment via Authorize.net.
 *
 * Note for PHP Mess Detector's TooManyPublicMethods / ExcessiveClassComplexity /
 * CouplingBetweenObjects rules: the bulk of this class's public surface and
 * dependencies come from mandatory enrol_plugin base-class overrides that Moodle
 * core calls by type, so they can't be made non-public or removed without breaking
 * the plugin. @SuppressWarnings is intentionally not used here as it is not a
 * docblock tag recognised by the Moodle coding standard.
 *
 * @package   enrol_authorizedotnet
 */
class enrol_authorizedotnet_plugin extends enrol_plugin {
    /**
     * Get the merchant currency from the Authorize.net API via helper.
     *
     * @return string ISO-4217 currency code (e.g., "USD")
     */
    protected function get_merchant_currency() {
        $plugin = enrol_get_plugin('authorizedotnet');
        $helper = new authorizedotnet_helper(
            $plugin->get_config('loginid'),
            $plugin->get_config('transactionkey'),
            (bool) $plugin->get_config('checkproductionmode')
        );
        return $helper->get_merchant_currency();
    }

    /**
     * Return info icons for course page listing.
     *
     * @param array $instances enrol instances in a course
     * @return pix_icon[] list of icons
     */
    public function get_info_icons(array $instances) {
        unset($instances);
        return [new pix_icon('icon', get_string('pluginname', 'enrol_authorizedotnet'), 'enrol_authorizedotnet')];
    }

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
     * Add navigation to course settings for this enrolment instance.
     *
     * @param navigation_node $instancesnode
     * @param stdClass $instance enrol instance
     * @return void
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {
        if ($instance->enrol !== 'authorizedotnet') {
            throw new coding_exception('Invalid enrol instance type!');
        }
        $context = context_course::instance($instance->courseid);
        if (has_capability('enrol/authorizedotnet:config', $context)) {
            $managelink = new moodle_url(
                '/enrol/editinstance.php',
                [
                    'courseid' => $instance->courseid,
                    'id' => $instance->id,
                    'type' => 'authorizedotnet',
                ]
            );
            $instancesnode->add($this->get_instance_name($instance), $managelink, navigation_node::TYPE_SETTING);
        }
    }

    /**
     * Action icons for the manage enrolments table.
     *
     * @param stdClass $instance enrol instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;
        if ($instance->enrol !== 'authorizedotnet') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid);
        $icons = [];
        if (has_capability('enrol/authorizedotnet:config', $context)) {
            $editlink = new moodle_url(
                '/enrol/editinstance.php',
                [
                    'courseid' => $instance->courseid,
                    'id' => $instance->id,
                    'type' => 'authorizedotnet',
                ]
            );
            $icons[] = $OUTPUT->action_icon(
                $editlink,
                new pix_icon(
                    't/edit',
                    get_string('edit'),
                    'core',
                    [
                        'class' => 'iconsmall',
                    ]
                )
            );
        }
        return $icons;
    }

    /**
     * Link to create a new instance on the course enrolment methods page.
     *
     * @param int $courseid course id
     * @return moodle_url|null
     */
    public function get_newinstance_link($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);
        if (!has_capability('moodle/course:enrolconfig', $context) || !has_capability('enrol/authorizedotnet:config', $context)) {
            return null;
        }
        return new moodle_url('/enrol/editinstance.php', ['courseid' => $courseid, 'type' => 'authorizedotnet']);
    }

    /**
     * Render the enrolment page for this instance.
     *
     * @param stdClass $instance enrol instance
     * @return string HTML
     */
    public function enrol_page_hook(stdClass $instance) {
        global $USER, $OUTPUT, $DB, $PAGE;

        if ($DB->record_exists('user_enrolments', ['userid' => $USER->id, 'enrolid' => $instance->id])) {
            return '';
        }

        if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
            $message = get_string('canntenrolearly', 'enrol_authorizedotnet', userdate($instance->enrolstartdate));
            $enrolpage = new enrol_page($instance, $this->get_instance_name($instance), $OUTPUT->notification($message, 'info'));
            return $OUTPUT->render($enrolpage);
        }

        if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
            $message = get_string('canntenrollate', 'enrol_authorizedotnet', userdate($instance->enrolenddate));
            $enrolpage = new enrol_page($instance, $this->get_instance_name($instance), $OUTPUT->notification($message, 'error'));
            return $OUTPUT->render($enrolpage);
        }

        $course = $DB->get_record('course', ['id' => $instance->courseid]);
        $context = context_course::instance($course->id);

        if ((float) $instance->cost <= 0) {
            $cost = (float) $this->get_config('cost');
        } else {
            $cost = (float) $instance->cost;
        }

        if (abs($cost) < 0.01) {
            $enrolpage = new enrol_page(
                $instance,
                $this->get_instance_name($instance),
                $OUTPUT->notification(get_string('nocost', 'enrol_authorizedotnet'))
            );
            return $OUTPUT->render($enrolpage);
        }

        $merchantcurrency = $this->get_merchant_currency();
        if ($merchantcurrency === '') {
            // The Authorize.net API call failed (bad credentials, sandbox/production mismatch,
            // network issue, etc.) - see the Moodle log for the underlying reason. Showing the
            // payment widget with no currency would just present a broken-looking checkout.
            $enrolpage = new enrol_page(
                $instance,
                $this->get_instance_name($instance),
                $OUTPUT->notification(get_string('paymentunavailable', 'enrol_authorizedotnet'), 'error')
            );
            return $OUTPUT->render($enrolpage);
        }

        $name = $this->get_instance_name($instance);
        $localisedcost = format_float($cost, 2, true);

        $templatedata = [
            'currency' => $merchantcurrency,
            'cost' => $localisedcost,
            'coursename' => format_string($course->fullname, true, ['context' => $context]),
            'instanceid' => $instance->id,
        ];

        $body = $OUTPUT->render_from_template('enrol_authorizedotnet/enrol_page', $templatedata);

        $PAGE->requires->js_call_amd('enrol_authorizedotnet/payment', 'authorizeNetPayment', [$instance->id, $USER->id]);

        $enrolpage = new enrol_page($instance, $name, $body);
        return $OUTPUT->render($enrolpage);
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
     * Add elements to the instance edit form.
     *
     * @param stdClass $instance enrol instance (or defaults when creating)
     * @param MoodleQuickForm $mform form
     * @param context $context course context
     * @return void
     */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context) {
        global $OUTPUT;

        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));
        $mform->setType('name', PARAM_TEXT);

        $options = [
            ENROL_INSTANCE_ENABLED  => get_string('yes'),
            ENROL_INSTANCE_DISABLED => get_string('no'),
        ];
        $mform->addElement('select', 'status', get_string('status', 'enrol_authorizedotnet'), $options);
        $mform->setDefault('status', $this->get_config('status'));

        $mform->addElement('text', 'cost', get_string('cost', 'enrol_authorizedotnet'), ['size' => 4]);
        $mform->setType('cost', PARAM_RAW); // Use unformat_float to get real value.
        $mform->setDefault('cost', format_float($this->get_config('cost'), 2, true));

        $merchantcurrency = $this->get_merchant_currency();
        if ($merchantcurrency === '') {
            $mform->addElement(
                'static',
                'currency_display',
                get_string('currency', 'enrol_authorizedotnet'),
                $OUTPUT->notification(get_string('currencyunavailable', 'enrol_authorizedotnet'), 'error')
            );
        } else {
            $mform->addElement('static', 'currency_display', get_string('currency', 'enrol_authorizedotnet'), $merchantcurrency);

            $mform->addElement(
                'static',
                'currencywarning',
                '',
                get_string('currencycannotchange', 'enrol_authorizedotnet', $merchantcurrency)
            );
        }

        if ($instance->id) {
            $roles = get_default_enrol_roles($context, $instance->roleid);
        } else {
            $roles = get_default_enrol_roles($context, $this->get_config('roleid'));
        }
        $mform->addElement('select', 'roleid', get_string('assignrole', 'enrol_authorizedotnet'), $roles);
        $mform->setDefault('roleid', $this->get_config('roleid'));

        $mform->addElement(
            'duration',
            'enrolperiod',
            get_string('enrolperiod', 'enrol_authorizedotnet'),
            ['optional' => true, 'defaultunit' => 86400]
        );
        $mform->setDefault('enrolperiod', $this->get_config('enrolperiod'));
        $mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_authorizedotnet');

        $mform->addElement(
            'date_time_selector',
            'enrolstartdate',
            get_string('enrolstartdate', 'enrol_authorizedotnet'),
            ['optional' => true]
        );
        $mform->setDefault('enrolstartdate', 0);
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_authorizedotnet');

        $mform->addElement(
            'date_time_selector',
            'enrolenddate',
            get_string('enrolenddate', 'enrol_authorizedotnet'),
            ['optional' => true]
        );
        $mform->setDefault('enrolenddate', 0);
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_authorizedotnet');

        if (enrol_accessing_via_instance($instance)) {
            $mform->addElement(
                'static',
                'selfwarn',
                get_string('instanceeditselfwarning', 'core_enrol'),
                get_string('instanceeditselfwarningtext', 'core_enrol')
            );
        }
    }

    /**
     * Validate the instance edit form.
     *
     * @param array $data form data
     * @param array $files files
     * @param stdClass $instance enrol instance
     * @param context $context course context
     * @return array errors
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        unset($files, $instance, $context);
        $errors = [];

        if (!empty($data['enrolenddate']) && $data['enrolenddate'] < $data['enrolstartdate']) {
            $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_authorizedotnet');
        }

        $cost = str_replace(get_string('decsep', 'langconfig'), '.', $data['cost']);
        if (!is_numeric($cost)) {
            $errors['cost'] = get_string('costerror', 'enrol_authorizedotnet');
        }
        return $errors;
    }

    /**
     * Restore an enrolment instance during course restore.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data instance data
     * @param stdClass $course course record
     * @param int $oldid old instance id
     * @return void
     */
    public function restore_instance($step, stdClass $data, $course, $oldid) {
        global $DB;
        if (!$step->get_task()->get_target() == backup::TARGET_NEW_COURSE) {
            if (
                $instances = $DB->get_records(
                    'enrol',
                    [
                    'courseid'   => $data->courseid,
                    'enrol'      => $this->get_name(),
                    'roleid'     => $data->roleid,
                    'cost'       => $data->cost,
                    'currency'   => $data->currency,
                    ],
                    'id'
                )
            ) {
                $instance = reset($instances);
                $instanceid = $instance->id;
            }
        }
        $instanceid = $this->add_instance($course, (array) $data);
        $step->set_mapping('enrol', $oldid, $instanceid);
    }

    /**
     * Restore a user enrolment record.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data user enrolment data
     * @param stdClass $instance enrol instance
     * @param int $userid user id
     * @param int $oldinstancestatus previous instance status
     * @return void
     */
    public function restore_user_enrolment($step, $data, $instance, $userid, $oldinstancestatus) {
        unset($step, $oldinstancestatus);
        $this->enrol_user($instance, $userid, null, $data->timestart, $data->timeend, $data->status);
    }

    /**
     * Actions available for a user enrolment row.
     *
     * @param course_enrolment_manager $manager
     * @param stdClass $ue user enrolment
     * @return array of user_enrolment_action
     */
    public function get_user_enrolment_actions($manager, $ue) {
        return enrolment_action_builder::build($this, $manager, $ue);
    }

    /**
     * Cron task to process enrolment expirations.
     *
     * @return void
     */
    public function cron() {
        $trace = new text_progress_trace();
        $this->process_expirations($trace);
    }

    /**
     * Whether the current user can delete this instance.
     *
     * @param stdClass $instance enrol instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/authorizedotnet:config', $context);
    }

    /**
     * Whether the current user can hide/show this instance.
     *
     * @param stdClass $instance enrol instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/authorizedotnet:config', $context);
    }
}
