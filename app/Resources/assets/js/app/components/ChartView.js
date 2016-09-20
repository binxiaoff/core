/*
 * Unilend ChartView
 * Viewing of charts with whatever other tech to use (HighCharts, D3, etc.)
 */

// @todo different chart views: column, stacked column, line, pie, custom

// Lib Dependencies
var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var __ = require('__')
// var Promise = require('bluebird')

// Any other dependencies
var Highcharts = require('highcharts/highstock.src')
require('highcharts/modules/map')(Highcharts)
require('highcharts/modules/treemap')(Highcharts)

// Highcharts visuals
// -- Colors (taken from `src/sass/site/common/_colors.scss`)
var COLORS = {
  none:     'none',

  purple:   '#b20066',
  purpled:  '#9a1d5a',
  purpled2: '#6d1f4f',
  purpled3: '#4e203c',
  purplel:  '#FCD4EB',
  purplel2: '#FDF4FA',
  purplel3: '#ffd0eb',

  green:    '#85C085',
  greenl:   '#B8CEB9',
  teal:     '#2cbaa4',
  orange:   '#f47070',
  red:      '#DE2F4C',
  redl:     '#ECA2AF',
  redl2:    '#F5D5DB',

  black:    '#302a32',
  blackr:   '#000000', // Rich black
  grey:     '#787679',
  greyl:    '#9f9d9f',
  greyl2:   '#dedcdf',
  greyl3:   '#eceaed',
  greyl4:   '#f8f6f8',
  greyl5:   '#fdfcfe',
  white:    '#ffffff',

  facebook: '#3b5998',
  twitter:  '#55acee',

  // Notifications
  rejected: '#f8524e',
  accepted: '#2cbaa4',
  account:  '#ffa210',

  // Levels
  level1:   '#2cbaa4',
  level2:   '#4378c6',
  level3:   '#f78e61',
  level4:   '#f55150',
  level5:   '#1e4155',

  // Charts
  chart1:   '#428890',
  chart2:   '#5FC4D0',
  chart3:   '#F76965',
  chart4:   '#F2980C',
  chart5:   '#FFCA2C',
  chart6:   '#1B88DB'
}

// @returns {Array}
function getRgb (color) {
  var hex = color.trim().replace('#', '0x')
  return [hex >> 16, hex >> 8 & 0xFF, hex & 0xFF]
}

// @returns {String} css style rgb() string
function mixColor (colorA, colorB, amount) {
  var a = getRgb(colorA)
  var b = getRgb(colorB)
  var mixed = [
    a[0] + (a[0] - b[0] * amount),
    a[1] + (a[1] - b[1] * amount),
    a[2] + (a[2] - b[2] * amount)
  ]

  // Crop values
  for (var i = 0; i < mixed.length; i++) {
    if (mixed[i] > 255) mixed[i] = 255
    if (mixed[i] < 0) mixed[i] = 0
  }

  // return mixed
  return 'rgb(' + mixed.join(',') + ')'
}

// @returns {String} css style rgba() string
function fadeColor (color, amount) {
  var rgb = getRgb(color)
  return 'rgba(' + rgb.join(',') + ',' + amount +')'
}

// -- Load the fonts
// Highcharts.createElement('link', {
//    href: 'https://fonts.googleapis.com/css?family=Cabin',
//    rel: 'stylesheet',
//    type: 'text/css'
// }, null, document.getElementsByTagName('head')[0]);

