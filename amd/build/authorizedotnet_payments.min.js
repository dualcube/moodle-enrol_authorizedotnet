define(['jquery', 'core/ajax'
    ],
    function($, ajax
    ) {
    return {
        authorizedotnet_payments: function(client_key, login_id, amount, instance_currency, transaction_key, instance_courseid, user_id, user_email, instance_id, context_id, description, invoice, sequence, timestamp, error_payment_text, requiredmissing) {
            var pay_type = 0;
            $(document).ready(function() {
                $('.loader').hide();
            });


            $("#final-payment-button").click(function() {
                $("#payment_error").html(' ');
                var payment_card_number = $("#card-number").val();
                var month = $("#exp-month").val();
                var year = $("#exp-year").val();
                var card_code = $("#card-code").val();
                var firstname = $("#firstname").val();
                var lastname = $("#lastname").val();
                var address = $("#address").val();
                var zip = $("#zip").val();
                if(payment_card_number == '' || month == '' || year == '' || card_code == '' ||firstname == '' ||  lastname == '' || address == '' || zip == ''){
                    $("#payment_error").html('<p style="color:red;">'+requiredmissing+'</p>');
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
                        },
                    }]);
                    $('.loader').show();
                    $('#final-payment-button').hide();
                    
                    promises[0].then(function(data) {
                        console.log(data.status);
                        $('.loader').hide();
                        $('#final-payment-button').show();
                        if (data.status == 'success') {
                            location.reload();
                        } else {
                            $("#payment_error").html('<p style="color:red;">'+data.status+'</p>');
                        }
                    }).fail(function(ex) { // do something with the exception 
                        $('.loader').hide();
                        $('#final-payment-button').show();
                        $("#payment_error").html('<p style="color:red;">'+ex.error+'</p>');
                    });
                }
            });
        }
    };
});