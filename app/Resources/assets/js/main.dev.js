/*
 * Unilend JS
 * @linter Standard JS (http://standardjs.com/)
 */

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
var SortableJS = require('sortablejs')

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

// Components
var UserNotifications = require('UserNotifications')
var UserNotificationsList = require('UserNotificationsList')
var UserNotificationsDrop = require('UserNotificationsDrop')
var AutoComplete = require('AutoComplete')
var WatchScroll = require('WatchScroll')
var TextCount = require('TextCount')
var TimeCount = require('TimeCount')
var Sortable = require('Sortable')
var PasswordCheck = require('PasswordCheck')
var FileAttach = require('FileAttach')
var FormValidation = require('FormValidation')
var DashboardPanel = require('DashboardPanel')
var DashboardPanels = require('DashboardPanels')
var CacheForm = require('CacheForm')
var AutolendTable = require('AutolendTable')
var NavDropdownMenu = require('NavDropdownMenu')
var MapView = require('MapView')
var ChartView = require('ChartView')
var Sticky = require('Sticky')
var Spinner = require('./app/components/Spinner')
var Modal = require('./app/components/Modal')
var ModalTOS = require('./app/components/ModalTOS')
var CookieCheck = require('./app/components/Cookies')
var LoginTimer = require('./app/components/LoginTimer')
var LoginCaptcha = require('./app/components/LoginCaptcha')
var SimpleCountDown = require('./app/components/SimpleCountDown')
var BidConfirmation = require('./app/components/BidConfirmation')
var BidsDetail = require('./app/components/BidsDetail')

// Page controllers
// Control page-specific behaviours
require('./app/controllers/BorrowerOperation')
require('./app/controllers/NewPasswordRequest')
require('./app/controllers/LenderSubscription')
require('./app/controllers/LenderWallet')
require('./app/controllers/LenderOperations')
require('./app/controllers/LenderProfile')
require('./app/controllers/Projects')
require('./app/controllers/ProjectRequest')

// @debug
// CacheData.clearAll()

