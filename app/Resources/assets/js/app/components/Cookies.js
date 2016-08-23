// Lib Dependencies
var $ = require('jquery')

var setCookie = function() {

    $.ajax({
        type: 'POST',
        url: '/accept-cookies',
        success: function(response) {
            var CookieWrap = $('[data-cookies]');
            $(CookieWrap).hide();
        }
    });

}

$(document).on('click', '[data-cookies-accept]', function() {
    setCookie();
});
