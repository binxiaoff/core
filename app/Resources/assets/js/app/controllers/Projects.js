/*
 * Project List component
 */

var $ = require('jquery')
var Utility = require('Utility')
var __ = require('__')

var $win = $(window)
var $doc = $(document)
var $html = $('html')
var $body = $('body')

// Seconds as units for projects
var secondsAsUnits = [{
    min: 0,
    max: 1,
    single: __.__('Project expired', 'projectPeriodExpired'),
    plural: __.__('Project expired', 'projectPeriodExpired')
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
    max: 172800,
    single: '%d ' + __.__('hour', 'timeUnitHour'),
    plural: '%d ' + __.__('hours', 'timeUnitHours')
  },{
    min: 172800,
    mod: 86400,
    max: -1,
    single: '%d ' + __.__('day', 'timeUnitDay'),
    plural: '%d ' + __.__('days', 'timeUnitDays')
  }]

$doc.on('ready', function () {
  // IE9 adjustments for displaying project-list-item in the project-list
  if (Utility.isIE(9)) {
    // Specific fixes for IE
    $('.project-list-item .project-list-item-category').each(function (i, item) {
      $(this).wrapInner('<div style="width: 100%; height: 100%; position: relative"></div>')
    })
  }

  // Clicking on a project list item should take a user to the single project details page
  $doc.on('click', '.project-list-item', function (event) {
    var $target = $(event.target)
    var href = $target.closest('.project-list-item').find('.project-list-item-title a').first().attr('href')

    // Not an anchor link? Let's go...
    if ($target.closest('a, [data-toggle="tooltip"]').length === 0) {
      event.preventDefault()

      // Go to the project page
      window.location = href
    }
  })

  // Set different update/complete function for these time counters
  // @note this also includes time counters on the single project details page (project-single)
  $('.project .ui-has-timecount, .project-list-item .ui-has-timecount, .project-single .ui-has-timecount').uiTimeCount({
    // @note DEV-949 using relative time now always
    onupdate: function (timeDiff) {
      var elemTimeCount = this
      var outputTime

      // Expired
      if (timeDiff.total > 0) {
        outputTime = elemTimeCount.getRelativeTime(elemTimeCount.settings.startDate, elemTimeCount.settings.endDate, secondsAsUnits)
      } else {
        outputTime = __.__('Project expired', 'projectPeriodExpired')
      }

      // @debug
      if (elemTimeCount.settings.debug) {
        console.log('timecount onupdate timeDiff', timeDiff, outputTime)
      }

      // Update counter
      elemTimeCount.$elem.text(outputTime)
    },
    oncomplete: function () {
      var elemTimeCount = this

      // Project list item
      if (elemTimeCount.$elem.parents('.project-list-item').length > 0) {
        elemTimeCount.$elem.parents('.project-list-item').addClass('ui-project-expired')

        // Project Single
      } else if (elemTimeCount.$elem.parents('.project-single').length > 0) {
        elemTimeCount.$elem.parents('.project-single').addClass('ui-project-expired')

        // Project
      } else {
        elemTimeCount.$elem.parents('.project').addClass('ui-project-expired')
      }

      // Set text to say expired/finished/terminé
      elemTimeCount.$elem.text(__.__('Project expired', 'projectPeriodExpired'))
    }
  })

  /*
   * Project Single Fixed Menu
   */
  var projectSingleNavOffsetTop = 0
  var $projectSingleMenu = $('.project-single-menu')

  function updateProjectSingleNavOffsetTop () {
    if ($projectSingleMenu.length > 0) {
      projectSingleNavOffsetTop = $('.project-single-content .project-single-nav').first().offset().top - (parseInt(Utility.$siteHeader.height(), 10) * 0.5)
    } else {
      projectSingleNavOffsetTop = undefined
    }
  }

  // Add to window WatchScroll watcher means to make project-single-menu fixed
  if ($projectSingleMenu.length > 0) {
    updateProjectSingleNavOffsetTop()

    window.watchWindow
      .watch(window, function (params) {
        // @debug console.log($win.scrollTop() >= projectSingleNavOffsetTop)
        if (typeof projectSingleNavOffsetTop !== 'undefined' && $win.scrollTop() >= projectSingleNavOffsetTop) {
          if (!$html.is('.ui-project-single-menu-fixed')) {
            // @debug
            // console.log('add ui-project-single-menu-fixed')
            $html.addClass('ui-project-single-menu-fixed')
          }
        } else {
          if ($html.is('.ui-project-single-menu-fixed')) {
            // @debug
            // console.log('remove ui-project-single-menu-fixed')
            $html.removeClass('ui-project-single-menu-fixed')
          }
        }
      })
  }

  /*
   * Project Single Map
   * @todo should be refactored out to own app component
   */
  $doc
  // -- Click to show map
    .on(Utility.clickEvent, '.ui-project-single-map-toggle', function (event) {
      event.preventDefault()
      toggleProjectSingleMap()
    })
    // -- Animation Events
    .on(Utility.transitionEndEvent, '.ui-project-single-map-opening', function (event) {
      showProjectSingleMap()
    })
    .on(Utility.transitionEndEvent, '.ui-project-single-map-closing', function (event) {
      hideProjectSingleMap()
    })

  function openProjectSingleMap () {
    // @debug console.log('openProjectSingleMap')
    if (Utility.isIE(9) || Utility.isIE('<9')) return showProjectSingleMap()
    if (!$html.is('.ui-project-single-map-open, .ui-project-single-map-opening')) {
      $html.removeClass('ui-project-single-map-open ui-project-single-map-closing').addClass('ui-project-single-map-opening')
    }
  }

  function closeProjectSingleMap () {
    // @debug console.log('closeProjectSingleMap')
    if (Utility.isIE(9) || Utility.isIE('<9')) return hideProjectSingleMap()
    $html.removeClass('ui-project-single-map-opening ui-project-single-map-open').addClass('ui-project-single-map-closing')
  }

  function showProjectSingleMap () {
    // @debug console.log('showProjectSingleMap')
    if (!$html.is('.ui-project-single-map-open')) {
      $html.removeClass('ui-project-single-map-opening ui-project-single-map-closing').addClass('ui-project-single-map-open')

      // Initialise the project map using the settings JSON object
      if (projectMapViewSettings) {
        projectMapViewSettings = Utility.convertStringToJson(projectMapViewSettings)

        // Initialise the map only if an object was given
        if (typeof projectMapViewSettings === 'object') {
          // Ensure target is set
          if (!projectMapViewSettings.target) projectMapViewSettings.target = '#project-map'
          $(projectMapViewSettings.target).uiMapView(projectMapViewSettings)
        }
      }

      // Manually trigger refreshMapbox on the MapView
      $('[data-mapview], .ui-mapview').uiMapView('refreshMapbox')
    }
  }

  function hideProjectSingleMap () {
    // @debug console.log('hideProjectSingleMap')
    $html.removeClass('ui-project-single-map-opening ui-project-single-map-open ui-project-single-map-closing')

    // Manually trigger refreshMapbox on the MapView
    $('[data-mapview], .ui-mapview').uiMapView('refreshMapbox')
  }

  function toggleProjectSingleMap () {
    if($html.is('.ui-project-single-map-open, .ui-project-single-map-opening')) {
      closeProjectSingleMap()
    } else {
      openProjectSingleMap()
    }
  }

  /*
   * Sticky Project Single Menu
   */
  // @todo probably needs a lot of refactoring. Trickiest thing is all the responsive stuff

  // Offset sticky by marginTop
  var doStickyOffset = function ($elem, amount) {
    if (amount !== false) {
      $elem.css('marginTop', amount + 'px')
    } else {
      $elem.css('marginTop', '')
    }
  }

  // Offset sticky by CSS transform
  if ($html.is('.has-csstransforms')) {
    doStickyOffset = function ($elem, amount) {
      if (amount !== false) {
        $elem.css('transform', 'translateY(' + amount + 'px)')
      } else {
        $elem.css('transform', '')
      }
    }
  }

  /*
   * Sticky Project Single Info
   * @note Slightly more complex than normal sticky because of the project-single-map and negative margins everywhere!
   * @todo potentially use the Sticky class for this, but there might be trouble with the bounds (try using the onbeforehardupdate to calculate the top buffer using the negative margins... ?)
   */
  var $projectSingleInfoWrap = $('.project-single-info-wrap')
  var $projectSingleInfoPos = $('.project-single-info-position')
  var $projectSingleInfo = $('.project-single-info')

  function offsetProjectSingleInfo () {
    // Only do if within the md/lg breakpoint
    if ($projectSingleInfo.length === 1 && /md|lg/.test(currentBreakpoint)) {
      var bufferTop = 25
      var bufferBottom = 100
      var siteHeaderHeight = Utility.$siteHeader.outerHeight()
      var winScrollTop = $win.scrollTop()
      var startInfoFixed = $projectSingleInfoWrap.offset().top + parseFloat($projectSingleInfoPos.css('margin-top')) - siteHeaderHeight - bufferTop
      var infoHeight = $projectSingleInfo.outerHeight()
      var endInfoFixed = Utility.$siteFooter.offset().top - infoHeight - siteHeaderHeight - bufferTop - bufferBottom
      var translateAmount = winScrollTop - startInfoFixed
      var offsetInfo = 0

      // Constrain info within certain area
      if (winScrollTop > startInfoFixed) {
        if (winScrollTop < endInfoFixed) {
          offsetInfo = translateAmount
        } else {
          offsetInfo = endInfoFixed - startInfoFixed
        }
      }

      // Apply offset
      doStickyOffset($projectSingleInfo, offsetInfo)

      // Reset
    } else {
      doStickyOffset($projectSingleInfo, false)
    }
  }

  // Debounce update of sticky within the watchWindow to reduce jank
  if ($projectSingleInfoWrap.length > 0) {
    window.watchWindow.watch(window, offsetProjectSingleInfo)
    offsetProjectSingleInfo()
  }

  // Update the position of the project-single-menu top offset
  $doc.on('UI:updateWindow', function () {
    if (!$html.is('.ui-project-single-menu-fixed') && typeof projectSingleNavOffsetTop !== 'undefined') {
      updateProjectSingleNavOffsetTop()
    }
  })

  /*
   * Project Single monthly repayment estimation
   */
  var monthlyRepaymentTimeout
  var previousAmount
  var previousRate

  function estimateMonthlyRepayment () {
    var messageHolder = $('#repayment-estimation')
    var amount = $('#bid-amount').val()
    var duration = $('#bid-duration').val()
    var rate = $('#bid-interest option:selected').val()

    if (amount && duration && rate && amount >= 20 && (previousAmount != amount || previousRate != rate)) {
      previousAmount = amount
      previousRate = rate

      messageHolder.html('')

      // @trigger elem `Spinner:showLoading`
      $('#bid-amount').trigger('Spinner:showLoading')

      $.ajax({
        url: '/projects/monthly_repayment',
        method: 'POST',
        data: {
          amount: amount,
          duration: duration,
          rate: rate
        },
        success: function (data) {
          if (data.success && data.message) {
            var messageContent = $('<p>').addClass('c-t2').html(data.message)
            messageHolder.html(messageContent)
          } else if (data.error && data.message) {
            console.log(data.message)
          } else {
            console.log('Unknown state')
          }
        },
        error: function () {
          console.log('Unable to estimate monthly repayments')
        }
      })
    } else if (amount && amount < 20) {
      messageHolder.html('')
    }
  }

  $doc.on('change keyup', '#bid-amount, #bid-interest', function () {
    if (monthlyRepaymentTimeout) {
      clearTimeout(monthlyRepaymentTimeout)
    }
    monthlyRepaymentTimeout = setTimeout(estimateMonthlyRepayment, 250)
  })
})
