/*
 * Lender Operations controller
 */

var $ = require('jquery')
var Utility = require('Utility')

var $doc = $(document)

$doc.on('ready', function () {

    // Changing filters will change the contents of the loans table
    $doc.on('change', '#dashboard-lender-operations :input', function (event) {
        var $input = $(this)
        var $form = $input.closest('form')
        var filterAction = $input.attr('name').match(/filter\[(.*)\]/i)[1]
        $form.children(':input.id_last_action').val(filterAction)

        $.ajax({
            method: $form.attr('method'),
            url: $form.attr('action'),
            data: $form.serialize(),
            success: function (data) {
                // Data has object with props target and template
                // After adding the template, need to trigger that it's visible for any other components to auto-initialise
                $('article#' + data.target).html(data.template).trigger('UI:visible')
            }
        })
    })
})
