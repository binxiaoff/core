// Lib Dependencies
var $ = require('jquery')

onSubmitRecaptchaCallback = function () {
    $('.secure-form').find('[type=submit]').prop('disabled', false)
}
onExpireRecaptchaCallback = function () {
    $('.secure-form').find('[type=submit]').prop('disabled', true)
}
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
                        var $loginFormSubmitButton = $('#project-single-login-form').find('[type=submit]')
                        $loginFormSubmitButton.prop('disabled', true)

                        grecaptcha.render(captcha)

                        var $recaptcha = $('#project-login-recaptcha')
                        var containerWidth = $recaptcha.parent().width()
                        var recaptchaWidth = $recaptcha.children().width()
                        var scale = 1 - (recaptchaWidth - containerWidth) / recaptchaWidth

                        if (scale < 1) {
                            $recaptcha.css('transform', 'scale(' + scale + ')')
                            $recaptcha.css('transform-origin', '0 0')
                        }
                    }
                }
            })
        } else {
            grecaptcha.render(this)
        }
    })
}

$('.secure-form').each(function () {
    var $form = $(this)

    $form.submit(function () {
        var $csrfField = $form.find('input[name=_csrf_token]')

        if ($csrfField.length > 0 && '' === $csrfField.val()) {
            $.ajax({
                type: 'GET',
                url: '/security/csrf-token/' + $csrfField.data('token-name'),
                global: false,
                async: false,
                success: function (response) {
                    $csrfField.val(response)
                }
            })
        }
    })
})
