<?php
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->libdir/enrollib.php");
require_once("$CFG->libdir/filelib.php");

// Include Authorize.Net PHP sdk 
require 'authorize_net_sdk_php/autoload.php';  
use net\authorize\api\contract\v1 as AnetAPI; 
use net\authorize\api\controller as AnetController; 

class moodle_enrol_authorizedotnet_external extends external_api {

    public static function authorizedotnet_payment_processing_parameters() {
        return new external_function_parameters(
            array(
                'clientkey' => new external_value(PARAM_RAW, 'The item id to operate on'),
                'loginid' => new external_value(PARAM_RAW, 'The item id to operate on'),
                'amount' => new external_value(PARAM_RAW, 'The item id to operate on'),
                'instance_currency' => new external_value(PARAM_RAW, 'The item id to operate on'),
                'transactionkey' => new external_value(PARAM_RAW, 'The item id to operate on'),
                'instance_courseid' => new external_value(PARAM_RAW, 'The item id to operate on'),
                'USER_id' => new external_value(PARAM_RAW, 'The item id to operate on'),
                'USER_email' => new external_value(PARAM_RAW, 'The item id to operate on'),
                'instance_id' => new external_value(PARAM_RAW, 'The item id to operate on'),
                'context_id' => new external_value(PARAM_RAW, 'The item id to operate on'),
                'description' => new external_value(PARAM_RAW, 'The item id to operate on'),
                'invoice' => new external_value(PARAM_RAW, 'The item id to operate on'),
                'sequence' => new external_value(PARAM_RAW, 'The item id to operate on'),
                'timestamp' => new external_value(PARAM_RAW, 'The item id to operate on'),
                'cardNumber' => new external_value(PARAM_TEXT, 'The item id to operate on'),
                'month' => new external_value(PARAM_TEXT, 'The item id to operate on'),
                'year' => new external_value(PARAM_TEXT, 'The item id to operate on'),
                'cardCode' => new external_value(PARAM_TEXT, 'The item id to operate on'),
                'auth_modess' => new external_value(PARAM_TEXT, 'The item id to operate on')
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

    public static function authorizedotnet_payment_processing($clientkey, $loginid, $amount, $instance_currency, $transactionkey, $instance_courseid, $USER_id, $user_email, $instance_id, $context_id, $description, $invoice, $sequence, $timestamp, $cardNumber, $month, $year, $cardCode, $auth_modess) {
        global $DB, $CFG, $PAGE;

        require("../../config.php");
        require('../../lib/setup.php');
        require_once("lib.php");
        require_once($CFG->libdir.'/enrollib.php');
        require_once($CFG->libdir . '/filelib.php');
        require_once("$CFG->dirroot/enrol/authorizedotnet/lib.php");

        if (! $user = $DB->get_record("user", array("id" => $USER_id))) {
            print_error("Not a valid user id"); die;
        }

        if (! $course = $DB->get_record("course", array("id" => $instance_courseid))) {
            print_error("Not a valid course id"); die;
        }

        if (! $context = context_course::instance($instance_courseid, IGNORE_MISSING)) {
            print_error("Not a valid context id"); die;
        }

        if (! $plugininstance = $DB->get_record("enrol", array("id" => $instance_id, "status" => 0))) {
            print_error("Not a valid instance id"); die;
        }

        $paymentID = $statusMsg = '';
        $ordStatus = 'error';

        $ANET_ENV = $auth_modess && $auth_modess == 1 ? 'PRODUCTION': 'SANDBOX'; // or PRODUCTION 
        // Check whether card information is not empty 
        if(!empty($cardNumber) && !empty($month) && !empty($year) && !empty($cardCode)){ 

            // Retrieve card and user info from the submitted form data 
            $email = $user_email;
            $card_number = preg_replace('/\s+/', '', $cardNumber); 
            $card_exp_month = $month; 
            $card_exp_year = $year; 
            $card_exp_year_month = $card_exp_year.'-'.$card_exp_month; 
            $card_cvc = $cardCode; 

            // Set the transaction's reference ID 
            $refID = 'REF'.time(); 

            // Create a merchantAuthenticationType object with authentication details 
            // retrieved from the config file 
            $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();    
            $merchantAuthentication->setName($loginid);    
            $merchantAuthentication->setTransactionKey($transactionkey);    

            // Create the payment data for a credit card 
            $creditCard = new AnetAPI\CreditCardType(); 
            $creditCard->setCardNumber($card_number); 
            $creditCard->setExpirationDate($card_exp_year_month); 
            $creditCard->setCardCode($card_cvc); 

            // Add the payment data to a paymentType object 
            $paymentOne = new AnetAPI\PaymentType(); 
            $paymentOne->setCreditCard($creditCard); 

            // Create order information 
            $order = new AnetAPI\OrderType(); 
            $order->setDescription($description); 

            // Set the customer's identifying information 
            $customerData = new AnetAPI\CustomerDataType(); 
            $customerData->setType("individual"); 
            $customerData->setEmail($email); 

            // Create a transaction 
            $transactionRequestType = new AnetAPI\TransactionRequestType(); 
            $transactionRequestType->setTransactionType("authCaptureTransaction");    
            $transactionRequestType->setAmount($amount); 
            $transactionRequestType->setOrder($order); 
            $transactionRequestType->setPayment($paymentOne); 
            $transactionRequestType->setCustomer($customerData); 
            $request = new AnetAPI\CreateTransactionRequest(); 
            $request->setMerchantAuthentication($merchantAuthentication); 
            $request->setRefId($refID); 
            $request->setTransactionRequest($transactionRequestType); 
            $controller = new AnetController\CreateTransactionController($request); 
            $response = $controller->executeWithApiResponse(constant("\\net\authorize\api\constants\ANetEnvironment::$ANET_ENV")); 

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

                        $plugin = enrol_get_plugin('authorizedotnet');

                        $mailstudents = $plugin->get_config('mailstudents');
                        $mailteachers = $plugin->get_config('mailteachers');
                        $mailadmins   = $plugin->get_config('mailadmins');
                        $shortname = format_string($course->shortname, true, array('context' => $context));

                        if (!empty($mailstudents)) {
                            $a = new stdClass();
                            $a->coursename = format_string($course->fullname, true, array('context' => $coursecontext));
                            $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id";

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
                            $eventdata->fullmessage       = get_string('welcometocoursetext', '', $a);
                            $eventdata->fullmessageformat = FORMAT_PLAIN;
                            $eventdata->fullmessagehtml   = '';
                            $eventdata->smallmessage      = '';
                            message_send($eventdata);

                        }

                        if (!empty($mailteachers) && !empty($teacher)) {
                            $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
                            $a->user = fullname($user);

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
                            $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
                            $eventdata->fullmessageformat = FORMAT_PLAIN;
                            $eventdata->fullmessagehtml   = '';
                            $eventdata->smallmessage      = '';
                            message_send($eventdata);
                        }

                        if (!empty($mailadmins)) {
                            $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
                            $a->user = fullname($user);
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
                                $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
                                $eventdata->fullmessageformat = FORMAT_PLAIN;
                                $eventdata->fullmessagehtml   = '';
                                $eventdata->smallmessage      = '';
                                message_send($eventdata);
                            }
                        }

                        $enrolauthorizedotnet = $userenrolments = $roleassignments = new stdClass();
                        $enrolauthorizedotnet->item_name = $description;
                        $enrolauthorizedotnet->courseid = $instance_courseid;
                        $enrolauthorizedotnet->userid = $USER_id;
                        $enrolauthorizedotnet->instanceid = $instance_id;
                        $enrolauthorizedotnet->amount = $amount;
                        $enrolauthorizedotnet->payment_status = 'Approved';
                        $enrolauthorizedotnet->card_type = 'card';
                        $enrolauthorizedotnet->invoice_num = $invoice;
                        $enrolauthorizedotnet->timeupdated = time();

                        /* Inserting value to enrol_authorizedotnet table */
                        $ret1 = $DB->insert_record("enrol_authorizedotnet", $enrolauthorizedotnet, false);

                        if ($plugininstance->enrolperiod) {
                            $timestart = time();
                            $timeend   = $timestart + $plugininstance->enrolperiod;
                        } else {
                            $timestart = 0;
                            $timeend   = 0;
                        }

                        /* Enrol User */
                        $plugin->enrol_user($plugininstance, $user->id, $plugininstance->roleid, $timestart, $timeend);

                        if (!$course) {
                            redirect($CFG->wwwroot);
                        }
                        $context = context_course::instance($course->id, MUST_EXIST);
                        $PAGE->set_context($context);

                        $destination = "$CFG->wwwroot/course/view.php?id=$course->id";

                        $fullname = format_string($course->fullname, true, array('context' => $context));

                        if (is_enrolled($context, null, '', true)) {} else {
                            $PAGE->set_url($destination);
                        }

                        $ordStatus = 'success'; 
                        $statusMsg = 'Your Payment has been Successful!'; 
                    } else { 
                        $error = "Transaction Failed! \n"; 
                        if ($tresponse->getErrors() != null) { 
                            $error .= " Error Code  : " . $tresponse->getErrors()[0]->getErrorCode() . "<br/>"; 
                            $error .= " Error Message : " . $tresponse->getErrors()[0]->getErrorText() . "<br/>"; 
                        } 
                        $statusMsg = $error; 
                    } 
                // Or, print errors if the API request wasn't successful 
                } else { 
                    $error = "Transaction Failed! \n"; 
                    $tresponse = $response->getTransactionResponse(); 

                    if ($tresponse != null && $tresponse->getErrors() != null) { 
                        $error .= " Error Code  : " . $tresponse->getErrors()[0]->getErrorCode() . "<br/>"; 
                        $error .= " Error Message : " . $tresponse->getErrors()[0]->getErrorText() . "<br/>"; 
                    } else { 
                        $error .= " Error Code  : " . $response->getMessages()->getMessage()[0]->getCode() . "<br/>"; 
                        $error .= " Error Message : " . $response->getMessages()->getMessage()[0]->getText() . "<br/>"; 
                    } 
                    $statusMsg = $error; 
                } 
            } else { 
                $statusMsg =  "Transaction Failed! No response returned"; 
            } 
        } else { 
            $statusMsg = "Error on form submission."; 
        }

        $result = array();
        $result['status'] = $ordStatus;
        return $result;
        die;
    }
}