/*
 * Unilend Sortable
 * Re-order elements within another element depending on a value
 */

// Dependencies
var $ = require('jquery')

// Convert an input value (most likely a string) into a primitive, e.g. number, boolean, etc.
function convertToPrimitive (input) {
  // Non-string? Just return it straight away
  if (typeof input !== 'string') return input

  // Trim any whitespace
  input = (input + '').trim()

  // Number
  if (/^\-?(?:\d*[\.\,])*\d*(?:[eE](?:\-?\d+)?)?$/.test(input)) {
    return parseFloat(input)
  }

  // Boolean: true
  if (/^true|1$/.test(input)) {
    return true

  // NaN
  } else if (/^NaN$/.test(input)) {
    return NaN

  // undefined
  } else if (/^undefined$/.test(input)) {
    return undefined

  // null
  } else if (/^null$/.test(input)) {
    return null

  // Boolean: false
  } else if (/^false|0$/.test(input) || input === '') {
    return false
  }

  // Default to string
  return input
}

/*
 * Sortable class
 *
 * @class
 * @param {Mixed} elem Can be a {String} selector, {HTMLElement} or {jQueryElement}
 * @param {Object} options An object containing configurable settings for the sortable element
 * @returns {Sortable}
 */
var Sortable = function (elem, options) {
  var self = this

  // Invalid element
  if ($(elem).length === 0) return

  // Properties
  self.$elem = undefined
  self.$columns = undefined
  self.columnNames = []
  self.$content = undefined
  self.sortedColumn = false
  self.sortedDirection = false

  // Setup the Sortable
  return self.setup(elem, options)
}

/*
 * Get data attributes from an element (converts string values to JS primitives)
 *
 * @method attrsToObject
 * @param {Mixed} elem Can be a {String} selector, {HTMLElement} or {jQueryElement}
 * @param {Array} attrs An array of {Strings} which contain names of attributes to retrieve (these attributes will already be namespaced to fetch `data-sortable-{name}`)
 * @returns {Object}
 */
Sortable.prototype.attrsToObject = function (elem, attrs) {
  var $elem = $(elem).first()
  var self = this
  var output = {}

  if ($elem.length === 0 || !attrs) return output

  for (var i in attrs) {
    var attrValue = convertToPrimitive($elem.attr('data-sortable-' + attrs[i]))
    output[attrs[i]] = attrValue
  }

  return output
}

/*
 * Setup the element and properties
 *
 * @method setup
 * @param {Mixed} elem Can be a {String} selector, {HTMLElement} or {jQueryElement}
 * @param {Object} options An object containing configurable settings for the sortable element
 * @returns {Sortable}
 */
Sortable.prototype.setup = function (elem, options) {
  var self = this

  // Invalid element
  if ($(elem).length === 0) return

  // Unhook any previous elements
  if (self.$elem && self.$elem.length > 0) {
    self.$elem.removeClass('ui-sortable')
  }

  // Setup the properties
  self.$elem = $(elem).first()
  self.$columns = undefined
  self.columnNames = []
  self.$content = undefined
  self.sortedColumn = false
  self.sortedDirection = false

  // Get any settings applied to the element
  var elemSettings = Sortable.prototype.attrsToObject(elem, ['columns', 'content', 'saveoriginalorder'])

  // Settings with default values
  self.settings = $.extend({
    // The columns to sort by
    columns: '[data-sortable-by]',

    // The content to sort
    content: '[data-sortable-content]',

    // Sorting function
    onsortcompare: self.sortCompare,

    // Save the original order
    saveoriginalorder: true
  }, elemSettings, options)

  // Get/set the columns
  self.$columns = $(self.settings.columns)
  // -- Not found, look within element
  if (self.$columns.length === 0) {
    self.$columns = self.$elem.find(self.settings.columns)
    // -- Again, not found. Error!
    if (self.$columns.length === 0) {
      throw new Error('Sortable.setup error: no columns defined. Make sure you set each sortable column with the HTML attribute `data-sortable-by`')
    }
    // Set the column elements
    self.$columns = self.$columns

    // Get/set the column names
    self.$columns.each(function (i, elem) {
      var columnName = $(elem).attr('data-sortable-by')
      if (columnName) self.columnNames.push(columnName)
    })
  }

  // Get/set the content
  self.$content = $(self.settings.content)
  // -- Not found, look within element
  if (self.$content.length === 0) {
    self.$content = self.$elem.find(self.settings.content)
    // -- Again, not found. Error!
    if (self.$content.length === 0) {
      throw new Error('Sortable.setup error: no content defined. Make sure you set the sortable content with the HTML attribute `data-sortable-content`')
    }

    // Set the content element
    self.$content = self.$content.first()
  }

  // Save the original order
  if (self.settings.saveoriginalorder) {
    self.$content.children().each(function (i, item) {
      $(item).attr('data-sortable-original-order', i)
    })
  }

  // Trigger sortable element when ready
  self.$elem[0].Sortable = self
  self.$elem.trigger('sortable:ready')
  return self
}