// -- The theme
Highcharts.theme = {
   colors: [COLORS.chart1, COLORS.chart2, COLORS.chart3, COLORS.chart4, COLORS.chart5, COLORS.chart6, COLORS.purple, COLORS.red, COLORS.orange, COLORS.teal],
   chart: {
      backgroundColor: {
         linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
         stops: [
            [0, COLORS.greyl4],
            [1, COLORS.white]
         ]
      },
      style: {
         fontFamily: "'Cabin', sans-serif"
      },
      plotBorderColor: COLORS.greyl,
      spacing: [30, 20, 30, 20]
   },
   title: {
      margin: 20,
      style: {
         color: COLORS.black,
         fontSize: '21px'
      }
   },
   subtitle: {
      margin: 20,
      style: {
         color: COLORS.grey
      }
   },
   xAxis: {
      gridLineColor: COLORS.white,
      labels: {
         style: {
            color: COLORS.grey
         }
      },
      lineColor: COLORS.white,
      minorGridLineColor: COLORS.white,
      tickColor: COLORS.white,
      title: {
         style: {
            color: COLORS.grey
         }
      }
   },
   yAxis: {
      gridLineColor: COLORS.white,
      labels: {
         style: {
            color: COLORS.grey
         }
      },
      lineColor: COLORS.white,
      minorGridLineColor: COLORS.white,
      tickColor: COLORS.white,
      // tickWidth: 0.5,
      title: {
         style: {
            color: COLORS.grey
         }
      }
   },
   plotBands: {
      label: {
        style: {
          fontSize: '14px'
        }
      }
    },
   tooltip: {
      backgroundColor: COLORS.white,
      style: {
         color: COLORS.black,
         fontSize: '14px'
      }
   },
   plotOptions: {
    series: {
       dataLabels: {
          color: COLORS.grey
       },
       marker: {
          lineColor: COLORS.white
       }
    }
   },
   legend: {
      symbolRadius: 10,
      itemMarginTop: 5,
      itemMarginBottom: 5,
      itemStyle: {
         color: COLORS.greyd,
         fontWeight: 'normal',
         fontSize: '16px'
      },
      itemHoverStyle: {
         color: COLORS.black
      },
      itemHiddenStyle: {
         color: COLORS.greyl2
      }
   },
   credits: {
      style: {
         color: COLORS.grey
      }
   },
   labels: {
      style: {
         color: COLORS.black,
         fontSize: '12px'
      }
   },
   navigation: {
      buttonOptions: {
         symbolStroke: COLORS.grey,
         theme: {
            fill: COLORS.white
         }
      }
   },

   // scroll charts
   rangeSelector: {
      buttonTheme: {
         fill: '#505053',
         stroke: '#000000',
         style: {
            color: '#CCC'
         },
         states: {
            hover: {
               fill: '#707073',
               stroke: '#000000',
               style: {
                  color: 'white'
               }
            },
            select: {
               fill: '#000003',
               stroke: '#000000',
               style: {
                  color: 'white'
               }
            }
         }
      },
      inputBoxBorderColor: '#505053',
      inputStyle: {
         backgroundColor: '#333',
         color: 'silver'
      },
      labelStyle: {
         color: 'silver'
      }
   },

   navigator: {
      handles: {
         backgroundColor: '#666',
         borderColor: '#AAA'
      },
      outlineColor: '#CCC',
      maskFill: 'rgba(255,255,255,0.1)',
      series: {
         color: '#7798BF',
         lineColor: '#A6C7ED'
      },
      xAxis: {
         gridLineColor: '#505053'
      }
   },

   scrollbar: {
      barBackgroundColor: '#808083',
      barBorderColor: '#808083',
      buttonArrowColor: '#CCC',
      buttonBackgroundColor: '#606063',
      buttonBorderColor: '#606063',
      rifleColor: '#FFF',
      trackBackgroundColor: '#404043',
      trackBorderColor: '#404043'
   },

   // special colors for some of the
   legendBackgroundColor: 'rgba(0, 0, 0, 0.5)',
   background2: '#505053',
   dataLabelsColor: '#B0B0B3',
   textColor: '#C0C0C0',
   contrastTextColor: '#F0F0F3',
   maskColor: 'rgba(255,255,255,0.3)'
}

// Apply the theme
Highcharts.setOptions(Highcharts.theme)

// Enable hiding single data points in Highcharts legend
// See: http://jsfiddle.net/sNzrK/
Highcharts.wrap(Highcharts.Legend.prototype, 'renderItem', function (proceed, item) {
  if (item.showInLegend !== false) {
    proceed.call(this, item)
  }
})

Highcharts.wrap(Highcharts.Legend.prototype, 'positionItem', function (proceed, item) {
  if (item.showInLegend !== false) {
    proceed.call(this, item)
  }
})

// Highcharts localisation
Highcharts.setOptions({
  lang: {
    decimalPoint: __.__(',', 'numberDecimal'),
    thousandsSep: __.__(' ', 'numberMilli')
  }
})

/*
 * ChartView
 * @class
 */
