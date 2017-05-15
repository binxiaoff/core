/*
 * Lender Operations controller
 */

var $ = require('jquery')
var Utility = require('Utility')
var Templating = require('Templating')
var __ = require('__')

var $doc = $(document)

var operationsAjaxTimer = 0
var lastFormState = {}

// Changing filters will change the contents of the loans table
// TMA-1182 Added extra filters to avoid firing change event on input before initilised pikaday
$doc.on('change', '#dashboard-lender-operations :input:not(.ui-has-datepicker, [data-ui-datepicker])', function (event) {
  var $input = $(this)
  var $form = $input.closest('form')
  var filterAction = $input.attr('name').match(/filter\[(.*)\]/i)[1]
  $form.children(':input.id_last_action').val(filterAction)

  // Disable
  if ($('#dashboard-lender-operations').prop('disabled')) return

  // Debounce AJAX
  clearTimeout(operationsAjaxTimer)
  operationsAjaxTimer = setTimeout(function () {
    $('#dashboard-lender-operations').prop('disabled', true)
    $.ajax({
      method: $form.attr('method'),
      url: $form.attr('action'),
      data: $form.serialize(),
      success: function (data) {
        // Data has object with props target and template
        // After adding the template, need to trigger that it's visible for any other components to auto-initialise
        $('article#' + data.target).html(data.template).trigger('UI:visible')

        $('#dashboard-lender-operations').removeProp('disabled')
      }
    })
  }, 400)
})

