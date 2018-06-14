/*
 * Partner Controller
 * Includes:  Project Request, Prospect Table Actions ...
 */

var $ = require('jquery')
var __ = require('__')
var Utility = require('Utility')
var Templating = require('Templating')

// Partner Project Details
// @todo probably needing to be reviewed by a frontend dev
$doc.on(Utility.clickEvent, '#form-project-create .btn-save', function (event) {
  // We only want to save data, do not check required fields
  var $form = $(this).closest('form')
  $form.find('[data-formvalidation-required]').each(function () {
    var $input = $(this)
    $input.removeAttr('data-formvalidation-required')
  })
  $('[data-spinnerbutton]').uiSpinnerButton('destroy')
  $form.submit()
})

// Partner Projects Table
$doc.on(Utility.clickEvent, '.table-projects [data-action]:not([data-action="memos"])', function () {
  var $project = $(this).closest('tr')
  var action = $(this).data('action')
  var $modal = $('#modal-partner-project-' + action)
  var $form = $modal.find('form')

  if (action === 'tos') {
    $form.find('table').hide()
    var tos = Utility.convertStringToJson($project.data('details-tos'))
    if (tos.length > 0) {
      var tosHtml = ''
      $.each(tos, function (i, date) {
        tosHtml += Templating.replace('<tr>\
                    <td>{{ date }}</td>\
                </tr>', {
          date: date,
        })
      })
      $form.find('table').show()
      $form.find('table tbody').html(tosHtml)
    }
  }

  $form.find('[name="hash"]').val($project.data('hash'))

  if ($modal.is('.ui-ajax-feedback')) {
    $modal.removeClass('ui-ajax-feedback').removeClass('success').removeClass('error')
  }
  // Insert the company name inside the modal text and Show the popup
  $modal.find('.ui-modal-output-company').html($project.data('sortable-borrower'))
  $modal.uiModal('open')
})

$doc.on('submit', '#modal-partner-project-tos form', function (event) {
  event.preventDefault()
  var $form = $(this)
  var $modal = $(this).parents('[data-modal]')
  $.ajax({
    url: $form.attr('action'),
    method: 'POST',
    data: $form.serialize()
  })
    .done(function (response) {
      if (response.success) {
        $modal.addClass('ui-ajax-feedback').addClass('success')
      } else {
        $modal.addClass('ui-ajax-feedback').addClass('error')
      }
      $modal.find('.ui-ajax-feedback-message').html(response.message)
      $modal.uiModal('update')
    })
    .fail(function () {
      $modal.uiModal('update')
      $modal.addClass('ui-ajax-feedback').addClass('error')
    })
})

// Partner Users Table
$doc.on(Utility.clickEvent, '.table-users [data-action]', function () {
  var $item = $(this).closest('tr')
  var action = $(this).data('action')
  var $modal = $('#modal-partner-users-' + action)

  // Insert the company name inside the modal text and Show the popup
  $modal.find('input[name="user"]').val($item.data('user-id'))
  $modal.find('.ui-modal-output-firstname').html($item.find('.first-name').text())
  $modal.find('.ui-modal-output-lastname').html($item.find('.last-name').text())
  $modal.uiModal('open')
})

// Partner Statistics
var partnerStatisticsTabs = $('.partner-statistics-tabs')

// Open last month tooltip by default
var showChartTooltip = function ($chart) {
  var chart = $chart.highcharts()
  var pointIndex = chart.series[0].data.length - 1;
  var tooltipPoint = chart.series[0].points[pointIndex];
  chart.series[0].data[pointIndex].setState('hover');
  chart.tooltip.refresh(tooltipPoint);
}

$doc.on('ready UI:visible UI:update', function (event) {
  if (partnerStatisticsTabs.length) {
    partnerStatisticsTabs.each(function () {
      var $chart = $(this).find('.tab-pane.active .chart')
      showChartTooltip($chart)
    })
  }
})

