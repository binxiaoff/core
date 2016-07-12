// Lib Dependencies
var $ = require('jquery')

var LoginTimer = function() {
    var self = this
    var LoginInput = $('input[data-formvalidation-input]')
    LoginInput.closest("div.form-field").on('focus click', function(e){
        var currentInput = $(e.target);
        if($('span[data-login]').length && $('span[data-login]').html() != $('span[data-login]').attr('data-end-count')) {
            currentInput.attr('disabled', 'true');
            $('#form-connect-notifications').effect('shake');
        }
        else {
            currentInput.removeAttr('disabled');
            $("#form-connect-notifications").hide(500);
        }
    });
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
