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
 * @copyright  2021 DualCube
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $PAGE;
$login_id = $this->get_config('loginid');
$transaction_key = $this->get_config('transactionkey');
$client_key = $this->get_config('clientkey');
$auth_mode = $this->get_config('checkproductionmode');
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
<div class="payment-wrap">
<div class="payment-left">
  <p><?php echo get_string('requires_payment', 'enrol_authorizedotnet'); ?></p>
  <p><b><?php echo $instancename; ?></b></p>
  <p><b><?php echo get_string("cost").": {$instance->currency} {$localisedcost}"; ?></b></p>


  <p>&nbsp;</p>
  <p><img alt="Authorize.net" src="<?php echo $CFG->wwwroot; ?>/enrol/authorizedotnet/pix/authorize-net-logo.jpg" /></p>
  <p>&nbsp;</p>


  <p><input type="button" id="open-creditcard-popup" class="popup"/></p>
</div>
<div class="popup payment-right">
    <div class="popuptext" id="net-pay-popup">
        <h3><?php echo get_string('make_payment', 'enrol_authorizedotnet'); ?></h3>
        <div id="payment_error"></div>
        <div id="card_form">
          <div class="form-group-authorized-net">
            <label for="card-number">Card Number</label>
            <input type="text" name="cardNumber" id="card-number" placeholder="<?php echo get_string('cardnumber', 'enrol_authorizedotnet'); ?>"/>
            </div>
             <div class="form-group-authorized-net">
            <label for="">Exp Date</label>
            <div class="authorized-net-input-wrap">
            <input type="text" name="expMonth" id="exp-month" placeholder="<?php echo get_string('expmonth', 'enrol_authorizedotnet'); ?>"/>
            <input type="text" name="expYear" id="exp-year" placeholder="<?php echo get_string('expyear', 'enrol_authorizedotnet'); ?>"/>
            </div>
            </div>
           
             <div class="form-group-authorized-net">
            <label for="card-coder">Card Code</label>
            <input type="text" name="cardCode" id="card-code" placeholder="<?php echo get_string('cardcode', 'enrol_authorizedotnet'); ?>"/>
            </div>
        </div>
        <div class="loader"></div>
        <button type="button" id="final-payment-button"><?php echo get_string('pay', 'enrol_authorizedotnet'); ?></button>
    </div>
  </div>
  </div>


<?php
$PAGE->requires->js_call_amd('enrol_authorizedotnet/authorizedotnet_payments', 'authorizedotnet_payments', array($client_key, $login_id, $amount, $instance->currency, $transaction_key, $instance->courseid, $USER->id, $USER->email, $instance->id, $context->id, $description, $invoice, $sequence, $timestamp, $auth_mode, $error_payment_text));
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
.payment-wrap {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 1rem;
}
.form-group-authorized-net {
    text-align: left;
    margin:1rem 0;
}
.form-group-authorized-net label {
    font-weight: 600;
    color: #050606;
}
.authorized-net-input-wrap {
    display: flex;
    gap: 0.5rem;
}
/* Popup container - can be anything you want */
.popup {
  display:block;
  cursor: pointer;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}
.payment-left {
    text-align: center;
    background: #0075ff;
    padding: 1rem;
    color: #fff;
    border-radius: 0.25rem;
    transition:0.2s all ease-in-out;
}
.payment-left:hover{
  box-shadow:2px 3px 20px 0 #00000036;
}
/* The actual popup */
.popup .popuptext {
    visibility: hidden;
    width: 330px;
    background-color: #ffc300;
    color: #181718;
    text-align: center;
    border-radius: 6px;
    padding: 1rem 1rem 0.5rem;
    height: 100%;
    display: grid;
    place-item:center;
    transition:0.2s all ease-in-out;
}
.popup .popuptext:hover{
  box-shadow:2px 3px 20px 0 #00000036;
}
/* Toggle this class - hide and show the popup */
.popup .show {
  visibility: visible;
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
/* button style */
button#final-payment-button {
    border: 0;
    padding: 0rem 1.5rem;
    margin: 0.5rem auto;
    border-radius: 0.25rem;
    background: #0075ff;
    color: #fff;
}
/* payment input style */
.popup .show div#card_form input {
    width: 100%;
    border: 0;
    min-height: 2rem;
    padding: 0.5rem;
    border-radius: 0.25rem;
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



