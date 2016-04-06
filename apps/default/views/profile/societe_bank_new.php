        <h2><?= $this->lng['profile']['titre-2'] ?></h2>

        <?php if (isset($_SESSION['reponse_profile_bank']) && $_SESSION['reponse_profile_bank'] != '') : ?>
            <div class="reponseProfile"><?= $_SESSION['reponse_profile_bank'] ?></div>
        <?php unset($_SESSION['reponse_profile_bank']); ?>
        <?php endif; ?>

        <p><?= $this->lng['etape2']['compte-beneficiaire-des-virements'] ?></p>
        <p><?= $this->lng['etape2']['texte-bic-iban'] ?></p>

        <div class="row row-bank">
            <label for="bic" class="inline-text">
                <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?= $this->lng['etape2']['info-bic'] ?>"></i>

                <?= $this->lng['etape2']['bic'] ?>
            </label>

                    <span class="field-holder">
                        <input type="text" id="bic" name="bic" title="<?= $this->lng['etape2']['bic-exemple'] ?>" value="<?= ($this->lenders_accounts->bic != '' ? $this->lenders_accounts->bic : $this->lng['etape2']['bic-exemple']) ?>" class="field field-medium "><br/>

                        <em class="error_bic"><?= $this->lng['etape2']['bic-erreur'] ?></em>
                    </span>
        </div><!-- /.row -->

        <div class="row row-bank">
            <label class="inline-text">
                <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?= $this->lng['etape2']['info-iban'] ?>"></i>

                <?= $this->lng['etape2']['iban'] ?>
            </label>

                    <span class="field-holder">
                        <input maxlength="4" type="text" name="iban-1" id="iban-1" value="<?= $this->iban1 ?>" title="<?= $this->iban1 ?>" class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                        <input maxlength="4" type="text" name="iban-2" id="iban-2" value="<?= $this->iban2 ?>" class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                        <input maxlength="4" type="text" name="iban-3" id="iban-3" value="<?= $this->iban3 ?>" class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                        <input maxlength="4" type="text" name="iban-4" id="iban-4" value="<?= $this->iban4 ?>" class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                        <input maxlength="4" type="text" name="iban-5" id="iban-5" value="<?= $this->iban5 ?>" class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                        <input maxlength="4" type="text" name="iban-6" id="iban-6" value="<?= $this->iban6 ?>" class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                        <input maxlength="3" type="text" name="iban-7" id="iban-7" value="<?= $this->iban7 ?>" class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,3);" onchange="check_ibanNB(this.id,this.value,3);">
                        <br/>

                        <em class="error_iban"><?= $this->lng['etape2']['iban-erreur'] ?></em>

                        <p>
                            <em class="change_bank">
                                <br/><br/><?= $this->lng['profile']['les-informations-relatives-a-vos-coordonnees-bancaires-ont-ete-modifiees.-merci-de-telecharger-un-nouveau-justificatif-bancaire'] ?>
                            </em>
                        </p>
                    </span>
        </div><!-- /.row -->

        <p><?= $this->lng['etape2']['origine-des-fonds'] ?></p>

        <div class="row">
            <select name="origine_des_fonds" id="origine_des_fonds" class="custom-select field-medium required">
                <option value="0"><?= $this->lng['etape2']['choisir'] ?></option>
                <option value="0"><?= $this->lng['etape2']['choisir'] ?></option>
                <?php foreach ($this->origine_fonds_E as $k => $origine_fonds) : ?>
                    <option <?= ($this->lenders_accounts->origine_des_fonds == $k + 1 ? 'selected' : '') ?> value="<?= $k + 1 ?>" ><?= $origine_fonds ?></option>
                <?php endforeach; ?>
                <option <?= ($this->lenders_accounts->origine_des_fonds == 1000000 ? 'selected' : '') ?> value="1000000"><?= $this->lng['etape2']['autre'] ?></option>
            </select>
        </div><!-- /.row -->

        <div class="row" id="row_precision" <?= ($this->lenders_accounts->origine_des_fonds == 1000000 ? '' : 'style="display:none;"') ?>>
            <input type="text" id="preciser" name="preciser" title="<?= $this->lng['etape2']['autre-preciser'] ?>" value="<?= ($this->lenders_accounts->precision != '' ? $this->lenders_accounts->precision : $this->lng['etape2']['autre-preciser']) ?>" class="field field-mega">
        </div><!-- /.row -->

        <p><?= $this->lng['etape2']['documents-a-fournir'] ?></p>
        <em class="error_fichier" <?= ($this->error_fichier == true ? 'style="display:block;"' : '') ?>><?= $this->lng['etape2']['erreur-fichier'] ?></em>
        <div class="row row-upload">
            <label class="inline-text">
                <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?= $this->lng['etape2']['info-extrait-kbis'] ?>"></i>
                <?= $this->lng['etape2']['extrait-kbis'] ?>
            </label>
            <div class="uploader">
                <input id="txt_kbis" type="text" class="field required" readonly="readonly" value="<?= ($this->attachments[attachment_type::KBIS]["path"] != '' ? $this->attachments[attachment_type::KBIS]["path"] : $this->lng['etape2']['aucun-fichier-selectionne']) ?>"/>
                <div class="file-holder">
                    <span class="btn btn-small">
                        <?= $this->lng['etape2']['parcourir'] ?>
                        <span class="file-upload">
                            <input type="file" class="file-field" name="extrait_kbis">
                        </span>
                    </span>
                </div>
            </div><!-- /.uploader -->
        </div><!-- /.row -->

        <div class="row row-upload">
            <label class="inline-text">
                <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?= $this->lng['etape2']['info-delegation-de-pouvoir'] ?>"></i>

                <?= $this->lng['etape2']['delegation-de-pouvoir'] ?>
            </label>

            <div class="uploader">
                <input id="txt_delegation_pouvoir" type="text" class="field required" readonly="readonly" value="<?= ($this->attachments[attachment_type::DELEGATION_POUVOIR]["path"] != '' ? $this->attachments[attachment_type::DELEGATION_POUVOIR]["path"] : $this->lng['etape2']['aucun-fichier-selectionne']) ?>"/>

                <div class="file-holder">
                    <span class="btn btn-small">
                        <?= $this->lng['etape2']['parcourir'] ?>
                        <span class="file-upload">
                            <input type="file" class="file-field" name="delegation_pouvoir">
                        </span>
                    </span>
                </div>
            </div><!-- /.uploader -->
        </div><!-- /.row -->

        <div class="row row-upload">
            <label class="inline-text">
                <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?= $this->lng['etape2']['info-rib'] ?>"></i>

                <?= $this->lng['etape2']['rib'] ?>
            </label>

            <div class="uploader">
                <input id="txt_rib" type="text" class="field required" readonly="readonly" value="<?= ($this->attachments[attachment_type::RIB]["path"] != '' ? $this->attachments[attachment_type::RIB]["path"] : $this->lng['etape2']['aucun-fichier-selectionne']) ?>"/>

                <div class="file-holder">
                    <span class="btn btn-small">
                        <?= $this->lng['etape2']['parcourir'] ?>

                        <span class="file-upload">
                            <input type="file" class="file-field" name="rib" id="file-rib">
                        </span>
                    </span>
                </div>
            </div><!-- /.uploader -->
        </div><!-- /.row -->

        <div class="row row-upload">
            <label class="inline-text">
                <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?= $this->lng['etape2']['info-cni-passeport-dirigeants'] ?>"></i>
                <?= $this->lng['etape2']['cni-passeport-dirigeants'] ?>
            </label>

            <div class="uploader">
                <input id="txt_ci_dirigeant" type="text" class="field required" readonly="readonly" value="<?= ($this->attachments[attachment_type::CNI_PASSPORTE_DIRIGEANT]["path"] != '' ? $this->attachments[attachment_type::CNI_PASSPORTE_DIRIGEANT]["path"] : $this->lng['etape2']['aucun-fichier-selectionne']) ?>"/>

                <div class="file-holder">
                    <span class="btn btn-small">
                        <?= $this->lng['etape2']['parcourir'] ?>

                        <span class="file-upload">
                            <input type="file" class="file-field" name="cni_passeport_dirigeant" id="file-ci_dirigeant">
                        </span>
                    </span>
                </div>
            </div><!-- /.uploader -->
        </div><!-- /.row -->

        <div class="row row-upload">
            <label class="inline-text">
                <div class="row-upload file-uploaded">
                    <div class="uploader">
                        <div class="file-holder">
                            <span class="btn btn-small btn-add-new-row">+<small><?= $this->lng['etape2']['telecharger-un-autre-document'] ?></small></span>
                        </div>
                    </div><!-- /.uploader -->
                </div><!-- /.row -->
            </label>
            <div class="uploader uploader-file" <?= ($this->attachments[attachment_type::CNI_PASSPORTE_VERSO]["path"] != '' || $this->error_autre == true ? '' : 'style="display:none;"') ?> >
                <input id="txt_autre" type="text" class="field required" readonly="readonly" value="<?= ($this->attachments[attachment_type::CNI_PASSPORTE_VERSO]["path"] != '' ? $this->attachments[attachment_type::CNI_PASSPORTE_VERSO]["path"] : $this->lng['etape2']['aucun-fichier-selectionne']) ?>"/>
                <div class="file-holder">
                    <span class="btn btn-small">
                        <?= $this->lng['etape2']['parcourir'] ?>
                        <span class="file-upload">
                            <input type="file" class="file-field" name="cni_passeport_verso">
                        </span>
                    </span>
                </div>
            </div><!-- /.uploader -->
        </div><!-- /.row -->
        <script type="text/javascript">
            $(".btn-add-new-row").click(function () {
                $(".uploader-file").fadeIn();
            });
        </script>

        <span class="form-caption"><?= $this->lng['etape2']['champs-obligatoires'] ?></span>

        <div class="form-foot row row-cols centered">
            <input type="hidden" name="send_form_bank_societe">

            <button id="next_preteur" class="btn" type="button" onClick="$('#form_societe_perso').submit();"><?= $this->lng['etape1']['valider'] ?>
                <i class="icon-arrow-next"></i></button>

        </div><!-- /.form-foot foot-cols -->
    </form>
