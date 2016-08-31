// Lib Dependencies
var $ = require('jquery')

$('#password_forgotten').on('submit', function (e) {
    e.preventDefault();
    $('#password-forgotten-error-message').hide();

    $.ajax({
        type: $(this).attr('method'),
        url: $(this).attr('action'),
        data: $(this).serialize(),
        success: function (data) {
            if (data == 'ok') {
                $('#password-forgotten-form').hide();
                $('#password-forgotten-success-message').show();
            } else {
                $('#password-forgotten-error-message').show();
            }
        },
        error: function(xhr, errorTxt){
        }

    })
})