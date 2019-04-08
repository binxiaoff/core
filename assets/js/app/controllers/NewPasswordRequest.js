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

    // Validate the password via AJAX
    $doc.on('keyup', 'input[name="client_new_password"]', function () {
        var $elem = $(this)

        // Do quick JS validation before doing AJAX validation
        // @note FormValidation already supports checking with the minLength rule
        if ($elem.val().length >= 6) return false

        // Debounce AJAX
        clearTimeout(pwdTimer)
        pwdTimer = setTimeout(function () {
            // Talk to AJAX
            $.ajax({
                url: '/security/ajax/password',
                method: 'post',
                data: {
                    client_password: $elem.val()
                },
                global: false,
                success: function (data) {
                    if (data && data.hasOwnProperty('error')) {
                        $elem.parents('.ui-formvalidation').uiFormValidation('validateInput', $elem, {
                            rules: {
                                // Use custom rule to invoke an error on the field
                                custom: function (inputValidation) {
                                    inputValidation.isValid = false
                                    inputValidation.errors.push({
                                        type: 'minLength',
                                        description: data.error
                                    })
                                }
                            }
                        })
                    }
                }
            })
        }, ajaxDelay)
    })

})