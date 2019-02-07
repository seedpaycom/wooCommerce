jQuery(function($) {
    function seedpay_check_transaction(transaction_id, phone) {
        jQuery.post(seedpay_params.ajax_url, {
            'action': 'ajax_seedpay_check_request',
            'transaction_id': transaction_id,
            'phone': phone,
        }, function(response) {
            var obj = $.parseJSON(response)
            if (obj.error == '' && $('.seedpay_payment_cancel').val() == 0) {
                if (obj.response[0].status == 'acceptedAndPaid') {
                    $('.seedpay_payment_success').val(obj.response[0].status)
                    $('.seedpay-number-form').hide()
                    $('.seedpay-number-form-pending').hide()
                    $('.seedpay-number-form-success').fadeIn()
                    $('.woocommerce-checkout').submit()
                } else if (obj.response[0].status == 'rejected') {
                    $('.seedpay_payment_cancel').val('1')
                    $('.seedpay-number-form-pending').hide()
                    $('.seedpay-number-form').fadeIn()
                } else {
                    setTimeout(function() {
                        seedpay_check_transaction(transaction_id, phone)
                    }, 5000)
                }
            } else {
                $('.seedpay-messages').html(obj.error)
                $('.seedpay_payment_cancel').val('1')
                $('.seedpay-number-form-pending').hide()
                $('.seedpay-number-form').fadeIn()
                return obj.error
            }
        })
    }
    if ($('.seedpay_recheck_payment').val() == 1) {
        seedpay_check_transaction($('.seedpay_recheck_payment').attr('data-id'), $('.seedpay_recheck_payment').attr('data-pn'))
    }
    $(document).on('click', '.seedpay-cancel-payment-submit', function() {
        // $(".seedpay_payment_cancel").val('1');
        $('.seedpay-number-form-pending').hide()
        $('.seedpay-number-form').fadeIn()
        return false
    })
    $(document).on('click', '.seedpay-request-payment-submit', function() {
        $('.seedpay-messages').empty()
        $('.seedpay_payment_cancel').val('0')
        var phone = $('#seedpay_payment_phone').val()
        jQuery.post(seedpay_params.ajax_url, {
            'action': 'ajax_seedpay_submit_request',
            'phone': phone,
        }, function(response) {
            var obj = $.parseJSON(response)
            if (obj.error == '') {
                var transaction_id = obj.request.uniqueTransactionId
                $('.seedpay_payment_cart_hash').val(obj.request.uniqueTransactionId)
                $('.seedpay-messages').html(obj.response.message)
                $('.seedpay-number-form').fadeOut()
                $('.seedpay-number-form-pending').fadeIn()
                seedpay_check_transaction(transaction_id, phone)
            } else {
                $('.seedpay-messages').html(obj.error)
            }
        })
        return false
    })
})
