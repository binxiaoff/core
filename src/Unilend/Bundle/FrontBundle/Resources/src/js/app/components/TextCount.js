/*
 * Unilend Text Counter
 */

var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var Tween = require('Tween')
var __ = require('__')

function isFloat (input) {
  input = Utility.convertStringToFloat(input)

  // Has a decimal point?
  if (/\./.test(input + '')) return true

  return false
}

// @class TextCount
var TextCount = function (elem, options) {
  var self = this
  self.$elem = $(elem)
  if (self.$elem.length === 0 || elem.hasOwnProperty('TextCount')) return false

  /*
   * Properties
   */
  self.elem = self.$elem[0]
  self.timer = false
  self.track = {}

  /*
   * Options
   */
  self.settings = $.extend({
    // Properties
    fps: 60,
    startCount: undefined, // int/float
    endCount: undefined, // int/float
    totalTime: 0, // in ms
    roundFloat: false, // how to round the float: {Booelan} false or {String} 'round', 'floor', 'ceil'
    limitDecimal: 0,
    isPrice: false,
    tweenCount: false,
    debug: false,

    // Custom event methods
    // -- Use the default prototype formatOutput function
    formatOutput: self.formatOutput
  }, ElementAttrsObject(elem, {
    fps: 'data-fps',
    startCount: 'data-start-count',
    endCount: 'data-end-count',
    totalTime: 'data-total-time',
    roundFloat: 'data-round-float',
    limitDecimal: 'data-limit-decimal',
    isPrice: 'data-is-price',
    tweenCount: 'data-tween-count',
    debug: 'data-debug'
  }), options)

  // Get start/end time within element text if not set
  if (typeof self.settings.startCount === 'undefined' && typeof self.settings.endCount !== 'undefined') self.settings.startCount = Utility.convertStringToFloat(self.$elem.text())
  if (typeof self.settings.endCount === 'undefined' && typeof self.settings.startCount !== 'undefined') self.settings.endCount = Utility.convertStringToFloat(self.$elem.text())

  // Limit decimal for prices
  if (self.settings.isPrice && typeof self.settings.limitDecimal === 'undefined') {
    self.settings.limitDecimal = 2
  }

  // Invalid tween count function name
  if (typeof self.settings.tweenCount !== 'undefined') {
    if (!Tween.hasOwnProperty(self.settings.tweenCount)) {
      self.settings.tweenCount = false
    }
  }

  /*
   * UI
   */
  self.$elem.addClass('ui-textcount')

  /*
   * Initialising
   */
  // Ensure element has direct access to its TextCount
  self.elem.TextCount = self

  // Set the initial tracking values
  self.resetCount()

  // @debug
  if (self.settings.debug) console.log('new TextCount', self)

  return self
}

/*
 * Methods
 */
// Reset count
TextCount.prototype.resetCount = function () {
  var self = this
  self.stopCount()

  // Reset the tracking vars
  self.track = {
    fps:        parseInt(self.settings.fps, 10) || 60,      // int
    start:      Utility.convertStringToFloat(self.settings.startCount) || 0,  // can be int/float
    current:    0,
    end:        Utility.convertStringToFloat(self.settings.endCount) || 0,    // can be int/float
    total:      parseInt(self.settings.totalTime, 10) || 0, // int
    progress:   0 // float: from 0 to 1
  }

  self.track.timeIncrement = Math.ceil(self.track.total / self.track.fps) || 0
  self.track.increment = ((self.track.end - self.track.start) / self.track.timeIncrement) || 0
  self.track.current = self.track.start

  // Reset the count
  self.setText(self.track.current)

  // @trigger elem `TextCount:resetted` [{TextCount}]
  self.$elem.trigger('TextCount:resetted', [self])
}

// Start counting
TextCount.prototype.startCount = function () {
  var self = this

  // @debug
  if (self.settings.debug) console.log('TextCount.startCount: started=%s, ended=%s, direction=%s', self.started()+'', self.ended()+'', self.countDirection()+'')

  if (!self.started() && !self.ended() && self.countDirection() !== 0 && self.track.start != self.track.end) {
    self.timer = setInterval(function () {
      self.incrementCount()
    }, self.track.timeIncrement)

    // @trigger elem `TextCount:started` [{TextCount}]
    self.$elem.trigger('TextCount:started', [self])
  }
}

