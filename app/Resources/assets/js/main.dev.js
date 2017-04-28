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
var videojs = require('videojs') // Gets the global (see package.json)
var svg4everybody = require('svg4everybody')
var Swiper = require('Swiper')
var Iban = require('iban')
var raf = require('raf')
var Clipboard = require('clipboard')
var Tether = require('tether')
var Drop = require('tether-drop')
var SortableJS = require('sortablejs')

// GSAP animation
require('gsap.jquery') // Browserify alias (see package.json)

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
var DashboardPanel = require('DashboardPanel')
var DashboardPanels = require('DashboardPanels')
var CacheForm = require('CacheForm')
var NavDropdownMenu = require('NavDropdownMenu')
var MapView = require('MapView')
var ChartView = require('ChartView')
var Sticky = require('Sticky')
var Spinner = require('Spinner')
var SpinnerButton = require('SpinnerButton')
var Modal = require('Modal')
var ModalTOS = require('./app/components/ModalTOS')
var CookieCheck = require('./app/components/Cookies')
var BidsDetail = require('./app/components/BidsDetail')
var ProgressBar = require('ProgressBar')

// @debug
// CacheData.clearAll()

/*
 * Globals
 */
// Modernizr
var Modernizr = window.Modernizr

// VideoJS
// Running a modified version to customise the placement of items in the control bar
videojs.options.flash.swf = '/assets/js/vendor/videojs/video-js.swf'

// Track the current breakpoints (also updated in updateWindow())
var currentBreakpoint = window.currentBreakpoint = Utility.getActiveBreakpoints()

// Main vars/elements
var $doc = $(document)
var $html = $('html')
var $body = $('body')
var $win = $(window)

/*
 * Unilend Controllers
 * The order is very important
 */
require('./app/controllers/Window')
require('./app/controllers/Site')
require('./app/controllers/Fancybox')
require('./app/controllers/Pikaday')
require('./app/controllers/Swipers')
require('./app/controllers/Login')
require('./app/controllers/Promos')
require('./app/controllers/NewPasswordRequest')
require('./app/controllers/BorrowerOperations')
require('./app/controllers/LenderSubscription')
require('./app/controllers/LenderDashboard')
require('./app/controllers/LenderWallet')
require('./app/controllers/LenderOperations')
require('./app/controllers/LenderProfile')
require('./app/controllers/Projects')
require('./app/controllers/BidConfirmation')
require('./app/controllers/ProjectRequest')
require('./app/controllers/Autolend')
require('./app/controllers/ProjectDetails')

