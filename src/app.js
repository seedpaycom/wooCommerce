import ajax from './ajax'
import appConfig from './appConfig'
import processTransaction from './processTransaction'
import jQuery from 'jquery'
var $ = jQuery

appConfig.ajaxUrl = ajaxUrl
var shouldContinueCheckingTransactionStatus = true
var startedCheckingTransactionStatus = false
var paymentAccepted = false
let submitPaymentRequest = async ({
    errorHandler,
    submitPaymentRequestSuccessHandler,
    transactionAccepted,
}) => {
    let submitPaymentRequestErrorHandler = (errorMessage) => {
        if (errorMessage.includes('payment already received')) return transactionAccepted()
        errorHandler(errorMessage)
    }
    let response = ajax.processAjaxResponse({
        response: await ajax.requestPayment($('#seedpayPhoneNumber').val()),
        errorHandler: submitPaymentRequestErrorHandler,
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
    shouldContinueCheckingTransactionStatus = false
    paymentAccepted = true
    $('#place_order').click()
}
let submitPaymentRequestSuccessHandler = (transaction) => {
    showWaitingToAcceptIndicator()
    checkTransactionStatus()
}
let showWaitingToAcceptIndicator = () => {
    $('.seedpayRequestingPaymentIndicator').show()
}
let errorHandler = (errorMessage) => {
    if (errorMessage.toLowerCase().indexOf('payment already received') >= 0) return
    resetPage()
    $('.seedpayErrorMessage').html(errorMessage)
    $('.seedpayRequestingPaymentIndicator').hide()
    $('.seedpayPhoneNumberPrompt').show()
    shouldContinueCheckingTransactionStatus = false
    if (errorMessage.toLowerCase().indexOf('10 digit') < 0 &&
        errorMessage.toLowerCase().indexOf('payment processing') < 0)
        ajax.generateNewTransactionId()
}

function startTransactionCheckingLoop() {
    if (shouldContinueCheckingTransactionStatus) {
        setTimeout(() => {
            checkTransactionStatus()
            startTransactionCheckingLoop()
        }, 5000)
    } else startedCheckingTransactionStatus = false
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
let isPhoneNumberValid = () => {
    let phoneNumber = $('#seedpayPhoneNumber').val()
    if (phoneNumber.length != 10) {
        $('#seedpayPhoneNumber').focus()
        return false
    }
    return true
}
jQuery(($) => {
    let bindStuffs = () => {
        $('#seedpayPhoneNumber').off('change').on('change', cleanPhoneNumber)
        $('#place_order').off('click').click((event) => {
            if ($('#payment_method_seedpay').is(':checked')) {
                let emptyRequiredFields = $('.required').filter((_, requiredField) => {
                    if ($(requiredField).parent().parent().find('input').length) {
                        return !$(requiredField).parent().parent().find('input').val()
                    }
                    return false
                })
                if (paymentAccepted ||
                    $('.form-row.woocommerce-invalid').length ||
                    emptyRequiredFields.length) {
                    resetPage()
                    shouldContinueCheckingTransactionStatus = false
                    bindAllTheStuffsAfterDelay()
                    return true
                }
                if (!paymentAccepted && event && event.preventDefault) event.preventDefault()
                if (!paymentAccepted) shouldContinueCheckingTransactionStatus = true
                cleanPhoneNumber()
                resetPage()
                if (!isPhoneNumberValid()) {
                    errorHandler('Please enter a valid 10 digit phone number')
                    event.preventDefault()
                    return false
                }
                showWaitingToAcceptIndicator()
                submitPaymentRequest({
                    errorHandler,
                    submitPaymentRequestSuccessHandler,
                    transactionAccepted,
                    pendingTransactionHandler,
                })
            }
            return true
        })
        return true
    }
    let bindAllTheStuffsAfterDelay = () => {
        bindStuffs()
        setTimeout(() => {
            bindStuffs()
        }, 1000)
        setTimeout(() => {
            bindStuffs()
        }, 2000)
        setTimeout(() => {
            bindStuffs()
        }, 3000)
        setTimeout(() => {
            bindStuffs()
        }, 5000)
        setTimeout(() => {
            bindStuffs()
        }, 7000)
        setTimeout(() => {
            bindStuffs()
        }, 10000)
    }
    bindAllTheStuffsAfterDelay()
    //rebind stuffs when the update_order_review ajax call returns.  This is real dumb. 
    var observer = new MutationObserver((bindStuffs))
    var config = {
        attributes: true,
    }
    observer.observe(document.body, config)
})
