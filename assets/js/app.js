import ajax from './ajax'
import appConfig from './appConfig'
import processTransaction from './processTransaction'

appConfig.ajaxUrl = ajaxUrl
var shouldContinueCheckingStuffs = true
var startedCheckingUserStatus = false
var startedCheckingTransactionStatus = false
var paymentAccepted = false
let submitPaymentRequest = async ({
    errorHandler,
    messageHandler,
    requestSuccessHandler,
    transactionAccepted,
}) => {
    let submitPaymentRequestErrorHandler = (errorMessage) => {
        if (errorMessage.includes('received')) return transactionAccepted()
        errorHandler(errorMessage)
    }
    let response = ajax.processAjaxResponse({
        response: await ajax.requestPayment($('#seedpayPhoneNumber').val()),
        errorHandler: submitPaymentRequestErrorHandler,
        messageHandler,
        successHandler: requestSuccessHandler,
        genericError: ajax.generateGenericErrorMessage('requesting payment'),
    })
    if (!response) return false
    return processTransaction({
        transaction: response.transaction,
        errorHandler,
        transactionAccepted,
        pendingTransactionHandler,
    })
}

let checkTransactionStatus = async () => {
    let response = ajax.processAjaxResponse({
        response: await ajax.checkTransactionStatus(),
        errorHandler,
        messageHandler,
        pendingTransactionHandler,
        genericError: ajax.generateGenericErrorMessage('checking your transaction status'),
    })
    if (!response || !response.transactions) return null
    let transaction = response.transactions[0]
    processTransaction({
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
        showWaitingToAcceptIndicator()
        startTransactionCheckingLoop()
    }
}
let transactionAccepted = () => {
    $('.seedpayPhoneNumberPrompt').hide()
    $('.seedpayRequestingPaymentIndicator').hide()
    $('.seedpaySuccessMessage').show()
    $('.woocommerce-checkout').submit()
    shouldContinueCheckingStuffs = false
    paymentAccepted = true
    $('form.woocommerce-checkout').submit()
}
let submitPaymentRequestSuccessHandler = (transaction) => {
    showWaitingToAcceptIndicator()
    checkTransactionStatus()
}
let showWaitingToAcceptIndicator = () => {
    $('.seedpayPhoneNumberPrompt').hide()
    $('.seedpayRequestingPaymentIndicator').show()
}
let messageHandler = (message) => {
    resetPage()
    $('.seedpayErrorMessage').html(message)
    if (message.toLowerCase().indexOf('inv') >= 0) {
        checkUserStatus()
        return false
    }
}
let errorHandler = (errorMessage) => {
    resetPage()
    $('.seedpayErrorMessage').html(errorMessage)
    $('.seedpayRequestingPaymentIndicator').hide()
    $('.seedpayPhoneNumberPrompt').show()
    shouldContinueCheckingStuffs = false
    let errorWrappedError = {
        error: errorMessage,
    }
    ajax.generateNewTransactionId()
    return errorWrappedError
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
    $('.seedpayPhoneNumberPrompt').show()
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
    $('form.woocommerce-checkout').on('checkout_place_order', () => {
        if ($('#payment_method_seedpay').is(':checked')) {
            if (!paymentAccepted) shouldContinueCheckingStuffs = true
            let cleanedUpPhoneNumber = $('#seedpayPhoneNumber').val().replace(/\D/g, '')
            if (cleanedUpPhoneNumber[0] == '1') cleanedUpPhoneNumber = cleanedUpPhoneNumber.substr(1)
            $('#seedpayPhoneNumber').val(cleanedUpPhoneNumber)
            resetPage()
            showWaitingToAcceptIndicator()
            submitPaymentRequest({
                errorHandler,
                messageHandler,
                submitPaymentRequestSuccessHandler,
                transactionAccepted,
                pendingTransactionHandler,
            })

            if (!paymentAccepted && event && event.preventDefault) event.preventDefault()
            return paymentAccepted
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
