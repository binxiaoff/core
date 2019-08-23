// Lib Dependencies
var $ = require('jquery')
var Utility = require('Utility')

var $doc = $(document)

$doc.on('ready', function () {
    // Timers
    var pwdTimer = 0
    var ajaxDelay = 2000

    $('#password_forgotten').on('submit', function (e) {
        e.preventDefault();
        $('#password-forgotten-error-message').hide();
        $('#password-forgotten-error-generic-message').hide();

        $.ajax({
            type: $(this).attr('method'),
            url: $(this).attr('action'),
            data: $(this).serialize(),
            success: function (data) {
                if (! data.hasOwnProperty('success')) {
                    $('#password-forgotten-success-generic-message').show();
                } else if (data.success) {
                    $('#password-forgotten-form').hide();
                    $('#password-forgotten-success-message').show();
                } else if (data.hasOwnProperty('error')) {
                    $('#password-forgotten-error-message').html(data.error).show();
                } else {
                    $('#password-forgotten-success-generic-message').show();
                }
            },
            error: function (xhr, errorTxt) {}
        })
    })
})