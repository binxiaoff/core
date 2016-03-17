<div class="main form-page account-page account-page-personal">
    <div class="shell">

        <div class="account-data">
            <h2><?= $this->lng['profile']['validation-compte'] ?></h2>

            <?php if (isset($_SESSION['reponse_upload']) && $_SESSION['reponse_upload'] != '') : ?>
                <div class="reponseProfile"><?= $_SESSION['reponse_upload'] ?></div>
                <?php unset($_SESSION['reponse_upload']); ?>
            <?php endif; ?>

            <p><?= $this->lng['profile']['validation-compte-contenu'] ?></p>
            <div class="row">
                <?= $this->sAttachmentList ?>
            </div>

            <em class="error_fichier" <?= ($this->error_fichier == true ? 'style="display:block;"' : '') ?>><?= $this->lng['etape2']['erreur-fichier'] ?></em>

            <form action="" method="post" name="form_upload_doc" id="form_upload_doc" enctype="multipart/form-data">
                <div class="row row-upload">
                    <div class="row-title"><?= $this->lng['profile']['document-type-field-title'] ?></div>
                    <div class="row-title"><?= $this->lng['profile']['upload-field-title'] ?></div>
                </div>
                <div class="row row-upload show-scrollbar">
                    <select class="custom-select required field field-large">
                        <option value=""><?= $this->lng['profile']['select-placeholder'] ?></option>
                        <?php foreach ($this->aAttachmentTypes as $aAttachmentType) : ?>
                            <option value="<?= $aAttachmentType['id'] ?>"><?= $aAttachmentType['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="uploader">
                        <input type="text"
                               value="<?= $this->lng['etape2']['aucun-fichier-selectionne'] ?>"
                               class="field required"
                               readonly="readonly">
                        <div class="file-holder">
                            <span class="btn btn-small">
                                <?= $this->lng['etape2']['parcourir'] ?>
                                <span class="file-upload">
                                    <input type="file" class="file-field">
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <span class="btn btn-small btn-add-row">+</span>
                    <span style="margin-left: 5px;"><?= $this->lng['profile']['message-beside-plus-button'] ?></span>
                </div>
                <div class="form-foot row row-cols centered">
                    <input type="hidden" name="send_form_upload_doc">
                    <button class="btn" type="button" onClick="$('#form_upload_doc').submit();"><?= $this->lng['profile']['valider'] ?>
                        <i class="icon-arrow-next"></i></button>
                </div><!-- /.form-foot foot-cols -->
            </form>
            <div style="clear: both;"></div>
        </div>
    </div>
</div>
<style type="text/css">
    .row.error {font-weight: bold; color: #c84747;}
    .row > select.field-large {float: left; width: 453px;}
    .row .row-title {font-weight: bold; display: inline-block; border: 1px solid #b10366; color: #b10366; height: 35px; border-radius: 4px; text-transform: uppercase; line-height: 35px; padding: 0 10px; width: 360px;}
    .row ul {padding-bottom: 1em;}
    form .row .uploader input.field {margin-left: 5px; width: 295px;}
    .row.form-foot button[type=submit] {padding: 0 30px;}
    .btn-add-row {font-size: 20px; top: 0;}
    .btn-remove-row {font-size: 20px; margin-top: 5px;}
    .file-holder > .btn {float: left; margin: 5px 0 0 5px;}
</style>

<script type="application/javascript">
    var uploadFieldId = 1;
    $(function() {
        $('.row.row-upload').find('select').change(function() {
            var attachmentTypeId = $(this).val();
            $(this).parents('.row.row-upload').find('input.file-field').attr('name', attachmentTypeId);
        });

        $uploadRow = $('.row.row-upload:nth-child(2)').clone().hide().prop('id', 'upload-row-pattern');
        $uploadRow.children('select').removeClass('custom-select');
        $('#form_upload_doc').append($uploadRow);
    });

    $(document).on('change', 'input.file-field', function() {
        var val = $(this).val();

        if (val.length != 0 || val != '') {
            val = val.replace(/\\/g, '/').replace(/.*\//, '');
            $(this).closest('.uploader').find('input.field').val(val).addClass('LV_valid_field').addClass('file-uploaded');
        }
    });

    $('.btn-add-row').click(function () {
         var uploadRow = $('#upload-row-pattern').clone().show().prop('id', 'upload-row-pattern_' + uploadFieldId);

        $(this).parent('.row').before(uploadRow);
        uploadRow.children('select').addClass('custom-select').c2Selectbox();
        var removeButton = $('<span class="btn btn-small btn-remove-row">-</span>').on('click', function() {
            $(this).parents('.row.row-upload').remove();
        });
        uploadRow.find('.file-holder').append(removeButton);

        $('#upload-row-pattern_'+ uploadFieldId).find('select').change(function() {
            var attachmentTypeId = $(this).val();
            $(this).parents('.row.row-upload').find('input.file-field').attr('name', attachmentTypeId);
        });

        uploadFieldId ++;
    });
</script>