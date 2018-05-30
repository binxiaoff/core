var $ = require('jquery')

var $doc = $(document)

$('.lender-data-update-save-btn').on('click', function () {
  var $section = $(this).parents('section.lender-data-update-edit')
  $section.find('form').uiFormValidation('validate')
  var elementId, text;
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

  $section.find('form').uiFormValidation('clearAll')
})

$.fn.getType = function () {
  return this[0].tagName === 'INPUT' ? this[0].type.toLowerCase() : this[0].tagName.toLowerCase()
}
