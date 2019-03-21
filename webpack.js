let path = require('path')
module.exports = {
    entry: './src/app.js',
    output: {
        path: path.join(__dirname, './assets/js'),
        filename: 'app.min.js',
    },
}
