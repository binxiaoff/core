/*
 * Partner Dashboard Controller
 * Includes:  Project Request, Prospect Table Actions ...
 */

var $ = require('jquery')
var Utility = require('Utility')

$doc
    .on(Utility.clickEvent, '[data-form-submit]', function (event) {
        event.preventDefault()
        var $this = $(this)
        var $form = $($this.data('form-submit'))
        if ($this.is('.btn-abandon')) {
            var $modal = $('#modal-partner-prospect-abandon')
            $modal.uiModal('open')
            return false
        }
        $form.submit()
    })
    .on(Utility.clickEvent, '#modal-partner-prospect-abandon [data-modal-doactionsubmit]', function() {
        var $modal = $(this).closest('[data-modal]')
        var $form = $($(this).data('form-target'))
        var $select = $modal.find('#prospect-cancel-motif')
        if ($select.val() !== '0') {
            var actionUrl = $(this).data('form-action-url')
            if (actionUrl && typeof actionUrl === 'string' && $form.length) {
                $form.attr('action', actionUrl)
            }
            $form.find('#esim-input-status').val('abandon')
            $form.removeAttr('data-formvalidation')
            $form.submit()
        } else {
            $select.parent().addClass('ui-formvalidation-error')
            $select.change(function(){
                if ($(this).val() !== 0) {
                    $(this).parent().removeClass('ui-formvalidation-error')
                }
            })
        }
    })

// Partner Prospects Table
$doc
    .on(Utility.clickEvent, '.table-prospects [data-action]', function() {
        // TODO - ADD AJAX URL FOR DELETING A PROSPECT
        var $prospect = $(this).closest('tr')
        var action = $(this).data('action')
        var $modal = $('#modal-partner-prospect-' + action)

        // Add prospect id for further actions (abandon / submit)
        $modal.data('prospect-id', $prospect.attr('id'))
        // Insert the company name inside the modal text and Show the popup
        $modal.find('.ui-modal-output-company').html($prospect.data('sortable-borrower'))
        $modal.uiModal('open')
    })
    .on(Utility.clickEvent, '#modal-partner-prospect-submit [data-modal-doactionsubmit]', function() {
        var $modal = $('#modal-partner-prospect-submit')
        var $prospect = $('#' + $modal.data('prospect-id'))
        var $form = $('#submit-partner-prospect')
        var siren = $prospect.data('sortable-siren')
        var company = $prospect.data('sortable-borrower')
        var amount = $prospect.data('sortable-amount')
        var duration = $prospect.data('sortable-duration')
        var motif = $prospect.data('sortable-motif')

        // @Debug data
        console.log('siren: ' + siren + ' | company: ' + company + ' | amount: ' + amount + ' | duration: ' + duration + ' | motif: ' + motif)

        $form.find('[name="esim[siren]"]').val(siren)
        $form.find('[name="esim[company]"]').val(company)
        $form.find('[name="esim[amount]"]').val(amount)
        $form.find('[name="esim[duration]"]').val(duration)
        $form.find('[name="esim[motif]"]').val(motif)
        $form.submit();

        $modal.uiModal('close')

        console.log('Submit prospect ' + $prospect.attr('id'))
    })
    .on(Utility.clickEvent, '#modal-partner-prospect-cancel [data-modal-doactionsubmit]', function() {
        var $modal = $(this).closest('[data-modal]')
        var $prospect = $('#' + $modal.data('prospect-id'))

        var $select = $modal.find('#prospect-cancel-motif')
        if ($select.val() !== '0') {
            // TODO - Remove lines below and Uncomment Ajax

            $modal.uiModal('close')
            $prospect.remove()
            if (!$('.table-prospects-item').length) {
                $('#partner-prospects-panel .table-scroll').remove()
                $('#partner-prospects-panel .message-info').show()
            }

            // var formData = {
            //   prospectId : $prospect.attr('id'),
            //   motif : $select.val()
            // }
            // // console.log(formData)
            // $.ajax({
            //   type: 'POST',
            //   url: '',
            //   data: formData,
            //   success: function(response) {
            //     if (response.text === 'OK') {
            //       $modal.uiModal('close')
            //       $prospect.remove()
            //       if (!$('.table-prospects-item').length) {
            //         $('#partner-prospects-panel .table-scroll').remove()
            //         $('#partner-prospects-panel .message-info').show()
            //       }
            //     }
            //   },
            //   error: function() {
            //     console.log("error retrieving data");
            //   }
            // })
            // TODO END
        } else {
            $select.parent().addClass('ui-formvalidation-error')
            $select.change(function(){
                if ($(this).val() !== 0) {
                    $(this).parent().removeClass('ui-formvalidation-error')
                }
            })
        }
    })