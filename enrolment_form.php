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

$loginid = $this->get_config('loginid');
$transactionkey = $this->get_config('transactionkey');
$sigkey = $this->get_config('signatureKey');
if ( phpversion() >= '5.1.2' ) {
    $sigkey = hex2bin($sigkey);
}
else
{
    $sigkey = pack("H*" , $sigkey);
}

$amount = $cost;
$description = $coursefullname;
$label = "Pay Now";

if ($this->get_config('checkproductionmode') == 1) {
    $url = "https://secure.authorize.net/gateway/transact.dll";
    $testmode = "false";
} else {
    $url = "https://test.authorize.net/gateway/transact.dll";
    $testmode = "true";
}

$invoice = date('YmdHis');
$_SESSION['sequence'] = $sequence = rand(1, 1000);
$_SESSION['timestamp'] = $timestamp = time();

if ( phpversion() >= '5.1.2' ) {
    if ($this->get_config('checkproductionmode') == 1) {
    		/*
        $fingerprint = hash_hmac("md5"
                       , $loginid . "^" . $sequence . "^" . $timestamp . "^" . $amount . "^" . $instance->currency
                       , $transactionkey);*/
                       
				$fingerprint = hash_hmac("sha512", $loginid . "^" . $sequence . "^" . $timestamp . "^" . $amount . "^", $sigkey);
    } else {
        //$fingerprint = hash_hmac("md5", $loginid . "^" . $sequence . "^" . $timestamp . "^" . $amount . "^", $transactionkey);
        $fingerprint = hash_hmac("sha512", $loginid . "^" . $sequence . "^" . $timestamp . "^" . $amount . "^", $sigkey);
    }
} else {
    if ($this->get_config('checkproductionmode') == 1) {
        $fingerprint = bin2hex(mhash(MHASH_SHA512
                       , $loginid . "^" . $sequence . "^" . $timestamp . "^" . $amount . "^" . $instance->currency
                       , $sigkey));
    } else {
        $fingerprint = bin2hex(mhash(MHASH_SHA512
                       , $loginid . "^" . $sequence . "^" . $timestamp . "^" . $amount . "^"
                       , $sigkey));
    }
}
?>
<div align="center">
<p>This course requires a payment for entry.</p>
<p><b><?php echo $instancename; ?></b></p>
<p><b><?php echo get_string("cost").": {$instance->currency} {$localisedcost}"; ?></b></p>
<p>&nbsp;</p>
<p><img alt="Authorize.net" src="<?php echo $CFG->wwwroot; ?>/enrol/authorizedotnet/pix/authorize-net-logo.jpg" /></p>
<p>&nbsp;</p>
<p>
	<form method="post" action="<?php echo $url; ?>" >
		<input type="hidden" name="x_login" value="<?php echo $loginid; ?>" />
		<input type="hidden" name="x_amount" value="<?php echo $amount; ?>" />
<?php
if ($this->get_config('checkproductionmode') == 1) {
?>
		<input type="hidden" name="x_currency_code" value="<?php echo $instance->currency; ?>" />
<?php
}
?>
		<input type="hidden" name="x_cust_id" value="<?php echo $instance->courseid.'-'.$USER->id.'-'.$instance->id.'-'.$context->id; ?>">
		<input type="hidden" name="x_description" value="<?php echo $description; ?>" />
		<input type="hidden" name="x_invoice_num" value="<?php echo $invoice; ?>" />
		<input type="hidden" name="x_fp_sequence" value="<?php echo $sequence; ?>" />
		<input type="hidden" name="x_fp_timestamp" value="<?php echo $timestamp; ?>" />
		<input type="hidden" name="x_fp_hash" value="<?php echo $fingerprint; ?>" />
		<input type="hidden" name="x_test_request" value="<?php echo $testmode; ?>" />
		<input type="hidden" name="x_email_customer" value="true" >

	    <input type="hidden" name="x_relay_response" value="TRUE" >
	    <input type="hidden" name="x_relay_url" value="<?php echo $CFG->wwwroot; ?>/enrol/authorizedotnet/ipn.php" >

		<input type="hidden" name="x_show_form" value="PAYMENT_FORM" />
		<input type="submit" id="sub_button" value="" />
	</form>
</p>
</div>
<style type="text/css">
#sub_button{
  background: url("<?php echo $CFG->wwwroot; ?>/enrol/authorizedotnet/pix/paynow.png") no-repeat scroll 0 0 transparent;
  color: #000000;
  cursor: pointer;
  font-weight: bold;
  height: 20px;
  padding-bottom: 2px;
  width: 300px;
  height: 110px;
}
</style>