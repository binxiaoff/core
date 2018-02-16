// Lib Dependencies
var $ = require('jquery')

var $projectPageLoginForm = $('#project-single-login-form')
onloadRecaptchaCallback = function () {
    $('.g-recaptcha').each(function () {
        grecaptcha.render(this)
    })
}
onProjectPageLoginSubmitRecaptchaCallback = function () {
    $projectPageLoginForm.find('[type=submit]').prop('disabled', false)
}
onProjectPageLoginExpireRecaptchaCallback = function () {
    $projectPageLoginForm.find('[type=submit]').prop('disabled', true)
}
onLoginPageLoginSubmitRecaptchaCallback = function () {
    $('.login-form').find('[type=submit]').prop('disabled', false)
}
onLoginPageLoginExpiredRecaptchaCallback = function () {
    $('.login-form').find('[type=submit]').prop('disabled', true)
}
onPasswordSubmitRecaptchaCallback = function () {
    $('#password_forgotten').find('[type=submit]').prop('disabled', false)
}
onPasswordExpireRecaptchaCallback = function () {
    $('#password_forgotten').find('[type=submit]').prop('disabled', true)
}

if ($projectPageLoginForm.length) {
    onloadRecaptchaCallback = function () {
        $('.g-recaptcha').each(function () {
            if ('project-login-recaptcha' === this.id) {
                var captcha = this

                $.ajax({
                    type: 'GET',
                    url: '/security/recaptcha',
                    global: false,
                    async: false,
                    success: function (response) {
                        if (response) {
                            var $loginFormSubmitButton = $projectPageLoginForm.find('[type=submit]')
                            $loginFormSubmitButton.prop('disabled', true)

                            grecaptcha.render(captcha)

                            var $recaptcha = $('#project-login-recaptcha')
                            var containerWidth = $recaptcha.parent().width()
                            var recaptchaWidth = $recaptcha.children().width()
                            var scale = 1 - (recaptchaWidth - containerWidth) / recaptchaWidth

                            $recaptcha.css('transform', 'scale(' + scale + ')')
                            $recaptcha.css('transform-origin', '0 0')
                        }
                    }
                })
            } else {
                grecaptcha.render(this)
            }
        })
    }

    $projectPageLoginForm.submit(function () {
        var $csrfField = $('input[name=_csrf_token]')

        if ($csrfField.length > 0 && '' === $csrfField.val()) {
            $.ajax({
                type: 'GET',
                url: '/security/csrf-token/authenticate',
                global: false,
                async: false,
                success: function (response) {
                    $csrfField.val(response)
                }
            })
        }
    })
}
