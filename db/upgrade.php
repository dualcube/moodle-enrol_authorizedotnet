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
 * Upgrade script for the authorizedotnet enrolment plugin.
 *
 * @package    enrol_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2021 DualCube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * Upgrade steps for enrol_authorizedotnet.
  *
  * This function is called during the upgrade process when the plugin
  * version in version.php is higher than the one stored in the database.
  *
  * @param int $oldversion The version we are upgrading from
  * @return bool Always true
  */
function xmldb_enrol_authorizedotnet_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // The version number should be greater than the last version in install.xml
    // and any previous upgrade scripts. We will use a version after the one you provided.
    if ($oldversion < 2025090400) {
        // Define table enrol_authorizedotnet to be modified.
        $table = new xmldb_table('enrol_authorizedotnet');

        // Drop redundant fields that are not used with the new Accept.js API.
        $fieldstodrop = [
            'tax',
            'duty',
            'method',
            'account_number',
            'card_type',
            'fax',
            'state',
        ];

        foreach ($fieldstodrop as $fieldname) {
            if ($dbman->field_exists($table, $fieldname)) {
                $dbman->drop_field($table, new xmldb_field($fieldname));
            }
        }

        $field = new xmldb_field('response_code', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_precision($table, $field);
        }

        $field = new xmldb_field('response_reason_code', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_precision($table, $field);
        }

        $field = new xmldb_field('auth_code', XMLDB_TYPE_CHAR, '30', null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_precision($table, $field);
        }

        $field = new xmldb_field('payment_status', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_precision($table, $field);
        }
        // Fields to rename (remove underscores).
        $fieldstorename = [
            'item_name' => ['itemname', XMLDB_TYPE_CHAR, '255', null, null, null, null],
            'payment_status' => ['paymentstatus', XMLDB_TYPE_CHAR, '255', null, null, null, null],
            'response_code' => ['responsecode', XMLDB_TYPE_INTEGER, '10', null, null, null, null],
            'response_reason_code' => ['responsereasoncode', XMLDB_TYPE_INTEGER, '10', null, null, null, null],
            'response_reason_text' => ['responsereasontext', XMLDB_TYPE_CHAR, '255', null, null, null, null],
            'auth_code' => ['authcode', XMLDB_TYPE_CHAR, '30', null, null, null, null],
            'trans_id' => ['transid', XMLDB_TYPE_CHAR, '255', null, null, null, null],
            'invoice_num' => ['invoicenum', XMLDB_TYPE_CHAR, '255', null, null, null, null],
            'test_request' => ['testrequest', XMLDB_TYPE_INTEGER, '1', null, null, null, null],
            'first_name' => ['firstname', XMLDB_TYPE_CHAR, '255', null, null, null, null],
            'last_name' => ['lastname', XMLDB_TYPE_CHAR, '255', null, null, null, null],
            'auth_json' => ['authjson', XMLDB_TYPE_TEXT, null, null, null, null, null],
        ];

        foreach ($fieldstorename as $oldname => [$newname, $type, $length, $decimals, $notnull, $sequence, $default]) {
            $field = new xmldb_field($oldname, $type, $length, $decimals, $notnull, $sequence, $default);
            if ($dbman->field_exists($table, $field)) {
                $dbman->rename_field($table, $field, $newname);
            }
        }

        // Carry over the public client key from the old config name used before
        // the switch to the Accept.js-based checkout flow.
        $oldclientkey = get_config('enrol_authorizedotnet', 'clientkey');
        if ($oldclientkey !== false) {
            set_config('publicclientkey', $oldclientkey, 'enrol_authorizedotnet');
            unset_config('clientkey', 'enrol_authorizedotnet');
        }

        // Update plugin version.
        upgrade_plugin_savepoint(true, 2025090400, 'enrol', 'authorizedotnet');
    }

    return true;
}
