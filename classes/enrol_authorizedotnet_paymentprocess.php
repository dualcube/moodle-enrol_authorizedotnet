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
 * Authorize.net enrolment plugin version specification.
 *
 * @package    enrol_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2021 DualCube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once( "$CFG->dirroot/enrol/authorizedotnet/authorize-dot-net-sdk-php/autoload.php");
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
/**
 * class to apply payment process
 *
 * @package    enrol_authorizedotnet
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2021 DualCube (https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_authorizedotnet_payment_process {
    protected $user = null;
    protected $course = null;
    protected $context = null;
    protected $plugininstance = null;
    protected $plugin;
    protected $invoice;
    protected $description;
    protected $authmode;
    protected $paymnetenv;
    protected $refid;
    protected $formdata;
    /**
     * Process a payment for enrolling a user in a course using Authorize.net.
     * @param object $formdata An array containing form data submitted by the user.
     * @param int $courseid The ID of the course in which the user wants to enroll.
     * @param int $userid The ID of the user who is enrolling in the course.
     * @param int $instanceid The ID of the enrollment instance.
     * @return void
     */
    public function process_payment($formdata , $courseid , $userid , $instanceid) {
        if ($this->invalid_details_check($courseid , $userid , $instanceid)) {
            $this->formdata = $formdata;
            $this->plugin = enrol_get_plugin('authorizedotnet');
            $this->invoice = date('YmdHis');
            $this->description = $this->course->fullname;
            $this->authmode = $this->plugin->get_config('checkproductionmode');
            $this->paymnetenv = $this->authmode == 0 ? 'PRODUCTION' : 'SANDBOX';
            $this->refid = 'REF'.time();
            $merchantauthentication = $this->create_merchant_authentication();
            $order = $this->create_order();
            $transactionrequesttype = $this->create_transaction($order );
            $response = $this->complete_transaction($merchantauthentication , $transactionrequesttype);
            if (!$this->generate_error_messsage($response)) {
                $this->enrol_user($response);
            }
        }
    }

    /**
     * Check details of users and details for a payment for enrolling a user in a course using Authorize.net..
     * @param int $courseid The ID of the course in which the user wants to enroll.
     * @param int $userid The ID of the user who is enrolling in the course.
     * @param int $instanceid The ID of the enrollment instance.
     * @return boolean
     */
    public function invalid_details_check($courseid , $userid , $instanceid) {
        global $DB;
        if (! $this->user = $DB->get_record("user" , ["id" => $userid])) {
            throw new moodle_exception(get_string('invaliduserid' , 'enrol_authorizedotnet'));
        }
        if (! $this->course = $DB->get_record("course" , ["id" => $courseid])) {
            throw new moodle_exception(get_string('invalidcourseid' , 'enrol_authorizedotnet'));
        }
        if (! $this->context = context_course::instance($courseid , IGNORE_MISSING)) {
            throw new moodle_exception(get_string('invalidcontextid' , 'enrol_authorizedotnet'));
        }
        if (! $this->plugininstance = $DB->get_record("enrol" , ["id" => $instanceid , "status" => 0])) {
            throw new moodle_exception(get_string('invalidintanceid' , 'enrol_authorizedotnet'));
        } else {
            return true;
        }
    }

    /**
     * merchant aouthentication for authorize.net for payment and enrolling a user in a course using Authorize.net.
     * @return object
     */
    public function create_merchant_authentication() {
        $merchantauthentication = new AnetAPI\MerchantAuthenticationType();
        $loginid = $this->plugin->get_config('loginid');
        $merchantauthentication->setName($loginid);
        $transactionkey = $this->plugin->get_config('transactionkey');
        $merchantauthentication->setTransactionKey($transactionkey);
        return $merchantauthentication;
    }


    /**
     * create order for enrolling a user in a course using Authorize.net.
     * @return object
     **/
    public function create_order() {
        $order = new AnetAPI\OrderType();
        $order->setDescription($this->description);
        return $order;
    }


    /**
     * create the transaction request for enrolling a user in a course using Authorize.net.
     * @param object $order instance of the order we created previously
     * @return object
     */
    public function create_transaction($order ) {

        // Create a credit card type object.
        $cardexpyearmonth = $this->formdata->expyear . '-' . $this->formdata->expmonth;
        $creditcardset = new AnetAPI\CreditCardType();
        $creditcardset->setCardNumber(preg_replace('/\s+/', '', $this->formdata->cardnumber));
        $creditcardset->setExpirationDate($cardexpyearmonth);
        $creditcardset->setCardCode($this->formdata->cardcode);

        // Creating a payment type object.
        $paymentone = new AnetAPI\PaymentType();
        $paymentone->setCreditCard($creditcardset);

        // Creating customer datatype object.
        $customerdatatype = new AnetAPI\CustomerDataType();
        $customerdatatype->setType("individual");
        $customerdatatype->setId($this->user->id);

        // Creating customer address type object.
        $customeraddress = new AnetAPI\CustomerAddressType();
        $customeraddress->setFirstName($this->formdata->firstname);
        $customeraddress->setLastName($this->formdata->lastname);
        $customeraddress->setCompany($this->user->department);
        $customeraddress->setAddress($this->formdata->address);
        $customeraddress->setCity($this->formdata->city);
        $customeraddress->setZip($this->formdata->zip);
        $customeraddress->setCountry($this->formdata->country);
        
        // Creating transaction request type object.
        $transactionrequesttype = new AnetAPI\TransactionRequestType();
        $transactionrequesttype->setTransactionType("authCaptureTransaction");
        $transactionrequesttype->setAmount($this->plugininstance->cost);
        $transactionrequesttype->setOrder($order);
        $transactionrequesttype->setPayment($paymentone);
        $transactionrequesttype->setBillTo($customeraddress);
        $transactionrequesttype->setCustomer($customerdatatype);
        return $transactionrequesttype;
    }

    /**
     * create a payment request in authorize.net for enrolling a user in a course using Authorize.net.
     * And implement the request and get the response
     * @param object $merchantauthentication  the merchantauthentication object we created previously
     * @param object $transactionrequesttype the transactionrequesttype object we created
     * @return object
     */
    public function complete_transaction($merchantauthentication , $transactionrequesttype) {
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantauthentication);
        $request->setRefId($this->refid);
        $request->setTransactionRequest($transactionrequesttype);
        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse(constant("\\net\authorize\api\constants\ANetEnvironment::$this->paymnetenv"));
        return $response;
    }

    /**
     * check the response and if there is an error show the error.
     * @param object $response the rsponse object we created by executing transaction request
     * @return boolean
     */
    public function generate_error_messsage($response) {
        $error = null;
        if ($response->getTransactionResponse()->getErrors()) {
            $error = $response->getTransactionResponse()->getErrors()[0]->getErrorText();
            echo "<div class ='error_message'>$error</div>";
            return 1;
        }
        if ($response == null) {
            return 1;
        }
        return 0;
    }


    /**
     * if the payment  is successfull enrol the user to course
     * @param object $response the rsponse object we created by executing transaction request
     * @return mixed
     */
    public function enrol_user($response) {
        global $DB , $CFG , $PAGE;
        $tresponse = $response->getTransactionResponse();

        // Transaction info.
        $transactionid = $tresponse->getTransId();
        $paymentresponse = $tresponse->getResponseCode();
        $PAGE->set_context($this->context);
        $coursecontext = context_course::instance($this->course->id, IGNORE_MISSING);
        if ($users = get_users_by_capability($this->context, 'moodle/course:update', 'u.*', 'u.id ASC',
                                                '', '', '', '', false, true)) {
            $users = sort_by_roleassignment_authority($users, $this->context);
            $teacher = array_shift($users);
        } else {
            $teacher = false;
        }
        $mailstudents = $this->plugin->get_config('mailstudents');
        $mailteachers = $this->plugin->get_config('mailteachers');
        $mailadmins   = $this->plugin->get_config('mailadmins');
        $shortname = format_string($this->course->shortname, true, ['context' => $this->context]);
        $userdetails = new stdClass();
        $userdetails->course = format_string($this->course->fullname, true, ['context' => $coursecontext]);
        $userdetails->user = fullname($this->user);
        $thisuser = $this->user;
        $userdetails->profileurl = "$CFG->wwwroot/user/view.php?id=$thisuser->id";
        $eventdata = new \core\message\message();
        $eventdata->component         = 'enrol_authorizedotnet';
        $eventdata->name              = 'authorizedotnet_enrolment';
        $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = '';
        if (!empty($mailstudents)) {
            $eventdata->userfrom          = empty($teacher) ? core_user::get_noreply_user() : $teacher;
            $eventdata->userto            = $this->user;
            $eventdata->fullmessage       = get_string('welcometocoursetext', '', $userdetails);
            message_send($eventdata);
        }
        if (!empty($mailteachers) && !empty($teacher)) {
            $eventdata->userfrom          = $this->user;
            $eventdata->userto            = $teacher;
            $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $userdetails);
            message_send($eventdata);
        }
        if (!empty($mailadmins)) {
            $admins = get_admins();
            foreach ($admins as $admin) {
                $eventdata->userfrom          = $this->user;
                $eventdata->userto            = $admin;
                $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $userdetails);
                message_send($eventdata);
            }
        }
        $enrolauthorizedotnet = new stdClass();
        $enrolauthorizedotnet->item_name = $this->description;
        $enrolauthorizedotnet->courseid = $this->course->id;
        $enrolauthorizedotnet->userid = $this->user->id;
        $enrolauthorizedotnet->instanceid = $this->plugininstance->id;
        $enrolauthorizedotnet->amount = $this->plugininstance->cost;
        $enrolauthorizedotnet->payment_status = 'Approved';
        $enrolauthorizedotnet->card_type = 'card';
        $enrolauthorizedotnet->invoice_num = $this->invoice;
        $enrolauthorizedotnet->email = $this->formdata->email;
        $enrolauthorizedotnet->first_name = $this->formdata->firstname;
        $enrolauthorizedotnet->last_name = $this->formdata->lastname;
        $enrolauthorizedotnet->country = $this->formdata->country;
        $enrolauthorizedotnet->address = $this->formdata->address;
        $enrolauthorizedotnet->zip = $this->formdata->zip;
        $enrolauthorizedotnet->trans_id = $transactionid;
        $enrolauthorizedotnet->response_code = $paymentresponse;
        $enrolauthorizedotnet->timeupdated = time();
        /* Inserting value to enrol_authorizedotnet table */
        $DB->insert_record("enrol_authorizedotnet", $enrolauthorizedotnet, false);
        if ($this->plugininstance->enrolperiod) {
            $timestart = time();
            $timeend   = $timestart + $this->plugininstance->enrolperiod;
        } else {
            $timestart = 0;
            $timeend   = 0;
        }
        /* Enrol User */
        $this->plugin->enrol_user($this->plugininstance, $this->user->id, $this->plugininstance->roleid, $timestart, $timeend);
        if (!$this->course) {
            redirect($CFG->wwwroot);
        }
        $context = context_course::instance($this->course->id, MUST_EXIST);
        $PAGE->set_context($context);
        $courseid = $this->course->id;
        $destination = "$CFG->wwwroot/course/view.php?id=$courseid";
        if (!is_enrolled($context, null, '', true)) {
            $PAGE->set_url($destination);
        }
    }
}
