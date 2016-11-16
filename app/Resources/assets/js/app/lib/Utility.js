/*
 * Utility Functions
 * General shared functions and properties
 */

var $ = require('jquery')
var sprintf = require('sprintf-js').sprintf
var __ = require('__')

var $win = $(window)
var $doc = $(document)
var $html = $('html')
var $body = $('body')

// Default values
var defaults = {
  secondsAsUnits: [{
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
}

var Utility = {
  // Click event
  clickEvent: 'click', //$html.is('.has-touchevents') ? 'touchend' : 'click',

  // Transition end event
  transitionEndEvent: 'transitionend webkitTransitionEnd oTransitionEnd otransitionend',

  // Animation end event
  animationEndEvent: 'animationend webkitAnimationEnd oAnimationEnd oanimationend',

  // Common elements
  $siteHeader: $('.site-header'),
  $siteContent: $('.site-content'),
  $siteFooter: $('.site-footer'),

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
    if (!window) console.error('Utility.getActiveBreakpoints: no window detected')

    var self = this
    var width = window.innerWidth
    var bp = []

    for (var x in self.breakpoints) {
      if ( width >= self.breakpoints[x][0] && width <= self.breakpoints[x][1]) bp.push(x)
    }

    return bp.join(' ')
  },

  // Check if a breakpoint keyword is currently active
  // @method isBreakpointActive
  // @returns {Boolean}
  isBreakpointActive: function (input) {
    if (!window) console.error('Utility.getActiveBreakpoints: no window detected')

    var self = this
    if (typeof input === 'string') input = new RegExp(input.replace(/[ ,]+/g, '|'), 'i')
    if (input.test(window.currentBreakpoint || self.getActiveBreakpoints())) {
      return true
    }

    return false
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
  // Ignores any string that starts with `javascript` because it fires jQuery errors
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
    var self = this

    // Non-string? Just return it straight away
    if (typeof input !== 'string') return input

    // Trim any whitespace
    input = (input + '').trim()

    // Number
    if (/^\-?(?:\d*[\.\,])*\d*(?:[eE](?:\-?\d+)?)?$/.test(input)) {
      return parseFloat(input)

    // Boolean: true
    } else if (/^(true|1)$/.test(input)) {
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

    // JSON: starts with [ or { and ends with ] or }
    } else if (/^[\[\{]/.test(input) && /[\]\}]$/.test(input)) {
      return self.convertStringToJson(input)
    }

    // Default to string
    return input
  },

  // Convert a string to an array
  // @method convertStringToArray
  // @param {String} input
  // @returns {Array}
  convertStringToArray: function (input, delimiter) {
    // Already an array
    if (input instanceof Array) {
      return input
    }

    // Default delimiter is comma or whitespace
    if (!delimiter) {
      delimiter = /[,\s\t\r\n]+/
    }

    // Create delimiter RegExp
    if (!(delimiter instanceof RegExp)) {
      delimiter = new RegExp(Utility.reEscape(delimiter))
    }

    // Test if the input string has the delimiter and split by the delimiter
    if (delimiter.test(input)) {
      return input.split(delimiter)
    }

    // Convert the plain string to an array with one item
    return [input]
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

  // Convert string to float
  convertStringToFloat: function (input) {
    if (typeof input === 'number') return input

    var output = parseFloat((input + '').replace(/[^\d\-\.]+/g, ''))

    // Infinity / NaN
    if (input === Infinity || isNaN(output)) output = 0

    return output
  },

  // Escape a string for regexp purposes
  // See: http://stackoverflow.com/a/6969486
  reEscape: function (str) {
    return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&")
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
    var $find = $(elem).closest(selector).first()
    if ($find.length > 0) return $find
    return false
  },

  // Add leading zero
  leadingZero: function (input) {
    return (parseInt(input, 10) < 10 ? '0' : '') + input
  },

  // Deserialize a serialized string (i.e. takes the return value of `$('form').serialize()` and turns it into an object)
  // Taken from https://github.com/chrissrogers/jquery-deparam/blob/master/jquery-deparam.js
  deserialize: function (params, coerce) {
    var obj = {}
    var coerce_types = { 'true': !0, 'false': !1, 'null': null }

    // Iterate over all name=value pairs.
    $.each(params.replace(/\+/g, ' ').split('&'), function (j,v) {
      var param = v.split('=')
      var key = decodeURIComponent(param[0])
      var val
      var cur = obj
      var i = 0

      // If key is more complex than 'foo', like 'a[]' or 'a[b][c]', split it
      // into its component parts.
      var keys = key.split('][')
      var keys_last = keys.length - 1

      // If the first keys part contains [ and the last ends with ], then []
      // are correctly balanced.
      if (/\[/.test(keys[0]) && /\]$/.test(keys[keys_last])) {
        // Remove the trailing ] from the last keys part.
        keys[keys_last] = keys[keys_last].replace(/\]$/, '')

        // Split first keys part into two parts on the [ and add them back onto
        // the beginning of the keys array.
        keys = keys.shift().split('[').concat(keys)

        keys_last = keys.length - 1
      } else {
        // Basic 'foo' style key.
        keys_last = 0
      }

      // Are we dealing with a name=value pair, or just a name?
      if (param.length === 2) {
        val = decodeURIComponent(param[1])

        // Coerce values.
        if (coerce) {
          val = (val && !isNaN(val)           ? +val              // number
            : val === 'undefined'             ? undefined         // undefined
            : coerce_types[val] !== undefined ? coerce_types[val] // true, false, null
            : val)                                                // string
        }

        if ( keys_last ) {
          // Complex key, build deep object structure based on a few rules:
          // * The 'cur' pointer starts at the object top-level.
          // * [] = array push (n is set to array length), [n] = array if n is
          //   numeric, otherwise object.
          // * If at the last keys part, set the value.
          // * For each keys part, if the current level is undefined create an
          //   object or array based on the type of the next keys part.
          // * Move the 'cur' pointer to the next level.
          // * Rinse & repeat.
          for (; i <= keys_last; i++) {
            key = keys[i] === '' ? cur.length : keys[i]
            cur = cur[key] = (i < keys_last
              ? cur[key] || (keys[i+1] && isNaN(keys[i+1]) ? {} : [])
              : val)
          }

        } else {
          // Simple key, even simpler rules, since only scalars and shallow
          // arrays are allowed.

          if ($.isArray(obj[key])) {
            // val is already an array, so push on the next value.
            obj[key].push(val)

          } else if (obj[key] !== undefined) {
            // val isn't an array, but since a second value has been specified,
            // convert val into an array.
            obj[key] = [obj[key], val]

          } else {
            // val is a scalar.
            obj[key] = val
          }
        }

      } else if (key) {
        // No value was defined, so set something meaningful.
        obj[key] = (coerce ? undefined : '')
      }
    })

    return obj
  },

  // Get date object from input
  // @method getDate
  // @param {Mixed} input {String} representing date/time or {Date}
  // @returns {Date}
  getDate: function (input) {
    var output

    // Already date
    if (input instanceof Date) {
      output = input

    // Parse date from string
    } else if (typeof input === 'string' && input !== 'now') {
      input = input.trim()

      // Ensure date has times formatted as ISO for Safari compatibility
      if (/^\d{4}\-\d{2}\-\d{2} \d{2}\:\d{2}(?:\:\d{2}(?:\.\d{0,3})?)?$/.test(input)) {
        input = input.replace(/^(\d{4}\-\d{2}\-\d{2}) (\d{2}\:\d{2})(?:\:\d{2}(?:\.\d{,3})?)?$/, '$1T$2:00.000')
      }

      output = new Date(input)
      // return new Date(input)

    // Assume unix time (in seconds)
    } else if (typeof input === 'number') {
      var output = new Date()
      output.setTime(input * 1000)

    // Assume now
    } else {
      // Now
      output = new Date()
      // return new Date()
    }

    // @debug
    // console.log('Utility.getDate', input, output)
    return output
  },

  // Get the time difference between two dates
  // @note TimeCount relies on this to output as an object
  // See: http://www.sitepoint.com/build-javascript-countdown-timer-no-dependencies/
  getTimeDiff: function (startTime, endTime) {

    // Get the dates and the direction
    var startDate = Utility.getDate(startTime)
    var endDate = Utility.getDate(endTime)

    // @debug
    // console.log('Utility.getTimeDiff', {
    //   startTime: startTime,
    //   endTime: endTime,
    //   startDate: startDate,
    //   endDate: endDate
    // })

    // Get the differences
    var t = endDate - startDate
    var seconds = Math.floor(Math.abs(t/1000) % 60)
    var minutes = Math.floor(Math.abs(t/(1000 * 60)) % 60)
    var hours = Math.floor(Math.abs(t/(1000 * 60 * 60)) % 24)
    var days = Math.floor(Math.abs(t/(1000 * 60 * 60 * 24)) % 30)
    var months = Math.floor(Math.abs(t/(1000 * 60 * 60 * 24 * 30)) % 12)
    var years = Math.floor(Math.abs(t/(1000 * 60 * 60 * 24 * 365)))

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
  // @param {Array} secondsAsUnits Array of {Object}s representing each unit of measurement to report back with
  // @returns {String}
  getRelativeTime: function (startTime, endTime, secondsAsUnits) {
    // Reference
    secondsAsUnits = secondsAsUnits || defaults.secondsAsUnits

    // Dates
    var startDate = Utility.getDate(startTime)
    var endDate = Utility.getDate(endTime)
    var diffSeconds = ((endDate.getTime() - startDate.getTime()) / 1000)

    // @debug
    // console.log('Utility.getRelativeTime', {
    //   startTime: startTime,
    //   startDate: startDate,
    //   endTime: endTime,
    //   endDate: endDate,
    //   diffSeconds: diffSeconds
    // })

    // Output
    var outputDiff = ''
    var output = ''

    for (var i = 0; i < secondsAsUnits.length; i++) {
      var u = secondsAsUnits[i]
      if (Math.abs(diffSeconds) >= u.min && (Math.abs(diffSeconds) < u.max || u.max === -1)) {
        // Show the difference via number
        if (u.min > 0) {
          var mod = u.min;
          if (typeof u.mod !== 'undefined') {
            mod = u.mod;
          }
          outputDiff = Math.floor(Math.abs(diffSeconds) / mod)
          output = sprintf((outputDiff === 1 ? u.single : u.plural), outputDiff)

        // No minimum amount given, so assume no need to put number within unit output (reference only single)
        } else {
          output = u.single
        }

        break
      }
    }

    // @debug
    // console.log('Utility.getRelativeTime', outputDiff, output)

    return output
  },

  // Get a time code
  getTimecode: function (input) {
    var inputDate = Utility.getDate(input)
    var timeCode = []
    timeCode.push(Utility.leadingZero(Math.abs(inputDate.getHours())))
    timeCode.push(Utility.leadingZero(Math.abs(inputDate.getMinutes())))
    timeCode.push(Utility.leadingZero(Math.abs(inputDate.getSeconds())))
    return timeCode.join(':')
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

  // Check if an element is hidden (if it has CSS of `display:none` or `visibility:hidden`)
  elemIsHidden: function (elem) {
    var $elem = $(elem)
    var isHidden = false

    // No element to check
    if ($elem.length === 0) return false

    // Element is hidden
    if ($elem.css('display') === 'none' || $elem.css('visibility') === 'hidden') isHidden = true

    // If element isn't directly hidden, traverse parents to check their status
    if (!isHidden) {
      $elem.parents().each(function (i, parent) {
        var $parent = $(parent)
        if ($parent.css('display') === 'none' || $parent.css('visibility') === 'hidden') {
          isHidden = true
          // Break loop
          return true
        }
      })
    }

    // @debug
    // console.log('elemIsHidden', elem, isHidden)
    return isHidden
  },

  // Check if an element exists in the DOM
  elemExists: function (elem) {
    var $elem = $(elem)
    // {jQueryObject} length must be greater than zero and document must contain the HTMLElement
    if ($elem.length > 0 && $.contains(document, $elem[0])) return true
    return false
  },

  // Special debug object to take place of console
  // debug: {
  //   // Log a message (includes a timecode at the start too)
  //   log: function () {
  //     // Error: no console or console.log method found
  //     if (!console || !console.log) return

  //     // Turn the arguments into an array to use in `apply`
  //     var args = Array.prototype.slice.call(arguments)
  //     if (args.length === 0) return

  //     // Build timecode
  //     var timeCode = Utility.getTimecode()

  //     // Add to start of args
  //     args.unshift('[' + timeCode + ']')

  //     // Fire the console log method
  //     console.log.apply(console, args)
  //   }
  // }

  // Get an element's absolute offset, taking in account any parents' scrollTop/Left values
  elemOffsetAbsolute: function (elem) {
    var $elem = $(elem).first()
    var output = {
      top: 0,
      left: 0
    }
    if ($elem.length === 0) return output

    // Get the raw offset value
    offset = $elem.offset()

    // Offset the offset by any parent's scroll values
    $elem.parents().each(function (i, parent) {
      var $parent = $(parent)
      offset.top += $parent.scrollTop()
      offset.left += $parent.scrollLeft()
    })

    return offset
  },

  // Get the space between an element and its parent
  elemOffsetBetween: function (elem, parentElem) {
    var self = this
    var $elem = $(elem).first()
    var $parent = $(parentElem).last()

    // Just get the offset
    if (!$parent.length || $.isWindow($parent) || $parent.is('html, body')) {
      return $elem.offset()
    }
    if (!$elem.length) {
      return $parent.offset()
    }

    // Calculate the distance
    var elemPos = $elem.position()
    var parentPos = $parent.position()

    return {
      top: elemPos.top - parentPos.top,
      left: elemPos.left - parentPos.left
    }
  },

  // Same as above, negating any scrollLeft/Top values
  elemOffsetBetweenAbsolute: function (elem, parentElem) {
    var self = this
    var offset = self.elemOffsetBetween(elem, parentElem)
    var $elem = $(elem).first()
    var $parentElem = $elem.closest(parentElem).last()

    $elem.parents().each(function (i, parent) {
      var $parent = $(parent)
      offset.top += $parent.scrollTop()
      offset.left += $parent.scrollLeft()

      if ($parent.is($parentElem)) return false
    })

    return offset
  },

  // Scroll an element to a specific target within (either number or a child element)
  // @method scrollTo
  // @param {Mixed} target Can be {String} selector, {HTMLElement} or {jQueryObject}
  // @param {Function} cb Callback to fire after scroll animation
  // @param {Int} time Time in milliseconds for the scroll animation
  // @param {Mixed} elem The element to scroll (defaults to `html,body`)
  // @param {Object} options Extra options
  scrollTo: function (target, cb, time, elem, options) {
    var self = this

    // Options
    options = $.extend({
      centerTargetInElem: false
    }, options)

    // Get target to scroll to
    var $elem = $('html, body')
    if (Utility.checkSelector(elem)) $elem = $(elem)
    if ($elem.length === 0) return

    // Get the elements current scrollTop value
    var elemScrollTop = $elem.scrollTop()

    // Get the target details
    var $target
    var toScrollTop

    // Point in element
    if (typeof target === 'number') {
      toScrollTop = parseInt(target, 10)

    // Get point from target in element
    } else {
      $target = $elem.find(target).filter(':visible').first()

      // Don't scroll to invisible elements
      if ($target.length === 0) return

      // Get the location to scroll to
      if ($elem.is('html, body') || $.isWindow($elem)) {
        toScrollTop = $target.offset().top

        // Take site header height into account
        var siteHeaderHeight = $('.site-header').outerHeight()
        toScrollTop -= siteHeaderHeight + 25 // + buffer

      } else {
        toScrollTop = self.elemOffsetBetweenAbsolute($target, $elem).top
      }

      // Place in middle of screen
      if (options.centerTargetInElem) {
        var elemHeight = $elem.height()
        if ($elem.is('html, body, window')) {
          elemHeight = $(window).innerHeight()
        }

        // Minus height of target from elemHeight and divide by two to get the middle spot where the element should be
        var targetHeight = $target.outerHeight()
        toScrollTop = $target.offset().top - ((elemHeight - targetHeight) * 0.5)
      }
    }

    // @debug
    // console.log('scrollTo', $target, toScrollTop)

    // Calculate time to animate by the difference in distance
    if (typeof time === 'undefined') time = (Math.max(elemScrollTop, toScrollTop) - Math.min(elemScrollTop, toScrollTop)) * 0.1
    if (time > 0 && time < 300) time = 300

    // Animate the scroll
    if ($elem.length > 0) {
      $elem.animate({
        scrollTop: toScrollTop + 'px',
        skipGSAP: true
      }, time, 'swing', cb)
    }
  },

  /*
   * Reveal element
   * @note due to nested nature of collapses and tabs, this function enables
   *       crawling back through the DOM to show all hidden parent collapse or tab elements
   */
  revealElem: function (elem, cb) {
    var $elem = $(elem)
    if ($elem.length === 0) return

    // @debug
    // console.log('revealElem', $elem)

    $elem.each(function (i, item) {
      var $item = $(item)
      var targetSelector = '#' + $item.attr('id')
      var $parents = $elem.parents('.collapse, .collapsing, [role="tabpanel"], .tab-pane')

      // Reveal parents
      if ($parents.length > 0) Utility.revealElem($parents)

      // Show message
      if ($item.is('.message, .message-alert, .message-info, .message-success, .message-error')) {
        // Slide the message up and remove it
        $item.slideDown(function () {
          // a11y stuff
          if (Utility.isSet($item.attr('aria-hidden'))) $item.attr('aria-hidden', false)
        })
        return
      }

      // Show collapse
      if ($item.is('.collapse, .collapsing')) {
        $item.collapse('show')
        return
      }

      // Show tab
      if ($item.is('[role="tabpanel"], .tab-pane')) {
        // Get the first tab target to perform control operation on
        $('[href="' + targetSelector + '"][role="tab"]').first().tab('show')
        return
      }

      // Show the element item (if hidden otherwise)
      if ($item.css('display') === 'none' || $item.css('visibility') === 'hidden') {
        // a11y stuff
        if (typeof $item.attr('aria-hidden') !== 'undefined') $item.attr('aria-hidden', false)

        // Show the item
        $item.show()
      }
    })

    // Fire the callback
    if (typeof cb === 'function') cb()
  },

  /*
   * Dismiss element
   * @note due to nested nature of collapses and tabs, this function enables
   *       crawling back through the DOM to show all hidden parent collapse or tab elements
   */
  dismissElem: function (elem, cb) {
    var $elem = $(elem)
    if ($elem.length === 0) return
    var $target

    // @debug
    // console.log('dismissElem', $elem)

    $elem.each(function (i, item) {
      var $item = $(item)
      var targetSelector = ($item.attr('id') ? '#' + $item.attr('id') : '')

      // Dismiss message
      if ($item.is('.message, .message-alert, .message-info, .message-success, .message-error')) {
        // Slide the message up and remove it
        $item.addClass('collapsing').slideUp(function () {
          // a11y stuff
          if (Utility.isSet($(this).attr('aria-hidden'))) {
            $(this).attr('aria-hidden', true)
          } else {
            $(this).remove()
          }
        })
        return
      }

      // Hide collapse
      if ($item.is('.collapse, .collapsing')) {
        $item.collapse('hide')
        return
      }

      // Hide tab
      if ($item.is('[role="tabpanel"], .tab-pane')) {
        // Get the first tab target to perform control operation on
        $('[href="' + targetSelector + '"][role="tab"]').first().tab('hide')
        return
      }

      // Hide the element item (if visible otherwise)
      if ($item.css('display') !== 'none' || $item.css('visibility') !== 'hidden') {
        // a11y stuff
        if (typeof $item.attr('aria-hidden') !== 'undefined') $item.attr('aria-hidden', true)

        // Hide the item
        $item.hide()
      }
    })

    // Fire the callback
    if (typeof cb === 'function') cb()
  },

  /* Use SVG item from SVG symbol set (see {build}/media/icons.svg)
   * (SVG symbol set is loaded in via ./src/twig/layouts/_layout.twig)
   * ID corresponds to {foldername-filename}
   * e.g. SVG hosted in media/svg/example-folder/another-folder/filename.svg
   *      will translate to:
   *      svgImage('#example-folder-another-folder-filename', 'Example')
   * You can also specify multiple IDs (or URLs) to layer SVG symbols
   */
  // @note same used in `src/twig/extensions/twig.extensions.js`, just with minor modifications to reference the `window.site.assets.media` value for default URL
  svgImage: function (id, title, width, height, sizing) {
    // Default URL
    var url = (window.site ? window.site.assets.media + 'svg/icons.svg' : '/assets/images/svg/icons.svg')
    var svgHeaders = ' version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve"'
    var uses = []
    var usesIds = []

    // Supported sizing sizes, using preserveAspectRatio
    var sizes = {
      'none': '',
      'stretch': 'none',
      'cover': 'xMidYMid slice',
      'contain': 'xMidYMid meet'
    }

    // Fallback to 'contain' aspect ratio if invalid option given
    if (sizing && !sizes.hasOwnProperty(sizing)) sizing = 'contain'

    // Specify multiple IDs or items to stack within the SVG image
    if (!(id instanceof Array)) id = [id]
    for (var i = 0; i < id.length; i++) {
      var useId = ''

      // Reference to ID
      if (/^#/.test(id[i])) {
        useId = id[i].replace('#', '')
        usesIds.push(useId)
        uses.push('<use xlink:href="' + url + id[i] + '" class="svg-file-' + useId + '"/>')

      // Reference to other SVG file
      } else {
        if (/#/.test(id[i])) {
          useId = id[i].split('#').pop()
          usesIds.push(useId)
        }
        uses.push('<use xlink:href="' + id[i] + '" ' + (useId ? 'class="svg-file-' + useId + '"' : '') + '/>')
      }
    }

    // List of IDs
    if (usesIds.length > 0) {
      usesIds = ' svg-file-' + usesIds.join(' svg-file-')
    } else {
      usesIds = ''
    }

    // Set attributes
    var titleAttr = (title ? ' title="' + title + '"' : '')
    var widthAttr = (width ? ' width="' + width + '"' : '' )
    var heightAttr = (height ? ' height="' + height + '"' : '' )
    var viewBox = '0 0 ' + width + ' ' + height
    // @note don't need this set anymore as it is set in the individual SVGs
    var viewBoxAttr = ''// ' viewBox="' + viewBox + '"'
    var preserveAspectRatioAttr = (sizing ? ' preserveAspectRatio="' + sizes[sizing] + '"' : '')

    // @note setting width/height attrs doesn't work well for IE
    // var svgHtml = '<svg role="img"' + titleAttr + widthAttr + heightAttr + viewBoxAttr + preserveAspectRatioAttr + ' class="svg-icon' + usesIds + '"' + svgHeaders + '>' + uses.join('') + '</svg>'

    // @note but only having viewBox isn't the best either...
    var svgHtml = '<svg role="img"' + titleAttr + viewBoxAttr + preserveAspectRatioAttr + ' class="svg-icon' + usesIds + '"' + svgHeaders + '>' + uses.join('') + '</svg>'

    // @note so let's wrap it with a div that has a max-width set
    // @note also found that it's not enough, so add in a canvas element which does the responsive auto scaling work
    var canvasAspectHtml = '<canvas class="svg-icon-aspect" width="' + width + '" height="' + height + '"></canvas>'
    svgHtml = '<div class="svg-icon-wrap" style="max-width: ' + width + 'px">' + canvasAspectHtml + svgHtml + '</div>'

    // Output SVG code
    return svgHtml
  },

  // Sets an object's property according to the propChain and
  // will automatically create the objects in the propChain if they don't exist
  // Saves having to write multiple typeof x === 'undefined' conditions
  // @param {Boolean} extend Extends the final prop's object instead of setting
  setObjProp: function (obj, propChain, value, extend) {
    var self = this
    var traverseObj = obj
    var setProp

    // Make sure the propChain is set
    if (!propChain) return

    // Format the prop's key
    function getPropKey (key) {
      // Support array objects which are indicated by integers
      if (/^\d+$/.test(key + '')) key = parseInt(key, 10)
      return key
    }

    // Traverse the object along the propChain, setting objects along the way like a boss
    propChain = (propChain.match(/[\[\]\.]/) ? propChain.split(/[\[\]\.]+/) : [propChain])
    for (var i = 0; i < propChain.length - 1; i++) {
      var propKey = getPropKey(propChain[i])

      // Check if the point in the object is defined
      if (typeof traverseObj[propKey] === 'undefined') {
        // Add arrays/objects until getting to the last property
        traverseObj[propKey] = (typeof propKey === 'string' ? {} : [])
      }

      // Jump to the next level
      traverseObj = traverseObj[propKey]
    }

    // Set the value to the last traversed object
    var lastKey = getPropKey(propChain[propChain.length - 1])

    // Extend the last traversed obj prop with the properties of the value
    if (extend && typeof value === 'object') {
      if (typeof setProp === 'undefined') {
        traverseObj[lastKey] = (typeof lastKey === 'string' ? {} : [])
      }
      traverseObj[lastKey] = $.extend(traverseObj[lastKey], value)

    // Set
    } else {
      traverseObj[lastKey] = value
    }

    // @debug
    // console.log('Utility.setObjProp: extend='+extend, traverseObj, lastKey, traverseObj[lastKey])
    return traverseObj[lastKey]
  },

  // Shortcut alias to above with extend set to true
  // @returns {Mixed} {Undefined} if it doesn't, or else it returns the value of the prop
  extendObjProp: function (obj, propChain, value) {
    var self = this
    return self.setObjProp.apply(self, [obj, propChain, value, true])
  },

  // Check if object has a prop via a propChain
  objHasProp: function (obj, propChain) {
    var self = this
    var traverseObj = obj

    // Make sure the propChain is set
    if (!propChain) return

    // Format the prop's key
    function getPropKey (key) {
      // Support array objects which are indicated by integers
      if (/^\d+$/.test(key + '')) key = parseInt(key, 10)
      return key
    }

    // Traverse the object along the propChain, setting objects along the way like a boss
    propChain = (propChain.match(/[\[\]\.]/) ? propChain.split(/[\[\]\.]+/) : [propChain])
    for (var i = 0; i < propChain.length; i++) {
      var propKey = getPropKey(propChain[i])

      // Check if the point in the object is defined
      if (typeof traverseObj[propKey] === 'undefined') {
        return undefined
      }

      // Jump to the next level
      traverseObj = traverseObj[propKey]
    }

    // Since it didn't break above, consider it a success
    return traverseObj
  },

  // Apply each arguments' properties to the first (target) argument
  // Like $.extend, and _.assign but will replace previous value with new value even if new value is false/undefined/null
  inherit: function () {
    if (arguments.length < 2) return
    var target = arguments[0]

    // @debug
    // console.log('Utility.inherit', arguments)

    for (var i = 1; i < arguments.length; i++) {
      // Move along if the target prop is same as reference prop, or reference isn't an object
      if (target[j] === arguments[i] || typeof arguments[i] !== 'object') continue

      for (var j in arguments[i]) {
        // Ignore prototype properties
        if (arguments[i].hasOwnProperty(j)) {
          target[j] = arguments[i][j]
        }
      }
    }

    return target
  },

  // Same as inherit, except will also traverse nested objects
  inheritNested: function () {
    if (arguments.length < 2) return
    var target = arguments[0]
    for (var i = 1; i < arguments.length; i++) {
      // Move along if the target prop is same as reference prop, or reference isn't an object
      if (target[j] === arguments[i] || typeof arguments[i] !== 'object') continue

      for (var j in arguments[i]) {
        // Ignore prototype properties
        if (arguments[i].hasOwnProperty(j)) {
          // If both target property and reference property are objects, do inheritNested
          if (typeof target[j] === 'object' && typeof arguments[i][j] === 'object') {
            // Create a new object to inherit into to avoid some weirdness
            target[j] = Utility.inheritNested({}, target[j], arguments[i][j])
          } else {
            target[j] = arguments[i][j]
          }
        }
      }
    }
    return target
  },

  /*
   * Test for IE
   */

  isIE: function (version) {
    var versionNum = ~~(version + ''.replace(/\D+/g, ''))
    if (/^\</.test(version)) {
      version = 'lt-ie' + versionNum
    } else {
      version = 'ie' + versionNum
    }
    return $html.is('.' + version)
  },

  /*
   * Responsive
   */

  /*
   * Set to device height
   * Relies on element to have [data-set-device-height] attribute set
   * to one or many breakpoint names, e.g. `data-set-device-height="xs sm"`
   * for device's height to be applied at those breakpoints
   */
  setDeviceHeights: function () {
    // Always get the site header height to remove from the element's height
    var siteHeaderHeight = Utility.$siteHeader.outerHeight()
    var deviceHeight = window.innerHeight - siteHeaderHeight

    // Set element to height of device
    $('[data-set-device-height]').each(function (i, elem) {
      var $elem = $(elem)
      var checkBp = $elem.attr('data-set-device-height').trim().toLowerCase()
      var setHeight = false

      // @debug
      // console.log(typeof checkBp, checkBp, setHeight)

      // Turn elem setting into an array to iterate over later
      if (checkBp) {
        if (!/[, ]/.test(checkBp)) {
          checkBp = [checkBp]
        } else {
          checkBp = checkBp.split(/[, ]+/)
        }

        // Check if elem should be set to device's height
        for (var j in checkBp) {
          if (new RegExp(checkBp[j], 'i').test(Utility.getActiveBreakpoints())) {
            setHeight = checkBp[j]
            break
          }
        }

      // If no breakpoint(s) given, assume height of all devices
      } else {
        setHeight = true
      }

      // Set the height
      if (setHeight) {
        // @debug
        // console.log('Setting element height to device', currentBreakpoint, checkBp)
        $elem.css('height', deviceHeight + 'px').addClass('ui-set-device-height')
      } else {
        $elem.css('height', '').removeClass('ui-set-device-height')
      }
    })
  },

  /*
   * Equal Height
   * Sets multiple elements to be the equal (maximum) height
   * Elements require attribute [data-equal-height] set. You can also specify the
   * breakpoints you only want this to be applied to in this attribute, e.g.
   * `<div data-equal-height="xs">..</div>` would only be applied in `xs` breakpoint
   * If you want to separate equal height elements into groups, additionally
   * set the [data-equal-height-group] attribute to a unique string ID, e.g.
   * `<div data-equal-height="xs" data-equal-height-group="promo1">..</div>`
   */
  setEqualHeights: function () {
    var equalHeights = {}

    // Iterate over items with [data-equal-height] attribute
    $('[data-equal-height]').each(function (i, elem) {
      var $elem = $(elem)

      // Equal height config per elem
      var applyToBp = $elem.attr('data-equal-height')
      var groupName = $elem.attr('data-equal-height-group') || 'default'

      // Calc eleme's height
      var elemHeight
      // Height set via equal-height, so clear it to get the original element height
      if ($elem.is('.ui-set-equal-height')) {
        // Height was originally set on element via inline CSS, so reference that value
        if ($elem.is('.ui-equal-height-inlinecss')) {
          elemHeight = parseFloat($elem.attr('data-equal-height-original'))

        } else {
          // Remove height set via inline CSS to test height of element
          elemHeight = $elem.css('height', '').outerHeight()
        }

      } else {
        // Mark elem's height was set via inline CSS
        if ($elem.attr('style') && $elem.attr('style').match(/[^-]height:/i)) {
          $elem.addClass('ui-equal-height-inlinecss')
        }

        // Get the height of the element
        elemHeight = $elem.outerHeight()
      }

      // Get the original height
      var originalHeight = $elem.attr('data-equal-height-original') || elemHeight

      // Add px to the original height value if not already set
      if (typeof originalHeight === 'number') originalHeight += 'px'

      // Set the original height if not already set
      if (typeof $elem.attr('data-equal-height-original') === 'undefined') {
        $elem.attr('data-equal-height-original', originalHeight)
      }

      // Create value to save max height to
      if (!equalHeights.hasOwnProperty(groupName)) equalHeights[groupName] = 0

      // Only apply to certain breakpoints
      if (applyToBp) {
        applyToBp = applyToBp.split(/[ ,]+/)

        // Test breakpoint
        if (new RegExp(applyToBp.join('|'), 'i').test(Utility.getActiveBreakpoints())) {
          // Set max height
          if (elemHeight > equalHeights[groupName]) equalHeights[groupName] = elemHeight
        }

      // No breakpoint set? Doits
      } else {
        // Set max height
        if (elemHeight > equalHeights[groupName]) equalHeights[groupName] = elemHeight
      }

    // After processing all, apply height (depending on breakpoint)
    }).each(function (i, elem) {
      var $elem = $(elem)
      var groupName = $elem.attr('data-equal-height-group') || 'default'
      var applyToBp = $elem.attr('data-equal-height')
      var originalHeight = $elem.attr('data-equal-height-original')

      // Only apply to certain breakpoints
      if (applyToBp) {
        applyToBp = applyToBp.split(/[ ,]+/)

        // Test breakpoint
        if (new RegExp(applyToBp.join('|'), 'i').test(Utility.getActiveBreakpoints())) {
          $elem.addClass('ui-set-equal-height')
          $elem.height(equalHeights[groupName])

          // Remove height
        } else {
          // Set to the original height, or remove the height CSS style to use CSS class's style
          $elem.removeClass('ui-set-equal-height')
            .css('height', $elem.is('.ui-equal-height-inlinecss') ? originalHeight : '')
        }

      // No breakpoint set? Apply indiscriminately
      } else {
        $elem.addClass('ui-set-equal-height')
        $elem.height(equalHeights[groupName])
      }
    })

    // @debug
    // console.log('equalHeights', equalHeights)
  },

  /*
   * Update Window
   */
  // Manual debouncing instead of using requestAnimationFrame
  // I found significant slowness when using requestAnimationFrame
  timerDebounceUpdateWindow: 0,
  debounceUpdateWindow: function () {
    clearTimeout(Utility.timerDebounceUpdateWindow)
    Utility.timerDebounceUpdateWindow = setTimeout(function () {
      Utility.updateWindow()
    }, 50)
  },

  // Perform actions when the window needs to be updated
  // This should be only invoked by debounceUpdateWindow
  updateWindow: function () {
    clearTimeout(Utility.timerDebounceUpdateWindow)
    // requestAnimationFrame(updateWindow)

    // @trigger doc `UI:beforeupdate`
    $doc.trigger('UI:beforeupdate')

    // Get active breakpoints
    window.currentBreakpoint = Utility.getActiveBreakpoints()

    // @trigger doc `UI:update:beforeresize`
    $doc.trigger('UI:update:beforeresize')

    // Set device heights
    Utility.setDeviceHeights()

    // Update equal heights
    Utility.setEqualHeights()

    // @trigger doc `WatchScroll:refresh` [targetElem]
    $doc.trigger('WatchScroll:refresh', [window])

    // @trigger doc `UI:update:afterresize`
    $doc.trigger('UI:update:afterresize')

    // Trigger UI:update event to signal to any other elements that need to update on this event
    // @trigger doc `UI:update`
    $doc.trigger('UI:update')
  }
}

/*
 * jQuery Plugins
 */
// Utility.scrollTo shorthand on an element
// @jquery-plugin $(selector).uiScrollTo()
// @param {Mixed} options Can be {String} target selector to scroll element to, or an {Object} containing different settings to apply to the Utility.scrollTo method
$.fn.uiScrollTo = function (options) {
  var args = Array.prototype.slice.call(arguments)

  return this.each(function (i, elem) {
    if (args.length === 1) {
      Utility.scrollTo(args[0], undefined, undefined, elem)
    } else if (args.length > 1) {
      Utility.scrollTo.apply(Utility, args)
    }
  })
}

/*
 * jQuery Events
 */
$doc.on('ready', function () {

    // Hook into orientationchange/resize event to update the window
    $win.on('orientationchange resize', Utility.debounceUpdateWindow)

    // @bind document `UI:updateWindow` Fire debounceUpdateWindow to update any elements due to repaint/reflow
    $doc.on('UI:updateWindow', Utility.debounceUpdateWindow)

  })

module.exports = Utility
