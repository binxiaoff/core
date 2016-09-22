/*
 * Bids Details on Project details page
 */

// Lib Dependencies
var $ = require('jquery')
var Utility = require('Utility')

var $doc = $(document)

// Instantiate on ready
$doc.on('ready', function () {
  var bidDetailsAjaxTimer = 0

  // AJAX to retrieve bid details results
  var AjaxCall = function(elem, rate, project, prev) {
    clearTimeout(bidDetailsAjaxTimer)

    bidDetailsAjaxTimer = setTimeout(function () {
      $.ajax({
        type: 'POST',
        url: '/projects/bids/' + project + '/' + rate,
        success: function(response) {
          var $resp = $(response)

          // Scroll to the active row
          Utility.scrollTo('.active-row')

          // Add response HTML to DOM
          $resp.insertAfter(elem)

          // Initiate any interactive bits within
          $resp.trigger('UI:visible')

          // If clicked on user "myoffer"
          if (prev !== false) {
            // Hide any previously focused rows
            $('.is-focused').removeClass('is-focused')

            // Get the new row to focus to
            var FocusedRow = $('[data-sortable-detail-id="' + prev + '"]')
            FocusedRow.addClass('is-focused')
            if (FocusedRow.length == 0) {
              FocusedRow = $('.ui-current-user-involved').attr('data-sortable-detail-id', prev)
            }

            // Scroll to the focused row
            Utility.scrollTo(FocusedRow, function () {
              FocusedRow.removeClass('is-focused')
            })
          }
        },
        error: function() {
          console.log("error retrieving datas")
        }
      })
    }, 200)
  }

  // Click on a main row to view details
  $doc.on('click', '.bids-row, .my-offers-bid-row', function() {
    var ClickedElement = $(this)
    var CurrentProject = $('.table-alloffersoverview').attr('data-current-project')
    var ClickedRate = ClickedElement.attr('data-bid-rate')
    var CurrentDetail = $('.detail-table-item')
    var Preview = false

    // Check if clicked row is from the main table
    if (ClickedElement.is('.bids-row')) {

      // check if a row is already active and disactive it before openning the clicked one
      if ($('.detail-table-item').length && ! ClickedElement.hasClass('active-row')) {
        ClickedElement.addClass('active-row')
        CurrentDetail.prev().removeClass('active-row')
        CurrentDetail.remove()
        AjaxCall(ClickedElement, ClickedRate, CurrentProject, Preview)

      // close current active row if user click on it
      } else if (ClickedElement.hasClass('active-row')) {
        ClickedElement.removeClass('active-row')
        CurrentDetail.remove()

      // there is no active row
      } else {
        $('.active-row').removeClass('active-row')
        $('.detail-table-item').remove()
        ClickedElement.addClass('active-row')
        AjaxCall(ClickedElement, ClickedRate, CurrentProject, Preview)
      }

    // Check if user click on his own bid table
    } else if (ClickedElement.hasClass('my-offers-bid-row')) {
      // remove active table if exist
      if ($('.active-row').length) {
        $('.active-row').removeClass('active-row')
        $('.detail-table-item').remove()
      }
      var TargetedRow = ClickedElement.find('a').attr('data-related-bid-rate')
      Preview = ClickedElement.children('.table-myoffers-item-id').html()
      TargetedRow = TargetedRow.replace(',','.')
      ClickedRate = Number(TargetedRow)
      ClickedElement = $('[data-bid-rate="'+ClickedRate+'"]')
      ClickedElement.addClass('active-row')
      AjaxCall(ClickedElement, ClickedRate, CurrentProject, Preview)
    }
  })

  // Show rejected offers
  $doc.on('click', '.rejected-offers', function(event) {
    event.preventDefault()
    $('.rejected-row').show()
    $('.rejected-offers').remove()
  })
})
