
var $ = require('jquery')

var LoginCaptcha = function() {
    var self = this
}

/*
 * jQuery Plugin
 */
$.fn.uiLoginCaptcha = function () {
    new LoginCaptcha()
    var LoginButton = $('div[data-captcha-related]');
}

/*
 * jQuery Events
 */
$(document)
    .on('ready UI:visible', function (event) {
        $(event.target).find('[data-captcha]').not('.ui-uiLoginCaptcha').uiLoginCaptcha();
    })

module.exports = LoginCaptcha
