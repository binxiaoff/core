/*
 * Unilend Nav Dropdown Menu
 * @note This component is structured differently to others as it doesn't need to manage much aside from class and some custom events
 * @note of course it doesn't *have* to be styled as a dropdown, it can be whatever...
 */

// Dependencies
var $ = require('jquery')
var Utility = require('Utility')

/*
 * Private functions
 */

// Test to see if dropdown menu functionality applies
// @returns {Mixed} {Boolean} false if doesn't apply or {jQueryObject} of the menu element
function testNavDropdownMenu(elem) {
  var $menu = Utility.getElemIsOrHasParent(elem, '[data-nav-dropdown-menu]')
  if ($menu.length === 0) return false

  // Get any breakpoints functionality is restricted to
  var applyToBp = $menu.attr('data-nav-dropdown-menu')

  // Breakpoint restrictions in place
  if (applyToBp) {
    // Breakpoint specified in the attribute is currently active
    if (new RegExp(applyToBp.trim().replace(/\s+/g, '|'), 'i').test(Utility.getActiveBreakpoints())) {
      return $menu

    // No matching breakpoint specified
    } else {
      return false
    }

  // No restrictions, it can do as it pleases
  } else {
    return $menu
  }
}

// Show the dropdown menu
function showNavDropdownMenu(elem) {
  var $menu = testNavDropdownMenu(elem)
  if (!$menu || $menu.length === 0) return

  $menu.addClass('ui-nav-dropdown-menu-open')

  // Focus the active item
  $menu.find('.active').first().focus()

  // @trigger elem `DropdownMenu:shown`
  $menu.trigger('NavDropdownMenu:shown')
}

// Hide the dropdown menu
function hideNavDropdownMenu(elem) {
  var $menu = testNavDropdownMenu(elem)
  if (!$menu || $menu.length === 0) return

  $menu.removeClass('ui-nav-dropdown-menu-open')

  // @trigger elem `DropdownMenu:hidden`
  $menu.trigger('NavDropdownMenu:hidden')
}

// Toggle the dropdown menu
function toggleNavDropdownMenu(elem) {
  var $menu = testNavDropdownMenu(elem)
  if (!$menu || $menu.length === 0) return

  // Hide
  if ($menu.is('.ui-nav-dropdown-menu-open')) {
    hideNavDropdownMenu($menu)

  // Show
  } else {
    showNavDropdownMenu($menu)
  }
}

/*
 * jQuery Events (effectively public functions latched to UI elements)
 */
$(document)
  // Show/hide dropdown menu
  .on(Utility.clickEvent, '[data-nav-dropdown-menu]', function (event) {
    toggleNavDropdownMenu(this)
  })

  .on('focus active', '[data-nav-dropdown-menu]', function (event) {
    showNavDropdownMenu(this)
  })

  // Focus/active anchor in menu shows menu
  // .on('focus active', '[data-nav-dropdown-menu] a', function (event) {
  //   var $target = $(this)
  //   var $menu = Utility.getElemIsOrHasParent(this, '[data-nav-dropdown-menu]')
  //   showNavDropdownMenu($menu)
  // })

  // Direct public function triggers
  // Show dropdown menu via event trigger
  .on('NavDropdownMenu:show', '[data-nav-dropdown-menu]', function (event) {
    showNavDropdownMenu(this)
  })

  // Hide dropdown menu via event trigger
  .on('NavDropdownMenu:hide', '[data-nav-dropdown-menu]', function (event) {
    hideNavDropdownMenu(this)
  })

  // Toggle dropdown menu via event trigger
  .on('NavDropdownMenu:toggle', '[data-nav-dropdown-menu]', function (event) {
    toggleNavDropdownMenu(this)
  })