var ChartView = function (elem, options) {
  var self = this
  self.$elem = $(elem)

  // Error
  if (self.$elem.length === 0 || elem.hasOwnProperty('ChartView')) return false

  // Default settings
  self.settings = $.extend({
    // The target element which holds the chart render
    target: self.$elem,

    // Width of the chart
    width: undefined, // px or %

    // Height of the chart
    height: undefined, // px or %

    // The color for the background
    background: 'default', // can be 'default', 'none', 'transparent', '#ffaacc', or 'rgba(123,123,123)'

    // JSON to load via AJAX first
    // Use the property names within the settings object to set the contents' endpoint
    // This could be 'data', 'highcharts' and/or 'mapGeoJSON'
    // e.g. {data: 'http://example.com/data.json', mapGeoJSON: 'http://example.com/france.geojson'}
    loadFirst: undefined,

    // The data to load
    data: undefined,

    // GeoJSON representing the map (only necessary for charts set to 'map')
    mapGeoJson: undefined,

    // Options for Highcharts
    highcharts: {}
  },

  // Override default settings using values from element's attributes
  ElementAttrsObject(elem, {
    target: 'data-chartview-target',
    schema: 'data-chartview-schema',
    data: 'data-chartview-data',
    highcharts: 'data-chartview-highcharts'
  }),

  // Override default settings using single element attributes
  ElementAttrsObject(elem, 'data-chartview') || {},

  // Override default settings with options set from JS invocation
  options)

  // Assign class to show component behaviours have been applied
  self.$elem.addClass('ui-chartview ui-chartview-loading')

  // Properties
  self.chart = undefined // The highcharts instance

  // Assign instance of class to the element
  self.$elem[0].ChartView = self

  // Initialise
  self.init()

  return self
}

/*
 * Initialises the chart view
 *
 * @method init
 * @param {Array} data The data to initalise the chart with
 * @returns {Void}
 */
ChartView.prototype.init = function () {
  var self = this

  // Ensure the element is set and has an ID
  self.$target = $(self.settings.target)
  if (self.$target.length === 0) {
    console.log('ChartView.init Error: target is not a valid element', self.settings.target, self.$target)
    return false
  }
  if (!self.$target.attr('id')) self.$target.attr('id', 'chart' + Utility.randomString())

  // Load dependencies before rendering
  if (typeof self.settings.loadFirst !== 'undefined') {
    var queue = []
    self.$elem.addClass('ui-chartview-loading')

    // Build the queue
    $.each(self.settings.loadFirst, function (i, url) {
      queue.push($.ajax({
        url: url,
        global: false,
        dataType: 'json'
      }).then(function (data) {
        self.settings[i] = data
        // @debug
        // console.log('ChartView.init Success: loaded ' + url + ' into `elemChartView.settings.' + i + '`', self)

        return {
          name: i,
          url: url,
          status: 'success'
        }
      }).fail(function (err) {
        // @debug
        console.warn('ChartView.init Error: could not load "' + url + '" before rendering the chart', err)

        return {
          name: i,
          url: url,
          status: 'error',
          error: err
        }
      }))
    })

    // Process the queue and render the chart if everything completed successfully
    $.when.apply(self, queue).then(function () {
      var returnedLoaders = Array.prototype.slice.call(arguments)
      // console.log(returnedLoaders)
      self.render()
    }).fail(function () {
      self.$elem.addClass('ui-chartview-load-failed')
    }).done(function () {
      self.$elem.removeClass('ui-chartview-loading')
    })

  // Render immediately
  } else {
    self.render()
  }
}

/*
 * Renders the data in the chart
 *
 * @method render
 * @returns {Void}
 */