//
$(document).ready(function ($) {
  // Main vars/elements
  var $doc = $(document)
  var $html = $('html')
  var $body = $('body')
  var $win = $(window)
  var Modernizr = window.Modernizr
  raf.polyfill()

  // @debug
  // window.__ = __
  // window.Utility = Utility
  // window.CacheForm = CacheForm

  // Remove HTML
  $html.removeClass('no-js')

  // Track the current breakpoints (also updated in updateWindow())
  var currentBreakpoint = window.currentBreakpoint = Utility.getActiveBreakpoints()

  // TWBS setup
  // $.support.transition = false
  // Bootstrap Tooltips
  $('.ui-has-tooltip, [data-toggle="tooltip"]').tooltip()

  /*
   * jQuery UI Date Picker
   */
  // Set FR language in datepicker
  // @note For supporting other languages, see: https://github.com/jquery/jquery-ui/blob/master/ui/i18n/
  // @note I've inlined the language code since we are using browserify to compile code as it's a pain to do this kind of stuff in the Twig files
  if (/^fr/i.test($('html').attr('lang'))) {
    $.datepicker.regional.fr = {
      closeText: "Fermer",
      prevText: "Précédent",
      nextText: "Suivant",
      currentText: "Aujourd'hui",
      monthNames: ["janvier", "février", "mars", "avril", "mai", "juin",
        "juillet", "août", "septembre", "octobre", "novembre", "décembre"],
      monthNamesShort: ["janv.", "févr.", "mars", "avr.", "mai", "juin",
        "juil.", "août", "sept.", "oct.", "nov.", "déc."],
      dayNames: ["dimanche", "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi"],
      dayNamesShort: ["dim.", "lun.", "mar.", "mer.", "jeu.", "ven.", "sam."],
      dayNamesMin: ["D", "L", "M", "M", "J", "V", "S"],
      weekHeader: "Sem.",
      dateFormat: "dd/mm/yy",
      firstDay: 1,
      isRTL: false,
      showMonthAfterYear: false,
      yearSuffix: ""
    }
    $.datepicker.setDefaults($.datepicker.regional.fr)

  // Italian
  } else if (/^it/i.test($('html').attr('lang'))) {
    datepicker.regional.it = {
      closeText: "Chiudi",
      prevText: "&#x3C;Prec",
      nextText: "Succ&#x3E;",
      currentText: "Oggi",
      monthNames: [ "Gennaio","Febbraio","Marzo","Aprile","Maggio","Giugno",
        "Luglio","Agosto","Settembre","Ottobre","Novembre","Dicembre" ],
      monthNamesShort: [ "Gen","Feb","Mar","Apr","Mag","Giu",
        "Lug","Ago","Set","Ott","Nov","Dic" ],
      dayNames: [ "Domenica","Lunedì","Martedì","Mercoledì","Giovedì","Venerdì","Sabato" ],
      dayNamesShort: [ "Dom","Lun","Mar","Mer","Gio","Ven","Sab" ],
      dayNamesMin: [ "Do","Lu","Ma","Me","Gi","Ve","Sa" ],
      weekHeader: "Sm",
      dateFormat: "dd/mm/yy",
      firstDay: 1,
      isRTL: false,
      showMonthAfterYear: false,
      yearSuffix: ""
    }
    $.datepicker.setDefaults($.datepicker.regional.it)
  }

  // Initialise any datepicker inputs
  $('.ui-has-datepicker, [data-ui-datepicker]').datepicker({
    firstDay: 1,
    format: 'dd/mm/yy'
  })

  // VideoJS
  // Running a modified version to customise the placement of items in the control bar
  videojs.options.flash.swf = '/assets/js/vendor/videojs/video-js.swf' // @TODO needs correct link '/js/vendor/videojs/video-js.swf'

  // Site Search AutoComplete
  if ($('.site-header .site-search-input').length > 0) {
    var siteSearchAutoComplete = new AutoComplete('.site-header .site-search-input', {
      // @TODO eventually when AJAX is connected, the URL will go here
      // ajaxUrl: '',
      target: '#site-search-autocomplete',
      useTether: false
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
    if (Utility.isIE(9) || Utility.isIE('<9')) return showSiteMobileMenu()
    if (!$html.is('.ui-site-mobile-menu-open, .ui-site-mobile-menu-opening')) {
      $html.removeClass('ui-site-mobile-menu-closing').addClass('ui-site-mobile-menu-opening')
    }
  }

  function closeSiteMobileMenu () {
    if (Utility.isIE(9) || Utility.isIE('<9')) return hideSiteMobileMenu()
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

  /*
   * Watch Scroll
   * This aims to batch all window scroll operations in one place.
   */
  // Attach WatchScroll instance to watch the window's scrolling
  window.watchWindow = new WatchScroll.Watcher(window)
    // Fix site nav
    .watch(window, 'scrollTop>50', function () {
      if (!$html.is('.ui-site-header-fixed')) {
        // @debug
        // console.log('add ui-site-header-fixed')
        $html.addClass('ui-site-header-fixed')
        Utility.debounceUpdateWindow()
      }
    })

    // Unfix site nav
    .watch(window, 'scrollTop<=50', function () {
      if ($html.is('.ui-site-header-fixed')) {
        // @debug
        // console.log('remove ui-site-header-fixed')
        $html.removeClass('ui-site-header-fixed')
        Utility.debounceUpdateWindow()
      }
    })

    // Start text counters
    .watch('[data-textcount], .ui-has-textcount, .ui-textcount', 'enter', function () {
      // console.log('WatchScroll enter', this)
      $(this).uiTextCount('startCount')
    })

  // Dynamic watchers specified through view attributes (single action per element)
  // This enables setting basic watchscroll actions on elements, rather than assigning via JS
  // It's primarily used with the items which start text counts when the user has scrolled the element into the view
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
   * WatchScroll Nav
   * If item is visible (via WatchScroll action `enter`) then make the navigation item active
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
    .on('click', '#submit-step-1', function () {
      var period = $("input[id^='esim-input-duration-']:checked").val(),
          amount = $("#esim-input-amount").val(),
          motiveId = $("#esim-input-reason > option:selected").val()

      if (! $(".form-validation-notifications .message-error").length) {
        $.ajax({
          type: 'POST',
          url: '/simulateur-projet-etape1',
          data: {
            period: period,
            amount: amount,
            motiveId: motiveId
          },
          success: function(response) {
            // Show the continue button
            $('.emprunter-sim').addClass('ui-emprunter-sim-estimate-show')

            $(".ui-esim-output-cost").prepend(response.amount);
            $('.ui-esim-output-duration').prepend(response.period)
            $('.ui-esim-monthly-output').html(response.estimatedMonthlyRepayment)
            $('.ui-esim-interest-output').html(response.estimatedRate)

            if (!response.motiveSentenceComplementToBeDisplayed) {
              $('p[data-borrower-motive]').show()
              while ($('.ui-esim-output-duration')[0].nextSibling != null) {
                $('.ui-esim-output-duration')[0].nextSibling.remove()
              }
              $('#esim2 > fieldset > div:nth-child(2) > div > p:nth-child(1)').append('.')
            }
            else {
              var text = $('p[data-borrower-motive]').html()
                  text = text.replace(/\.$/g, '')

              $('p[data-borrower-motive]')
                .show()
                .html(text + response.translationComplement + '.')
            }
          },
          error: function() {
            console.log("error retrieving data");
          }
        });

        $('a[href*="esim1"]')
          .removeAttr("href data-toggle aria-expanded")
          .attr("nohref", "nohref")
      }
    })

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
    Utility.debounceUpdateWindow()

    // Child components bind to the `UI:visible` event in order to render themselves when their parent is visible (i.e. not `display:none`). This is used primarily for items which require "physical" space to render, like maps and charts
    $target.trigger('UI:visible')
  })

  // Watch the window scroll and update any Sticky elements
  watchWindow.watch(window, Sticky.prototype._updateAllStickyWatchers)

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
   * Ajouter des fichiers
   */
  var $extraFilesElem = $('#form-extrafiles')
  var $extraFilesList = $('.form-extrafiles-list')
  var $extraFilesToggle = $('input#form-extrafiles-toggle')
  var extraFileTemplate = $('#form-extrafiles-template').html()

  // Toggle extra files
  $doc.on('change', 'input#form-extrafiles-toggle', function (event) {
    checkFormHasExtraFiles()
  })

  function checkFormHasExtraFiles () {
    if ($extraFilesToggle.is(':checked')) {
      $extraFilesElem.slideDown()
    } else {
      $extraFilesElem.slideUp()
    }
  }

  // Add extra files item
  $doc.on(Utility.clickEvent, '.ui-form-extrafiles-add', function (event) {
    event.preventDefault()
    addFormExtraFile()
  })

  function addFormExtraFile () {
    var totalExtraFiles = $extraFilesList.find('.file-upload-extra').length

    // Prepare the template
    template = extraFileTemplate.replace(/__NUM__/g, totalExtraFiles - 1)

    // Make the new one and add to the list
    var $extraFile = $(template).appendTo($extraFilesList)
    var $extraFileAttach = $extraFile.find('[data-fileattach], .ui-has-fileattach, .ui-fileattach')

    // Make sure the FileAttach behaviours are loaded
    if ($extraFileAttach.length > 0) $extraFileAttach.uiFileAttach()
  }

  // Show the collapse
  if ($extraFilesElem.length > 0) {
    checkFormHasExtraFiles()

    // Add one to prompt the user
    addFormExtraFile()
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
    $projectEsim.find('.emprunter-sim-mini-header > p').hide()

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
    $projectEsim.find('.emprunter-sim-mini-header > p').hide()
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
   * Movable content area
   * Any [data-draggable] elements within this element can be dragged and sorted
   */
  $('[data-movable-content]').each(function (i, elem) {
    var $elem = $(elem)

    var sortablearea = SortableJS.create(elem, {
      handle: '.ui-draggable-handle',
      draggable: '.ui-draggable',
      ghostClass: 'ui-movablecontent-ghost',
      chosenClass: 'ui-movablecontent-chosen',
      forceFallback: true,
      fallbackClass: 'ui-movablecontent-fallback',
      scroll: true,
      scrollSensitivity: 100,
      scrollSpeed: 20,
      onUpdate: function (event) {
        // @trigger elem `MovableContent:sortupdate` [elemItemMoved]
        $elem.trigger('MovableContent:sortupdate', [event.item])

        // Trigger update on any elements which might have charts to re-render
        $(event.item).trigger('UI:update')
      }
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

  /*
   * User Borrower Operations
   */
  // Show/hide details
  $doc.on(Utility.clickEvent, '#user-emprunteur-operations .table-myoperations-item[data-details]', function (event) {
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
          detailsItemsHtml += '<dt><span class="cell-right" style="width:30%;display:block;">' + item.label + '</span></dt>' + '<dd><span class="' + classItem + '">' + __.formatNumber(item.value, 2, true) + '€</span></dd>'
        })

        // Build element and add to DOM
        $details = $('<tr class="table-myoperations-details" data-parent="'
            + $item.attr('id') + '" style="display: none;"><td colspan="4">' + detailsItemsHtml + '</td><td>&nbsp;</td></tr>')
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
   * Show print dialog
   */
  $doc.on(Utility.clickEvent, '.btn-print, .ui-print', function (event) {
    event.preventDefault()
    window.print()
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
   * To use this behaviour, the collapsable element must be within a `.ui-toggle-group`
   */
  $doc.on('show.bs.collapse', '.ui-toggle-group > .collapse', function (event) {
    var $target = $(event.target)

    // Only fire on direct descendants of the `.ui-toggle-group`
    if ($target.is('.ui-toggle-group > .collapse')) {
      // var $group = $target.parents('.ui-toggle-group').first()
      var $siblings = $target.siblings('.collapse')

      // @debug
      // console.log('ui-toggle-group hiding siblings', event.target, $siblings)

      // This one is already showing, so hide the others via bootstrap
      $siblings.collapse('hide')
    }
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

  // Toggle an element
  // @note setting a link with class `.ui-toggle` or attribute `[data-ui-toggle]` enables this behaviour
  $doc.on(Utility.clickEvent, '.ui-toggle, [data-ui-toggle]', function (event) {
    var $elem = $(this)
    var targetSelector = Utility.checkSelector($elem.attr('data-target') || $elem.attr('href'))
    var $target

    // If visible, dismiss
    if ($target.is(':visible')) {
      Utility.dismissElem(targetSelector)

    // If not (assume hidden), reveal
    } else {
      Utility.revealElem(targetSelector)
    }
  })

  // Perform on initialisation
  svg4everybody()
  Utility.debounceUpdateWindow()

  // Battle FOUT with a setTimeout! Not perfect...
  setTimeout(function () {
    Utility.debounceUpdateWindow()
  }, 1000)
})
