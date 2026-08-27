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
 * Web services for Authorize.Net enrolment plugin.
 *
 * @package    enrol_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2019 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$services = [
    'moodle_enrol_authorizedotnet' => [
        'functions' => [
            'moodle_authorizedotnet_get_config_for_js',
            'moodle_authorizedotnet_process_payment',
        ],
        'requiredcapability' => '',
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'enrolauthorizedotnet',
    ],
];

$functions = [
    'moodle_authorizedotnet_get_config_for_js' => [
        'classname'   => 'enrol_authorizedotnet_externallib',
        'methodname'  => 'get_config_for_js',
        'classpath'   => 'enrol/authorizedotnet/externallib.php',
        'description' => 'Get the configuration necessary for the client-side JavaScript.',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'moodle_authorizedotnet_process_payment' => [
        'classname'   => 'enrol_authorizedotnet_externallib',
        'methodname'  => 'process_payment',
        'classpath'   => 'enrol/authorizedotnet/externallib.php',
        'description' => 'Process a payment with opaque data from Authorize.Net.',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
];
