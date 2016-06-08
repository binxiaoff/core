/*
 * Unilend Sortable
 * Re-order elements within another element depending on a value
 */

// Dependencies
var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')

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
  self.$elem = $(elem).first()

  // Error: no element
  if (self.$elem.length === 0) return

  // Settings
  self.settings = $.extend({
    // The columns to sort by
    columns: '[data-sortable-by]',

    // The content to sort
    content: '[data-sortable-content]',

    // Save the original order
    saveOriginalOrder: true,

    // Sorting function
    onsortcompare: self.sortCompare,
  },
  // -- Override with options set on the element
  ElementAttrsObject(elem, {
    columns: 'data-sortable-columns',
    content: 'data-sortable-content',
    saveOriginalOrder: 'data-sortable-saveoriginalorder'
  }),
  // -- Override with options set via JS
  options)

  // Properties
  self.$columns = undefined
  self.columnNames = []
  self.$content = undefined
  self.sortedColumn = false
  self.sortedDirection = false

  /*
   * Elements
   */
  // Get/set the columns
  // -- Search whole DOM if specified ID or class selector
  if (typeof self.settings.columns === 'string') {
    if (/^[#.]/.test(self.settings.columns)) {
      self.$columns = $(self.settings.columns)

    // -- Search within element
    } else {
      self.$columns = self.$elem.find(self.settings.columns)
    }

  // Assume already {HTMLElement} or {jQueryObject}
  } else if (self.settings.columns) {
    self.$columns = $(self.settings.columns)
  }

  // -- Not found, look for [data-sortable-columns] element within main element
  if (self.$columns.length === 0) {
    self.$columns = self.$elem.find('[data-sortable-columns]')
  }

  // -- Again, not found. Error!
  if (self.$columns.length === 0) {
    throw new Error('Sortable.setup error: no columns defined. Make sure you set each sortable column with the HTML attribute `[data-sortable-by="columnName"]`')
    return
  }

  // Get/set the column names
  self.$columns.each(function (i, elem) {
    var columnName = $(elem).attr('data-sortable-by')
    if (columnName) self.columnNames.push(columnName)
  })

  // Get/set the content
  // -- Search whole DOM if specified ID or class selector
  if (typeof self.settings.content === 'string') {
    if (/^[#.]/.test(self.settings.content)) {
      self.$content = $(self.settings.content)

    // -- Search within element
    } else {
      self.$content = self.$elem.find(self.settings.content)
    }

  // Assume already {HTMLElement} or {jQueryObject}
  } else if (self.settings.columns) {
    self.$content = $(self.settings.content)
  }

  // -- Not found, look for [data-sortable-content] element within main element
  if (self.$content.length === 0) {
    self.$content = self.$elem.find('[data-sortable-content]')
  }

  // -- Again, not found. Error!
  if (self.$content.length === 0) {
    throw new Error('Sortable.setup error: no content defined. Make sure you set the sortable content with the HTML attribute `[data-sortable-content]`')
  }

  // Set single content element
  self.$content = self.$content.first()

  // Save the original order
  if (self.settings.saveOriginalOrder) {
    self.$content.children().each(function (i, item) {
      $(item).attr('data-sortable-originalorder', i)
    })
  }

  /*
   * UI
   */
  self.$elem.addClass('ui-sortable')

  // Attach instance to element
  self.$elem[0].Sortable = self

  // @trigger [data-sortable] `Sortable:ready`
  self.$elem.trigger('Sortable:ready')

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

  // @trigger .ui-sortable `Sortable:sort:before`
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
  self.$elem
    .removeClass('ui-sortable-direction-asc ui-sortable-direction-desc')
    .addClass('ui-sortable-direction-' + direction)

  // @trigger .ui-sortable `Sortable:sort:after`
  self.$elem.trigger('Sortable:sort:after', [columnName, direction])

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
  a = Utility.convertToPrimitive($(a).attr('data-sortable-' + columnName))
  b = Utility.convertToPrimitive($(b).attr('data-sortable-' + columnName))
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

  if (self.settings.saveOriginalOrder) {
    // @trigger .ui-sortable `Sortable:reset`
    self.$elem.trigger('Sortable:reset')
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
  if (typeof op === 'string' && /^(sort|reset|destroy)$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.hasOwnProperty('Sortable') && typeof elem.Sortable[op] === 'function') {
        elem.Sortable[op].apply(elem.Sortable, args)
      }
    })

  // Set up a new Sortable instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.hasOwnProperty('Sortable')) {
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
