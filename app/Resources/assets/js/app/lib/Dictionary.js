/*
 * Unilend Dictionary
 * Enables looking up text to change per user's lang
 * Works same as most i18n functions
 * This is also used by Twig to look up dictionary entries
 * See gulpfile.js to see how it is loaded into Twig
 */

/*
 * Jira#DEV-747
 * Supporting multiple languages within a single dictionary has been deprecated.
 * To support backwards compatibility, dictionaries still support the `"lang": {Object}` structure, however only 1 lang key will exist.
 * Language translation key naming format has changed from camelCase to snake_kebab-case
 */

// Kebab to Camel
// See: http://stackoverflow.com/a/2970667
function kebabToCamel (str) {
  return str.replace(/(?:^\w|[A-Z]|\b\w|\s+)/g, function(match, index) {
    if (+match === 0) return "" // or if (/\s+/.test(match)) for white spaces
    return index == 0 ? match.toLowerCase() : match.toUpperCase()
  }).replace(/[_-\s]+/g, '')
}

// Camel to Kebab
function camelToKebab (str) {
  // See: http://stackoverflow.com/a/34680912
  return str.replace(/([A-Z](?=[A-Z][a-z])|[^A-Z](?=[A-Z])|[a-zA-Z](?=[^a-zA-Z]))/g, function(match, index) {
    return match + '-'
  }).toLowerCase()
}

// Create/load a new dictionary
//
// @class Dictionary
// @param {Object} dictionary A JS object which contains multiple language options,
//                            e.g. `{"en": {..}, "fr": {..}}`
// @param {Object} options An {Object} with the properties below:
// @param {String} keyPrefix a prefix to add at the start of every key reference
// @param {Boolean} legacyMode Switch to legacy mode, which converts keys from snake/kebab-case to camelCase
// @param {String} lang The default language to reference
var Dictionary = function (dictionary, options) {
  var self = this

  // Error if no dictionary object given
  if (!dictionary) return

  // Default settings and options
  var settings = $.extend({
    lang: 'fr_FR',
    legacyMode: false,
    keyPrefix: null
  } , options);

  // Properties
  self.defaultLang = settings.lang
  self.legacyMode = settings.legacyMode
  self.keyPrefix = settings.keyPrefix
  self.dictionary = {}

  // Provided dictionary doesn't have any lang keys, so it refers to only one lang (set as `lang` or as `defaultLang`)
  if (!dictionary.hasOwnProperty(self.defaultLang)) {
    self.dictionary[self.defaultLang] = dictionary
  } else {
    self.dictionary = dictionary
  }

  return self
}

// Get a message within the dictionary
// @method __
// @param {String} fallbackText The text to use if the textKey isn't within the dictionary
// @param {String} textKey The key which corresponds to the dictionary entry to use
// @param {String} lang The lang code to reference within the dictionary (DEPRECATED)
// @returns {String}
Dictionary.prototype.__ = function (fallbackText, textKey, lang) {
  var self = this
  lang = lang || self.defaultLang

  // Set empty fallback text (this avoids outputting 'undefined')
  if (typeof fallbackText !== 'string') fallbackText = ''

  // Ensure dictionary supports lang
  if (!self.supportsLang(lang)) {
    // See if general language exists
    if (lang.match(/[_-]/)) lang = lang.split(/[_-]/)[0]

    // Go to default
    if (!self.supportsLang(lang)) lang = self.defaultLang

    // Default not supported? Use the first lang entry in the dictionary
    if (!self.supportsLang(lang)) {
      for (x in self.dictionary) {
        lang = x
        break
      }
    }
  }

  // Legacy mode converts kebab-case to camelCase
  if (self.legacyMode) {
    if (/[-_]/.test(textKey)) {
      textKey = kebabToCamel(textKey)
      // @debug
      // console.log('Converted kebab-case textKey to legacy mode camelCase', textKey)
    }
  } else {
    // Test for camelCase to convert to kebab-case
    if (/^[a-z]+[A-Z0-9]/.test(textKey)) {
      textKey = camelToKebab(textKey)
      // @debug
      // console.log('Converted legacy camelCase textKey to kebab-case', textKey)
    }
  }

  // keyPrefix set and not present at start of textKey so add it
  if (self.keyPrefix && textKey.match(new RegExp('^' + self.keyPrefix)) == null) {
    textKey = self.keyPrefix + textKey
  }

  // @debug
  // console.log(textKey, self.dictionary, self.dictionary[lang][textKey])

  // Ensure the textKey exists within the selected lang dictionary
  if (self.dictionary[lang].hasOwnProperty(textKey)) return self.dictionary[lang][textKey]

  // Fallback text
  return fallbackText

  // @debug console.log('Error: textKey not found => dictionary.' + lang + '.' + textKey)
  // return '{# Error: textKey not found => dictionary.' + lang + '.' + textKey + ' #}'
}

// Get a message via the textKey
// @alias for __ to allow for different params order
// @method __key
// @param {String} textKey The key which corresponds to the dictionary entry to use
// @param {String} fallbackText The text to use if the textKey isn't within the dictionary
// @param {String} lang The lang code to reference within the dictionary (DEPRECATED)
// @returns {String}
Dictionary.prototype.__key = function (textKey, fallbackText, lang) {
  var self = this
  return self.__.apply(self, [fallbackText, textKey, lang])
}

