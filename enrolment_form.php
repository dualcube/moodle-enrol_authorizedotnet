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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Authorize.net enrolment plugin - enrolment form .
 * @package    enrol_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2021 DualCube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_authorizedotnet_form extends moodleform {
    protected $instance;
    protected $localisedcost;

    // By defaulting $customdata to null, the constructor can still be invoked without providing any custom data.
    /**
     * enrol_authorizedotnet_form class constructor
     * @param  $action
     * @param $customdata
     */
    public function __construct($action = null, $customdata = null) {
        $this->instance = isset($customdata['instance']) ? $customdata['instance'] : null;
        $this->localisedcost = isset($customdata['localisedcost']) ? $customdata['localisedcost'] : null;
        parent::__construct($action);
    }

    /**
     * defination of form
     */
    public function definition() {
        global $CFG, $DB, $USER;
        $mform = $this->_form;
        $instance = $this->instance;
        $localisedcost = $this->localisedcost;
        $user = $DB->get_record('user', ['id' => $USER->id]);
        // Image of authorization.net.
        $mform->addElement('html', '<div class = "authorize-imgwrap"><img src="'
        . $CFG->wwwroot . '/enrol/authorizedotnet/pix/authorize-net-logo.png"></div>');
        // Pay Now image.
        $mform->addElement('html', '<div class="authorize-card-img"><img src="' . $CFG->wwwroot . '/enrol/authorizedotnet/pix/paynow.png"></div>');
        // Payment information header.
        $mform->addElement('html', '<div class="authorize-payment-header"><h1>'. get_string('paymentinfo', 'enrol_authorizedotnet').'</h1></div>');
        // Order info.
        $mform->addElement('html', '<div class="authorize-total-order-amount-section"> <h6>'. get_string('orderinfo', 'enrol_authorizedotnet').'</h6><div>'.$instance->currency.''.$localisedcost.'</div> </div>');
        // Payment details header.
        $mform->addElement('html', '<div><h6>'.get_string('paymentinfo', 'enrol_authorizedotnet').'</h6></div>');
        // Card number.
        $mform->addElement('text', 'cardnumber', get_string('cardnumber', 'enrol_authorizedotnet'),
        ['size' => '20', 'maxlength' => '16', 'placeholder' => get_string('cardnumberplaceholder', 'enrol_authorizedotnet'),'class'=>'inputfeild']);
        $mform->setType('cardnumber', PARAM_INT);
        $mform->addRule('cardnumber', get_string('required'), 'required', null, 'client');
        // Expiry Month.
        $mform->addElement('text', 'expmonth', get_string('expmonth', 'enrol_authorizedotnet'),
        ['size' => '20', 'maxlength' => '2', 'placeholder' => get_string('expmonthplaceholder', 'enrol_authorizedotnet'),'class'=>'inputfeild']);
        $mform->setType('expmonth', PARAM_INT);
        $mform->addRule('expmonth', get_string('required'), 'required', null, 'client');
        // Expiry date (year).
        $mform->addElement('text', 'expyear', get_string('expyear', 'enrol_authorizedotnet'),
        ['size' => '20', 'maxlength' => '4', 'placeholder' => get_string('expyearplaceholder', 'enrol_authorizedotnet'),'class'=>'inputfeild']);
        $mform->setType('expyear', PARAM_INT);
        $mform->addRule('expyear', get_string('required'), 'required', null, 'client');
        // Card code (CVV).
        $mform->addElement('text', 'cardcode', get_string('cardcode', 'enrol_authorizedotnet'),
        ['size' => '20', 'maxlength' => '3', 'placeholder' => get_string('cardcodeplaceholder', 'enrol_authorizedotnet'),'class'=>'inputfeild']);
        $mform->setType('cardcode', PARAM_TEXT);
        $mform->addRule('cardcode', get_string('required'), 'required', null, 'client');
        // Billing information header.
        $mform->addElement('html', '<div><h6>'.get_string('billinginfo', "enrol_authorizedotnet").'</h6></div>', );
        // First name.
        $mform->addElement('text', 'firstname', get_string('firstname', 'enrol_authorizedotnet'),
        ['size' => '20', 'placeholder' => get_string('firstname', 'enrol_authorizedotnet'),'class'=>'inputfeild']);
        $mform->setType('firstname', PARAM_TEXT);
        $mform->addRule('firstname', get_string('required'), 'required', null, 'client');
        // Last name.
        $mform->addElement('text', 'lastname', get_string('lastname', 'enrol_authorizedotnet'),
        ['size' => '20', 'placeholder' => get_string('lastname', 'enrol_authorizedotnet'),'class'=>'inputfeild']);
        $mform->setType('lastname', PARAM_TEXT);
        $mform->addRule('lastname', get_string('required'), 'required', null, 'client');
        // Email.
        $mform->addElement('text', 'email', get_string('email', 'enrol_authorizedotnet'),
        ['size' => '20', 'placeholder' => get_string('emailplaceholder', 'enrol_authorizedotnet'),'class'=>'inputfeild']);
        $mform->setType('email', PARAM_TEXT);
        $mform->addRule('email', get_string('required'), 'required', null, 'client');
        // phone.
        $mform->addElement('text', 'phone', get_string('phone', 'enrol_authorizedotnet'),
        ['size' => '20', 'placeholder' => get_string('phoneplaceholder', 'enrol_authorizedotnet'),'class'=>'inputfeild']);
        $mform->setType('phone', PARAM_TEXT);
        // Address.
        $mform->addElement('text', 'address', get_string('address', 'enrol_authorizedotnet'),
        ['size' => '20', 'placeholder' => get_string('addressplaceholder', 'enrol_authorizedotnet'),'class'=>'inputfeild']);
        $mform->setType('address', PARAM_TEXT);
        $mform->addRule('address', get_string('required'), 'required', null, 'client');
        // City.
        $mform->addElement('text', 'city', get_string('city', 'enrol_authorizedotnet'),
        ['size' => '20', 'placeholder' => get_string('cityplaceholder', 'enrol_authorizedotnet'),'class'=>'inputfeild']);
        $mform->setType('city', PARAM_TEXT);
        $mform->addRule('city', get_string('required'), 'required', null, 'client');
        // State.
        $mform->addElement('text', 'state', get_string('state', 'enrol_authorizedotnet'),
        ['size' => '20', 'placeholder' => get_string('stateplaceholder', 'enrol_authorizedotnet'),'class'=>'inputfeild']);
        $mform->setType('state', PARAM_TEXT);
        $mform->addRule('state', get_string('required'), 'required', null, 'client');

        // ZIP code.
        $mform->addElement('text', 'zip', get_string('ZIP', 'enrol_authorizedotnet'),
        ['size' => '20', 'placeholder' => get_string('ZIPplaceholder', 'enrol_authorizedotnet'),'class'=>'inputfeild']);
        $mform->setType('zip', PARAM_INT);
        $mform->addRule('zip', get_string('required'), 'required', null, 'client');

        // Country.
        $mform->addElement('select', 'country', get_string('country', 'enrol_authorizedotnet'), [
            'notselected' => get_string('notselected', 'enrol_authorizedotnet'),
            'United States' => get_string('country_us', 'enrol_authorizedotnet'),
            'Australia' => get_string('country_australia', 'enrol_authorizedotnet'),
            'Canada' => get_string('country_canada', 'enrol_authorizedotnet'),
        ],['class'=>'inputfeild']);
        $mform->addRule('country', get_string('required'), 'required', null, 'client');
        $mform->addElement('html', '<div id="payment_error"></div>');
        // This id shoud be givven and set default to the courseid.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        // Submit button.
        $mform->addElement('submit', 'final-payment-button', get_string('pay', 'enrol_authorizedotnet'),['class'=>'authorize-submit-button']);
        // Addidng default value from db/ user.
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
    
    /**
     *  Custom validation should be added here.
     */
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
