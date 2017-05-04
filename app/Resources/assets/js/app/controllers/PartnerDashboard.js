/*
 * Partner Dashboard Controller
 * Includes:  Project Request, Prospect Table Actions ...
 */

var $ = require('jquery')
var Utility = require('Utility')

// Partner Prospects Table
$doc
    .on(Utility.clickEvent, '.table-prospects [data-action]', function() {
        var $prospect = $(this).closest('tr')
        var action = $(this).data('action')
        var $modal = $('#modal-partner-prospect-' + action)

        // Fill in the hidden fields
        if (action === 'submit') {
            var company = $prospect.data('sortable-borrower')
            var amount = $prospect.data('sortable-amount')
            var duration = $prospect.data('sortable-duration')
            var motif = $prospect.data('sortable-motif')

            var $form = $modal.find('form')
            $form.find('[name="simulator[company]"]').val(company)
            $form.find('[name="simulator[amount]"]').val(amount)
            $form.find('[name="simulator[duration]"]').val(duration)
            $form.find('[name="simulator[motif]"]').val(motif)
        }

        // Insert the company name inside the modal text and Show the popup
        $modal.find('.ui-modal-output-company').html($prospect.data('sortable-borrower'))
        $modal.uiModal('open')
    })
// Partner Users Table
$doc
    .on(Utility.clickEvent, '.table-users [data-action]', function() {
        var $item = $(this).closest('tr')
        var action = $(this).data('action')
        var $modal = $('#modal-partner-users-' + action)

        // Insert the company name inside the modal text and Show the popup
        $modal.find('input[name="partner-user-id"]').val($item.data('user-id'))
        $modal.find('.ui-modal-output-firstname').html($item.find('.first-name').text())
        $modal.find('.ui-modal-output-lastname').html($item.find('.last-name').text())
        $modal.uiModal('open')
    })