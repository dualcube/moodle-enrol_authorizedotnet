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
 * External function: get the config needed for the client-side payment JS.
 *
 * @package    enrol_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2026 DualCube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_authorizedotnet\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;

/**
 * Returns configuration data needed for client-side JS.
 *
 * @package    enrol_authorizedotnet
 * @category   external
 */
class get_payment_config extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'Enrolment instance ID'),
        ]);
    }

    /**
     * Returns configuration data needed for client-side JS.
     *
     * @param int $instanceid Enrolment instance ID
     * @return array
     */
    public static function execute(int $instanceid): array {
        $params = self::validate_parameters(self::execute_parameters(), ['instanceid' => $instanceid]);
        enrol_instance_loader::require_enrol_instance($params['instanceid']);

        $plugin = enrol_get_plugin('authorizedotnet');

        return [
            'apiloginid' => $plugin->get_config('loginid'),
            'publicclientkey' => $plugin->get_config('publicclientkey'),
            'environment' => $plugin->get_config('checkproductionmode') ? 'sandbox' : 'production',
        ];
    }

    /**
     * Returns description of method return value.
     *
     * @return external_function_parameters
     */
    public static function execute_returns(): external_function_parameters {
        return new external_function_parameters([
            'apiloginid' => new external_value(PARAM_RAW, 'The API login ID for the gateway.'),
            'publicclientkey' => new external_value(PARAM_RAW, 'The public client key for the gateway.'),
            'environment' => new external_value(PARAM_RAW, 'The environment (sandbox or production).'),
        ]);
    }
}
