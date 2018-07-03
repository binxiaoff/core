/**
 * Project Request controller
 */

var $ = require('jquery')
var $doc = $(document)
var __ = require('__')

/**
 * Handle any UI changes for borrower's reason
 */
var $borrowerReasonInput = $('select[data-borrower-reason-input]')

// Continue only if select is in DOM
if ($borrowerReasonInput.length) {
  // Default labels in case can't get from DB
  var TRANS_SIREN_LABEL_DEFAULT = 'SIREN'
  var TRANS_SIREN_LABEL_MOTIVE_9 = 'SIREN de la cible'
  var TRANS_SIREN_LABEL_MOTIVE_8 = 'SIREN du franchiseur'

  // Related elements
  var $borrowerSirenInput = $('input[data-borrower-siren-input]')
  var $borrowerSirenLabel = $('label[data-borrower-siren-label]')
  var $borrowerCompanyName = $('[data-borrower-company-name]')
  var $borrowerCompanyNameInput = $borrowerCompanyName.find('input')

  /** Change the `data-borrower-*` inputs depending on the reason */
  function handleBorrowingReason(reasonValue) {
    if (!reasonValue) {
      reasonValue = $borrowerReasonInput.first().val()
    }

    switch (~~reasonValue) {
      // Création de franchise
      case borrowerEsim.idMotiveFranchiserCreation:
        $borrowerCompanyName.show()
        $borrowerSirenLabel.find('.text').text(__.__(TRANS_SIREN_LABEL_MOTIVE_8, 'franchisorSirenLabel'))
        $borrowerSirenLabel.find('.field-required').hide()
        $borrowerSirenInput
          .removeAttr('data-formvalidation-required')
        $borrowerCompanyNameInput
          .attr('data-formvalidation-required', true)
        break

      // Rachat de parts sociale
      case borrowerEsim.idMotiveShareBuyBack:
        $borrowerCompanyName.hide()
        $borrowerCompanyNameInput.val('')
        $borrowerSirenLabel.find('.text').text(__.__(TRANS_SIREN_LABEL_MOTIVE_9, 'targetSirenLabel'))
        $borrowerSirenLabel.find('.field-required').show()
        $borrowerSirenInput
          .attr('data-formvalidation-required', true)
        $borrowerCompanyNameInput
          .removeAttr('data-formvalidation-required')
        break

      // Everything else
      default:
        $borrowerCompanyName.hide()
        $borrowerCompanyNameInput.val('')
        $borrowerSirenLabel.find('.text').text(__.__(TRANS_SIREN_LABEL_DEFAULT, 'sirenLabel'))
        $borrowerSirenLabel.find('.field-required').show()
        $borrowerSirenInput
          .attr('data-formvalidation-required', true)
        $borrowerCompanyNameInput
          .removeAttr('data-formvalidation-required')
        break
    }
  }

  $doc.on('change', 'select[data-borrower-reason-input]', function () {
    handleBorrowingReason($(this).val())
  })

  handleBorrowingReason($('select[data-borrower-reason-input]').val())
}

var executiveSelector = $('#identity-executive-selector')

if (executiveSelector.length) {
  function autofillExecutive() {
    var executiveSelector = $('#identity-executive-selector');
    var titleField = $('[name=title]'),
      lastNameField = $('[name=lastName]'),
      firstNameField = $('[name=firstName]'),
      functionField = $('[name=function]'),
      selectedExecutive = executiveSelector.find(':selected')

    if ('M' === selectedExecutive.data('executiveTitle')) {
      titleField.filter('[value="M."]').prop('checked', true)
      titleField.filter('[value=Mme]').prop('checked', false)
    } else {
      titleField.filter('[value="M."]').prop('checked', false)
      titleField.filter('[value=Mme]').prop('checked', true)
    }

    if (0 === executiveSelector.length || '' === executiveSelector.val()) {
      titleField.prop('disabled', false);
      lastNameField.prop('readonly', false)
      firstNameField.prop('readonly', false)
      functionField.prop('readonly', false)
    } else {
      titleField.filter(':not(:checked)').prop('disabled', true)
      titleField.filter(':checked').prop('disabled', false)
      lastNameField.prop('readonly', true).val(selectedExecutive.data('executiveLastName'))
      firstNameField.prop('readonly', true).val(selectedExecutive.data('executiveFirstName'))
      functionField.prop('readonly', true).val(selectedExecutive.data('executiveFunction'))
    }

    lastNameField.val(selectedExecutive.data('executiveLastName'))
    firstNameField.val(selectedExecutive.data('executiveFirstName'))
    functionField.val(selectedExecutive.data('executiveFunction'))
  }

  autofillExecutive()

  $doc.on('change', '#identity-executive-selector', function () {
    $('#form-project-create').uiFormValidation('clearAll')
    autofillExecutive()
  })
}
