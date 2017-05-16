/*
 * Partner Dashboard Controller
 * Includes:  Project Request, Prospect Table Actions ...
 */

var $ = require('jquery')
var Utility = require('Utility')

// Partner Prospects Table
$doc
    .on(Utility.clickEvent, '.table-prospects [data-action]', function () {
        var $prospect = $(this).closest('tr')
        var action = $(this).data('action')
        var $modal = $('#modal-partner-prospect-' + action)
        var $form = $modal.find('form')

        $form.find('[name="hash"]').val($prospect.data('hash'))

        // Insert the company name inside the modal text and Show the popup
        $modal.find('.ui-modal-output-company').html($prospect.data('sortable-borrower'))
        $modal.uiModal('open')
    })
// Partner Projects Table
$doc
    .on(Utility.clickEvent, '.table-projects [data-action]:not([data-action="memos"])', function () {
        var $project = $(this).closest('tr')
        var action = $(this).data('action')
        var $modal = $('#modal-partner-project-' + action)
        var $form = $modal.find('form')

        $form.find('[name="hash"]').val($project.data('hash'))
        $modal.uiModal('open')
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



