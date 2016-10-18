// Lib Dependencies
var $ = require('jquery')
var __ = require('__')
var Templating = require('Templating')
var Modal = require('Modal')

var $doc = $(document)

// Show the bid confirmation prompt modal
function bidConfirmationPrompt (project, rate, amount) {

  // Error
  if (parseFloat(rate) <= 0 || parseFloat(amount) <= 0) {
    return
  }

  // Show the bid confirmation message with the correct values
  $('#modal-bid-confirmation-prompt [data-bid-confirmation-message]').each(function (i, elem) {
    var messageTemplate = $(this).attr('data-bid-confirmation-message')
    $(elem).html(Templating.replace(messageTemplate, {
      rate: '<strong>' + __.localizedNumber(rate, 1) + '%</strong>',
      amount: '<strong>' + __.localizedPrice(amount) + '€</strong>'
    }, {
      keywordPrefix: '%',
      keywordSuffix: '%'
    }))
  })

  // Show the modal to get the user's confirmation
  $('#modal-bid-confirmation-prompt').uiModal('open')
}

// When the CIP questionnaire prompt modal is initialised
$doc.on('Modal:initialised', '#modal-cip-questionnaire-prompt', function (event, elemModal) {
  // Set the onconfirm action
  elemModal.settings.onconfirm = function () {
    var promise = $.Deferred()

    // Reject the promise to stop the modal from closing
    promise.reject(false)

    // Get the bid data attached to the modal element
    var bidData = elemModal.$elem.data('bid')

    //
    console.log('onconfirm', bidData)

    // Stops it from closing the modal
    return promise
  }
})

// Ensure that any spinner buttons and their targets (i.e. forms) are re-enabled when modals are closed/cancelled
$doc.on('Modal:cancelled Modal:closed', '#modal-cip-questionnaire-prompt, #modal-bid-confirmation-prompt', function () {
  $('[data-bid-confirmation] [data-spinnerbutton]').uiSpinnerButton('stopLoading')
})

// When the CIP questionnaire modal is closed, open up the bid confirmation prompt
// @note Due to Fancybox's limitation of supporting only a single modal being shown, it's necessary to hook into the closed event to then show the next modal
$doc.on('Modal:closed', '#modal-cip-questionnaire-prompt', function (event, elemModal) {
  var $modalCip = $(this)
  var bidData = $modalCip.data('bid')

  // Show
  if (bidData) {
    bidConfirmationPrompt(bidData.projectSlug, bidData.rate, bidData.amount)
  }
})

