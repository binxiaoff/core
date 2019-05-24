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
        grecaptcha.render(this)

        $(this).closest('.secure-form').find('[type=submit]').prop('disabled', true)
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