</div>

<script type="text/javascript">


    /////////////////////
    // change_identite //
    /////////////////////

    var change_file_identite = false;
    var change_txt_identite = false;

    // nom famille et prenom
    $("#nom_inscription,#prenom_inscription").change(function () {
        // type d'utilisateur
        var radio_enterprise = $('input[type=radio][name=enterprise]:checked').attr('value');
        if (radio_enterprise == 1) {
            if ($(this).val() != "<?= $this->clients->nom ?>" && change_file_identite == false) {
                $("#txt_ci_dirigeant").val('');
                $(".change_identite").fadeIn();
                change_txt_identite = true;
            }
        }
    });

    // nom famille et prenom
    $("#nom2_inscription,#prenom2_inscription").change(function () {
        // type d'utilisateur
        var radio_enterprise = $('input[type=radio][name=enterprise]:checked').attr('value');
        if (radio_enterprise == 2 || radio_enterprise == 3) {
            if ($(this).val() != "<?= $this->clients->nom ?>" && change_file_identite == false) {
                $("#txt_ci_dirigeant").val('');
                $(".change_identite").fadeIn();
                change_txt_identite = true;
            }
        }
    });


    $("#file-ci_dirigeant").change(function () {

        // type d'utilisateur
        var radio_enterprise = $('input[type=radio][name=enterprise]:checked').attr('value');
        if (radio_enterprise == 2 || radio_enterprise == 3) {
            if (change_txt_identite == false) {
                $("#nom2_inscription,#prenom2_inscription").val('');
                $(".change_identite").fadeIn();
                change_file_identite = true;
            }
        }
        else {
            if (change_txt_identite == false) {
                $("#nom_inscription,#prenom_inscription").val('');
                $(".change_identite").fadeIn();
                change_file_identite = true;
            }
        }
    });


    // info bank //

    var info_bank_txt = false;
    var info_bank_file = false;

    // BIC
    $("#bic").keyup(function () {
        check_bic($(this).val());
    });
    $("#bic").change(function () {
        check_bic($(this).val());
        if ($(this).val() != "<?= $this->lenders_accounts->bic ?>" && info_bank_file == false) {
            $(".change_bank").fadeIn();
            $('#txt_rib').val('');
            info_bank_txt = true;
        }
    });

    // IBAN
    for (var i = 2; i <= 7; i++) {
        $('#iban-' + i).change(function () {
            $(".change_bank").fadeIn();
            if (info_bank_file == false) {
                $('#txt_rib').val('');
                info_bank_txt = true;
            }
        });
    }

    $('#iban-1').change(function () {
        if ($("#iban-1").val().substring(0, 2).toLowerCase() != 'fr') {
            $("#iban-1").addClass('LV_invalid_field');
            $("#iban-1").removeClass('LV_valid_field');
            $(".error_iban").html('<?= $this->lng['etape2']['iban-erreur-2'] ?>');
            $(".error_iban").slideDown();
        }
        else {
            $("#iban-1").addClass('LV_valid_field');
            $("#iban-1").removeClass('LV_invalid_field');
            $(".error_iban").slideUp();
        }
    });

    $("#file-rib").change(function () {
        if (info_bank_txt == false) {
            $("#bic").val('');
            for (var i = 2; i <= 7; i++) {
                $('#iban-' + i).val('');
            }
            $(".change_bank").fadeIn();
            info_bank_file = true;
        }
    });


    // formulaire informations bancaires
    $("#form_societe_perso").submit(function (event) {
        var form_ok = true;

        // origine
        if ($('#origine_des_fonds').val() == 0) {
            $('#origine_des_fonds').addClass('LV_invalid_field');
            $('#origine_des_fonds').removeClass('LV_valid_field');
            form_ok = false;
        }
        else {
            $('#origine_des_fonds').addClass('LV_valid_field');
            $('#origine_des_fonds').removeClass('LV_invalid_field');
        }

        // fichiers
        // ci_dirigeant
        if ($('#txt_ci_dirigeant').val() == '') {
            form_ok = false
            $("#txt_ci_dirigeant").removeClass("LV_valid_field");
            $("#txt_ci_dirigeant").addClass("LV_invalid_field");
        }
        else {
            $("#txt_ci_dirigeant").removeClass("LV_invalid_field");
            $("#txt_ci_dirigeant").addClass("LV_valid_field");
        }
        // rib
        if ($('#txt_rib').val() == '') {
            form_ok = false;
            $('#txt_rib').addClass('LV_invalid_field');
            $('#txt_rib').removeClass('LV_valid_field');
        }
        else {
            $('#txt_rib').addClass('LV_valid_field');
            $('#txt_rib').removeClass('LV_invalid_field');
        }
        // txt_kbis
        if ($('#txt_kbis').val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>') {
            form_ok = false
            $("#txt_kbis").removeClass("LV_valid_field");
            $("#txt_kbis").addClass("LV_invalid_field");
        }
        else {
            $("#txt_kbis").removeClass("LV_invalid_field");
            $("#txt_kbis").addClass("LV_valid_field");
        }

        // check BIC
        if (check_bic($("#bic").val()) == false) {
            form_ok = false;
        }

        //check IBAN
        var iban_ok = true;
        var size_iban = true;
        var new_iban = '';
        for (var i = 1; i <= 7; i++) {
            // 4 caracteres
            if (i < 7) {
                if ($('#iban-' + i).val().length < 4 || $('#iban-' + i).val().length > 4) {
                    check_ibanNB('iban-' + i, $('#iban-' + i).val(), 4);
                    size_iban = false;
                }
                else new_iban = new_iban + $('#iban-' + i).val();
            }
            // 3 caracteres
            else {
                if ($('#iban-' + i).val().length < 3 || $('#iban-' + i).val().length > 3) {
                    check_ibanNB('iban-' + i, $('#iban-' + i).val(), 3);
                    size_iban = false;
                }
                else new_iban = new_iban + $('#iban-' + i).val();
            }
        }

        if ($("#iban-1").val().substring(0, 2) != 'FR') {
            $("#iban-1").addClass('LV_invalid_field');
            $("#iban-1").removeClass('LV_valid_field');
            $(".error_iban").html('<?=$this->lng['etape2']['iban-erreur-2']?>');
            form_ok = false;
            iban_ok = false;
        }
        else {
            $("#iban-1").addClass('LV_valid_field');
            $("#iban-1").removeClass('LV_invalid_field');
            $(".error_iban").html('<?=$this->lng['etape2']['iban-erreur']?>');
        }

        // Lorsque l'on a le bon nombre de caracteres
        if (size_iban == true) {
            // On verifie si l'IBAN est bon
            if (validateIban(new_iban) == false) {
                for (var i = 1; i <= 7; i++) {
                    $("#iban-" + i).addClass('LV_invalid_field');
                    $("#iban-" + i).removeClass('LV_valid_field');
                }
                iban_ok = false;
            }
            else {
                for (var i = 1; i <= 7; i++) {
                    $("#iban-" + i).addClass('LV_valid_field');
                    $("#iban-" + i).removeClass('LV_invalid_field');
                }
            }
        }
        else {
            iban_ok = false;
        }

        // Resultat check IBAN
        if (iban_ok == false) {
            $(".error_iban").show();
            form_ok = false
        }
        else $(".error_iban").hide();

        if (form_ok == false) {
            event.preventDefault();
        }
    });


</script>
