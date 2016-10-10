/*
 * Bids Details on Project details page
 */

// Lib Dependencies
var $ = require('jquery')
var Utility = require('Utility')

var $doc = $(document)

// Clear active rows and detail
function clearActiveRow () {
  if ($('.table-alloffersoverview .active-row').length) {
    $('.table-alloffersoverview .active-row').removeClass('active-row')
    $('.table-alloffersoverview .detail-table-item').remove()
  }
}

// Show all rejected offer rows for rates
function showAllRejectedRows () {
  $('.table-alloffersoverview tr.view-rejected-rows').remove()
  $('.table-alloffersoverview .rate-row.rejected-row').show()
}

// Focus on a specific rate's bid element
function focusBid (rate, bidId, enableScroll) {
  // Get the new bid to focus to
  var $targetRate = $('[data-bid-rate="' + rate  + '"]')
  var $targetBid = $('[data-sortable-detail-id="' + bidId + '"]')

  // @debug
  // console.log(rate, $targetRate, bidId, $targetBid)

  if ($targetRate.length) {
    // If the target rate is in a tab/collapsable element which is hidden, it should be made visible via this `revealElem` method
    Utility.revealElem($targetRate)

    // Show all rejected rows if this row was rejected
    if ($targetRate.is('.rejected-row')) {
      showAllRejectedRows()
    }

    // Ensure that the target rate's row is active and visible
    if (!$targetRate.is('.active-row')) {
      $('.active-row').removeClass('.active-row')
      $targetRate.addClass('active-row').show()
    }

    // Scroll to the target rate, only if the bid wasn't specified
    if (!$targetBid.length && enableScroll) {
      Utility.scrollTo($targetRate, undefined, undefined, undefined, {
        centerTargetInElem: true
      })
    }
  }

  if ($targetBid.length) {
    // If the target bid is in a tab/collapsable element which is hidden, it should be made visible via this `revealElem` method
    Utility.revealElem($targetBid)

    // Not already focused
    if (!$targetBid.is('is-focus')) {
      // Remove any previously focused items
      $('.is-focus').removeClass('is-focus')

      // Set this one to focused
      $targetBid.addClass('is-focus')
    }

    // Always scroll to the target bid
    Utility.scrollTo($targetBid, undefined, undefined, undefined, {
      centerTargetInElem: true
    })
  }
}

// Instantiate on ready
$doc.on('ready', function () {
  var bidDetailsAjaxTimer = 0

  // AJAX to retrieve bid details results
  var AjaxCall = function(elem, projectId, rate, bidId) {
    clearTimeout(bidDetailsAjaxTimer)

    // Before running ajax, ensure that the item isn't already rendered on the page
    if (bidId && $('[data-sortable-detail-id="' + bidId + '"]').length > 0) {
      // Hide any previously focused rows
      $('.is-focused').removeClass('is-focused')

      // Focus the bid
      focusBid(rate, bidId, !!bidId)
      return
    }

    // @debug
    // console.log('AjaxCall', {
    //   elem: elem,
    //   projectId: projectId,
    //   rate: rate,
    //   bidId: bidId,
    //   bidElem: $('[data-sortable-detail-id="' + bidId + '"]')
    // })

    // Debounce AJAX
    bidDetailsAjaxTimer = setTimeout(function () {
      $.ajax({
        type: 'POST',
        url: '/projects/bids/' + projectId + '/' + rate,
        success: function(response) {
          var $resp = $(response)

          // Add response HTML to DOM
          $resp.insertAfter(elem)

          // Initiate any interactive bits within
          $resp.trigger('UI:visible')

          // Focus on the bid
          focusBid(rate, bidId, !!bidId)
        },
        error: function() {
          console.log("error retrieving datas")
        }
      })
    }, 200)
  }

  // Click on a main row to view details
  $doc.on('click', '.table-alloffersoverview tr.rate-row, .table-myoffers .my-offers-bid-row', function() {
    var ClickedElement = $(this)
    var CurrentProject = $('.table-alloffersoverview').attr('data-current-project')
    var ClickedRate = ClickedElement.attr('data-bid-rate')
    var CurrentDetail = $('.table-alloffersoverview .detail-table-item')
    var bidId = false

    // Check if clicked row is from the main table
    if (ClickedElement.is('.rate-row')) {

      // check if a row is already active and deactivate it before opening the clicked one
      if ($('.table-alloffersoverview .detail-table-item').length && !ClickedElement.hasClass('active-row')) {
        clearActiveRow()
        ClickedElement.addClass('active-row')
        AjaxCall(ClickedElement, CurrentProject, ClickedRate, bidId)

      // close current active row if user click on it
      } else if (ClickedElement.hasClass('active-row')) {
        clearActiveRow()

      // there is no active row
      } else {
        ClickedElement.addClass('active-row')
        AjaxCall(ClickedElement, CurrentProject, ClickedRate, bidId)
      }

    // Check if user click on his own bid table
    } else if (ClickedElement.hasClass('my-offers-bid-row')) {
      // Remove active table if exist
      clearActiveRow()
      var TargetedRow = ClickedElement.find('a').attr('data-related-bid-rate')
      bidId = ClickedElement.children('.table-myoffers-item-id').html()
      TargetedRow = TargetedRow.replace(',','.')
      ClickedRate = Number(TargetedRow)
      ClickedElement = $('[data-bid-rate="' + ClickedRate + '"]')
      ClickedElement.addClass('active-row')
      AjaxCall(ClickedElement, CurrentProject, ClickedRate, bidId)
    }
  })

  // Show rejected offers
  $doc.on('click', '.table-alloffersoverview .view-rejected-rows', function(event) {
    event.preventDefault()
    showAllRejectedRows()
  })
})
