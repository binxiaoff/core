var loadOperations = function() {
    var form = $('#form-filters-operations');

    $.ajax({
        method: form.attr('method'),
        url: form.attr('action'),
        data: form.serialize(),
        dataType: 'json'
    }).done(function (data) {
        if (data.count) {
            $('table.table-myoperations').show().next('.message-info').hide();
        }
        else {
            $('table.table-myoperations').hide().next('.message-info').show();
        }
        $('table.table-myoperations tbody').html(data.html_response);
    });
}

$(function(){
    if ($("#user-emprunteur-operations").length > 0) {
        loadOperations();
    }

});

$("#user-emprunteur-operations input, #user-emprunteur-operations select").change(function () {
    var action = $(this).attr('name').match(/filter\[(.*)]/);
    if (action !== null) {
        action = action[1];
        var start = new Date(),
            end = new Date();
        if (action == 'slide') {
            start.setMonth(end.getMonth() - $(this).val());
            $('#filter-start').datepicker("setDate", start);
            $('#filter-end').datepicker("setDate", end);
        } else if (action == 'year') {
            start = new Date($(this).val(), 0, 1);
            end = new Date($(this).val(), 11, 31);
            $('#filter-start').datepicker("setDate", start);
            $('#filter-end').datepicker("setDate", end);
        }

        loadOperations();
    }
});

$("#user-emprunteur-operations #link-export-operations").click(function (event) {
    event.preventDefault();
    var form = $('#form-filters-operations');
    form.children('input[name=action]').val('export');
    form.submit();
});
