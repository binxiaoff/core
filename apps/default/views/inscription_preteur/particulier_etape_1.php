<form action="" method="post" id="form_inscription_preteur_particulier_etape_1" name="form_inscription_preteur_particulier_etape_1">
    <div class="part_particulier1">
        <div class="group" id="group_identiy"> <!-- start GROUP add class "group" -->
            <span class="group-ttl"><?= $this->lng['etape1']['group-name-identity'] ?></span> <!-- title of the group optional -->
            <div class="row" id="radio_sex">
                <div class="form-choose fixed">
                    <span class="title"><?= $this->lng['etape1']['civilite'] ?></span>
                    <div class="radio-holder validationRadio1">
                        <label for="female"><?= $this->lng['etape1']['madame'] ?></label>
                        <input <?= $this->aForm['particulier']['sex'] == 'Mme' ? 'checked="checked"' : '' ?> type="radio" class="custom-input" name="sex" id="female" value="Mme">
                    </div>
                    <div class="radio-holder validationRadio2">
                        <label for="male"><?= $this->lng['etape1']['monsieur'] ?></label>
                        <input <?= $this->aForm['particulier']['sex'] == 'M.' ? 'checked="checked"' : '' ?> type="radio" class="custom-input" name="sex" id="male" value="M.">
                    </div>
                </div>
            </div>
            <div class="row" id="row_identity_name">
                <input type="text" name="nom-famille" id="nom-famille"
                       title="<?= $this->lng['etape1']['nom-de-famille'] ?>"
                       value="<?= empty($this->aForm['particulier']['nom-famille']) && false === empty($this->aLanding['nom']) ? $this->aLanding['nom'] : $this->aForm['particulier']['nom-famille'] ?>"
                       placeholder = "<?= $this->lng['etape1']['nom-de-famille'] ?>"
                       class="field field-small required <?= empty($this->aForm['particulier']['nom-famille']) ? "LV_valid_field" : '' ?>" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">
                <input type="text" name="nom-dusage" id="nom-dusage" title="<?= $this->lng['etape1']['nom-dusage'] ?>"
                       value="<?= $this->aForm['particulier']['nom-dusage'] ?>"
                       placeholder="<?= $this->lng['etape1']['nom-dusage'] ?>"
                       class="field field-small" data-validators="Format,{  pattern:/^([^0-9]*)$/}">
                <input type="text" name="prenom" id="prenom" title="<?= $this->lng['etape1']['prenom'] ?>"
                       value="<?= empty($this->aForm['particulier']['prenom']) && false === empty($this->aLanding['prenom']) ? $this->aLanding['prenom'] : $this->aForm['particulier']['prenom'] ?>"
                       placeholder = "<?= $this->lng['etape1']['prenom'] ?>"
                       class="field field-small required <?= (empty($this->aForm['particulier']['prenom']) ? "LV_valid_field" : '') ?>"
                       data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">
            </div>
            <div class="row small-select">
                <span class="inline-text inline-text-alt"><?= $this->lng['etape1']['date-de-naissance'] ?> :</span>
                <select name="jour_naissance" id="jour_naissance" class="custom-select required field-tiny">
                    <option value=""><?= $this->lng['etape1']['jour'] ?></option>
                    <option value=""><?= $this->lng['etape1']['jour'] ?></option>
                    <?php for ($i = 1; $i <= 31; $i++) : ?>
                        <option <?= ($this->aForm['particulier']['jour_naissance'] == $i ? 'selected' : '') ?> value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                </select>
                <select name="mois_naissance" id="mois_naissance" class="custom-select required field-tiny">
                    <option value=""><?= $this->lng['etape1']['mois'] ?></option>
                    <option value=""><?= $this->lng['etape1']['mois'] ?></option>
                    <?php foreach ($this->dates->tableauMois['fr'] as $k => $mois) : ?>
                        <?php if ($k > 0) : ?>
                            <option <?= $this->aForm['particulier']['mois_naissance'] == $k ? "selected" : '' ?> value="<?= $k  ?>"> <?= $mois ?></option>;
                        <?php endif; ?>
                    <?php endforeach;?>
                </select>
                <select name="annee_naissance" id="annee_naissance" class="custom-select required field-tiny">
                    <option value=""><?= $this->lng['etape1']['annee'] ?></option>
                    <option value=""><?= $this->lng['etape1']['annee'] ?></option>
                    <?php for ($i = date('Y') - 18; $i >= 1910; $i--) : ?>
                        <option <?= $this->aForm['particulier']['annee_naissance'] == $i ? "selected" : '' ?> value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                </select>
                <div style="clear: both;"></div>
                <em class="error_age"><?= $this->lng['etape1']['erreur-age'] ?></em>
                <span class="check_age" style="display:none">true</span>
            </div>
            <div class="row">
                <span class="inline-text inline-text-alt inline-text-alt-small"><?= $this->lng['etape1']['commune-de-naissance'] ?>:</span>
                <input type="text" name="naissance" id="naissance" class="field field-small required" data-autocomplete="birth_city"
                       placeholder="<?= $this->lng['etape1']['commune-de-naissance'] ?>"
                       value="<?= $this->aForm['particulier']['naissance'] ?>">
                <input type="hidden" id="insee_birth" name="insee_birth" value="<?= $this->aForm['particulier']['insee_birth'] ?>"/>
            </div>
            <div class="row row-triple-fields row-triple-fields-alt">
                <span style="color:#C84747; display: none" id="error-message-nationality">
                    <?= $this->lng['etape1']['error-message-selected-nationality-other'] ?>
                </span>
                <span class="inline-text inline-text-alt inline-text-alt-small"><?= $this->lng['etape1']['pays-de-naissance'] ?>
                    :</span>
                <select name="pays3" id="pays3" class="country custom-select required field-small">
                    <option value=""><?= $this->lng['etape1']['pays-de-naissance'] ?></option>
                    <option value=""><?= $this->lng['etape1']['pays-de-naissance'] ?></option>
                    <?php foreach ($this->lPays as $p) : ?>
                        <option <?= ($this->aForm['particulier']['pays3'] == $p['id_pays'] ? 'selected' : ($this->aForm['particulier']['pays3'] == 0 && $p['id_pays'] == 1 ? 'selected' : '')) ?> value="<?= $p['id_pays'] ?>"><?= $p['fr'] ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="inline-text" style="margin-left: 40px;"><?= $this->lng['etape1']['nationalite'] ?> :</span>
                <select name="nationalite" id="nationalite" class="custom-select required field-small">
                    <option value=""><?= $this->lng['etape1']['nationalite'] ?></option>
                    <option value=""><?= $this->lng['etape1']['nationalite'] ?></option>
                    <?php foreach ($this->lNatio as $p) : ?>
                        <option <?= ($this->aForm['particulier']['nationalite'] == $p['id_nationalite'] ? 'selected' : ($this->aForm['particulier']['nationalite'] == 0 && $p['id_nationalite'] == 1 ? 'selected' : '')) ?> value="<?= $p['id_nationalite'] ?>"><?= $p['fr_f'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row etranger">
                <div class="cb-holder">
                    <label class="check_etranger" for="check_etranger"><?= $this->lng['etape1']['checkbox-etranger'] ?></label>
                    <input type="checkbox" class="custom-input" name="check_etranger" id="check_etranger">
                </div>
                <p class="message_check_etranger"><?= $this->lng['etape1']['checkbox-etranger-message'] ?></p>
            </div>
        </div> <!-- end GROUP -->


        <div class="group"> <!-- start GROUP -->
            <span class="group-ttl"><?= $this->lng['etape1']['group-name-contact-details'] ?></span> <!-- title of the group -->
            <div class="row">
                <span class="pass-field-holder">
                    <input type="text" name="email" id="email" title="<?= $this->lng['etape1']['email'] ?>"
                           placeholder ="<?= $this->lng['etape1']['email'] ?>"
                           value="<?= empty($this->aForm['particulier']['email']) && false === empty($this->aLanding['email']) ? $this->aLanding['email'] : $this->aForm['particulier']['email'] ?>"
                           class="field field-large required <?= empty($this->aForm['particulier']['email']) ? "LV_valid_field" : '' ?>"
                           data-validators="Presence&amp;Email&amp;Format,{ pattern:/^((?!@yopmail.com).)*$/}" onkeyup="checkConf(this.value,'conf_email')">
                    <em><?= $this->lng['etape1']['info-email'] ?></em>
                </span>
                <span class="pass-field-holder">
                    <input type="text" name="conf_email" id="conf_email" title="<?= $this->lng['etape1']['confirmation-email'] ?>" placeholder="<?= $this->lng['etape1']['confirmation-email'] ?>"
                           value="<?= $this->aForm['particulier']['conf_email'] ?>"
                           class="field field-large required <?= (empty($this->aForm['particulier']['conf_email']) ? "LV_valid_field" : "") ?>"
                           data-validators="Confirmation,{ match: 'email' }&amp;Format,{ pattern:/^((?!@yopmail.com).)*$/ }">
                </span>
            </div>
            <div class="row">
                <span class="inline-text inline-text-alt"><?= $this->lng['etape1']['telephone'] ?> :</span>
                <input type="text" name="phone" id="phone" title="<?= $this->lng['etape1']['telephone'] ?>"
                       placeholder="<?= $this->lng['etape1']['telephone'] ?>"
                       value="<?= $this->aForm['particulier']['phone'] ?>" class="field field-small required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9,maximum: 14}">
            </div>
        </div> <!-- end GROUP -->
    </div>
    <div class="les_deux">
        <div class="group"> <!-- start GROUP -->
            <span class="group-ttl"><?= $this->lng['etape1']['group-name-addresses'] ?></span> <!-- title of the group -->
            <p><?= $this->lng['etape1']['adresse-fiscale'] ?>
            </p>
            <em class="exInfoBulle"><?= $this->lng['etape1']['info-adresse-fiscale'] ?></em>

            <div class="row">
                <input type="text" id="adresse_inscription" name="adresse_inscription" title="<?= $this->lng['etape1']['adresse'] ?>" placeholder="<?= $this->lng['etape1']['adresse'] ?>"
                       value="<?= $this->aForm['particulier']['adresse_inscription'] ?>"
                       class="field field-mega required" data-validators="Presence">
            </div>
            <div class="row row-triple-fields">
                <input type="text" id="postal" name="postal" class="field field-small required" data-autocomplete="post_code"
                       placeholder="<?= $this->lng['etape1']['code-postal'] ?>" title="<?= $this->lng['etape1']['code-postal'] ?>" value="<?= $this->aForm['particulier']['postal'] ?>"/>
                <input type="text" id="ville_inscription" name="ville_inscription" class="field field-small required" data-autocomplete="city"
                       placeholder="<?= $this->lng['etape1']['ville'] ?>" title="<?= $this->lng['etape1']['ville'] ?>" value="<?= $this->aForm['particulier']['ville_inscription'] ?>"/>
                <select name="pays1" id="pays1" class="country custom-select required field-small">
                    <option value=""><?= $this->lng['etape1']['pays'] ?></option>
                    <option value=""><?= $this->lng['etape1']['pays'] ?></option>
                    <?php foreach ($this->lPays as $p) : ?>
                        <option <?= ($this->aForm['particulier']['pays1'] == $p['id_pays'] ? 'selected' : ($this->aForm['particulier']['pays1'] == 0 && $p['id_pays'] == 1 ? 'selected' : '')) ?> value="<?= $p['id_pays'] ?>"><?= $p['fr'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="cb-holder">
                    <label for="mon-addresse"><?= $this->lng['etape1']['meme-adresse'] ?>
                        <p class="exInfoBulle"><?= $this->lng['etape1']['instruction-second-address'] ?></p>
                    </label>
                    <input <?= $this->aForm['particulier']['mon-addresse'] == 0 ? '' : 'checked="checked"' ?>
                        type="checkbox" class="custom-input" name="mon-addresse" id="mon-addresse" data-condition="hide:.add-address">
                </div>
            </div>
            <div class="add-address">
                <p><?= $this->lng['etape1']['adresse-de-correspondance'] ?></p>
                <div class="row">
                    <input type="text" id="address2" name="adress2" title="<?= $this->lng['etape1']['adresse'] ?>" placeholder="<?= $this->lng['etape1']['adresse'] ?>"
                           value="<?= $this->aForm['particulier']['adress2'] ?>"
                           class="field field-mega required" data-validators="Presence">
                </div>
                <div class="row row-triple-fields">
                    <input type="text" id="postal2" name="postal2" class="field field-small required" data-autocomplete="post_code"
                           placeholder="<?= $this->lng['etape1']['code-postal'] ?>" value="<?= $this->aForm['particulier']['postal2'] ?>" title="<?= $this->lng['etape1']['code-postal'] ?>"/>
                    <input type="text" id="ville2" name="ville2" class="field field-small required" data-autocomplete="city"
                           placeholder="<?= $this->lng['etape1']['ville'] ?>" value="<?= $this->aForm['particulier']['ville2'] ?>" title="<?= $this->lng['etape1']['ville'] ?>" />
                    <select name="pays2" id="pays2" class="country custom-select required field-small">
                        <option value=""><?= $this->lng['etape1']['pays'] ?></option>
                        <option value=""><?= $this->lng['etape1']['pays'] ?></option>
                        <?php foreach ($this->lPays as $p) : ?>
                            <option <?= ($this->aForm['particulier']['pays2'] == $p['id_pays'] ? 'selected' : ($this->aForm['particulier']['pays2'] == 0 && $p['id_pays'] == 1 ? 'selected' : '')) ?> value="<?= $p['id_pays'] ?>"><?= $p['fr'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="group"> <!-- start GROUP -->
            <span class="group-ttl"><?= $this->lng['etape1']['group-name-security'] ?></span> <!-- title of the group -->
            <div class="row">
                <span class="pass-field-holder">
                    <input type="password" name="pass" id="pass" title="<?= $this->lng['etape1']['mot-de-passe'] ?>"
                           placeholder="<?= $this->lng['etape1']['mot-de-passe'] ?>"
                           value="" class="field field-large required">
                    <em><?= $this->lng['etape1']['info-mdp'] ?></em>
                </span>
                <span class="pass-field-holder">
                    <input type="password" name="pass2" id="pass2" title="<?= $this->lng['etape1']['confirmation-de-mot-de-passe'] ?>"
                           placeholder="<?= $this->lng['etape1']['confirmation-de-mot-de-passe'] ?>"
                           value="" class="field field-large " data-validators="Confirmation,{ match: 'pass' }" >
                </span>
            </div>
            <div class="row">
                <input type="text" id="secret-question" name="secret-question" title="<?= $this->lng['etape1']['question-secrete'] ?>"
                       placeholder="<?= $this->lng['etape1']['question-secrete'] ?>"
                       value=""
                       class="field field-mega required" data-validators="Presence">
                <label class="exInfoBulle"><?= $this->lng['etape1']['info-question-secrete'] ?></label>

            </div>
            <div class="row">
                <input type="text" id="secret-response" name="secret-response" title="<?= $this->lng['etape1']['response'] ?>"
                       placeholder="<?= $this->lng['etape1']['response'] ?>" class="field field-mega required" data-validators="Presence">
            </div>
        </div>
    </div>


    <!-- end -->

    <div class="row">
        <div class="cb-holder">
            <label class="check" for="accept-cgu"><a style="color:#A1A5A7; text-decoration: underline;" class="check" target="_blank" href="<?= $this->lurl . '/cgv_preteurs' ?>"><?= $this->lng['etape3']['jaccepte-les-cgu-dunilend'] ?></a></label>
            <input type="checkbox" class="custom-input required" name="accept-cgu" id="accept-cgu">
            <span class="form-caption"><?= $this->lng['etape1']['champs-obligatoires'] ?></span>
        </div>
    </div>
    <div class="form-foot row row-cols centered">
        <input type="hidden" name="form_inscription_preteur_particulier_etape_1">
        <button class="btn" type="submit"><?= $this->lng['etape1']['suivant'] ?><i class="icon-arrow-next"></i></button>
    </div>
</form>
