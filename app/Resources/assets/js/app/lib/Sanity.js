/**
 * Regain your text sanity by transforming, sanitising and normalising various inputs
 */

var unorm = require('unorm')

// Polyfill normalize
if (!String.prototype.hasOwnProperty('normalize')) {
  String.prototype.normalize = unorm
}

// Declare once, reuse ∞
var RE_TEXT_TITLE = /[^a-z0-9'& \-]+/gi
var RE_HTML_SCRIPT = /<script[^>]*>(?:.*?)<\/script>/gi
var RE_HTML_JS_EVAL = /javascript:[^;]*/gi
var RE_PHONE_LAX = /[^0-9 +]/g
var RE_PHONE_CONVERT_TO_SPACE = /[ .-]+/g
var RE_PHONE_STRICT = /[^0-9]+/g
var RE_PHONE_INTL = /^\(?\+/
var RE_PHONE_TEXT = /[()]|[^0-9 +.-]+.*$/g
var RE_PHONE_REDUNDANT_ZERO = /^((?:0{2}|\+)\d+)\s*\(0\)(\s*)/
var REPLACE_PHONE_REDUNDANT_ZERO = "$1$2" // eslint-disable-line
var RE_SIREN = /[^0-9]+/g

var RE_NORMALISE_APOSTROPHE = /['\u2016-\u2019]|&(?:lsquo|rsquo|sbquo|#(?:821[6-9]|x201[89ab]));/gi
var REPLACE_NORMALISE_APOSTROPHE = "'"
var RE_NORMALISE_SPACE = /[ \t\u0020\u2007\u202F\u2060\uFEFF]|&(?:nbsp|#(?:160|xa0));/gi
var REPLACE_NORMALISE_SPACE = ' '
var RE_NORMALISE_NEWLINE = /[\n\r]|&(?:#(?:1[0-3]|x[a-d]))|<br\s*\/?>/gi
var REPLACE_NORMALISE_NEWLINE = "\n" // eslint-disable-line

var RE_TRANSFORM_CAPITALISE = /\b([a-z])([a-z]*)\b/gi
var REPLACE_TRANSFORM_CAPITALISE = function (match, p1, p2) {
  return p1.toUpperCase() + p2.toLowerCase()
}

/**
 * Does what it says on the label.
 *
 * @param {string} input
 * @returns {string}
 */
function transformCapitalise (input) {
  return (input + '')
    .replace(RE_TRANSFORM_CAPITALISE, REPLACE_TRANSFORM_CAPITALISE)
}

/**
 * Normalise diacritic characters to latin equivalents.
 *
 * @param {string} input
 * @returns {string}
 */
function normaliseDiacritic (input) {
  return (input + '')
    .normalize('NFKD')
}

/**
 * Normalise spaces: convert tabs and non-breaking spaces to normal spaces
 *
 * @param {string} input
 * @param {string} [replacement=" "]
 * @returns {string}
 */
function normaliseSpace (input, replacement) {
  return (input + '')
    .replace(RE_NORMALISE_SPACE, replacement !== undefined ? replacement : REPLACE_NORMALISE_SPACE)
    .trim()
}

/**
 * Normalise new lines: convert carriage returns to normal spaces
 *
 * @param {string} input
 * @param {string} [replacement="\n"]
 * @returns {string}
 */
function normaliseNewline (input, replacement) {
  return (input + '')
    .replace(RE_NORMALISE_NEWLINE, replacement !== undefined ? replacement : REPLACE_NORMALISE_NEWLINE)
    .trim()
}

/**
 * Normalise whitespace: shorthand to normalise spaces and new lines
 *
 * @param {string} input
 * @param {string} [replacement]
 * @returns {string}
 */
function normaliseWhitespace (input, replacement) {
  return normaliseSpace(normaliseNewline(input, replacement), replacement)
}

/**
 * Normalise all similar forms of a single apostrophe with a replacement.
 *
 * @param {string} input
 * @param {string} [replacement="'"]
 * @returns {string}
 */
function normaliseApostrophe (input, replacement) {
  return (input + '')
    .replace(RE_NORMALISE_APOSTROPHE, replacement !== undefined ? replacement : REPLACE_NORMALISE_APOSTROPHE)
}

/**
 * Sanitise text to be simplified, as in very few special characters, only alpha and numbers.
 *
 * You can also specify to set to lower, title (capitalised) or upper case.
 *
 * @param {string} input
 * @param {bool|string} [setCase=upper]
 * @returns {string}
 */
function sanitiseTitle (input, setCase) {
  var output = normaliseDiacritic(normaliseApostrophe(normaliseSpace(normaliseNewline(input, ''))))

  switch (setCase) {
    case 'lower':
    case 'lowercase':
      output = output.toLowerCase()
      break

    case 'title':
    case 'capitalise':
    case 'capitalised':
    case 'capitalize':
    case 'capitalized':
      output = transformCapitalise(output)
      break

    case 'upper':
    case 'uppercase':
    default:
      output = output.toUpperCase()
      break
  }

  return output
    // Strip all unaccepted characters
    .replace(RE_TEXT_TITLE, '')
}

/**
 * Sanitise HTML strings.
 *
 * @param {string} input
 * @returns {string}
 */
function sanitiseHtml (input) {
  return (input + '')
    .trim()
    // Remove script blocks
    .replace(RE_HTML_SCRIPT, '')
    // Remove inline javascript eval()
    .replace(RE_HTML_JS_EVAL, '')
}

/**
 * Sanitise SIREN values.
 *
 * @param {number|string} input
 * @returns {string}
 */
function sanitiseSiren (input) {
  return (input + '')
    .trim()
    .replace(RE_SIREN, '')
    .slice(0, 9)
}

/**
 * Sanitise SIRET values.
 *
 * @param {number|string} input
 * @returns {string}
 */
function sanitiseSiret (input) {
  return (input + '')
    .trim()
    .replace(RE_SIREN, '')
    .slice(0, 14)
}

/**
 * Sanitise phone number values.
 *
 * If the phone number has a `(0)` after the country code, it will remove it.
 *
 * Normal (lax) mode retains phone-friendly characters like the plus and space.
 *
 * Strict mode strips all characters than aren't numbers.
 *
 * If strict mode is enabled and the phone number starts with `+`, it will convert to `00`.
 *
 * @param {number|string} input
 * @param {bool} [strict=false]
 * @returns {string}
 */
function sanitisePhone (input, strict) {
  var output = (input + '')

  // Convert starting `+` to `00`
  if (strict) {
    output = output.replace(RE_PHONE_INTL, '00')

  // Convert space-like characters to single space (primarily period and hyphen)
  } else {
    output = normaliseWhitespace(output.replace(RE_PHONE_CONVERT_TO_SPACE, ' '))
  }

  // Sometimes mobile numbers look like : +33 (0) 6...
  // Since we don't need the starting zero after a country code, let's remove it
  output = output.replace(RE_PHONE_REDUNDANT_ZERO, REPLACE_PHONE_REDUNDANT_ZERO)

  // Strip text
  output = output.replace(RE_PHONE_TEXT, '')

  return output
    .replace(strict ? RE_PHONE_STRICT : RE_PHONE_LAX, '').trim()
}

/** Collect all the transform methods into object */
var transform = {
  capitalise: transformCapitalise
}

/** Collect all the sanitise methods into object */
var sanitise = {
  title: sanitiseTitle,
  html: sanitiseHtml,
  siren: sanitiseSiren,
  siret: sanitiseSiret,
  phone: sanitisePhone
}

/** Collect all the normalise methods into object */
var normalise = {
  diacritic: normaliseDiacritic,
  apostrophe: normaliseApostrophe,
  space: normaliseSpace,
  newline: normaliseNewline,
  whitespace: normaliseWhitespace
}

/** Funky utility */
function curryFn (context, fn) {
  return function () {
    var props = Array.prototype.slice.call(arguments)
    props.unshift(context.output)
    return fn.apply(context, props)
  }
}

/**
 * Cool guy usage: `Sanity(input).sanitise.simpleText()`
 *
 * @param {string} input
 * @returns {Sanity}
 * @constructor
 */
function Sanity (input) {
  if (!(this instanceof Sanity)) {
    return new Sanity(input)
  }

  var self = this
  var i
  var keysTransform = Object.keys(transform)
  var keysSanitise = Object.keys(sanitise)
  var keysNormalise = Object.keys(normalise)
  var selfTransform = {}
  var selfSanitise = {}
  var selfNormalise = {}
  var curryMethod

  // Transform
  for (i = 0; i < keysTransform.length; i++) {
    curryMethod = curryFn(self, transform[keysTransform[i]], self.output)
    selfTransform[keysTransform[i]] = curryMethod
    self['transform' + transformCapitalise(keysTransform[i])] = curryMethod
  }

  // Sanitise
  for (i = 0; i < keysSanitise.length; i++) {
    curryMethod = curryFn(self, sanitise[keysSanitise[i]])
    selfSanitise[keysSanitise[i]] = curryMethod
    self['sanitise' + transformCapitalise(keysSanitise[i])] = curryMethod
  }

  // Normalise
  for (i = 0; i < keysNormalise.length; i++) {
    curryMethod = curryFn(self, normalise[keysNormalise[i]])
    selfNormalise[keysNormalise[i]] = curryMethod
    self['normalise' + transformCapitalise(keysNormalise[i])] = curryMethod
  }

  // This lets you do Sanity(input).sanitise.string() as well as Sanity(input).sanitiseString()
  self.transform = selfTransform
  self.sanitise = selfSanitise
  self.normalise = selfNormalise

  return self.set(input)
}

/** Set the input */
Sanity.prototype.set = function (input) {
  this.input = input
  this.output = input !== undefined ? (input + '') : undefined
  return this
}

/** Reset the output to be the same as the original input */
Sanity.prototype.reset = function () {
  return this.set()
}

/** Get the transformed output */
Sanity.prototype.toString = function () {
  return this.output === undefined ? this.output : this.output.toString()
}

/**
 * @module Sanity
 */
module.exports = Sanity

/*
 * @debug Tests
 *
 * If you change any of the above, make sure to uncomment below to run these tests
 */
console.log('### TESTING SANITY ###')

var testPhone = Sanity('+123 456 789 ext. 1456').sanitise.phone()
var testPhone2 = Sanity('+123 (0) 456-789 ext. 1456').sanitise.phone()
var testPhone3 = Sanity('+123 (0) 456.789 ext. 1456').sanitise.phone()
var testPhoneBrackets = Sanity('(+123) 456 789 ext. 1456').sanitise.phone()
var testPhoneRedundantZero = Sanity('+123 (0) 456 789 ext. 1456').sanitise.phone()
var testPhoneStrict = Sanity('+123 456 789 ext. 1456').sanitise.phone(true)
var testPhoneRedundantZeroStrict = Sanity('+123 (0) 456 789 ext. 1456').sanitise.phone(true)

console.log('Phone:', testPhone, testPhone === '+123 456 789')
console.log('Phone:', testPhone2, testPhone2 === '+123 456 789')
console.log('Phone:', testPhone3, testPhone3 === '+123 456 789')
console.log('Phone:', testPhoneBrackets, testPhoneBrackets === '+123 456 789')
console.log('Phone:', testPhoneRedundantZero, testPhoneRedundantZero === '+123 456 789')
console.log('Phone:', testPhoneStrict, testPhoneStrict === '00123456789')
console.log('Phone:', testPhoneRedundantZeroStrict, testPhoneRedundantZeroStrict === '00123456789')

var testTitle = Sanity("Mått’s ßuper-fun & háppy Ràisoñ soçiale sAR`L.").sanitise.title() // eslint-disable-line
console.log('Title:', testTitle, testTitle === "MATT'S SSUPER-FUN & HAPPY RAISON SOCIALE SARL")

var testNormaliseWhitespace = Sanity('0 1 2 3 4 5 6 7 8 9').normalise.whitespace('')
console.log('Whitespace:', testNormaliseWhitespace, testNormaliseWhitespace === '0123456789')

var testSiren = Sanity('123456789abc').sanitise.siren()
var testSiret = Sanity('12345678901234abc').sanitise.siret()

console.log('Siren:', testSiren, testSiren === '123456789')
console.log('Siret:', testSiret, testSiret === '12345678901234')
