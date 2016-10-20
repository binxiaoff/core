/*
 * Bid Confirmation
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
    rate: parseFloat($elem.find('[data-bid-rate]').val()),
    amount: parseFloat($elem.find('[data-bid-amount]').val())
  }

  // No project, rate or amount set
  if (!bidData.projectSlug || !bidData.rate || !bidData.amount) {
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
      rate: '<strong>' + __.localizedNumber(bidData.rate, 1) + '&nbsp;%</strong>',
      amount: '<strong>' + __.localizedNumber(bidData.amount) + '&nbsp;€</strong>'
    }, {
      keywordPrefix: '%',
      keywordSuffix: '%'
    }))
  })

  // Show the modal to get the user's confirmation
  $('#modal-bid-confirmation-prompt').uiModal('open')
}

// Ensure that any spinner buttons and their targets (i.e. forms) are re-enabled when modals are closed/cancelled
$doc.on('Modal:cancelled Modal:closed', '#modal-cip-questionnaire-prompt, #modal-bid-confirmation-prompt', function () {
  $('[data-bid-confirmation] [data-spinnerbutton]').uiSpinnerButton('stopLoading')
})

// Submit the form when modal bid is confirmed
$doc.on('Modal:confirmed', '#modal-bid-confirmation-prompt', function (event) {
  // Use the special trigger method to pass through some extra options on the submit event
  $('form[data-bid-confirmation]').trigger('submit', [{
    ignoreBidConfirmation: true
  }])
})

$doc.on('Modal:confirmed', '#modal-cip-advices-prompt', function () {
  var $form = $('form[data-bid-confirmation]')
  var bidData = getBidData($form)
  bidConfirmationPrompt(bidData)
})

// Submit the bid for confirmation
// @note With the CIP questionnaire, this now needs to validate whether to ask the user if they want to take a questionnaire or not.
//       If a user declines, they go back to bid form
//       If a user accepts to take the questionnaire, they are then redirected to the CIP questionnare
//       After filling in the CIP questionnaire, a user is taken back to the project detail page with their bid attempt restored
//       and the prompt available to continue their bid.
$doc.on('submit', 'form[data-bid-confirmation]', function (event, options) {
  var $form = $(this)
  var bidData = getBidData($form)
  var $modalCipAdvices = $('#modal-cip-advices-prompt')
  var $modalCipQuestionnaire = $('#modal-cip-questionnaire-prompt')

  // @debug
  // console.log($form.attr('action'))

  // Let the normal form submit go through
  if (options && options.hasOwnProperty('ignoreBidConfirmation') && options.ignoreBidConfirmation) {
    // @debug
    // console.log('Bid confirmed. Submitting...')

    return

  // Show the bid confirmation/CIP questionnaire prompts
  } else {
    event.preventDefault()

    // @debug
    // console.log('data-bid-confirmation', bidData)

    // Show spinner is loading
    $form.trigger('Spinner:showLoading')

    // Validate with server to show CIP questionnaire prompt
    $.ajax({
      url: '/conseil-cip/bid',
      data: {
        project: bidData.projectSlug,
        rate: bidData.rate,
        amount: bidData.amount
      },
      global: false,
      success: function (data, textStatus) {
        if (data.hasOwnProperty('validation') && data.validation) {
          // Show the advices
          if (data.hasOwnProperty('advices') && data.advices && $modalCipAdvices.length > 0) {
            // @debug
            // console.log('Showing CIP advices...')

            // Set message
            $modalCipAdvices.find('#modal-cip-advices-message').html(data.advices)

            // Show the CIP questionnare confirmation prompt modal
            $modalCipAdvices.uiModal('open')
            return
          }
          // Show the CIP questionnaire
          else if (data.hasOwnProperty('questionnaire') && data.questionnaire && $modalCipQuestionnaire.length > 0) {
            // @debug
            // console.log('Asking CIP questionnaire...')

            // Show the CIP questionnare confirmation prompt modal
            $modalCipQuestionnaire.uiModal('open')
            return
          }
        }

        // Ignore errors...
        // if (data.hasOwnProperty('error')) {
        //   console.warn(data)
        // }

        // If no need to show the CIP questionnaire, show the
        bidConfirmationPrompt(bidData)
      },
      complete: function () {
        // Hide spinner is loading
        $form.trigger('Spinner:hideLoading')
      }
    })

    return false
  }
})
