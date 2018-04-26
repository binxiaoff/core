/**
 * Regain your text sanity by transforming, sanitising and normalising various inputs
 */

// Declare once, reuse ∞
var RE_TEXT_SIMPLE = /[^a-z0-9'& ]+/gi
var RE_HTML_SCRIPT = /<script[^>]*>(?:.*?)<\/script>/gi
var RE_HTML_JS_EVAL = /javascript:[^;]*/gi
var RE_PHONE_STRICT = /[^0-9]+/g
var RE_PHONE_LAX = /[^0-9 +.\-]+/g
var RE_PHONE_INTL = /^\(?\+/
var RE_SIREN = /[^0-9]+/g

var RE_NORMALISE_APOSTROPHE = /[\u2016-\u2019]|&(?:lsquo|rsquo|sbquo|#(?:821[6-9]|x201[89ab]));/gi
var REPLACE_NORMALISE_APOSTROPHE = "'"
var RE_NORMALISE_SPACE = /\t|&(?:nbsp|#(?:160|xa0));/gi
var REPLACE_NORMALISE_SPACE = ' '
var RE_NORMALISE_NEWLINE = /\r|<br\/?>/gi
var REPLACE_NORMALISE_NEWLINE = "\n"

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
 * Normalise whitespace
 *
 * @param {string} input
 * @returns {string}
 */
function normaliseWhitespace (input) {
  return (input + '')
    .replace(RE_NORMALISE_SPACE, REPLACE_NORMALISE_SPACE)
    .replace(RE_NORMALISE_NEWLINE, REPLACE_NORMALISE_NEWLINE)
}

/**
 * Normalise all similar forms of a single apostrophe.
 *
 * @param {string} input
 * @returns {string}
 */
function normaliseApostrophe (input) {
  return (input + '')
    .replace(RE_NORMALISE_APOSTROPHE, REPLACE_NORMALISE_APOSTROPHE)
}

/**
 * Sanitise text to be simplified, as in no special characters, only alpha and numbers.
 *
 * You can also specify to set to lower, title (capitalised) or upper case.
 *
 * @param {string} input
 * @param {bool|string} [setCase=upper]
 * @returns {string}
 */
function sanitiseSimpleText (input, setCase) {
  var output = (input + '').trim()

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
  // Strip all characters that aren't alpha, number, ampersand and apostrophes
    .replace(RE_TEXT_SIMPLE, '')
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
 * Strict mode strips other phone-friendly characters `+().- `
 *
 * If strict mode is enabled and the phone number starts with `+`, it will convert to `00`
 *
 * @param {number|string} input
 * @param {bool} [strict=false]
 * @returns {string}
 */
function sanitisePhone (input, strict) {
  var output = (input + '').trim()

  // Convert starting `+` to `00`
  if (strict) {
    output = output.replace(RE_PHONE_INTL, '00')
  }

  return output
    .replace(strict ? RE_PHONE_STRICT : RE_PHONE_LAX, '')
}

/** Collect all the transform methods into object */
var transform = {
  capitalise: transformCapitalise
}

/** Collect all the sanitise methods into object */
var sanitise = {
  simpleText: sanitiseSimpleText,
  html: sanitiseHtml,
  siren: sanitiseSiren,
  siret: sanitiseSiret,
  phone: sanitisePhone
}

/** Collect all the normalise methods into object */
var normalise = {
  apostrophe: normaliseApostrophe,
  whitespace: normaliseWhitespace
}

/** Funky utility */
function hotSpicyCurry(context, fn) {
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
function Sanity(input) {
  if (!(this instanceof Sanity)){
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
    curryMethod = hotSpicyCurry(self, transform[keysTransform[i]], self.output)
    selfTransform[keysTransform[i]] = curryMethod
    self['transform' + transformCapitalise(keysTransform[i])] = curryMethod
  }

  // Sanitise
  for (i = 0; i < keysSanitise.length; i++) {
    curryMethod = hotSpicyCurry(self, sanitise[keysSanitise[i]])
    selfSanitise[keysSanitise[i]] = curryMethod
    self['sanitise' + transformCapitalise(keysSanitise[i])] = curryMethod
  }

  // Normalise
  for (i = 0; i < keysNormalise.length; i++) {
    curryMethod = hotSpicyCurry(self, normalise[keysNormalise[i]])
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
