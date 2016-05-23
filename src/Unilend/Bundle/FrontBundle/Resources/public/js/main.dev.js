/*
 * Unilend JS
 *
 * @linter Standard JS (http://standardjs.com/)
 */

// @TODO emprunter sim functionality
// @TODO AutoComplete needs hooked up to AJAX
// @TODO Sortable may need AJAX functionality
// @TODO FileAttach may need AJAX functionality

// Dependencies
var $ = require('jquery') // Gets the global (see package.json)
var videojs = require('videojs') // Gets the global (see package.json)
var svg4everybody = require('svg4everybody')
var Swiper = require('Swiper')
var Iban = require('iban')

// Lib
var Utility = require('Utility')
var __ = require('__')
var Tween = require('Tween')
var ElementBounds = require('ElementBounds')

// Components & behaviours
var AutoComplete = require('AutoComplete')
var WatchScroll = require('WatchScroll')
var TextCount = require('TextCount')
var TimeCount = require('TimeCount')
var Sortable = require('Sortable')
var PasswordCheck = require('PasswordCheck')
var FileAttach = require('FileAttach')
var FormValidation = require('FormValidation')
var DashboardPanel = require('DashboardPanel')
// var Sticky = require('Sticky') // @note unfinished

//
$(document).ready(function ($) {
  // Main vars/elements
  var $doc = $(document)
  var $html = $('html')
  var $win = $(window)
  var $siteHeader = $('.site-header')
  var $siteFooter = $('.site-footer')

  // Remove HTML
  $html.removeClass('no-js')

  // TWBS setup
  // $.support.transition = false
  // Bootstrap Tooltips
   $('[data-toggle="tooltip"]').tooltip()

  // Breakpoints
  // -- Devices
  var breakpoints = {
    'mobile-p': [0, 599],
    'mobile-l': [600, 799],
    'tablet-p': [800, 1023],
    'tablet-l': [1024, 1299],
    'laptop':   [1300, 1599],
    'desktop':  [1600, 99999]
  }

  // -- Device groups
  breakpoints.mobile = [0, breakpoints['mobile-l'][1]]
  breakpoints.tablet = [breakpoints['tablet-p'][0], breakpoints['tablet-l'][1]]
  breakpoints.computer = [breakpoints['laptop'][0], breakpoints['desktop'][1]]

  // -- Grids
  breakpoints.xs = breakpoints['mobile-p']
  breakpoints.sm = [breakpoints['mobile-l'][0], breakpoints['tablet-p'][1]]
  breakpoints.md = [breakpoints['tablet-l'][0], breakpoints['laptop'][1]]
  breakpoints.lg = breakpoints['desktop']

  // Track the current breakpoints (also updated in updateWindow())
  var currentBreakpoint = getActiveBreakpoints()

  // VideoJS
  // Running a modified version to customise the placement of items in the control bar
  videojs.options.flash.swf = null // @TODO needs correct link '/js/vendor/videojs/video-js.swf'

  // siteSearch autocomplete
  var siteSearchAutoComplete = new AutoComplete('.site-header .site-search-input', {
    // @TODO eventually when AJAX is connected, the URL will go here
    // ajaxUrl: '',
    target: '.site-header .site-search .autocomplete'
  })

  // Site Search
  var siteSearchTimeout = 0

  // -- Events
  $doc
    // Activate/focus .site-search-input
    .on(Utility.clickEvent + ' active focus keydown', '.site-search-input', function (event) {
      openSiteSearch()
    })
    // Hover over .site-search .autocomplete
    .on('mouseenter mouseover', '.site-search .autocomplete', function (event) {
      openSiteSearch()
    })

    // Dismiss site search after blur
    .on('keydown', '.site-search-input', function (event) {
      // @debug console.log('keyup', '.site-search-input')
      // Dismiss
      if (event.which === 27) {
        closeSiteSearch(0)
        $(this).blur()
      }
    })
    .on('blur', '.site-search-input, .site-search .autocomplete-results a', function (event) {
      // @debug console.log('blur', '.site-search-input')
      closeSiteSearch(200)
    })
    // @debug
    // .on('mouseleave', '.site-header .site-search', function (event) {
    //   console.log('mouseleave', '.site-search')

    //   // Don't dismiss
    //   if ($('.site-header .site-search-input').is(':focus, :active')) {
    //     return
    //   }

    //   closeSiteSearch()
    // })

    // Stop site search dismissing when hover in autocomplete
    .on('mouseenter mouseover', '.site-search .autocomplete', function (event) {
      // @debug console.log('mouseenter mouseover', '.site-header .site-search .autocomplete a')
      cancelCloseSiteSearch()
    })
    // Stop site search dismissing when focus/active links in autocomplete
    .on('keydown focus active', '.site-search .autocomplete a', function (event) {
      // @debug console.log('keydown focus active', '.site-header .site-search .autocomplete a')
      cancelCloseSiteSearch()
    })

  // -- Methods
  function openSiteSearch () {
    // @debug console.log('openSiteSearch')
    cancelCloseSiteSearch()
    $html.addClass('ui-site-search-open')
  }

  function closeSiteSearch (timeout) {
    // @debug console.log('closeSiteSearch', timeout)

    // Defaults to time out after .5s
    if (typeof timeout === 'undefined') timeout = 500

    siteSearchTimeout = setTimeout(function () {
      $html.removeClass('ui-site-search-open')

      // Hide the autocomplete
      siteSearchAutoComplete.hide()
    }, timeout)
  }

  function cancelCloseSiteSearch () {
    // @debug console.log('cancelCloseSiteSearch')
    clearTimeout(siteSearchTimeout)
  }

  /*
   * Site Mobile Menu
   */
  // Show the site mobile menu
  $doc.on(Utility.clickEvent, '.site-mobile-menu-open', function (event) {
    event.preventDefault()
    openSiteMobileMenu()
  })

  // Close the site mobile menu
  $doc.on(Utility.clickEvent, '.site-mobile-menu-close', function (event) {
    event.preventDefault()
    closeSiteMobileMenu()
  })

  // At end of opening animation
  $doc.on(Utility.animationEndEvent, '.ui-site-mobile-menu-opening', function (event) {
    showSiteMobileMenu()
  })

  // At end of closing animation
  $doc.on(Utility.animationEndEvent, '.ui-site-mobile-menu-closing', function (event) {
    hideSiteMobileMenu()
  })

  function openSiteMobileMenu () {
    // @debug console.log('openSiteMobileMenu')
    if (isIE(9) || isIE('<9')) return showSiteMobileMenu()
    if (!$html.is('.ui-site-mobile-menu-open, .ui-site-mobile-menu-opening')) {
      $html.removeClass('ui-site-mobile-menu-closing').addClass('ui-site-mobile-menu-opening')
    }
  }

  function closeSiteMobileMenu () {
    if (isIE(9) || isIE('<9')) return hideSiteMobileMenu()
    // @debug console.log('closeSiteMobileMenu')
    $html.removeClass('ui-site-mobile-menu-opening ui-site-mobile-menu-open').addClass('ui-site-mobile-menu-closing')
  }

  function showSiteMobileMenu () {
    // @debug console.log('showSiteMobileMenu')
    $html.addClass('ui-site-mobile-menu-open').removeClass('ui-site-mobile-menu-opening ui-site-mobile-menu-closing')

    // ARIA stuff
    $('.site-mobile-menu').removeAttr('aria-hidden')
    $('.site-mobile-menu [tabindex]').attr('tabindex', 1)
  }

  function hideSiteMobileMenu () {
    // @debug console.log('hideSiteMobileMenu')
    $html.removeClass('ui-site-mobile-menu-opening ui-site-mobile-menu-closing ui-site-mobile-menu-open')

    // ARIA stuff
    $('.site-mobile-menu').attr('aria-hidden', 'true')
    $('.site-mobile-menu [tabindex]').attr('tabindex', -1)
  }

  /*
   * Site Mobile Search
   */

  // Click button search
  $doc.on(Utility.clickEvent, '.site-mobile-search-toggle', function (event) {
    event.preventDefault()
    if (!$html.is('.ui-site-mobile-search-open')) {
      openSiteMobileSearch()
    } else {
      closeSiteMobileSearch()
    }
  })

  // Focus/activate input
  $doc.on('focus active', '.site-mobile-search-input', function (event) {
    // @debug console.log('focus active .site-mobile-search-input')
    openSiteMobileSearch()
  })

  // Blur input
  // $doc.on('blur', '.site-mobile-search-input', function (event) {
  //   // @debug console.log('blur site-mobile-search-input')
  //   closeSiteMobileSearch()
  // })

  function openSiteMobileSearch () {
    // @debug console.log('openSiteMobileSearch')
    openSiteMobileMenu()
    $html.addClass('ui-site-mobile-search-open')
  }

  function closeSiteMobileSearch () {
    $html.removeClass('ui-site-mobile-search-open')
  }

  /*
   * Open search (auto-detects which)
   */
  function openSearch() {
    // Mobile site search
    if (/xs|sm/.test(currentBreakpoint)) {
      // @debug console.log('openSiteMobileSearch')
      openSiteMobileSearch()
      $('.site-mobile-search-input').focus()

    // Regular site search
    } else {
      $('.site-search-input').focus()
    }
  }

  // Open the site-search from a different button
  $doc.on('click', '.ui-open-site-search', function (event) {
    event.preventDefault()
    openSearch()
  })

  /*
   * FancyBox
   */
  $('.fancybox').fancybox()
  $('.fancybox-media').each(function (i, elem) {
    var $elem = $(elem)
    if ($elem.is('.fancybox-embed-videojs')) {
      $elem.fancybox({
        padding: 0,
        margin: 0,
        autoSize: true,
        autoCenter: true,
        content: '<div class="fancybox-video"><video id="fancybox-videojs" class="video-js" autoplay controls preload="auto" data-setup=\'{ "techOrder": ["youtube"], "sources": [{ "type": "video/youtube", "src": "' + $elem.attr('href') + '" }], "inactivityTimeout": 0 }\'></video></div>',
        beforeShow: function () {
          $.fancybox.showLoading()
          $('.fancybox-overlay').addClass('fancybox-loading')
        },
        // Assign video player functionality
        afterShow: function () {
          // Video not assigned yet
          if (!videojs.getPlayers().hasOwnProperty('fancybox-video')) {
            videojs('#fancybox-videojs', {}, function () {
              var videoPlayer = this

              // Set video width
              var videoWidth = window.innerWidth * 0.7
              if (videoWidth < 280) videoWidth = 280
              if (videoWidth > 1980) videoWidth = 1980
              videoPlayer.width(videoWidth)

              // Update the fancybox width
              $.fancybox.update()
              $.fancybox.hideLoading()
              setTimeout(function () {
                $('.fancybox-overlay').removeClass('fancybox-loading')
              }, 200)
            })
          } else {
            $.fancybox.update()
            $.fancybox.hideLoading()
            setTimeout(function () {
              $('.fancybox-overlay').removeClass('fancybox-loading')
            }, 200)
          }
        },
        // Remove video player on close
        afterClose: function () {
          videojs.getPlayers()['fancybox-videojs'].dispose()
        }
      })
    } else {
      $elem.fancybox({
        helpers: {
          'media': {}
        }
      })
    }
  })

  /*
   * Swiper
   */
  $('.swiper-container').each(function (i, elem) {
    var $elem = $(elem)
    var swiperOptions = {
      direction: $elem.attr('data-swiper-direction') || 'horizontal',
      loop: $elem.attr('data-swiper-loop') === 'true',
      effect: $elem.attr('data-swiper-effect') || 'fade',
      speed: parseInt($elem.attr('data-swiper-speed'), 10) || 250,
      autoplay: parseInt($elem.attr('data-swiper-autoplay'), 10) || 5000,
      // ARIA keyboard functionality
      a11y: $elem.attr('data-swiper-aria') === 'true'
    }

    // Fade / Crossfade
    if (swiperOptions.effect === 'fade') {
      swiperOptions.fade = {
        crossFade: $elem.attr('data-swiper-crossfade') === 'true'
      }
    }

    // Dynamically test if has pagination
    if ($elem.find('.swiper-custom-pagination').length > 0 && $elem.find('.swiper-custom-pagination > *').length > 0) {
      swiperOptions.paginationType = 'custom'
    }

    var elemSwiper = new Swiper(elem, swiperOptions)
    // console.log(elemSwiper)

    // Add event to hook up custom pagination to appropriate slide
    if (swiperOptions.paginationType === 'custom') {
      // Hook into sliderMove event to update custom pagination
      elemSwiper.on('slideChangeStart', function () {
        // Unactive any active pagination items
        $elem.find('.swiper-custom-pagination li.active').removeClass('active')

        // Activate the current pagination item
        $elem.find('.swiper-custom-pagination li:eq(' + elemSwiper.activeIndex + ')').addClass('active')

        // console.log('sliderMove', elemSwiper.activeIndex)
      })

      // Connect user interaction with custom pagination
      $elem.find('.swiper-custom-pagination li').on('click', function (event) {
        var $elem = $(this).parents('.swiper-container')
        var $target = $(this)
        var swiper = $elem[0].swiper
        var newSlideIndex = $elem.find('.swiper-custom-pagination li').index($target)

        event.preventDefault()
        swiper.pauseAutoplay()
        swiper.slideTo(newSlideIndex)
      })
    }

    // Specific swipers
    // -- Homepage Acquisition Video Hero
    if ($elem.is('#homeacq-video-hero-swiper')) {
      elemSwiper.on('slideChangeStart', function () {
        var emprunterName = $elem.find('.swiper-slide:eq(' + elemSwiper.activeIndex + ')').attr('data-emprunter-name')
        var preterName = $elem.find('.swiper-slide:eq(' + elemSwiper.activeIndex + ')').attr('data-preter-name')
        if (emprunterName) $elem.parents('.cta-video-hero').find('.ui-emprunter-name').text(emprunterName)
        if (preterName) $elem.parents('.cta-video-hero').find('.ui-preter-name').text(preterName)
      })
    }
  })


  // @debug remove for production
  $doc
    .on(Utility.clickEvent, '#set-lang-en', function (event) {
      event.preventDefault()
      $html.attr('lang', 'en')
      __.defaultLang = 'en'
    })
    .on(Utility.clickEvent, '#set-lang-en-gb', function (event) {
      event.preventDefault()
      $html.attr('lang', 'en-gb')
      __.defaultLang = 'en-gb'
    })
    .on(Utility.clickEvent, '#set-lang-fr', function (event) {
      event.preventDefault()
      $html.attr('lang', 'fr')
      __.defaultLang = 'fr'
    })
    .on(Utility.clickEvent, '#set-lang-es', function (event) {
      event.preventDefault()
      $html.attr('lang', 'es')
      __.defaultLang = 'es'
    })
    .on(Utility.clickEvent, '#restart-text-counters', function (event) {
      event.preventDefault()
      $('.ui-text-count').each(function (i, elem) {
        elem.TextCount.resetCount()
        elem.TextCount.startCount()
      })
    })

  /*
   * Time counters
   */
  $doc
    // Basic project time counter
    .on('TimeCount:update', '.ui-time-counting', function (event, elemTimeCount, timeRemaining) {
      var $elem = $(this)
      var outputTime

      // @debug console.log(timeRemaining)

      if (timeRemaining.days > 2) {
        outputTime = (timeRemaining.days + Math.ceil(timeRemaining.hours / 24)) + ' ' + __.__('days', 'timeCountDays') + ' ' + __.__('remaining', 'timeCountRemaining')
      } else {
        // Expired
        if (timeRemaining.seconds < 0) {
          outputTime = __.__('Project expired', 'projectPeriodExpired')

        // Countdown
        } else {
          outputTime = Utility.leadingZero(timeRemaining.hours + (24 * timeRemaining.days)) + ':' + Utility.leadingZero(timeRemaining.minutes) + ':' + Utility.leadingZero(timeRemaining.seconds)
        }
      }

      // Update counter
      $elem.text(outputTime)
    })

    // Project list time counts completed
    .on('TimeCount:completed', '.project-list-item .ui-time-count', function () {
      $(this).parents('.project-list-item').addClass('ui-project-expired')
      $(this).text(__.__('Project expired', 'projectListItemPeriodExpired'))
    })

    // Project single time count completed
    .on('TimeCount:completed', '.project-single .ui-time-count', function () {
      $(this).parents('.project-single').addClass('ui-project-expired')
      $(this).text(__.__('Project expired', 'projectSinglePeriodExpired'))
    })

  /*
   * Watch Scroll
   */
  // Window scroll watcher
  var watchWindow = new WatchScroll.Watcher(window)
    // Fix site nav
    .watch(window, 'scrollTop>50', function () {
      $html.addClass('ui-site-header-fixed')
    })
    // Unfix site nav
    .watch(window, 'scrollTop<=50', function () {
      $html.removeClass('ui-site-header-fixed')
    })
    // Start text counters
    .watch('.ui-text-count', 'enter', function () {
      if (this.hasOwnProperty('TextCount')) {
        if (!this.TextCount.started()) this.TextCount.startCount()
      }
    })

  // Dynamic watchers (single)
  // @note if you need to add more than one action, I suggest doing it via JS
  $('[data-watchscroll-action]').each(function (i, elem) {
    var $elem = $(elem)
    var action = {
      action: $elem.attr('data-watchscroll-action'),
      callback: $elem.attr('data-watchscroll-callback'),
      target: $elem.attr('data-watchscroll-target')
    }

    // Detect which action and callback to fire
    watchWindow.watch(elem, $elem.attr('data-watchscroll-action'), function () {
      watchScrollCallback.apply(elem, [action])
    })
  })

  // Basic WatchScroll callback methods
  function watchScrollCallback (action) {
    var $elem = $(this)

    // e.g. `addClass:ui-visible`
    var handle = action.callback
    var target = action.target || $elem[0]
    var method = handle
    var value
    if (!handle) return

    // Split to get other values
    if (/\:/.test(handle)) {
      handle = handle.split(':')
      method = handle[0]
      value = handle[1]
    }

    // Get the target
    $target = $(target)

    // @debug console.log('watchScrollCallback', this, method, value);

    // Handle different methods
    switch (method.toLowerCase()) {
      // addclass:class-to-add
      case 'addclass':
        $target.addClass(value)
        break

      // removeclass:class-to-remove
      case 'removeclass':
        $target.removeClass(value)
        break
    }
  }

  /*
   * WatchScroll Nav: If item is visible (via WatchScroll action `enter`) then make the navigation item active
   */
  $('[data-watchscroll-nav]').each(function (i, elem) {
    watchWindow.watch(elem, WatchScroll.actions.withinMiddle)
  })
  $doc.on('watchscroll-action-withinmiddle', '[data-watchscroll-nav]', function () {
    var $navLinks = $('.nav li:not(".active") a[href="#' + $(this).attr('id') + '"]')
    $navLinks.each(function (i, elem) {
      var $elem = $(elem)
      var $navItem = $elem.parents('li').first()
      if (!$navItem.is('.active')) {
        $elem.parents('.nav').first().find('li').removeClass('active').filter($navItem).addClass('active')
      }
    })
  })

  /*
   * Fixed project single menu
   */
  var projectSingleNavOffsetTop
  if ($('.project-single-menu').length > 0) {
    projectSingleNavOffsetTop = $('.project-single-nav').first().offset().top - (parseInt($('.site-header').height(), 10) * 0.5)
    watchWindow
      .watch(window, function (params) {
        // @debug console.log($win.scrollTop() >= projectSingleNavOffsetTop)
        if ($win.scrollTop() >= projectSingleNavOffsetTop) {
          $html.addClass('ui-project-single-menu-fixed')
        } else {
          $html.removeClass('ui-project-single-menu-fixed')
        }
      })
  }

  /*
   * Progress tabs
   */
  // Any tabs areas with `.ui-tabs-progress` class will add a `.complete` class to the tabs before
  $doc.on('shown.bs.tab', '.tabs.ui-tabs-progress', function (event) {
    var $target = $(event.target)
    var $tab = $('.nav a[role="tab"][href="' + $target.attr('href') + '"]').first()
    var $nav = $tab.parents('.nav')
    var $tabs = $nav.find('a[role="tab"]')
    var tabIndex = $tabs.index($tab)

    if (tabIndex >= 0) {
      $tabs.filter(':gt('+tabIndex+')').parents('li').removeClass('active complete')
      $tabs.filter(':lt('+tabIndex+')').parents('li').removeClass('active').addClass('complete')
      $tab.parents('li').removeClass('complete').addClass('active')
    }
  })

  // Validate any groups/fields within the tabbed area before going on to the next stage
  $doc.on('show.bs.tab', '.tabs.ui-tabs-progress [role="tab"]', function (event) {
    var $form = Utility.getElemIsOrHasParent(event.target, 'form').first()
    var $nextTab = $($(event.target).attr('href'))
    var $currentTab = $form.find('[role="tabpanel"].active').first()

    // console.log($form, $currentTab)

    // Validate the form within the current tab before continuing
    if ($currentTab.find('[data-formvalidation]').length > 0) {
      var fa = $currentTab.find('[data-formvalidation]').first()[0].FormValidation
      var formValidation = fa.validate()
      console.log(formValidation)

      // Validation Errors: prevent going to the next tab
      if (formValidation.erroredFields.length > 0) {
        event.preventDefault()
        event.stopPropagation()
        scrollTo(fa.$notifications)
        return false
      }
    }
  })

  /*
   * Emprunter Sim
   */
  $doc
    .on('shown.bs.tab', '.emprunter-sim', function () {
      console.log('shown tab')
    })
    // Step 1
    .on('FormValidation:validate:error', '#esim1', function () {
      // Hide the continue button
      $('.emprunter-sim').removeClass('ui-emprunter-sim-estimate-show')
    })
    .on('FormValidation:validate:success', '#esim1', function () {
      // Show the continue button
      $('.emprunter-sim').addClass('ui-emprunter-sim-estimate-show')
    })
    .on('change', 'form.emprunter-sim', function (event) {
      console.log(event.type, event.target)
    })
    // Step 2
    // .on('FormValidation:validate:error', '#esim2', function () {
    //   // Hide the submit button
    //   $('.emprunter-sim').removeClass('ui-emprunter-sim-step-2')
    // })
    // .on('FormValidation:validate:success', '#esim2', function () {
    //   // Show the submit button
    //   $('.emprunter-sim').removeClass('ui-emprunter-sim-step-1').addClass('ui-emprunter-sim-step-2')
    // })

  /*
   * Project List
   */
  // Set original order for each list
  $('.project-list').each(function (i, elem) {
    var $elem = $(elem)
    var $items = $elem.find('.project-list-item')

    $items.each(function (j, item) {
      $(item).attr('data-original-order', j)
    })
  })

  // Reorder project list depending on filtered column and sort direction
  $doc.on(Utility.clickEvent, '.project-list-filter[data-sort-by]', function (event) {
    var $elem = $(this)
    var $projectList = $elem.parents('.project-list')
    var $filters = $projectList.find('.project-list-filter')
    var $list = $projectList.find('.project-list-items')
    var $items = $list.find('.project-list-item')
    var sortColumn = false
    var sortDirection = false
    event.preventDefault()

    // Get column to sort by
    sortColumn = $elem.attr('data-sort-by')

    // Get direction to sort by
    if ($elem.is('.ui-project-list-sort-asc')) {
      sortDirection = 'desc'
    } else {
      sortDirection = 'asc'
    }

    // Error if invalid values
    if (!sortColumn || !sortDirection) return

    // Reset all sorting filters
    $filters.removeClass('ui-project-list-sort-asc ui-project-list-sort-desc')

    // Sorting
    switch (sortDirection) {
      case 'asc':
        $items.sort(function (a, b) {
          a = parseFloat($(a).attr('data-sort-' + sortColumn))
          b = parseFloat($(b).attr('data-sort-' + sortColumn))
          switch (a > b) {
            case true:
              return 1
            case false:
              return -1
            default:
              return 0
          }
        })
        break

      case 'desc':
        $items.sort(function (a, b) {
          a = parseFloat($(a).attr('data-sort-' + sortColumn))
          b = parseFloat($(b).attr('data-sort-' + sortColumn))
          switch (a < b) {
            case true:
              return 1
            case false:
              return -1
            default:
              return 0
          }
        })
        break
    }

    // Set sorted column to sort direction class
    $elem.addClass('ui-project-list-sort-' + sortDirection)

    // Change the DOM order of items
    $items.detach().appendTo($list)
  })

  /*
   * Test for IE
   */
  function isIE (version) {
    var versionNum = ~~(version + ''.replace(/\D+/g, ''))
    if (/^\</.test(version)) {
      version = 'lt-ie' + versionNum
    } else {
      version = 'ie' + versionNum
    }
    return $html.is('.' + version)
  }

  /*
   * I hate IE
   */
  if (isIE(9)) {
    // Specific fixes for IE
    $('.project-list-item .project-list-item-category').each(function (i, item) {
      $(this).wrapInner('<div style="width: 100%; height: 100%; position: relative"></div>')
    })
  }

  /*
   * Responsive
   */
  // Get breakpoints
  // @method getActiveBreakpoints
  // @returns {String}
  function getActiveBreakpoints() {
    var width = window.innerWidth;
    var bp = []
    for (var x in breakpoints) {
      if ( width >= breakpoints[x][0] && width <= breakpoints[x][1]) bp.push(x)
    }
    return bp.join(' ')
  }

  /*
   * Set to device height
   * Relies on element to have [data-set-device-height] attribute set
   * to one or many breakpoint names, e.g. `data-set-device-height="xs sm"`
   * for device's height to be applied at those breakpoints
   */
  function setDeviceHeights() {
    // Always get the site header height to remove from the element's height
    var siteHeaderHeight = $('.site-header').outerHeight()
    var deviceHeight = window.innerHeight - siteHeaderHeight

    // Set element to height of device
    $('[data-set-device-height]').each(function (i, elem) {
      var $elem = $(elem)
      var checkBp = $elem.attr('data-set-device-height').trim().toLowerCase()
      var setHeight = false

      // Turn elem setting into an array to iterate over later
      if (!/[, ]/.test(checkBp)) {
        checkBp = [checkBp]
      } else {
        checkBp = checkBp.split(/[, ]+/)
      }

      // Check if elem should be set to device's height
      for (var j in checkBp) {
        if (new RegExp(checkBp[j], 'i').test(currentBreakpoint)) {
          setHeight = checkBp[j]
          break
        }
      }

      // Set the height
      if (setHeight) {
        // @debug
        // console.log('Setting element height to device', currentBreakpoint, checkBp)
        $elem.css('height', deviceHeight + 'px').addClass('ui-set-device-height')
      } else {
        $elem.css('height', '').removeClass('ui-set-device-height')
      }
    })
  }

  /*
   * Equal Height
   * Sets multiple elements to be the equal (maximum) height
   * Elements require attribute [data-equal-height] set. You can also specify the
   * breakpoints you only want this to be applied to in this attribute, e.g.
   * `<div data-equal-height="xs">..</div>` would only be applied in `xs` breakpoint
   * If you want to separate equal height elements into groups, additionally
   * set the [data-equal-height-group] attribute to a unique string ID, e.g.
   * `<div data-equal-height="xs" data-equal-height-group="promo1">..</div>`
   */
  function setEqualHeights () {
    var equalHeights = {}
    $('[data-equal-height]').each(function (i, elem) {
      var $elem = $(elem)
      var groupName = $elem.attr('data-equal-height-group') || 'default'
      var elemHeight = $elem.css('height', '').outerHeight()

      // Create value to save max height to
      if (!equalHeights.hasOwnProperty(groupName)) equalHeights[groupName] = 0

      // Set max height
      if (elemHeight > equalHeights[groupName]) equalHeights[groupName] = elemHeight

    // After processing all, apply height (depending on breakpoint)
    }).each(function (i, elem) {
      var $elem = $(elem)
      var groupName = $elem.attr('data-equal-height-group') || 'default'
      var applyToBp = $elem.attr('data-equal-height')

      // Only apply to certain breakpoints
      if (applyToBp) {
        applyToBp = applyToBp.split(/[ ,]+/)

        // Test breakpoint
        if (new RegExp(applyToBp.join('|'), 'i').test(getActiveBreakpoints())) {
          $elem.height(equalHeights[groupName])

        // Remove height
        } else {
          $elem.css('height', '')
        }

      // No breakpoint set? Apply indiscriminately
      } else {
        $elem.height(equalHeights[groupName])
      }
    })
  }

  /*
   * Update Window
   */
  // Perform actions when the window needs to be updated
  function updateWindow() {
    clearTimeout(timerDebounceResize)

    // Get active breakpoints
    currentBreakpoint = getActiveBreakpoints()

    // Update the position of the project-single-menu top offset
    if (!$html.is('.ui-project-single-menu-fixed') && typeof projectSingleNavOffsetTop !== 'undefined') {
      projectSingleNavOffsetTop = $('.project-single-nav').first().offset().top - (parseInt($('.site-header').height(), 10) * 0.5)
    }

    // Update the position of the project-single-info offset
    offsetProjectSingleInfo()

    // Set device heights
    setDeviceHeights()

    // Update equal heights
    setEqualHeights()
  }

  // Scroll the window to a point, or an element on the page
  function scrollTo (point, cb, time) {
    // Get element to scroll too
    var $elem = $(point)
    var winScrollTop = $win.scrollTop()
    var toScrollTop = 0
    var diff

    // Try numeric value
    if ($elem.length === 0) {
      toScrollTop = parseInt(point, 10)
    } else {
      toScrollTop = $elem.eq(0).offset().top - 80 // Fixed header space
    }
    if (toScrollTop < 0) toScrollTop = 0

    if (toScrollTop !== winScrollTop) {
      diff = Math.max(toScrollTop, winScrollTop) - Math.min(toScrollTop, winScrollTop)

      // Calculate time to animate by the difference in distance
      if (typeof time === 'undefined') time = diff * 0.1
      if (time < 300) time = 300

      // @debug
      // console.log('scrollTo', {
      //   point: point,
      //   toScrollTop: toScrollTop,
      //   time: time
      // })

      $('html, body').animate({
        scrollTop: toScrollTop + 'px',
        skipGSAP: true
      }, time, 'swing', cb)
    }
  }

  // Scroll to an item which has been referenced on this page
  $doc.on(Utility.clickEvent, 'a[href^="#"]', function (event) {
    var elemId = $(this).attr('href').replace(/^[^#]*/, '')
    var $elem = $(elemId)
    var $self = $(this)

    // Ignore toggles
    if ($self.not('[data-toggle]').length > 0) {
      if ($elem.length > 0) {
        // Custom toggles
        // @note may need to refactor to place logic in better position
        if ($elem.is('.ui-toggle, [data-toggle-group]')) {
          // Get other elements
          $('[data-toggle-group="' + $elem.attr('data-toggle-group') + '"]').not($elem).hide()
          $elem.toggle()
          event.preventDefault()
          return
        }

        // event.preventDefault()
        scrollTo(elemId)
      }
    }
  })



  // Resize window (manual debouncing instead of using requestAnimationFrame)
  var timerDebounceResize = 0
  $win.on('resize', function () {
    clearTimeout(timerDebounceResize)
    timerDebounceResize = setTimeout(function () {
      updateWindow()
    }, 100)
  })

  /*
   * Sortables
   */
  // User interaction sort columns
  $doc.on(Utility.clickEvent, '[data-sortable-by]', function (event) {
    var $target = $(this)
    var columnName = $target.attr('data-sortable-by')

    event.preventDefault()
    $(this).parents('[data-sortable]').uiSortable('sort', columnName)
  })

  /*
   * Charts
   */
  // Convert chart placeholder JSON data to Chart Data
  var chartJSON = window.chartJSON || {}
  var chartData = {}
  for (var i in chartJSON) {
    chartData[i] = JSON.parse(chartJSON[i])
  }

  // Build charts
  function renderCharts() {
    $('[data-chart]:visible').not('[data-highcharts-chart]').each(function (i, elem) {
      // Get the data
      var $elem = $(elem)
      var chartDataKey = $elem.attr('data-chart')

      // Has data
      if (chartData.hasOwnProperty(chartDataKey)) {
        chartData[chartDataKey].credits = {
          enabled: false,
          text: ''
        }
        $elem.highcharts(chartData[chartDataKey])
      }
    })
  }

  // When viewing a tab, see if any charts need to be rendered inside
  $doc.on('shown.bs.tab', function (event) {
    renderCharts()

    // Scroll to the tab in the view too
    // @note currently disabling for fun
    // if ($(event.target).attr('href')) {
    //   scrollTo($(event.target).attr('href'))
    // }
  })

  /*
   * Project Single
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
    if (isIE(9) || isIE('<9')) return showProjectSingleMap()
    if (!$html.is('.ui-project-single-map-open, .ui-project-single-map-opening')) {
      $html.removeClass('ui-project-single-map-open ui-project-single-map-closing').addClass('ui-project-single-map-opening')
    }
  }

  function closeProjectSingleMap () {
    // @debug console.log('closeProjectSingleMap')
    if (isIE(9) || isIE('<9')) return hideProjectSingleMap()
    $html.removeClass('ui-project-single-map-opening ui-project-single-map-open').addClass('ui-project-single-map-closing')
  }

  function showProjectSingleMap () {
    // @debug console.log('showProjectSingleMap')
    if (!$html.is('.ui-project-single-map-open')) {
      $html.removeClass('ui-project-single-map-opening ui-project-single-map-closing').addClass('ui-project-single-map-open')
      $('.ui-project-single-map-toggle .label').text(__.__('Hide map', 'projectSingleMapHideLabel'))
    }
  }

  function hideProjectSingleMap () {
    // @debug console.log('hideProjectSingleMap')
    $html.removeClass('ui-project-single-map-opening ui-project-single-map-open ui-project-single-map-closing')
    $('.ui-project-single-map-toggle .label').text(__.__('View map', 'projectSingleMapShowLabel'))
  }

  function toggleProjectSingleMap () {
    if($html.is('.ui-project-single-map-open, .ui-project-single-map-opening')) {
      closeProjectSingleMap()
    } else {
      openProjectSingleMap()
    }
  }

  // Project Single Info
  var $projectSingleInfoWrap = $('.project-single-info-wrap')
  var $projectSingleInfo = $('.project-single-info')
  if ($projectSingleInfoWrap.length > 0) {
    watchWindow.watch($projectSingleInfo, offsetProjectSingleInfo)
  }

  function offsetProjectSingleInfo () {
    // Only do if within the md/lg breakpoint
    if (/md|lg/.test(currentBreakpoint) && $projectSingleInfo.length > 0) {
      var winScrollTop = $win.scrollTop()
      var infoTop = ($projectSingleInfoWrap.offset().top + parseFloat($projectSingleInfo.css('margin-top')) - $siteHeader.height() - 25)
      var maxInfoTop = $siteFooter.offset().top - $win.innerHeight() - 25
      var translateAmount = winScrollTop - infoTop
      var offsetInfo = 0

      // Constrain info within certain area
      if (winScrollTop > infoTop) {
        if (winScrollTop < maxInfoTop) {
          offsetInfo = translateAmount
        } else {
          offsetInfo = maxInfoTop - 300
        }
      }

      // @debug
      // console.log({
      //   winScrollTop: winScrollTop,
      //   infoTop: infoTop,
      //   maxInfoTop: maxInfoTop,
      //   translateAmount: translateAmount,
      //   offsetInfo: offsetInfo
      // })

      $projectSingleInfo.css({
        transform: 'translateY(' + offsetInfo + 'px)'
      })

    // Reset
    } else {
      $projectSingleInfo.css('transform', '')
    }
  }

  /*
   * Collapse
   */
  // Mark on [data-toggle] triggers that the collapseable is/isn't collapsed
  $doc.on('shown.bs.collapse', function (event) {
    var targetTrigger = '[data-toggle="collapse"][data-target="#'+$(event.target).attr('id')+'"],[data-toggle="collapse"][href="#'+$(event.target).attr('id')+'"]'
    $(targetTrigger).addClass('ui-collapse-open')
  })
  .on('hidden.bs.collapse', function (event) {
    var targetTrigger = '[data-toggle="collapse"][data-target="#'+$(event.target).attr('id')+'"],[data-toggle="collapse"][href="#'+$(event.target).attr('id')+'"]'
    $(targetTrigger).removeClass('ui-collapse-open')
  })

  /*
   * Debug
   */
  if ($('#invalid-route').length > 0 && window.location.search) {
    var queryVars = []

    if (/^\?/.test(window.location.search)) {
      var qv = (window.location.search + '').replace('?', '')

      // Split again
      if (/&(amp;)?/i.test(qv)) {
        qv = qv.split(/&(amp;)?/)
      } else {
        qv = [qv]
      }

      // Process each qv
      for (var i = 0; i < qv.length; i++) {
        var qvSplit = qv[i].split('=')
        queryVars[qvSplit[0]] = qvSplit[1]
      }

      // Output the invalid route to the view
      $('#invalid-route').html('<pre><code>' + decodeURIComponent(queryVars.invalidroute) + '</code></pre>').css({
        display: 'block'
      })
    }
  }

  /*
   * Devenir Preteur
   */
  $doc.on('change', 'input#form-preter-address-is-correspondence', function (event) {
    checkAddressIsCorrespondence()
  })

  function checkAddressIsCorrespondence () {
    var address = ['street', 'code', 'ville', 'pays', 'telephone', 'mobile']
    if ($('input#form-preter-address-is-correspondence').is(':checked')) {
      $('#form-preter-fieldset-correspondence').hide()

      // Clear input values (checkbox defines addresses are same, so backend should reference only single address)
      // @note should only clear values on submit, in case user needs to edit again before submitting
      // for (var i = 0; i < address.length; i++) {
      //   $('[name="identity[correspondence][' + address[i] + ']"').val('')
      // }

    } else {
      $('#form-preter-fieldset-correspondence').show()
    }
  }
  checkAddressIsCorrespondence()

  /*
   * Validate IBAN Input
   */
  function checkIbanInput (event) {
    // Default: check all on the page
    if (typeof event === 'undefined') event = {target: '.custom-input-iban .iban-input', which: 0}

    $(event.target).each(function (i, elem) {
      // Get the current input
      var iban = $(this).val().toUpperCase().replace(/[^0-9A-Z]+/g, '')
      var caretPos = $(this).caret() || $(this).val().length

      // Reformat the input if entering text
      // @TODO when user types fast the caret sometimes gets left behind. May need to figure out better method for this
      if ((event.which >= 48 && event.which <= 90) || (event.which >= 96 && event.which <= 105) || event.which === 8 || event.which === 46 || event.which === 32) {
        if (iban) {
          // Format preview
          var previewIban = iban.match(/.{1,4}/g)
          var newCaretPos = (caretPos % 5 === 0 ? caretPos + 1 : caretPos)

          // @debug
          // console.log({
          //   value: $(this).val(),
          //   valueLength: $(this).val().length,
          //   iban: iban,
          //   ibanLength: iban.length,
          //   groupCount: previewIban.length,
          //   groupCountDivided: previewIban.length / 4,
          //   groupCountMod: previewIban.length % 4,
          //   caretPos: caretPos,
          //   caretPosDivided: caretPos / 4,
          //   caretPosMod: caretPos % 4
          // })

          // Add in spaces and assign the new caret position
          $(this).val(previewIban.join(' ')).caret(newCaretPos)
        }
      }

      // Check if valid
      if (Iban.isValid(iban)) {
        // Valid
      } else {
        // Invalid
      }
    })
  }
  $doc.on('keyup', '.custom-input-iban .iban-input', checkIbanInput)
  checkIbanInput()

  /*
   * Packery
   */
  $('[data-packery]').each(function (i, elem) {
    var $elem = $(elem)
    var elemOptions = JSON.parse($elem.attr('data-packery') || '{}')

    // @debug
    // console.log('data-packery options', elem, elemOptions)

    $elem.packery(elemOptions)

    // Draggable items
    if ($elem.find('.draggable, [data-draggable]').length > 0) {
      $elem.find('.draggable, [data-draggable]').each(function (j, item) {
        var itemOptions = JSON.parse($(item).attr('data-draggable') || '{}')

        // Special case
        if ($(item).is('.dashboard-panel')) {
          itemOptions.handle = '.dashboard-panel-title'
          itemOptions.containment = true
        }

        var draggie = new Draggabilly(item, itemOptions)
        $elem.packery('bindDraggabillyEvents', draggie)
      })
    }
  })

  // Perform on initialisation
  svg4everybody()
  renderCharts()
  updateWindow()
})
