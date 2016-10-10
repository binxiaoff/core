// Lib Dependencies
var $ = require('jquery')
var __ = require('__')

$(document).on('submit', 'form[data-bid-confirmation]', function (e) {
  var form = e.target

  e.preventDefault();

  if ($('[data-popup-amount]').val() == '' || $('[data-popup-rate]').val() == ''
    || false == $.isNumeric($('[data-popup-amount]').val()) || false == $.isNumeric($('[data-popup-rate]').val())) {
    $('[data-popup-amount], [data-popup-rate]').each(function (i, elm) {
      var el = $(elm)

      if (el.val() == "" || false == $.isNumeric(el.val())) {
        el.closest('.form-field').addClass('ui-formvalidation-error');
      } else {
        el.closest('.form-field').removeClass('ui-formvalidation-error');
      }
    })
  } else {
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

    var message = $('.ui-BidConfirmation .bids-confirmation-details-holder').html()
    var bidAmount = __.formatNumber($('#bid-amount').val(), 0)
    var bidRate = __.formatNumber($('#bid-interest').val(), 1)
    message = message.replace('%rate%', bidRate + ' %' )
    message = message.replace('%amount%', bidAmount + ' €')

    $('.ui-BidConfirmation .bids-confirmation-details').html(message)

    $('[data-popup-bid-confirmation-yes]').click(function () {
      $('.ui-BidConfirmation').hide()
      form.submit()
    })

    $('[data-popup-bid-confirmation-no]').click(function () {
      $(this).parents('.popup-overlay').hide()
    })
  }
})
