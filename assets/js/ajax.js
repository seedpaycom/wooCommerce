require('./prototypes/string')
let appConfig = require('./appConfig').default
let ajax = {
    submitRequest: async function({
        parameters,
        callback,
        jQuery = require('jquery'),
    }) {
        jQuery.post(appConfig.ajaxUrl, parameters, (response) => {
            if (callback) callback(response)
        })
    },

    checkTransactionStatus: function(callBack) {
        ajax.submitRequest({
            'action': 'checkTransactionStatus',
        }, (response) => {
            if (callBack) callBack(response)
        })
    },
    submitPaymentRequest: function(phoneNumber, callBack) {
        ajax.submitRequest({
            'action': 'submitPaymentRequest',
            phoneNumber,
        }, (response) => {
            if (callBack) callBack(response)
        })
    },
}
export default ajax
