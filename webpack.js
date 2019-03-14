let path = require('path')
module.exports = {
    entry: './assets/js/index.js',
    output: {
        path: path.join(__dirname, './assets/js'),
        filename: 'index.min.js',
    },
}
