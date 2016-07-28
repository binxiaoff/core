/*
 * Unilend JS
 * @linter Standard JS (http://standardjs.com/)
 */

// @TODO AutoComplete needs hooked up to AJAX
// @TODO Sortable may need AJAX functionality
// @TODO FileAttach may need AJAX functionality

// Dependencies
var $ = require('jquery') // Gets the global (see package.json)
var videojs = require('videojs') // Gets the global (see package.json)
var svg4everybody = require('svg4everybody')
var Swiper = require('Swiper')
var Iban = require('iban')
var raf = require('raf')
var Clipboard = require('clipboard')
var Tether = require('tether')
var Drop = require('tether-drop')

// UI stuff
require('jquery-ui')
// @note due to browserify and global jQuery object, I can't require these like normal :(
// require('jquery-ui/draggable')
// require('jquery-ui/sortable')
// @note since I've integrated the datepicker too, it requires a few jQuery UI modules within

// @note Bootstrap stuff after jQuery UI
// See: http://stackoverflow.com/questions/17458224/uncaught-error-no-such-method-show-for-tooltip-widget-instance
require('bs.transition')
require('bs.tab')
require('bs.tooltip')
require('bs.collapse')

// Lib
var Utility = require('Utility')
var __ = require('__')
var Tween = require('Tween')
var ElementBounds = require('ElementBounds')
var ElementAttrsObject = require('ElementAttrsObject')
var CacheData = require('CacheData')
var Templating = require('Templating')

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
var CacheForm = require('CacheForm')
var AutolendTable = require('AutolendTable')
var NavDropdownMenu = require('NavDropdownMenu')
var MapView = require('MapView')
var ChartView = require('ChartView')
var BorrowerEsimForm = require('./app/components/BorrowerEsimForm')
var LoginTimer = require('./app/components/LoginTimer')
var LoginCaptcha = require('./app/components/LoginCaptcha')
var SimpleTimer = require('./app/components/SimpleCountDown')
var BidConfirmation = require('./app/components/BidConfirmation')
var CookieCheck = require('./app/components/Cookies')
var BidsDetail = require('./app/components/BidsDetail')
var Spinner = require('./app/components/Spinner')

// @debug
// CacheData.clearAll()

