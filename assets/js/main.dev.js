/*
 * Unilend JS
 * @linter Standard JS (http://standardjs.com/)
 */

/*
 * This is the main frontend "app" script. It sets up all the dependencies and configuration.
 * Ideally, this needs plenty of refactoring to remove any specific element/component/controller behaviours outside of it.
 * Due to the nature of the site functionality continuing to grow, it'd be great to refactor the architecture of the
 * frontend to use a better class inheritance architecture. So far I'm using prototype for shared properties/methods,
 * but it'd be great to introduce some ES6 class based inheritance (in fact, the Ember-style Mixin inheritance is really cool).
 *
 * I've "reactively" designed the architecture, so I did it as was needed at the time. Now it'd would be great to "actively"
 * re-engineer the architecture to better optimise the frontend app run-cycle, reduce any complexity and client-side lag,
 * and to just have better JS programming practices in place.
 *
 * -- Matt
 */

// Dependencies (some are Browserify aliases -- see package.json)
var $ = require('jquery') // Gets the global (see package.json)
var svg4everybody = require('svg4everybody')
var Swiper = require('Swiper')
var raf = require('raf')
var Clipboard = require('clipboard')
var Tether = require('tether')
var Drop = require('tether-drop')
var SortableJS = require('sortablejs')

// Browserify alias (see package.json)

// Bootstrap -- browserify aliases (see package.json)
require('bs.transition')
require('bs.tab')
require('bs.tooltip')
require('bs.collapse')

// Unilend Lib
var Utility = require('Utility')
var __ = require('__')
var Tween = require('Tween')
var ElementBounds = require('ElementBounds')
var ElementAttrsObject = require('ElementAttrsObject')
var CacheData = require('CacheData')
var Templating = require('Templating')

// Unilend Components
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
var CacheForm = require('CacheForm')
var ChartView = require('ChartView')
var Spinner = require('Spinner')
var SpinnerButton = require('SpinnerButton')
var Modal = require('Modal')
var ModalServiceTerms = require('./app/components/ModalServiceTerms')
var Paginate = require('./app/components/Paginate')
var DataTable = require('./app/components/DataTable')
var Security = require('./app/components/Security')
var LendingRate = require('./app/components/LendingRate')
var CollectionForm = require('./app/components/CollectionForm')
// @debug
// CacheData.clearAll()

/*
 * Globals
 */
// Modernizr
var Modernizr = window.Modernizr

// Track the current breakpoints (also updated in updateWindow())
var currentBreakpoint = window.currentBreakpoint = Utility.getActiveBreakpoints()

// Main vars/elements
var $doc = $(document)
var $html = $('html')
var $body = $('body')
var $win = $(window)

$doc.ready(function ($) {
  /*
 * Unilend Controllers
 * The order is very important
 */
require('./app/controllers/Window')
require('./app/controllers/Site')
require('./app/controllers/Fancybox')
require('./app/controllers/Pikaday')
require('./app/controllers/Collapses')
require('./app/controllers/ToggleGroup')
require('./app/controllers/FieldSanitisation')
require('./app/controllers/NewPasswordRequest')
require('./app/controllers/Projects')

  // @debug
  // window.__ = __
  // window.Utility = Utility
  // window.CacheForm = CacheForm

  // Remove HTML
  $html.removeClass('no-js')

  // Detect Safari
  if (navigator.userAgent.indexOf('Safari') > -1 && navigator.userAgent.indexOf('Chrome') == -1) {
    $body.addClass('ua-safari')
  }

  // TWBS setup
  // @todo refactor into General controller
  // $.support.transition = false
  // Bootstrap Tooltips
  $body.tooltip({
    selector: '.ui-has-tooltip, [data-toggle="tooltip"]',
    container: 'body'
  })

  // Basic jQuery plugin to get the element's tagName or type of input element
  $.fn.getType = function () {
    return (this[0].tagName === 'INPUT' ? this[0].type : this[0].tagName).toLowerCase()
  }

  $('.select2').select2({
    minimumResultsForSearch: 4
  })

  /*
   * Tabs
   * @todo refactor into Tabs.js controller
   */
  // Any tabs areas with `.ui-tabs-progress` class will add a `.complete` class to the tabs before
  $doc.on('shown.bs.tab', '.tabs.ui-tabs-progress', function (event) {
    var $target = $(event.target)
    var $tab = $('.nav a[role="tab"][href="' + $target.attr('href') + '"]').first()
    var $nav = $tab.parents('.nav')
    var $tabs = $nav.find('a[role="tab"]')
    var tabIndex = $tabs.index($tab)

    if (tabIndex >= 0) {
      $tabs.filter(':gt(' + tabIndex + ')').parents('li').removeClass('active complete')
      $tabs.filter(':lt(' + tabIndex + ')').parents('li').removeClass('active').addClass('complete')
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
   * Smooth scrolling to point on screen or specific element
   * @todo refactor into General controller
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
  // @todo refactor into Tabs/Collapses controller
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

  /*
   * Ajouter des fichiers
   * @todo finish ExtraFiles.js component and remove this legacy code
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

  function removeFormExtraFile (elem) {
    var $extraFile = $(elem)

    // No element found
    if ($extraFile.length === 0) return

    // Remove it
    $extraFile.remove()

    // Add an empty one if list is completely empty
    if ($extraFilesList.find('.file-upload-extra').length === 0) {
      addFormExtraFile()
    }
  }

  // Show the collapse
  if ($extraFilesElem.length > 0) {
    checkFormHasExtraFiles()

    // Add one to prompt the user
    addFormExtraFile()
  }

  // Remove the extra file if the `.ui-fileattach` element was removed
  $doc.on('FileAttach:removed', '.file-upload-extra .ui-fileattach', function (event) {
    var $extraFile = $(this).parents('.file-upload-extra')
    removeFormExtraFile($extraFile)
  })

  // Remove the extra file field
  $doc.on(Utility.clickEvent, '.file-upload-extra .ui-extrafiles-removefile', function (event) {
    var $extraFile = $(this).parents('.file-upload-extra')
    removeFormExtraFile($extraFile)
  })

  /*
   * Copy to Clipboard
   * @todo refactor into General controller
   */
  $('.btn-copy, [data-clipboard-target]').each(function (i, elem) {
    elem.Clipboard = new Clipboard(elem)
    elem.Clipboard.on('success', function (event) {
      $(event.trigger).html('<span class="icon fa-check"></span>')
    })
  })

  /*
   * Show print dialog
   * @todo refactor into General controller
   */
  $doc.on(Utility.clickEvent, '.btn-print, .ui-print', function (event) {
    event.preventDefault()
    window.print()
  })

  /*
   * Reveal an element on page init
   * @todo refactor into General controller
   */
  if (window.location.hasOwnProperty('hash') && window.location.hash) {
    var $hash = $(window.location.hash)
    // Reveal the element
    if ($hash.length > 0) {
      Utility.revealElem($hash)
      Utility.scrollTo($hash)
    }
  }

  // Reveal element by clicking special element
  // @note setting a link with class `.ui-reveal` or attribute `[data-ui-reveal]` enables this behaviour
  // @todo refactor into General controller
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

  // Close/dismiss an element by clicking a special element
  // @note setting a link with class `.ui-dismiss` or attribute `[data-ui-dismiss]` enables this behaviour
  // @todo refactor into General controller
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
  // @todo refactor into General controller
  $doc.on(Utility.clickEvent, '.ui-toggle, [data-ui-toggle]', function (event) {
    var $elem = $(this)
    var targetSelector = Utility.checkSelector($elem.attr('data-target') || $elem.attr('href'))
    var $target = $(targetSelector)

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
