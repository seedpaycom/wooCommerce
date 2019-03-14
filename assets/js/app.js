import ajax from './ajax'
import appConfig from './appConfig'
import transactionStatus from './transactionStatus'

appConfig.ajaxUrl = ajaxUrl
var shouldContinueCheckingStuffs = true
var startedCheckingUserStatus = false
var startedCheckingTransactionStatus = false

let submitPaymentRequest = async ({
    errorHandler,
    messageHandler,
    requestSuccessHandler,
    receivedTransaction,
    transactionAccepted,
}) => {
    let maybeTransaction = ajax.processAjaxResponse({
        response: await ajax.requestPayment($('#seedpayPhoneNumber').val()),
        errorHandler,
        messageHandler,
        successHandler: requestSuccessHandler,
        pendingTransactionHandler,
        genericError: ajax.generateGenericErrorMessage('requesting payment'),
    })
    if (!maybeTransaction) return null
    let transaction = maybeTransaction.transaction || maybeTransaction
    receivedTransaction({
        transaction,
        errorHandler,
        transactionAccepted,
        pendingTransactionHandler,
    })
    return transaction
}
let pendingTransactionHandler = () => {
    if (!startedCheckingTransactionStatus) {
        startedCheckingTransactionStatus = true
        startTransactionCheckingLoop()
    }
}
let transactionAccepted = () => {
    $('.seedpayPhoneNumberPrompt').hide()
    $('.seedpayRequestingPaymentIndicator').hide()
    $('.seedpaySuccessMessage').fadeIn()
    $('.woocommerce-checkout').submit()
    shouldContinueCheckingStuffs = false
}
let submitPaymentRequestSuccessHandler = (transaction) => {
    $('.seedpayPhoneNumberPrompt').fadeOut(0.2, 'linear', () => {
        $('.seedpayRequestingPaymentIndicator').fadeIn()
    })
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

jQuery(($) => {
    $('form.woocommerce-checkout').on('checkout_place_order', async () => {
        if ($('#payment_method_seedpay').is(':checked')) {
            shouldContinueCheckingStuffs = true
            let cleanedUpPhoneNumber = $('#seedpayPhoneNumber').val().replace(/\D/g, '')
            if (cleanedUpPhoneNumber[0] == '1') cleanedUpPhoneNumber = cleanedUpPhoneNumber.substr(1)
            $('#seedpayPhoneNumber').val(cleanedUpPhoneNumber)

            submitPaymentRequest({
                errorHandler,
                messageHandler,
                submitPaymentRequestSuccessHandler,
                receivedTransaction,
                transactionAccepted,
                pendingTransactionHandler,
            })
            return false
        }
        return true
    })
    $(document).on('click', '.seedpayCancelButton', () => {
        resetPage()
        shouldContinueCheckingStuffs = false
        return false
    })
})

export default {
    submitPaymentRequest,
    resetPage,
}
