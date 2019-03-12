import '@babel/polyfill'
import appConfig from './appConfig'
let ajax = require('./ajax').default
describe('submitRequest', () => {
    var options
    beforeEach(() => {
        options = {
            parameters: {
                'parameterz': 'o-boy',
            },
            jQuery: {
                post: (url, parameters) => {
                    options.postUrl = url
                    options.postAction = parameters.action
                    options.postParameters = parameters
                    return 'fake response'
                },
            },
        }
        options.oldAppConfig = Object.assign({}, appConfig)
    })
    afterEach(() => {
        appConfig.ajaxUrl = options.oldAppConfig.ajaxUrl
    })

    it('uses the appConfig`s ajax url', async () => {
        let url = 'yay me!'
        appConfig.ajaxUrl = url

        await ajax.submitRequest(options.parameters, options.jQuery)

        options.postUrl.should.equal(url)
    })
    it('posts with url and parameters', async () => {
        let url = 'yay me 2!'
        appConfig.ajaxUrl = url
        let parameters = {
            moobzor: 'wowozor',
        }

        await ajax.submitRequest(parameters, options.jQuery)

        options.postUrl.should.equal(url)
        options.postParameters.should.equal(parameters)
    })
})
