/*
 * Element Attributes as Object
 *
 * Get a range of element attributes as an object
 */

var $ = require('jquery')
var Utility = require('Utility')

/*
 * @method ElementAttrsObject
 * @param {Mixed} elem Can be {String} selector, {HTMLElement} or {jQueryObject}
 * @param {Array} attrs An array of the possible attributes to retrieve from the element
 * @returns {Object}
 */
var ElementAttrsObject = function (elem, attrs) {
  var $elem = $(elem)
  var output = {}
  var attrValue
  var i

  // No element/attributes
  if ($elem.length === 0 || (typeof attrs !== 'object' && !(attrs instanceof Array))) return {}

  // Process attributes via array
  if (attrs instanceof Array) {
    for (i = 0; i < attrs.length; i++) {
      attrValue = Utility.checkElemAttrForValue(elem, attrs[i])
      if (typeof attrValue !== 'undefined') {
        output[attrs[i]] = Utility.convertToPrimitive(attrValue)
      }
    }

  // Process attributes via object key-value
  } else if (typeof attrs === 'object') {
    for (i in attrs) {
      attrValue = Utility.checkElemAttrForValue(elem, attrs[i])
      if (typeof attrValue !== 'undefined') {
        output[i] = Utility.convertToPrimitive(attrValue)
      }
    }
  }

  // @debug
  // console.log('ElementAttrsObject', elem, attrs, output)

  return output
}

module.exports = ElementAttrsObject
