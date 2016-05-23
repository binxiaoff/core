/*
 * Utility Functions
 * General shared functions and properties
 */

var $ = require('jquery')

var Utility = {
  // Click event
  clickEvent: $('html').is('.has-touchevents') ? 'touchend' : 'click',

  // Transition end event
  transitionEndEvent: 'transitionend webkitTransitionEnd oTransitionEnd otransitionend',

  // Animation end event
  animationEndEvent: 'animationend webkitAnimationEnd oAnimationEnd oanimationend',

  // Generate a random string
  randomString: function (stringLength) {
    var output = ''
    var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    stringLength = stringLength || 8
    for (var i = 0; i < stringLength; i++) {
      output += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    return output
  },

  // Convert an input value (most likely a string) into a primitive, e.g. number, boolean, etc.
  convertToPrimitive: function (input) {
    // Non-string? Just return it straight away
    if (typeof input !== 'string') return input

    // Trim any whitespace
    input = (input + '').trim()

    // Number
    if (/^\-?(?:\d*[\.\,])*\d*(?:[eE](?:\-?\d+)?)?$/.test(input)) {
      return parseFloat(input)
    }

    // Boolean: true
    if (/^(true|1)$/.test(input)) {
      return true

    // NaN
    } else if (/^NaN$/.test(input)) {
      return NaN

    // undefined
    } else if (/^undefined$/.test(input)) {
      return undefined

    // null
    } else if (/^null$/.test(input)) {
      return null

    // Boolean: false
    } else if (/^(false|0)$/.test(input) || input === '') {
      return false
    }

    // Default to string
    return input
  },

  checkElemAttrForValue: function (elem, attr) {
    var $elem = $(elem)
    var attrValue = $elem.attr(attr)

    // Check non-'data-' prefixed attributes for one if value is undefined
    if (typeof attrValue === 'undefined' && !/^data\-/i.test(attr)) {
      attrValue = $elem.attr('data-' + attr)
    }

    return attrValue
  },

  // Check if the element is or its parents' matches a {String} selector
  // @returns {Boolean}
  checkElemIsOrHasParent: function (elem, selector) {
    return $(elem).is(selector) || $(elem).parents(selector).length > 0
  },

  // Same as above, except returns the elements itself
  // @returns {Mixed} A {jQueryObject} containing the element(s), or {Boolean} false
  getElemIsOrHasParent: function (elem, selector) {
    var $elem = $(elem)
    if ($elem.is(selector)) return $elem

    var $parents = $elem.parents(selector)
    if ($parents.length > 0) return $parents

    return false
  },

  // Add leading zero
  leadingZero: function (input) {
    return (parseInt(input, 10) < 10 ? '0' : '') + input
  },

  // Get the remaining time between two dates
  // @note TimeCount relies on this to output as an object
  // See: http://www.sitepoint.com/build-javascript-countdown-timer-no-dependencies/
  getTimeRemaining: function (endTime, startTime) {
    var t = Date.parse(endTime) - Date.parse(startTime || new Date())
    var seconds = Math.floor((t/1000) % 60)
    var minutes = Math.floor((t/1000/60) % 60)
    var hours = Math.floor((t/(1000*60*60)) % 24)
    var days = Math.floor(t/(1000*60*60*24))
    return {
      'total': t,
      'days': days,
      'hours': hours,
      'minutes': minutes,
      'seconds': seconds
    }
  }
}

module.exports = Utility
