var $ = require('jquery')
var Utility = require('Utility')
var $doc = $(document)
var CacheForm = require('CacheForm')

$doc.on('ready', function () {
  // Only apply this controller logic if this element is within the DOM
  if (!$('.form-data-update-info-edit').length) {
    return
  }

  var $form = $('form.form-data-update-info-edit[data-cacheform]')
  var formIsEdited = false
  var progressBarStep = 1

  // Clear FormValidation messages when the panel is hidden
  $doc.on('hidden.bs.collapse', '.form-data-update-info-edit [data-formvalidation]', function (event) {
    // Clear form validation errors when it is hidden
    $(this).uiFormValidation('clear')
  })

  // Clear the disabled property for the reset button in identity document upload section when the panel is hidden
  $doc.on('hidden.bs.collapse', '#data-update-id-doc-edit', function (event) {
    $(this).find('button[type="reset"]').prop('disabled', false)
  })

  // Update the progress bar length
  $doc.on(Utility.clickEvent, '.data-update-continue-btn', function () {
    progressBarStep = progressBarStep + 1
    $('.data-update-progress').uiProgressBar('setCurrent', progressBarStep, false)
  })

  // Click on reset button to reset only the fields within the panel
  $doc.on(Utility.clickEvent, '.form-data-update-info-edit button[type="reset"]', function (event) {
    event.preventDefault()

    // Restore the form state
    $form.uiCacheForm('restore', formIsEdited ? 'newState' : 'initialState')
  })

  // Click on save button within a form validation area
  $doc.on(Utility.clickEvent, '.form-data-update-info-edit [data-formvalidation] .lender-data-update-save-btn', function (event) {
    var $section = $(this).parents('section.lender-data-update-edit')
    var elementId, text
    var $inputs = $section.find(':input')
    var $editArea = $(this).closest('section[data-formvalidation]').first()
    var $viewArea = $editArea.siblings('section').not('[data-formvalidation]').first()
    var hasErrors

    // Ensure to validate first
    $editArea.uiFormValidation('validate')

    // Check if any errors occurred
    hasErrors = $editArea.find('.ui-formvalidation-error').length > 0

    // @debug
    // console.log('Save button click', {
    //   event: event,
    //   hasErrors: hasErrors,
    //   $editArea: $editArea,
    //   $viewArea: $viewArea
    // })

    if (hasErrors) {
      event.preventDefault()
      event.stopPropagation()
      return false
    }

    $inputs.each(function (index, input) {
      var $input = $(input)
      elementId = $input.data('impacted-element-id')

      if ($input.getType() === 'radio') {
        elementId = $input.parent('div').data('impacted-element-id')
      }

      if (elementId) {
        switch ($input.getType()) {
          case 'radio':
            var checkedOption = $('input[name="' + $input.attr('name') + '"]:checked')
            text = $("label[for='" + checkedOption.attr('id') + "']").text()
            break

          case 'select':
            text = $input.find(':selected').text()
            break

          case 'text':
            text = $input.val()
        }
        $('#' + elementId).html(text)
      }
    })

    // Save the form state
    $form.uiCacheForm('save', 'newState')
    formIsEdited = true

    // I've removed the data-toggle/data-target from the save button and are managing the show/hide of the panel here
    $viewArea.collapse('show')
    $editArea.collapse('hide')
  })

  // Show/hide the upload identity documents panel if the users have modified their personal info and disable the cancel button in the the upload panel
  $doc.on(Utility.clickEvent, '#lender-data-update-identity-save-btn', function (event) {
    var $inputs = $('#data-update-info-edit :input')
    var isModified = false

    $inputs.each(function (index, input) {
      var submittedValue
      var $input = $(input)
      var originalValue = $input.data('original-value')

      if ($input.getType() === 'radio') {
        originalValue = $input.parent('div').data('original-value')
      }

      if (originalValue) {
        switch ($input.getType()) {
          case 'radio':
            submittedValue = $('input[name="' + $input.attr('name') + '"]:checked').val()
            break

          case 'select':
            submittedValue = ~~$input.find(':selected').val()
            break

          case 'text':
            submittedValue = $input.val()
            break
        }

        if (originalValue !== submittedValue && 'form-data-update-info-nomUsage' !== $input.attr('id')) {
          isModified = true

          return false //break
        }
      }
    })

    if (isModified) {
      $('#data-update-id-doc-view').collapse('hide')
      $('#data-update-id-doc-edit').collapse('show')
      $('#data-update-id-doc-edit button[type="reset"]').prop('disabled', true)
    }
  })

  // Save the initial form state
  $form.uiCacheForm('save', 'initialState')

  // Clear the form state after closing window or navigating away
  $(window).on('beforeunload', function () {
    $form.uiCacheForm('clear')
  })
})
