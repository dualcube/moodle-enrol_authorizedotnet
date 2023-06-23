<?php
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/enrol/authorizedotnet/lib.php");
// Include Authorize.Net PHP sdk 
require 'authorize_net_sdk_php/autoload.php';  
use net\authorize\api\contract\v1 as AnetAPI; 
use net\authorize\api\controller as AnetController; 
class moodle_enrol_authorizedotnet_external extends external_api {
    public static function authorizedotnet_payment_processing_parameters() {
        return new external_function_parameters(
            array(
                'instance_courseid' => new external_value(PARAM_RAW, 'The course id to operate on'),
                'user_id' => new external_value(PARAM_RAW, 'The user id to operate on'),
                'instance_id' => new external_value(PARAM_RAW, 'The instance id to operate on'),
                'payment_card_number' => new external_value(PARAM_TEXT, 'The card number for card transaction'),
                'month' => new external_value(PARAM_TEXT, 'The expiry mounth card for transaction'),
                'year' => new external_value(PARAM_TEXT, 'The expiry year for card transaction'),
                'card_code' => new external_value(PARAM_TEXT, 'The cvv for card transaction'),
                'firstname' => new external_value(PARAM_TEXT, 'The first name of customer '),
                'lastname' => new external_value(PARAM_TEXT, 'The last name of customer '),
                'address' => new external_value(PARAM_TEXT, 'The address for customer billing'),
                'zip' => new external_value(PARAM_TEXT, 'The zip for customer billing'),
            )  
        );
    }
    public static function authorizedotnet_payment_processing_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_RAW, 'status: true if success')
            )
        );
    }
    public static function authorizedotnet_payment_processing($instance_courseid, $user_id, $instance_id, $payment_card_number, $month, $year, $card_code,$firstname, $lastname, $address, $zip) {
        global $DB, $CFG, $PAGE;
        $plugin = enrol_get_plugin('authorizedotnet');
        if (! $user = $DB->get_record("user", array("id" => $user_id))) {
            print_error(get_string('invaliduserid','enrol_authorizedotnet')); die;
        }
        if (! $course = $DB->get_record("course", array("id" => $instance_courseid))) {
            print_error(get_string('invalidcourseid','enrol_authorizedotnet')); die;
        }
        if (! $context = context_course::instance($instance_courseid, IGNORE_MISSING)) {
            print_error(get_string('invalidcontextid','enrol_authorizedotnet')); die;
        }
        if (! $plugin_instance = $DB->get_record("enrol", array("id" => $instance_id, "status" => 0))) {
            print_error(get_string('invalidintanceid','enrol_authorizedotnet')); die;
        }
        $invoice = date('YmdHis');
        $description = $course->fullname;
        $payment_id = $error_msg = $status_msg = '';
        $order_status = 'error';
        $auth_mode = $plugin->get_config('checkproductionmode');
        $paymnet_env = $auth_mode && $auth_mode == 1 ? 'PRODUCTION': 'SANDBOX'; // or PRODUCTION 
        // Check whether card information is not empty 
        if(!empty($payment_card_number) && !empty($month) && !empty($year) && !empty($card_code)){ 
            // Retrieve card and user info from the submitted form data 
            $email = $user->email;
            $card_number = preg_replace('/\s+/', '', $payment_card_number); 
            $card_exp_month = $month; 
            $card_exp_year = $year; 
            $card_exp_year_month = $card_exp_year.'-'.$card_exp_month; 
            $card_cvc = $card_code; 
            // Set the transaction's reference ID 
            $ref_id = 'REF'.time(); 
            // Create a merchantAuthenticationType object with authentication details 
            // retrieved from the config file 
            $merchant_authentication = new AnetAPI\MerchantAuthenticationType();  
            $login_id = $plugin->get_config('loginid');  
            $merchant_authentication->setName($login_id);
            $transaction_key = $plugin->get_config('transactionkey');
            $merchant_authentication->setTransactionKey($transaction_key);
            // Create the payment data for a credit card 
            $credit_card_set = new AnetAPI\CreditCardType(); 
            $credit_card_set->setCardNumber($card_number); 
            $credit_card_set->setExpirationDate($card_exp_year_month); 
            $credit_card_set->setCardCode($card_cvc); 
            // Add the payment data to a paymentType object 
            $payment_one = new AnetAPI\PaymentType(); 
            $payment_one->setCreditCard($credit_card_set); 
            // Create order information 
            $order = new AnetAPI\OrderType(); 
            $order->setDescription($description); 
            // Set the customer's identifying information 
            $customer_data = new AnetAPI\CustomerDataType(); 
            $customer_data->setType("individual"); 
            $customer_data->setId($user->id);
            $customer_data->setEmail($email);
            // Set the customer's Bill To address
            $customerAddress = new AnetAPI\CustomerAddressType();
            $customerAddress->setFirstName($firstname);
            $customerAddress->setLastName($lastname);
            $customerAddress->setCompany($user->department);
            $customerAddress->setAddress($address);
            $customerAddress->setCity($user->city);
            $customerAddress->setZip($zip);
            $customerAddress->setCountry($user->country); 
            // Create a transaction 
            $transaction_request_type = new AnetAPI\TransactionRequestType(); 
            $transaction_request_type->setTransactionType("authCaptureTransaction");    
            $transaction_request_type->setAmount($plugin_instance->cost); 
            $transaction_request_type->setOrder($order); 
            $transaction_request_type->setPayment($payment_one); 
            $transaction_request_type->setBillTo($customerAddress); 
            $transaction_request_type->setCustomer($customer_data); 
            // Assemble the complete transaction request
            $request = new AnetAPI\CreateTransactionRequest(); 
            $request->setMerchantAuthentication($merchant_authentication); 
            $request->setRefId($ref_id); 
            $request->setTransactionRequest($transaction_request_type); 
            // Create the controller and get the response
            $controller = new AnetController\CreateTransactionController($request); 
            $response = $controller->executeWithApiResponse(constant("\\net\authorize\api\constants\ANetEnvironment::$paymnet_env")); 
            if ($response != null) { 
                // Check to see if the API request was successfully received and acted upon 
                if ($response->getMessages()->getResultCode() == "Ok") { 
                    // Since the API request was successful, look for a transaction response 
                    // and parse it to display the results of authorizing the card 
                    $tresponse = $response->getTransactionResponse(); 
                    if ($tresponse != null && $tresponse->getMessages() != null) { 
                        // Transaction info 
                        $transaction_id = $tresponse->getTransId(); 
                        $payment_status = $response->getMessages()->getResultCode(); 
                        $payment_response = $tresponse->getResponseCode(); 
                        $auth_code = $tresponse->getAuthCode(); 
                        $message_code = $tresponse->getMessages()[0]->getCode(); 
                        $message_desc = $tresponse->getMessages()[0]->getDescription(); 
                        // ************************ success work ******************************** //
                        $PAGE->set_context($context);
                        $coursecontext = context_course::instance($course->id, IGNORE_MISSING);
                        if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
                                                             '', '', '', '', false, true)) {
                            $users = sort_by_roleassignment_authority($users, $context);
                            $teacher = array_shift($users);
                        } else {
                            $teacher = false;
                        }
                        $mailstudents = $plugin->get_config('mailstudents');
                        $mailteachers = $plugin->get_config('mailteachers');
                        $mailadmins   = $plugin->get_config('mailadmins');
                        $shortname = format_string($course->shortname, true, array('context' => $context));
                        if (!empty($mailstudents)) {
                            $userdetails = new stdClass();
                            $userdetails->coursename = format_string($course->fullname, true, array('context' => $coursecontext));
                            $userdetails->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id";
                            if ($CFG->version >= 2015051100) {
                                $eventdata = new \core\message\message();
                            } else {
                                $eventdata = new stdClass();
                            }
                            $eventdata->component         = 'enrol_authorizedotnet';
                            $eventdata->name              = 'authorizedotnet_enrolment';
                            //$eventdata->courseid          = $course->id;
                            $eventdata->userfrom          = empty($teacher) ? core_user::get_noreply_user() : $teacher;
                            $eventdata->userto            = $user;
                            $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
                            $eventdata->fullmessage       = get_string('welcometocoursetext', '', $userdetails);
                            $eventdata->fullmessageformat = FORMAT_PLAIN;
                            $eventdata->fullmessagehtml   = '';
                            $eventdata->smallmessage      = '';
                            message_send($eventdata);
                        }
                        if (!empty($mailteachers) && !empty($teacher)) {
                            $userdetails->course = format_string($course->fullname, true, array('context' => $coursecontext));
                            $userdetails->user = fullname($user);
                            if ($CFG->version >= 2015051100) {
                                $eventdata = new \core\message\message();
                            } else {
                                $eventdata = new stdClass();
                            }
                            $eventdata->component         = 'enrol_authorizedotnet';
                            $eventdata->name              = 'authorizedotnet_enrolment';
                            //$eventdata->courseid          = $course->id;
                            $eventdata->userfrom          = $user;
                            $eventdata->userto            = $teacher;
                            $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
                            $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $userdetails);
                            $eventdata->fullmessageformat = FORMAT_PLAIN;
                            $eventdata->fullmessagehtml   = '';
                            $eventdata->smallmessage      = '';
                            message_send($eventdata);
                        }
                        if (!empty($mailadmins)) {
                            $userdetails->course = format_string($course->fullname, true, array('context' => $coursecontext));
                            $userdetails->user = fullname($user);
                            $admins = get_admins();
                            foreach ($admins as $admin) {
                                if ($CFG->version >= 2015051100) {
                                    $eventdata = new \core\message\message();
                                } else {
                                    $eventdata = new stdClass();
                                }
                                $eventdata->component         = 'enrol_authorizedotnet';
                                $eventdata->name              = 'authorizedotnet_enrolment';
                                //$eventdata->courseid          = $course->id;
                                $eventdata->userfrom          = $user;
                                $eventdata->userto            = $admin;
                                $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
                                $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $userdetails);
                                $eventdata->fullmessageformat = FORMAT_PLAIN;
                                $eventdata->fullmessagehtml   = '';
                                $eventdata->smallmessage      = '';
                                message_send($eventdata);
                            }
                        }
                        $enrolauthorizedotnet = $userenrolments = $roleassignments = new stdClass();
                        $enrolauthorizedotnet->item_name = $description;
                        $enrolauthorizedotnet->courseid = $instance_courseid;
                        $enrolauthorizedotnet->userid = $user_id;
                        $enrolauthorizedotnet->instanceid = $instance_id;
                        $enrolauthorizedotnet->amount = $amount;
                        $enrolauthorizedotnet->payment_status = 'Approved';
                        $enrolauthorizedotnet->card_type = 'card';
                        $enrolauthorizedotnet->invoice_num = $invoice;
                        $enrolauthorizedotnet->email = $email;
                        $enrolauthorizedotnet->first_name = $firstname;
                        $enrolauthorizedotnet->last_name = $lastname;
                        $enrolauthorizedotnet->country = $user->country;
                        $enrolauthorizedotnet->address = $address;
                        $enrolauthorizedotnet->zip = $zip;
                        $enrolauthorizedotnet->trans_id = $transaction_id;
                        $enrolauthorizedotnet->response_code = $payment_response;
                        $enrolauthorizedotnet->timeupdated = time();
                        /* Inserting value to enrol_authorizedotnet table */
                        $ret1 = $DB->insert_record("enrol_authorizedotnet", $enrolauthorizedotnet, false);
                        if ($plugin_instance->enrolperiod) {
                            $timestart = time();
                            $timeend   = $timestart + $plugin_instance->enrolperiod;
                        } else {
                            $timestart = 0;
                            $timeend   = 0;
                        }
                        /* Enrol User */
                        $plugin->enrol_user($plugin_instance, $user->id, $plugin_instance->roleid, $timestart, $timeend);
                        if (!$course) {
                            redirect($CFG->wwwroot);
                        }
                        $context = context_course::instance($course->id, MUST_EXIST);
                        $PAGE->set_context($context);
                        $destination = "$CFG->wwwroot/course/view.php?id=$course->id";
                        $fullname = format_string($course->fullname, true, array('context' => $context));
                        if (!is_enrolled($context, null, '', true)) {
                            $PAGE->set_url($destination);
                        }
                        $order_status = get_string('success','enrol_authorizedotnet'); 
                        $status_msg = get_string('successmsg','enrol_authorizedotnet'); 
                    } else { 
                        $error = get_string('tarnsactionfail','enrol_authorizedotnet'); 
                        if ($tresponse->getErrors() != null) { 
                            $error .= get_string('errorcode','enrol_authorizedotnet') . $tresponse->getErrors()[0]->getErrorCode() . "<br/>"; 
                            $error .= get_string('errormsg','enrol_authorizedotnet') . $tresponse->getErrors()[0]->getErrorText() . "<br/>"; 
                        } 
                        $status_msg = $error; 
                    } 
                // Or, print errors if the API request wasn't successful 
                } else { 
                    $error = get_string('tarnsactionfail','enrol_authorizedotnet'); 
                    $tresponse = $response->getTransactionResponse(); 
                    if ($tresponse != null && $tresponse->getErrors() != null) { 
                        $error .= get_string('errorcode','enrol_authorizedotnet') . $tresponse->getErrors()[0]->getErrorCode() . "<br/>"; 
                        $error .= get_string('errormsg','enrol_authorizedotnet')  . $tresponse->getErrors()[0]->getErrorText() . "<br/>"; 
                        $error_msg = $tresponse->getErrors()[0]->getErrorText();
                    } else { 
                        $error .= get_string('errorcode','enrol_authorizedotnet') . $response->getMessages()->getMessage()[0]->getCode() . "<br/>"; 
                        $error .= get_string('errormsg','enrol_authorizedotnet')  . $response->getMessages()->getMessage()[0]->getText() . "<br/>"; 
                        $error_msg = $response->getMessages()->getMessage()[0]->getText();
                    } 
                    $status_msg = $error; 
                } 
            } else { 
                $status_msg = get_string('transactionfailmsg','enrol_authorizedotnet') ; 
            } 
        } else { 
            $status_msg = get_string('errorsubmission','enrol_authorizedotnet') ; 
        }
        $result = array();
        if($order_status == 'error'){
            $result['status'] = $status_msg;
        }
        else{
            $result['status'] = $order_status;
        }
        return $result;
        die;
    }
}