import '@babel/polyfill'
import appConfig from './appConfig'
let ajax = require('./ajax').default
describe('submitRequest', () => {
    var options
    beforeEach(() => {
        options = {
            url: 'urlz',
            action: 'actionz',
            parameters: 'parameterz',
            jQuery: {
                post: (url, actionAndParameters, callback) => {
                    options.postUrl = url
                    options.postAction = actionAndParameters.action
                    options.postParameters = actionAndParameters.parameters
                    callback()
                },
            },
            callback: (response) => {
                options.calledCallback = true
                options.response = response
            },
        }
    })

    it('uses the appConfig`s ajax url', () => {
        let url = 'yay me!'
        appConfig.ajaxUrl = url
        options.callback = () => {
            options.postUrl.should.equal(url)
        }
        ajax.submitRequest(options)
    })
    it('awaits', async () => {
        let response = await ajax.submitRequest()
    })
    it('posts to the url', () => {
        let parameters = {
            phone: 'asdf',
        }
        let as = Object.assign({
            'action': 'POST',
        }, parameters)
        options.callback = (response) => {
            options.postUrl
        }
        ajax.submitRequest(options)
    })
})
