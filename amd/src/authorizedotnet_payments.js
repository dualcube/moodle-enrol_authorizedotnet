define(['jquery', 'core/ajax'
    ],
    function($, ajax
    ) {
    return {
        authorizedotnet_payments: function(clientkey, loginid, amount, instance_currency, transactionkey, instance_courseid, USER_id, USER_email, instance_id, context_id, description, invoice, sequence, timestamp, auth_modess, error_payment_text) {
            var pay_type = 0;
            $(document).ready(function() {
                $('.loader').hide();
            });

            // When the user clicks on button, open the popup
            $("#open-creditcard-popup").click(function() {
                var popup = document.getElementById("net-pay-popup");
                popup.classList.toggle("show");
            });

            $("#final-payment-button").click(function() {
                var authData = {};
                authData.clientKey = clientkey;
                authData.apiLoginID = loginid;

                var cardNumber = document.getElementById("cardNumber").value;
                var month = document.getElementById("expMonth").value;
                var year = document.getElementById("expYear").value;
                var cardCode = document.getElementById("cardCode").value;

                var promises = ajax.call([{
                    methodname: 'moodle_authorizedotnet_payprocess',
                    args: { clientkey: clientkey, loginid: loginid, amount: amount, instance_currency: instance_currency, transactionkey: transactionkey, instance_courseid: instance_courseid, USER_id: USER_id, USER_email: USER_email, instance_id: instance_id, context_id: context_id, description: description, invoice: invoice, sequence: sequence, timestamp: timestamp, cardNumber: cardNumber, month: month, year: year, cardCode: cardCode, auth_modess: auth_modess},
                }]);
                $('.loader').show();
                $('#final-payment-button').hide();
                
                promises[0].then(function(data) {
                    console.log(data.status);
                    $('.loader').hide();
                    $('#final-payment-button').show();
                    if (data.status == 'error') {
                        $("#payment_error").html('<p style="color:red;"><b>'+error_payment_text+'</b></p>');
                    } else {
                        location.reload();
                    }
                }).fail(function(ex) { // do something with the exception 
                    $('.loader').hide();
                    $('#final-payment-button').show();
                    $("#payment_error").html('<p style="color:red;"><b>'+ex.error+'</b></p>');
                });
            });
        }
    };
});