/*
 * Unilend Watch Scroll
 *
 * Set and manage callbacks to occur on element's scroll top/left value
 * Hopefully helps to avoid jank
 * Also enables detecting elements in relation to another for extra functions (mark navigation, etc.)
 */

// Watch element for scroll left/top and perform callback if:
// -- If element reaches scroll left/top value
// -- If specific child element in element is entered or is visible in viewport
// -- If specific child element in element is left or not visible in viewport

// Dependencies
var $ = require('jquery')
var Utility = require('Utility')
var Bounds = require('ElementBounds')
var raf = require('raf')

// Load requestAnimationFrame polyfill
if (window) {
  raf.polyfill()
}

var $win = $(window)
var $doc = $(document)
var $html = $('html')
var $body = $('body')

/*
 * WatchScroll
 */
var WatchScroll = {
  /*
   * Actions to test Watcher elements and targets with
   * @note See Watcher.checkTargetForAction() to see how these get applied
   * @property
   */
  actions: {
    // Checks to see if the target is outside the element
    outside: function (params) {
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem)
      var targetBounds = new Bounds().setBoundsFromElem(this)
      var state = targetBounds.withinBounds(elemBounds)
      // elemBounds.showViz()
      // targetBounds.showViz()
      if (!state) return 'outside'
    },

    // Checks to see if the target is before the element (X axis)
    before: function (params) {
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem)
      var targetBounds = new Bounds().setBoundsFromElem(this)
      var state = targetBounds.coords[2] < elemBounds.coords[0]
      // elemBounds.showViz()
      // targetBounds.showViz()
      if (state) return 'before'
    },

    // Checks to see if the target is after the element (X axis)
    after: function (params) {
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem)
      var targetBounds = new Bounds().setBoundsFromElem(this)
      var state = targetBounds.coords[0] > elemBounds.coords[2]
      // elemBounds.showViz()
      // targetBounds.showViz()
      if (state) return 'after'
    },

    // Checks to see if the target is above the element (Y axis)
    above: function (params) {
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem)
      var targetBounds = new Bounds().setBoundsFromElem(this)
      // target.Y2 < elem.Y1
      var state = targetBounds.coords[3] < elemBounds.coords[1]
      // elemBounds.showViz()
      // targetBounds.showViz()
      if (state) return 'above'
    },

    // Checks to see if the target is below the element (Y axis)
    below: function (params) {
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem)
      var targetBounds = new Bounds().setBoundsFromElem(this)
      // target.Y1 > elem.Y2
      var state = targetBounds.coords[1] > elemBounds.coords[3]
      if (state) return 'below'
    },

    // Checks if the target is past the element (Y axis)
    past: function (params) {
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem)
      var targetBounds = new Bounds().setBoundsFromElem(this)
      // target.Y1 > elem.Y1
      var state = targetBounds.coords[1] > elemBounds.coords[1]
      // elemBounds.showViz()
      // targetBounds.showViz()
      if (state) return 'past'
    },

    // Checks to see if the target is within the element
    within: function (params) {
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem)
      var targetBounds = new Bounds().setBoundsFromElem(this)
      var state = targetBounds.withinBounds(elemBounds)
      if (state) return 'within'
    },

    // Checks to see if the target is in top half of the element
    withinTopHalf: function (params) {
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem).scale(1, 0.5)
      var targetBounds = new Bounds().setBoundsFromElem(this)
      var state = targetBounds.withinBounds(elemBounds)
      if (state) return 'withintophalf'
    },

    // Checks to see if target is in the middle of the element
    withinMiddle: function (params) {
      // Get the bounds of all
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem)
      var targetBounds = new Bounds().setBoundsFromElem(this)

      // Get middle of elem
      var middleY1 = (elemBounds.getHeight() * 0.5) - 1
      var middleY2 = (elemBounds.getHeight() * 0.5)
      var middleBounds = new Bounds(elemBounds.coords[0], middleY1, elemBounds.coords[2], middleY2)

      // Is target within middle?
      var state = targetBounds.withinBounds(middleBounds)
      if (state) return 'withinmiddle'
    }
  },

  /*
   * WatchScroll.Watcher
   * Watches an element with a list of listeners and targets
   * @class
   * @param elem {String | HTMLElement} The element to watch scroll positions
   * @param options {Object} The options to configure the watcher
   */
  Watcher: function (elem, options) {
    var self = this

    /*
     * Element to be watched
     */
    self.$elem = $(elem) // jQuery
    self.elem = self.$elem[0] // Normal HTMLElement

    // Check if a watcher for this element already exists and use that, rather than creating another
    if (self.elem.hasOwnProperty('WatchScrollWatcher')) {
      // @debug
      // console.log('new WatchScroll.Watcher: using elem\'s existing watcher', self.elem, self.elem.WatchScrollWatcher)
      return self.elem.WatchScrollWatcher
    }

    /*
     * Properties
     */
    self.listeners = [] // List of listeners on this watcher

    /*
     * Options
     */
    self.options = $.extend({
      // Nothing yet
    }, options)

    /*
     * Methods
     */
    // Add a listener to watch a target element (or collection of elements)
    self.watch = function (target, action, callback) {
      // Needs a valid target and action
      if (typeof target !== 'object' && typeof target !== 'string') return
      if (typeof action !== 'string' && typeof action !== 'function') return
      // if (typeof callback !== 'function') return

      // Create the WatchScrollListener
      var watchScrollListener = new WatchScroll.Listener(target, action, callback)
      watchScrollListener.WatchScrollWatcher = self

      // @debug console.log('WatchScroll.watch', target, action)

      // Fire any relevant actions on the newly watched target
      watchScrollListener.$target.each(function (i, target) {
        for (var i = 0; i < watchScrollListener.action.length; i++) {
          var doneAction = self.checkTargetForAction(target, watchScrollListener.action[i], watchScrollListener.callback)
        }
      })

      // Enable watching
      if (watchScrollListener) self.listeners.push(watchScrollListener)

      // You can chain more watchers to the instance
      return self
    }

    // Get the bounds of an element
    self.getBounds = function (target) {
      var targetBounds = new Bounds().setBoundsFromElem(target).coords
      return targetBounds
    }

    // Check if a space (denoted either by an element, or by 2 sets of x/y co-ords) is visible within the element
    self.isVisible = function (target) {
      var elemBounds = new Bounds().setBoundsFromElem(self.elem)
      var targetBounds = new Bounds().setBoundsFromElem(target)
      var visible = targetBounds.withinBounds(elemBounds)
      return visible && $(target).is(':visible')
    }

    // Check all watchScrollListeners for element and determines if targets can be actioned upon
    self.checkListeners = function () {
      var targetsVisible = []

      // @trigger elem `WatchScroll:checkListeners:before` [elemWatcher]
      self.$elem.trigger('WatchScroll:checkListeners:before', [self])

      // Iterate over all listeners and fire callback depending on target's state (enter/leave/visible/hidden)
      for (var x in self.listeners) {
        var listener = self.listeners[x]

        // Iterate through each target
        listener.$target.each( function (i, target) {
          var isVisible = undefined

          // Only show visibility info if listener target is not the watching element
          if (self.elem !== target) {
            isVisible = self.isVisible(target)

            // Store the isVisible to apply as wasVisible after all listeners have been processed
            targetsVisible.push({
              target: target,
              wasVisible: isVisible
            })
          }

          // Iterate through each action
          for (var y in listener.action) {
            self.checkTargetForAction(target, listener.action[y], listener.callback)
          }
        })
      }

      // Iterate over all targets and apply their isVisible value to wasVisible
      if (targetsVisible.length > 0) {
        for (x in targetsVisible) {
          targetsVisible[x].target.wasVisible = targetsVisible[x].wasVisible
        }
      }

      // @trigger elem `WatchScroll:checkListeners:complete` [elemWatcher, targetsVisible]
      self.$elem.trigger('WatchScroll:checkListeners:complete', [self, targetsVisible])
    }

    // Alias for checkListeners
    self.refresh = function () {
      self.checkListeners()
    }

    // Check single target for state
    self.getTargetState = function (target) {
      var $target = $(target)
      target = $target[0]
      var state = []

      // Visibility
      var wasVisible = target.wasVisible || false
      var isVisible = self.isVisible(target)

      // Enter
      if ( !wasVisible && isVisible ) {
        state.push('enter')

      // Visible
      } else if ( wasVisible && isVisible ) {
        state.push('visible')

      // Leave
      } else if ( wasVisible && !isVisible ) {
        state.push('leave')

      // Hidden
      } else if ( !wasVisible && !isVisible ) {
        state.push('hidden')
      }

      // @debug console.log( 'WatchScroll.getTargetState', wasVisible, isVisible, target )

      return state.join(' ')
    }

    // Fire callback if target matches action
    //
    // Valid actions:
    //  -- scroll               => target.onscroll
    //  -- positionTop>50       => target.positionTop > 50
    //  -- scrollTop><50:100    => target.scrollTop > 50 && target.scrollTop < 100
    //  -- offsetTop<=>50:100   => target.offsetTop <= 0 && target.offsetTop >= 100
    //  -- enter                => target.isVisible && !target.wasVisible
    //
    // See switch control blocks below for more expressions and state keywords
    self.checkTargetForAction = function (target, action, callback) {
      var doAction = false
      var $target = $(target)
      var target = $target[0]
      var state

      // Custom action
      if (typeof action === 'function') {
        // Fire the action to see if it applies
        doAction = action.apply(target, [{
          Watcher: self,
          target: target,
          callback: callback
        }])

        // Successful action met
        if (doAction) {
          // Fire the callback
          if (typeof callback === 'function') callback.apply(target, [doAction])

          // Trigger actions for any other things watching
          // If your custom action returns a string, it'll trigger 'watchscroll-action-{returned string}'
          if (typeof doAction === 'string') $(target).trigger('watchscroll-action-' + doAction, [self])
        }

        return doAction
      }

      // Action is a string
      // Ensure lowercase
      action = action.toLowerCase()

      // Get target position
      if (/^((position|offset|scroll)top)/.test(action)) {
        // Break action into components, e.g. scrollTop>50 => scrolltop, >, 50
        var prop = action.replace(/^((position|offset|scroll)top).*$/, '$1').trim()
        var exp = action.replace(/^[\w\s]+([\<\>\=]+).*/, '$1').trim()
        var value = action.replace(/^[\w\s]+[\<\>\=]+(\s*[\d\-\.\:]+)$/, '$1').trim()
        var checkValue

        // Split value if it is a range (i.e. has a `:` separating two numbers: `120:500`)
        if (/\-?\d+(\.[\d+])?:\-?\d+(\.[\d+])?/.test(value)) {
          value = value.split(':')
          value[0] = parseFloat(value[0])
          value[1] = parseFloat(value[1])
        } else {
          value = parseFloat(value)
        }

        // Get the value to check based on prop
        switch (prop.toLowerCase()) {
          case 'positiontop':
            checkValue = $target.position().top
            break;

          case 'offsettop':
            checkValue = $target.offset().top
            break;

          case 'scrolltop':
            checkValue = $target.scrollTop()
            break;
        }

        // @debug console.log( action, prop, exp, value, checkValue )

        // Compare values
        switch (exp) {
          // eq
          case '=':
          case '==':
          case '===':
            if ( checkValue == value ) {
              doAction = true
            }
            break;

          // ne
          case '!=':
          case '!==':
            if ( checkValue == value ) {
              doAction = true
            }
            break;

          // gt
          case '>':
            if ( checkValue > value ) {
              doAction = true
            }
            break;

          // gte
          case '>=':
            if ( checkValue >= value ) {
              doAction = true
            }
            break;

          // lt
          case '<':
            if ( checkValue < value ) {
              doAction = true
            }
            break;

          // lte
          case '<=':
            if ( checkValue <= value ) {
              doAction = true
            }
            break;

          // outside range
          case '<>':
            if ( value instanceof Array && (checkValue < value[0] && checkValue > value[1]) ) {
              doAction = true
            }
            break;

          // outside range (including min:max)
          case '<=>':
            if ( value instanceof Array && (checkValue <= value[0] && checkValue >= value[1]) ) {
              doAction = true
            }
            break;

          // inside range
          case '><':
            if ( value instanceof Array && (checkValue > value[0] && checkValue < value[1]) ) {
              doAction = true
            }
            break;

          // Inside range (including min:max)
          case '>=<':
            if ( value instanceof Array && (checkValue >= value[0] && checkValue <= value[1]) ) {
              doAction = true
            }
            break;
        }

        // Keyword actions representing state: enter, leave, visible, hidden
      } else {
        state = self.getTargetState(target)
        if (state.match(action)) {
          doAction = true
        }
      }

      // @debug console.log(state, doAction, target, $target)
      // @debug console.log( 'WatchScroll.Watcher.checkTargetForAction:', action, target )

      if (doAction) {
        doAction = action
        // @debug console.log( ' --> ' + doAction )
        if (typeof callback === 'function') {
          callback.apply(target)
        }

        // Trigger actions for any other things watching
        if (typeof doAction === 'string') $(target).trigger('watchscroll-action-' + doAction, [self])
      }

      return doAction
    }


    /*
     * Scroll events
     */
    self.$elem.on('scroll', function (event) {
      // Let the browser determine best time to animate
      window.requestAnimationFrame(self.checkListeners)

      // @debug console.log('event.scroll')
      self.checkListeners()
    })

    // Attach the instance to the element
    self.elem.WatchScrollWatcher = self

    return self
  },

  /*
   * WatchScrollListener
   * @class
   */
  Listener: function (target, action, callback) {
    var self = this

    // Needs a target, action and callback
    if (typeof target !== 'object' && typeof target !== 'string') return false
    if (typeof action !== 'string' && typeof action !== 'function') return false

    // @debug console.log('added WatchScrollListener', target, action)

    /*
     * Properties
     */
    self.WatchScrollWatcher // Parent WatchScroll Watcher, for reference if needed
    self.$target = $(target) // The target(s)

    // Convert action to array of action(s)
    if (typeof action === 'string') {
      self.action = /\s/.test(action) ? action.split(/\s+/) : [action]
    } else {
      self.action = [action]
    }

    self.callback = callback
    self.hasCallback = (typeof callback === 'function')
    if (self.hasCallback) self.callback = callback

    /*
     * Methods
     */
    // Do callback
    self.doCallback = function () {
      if (!self.hasCallback) return

      self.$target.each( function (i, target) {
        // @debug console.log( 'WatchScrollListener', target, self.action )
        self.callback.apply(target)
      })
    }

    return self
  }
}

module.exports = WatchScroll
