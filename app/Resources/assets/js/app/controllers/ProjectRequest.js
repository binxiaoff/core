/*
 * Project Request controller
 */

var $ = require('jquery')
var __ = require('__')
var $doc = $(document)

$doc.on('ready', function () {
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

  function handleBorrowingReason(reasonSelect, sirenInput, sirenLabel, sirenRequiredMarker, franchiserCreationReasonId, shareBuyBackReasonId, defaultLabelValue, defaultLabelText, defaultRequiredValue) {
    var reason = parseInt(reasonSelect.val());
    switch (reason) {
      case franchiserCreationReasonId:
        sirenInput.attr('data-formvalidation-required', false)
        sirenLabel.html(defaultLabelText)
        break
      case shareBuyBackReasonId:
        sirenInput.attr('data-formvalidation-required', defaultRequiredValue)
        sirenLabel.html(__.__('Target siren', 'targetSirenLabel')).append(sirenRequiredMarker)
        break
      default:
        sirenInput.attr('data-formvalidation-required', defaultRequiredValue)
        sirenLabel.html(defaultLabelValue)
        break
    }
  }

  var reasonSelect = $('select[data-reason-select]'),
    sirenInput = $('input[data-siren-input]'),
    sirenLabel = $('label[data-siren-label]'),
    sirenRequiredMarker = $('span[data-siren-required-marker]'),
    defaultRequiredValue = sirenInput.attr('data-formvalidation-required'),
    defaultLabelValue = sirenLabel.html(),
    defaultLabelText = sirenLabel.text(),
    franchiserCreationReasonId = 8,
    shareBuyBackReasonId = 9

  $(document).on('change', reasonSelect, function () {
    handleBorrowingReason(reasonSelect, sirenInput, sirenLabel, sirenRequiredMarker, franchiserCreationReasonId, shareBuyBackReasonId, defaultLabelValue, defaultLabelText, defaultRequiredValue)
  })

  checkIsManager()

  $doc.on('change', '#form-project-create input[name="manager"]', function () {
    checkIsManager()
  })
})
