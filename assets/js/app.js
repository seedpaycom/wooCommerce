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
    submitPaymentRequestSuccessHandler,
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
        successHandler: submitPaymentRequestSuccessHandler,
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

let checkUserStatus = async ({
    errorHandler,
}) => {
    let response = ajax.processAjaxResponse({
        response: await ajax.checkUserStatus($('#seedpayPhoneNumber').val()),
        errorHandler,
        genericError: ajax.generateGenericErrorMessage('checking your account status'),
    })
    if (!response || !response.isRegistered) {
        if (!startedCheckingUserStatus) {
            startedCheckingUserStatus = true
            startUserCheckingLoop()
        }
        return false
    }
    $('.woocommerce-checkout').submit()
    return true
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
    $('.seedpayRequestingPaymentIndicator').show()
}
let messageHandler = (message) => {
    if (message.toLowerCase().indexOf('inv') >= 0) {
        checkUserStatus({
            errorHandler,
            messageHandler
        })
        return
    }
    $('.seedpayErrorMessage').html(message)
    resetPage()
}
let errorHandler = (errorMessage) => {
    resetPage()
    $('.seedpayErrorMessage').html(errorMessage)
    $('.seedpayRequestingPaymentIndicator').hide()
    $('.seedpayPhoneNumberPrompt').show()
    shouldContinueCheckingStuffs = false
    if (errorMessage.toLowerCase().indexOf('10 digits') < 0)
        ajax.generateNewTransactionId()
}

function startTransactionCheckingLoop() {
    if (shouldContinueCheckingStuffs) {
        setTimeout(() => {
            checkTransactionStatus()
            startTransactionCheckingLoop()
        }, 5000)
    } else startedCheckingTransactionStatus = false
}

let startUserCheckingLoop = async () => {
    if (shouldContinueCheckingStuffs) {
        setTimeout(async () => {
            let isRegistered = await checkUserStatus({
                errorHandler,
                messageHandler
            })
            if (!isRegistered) startUserCheckingLoop()
        }, 5000)
    } else startedCheckingUserStatus = false
}

function resetPage() {
    $('.seedpayRequestingPaymentIndicator').hide()
    $('.seedpayPhoneNumberPrompt').show()
    $('.seedpaySuccessMessage').hide()
    $('.seedpayErrorMessage').empty('')
}
let cleanPhoneNumber = () => {
    let cleanedUpPhoneNumber = $('#seedpayPhoneNumber').val().replace(/\D/g, '')
    if (cleanedUpPhoneNumber[0] == '1') cleanedUpPhoneNumber = cleanedUpPhoneNumber.substr(1)
    cleanedUpPhoneNumber = cleanedUpPhoneNumber.substr(0, 10)
    $('#seedpayPhoneNumber').val(cleanedUpPhoneNumber)
}
jQuery(($) => {
    setTimeout(() => {
        $('#seedpayPhoneNumber').on('input', cleanPhoneNumber);
    }, 5000)
    $('form.woocommerce-checkout').on('checkout_place_order', () => {
        if ($('#payment_method_seedpay').is(':checked')) {
            if (!paymentAccepted) shouldContinueCheckingStuffs = true
            cleanPhoneNumber()
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
})

export default {
    submitPaymentRequest,
    resetPage,
}
