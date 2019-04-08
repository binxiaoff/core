var $ = require('jquery')
var Utility = require('Utility')
var $doc = $(document)
var CacheForm = require('CacheForm')
var __ = require('__')

$doc.on('ready', function () {
  // Only apply this controller logic if this element is within the DOM
  if (!$('.form-data-update-info-edit').length) {
    return
  }

  var $form = $('form.form-data-update-info-edit[data-cacheform]')
  var formIsEdited = false
  var progressBarStep = 1
  var TRANS_DOCUMENT_MODIFIED = 'Vous venez de modifier votre document. ce document sera prochainement validé par nos équipes.'

  // Clear FormValidation messages when the panel is hidden
  $doc.on('hidden.bs.collapse', '.form-data-update-info-edit [data-formvalidation]', function (event) {
    // Clear form validation errors when it is hidden
    $(this).uiFormValidation('clear')
  })

  // Show the reset button in identity document upload or Kbis upload section when the panel is hidden
  $doc.on('hidden.bs.collapse', '#data-update-id-doc-edit, #data-update-kbis-doc-edit', function (event) {
    $(this).find('button[type="reset"]').removeClass('hidden')
  })

  // Show the continue button in funds origins section when the panel is hidden
  $doc.on('hidden.bs.collapse', '#data-update-funds-origin-edit', function (event) {
    $(this).find('button[type="reset"]').removeClass('hidden')
    $('#data-update-funds-origin-view').find('button.data-update-continue-btn').removeClass('hidden')
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

    var hasUploadFile = false
    $inputs.each(function (index, input) {
      var $input = $(input)
      var text = '';
      elementId = $input.data('impacted-element-id')

      if ($input.getType() === 'radio') {
        elementId = $input.parent('div, li').data('impacted-element-id')
      }

      if (elementId || 'file' === $input.getType()) {
        switch ($input.getType()) {
          case 'radio':
            var checkedOption = $('input[name="' + $input.attr('name') + '"]:checked')
            text = $("label[for='" + checkedOption.attr('id') + "']").text()
            break

          case 'select':
            text = $input.find(':selected').text()
            break

          case 'file':
            if ($input.closest('.ui-fileattach-item').attr('title')) {
              hasUploadFile = true || hasUploadFile
            }
            break

          default:
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

    if (hasUploadFile) {
      var translation = 'identityDocumentModified';
      if ('data-update-kbis-doc-view' === $viewArea.attr('id')) {
        translation = 'kbisDocumentModified';
      }
      $viewArea.find('.document-information-area').html('<p>' + __.__(TRANS_DOCUMENT_MODIFIED, translation) + '</p>')
    }
  })

  // Show the upload identity documents panel if the users have modified their personal info and hide the cancel button in the the upload panel
  $doc.on(Utility.clickEvent, '#lender-data-update-identity-save-btn', function (event) {

    var isModified = hasChanges($('#data-update-info-edit :input'))

    if (isModified) {
      $('#data-update-id-doc-view').collapse('hide')
      $('#data-update-id-doc-edit').collapse('show')
      $('#data-update-id-doc-edit button[type="reset"]').addClass('hidden')
    }
  })

  // Show the upload Kbis documents panel if the users have modified their companies info and hide the cancel button in the the upload panel
  $doc.on(Utility.clickEvent, '#lender-data-update-legal-entity-identity-save-btn, #lender-data-update-legal-entity-address-save-btn', function (event) {

    var isModified = hasChanges($('#data-update-legal-entity-identity-edit :input, #data-update-main-address-edit :input'))

    if (isModified) {
      $('#data-update-kbis-doc-view').collapse('hide')
      $('#data-update-kbis-doc-edit').collapse('show')
      $('#data-update-kbis-doc-edit button[type="reset"]').addClass('hidden')
    }
  })

  function hasChanges(inputs) {
    var isModified = false

    inputs.each(function (index, input) {
      var $input = $(input)
      var originalValue = $input.data('original-value')

      if ($input.getType() === 'radio') {
        originalValue = $input.parent('div').data('original-value')
      }

      if (originalValue) {
        var submittedValue
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

        if (originalValue != submittedValue) {
          isModified = true || isModified;
        }
      }
    })

    return isModified
  }

  // Save the initial form state
  $form.uiCacheForm('save', 'initialState')

  // Clear the form state after closing window or navigating away
  $(window).on('beforeunload', function () {
    $form.uiCacheForm('clear')
  })

  // Synchronise the company names shown in the form
  $doc.on('hidden.bs.collapse', '#data-update-legal-entity-identity-edit', function (event) {
    $('#data-update-section span.company-name').html($('#form-data-update-legal-entity-identity-company-name').val())
  })
})
