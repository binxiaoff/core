/*
 * Borrower Operations
 */
var $ = require('jquery')
var Utility = require('Utility')
var $doc = $(document)

// Debounce AJAX timer
var borrowerOperationsAjaxTimer = 0
var winChild

// Process the filters
function loadOperations (outputType) {
  var $form = $('#form-myoperations-borrower-filters')

  // Get the form data as an object
  var formData = $form.serialize()

  // If outputType specified, add the secret action sauce to load in the child window
  if (outputType) {
    winChild = window.open($form.attr('action') + '?' + formData + '&action=' + outputType)
    return
  }

  // Debounce AJAX
  clearTimeout(borrowerOperationsAjaxTimer)
  borrowerOperationsAjaxTimer = setTimeout(function () {
    $.ajax({
      method: $form.attr('method'),
      url: $form.attr('action'),
      data: formData,
      dataType: 'json'
    }).done(function (data) {
      if (!outputType) {
        $('table.table-myoperations-borrower tbody').html(data.html_response).trigger('UI:visible')

        // Results
        if ($('table.table-myoperations-borrower tbody tr').length > 0) {
          $('#table-myoperations-borrower-empty').hide()
          $('table.table-myoperations-borrower').show()
          Utility.scrollTo('table.table-myoperations-borrower')

          // No results
        } else {
          $('#table-myoperations-borrower-empty').show()
          $('table.table-myoperations-borrower').hide()
          Utility.scrollTo('#table-myoperations-borrower-empty')
        }
      }
    })
  }, 200)
}

// Export the results
$doc.on(Utility.clickEvent, 'button[name="action"][value="export"]', function (event) {
  event.preventDefault()
  loadOperations('export')
})

// Print the results
$doc.on(Utility.clickEvent, 'button[name="action"][value="print"]', function (event) {
  event.preventDefault()
  loadOperations('print')
})

// Change an input
$doc.on('change', "#user-emprunteur-operations :input", function (event) {
  var action = $(this).attr('name').match(/filter\[(.*)]/)
  if (action !== null) {
    action = action[1]
    var start = new Date()
    var end = new Date()
    if (action == 'slide') {
      start.setMonth(end.getMonth() - $(this).val())
      // $('#filter-start').datepicker("setDate", start)
      // $('#filter-end').datepicker("setDate", end)
      $('#filter-start').pikaday("setDate", start)
      $('#filter-end').pikaday("setDate", end)
    } else if (action == 'year') {
      start = new Date($(this).val(), 0, 1)
      end = new Date($(this).val(), 11, 31)
      // $('#filter-start').datepicker("setDate", start)
      // $('#filter-end').datepicker("setDate", end)
      $('#filter-start').pikaday("setDate", start)
      $('#filter-end').pikaday("setDate", end)
    }

    loadOperations()
  }
})

// Show/hide details for individual rows
// @note handled in LenderOperations controller since functionality and selector is almost same

