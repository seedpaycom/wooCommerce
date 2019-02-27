jQuery(function($) {
    var shouldContinueCheckingStuffs = true
    var startedCheckingUserStatus = false
    var startedCheckingTransactionStatus = false
    var uniqueTransactionId = $('.seedpay_payment_cart_hash').val()

    function checkTransactionStatus(callBack) {
        var phone = $('#seedpayPhoneNumber').val()
        if (!uniqueTransactionId && callBack) {
            callBack()
            return
        }
        jQuery.post(seedpay_params.ajax_url, {
            'action': 'checkTransactionStatus',
            'transaction_id': uniqueTransactionId,
            phone,
        }, function(response) {
            var showErrorAndCallCallback = function(errorMessage, callBack) {
                $('.seedpay-messages').html(errorMessage)
                $('.seedpay-number-form-pending').hide()
                $('.seedpay-number-form').fadeIn()
                shouldContinueCheckingStuffs = false
                var errorWrappedError = {
                    error: errorMessage,
                }
                if (callBack) callBack(errorWrappedError)
                return errorWrappedError
            }
            if (typeof response == typeof '') {
                return showErrorAndCallCallback(response, callBack)
            }
            if ((response && response.error) || (response.response && !response.response[0])) {
                return showErrorAndCallCallback(response.error, callBack)
            }
            var status = (response.response[0] || {}).status
            if (status == 'acceptedAndPaid') {
                $('.seedpay_payment_success').val(response.response[0].status)
                $('.seedpay-number-form').hide()
                $('.seedpay-number-form-pending').hide()
                $('.seedpay-number-form-success').fadeIn()
                $('.woocommerce-checkout').submit()
                shouldContinueCheckingStuffs = false
            } else if (status == 'rejected' || status == 'errored') {
                $('.seedpay-number-form-pending').hide()
                $('.seedpay-number-form').fadeIn()
                shouldContinueCheckingStuffs = false
            } else {
                if (!startedCheckingTransactionStatus) {
                    startedCheckingTransactionStatus = true
                    startTransactionCheckingLoop()
                }
            }
            if (callBack) callBack()
        })
    }

    function startTransactionCheckingLoop() {
        if (shouldContinueCheckingStuffs) {
            setTimeout(function() {
                checkTransactionStatus()
                startTransactionCheckingLoop()
            }, 5000)
        } else startedCheckingTransactionStatus = false
    }

    function startUserCheckingLoop() {
        if (shouldContinueCheckingStuffs) {
            setTimeout(function() {
                checkUserStatus()
                startUserCheckingLoop()
            }, 5000)
        } else startedCheckingUserStatus = false
    }

    function resetForm() {
        $('.seedpay-number-form-pending').hide()
        $('.seedpay-number-form').fadeIn()
        $('.seedpay-messages').empty('')
        shouldContinueCheckingStuffs = false
    }

    function submitPaymentRequest() {
        checkTransactionStatus(function(response) {
            if (response && response.error) {
                return
            }
            $('.seedpay-number-form-pending').hide()
            $('.seedpay-number-form').fadeIn()
            $('.seedpay-messages').empty('')
            var phone = $('#seedpayPhoneNumber').val()
            jQuery.post(seedpay_params.ajax_url, {
                'action': 'requestPayment',
                phone,
            }, function(responseString) {
                var response = $.parseJSON(responseString)
                if (response.response.message) {
                    $('.seedpay-messages').html(response.response.message)
                    if (response.response.message.toLowerCase().indexOf('inv') >= 0) {
                        checkUserStatus()
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
                checkTransactionStatus()
            })
        })
        return false
    }

    function checkUserStatus() {
        var phone = $('#seedpayPhoneNumber').val()
        jQuery.post(seedpay_params.ajax_url, {
            'action': 'ajax_checkUserStatus',
            phone,
        }, function(responseString) {
            var response = $.parseJSON(responseString).response
            if (response.isRegistered == true) {
                $('.seedpay_payment_registered').val(1)
                submitPaymentRequest()
                return
            }
            $('.seedpay_payment_registered').val(0)
            $('.seedpay-messages').html('Please check your text messages for an invite.')
            shouldContinueCheckingStuffs = true
            if (!startedCheckingUserStatus) {
                startedCheckingUserStatus = true
                startUserCheckingLoop()
            }
        })
    }

    $('form.woocommerce-checkout').on('checkout_place_order', function() {
        if ($('#payment_method_seedpay').is(':checked')) {
            shouldContinueCheckingStuffs = true
            $('#seedpayPhoneNumber').val($('#seedpayPhoneNumber').val().replace(/\D/g, ''))
            submitPaymentRequest()
            return false
        }
    })
    $(document).on('click', '.seedpay-cancel-payment-submit', function() {
        resetForm()
        return false
    })
    if ($('.seedpay_recheck_payment').val() == 1) {
        checkTransactionStatus()
    }
})