/*
 * Sort the element's contents by a column name and in a particular direction
 * (if no direction given, it will toggle the direction)
 *
 * @method sort
 * @param {String} columnName The name of the column to sort by
 * @param {String} direction The direction to sort: `asc` | `desc`
 * @returns {Sortable}
 */
Sortable.prototype.sort = function (columnName, direction) {
  var self = this

  // Defaults
  columnName = columnName || 'original-order'
  direction = direction || 'asc'

  // Toggle sort direction
  if (self.sortedColumn === columnName || direction === 'toggle') {
    direction = (self.sortedDirection === 'asc' ? 'desc' : 'asc')
  }

  // Don't need to sort
  if (columnName === self.sortedColumn && direction === self.sortedDirection) return self

  // Get the new column to sort and compare
  var $sortColumn = self.$columns.filter('[data-sortable-by="' + columnName + '"]')

  // @debug console.log('Sortable.sort:', columnName, direction)

  // Trigger event before sort
  self.$elem.trigger('sortable:sort:before', [columnName, direction])

  // Do the sort in the UI
  self.$content.children().detach().sort(function (a, b) {
    return self.settings.onsortcompare.apply(self, [a, b, columnName, direction])
  }).appendTo(self.$content)

  // Update Sortable properties
  self.sortedColumn = columnName
  self.sortedDirection = direction

  // Update UI
  self.$columns.removeClass('ui-sortable-current ui-sortable-direction-asc ui-sortable-direction-desc')
  $sortColumn.addClass('ui-sortable-current ui-sortable-direction-' + direction)

  // Trigger event after the sort
  self.$elem.trigger('sortable:sort:after', [columnName, direction])

  return self
}

/*
 * Generic sorting comparison function (converts to floats and makes comparisons)
 * Uses the $.sort() method which compares 2 elements
 *
 * @method sortCompare
 * @param {Mixed} a The first item to compare
 * @param {Mixed} b The second item to compare
 * @param {String} columnName The name of the column to sort by
 * @param {String} direction The direction to sort (default is `asc`)
 * @returns {Number} Represents comparison: 0 | 1 | -1
 */
Sortable.prototype.sortCompare = function (a, b, columnName, direction) {
  if (!columnName) return 0

  // Get the values to compare based on the columnName
  a = convertToPrimitive($(a).attr('data-sortable-' + columnName))
  b = convertToPrimitive($(b).attr('data-sortable-' + columnName))
  output = 0

  // Get the direction to sort (default is `asc`)
  direction = direction || 'asc'
  switch (direction) {
    case 'asc':
    case 'ascending':
    case 1:
      if (a > b) {
        output = 1
      } else if (a < b) {
        output = -1
      }
      break

    case 'desc':
    case 'descending':
    case -1:
      if (a < b) {
        output = 1
      } else if (a > b) {
        output = -1
      }
      break
  }

  // @debug console.log('sortCompare', a, b, columnName, direction, output)
  return output
}

/*
 * Reset the contents' order back to the original
 *
 * @method reset
 * @returns {Sortable}
 */
Sortable.prototype.reset = function () {
  var self = this

  if (self.settings.saveoriginalorder) {
    self.$elem.trigger('sortable:reset')
    return self.sort('original-order', 'asc')
  }

  return self
}

/*
 * Destroy the Sortable instance
 *
 * @method destroy
 * @returns {Void}
 */
Sortable.prototype.destroy = function () {
  var self = this

  self.$elem[0].Sortable = false
  delete self
}

/*
 * jQuery API
 */
$.fn.uiSortable = function (op) {
  // Fire a command to the Sortable object, e.g. $('[data-sortable]').uiSortable('sort', 'id', 'asc')
  if (typeof op === 'string' && /^sort|reset$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.Sortable && typeof elem.Sortable[op] === 'function') {
        elem.Sortable[op].apply(elem.Sortable, args)
      }
    })

  // Set up a new Sortable instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.Sortable) {
        new Sortable(elem, op)
      }
    })
  }
}

// Auto-assign functionality to components with [data-sortable] attribute
$(document).on('ready', function () {
  $('[data-sortable]').uiSortable()
})

module.exports = Sortable
