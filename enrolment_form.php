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

$loginid = $this->get_config('loginid');
$transactionkey = $this->get_config('transactionkey');
$clientkey = $this->get_config('clientkey');

$auth_modess = $this->get_config('checkproductionmode');
if($auth_modess == 1) {
 $s_path = "https://js.authorize.net/v1/Accept.js";
}
elseif($auth_modess == 0) {
    $s_path = "https://jstest.authorize.net/v1/Accept.js";
}
$amount = $cost;
$description = $coursefullname;

$invoice = date('YmdHis');
$_SESSION['sequence'] = $sequence = rand(1, 1000);
$_SESSION['timestamp'] = $timestamp = time();

?>
<!-- Load the jQuery library from the Google CDN -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js">
</script>
<!-- Load the Accept.js CDN -->
<script type="text/javascript"
    src="<?php echo $s_path;?>"
    charset="utf-8">
</script>

<div align="center">
<p>This course requires a payment for entry.</p>
<p><b><?php echo $instancename; ?></b></p>
<p><b><?php echo get_string("cost").": {$instance->currency} {$localisedcost}"; ?></b></p>

<p>&nbsp;</p>
<p><img alt="Authorize.net" src="<?php echo $CFG->wwwroot; ?>/enrol/authorizedotnet/pix/authorize-net-logo.jpg" /></p>
<p>&nbsp;</p>

<div class="popup">
<div class="popuptext" id="myPopup">
    <form id="paymentForm"
        method="POST"
        action="<?php echo $CFG->wwwroot; ?>/enrol/authorizedotnet/pay_process.php"
    >
	    <h3>Make Your Payment</h3>
	    
	    <button type="button" id="card_btn">Card Payment</button>
	    <button type="button" id="account_btn">Account Payment</button>
	    
	    <div id="card_form">
	    <input type="text" name="cardNumber" id="cardNumber" placeholder="cardNumber"/> <br><br>
        <input type="text" name="expMonth" id="expMonth" placeholder="expMonth"/> <br><br>
        <input type="text" name="expYear" id="expYear" placeholder="expYear"/> <br><br>
        <input type="text" name="cardCode" id="cardCode" placeholder="cardCode"/> 
        </div>
        
        <div id="account_form">
        <input type="text" name="accountNumber" id="accountNumber" placeholder="accountNumber"/> <br><br>
        <input type="text" name="routingNumber" id="routingNumber" placeholder="routingNumber"/> <br><br>
        <input type="text" name="nameOnAccount" id="nameOnAccount" placeholder="nameOnAccount"/> <br><br>
        <input type="text" name="accountType" id="accountType" placeholder="accountType"/> 
	    </div>
	    
	    <input type="hidden" name="dataValue" id="dataValue" />
        <input type="hidden" name="dataDesc" id="dataDescriptor" />

		<input type="hidden" name="amount" value="<?php echo $amount; ?>" />

		<input type="hidden" name="x_currency_code" value="<?php echo $instance->currency; ?>" />

        <input type="hidden" name="loginkey" value="<?php echo $loginid; ?>" />
        <input type="hidden" name="transactionkey" value="<?php echo $transactionkey; ?>" />
        <input type="hidden" name="clientkey" value="<?php echo $clientkey; ?>" />
        
		<input type="hidden" name="x_cust_id" value="<?php echo $instance->courseid.'-'.$USER->id.'-'.$instance->id.'-'.$context->id; ?>">
		<input type="hidden" name="x_description" value="<?php echo $description; ?>" />
		<input type="hidden" name="x_invoice_num" value="<?php echo $invoice; ?>" />
		<input type="hidden" name="x_fp_sequence" value="<?php echo $sequence; ?>" />
		<input type="hidden" name="x_fp_timestamp" value="<?php echo $timestamp; ?>" />
		<input type="hidden" name="x_email_customer" value="true" >

		 <button type="button" id="pay_btns" onclick="sendPaymentDataToAnet()">Pay</button>
	</form>
</div>
</div>

<p>
<input type="button" id="sub_button" value="" class="popup" onclick="myFunction()"/>

	
</p>
</div>
<style type="text/css">
#sub_button{
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
</style>

<script>
var pay_type = 0;
 $(document).ready(function(){
 
   $('#card_form').hide();
   $('#account_form').hide();
   $('#pay_btns').hide();
 });
 
 
// When the user clicks on button, open the popup
function myFunction() {
  var popup = document.getElementById("myPopup");
  popup.classList.toggle("show");
}

$('#card_btn').click(function(){
    
         $('#card_form').show();
         $('#account_form').hide();
         $('#pay_btns').show();
         pay_type = 1;
    });
    
$('#account_btn').click(function(){

         $('#account_form').show();
         $('#card_form').hide();
         $('#pay_btns').show();
         pay_type = 2;
    });
    
    
//Accept js Operation Starts Here

function sendPaymentDataToAnet() {
    var authData = {};
        authData.clientKey = "<?php echo $clientkey; ?>";
        authData.apiLoginID = "<?php echo $loginid; ?>";

    var cardData = {};
        cardData.cardNumber = document.getElementById("cardNumber").value;
        cardData.month = document.getElementById("expMonth").value;
        cardData.year = document.getElementById("expYear").value;
        cardData.cardCode = document.getElementById("cardCode").value;

    // If using banking information instead of card information,
    // build a bankData object instead of a cardData object.
    //
    var bankData = {};
        bankData.accountNumber = document.getElementById('accountNumber').value;
        bankData.routingNumber = document.getElementById('routingNumber').value;
        bankData.nameOnAccount = document.getElementById('nameOnAccount').value;
        bankData.accountType = document.getElementById('accountType').value;
        
    var secureData = {};
    
    if(pay_type == 1) {
    
    
        secureData.authData = authData;
        secureData.cardData = cardData;
    }
    
    if(pay_type == 2) {
        secureData.authData = authData;
        secureData.bankData = bankData;
    }
        // If using banking information instead of card information,
        // send the bankData object instead of the cardData object.
        //
        // secureData.bankData = bankData;

    Accept.dispatchData(secureData, responseHandler);

    function responseHandler(response) {
        if (response.messages.resultCode === "Error") {
            var i = 0;
            while (i < response.messages.message.length) {
                alert(
                    response.messages.message[i].text
                );
                i = i + 1;
            }
        } else {
            paymentFormUpdate(response.opaqueData);
        }
    }
}

function paymentFormUpdate(opaqueData) {
    document.getElementById("dataDescriptor").value = opaqueData.dataDescriptor;
    document.getElementById("dataValue").value = opaqueData.dataValue;

    // If using your own form to collect the sensitive data from the customer,
    // blank out the fields before submitting them to your server.
    document.getElementById("cardNumber").value = "";
    document.getElementById("expMonth").value = "";
    document.getElementById("expYear").value = "";
    document.getElementById("cardCode").value = "";
    document.getElementById("accountNumber").value = "";
    document.getElementById("routingNumber").value = "";
    document.getElementById("nameOnAccount").value = "";
    document.getElementById("accountType").value = "";

    document.getElementById("paymentForm").submit();
}
</script>