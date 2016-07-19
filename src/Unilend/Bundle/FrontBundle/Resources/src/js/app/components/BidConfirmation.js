// Lib Dependencies
var $ = require('jquery')

$(document).on('click', 'div[data-bid-confirmation-button]', function() {

    if($('input[data-popup-amount]').val() == "" || $('input[data-popup-rate]').val() == "") {

        $('input[data-popup-amount],input[data-popup-rate]').each(function() {
            var el = $(this)

            if(el.val() == "") {
                el.closest('.form-field').addClass('ui-formvalidation-error');
            }

            else {
                el.closest('.form-field').removeClass('ui-formvalidation-error');
            }

        })

    }

    else {

        $('input[data-popup-amount],input[data-popup-rate]').closest('.form-field').removeClass('ui-formvalidation-error');
        $('.ui-BidConfirmation').show();

        $('a[data-popup-bid-confirmation-yes]').click(function(e) {
            e.preventDefault;
            $('.ui-BidConfirmation').hide();
            $('form[data-bid-confirmation] button').click();
        })

        $('a[data-popup-bid-confirmation-no]').click(function(e) {
            e.preventDefault;
            $('.ui-BidConfirmation').hide();
        })

    }
})

