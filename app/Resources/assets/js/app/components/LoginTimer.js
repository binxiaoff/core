// Lib Dependencies
var $ = require('jquery')

var LoginTimer = function() {
    var $timer = $('[data-countdown]')
    if($timer.length) {
        $('#form-connect-notifications-timer').show();
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