// Submit the bid for confirmation
// @note With the CIP questionnaire, this now needs to validate whether to ask the user if they want to take a questionnaire or not.
//       If a user declines, they continue with the answering the modal prompt
//       If a user accepts to take the questionnaire, they are then redirected to the CIP questionnare
//       After filling in the CIP questionnaire, a user is taken back to the project detail page with their bid attempt restored
//       and the prompt available to continue their bid.
$doc.on('submit', 'form[data-bid-confirmation]', function (event) {
  var $form = $(this)
  var projectSlug = window.location.href.split('/').pop()
  var projectId = $form.attr('action').split('/').pop()
  var rate = parseFloat($form.find('[data-bid-rate]').val())
  var amount = parseFloat($form.find('[data-bid-amount]').val())

  // The questionnaire prompt modal
  var $modalCip = $('#modal-cip-questionnaire-prompt')

  event.preventDefault()

  // Sanitise URLs
  function sanitiseURL(input) {
    var output = input
    if (input.match(/[#\?\/]/)) {
      output = input.replace(/[#\?\/].*$/g, '')
    }
    return output
  }
  projectSlug = sanitiseURL(projectSlug)
  projectId = sanitiseURL(projectId)

  // @debug
  // console.log('data-bid-confirmation', {
  //   projectId: projectId,
  //   projectSlug: projectSlug,
  //   rate: rate,
  //   amount: amount
  // })

  // No project, rate or amount set
  if (!projectId || !projectSlug || !rate || !amount) {
    return false
  }

  // Test for CIP questionnare validation
  if (!$form.is('[data-bid-confirmation-show]')) {
    // Show spinner is loading
    $form.find('[data-spinnerbutton]').trigger('Spinner:showLoading')

    $.ajax({
      url: '/conseil-cip/bid',
      data: {
        project: projectSlug,
        rate: rate,
        amount: amount
      },
      global: false,
      success: function (data, textStatus) {
        if (data.hasOwnProperty('validation')) {
          // Show the CIP questionnaire
          if (data.validation && $modalCip.length > 0) {
            // Save the information about the project to the modal
            $modalCip.data('bid', {
              projectId: projectId,
              projectSlug: projectSlug,
              rate: rate,
              amount: amount
            })

            // Show the CIP questionnare confirmation prompt modal
            $modalCip.uiModal('open')
            return
          }
        }

        // Ignore errors...
        // if (data.hasOwnProperty('error')) {
        //   console.warn(data)
        // }

        // Clear any bid data
        $modalCip.data('bid', false)

          // If no need to show the CIP questionnaire, show the
        bidConfirmationPrompt(projectSlug, rate, amount)
      },
      complete: function () {
        // Hide spinner is loading
        $form.find('[data-spinnerbutton]').trigger('Spinner:hideLoading')
      }
    })

  // No CIP test needed, so show the bid confirmation prompt
  } else {
    // Check if there are any form validation errors and only show confirmation if no errors
    if ($form.find('ui-formvalidation-error').length === 0) {
      bidConfirmationPrompt(project, rate, amount)
    }
  }

  // if ($('[data-popup-amount]').val() == '' || $('[data-popup-rate]').val() == ''
  //   || false == $.isNumeric($('[data-popup-amount]').val()) || false == $.isNumeric($('[data-popup-rate]').val())) {
  //   $('[data-popup-amount], [data-popup-rate]').each(function (i, elm) {
  //     var el = $(elm)
  //
  //     if (el.val() == "" || false == $.isNumeric(el.val())) {
  //       el.closest('.form-field').addClass('ui-formvalidation-error')
  //     } else {
  //       el.closest('.form-field').removeClass('ui-formvalidation-error')
  //     }
  //   })
  // } else {
  //   $('[data-popup-amount], [data-popup-rate]').closest('.form-field').removeClass('ui-formvalidation-error')
  //
  //   if (parseFloat($('#bid-min-amount').val()) > parseFloat($('#bid-amount').val())) {
  //     $('.ui-BidConfirmation-error').show()
  //     $('.bid-min-amount-error').show()
  //     $('.loan-max-amount-error').hide()
  //   } else if ($('#bid-rest-amount').val().length > 0 && parseFloat($('#bid-rest-amount').val()) < parseFloat($('#bid-amount').val())) {
  //     $('.ui-BidConfirmation-error').show()
  //     $('.loan-max-amount-error').show()
  //     $('.bid-min-amount-error').hide()
  //   } else {
  //     $('.ui-BidConfirmation').show()
  //   }
  //
  //   var message = $('.ui-BidConfirmation .bids-confirmation-details-holder').html()
  //   var bidAmount = __.formatNumber($('#bid-amount').val(), 0)
  //   var bidRate = __.formatNumber($('#bid-interest').val(), 1)
  //   message = message.replace('%rate%', bidRate + ' %' )
  //   message = message.replace('%amount%', bidAmount + ' €')
  //
  //   $('.ui-BidConfirmation .bids-confirmation-details').html(message)
  //
  //   $('[data-popup-bid-confirmation-yes]').click(function () {
  //     $('.ui-BidConfirmation').hide()
  //     form.submit()
  //   })
  //
  //   $('[data-popup-bid-confirmation-no]').click(function () {
  //     $(this).parents('.popup-overlay').hide()
  //   })
  // }
})

// Show the bid confirmation on ready
// @note this is used for when the user has filled in the CIP questionnaire and is returned back to the project detail page
//       If you want to show the confirmation on load/ready, set `data-bid-confirmation-show="true"` on the element
$doc.on('ready', function () {
  $('[data-bid-confirmation-show]').each(function (i, elem) {
    var $elem = $(elem)
    if (Utility.convertToBoolean($(elem).attr('data-bid-confirmation-show'))) {
      var project = $elem.attr('action').replace(/^.*projectId=([^&]+).*$/i, '$1')
      var rate = parseFloat($elem.find('[data-bid-rate]').val())
      var amount = parseFloat($elem.find('[data-bid-amount]').val())

      // Error if any empty value
      if (!project || !rate || !amount) {
        return false
      }

      // Show the bid confirmation prompt
      bidConfirmationPrompt(project, rate, amount)
    }
  })
})

