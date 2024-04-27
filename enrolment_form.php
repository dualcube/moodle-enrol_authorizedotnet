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
defined('MOODLE_INTERNAL') || die();

global $PAGE;
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
  width: 30%;
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


<?php
/**
 * Authorize.net enrolment plugin - enrolment form.
 *
 * @package    enrol_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2021 DualCube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_authorizedotnet_form extends moodleform {
    protected $instance;
    protected $localisedcost;
    // By defaulting $customdata to null, the constructor can still be invoked without providing any custom data.
    public function __construct($action = null, $customdata = null) {
        $this->instance = isset($customdata['instance']) ? $customdata['instance'] : null;
        $this->localisedcost = isset($customdata['localisedcost']) ? $customdata['localisedcost'] : null;
        parent::__construct($action);
    }
    public function definition() {
        global $CFG, $DB, $USER, $PAGE;
        $mform = $this->_form;
        $instance = $this->instance;
        $localisedcost = $this->localisedcost;
        $user = $DB->get_record('user', array('id' => $USER->id));
        // image of authorization.net.
        $mform->addElement('html', '<div class = "authorize-img-wrap"><div class = "authorize-img"><img src="'
        . $CFG->wwwroot . '/enrol/authorizedotnet/pix/authorize-net-logo.png"></div></div>');
        // Pay Now image.
        $mform->addElement('html', '<div class="authorize-card-img"><img src="' . $CFG->wwwroot . '/enrol/authorizedotnet/pix/paynow.png"></div>');
        // Payment information header.
        $mform->addElement('header', 'payment_header', get_string('paymentinfo', 'enrol_authorizedotnet'));
        // Order info.
        $mform->addElement('html', '<div> <h6>'. get_string('orderinfo', 'enrol_authorizedotnet').'</h6><div>'.$instance->currency.''.$localisedcost.'</div> </div>');
        // Payment details header.
        $mform->addElement('html', '<div><h6>'.get_string('paymentinfo', 'enrol_authorizedotnet').'</h6></div>');
        // Card number.
        $mform->addElement('text', 'cardnumber', get_string('cardnumber', 'enrol_authorizedotnet'),
        array('size' => '20', 'maxlength' => '16', 'placeholder' => get_string('cardnumberplaceholder', 'enrol_authorizedotnet')));
        $mform->setType('cardnumber', PARAM_INT);
        $mform->addRule('cardnumber', get_string('required'), 'required', null, 'client');
        // Expiry Month.
        $mform->addElement('text', 'expmonth', get_string('expmonth', 'enrol_authorizedotnet'),
        array('size' => '20', 'maxlength' => '2', 'placeholder' => get_string('expmonthplaceholder', 'enrol_authorizedotnet')));
        $mform->setType('expmonth', PARAM_INT);
        $mform->addRule('expmonth', get_string('required'), 'required', null, 'client');
        // Expiry date (year).
        $mform->addElement('text', 'expyear', get_string('expyear', 'enrol_authorizedotnet'), 
        array('size' => '20', 'maxlength' => '4', 'placeholder' => get_string('expyearplaceholder', 'enrol_authorizedotnet')));
        $mform->setType('expyear', PARAM_INT);
        $mform->addRule('expyear', get_string('required'), 'required', null, 'client');
        // Card code (CVV).
        $mform->addElement('text', 'cardcode', get_string('cardcode', 'enrol_authorizedotnet'),
        array('size' => '20', 'maxlength' => '3', 'placeholder' => get_string('cardcodeplaceholder', 'enrol_authorizedotnet')));
        $mform->setType('cardcode', PARAM_INT);
        $mform->addRule('cardcode', get_string('required'), 'required', null, 'client');
        // Billing information header.
        $mform->addElement('html', '<div><h6>'.get_string('billinginfo', "enrol_authorizedotnet").'</h6></div>', );
        // First name.
        $mform->addElement('text', 'firstname', get_string('firstname', 'enrol_authorizedotnet'),
        array('size' => '20', 'placeholder' => get_string('firstname', 'enrol_authorizedotnet')));
        $mform->setType('firstname', PARAM_TEXT);
        $mform->addRule('firstname', get_string('required'), 'required', null, 'client');
        // Last name.
        $mform->addElement('text', 'lastname', get_string('lastname', 'enrol_authorizedotnet'),
        array('size' => '20', 'placeholder' => get_string('lastname', 'enrol_authorizedotnet')));
        $mform->setType('lastname', PARAM_TEXT);
        $mform->addRule('lastname', get_string('required'), 'required', null, 'client');
        // Email.
        $mform->addElement('text', 'email', get_string('email', 'enrol_authorizedotnet'),
        array('size' => '20', 'placeholder' => get_string('emailplaceholder', 'enrol_authorizedotnet')));
        $mform->setType('email', PARAM_TEXT);
        $mform->addRule('email', get_string('required'), 'required', null, 'client');
        // phone.
        $mform->addElement('text', 'phone', get_string('phone', 'enrol_authorizedotnet'),
        array('size' => '20', 'placeholder' => get_string('phoneplaceholder', 'enrol_authorizedotnet')));
        $mform->setType('phone', PARAM_TEXT);
        // Address.
        $mform->addElement('text', 'address', get_string('address', 'enrol_authorizedotnet'),
        array('size' => '20', 'placeholder' => get_string('addressplaceholder', 'enrol_authorizedotnet')));
        $mform->setType('address', PARAM_TEXT);
        $mform->addRule('address', get_string('required'), 'required', null, 'client');
        // city.
        $mform->addElement('text', 'city', get_string('city', 'enrol_authorizedotnet'),
        array('size' => '20', 'placeholder' => get_string('cityplaceholder', 'enrol_authorizedotnet')));
        $mform->setType('city', PARAM_TEXT);
        $mform->addRule('city', get_string('required'), 'required', null, 'client');
        // state.
        $mform->addElement('text', 'state', get_string('state', 'enrol_authorizedotnet'),
        array('size' => '20', 'placeholder' => get_string('stateplaceholder', 'enrol_authorizedotnet')));
        $mform->setType('state', PARAM_TEXT);
        $mform->addRule('state', get_string('required'), 'required', null, 'client');

        // ZIP code.
        $mform->addElement('text', 'zip', get_string('ZIP', 'enrol_authorizedotnet'),
        array('size' => '20', 'placeholder' => get_string('ZIPplaceholder', 'enrol_authorizedotnet')));
        $mform->setType('zip', PARAM_INT);
        $mform->addRule('zip', get_string('required'), 'required', null, 'client');

        // country.
        $mform->addElement('select', 'country', get_string('country', 'enrol_authorizedotnet'), array(
            'notselected' => get_string('notselected', 'enrol_authorizedotnet'),
            'United States' => get_string('country_us', 'enrol_authorizedotnet'),
            'Australia' => get_string('country_australia', 'enrol_authorizedotnet'),
            'Canada' => get_string('country_canada', 'enrol_authorizedotnet'),
        ));
        $mform->addRule('country', get_string('required'), 'required', null, 'client');
        $mform->addElement('html', '<div id="payment_error"></div>');
        // this id shoud be givven and set default to the courseid.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        // Submit button.
        $mform->addElement('submit', 'final-payment-button', get_string('pay', 'enrol_authorizedotnet'));
        // addidng default value from db/ user.
        if ( $user) {
            $mform->setDefault('firstname', $user->firstname);
            $mform->setDefault('lastname', $user->lastname);
            $mform->setDefault('email', $user->email);
            $mform->setDefault('phone', $user->phone1);
            $mform->setDefault('city', $user->city);
            $mform->setDefault('address', $user->address);
            $mform->setDefault('address', $user->country);
            $mform->setDefault('id', $instance->courseid);
        }
    }
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        // Validate form fields if needed
        if (empty($data['cardnumber'])) {
            $errors['cardnumber'] = get_string('required', 'enrol_authorizedotnet');
        }

        if (empty($data['expmonth'])) {
            $errors['exp-month'] = get_string('required', 'enrol_authorizedotnet');
        }

        if (empty($data['expyear'])) {
            $errors['expyear'] = get_string('required', 'enrol_authorizedotnet');
        }

        if (empty($data['cardcode'])) {
            $errors['cardCode'] = get_string('required', 'enrol_authorizedotnet');
        }

        if (empty($data['firstname'])) {
            $errors['firstname'] = get_string('required', 'enrol_authorizedotnet');
        }

        if (empty($data['lastname'])) {
            $errors['lastname'] = get_string('required', 'enrol_authorizedotnet');
        }

        if (empty($data['address'])) {
            $errors['address'] = get_string('required', 'enrol_authorizedotnet');
        }

        if (empty($data['zip'])) {
            $errors['zip'] = get_string('required', 'enrol_authorizedotnet');
        }
        if ($data['country'] == 'notselected') {
            $errors['country'] = get_string('required', 'enrol_authorizedotnet');
        }
        return $errors;
    }
}
