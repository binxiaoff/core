/*
 * Unilend Text Counter
 */

var $ = require('jquery')
var ElementAttrsObject = require('ElementAttrsObject')
var Tween = require('Tween')
var __ = require('__')

var TextCount = function (elem, options) {
  var self = this

  /*
   * Properties
   */
  self.$elem = $(elem)
  self.elem = self.$elem[0]
  self.timer = false
  self.track = {}

  /*
   * Options
   */
  self.settings = $.extend({
    fps: 60,
    startCount: parseFloat(self.$elem.text()), // int/float
    endCount: 0, // int/float
    totalTime: 0, // in ms
    roundFloat: false, // how to round the float (and if)
    formatOutput: false
  }, ElementAttrsObject(elem, {
    fps: 'data-fps',
    startCount: 'data-start-count',
    endCount: 'data-end-count',
    totalTime: 'data-total-time',
    roundFloat: 'data-round-float'
  }), options)

  /*
   * UI
   */
  self.$elem.addClass('ui-text-count')

  /*
   * Initialising
   */
  // Ensure element has direct access to its TextCount
  self.elem.TextCount = self

  // Set the initial tracking values
  self.resetCount()

  // @debug console.log( self )

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
    start:      parseFloat(String(self.settings.startCount).replace(/[^\d\-\.]+/g, '')) || 0,  // can be int/float
    current:    0,
    end:        parseFloat(self.settings.endCount) || 0,    // can be int/float
    total:      parseInt(self.settings.totalTime, 10) || 0, // int
    progress:   0 // float: from 0 to 1
  }

  self.track.timeIncrement = Math.ceil(self.track.total / self.track.fps) || 0
  self.track.increment = ((self.track.end - self.track.start) / self.track.timeIncrement) || 0
  self.track.current = self.track.start

  // Reset the count
  self.setText(self.track.current)
}

// Start counting
TextCount.prototype.startCount = function () {
  var self = this
  if ( self.countDirection() !== 0 && self.track.start != self.track.end ) {
    self.timer = setInterval( function () {
      self.incrementCount()
    }, self.track.timeIncrement )
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
  if ( (self.countDirection() ===  1 && self.track.current < self.track.end) ||
       (self.countDirection() === -1 && self.track.current > self.track.end)    ) {
    self.endCount()
  }
}

// Set the text
TextCount.prototype.setText = function ( count ) {
  var self = this
  // Format the count
  if ( typeof self.settings.formatOutput === 'function' ) {
     count = self.settings.formatOutput.apply(self, [count])
  }

  // Set the element's text
  self.$elem.text(count)
}

// Stop counting
TextCount.prototype.stopCount = function () {
  var self = this
  clearTimeout(self.timer)
  self.timer = false
}

// Seek to end
TextCount.prototype.endCount = function () {
  var self = this
  self.stopCount()
  self.track.progress = 1
  self.track.current = self.track.end
  self.setText(self.track.end)
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

// Get direction of count
// 1: upward
// -1: downward
// 0: nowhere
TextCount.prototype.countDirection = function () {
  var self = this
  if ( self.track.start > self.track.end ) return  1
  if ( self.track.start < self.track.end ) return -1
  return 0
}

/*
 * jQuery Plugin
 */
$.fn.uiTextCount = function (op) {
  op = op || {}

  return this.each(function (i, elem) {
    // @debug
    // console.log('assign TextCount', elem)

    // Already assigned, ignore elem
    if (elem.hasOwnProperty('TextCount')) return

    var $elem = $(elem)
    var isPrice = /[\$\€\£]/.test($elem.text())
    var limitDecimal = $elem.attr('data-round-float') ? 0 : 2 // Set site-wide defaults here
    var tweenCount = $elem.attr('data-tween-count') || false // Set site-wide defaults here
    var debug = $elem.attr('data-debug') === 'true' // Output debug values for this item
    if (tweenCount && !Tween.hasOwnProperty(tweenCount)) tweenCount = false

    // Use separate functions here to reduce load within formatOutput callback
    if (tweenCount) {
      op.formatOutput = function (count) {
        // Tween the number
        var newCount = Tween[tweenCount].apply(this, [this.track.progress, this.track.start, Math.max(this.track.start, this.track.end) - Math.min(this.track.start, this.track.end), 1])

        // @debug if (debug) console.log(this.track.progress, count + ' => ' + newCount)

        // Format the output number
        return __.formatNumber(newCount, limitDecimal, isPrice)
      }
    } else {
      op.formatOutput = function (count) {
        // Format the output number
        return __.formatNumber(count, limitDecimal, isPrice)
      }
    }

    // Initialise the text count
    new TextCount(elem, op)

    // @debug
    // console.log('initialised TextCount', elem.TextCount)
  })
}

/*
 * jQuery Events
 */
$(document)
  // Initalise any element with the `.ui-text-count` class
  .on('ready', function () {
    // Applies to all generic .ui-text-count elements
    $('.ui-text-count').uiTextCount()
  })

module.exports = TextCount
