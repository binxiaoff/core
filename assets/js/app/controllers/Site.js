/*
 * Site UI Controller
 * Controls UI behaviours within the `site-` classed elements, like `site-header`, `site-mobile-header`, etc.
 */

var $ = require('jquery')
var Utility = require('Utility')
var Cleave = require('cleave.js')

var $doc = $(document)
var $html = $('html')
var $body = $('body')
var $win = $(window)

$('.amount').each(function () {
  var $element = $(this)
  initAmountCleave($element)
})

// -- Events
$doc
  .on('DOMNodeInserted', function(event) {
    var $amounts = $(event.target).find('.amount')
    $amounts.each(function (event) {
      var $element = $(this)
      initAmountCleave($element)
    })
  })

function initAmountCleave($element) {
  var amountClass = 'amount-' + Utility.randomString()
  $element.addClass(amountClass)
  var cleave = new Cleave('.' + amountClass, {numeral: true, delimiter: ' '})
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

/*
 * Site Mobile Menu
 */
function openSiteMobileMenu (forceShow) {
  // @debug console.log('openSiteMobileMenu')

  // Hide all (but...)
  // @trigger doc `Site:overlay:hideAll` [openOverlaySelector]
  $doc.trigger('Site:overlay:hideAll', ['.site-mobile-menu'])

  // Hide any other elements which could be obstructing this element
  $doc.trigger('UI:hideOthers')

  // Ensure always at top
  $('.site-mobile-menu').scrollTop(0)

  // Force show
  if (Utility.isIE(9) || Utility.isIE('<9') || forceShow) return showSiteMobileMenu()

  if (!$html.is('.ui-site-mobile-menu-open, .ui-site-mobile-menu-opening')) {
    $html.removeClass('ui-site-mobile-menu-closing').addClass('ui-site-mobile-menu-opening')
  }
}

function closeSiteMobileMenu (forceHide) {
  // @debug console.log('closeSiteMobileMenu')

  // Force hide
  if (Utility.isIE(9) || Utility.isIE('<9') || forceHide) return hideSiteMobileMenu()

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
 * Site User Mobile Menu
 */

function openSiteUserMobileMenu (forceShow) {
  // @debug console.log('openSiteUserMobileMenu')

  // Hide all (but...)
  // @trigger doc `Site:overlay:hideAll` [openOverlaySelector]
  $doc.trigger('Site:overlay:hideAll', ['.site-user-mobile-menu'])

  // Hide any other elements which could be obstructing this element
  $doc.trigger('UI:hideOthers')

  // Make sure always at top
  $('.site-user-mobile-menu').scrollTop(0)

  // Force show
  if (Utility.isIE(9) || Utility.isIE('<9') || forceShow) return showSiteUserMobileMenu()

  if (!$html.is('.ui-site-user-mobile-menu-open, .ui-site-user-mobile-menu-opening')) {
    $html.removeClass('ui-site-user-mobile-menu-closing').addClass('ui-site-user-mobile-menu-opening')
  }
}

function closeSiteUserMobileMenu (forceHide) {
  // @debug console.log('closeSiteUserMobileMenu')

  // Force hide
  if (Utility.isIE(9) || Utility.isIE('<9') || forceHide) return hideSiteUserMobileMenu()

  $html.removeClass('ui-site-user-mobile-menu-opening ui-site-user-mobile-menu-open').addClass('ui-site-user-mobile-menu-closing')
}

function showSiteUserMobileMenu () {
  // @debug console.log('showSiteUserMobileMenu')
  $html.addClass('ui-site-user-mobile-menu-open').removeClass('ui-site-user-mobile-menu-opening ui-site-user-mobile-menu-closing')

  // ARIA stuff
  $('.site-user-mobile-menu').removeAttr('aria-hidden')
  $('.site-user-mobile-menu[tabindex], .site-user-mobile-menu [tabindex]').attr('tabindex', 1)

  // Focus
  $('.site-user-mobile-menu').focus()
}

function hideSiteUserMobileMenu () {
  // @debug console.log('hideSiteUserMobileMenu')
  $html.removeClass('ui-site-user-mobile-menu-opening ui-site-user-mobile-menu-closing ui-site-user-mobile-menu-open')

  // ARIA stuff
  $('.site-user-mobile-menu').attr('aria-hidden', 'true')
  $('.site-user-mobile-menu[tabindex], .site-user-mobile-menu [tabindex]').attr('tabindex', -1)
}

// Show/close the site user mobile menu
$doc.on(Utility.clickEvent, '[data-site-user-menu-toggle]', function (event) {
  event.preventDefault()
  if ($html.is('.ui-site-user-mobile-menu-open')) {
    closeSiteUserMobileMenu()
  } else {
    openSiteUserMobileMenu()
  }
})

// Close the site user mobile menu (if open)
$doc.on(Utility.clickEvent, '.ui-site-user-mobile-menu-open .site', function (event) {
  closeSiteUserMobileMenu()
})

// At end of opening transition
$doc.on(Utility.transitionEndEvent, '.ui-site-user-mobile-menu-opening', function (event) {
  showSiteUserMobileMenu()
})

// At end of closing transition
$doc.on(Utility.transitionEndEvent, '.ui-site-user-mobile-menu-closing', function (event) {
  hideSiteUserMobileMenu()
})

// Hide site overlays
// @event `Site:overlay:hideAll` [overlaySelector]
$doc.on('Site:overlay:hideAll', function (event, overlaySelector) {
  // Needs to be a string to match the following
  if (!overlaySelector) overlaySelector = ''

  if (!overlaySelector.match('site-mobile-menu')) {
    closeSiteMobileMenu(true)
  }
  if (!overlaySelector.match('site-user-mobile-menu')) {
    closeSiteUserMobileMenu(true)
  }
})
