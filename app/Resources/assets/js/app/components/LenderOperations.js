reloadTabContent = function () {
    /**
     * @todo fix the event undefined on other browsers : firefox
     * the instruction is not necessary
     */
    event.preventDefault();
    var form = $(this).closest('form'),
        action = $(this).attr('name').match(/filter\[(.*)\]/)[1];
        form.children(':input.id_last_action').val(action);
    $.ajax({
        method: form.attr('method'),
        url: form.attr('action'),
        data: form.serialize(),
        success: function (data) {
            console.log('success');
            $('article#' + data.target).html(data.template);
            $('#lender-operations-loans').find(':input').unbind().on('change', reloadTabContent);
        },
        error: function (jqXHR, responseText) {
            form.after('responseText');
            $('#lender-operations-loans').find(':input').unbind().on('change', reloadTabContent);
        }
    })
};
$('#lender-operations-loans').find(':input').unbind().on('change', reloadTabContent);