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
global $PAGE;
$loginid = $this->get_config('loginid');
$transactionkey = $this->get_config('transactionkey');
$clientkey = $this->get_config('clientkey');
$auth_modess = $this->get_config('checkproductionmode');
$amount = $cost;
$description = $coursefullname;
$invoice = date('YmdHis');
$sequence = rand(1, 1000);
$timestamp = time();
$error_payment_text = get_string('error_payment', 'enrol_authorizedotnet');
?>
<!-- Load the jQuery library from the Google CDN -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js">
</script>

<div align="center">
  <p><?php echo get_string('requires_payment', 'enrol_authorizedotnet'); ?></p>
  <p><b><?php echo $instancename; ?></b></p>
  <p><b><?php echo get_string("cost").": {$instance->currency} {$localisedcost}"; ?></b></p>

  <p>&nbsp;</p>
  <p><img alt="Authorize.net" src="<?php echo $CFG->wwwroot; ?>/enrol/authorizedotnet/pix/authorize-net-logo.jpg" /></p>
  <p>&nbsp;</p>
  <div class="popup">
    <div class="popuptext" id="net-pay-popup">
        <h3><?php echo get_string('make_payment', 'enrol_authorizedotnet'); ?></h3>
        <div id="payment_error"></div>
        <div id="card_form">
            <input type="text" name="cardNumber" id="cardNumber" placeholder="<?php echo get_string('cardnumber', 'enrol_authorizedotnet'); ?>"/> <br><br>
            <input type="text" name="expMonth" id="expMonth" placeholder="<?php echo get_string('expmonth', 'enrol_authorizedotnet'); ?>"/> <br><br>
            <input type="text" name="expYear" id="expYear" placeholder="<?php echo get_string('expyear', 'enrol_authorizedotnet'); ?>"/> <br><br>
            <input type="text" name="cardCode" id="cardCode" placeholder="<?php echo get_string('cardcode', 'enrol_authorizedotnet'); ?>"/> 
        </div>
        <div class="loader"></div>
        <button type="button" id="final-payment-button"><?php echo get_string('pay', 'enrol_authorizedotnet'); ?></button>
    </div>
  </div>
  <p><input type="button" id="open-creditcard-popup" class="popup"/></p>
</div>

<?php
$PAGE->requires->js_call_amd('enrol_authorizedotnet/authorizedotnet_payments', 'authorizedotnet_payments', array($clientkey, $loginid, $amount, $instance->currency, $transactionkey, $instance->courseid, $USER->id, $USER->email, $instance->id, $context->id, $description, $invoice, $sequence, $timestamp, $auth_modess, $error_payment_text));
?>

<style>
#open-creditcard-popup{
  background: url("<?php echo $CFG->wwwroot; ?>/enrol/authorizedotnet/pix/paynow.png") no-repeat scroll 0 0 transparent;
  color: #000000;
  cursor: pointer;
  font-weight: bold;
  height: 20px;
  padding-bottom: 2px;
  width: 300px;
  height: 110px;
}

/* Popup container - can be anything you want */
.popup {
  position: relative;
  display: inline-block;
  cursor: pointer;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

/* The actual popup */
.popup .popuptext {
  visibility: hidden;
  width: 330px;
    background-color: #bbb;
    color: #333;
    text-align: center;
    border-radius: 6px;
    padding: 8px 0;
    position: absolute;
    float: 1;
    float: right;
    bottom: 125%;
    left: 50%;
    height: fit-content;
    margin-left: -165px;
}

/* Popup arrow */
.popup .popuptext::after {
  content: "";
  position: absolute;
  top: 100%;
  left: 50%;
  margin-left: -5px;
  border-width: 5px;
  border-style: solid;
  border-color: #555 transparent transparent transparent;
}

/* Toggle this class - hide and show the popup */
.popup .show {
  visibility: visible;
  -webkit-animation: fadeIn 1s;
  animation: fadeIn 1s;
}

/* Add animation (fade in the popup) */
@-webkit-keyframes fadeIn {
  from {opacity: 0;} 
  to {opacity: 1;}
}

@keyframes fadeIn {
  from {opacity: 0;}
  to {opacity:1 ;}
}

.loader {
  border: 16px solid #f3f3f3;
  border-radius: 50%;
  border-top: 16px solid #3498db;
  width: 120px;
  height: 120px;
  -webkit-animation: spin 2s linear infinite; /* Safari */
  animation: spin 2s linear infinite;
}

/* Safari */
@-webkit-keyframes spin {
  0% { -webkit-transform: rotate(0deg); }
  100% { -webkit-transform: rotate(360deg); }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>