//
// Unilend JS Templating
// Very basic string replacement to allow templating
// It now integrates with Dictionary! You can now pass a Dictionary instance as a props item
//

var Utility = require('Utility')
var Dictionary = require('Dictionary')

// Replaces {String} input with the properties within the {Object} props
// e.g. replaceKeywordsWithValues('Hello {{ example }}', {example: 'World!'}) => "Hello World!"
function replaceKeywordsWithValues (input, props) {
  output = input
  if (typeof output === 'undefined') return ''

  // Search for keywords
  var matches = output.match(/\{\{\s*[a-z0-9_\-\|]+\s*\}\}/gi)
  if (matches && matches.length > 0) {
    for (var i = 0; i < matches.length; i++) {
      var propName = matches[i].replace(/^\{\{\s*|\s*\}\}$/g, '')
      var propValue = ''

      // Is prop a Dictionary object? If so get the value as per the matched propName
      if (props instanceof Dictionary) {
        propValue = props.__key(propName)
      } else {
        propValue = (props.hasOwnProperty(propName) ? props[propName] : matches[i])
      }

      // Only replace if need to
      if (propValue !== matches[i]) {
        // @debug
        // console.log('Templating', matches[i], propName, propValue)

        // Prop is function, so run it
        // @note make sure custom functions return their final value as a string (or something human-readable)
        if (typeof propValue === 'function') propValue = propValue.apply(props, [propName, propValue])

        output = output.replace(new RegExp(matches[i], 'g'), propValue)
      }
    }
  }

  return output
}

var Templating = {
  // Replaces instances of {{ propName }} in the template string with the corresponding values in the props object
  // @method replace
  // @param {String} input The string to replace keywords with
  // @param {Mixed} props An {Object} (or {Array} of {Objects}) to replace matching keywords within the input string with its values
  //                      Values in the props are usually strings, but they can also be {Function}s which return strings
  //                      Or even a {Dictionary} instance
  // @returns {String}
  replace: function (input, props) {
    var output = input

    // Support processing props in sequential order with multiple objects
    if (!(props instanceof Array)) props = [props]
    for (var i = 0; i < props.length; i++) {
      output = replaceKeywordsWithValues(output, props[i])
    }

    // Clean any unmatched props
    output = output.replace(/\{\{\s*[a-z0-9_\-\|]+\s*\}\}/gi, '')

    // Return the final string
    return output
  }
}

module.exports = Templating
