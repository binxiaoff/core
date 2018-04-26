/**
 * Project Request controller
 */

var $ = require('jquery')
var $doc = $(document)

/**
 * Handle any changes on the Project Create form
 */
var $formProjectCreate = $('#form-project-create');

// Continue only if form is in DOM
if ($formProjectCreate.length) {
  // Show/hide manager details panel and confirm TOS checkbox if user is/is not manager
  function checkIsManager() {
    if ($('#form-project-create input[name="manager"]:checked').val() === 'no') {
      $('#form-project-create .toggle-if-not-manager').collapse('show')
      $('#form-project-create .toggle-if-manager').collapse('hide')
    } else {
      $('#form-project-create .toggle-if-not-manager').collapse('hide')
      $('#form-project-create .toggle-if-manager').collapse('show')
    }
  }

  $doc.on('change', '#form-project-create input[name="manager"]', function () {
    checkIsManager()
  })

  checkIsManager()
}

/**
 * Handle any UI changes for borrower's reason
 */
var $borrowerReasonInput = $('select[data-borrower-reason-input]')

// Continue only if select is in DOM
if ($borrowerReasonInput.length) {
  // Default labels in case can't get from DB
  var TRANS_SIREN_LABEL_DEFAULT = 'SIREN'
  var TRANS_SIREN_LABEL_MOTIVE_9 = 'SIREN de la cible'

  // Related elements
  var $borrowerSirenInput = $('input[data-borrower-siren-input]')
  var $borrowerSirenLabel = $('label[data-borrower-siren-label]')
  var $borrowerCompanyName = $('[data-borrower-company-name]')

  /** Change the `data-borrower-*` inputs depending on the reason */
  function handleBorrowingReason(reasonValue) {
    if (!reasonValue) {
      reasonValue = $borrowerReasonInput.first().val()
    }

    switch (~~reasonValue) {
      // Création de franchise
      case 8:
        $borrowerCompanyName.show()
        $borrowerSirenLabel.find('.text').text(borrowerEsim.sirenPlaceholder.default || TRANS_SIREN_LABEL_DEFAULT)
        $borrowerSirenLabel.find('.field-required').hide()
        $borrowerSirenInput
          .removeAttr('data-formvalidation-required')
        break

      // Rachat de parts sociale
      case 9:
        $borrowerCompanyName.hide()
        $borrowerSirenLabel.find('.text').text(borrowerEsim.sirenPlaceholder.motive_9 || TRANS_SIREN_LABEL_MOTIVE_9)
        $borrowerSirenLabel.find('.field-required').show()
        $borrowerSirenInput
          .attr('data-formvalidation-required', true)
        break

      // Everything else
      default:
        $borrowerCompanyName.hide()
        $borrowerSirenLabel.find('.text').text(borrowerEsim.sirenPlaceholder.default || TRANS_SIREN_LABEL_DEFAULT)
        $borrowerSirenLabel.find('.field-required').show()
        $borrowerSirenInput
          .attr('data-formvalidation-required', true)
        break
    }
  }

  $doc.on('change', 'select[data-borrower-reason-input]', function () {
    handleBorrowingReason($(this).val())
  })
}
