// Lib Dependencies
var $ = require('jquery')

$(document).on('submit', 'form[data-bid-confirmation]', function (e) {
  var form = e.target

  e.preventDefault();

  if ($('[data-popup-amount]').val() == '' || $('[data-popup-rate]').val() == '') {
    $('[data-popup-amount], [data-popup-rate]').each(function (i, elm) {
      var el = $(elm)

      if (el.val() == "") {
        el.closest('.form-field').addClass('ui-formvalidation-error');
      }
      else {
        el.closest('.form-field').removeClass('ui-formvalidation-error');
      }
    })
  }
  else {
    $('[data-popup-amount], [data-popup-rate]').closest('.form-field').removeClass('ui-formvalidation-error');

    if (parseFloat($('#bid-min-amount').val()) > parseFloat($('#bid-amount').val())) {
      $('.ui-BidConfirmation-error').show();
      $('.bid-min-amount-error').show();
      $('.loan-max-amount-error').hide();
    } else if ($('#bid-rest-amount').val().length > 0 && parseFloat($('#bid-rest-amount').val()) < parseFloat($('#bid-amount').val())) {
      $('.ui-BidConfirmation-error').show();
      $('.loan-max-amount-error').show();
      $('.bid-min-amount-error').hide();
    } else {
      $('.ui-BidConfirmation').show()
    }

    $('[data-popup-bid-confirmation-yes]').click(function () {
      $('.ui-BidConfirmation').hide()
      form.submit()
    })

    $('[data-popup-bid-confirmation-no]').click(function () {
      $(this).parents('.popup-overlay').hide()
    })
  }
})