//
$(document).ready(function ($) {
  // Main vars/elements
  var $doc = $(document)
  var $html = $('html')
  var $body = $('body')
  var $win = $(window)
  var $siteHeader = $('.site-header')
  var $siteContent = $('.site-content')
  var $siteFooter = $('.site-footer')
  var Modernizr = window.Modernizr
  raf.polyfill()

  // @debug
  // window.__ = __
  // window.Utility = Utility

  // Remove HTML
  $html.removeClass('no-js')

  // Track the current breakpoints (also updated in updateWindow())
  var currentBreakpoint = window.currentBreakpoint = Utility.getActiveBreakpoints()

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

  // TWBS setup
  // $.support.transition = false
  // Bootstrap Tooltips
   $('.ui-has-tooltip, [data-toggle="tooltip"]').tooltip()

  /*
   * jQuery UI Date Picker
   */
  $('.ui-has-datepicker, [data-ui-datepicker]').datepicker({
    format: 'dd/mm/yy'
  })

  // VideoJS
  // Running a modified version to customise the placement of items in the control bar
  videojs.options.flash.swf = null // @TODO needs correct link '/js/vendor/videojs/video-js.swf'

  // Site Search AutoComplete
  if ($('.site-header .site-search-input').length > 0) {
    var siteSearchAutoComplete = new AutoComplete('.site-header .site-search-input', {
      // @TODO eventually when AJAX is connected, the URL will go here
      // ajaxUrl: '',
      target: '.site-header .site-search .autocomplete'
    })
  }

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
   * Open search (auto-detects whether mobile search or normal search to open)
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
  // Generic fancybox
  $('.fancybox').fancybox()

  // Show HTML content in fancybox (use href='#target-id' to indicate the content. See `src/twig/devenir_preter_lp.twig` for an example)
  $('.fancybox-html').fancybox({
    maxWidth: 800,
    maxHeight: 600,
    autoSize: true
  })

  // Open up media
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

  /*
   * Text Counters
   */
  // @note `.ui-text-count` is the old class, adding auto invocation here for backwards compatibility
  // @note API relies on [data-textcount] being set for behaviours applied automatically
  //       you should only use the `.ui-textcount` class if you want to explicitly set the behaviours
  //       through JS otherwise rely on automatic invocation through the attribute [data-textcount]
  // @todo Remove this call when the Twig views have been updated
  $('.ui-text-count, .ui-has-textcount').uiTextCount()

  // @debug
  // @todo remove for production
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
      $('.ui-text-count, .ui-textcount, [data-textcount]').uiTextCount('resetCount').uiTextCount('startCount')
    })

  /*
   * Time counters
   */
  // Set different update/complete function for these time counters
  $('.project .ui-has-timecount, .project-list-item .ui-has-timecount, .project-single .ui-has-timecount').uiTimeCount({
    onupdate: function (timeDiff) {
      var elemTimeCount = this
      var outputTime

      // Show relative time outside of 2 days
      if (timeDiff.total > (3600000 * 48)) {
        outputTime = elemTimeCount.getRelativeTime()

      } else {
        // Expired
        if (timeDiff.total < 0) {
          outputTime = __.__('Project expired', 'projectPeriodExpired')

        // Countdown
        } else {
          // Custom timecode
          outputTime = Utility.leadingZero(timeDiff.hours + (24 * timeDiff.days)) + ':' + Utility.leadingZero(timeDiff.minutes) + ':' + Utility.leadingZero(timeDiff.seconds)
        }
      }

      // Update counter
      elemTimeCount.$elem.text(outputTime)
    },
    oncomplete: function () {
      var elemTimeCount = this

      // Project list item
      if (elemTimeCount.$elem.parents('.project-list-item').length > 0) {
        elemTimeCount.$elem.parents('.project-list-item').addClass('ui-project-expired')
        elemTimeCount.$elem.text(__.__('Project expired', 'projectListItemPeriodExpired'))

      // Project Single
      } else if (elemTimeCount.$elem.parents('.project-single').length > 0) {
        elemTimeCount.$elem.parents('.project-single').addClass('ui-project-expired')
        elemTimeCount.$elem.text(__.__('Project expired', 'projectSinglePeriodExpired'))

      // Project
      } else {
        elemTimeCount.$elem.parents('.project').addClass('ui-project-expired')
        elemTimeCount.$elem.text(__.__('Project expired', 'projectListItemPeriodExpired'))
      }
    }
  })

  /*
   * Watch Scroll
   */
  // Window scroll watcher
  var watchWindow = new WatchScroll.Watcher(window)
    // Fix site nav
    .watch(window, 'scrollTop>50', function () {
      if (!$html.is('.ui-site-header-fixed')) {
        // @debug
        // console.log('add ui-site-header-fixed')
        $html.addClass('ui-site-header-fixed')
        debounceUpdateWindow()
      }
    })

    // Unfix site nav
    .watch(window, 'scrollTop<=50', function () {
      if ($html.is('.ui-site-header-fixed')) {
        // @debug
        // console.log('remove ui-site-header-fixed')
        $html.removeClass('ui-site-header-fixed')
        debounceUpdateWindow()
      }
    })

    // Start text counters
    .watch('[data-textcount], .ui-has-textcount, .ui-textcount', 'enter', function () {
      // console.log('WatchScroll enter', this)
      $(this).uiTextCount('startCount')
    })

  // Dynamic watchers specified through view attributes (single action per element)
  // @note if you need to add more than one action, I suggest doing it via JS
  $('[data-watchscroll-action]').each(function (i, elem) {
    var $elem = $(elem)
    var action = ElementAttrsObject(elem, {
      action: 'data-watchscroll-action',
      callback: 'data-watchscroll-callback',
      target: 'data-watchscroll-target'
    })

    // Detect which action and callback to fire
    watchWindow.watch(elem, action.action, function () {
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

    // Make sure the tab is scrolled to
    Utility.scrollTo($tab)
  })

  // Validate any groups/fields within the tabbed area before going on to the next stage
  $doc.on(Utility.clickEvent + ' show.bs.tab', '.tabs.ui-tabs-progress [data-toggle="tab"], .tabs.ui-tabs-progress [role="tab"]', function (event) {
    var $nextTab = $(this)
    var $tabsElem = $nextTab.closest('.tabs').first()
    var $allTabPanels = $tabsElem.find('[role="tabpanel"]')
    var $currentTabPanel = $allTabPanels.filter('.active').first()

    // Don't do anymore bro
    if ('#' + $currentTabPanel.attr('id') === $nextTab.attr('href')) return

    var $nextTabPanel = $allTabPanels.filter($nextTab.attr('href')).first()
    var $currentTab = $tabsElem.find('[href="#' + $currentTabPanel.attr('id') + '"][role="tab"]').first()
    var currentTabIndex = $allTabPanels.index($currentTabPanel)
    var nextTabIndex = $allTabPanels.index($nextTabPanel)

    // @debug
    // console.log(currentTabIndex, nextTabIndex)

    // Validate the form within the current tab before continuing
    // (moving backward is free though)
    if (nextTabIndex > currentTabIndex) {
      if ($currentTabPanel.is('[data-formvalidation]') || $currentTabPanel.find('[data-formvalidation]').length > 0) {
        var fvElem

        // Get the right validation elem to pluck instance from
        if ($currentTabPanel.is('[data-formvalidation]')) {
          fvElem = $currentTabPanel[0]
        } else {
          fvElem = $currentTabPanel.find('[data-formvalidation]').first()[0]
        }

        // FormValidation hasn't been loaded yet so reject the event
        if (!fvElem.hasOwnProperty('FormValidation')) {
          event.preventDefault()
          event.stopPropagation()
          return false
        }

        // Validate the form
        var validation = fvElem.FormValidation.validate()

        // Validation Errors: prevent going to the next tab
        if (validation.erroredFields.length > 0) {
          event.preventDefault()
          event.stopPropagation()
          Utility.scrollTo(validation.$notifications)
          return false
        }
      }
    }
  })

  // Enable tab functionality within multi-form tab-content
  $doc.on('shown.bs.tab', '.tabs.ui-tabs-progress [data-toggle="tab"]', function (event) {
    var $target = $(event.target)
    var nextTabId = $target.attr('href')
    var $tabs = Utility.getElemIsOrHasParent($target, '.tabs').first()
    var $navTabs = $tabs.find('.nav').first().find('[data-toggle="tab"]')
    var $contentTabs = $tabs.find('.tab-content > form > [role="tabpanel"]')

    // Remove active on tabs which aren't the next tag
    $navTabs.not('[href="' + nextTabId + '"]').removeClass('active')
    $contentTabs.not(nextTabId).removeClass('active')

    // Add active
    $navTabs.find('[href="' + nextTabId + '"]').addClass('active')
    $contentTabs.find(nextTabId).addClass('active')

    // @debug
    // console.log(nextTabId, event.target, $navTabs, $contentTabs)
  })

  /*
   * Emprunter Sim
   */
  $doc
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
      // console.log(event.type, event.target)
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
  $doc.on(Utility.clickEvent, '.project-list-item', function (event) {
    var $target = $(event.target)
    var href = $target.closest('.project-list-item').find('.project-list-item-title a').first().attr('href')

    // Not an anchor link? Let's go...
    if ($target.closest('a, [data-toggle="tooltip"]').length === 0) {
      event.preventDefault()

      // Go to the project page
      window.location = href
    }
  })

  /*
   * Responsive
   */

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
        if (new RegExp(applyToBp.join('|'), 'i').test(currentBreakpoint)) {
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

    // @debug
    // console.log('equalHeights', equalHeights)
  }

  /*
   * Update Window
   */
  // Manual debouncing instead of using requestAnimationFrame
  // I found significant slowness when using requestAnimationFrame
  var timerDebounceUpdateWindow = 0
  function debounceUpdateWindow () {
    clearTimeout(timerDebounceUpdateWindow)
    timerDebounceUpdateWindow = setTimeout(function () {
      updateWindow()
    }, 50)
  }
  $win.on('orientationchange resize', debounceUpdateWindow)

  // Perform actions when the window needs to be updated
  function updateWindow () {
    clearTimeout(timerDebounceUpdateWindow)
    // requestAnimationFrame(updateWindow)

    // Get active breakpoints
    currentBreakpoint = Utility.getActiveBreakpoints()

    // Update the position of the project-single-menu top offset
    if (!$html.is('.ui-project-single-menu-fixed') && typeof projectSingleNavOffsetTop !== 'undefined') {
      updateProjectSingleNavOffsetTop()
    }

    // Adjust drops
    if (profileNotificationsDrop) profileNotificationsDrop.position()

    // Set device heights
    setDeviceHeights()

    // Update equal heights
    setEqualHeights()

    // Update the stickies (perform hardUpdate due to resize/orientationchange)
    updateStickyWatchers(1)

    // Trigger UI:update event to signal to any other elements that need to update on this event
    // @trigger elem `UI:update`
    $doc.trigger('UI:update')
  }
  // @bind document `UI:updateWindow` Fire debounceUpdateWindow to update any elements due to repaint/reflow
  $doc.on('UI:updateWindow', debounceUpdateWindow)

  /*
   * Smooth scrolling to point on screen or specific element
   */
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
        Utility.scrollTo(elemId)
      }
    }
  })

  // When showing a tab/collapse/any other element which is `display:none`, check to see if any render components inside
  $doc.on('shown.bs.tab shown.bs.collapse', function (event) {
    var $target = $(event.target)

    // BS tabs treats visible tab as link rather than panel, so let's adjust that...
    if (!$target.is('[role="tabpane"], .collapse')) {
      var $actualTarget = $($target.attr('data-target') || $target.attr('href'))
      if ($actualTarget.length > 0) $target = $actualTarget
    }

    // Trigger the updateWindow since the collapse/tab content may cause widths/heights to change
    debounceUpdateWindow()

    // Child components bind to the `UI:visible` event in order to render themselves when their parent is visible (i.e. not `display:none`). This is used primarily for items which require "physical" space to render, like maps and charts
    $target.trigger('UI:visible')
  })

  /*
   * Project Single Fixed Menu
   */
  var projectSingleNavOffsetTop = 0
  var $projectSingleMenu = $('.project-single-menu')

  function updateProjectSingleNavOffsetTop () {
    if ($projectSingleMenu.length > 0) {
      projectSingleNavOffsetTop = $('.project-single-content .project-single-nav').first().offset().top - (parseInt($siteHeader.height(), 10) * 0.5)
    } else {
      projectSingleNavOffsetTop = undefined
    }
  }

  // Add to window WatchScroll watcher means to make project-single-menu fixed
  if ($projectSingleMenu.length > 0) {
    updateProjectSingleNavOffsetTop()
    watchWindow
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
    $('.ui-project-single-map-toggle .label').text(__.__('View map', 'projectSingleMapShowLabel'))

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
   * Sticky things
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
      var siteHeaderHeight = $siteHeader.outerHeight()
      var winScrollTop = $win.scrollTop()
      var startInfoFixed = $projectSingleInfoWrap.offset().top + parseFloat($projectSingleInfoPos.css('margin-top')) - siteHeaderHeight - bufferTop
      var infoHeight = $projectSingleInfo.outerHeight()
      var endInfoFixed = $siteFooter.offset().top - infoHeight - siteHeaderHeight - bufferTop - bufferBottom
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

  // Debounce update to reduce jank
  if ($projectSingleInfoWrap.length > 0) {
    watchWindow.watch(window, offsetProjectSingleInfo)
    offsetProjectSingleInfo()
  }

  /*
   * Sticky stuff
   */
  var STICKY_WATCHERS = []

  // This will go and update any stickied element on the page
  function updateStickyWatchers (hardUpdate) {
    $.each(STICKY_WATCHERS, function (i, sticky) {
      sticky.update(hardUpdate)
    })
  }

  /*
   * Sticky
   * @class
   */
  var Sticky = function (elem, options) {
    var self = this
    self.$elem = $(elem)
    if (self.$elem.length === 0) return false

    // Settings
    self.settings = $.extend({
      breakpoints: /md|lg/,
      bufferTop: 25,
      bufferBottom: 0,

      // The parent to detect scrollTop from
      scrollParent: window,
      bounds: undefined,

      // Fires before recalculating necessary values
      onbeforehardupdate: function () {
        // Ensure the (fixed) siteHeaderHeight modifies the buffers too
        var siteHeaderHeight = $siteHeader.outerHeight()
        this.track.buffer.top = this.settings.bufferTop + siteHeaderHeight
        this.track.buffer.bottom = this.track.buffer.top + this.settings.bufferBottom
      }
    },
    // Override with element's attribute settings
    ElementAttrsObject(elem, {
      bufferTop: 'data-sticky-buffertop',
      bufferBottom: 'data-sticky-bufferbottom',
      scrollParent: 'data-sticky-scrollparent',
      bounds: 'data-sticky-bounds'
    }),
    // Override with JS instantiation settings
    options)

    // Track
    self.track = {
      buffer: {
        top: 0,
        bottom: 0
      },
      scrollParent: {
        scroll: {
          top: 0,
          left: 0
        }
      },
      bounds: {
        height: 0,
        offset: {
          top: 0,
          left: 0
        }
      },
      elem: {
        height: 0
      },
      sticky: {
        start: 0,
        end: 0,
        amount: 0,
        offset: 0
      }
    }

    // Elements

    // @todo Support non-window scroll parent elements (if necessary -- pretty tricky with managing offsets with nested scrollTop values)
    self.$scrollParent = $(self.settings.scrollParent || window)

    // Bounds element sets the area that the sticky can stick into
    if (!self.settings.bounds || $(self.settings.bounds).length === 0) {
      self.$elem.parents().each(function (i, parent) {
        var $parent = $(parent)
        var posType = $parent.css('position')
        if ($parent.is('.ui-sticky-bounds') || posType === 'relative') {
          self.settings.bounds = parent
          return false
        }
      })

      // Still no bounds? Use the scrollParent
      if (!self.settings.bounds) self.settings.bounds = self.settings.scrollParent
    }
    self.$bounds = $(self.settings.bounds)

    // Methods

    /*
     * Offset the element by margin
     *
     * @method _offsetMargin
     * @param {Mixed} amount The {Int} pixel amount to offset by, or {Boolean} false to remove any CSS offset
     * @return {Void}
     */
    self._offsetMargin = function (amount) {
      if (amount !== false) {
        self.$elem.css('marginTop', amount + 'px')
      } else {
        self.$elem.css('marginTop', '')
      }
    }

    /*
     * Offset the element by CSS3 transform
     *
     * @method _offsetTransform
     * @param {Mixed} amount The {Int} pixel amount to offset by, or {Boolean} false to remove any CSS offset
     * @return {Void}
     */
    self._offsetTransform = function (amount) {
      if (amount !== false) {
        self.$elem.css('transform', 'translateY(' + amount + 'px)')
      } else {
        self.$elem.css('transform', '')
      }
    }

    /*
     * Offset the element (chooses from either _offsetMargin or _offsetTransform depending on
     * the device's capabilities)
     *
     * @method offset
     * @param {Mixed} amount The amount to offset by, or {Boolean} false to remove any CSS offset
     * @return {Void}
     */
    if ($html.is('.has-csstransforms')) {
      self.offset = self._offsetTransform
    } else {
      self.offset = self._offsetMargin
    }

    /*
     * Update the element (and calculate the necessary offsets)
     *
     * @method update
     * @param {Boolean} hardUpdate Whether to update all the values before calculating the offset
     * @return {Void}
     */
    self.update = function (hardUpdate) {
      if (Utility.isBreakpointActive(self.settings.breakpoints)) {
        // Hard Update recalculates all the main values
        if (hardUpdate) {
          // Update bounds values
          self.track.bounds.offset = self.$bounds.offset()
          self.track.bounds.height = self.$bounds.outerHeight()

          // Update elem values
          self.track.elem.height = self.$elem.outerHeight()

          // Set the buffer top/bottom
          self.track.buffer.top = self.settings.bufferTop
          self.track.buffer.bottom = self.settings.bufferBottom

          // Run function before hardupdate
          if (self.settings.onbeforehardupdate) self.settings.onbeforehardupdate.call(self)

          // Figure out sticky start/end
          self.track.sticky.start = self.track.bounds.offset.top - self.track.buffer.top
          self.track.sticky.end = self.track.bounds.offset.top + self.track.bounds.height - self.track.elem.height - self.track.buffer.bottom
        }

        // Calculate the offset amount based on parent's scrollTop
        self.track.scrollParent.scroll.top = self.$scrollParent.scrollTop()
        self.track.sticky.amount = self.track.scrollParent.scroll.top - self.track.sticky.start

        // Constrain within the sticky's start/end
        if (self.track.scrollParent.scroll.top > self.track.sticky.start) {
          if (self.track.scrollParent.scroll.top < self.track.sticky.end) {
            self.track.sticky.offset = self.track.sticky.amount
          } else {
            self.track.sticky.offset = self.track.sticky.end - self.track.sticky.start
          }

          // Apply the offset
          // console.log('apply offset', self.track.sticky.offset)
          self.offset(self.track.sticky.offset)

        // Reset the offset
        } else {
          // console.log('reset offset')
          self.offset(false)
        }

        // @debug
        // console.log('Sticky.update %s', (hardUpdate ? '(hard)' : ''), self.track.sticky.offset, self.track)
      } else {
        self.offset(false)
      }
    }

    // Initialise
    self.$elem.addClass('ui-sticky')
    self.$elem[0].Sticky = self
    STICKY_WATCHERS.push(self)

    // Don't forget to update yerself before you try to stick yerself!
    self.update(1)

    return self
  }

  /*
   * jQuery Plugin for Sticky
   */
  $.fn.uiSticky = function (op) {
    return this.each(function (i, elem) {
      if (!elem.hasOwnProperty('Sticky')) {
        new Sticky(elem, op)
      }
    })
  }

  // Instantiate any stickies
  $('[data-sticky], .ui-has-sticky').uiSticky()

  // Watch the window scroll
  watchWindow.watch(window, updateStickyWatchers)

  /*
   * Debug
   * @todo remove for production
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
    if ($('input#form-preter-address-is-correspondence').is(':checked')) {
      // Set required inputs to false
      $('#form-preter-fieldset-correspondence [data-formvalidation-required]').attr('data-formvalidation-required', false)
      // Hide the fieldset
      $('#form-preter-fieldset-correspondence').hide()

    } else {
      // Clear field values? Yeah, why not
      $('#form-preter-fieldset-correspondence').find('input, textarea, select').val('')
      // Set required inputs to true
      $('#form-preter-fieldset-correspondence [data-formvalidation-required]').attr('data-formvalidation-required', true)
      // Show the fieldset
      $('#form-preter-fieldset-correspondence').show()
    }
  }
  checkAddressIsCorrespondence()

  /*
   * Validate IBAN Input
   * @todo should be refactored out to own app component
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
   * Presenter un projet
   */
  var $projectExtraFilesElem = $('#form-project-create-finance-extrafiles')
  var $projectExtraFilesList = $('.form-project-create-extrafiles-list')
  var $projectExtraFilesToggle = $('input#form-project-create-extrafiles-toggle')
  var projectExtraFileTemplate = $('#form-project-create-extrafiles-template').html()

  // Toggle extra files
  $doc.on('change', 'input#form-project-create-extrafiles-toggle', function (event) {
    checkProjectHasExtraFiles()
  })

  function checkProjectHasExtraFiles () {
    if ($projectExtraFilesToggle.is(':checked')) {
      $projectExtraFilesElem.slideDown()
    } else {
      $projectExtraFilesElem.slideUp()
    }
  }

  // Add extra files item
  $doc.on(Utility.clickEvent, '.ui-form-project-create-extrafiles-add', function (event) {
    event.preventDefault()
    addProjectExtraFile()
  })

  function addProjectExtraFile () {
    var totalExtraFiles = $projectExtraFilesList.find('.file-upload-extra').length

    // Prepare the template
    template = projectExtraFileTemplate.replace(/__NUM__/g, totalExtraFiles - 1)

    // Make the new one and add to the list
    var $extraFile = $(template).appendTo($projectExtraFilesList)
    var $extraFileAttach = $extraFile.find('[data-fileattach], .ui-has-fileattach, .ui-fileattach')

    // Make sure the FileAttach behaviours are loaded
    if ($extraFileAttach.length > 0) $extraFileAttach.uiFileAttach()
  }

  // Show the collapse
  if ($projectExtraFilesElem.length > 0) {
    checkProjectHasExtraFiles()

    // Add one to prompt the user
    addProjectExtraFile()
  }

  // Esim
  // @note the following is a guide on how it could be done
  //       I'm not devving it next to you guys so I dunno what your thinking is
  var $projectEsim = $('.form-project-create .emprunter-sim-mini')
  var esimEstimate = {
    step: 0,
    amount: '10 000€',
    duration: '2 jours',
    monthly: '2 887 / 3 285€'
  }

  // Step 0: reset everything
  function setEsimStep0 () {
    // @debug
    // console.log('setEsimStep0')

    esimEstimate.step = 0

    // Ensure visible!
    $projectEsim.show()

    // Reset the outputs
    $projectEsim.find('#esim-output-amount').text('')
    $projectEsim.find('#esim-output-duration').text('')
    $projectEsim.find('#esim-output-monthly').text('')

    // Show the content & footer (displays a message about step 2)
    $projectEsim.find('.emprunter-sim-mini-content, footer').hide()
  }

  // Step 1a: Changed form values instigate AJAX
  function setEsimStep1a () {
    // @debug
    // console.log('setEsimStep1a')

    esimEstimate.step = 1

    // Ensure visible!
    $projectEsim.show()

    // Set the outputs
    $projectEsim.find('#esim-output-amount').text('...')
    $projectEsim.find('#esim-output-duration').text('...')

    // Hide the footer
    $projectEsim.find('footer').hide()

    // Show the content (what a person can expect to see from ajax)
    $projectEsim.find('.emprunter-sim-mini-content').show()

    // @todo Do whatever AJAX you need to get and show the esimEstimate
    //       monthly value using the setEsimValues1b function, e.g.
    // var simData = {
    //   amount: $('#form-project-create-amount').val(),
    //   duration: $('#form-project-create-duration').val()
    // }
    // if (simData.amount && simData.duration) {
    //   $.ajax( ??? ).then(function (data) {
    //     esimEstimate = data
    //     setEsimStep1b()
    //   })
    // }

    // @debug
    setTimeout(function () {
      setEsimStep1b()
    }, 1000)
  }

  // Step 1b: AJAX then populated into sim
  function setEsimStep1b () {
    // Don't do anything if it isn't visible (not visible for xs/sm breakpoints)
    if (!$projectEsim.is(':visible')) return

    // @debug
    // console.log('setEsimStep1b')

    esimEstimate.step = 1.1

    // Ensure visible!
    $projectEsim.show()

    // Set the outputs
    $projectEsim.find('#esim-output-amount').text(esimEstimate.amount)
    $projectEsim.find('#esim-output-duration').text(esimEstimate.duration)

    // Show the content & footer (displays a message about step 2)
    $projectEsim.find('.emprunter-sim-mini-content, footer').show()
  }

  function setEsimStep2a () {
    // Don't do anything if it isn't visible (not visible for xs/sm breakpoints)
    if (!$projectEsim.is(':visible')) return

    // @debug
    // console.log('setEsimStep2a')

    esimEstimate.step = 2

    // Ensure visible!
    $projectEsim.show()

    // Set the outputs
    $projectEsim.find('#esim-output-monthly').text('...')

    // Hide the footer
    $projectEsim.find('footer').hide()

    // Hide the monthly fields
    $projectEsim.find('.emprunter-sim-mini-content, #esim-label-monthly, #esim-output-monthly').show()

    // @todo Do whatever AJAX you need to get the esimEstimate
    //       monthly value using the setEsimValues2b function, e.g.
    // var simData = {
    //   amount: $('#form-project-create-amount').val(),
    //   duration: $('#form-project-create-duration').val()
    // }
    // if (simData.amount && simData.duration) {
    //   $.ajax( ??? ).then(function (data) {
    //     esimEstimate = data
    //     setEsimStep2b()
    //   })
    // }

    // @debug
    setTimeout(function () {
      setEsimStep2b()
    }, 1000)
  }

  function setEsimStep2b () {
    // Don't do anything if it isn't visible (not visible for xs/sm breakpoints)
    if (!$projectEsim.is(':visible')) return

    // @debug
    // console.log('setEsimStep2b')

    esimEstimate.step = 2.1

    // Ensure visible!
    $projectEsim.show()

    // Set the outputs
    $projectEsim.find('#esim-output-monthly').text(esimEstimate.monthly)

    // Hide the footer
    $projectEsim.find('footer').hide()

    // Show the monthly fields
    $projectEsim.find('.emprunter-sim-mini-content, #esim-label-monthly, #esim-output-monthly').show()
  }

  function setEsimStep3 () {
    // Don't do anything if it isn't visible (not visible for xs/sm breakpoints)
    if (!$projectEsim.is(':visible')) return

    // @debug
    // console.log('setEsimStep3')

    esimEstimate.step = 3

    // Ensure hidden!
    $projectEsim.hide()
  }

  if ($projectEsim.length > 0) {
    setEsimStep0()

    // Tie into the tab events to show/fire esim stuff
    $doc.on(Utility.clickEvent + ' show.bs.tab', '.form-project-create [data-toggle="tab"], .form-project-create [role="tab"]', function (event) {
      var $tab = $(this)
      var tabId = $tab.attr('href') || $tab.attr('data-target') || '#' + $tab.attr('id')

      // Don't do anything if it isn't visible (not visible for xs/sm breakpoints)
      if ($projectEsim.is(':visible')) {
        if (tabId === '#form-project-create-1') {
          setEsimStep1a()

        } else if (tabId === '#form-project-create-2') {
          if (esimEstimate.step < 1.1) {
            setEsimStep1a()
            event.stopPropagation()
            event.preventDefault()
            return false
          }
          setEsimStep2a()

        } else if (tabId === '#form-project-create-3') {
          setEsimStep3()
        }
      }
    })

    // Change value which fires setEsimStep1b
    $doc
      .on('change', '#form-project-create-amount, #form-project-create-duration', function (event) {
        // @debug
        setTimeout(function () {
          setEsimStep1b()
        }, 1000)
      })
  }

  /*
   * Custom Input Duration
   * User can click/drag around to select the range
   */
  $doc
    .on('mousedown touchstart', '.custom-input-duration', function (event) {
      $(this).addClass('ui-interact-is-down')
    })
    .on('mouseup touchend', '.custom-input-duration', function (event) {
      $(this).removeClass('ui-interact-is-down')
    })
    .on('mousemove touchmove', '.custom-input-duration', function (event) {
      // Only do when interaction is down
      if ($(this).is('.ui-interact-is-down')) {
        $(event.target).closest('label').first().click()
      }
    })

  /*
   * Packery
   * @note was used for dashboard panels, but wasn't ideal
   * (didn't work with jquery-ui sortable)
   */
  // $('[data-packery]').each(function (i, elem) {
  //   var $elem = $(elem)
  //   var elemOptions = JSON.parse($elem.attr('data-packery') || '{}')

  //   // @debug
  //   // console.log('data-packery options', elem, elemOptions)

  //   $elem.packery(elemOptions)

  //   // Draggable items
  //   if ($elem.find('.draggable, [data-draggable]').length > 0) {
  //     $elem.find('.draggable, [data-draggable]').each(function (j, item) {
  //       var itemOptions = JSON.parse($(item).attr('data-draggable') || '{}')

  //       // Special case
  //       if ($(item).is('.dashboard-panel')) {
  //         itemOptions.handle = '.dashboard-panel-title'
  //         itemOptions.containment = true
  //       }

  //       var draggie = new Draggabilly(item, itemOptions)
  //       $elem.packery('bindDraggabillyEvents', draggie)
  //     })
  //   }
  // })

  /*
   * User Preter Notifications popup
   */
  var $profileNotificationsToggle = $('.site-header .site-user a.profile-notifications')
  var profileNotificationsDrop = undefined
  if ($profileNotificationsToggle.length > 0) {
    profileNotificationsDrop = new Drop({
      target: $profileNotificationsToggle[0],
      content: '',
      classes: 'drop-profile-notifications',
      position: 'bottom center',
      openOn: 'click',
      tetherOptions: {
        attachment: 'top right',
        targetAttachment: 'bottom center',
        offset: '-15px -25px'
      }
    })
    var profileNotificationsItems = []

    $doc
      // Stop link from going to page on md/lg responsive
      .on(Utility.clickEvent, '.site-user a.profile-notifications[href]', function (event) {
        // Show/hide of drop is handled with "openOn: click" above
        // This behaviour below is if user on mobile/tablet
        if (/(xs|mobile)/.test(currentBreakpoint)) {
          event.preventDefault()
          event.stopPropagation()
          window.location = $(this).attr('href')
          return false
        }
      })

    // HTML Templates
    var profileNotificationsTemplates = {
      frame: '<div class="profile-notifications">\
        <ul class="profile-notifications-list list-notifications">{{ listItems }}</ul>\
      </div>',
      list: '<ul class="profile-notifications-list list-notifications">{{ listItems }}</ul>',
      listItem: '<li class="notification notification-type-{{ type }} ui-notification-status-{{ status }}">\
        <header class="notification-header">\
          <h5 class="notification-datetime">{{ datetime }}</h5>\
          <h4 class="notification-title">{{ title }}</h4>\
          <div class="notification-image">{{ image }}</div>\
        </header>\
        <div class="notification-content">\
          <p>{{ content }}</p>\
        </div>\
      </li>',
      emptyItem: '<li class="notification notification-type-empty">\
        <div class="notification-content">\
          <p>{{ profileNotificationsEmptyLabel }}</p>\
        </div>\
      </li>'
    }

    function buildProfileNotificationsContent (notifications, pushOrReplace) {
      // Compiled list items to render
      var notificationsHTML = []

      // Get any notifications on the page to populate
      // Reason why I have this is to pick up any other notifications on the page and collate them all here first before building the element
      if (notifications === undefined) {
        var $notifications = $('#profile-notifications .notification.ui-notification-status-unread')
        notifications = []

        // Build the object from the element
        $notifications.each(function (i, notification) {
          var $n = $(notification)
          var notificationObj = {
            type: $n[0].className.replace(/^.*notification-type-([^ ]+).*/, '$1') || 'default',
            status: $n[0].className.replace(/^.*notification-status-([^ ]+).*$/, '$1') || 'unread',
            datetime: $n.find('.notification-datetime').html(),
            title: $n.find('.notification-title').html(),
            image: $n.find('.notification-image').html(),
            content: $n.find('.notification-content').html()
          }
          notifications.push(notificationObj)
        })

        // console.log('buildProfileNotificationsContent auto-detected notifications', notifications)
      }

      // Show empty
      if (notifications instanceof Array && notifications.length === 0 && !pushOrReplace) {
        pushOrReplace = false // Replace
        notificationsHTML.push(Templating.replace(profileNotificationsTemplates.emptyItem, [__]))

      // Render the notifications to the list
      // This script assumes notifications is ordered by most recent first
      } else {
        $.each(notifications, function (i, notification) {
          // Requires notifications to be structured as such:
          /*
          {
            type: 'offer-rejected',
            status: 'unread',
            datetime: 'Il y a 5 minutes',
            title: 'Offre refusée',
            content: 'Votre offre de prêt de 45€ au taux de 9,4% à Garage Toniol a été refusée.',
            image: '<svg role="img" title="Offre refusée" width="100" height="100" preserveAspectRatio="" class="svg-icon svg-file-notification-offer-rejected" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve"><use xlink:href="/media/svg/icons.svg#notification-offer-rejected" class="svg-file-notification-offer-rejected"></use></svg>'
          }
          */
          notificationsHTML.push(Templating.replace(profileNotificationsTemplates.listItem, [notification, __]))
        })
      }

      // console.log('buildProfileNotificationsContent notificationsHTML', notificationsHTML)

      // No notifications to render, don't do anything
      if (notificationsHTML.length === 0) return

      // Push new items
      if (pushOrReplace) {
        // console.log('buildProfileNotificationsContent pushing new items')
        profileNotificationsItems = notificationsHTML.concat(profileNotificationsItems)

      // Replace with new items
      } else {
        // console.log('buildProfileNotificationsContent replacing items')
        profileNotificationsItems = notificationsHTML
      }

      // Change the content in the drop
      profileNotificationsDrop.content.innerHTML = Templating.replace(profileNotificationsTemplates.list, {
        listItems: profileNotificationsItems.join('')
      })

      // Update the drop's position if it's open and new content was applied
      if ($body.is('.drop-open')) {
        profileNotificationsDrop.position()
      }

      // console.log('buildProfileNotificationsContent update drop content', profileNotificationsDrop.content)
    }

    // Show the profile notifications drop
    function showProfileNotifications (notifications, pushOrReplace) {
      buildProfileNotificationsContent(notifications, pushOrReplace)
      profileNotificationsDrop.open()
    }

    // Hide the profile notifications drop
    function hideProfileNotifications () {
      profileNotificationsDrop.close()
    }

    // Initialise any profile notifications into the drop on ready
    if ($('.list-notifications .notification').length > 0) {
      buildProfileNotificationsContent()
    }
  }

  /*
   * Movable content area
   * Any [data-draggable] elements within this element can be dragged and sorted
   * @note requires jquery-ui Draggable and Sortable modules
   */
  $('[data-movable-content]').each(function (i, elem) {
    var $elem = $(elem)

    // Enable sorting
    $elem.sortable(/*{
      revert: true
    }*/)

    // Enable dragging on each element within
    $elem.find('[data-draggable]').each(function (j, dragElem) {
      var $dragElem = $(dragElem)

      // Options per elem
      var draggableOptions = {
        connectToSortable: $elem,
        containment: $elem
      }

      // Set the dashboard panel title as the handle
      if (Utility.checkElemIsOrHasParent($dragElem, '.dashboard-panel')) {
        draggableOptions.handle = '.dashboard-panel-title'
      }

      // @debug
      // console.log('draggable', draggableOptions)
      $dragElem.draggable(draggableOptions)
    })
  })

  /*
   * User Preter Balance
   */
  $doc.on('change', '#balance-payment-cb-toggle, #balance-payment-transfer-toggle', function (event) {
    var $elem = $(this)
    var $cb = $('.balance-payment-cb')
    var $transfer = $('.balance-payment-transfer')

    // Avoid bubbling and default events because we don't want it to trigger anything else
    // as its purely for visual
    event.stopPropagation()
    event.preventDefault()

    // Show
    if ($elem.is('#balance-payment-cb-toggle')) {
      if ($elem.is(':checked')) {
        $transfer.hide()
        $cb.show()
      } else {
        $transfer.show()
        $cb.hide()
      }
    } else {
      if ($elem.is(':checked')) {
        $transfer.show()
        $cb.hide()
      } else {
        $transfer.hide()
        $cb.show()
      }
    }

    return false
  })

  // Technically these operations should be fired from a successful AJAX result
  function successBalanceDeposit() {
    $('#balance-deposit-2').collapse('show')
    Utility.scrollTo('#user-preter-balance')
  }

  function successBalanceWithdraw() {
    $('#balance-withdraw-2').collapse('show')
    Utility.scrollTo('#user-preter-balance')
  }

  // Temp implementation for front-end staging demo
  // @todo refactor to production-ready
  $doc.on('submit', '#form-balance-withdraw', function (event) {
    event.preventDefault()
    successBalanceWithdraw()
    return false
  })

  /*
   * User Preter Profile
   */
  // Show/hide the correspondence address
  $doc.on('change', 'input#form-profile-address-is-correspondence', function (event) {
    var $input = $(this)
    var $headerLabel = $('#profile-address-is-correspondence')
    var $panel = $('#panel-correspondence')

    if ($input.is(':checked')) {
      $headerLabel.removeClass('hide')
      $panel.collapse('hide')
    } else {
      $headerLabel.addClass('hide')
      $panel.collapse('show')
    }
  })

  /*
   * User Preter Operations
   */
  // Show/hide details
  $doc.on(Utility.clickEvent, '.table-myoperations-item[data-details]', function (event) {
    var $item = $(this)
    var $table = $item.parents('tbody').first()
    var $details = $table.find('.table-myoperations-details[data-parent="' + $item.attr('id') + '"]')
    event.preventDefault()

    // Hide details
    if ($item.is('.ui-operation-details-open')) {
      if ($details.length > 0) {
        $details.slideUp(200, function () {
          $item.removeClass('ui-operation-details-open')
        })
      } else {
        $item.removeClass('ui-operation-details-open')
      }

    // Show details
    } else {
      if ($details.length === 0) {
        // Get the details
        var details = Utility.convertStringToJson($item.attr('data-details'))
        var detailsItemsHtml = '';

        // Build the list of items
        $.each(details.items, function (i, item) {
          // @todo may need to programmatically change the currency here
          // @note this relies on the backend to supply the correcly translated text for labels
          var classItem = (item.value >= 0 ? 'ui-value-positive' : 'ui-value-negative')
          detailsItemsHtml += '<dt>' + item.label + '</dt><dd><span class="' + classItem + '">' + __.formatNumber(item.value, 2, true) + '€</span></dd>'
        })

        // Build element and add to DOM
        $details = $('<tr class="table-myoperations-details" data-parent="' + $item.attr('id') + '" style="display: none;"><td colspan="2">' + details.label + '</td><td colspan="3">' + detailsItemsHtml + '</td><td>&nbsp;</td></tr>')
        $item.after($details)
      }

      // Show
      $item.addClass('ui-operation-details-open')
      $details.slideDown(200)
    }
  })

  // Remove details before sorting
  $doc.on('Sortable:sort:before', 'table.table-myoperations', function (event, elemSortable, columnName, direction) {
    var $table = $(this)
    var $details = $table.find('.table-myoperations-details')

    // Find any details rows and remove them before the sorting occurs
    if ($details.length > 0) $details.remove()

    // Find any items which are "open" and remove the class
    $table.find('.ui-operation-details-open').removeClass('ui-operation-details-open')
  })

  /*
   * Copy to Clipboard
   */
  $('.btn-copy, [data-clipboard-target]').each(function (i, elem) {
    elem.Clipboard = new Clipboard(elem)
    elem.Clipboard.on('success', function (event) {
      $(event.trigger).html('<span class="icon fa-check"></span>')
    })
  })

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

  // Force a collapse open
  $doc.on(Utility.clickEvent, '.ui-collapse-set-open', function (event) {
    var $elem = $(this)
    var $target = $($elem.attr('target') || $elem.attr('html')).filter('.collapse')

    if ($target.length > 0) $target.collapse('show')
  })

  /*
   * Collapse Toggle Groups
   * Because bootstrap is quite opinionated and I don't need all their markup and specific classes
   * this here is a work-around to use the collapse within toggleable groups, i.e. accordians
   * minus all the reliance on certain element classes, e.g. `.panel`
   * To use this behaviour, the collapseable element must be within a `.ui-toggle-group`
   */
  $doc.on('show.bs.collapse', '.ui-toggle-group .collapse', function (event) {
    var $target = $(event.target)
    var $group = $target.parents('.ui-toggle-group').first()
    var $siblings = $target.siblings('.collapse')

    // @debug
    // console.log('ui-toggle-group hiding siblings')

    // This one is already showing, so hide the others via bootstrap
    $siblings.collapse('hide')
  })

  /*
   * Reveal an element on page init
   */
  if (window.location.hasOwnProperty('hash') && window.location.hash) {
    var $hash = $(window.location.hash)
    // Reveal the element
    if ($hash.length > 0) {
      Utility.revealElem($hash)
      Utility.scrollTo($hash)
    }
  }

  // Scroll to tab/collapse element
  // @note setting a link with class `.ui-reveal` or attribute `[data-ui-reveal]` enables this behaviour
  $doc.on(Utility.clickEvent, '.ui-reveal, [data-ui-reveal]', function (event) {
    var $elem = $(this)
    var targetSelector = Utility.checkSelector($elem.attr('data-target') || $elem.attr('href'))
    var $target

    // Error
    if (!targetSelector) return
    $target = $(targetSelector)

    // Reveal!
    if ($target.length > 0) {

      // Reveal the element
      Utility.revealElem($target)

      // Scroll to the target (will always just be the first)
      Utility.scrollTo($target)
    }
  })

  // Close/dismiss an element
  // @note setting a link with class `.ui-dismiss` or attribute `[data-ui-dismiss]` enables this behaviour
  $doc.on(Utility.clickEvent, '.ui-dismiss, [data-ui-dismiss]', function (event) {
    var $elem = $(this)
    var targetSelector = Utility.checkSelector($elem.attr('data-target') || $elem.attr('href'))
    var $target

    // Error
    if (!targetSelector) {
      // Test if this is within a message
      $target = $elem.parents('.message, .message-alert, .message-info, .message-success, .message-error').first()
    } else {
      $target = $(targetSelector)
    }

    if ($target && $target.length > 0) Utility.dismissElem($target)
  })

  // @debug
  $('#test-svg1').html(Utility.svgImage('#misc-computermapmarker', '', 300, 300))
  $('#test-svg2').html(Utility.svgImage('#header-fiscalite', '', 300, 300))
  $('#test-svg3').html(Utility.svgImage('#category-medical', '', 300, 300))

  // Perform on initialisation
  svg4everybody()
  debounceUpdateWindow()

  // Battle FOUT with a setTimeout! Not perfect...
  setTimeout(function () {
    debounceUpdateWindow()
  }, 1000)
})
