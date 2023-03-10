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
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2021 DualCube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $PAGE;
?>
<div class="payment-wrap">
  <div class="authorize-img-wrap">
    <div class="authorize-img">
      <img src="<?php echo $CFG->wwwroot; ?>/enrol/authorizedotnet/pix/authorize-net-logo.png">
    </div>
      <div class="authorize-card-img"><img src="<?php echo $CFG->wwwroot; ?>/enrol/authorizedotnet/pix/paynow.png"></div>
  </div>
  <div class="order-info">
    <b class='heading-athorzed'><?php echo get_string('orderinfo', 'enrol_authorizedotnet'); ?></b>
    <div class="form-group-authorized-net">
      <label for="cost"><b><?php echo get_string('cost', 'enrol_authorizedotnet'); ?></b></label>
      <div class="authorized-net-input-wrap">
    <p><b><?php echo " {$instance->currency} {$localisedcost}"; ?></b></p>
      </div>
    </div>
  </div>
  <div class='payment-info-authorized'>
    <b class='heading-athorzed'><?php echo get_string('paymentinfo', 'enrol_authorizedotnet'); ?></b>
      <!-- card number -->
    <div class="form-group-authorized-net">
      <label for="cardnumber"><?php echo get_string('cardnumber', 'enrol_authorizedotnet'); ?></label>
      <span class="requiredstar">*</span> 
      <div class="authorized-net-input-wrap">
      <input type="number" name="cardnumber" id="card-number" placeholder="<?php echo get_string('cardnumberplaceholder', 'enrol_authorizedotnet'); ?>"/><div><?php echo get_string('cardnumberreq', 'enrol_authorizedotnet'); ?></div>
      </div>
    </div>
    <!-- exp date -->
    <div class="form-group-authorized-net">
      <label for=""><?php echo get_string('expdate', 'enrol_authorizedotnet'); ?></label>
      <span class="requiredstar">*</span> 
      <div class="authorized-net-input-wrap exp-date">
        <input type="number" name="expMonth" id="exp-month" maxlength="2" placeholder="<?php echo get_string('expmonthplaceholder', 'enrol_authorizedotnet'); ?>"/>
        <input type="number" name="expYear" id="exp-year" maxlength="4" placeholder="<?php echo get_string('expyearplaceholder', 'enrol_authorizedotnet'); ?>"/><div>(mm yyyy)</div>
      </div>
    </div>
    <!-- card code -->
    <div class="form-group-authorized-net">
      <label for="cardcoder"><?php echo get_string('cardcode', 'enrol_authorizedotnet'); ?></label>
      <span class="requiredstar">*</span>
      <div class="authorized-net-input-wrap">
      <input type="number" name="cardCode" id="card-code" maxlength="4" placeholder="<?php echo get_string('cardcodeplaceholder', 'enrol_authorizedotnet'); ?>"/>
    <div> <a href="https://www.cvvnumber.com/"><?php echo get_string('whatscvv', 'enrol_authorizedotnet'); ?></a></div>
  </div>
    </div>
  </div>
  <div class='billing-info-athorized'>
    <b class='heading-athorzed'><?php echo get_string('billinginfo', 'enrol_authorizedotnet'); ?></b>
    <!-- first name -->
    <div class="form-group-authorized-net">
      <span class="requiredstar">*</span>
      <label for="First Name"><?php echo get_string('firstname', 'enrol_authorizedotnet'); ?></label>
      <input type="text" name="Firstname" id="firstname" placeholder="<?php echo get_string('firstname', 'enrol_authorizedotnet'); ?>">
    </div>
    <!-- last name -->
    <div class="form-group-authorized-net">
      <span class="requiredstar">*</span>
      <label for="Last Name"><?php echo get_string('lastname', 'enrol_authorizedotnet'); ?></label>
      <input type="text" name="Lastname" id="lastname" placeholder="<?php echo get_string('lastname', 'enrol_authorizedotnet'); ?>">
    </div>
    <!-- Address -->
    <div class="form-group-authorized-net">
      <span class="requiredstar">*</span>
      <label for="Address"><?php echo get_string('address', 'enrol_authorizedotnet'); ?></label>
      <input type="text" name="Address" id="address" placeholder="<?php echo get_string('addressplaceholder', 'enrol_authorizedotnet'); ?>">
    </div>
    <!-- zip -->
    <div class="form-group-authorized-net">
      <span class="requiredstar">*</span>
      <label for="ZIP Code"><?php echo get_string('ZIP', 'enrol_authorizedotnet'); ?></label>
      <input type="number" name="ZIPCode" id="zip" placeholder="<?php echo get_string('ZIPplaceholder', 'enrol_authorizedotnet'); ?>">
    </div>
  </div>
  <div class="auth-submit">
    <div class="loader"></div>
    <div id="payment_error"></div>
    <div id="error_massage"></div>
    <button type="button" id="final-payment-button"><?php echo get_string('pay', 'enrol_authorizedotnet'); ?></button>
  </div>
