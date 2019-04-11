// const wallabyWebpack = require('wallaby-webpack')
// const webpackPostprocessor = wallabyWebpack({})
let prefix = 'src/**/'
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
            `!${prefix}beforeAllTests.js`,
        ],
        testFramework: 'mocha',
        compilers: {
            '**/*.js': wallaby.compilers.babel(),
        },
    }
}
