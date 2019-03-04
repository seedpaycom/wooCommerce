import 'babel-polyfill'
let ajax = require('./ajax').default
describe('submitRequest', function() {
    var options
    beforeEach(() => {
        options = {
            url: 'urlz',
            action: 'actionz',
            parameters: 'parameterz',
            jQuery: {
                post: (url, actionAndParameters, callback) => {
                    options.postUrl = url
                    options.postAction = actionAndParameters.action //?
                    options.postParameters = actionAndParameters.parameters //?
                    callback()
                },
            },
            callback: (response) => {
                options.calledCallback = true
                options.response = response
            },
        }
    })
    it('jquery posts to the url', function() {
        let parameters = {
            phone: 'asdf',
        }
        let as = Object.assign({}, parameters) //?
        options.callback = (response) => {
            options.postUrl //?
        }
        ajax.submitRequest(options) //?
    })
})
