/*
 * Dictionary Shortcut
 */

var $ = require('jquery')
var Dictionary = require('Dictionary')
var UNILEND_LANG = require('../../../lang/Unilend.lang.json')
var __ = new Dictionary(UNILEND_LANG, $('html').attr('lang') || 'fr')

module.exports = __
