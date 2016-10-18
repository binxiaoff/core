/*
 * Unilend JS Templating
 * Mostly basic string replacement to allow templating
 * It now integrates with Dictionary! You can now pass a Dictionary instance as a props item
 */

/*

  How to use
  ===========

  Templating is essentially a search/replace operation on a string. It takes a string, looks for specifically formatted keywords and replaces those keywords with values that match a given object's property name (case sensitive):

  ```
    Templating.replace('Here is an example string with a {{ prop }}', { prop: 'New Value!' })
  ```
    Outputs =>  "Here is an example string with a New Value!"


  If no keyword matches are found, keywords are wiped from the return string:

  ```
    Templating.replace('I have nothing to {{ say }}.', { notFound: 'This won\'t be visible' })
  ```
    Outputs => "I have nothing to ."


  You can insert new keywords into replacement values and they will get replaced too:

  ```
    Templating.replace('Here is an example with {{ nesting }}', { nesting: '{{ anotherProp }}', anotherProp: 'A New Value!' })
  ```
    Outputs => "Here is an example with A New Value!"


  You can have multiple objects to use for replacement value reference (order of objects affects replacement order) by setting the second parameter to an array of objects:

  ```
    Templating.replace('Here is an example with {{ multiple }} {{ objects }}', [{ multiple: 'Many' }, { objects: 'Replacement Values!' }])
  ```
    Outputs => "Here is an example with Many Replacement Values!"


  You can also filter (or transform) the replacement value by piping extra keywords after the object's property keyword:

  ```
    Templating.replace('I might be sanitised {{ example|html }}', { example: "& you'll see the difference" })
  ```
    Outputs => "I might be sanitised &amp; you&#39;ll see the difference"


  Order of filters will affect the final transformed value:

  ```
    Templating.replace('Show me how to write an ampersand with HTML: <code>{{ example|html|html }}</code>', { example: '&' })
  ```
    Outputs => "Show me how to write an ampersand with HTML: <code>&amp;amp;</code>"


  By default keywords are marked with double curly braces {{ likeTwigAndHandlebars }}. If you want something different, you can customise via the third param which is an object containing extra options:

  ```
    Templating.replace('I have a different kind of __EXAMPLE|html__', { EXAMPLE: 'New & Shiny Value!' }, { keywordPrefix: '__', keywordSuffix: '__'})
  ```
    Outputs => "I have a different kind of New &amp; Shiny Value!"

 */

var Utility = require('Utility')
var Dictionary = require('Dictionary')

// Builds RegExp to find keywords
function buildRegExp (options) {
  // Default options
  options = $.extend({
    keywordPrefix: '{{',
    keywordSuffix: '}}'
  }, options)

  // Build keyword and propName RegExps and place in options object
  options.reKeywordMatch = new RegExp(Utility.reEscape(options.keywordPrefix) + '\\s*[a-z0-9_\\|\\-]+\\s*' + Utility.reEscape(options.keywordSuffix), 'gi')
  options.reKeywordPrefixSuffixMatch = new RegExp('^' + Utility.reEscape(options.keywordPrefix) + '\\s*|\\s*' + Utility.reEscape(options.keywordSuffix) + '$', 'g')
  options.reKeywordFiltersMatch = new RegExp('^' + Utility.reEscape(options.keywordPrefix) + '\\s*[a-z0-9_\\-]+\\|([a-z0-9_\\|\\-]+)\\s*' + Utility.reEscape(options.keywordSuffix) + '$', 'i')

  return options
}

