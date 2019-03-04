let path = require('path')
module.exports = {
    entry: './assets/js/scripts.js',
    output: {
        path: path.join(__dirname, './assets/js'),
        filename: 'scripts.min.js',
        libraryTarget: 'commonjs',
    },
}
