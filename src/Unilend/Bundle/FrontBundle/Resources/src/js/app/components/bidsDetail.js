// Lib Dependencies
var $ = require('jquery')



var AjaxCall = function(elem, rate, project) {

    $.ajax({
        type: 'POST',
        url: '/projects/bids/'+project+'/'+rate,
        success: function(response) {
            $(response).insertAfter(elem);
        },
        error: function() {
            console.log("error retrieving datas");
        }
    });

}

$(document).on('click', '.bids-row', function() {

    var ClickedElement = $(this);
    var CurrentProject = $('.table-alloffersoverview').attr('data-current-project');
    var ClickedRate = ClickedElement.attr('data-bid-rate');
    var CurrentDetail = $('.detail-table-content');

    if ( $('.detail-table-content').length && ! ClickedElement.hasClass('active-row') ) {
        ClickedElement.addClass('active-row');
        CurrentDetail.prev().removeClass('active-row');
        CurrentDetail.remove();
        AjaxCall(ClickedElement, ClickedRate, CurrentProject);
    }

    else if ( ClickedElement.hasClass('active-row') ) {
        ClickedElement.removeClass('active-row');
        CurrentDetail.remove();
    }

    else {
        $('.active-row').removeClass('active-row');
        $('.detail-table-content').remove();
        ClickedElement.addClass('active-row');
        AjaxCall(ClickedElement, ClickedRate, CurrentProject);
    }



});


$(document).on('click', '.rejected-offers', function() {
    $('.rejected-row').show();
    $('.rejected-offers').remove();
});