// Replaces {String} input with the properties within the {Object} props
// e.g. replaceKeywordsWithValues('Hello {{ example }}', {example: 'World!'}) => "Hello World!"
// Keyword prefix and suffix can be changed within options {Object}
function replaceKeywordsWithValues (input, props, options) {
  output = input
  if (typeof output === 'undefined') return ''

  // No options given, so build with default values
  if (!options) {
    options = buildRegExp(options)
  }

  // @debug
  // console.log('replaceKeywordsWithValues', input, props, options)

  // Search for keywords
  var matches = output.match(options.reKeywordMatch)

  // @debug
  // console.log('replaceKeywordsWithValues: matches', matches)

  if (matches && matches.length > 0) {
    for (var i = 0; i < matches.length; i++) {
      var propName = matches[i].replace(options.reKeywordPrefixSuffixMatch, '')
      var propValue = ''
      var propFilters = matches[i].replace(options.reKeywordFiltersMatch, '$1')

      // Remove the propFilters from the propName, if any were detected
      if (/\|/.test(propName)) {
        propName = propName.replace(/\|.*/, '')
      }

      // @debug
      // console.log('replaceKeywordsWithValues: matches[' + i + ']', matches[i], propName, propFilters)

      // Is prop a Dictionary object? If so get the value as per the matched propName
      if (props instanceof Dictionary) {
        propValue = props.__key(propName)
      } else {
        propValue = (typeof props === 'object' && props.hasOwnProperty(propName) ? props[propName] : matches[i]) + '' // Ensure it's a string, baby~
      }

      // Only replace if need to
      if (propValue !== matches[i]) {
        // @debug
        // console.log('Templating', matches[i], propName, propFilters, propValue)

        // Check if props value has more keywords to match. If so, add to matches
        if (propValue) {
          // Look for extra keywords
          // @note If you're replacing a filtered keyword (e.g. `{{ test|filter_name }}`) with a value that has more keywords, it won't get filtered!
          var propValueKeywordMatches = propValue.match(options.reKeywordMatch)
          if (propValueKeywordMatches && propValueKeywordMatches.length > 0) {
            // @debug
            // console.log('Found new keywords in propValue', propValueKeywordMatches)
            matches = matches.concat(propValueKeywordMatches)

          // Only apply filters if there are no further keyword matches
          } else {
            if (propFilters) {
              propValue = filterKeywordValue(propValue, propFilters)
            }
          }
        }

        output = output.replace(new RegExp(Utility.reEscape(matches[i]), 'g'), propValue)
      }
    }
  }

  return output
}

// Filter the keyword value by a filter type
// @method filterKeywordValue
// @param {String} keywordValue
// @param {String} filters
// @returns {String}
function filterKeywordValue (keywordValue, filters) {
  if (!filters) {
    return keywordValue
  }

  // Multiple filters
  if (typeof filters === 'string') {
    if (/[, \|]+/.test(filters)) {
      filters = (filters + '').split(/[, \|]+/)
    } else {
      filters = [filters]
    }
  }

  // @debug
  // console.log('filterKeywordValue', filters, keywordValue)

  // Process filters
  for (var i = 0; i < filters.length; i++) {
    switch (filters[i].toLowerCase()) {
      // Replace special characters with special character equivalents
      case 'attr':
        if (/['"]/.test(keywordValue)) {
          var charMap = [['"', '\x22'], ["'", '\x27']]
          for (var j = 0; j < charMap.length; j++) {
            keywordValue = keywordValue.replace(new RegExp(Utility.reEscape(charMap[j][0]), 'g'), charMap[j][1])
          }
        }
        break

      // Replace special characters with HTML character equivalents
      case 'html':
        if (/['"&]/.test(keywordValue)) {
          var charMap = [['&', '&amp;'], ['"', '&quot;'], ["'", "&#39;"]]
          for (var j = 0; j < charMap.length; j++) {
            keywordValue = keywordValue.replace(new RegExp(Utility.reEscape(charMap[j][0]), 'g'), charMap[j][1])
          }
        }
        break

      // JSON encode (stringify)
      case 'json':
      case 'json_encode':
        if (typeof keywordValue === 'object') {
          keywordValue = JSON.stringify(keywordValue)
        }
        break

      // Convert spaces to non-breaking spaces
      case 'nbsp':
        keywordValue = keywordValue.replace(/ /g, '&nbsp;')
        break

      //
      // Add any extra filters here
      //
    }
  }

  return keywordValue
}

var Templating = {
  // Replaces instances of {{ propName }} in the template string with the corresponding values in the props object
  // @method replace
  // @param {String} input The string to replace keywords with
  // @param {Mixed} props An {Object} (or {Array} of {Objects}) to replace matching keywords within the input string with its values
  //                      Values in the props are usually strings, --but they can also be {Function}s which return strings--
  //                      Or even a {Dictionary} instance
  // @returns {String}
  replace: function (input, props, options) {
    var self = this
    var output = input

    // Default options (so to build the regexp to search by)
    options = buildRegExp(options)

    // Support processing props in sequential order with multiple objects
    if (!(props instanceof Array)) props = [props]
    for (var i = 0; i < props.length; i++) {
      output = replaceKeywordsWithValues(output, props[i], options)

      // @debug
      // console.log('Templating.replace: replaced keywords from ' + (i+1) + '/' + props.length + ' prop groups', output, options)
    }

    // Clean any unmatched props
    output = output.replace(options.reKeywordMatch, '')

    // @debug
    // console.log('Templating.replace: replaced keywords', input, props, options, output)

    // Return the final string
    return output
  },

  // Filters a string based on the filterKeywordValue function
  // @method filter
  // @param {String} input
  // @param {Mixed} filters Can be a {String} or an {Array}
  // @returns {String}
  filter: function (input, filters) {
    return filterKeywordValues(input, filters)
  }
}

module.exports = Templating
