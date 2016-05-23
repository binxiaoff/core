//
// Unilend JS Templating
// Very basic string replacement to allow templating
//

function replaceKeywordsWithValues (input, props) {
  output = input
  if (typeof output === 'undefined') return ''

  // Search for keywords
  var matches = output.match(/\{\{\s*[a-z0-9_\-\|]+\s*\}\}/gi)
  if (matches.length > 0) {
    for (var i = 0; i < matches.length; i++) {
      var propName = matches[i].replace(/^\{\{\s*|\s*\}\}$/g, '')
      var propValue = (props.hasOwnProperty(propName) ? props[propName] : '')

      // @debug
      // console.log('Templating', matches[i], propName, propValue)

      // Prop is functions
      // @note make sure custom functions return their final value as a string (or something human-readable)
      if (typeof propValue === 'function') propValue = propValue.apply(props)

      output = output.replace(new RegExp(matches[i], 'g'), propValue)
    }
  }

  return output
}

var Templating = {
  // Replaces instances of {{ propName }} in the template string with the corresponding value in the props object
  replace: function (input, props) {
    var output = input

    // Support processing props in sequential order with multiple objects
    if (!(props instanceof Array)) props = [props]
    for (var i = 0; i < props.length; i++) {
      output = replaceKeywordsWithValues(output, props[i])
    }

    return output
  }
}

module.exports = Templating