$doc.on('change keyup', '#cost-simulator-form input, #cost-simulator-form select', function () {
    var rate = Utility.convertStringToFloat($('#simulator-rate').val()),
      duration = Utility.convertStringToFloat($('#simulator-duration').val()),
      amount = Utility.convertStringToFloat($('#simulator-amount').val()),
      fundsCommission = Utility.convertStringToFloat($('#simulator-funds-commission').val()),
      repaymentCommission = Utility.convertStringToFloat($('#repayment-commission').val()),
      vatRate = Utility.convertStringToFloat($('#vat-rate').val()),
      releasedFundsLabel = $('#released-funds'),
      monthlyPaymentLabel = $('#monthly-payment'),
      monthlyCommissionLabel = $('#monthly-commission'),
      monthlyPaymentTotalLabel = $('#monthly-payment-total'),
      monthlyPaymentTotalTaxesIncludedLabel = $('#monthly-payment-total-taxes-included'),
      interestsCostLabel = $('#interests-cost'),
      unilendFundsCommissionLabel = $('#unilend-funds-commission'),
      recoveredVatAmountLabel = $('#recovered-vat-amount'),
      interestsCommissionsCostLabel = $('#interests-commissions-cost'),
      totalFundingCostLabel = $('#total-funding-cost'),
      releasedFunds, monthlyPayment, monthlyCommission, monthlyPaymentTotal, monthlyPaymentTotalTaxesIncluded,
      interestsCost, unilendFundsCommission, recoveredVatAmount, interestsCommissionsCost, totalFundingCost

    $('.simulation').hide()

    if (rate && duration && amount && fundsCommission) {
      rate = rate / 100

      releasedFunds = amount * (1 - (fundsCommission / 100 * vatRate / 100))
      releasedFundsLabel.html(__.formatNumber(Math.round(releasedFunds * 100) / 100), undefined, 2)

      monthlyPayment = Utility.pmt(rate / 12, duration, -amount)
      monthlyPaymentLabel.html(__.formatNumber(Math.round(monthlyPayment * 100) / 100), undefined, 2)

      monthlyCommission = Utility.pmt(repaymentCommission / 100 / 12, duration, -amount) - Utility.pmt(0, duration, -amount)
      monthlyCommissionLabel.html(__.formatNumber(Math.round(monthlyCommission * 100) / 100), undefined, 2)

      monthlyPaymentTotal = monthlyPayment + monthlyCommission
      monthlyPaymentTotalLabel.html(__.formatNumber(Math.round(monthlyPaymentTotal * 100) / 100), undefined, 2)

      monthlyPaymentTotalTaxesIncluded = monthlyPayment + monthlyCommission * (1 + vatRate / 100)
      monthlyPaymentTotalTaxesIncludedLabel.html(__.formatNumber(Math.round(monthlyPaymentTotalTaxesIncluded * 100) / 100), undefined, 2)

      interestsCost = monthlyPayment * duration - amount
      interestsCostLabel.html(__.formatNumber(Math.round(interestsCost * 100) / 100), undefined, 2)

      unilendFundsCommission = amount * fundsCommission / 100
      unilendFundsCommissionLabel.html(__.formatNumber(Math.round(unilendFundsCommission * 100) / 100), undefined, 2)

      recoveredVatAmount = unilendFundsCommission * vatRate / 100
      recoveredVatAmountLabel.html(__.formatNumber(Math.round(recoveredVatAmount * 100) / 100), undefined, 2)

      interestsCommissionsCost = unilendFundsCommission + monthlyCommission * duration + interestsCost
      interestsCommissionsCostLabel.html(__.formatNumber(Math.round(interestsCommissionsCost * 100) / 100), undefined, 2)

      totalFundingCost = monthlyPaymentTotal * duration
      totalFundingCostLabel.html(__.formatNumber(Math.round(totalFundingCost * 100) / 100), undefined, 2)

      $('.simulation').show()
    }
  }
)
