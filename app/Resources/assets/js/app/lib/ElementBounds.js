/*
 * Bounds
 * Get the bounds of an element
 * You can also perform other operations on the bounds (combine, scale, etc.)
 * This is used primarily by WatchScroll to detect if an element
 * is within the visible area of another element
 */

var $ = require('jquery')
var Utility = require('Utility')

function isElement(o){
  // Regular element check
  return (
    typeof HTMLElement === "object" ? o instanceof HTMLElement : //DOM2
    o && typeof o === "object" && o !== null && o.nodeType === 1 && typeof o.nodeName==="string"
  )
}

/*
 * Bounds object class
 */
var Bounds = function () {
  var self = this
  var args = Array.prototype.slice.call(arguments)

  // Properties
  self.id = Utility.randomString()
  self.coords = [0, 0, 0, 0]
  self.width = 0
  self.height = 0
  self.elem = undefined
  self.$viz = undefined

  // Initialise with any arguments, e.g. new Bounds(0, 0, 100, 100)
  if (args.length > 0) self.setBounds.apply(self, args)

  return self
}

// Set the bounds (and update width and height properties too)
// @returns {Bounds}
Bounds.prototype.setBounds = function () {
  var self = this

  // Check if 4 arguments given: x1, y1, x2, y2
  var args = Array.prototype.slice.call(arguments)

  // Single argument given
  if (args.length === 1) {
    // Bounds object given
    if (args[0] instanceof Bounds) {
      return args[0].clone()

    // 4 points given: x1, y1, x2, y2
    } else if (args[0] instanceof Array && args[0].length === 4) {
      args = args[0]

    // String or HTML element given
    } else if (typeof args[0] === 'string' || isElement(args[0]) || args[0] === window) {
      // @debug console.log('setBoundsFromElem', args[0])
      return self.setBoundsFromElem(args[0])
    }
  }

  // 4 coords given
  if (args.length === 4) {
    for (var i = 0; i < args.length; i++) {
      self.coords[i] = args[i]
    }
  }

  // Recalculate width and height
  self.width = self.getWidth()
  self.height = self.getHeight()

  return self
}

// Update the bounds (only if element is attached)
// @returns {Void}
Bounds.prototype.update = function () {
  var self = this

  // Only if related to an element
  if (self.elem) {
    self.setBoundsFromElem(self.elem)
  }

  self.width = self.getWidth()
  self.height = self.getHeight()

  // Update the viz
  if (self.getViz().length > 0) self.showViz()

  return self
}

// Calculate the width of the bounds
// @returns {Number}
Bounds.prototype.getWidth = function () {
  var self = this
  return self.coords[2] - self.coords[0]
}

// Calculate the height of the bounds
// @returns {Number}
Bounds.prototype.getHeight = function () {
  var self = this
  return self.coords[3] - self.coords[1]
}

// Scale the bounds: e.g. scale(2) => double, scale(1, 2) => doubles only height
// @returns {Bounds}
Bounds.prototype.scale = function () {
  var self = this
  var args = Array.prototype.slice.apply(arguments)
  var width
  var height
  var xScale = 1
  var yScale = 1

  // Depending on the number of arguments, scale the bounds
  switch (args.length) {
    case 0:
      return

    case 1:
      if (typeof args[0] === 'number') {
        xScale = args[0]
        yScale = args[0]
      }
      break

    case 2:
      if (typeof args[0] === 'number') xScale = args[0]
      if (typeof args[1] === 'number') yScale = args[1]
      break
  }

  // @debug console.log('Bounds.scale', xScale, yScale)

  // Scale
  if (xScale !== 1 || yScale !== 1) {
    width = self.getWidth()
    height = self.getHeight()
    self.setBounds(
      self.coords[0],
      self.coords[1],
      self.coords[0] + (width * xScale),
      self.coords[1] + (height * yScale)
    )
  }

  return self
}

