import ajax from './ajax'
import appConfig from './appConfig'
import processTransaction from './processTransaction'
import transactionStatus from './transactionStatus'

appConfig.ajaxUrl = ajaxUrl
var shouldContinueCheckingStuffs = true
var startedCheckingUserStatus = false
var startedCheckingTransactionStatus = false

jQuery(($) => {
    $('form.woocommerce-checkout').on('checkout_place_order', async () => {
        if ($('#payment_method_seedpay').is(':checked')) {
            shouldContinueCheckingStuffs = true
            let cleanedUpPhoneNumber = $('#seedpayPhoneNumber').val().replace(/\D/g, '')
            if (cleanedUpPhoneNumber[0] == '1') cleanedUpPhoneNumber = cleanedUpPhoneNumber.substr(1)
            $('#seedpayPhoneNumber').val(cleanedUpPhoneNumber)
            submitPaymentRequest()
            return false
        }
        return true
    })
    $(document).on('click', '.seedpayCancelButton', () => {
        resetPage()
        shouldContinueCheckingStuffs = false
        return false
    })

    let submitPaymentRequest = async () => {
        resetPage()
        let maybeTransaction = ajax.processAjaxResponse({
            response: await ajax.requestPayment($('#seedpayPhoneNumber').val()),
            errorHandler,
            messageHandler,
            successHandler: transactionSuccessHandler,
            genericError: ajax.generateGenericErrorMessage('requesting payment'),
        })
        processTransaction({
            maybeTransaction,
            transactionStatusHandlers: {
                transactionStatus,
            },
        })
    }
    let transactionSuccessHandler = (transaction) => {
        $('.seedpayPhoneNumberPrompt').fadeOut()
        $('.seedpayRequestingPaymentIndicator').fadeIn()
        checkTransactionStatus()
    }
    let messageHandler = (message) => {
        $('.seedpayErrorMessage').html(message)
        if (message.toLowerCase().indexOf('inv') >= 0) {
            checkUserStatus()
            return false
        }
    }
    let errorHandler = (errorMessage) => {
        $('.seedpayErrorMessage').html(errorMessage)
        $('.seedpayRequestingPaymentIndicator').hide()
        $('.seedpayPhoneNumberPrompt').fadeIn()
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
            $('.seedpayPhoneNumberPrompt').hide()
            $('.seedpayRequestingPaymentIndicator').hide()
            $('.seedpaySuccessMessage').fadeIn()
            $('.woocommerce-checkout').submit()
            shouldContinueCheckingStuffs = false
        } else if (status == 'rejected' || status == 'errored') {
            $('.seedpayRequestingPaymentIndicator').hide()
            $('.seedpayPhoneNumberPrompt').fadeIn()
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

    function resetPage() {
        $('.seedpayRequestingPaymentIndicator').hide()
        $('.seedpayPhoneNumberPrompt').fadeIn()
        $('.seedpaySuccessMessage').hide()
        $('.seedpayErrorMessage').empty('')
    }

    function checkUserStatus() {
        var phone = $('#seedpayPhoneNumber').val()
        jQuery.post(ajaxUrl, {
            'action': 'checkUserStatus',
            phone,
        }, (responseString) => {
            var response = $.parseJSON(responseString).response
            if (response.isRegistered == true) {
                submitPaymentRequest()
                return
            }
            $('.seedpayErrorMessage').html('Please check your text messages for an invite.')
            shouldContinueCheckingStuffs = true
            if (!startedCheckingUserStatus) {
                startedCheckingUserStatus = true
                startUserCheckingLoop()
            }
        })
    }
})
