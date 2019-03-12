import '@babel/polyfill'
import appConfig from './appConfig'
let ajax = require('./ajax').default
describe('ajax', () => {
    var options
    beforeEach(() => {
        options = {
            parameters: {
                'parameterz': 'og parameters',
            },
            jQuery: {
                post: (url, parameters) => {
                    options.postUrl = url
                    options.postAction = parameters.action
                    options.postParameters = parameters
                    return 'fake response'
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

            await ajax.submitRequest(options.parameters, options.jQuery)

            options.postUrl.should.equal(url)
        })
        it('posts with given parameter', async () => {
            let parameters = {
                moobzor: 'wowozor',
            }

            await ajax.submitRequest(parameters, options.jQuery)

            options.postParameters.should.equal(parameters)
        })
    })
    describe('checkTransactionStatus', () => {
        it('posts with correct action', async () => {
            await ajax.checkTransactionStatus()

            options.postAction.should.equal('checkTransactionStatus')
        })
    })
    describe('requestPayment', () => {
        it('posts with correct action', async () => {
            await ajax.requestPayment()

            options.postAction.should.equal('requestPayment')
        })
    })
})
