/*
 * jQuery-Simple-Timer
 */

var SimpleCountDown = function() {
    var self = this;
    var elem = $('[data-countdown]');
    var time = $('[data-countdown]').attr('data-duration');
    elem.html(time);

    var Count = setInterval(function () {
        if (time == 0) {
            clearInterval(Count);
        } else {
            time -= 1;
            elem.html(time);
        }
    }, 1000);
};

/*
 * jQuery Plugin
 */
$.fn.uiSimpleCountDown = function () {
    new SimpleCountDown();
};
/*
 * jQuery Events
 */
$(document)
    .on('ready UI:visible', function (event) {
        $(event.target).find('[data-countdown]').uiSimpleCountDown();
    });

module.exports = SimpleCountDown
