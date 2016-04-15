/**
 * Created by mesbahzitouni on 13/04/2016.
 */
;
(function($){
    var uploadFieldId = 1;
    $(function () {
        $('table.add-attachment').each(function(){
            $(this).find('select').change(function () {
                var attachmentTypeId = $(this).val();
                $(this).closest('tr').find('input.file-field').attr('name', attachmentTypeId);
            });

            $uploadRow = $(this).find('tr.row-upload').clone().hide().prop('id', 'upload-row-pattern-'+$(this).prop('id'));
            $uploadRow.find('select').removeClass('custom-select');
            $(this).append($uploadRow);
            $uploadRow = null;
        });
    });

    $(document).on('change', 'input.file-field', function () {
        var val = $(this).val();

        if (val.length != 0 || val != '') {
            val = val.replace(/\\/g, '/').replace(/.*\//, '');
            $(this).closest('.uploader').find('input.field').val(val).addClass('LV_valid_field').addClass('file-uploaded');
        }
    });

    $(document).on('click', '.btn-add-row', function () {
        tableId = $(this).closest('table').prop('id');
        var uploadRow = $('#upload-row-pattern-'+tableId).clone().show().prop('id', 'upload-row-pattern_' + uploadFieldId);

        $(this).closest('tr').before(uploadRow);
            var removeButton = $('<span class="btn btn-small btn-remove-row">-</span>').on('click', function () {
            $(this).closest('tr').remove();
        });
        uploadRow.find('td:last').append(removeButton);

        $('#upload-row-pattern_' + uploadFieldId).find('select').change(function () {
            var attachmentTypeId = $(this).val();
            $(this).closest('tr').find('input.file-field').attr('name', attachmentTypeId);
        });

        uploadFieldId++;
    });
})(jQuery);

