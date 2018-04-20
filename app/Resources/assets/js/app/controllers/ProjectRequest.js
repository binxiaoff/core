/*
 * Project Request controller
 */

var $ = require('jquery')
var $doc = $(document)

$doc.on('ready', function () {
  function autofillExecutive() {
    var executiveSelector = $('#identity-executive-selector');
    var titleField = $('[name=title]'),
      lastNameField = $('[name=lastName]'),
      firstNameField = $('[name=firstName]'),
      functionField = $('[name=function]'),
      executiveContent = $('.toggle-if-executive'),
      selectedExecutive = executiveSelector.find(':selected')

    if (0 === executiveSelector.length || '' === executiveSelector.val()) {
      titleField.children().prop('disabled', false);
      lastNameField.prop('readonly', false)
      firstNameField.prop('readonly', false)
      functionField.prop('readonly', false)
      executiveContent.collapse('hide')
    } else {
      titleField.filter(':not(:checked)').prop('disabled', true);
      titleField.filter(':checked').prop('disabled', false);
      lastNameField.prop('readonly', true).val(selectedExecutive.data('executiveLastName'))
      firstNameField.prop('readonly', true).val(selectedExecutive.data('executiveFirstName'))
      functionField.prop('readonly', true).val(selectedExecutive.data('executiveFunction'))
      executiveContent.collapse('show')
    }

    if ('M' === selectedExecutive.data('executiveTitle')) {
      titleField.filter('[value="M."]').prop('checked', true)
    } else {
      titleField.filter('[value=Mme]').prop('checked', true)
    }
    titleField.filter(':not(:checked)').prop('disabled', true)
    titleField.filter(':checked').prop('disabled', false)
    if (0 === executiveSelector.length || '' === executiveSelector.val()) {
      titleField.filter(':not(:checked)').prop('disabled', false)
    }

    lastNameField.val(selectedExecutive.data('executiveLastName'))
    firstNameField.val(selectedExecutive.data('executiveFirstName'))
    functionField.val(selectedExecutive.data('executiveFunction'))
  }

  autofillExecutive()

  $doc.on('change', '#identity-executive-selector', function () {
    $('#form-project-create-1').uiFormValidation('clearAll')
    autofillExecutive()
  })
})