// Combine with another bounds
// @returns {Bounds}
Bounds.prototype.combine = function () {
  var self = this
  var args = Array.prototype.slice.call(arguments)
  var totalBounds = self.clone().coords
  var newBounds

  if (args.length > 0) {
    // Process each item in the array
    for (var i = 0; i < args.length; i++) {
      // Bounds object given
      if (args[i] instanceof Bounds) {
        newBounds = args[i]

      // HTMLElement, String or Array given (x1, y1, x2, y2)
      } else if (typeof args[i] === 'string' || args[i] instanceof Array || isElement(args[i]) || args[0] === window) {
        newBounds = new Bounds(args[i])
      }

      // Combine
      if (newBounds) {
        for (var j = 0; j < newBounds.coords.length; j++) {
          // Set lowest/highest values of bounds
          if ((j < 2 && newBounds.coords[j] < totalBounds[j]) ||
              (j > 1 && newBounds.coords[j] > totalBounds[j])) {
            totalBounds[j] = newBounds.coords[j]
          }
        }
      }
    }

    // Set new combined bounds
    return self.setBounds(totalBounds)
  }
}

// Set bounds based on a DOM element
// @returns {Bounds}
Bounds.prototype.setBoundsFromElem = function (elem) {
  var self = this
  var $elem
  var elemWidth = 0
  var elemHeight = 0
  var elemOffset = {
    left: 0,
    top: 0
  }
  var elemBounds = [0, 0, 0, 0]
  var windowOffset = {
    left: $(window).scrollLeft(),
    top: $(window).scrollTop()
  }

  // Clarify elem objects
  if (!elem) elem = self.elem
  if (typeof elem === 'undefined') return self
  $elem = $(elem)
  if ($elem.length === 0) return self
  self.elem = elem = $elem[0]

  // Treat window object differently
  if (elem === window) {
    elemWidth = $(window).innerWidth()
    elemHeight = $(window).innerHeight()
    windowOffset.left = 0
    windowOffset.top = 0

  // Any other element
  } else {
    elemWidth = $elem.outerWidth()
    elemHeight = $elem.outerHeight()
    elemOffset = $elem.offset()
  }

  // Calculate the bounds
  elemBounds = [
    (elemOffset.left - windowOffset.left),
    (elemOffset.top - windowOffset.top),
    (elemOffset.left + elemWidth - windowOffset.left),
    (elemOffset.top + elemHeight - windowOffset.top)
  ]

  // @debug
  // self.showViz()
  // console.log('Bounds.setBoundsFromElem', {
  //   elem: elem,
  //   elemBounds: elemBounds,
  //   elemWidth: elemWidth,
  //   elemHeight: elemHeight,
  //   windowOffset: windowOffset
  // })

  // Instead of creating a new bounds object, just update these values
  self.coords = elemBounds
  self.width = self.getWidth()
  self.height = self.getHeight()

  // Set the bounds
  //return self.setBounds(elemBounds)
  return self
}

// Check if coords or bounds within another Bounds object
// @returns {Boolean}
Bounds.prototype.withinBounds = function () {
  var self = this
  var args = Array.prototype.slice.call(arguments)
  var totalBounds
  var visible = false

  // Calculate the total bounds
  for (var i = 0; i < args.length; i++) {
    var addBounds
    // Bounds object
    if (args[i] instanceof Bounds) {
      addBounds = args[i]

    // Array object
    } else if (args[i] instanceof Array) {
      // Single co-ord given (x, y)
      if (args[i].length === 2) {
        addBounds = new Bounds(args[i][0], args[i][1], args[i][0], args[i][1])

      // Pair of co-ords given (x1, y1, x2, y2)
      } else if (args[i].length === 4) {
        addBounds = new Bounds(args[i])
      }

    // Selector
    } else if (typeof args[i] === 'string') {
      addBounds = new Bounds().getBoundsFromElem(args[i])
    }

    // Add to total
    if (totalBounds) {
      totalBounds.combine(addBounds)

    // Create new total
    } else {
      totalBounds = addBounds
    }
  }

  // @debug
  // totalBounds.showViz()

  // See if this Bounds is within the totalBounds
  visible = self.coords[0] < totalBounds.coords[2] && self.coords[2] > totalBounds.coords[0] &&
            self.coords[1] < totalBounds.coords[3] && self.coords[3] > totalBounds.coords[1]

  return visible
}

