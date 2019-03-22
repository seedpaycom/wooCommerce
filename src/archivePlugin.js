var fs = require('fs')
var archiver = require('archiver')
var output = fs.createWriteStream(__dirname + '/../seedpay.zip')
var archive = archiver('zip', {
    zlib: {
        level: 0,
    },
})
output.on('close', () => {
    console.log(archive.pointer() + ' total bytes') //eslint-disable-line
    console.log('archiver has been finalized and the output file descriptor has closed.') //eslint-disable-line
})
output.on('end', () => {
    console.log('Data has been drained') //eslint-disable-line
})
archive.on('warning', (err) => {
    if (err.code === 'ENOENT') {
        console.log(err) //eslint-disable-line
    } else {
        throw err
    }
})
archive.on('error', (err) => {
    throw err
})
archive.pipe(output)
archive.glob('**/*', {
    cwd: __dirname + '/../',
    ignore: [
        'src/**',
        'node_modules/**',
        'vendor/**',
        'seedpay.zip',
        'seedpay/**',
        'changelog.txt',
        '.babelrc',
        '.eslintrc.json',
        '.gitignore',
        '.jsbeautifyrc',
        '.phpunit-watcher.yml',
        '.travis.yml',
        '.vscode/**',
        'composer.json',
        'composer.lock',
        'package-lock.json',
        'package.json',
        'phpunit.xml',
        'README.md',
        'readme.txt',
        'transactionId.php',
        'wallaby.js',
        'webpack.js',
        '**/*Tests.php',
        '**/app.css',
    ],
})
archive.finalize()
