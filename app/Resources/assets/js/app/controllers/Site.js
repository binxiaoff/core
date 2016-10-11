/*
 * Site UI Controller
 * Controls UI behaviours within the `site-` classed elements, like `site-header`, `site-mobile-header`, etc.
 */

// @TODO if AutoComplete is needed for site search, will have to target events to stop closing the site search input

var $ = require('jquery')
var Utility = require('Utility')

var $doc = $(document)
var $html = $('html')
var $body = $('body')
var $win = $(window)

// Site Search AutoComplete
// @TODO if needed, reimplement
// if ($('.site-header .site-search-input').length > 0) {
//   var siteSearchAutoComplete = new AutoComplete('.site-header .site-search-input', {
//     // @TODO eventually when AJAX is connected, the URL will go here
//     // ajaxUrl: '',
//     target: '#site-search-autocomplete',
//     useTether: true
//   })
// }

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

  // Dismiss site search after blur or special keypress (escape)
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

  // Stop site search dismissing when hover in autocomplete
  .on('mouseenter mouseover', '.site-search', function (event) {
    // @debug console.log('mouseenter mouseover', '.site-header .site-search .autocomplete a')
    cancelCloseSiteSearch()
  })

  // Stop site search dismissing when focus/active links in autocomplete
  .on('keydown focus active', '.site-search', function (event) {
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
    // siteSearchAutoComplete.hide()
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

/*
 * Site Mobile Menu
 */
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
  if (/xs|sm/.test(Utility.getActiveBreakpoints())) {
    // @debug console.log('openSiteMobileSearch')
    openSiteMobileSearch()
    $('.site-mobile-search-input').focus()

    // Regular site search
  } else {
    $('.site-search-input').focus()
  }
}

// Open the site-search from any element button
// Class any element `ui-open-site-search` and it can then pull focus and open the site search field
$doc.on(Utility.clickEvent, '.ui-open-site-search', function (event) {
  event.preventDefault()
  console.log('open site search')
  openSearch()
})
