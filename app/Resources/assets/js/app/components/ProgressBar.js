/*
 * Unilend ProgressBar
 * When making a new component, use this as a base
 *
 * @componentName   ProgressBar
 * @className       ui-progressbar
 * @attrPrefix      data-progressbar
 * @langName        PROGRESSBAR
 */

// Dependencies
var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var Templating = require('Templating')
var gsap = require('gsap')
var __ = require('__')

/*
 * ProgressBar
 * @class
 */
var ProgressBar = function (elem, options) {
  var self = this
  self.$elem = $(elem)

  // Error: element not specified or already has an instance of this component
  if (self.$elem.length === 0) return false

  // Element already has instance of this component, return that
  if (elem.hasOwnProperty('ProgressBar')) return elem.ProgressBar

  // Component's instance settings
  self.settings = $.extend({
    current: 0,
    total: 1,
    showLabel: false,
    labelText: '{{percent}}&nbsp;%',
    labelPosition: 'right inside', // Which side to put the label, and whether inside or outside the progress bar
    repositionLabel: true, // Reposition the label within the bar if it displays outside the bar
    animation: true, // Use GSAP to animate the bar (otherwise rely on CSS transition, if set)
    animationTime: 1000, // Time in milliseconds for the animation
    animationDelay: 0 // Time in milliseconds before the first animation plays
  }, ElementAttrsObject(elem, {
    current: 'data-progressbar-current',
    total: 'data-progressbar-total',
    showLabel: 'data-progressbar-showlabel',
    labelText: 'data-progressbar-labeltext',
    labelPosition: 'data-progressbar-labelposition',
    repositionLabel: 'data-progressbar-repositionlabel',
    animation: 'data-progressbar-animation',
    animationTime: 'data-progressbar-animationtime',
    animationDelay: 'data-progressbar-animationdelay'
  }), options)

  // Tracking
  self.track = {
    current: 0,
    percent: 0,
    elemWidth: 0,
    labelWidth: 0
  }

  // Other elements
  self.$bar = self.$elem.find('[data-progressbar-bar], .ui-progressbar-bar')
  self.$label = self.$elem.find('[data-progressbar-label], .ui-progressbar-label')

  // It has a label, so set showLabel true to enable the repositionLabel update behaviours
  if (self.$label.length) {
    self.settings.showLabel = true
  }

  // Assign class to main element to show behaviours have been applied
  self.$elem.addClass('ui-progressbar')

  // Assign instance of class to the element (required)
  self.$elem[0].ProgressBar = self

  // Initialise the ProgressBar
  self.init()

  return self
}

/*
 * Prototype properties and methods (shared between all class instances)
 */

// Templates
ProgressBar.prototype.templates = {
  bar: '<div class="ui-progressbar-bar">{{ label }}</div>',

  // The label to render
  label: '<span class="ui-progressbar-label"></span>'
}

/*
 * Initialise the progress bar
 *
 * @method init
 * @returns {Void}
 */
ProgressBar.prototype.init = function (newCurrent) {
  var self = this

  // Create the bar (and the label)
  if (!self.$bar.length) {
    self.$bar = $(Templating.replace(self.templates.bar, {
      label: self.templates.label
    }))

    // Append to the element
    self.$elem.append(self.$bar)

    // Set up the label (if the bar has it within)
    if (self.$bar.find('[data-progressbar-label], .ui-progressbar-label').length) {
      self.$label = self.$bar.find('[data-progressbar-label], .ui-progressbar-label')
    }
  }

  // Create the label
  if (!self.$label.length) {
    self.$label = $(self.templates.label)

    // Add the label to the bar
    if (self.settings.showLabel) {
      self.$bar.append(self.$label)
    }
  }

  // Get the label text to use (only if the element was found first)
  if (self.$label.length && self.$label.eq(0).html()) {
    self.settings.labelText = self.$label.eq(0).html()
  }

  // Assign classes to child elements
  self.$bar.addClass('ui-progressbar-bar')
  self.$label.addClass('ui-progressbar-label')

  // Set a class to mark to show it has animation
  if (self.settings.animation) {
    self.$elem.addClass('ui-progressbar-animation')
  } else {
    self.$elem.removeClass('ui-progressbar-animation')
  }

  // Set a class to mark to show a label
  if (self.settings.showLabel) {
    self.$elem.addClass('ui-progressbar-showlabel')
  } else {
    self.$label.hide()
    self.$elem.removeClass('ui-progressbar-showlabel')
  }

  // @quickfix since label position `right outside` is currently not adequately supported
  if (self.settings.labelPosition.match('right') && self.settings.labelPosition.match('outside')) {
    self.settings.labelPosition = 'left outside'
  }

  // Set the label position classes
  var labelPosition = Utility.convertStringToArray(self.settings.labelPosition)
  var classLabelPosition = []

  for (var i = 0; i < labelPosition.length; i++) {
    classLabelPosition.push('ui-progressbar-labelposition-' + labelPosition[i])
  }
  self.$elem.addClass(classLabelPosition.join(' '))

  // Set the current value
  if (self.settings.current > 0) {
    self.setCurrent(self.settings.current, true)
  }

  // Update the UI...
  // -- after delay
  if (self.settings.animation && self.settings.animationDelay) {
    setTimeout(function () {
      self.update()
    }, self.settings.animationDelay)

  // -- Immediately
  } else {
    self.update()
  }
}

