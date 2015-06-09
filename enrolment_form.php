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
 * Authorize.net enrolment plugin - enrolment form.
 *
 * @package    enrol_authorizedotnet
 * @copyright  2015 Dualcube, Moumita Ray, Parthajeet Chakraborty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$currentyear = date('y');
$currentmonth = date('m');
$msg = '';
if (isset($_POST['btn_pay'])) {
    $user = $DB->get_record('user', array('id' => $USER->id));
    $admin = $DB->get_record('user', array('id' => 2));
    $course = $DB->get_record('course', array('id' => $course->id));
    if ($this->get_config('loginid') == 1) {
        $posturl = "https://secure.authorize.net/gateway/transact.dll";
    } else {
        $posturl = "https://test.authorize.net/gateway/transact.dll";
    }
    $postvalues = array(
    "x_login" => $this->get_config('loginid'),
    "x_tran_key" => $this->get_config('transactionkey'),
    "x_version" => "3.1",
    "x_delim_data" => "TRUE",
    "x_delim_char" => "|",
    "x_relay_response" => "FALSE",
    "x_type" => "AUTH_CAPTURE",
    "x_method" => "CC",
    "x_card_num" => $_POST['card_number'],
    "x_exp_date" => $_POST['exp_month'].$_POST['exp_year'],
    "x_amount" => $_POST['amount'],
    "x_currency_code" => $this->get_config('currency'),
    "x_description" => $_POST['item_name'],
    "x_first_name" => $_POST['first_name'],
    "x_last_name" => $_POST['last_name'],
    "x_address" => $user->city.', '.$user->country,
    // Additional fields can be added here as outlined in the AIM integration.
    );

    $poststring = "";
    foreach ($postvalues as $key => $value) {
        $poststring .= "$key=" . urlencode( $value ) . "&";
    }
    $poststring = rtrim( $poststring, "& " );
    $request = curl_init($posturl);
    curl_setopt($request, CURLOPT_HEADER, 0);
    curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($request, CURLOPT_POSTFIELDS, $poststring);
    curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);
    $postresponse = curl_exec($request);
    curl_close ($request);
    $responsearray = explode($postvalues["x_delim_char"], $postresponse);
    $enrolauthorizedotnet = $userenrolments = $roleassignments = new stdClass();
    if ($responsearray[0] == 1) {
        /* Print trasaction success message */
        $msg = $responsearray[3];
        /* Inserting value to enrol_authorizedotnet table */
        $enrolauthorizedotnet->item_name = $responsearray[8];
        $enrolauthorizedotnet->userid = $USER->id;
        $enrolauthorizedotnet->courseid = $instance->courseid;
        $enrolauthorizedotnet->instanceid = $instance->id;
        $enrolauthorizedotnet->amount = $responsearray[9];
        $enrolauthorizedotnet->tax = 0;
        $enrolauthorizedotnet->payment_status = 'PAID';
        $enrolauthorizedotnet->pending_reason = '';
        $enrolauthorizedotnet->txn_id = $responsearray[4];
        $enrolauthorizedotnet->payment_type = $responsearray[10];
        $enrolauthorizedotnet->timeupdated = time();
        $ret1 = $DB->insert_record("enrol_authorizedotnet", $enrolauthorizedotnet, false);
        /* Inserting value to user_enrolments table */
        $userenrolments->status = 0;
        $userenrolments->enrolid = $instance->id;
        $userenrolments->userid = $USER->id;
        $userenrolments->timestart = time();
        $userenrolments->timeend = 0;
        $userenrolments->modifierid = 2;
        $userenrolments->timecreated = time();
        $userenrolments->timemodified = time();
        $ret2 = $DB->insert_record("user_enrolments", $userenrolments, false);
        /* Inserting value to role_assignments table */
        $roleassignments->roleid = 5;
        $roleassignments->contextid = $context->id;
        $roleassignments->userid = $USER->id;
        $roleassignments->timemodified = time();
        $roleassignments->modifierid = 2;
        $roleassignments->component = '';
        $roleassignments->itemid = 0;
        $roleassignments->sortorder = 0;
        $ret3 = $DB->insert_record('role_assignments', $roleassignments, false);
        if ($ret1 > 0 && $ret2 > 0 && $ret3 > 0) {
            $subject = 'Transaction Successfull';
            $messagehtml =
            '<p>You have successfully enrolled for '.$course->fullname.'.
            Your Transaction ID is : '.$responsearray[6].' and your Authorization Code : '.$responsearray[4].' </p>';
            $messagetext = html_to_text($messagehtml);
            email_to_user($user, $admin, $subject, $messagetext, $messagehtml, '', '', false);
            echo '<script type="text/javascript">
                window.location.href="'.$CFG->wwwroot.'/enrol/authorizedotnet/return.php?id='.$course->id.'";</script>';
            die;
        }
    } else {
        $msg = $responsearray[3];
    }
}
?>
<div align="center">
<p>This course requires a payment for entry.</p>
<p><b><?php echo $instancename; ?></b></p>
<p><b><?php echo get_string("cost").": USD {$localisedcost}"; ?></b></p>
<p>&nbsp;</p>
<p><img alt="Authorize.net" src="<?php echo $CFG->wwwroot; ?>/enrol/authorizedotnet/pix/authorize-net-logo.jpg" /></p>
<p>&nbsp;</p>
<p>
 <form name="adnpayment_frm" id="adnpayment_frm" method="post">
	<table width="50%" cellpadding="10" cellspacing="10">
		<tr><td colspan="2" style="color:#127ca5; font-size:14px; text-align:center;"><?php echo $msg; ?></td></tr>
		<tr>
			<td width="27%" align="right"><strong>Card Number : </strong></td>
			<td align="left"><input type="text" name="card_number" maxlength="16"></td>
		</tr>
		<tr>
			<td width="27%" align="right"><strong>Expiry Date : </strong></td>
			<td align="left">
				<select name="exp_month">
					<option value="01" <?php echo ($currentmonth == '01') ? 'selected' : ''; ?>>01</option>
					<option value="02" <?php echo ($currentmonth == '02') ? 'selected' : ''; ?>>02</option>
					<option value="03" <?php echo ($currentmonth == '03') ? 'selected' : ''; ?>>03</option>
					<option value="04" <?php echo ($currentmonth == '04') ? 'selected' : ''; ?>>04</option>
					<option value="05" <?php echo ($currentmonth == '05') ? 'selected' : ''; ?>>05</option>
					<option value="06" <?php echo ($currentmonth == '06') ? 'selected' : ''; ?>>06</option>
					<option value="07" <?php echo ($currentmonth == '07') ? 'selected' : ''; ?>>07</option>
					<option value="08" <?php echo ($currentmonth == '08') ? 'selected' : ''; ?>>08</option>
					<option value="09" <?php echo ($currentmonth == '09') ? 'selected' : ''; ?>>09</option>
					<option value="10" <?php echo ($currentmonth == '10') ? 'selected' : ''; ?>>10</option>
					<option value="11" <?php echo ($currentmonth == '11') ? 'selected' : ''; ?>>11</option>
					<option value="12" <?php echo ($currentmonth == '12') ? 'selected' : ''; ?>>12</option>
				</select>
				<select name="exp_year">
<?php
for ($i = $currentyear; $i <= $currentyear + 10; $i++) {
?>
					<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
<?php 
}
?>
				</select>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td><td align="left"><input type="submit" name="btn_pay" value="Payment via Authorize.net"></td>
		</tr>
	</table>
  <input type="hidden" name="item_name" value="<?php p($coursefullname) ?>" />
  <input type="hidden" name="quantity" value="1" />
  <input type="hidden" name="amount" value="<?php p($cost) ?>" />
  <input type="hidden" name="first_name" value="<?php p($userfirstname) ?>" />
  <input type="hidden" name="last_name" value="<?php p($userlastname) ?>" />
  <input type="hidden" name="address" value="<?php p($useraddress) ?>" />
  <input type="hidden" name="city" value="<?php p($usercity) ?>" />
  <input type="hidden" name="email" value="<?php p($USER->email) ?>" />
  <input type="hidden" name="country" value="<?php p($USER->country) ?>" />
 </form>
</p>
</div>
