/*
 * Unilend Simple Count Down
 */

var SimpleCountDown = function(callback) {
    var self = this;
    var elem = $('[data-countdown]');
    var time = $('[data-countdown]').attr('data-duration');
    elem.html(time);

    var Count = setInterval(function () {
        if (time == 0) {
            clearInterval(Count);
            if(typeof callback === 'function') {
                callback();
            }
        } else {
            time -= 1;
            elem.html(time);
        }
    }, 1000);
};


var endingCount = function() {
    $('#form-connect-notifications-timer').hide();
    $('[data-captcha-related]').show();
}

/*
 * jQuery Plugin
 */
$.fn.uiSimpleCountDown = function (callback) {
    new SimpleCountDown(callback);
};
/*
 * jQuery Events
 */
$(document)
    .on('ready UI:visible', function (event) {
        $(event.target).find('[data-countdown]').uiSimpleCountDown(endingCount);
    });

module.exports = SimpleCountDown
