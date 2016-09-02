/*
 * Unilend Dictionary Shortcut Module
 */

var Dictionary = require('Dictionary')

__ = new Dictionary(window.UTILITY_LANG)
// @debug
// console.log('AutoComplete: using window.UTILITY_LANG for Dictionary')

module.exports = __