/*
 * Set the current value
 *
 * @method setCurrent
 * @param {Number} newCurrent
 * @returns {Void}
 */
ProgressBar.prototype.setCurrent = function (newCurrent, skipUpdate) {
  var self = this
  newCurrent = parseFloat(newCurrent)

  // Max
  if (newCurrent > self.settings.total) {
    newCurrent = self.settings.total
  }

  // Update the track values
  self.track.current = newCurrent
  self.track.percent = (self.track.current / self.settings.total) * 100

  // @debug
  // console.log('ProgressBar.setCurrent', newCurrent, {
  //   current: self.track.current,
  //   percent: self.track.percent,
  //   total: self.settings.total
  // })

  // Update attributes
  self.$elem.attr('data-progressbar-current', self.track.current)
  self.$elem.attr('data-progressbar-percent', Math.floor(self.track.percent))

  // Updates by default, unless skipped
  if (!skipUpdate) {
    self.update()
  }
}

/*
 * Get the rendered label text
 *
 * @method getLabelText
 * @param {String} labelText
 * @returns {Void}
 */
ProgressBar.prototype.getLabelText = function (labelText) {
  var self = this

  // Use the instance's labelText
  if (!labelText) {
    labelText = self.settings.labelText
  }

  // Render the text and replace keywords
  return Templating.replace(labelText, {
    current: self.track.current,
    total: self.track.total,
    percent: __.formatNumber(Math.floor(self.track.percent * 10) / 10)
  })
}

/*
 * Update the UI component
 *
 * @method update
 * @returns {Void}
 */
ProgressBar.prototype.update = function () {
  var self = this

  // Animate the bar
  if (self.settings.animation) {
    self.$bar.animate({
      width: self.track.percent + '%'
    }, self.settings.animationTime || 200)
  } else {
    self.$bar.width(Math.floor(self.track.percent) + '%')
  }

  // Update the label value
  if (self.settings.showLabel) {
    self.$label.html(self.getLabelText())

    // Move the label inside if it is too large for the outside on the right (and vice-versa)
    self.repositionLabel()
  }
}

/*
 * Reposition the label
 *
 * @method repositionLabel
 * @returns {Void}
 */