ChartView.prototype.render = function (data, schema) {
  var self = this

  // Remove any loading
  self.$elem.removeClass('ui-chartview-loading')

  // Ensure the target is correctly configured
  if (self.settings.highcharts) {
    // Set the width of the target
    if (self.settings.width || parseFloat(self.settings.width) < 250) {
      self.settings.width = '250px'
    }
    if (self.settings.width) self.$target.width(self.settings.width)

    // Set the height of the target
    if (self.settings.height) self.$target.height(self.settings.height)

    // Remove Highcharts credits
    Utility.setObjProp(self.settings.highcharts, 'credits.enabled', false)

    // Set the renderTo
    Utility.setObjProp(self.settings.highcharts, 'chart.renderTo', self.$target[0])

    // Set the background color of the chart
    if (/(none|transparent)/.test(self.settings.background)) {
      Utility.setObjProp(self.settings.highcharts, 'chart.backgroundColor', 'rgba(255,255,255,0)')
    } else if (self.settings.background !== 'default') {
      Utility.setObjProp(self.settings.highcharts, 'chart.backgroundColor', self.settings.background)
    }

    // Use the loaded data
    if (typeof self.settings.data !== 'undefined') {
      Utility.setObjProp(self.settings.highcharts, 'series[0].data', self.settings.data)
    }

    // Use the loaded mapGeoJson data
    if (typeof self.settings.mapGeoJson !== 'undefined') {
      Utility.setObjProp(self.settings.highcharts, 'series[0].mapData', self.settings.mapGeoJson)
    }

    // Use other mapData
    if (typeof self.settings.mapData !== 'undefined') {
      Utility.setObjProp(self.settings.highcharts, 'series[0].mapData', self.settings.mapData)
    }

    // Modifications by chart type
    if (typeof self.settings.type !== 'undefined') {
      switch(self.settings.type) {
        // Project Offers (spline)
        case 'projectOffers':
          break

        // Project Owner Income (spline)
        case 'projectOwnerIncome':
          break

        // Project Owner Balance Active (spline)
        case 'projectOwnerBalanceActive':
          break

        // Project Owner Balance Passive (spline)
        case 'projectOwnerBalancePassive':
          break

        // Preter Projects Categories is a treemap with a single series
        case 'preterProjectsCategories':
          // Only enable white color
          Utility.setObjProp(self.settings.highcharts, 'colors', [COLORS.white])

          // Format the labels to show the icons
          Utility.extendObjProp(self.settings.highcharts, 'series[0].dataLabels', {
            useHTML: true,
            formatter: function () {
              var svgIconId = '#category-sm-' + this.key.replace(/\s+/g, '').toLowerCase()

              // Data point has svgIconId set
              if (this.point.hasOwnProperty('svgIconId')) {
                svgIconId = this.point.svgIconId
              }

              // Relative size
              var maxSize = 60
              var pointWidth = this.point.shapeArgs.width
              var pointHeight = this.point.shapeArgs.height

              // Try proportional size (based on shortest side)
              maxSize = Math.ceil((Math.min(pointWidth, pointHeight) / 100) * maxSize)

              // Cap the size
              if (pointWidth < (maxSize + 8) || pointHeight < (maxSize + 8)) {
                maxSize = Math.min(pointWidth, pointHeight) - 8
              }

              // Generate the SVG category image
              var svgCategory = Utility.svgImage(svgIconId, this.key, maxSize, maxSize)
              return '<div class="chartview-svg-label" style="width: ' + maxSize + 'px; height: ' + maxSize + 'px;">' + svgCategory + '&nbsp;</div>'
            }
          })

          // Enable click events to show the corresponding sector information in the view
          Utility.setObjProp(self.settings.highcharts, 'plotOptions.treemap.events.click', function (event) {
            var series = this

            // @debug
            // console.log('clicked sector in treemap chart', series, event.point)

            // Hide all the other sector info
            $('[data-chartview-sector]').not('[data-chartview-sector="' + event.point.svgIconId + '"]').hide()

            // Show this sector's info
            $('[data-chartview-sector="' + event.point.svgIconId + '"]').show()
          })

          // @debug
          // console.log('init preterProjectsCategories treemap', self.settings.highcharts)

          break

        // Preter Projects Categories Map
        case 'preterStatisticsMap':
        case 'emprunterStatisticsMap':
        case 'preterProjectsCategoriesMap':

          // Never show the legend
          self.settings.highcharts.legend = {
            enabled: false
          }

          // Default tooltip formatting
          Utility.extendObjProp(self.settings.highcharts, 'tooltip', {
            enabled: true,
            headerFormat: '',
            pointFormat: '{point.name}: {point.value}%',
          })

          // Enable click actions on the series
          Utility.setObjProp(self.settings.highcharts, 'plotOptions.map.events.click', function (event) {
            var series = this

            // Hide all the other sector info
            $('[data-chartview-region]').not('[data-chartview-region="' + event.point.insee + '"]').hide()

            // Show this sector's info
            $('[data-chartview-region="' + event.point.insee + '"]').show()
          })

          // Format the display of data labels
          Utility.extendObjProp(self.settings.highcharts, 'plotOptions.map.dataLabels', {
            enabled: true,
            formatter: function () {
              return (this.point.value ? this.point.value + '%' : '')
            },
            style: {
              textShadow: '0 2px 2px rgba(0,0,0,.2), 0 2px 2px rgba(0,0,0,.2), 0 2px 2px rgba(0,0,0,.2)',
              fontSize: '16px'
            }
          })

          // @debug
          // console.log('init preterProjectsCategoriesMap', self.settings.highcharts)

          break

        // Preter Projects Account Pie
        case 'preterProjectsAccount':

          // Change the tooltip display
          Utility.extendObjProp(self.settings.highcharts, 'plotOptions.pie.tooltip', {
            headerFormat: '',
            pointFormat: '<span style="color:{point.color}">\u25CF</span> {point.name}: <b>{point.y}</b><br/>'
          })

          // @debug
          // console.log('init preterProjectsAccount', self.settings.highcharts)

          break

        // Preter Projects Overview Custom Donut Chart
        case 'preterProjectsOverview':

          // Ensure has minimum height for this chart
          if (parseFloat(self.settings.height) < 300) {
            self.settings.height = '300px'
            self.$target.height(self.settings.height)
            Utility.setObjProp(self.settings.highcharts, 'chart.height', 300)
          }

          // Ensure using HTML tooltips
          Utility.setObjProp(self.settings.highcharts, 'tooltip.useHTML', true)

          // Disable tooltip if set false
          // -- Save reference to any previous tooltip formatter function
          var tooltipFormatter = undefined
          if (typeof Utility.objHasProp(self.settings.highcharts, 'tooltip.formatter') === 'function') {
            tooltipFormatter = self.settings.highcharts.tooltip.formatter
          } else {
            tooltipFormatter = Highcharts.Tooltip.prototype.defaultFormatter
          }
          // -- Set the wrapping tooltip formatter function
          Utility.setObjProp(self.settings.highcharts, 'tooltip.formatter', function () {
            var args = Array.prototype.slice.call(arguments)

            // Disable displaying the tooltip if set to false
            if (typeof this.point.options.tooltip !== 'undefined' && this.point.options.tooltip == false) {
              return false

            // Render the tooltip
            } else {
              if (tooltipFormatter) return tooltipFormatter.apply(this, args)
            }
          })

          // Disable hiding items in the legend
          // function disableLegendItemClick () {
          //   return false
          // }
          // Utility.setObjProp(self.settings.highcharts, 'plotOptions.point.events.legendItemClick', disableLegendItemClick)
          // $.each(self.settings.highcharts.series, function (i, series) {
          //   Utility.setObjProp(series, 'point.events.legendItemClick', disableLegendItemClick)
          // })

          // I've had a lot of trouble with this chart and the legend, so I'm just going to build the legend underneath it in the view itself
          Utility.setObjProp(self.settings.highcharts, 'legend', {
            enabled: false
          })

          // @debug
          // console.log('init preterProjectsOverview', self.settings.highcharts)

          break

        // Preter Remboursements Stacked Columns Chart
        case 'preterRemboursements':
          break

        // Preter Operations Pie/Donut Chart
        case 'preterOperations':
          break
      }
    }

    // Create the highcharts controller
    if (self.settings.highcharts.chart.type === 'map' && self.settings.highcharts.series[0].hasOwnProperty('mapData')) {
      self.chart = new Highcharts.Map(self.settings.highcharts)
    } else {
      self.chart = new Highcharts.Chart(self.settings.highcharts)
    }

    // Apply the type as a class
    self.$elem.addClass('ui-chartview-type-' + self.settings.type)
  }
}