// Set the default lang of the dictionary
// @method setDefaultLang
// @param {String} lang The lang code to reference within the dictionary (DEPRECATED)
// @returns {Void}
Dictionary.prototype.setDefaultLang = function (lang) {
  var self = this
  self.defaultLang = lang
}

// Check if the Dictionary supports a language
// @method supportsLang
// @param {String} lang The lang code to reference within the dictionary (DEPRECATED)
// @returns {Boolean}
Dictionary.prototype.supportsLang = function (lang) {
  var self = this
  if (self.dictionary) {
    return self.dictionary.hasOwnProperty(lang)
  } else {
    return true
  }
}

// Add separation characters to number (thousand and decimal), e.g. 1000000.123 => 1,000,000.123
// Adapted from: http://www.mredkj.com/javascript/nfbasic.html
// @method addNumberSeps
// @param {Number} number The number to format
// @param {String} milliSep The character to use to represent blocks of thousans
// @param {String} decimalSep The character to use to represent decimal points
// @param {Int} limitDecimal How much to limit the decimal points by, e.g. 1000000.123 => 1,000,000.1
// @param {Boolean} padDecimal Whether to fill the decimal points with extra zeroes, e.g. 10000000.123 => 1000000.12300 (used in conjunction with limitDecimal)
// @returns {String}
Dictionary.prototype.addNumberSeps = function (number, milliSep, decimalSep, limitDecimal, padDecimal) {
  var self = this
  var x = (number + '').split(/\./)
  var a = x[0]
  var b = ''

  milliSep = milliSep || ','
  decimalSep = decimalSep || '.'

  // @debug
  // console.log('Dictionary.addNumberSeps', number, milliSep, decimalSep, limitDecimal, padDecimal)

  // Default limit decimal
  if (limitDecimal && typeof limitDecimal !== 'number') limitDecimal = 2

  // Add the milliSep
  var rgx = /(\d+)(\d{3})/
  while (rgx.test(a)) {
    a = a.replace(rgx, '$1' + milliSep + '$2')
  }

  // Limit the decimal
  // -- Don't
  if (!limitDecimal && limitDecimal !== 0 && x.length > 1) {
    b = x[1]

  // -- Do!
  } else if (limitDecimal > 0) {
    b = (x.length > 1 ? x[1].substr(0, limitDecimal) : '')
  }

  // Pad the decimal
  if (padDecimal && limitDecimal > 0) {
    while (b.length < limitDecimal) {
      b += '0'
    }
  }

  // Add the decimalSep
  if (b && b.length > 0) b = decimalSep + b

  // Output
  var output = a + b

  // @debug
  // console.log('Dictionary.addNumberSeps', {
  //   number: number,
  //   milliSep: milliSep,
  //   decimalSep: decimalSep,
  //   limitDecimal: limitDecimal,
  //   padDecimal: padDecimal,
  //   output: output
  // })

  return output
}

// Format a number (adds correct punctuation, pads decimals, etc.)
// @method formatNumber
// @param {Mixed} input Can be {String} or {Number}
// @param {Int} limitDecimal How many decimal points to limit (or pad) to
// @param {Boolean} padDecimal Pad the decimal with extra zeroes
// @param {String} lang The lang code to reference within the dictionary (DEPRECATED)
// @returns {String}
Dictionary.prototype.formatNumber = function (input, limitDecimal, padDecimal, lang) {
  var self = this
  var number = parseFloat(input + ''.replace(/[^\d\-\.]+/, ''))

  // Don't operate on non-numbers
  if (input === Infinity || isNaN(number)) return input

  // Language options
  var numberDecimal = self.__('.', 'number-decimal', lang)
  var numberMilli = self.__(',', 'number-milli', lang)

  // Pad decimal
  // If detects there's a money sign, will pad decimal to 2
  if (typeof padDecimal === 'undefined') {
    padDecimal = /^[\$\€\£]|[\$\€\£]$/.test(input)
  }

  // Limit the decimals shown
  if (typeof limitDecimal === 'undefined' && padDecimal) {
    limitDecimal = 2
  }

  // Default output
  var output = input

  // Output the formatted number
  output = self.addNumberSeps(number, numberMilli, numberDecimal, limitDecimal, padDecimal)

  return output
}

// Localize a number
// @alias Shorthand to using formatNumber for integers
// @method localizedNumber
// @param {Mixed} input
// @param {Int} limitDecimal Default is set to 0
// @param {String} lang
// @returns {String}
Dictionary.prototype.localizedNumber = function (input, limitDecimal, lang) {
  var self = this
  return self.formatNumber(input, limitDecimal || 0, false, lang)
}

// Localize a price
// @alias Shorthand to using formatNumber for floats
// @method localizedPrice
// @param {Mixed} input
// @param {Int} limitDecimal Default is set to 2
// @param {String} lang
// @returns {String}
Dictionary.prototype.localizedPrice = function (input, limitDecimal, lang) {
  var self = this
  return self.formatNumber(input, limitDecimal || 2, true, lang)
}

module.exports = Dictionary
