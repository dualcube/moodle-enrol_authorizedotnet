define(['jquery', 'core/ajax'
    ],
    function($, ajax
    ) {
    return {
        authorizedotnet_payments: function(client_key, login_id, amount, instance_currency, transaction_key, instance_courseid, user_id, user_email, instance_id, context_id, description, invoice, sequence, timestamp, auth_mode, error_payment_text, requiredmissing) {
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

                var payment_card_number = document.getElementById("card-number").value;
                var month = document.getElementById("exp-month").value;
                var year = document.getElementById("exp-year").value;
                var card_code = document.getElementById("card-code").value;
                var firstname = document.getElementById("firstname").value;
                var lastname = document.getElementById("lastname").value;
                var address = document.getElementById("address").value;
                var zip = document.getElementById("zip").value;
                if(payment_card_number == '' || month == '' || year == '' || card_code == '' ||firstname == '' ||  lastname == '' || address == '' || zip == ''){
                    $("#payment_error").html('<p style="color:red;"><b>'+requiredmissing+'</b></p>');
                }
                else{
                    var promises = ajax.call([{
                        methodname: 'moodle_authorizedotnet_payprocess',
                        args: { 
                            client_key: client_key, 
                            login_id: login_id, 
                            amount: amount, 
                            instance_currency: instance_currency, 
                            transaction_key: transaction_key, 
                            instance_courseid: instance_courseid, 
                            user_id: user_id, 
                            user_email: user_email, 
                            instance_id: instance_id, 
                            context_id: context_id, 
                            description: description, 
                            invoice: invoice, 
                            sequence: sequence, 
                            timestamp: timestamp, 
                            payment_card_number: payment_card_number, 
                            month: month, 
                            year: year, 
                            card_code: card_code,
                            firstname: firstname,
                            lastname: lastname,
                            address: address,
                            zip: zip,
                            auth_mode: auth_mode
                        },
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
            }
            });
        }
    };
});