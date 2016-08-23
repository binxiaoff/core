// Lib Dependencies
var $ = require('jquery')

$(document).on('submit', 'form[data-bid-confirmation]', function (e) {
  var form = e.target

  e.preventDefault();

  if ($('[data-popup-amount]').val() == '' || $('[data-popup-rate]').val() == '') {
    $('[data-popup-amount], [data-popup-rate]').each(function () {
      var el = $(this)

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

    $('.ui-BidConfirmation').show();

    $('[data-popup-bid-confirmation-yes]').click(function () {
      $('.ui-BidConfirmation').hide();

      form.submit()
    })

    $('[data-popup-bid-confirmation-no]').click(function () {
      $('.ui-BidConfirmation').hide();
    })
  }
})
