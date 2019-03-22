console.log('beforingAllTests') //eslint-disable-line
let chai = require('chai')
let should = chai.should
global.should = should()
chai.use(should)
global.ajaxUrl = 'ajaxUrlFromBeforeAllTests'
require('@babel/polyfill')
let jsdomClassMaker = require('jsdom').JSDOM
const jsdom = new jsdomClassMaker()
var window = jsdom.window
global.window = window
global.document = window.document
global.$ = global.jQuery = require('jquery')
module.exports = () => {}
