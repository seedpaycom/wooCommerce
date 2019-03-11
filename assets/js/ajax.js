require('./prototypes/string')
let ajax = {
    submitRequest: function({
        parameters,
        callback,
        jQuery = require('jquery'),
        ajaxUrl = ajax.ajaxUrl,
    }) {
        // if()
        jQuery.post(ajaxUrl, parameters, function(response) {
            if (callback) callback(response)
        })
    },

    checkTransactionStatus: function(callBack) {
        ajax.submitRequest({
            'action': 'checkTransactionStatus',
        }, function(response) {
            if (callBack) callBack(response)
        })
    },
    submitPaymentRequest: function(phoneNumber, callBack) {
        ajax.submitRequest({
            'action': 'submitPaymentRequest',
            phoneNumber,
        }, function(response) {
            if (callBack) callBack(response)
        })
    },
}
export default ajax
