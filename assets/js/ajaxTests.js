import '@babel/polyfill'
import appConfig from './appConfig'
let ajax = require('./ajax').default
describe('ajax', () => {
    var options
    beforeEach(() => {
        options = {
            response: 'og response',
            parameters: {
                'parameterz': 'og parameters',
            },
            jQuery: {
                post: (url, parameters) => {
                    options.postUrl = url
                    options.postAction = parameters.action
                    options.postParameters = parameters
                    return options.response
                },
            },
            url: 'og url',
        }
        options.oldAppConfig = Object.assign({}, appConfig)
        appConfig.ajaxUrl = options.url
        ajax.jQuery = options.jQuery
    })
    afterEach(() => {
        appConfig.ajaxUrl = options.oldAppConfig.ajaxUrl
        ajax.jQuery = require('jquery')
    })
    describe('submitRequest', () => {
        it('uses the appConfig`s ajax url', async () => {
            let url = 'yay me!'
            appConfig.ajaxUrl = url

            await ajax.submitRequest(options.parameters)

            options.postUrl.should.equal(url)
        })
        it('posts with given parameter', async () => {
            let parameters = {
                moobzor: 'wowozor',
            }

            await ajax.submitRequest(parameters)

            options.postParameters.should.equal(parameters)
        })
        it('returns the jquery post response', async () => {
            let response = await ajax.submitRequest(options.parameters)

            response.should.equal(options.response)
        })
    })
    describe('checkTransactionStatus', () => {
        it('posts with correct action', async () => {
            await ajax.checkTransactionStatus()

            options.postAction.should.equal('checkTransactionStatus')
        })
        it('returns the jquery post response', async () => {
            let response = await ajax.checkTransactionStatus()

            response.should.equal(options.response)
        })
    })
    describe('requestPayment', () => {
        it('posts with correct action and phone number', async () => {
            let phoneNumber = 'omgeezus'
            await ajax.requestPayment(phoneNumber)

            options.postAction.should.equal('requestPayment')
            options.postParameters.phoneNumber.should.equal(phoneNumber)
        })
        it('returns the jquery post response', async () => {
            let response = await ajax.requestPayment()

            response.should.equal(options.response)
        })
    })
    describe('processRequestPaymentResponse', () => {
        it('calls the error handler with a generic error when wtf is given', async () => {
            ajax.processAjaxResponse({
                errorHandler: (errorMessage) => {
                    options.handlerCalled = true
                    errorMessage.should.equal(ajax.generateGenericErrorMessage('sending your request'))
                },
            })

            options.handlerCalled.should.be.true
        })
        it('calls the error handler with given generic error', async () => {
            let options = {
                genericError: 'oh wowzer',
                errorHandler: (errorMessage) => {
                    options.handlerCalled = true
                    errorMessage.should.equal(options.genericError)
                },
            }
            ajax.processAjaxResponse(options)

            options.handlerCalled.should.be.true
        })
        it('calls the error handler with the response.error', async () => {
            let response = {
                error: 'i amz errorzorz',
            }

            ajax.processAjaxResponse({
                response,
                errorHandler: (errorMessage) => {
                    options.handlerCalled = true
                    errorMessage.should.equal(response.error)
                },
            })

            options.handlerCalled.should.be.true
        })
        it('calls the error handler with the response.response.errors[0]', () => {
            let response = {
                response: {
                    errors: ['i amz bettor errorz', ],
                },
            }

            ajax.processAjaxResponse({
                response,
                errorHandler: (errorMessage) => {
                    options.handlerCalled = true
                    errorMessage.should.equal(response.response.errors[0])
                },
            })

            options.handlerCalled.should.be.true
        })
        it('calls the message handler with the response.response.message', () => {
            let response = {
                response: {
                    message: 'luuk at meezor',
                },
            }

            ajax.processAjaxResponse({
                response,
                messageHandler: (message) => {
                    options.handlerCalled = true
                    message.should.equal(response.response.message)
                },
            })

            options.handlerCalled.should.be.true
        })
        it('calls the success handler with the response.response', () => {
            let response = {
                response: {},
            }

            ajax.processAjaxResponse({
                response,
                successHandler: (successResponse) => {
                    options.handlerCalled = true
                    successResponse.should.equal(response.response)
                },
            })

            options.handlerCalled.should.be.true
        })
    })
})
