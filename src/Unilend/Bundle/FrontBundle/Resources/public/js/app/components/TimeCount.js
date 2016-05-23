/*
 * TimeCount
 */

var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var __ = require('__')

// Overall timer for updating time counts (better in single timer than per element so all related time counters are updated at the same time)
var TimeCountTimer = setInterval(function () {
  var $timeCounters = $('.ui-time-counting')
  if ($timeCounters.length > 0) {
    $timeCounters.each(function (i, elem) {
      if (elem.hasOwnProperty('TimeCount')) {
        elem.TimeCount.update()
      }
    })
  }
}, 1000)

// TimeCount Class
var TimeCount = function (elem, options) {
  var self = this

  // The related element
  self.$elem = $(elem)
  if (self.$elem.length === 0) return

  // Settings
  self.settings = $.extend({
    startDate: false,
    endDate: false
  }, ElementAttrsObject(elem, {
    startDate: 'data-time-count-from',
    endDate: 'data-time-count-to'
  }), options)

  // Set up the dates
  if (self.settings.startDate && !(self.settings.startDate instanceof Date)) self.settings.startDate = new Date(self.settings.startDate)
  if (self.settings.endDate && !(self.settings.endDate instanceof Date)) self.settings.endDate = new Date(self.settings.endDate)

  // Track
  self.track = {
    direction: (self.settings.startDate > self.settings.endDate ? -1 : 1),
    timeRemaining: Utility.getTimeRemaining(self.settings.endDate, self.settings.startDate)
  }

  // UI
  self.$elem.addClass('ui-time-counting')

  // Attach reference to TimeCount to elem
  self.$elem[0].TimeCount = self

  // Trigger the starting event
  self.$elem.trigger('TimeCount:starting', [self, self.track.timeRemaining])

  // Update the time remaining
  self.update()

  return self
}

// Update the time count
TimeCount.prototype.update = function () {
  var self = this
  self.track.timeRemaining = Utility.getTimeRemaining(self.settings.endDate, self.settings.startDate)

  // Trigger the update event on the UI element
  self.$elem.trigger('TimeCount:update', [self, self.track.timeRemaining])

  // Count complete
  if ((self.track.direction > 0 && self.track.timeRemaining.total <= 0) ||
      (self.track.direction < 0 && self.track.timeRemaining.total >= 0)) {
    self.complete()
  }
}

// Complete the time count
TimeCount.prototype.complete = function () {
  var self = this

  // Trigger the completing event on the UI element
  self.$elem.trigger('TimeCount:completing', [self, self.track.timeRemaining])

  // Remove the .ui-time-counting class
  self.$elem.removeClass('.ui-time-counting')

  // Trigger the completed event on the UI element
  self.$elem.trigger('TimeCount:completed', [self, self.track.timeRemaining])
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
  .on('ready', function () {
    $('.ui-time-count').uiTimeCount()
  })
