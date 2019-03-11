jQuery(function($) {
    require('./prototypes/string')
    let ajax = require('./ajax')
    var shouldContinueCheckingStuffs = true
    var startedCheckingUserStatus = false
    var startedCheckingTransactionStatus = false
    var uniqueTransactionId = $('.uniqueTransactionIdHiddenForm').val()

    function checkTransactionStatus(callBack) {
        ajax.checkTransactionStatus(function(response) {
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
            let responseObject = response.response.tryParseJson()
            if (response.error || responseObject.errors || !(responseObject || responseObject[0] || responseObject[0].status)) {
                return showErrorAndCallCallback(responseObject.errors[0] || response.error || 'Error while checking your transaction\'s status', callBack)
            }
            var status = responseObject[0].status
            if (status == 'acceptedAndPaid') {
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
        $('.seedpay-number-form-pending').hide()
        $('.seedpay-number-form').fadeIn()
        $('.seedpay-messages').empty('')
        ajax.submitPaymentRequest($('#seedpayPhoneNumber').val(), function(response) {
            var responseResponseObject = (response || {
                'response': '',
            }).response.tryParseJson()
            if (response && responseResponseObject && responseResponseObject.message) {
                $('.seedpay-messages').html(responseResponseObject.message)
                if (responseResponseObject.message.toLowerCase().indexOf('inv') >= 0) {
                    checkUserStatus()
                    return
                }
            }
            if (response.error) {
                $('.seedpay-messages').html(response.error)
                return
            }
            uniqueTransactionId = response.request.uniqueTransactionId
            $('.uniqueTransactionIdHiddenForm').val(uniqueTransactionId)
            $('.seedpay-messages').html(responseResponseObject.message)
            $('.seedpay-number-form').fadeOut()
            $('.seedpay-number-form-pending').fadeIn()
            checkTransactionStatus()
        })
        return false
    }

    function checkUserStatus() {
        var phone = $('#seedpayPhoneNumber').val()
        jQuery.post(ajaxUrl, {
            'action': 'checkUserStatus',
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
