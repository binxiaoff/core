/*
 * Borrower Operations
 */
var $ = require('jquery')
var Utility = require('Utility')
var $doc = $(document)

$doc.on('ready', function () {
  // Process the filters
  function loadOperations () {
    var form = $('#form-myoperations-borrower-filters')

    $.ajax({
      method: form.attr('method'),
      url: form.attr('action'),
      data: form.serialize(),
      dataType: 'json'
    }).done(function (data) {
      if (data.count) {
        $('table.table-myoperations-borrower').show().next('.message-info').hide()
      }
      else {
        $('table.table-myoperations-borrower').hide().next('.message-info').show()
      }
      $('table.table-myoperations-borrower tbody').html(data.html_response)
    })
  }

  // Change an input
  $("#user-emprunteur-operations :input").change(function () {
    var action = $(this).attr('name').match(/filter\[(.*)]/)
    if (action !== null) {
      action = action[1]
      var start = new Date()
      var end = new Date()
      if (action == 'slide') {
        start.setMonth(end.getMonth() - $(this).val())
        $('#filter-start').datepicker("setDate", start)
        $('#filter-end').datepicker("setDate", end)
      } else if (action == 'year') {
        start = new Date($(this).val(), 0, 1)
        end = new Date($(this).val(), 11, 31)
        $('#filter-start').datepicker("setDate", start)
        $('#filter-end').datepicker("setDate", end)
      }

      loadOperations()
    }
  })

  // Show/hide details for individual rows
  // @note handled in LenderOperations controller since functionality and selector is almost same
})