//
$doc.ready(function ($) {
  // @debug
  // window.__ = __
  // window.Utility = Utility
  // window.CacheForm = CacheForm

  // Remove HTML
  $html.removeClass('no-js')

  // TWBS setup
  // @todo refactor into General controller
  // $.support.transition = false
  // Bootstrap Tooltips
  $body.tooltip({
    selector: '.ui-has-tooltip, [data-toggle="tooltip"]',
    container: 'body'
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
   * @todo refactor into separate component
   */
  $doc
  // Step 1
    .on('FormValidation:validate:error', '#esim1', function (event) {
      // Hide the continue button
      $('.emprunter-sim').removeClass('ui-emprunter-sim-estimate-show')
      event.stopPropagation()
    })
    .on('shown.bs.tab', '[href="#esim2"]', function () {
      var period = $("input[id^='esim-input-duration-']:checked").val()
      var amount = $("#esim-input-amount").val()
      var motiveId = $("#esim-input-reason > option:selected").val()

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

            $('#esim-input-siren').focus()

            $(".ui-esim-output-cost").prepend(response.amount);
            $('.ui-esim-output-duration').prepend(response.period)
            $('.ui-esim-funding-duration-output').html(response.estimatedFundingDuration)
            $('.ui-esim-monthly-output').html(response.estimatedMonthlyRepayment)

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

  // Partner Simulator
  $doc
    .on(Utility.clickEvent, '[data-form-submit]', function (event) {
      event.preventDefault()
      var $this = $(this)
      var $form = $($this.data('form-submit'))
      if ($this.is('.btn-abandon')) {
        var $modal = $('#modal-partner-prospect-abandon')
        $modal.uiModal('open')
        return false
      }
      $form.submit()
    })
    .on(Utility.clickEvent, '#modal-partner-prospect-abandon [data-modal-doactionsubmit]', function() {
      var $modal = $(this).closest('[data-modal]')
      var $form = $($(this).data('form-target'))
      var $select = $modal.find('#prospect-cancel-motif')
      if ($select.val() !== '0') {
        var actionUrl = $(this).data('form-action-url')
        if (actionUrl && typeof actionUrl === 'string' && $form.length) {
          $form.attr('action', actionUrl)
        }
        $form.find('#esim-input-status').val('abandon')
        $form.removeAttr('data-formvalidation')
        $form.submit()
      } else {
        $select.parent().addClass('ui-formvalidation-error')
        $select.change(function(){
          if ($(this).val() !== 0) {
            $(this).parent().removeClass('ui-formvalidation-error')
          }
        })
      }
    })

  // Partner Prospects Table
  $doc
    .on(Utility.clickEvent, '.table-prospects [data-action]', function() {
      // TODO - ADD AJAX URL FOR DELETING A PROSPECT
      var $prospect = $(this).closest('tr')
      var action = $(this).data('action')
      var $modal = $('#modal-partner-prospect-' + action)

      // Add prospect id for further actions (abandon / submit)
      $modal.data('prospect-id', $prospect.attr('id'))
      // Insert the company name inside the modal text and Show the popup
      $modal.find('.ui-modal-output-company').html($prospect.data('sortable-borrower'))
      $modal.uiModal('open')
    })
    .on(Utility.clickEvent, '#modal-partner-prospect-submit [data-modal-doactionsubmit]', function() {
      var $modal = $('#modal-partner-prospect-submit')
      var $prospect = $('#' + $modal.data('prospect-id'))
      var $form = $('#submit-partner-prospect')
      var siren = $prospect.data('sortable-siren')
      var company = $prospect.data('sortable-borrower')
      var amount = $prospect.data('sortable-amount')
      var duration = $prospect.data('sortable-duration')
      var motif = $prospect.data('sortable-motif')

      // @Debug data
      console.log('siren: ' + siren + ' | company: ' + company + ' | amount: ' + amount + ' | duration: ' + duration + ' | motif: ' + motif)

      $form.find('[name="esim[siren]"]').val(siren)
      $form.find('[name="esim[company]"]').val(company)
      $form.find('[name="esim[amount]"]').val(amount)
      $form.find('[name="esim[duration]"]').val(duration)
      $form.find('[name="esim[motif]"]').val(motif)
      $form.submit();

      $modal.uiModal('close')

      console.log('Submit prospect ' + $prospect.attr('id'))
    })
    .on(Utility.clickEvent, '#modal-partner-prospect-cancel [data-modal-doactionsubmit]', function() {
      var $modal = $(this).closest('[data-modal]')
      var $prospect = $('#' + $modal.data('prospect-id'))

      var $select = $modal.find('#prospect-cancel-motif')
      if ($select.val() !== '0') {
        // TODO - Remove lines below and Uncomment Ajax

        $modal.uiModal('close')
        $prospect.remove()
        if (!$('.table-prospects-item').length) {
          $('#partner-prospects-panel .table-scroll').remove()
          $('#partner-prospects-panel .message-info').show()
        }

        // var formData = {
        //   prospectId : $prospect.attr('id'),
        //   motif : $select.val()
        // }
        // // console.log(formData)
        // $.ajax({
        //   type: 'POST',
        //   url: '',
        //   data: formData,
        //   success: function(response) {
        //     if (response.text === 'OK') {
        //       $modal.uiModal('close')
        //       $prospect.remove()
        //       if (!$('.table-prospects-item').length) {
        //         $('#partner-prospects-panel .table-scroll').remove()
        //         $('#partner-prospects-panel .message-info').show()
        //       }
        //     }
        //   },
        //   error: function() {
        //     console.log("error retrieving data");
        //   }
        // })
        // TODO END
      } else {
        $select.parent().addClass('ui-formvalidation-error')
        $select.change(function(){
          if ($(this).val() !== 0) {
            $(this).parent().removeClass('ui-formvalidation-error')
          }
        })
      }
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
   * Devenir Preteur
   * @todo refactor into LenderSubscription controller (if not already there)
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
   * Custom Input Duration
   * User can click/drag around to select the range
   * @todo refactor into separate component
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
   * @todo refactor into separate component
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
   * @todo refactor into LenderWallet controller
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
   * @todo refactor into LenderProfile controller (if not already there)
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
   * Collapse
   * @todo refactor into Collapses.js controller
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
   * @todo refactor into Collapses.js controller
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

  /*
   * Sticky Scroll More
   * @note Slightly more complex than normal sticky because of its position at the bottom and added class when End is reached
   * @todo Combine with Sticky Instance from Projects.js controller into a separate component (StickyAlt.js)
   */
  var $scrollMore = $('#scroll-more')
  if ($scrollMore.length === 1 && /md|lg/.test(currentBreakpoint)) {
    var watchWindow = new WatchScroll.Watcher(window)

    // Position at the bottom of the window
    $scrollMore.css('top', $win.height())
    // Start animating
    if ($html.is('.has-csstransforms')) {
      $scrollMore.addClass('scroll-more-animate')

      // Only animate on homepages, not inner landing pages
      if (!$('body').is('.layout-page-single')) {
        $scrollMore.addClass('start')
      }
    }

    // Scroll down the page
    $doc.on(Utility.clickEvent, '#scroll-more', function (event) {
      if (!$(this).hasClass('end')) {
        var winScrollTop = $win.scrollTop()
        $('html, body').animate({scrollTop: winScrollTop + $win.height() / 2}, 400)
      }
    })
    // Scroll to top
    $doc.on(Utility.clickEvent, '#scroll-more.end', function (event) {
      $('html, body').animate({scrollTop: 0}, 400)
    })

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

    // Handle scroll state
    function offsetScrollMore() {
      var winScrollTop = $win.scrollTop()
      var startScrollFixed = 0
      var endScrollFixed = $('footer').offset().top - $win.height()
      var translateAmount = winScrollTop - startScrollFixed
      var offsetInfo = 0

      // Constrain info within certain area
      if (winScrollTop > startScrollFixed) {
        if (winScrollTop < endScrollFixed) {
          offsetInfo = translateAmount
          // Arrows - Back to original state
          if ($scrollMore.hasClass('end')) $scrollMore.removeClass('end')
        } else {
          offsetInfo = endScrollFixed - startScrollFixed
          // Invert arrows once at the bottom of the page
          if (!$scrollMore.hasClass('end')) $scrollMore.addClass('end').removeClass('start')
        }
      }

      // Apply offset
      doStickyOffset($scrollMore, offsetInfo)
    }

    // Debounce update of sticky within the watchWindow to reduce jank
    if ($scrollMore.length > 0) {
      watchWindow.watch(window, offsetScrollMore)
      offsetScrollMore()
    }
  }

})
