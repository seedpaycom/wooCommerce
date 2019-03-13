jQuery(($) => {
    require('./prototypes/string')
    let ajax = require('./ajax').default
    let appConfig = require('./appConfig').default
    appConfig.ajaxUrl = ajaxUrl

    var shouldContinueCheckingStuffs = true
    var startedCheckingUserStatus = false
    var startedCheckingTransactionStatus = false
    var uniqueTransactionId = $('.uniqueTransactionIdHiddenForm').val()

    $('form.woocommerce-checkout').on('checkout_place_order', async () => {
        if ($('#payment_method_seedpay').is(':checked')) {
            shouldContinueCheckingStuffs = true
            let cleanedUpPhoneNumber = $('#seedpayPhoneNumber').val().replace(/\D/g, '')
            if (cleanedUpPhoneNumber[0] == '1') cleanedUpPhoneNumber = cleanedUpPhoneNumber.substr(1)
            $('#seedpayPhoneNumber').val(cleanedUpPhoneNumber)
            return await submitPaymentRequest()
        }
        return true
    })
    $(document).on('click', '.seedpay-cancel-payment-submit', () => {
        resetForm()
        return false
    })
    if ($('.seedpay_recheck_payment').val() == 1) {
        checkTransactionStatus()
    }

    let submitPaymentRequest = async () => {
        $('.seedpay-number-form-pending').hide()
        $('.seedpay-number-form').fadeIn()
        $('.seedpay-messages').empty('')

        let response = await ajax.requestPayment($('#seedpayPhoneNumber').val())
        ajax.processAjaxResponse({
            response,
            handleError: errorHandler,
            messageHandler,
        })

        uniqueTransactionId = response.request.uniqueTransactionId
        $('.uniqueTransactionIdHiddenForm').val(uniqueTransactionId)
        $('.seedpay-messages').html(response.response.message)
        $('.seedpay-number-form').fadeOut()
        $('.seedpay-number-form-pending').fadeIn()
        checkTransactionStatus()
        return false
    }
    let messageHandler = (message) => {
        $('.seedpay-messages').html(message)
        if (message.toLowerCase().indexOf('inv') >= 0) {
            checkUserStatus()
            return false
        }
    }
    let errorHandler = (errorMessage) => {
        $('.seedpay-messages').html(errorMessage)
        $('.seedpay-number-form-pending').hide()
        $('.seedpay-number-form').fadeIn()
        shouldContinueCheckingStuffs = false
        let errorWrappedError = {
            error: errorMessage,
        }
        return errorWrappedError
    }
    let checkTransactionStatus = async () => {
        let response = await ajax.checkTransactionStatus()

        if (typeof response == typeof '') {
            return errorHandler(response)
        }
        let responseObject = response.response.tryParseJson()
        if (response.error || responseObject.errors || !(responseObject || responseObject[0] || responseObject[0].status)) {
            return errorHandler(responseObject.errors[0] || response.error || 'Error while checking your transaction\'s status')
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

    }

    function startTransactionCheckingLoop() {
        if (shouldContinueCheckingStuffs) {
            setTimeout(() => {
                checkTransactionStatus()
                startTransactionCheckingLoop()
            }, 5000)
        } else startedCheckingTransactionStatus = false
    }

    function startUserCheckingLoop() {
        if (shouldContinueCheckingStuffs) {
            setTimeout(() => {
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

    function checkUserStatus() {
        var phone = $('#seedpayPhoneNumber').val()
        jQuery.post(ajaxUrl, {
            'action': 'checkUserStatus',
            phone,
        }, (responseString) => {
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
})
