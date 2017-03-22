/*
 * Lender Operations controller
 */

var $ = require('jquery')
var Utility = require('Utility')
var Templating = require('Templating')
var ProjectNotifications = require('../components/ProjectNotifications')
var __ = require('__')

var $doc = $(document)

var operationsAjaxTimer = 0

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

  // My loans details
  if ($item.is('.table-myloans-item')) {
    var evTarget = $(event.target)
    if (!evTarget.is('.table-myloans-item-project-name')) {
      event.preventDefault()
      if (evTarget.parents('.table-myloans-item-controls').length) {
        if (evTarget.parents('.ui-show-table-myloans-item-activity').length || evTarget.is('.ui-show-table-myloans-item-activity')) {
          $details.find('.nav-tab-anchors li:first-child a').trigger('click')
        } else {
          $details.find('.nav-tab-anchors li:last-child a').trigger('click')
        }
      }
      if ($item.is('.ui-details-open')) {
        $table.parent().removeClass('ui-items-closed-grayscale')
        $item.removeClass('ui-details-open')
      } else {
        $table.parent().addClass('ui-items-closed-grayscale')
        $item.siblings().removeClass('ui-details-open')
        $item.addClass('ui-details-open')
        if ($details.find('.nav-tab-anchors li:first-child').is('.active')) {
          $details.trigger('MyLoansActivity:visible');

        }
      }
    }
  } else {

    event.preventDefault()

    // My operations details & My operations borrower details
    if ($details.length === 0) {
      // Get the details
      var details = Utility.convertStringToJson($item.attr('data-details'))
      var detailsItemsHtml = ''
      if ($item.is('.table-myoperations-item')) {
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
      }
      else if ($item.is('.table-myoperations-borrower-item')) {
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
      $item.after($details)
    }

    // Toggle details visibility
    if ($item.is('.ui-details-open')) {
      $item.removeClass('ui-details-open')
      $details.hide()
    } else {
      $item.addClass('ui-details-open')
      $details.show().trigger('UI:visible')
    }
  }
})

// Remove details before sorting
$doc.on('Sortable:sort:before', 'table.table-myoperations', function (event, elemSortable, columnName, direction) {
  var $table = $(this)
  var $details = $table.find('.table-myoperations-details')

  // Find any details rows and remove them before the sorting occurs
  if ($details.length > 0) $details.remove()

  // Find any items which are "open" and remove the class
  $table.find('.ui-details-open').removeClass('ui-details-open')
})


// Handling my loans items details before/after sorting
$doc.on('Sortable:sort:before', 'table.table-myloans', function () {

  var $table = $(this).find('tbody').first()
  $table.children('.ui-details-open').removeClass('ui-details-open')
  if ($table.parent().is('.ui-items-closed-grayscale')) $table.parent().removeClass('ui-items-closed-grayscale')

  // Store the details in a hidden div
  var $detailsElms = $table.children("[data-parent]")
  var $hiddenDiv = $('<div class="table-myloans-hidden-details" style="display: none;"></div>')
  $table.after($hiddenDiv)
  $detailsElms.appendTo($hiddenDiv)
})

$doc.on('Sortable:sort:after', 'table.table-myloans', function () {
  var $table = $(this).find('tbody').first()
  var $hiddenDiv = $table.next()
  var $detailsElms = $hiddenDiv.children()

  // Retrieve and place each detail after its respective parent
  for (i = 0; i < $detailsElms.length; i++) {
    var id = $detailsElms[i].getAttribute('data-parent');
    $table.find('#'+ id).after($detailsElms[i])
  }
  $hiddenDiv.remove()
})

$doc.on('MyLoansActivity:visible', '.table-myloans-view-details', function () {
  var $list = $(this).find('.list-notifications')
  $list.uiPaginate()
  $list.trigger('UI:visible')
})