ProgressBar.prototype.repositionLabel = function () {
  var self = this

  // Don't do if not showing/repositioning label
  if (!self.settings.showLabel || !self.settings.repositionLabel) return

  // Update the label position
  self.track.elemWidth = self.$elem.outerWidth()
  self.track.labelWidth = self.$label.outerWidth()

  // Get the overlap percentages
  var leftLabelOverlapPercent = (self.track.labelWidth / self.track.elemWidth) * 100
  var rightLabelOverlapPercent = 100 - leftLabelOverlapPercent

  // @debug
  // console.log('ProgressBar.repositionLabel', {
  //   ProgressBar: self,
  //   labelPosition: self.settings.labelPosition,
  //   elemWidth: self.track.elemWidth,
  //   labelWidth: self.track.labelWidth,
  //   leftLabelOverlapPercent: leftLabelOverlapPercent,
  //   rightLabelOverlapPercent: rightLabelOverlapPercent
  // })

  // Right Inside
  // @note This should be default behaviour
  // ##::LABEL::::::::::::::::::: Bar too short, text is on left & outside
  // ##LABEL##::::::::::::::::::: Once label can fit inside, it moves inside & on right
  // ########LABEL##::::::::::::: Plenty of space on either side, text is on right & inside
  // #####################LABEL## Maximum width, text is on right & inside
  if (self.settings.labelPosition.match('right') && self.settings.labelPosition.match('inside')) {

    // Place left outsite
    if (self.$elem.is('.ui-progressbar-labelposition-right.ui-progressbar-labelposition-inside') && self.track.percent < leftLabelOverlapPercent) {
      self.$elem.removeClass('ui-progressbar-labelposition-right ui-progressbar-labelposition-inside').addClass('ui-progressbar-labelposition-left ui-progressbar-labelposition-outside')

      // Place back right inside
    } else if (self.$elem.is('.ui-progressbar-labelposition-left.ui-progressbar-labelposition-outside') && self.track.percent > leftLabelOverlapPercent) {
      self.$elem.removeClass('ui-progressbar-labelposition-left ui-progressbar-labelposition-outside').addClass('ui-progressbar-labelposition-right ui-progressbar-labelposition-inside')
    }

    // Right outside
    // @note since label is placed in progressbar-bar, the utmost right is the max width of the
    //       progressbar-bar element (so it currently won't work correctly unless I pull out label
    //       and manage its position/width via JS). As a quickfix I'm just going to change
    //       self.settings.labelPosition to `left outside` in the `init` function
    // ##:::::::::::::::::::LABEL:: Bar too short, text is on right & outside
    // ##############:::::::LABEL:: Plenty of space on either side, text is on right & outside
    // ################LABEL##::::: Once label can't fit outside, it moves inside & on right
    // #####################LABEL## Maximum width, text is on right & inside
  } else if (self.settings.labelPosition.match('right') && self.settings.labelPosition.match('outside')) {

    // Place right inside
    if (self.$elem.is('.ui-progressbar-labelposition-outside') && self.track.percent > rightLabelOverlapPercent) {
      self.$elem.removeClass('ui-progressbar-labelposition-left ui-progressbar-labelposition-outside').addClass('ui-progressbar-labelposition-right ui-progressbar-labelposition-inside')

      // Place back right outside
    } else if (self.$elem.is('.ui-progressbar-labelposition-inside') && self.track.percent <= rightLabelOverlapPercent) {
      self.$elem.removeClass('ui-progressbar-labelposition-left ui-progressbar-labelposition-inside').addClass('ui-progressbar-labelposition-right ui-progressbar-labelposition-outside')
    }

  // Left inside
  // ##::LABEL::::::::::::::::::: Bar too short, text is on left & outside
  // ##LABEL##::::::::::::::::::: Once label can fit inside, it moves inside & on left
  // ##LABEL#######:::::::::::::: Plenty of space on either side, text is on left & inside
  // ##LABEL##################### Maximum width, text is on left & inside
  } else if (self.settings.labelPosition.match('left') && self.settings.labelPosition.match('inside')) {

    // Place outside
    if (self.$elem.is('.ui-progressbar-labelposition-inside') && self.track.percent < leftLabelOverlapPercent) {
      self.$elem.removeClass('ui-progressbar-labelposition-right ui-progressbar-labelposition-inside').addClass('ui-progressbar-labelposition-left ui-progressbar-labelposition-outside')

    // Place back inside
    } else if (self.$elem.is('.ui-progressbar-labelposition-outside') && self.track.percent > leftLabelOverlapPercent) {
      self.$elem.removeClass('ui-progressbar-labelposition-right ui-progressbar-labelposition-outside').addClass('ui-progressbar-labelposition-left ui-progressbar-labelposition-inside')
    }

  // Left Outside
  // ##::LABEL::::::::::::::::::: Bar too short, text is on left & outside
  // #########::LABEL:::::::::::: Plenty of space on either side, text is on left & outside
  // ################LABEL##::::: Once label can't fit outside, it moves inside & on right
  // #####################LABEL## Maximum width, text is on right & inside
  } else if (self.settings.labelPosition.match('left') && self.settings.labelPosition.match('outside')) {

    // Place right inside
    if (self.$elem.is('.ui-progressbar-labelposition-left.ui-progressbar-labelposition-outside') && self.track.percent > rightLabelOverlapPercent) {
      self.$elem.removeClass('ui-progressbar-labelposition-left ui-progressbar-labelposition-outside').addClass('ui-progressbar-labelposition-right ui-progressbar-labelposition-inside')

      // Place back left outside
    } else if (self.$elem.is('.ui-progressbar-labelposition-right.ui-progressbar-labelposition-inside') && self.track.percent < rightLabelOverlapPercent) {
      self.$elem.removeClass('ui-progressbar-labelposition-right ui-progressbar-labelposition-inside').addClass('ui-progressbar-labelposition-left ui-progressbar-labelposition-outside')
    }
  }
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
  if (typeof op === 'string' && /^(update|repositionLabel|setCurrent|destroy)$/.test(op)) {
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
    $(event.target).find('[data-progressbar], ui-progressbar').uiProgressBar()
  })

  // Update the progressbar view when the UI is updated
  .on('UI:update', function (event) {
    $('.ui-progressbar.ui-progressbar-showlabel').uiProgressBar('repositionLabel')
  })

module.exports = ProgressBar