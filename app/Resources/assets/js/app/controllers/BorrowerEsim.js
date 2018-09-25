/**
 * Borrower Esim controller
 */

var $ = require('jquery')
var Utility = require('Utility')

var $esim = $('.emprunter-sim')

// Continue only if sim is within DOM
if (!$esim.length) {
  return
}

var $doc = $(document)

$doc
// Errors on Step 1
  .on('FormValidation:validate:error', '#esim1', function (event) {
    $esim.removeClass('ui-emprunter-sim-estimate-show')
    event.stopPropagation()
  })
  // View Step 2
  .on('shown.bs.tab', '[href="#esim2"]', function () {
    var period = $("input[id^='esim-input-duration-']:checked").val()
    var amount = $("#esim-input-amount").val()
    var motiveId = ~~$("#esim-input-reason").val()

    if (!$(".form-validation-notifications .message-error").length) {
      $.ajax({
        type: 'POST',
        url: '/simulateur-projet-etape1',
        data: {
          period: period,
          amount: amount,
          motiveId: motiveId
        },
        success: function (response) {
          // Show the continue button
          $esim.addClass('ui-emprunter-sim-estimate-show')

          if ($('#esim-input-company-name').is(':visible')) {
            $('#esim-input-company-name input:eq(0)').focus()
          } else {
            $('#esim-input-siren').focus()
          }

          $(".ui-esim-output-cost").prepend(response.amount)
          $('.ui-esim-output-duration').prepend(response.period)
          $('.ui-esim-funding-duration-output').html(response.estimatedFundingDuration)
          $('.ui-esim-monthly-output').html(response.estimatedMonthlyRepayment)

          if (!response.motiveSentenceComplementToBeDisplayed) {
            $('[data-esim-borrower-motive-output]').show()
            while ($('.ui-esim-output-duration')[0].nextSibling != null) {
              $('.ui-esim-output-duration')[0].nextSibling.remove()
            }
            $('#esim2 > fieldset > div:nth-child(2) > div > p:nth-child(1)').append('.')
          }
          else {
            var text = $('[data-esim-borrower-motive-output]').html()
            text = text.replace(/\.$/g, '')

            $('[data-esim-borrower-motive-output]')
              .show()
              .html(text + response.translationComplement + '.')
          }
        },
        error: function () {
          console.log("error retrieving data")
        }
      })

      $('a[href*="esim1"]')
        .removeAttr("href data-toggle aria-expanded")
        .attr("nohref", "nohref")
    }
  })
  .on(Utility.clickEvent, 'form.emprunter-sim button.btn-submit', function (event) {
    event.preventDefault()

    if (!$(".form-validation-notifications .message-error").length) {
      var formData = $esim.serializeArray()

      $.ajax({
        type: 'POST',
        url: '/simulateur-projet',
        data: formData,
        dataType: 'json',
        statusCode: {
          400: function (result) {
            var errorMessage
            var tab = $('#esim2')
            var response = $.parseJSON(result.responseText)

            switch (response.error) {
              case borrowerEsimErrorCode.invalidAmount:
                errorMessage = borrowerEsimErrorMessages.invalidAmount
                tab = $('#esim1')
                tab.tab('show')
                break
              case borrowerEsimErrorCode.invalidDuration:
                errorMessage = borrowerEsimErrorMessages.invalidDuration
                tab = $('#esim1')
                tab.tab('show')
                break
              case borrowerEsimErrorCode.invalidReason:
                errorMessage = borrowerEsimErrorMessages.invalidReason
                tab = $('#esim1')
                tab.tab('show')
                break
              case borrowerEsimErrorCode.invalidEmail:
                errorMessage = borrowerEsimErrorMessages.invalidEmail
                break
              case borrowerEsimErrorCode.invalidSiren:
                errorMessage = borrowerEsimErrorMessages.invalidSiren
                break
              default:
                errorMessage = borrowerEsimErrorMessages.unknownError
                break
            }

            tab.find('.form-validation-notifications').html('<div class="message-error">' + errorMessage + '</div>')
          }
        }
      }).done(function (result) {
        window.location.replace(result.data.redirectTo)
      })
    }
  })
  .on('change', $esim.find(':input'), function () {
    $(".form-validation-notifications .message-error").fadeOut(1000, function() {
      $(this).remove();
    });
  })

