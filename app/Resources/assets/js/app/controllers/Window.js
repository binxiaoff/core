/*
 * Watch Scroll
 * This aims to batch all window scroll operations in one place.
 */

var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var WatchScroll = require('WatchScroll')
var Sticky = require('Sticky')

var $doc = $(document)
var $html = $('html')
var $body = $('body')
var $win = $(window)

// The watchWindow WatchScroll instance
var watchWindow = window.watchWindow = new WatchScroll.Watcher(window)

// Error
if (!watchWindow) {
  console.warn('Controller `WatchScroll.js` was not initialized in the correct order.')
  return
}

// Attach WatchScroll instance to watch the window's scrolling
watchWindow
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

// Watch the window scroll and update any Sticky elements
watchWindow.watch(window, Sticky.prototype._updateAllStickyWatchers)

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