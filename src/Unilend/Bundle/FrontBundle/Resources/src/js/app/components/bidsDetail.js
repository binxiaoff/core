// Lib Dependencies
var $ = require('jquery')



$(document).on('click', '.bids-row', function() {
    var el = $(this);
    console.log(el.attr('data-sortable-rate'));
    if(el.hasClass('active-row')) {
        el.removeClass('active-row');
    }
    else {
        el.addClass('active-row');
    }
});
