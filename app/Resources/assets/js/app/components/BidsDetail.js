// Lib Dependencies
var $ = require('jquery')

var AjaxCall = function(elem, rate, project, prev) {
    $.ajax({
        type: 'POST',
        url: '/projects/bids/' + project + '/' + rate,
        success: function(response) {
            $('html, body').animate({scrollTop: ($('.active-row').offset().top) - 100}, 500)
            $(response).insertAfter(elem)
            $('.ui-has-tooltip, [data-toggle="tooltip"]').tooltip()

            // if clicked on user "myoffer"
            if (prev !== false) {
                var FocusedRow = $('[data-sortable-detail-id="' + prev + '"]')
                FocusedRow.addClass('is-focused')
                if(FocusedRow.length == 0) {
                    FocusedRow = $('.ui-current-user-involved').attr('data-sortable-detail-id', prev)
                }
                $('html, body').animate({
                    scrollTop: (FocusedRow.offset().top)-200
                }, 500, function() {
                    FocusedRow.removeClass('is-focused')
                })
            }
        },
        error: function() {
            console.log("error retrieving datas")
        }
    })
}

$(document).on('click', '.bids-row, .my-offers-bid-row', function() {
    var ClickedElement = $(this),
        CurrentProject = $('.table-alloffersoverview').attr('data-current-project'),
        ClickedRate = ClickedElement.attr('data-bid-rate'),
        CurrentDetail = $('.detail-table-content'),
        Preview = false

    // Check if clicked row is from the main table
    if (ClickedElement.hasClass('bids-row')) {
        // check if a row is already active and disactive it before openning the clicked one
        if ($('.detail-table-content').length && ! ClickedElement.hasClass('active-row')) {
            ClickedElement.addClass('active-row')
            CurrentDetail.prev().removeClass('active-row')
            CurrentDetail.remove()
            AjaxCall(ClickedElement, ClickedRate, CurrentProject, Preview)
        }
        // close current active row if user click on it
        else if (ClickedElement.hasClass('active-row')) {
            ClickedElement.removeClass('active-row')
            CurrentDetail.remove()
        }
        // there is no active row
        else {
            $('.active-row').removeClass('active-row')
            $('.detail-table-content').remove()
            ClickedElement.addClass('active-row')
            AjaxCall(ClickedElement, ClickedRate, CurrentProject, Preview)
        }
    }
    // Check if user click on his own bid table
    else if (ClickedElement.hasClass('my-offers-bid-row')) {
        // remove active table if exist
        if ($('.active-row').length) {
            $('.active-row').removeClass('active-row')
            $('.detail-table-content').remove()
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

$(document).on('click', '.rejected-offers', function() {
    $('.rejected-row').show()
    $('.rejected-offers').remove()
})