// Show/hide details for individual rows
$doc.on(Utility.clickEvent, 'tr[data-details]', function (event) {
  var $item = $(this)
  var $table = $item.parents('tbody').first()
  var $details = $table.find('[data-parent="' + $item.attr('id') + '"]')
  event.preventDefault()

  // Hide details
  if ($item.is('.ui-details-open')) {
    if ($details.length > 0) {
      $details.slideUp(200, function () {
        $item.removeClass('ui-details-open')
      })
    } else {
      $item.removeClass('ui-details-open')
    }

  // Show details
  } else {
    if ($details.length === 0) {
      // Get the details
      var details = Utility.convertStringToJson($item.attr('data-details'))
      var detailsItemsHtml = ''

      // My loans details
      if ($item.is('.table-myloans-item')) {
        $.each(details.loans, function (i, item) {
          // Generate documents HTML for loan
          var docsHtml = ''
          $.each(item.documents, function (j, doc) {
            docsHtml += Templating.replace('<a href="{{ url }}" class="loan-doc loan-doc-{{ type }}" title="{{ label }}" data-toggle="tooltip"><span class="sr-only">{{ label }}</span></a> ', doc)
          })

          // Generate loan details HTML
          detailsItemsHtml += Templating.replace('<tr class="table-myloans-details-item">\
            <td class="table-myloans-item-amount">\
              {{ amount }}&nbsp;€\
            </td>\
            <td class="table-myloans-item-interest">\
              {{ rate }}%\
            </td>\
            <td class="table-myloans-item-documents">\
              {{ documents }}\
            </td>\
          </tr>', {
            amount: __.localizedPrice(item.amount),
            rate: __.localizedNumber(item.rate, 1),
            documents: docsHtml
          })
        })

        $details = $('<tr class="table-myloans-details" data-parent="' + $item.attr('id') + '"><td colspan="8"><table class="table-myloans-details-list">' + detailsItemsHtml + '</table></td></tr>')

      // My operations details (lender)
      } else if ($item.is('.table-myoperations-item')) {
        // Build the list of items
        $.each(details.items, function (i, item) {
          // @todo may need to programmatically change the currency here
          // @note this relies on the backend to supply the correcly translated text for labels
          var classItem = (item.value >= 0 ? 'ui-value-positive' : 'ui-value-negative')
          detailsItemsHtml += Templating.replace('<dt>{{ label }}</dt><dd><span class="{{ classNames }}">{{ value }}&nbsp;€</span></dd>', {
            label: item.label,
            value: __.formatNumber(item.value, 2, true),
            classNames: classItem
          })
        })

        // Build element and add to DOM
        $details = $('<tr class="table-myoperations-details" data-parent="' + $item.attr('id') + '" style="display: none;"><td colspan="2">' + details.label + '</td><td colspan="3"><dl>' + detailsItemsHtml + '</dl></td><td>&nbsp;</td></tr>')

      // My operations borrower details
      } else if ($item.is('.table-myoperations-borrower-item')) {
        // Build the list of items
        $.each(details.items, function (i, item) {
          // @todo may need to programmatically change the currency here
          // @note this relies on the backend to supply the correcly translated text for labels
          var classItem = (item.value >= 0 ? 'ui-value-positive' : 'ui-value-negative')
          detailsItemsHtml += Templating.replace('<dt>{{ label }}</dt><dd><span class="{{ classNames }}">{{ value }}&nbsp;€</span></dd>', {
            label: item.label,
            value: __.formatNumber(item.value, 2, true),
            classNames: classItem
          })
        })

        // Build element and add to DOM
        $details = $('<tr class="table-myoperations-borrower-details" data-parent="' + $item.attr('id') + '" style="display: none;"><td>' + details.label + '</td><td colspan="3"><dl>' + detailsItemsHtml + '</dl></td><td>&nbsp;</td></tr>')
      }

      // Partner project details
      else if ($item.is('.table-projects-item')) {

        // Check columns to show depending on user level
        var colspanAuthor = 1
        var colspanText   = 5
        if (!$('body').is('.ui-user-type-partner-admin')) {
          colspanAuthor = 2
          colspanText   = 4
        }

        // Inherit parent row color
        var oddEvenClass = 'odd'
        if ($item.is('.even')) {
          oddEvenClass = 'even'
        }

        // Generate HTML for memos
        $.each(details, function (i, item) {
          var isReadClass = 'ui-details-memo-unread'
          if (item.status === 'read') {
            isReadClass = 'ui-details-memo-read'
          }
          detailsItemsHtml += Templating.replace('<tr class="details-memo ui-details-memo-closed ' + oddEvenClass + ' ' + isReadClass +'">\
            <td class="details-memo-author" colspan="' + colspanAuthor + '">{{ author }}</td>\
            <td class="details-memo-date">{{ date }}</td>\
            <td class="details-memo-text" colspan="' + colspanText + '">{{ text }}</td>\
          </tr>', {
            author: item.author,
            date: __.localizedDate(item.date.date),
            text: item.content
          })
        })

        // Check columns to show depending on user level
        var colspanDetails = 8
        if (!$('body').is('.ui-user-type-partner-admin')) {
          colspanDetails = 7
        }

        $details = $('<tr class="table-projects-details" data-parent="' + $item.attr('id') + '" style="display: none;"><td colspan="' + colspanDetails + '"><table>' + detailsItemsHtml + '</table></td></tr>')
      }

      // Attach details for all cases above
      $item.after($details)
    }

    // Show
    $item.addClass('ui-details-open')
    $details.slideDown(200).trigger('UI:visible')
  }
})

// Remove details before sorting
$doc.on('Sortable:sort:before', 'table.table-myoperations, table.table-myloans, .table.table-projects', function (event, elemSortable, columnName, direction) {
  var $table = $(this)
  var $details = $table.find('.table-myoperations-details, .table-myloans-view-details, .table-projects-details')

  // Find any details rows and remove them before the sorting occurs
  if ($details.length > 0) $details.remove()

  // Find any items which are "open" and remove the class
  $table.find('.ui-details-open').removeClass('ui-details-open')
})

$doc.on(Utility.clickEvent, '.ui-details-memo-unread', function () {
  // TODO - AJAX FOR MARKING MEMO AS READ
  $(this).removeClass('ui-details-memo-unread').addClass('ui-details-memo-read')
})
$doc.on(Utility.clickEvent, '.ui-details-memo-closed', function () {
  $(this).removeClass('ui-details-memo-closed').addClass('ui-details-memo-open')
})
$doc.on(Utility.clickEvent, '.ui-table-projects-item-memos-unread', function () {
  $(this).removeClass('ui-table-projects-item-memos-unread')
})
