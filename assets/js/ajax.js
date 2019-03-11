require('./prototypes/string')
let submitRequest = function({
    url,
    parameters,
    callback,
    jQuery = require('jquery'),
}) {
    jQuery.post(url, parameters, function(response) {
        if (callback) callback(response)
    })
}

function checkTransactionStatus(callBack) {
    jQuery.post(ajaxUrl, {
        'action': 'checkTransactionStatus',
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
export default {
    submitRequest,
    checkTransactionStatus,
}
