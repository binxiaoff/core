<div class="main">
    <div class="shell">
        <div class="content-col left">
            <p><?= $this->lng['partenaire']['introduction'] ?></p>
            <div class="register-form">
                <form action="<?= parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?>" method="post" id="form_depot_dossier" name="form_depot_dossier" enctype="multipart/form-data">
                    <?php if (false === empty($this->aErrors)) : ?>
                        <?php foreach ($this->aErrors as $sError) : ?>
                            <div class="row error"><?= $sError ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="row">
                        <input type="text" name="raison_sociale" id="raison_sociale"
                               placeholder="<?= $this->lng['partenaire']['raison-sociale'] ?>"
                               value="<?= $this->aForm['raison_sociale'] ?>"
                               class="field required<?= isset($this->aErrors['raison_sociale']) ? ' LV_invalid_field' : '' ?>"
                               style="width: 588px;"
                               data-validators="Presence">
                    </div>
                    <div class="row">
                        <div class="form-choose fixed">
                            <div class="radio-holder">
                                <label for="civilite_madame"><?= $this->lng['partenaire']['madame'] ?></label>
                                <input type="radio" class="custom-input" name="civilite" id="civilite_madame"
                                       value="Mme"<?= $this->aForm['civilite'] == 'Mme' ? ' checked' : '' ?>>
                            </div>
                            <div class="radio-holder">
                                <label for="civilite_monsieur"><?= $this->lng['partenaire']['monsieur'] ?></label>
                                <input type="radio" class="custom-input" name="civilite" id="civilite_monsieur"
                                       value="M."<?= $this->aForm['civilite'] == 'M.' ? ' checked' : '' ?>>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <input type="text" name="prenom" id="prenom"
                               placeholder="<?= $this->lng['partenaire']['prenom'] ?>"
                               value="<?= $this->aForm['prenom'] ?>"
                               class="field required"
                               data-validators="Presence&amp;Format,{pattern:/^([^0-9]*)$/}">
                        <input type="text" name="nom" id="nom"
                               placeholder="<?= $this->lng['partenaire']['nom'] ?>"
                               value="<?= $this->aForm['nom'] ?>"
                               class="field required"
                               data-validators="Presence&amp;Format,{pattern:/^([^0-9]*)$/}">
                        <input type="text" name="fonction" id="fonction"
                               placeholder="<?= $this->lng['partenaire']['fonction'] ?>"
                               value="<?= $this->aForm['fonction'] ?>"
                               class="field required"
                               data-validators="Presence&amp;Format,{pattern:/^([^0-9]*)$/}">
                    </div>
                    <div class="row">
                        <input type="email" name="email" id="email"
                               placeholder="<?= $this->lng['partenaire']['email'] ?>"
                               value="<?= $this->aForm['email'] ?>"
                               class="field required"
                               data-validators="Presence&amp;Email">
                        <input type="text" name="telephone" id="telephone"
                               placeholder="<?= $this->lng['partenaire']['telephone'] ?>"
                               value="<?= $this->aForm['telephone'] ?>"
                               class="field required"
                               data-validators="Presence&amp;Numericality&amp;Length,{minimum: 9, maximum: 14}">
                    </div>
                    <div class="spacer">&nbsp;</div>
                    <div class="row">
                        <select name="duree" id="duree" class="field required custom-select" style="width: 410px;">
                            <option value="0"><?= $this->lng['partenaire']['duree'] ?></option>
                            <?php foreach ($this->dureePossible as $duree): ?>
                                <option value="<?= $duree ?>"<?= $duree == $this->aForm['duree'] ? ' selected' : '' ?>><?= str_replace('[#DURATION#]', $duree, $this->lng['partenaire']['valeur-duree']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="spacer">&nbsp;</div>
                    <div class="row">
                        <div class="cb-holder">
                            <label class="cgv" for="cgv">
                                <?=
                                    str_replace(
                                        array('[#LINK_START#]', '[#LINK_END#]'),
                                        array('<a style="color:#A1A5A7; text-decoration: underline;" class="cgv" target="_blank" href="' . $this->lurl . '/' . $this->tree->getSlug($this->lienConditionsGenerales, $this->language) . '">', '</a>'),
                                        $this->lng['partenaire']['cgv']
                                    )
                                ?>
                            </label>
                            <input type="checkbox" class="custom-input" name="cgv" id="cgv">
                        </div>
                    </div>
                    <div class="spacer">&nbsp;</div>
                    <div class="row">
                        <div class="row-title"><?= $this->lng['partenaire']['titre-type-document'] ?></div>
                        <div class="row-title"><?= $this->lng['partenaire']['titre-champs-upload'] ?></div>
                    </div>
                    <div class="row row-upload show-scrollbar">
                        <select class="custom-select required field field-large">
                            <option value=""><?= $this->lng['partenaire']['placeholder-type-document'] ?></option>
                            <option value="<?= \attachment_type::AUTRE1 ?>">Fiche contact</option>
                            <?php foreach ($this->aAttachmentTypes as $aAttachmentType) { ?>
                                <option value="<?= $aAttachmentType['id'] ?>"><?= $aAttachmentType['label'] ?></option>
                            <?php } ?>
                        </select>
                        <div class="uploader">
                            <input type="text"
                                   value="<?= $this->lng['partenaire']['placeholder-champ-upload'] ?>"
                                   class="field required"
                                   readonly="readonly">
                            <div class="file-holder">
                                <span class="btn btn-small">
                                    ...
                                    <span class="file-upload">
                                        <input type="file" class="file-field">
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <span class="btn btn-small btn-add-row">+</span>
                        <span style="margin-left: 5px;"><?= $this->lng['partenaire']['legende-bouton-ajout'] ?></span>
                    </div>
                    <div class="row">
                        <span class="form-caption"><?= $this->lng['partenaire']['champs-obligatoires'] ?></span>
                    </div>
                    <div class="row centered">
                        <button class="btn" name="send_form_depot_dossier" type="submit"><?= $this->lng['partenaire']['bouton-soumission-formulaire'] ?></button>
                    </div>
                </form>
            </div>
        </div>
        <div class="sidebar right">
            <aside class="widget widget-price">
                <div class="widget-body">
                    <div class="widget-cat" style="padding-top:25px;">
                        <strong><?= $this->lng['partenaire']['titre-widget-documents-obligatoires'] ?></strong>
                        <?= $this->lng['partenaire']['contenu-widget-documents-obligatoires'] ?>
                    </div>
                    <div class="widget-cat" style="padding-top:25px;">
                        <strong><?= $this->lng['partenaire']['titre-widget-documents-optionnels'] ?></strong>
                        <?= $this->lng['partenaire']['contenu-widget-documents-optionnels'] ?>
                    </div>
                </div>
            </aside>
        </div>
        <div class="clearfix"></div>
        <p><?= $this->lng['partenaire']['encart-aide-bas-page'] ?></a></p>
    </div>
</div>

<style>
    .spacer {height: 30px;}
    .register-form .field {width: 160px;}
    .widget ul {font-size: 13px; padding: 10px 15px 0 15px;}
    #form_depot_dossier .btn {line-height: 36px;}
    .row.error {font-weight: bold; color: #c84747;}
    .row > select.field-large {float: left; width: 288px;}
    .row .row-title {font-weight: bold; display: inline-block; border: 1px solid #b10366; color: #b10366; height: 35px; border-radius: 4px; text-transform: uppercase; line-height: 35px; padding: 0 10px; width: 268px;}
    form .row .uploader input.field {margin-left: 5px; width: 202px;}
    .btn-add-row {font-size: 20px; top: 0;}
    .btn-remove-row {font-size: 20px; margin-top: 5px;}
    .file-holder > .btn {float: left; margin: 5px 0 0 5px;}
</style>

<script>
    var validColor = '#727272',
        errorColor = '#C84747',
        uploadFieldId = 1;

    $(function() {
        $('input[type=radio]').on('change click', function() {
            $(this).parent('.radio-holder').css('color', validColor).css('font-weight', '');
        });

        $('#cgv').on('change click', function() {
            $('.cgv').css('color', validColor).css('font-weight', '');
        });

        $(document).on('change', 'input.file-field', function() {
            var val = $(this).val();

            if (val.length != 0 || val != '') {
                val = val.replace(/\\/g, '/').replace(/.*\//, '');
                $(this).closest('.uploader').find('input.field').val(val).addClass('LV_valid_field').addClass('file-uploaded');
            }
        });

        $('#form_depot_dossier').submit(function(event) {
            var error = false;

            if ($('input[type=radio][name=civilite]:checked').length == 0) {
                $('input[type=radio][name=civilite]').parent('.radio-holder').css('color', errorColor).css('font-weight', 'bold');
                error = true;
            }
            if ($('#cgv').is(':checked') == false) {
                $('.cgv').css('color', errorColor).css('font-weight', 'bold');
                error = true;
            }

            if (error) {
                event.preventDefault();
            }
        });

        $('.row.row-upload').find('select').change(function() {
            var attachmentTypeId = $(this).val();
            $(this).parents('.row.row-upload').find('input.file-field').attr('name', attachmentTypeId);
        });

        $uploadRow = $('.row.row-upload').clone().hide().prop('id', 'upload-row-pattern');
        $uploadRow.children('select').removeClass('custom-select');
        $('#form_depot_dossier').append($uploadRow);

        $('.btn-add-row').click(function() {
            $uploadRow = $('#upload-row-pattern').clone().show().prop('id', 'upload-row-pattern_' + uploadFieldId);
            $(this).parent('.row').before($uploadRow);
            $uploadRow.children('select').addClass('custom-select').c2Selectbox();
            $removeButton = $('<span class="btn btn-small btn-remove-row">-</span>').on('click', function() {
                $(this).parents('.row.row-upload').remove();
            });
            $uploadRow.find('.file-holder').append($removeButton);

            $('#upload-row-pattern_' + uploadFieldId).find('select').change(function() {
                var attachmentTypeId = $(this).val();
                $(this).parents('.row.row-upload').find('input.file-field').attr('name', attachmentTypeId);
            });

            uploadFieldId ++;
        });
    });
</script>
