jQuery(function($) {
    var isCheckingUserStatus = false
    var isCheckingTransactionStatus = false
    var uniqueTransactionId = $('.seedpay_payment_cart_hash').val()

    function checkTransactionStatus(callBack) {
        let phone = $('#seedpay_payment_phone').val()
        if (!uniqueTransactionId && callBack) {
            callBack()
            return
        }
        jQuery.post(seedpay_params.ajax_url, {
            'action': 'ajax_seedpay_check_request',
            'transaction_id': uniqueTransactionId,
            phone,
        }, function(responseString) {
            var response = $.parseJSON(responseString)
            if (response.error || !response.response[0]) {
                $('.seedpay-messages').html(response.error)
                $('.seedpay-number-form-pending').hide()
                $('.seedpay-number-form').fadeIn()
                isCheckingTransactionStatus = false
                if (callBack) callBack({
                    error: response.error,
                })
                return response.error
            }
            let status = (response.response[0] || {}).status
            if (status == 'acceptedAndPaid') {
                $('.seedpay_payment_success').val(response.response[0].status)
                $('.seedpay-number-form').hide()
                $('.seedpay-number-form-pending').hide()
                $('.seedpay-number-form-success').fadeIn()
                $('.woocommerce-checkout').submit()
                isCheckingTransactionStatus = false
            } else if (status == 'rejected' || status == 'errored') {
                $('.seedpay-number-form-pending').hide()
                $('.seedpay-number-form').fadeIn()
                isCheckingTransactionStatus = false
            } else {
                isCheckingTransactionStatus = true
                setTimeout(function() {
                    if (isCheckingTransactionStatus) checkTransactionStatus()
                }, 5000)
            }
            if (callBack) callBack()
        })
    }
    if ($('.seedpay_recheck_payment').val() == 1) {
        if (!isCheckingTransactionStatus) checkTransactionStatus()
    }

    function resetForm() {
        $('.seedpay-number-form-pending').hide()
        $('.seedpay-number-form').fadeIn()
        $('.seedpay-messages').empty('')
        isCheckingTransactionStatus = false
        isCheckingUserStatus = false
    }
    $('form.woocommerce-checkout').on('checkout_place_order', function() {
        if ($('#payment_method_seedpay').is(':checked')) {
            if ($('#seedpay_payment_phone').val() != '') {
                submitPaymentRequest()
            }
            return true
        }
    })
    $(document).on('click', '.seedpay-cancel-payment-submit', function() {
        resetForm()
        return false
    })

    function submitPaymentRequest() {
        checkTransactionStatus(function() {
            resetForm()
            let phone = $('#seedpay_payment_phone').val()
            jQuery.post(seedpay_params.ajax_url, {
                'action': 'ajax_seedpay_submit_request',
                phone,
            }, function(responseString) {
                var response = $.parseJSON(responseString)
                if (response.response.message) {
                    $('.seedpay-messages').html(response.response.message)
                    if (response.response.message.toLowerCase().indexOf('inv') >= 0) {
                        if (!isCheckingUserStatus) checkUserStatus()
                        return
                    }
                }
                if (response.error) {
                    $('.seedpay-messages').html(response.error)
                    return
                }
                uniqueTransactionId = response.request.uniqueTransactionId
                $('.seedpay_payment_cart_hash').val(uniqueTransactionId)
                $('.seedpay-messages').html(response.response.message)
                $('.seedpay-number-form').fadeOut()
                $('.seedpay-number-form-pending').fadeIn()
                if (!isCheckingTransactionStatus) checkTransactionStatus()
            })
        })
    }

    function checkUserStatus() {
        let phone = $('#seedpay_payment_phone').val()
        jQuery.post(seedpay_params.ajax_url, {
            'action': 'ajax_checkUserStatus',
            phone,
        }, function(responseString) {
            var response = $.parseJSON(responseString).response
            if (response.isRegistered == true) {
                isCheckingUserStatus = false
                $('.seedpay_payment_registered').val(1)
                submitPaymentRequest()
            } else {
                $('.seedpay_payment_registered').val(0)
                $('.seedpay-messages').html('Please check your text messages for an invite.')
                isCheckingUserStatus = true
                setTimeout(function() {
                    if (isCheckingUserStatus) checkUserStatus(true)
                }, 6000)
            }
        })
    }
})
