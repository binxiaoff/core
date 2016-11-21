/*
 * Unilend __ComponentName__
 * When making a new component, use this as a base
 *
 * @componentName   __ComponentName__
 * @className       ui-__componentname__
 * @attrPrefix      data-__componentname__
 * @langName        __COMPONENTNAME__
 */

// Lib Dependencies
var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')

// @todo Add any other lib dependencies...

/*
 * __ComponentName__
 * @class
 */
var __ComponentName__ = function (elem, options) {
  var self = this
  self.$elem = $(elem)

  // Error: element not specified or already has an instance of this component
  if (self.$elem.length === 0 || elem.hasOwnProperty('__ComponentName__')) return false

  // Component's instance settings
  self.settings = $.extend({
    // @todo Put in settings properties and their defaults here
    // propName: 'example string value'
  }, ElementAttrsObject(elem, {
    // @todo Put in specific settings to override with attribute values
    // @note If element has attribute `[data-__componentname__-propname]` then the value of that attribute will be applied to the object above
    // propName: 'data-__componentname__-propname'
  }), options)

  // Assign class to show component behaviours have been applied (required)
  self.$elem.addClass('ui-__componentname__')

  // Assign instance of class to the element (required)
  self.$elem[0].__ComponentName__ = self

  return self
}

/*
 * Prototype properties and methods (shared between all class instances)
 */

/*
 * Destroy the __ComponentName__ instance
 *
 * @method destroy
 * @returns {Void}
 */
__ComponentName__.prototype.destroy = function () {
  var self = this

  // Do other necessary teardown things here, like destroying other related plugin instances, etc. Most often used to reduce memory leak

  self.$elem[0].__ComponentName__ = null
  delete self
}

/*
 * jQuery Plugin
 */
$.fn.ui__ComponentName__ = function (op) {
  // Fire a command to the __ComponentName__ object, e.g. $('[data-__componentname__]').ui__ComponentName__('publicMethod', {..})
  // @todo add in list of public methods that $.fn.ui__ComponentName__ can reference
  if (typeof op === 'string' && /^(publicMethod|anotherPublicMethod|destroy)$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.hasOwnProperty('__ComponentName__') && typeof elem.__ComponentName__[op] === 'function') {
        elem.__ComponentName__[op].apply(elem.__ComponentName__, args)
      }
    })

    // Set up a new __ComponentName__ instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.hasOwnProperty('__ComponentName__')) {
        new __ComponentName__(elem, op)
      }
    })
  }
}

/*
 * jQuery Events
 */
$(document)
// Auto-init `[data-__componentname__]` elements through declarative instantiation
.on('ready UI:visible', function (event) {
  $(event.target).find('[data-__componentname__]').not('.ui-__componentname__').ui__ComponentName__()
})


// @todo Add any other component UI events to bind behaviours to
// .on('click', '[data-__componentname__]', function (event) {
//   $(this).ui__ComponentName__('publicMethod')
// })

module.exports = __ComponentName__
