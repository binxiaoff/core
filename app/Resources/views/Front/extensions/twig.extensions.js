/*
 * Custom extensions for compiling Twig.js templates
 *
 * This file is loaded by the gulpfile.js when you start running a build.
 * If at any point you change this file, you will then need to restart any
 * currently running builds for the changes to take effect.
 *
 */

// Twig function/filter format usage in templates
// Function: __num(1000000.12, 0, 'en') => '10,000,00'
// Filter: 10000000.12|__num(0, 'en') => '10,000,00'

// Dependencies
var deepAssign = require('deep-assign')
var sprintf = require('sprintf-js').sprintf

// See gulpfile.js for the Dictionary implementation
var __ = GLOBAL.Unilend.__

var debug = GLOBAL.Unilend.config.verbose || false
var useSVG = GLOBAL.Unilend.config.useSVG || false

// Twig data
var twigData = GLOBAL.Unilend.config.twig.data

// Twig routes
var twigRoutes = GLOBAL.Unilend.config.twig.routes || {}

// Convert spaces to &nbsp;
var nbsp = function (input) {
  return (input + '').replace(/ /g, '&nbsp;')
}

// Convert value to string
var convertToString = function (input) {
  return (input + '')
}

// Convert value to int
var convertToInt = function (input) {
  return parseInt(input, 10)
}

// Convert value to float
var convertToFloat = function (input) {
  return parseFloat(input)
}

// Get URL relative to site.base
var urlSite = function (input) {
  return twigData.site.base + (input || '')
}

// Get URL relative to site.ajax
var urlAjax = function (input) {
  return twigData.site.ajax + (input || '')
}

// Get URL relative to site.assets.base
var urlAssets = function (input) {
  return twigData.site.assets.base + (input || '')
}

// Get URL relative to site.assets.css
var urlCSS = function (input) {
  return twigData.site.assets.css + (input || '')
}

// Get URL relative to site.assets.js
var urlJS = function (input) {
  return twigData.site.assets.js + (input || '')
}

// Get URL relative to site.assets.media
var urlMedia = function (input) {
  return twigData.site.assets.media + (input || '')
}

// Encode an object into a form which can be nicely displayed (also removes _keys property if one exists)
var jsonPrettyPrint = function (input, spaces) {
  if (input.hasOwnProperty('_keys')) delete input._keys
  return JSON.stringify(input, null, spaces || 2)
}

