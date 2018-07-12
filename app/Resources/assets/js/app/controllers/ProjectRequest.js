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
  // Dev sanity check
  if (!borrowerEsim) {
    console.error('Missing `borrowerEsim` variable declaration');
  }

  // Default labels in case can't get from DB
  var TRANS_SIREN_LABEL_DEFAULT = 'SIREN'
  var TRANS_SIREN_LABEL_MOTIVE_9 = 'SIREN de la cible'
  var TRANS_SIREN_LABEL_MOTIVE_8 = 'SIREN du franchiseur'

  // Related elements
  var $borrowerSirenInput = $('input[data-borrower-siren-input]')
  var $borrowerSirenLabel = $('label[data-borrower-siren-label]')
  var $borrowerCompanyName = $('[data-borrower-company-name]')
  var $borrowerCompanyNameInput = $borrowerCompanyName.find('input')
  var toggleType = $borrowerCompanyName.attr('data-borrower-company-name') || 'show'

  /**
   * Toggle the company name.
   *
   * @param {Boolean} setState
   * @param {Boolean} isRequired
   */
  function toggleCompanyName(setState, isRequired) {
    switch (toggleType) {
      case 'disable':
        if (setState) {
          $borrowerCompanyName.removeClass('disabled')
          $borrowerCompanyNameInput
            .prop('disabled', false)
        } else {
          $borrowerCompanyName.addClass('disabled')
          $borrowerCompanyNameInput
            .prop('disabled', true)
        }
        break

      case 'show':
      default:
        if (setState) {
          $borrowerCompanyName.show()
        } else {
          $borrowerCompanyName.hide()
        }
        break
    }

    // Set required status of the input field
    if (isRequired === true) {
      $borrowerCompanyNameInput
        .attr('data-formvalidation-required', true)
    } else if (isRequired === false) {
      $borrowerCompanyNameInput
        .attr('data-formvalidation-required', false)
    }
  }

  /**
   * Change the `data-borrower-*` inputs depending on the reason.
   *
   * @param {Number} reasonValue
   */
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
        toggleCompanyName(true, true)
        break

      // Rachat de parts sociale
      case borrowerEsim.idMotiveShareBuyBack:
        $borrowerCompanyNameInput.val('')
        $borrowerSirenLabel.find('.text').text(__.__(TRANS_SIREN_LABEL_MOTIVE_9, 'targetSirenLabel'))
        $borrowerSirenLabel.find('.field-required').show()
        $borrowerSirenInput
          .attr('data-formvalidation-required', true)
        toggleCompanyName(false, false)
        break

      // Everything else
      default:
        $borrowerCompanyNameInput.val('')
        $borrowerSirenLabel.find('.text').text(__.__(TRANS_SIREN_LABEL_DEFAULT, 'sirenLabel'))
        $borrowerSirenLabel.find('.field-required').show()
        $borrowerSirenInput
          .attr('data-formvalidation-required', true)
        toggleCompanyName(false, false)
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
    } else if ('Mme' === selectedExecutive.data('executiveTitle')) {
      titleField.filter('[value="M."]').prop('checked', false)
      titleField.filter('[value=Mme]').prop('checked', true)
    } else {
      titleField.filter('[value="M."]').prop('checked', false)
      titleField.filter('[value=Mme]').prop('checked', false)
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
