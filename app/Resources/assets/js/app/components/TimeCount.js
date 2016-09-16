/*
 * TimeCount
 */

// @TODO get the right text showing when outputting the time difference direction

var $ = require('jquery')
var sprintf = require('sprintf-js').sprintf
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var __ = require('__')

// Overall timer for updating time counts (better in single timer than per element so all related time counters are updated at the same time)
var TimeCountTimer = setInterval(function () {
  var $timeCounters = $('.ui-timecount-counting')
  if ($timeCounters.length > 0) {
    $timeCounters.each(function (i, elem) {
      if (elem.hasOwnProperty('TimeCount')) {
        elem.TimeCount.update()
      }
    })
  }
}, 1000)

// Decompte (adapted from old Unilend)
function decompte(fromDate) {
  var output = {
    day: 0,
    hour: 0,
    minute: 0,
    second: 0
  }
  var from = new Date(fromDate)
  var now = new Date()

  // on fait en sorte d'etre a lheure fr quelque soit le fuseau horaire
  var diff = 1
  if (now.dst()) {
    diff = 2
  }
  var tzOffset = now.getTimezoneOffset()
  now.setHours(now.getHours() + (tzOffset / 60 + diff))

  timeTmp  = parseInt(now.getTime() / 1000, 10)
  timeLeft = fromDate.getTime() - timeTmp
  day      = parseInt((timeLeft / (60 * 60 * 24)), 10)
  hour     = parseInt((timeLeft / (60 * 60) - day * 24), 10)
  minute   = parseInt((timeLeft / 60 - day * 24 * 60 - hour * 60), 10)
  second   = parseInt((timeLeft - day * 24 * 60 * 60 - hour * 60 * 60 - minute * 60), 10)

  // on fait rien
  // We do nothing if it's greater than 1 month
  if (day > 30) {
    output.day = day

  // si +2j ou +1j et 23h
  // If more than 2 days or 1 day and 23 hours...
  } else if (day >= 1 && hour > 23 || day >= 2) {
    output.hour = hour

  // si j-2 h-1 (1j+h23)
  // If less than 2 days and 1 hour...
  } else if (day <= 1 && hour <= 23 && day > 0 || day === 0 && hour >= 2 || day === 0 && hour == 1 && minute > 59) {
    var allHour = parseInt(day * 24)
    var newHour = parseInt(hour + allHour)
    output.hour = newHour

  // If greater than (or equal to) one minute
  } else if (minute > 1 || heure > 0 && minute >= 0) {
    var allMinute = parseInt(hour * 60)
    var newMinute = parseInt(minute + allMinutes)
    output.minute = newMinute

  // If less than one minute
  } else {
    var allSecond = parseInt(minute * 60)
    var newSecond = parseInt(second + allSecond)
    output.second = newSecond
  }

  return output
}

// TimeCount Class
var TimeCount = function (elem, options) {
  var self = this

  // The related element
  self.$elem = $(elem)
  if (self.$elem.length === 0 || elem.hasOwnProperty('TimeCount')) return

  // Settings
  self.settings = $.extend({
    startDate: false, // {String} representing date/time or {Date}
    endDate: false, // {String} representing date/time or {Date}
    relative: false, // {Boolean}

    // Callbacks
    onupdate: self.outputTime, // {Function} function (timeDiff) {}
    onstart: undefined, // {Function}
    oncomplete: undefined // {Function}
  }, ElementAttrsObject(elem, {
    startDate: 'data-timecount-from',
    endDate: 'data-timecount-to',
    relative: 'data-timecount-relative'
  }), options)

  // Set up the dates
  if (self.settings.startDate && !(self.settings.startDate instanceof Date)) self.settings.startDate = new Date(self.settings.startDate)
  if (self.settings.endDate && !(self.settings.endDate instanceof Date)) self.settings.endDate = new Date(self.settings.endDate)

  // Track
  self.track = {
    timeDiff: Utility.getTimeDiff(self.settings.startDate, self.settings.endDate),
    countDirection: 0
  }

  // Get the count direction
  // -- Both dates have been set
  if (self.settings.startDate && self.settings.endDate) {
    self.track.countDirection = 0

  // -- Only one of the dates have been set, the other date will be detected as 'now'
  } else if (self.settings.startDate && !self.settings.endDate) {
    self.track.countDirection = 1
  } else if (!self.settings.startDate && self.settings.endDate) {
    self.track.countDirection = -1
  }

  // UI
  self.$elem.addClass('ui-timecount')

  // Add a class to count
  if (self.track.countDirection !== 0) self.$elem.addClass('ui-timecount-counting')

  // Attach reference to TimeCount to elem
  self.$elem[0].TimeCount = self

  // Fire the callback
  if (typeof self.settings.onstart === 'function') {
    self.settings.onstart.apply(self)
  }

  // @trigger elem `TimeCount:starting`
  self.$elem.trigger('TimeCount:starting', [self, self.track.timeDiff])

  // Update the time remaining
  self.update()

  return self
}

