let appConfig = require('./appConfig').default
let ajax = {
    submitRequest: async function(parameters) {
        return await ajax.jQuery.post(appConfig.ajaxUrl, parameters)
    },

    checkTransactionStatus: async () => {
        return await ajax.submitRequest({
            'action': 'checkTransactionStatus',
        })
    },
    requestPayment: async (phoneNumber) => {
        return await ajax.submitRequest({
            'action': 'requestPayment',
            phoneNumber,
        })
    },
    jQuery: require('jquery'),
}
export default ajax
