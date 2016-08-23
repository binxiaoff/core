// Lib Dependencies
var $ = require('jquery')

var LoginTimer = function() {
    if($('[data-countdown]').length) {
        $('#form-connect-notifications').show();
        $('[data-captcha-related]').hide();
    }
}

/*
 * jQuery Plugin
 */
$.fn.uiLoginTimer = function () {
    new LoginTimer()
}

/*
 * jQuery Events
 */
$(document)
    .on('ready UI:visible', function (event) {
        $(event.target).find('[data-login]').not('.ui-uiLoginTimer').uiLoginTimer();
    })

module.exports = LoginTimer