Bounds.prototype.getCoordsVisibleWithinBounds = function () {
  var self = this
  var args = Array.prototype.slice.call(arguments)
  var totalBounds
  var coords = [false, false, false, false]

  // Calculate the total bounds
  for (var i = 0; i < args.length; i++) {
    var addBounds
    // Bounds object
    if (args[i] instanceof Bounds) {
      addBounds = args[i]

    // Array object
    } else if (args[i] instanceof Array) {
      // Single co-ord given (x, y)
      if (args[i].length === 2) {
        addBounds = new Bounds(args[i][0], args[i][1], args[i][0], args[i][1])

      // Pair of co-ords given (x1, y1, x2, y2)
      } else if (args[i].length === 4) {
        addBounds = new Bounds(args[i])
      }

    // Selector
    } else if (typeof args[i] === 'string') {
      addBounds = new Bounds().getBoundsFromElem(args[i])
    }

    // Add to total
    if (totalBounds) {
      totalBounds.combine(addBounds)

    // Create new total
    } else {
      totalBounds = addBounds
    }
  }

  // Check each coord
  if (self.coords[0] >= totalBounds.coords[0] && self.coords[0] <= totalBounds.coords[2]) coords[0] = self.coords[0]
  if (self.coords[1] >= totalBounds.coords[1] && self.coords[1] <= totalBounds.coords[3]) coords[1] = self.coords[1]
  if (self.coords[2] >= totalBounds.coords[0] && self.coords[2] <= totalBounds.coords[2]) coords[2] = self.coords[2]
  if (self.coords[3] >= totalBounds.coords[1] && self.coords[3] <= totalBounds.coords[3]) coords[3] = self.coords[3]

  return coords
}

// Get the offset between two bounds
// @returns {Bounds}
Bounds.prototype.getOffsetBetweenBounds = function (bounds) {
  var self = this

  var offsetCoords = [
    self.coords[0] - bounds.coords[0],
    self.coords[1] - bounds.coords[1],
    self.coords[2] - bounds.coords[2],
    self.coords[3] - bounds.coords[3]
  ]

  return new Bounds(offsetCoords)
}

// Creates a copy of the bounds
// @returns {Bounds}
Bounds.prototype.clone = function () {
  var self = this
  return new Bounds(self.coords)
}

// To string
// @returns {String}
Bounds.prototype.toString = function () {
  var self = this
  return self.coords.join(',')
}

// Bounds Visualiser
Bounds.prototype.getVizId = function () {
  var self = this
  var id = self.id
  if (self.elem) {
    id = $(self.elem).attr('id') || ($.isWindow(self.elem) ? 'window' : self.elem.name) || self.id
  }
  return 'bounds-viz-' + id
}

Bounds.prototype.getViz = function () {
  var self = this
  var $boundsViz = $('#' + self.getVizId())
  return $boundsViz
}

Bounds.prototype.showViz = function () {
  var self = this

  // @debug
  var $boundsViz = self.getViz()
  if ($boundsViz.length === 0) {
    $boundsViz = $('<div id="'+self.getVizId()+'" class="bounds-viz"></div>').css({
      position: 'fixed',
      backgroundColor: ['red','green','blue','yellow','orange'][Math.floor(Math.random() * 5)],
      opacity: .2,
      zIndex: 9999999
    }).appendTo('body')
  }

  // Set $viz element
  self.$viz = $boundsViz

  // Update viz properties
  $boundsViz.css({
    left: self.coords[0] + 'px',
    top: self.coords[1] + 'px',
    width: self.getWidth() + 'px',
    height: self.getHeight() + 'px'
  })

  return $boundsViz
}

Bounds.prototype.removeViz = function () {
  var self = this
  var $boundsViz = self.getViz()
  self.$viz = undefined
  $boundsViz.remove()
  return self
}

module.exports = Bounds
