/*
 * Unilend Dictionary
 * Enables looking up text to change per user's lang
 * Works same as most i18n functions
 * This is also used by Twig to look up dictionary entries
 * See gulpfile.js to see how it is loaded into Twig
 */

// @class Dictionary
// @param {Object} dictionary A JS object which contains multiple language options,
//                            e.g. `{"en": {..}, "fr": {..}}`
// @param {String} lang The default language to reference
var Dictionary = function (dictionary, lang) {
  var self = this

  // Error if no dictionary object given
  if (!dictionary) return

  // Properties
  self.defaultLang = lang || 'fr'
  self.dictionary = dictionary

  return self
}

// Get a message within the dictionary
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

  // Ensure the textKey exists within the selected lang dictionary
  if (self.dictionary[lang].hasOwnProperty(textKey)) return self.dictionary[lang][textKey]

  // Fallback text
  return fallbackText

  // @debug console.log('Error: textKey not found => dictionary.' + lang + '.' + textKey)
  // return '{# Error: textKey not found => dictionary.' + lang + '.' + textKey + ' #}'
}

// Get a message via the textKey
// @alias for __ to allow for different params order
Dictionary.prototype.__key = function (textKey, fallbackText, lang) {
  var self = this
  return self.__.apply(self, [fallbackText, textKey, lang])
}

// Set the default lang
Dictionary.prototype.setDefaultLang = function (lang) {
  var self = this
  self.defaultLang = lang
}

// Check if the Dictionary supports a language
Dictionary.prototype.supportsLang = function (lang) {
  var self = this
  return self.dictionary.hasOwnProperty(lang)
}

// Add seps to number (thousand and decimal)
// Adapted from: http://www.mredkj.com/javascript/nfbasic.html
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
Dictionary.prototype.formatNumber = function (input, limitDecimal, padDecimal, lang) {
  var self = this
  var number = parseFloat(input + ''.replace(/[^\d\-\.]+/, ''))

  // Don't operate on non-numbers
  if (input === Infinity || isNaN(number)) return input

  // Language options
  var numberDecimal = self.__('.', 'numberDecimal', lang)
  var numberMilli = self.__(',', 'numberMilli', lang)
  // var numberCurrency = self.__('$', 'numberCurrency', lang)

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
Dictionary.prototype.localizedNumber = function (input, limitDecimal, lang) {
  var self = this
  return self.formatNumber(input, limitDecimal || 0, false, lang)
}

// Localize a price
Dictionary.prototype.localizedPrice = function (input, limitDecimal, lang) {
  var self = this
  return self.formatNumber(input, limitDecimal || 2, true, lang)
}

module.exports = Dictionary
