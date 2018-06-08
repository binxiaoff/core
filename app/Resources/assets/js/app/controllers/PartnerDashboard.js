/*
 * Partner Dashboard Controller
 * Includes:  Project Request, Prospect Table Actions ...
 */

var $ = require('jquery')
var Utility = require('Utility')
var Templating = require('Templating')

// Partner Project Details
// @todo probably needing to be reviewed by a frontend dev
$doc
    .on(Utility.clickEvent, '#form-project-create .btn-save', function (event) {
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
$doc
    .on(Utility.clickEvent, '.table-projects [data-action]:not([data-action="memos"])', function () {
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

$doc
    .on('submit','#modal-partner-project-tos form', function(event) {
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
$doc
    .on(Utility.clickEvent, '.table-users [data-action]', function () {
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
$doc
    .on('ready UI:visible UI:update', function (event) {
        if (partnerStatisticsTabs.length) {
            partnerStatisticsTabs.each(function(){
                var $chart = $(this).find('.tab-pane.active .chart')
                showChartTooltip($chart)
            })
        }
    })
