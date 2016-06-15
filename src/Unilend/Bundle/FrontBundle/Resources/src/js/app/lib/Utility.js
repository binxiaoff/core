/*
 * Utility Functions
 * General shared functions and properties
 */

var $ = require('jquery')
var sprintf = require('sprintf-js').sprintf
var __ = require('__')

var Utility = {
  // Click event
  clickEvent: $('html').is('.has-touchevents') ? 'touchend' : 'click',

  // Transition end event
  transitionEndEvent: 'transitionend webkitTransitionEnd oTransitionEnd otransitionend',

  // Animation end event
  animationEndEvent: 'animationend webkitAnimationEnd oAnimationEnd oanimationend',

  // Breakpoints
  // @note if you change these here, please update `sass/site/common/dimensions.scss` too (and vice-versa)
  breakpoints: {
    'mobile-p': [0, 599],
    'mobile-l': [600, 799],
    'mobile':   [0, 799], // group of mobile-p and mobile-l
    'tablet-p': [800, 1023],
    'tablet-l': [1024, 1299],
    'tablet':   [800, 1299], // group of tablet-p and tablet-l
    'laptop':   [1300, 1599],
    'desktop':  [1600, 99999],
    'computer': [1300, 99999], // group of laptop and computer
    'xs':       [0, 599], // mobile-p
    'sm':       [600, 1023], // mobile-l[0], tablet-p[1]
    'md':       [1024, 1599], // tablet-l[0], laptop[1]
    'lg':       [1600, 99999] // desktop
  },

  // Get a string of the currently active breakpoints
  // @method getActiveBreakpoints
  // @returns {String}
  getActiveBreakpoints: function () {
    if (!window) console.log('Utility.getActiveBreakpoints: no window detected')

    var self = this
    var width = window.innerWidth
    var bp = []

    for (var x in self.breakpoints) {
      if ( width >= self.breakpoints[x][0] && width <= self.breakpoints[x][1]) bp.push(x)
    }

    return bp.join(' ')
  },

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

  // Check if a string selector is valid
  checkSelector: function (input) {
    var output = input

    if (typeof input === 'string') {
      // Invalid selector
      if (/^javascript/.test(input)) {
        output = ''
      }
    }

    // Invalid
    if (input === true || input === false || typeof input === 'function') {
      output = ''
    }

    // @debug
    // console.log('checkSelector', input, output)

    return output
  },

  // Convert to a boolean
  convertToBoolean: function (input) {
    // Already boolean
    if (input === true || input === false) return input

    // String to boolean
    if (typeof input === 'string') {
      switch (input) {
        case 'true':
        case '1':
          return true

        case 'false':
        case '0':
          return false
      }
    }

    // Number to boolean
    if (input === 1) return true
    if (input === 0) return false

    // Other false values
    if (typeof input === 'undefined' || input === '' || input === null || input === 'null' || input === NaN || input === 'NaN') return false

    // Otherwise...
    return true
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

  // Convert a string to JSON or just return the string if can't
  // @method convertStringToJson
  // @param {String} input
  // @returns {Mixed} The {Object} JSON or the original {String} input
  convertStringToJson: function (input) {
    var output = input

    // Convert string data to JSON
    if (typeof input === 'string') {
      try {
        output = JSON.parse(input)
      } catch (e) {
        console.error('Utility.convertStringToJson: Error parsing string JSON data', input)
      }
    }

    return output
  },

  // Check if element has an attribute set
  // @note if can't find the named attribute, it will also attempt to look
  //       for `data-{{ attr }}` if one exists
  // @method checkElemAttrForValue
  // @param {Mixed} elem Can be {String} selector, {HTMLElement} or {jQueryObject}
  // @param {String} attr The name of the attribute to check
  // @returns {Mixed} The value of the attribute, if found
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

    var $parents = $elem.parents(selector).first()
    if ($parents.length > 0) return $parents

    return false
  },

  // Add leading zero
  leadingZero: function (input) {
    return (parseInt(input, 10) < 10 ? '0' : '') + input
  },

  // Get date object from input
  // @method getDate
  // @param {Mixed} input {String} representing date/time or {Date}
  // @returns {Date}
  getDate: function (input) {
    if (input instanceof Date) return input

    // Parse date from string
    if (typeof input === 'string' && input !== 'now') {
      return Date.parse(input)
    }

    // Now
    return new Date()
  },

  // Get the time difference between two dates
  // @note TimeCount relies on this to output as an object
  // See: http://www.sitepoint.com/build-javascript-countdown-timer-no-dependencies/
  getTimeDiff: function (startTime, endTime) {
    var self = this

    // Get the dates and the direction
    var startDate = self.getDate(startTime)
    var endDate = self.getDate(endTime)

    // Get the differences
    var t = endDate - startDate
    var seconds = Math.floor(Math.abs(t/1000) % 60)
    var minutes = Math.floor(Math.abs(t/(1000*60)) % 60)
    var hours = Math.floor(Math.abs(t/(1000*60*60)) % 24)
    var days = Math.floor(Math.abs(t/(1000*60*60*24)) % 30)
    var months = Math.floor(Math.abs(t/(1000*60*60*24*30)) % 12)
    var years = Math.floor(Math.abs(t/(1000*60*60*24*365)))

    return {
      'startDate': startDate,
      'endDate': endDate,
      'total': t,
      'years': years,
      'months': months,
      'days': days,
      'hours': hours,
      'minutes': minutes,
      'seconds': seconds
    }
  },

  // Get the relative time between two dates
  // @method getRelativeTime
  // @param {Mixed} startTime {String} or {Date}
  // @param {Mixed} endTime {String} or {Date}
  // @returns {String}
  getRelativeTime: function (startTime, endTime) {
    var self = this

    // Reference
    var secondsAsUnits = [{
      min: 0,
      max: 5,
      single: __.__('now', 'timeUnitNow'),
      plural: __.__('now', 'timeUnitNow')
    },{
      min: 1,
      max: 60,
      single: '%d ' + __.__('second', 'timeUnitSecond'),
      plural: '%d ' + __.__('seconds', 'timeUnitSeconds')
    },{
      min: 60,
      max: 3600,
      single: '%d ' + __.__('minute', 'timeUnitMinute'),
      plural: '%d ' + __.__('minutes', 'timeUnitMinutes')
    },{
      min: 3600,
      max: 86400,
      single: '%d ' + __.__('hour', 'timeUnitHour'),
      plural: '%d ' + __.__('hours', 'timeUnitHours')
    },{
      min: 86400,
      max: 604800,
      single: '%d ' + __.__('day', 'timeUnitDay'),
      plural: '%d ' + __.__('days', 'timeUnitDays')
    },{
      min: 604800,
      max: 2419200,
      single: '%d ' + __.__('week', 'timeUnitWeek'),
      plural: '%d ' + __.__('weeks', 'timeUnitWeeks')
    },{
      min: 2628000,
      max: 31536000,
      single: '%d ' + __.__('month', 'timeUnitMonth'),
      plural: '%d ' + __.__('months', 'timeUnitMonths'),
    },{
      min: 31536000,
      max: -1,
      single: '%d ' + __.__('year', 'timeUnitYear'),
      plural: '%d ' + __.__('years', 'timeUnitYears')
    }]

    // Dates
    var startDate = self.getDate(startTime)
    var endDate = self.getDate(endTime)
    var diffSeconds = ((endDate.getTime() - startDate.getTime()) / 1000)

    // Output
    var outputDiff = ''
    var output = ''

    for (var i = 0; i < secondsAsUnits.length; i++) {
      var u = secondsAsUnits[i]
      if (Math.abs(diffSeconds) >= u.min && (Math.abs(diffSeconds) < u.max || u.max === -1)) {
        // Show the difference via number
        if (u.min > 0) {
          outputDiff = Math.round(Math.abs(diffSeconds) / u.min)
          output = sprintf((outputDiff === 1 ? u.single : u.plural), outputDiff)

        // No minimum amount given, so assume no need to put number within unit output (reference only single)
        } else {
          output = u.single
        }

        break
      }
    }

    // @debug
    // console.log('Utility.getRelativeTime', endDate.getTime() > startDate.getTime(), diffSeconds, outputDiff, output)

    return output
  },

  // Get an object's length
  getObjectLength: function (obj) {
    var len = 0
    for (var i in obj) {
      len += 1
    }
    return len
  },

  // Check if value is set
  isSet: function (value) {
    return !(typeof value === 'undefined' || value === undefined)
  },

  // Check if value is empty
  isEmpty: function (value) {
    var self = this
    return (value === '' || value === null || !self.isSet(value))
  },

  // Check if an element is hidden
  elemIsHidden: function (elem) {
    var $elem = $(elem)
    var isHidden = false

    // Nothing
    if ($elem.length === 0 || $elem.css('display') === 'none') return false

    // Traverse parents
    $elem.parents().each(function (i, parent) {
      if ($(parent).css('display') === 'none') {
        isHidden = true
        return true
      }
    })

    // @debug
    // console.log('elemIsHidden', elem, isHidden)
    return isHidden
  },

  elemExists: function (elem) {
    var $elem = $(elem)
    // {jQueryObject} length must be greater than zero and document must contain the HTMLElement
    if ($elem.length > 0 && $.contains(document, $elem[0])) return true
    return false
  }
}

module.exports = Utility
