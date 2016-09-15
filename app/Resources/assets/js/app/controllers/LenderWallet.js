;
var $ = require('jquery')
var Utility = require('Utility')


function balanceDeposit(button) {
    var form = $('#form-balance-deposit');
    button.remove()

    $.ajax({
        type: form.attr('method'),
        url: form.attr('action'),
        data: form.serialize(),
        success: function (responseText) {
            if (typeof responseText.url != 'undefined') {
                window.location.replace(responseText.url)
            } else if (typeof  responseText.message != 'undefined') {
                $('#balance-deposit-1').html(responseText.message).collapse('show');
                Utility.scrollTo('#user-preter-balance')
            }
        },
        error: function (jqXHR, errorText) {
            if (typeof jqXHR.responseJSON.message != 'undefined') {
                $('#balance-deposit-1').html(jqXHR.responseJSON.message).collapse('show');
                Utility.scrollTo('#user-preter-balance')
            } else {
                $('#balance-deposit-1').html(errorText).collapse('show');
                Utility.scrollTo('#user-preter-balance')
            }

        }
    });
}

$(document).on('submit', '#form-balance-deposit', function (event) {
    event.preventDefault()
    balanceDeposit($(this))
    return false
})