/*
 * Refreshes the chart's view
 *
 * @method refresh
 * @returns {Void}
 */
ChartView.prototype.refresh = function (redraw) {
  var self = this

  // Don't if no chart rendered
  if (!self.chart) return

  // Whether to redraw the chart or not
  if (!redraw) {
    self.chart.reflow()
  } else {
    self.chart.redraw()
  }
}

/*
 * Destroy the ChartView instance (free up memory)
 *
 * @method destroy
 * @returns {Void}
 */
ChartView.prototype.destroy = function () {
  var self = this

  self.$elem[0].ChartView = false
  delete self
}

/*
 * jQuery Plugin
 */
$.fn.uiChartView = function (op) {
  // Fire a command to the ChartView object, e.g. $('[data-chartview]').uiChartView('publicMethod', {..})
  // @todo add in list of public methods that $.fn.uiChartView can reference
  if (typeof op === 'string' && /^(refresh|destroy)$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.hasOwnProperty('ChartView') && typeof elem.ChartView[op] === 'function') {
        elem.ChartView[op].apply(elem.ChartView, args)
      }
    })

  // Set up a new ChartView instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.hasOwnProperty('ChartView')) {
        new ChartView(elem, op)
      }
    })
  }
}

/*
 * jQuery Events
 */
$(document)
  // Auto-init component behaviours on document ready, or when parent element (or self) is made visible with `UI:visible` custom event
  .on('ready UI:visible', function (event) {
    $(event.target).find('[data-chartview]').not('.ui-chartview').uiChartView()
  })

  // Ensure refresh of chartview if item is visible/updated
  .on('UI:visible UI:update', function (event) {
    $(event.target).find('[data-chartview], .ui-chartview').uiChartView('refresh')
  })