// Update the time count
TimeCount.prototype.update = function () {
  var self = this
  self.track.timeDiff = Utility.getTimeDiff(self.settings.startDate, self.settings.endDate)

  // @debug
  // console.log('TimeCount.update', self.$elem[0], self.track.timeDiff, self.track.countDirection)

  // Fire callback
  if (typeof self.settings.onupdate === 'function') {
    self.settings.onupdate.apply(self, [self.track.timeDiff])
  }

  // @trigger elem `TimeCount:update`
  self.$elem.trigger('TimeCount:update', [self, self.track.timeDiff])

  // Complete if finished and a start/end date is set
  if (self.isComplete()) self.complete()

  // @debug
  // console.log('TimeCount.update', self.$elem[0], self.settings.startDate, self.settings.endDate, self.track.countDirection)
}

// Check if the TimeCount is complete
TimeCount.prototype.isComplete = function () {
  var self = this

  // Only complete if there's no set start date and a set end date
  if (!self.settings.startDate && self.settings.endDate) {
    if (new Date() > self.settings.endDate) return true
  }

  return false
}

// Complete the time count
TimeCount.prototype.complete = function () {
  var self = this

  // @debug
  // console.log('TimeCount.complete', self.$elem[0], self.track.timeDiff, self.track.countDirection)

  // @trigger elem `TimeCount:completing`
  self.$elem.trigger('TimeCount:completing', [self, self.track.timeDiff])

  // Remove the .ui-time-counting class
  self.$elem.removeClass('ui-timecount-counting')

  // Fire the callback
  if (typeof self.settings.oncomplete === 'function') {
    self.settings.oncomplete.apply(self)
  }

  // @trigger elem `TimeCount:completed`
  self.$elem.trigger('TimeCount:completed', [self, self.track.timeDiff])
}

// Output the time to the element
// @note this is the default outputTime function. Feel free to override this depending on your timecount needs
//       see main.dev.js for an example (search for )
TimeCount.prototype.outputTime = function (timeDiff) {
  var self = this
  var output

  // Relative time
  if (self.settings.relative) {
    output = self.getRelativeTime()

  // Timecode
  } else {
    output = self.getTimecode()
  }

  // @debug
  // if (self.settings.startDate && self.settings.endDate) {
  //   console.log('TimeCount.outputTime', self.$elem[0], output, timeDiff, self.track.countDirection)
  // }

  if (output) self.$elem.text(output)
}

// Get a {String} relative time from a timeDiff object
TimeCount.prototype.getRelativeTime = function (startDate, endDate) {
  var self = this
  if (!startDate) startDate = self.settings.startDate
  if (!endDate) endDate = self.settings.endDate
  var output = Utility.getRelativeTime(startDate, endDate)

  // @note DEV-949 leaving out relative time count direction text
  // // Indicate time direction relative to the end date
  // if (startDate || endDate) {
  //   // End is in the past
  //   if (new Date() > (startDate || endDate)) {
  //     output = sprintf(__.__('%s ago', 'timeCountAgo'), output)
  //
  //   // End is in the future
  //   } else {
  //     output = sprintf(__.__('%s remaining', 'timeCountRemaining'), output)
  //   }
  // }

  return output
}

// Get a {String} timecode from a timeDiff object
TimeCount.prototype.getTimecode = function (timeDiff) {
  var self = this
  var timeCode = []
  if (!timeDiff) timeDiff = self.track.timeDiff
  if (timeDiff.years !== 0) timeCode.push(Math.abs(timeDiff.years))
  if (timeDiff.months !== 0) timeCode.push(Utility.leadingZero(Math.abs(timeDiff.months)))
  if (timeDiff.days !== 0) timeCode.push(Utility.leadingZero(Math.abs(timeDiff.days)))
  timeCode.push(Utility.leadingZero(Math.abs(timeDiff.hours)))
  timeCode.push(Utility.leadingZero(Math.abs(timeDiff.minutes)))
  timeCode.push(Utility.leadingZero(Math.abs(timeDiff.seconds)))
  return timeCode.join(':')
}

/*
 * jQuery plugin
 */
$.fn.uiTimeCount = function (op) {
  return this.each(function (i, elem) {
    if (!elem.hasOwnProperty('TimeCount')) {
      new TimeCount(elem, op)
    }
  })
}

/*
 * jQuery Initialisation
 */
$(document)
  // Auto-init component behaviours on document ready, or when parent element (or self) is made visible with `UI:visible` custom event
  .on('ready UI:visible', function (event) {
    $(event.target).find('[data-timecount]').not('.ui-timecount').uiTimeCount()
  })
