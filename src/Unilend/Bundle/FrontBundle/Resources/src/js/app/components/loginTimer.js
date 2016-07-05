/**
 * Created by davidtessier on 05/07/2016.
 */

// Lib Dependencies
var $ = require('jquery')


var LoginTimer = function(){
    var self = this
    var TryCount = 0;

}

LoginTimer.prototype.templates = {
    previousTries : 0,
    waitingPeriod : 5,
    displayCaptcha : false
}


/*
 * jQuery Plugin
 */
$.fn.uiLoginTimer = function () {
    return this.each(function (i, elem) {
        if (!elem.hasOwnProperty('LoginTimer')) {
            new LoginTimer()
        }
    })
}

/*
 * jQuery Events
 */
$(document)
    .on('ready UI:visible', function (event) {
        $(event.target).find('[data-login]').not('.ui-uiLoginTimer').uiLoginTimer();
    })

module.exports = LoginTimer
