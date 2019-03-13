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
    generateGenericErrorMessage: (whileDoing) => {
        return `Error while ${whileDoing}.  Please try again or contact helpdesk@seedpay.com`
    },
    processAjaxResponse: ({
        response,
        errorHandler,
        messageHandler,
        successHandler,
        genericError,
    }) => {
        if (!response) {
            if (errorHandler) errorHandler(genericError || ajax.generateGenericErrorMessage('sending your request'))
            return
        }
        if (response.error || (response.response && response.response.errors && response.response.errors[0])) {
            if (errorHandler) errorHandler(response.error || response.response.errors[0])
            return
        }
        if (response.response && response.response.message) {
            if (messageHandler) messageHandler(response.response.message)
            return
        }
        if (response.response) {
            if (successHandler) successHandler(response.response)
            return
        }
    },
    checkUserStatus: async (phoneNumber) => {
        return await ajax.submitRequest({
            'action': 'checkUserStatus',
            phoneNumber,
        })
    },
    jQuery: require('jquery'),
}
export default ajax
