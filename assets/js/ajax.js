let appConfig = require('./appConfig').default
let ajax = {
    submitRequest: async function(parameters, jQuery = require('jquery')) {
        return await jQuery.post(appConfig.ajaxUrl, parameters)
    },

    checkTransactionStatus: async () => {
        return await ajax.submitRequest({
            'action': 'checkTransactionStatus',
        })
    },
    requestPayment: async (phoneNumber) => {
        return await ajax.submitRequest({
            'action': 'submitPaymentRequest',
            phoneNumber,
        })
    },
}
export default ajax