// Increment the count
TextCount.prototype.incrementCount = function () {
  var self = this
  // Increment the count
  var count = self.track.current = self.track.current + self.track.increment

  // Progress
  self.track.progress = (self.track.current / Math.max(self.track.start, self.track.end))

  // Round float
  if (self.settings.roundFloat) {
    switch (self.settings.roundFloat) {
      case 'round':
        count = Math.round(count)
        break

      case 'ceil':
        count = Math.ceil(count)
        break

      case 'floor':
        count = Math.floor(count)
        break
    }
  }

  // Set the count text
  self.setText(count)

  // End the count at end of progress
  if (self.ended()) self.endCount()
}

// Set the text
TextCount.prototype.setText = function (count) {
  var self = this
  var output = count

  // Format the count
  if (typeof self.settings.formatOutput === 'function') {
    output = self.settings.formatOutput.apply(self, [count])
  }

  // @debug
  if (self.settings.debug) console.log('TextCount.setText', count, output)

  // Set the element's text
  self.$elem.text(output)
}

// Stop counting
TextCount.prototype.stopCount = function () {
  var self = this
  clearTimeout(self.timer)
  self.timer = false

  // @trigger elem `TextCount:stopped` [{TextCount}]
  self.$elem.trigger('TextCount:stopped', [self])
}

// Seek to end
TextCount.prototype.endCount = function () {
  var self = this
  self.stopCount()
  self.track.progress = 1
  self.track.current = self.track.end
  self.setText(self.track.end)

  // @trigger elem `TextCount:ended` [{TextCount}]
  self.$elem.trigger('TextCount:ended', [self])
}

// Check if has started
TextCount.prototype.started = function () {
  var self = this
  return self.timer !== false
}

// Check if has stopped
TextCount.prototype.stopped = function () {
  var self = this
  return !self.timer
}

// Check if has ended
TextCount.prototype.ended = function () {
  var self = this
  return ((self.countDirection() ===  1 && self.track.current < self.track.end) ||
          (self.countDirection() === -1 && self.track.current > self.track.end))
}

// Get direction of count
// 1: upward
// -1: downward
// 0: nowhere
TextCount.prototype.countDirection = function () {
  var self = this
  if (self.track.start > self.track.end) return  1
  if (self.track.start < self.track.end) return -1
  return 0
}

// Default format output function
TextCount.prototype.formatOutput = function (count) {
  var self = this
  var newCount = count

  // Tween the count
  if (self.settings.tweenCount) {
    newCount = Tween[self.settings.tweenCount].apply(this, [self.track.progress, self.track.start, Math.max(self.track.start, self.track.end) - Math.min(self.track.start, self.track.end), 1])
  }

  // Format the output number
  var output = __.formatNumber(newCount, self.settings.limitDecimal, self.settings.isPrice)

  // @debug
  if (self.settings.debug) console.log('TextCount.formatOutput', count, newCount, output)

  return output
}

/*
 * jQuery Plugin
 */
$.fn.uiTextCount = function (op) {
  op = op || {}

  // Fire a command to the TextCount object, e.g. $('[data-textcount]').uiTextCount('startCount')
  if (typeof op === 'string' && /^(startCount|stopCount|resetCount|setText)$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.hasOwnProperty('TextCount') && typeof elem.TextCount[op] === 'function') {
        elem.TextCount[op].apply(elem.TextCount, args)
      }
    })

  // Set up a new TextCount instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.hasOwnProperty('TextCount')) {
        new TextCount(elem, op)
      }
    })
  }
}

/*
 * jQuery Events
 */
$(document)
  // Auto-init component behaviours on document ready, or when parent element (or self) is made visible with `UI:visible` custom event
  .on('ready UI:visible', function (event) {
    $(event.target).find('[data-textcount]').not('.ui-textcount').uiTextCount()
  })

module.exports = TextCount
