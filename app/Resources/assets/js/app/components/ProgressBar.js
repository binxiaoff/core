/*
 * Unilend ProgressBar
 * When making a new component, use this as a base
 *
 * @componentName   ProgressBar
 * @className       ui-progressbar
 * @attrPrefix      data-progressbar
 * @langName        PROGRESSBAR
 */

// Lib Dependencies
var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')

/*
 * ProgressBar
 * @class
 */
var ProgressBar = function (elem, options) {
  var self = this
  self.$elem = $(elem)

  // Error: element not specified or already has an instance of this component
  if (self.$elem.length === 0 || elem.hasOwnProperty('ProgressBar')) return false

  // Component's instance settings
  self.settings = $.extend({
    current: 0,
    total: 1

    // @note Maybe add option to animate from 0 to current
  }, ElementAttrsObject(elem, {
    current: 'data-progressbar-current',
    total: 'data-progressbar-total'
  }), options)

  // Tracking
  self.track = {
    current: 0,
    barWidth: 0
  }

  // Other elements
  self.$bar = self.$elem.find('[data-progressbar-bar], .ui-progressbar-bar')

  // Assign class to show component behaviours have been applied (required)
  self.$elem.addClass('ui-progressbar')
  self.$bar.addClass('ui-progressbar-bar')

  // Assign instance of class to the element (required)
  self.$elem[0].ProgressBar = self

  // Set the current value
  if (self.settings.current > 0) {
    self.setCurrent(self.settings.current)
  }

  return self
}

/*
 * Prototype properties and methods (shared between all class instances)
 */

// Set the current value
// @method setCurrent
// @param {Number} newCurrent
ProgressBar.prototype.setCurrent = function (newCurrent) {
  var self = this
  newCurrent = parseFloat(newCurrent)

  // Max
  if (newCurrent > self.settings.total) {
    newCurrent = self.settings.total
  }

  // Update the track values
  self.track.current = newCurrent
  self.track.barWidth = Math.ceil((self.settings.current / self.settings.total) * 100)

  // Update UI
  self.$elem.attr('data-progressbar-current', self.track.current)
  self.$bar.width(self.track.barWidth + '%')
}

/*
 * Destroy the ProgressBar instance
 *
 * @method destroy
 * @returns {Void}
 */
ProgressBar.prototype.destroy = function () {
  var self = this

  // Do other necessary teardown things here, like destroying other related plugin instances, etc. Most often used to reduce memory leak

  self.$elem[0].ProgressBar = null
  delete self
}

/*
 * jQuery Plugin
 */
$.fn.uiProgressBar = function (op) {
  // Fire a command to the ProgressBar object, e.g. $('[data-progressbar]').uiProgressBar('publicMethod', {..})
  // @todo add in list of public methods that $.fn.uiProgressBar can reference
  if (typeof op === 'string' && /^(setCurrent|destroy)$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.hasOwnProperty('ProgressBar') && typeof elem.ProgressBar[op] === 'function') {
        elem.ProgressBar[op].apply(elem.ProgressBar, args)
      }
    })

    // Set up a new ProgressBar instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.hasOwnProperty('ProgressBar')) {
        new ProgressBar(elem, op)
      }
    })
  }
}

/*
 * jQuery Events
 */
$(document)
// Auto-init `[data-progressbar]` elements through declarative instantiation
.on('ready UI:visible', function (event) {
  $(event.target).find('[data-progressbar]').not('.ui-progressbar').uiProgressBar()
})

module.exports = ProgressBar