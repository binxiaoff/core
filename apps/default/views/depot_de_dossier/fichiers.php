<div class="main">
    <div class="shell">
        <h1><?= $this->companies->name ?></h1>
        <div class="register-form">
            <form action="<?= $_SERVER['REQUEST_URI'] ?>" method="post" id="form_espace_emprunteur" enctype="multipart/form-data">
                <?php if (empty($this->sAttachmentList)) { ?>
                    <div class="row"><?= $this->lng['espace-emprunteur']['liste-des-docs-procedure-rapide'] ?></div>
                <?php } else { ?>
                    <div class="row"><?= $this->lng['espace-emprunteur']['documents-demandes'] ?></div>
                    <div class="row"><?= $this->sAttachmentList ?></div>
                <?php } ?>
                <?php if (false === empty($this->aErrors)) { ?>
                    <?php foreach ($this->aErrors as $sError) { ?>
                        <div class="row error"><?= $sError ?></div>
                    <?php } ?>
                <?php } ?>
                <div class="row row-upload">
                    <div class="row-title"><?= $this->lng['espace-emprunteur']['type-de-document'] ?></div>
                    <div class="row-title"><?= $this->lng['espace-emprunteur']['champs-dupload'] ?></div>
                </div>
                <div class="row row-upload show-scrollbar">
                    <select class="custom-select required field field-large">
                        <option value=""><?= $this->lng['espace-emprunteur']['selectionnez-un-document'] ?></option>
                        <?php foreach ($this->aAttachmentTypes as $aAttachmentType) { ?>
                            <option value="<?= $aAttachmentType['id'] ?>"><?= $aAttachmentType['label'] ?></option>
                        <?php } ?>
                    </select>
                    <div class="uploader">
                        <input type="text"
                               value="<?= $this->lng['etape3']['aucun-fichier-selectionne'] ?>"
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
                    <span style="margin-left: 5px;"><?= $this->lng['espace-emprunteur']['cliquez-pour-ajouter'] ?></span>
                </div>
                <div class="row form-foot centered">
                    <input type="hidden" name="submit_files">
                    <button class="btn btn-large" type="submit">
                        <?= $this->lng['espace-emprunteur']['envoyer'] ?>
                        <i class="icon-arrow-next"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    #form_espace_emprunteur .btn {line-height: 36px;}
    .row.error {font-weight: bold; color: #c84747;}
    .row > select.field-large {float: left; width: 453px;}
    .row .row-title {font-weight: bold; display: inline-block; border: 1px solid #b10366; color: #b10366; height: 35px; border-radius: 4px; text-transform: uppercase; line-height: 35px; padding: 0 10px; width: 433px;}
    .row ul {padding-bottom: 1em;}
    form .row .uploader input.field {margin-left: 5px; width: 295px;}
    .row.form-foot button[type=submit] {padding: 0 30px;}
    .btn-add-row {font-size: 20px; top: 0;}
    .btn-remove-row {font-size: 20px; margin-top: 5px;}
    .file-holder > .btn {float: left; margin: 5px 0 0 5px;}
</style>

<script>
    var uploadFieldId = 1;
    $(function() {
        $('.row.row-upload').find('select').change(function() {
            var attachmentTypeId = $(this).val();
            $(this).parents('.row.row-upload').find('input.file-field').attr('name', attachmentTypeId);
        });

        $uploadRow = $('.row.row-upload:nth-child(3)').clone().hide().prop('id', 'upload-row-pattern');
        $uploadRow.children('select').removeClass('custom-select');
        $('#form_espace_emprunteur').append($uploadRow);
    });

    $(document).on('change', 'input.file-field', function() {
        var val = $(this).val();

        if (val.length != 0 || val != '') {
            val = val.replace(/\\/g, '/').replace(/.*\//, '');
            $(this).closest('.uploader').find('input.field').val(val).addClass('LV_valid_field').addClass('file-uploaded');
        }
    });

    $('.btn-add-row').click(function () {
        $uploadRow = $('#upload-row-pattern').clone().show().prop('id', 'upload-row-pattern_' + uploadFieldId);
        $(this).parent('.row').before($uploadRow);
        $uploadRow.children('select').addClass('custom-select').c2Selectbox();
        $removeButton = $('<span class="btn btn-small btn-remove-row">-</span>').on('click', function() {
            $(this).parents('.row.row-upload').remove();
        });
        $uploadRow.find('.file-holder').append($removeButton);

        $('#upload-row-pattern_'+ uploadFieldId).find('select').change(function() {
            var attachmentTypeId = $(this).val();
            $(this).parents('.row.row-upload').find('input.file-field').attr('name', attachmentTypeId);
        });

        uploadFieldId ++;
    });
</script>
