/*
 * Sticky stuff
 */

var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')

var STICKY_WATCHERS = []

// This will go and update any stickied element on the page
function updateStickyWatchers (hardUpdate) {
  $.each(STICKY_WATCHERS, function (i, sticky) {
    sticky.update(hardUpdate)
  })
}

/*
 * Sticky
 * @class
 */
var Sticky = function (elem, options) {
  var self = this
  self.$elem = $(elem)
  if (self.$elem.length === 0) return false

  // Settings
  self.settings = $.extend({
      breakpoints: /md|lg/,
      bufferTop: 25,
      bufferBottom: 0,

      // The parent to detect scrollTop from
      scrollParent: window,
      bounds: undefined,

      // Fires before recalculating necessary values
      onbeforehardupdate: function () {
        // Ensure the (fixed) siteHeaderHeight modifies the buffers too
        var siteHeaderHeight = Utility.$siteHeader.outerHeight()
        this.track.buffer.top = this.settings.bufferTop + siteHeaderHeight
        this.track.buffer.bottom = this.track.buffer.top + this.settings.bufferBottom
      }
    },
    // Override with element's attribute settings
    ElementAttrsObject(elem, {
      bufferTop: 'data-sticky-buffertop',
      bufferBottom: 'data-sticky-bufferbottom',
      scrollParent: 'data-sticky-scrollparent',
      bounds: 'data-sticky-bounds'
    }),
    // Override with JS instantiation settings
    options)

  // Track
  self.track = {
    buffer: {
      top: 0,
      bottom: 0
    },
    scrollParent: {
      scroll: {
        top: 0,
        left: 0
      }
    },
    bounds: {
      height: 0,
      offset: {
        top: 0,
        left: 0
      }
    },
    elem: {
      height: 0
    },
    sticky: {
      start: 0,
      end: 0,
      amount: 0,
      offset: 0
    }
  }

  // Elements

  // @todo Support non-window scroll parent elements (if necessary -- pretty tricky with managing offsets with nested scrollTop values)
  self.$scrollParent = $(self.settings.scrollParent || window)

  // Bounds element sets the area that the sticky can stick into
  if (!self.settings.bounds || $(self.settings.bounds).length === 0) {
    self.$elem.parents().each(function (i, parent) {
      var $parent = $(parent)
      var posType = $parent.css('position')
      if ($parent.is('.ui-sticky-bounds') || posType === 'relative') {
        self.settings.bounds = parent
        return false
      }
    })

    // Still no bounds? Use the scrollParent
    if (!self.settings.bounds) self.settings.bounds = self.settings.scrollParent
  }
  self.$bounds = $(self.settings.bounds)

  // Methods

  /*
   * Offset the element by margin
   *
   * @method _offsetMargin
   * @param {Mixed} amount The {Int} pixel amount to offset by, or {Boolean} false to remove any CSS offset
   * @return {Void}
   */
  self._offsetMargin = function (amount) {
    if (amount !== false) {
      self.$elem.css('marginTop', amount + 'px')
    } else {
      self.$elem.css('marginTop', '')
    }
  }

  /*
   * Offset the element by CSS3 transform
   *
   * @method _offsetTransform
   * @param {Mixed} amount The {Int} pixel amount to offset by, or {Boolean} false to remove any CSS offset
   * @return {Void}
   */
  self._offsetTransform = function (amount) {
    if (amount !== false) {
      self.$elem.css('transform', 'translateY(' + amount + 'px)')
    } else {
      self.$elem.css('transform', '')
    }
  }

  /*
   * Offset the element (chooses from either _offsetMargin or _offsetTransform depending on
   * the device's capabilities)
   *
   * @method offset
   * @param {Mixed} amount The amount to offset by, or {Boolean} false to remove any CSS offset
   * @return {Void}
   */
  if ($('html').is('.has-csstransforms')) {
    self.offset = self._offsetTransform
  } else {
    self.offset = self._offsetMargin
  }

  /*
   * Update the element (and calculate the necessary offsets)
   *
   * @method update
   * @param {Boolean} hardUpdate Whether to update all the values before calculating the offset
   * @return {Void}
   */
  self.update = function (hardUpdate) {
    if (Utility.isBreakpointActive(self.settings.breakpoints)) {
      // Hard Update recalculates all the main values
      if (hardUpdate) {
        // Update bounds values
        self.track.bounds.offset = self.$bounds.offset()
        self.track.bounds.height = self.$bounds.outerHeight()

        // Update elem values
        self.track.elem.height = self.$elem.outerHeight()

        // Set the buffer top/bottom
        self.track.buffer.top = self.settings.bufferTop
        self.track.buffer.bottom = self.settings.bufferBottom

        // Run function before hardupdate
        if (self.settings.onbeforehardupdate) self.settings.onbeforehardupdate.call(self)

        // Figure out sticky start/end
        self.track.sticky.start = self.track.bounds.offset.top - self.track.buffer.top
        self.track.sticky.end = self.track.bounds.offset.top + self.track.bounds.height - self.track.elem.height - self.track.buffer.bottom
      }

      // Calculate the offset amount based on parent's scrollTop
      self.track.scrollParent.scroll.top = self.$scrollParent.scrollTop()
      self.track.sticky.amount = self.track.scrollParent.scroll.top - self.track.sticky.start

      // Constrain within the sticky's start/end
      if (self.track.scrollParent.scroll.top > self.track.sticky.start) {
        if (self.track.scrollParent.scroll.top < self.track.sticky.end) {
          self.track.sticky.offset = self.track.sticky.amount
        } else {
          self.track.sticky.offset = self.track.sticky.end - self.track.sticky.start
        }

        // Apply the offset
        // console.log('apply offset', self.track.sticky.offset)
        self.offset(self.track.sticky.offset)

        // Reset the offset
      } else {
        // console.log('reset offset')
        self.offset(false)
      }

      // @debug
      // console.log('Sticky.update %s', (hardUpdate ? '(hard)' : ''), self.track.sticky.offset, self.track)
    } else {
      self.offset(false)
    }
  }

  // Initialise
  self.$elem.addClass('ui-sticky')
  self.$elem[0].Sticky = self
  STICKY_WATCHERS.push(self)

  // Don't forget to update yerself before you try to stick yerself!
  self.update(1)

  return self
}

/*
 * Prototype properties and methods shared between all instances
 */
Sticky.prototype._updateAllStickyWatchers = function (hardUpdate) {
  updateStickyWatchers(hardUpdate)
}

/*
 * jQuery Plugin for Sticky
 */
$.fn.uiSticky = function (op) {
  return this.each(function (i, elem) {
    if (!elem.hasOwnProperty('Sticky')) {
      new Sticky(elem, op)
    }
  })
}

// Instantiate any stickies
$(document)
  .on('ready UI:visible', function (event) {
    $('[data-sticky], .ui-has-sticky').not('.ui-sticky').uiSticky()
  })

module.exports = Sticky
