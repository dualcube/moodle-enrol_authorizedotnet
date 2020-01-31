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
 * Listens for Instant Payment Notification from Authorize.net
 *
 * This script waits for Payment notification from Authorize.net, 
 * then it sets up the enrolment for that user.
 *
 * @package    enrol_authorizedotnet
 * @copyright  2015 Dualcube, Moumita Ray, Parthajeet Chakraborty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Disable moodle specific debug messages and any errors in output,
// comment out when debugging or better look into error log!
define('NO_DEBUG_DISPLAY', true);

require("../../config.php");
require_once("lib.php");
//require_once($CFG->libdir.'/eventslib.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->libdir . '/filelib.php');
//echo "<pre>"; print_r($CFG); die;
//require_once($CFG->dirroot . '/admin/environment.php');

require_login();

global $DB, $CFG;

if (empty($_POST) or !empty($_GET)) {
    print_error("Sorry, you can not use the script that way."); die;
}
$PAGE->set_pagelayout('admin');
$PAGE->set_url($CFG->wwwroot.'/enrol/authorizedotnet/pay_process.php');
echo $OUTPUT->header();
echo $OUTPUT->heading("Your Payment is in Process....");
echo $OUTPUT->heading("Don't Reload or Leave This Page. This Page Will Automatically Redirect You To The Course Page. ");
echo $OUTPUT->footer();

$transRequestXmlStr=<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<createTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
      <merchantAuthentication></merchantAuthentication>
      <transactionRequest>
         <transactionType>authCaptureTransaction</transactionType>
         <amount>assignAMOUNT</amount>
         <currencyCode>assignCURRENCY</currencyCode>
         <payment>
            <opaqueData>
               <dataDescriptor>assignDD</dataDescriptor>
               <dataValue>assignDV</dataValue>
            </opaqueData>
         </payment>
      </transactionRequest>
</createTransactionRequest>
XML;

$transRequestXml=new SimpleXMLElement($transRequestXmlStr);

$loginId = $_POST['loginkey'];
$transactionKey = $_POST['transactionkey'];

$transRequestXml->merchantAuthentication->addChild('name',$loginId);
$transRequestXml->merchantAuthentication->addChild('transactionKey',$transactionKey);

$transRequestXml->transactionRequest->amount=$_POST['amount'];
$transRequestXml->transactionRequest->currencyCode=$_POST['x_currency_code'];
$transRequestXml->transactionRequest->payment->opaqueData->dataDescriptor=$_POST['dataDesc'];
$transRequestXml->transactionRequest->payment->opaqueData->dataValue=$_POST['dataValue'];

if($_POST['dataDesc'] === 'COMMON.VCO.ONLINE.PAYMENT')
{
    $transRequestXml->transactionRequest->addChild('callId',$_POST['callId']);  
}


if(isset($_POST['paIndicator'])){
    //$transRequestXml->transactionRequest->addChild('cardholderAuthentication');
    //$transRequestXml->transactionRequest->cardholderAuthentication->addChild('authenticationIndicator',$_POST['paIndicator']);
    //$transRequestXml->transactionRequest->cardholderAuthentication->addChild('cardholderAuthenticationValue',$_POST['paValue']);
}

$url="https://apitest.authorize.net/xml/v1/request.api";

//print_r($transRequestXml->asXML());

try{    //setting the curl parameters.
        $ch = curl_init();
        if (FALSE === $ch)
            throw new Exception('failed to initialize');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $transRequestXml->asXML());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
    // The following two curl SSL options are set to "false" for ease of development/debug purposes only.
    // Any code used in production should either remove these lines or set them to the appropriate
    // values to properly use secure connections for PCI-DSS compliance.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    //for production, set value to true or 1
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);    //for production, set value to 2
        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false );
        $content = curl_exec($ch);
        if (FALSE === $content)
            throw new Exception(curl_error($ch), curl_errno($ch));
        curl_close($ch);
        
        $xmlResult= @simplexml_load_string($content);

        $jsonResult=json_encode($xmlResult);
        
        
        
        $jsonResult=json_decode($jsonResult);
        $auth_c = $jsonResult->transactionResponse->authCode;
        $res_c = $jsonResult->transactionResponse->responseCode;
        $trans_i = $jsonResult->transactionResponse->transId;
        
        $enrolauthorizedotnet = new stdClass();
        $postdata = array_map('utf8_encode', $_POST);
        
        $existing_array = array('authCode'=>$auth_c, 'responseCode'=>$res_c, 'transId'=>$trans_i);
        $postdata = array_merge($postdata, $existing_array);
        
        $enrolauthorizedotnet->auth_json = json_encode($postdata);
        
        $enrolauthorizedotnet->timeupdated = time();
        
        $ret1 = $DB->insert_record("enrol_authorizedotnet", $enrolauthorizedotnet, true);

        echo '<script type="text/javascript">
             window.location.href="'.$CFG->wwwroot.'/enrol/authorizedotnet/update.php?id='.$ret1.'";
             </script>';
        
    }catch(Exception $e) {
        trigger_error(sprintf('Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
    }


