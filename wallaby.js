const wallabyWebpack = require('wallaby-webpack')
const webpackPostprocessor = wallabyWebpack({})
let prefix = 'assets/**/'
module.exports = function(wallaby) {
    return {
        files: [
            // loading chai globally
            {
                pattern: 'node_modules/chai/chai.js',
                instrument: false,
            },
            {
                pattern: `${prefix}!(*+(Tests)).js`,
                load: false,
            },
        ],
        tests: [{
            pattern: `${prefix}*Tests.js`,
            load: false,
        }, ],
        compilers: {
            '**/*.js': wallaby.compilers.babel(),
        },
        postprocessor: webpackPostprocessor,
        setup: function() {
            // window.expect = chai.expect
            // chai.use(chai.should)
            // window.should = chai.should
            window.__moduleBundler.loadTests()
        },
    }
}