</div>
<?php
$PAGE->requires->js_call_amd('enrol_authorizedotnet/authorizedotnet_payments', 'authorizedotnet_payments', array($instance->courseid,  $USER->id, $instance->id, $cost));
?>
<style>
.payment-wrap {
    width: 85%;
    margin: auto;
    padding-top: 2rem;
}
 div#payment_error p {
    text-align: center;
}
.payment-info-authorized input {
    border-radius: 0.25rem;
    padding: 0.25rem 1rem;
    width: 100%;
}
.form-group-authorized-net label {
  width: 25%;
    text-align: right;
}
.authorized-net-input-wrap.exp-date input {
  width: 49%;
}
.authorized-net-input-wrap {
    width: 50%;
}
.authorize-img {
    width: 35%;
}
.authorize-img img {
    object-fit: cover;
    object-position: center;
    height: 100%;
    width: 100%;
}
.heading-athorzed {
    border-bottom: 0.06rem solid #eee;
    display: block;
    text-align: left;
    font-size: 1rem;
    padding: 0.5rem;
}
.authorize-img-wrap {
      display: flex;
    align-items: center;
    justify-content: space-between;
    flex-direction: column;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 0rem 0 3rem;
}
.authorize-card-img{
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-direction: column;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 1rem 0 0;
}
.billing-info-athorized input {
    border-radius: 0.25rem;
    padding: 0.25rem 1rem;
    width: 50%;
}
.requiredstar {
    position: absolute;
    left: 25%;
    top: 0;
    color:#ff0000;
}
.form-group-authorized-net {
    display: flex;
    position: relative;
    margin: 1.5rem 0;
    gap: 1.5rem;
}
.auth-submit #final-payment-button {
    color: #fff;
    background-color: #1177d1;
    border: 0;
    padding: 5px 32px;
    border-radius: 0.25rem;
    font-size: 20px;
    box-shadow: 0 0.125rem 0.25rem #645cff2e;
    width: 32%;
}
.auth-submit {
    text-align: center;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 0.06rem solid #eee;
}
.loader {
    margin: auto;
    border: 0.5rem solid #f3f3f3;
    border-radius: 50%;
    border-top: 0.5rem solid #3498db;
    width: 2.5rem;
    height: 2.5rem;
    -webkit-animation: spin 2s linear infinite;
    animation: spin 2s linear infinite;
}
input[type='number']::-webkit-inner-spin-button, 
input[type='number']::-webkit-outer-spin-button { 
    -webkit-appearance: none;    
    appearance: none;
}
@media only screen and (max-width: 700px) {
  .generalbo {
    width: auto;
}
.form-group-authorized-net label {
    text-align: left;
}
.authorized-net-input-wrap.exp-date input {
    width: 48%;
}
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