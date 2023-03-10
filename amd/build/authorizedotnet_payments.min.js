define(['jquery', 'core/ajax'], function($, ajax) {
    return {
        authorizedotnet_payments: function(instance_courseid, user_id, instance_id, amount) {
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
                            instance_courseid: instance_courseid, 
                            user_id: user_id,  
                            instance_id: instance_id, 
                            amount: amount,  
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
                    $('#final-payment-button').hide();
                    $('.loader').show();
                    
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