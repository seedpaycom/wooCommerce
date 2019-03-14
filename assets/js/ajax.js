let appConfig = require('./appConfig').default
let ajax = {
    submitRequest: async (parameters) => {
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
        let responseDotResponse = typeof response.response == typeof {} ? response.response : null
        if (response.error || (response.response && response.response.errors && response.response.errors[0])) {
            if (errorHandler) errorHandler(response.error || response.response.errors[0])
            return responseDotResponse
        }
        if (response.response && response.response.message) {
            if (messageHandler) messageHandler(response.response.message)
            return responseDotResponse
        }
        if (responseDotResponse && successHandler) successHandler(response.response)
        return responseDotResponse
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
