var $ = require('jquery')

$body = $("body");

$(document).on({
    ajaxStart: function() { $body.addClass("loading");
        var posX = "50";
        var posY = "50";

        // If spinner is fired on single project page
        if($('#alloffers-table').length) {
            var pixelFromLeftSide = $('#alloffers-table').width() / 2;
            pixelFromLeftSide += $('#alloffers-table').offset().left;
            posX = (pixelFromLeftSide/window.innerWidth)*100;
        }
        $('#floatingCirclesG').css({
            'top': posY + '%',
            'left': posX + '%'
        });
    },
    ajaxStop: function() { $body.removeClass("loading"); }
});
