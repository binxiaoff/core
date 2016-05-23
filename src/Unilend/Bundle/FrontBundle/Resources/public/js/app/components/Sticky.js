//
// Unilend Sticky
// Created because no other sticky solution out there would work properly with the specific use-cases
//

// @TODO finish first working version! This ain't working out so well so far

var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var WatchScroll = require('WatchScroll')
var Bounds = require('ElementBounds')

var windowBounds = new Bounds(window)
var StickyElements = []

function updateAll (hardUpdate) {
  $(document).trigger('Sticky:updateAll', [StickyElements, hardUpdate])

  windowBounds.update()
  for (var i = 0; i < StickyElements.length; i++) {
    StickyElements[i].update(hardUpdate)
  }

  $(document).trigger('Sticky:updatedAll', [StickyElements, hardUpdate])
}

/*
 * Sticky class
 *
 * @param {Mixed} elem Can be {String} selector, {HTMLElement} or {jQueryObject}
 * @param {Object} options Extra options to customise the sticky element
 */
var Sticky = function (elem, options) {
  var self = this

  self.$elem = $(elem)
  if (self.$elem.length === 0) return

  // Properties
  self.settings = $.extend({
    // Constrain the sticky item within other elements
    constraints: 'parent',
    // The start position where to stick the item in relation to the parent
    startPosition: 'top',
    // The end position where to stick the item in relation to the parent
    endPosition: 'bottom'
  }, ElementAttrsObject(elem, {
    constraints: 'data-sticky-constraints',
    startPosition: 'data-sticky-startposition',
    endPosition: 'data-sticky-endposition'
  }), options)

  // Tracking
  self.track = {
    $scrollParent: undefined,
    $constraints: undefined,
    elemBounds: new Bounds(elem),
    constraintsBounds: new Bounds(),
    offsetBounds: new Bounds()
  }

  // UI
  self.$elem.addClass('ui-sticky')

  // Initialise
  self.$elem[0].Sticky = self
  self.setScrollParent()
  self.update(1)

  // Debug
  self.track.elemBounds.showViz()
  self.track.constraintsBounds.showViz()

  console.log(self)

  // Push to general reference
  StickyElements.push(self)

  return self
}

// Set the scroll parent
Sticky.prototype.setScrollParent = function () {
  var self = this
  var $elemParents = self.$elem.parents()

  $elemParents.each(function (i, elem) {
    var $elem = $(elem)
    // Check if has scrolling enabled
    if (/(auto|scroll)/i.test($elem.css('overflow')) || $elemParents.length - 1 === i) {
      self.track.$scrollParent = $elem
      return false
    }
  })
}

// Calculate the constraints
Sticky.prototype.setConstraints = function (elements) {
  var self = this
  if (!elements) elements = self.settings.constraints

  // Get the constraints elements
  if (elements === 'parent') {
    self.track.$constraints = self.$elem.parent()
  } else {
    self.track.$constraints = $(elements)
  }

  // Set default if none detected
  if (self.track.$constraints.length === 0) self.track.$constraints = $(window)
}

// Calculate the constraints total bounds
Sticky.prototype.updateConstraintsBounds = function () {
  var self = this
  var constraintsBounds = undefined

  // Process each constraints element to combine the bounds
  self.track.$constraints.each(function (i, elem) {
    if (typeof constraintsBounds === 'undefined') {
      constraintsBounds = new Bounds(elem)
    } else {
      constraintsBounds.combine(elem)
    }
  })

  // Update the track.constraintsBounds
  self.track.constraintsBounds = constraintsBounds
  return constraintsBounds
}

// Calculate the position in relation to the constraints
Sticky.prototype.update = function (hardUpdate) {
  var self = this

  // Hard update refreshes the constraints
  if (hardUpdate) {
    self.setConstraints()
  }

  // Update the element's bounds
  self.updateConstraintsBounds()
  self.track.elemBounds.update()
  self.track.offsetBounds = self.track.elemBounds.getOffsetBetweenBounds(self.track.constraintsBounds)

  // Check if the element's bounds are within the constraints
  var checkWithin = self.track.elemBounds.getCoordsVisibleWithinBounds(self.track.constraintsBounds)
  var stickSides = {}
  var checkSides = ['left', 'top', 'right', 'bottom']
  for (var i = 0; i < checkSides.length; i++) {
    if (new RegExp(checkSides[i], 'i').test(self.settings.startPosition) && checkWithin[i] === false) {
      stickSides[checkSides[i]] = self.track.constraintsBounds.coords[i]
    }
  }

  // Stick/unstick
  if (stickSides != {}) {
    self.stick(stickSides)
  } else {
    self.unstick(stickSides)
  }
}

// Sticks item to the target, based on the constraints
Sticky.prototype.stick = function (stickSides) {
  var self = this

  self.$elem.addClass('stuck')
  self.$elem.trigger('Sticky:sticked', [self, stickSides])
}

// Unstick the item
Sticky.prototype.unstick = function (coords) {
  var self = this
  self.$elem.removeClass('stuck')
  self.$elem.trigger('Sticky:unsticked', [self, stickSides])
}

/*
 * jQuery Plugin
 */
$.fn.uiSticky = function (op) {
  // Fire a command to the Sticky object, e.g. $('[data-sticky]').uiSticky('update')
  if (typeof op === 'string' && /^(update|stick|unstick|unstickAll)$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Global function
    if (op === 'updateAll') {
      updateAll.apply(document, args)
      return
    }

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.hasOwnProperty('Sticky') && typeof elem.Sticky[op] === 'function') {
        elem.Sticky[op].apply(elem.Sticky, args)
      }
    })

  // Set up a new Sortable instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.hasOwnProperty('Sticky')) {
        new Sticky(elem, op)
      }
    })
  }
}

/*
 * jQuery Initialise
 */
$(document)
  .on('ready', function () {
    $('[data-sticky]').uiSticky()
  })

/*
 * Window events
 */
$(window)
  .on('resize orientationchange', function () {
    updateAll(1)
  })
  .on('scroll', function () {
    updateAll()
  })

module.exports = Sticky
