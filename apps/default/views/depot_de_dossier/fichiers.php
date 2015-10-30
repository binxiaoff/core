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
                <?php if (false === empty($this->aForm['success'])) { ?>
                    <h2><?= $this->lng['espace-emprunteur']['fichier-sauvegarde-avec-succes'] ?></h2>
                <?php } ?>
                <?php if (false === empty($this->aForm['errors'])) { ?>
                    <h2><?= $this->lng['espace-emprunteur']['erreur-sauvegarde-fichier'] ?></h2>
                <?php } ?>
                <div class="row">
                    <div class="field-large" style="display: inline-block; background-color: #b10366; color: white; margin-outside: 5px; height: 35px; width: 300px; border-radius: 4px; text-align: center;text-transform: uppercase; line-height: 35px;"><?= $this->lng['espace-emprunteur']['type-de-document'] ?></div>
                    <div class="field-large" style="display: inline-block; background-color: #b10366; color: white; margin-outside: 5px; height: 35px; width: 460px; border-radius: 4px; text-align: center;text-transform: uppercase; line-height: 35px;"><?= $this->lng['espace-emprunteur']['champs-dupload'] ?></div>
                </div>
                <div class="row row-upload">
                    <select class="custom-select required field">
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
                            <span class="btn btn-small" style=" float: left; margin: 5px;">
                                <?= $this->lng['etape2']['parcourir'] ?>
                                <span class="file-upload">
                                    <input type="file" class="file-field">
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="row" style="display: inline-block;">
                    <span class="btn btn-small btn-add-new-row" style="font-size: 120%;">+</span>
                    <span style="float: right; margin-left: 5px; "><p><?= $this->lng['espace-emprunteur']['cliquez-pour-ajouter'] ?></p></span>
                </div>
                <div class="row">
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
    .row ul {padding-bottom: 1em;}
</style>

<script>
    var uploadFieldId = 1;
    $(function() {
        $('.row.row-upload').find('select').change(function() {
            var attachmentTypeId = $(this).val();
            $(this).parents('.row.row-upload').find('input.file-field').attr('name', attachmentTypeId);
        });
        $uploadRow = $('.row.row-upload').first().clone().hide().prop('id', 'upload-row-pattern');
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

    $('.btn-add-new-row').click(function () {
        $uploadRow = $('#upload-row-pattern').clone().show().prop('id', 'upload-row-pattern_' + uploadFieldId);
        $(this).parent('.row').before($uploadRow);
        $uploadRow.children('select').addClass('custom-select').c2Selectbox();

        $('#upload-row-pattern_'+ uploadFieldId).find('select').change(function() {
            var attachmentTypeId = $(this).val();
            $(this).parents('.row.row-upload').find('input.file-field').attr('name', attachmentTypeId);
        });

        uploadFieldId ++;
    });
</script>
