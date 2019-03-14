let path = require('path')
module.exports = {
    entry: './assets/js/app.js',
    output: {
        path: path.join(__dirname, './assets/js'),
        filename: 'app.min.js',
    },
}
