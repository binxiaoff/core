/*
 * Unilend Dictionary
 * Enables looking up text to change per user's lang
 * Works same as most i18n functions
 * This is also used by Twig to look up dictionary entries
 * See gulpfile.js to see how it is loaded into Twig
 */

var Dictionary = function (dictionary, lang) {
  var self = this

  if (!dictionary) return false

  self.defaultLang = lang || 'fr'
  self.dictionary = dictionary

  // Get a message within the dictionary
  self.__ = function (fallbackText, textKey, lang) {
    lang = lang || self.defaultLang

    // Ensure dictionary supports lang
    if (!self.supportsLang(lang)) {
      // See if general language exists
      if (lang.match('-')) lang = lang.split('-')[0]

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
    if (self.dictionary[lang].hasOwnProperty(textKey)) return dictionary[lang][textKey]

    // Fallback text
    return fallbackText

    // @debug console.log('Error: textKey not found => dictionary.' + lang + '.' + textKey)
    return '{# Error: textKey not found => dictionary.' + lang + '.' + textKey + ' #}'
  }

  // Set the default lang
  self.setDefaultLang = function (lang) {
    self.defaultLang = lang
  }

  // Check if the Dictionary supports a language
  self.supportsLang = function (lang) {
    return self.dictionary.hasOwnProperty(lang)
  }

  // Add seps to number (thousand and decimal)
  // Adapted from: http://www.mredkj.com/javascript/nfbasic.html
  self.addNumberSeps = function (number, milliSep, decimalSep, limitDecimal) {
    number += ''
    x = number.split('.')

    // Add the milliSep
    a = x[0]
    var rgx = /(\d+)(\d{3})/
    while (rgx.test(a)) {
      a = a.replace(rgx, '$1' + (milliSep || ',') + '$2')
    }

    // Limit the decimal
    if (limitDecimal > 0) {
      b = (x.length > 1 ? (decimalSep || '.') + x[1].substr(0, limitDecimal) : '')
    } else {
      b = ''
    }

    return a + b
  }

  // Format a number (adds punctuation, currency)
  self.formatNumber = function (input, limitDecimal, isPrice, lang) {
    var number = parseFloat(input + ''.replace(/[^\d\-\.]+/, ''))

    // Don't operate on non-numbers
    if (input === Infinity || isNaN(number)) return input

    // Language options
    var numberDecimal = self.__('.', 'numberDecimal', lang)
    var numberMilli = self.__(',', 'numberMilli', lang)
    var numberCurrency = self.__('$', 'numberCurrency', lang)

    // Is price
    // -- If not set, detect if has currency symbol in input
    var currency = numberCurrency
    if (typeof isPrice === 'undefined') {
      isPrice = /^[\$\€\£]/.test(input)
      if (isPrice) {
        currency = input.replace(/(^[\$\€\£])/g, '$1')
      }
    }

    // Default output
    var output = input

    // Limit the decimals shown
    if (typeof limitDecimal === 'undefined') {
      limitDecimal = isPrice ? 2 : 0
    }

    // Output the formatted number
    output = (isPrice ? currency : '') + self.addNumberSeps(number, numberMilli, numberDecimal, limitDecimal)

    // @debug
    // console.log({
    //   input: input,
    //   number: number,
    //   limitDecimal: limitDecimal,
    //   isPrice: isPrice,
    //   lang: lang,
    //   numberDecimal: numberDecimal,
    //   numberMilli: numberMilli,
    //   numberCurrency: numberCurrency,
    //   currency: currency,
    //   output: output
    // })

    return output
  }

  // Localize a number
  self.localizedNumber = function (input, limitDecimal, lang) {
    return self.formatNumber(input, limitDecimal || 0, false, lang)
  }

  // Localize a price
  self.localizedPrice = function (input, limitDecimal, lang) {
    return self.formatNumber(input, limitDecimal || 2, true, lang)
  }
}

module.exports = Dictionary
