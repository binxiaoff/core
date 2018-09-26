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
var firstTab = $('#esim1')
var secondTab = $('#esim2')

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

    if (!firstTab.find(".form-validation-notifications .message-error").length) {
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

          $(".ui-esim-output-cost").html(response.amount)
          $('.ui-esim-output-duration').html(response.period)
          $('.ui-esim-funding-duration-output').html(response.estimatedFundingDuration)
          $('.ui-esim-monthly-output').html(response.estimatedMonthlyRepayment)

          if (!response.motiveSentenceComplementToBeDisplayed) {
            $('[data-esim-borrower-motive-output]').show()
            while ($('.ui-esim-output-duration')[0].nextSibling != null) {
              $('.ui-esim-output-duration')[0].nextSibling.remove()
            }
            $('#esim2 > fieldset > div:nth-child(2) > div > p:nth-child(1)').append('.')
          } else {
            $('.ui-esim-output-reason').html(response.translationComplement)
          }

          $esim.find('.emprunter-sim-estimate button.btn-submit').show()
          $esim.find('.emprunter-sim-estimate a[href*="esim2"]').hide()
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
            var messages = '';
            var needActiveFirstTab = false;
            var response = $.parseJSON(result.responseText)

            response.error.forEach(function (errorCode) {
              switch (errorCode) {
                case borrowerEsimErrorCode.invalidAmount:
                  needActiveFirstTab = true;
                  messages += '<p>' + borrowerEsimErrorMessages.invalidAmount + '</p>'
                  break
                case borrowerEsimErrorCode.invalidDuration:
                  messages += '<p>' + borrowerEsimErrorMessages.invalidDuration + '</p>'
                  needActiveFirstTab = true;
                  break
                case borrowerEsimErrorCode.invalidReason:
                  messages += '<p>' + borrowerEsimErrorMessages.invalidReason + '</p>'
                  needActiveFirstTab = true;
                  break
                case borrowerEsimErrorCode.invalidEmail:
                  messages += '<p>' + borrowerEsimErrorMessages.invalidEmail + '</p>'
                  break
                case borrowerEsimErrorCode.invalidSiren:
                  messages += '<p>' + borrowerEsimErrorMessages.invalidSiren + '</p>'
                  break
                default:
                  messages += '<p>' + borrowerEsimErrorMessages.unknownError + '</p>'
                  break
              }
            });

            if (messages.length > 0) {
              if (needActiveFirstTab) {
                activeFirstTab()
                firstTab.find('.form-validation-notifications').html('<div class="message-error">' + messages + '</div>')
              } else {
                secondTab.find('.form-validation-notifications').html('<div class="message-error">' + messages + '</div>')
              }
            }
          }
        }
      }).done(function (result) {
        window.location.assign(result.data.redirectTo)
      })
    }
  })

function activeFirstTab() {
  $esim.find('ul.nav-steps li').first().addClass('active')
  $esim.find('ul.nav-steps li').first().removeClass('complet')
  $esim.find('ul.nav-steps li:nth-child(2)').removeClass('active')

  $('#esim1').addClass('active')
  $('#esim2').removeClass('active')

  $esim.find('a[href*="esim2"]').show()
  $esim.find('.emprunter-sim-estimate button.btn-submit').hide()
}
