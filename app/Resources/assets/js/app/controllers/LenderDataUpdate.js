var $ = require('jquery')
var Utility = require('Utility')
var $doc = $(document)

$doc.on('ready', function () {
  // Only apply this controller logic if this element is within the DOM
  if (!$('.form-data-update-info-edit').length) {
    return
  }

  var progressBarStep = 1

  // Clear FormValidation messages when the panel is hidden
  $doc.on('hidden.bs.collapse', '.form-data-update-info-edit [data-formvalidation]', function (event) {
    // Clear form validation errors when it is hidden
    $(this).uiFormValidation('clear')
  })

  // Update the progress bar length
  $doc.on(Utility.clickEvent, '.data-update-continue-btn', function () {
    progressBarStep = progressBarStep + 1
    $('.data-update-progress').uiProgressBar('setCurrent', progressBarStep, false)
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

    // I've removed the data-toggle/data-target from the save button and are managing the show/hide of the panel here
    $viewArea.collapse('show')
    $editArea.collapse('hide')
  })

  // $doc.on(Utility.clickEvent, '#lender-data-update-identity-save-btn', function (event) {
  //   var $inputs = $('#data-update-info-edit :input')
  //   var isModified = false

  //   $inputs.each(function (index, input) {
  //     var submittedValue
  //     var $input = $(input)
  //     var originalValue = $input.data('original-value')

  //     if ($input.getType() === 'radio') {
  //       originalValue = $input.parent('div').data('original-value')
  //     }

  //     if (originalValue) {
  //       switch ($input.getType()) {
  //         case 'radio':
  //           submittedValue = $('input[name="' + $input.attr('name') + '"]:checked').val()
  //           break

  //         case 'select':
  //           submittedValue = ~~$input.find(':selected').val()
  //           break

  //         case 'text':
  //           submittedValue = $input.val()
  //           break
  //       }

  //       if (originalValue !== submittedValue) {
  //         isModified = true
  //         return false
  //       }
  //     }
  //   })

  //   if (isModified) {
  //     $('#data-update-id-doc-view').collapse('hide')
  //     $('#data-update-id-doc-edit').collapse('show')
  //   }
  // })
})
