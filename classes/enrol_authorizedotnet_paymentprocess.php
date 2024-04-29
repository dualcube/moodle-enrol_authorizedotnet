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
    public $user = null;
    public $course = null;
    public $context = null;
    public $plugininstance = null;
    public $plugin;
    public $invoice;
    public $description;
    public $authmode;
    public $paymnetenv;
    public $refid;

    /**
     * Process a payment for enrolling a user in a course using Authorize.net.
     * @param object $formdata An array containing form data submitted by the user.
     * @param int $courseid The ID of the course in which the user wants to enroll.
     * @param int $userid The ID of the user who is enrolling in the course.
     * @param int $instanceid The ID of the enrollment instance.
     * @return mixed
     */
    public function process_payment($formdata , $courseid , $userid , $instanceid) {
        if ($this->invalid_details_check($courseid , $userid , $instanceid)) {
            $this->set_necessery_data();
            if ($this->check_card_information($formdata)) {
                $merchantauthentication = $this->create_merchant_authentication();
                $creditcardset = $this->set_credit_card($formdata);
                $paymentone = $this->create_payment_type_object($creditcardset);
                $order = $this->create_order();
                $customerdatatype = $this->create_customer_datatype($formdata);
                $customeraddress = $this->create_customer_address($formdata);
                $transactionrequesttype = $this->create_transaction($order , $paymentone , $customeraddress , $customerdatatype);
                $request = $this->create_transaction_request($merchantauthentication , $transactionrequesttype);
                $response = $this->create_transaction_controller($request);
                $bool = $this->generate_error_messsage($response);
                if ($bool) {
                    $this->process_all_data($response , $formdata);
                }
            }
        }
    }

    /**
     * Check details for a payment for enrolling a user in a course using Authorize.net..
     * @param int $courseid The ID of the course in which the user wants to enroll.
     * @param int $userid The ID of the user who is enrolling in the course.
     * @param int $instanceid The ID of the enrollment instance.
     * @return boolean
     */
    public function invalid_details_check($courseid , $userid , $instanceid) {
        global $DB , $CFG , $PAGE;
        $this->plugin = enrol_get_plugin('authorizedotnet');
        if (! $this->user = $DB->get_record("user" , array("id" => $userid))) {
            throw new moodle_exception(get_string('invaliduserid' , 'enrol_authorizedotnet'));
        }
        if (! $this->course = $DB->get_record("course" , array("id" => $courseid))) {
            throw new moodle_exception(get_string('invalidcourseid' , 'enrol_authorizedotnet'));
        }
        if (! $this->context = context_course::instance($courseid , IGNORE_MISSING)) {
            throw new moodle_exception(get_string('invalidcontextid' , 'enrol_authorizedotnet'));
        }
        if (! $this->plugininstance = $DB->get_record("enrol" , array("id" => $instanceid , "status" => 0))) {
            throw new moodle_exception(get_string('invalidintanceid' , 'enrol_authorizedotnet'));
        } else {
            return true;
        }
    }
    /**
     * set necessary data for payment and enrolling a user in a course using Authorize.net.
     * @return void
     */
    public function set_necessery_data() {
        $this->invoice = date('YmdHis');
        $this->description = $this->course->fullname;
        $this->authmode = $this->plugin->get_config('checkproductionmode');
        $this->paymnetenv = $this->authmode == 0 ? 'PRODUCTION' : 'SANDBOX';
        $this->refid = 'REF'.time();
    }
    /**
     * check card information for  payment and enrolling a user in a course using Authorize.net.
     * @param object $formdata An array containing form data submitted by the user.
     * @return boolean
     */
    public function check_card_information($formdata) {
        if (!empty($formdata->cardnumber) && !empty($formdata->expmonth) && !empty($formdata->expyear) && !empty($formdata->cardcode)) {
            return true;
        } else {
            return false;
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
     * Set the credit card information in authorize.net for payment and enrolling a user in a course using Authorize.net.
     * @param object $formdata An array containing form data submitted by the user.
     * @return object
     */
    public function set_credit_card($formdata) {

        $cardexpyearmonth = $formdata->expyear . '-' . $formdata->expmonth;

        $creditcardset = new AnetAPI\CreditCardType();
        $creditcardset->setCardNumber(preg_replace('/\s+/', '', $formdata->cardnumber));
        $creditcardset->setExpirationDate($cardexpyearmonth);
        $creditcardset->setCardCode($formdata->cardcode);
        return $creditcardset;
    }


    /**
     * create a payment type object for enrolling a user in a course using Authorize.net.
     * @return mixed
     */
    public function create_payment_type_object($creditcardset) {
        $paymentone = new AnetAPI\PaymentType();
        $paymentone->setCreditCard($creditcardset);
        return $paymentone;
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
     * create customer datatype in authorize.net for enrolling a user in a course using Authorize.net.
     * @param object $formdata An array containing form data submitted by the user.
     * @return object
     */
    public function create_customer_datatype($formdata) {
        $customerdatatype = new AnetAPI\CustomerDataType();
        $customerdatatype->setType("individual");
        $customerdatatype->setId($this->user->id);
        $customerdatatype->setEmail($formdata->email);
        return $customerdatatype;
    }
    /**
     * create customer address in authorize.net for enrolling a user in a course using Authorize.net.
     * @param object $formdata An array containing form data submitted by the user.
     * @return object
     */
    public function create_customer_address($formdata) {
        $customeraddress = new AnetAPI\CustomerAddressType();
        $customeraddress->setFirstName($formdata->firstname);
        $customeraddress->setLastName($formdata->lastname);
        $customeraddress->setCompany($this->user->department);
        $customeraddress->setAddress($formdata->address);
        $customeraddress->setCity($formdata->city);
        $customeraddress->setZip($formdata->zip);
        $customeraddress->setCountry($formdata->country);
        return $customeraddress;
    }
    /**
     * create the transaction for enrolling a user in a course using Authorize.net.
     * @param object $order instance of the order we created previously
     * @param object $paymentone instance of the object of payment type we created previoulsy
     * @param object $customeraddress the object of the customer address created previoulsly
     * @param object $customerdatatype the object of the customer datatype created previously
     * @return object
     */
    public function create_transaction($order , $paymentone , $customeraddress , $customerdatatype) {
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
     * @param object $merchantauthentication  the merchantauthentication object we created previously
     * @param object $transactionrequesttype the transactionrequesttype object we created
     * @return object
     */
    public function create_transaction_request($merchantauthentication , $transactionrequesttype) {
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantauthentication);
        $request->setRefId($this->refid);
        $request->setTransactionRequest($transactionrequesttype);
        return $request;
    }


    /**
     * execute the transaction request  for enrolling a user in a course using Authorize.net.
     * @param object $request the request object we created by creating transaction request
     * @return object
     */
    public function create_transaction_controller($request) {
        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse(constant("\\net\authorize\api\constants\ANetEnvironment::$this->paymnetenv"));
        return $response;
    }


    /**
     * if the payment  is successfull enrol him to course
     * @param object $response the rsponse object we created by executing transaction request
     * @return boolean
     */
    public function generate_error_messsage($response) {
        $bool = 1;
        $error = null;
        if ($response->getTransactionResponse()->getErrors()) {
            $bool = 0;
            $error = $response->getTransactionResponse()->getErrors()[0]->getErrorText();
            echo "<div class ='error_message'>$error</div>";
        }
        if ($response == null) {
            $bool = 0;
        }
        return $bool;
    }


    /**
     * if the payment  is successfull enrol him to course
     * @param object $response the rsponse object we created by executing transaction request
     * @return mixed
     */
    public function process_all_data($response , $formdata) {
        global $DB , $CFG , $PAGE;
        if ($response != null) {
            // Check to see if the API request was successfully received and acted upon
            if ($response->getMessages()->getResultCode() == "Ok") {
                // Since the API request was successful, look for a transaction response
                // and parse it to display the results of authorizing the card
                $tresponse = $response->getTransactionResponse();
                if ($tresponse != null && $tresponse->getMessages() != null) {
                    // Transaction info
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
                    $shortname = format_string($this->course->shortname, true, array('context' => $this->context));
                    $userdetails = new stdClass();
                    $userdetails->course = format_string($this->course->fullname, true, array('context' => $coursecontext));
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
                    $enrolauthorizedotnet->email = $formdata->email;
                    $enrolauthorizedotnet->first_name = $formdata->firstname;
                    $enrolauthorizedotnet->last_name = $formdata->lastname;
                    $enrolauthorizedotnet->country = $formdata->country;
                    $enrolauthorizedotnet->address = $formdata->address;
                    $enrolauthorizedotnet->zip = $formdata->zip;
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
        }
    }

}
