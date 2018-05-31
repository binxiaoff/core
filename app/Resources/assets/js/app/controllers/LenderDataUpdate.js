var $ = require('jquery')

$('.lender-data-update-save-btn').on('click', function () {
  var $section = $(this).parents('section.lender-data-update-edit')
  //$section.find('form').uiFormValidation('validate')
  var elementId, text
  var $inputs = $section.find(':input')
  $inputs.each(function (index, input) {
    var $input = $(input)
    elementId = $input.data('impacted-element-id')
    if ('radio' === $input.getType()) {
      elementId = $input.parent('div').data('impacted-element-id')
    }
    if (elementId) {
      switch ($input.getType()) {
        case 'radio':
          var checkedOption = $('input[name="' + $input.attr('name') + '"]:checked')
          text = $("label[for='" + checkedOption.attr('id') + "']").text()
          break;
        case 'select':
          text = $input.find(':selected').text()
          break;
        case 'text':
          text = $input.val()
      }
      $('#' + elementId).html(text)
    }
  })

  //$section.find('form').uiFormValidation('clearAll')
})

$('#lender-data-update-identity-save-btn').on('click', function () {
  var $inputs = $('#data-update-info-edit :input')
  var isModified = false
  $inputs.each(function (index, input) {
    var submittedValue
    var $input = $(input)
    var originalValue = $input.data('original-value')
    if ('radio' === $input.getType()) {
      originalValue = $input.parent('div').data('original-value')
    }
    if (originalValue) {
      switch ($input.getType()) {
        case 'radio':
          submittedValue = $('input[name="' + $input.attr('name') + '"]:checked').val()
          break;
        case 'select':
          submittedValue = parseInt($input.find(':selected').val())
          break;
        case 'text':
          submittedValue = $input.val()
          break;
        default:
          break;
      }
      if (originalValue !== submittedValue) {
        isModified = true
        return false
      }
    }
  })

  if (isModified) {
    $('#data-update-id-doc-view').collapse('hide')
    $('#data-update-id-doc-edit').collapse('show')
  }
})

$.fn.getType = function () {
  return this[0].tagName === 'INPUT' ? this[0].type.toLowerCase() : this[0].tagName.toLowerCase()
}
