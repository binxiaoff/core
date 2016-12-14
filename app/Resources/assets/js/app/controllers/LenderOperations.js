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

// Show/hide details for individual rows (myoperations lender and borrower)
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

      // My operations details (lender)
      if ($item.is('.table-myoperations-item')) {
        // Build the list of items
        $.each(details.items, function (i, item) {
          // @todo may need to programmatically change the currency here
          // @note this relies on the backend to supply the correcly translated text for labels
          var classItem = (item.value >= 0 ? 'ui-value-positive' : 'ui-value-negative')
          detailsItemsHtml += Templating.replace('<dt>{{ label }}</dt><dd><span class="{{ classNames }}">{{ value }}€</span></dd>', {
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
          detailsItemsHtml += Templating.replace('<dt>{{ label }}</dt><dd><span class="{{ classNames }}">{{ value }}€</span></dd>', {
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

    // Show
    $item.addClass('ui-details-open')
    $details.slideDown(200).trigger('UI:visible')
  }
})

// Show/hide details for myloans items (docs and activity)
$doc.on(Utility.clickEvent, '.table-myloans-item', function (event) {
  var $target = $(event.target)
  var $item = $(this)
  var loanId = parseInt($item.attr('data-loan-id'), 10)
  var loanIdElem = '#' + $item.attr('id')
  var $table = $item.parents('tbody').first()
  var $details = $table.find('[data-parent="' + loanIdElem + '"].table-myloans-view-details')
  var $detailsActiveTab = $details.find('[role="tabpanel"].active')
  var activeTab = 'activity'

  // Only prevent default if not clicking on the project name
  if ($target.closest('.table-myloans-item-project a').length > 0) {
    return true
  }

  event.preventDefault()

  // Check if target is documents or info button
  // -- Show the loans/docs
  if ($target.closest('.ui-show-table-myloans-item-docs').length > 0) {
    activeTab = 'docs'
  } else if ($target.closest('.ui-show-table-myloans-item-activity').length > 0) {
    activeTab = 'activity'
  }

  // By default clicking on a loan will open it, however if it is already open and details loaded with tab active, clicking it will close the details
  if (activeTab && $details.length > 0 && $item.is('.ui-details-open') && $('a[href="' + loanIdElem + '-details-' + activeTab + '"][data-toggle="tab"]').first().parent().is('li.active')) {
    activeTab = false
  }

  // Toggle info
  if (activeTab) {
    function showDetailsActiveTab (activeTabName) {
      switch (activeTabName) {
        case 'docs':
          $('a[href="' + loanIdElem + '-details-docs"][data-toggle="tab"]').first().tab('show')
          break

        case 'activity':
          $('a[href="' + loanIdElem + '-details-activity"][data-toggle="tab"]').first().tab('show')
          break
      }

      // Show
      $item.find('.ui-show-table-myloans-item-activity').addClass('active')
      $item.addClass('ui-details-open')
      $details.slideDown(200).trigger('UI:visible')
    }

    // Download the details first
    if ($details.length === 0) {
      // Start spinner

      // Get the HTML for the loan's details
      $.ajax({
        url: '/operations/loanDetails/',
        method: 'get',
        data: {
          id: loanId
        },
        dataType: 'html',
        // Uncomment below to not use global AJAX spinner
        // global: false,
        success: function (data, textStatus) {
          // Inject into page
          if (data && textStatus === 'success') {
            $details = $(data)
            $item.after($details)
            $details.slideDown(200).trigger('UI:visible')
            showDetailsActiveTab(activeTab)
          }
        },
        error: function (err, textStatus, xhr) {
          // Anything to do here?
        },
        complete: function () {
          // Stop spinner
        }
      })
    } else {
      showDetailsActiveTab(activeTab)
    }

  // Hide details
  } else {
    function afterHideDetails() {
      $item.removeClass('ui-details-open')
      $item.find('.ui-show-table-myloans-item-activity').removeClass('active')
    }

    if ($details.length > 0) {
      $details.slideUp(200, function () {
        afterHideDetails()
      })
    } else {
      afterHideDetails()
    }
  }
})

// Load a myloans details activity paginated page
$doc.on(Utility.clickEvent, '.table-myloans-view-details [data-page]', function (event) {
  event.preventDefault()
  var $details = $(this).parents('.table-myloans-view-details')
  var $detailsActivity = $details.find('.table-myloans-item-details-activity')
  var loanId = $details.attr('data-loan-id')
  var page = $(this).attr('data-page')
  var $item = $('#loan-' + loanId)

  // Start spinner

  // Get only the HTML for the paginated activity page
  $.ajax({
    url: '/operations/loanActivity',
    method: 'get',
    data: {
      id: loanId,
      page: page
    },
    dataType: 'html',
    // Uncomment below to not use global AJAX spinner
    // global: false,
    success: function (data, textStatus) {
      if (textStatus === 'success') {
        var $page = $(data)
        $detailsActivity.html(data).trigger('UI:visible')
      }
    },
    error: function (err, textStatus, xhr) {
      // Anything to do here?
    },
    complete: function () {
      // Stop spinner
    }
  })
})

// Remove details before sorting
$doc.on('Sortable:sort:before', 'table.table-myoperations, table.table-myloans', function (event, elemSortable, columnName, direction) {
  var $table = $(this)
  var $details = $table.find('.table-myoperations-details, .table-myloans-view-details')

  // Find any details rows and remove them before the sorting occurs
  if ($details.length > 0) {
    $details.remove()
  }

  // Find any items which are "open" and remove the class
  $table.find('.ui-details-open').removeClass('ui-details-open')
})
