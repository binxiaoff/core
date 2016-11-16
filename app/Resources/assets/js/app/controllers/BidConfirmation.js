/*
 * Bid Confirmation controller
 */

// Lib Dependencies
var $ = require('jquery')
var __ = require('__')
var Utility = require('Utility')
var Templating = require('Templating')
var Modal = require('Modal')

var $doc = $(document)

// Get the details of a user's bid attempt
function getBidData (elem) {
  var $elem = $(elem)

  // Sanitise strings from URLs (strip URL info)
  function sanitiseFromURL(input) {
    var output = input
    if (input.match(/[#\?\/]/)) {
      output = input.replace(/[#\?\/].*$/g, '')
    }
    return output
  }

  var bidData = {
    projectSlug: sanitiseFromURL(window.location.href.split('/').pop()),
    projectId: sanitiseFromURL($elem.attr('action').split('/').pop()),
    rate: parseFloat($elem.find('[data-bid-rate]').val()),
    amount: parseFloat($elem.find('[data-bid-amount]').val())
  }

  // No project, rate or amount set
  if (!bidData.projectId || !bidData.projectSlug || !bidData.rate || !bidData.amount) {
    return false
  }

  return bidData
}

// Show the bid confirmation prompt modal
function bidConfirmationPrompt (bidData) {

  // @debug
  // console.log('Confirming bid...', bidData)

  // Error: don't show the modal
  if (!bidData || bidData.rate <= 0 || bidData.amount <= 0) {
    return
  }

  // Show the bid confirmation message with the correct values
  $('#modal-bid-confirmation-prompt [data-bid-confirmation-message]').each(function (i, elem) {
    var messageTemplate = $(this).attr('data-bid-confirmation-message')
    $(elem).html(Templating.replace(messageTemplate, {
      rate: '<strong>' + __.localizedNumber(bidData.rate, 1) + '%</strong>',
      amount: '<strong>' + Templating.filter(__.localizedPrice(bidData.amount), 'nbsp') + '€</strong>'
    }, {
      keywordPrefix: '%',
      keywordSuffix: '%'
    }))
  })

  // Show the modal to get the user's confirmation
  $('#modal-bid-confirmation-prompt').uiModal('open')
}

// Submit the form when modal bid is confirmed
$doc.on('Modal:confirmed', '#modal-bid-confirmation-prompt', function (event) {
  // Use the special trigger method to pass through some extra options on the submit event
  $('form[data-bid-confirmation]').trigger('submit', [{
    ignoreBidConfirmation: true
  }])
})

// When the CIP questionnaire modal is closed, open up the bid confirmation prompt
// @note Due to Fancybox's limitation of supporting only a single modal being shown, it's necessary to hook into the closed event to then show the next modal
$doc.on('Modal:closed', '#modal-cip-questionnaire-prompt', function (event, elemModal) {
  var $modalCip = $(this)
  var bidData = $modalCip.data('bid')

  // Show
  if (bidData) {
    bidConfirmationPrompt(bidData)
  }
})

// Submit the bid for confirmation
// @note With the CIP questionnaire, this now needs to validate whether to ask the user if they want to take a questionnaire or not.
//       If a user declines, they continue with the answering the modal prompt
//       If a user accepts to take the questionnaire, they are then redirected to the CIP questionnare
//       After filling in the CIP questionnaire, a user is taken back to the project detail page with their bid attempt restored
//       and the prompt available to continue their bid.
$doc.on('submit', 'form[data-bid-confirmation]', function (event, options) {
  var $form = $(this)
  var bidData = getBidData($form)
  var $modalCip = $('#modal-cip-questionnaire-prompt')

  // @debug
  // console.log($form.attr('action'))

  // Let the normal form submit go through
  if (options && options.hasOwnProperty('ignoreBidConfirmation') && options.ignoreBidConfirmation) {
    // @debug
    // console.log('Bid confirmed. Submitting...')

    // Show spinner is loading...
    $form.trigger('Spinner:showLoading')

    // ~~~ Traa la la la laaaa ~~~
    // event.preventDefault()
    // return false

  // Show the bid confirmation/CIP questionnaire prompts
  } else {
    event.preventDefault()

    // @debug
    // console.log('data-bid-confirmation', bidData)

    // Test for CIP questionnaire
    if ($modalCip.length && !$form.is('[data-bid-confirmation-show]')) {

      // Show spinner is loading
      $form.trigger('Spinner:showLoading')

      // Validate with server to show CIP questionnaire prompt
      $.ajax({
        url: '/projects/pre-check-bid/' + bidData.projectSlug + '/' + bidData.amount + '/' + bidData.rate,
        method: 'post',
        data: {
          project: bidData.projectSlug,
          rate: bidData.rate,
          amount: bidData.amount
        },
        dataType: 'json',
        global: false,
        success: function (data, textStatus) {
          // Show the CIP questionnaire
          if (data.hasOwnProperty('validation') && data.validation && $modalCip.length > 0) {
            // @debug
            // console.log('Asking CIP questionnaire...')

            // Save the information about the project to the modal
            $modalCip.data('bid', bidData)

            // Show the CIP questionnare confirmation prompt modal
            $modalCip.uiModal('open')
            return
          }

          // Ignore errors...
          // if (data.hasOwnProperty('error')) {
          //   console.warn(data)
          // }

          // Clear any saved bid data
          $modalCip.data('bid', false)

          // Show the bid questionnaire
          bidConfirmationPrompt(bidData)
        },
        complete: function () {
          // Hide spinner is loading
          $form.trigger('Spinner:hideLoading')
        }
      })

    // No CIP test needed, so show the bid confirmation prompt
    } else {
      // Check if there are any form validation errors and only show confirmation if no errors
      if ($form.find('ui-formvalidation-error').length === 0) {
        bidConfirmationPrompt(bidData)
      }
    }

    return false
  }
})

// Change input values in side panel removes any saved bid data to enable rechecking for CIP questionnaire validation
$doc.on('change', '.project-single-form-invest :input', function (event) {
  $('#modal-cip-questionnaire-prompt').data('bid', false)
  $('[data-bid-confirmation-show]').removeAttr('data-bid-confirmation-show')
})

// When DOM ready...
$doc.on('ready', function () {
  // Show the bid confirmation on ready
  // @note this is used for when the user has filled in the CIP questionnaire and is returned back to the project detail page
  //       If you want to show the confirmation on load/ready, set `data-bid-confirmation-show="true"` on the element
  $('[data-bid-confirmation-show]').each(function (i, elem) {
    var $elem = $(elem)

    // @debug
    // console.log('show bid confirmation', {
    //   show: $(elem).attr('data-bid-confirmation-show'),
    //   showBoolean: Utility.convertToBoolean($(elem).attr('data-bid-confirmation-show'))
    // })

    if (Utility.convertToBoolean($(elem).attr('data-bid-confirmation-show'))) {
      var bidData = getBidData($elem)

      // Show the bid confirmation prompt
      if (bidData) {
        bidConfirmationPrompt(bidData)
      }
    }
  })
})
