// const wallabyWebpack = require('wallaby-webpack')
// const webpackPostprocessor = wallabyWebpack({})
let prefix = 'assets/**/'
module.exports = (wallaby) => {
    return {
        env: {
            type: 'node',
        },
        setup: function(_) {
            require('./src/test/beforeAllTests')
        },
        files: [
            `${prefix}!(*+(Tests)).js`,
            `${prefix}beforeAllTests.js`,
        ],
        tests: [
            `${prefix}*Tests.js`,
            `!beforeAllTests.js`,
        ],
        testFramework: 'mocha',
        compilers: {
            '**/*.js': wallaby.compilers.babel(),
        },
        // postprocessor: webpackPostprocessor,
        // setup: function() {
        //     chai.use(chai.should)
        //     window.should = chai.should()
        //     window.__moduleBundler.loadTests()
        // },
    }
}
