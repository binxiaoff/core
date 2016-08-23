/*
 * Unilend Dictionary Shortcut Module
 */

var Dictionary = require('Dictionary')
var UNILEND_LANG_LEGACY = require('../../../lang/Unilend.lang.json')

// -- Support new translation dictionary language format, e.g. `example-section-name_example-translation-key-name`
if (window.UTILITY_LANG) {
  __ = new Dictionary(window.UTILITY_LANG)
  // @debug
  // console.log('AutoComplete: using window.UTILITY_LANG for Dictionary')

// -- Support new legacy dictionary language format for fallbacks, e.g. `exampleTranslationKeyName`
} else {
  __ = new Dictionary(UNILEND_LANG_LEGACY, {
    legacyMode: true
  })
  // @debug
  console.log('__: using UNILEND_LANG_LEGACY for Dictionary. Please ensure window.UTILITY_LANG is correctly set.')
}

module.exports = __
