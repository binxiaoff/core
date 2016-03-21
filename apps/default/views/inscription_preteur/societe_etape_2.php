<style>
    form .row input.field ~ input.field, form .row .pass-field-holder ~ .pass-field-holder {
        margin-left: 20px !important;
    }
</style>

<form action="" method="post" id="form_inscription_preteur_societe_etape_2" enctype="multipart/form-data">

    <div class="group" > <!-- start GROUP add class "group" -->
        <span class="group-ttl"><?= $this->lng['etape2']['group-name-bank-information'] ?></span> <!-- title of the group optional -->
        <div class="form-header">
            <span><?= $this->lng['etape2']['compte-beneficiaire-des-virements'] ?></span>
            <span><?= $this->lng['etape2']['texte-bic-iban'] ?></span>
        </div><!-- /.form-header -->
        <p class="exInfoBulle"><?= $this->lng['etape2']['info-iban'] ?></p>
        <div class="row row-bank">
            <label for="bic" class="inline-text"><?= $this->lng['etape2']['bic'] ?></label>
            <span class="field-holder">
                <input type="text" id="bic" name="bic" title="<?= $this->lng['etape2']['bic-exemple'] ?>" value="<?= (empty($this->lenders_accounts->bic) ? $this->lng['etape2']['bic-exemple'] : $this->lenders_accounts->bic) ?>" class="field field-medium "><br />
                <em class="error_bic"><?= $this->lng['etape2']['bic-erreur'] ?></em>
            </span>
        </div><!-- /.row -->

        <div class="row row-bank">
            <label class="inline-text"><?= $this->lng['etape2']['iban'] ?></label>
            <span class="field-holder">
                <input maxlength="4" type="text" name="iban-1" id="iban-1" value="<?= $this->iban1 ?>"
                       title="<?= $this->iban1 ?>" class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="4" type="text" name="iban-2" id="iban-2" value="<?= $this->iban2 ?>"
                       class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="4" type="text" name="iban-3" id="iban-3" value="<?= $this->iban3 ?>"
                       class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="4" type="text" name="iban-4" id="iban-4" value="<?= $this->iban4 ?>"
                       class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="4" type="text" name="iban-5" id="iban-5" value="<?= $this->iban5 ?>"
                       class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="4" type="text" name="iban-6" id="iban-6" value="<?= $this->iban6 ?>"
                       class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="3" type="text" name="iban-7" id="iban-7" value="<?= $this->iban7 ?>"
                       class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,3);" onchange="check_ibanNB(this.id,this.value,3);">
                <br />
                <em class="error_iban"><?= $this->lng['etape2']['iban-erreur'] ?></em>
            </span>
        </div><!-- /.row -->
        <div class="row row-upload">
            <p class="exInfoBulle"><?= $this->lng['etape2']['info-rib'] ?></p>
            <label class="inline-text"><?= $this->lng['etape2']['rib'] ?></label>
            <div class="uploader">
                <input id="txt_rib" type="text" class="field required <?= (isset($this->error_rib) && $this->error_rib == true ? 'LV_invalid_field' : '') ?>" readonly="readonly" value="<?=(empty($this->attachments[attachment_type::RIB]['path']) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[attachment_type::RIB]['path']) ?>" />

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
                <?php foreach ($this->origine_fonds_E as $k => $origine_fonds): ?>
                    <option<?= ($this->lenders_accounts->origine_des_fonds == $k + 1 ? ' selected' : '') ?> value="<?= $k + 1 ?>" ><?= $origine_fonds ?></option>
                <?php endforeach; ?>
                <option<?= ($this->lenders_accounts->origine_des_fonds == 1000000 ? ' selected' : '') ?> value="1000000" ><?= $this->lng['etape2']['autre'] ?></option>
            </select>
        </div><!-- /.row -->

        <div class="row" id="row_precision"<?= ($this->lenders_accounts->origine_des_fonds == 1000000 ? '' : ' style="display:none;"') ?>>
            <input type="text" id="preciser" name="preciser" title="<?= $this->lng['etape2']['autre-preciser'] ?>" value="<?= (empty($this->lenders_accounts->precision) ? $this->lng['etape2']['autre-preciser'] : $this->lenders_accounts->precision)?>" class="field field-mega">
        </div><!-- /.row -->
    </div>

    <div class="group" > <!-- start GROUP add class "group" -->
        <span class="group-ttl"><?= $this->lng['etape2']['group-name-id-documents'] ?></span> <!-- title of the group optional -->
        <p><?= $this->lng['etape2']['documents-a-fournir'] ?></p>
        <em class="error_fichier"<?=(isset($this->error_fichier) && $this->error_fichier == true ? ' style="display:block;"' : '') ?>><?= $this->lng['etape2']['erreur-fichier'] ?></em>
        <div class="row row-upload">
            <p class="exInfoBulle"><?= $this->lng['etape2']['info-extrait-kbis'] ?></p>
            <label class="inline-text"><?= $this->lng['etape2']['extrait-kbis'] ?></label>
            <div class="uploader">
                <input id="txt_kbis" type="text" class="field required <?= (isset($this->error_extrait_kbis) && $this->error_extrait_kbis == true ? 'LV_invalid_field' : '') ?>" readonly="readonly" value="<?=(empty($this->attachments[attachment_type::KBIS]['path']) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[attachment_type::KBIS]['path']) ?>" />
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
            <p class="exInfoBulle"><?= $this->lng['etape2']['info-delegation-de-pouvoir'] ?></p>
            <label class="inline-text"><?= $this->lng['etape2']['delegation-de-pouvoir'] ?></label>
            <div class="uploader">
                <input id="txt_delegation_pouvoir" type="text" class="field required <?= (isset($this->error_delegation_pouvoir) && $this->error_delegation_pouvoir == true ?'LV_invalid_field' : '') ?>" readonly="readonly" value="<?= (empty($this->attachments[attachment_type::DELEGATION_POUVOIR]['path']) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[attachment_type::DELEGATION_POUVOIR]['path']) ?>" />

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
            <p class="exInfoBulle"><?= $this->lng['etape2']['info-cni-passeport-dirigeants'] ?></p>
            <label class="inline-text"><?= $this->lng['etape2']['cni-passeport-dirigeants'] ?></label>
            <div class="uploader">
                <input id="txt_ci_dirigeant" type="text" class="field required <?= (isset($this->error_cni_dirigent) && $this->error_cni_dirigent == true ? 'LV_invalid_field' : '') ?>" readonly="readonly" value="<?=(empty($this->attachments[attachment_type::CNI_PASSPORTE_DIRIGEANT]['path']) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[attachment_type::CNI_PASSPORTE_DIRIGEANT]['path'])?>" />

                <div class="file-holder">
                    <span class="btn btn-small">
                        <?= $this->lng['etape2']['parcourir'] ?>
                        <span class="file-upload">
                            <input type="file" class="file-field" name="cni_passeport_dirigeant">
                        </span>
                    </span>
                </div>
            </div><!-- /.uploader -->
        </div><!-- /.row -->
        <div class="row row-upload">
            <label class="inline-text"><?= $this->lng['etape2']['label-upload-field-id-company-owner-verso'] ?></label>
            <div class="uploader">
                <input id="txt_ci_dirigeant" type="text"
                       class="field required <?= (isset($this->error_cni_dirigent) && $this->error_cni_dirigent == true ? 'LV_invalid_field' : '') ?>" readonly="readonly"
                       value="<?=(empty($this->attachments[attachment_type::CNI_PASSPORTE_VERSO]['path']) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[attachment_type::CNI_PASSPORTE_VERSO]['path'])?>" />
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

        <span class="form-caption"><?= $this->lng['etape2']['champs-obligatoires'] ?></span>
        </div>

    <div class="form-foot row row-cols centered">
        <input type="hidden" name="send_form_inscription_preteur_societe_etape_2">
        <a class="btn btn-warning" href="<?= $this->lurl ?>/inscription_preteur/etape1/<?= $this->clients->hash ?>" ><i class="icon-arrow-prev"></i><?= $this->lng['etape2']['precedent'] ?> </a>
        <button id="next_preteur" class="btn" type="submit" onClick="$('#form_inscription_preteur_societe_etape_2').submit();"><?= $this->lng['etape2']['suivant'] ?><i class="icon-arrow-next"></i></button>
    </div><!-- /.form-foot foot-cols -->
</form>
