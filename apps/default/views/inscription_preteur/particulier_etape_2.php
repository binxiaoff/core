<style>
    form .row input.field ~ input.field, form .row .pass-field-holder ~ .pass-field-holder {
        margin-left: 20px !important;
    }
</style>

<form action="" method="post" id="form_inscription_preteur_particulier_etape_2" enctype="multipart/form-data">

    <div class="group">
        <span class="group-ttl"><?= $this->lng['etape2']['group-name-bank-information'] ?></span>
        <div class="form-header">
            <span><?= $this->lng['etape2']['compte-beneficiaire-des-virements'] ?></span>
            <span><?= $this->lng['etape2']['texte-bic-iban'] ?></span>
        </div><!-- /.form-header -->
        <div class="row row-bank">
            <p class="exInfoBulle"><?= $this->lng['etape2']['info-iban'] ?></p>
            <label for="bic" class="inline-text"><?= $this->lng['etape2']['bic'] ?></label>
            <span class="field-holder">
                <input type="text" id="bic" name="bic" title="<?= $this->lng['etape2']['bic-exemple'] ?>"
                       placeholder="<?= $this->lng['etape2']['bic-exemple'] ?>"
                       value="<?= false === empty($this->lenders_accounts->bic) ? $this->lenders_accounts->bic : '' ?>"
                       class="field field-medium "><br/>
                <div style="clear:both;"></div>
                <em class="error_bic"><?= $this->lng['etape2']['bic-erreur'] ?></em>
            </span>
        </div><!-- /.row -->

        <div class="row row-bank">
            <label class="inline-text"><?= $this->lng['etape2']['iban'] ?></label>
            <span class="field-holder">
                <input maxlength="4" type="text" name="iban-1" id="iban-1"
                       placeholder="<?= $this->ibanPlaceholder ?>" value="<?= false === empty($this->iban1) ? $this->iban1 : '' ?>"
                       class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="4" type="text" name="iban-2" id="iban-2"
                       value="<?= false === empty($this->iban2) ? $this->iban2 : '' ?>"
                       class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="4" type="text" name="iban-3" id="iban-3"
                       value="<?= false === empty($this->iban3) ? $this->iban3 : '' ?>"
                       class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="4" type="text" name="iban-4" id="iban-4"
                       value="<?= false === empty($this->iban4) ? $this->iban4 : '' ?>"
                       class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="4" type="text" name="iban-5" id="iban-5"
                       value="<?= false === empty($this->iban5) ? $this->iban5 : '' ?>"
                       class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="4" type="text" name="iban-6" id="iban-6"
                       value="<?= false === empty($this->iban6) ? $this->iban6 : '' ?>"
                       class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="3" type="text" name="iban-7" id="iban-7"
                       value="<?= false === empty($this->iban7) ? $this->iban7 : '' ?>"
                       class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,3);" onchange="check_ibanNB(this.id,this.value,3);">
                <br/>
                <em class="error_iban"><?= $this->lng['etape2']['iban-erreur'] ?></em>
            </span>
        </div><!-- /.row -->

        <div class="row row-upload">
            <p class="exInfoBulle"><?= $this->lng['etape2']['info-rib'] ?></p>
            <label class="inline-text"><?= $this->lng['etape2']['rib'] ?></label>
            <div class="uploader">
                <input id="txt_rib" type="text" class="field required <?= (isset($this->error_rib) && $this->error_rib == true ? 'LV_invalid_field' : '') ?>" readonly="readonly" value="<?= (empty($this->lenders_accounts->fichier_rib) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->lenders_accounts->fichier_rib) ?>"/>

                <div class="file-holder">
                    <span class="btn btn-small">
                       <?= $this->lng['etape2']['parcourir'] ?>
                        <span class="file-upload">
                            <input type="file" class="file-field" name="rib">
                        </span>
                    </span>
                </div>
            </div><!-- /.uploader -->
        </div><!-- /.row -->

        <div class="row">
            <p><?= $this->lng['etape2']['origine-des-fonds'] ?></p>
            <select name="origine_des_fonds" id="origine_des_fonds" class="custom-select field-medium required">
                <option value="0"><?= $this->lng['etape2']['choisir'] ?></option>
                <option value="0"><?= $this->lng['etape2']['choisir'] ?></option>
                <?php foreach ($this->origine_fonds as $k => $origine_fonds): ?>
                    <option <?= ($this->lenders_accounts->origine_des_fonds == $k + 1 ? 'selected' : '') ?> value="<?= $k + 1 ?>" ><?= $origine_fonds ?></option>
                <?php endforeach; ?>
                <option <?= ($this->lenders_accounts->origine_des_fonds == 1000000 ? 'selected' : '') ?> value="1000000"><?= $this->lng['etape2']['autre'] ?></option>
            </select>
        </div><!-- /.row -->

        <div class="row" id="row_precision" <?= ($this->lenders_accounts->origine_des_fonds == 1000000 ? '' : 'style="display:none;"') ?>>
            <input type="text" id="preciser" name="preciser" title="<?= $this->lng['etape2']['autre-preciser'] ?>" value="<?= (empty($this->lenders_accounts->precision) ? $this->lng['etape2']['autre-preciser'] : $this->lenders_accounts->precision) ?>" class="field field-mega">
        </div><!-- /.row -->
    </div>

    <div class="group" > <!-- start GROUP add class "group" -->
        <span class="group-ttl"><?= $this->lng['etape2']['group-name-id-documents'] ?></span> <!-- title of the group optional -->
        <p><?= $this->lng['etape2']['documents-a-fournir'] ?></p>
        <em class="error_fichier" <?= (isset($this->error_fichier) && $this->error_fichier == true ? 'style="display:block;"' : '') ?>><?= $this->lng['etape2']['erreur-fichier'] ?></em>

        <div class="row row-upload">
            <p class="exInfoBulle"><?= $this->lng['etape2']['info-cni'] ?></p>
            <label class="inline-text"><?= $this->lng['etape2']['piece-didentite'] ?></label>
            <div class="uploader">
                <input id="txt_ci" type="text" class="field required <?= (isset($this->error_cni) && $this->error_cni == true ? 'LV_invalid_field' : '') ?>" readonly="readonly" value="<?= (empty($this->attachments[attachment_type::CNI_PASSPORTE]['path']) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[attachment_type::CNI_PASSPORTE]['path']) ?>"/>
                <div class="file-holder">
                    <span class="btn btn-small">
                        <?= $this->lng['etape2']['parcourir'] ?>
                        <span class="file-upload">
                            <input type="file" class="file-field" name="cni_passeport">
                        </span>
                    </span>
                </div>
            </div><!-- /.uploader -->
        </div><!-- /.row -->

        <div class="row row-upload">
            <label class="inline-text"><?= $this->lng['etape2']['label-upload-field-id-verso'] ?></label>
                <div class="uploader">
                <input id="txt_cni_passeport_verso"
                       type="text" class="field required <?= (isset($this->error_cni_verso) && $this->error_cni_verso == true ? 'LV_invalid_field' : '') ?>"
                       readonly="readonly"
                       value="<?= (empty($this->attachments[attachment_type::CNI_PASSPORTE_VERSO]['path']) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[attachment_type::CNI_PASSPORTE_VERSO]['path']) ?>"/>
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
        <div class="row row-upload">
            <p class="exInfoBulle"><?= $this->lng['etape2']['info-justificatif-de-domicile'] ?></p>
            <label class="inline-text"><?= $this->lng['etape2']['justificatif-de-domicile'] ?></label>

            <div class="uploader">
                <input id="txt_justificatif_de_domicile" type="text" class="field required <?= (isset($this->error_justificatif_domicile) && $this->error_justificatif_domicile == true ? 'LV_invalid_field' : '') ?>" readonly="readonly" value="<?= (empty($this->attachments[attachment_type::JUSTIFICATIF_DOMICILE]['path']) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[attachment_type::JUSTIFICATIF_DOMICILE]['path']) ?>"/>
                <div class="file-holder">
                    <span class="btn btn-small">
                        <?= $this->lng['etape2']['parcourir'] ?>
                        <span class="file-upload">
                            <input type="file" class="file-field" name="justificatif_domicile">
                        </span>
                    </span>
                </div>
            </div><!-- /.uploader -->
        </div><!-- /.row -->
        <span class="btn btn-small btn-add-new-row">+<small><?= $this->lng['etape2']['label-button-more-documents'] ?></small></span>
        <div id="tiers_hebergeant" style="display: none;">
            <div class="row row-upload">
                <label class="inline-text"><?= $this->lng['etape2']['label-upload-field-housed-by-third-person-declaration'] ?></label>

                <div class="uploader">
                    <input id="txt_attestation_hebergement" type="text"
                           class="field required <?= (isset($this->error_attestation_hebergement) && $this->error_attestion_hebergement == true ? 'LV_invalid_field' : '') ?>"
                           readonly="readonly"
                           value="<?= (empty($this->attachments[attachment_type::ATTESTATION_HEBERGEMENT_TIERS]['path']) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[attachment_type::ATTESTATION_HEBERGEMENT_TIERS]['path']) ?>"/>
                    <div class="file-holder">
                        <span class="btn btn-small">
                            <?= $this->lng['etape2']['parcourir'] ?>
                            <span class="file-upload">
                                <input type="file" class="file-field" name="attestation_hebergement_tiers">
                            </span>
                        </span>
                    </div>
                </div><!-- /.uploader -->
            </div><!-- /.row -->
            <div class="row row-upload">
                <label class="inline-text"><?= $this->lng['etape2']['label-upload-field-id-third-person-housing'] ?></label>
                <div class="uploader">
                    <input id="txt_cni_passport_tiers_hebergeant" type="text"
                           class="field required <?= (isset($this->txt_identite_tiers_hebergeant) && $this->txt_identite_tiers_hebergeant == true ? 'LV_invalid_field' : '') ?>"
                           readonly="readonly" value="<?= (empty($this->attachments[attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT]['path']) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT]['path']) ?>"/>
                    <div class="file-holder">
                        <span class="btn btn-small">
                            <?= $this->lng['etape2']['parcourir'] ?>
                            <span class="file-upload">
                                <input type="file" class="file-field" name="cni_passport_tiers_hebergeant">
                            </span>
                        </span>
                    </div>
                </div><!-- /.uploader -->
            </div><!-- /.row -->
        </div>
    </div>



    <?php if ($this->etranger > 0): ?>
        <div class="group"> <!-- start GROUP -->
            <span class="group-ttl"><?= $this->lng['etape2']['group-name-fiscality'] ?></span> <!-- title of the group -->
        <div class="row row-upload">
            <p class="exInfoBulle"><?= $this->lng['etape2']['info-document-fiscal-' . $this->etranger] ?></p>
            <label class="inline-text">
                <?= $this->lng['etape2']['document-fiscal-' . $this->etranger] ?>
            </label>
            <div class="uploader">
                <input id="document_fiscal" type="text" class="field required <?= (isset($this->error_document_fiscal) && $this->error_document_fiscal == true ? 'LV_invalid_field' : '') ?>" readonly="readonly" value="<?= (empty($this->lenders_accounts->fichier_document_fiscal) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->lenders_accounts->fichier_document_fiscal) ?>"/>
                <div class="file-holder">
                    <span class="btn btn-small">
                       <?= $this->lng['etape2']['parcourir'] ?>
                        <span class="file-upload">
                            <input type="file" class="file-field" name="document_fiscal">
                        </span>
                    </span>
                </div>
            </div><!-- /.uploader -->
        </div><!-- /.row -->
        </div> <!-- end GROUP -->
    <?php endif; ?>


    <span class="form-caption"><?= $this->lng['etape2']['champs-obligatoires'] ?></span>
    <div class="form-foot row row-cols centered">
        <input type="hidden" name="send_form_inscription_preteur_particulier_etape_2">
        <a class="btn btn-warning" href="<?= $this->lurl ?>/inscription_preteur/etape1/<?= $this->clients->hash ?>"><i class="icon-arrow-prev"></i><?= $this->lng['etape2']['precedent'] ?></a>
        <button id="next_preteur" class="btn" type="submit" onClick="$('#form_inscription_preteur_particulier_etape_2').submit();">
            <?= $this->lng['etape2']['suivant'] ?>
            <i class="icon-arrow-next"></i>
        </button>
    </div><!-- /.form-foot foot-cols -->
</form>

<script type="text/javascript">
    $(".btn-add-new-row").click(function () {
        $("#tiers_hebergeant").fadeIn();
        $(".btn-add-new-row").fadeOut();
    });
</script>