// Get a route to a location on the website
// @note Could be refactored to split out specific operations (if REAAAALLY necessary, i.e. when use-case required)
var getRouteUrl = function (input, isInvalidRoute) {
  // Error
  if (typeof input === 'undefined') {
    if (debug) console.log('twig.extensions.getRouteUrl: Invalid input given (empty)')
    return '#invalid-route_no-input-given'
  }

  // Ignore any inputs with 'http' at start (it's already a URL)
  if (/^(#|http)/i.test(input)) {
    if (debug) console.log('twig.extensions.getRouteUrl: Input already URL (' + input + ')')
    return input
  }

  // Extract params from the route
  var params = []
  if (input.length > 1 && /\//.test(input)) {
    params = input.split('/')
  } else {
    params = [input]
  }

  // console.log('getRouteUrl', {
  //   input: input,
  //   params: params
  // })

  // Check the routes for any matches with the input params
  var matchedRoute = {
    pattern: false,
    params: [],
    paramsKeys: {},
    re: false,
    url: false
  }
  for (var i in twigRoutes) {
    // Basic string check
    if (i === input) {
      matchedRoute.pattern = i
      matchedRoute.url = twigRoutes[i]
      break
    }

    // Detailed check
    // -- Split route into RE pattern and params
    if (i.length > 1 && /\//.test(i)) {
      var routeRE = []
      var routeParams = i.split('/')
      var routeParamsKeys = []

      // Extract any param names and build the RE to check if input matches with this route
      for (var k in routeParams) {
        if (/^:/.test(routeParams[k])) {
          routeRE.push('([^\\\/]+)')

          // Set the paramKey name and index of the param value in the route
          routeParamsKeys[routeParams[k].replace(':', '')] = parseInt(k, 10)
        } else {
          routeRE.push(routeParams[k])
        }
      }
      routeRE = new RegExp('^' + routeRE.join('\\\/') +'$', 'i')

      // Match made with route
      if (routeRE.test(input)) {
        matchedRoute = {
          pattern: i,
          params: routeParams,
          paramsKeys: routeParamsKeys,
          re: routeRE,
          url: twigRoutes[i]
        }

        // @debug console.log('matchedRoute', matchedRoute, input)
        break
      }
    }
  }

  // A matched route was found and a URL was given, so use it!
  if (matchedRoute.url) {
    // Get any extra keywords and values from the input and pass to the route
    var keywords = {}
    if (params.length > 0) {
      for (var l in matchedRoute.paramsKeys) {
        keywords[l] = params[matchedRoute.paramsKeys[l]]
      }
    }

    // @debug
    // console.log('matchedRoute', {
    //   input: input,
    //   params: params,
    //   keywords: keywords,
    //   route: matchedRoute
    // })
    // if (debug && /^error\/invalidroute/i.test(input)) {
    //   console.log('matchedRoute', {
    //     input: input,
    //     params: params,
    //     keywords: keywords,
    //     route: matchedRoute
    //   })
    // }
    return replaceKeywords(matchedRoute.url, keywords)

  // Invalid route
  } else {
    if (debug) console.log('Twig.extentions.getRouteUrl: invalid route given (' + input + ')')
    if (!isInvalidRoute) {
      // Redirect to the error-invalidroute page which displays information about the invalid route
      return getRouteUrl('error/invalidroute/' + encodeURIComponent(input), true)
    } else {
      return '#invalid-route_' + input
    }
  }
}

// Use SVG item from SVG symbol set (see {build}/media/icons.svg)
// (SVG symbol set is loaded in via ./src/twig/layouts/_layout.html.twig)
// ID corresponds to {foldername-filename}
// e.g. SVG hosted in media/svg/example-folder/filename.svg
//      will translate to:
//      svgImage('#example-folder-filename', 'Example')
// You can also specify multiple IDs (or URLs) to layer SVG symbols
var svgImage = function (id, title, width, height, sizing) {
  // Default URL
  var url = urlMedia('svg/icons.svg')
  var svgHeaders = ' version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve"'
  var uses = []
  var usesIds = []

  // Supported sizing sizes, using preserveAspectRatio
  var sizes = {
    'none': '',
    'stretch': 'none',
    'cover': 'xMidYMid slice',
    'contain': 'xMidYMid meet'
  }

  // Fallback to 'contain' aspect ratio if invalid option given
  if (sizing && !sizes.hasOwnProperty(sizing)) sizing = 'contain'

  // Specify multiple IDs or items to stack within the SVG image
  if (!(id instanceof Array)) id = [id]
  for (var i = 0; i < id.length; i++) {
    var useId = ''

    // Reference to ID
    if (/^#/.test(id[i])) {
      useId = id[i].replace('#', '')
      usesIds.push(useId)
      uses.push('<use xlink:href="' + url + id[i] + '" class="svg-file-' + useId + '"/>')

    // Reference to other SVG file
    } else {
      if (/#/.test(id[i])) {
        useId = id[i].split('#').pop()
        usesIds.push(useId)
      }
      uses.push('<use xlink:href="' + id[i] + '" ' + (useId ? 'class="svg-file-' + useId + '"' : '') + '/>')
    }
  }

  // List of IDs
  if (usesIds.length > 0) {
    usesIds = ' svg-file-' + usesIds.join(' svg-file-')
  } else {
    usesIds = ''
  }

  // Set attributes
  var titleAttr = (title ? ' title="' + title + '"' : '')
  var widthAttr = (width ? ' width="' + width + '"' : '' )
  var heightAttr = (height ? ' height="' + height + '"' : '' )
  var preserveAspectRatioAttr = (sizing ? ' preserveAspectRatio="' + sizes[sizing] + '"' : '')
  var svgHtml = '<svg role="img"' + titleAttr + widthAttr + heightAttr + preserveAspectRatioAttr + ' class="svg-icon' + usesIds + '"' + svgHeaders + '>' + uses.join('') + '</svg>'

  // @debug
  // if (debug) {
  //   console.log('svgImage', {
  //     url: url,
  //     id: id,
  //     title: title,
  //     width: width,
  //     height: height,
  //     preserveAspectRatio: sizes[sizing],
  //     svgHeaders: svgHeaders,
  //     svgHtml: svgHtml
  //   })
  // }

  // Don't display SVG if it has been disabled
  if (!useSVG) return '<span class="icon fa-question-circle" title="SVG disabled"><span class="sr-only">SVG disabled</span></span>'

  // Output SVG code
  return svgHtml
}

// Use SVG item from SVG symbol set (see {build}/media/icons.svg)
// (SVG symbol set is loaded in via ./src/twig/layouts/_layout.html.twig)
// Outputs the URL link to an inline SVG file with ID refering to specific symbol
var svgUrl = function (id, width, height, sizing) {
  return 'url(\'data:image/svg+xml,' + encodeURIComponent(svgImage(id, false, width, height, sizing)) + '\')';
}

// Replace input's %keywords% strings with the values that match property names in the keywords object
var replaceKeywords = function (input, keywords) {
  var matches = input.match(/\%([^\%]+)\%/g)

  // Default keywords
  keywords = deepAssign({
    siteurl: urlSite(),
    siteurlajax: urlAjax(),
    siteurlassets: urlAssets(),
    siteurlmedia: urlMedia(),
    siteurljs: urlJS(),
    siteurlcss: urlCSS()
  }, keywords)

  // Replace keywords with object's property values
  if (matches && matches.length > 0) {
    for (var i in matches) {
      var propName = matches[i].replace(/\%/g, '')
      if (keywords.hasOwnProperty(propName)) {
        input = input.replace(new RegExp(matches[i], 'gi'), keywords[propName])
      }
    }
  }

  // Clean any unmatched keywords
  input = input.replace(/\%[^%]+\%/g, '')

  return input
}

// Get a date object from a string
var getDate = function (input) {
  if (input instanceof Date) return input

  // Parse date from string
  if (typeof input === 'string' && input !== 'now') {
    return new Date(input)
  }

  // Now
  return new Date()
}

// Get the relative time elapsed from an input
var timeDiff = function (input, endTime) {
  // Reference
  var secondsAsUnits = [{
    min: 0,
    max: 5,
    single: __.__('now', 'timeUnitNow'),
    plural: __.__('now', 'timeUnitNow')
  },{
    min: 1,
    max: 60,
    single: '%d ' + __.__('second', 'timeUnitSecond'),
    plural: '%d ' + __.__('seconds', 'timeUnitSeconds')
  },{
    min: 60,
    max: 3600,
    single: '%d ' + __.__('minute', 'timeUnitMinute'),
    plural: '%d ' + __.__('minutes', 'timeUnitMinutes')
  },{
    min: 3600,
    max: 86400,
    single: '%d ' + __.__('hour', 'timeUnitHour'),
    plural: '%d ' + __.__('hours', 'timeUnitHours')
  },{
    min: 86400,
    max: 604800,
    single: '%d ' + __.__('day', 'timeUnitDay'),
    plural: '%d ' + __.__('days', 'timeUnitDays')
  },{
    min: 604800,
    max: 2419200,
    single: '%d ' + __.__('week', 'timeUnitWeek'),
    plural: '%d ' + __.__('weeks', 'timeUnitWeeks')
  },{
    min: 2628000,
    max: 31536000,
    single: '%d ' + __.__('month', 'timeUnitMonth'),
    plural: '%d ' + __.__('months', 'timeUnitMonths'),
  },{
    min: 31536000,
    max: -1,
    single: '%d ' + __.__('year', 'timeUnitYear'),
    plural: '%d ' + __.__('years', 'timeUnitYears')
  }]

  // console.log('time_diff', input, endTime)

  // Dates
  var startDate = getDate(input)
  var endDate = getDate(endTime)
  var diffSeconds = ((endDate.getTime() - startDate.getTime()) / 1000)

  // console.log('time_diff', startDate, endDate, diffSeconds)

  // Output
  var outputDiff = ''
  var output = ''

  for (var i = 0; i < secondsAsUnits.length; i++) {
    var u = secondsAsUnits[i]
    if (Math.abs(diffSeconds) >= u.min && (Math.abs(diffSeconds) < u.max || u.max === -1)) {
      // Show the difference via number
      if (u.min > 0) {
        outputDiff = Math.round(Math.abs(diffSeconds) / u.min)
        output = sprintf((outputDiff === 1 ? u.single : u.plural), outputDiff)

      // No minimum amount given, so assume no need to put number within unit output (reference only single)
      } else {
        output = u.single
      }

      break
    }
  }

  return output
}

/*
 * Twig integration
 */
var TwigExtensions = function (Twig) {
  // Replace spaces with non-breaking spaces
  // @usage {{ nbsp('    ') }} => '&nbsp;&nbsp;&nbsp;&nbsp;'
  Twig.exports.extendFunction('nbsp', nbsp)

  // Replace spaces with non-breaking spaces
  // @usage {% '15 000€'|nbsp }} => '15&nbsp000€'
  Twig.exports.extendFilter('nbsp', nbsp)

  // @usage {{ __(fallbackText, textKey, lang) }}
  Twig.exports.extendFunction('__', function (fallbackText, textKey, lang) {
    return __.__(fallbackText, textKey, lang)
  })
  // @usage {{ 'Fallback text'|__(textKey, lang) }}
  Twig.exports.extendFilter('__', function (fallbackText, params) {
    // Insert input at start of params to apply to function
    params = params || []
    params.unshift(input)
    return __.__.apply(__, params)
  })

  // Format Number (using Dictionary.formatNumber())
  // @usage {{ formatnumber(input, limitDecimal, isPrice, lang) }}
  Twig.exports.extendFunction('formatnumber', function (input, limitDecimal, isPrice, lang) {
    return __.formatNumber(input, limitDecimal, isPrice, lang)
  })
  // @usage {{ var|formatnumber(limitDecimal, isPrice, lang) }}
  Twig.exports.extendFilter('formatnumber', function (input, params) {
    // Insert input at start of params to apply to function
    params = params || []
    params.unshift(input)
    return __.formatNumber.apply(__, params)
  })

  // Localized Number (using Dictionary.localizedNumber())
  // @usage {{ __num(input, limitDecimal, lang) }}
  Twig.exports.extendFunction('__num', function (input, limitDecimal, lang) {
    return __.localizedNumber(input, limitDecimal, lang)
  })
  // @usage {{ 15000.1234|__num(limitDecimal, lang) }}
  Twig.exports.extendFilter('__num', function (input, params) {
    // Insert input at start of params to apply to function
    params = params || []
    params.unshift(input)
    return __.localizedNumber.apply(__, params)
  })

  // Localized Price (using Dictionary.localizedPrice())
  // @usage {{ __price(input, limitDecimal, lang) }}
  Twig.exports.extendFunction('__price', function (input, limitDecimal, lang) {
    return __.localizedPrice(input, limitDecimal, lang)
  })
  // @usage {{ 15000.123|__price(limitDecimal, lang) }}
  Twig.exports.extendFilter('__price', function (input, params) {
    // Insert input at start of params to apply to function
    params = params || []
    params.unshift(input)
    return __.localizedPrice.apply(__, params)
  })

  // Convert value to string
  // @usage {{ tostring(10.7)|split('.')|join('-') }} => 10-7
  Twig.exports.extendFunction('tostring', function (input) {
    return convertToString(input)
  })
  // @usage {{ 10.7|tostring|split('.')|join('-') }} => 10-7
  Twig.exports.extendFilter('tostring', function (input) {
    return convertToString(input)
  })

  // Convert value to int
  // @usage {{ toint('10.7') }} => 10
  Twig.exports.extendFunction('toint', function (input) {
    return convertToString(input)
  })
  // @usage {{ '10.7'|toint }} => 10
  Twig.exports.extendFilter('toint', function (input) {
    return convertToString(input)
  })

  // Convert value to float
  // @usage {{ tofloat('10.7') }} => 10.7
  Twig.exports.extendFunction('tofloat', function (input) {
    return convertToFloat(input)
  })
  // @usage {{ '10.7'|tofloat }} => 10.7
  Twig.exports.extendFilter('tofloat', function (input) {
    return convertToFloat(input)
  })

  // Urls
  Twig.exports.extendFunction('siteurl', function (input) {
    return urlSite(input)
  })
  Twig.exports.extendFunction('siteurlajax', function (input) {
    return urlAjax(input)
  })
  Twig.exports.extendFunction('siteurlassets', function (input) {
    return urlAssets(input)
  })
  Twig.exports.extendFunction('siteurlcss', function (input) {
    return urlCSS(input)
  })
  Twig.exports.extendFunction('siteurljs', function (input) {
    return urlJS(input)
  })
  Twig.exports.extendFunction('siteurlmedia', function (input) {
    return urlMedia(input)
  })

  // Route
  Twig.exports.extendFunction('route', function (input) {
    return getRouteUrl(input)
  })

  // SVG
  Twig.exports.extendFunction('svgimage', function (id, title, width, height, sizing) {
    return svgImage(id, title, width, height, sizing)
  })
  Twig.exports.extendFunction('svgurl', function (id, width, height, sizing) {
    return svgUrl(id, width, height, sizing)
  })

  // JSON Pretty Print
  Twig.exports.extendFunction('json_encode_prettyprint', function (input, spaces) {
    return jsonPrettyPrint(input, spaces)
  })
  Twig.exports.extendFilter('json_encode_prettyprint', function (input, params) {
    // Insert input at start of params to apply to function
    params = params || []
    params.unshift(input)
    return jsonPrettyPrint.apply(this, params)
  })

  // Global check for disabled SVG
  Twig.exports.extendFunction('caniuse_svg', function (input) {
    return useSVG
  })

  // Time difference
  Twig.exports.extendFunction('time_diff', function (input, endTime) {
    return timeDiff(input, endTime)
  })
  Twig.exports.extendFilter('time_diff', function (input, params) {
    // Insert input at start of params to apply to function
    params = params || []
    params.unshift(input)
    return timeDiff.apply(this, params)
  })
}

module.exports = TwigExtensions
