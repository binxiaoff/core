<form action="" method="post" id="form_inscription_preteur_societe_etape_1" name="form_inscription_preteur_societe_etape_1">
    <div class="part_societe1">
        <div class="group"> <!-- start GROUP add class "group" -->
            <span class="group-ttl"><?= $this->lng['etape1']['group-name-company-identity'] ?></span> <!-- title of the group optional -->
            <div class="row">
                <input type="text" name="raison_sociale_inscription" id="raison_sociale_inscription"
                       value="<?= $this->aForm['societe']['raison_sociale_inscription'] ?>"
                       title="<?= $this->lng['etape1']['raison-sociale'] ?>"
                       placeholder="<?= $this->lng['etape1']['raison-sociale'] ?>"
                       class="field field-small required" data-validators="Presence">
                <input type="text" name="forme_juridique_inscription" id="forme_juridique_inscription"
                       value="<?= $this->aForm['societe']['forme_juridique_inscription'] ?>"
                       title="<?= $this->lng['etape1']['forme-juridique'] ?>"
                       placeholder="<?= $this->lng['etape1']['forme-juridique'] ?>"
                       class="field field-small required" data-validators="Presence">
                <input type="text" name="siren_inscription" id="siren_inscription"
                       title="<?= $this->lng['etape1']['placeholder-field-siren'] ?>"
                       placeholder="<?= $this->lng['etape1']['placeholder-field-siren'] ?>"
                       value="<?= $this->aForm['societe']['siren_inscription'] ?>"
                       data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9, maximum: 9}" class="field field-small required">
            </div>
            <div class="row rel">
                <input type="text" name="capital_social_inscription" id="capital_social_inscription"
                       title="<?= $this->lng['etape1']['capital-sociale'] ?>"
                       placeholder="<?= $this->lng['etape1']['capital-sociale'] ?>"
                       value="<?= $this->aForm['societe']['capital_social_inscription'] ?>"
                       class="field field-large required" onkeyup="lisibilite_nombre(this.value,this.id);" data-validators="Presence&amp;Numericality">
            </div>
            <div class="row">
                <input type="text" name="phone_inscription" id="phone_inscription"
                       value="<?= $this->aForm['societe']['phone_inscription'] ?>"
                       placeholder="<?= $this->lng['etape1']['telephone'] ?>"
                       title="<?= $this->lng['etape1']['telephone'] ?>" class="field field-large required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9,maximum: 14}">
            </div>
        </div>

        <div class="part_societe2">
            <div class="group" id="group_identiy"> <!-- start GROUP add class "group" -->
                <span class="group-ttl"><?= $this->lng['etape1']['group-name-applicant-identity'] ?></span> <!-- title of the group optional -->
                <div class="row">
                    <div class="form-choose list-view">
                        <span class="title"><?= $this->lng['etape1']['vous-etes'] ?> :</span>
                        <div class="radio-holder">
                            <label for="enterprise-1"><?= $this->lng['etape1']['je-suis-le-dirigeant-de-lentreprise'] ?></label>
                            <input <?= ($this->aForm['societe']['enterprise'] == 1 ? 'checked="checked"' : '') ?>
                                value="1" type="radio" class="custom-input" name="enterprise" id="enterprise-1" checked="checked" data-condition="show:.add-new-profile">
                        </div>
                        <div class="radio-holder">
                            <label for="enterprise-2"><?= $this->lng['etape1']['je-ne-suis-pas-le-dirigeant-de-lentreprise'] ?></label>
                            <input <?= ($this->aForm['societe']['enterprise'] == 2 ? 'checked="checked"' : '') ?>
                                value="2" type="radio" class="custom-input" name="enterprise" id="enterprise-2" data-condition="show:.add-new-profile, .identification">
                        </div>
                        <div class="radio-holder">
                            <label for="enterprise-3"><?= $this->lng['etape1']['je-suis-un-conseil-externe-de-lenterprise'] ?></label>
                            <input <?= ($this->aForm['societe']['enterprise'] == 3 ? 'checked="checked"' : '') ?>
                                value="3" type="radio" class="custom-input" name="enterprise" id="enterprise-3" data-condition="show:.add-new-profile, .identification, .external-consultant">
                        </div>
                    </div>
                </div>
                <div class="external-consultant">
                    <div class="row">
                        <select name="external-consultant" style="width:470px;" id="external-consultant" class="field field-large custom-select required">
                            <option><?= $this->lng['etape1']['choisir'] ?></option>
                            <option><?= $this->lng['etape1']['choisir'] ?></option>
                            <?php foreach ($this->conseil_externe as $k => $conseil_externe) : ?>
                                <option <?= ($this->aForm['societe']['external-consultant'] == $k ? 'selected' : '') ?> value="<?= $k ?>"><?= $conseil_externe ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input <?= ($this->aForm['societe']['external-consultant'] == 3 ? 'style="display:block;"' : 'style="display:none;"') ?>
                            type="text" name="autre_inscription" title="<?= $this->lng['etape1']['autre'] ?>"
                            value="<?= $this->aForm['societe']['autre_inscription'] ?>" id="autre_inscription" class="field field-large">
                    </div>
                </div>
                <div class="add-new-profile">
                    <p><?= $this->lng['etape1']['vos-coordonnees'] ?></p>
                    <div class="row" id="radio_genre1">
                        <div class="form-choose">
                            <span class="title"><?= $this->lng['etape1']['civilite'] ?></span>
                            <div class="radio-holder">
                                <label for="female1"><?= $this->lng['etape1']['madame'] ?></label>
                                <input <?= ($this->aForm['societe']['genre1'] == 'Mme' ? 'checked="checked"' : '') ?> type="radio" class="custom-input" name="genre1" id="female1" value="Mme">
                            </div>
                            <div class="radio-holder">
                                <label for="male1"><?= $this->lng['etape1']['monsieur'] ?></label>
                                <input type="radio" class="custom-input" name="genre1" id="male1"
                                    <?= ($this->aForm['societe']['genre1'] == 'M.' ? 'checked="checked"' : '') ?> value="M.">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <input type="text" name="nom_inscription" title="<?= $this->lng['etape1']['nom'] ?>" placeholder="<?= $this->lng['etape1']['nom'] ?>"
                               value="<?= $this->aForm['societe']['nom_inscription'] ?>"
                               id="nom_inscription" class="field field-large required" data-validators="Presence&Format,{  pattern:/^([^0-9]*)$/}">
                        <input type="text" name="prenom_inscription" title="<?= $this->lng['etape1']['prenom'] ?>"
                               value="<?= $this->aForm['societe']['prenom_inscription'] ?>"
                               id="prenom_inscription" class="field field-large required" data-validators="Presence&Format,{  pattern:/^([^0-9]*)$/}">
                    </div>
                    <div class="row" id="row_fonction_inscription">
                        <input type="text" name="fonction_inscription" title="<?= $this->lng['etape1']['fonction'] ?>" placeholder="<?= $this->lng['etape1']['fonction'] ?>"
                               value="<?= $this->aForm['societe']['fonction_inscription'] ?>" id="fonction_inscription" class="field field-large required" data-validators="Presence">
                    </div>
                </div>
            </div>
            <div class="identification">
                <div class="group" id="group_identiy"> <!-- start GROUP add class "group" -->
                <span class="group-ttl"><?= $this->lng['etape1']['identification-du-dirigeant'] ?></span>
                <div class="row" id="radio_genre2">
                    <div class="form-choose">
                        <span class="title"><?= $this->lng['etape1']['civilite'] ?></span>
                        <div class="radio-holder">
                            <label for="female2"><?= $this->lng['etape1']['madame'] ?></label>
                            <input <?= ($this->aForm['societe']['genre2'] == 'Mme' ? 'checked="checked"' : '') ?> type="radio" class="custom-input" name="genre2" id="female2" value="Mme">
                        </div>
                        <div class="radio-holder">
                            <label for="male2"><?= $this->lng['etape1']['monsieur'] ?></label>
                            <input type="radio" class="custom-input" name="genre2" id="male2" <?= ($this->aForm['societe']['genre2'] == 'M.' ? 'checked="checked"' : '') ?> value="M.">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <input type="text" name="nom2_inscription" title="<?= $this->lng['etape1']['nom'] ?>" placeholder="<?= $this->lng['etape1']['nom'] ?>"
                           value="<?= $this->aForm['societe']['nom2_inscription'] ?>"
                           id="nom2_inscription" class="field field-large required" data-validators="Presence&Format,{  pattern:/^([^0-9]*)$/}">
                    <input type="text" name="prenom2_inscription" title="<?= $this->lng['etape1']['prenom'] ?>" placeholder="<?= $this->lng['etape1']['prenom'] ?>"
                           value="<?= $this->aForm['societe']['prenom2_inscription'] ?>"
                           id="prenom2_inscription" class="field field-large required" data-validators="Presence&Format,{  pattern:/^([^0-9]*)$/}">
                </div>
                <div class="row">
                    <input type="text" name="fonction2_inscription" title="<?= $this->lng['etape1']['fonction'] ?>" placeholder="<?= $this->lng['etape1']['fonction'] ?>"
                           value="<?= $this->aForm['societe']['fonction2_inscription'] ?>"
                           id="fonction2_inscription" class="field field-large required" data-validators="Presence">
                    <input type="text" name="email2_inscription" title="<?= $this->lng['etape1']['email'] ?>" placeholder="<?= $this->lng['etape1']['email'] ?>"
                           value="<?= $this->aForm['societe']['email2_inscription'] ?>"
                           id="email2_inscription" class="field field-large required" data-validators="Presence&amp;Email">
                </div>
                <div class="row">
                    <input type="text" name="phone_new2_inscription" id="phone_new2_inscription"
                           title="<?= $this->lng['etape1']['telephone'] ?>" placeholder="<?= $this->lng['etape1']['telephone'] ?>"
                           value="<?= $this->aForm['societe']['phone_new2_inscription'] ?>"
                           class="field field-large required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 10,maximum: 14}">
                </div>
                <p><?= $this->lng['etape1']['contenu-dirigeant'] ?></p>
            </div>
        </div>
    </div>

    <div class="les_deux">
        <div class="group" id="group_identiy"> <!-- start GROUP add class "group" -->
            <span class="group-ttl"><?= $this->lng['etape1']['group-name-addresses'] ?></span> <!-- title of the group optional -->
            <p><?= $this->lng['etape1']['adresse-fiscale'] ?></p>
            <em class="exInfoBulle"><?= $this->lng['etape1']['info-adresse-fiscale'] ?></em>
            <div class="row">
                <input type="text" id="adresse_inscriptionE" name="adresse_inscriptionE" title="<?= $this->lng['etape1']['adresse'] ?>" placeholder="<?= $this->lng['etape1']['adresse'] ?>"
                       value="<?= $this->aForm['societe']['adresse_inscriptionE'] ?>"
                       class="field field-mega required" data-validators="Presence">
            </div>
            <div class="row row-triple-fields">
                <input type="text" name="postalE" id="postalE" class="field field-small required" data-autocomplete="post_code"
                       placeholder="<?= $this->lng['etape1']['code-postal'] ?>"
                       title="<?= $this->lng['etape1']['code-postal'] ?>"
                       value="<?= $this->aForm['societe']['postalE'] ?>"/>
                <input type="text" id="ville_inscriptionE" name="ville_inscriptionE" class="field field-small required" data-autocomplete="city"
                       placeholder="<?= $this->lng['etape1']['ville'] ?>" title="<?= $this->lng['etape1']['ville'] ?>"
                       value="<?= $this->aForm['societe']['ville_inscriptionE'] ?>"/>
                <select name="pays1E" id="pays1E" class="country custom-select required field-small">
                    <option value=""><?= $this->lng['etape1']['pays'] ?></option>
                    <option value=""><?= $this->lng['etape1']['pays'] ?></option>
                    <?php foreach ($this->lPays as $p) : ?>
                        <option <?= ($this->aForm['societe']['pays1E'] == $p['id_pays'] ? 'selected' : ($this->aForm['societe']['pays1E'] == 0 && $p['id_pays'] == 1 ? 'selected' : '')) ?>
                            value="<?= $p['id_pays'] ?>"><?= $p['fr'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="cb-holder">
                    <label for="mon-addresse"><?= $this->lng['etape1']['meme-adresse'] ?></label>
                    <input type="checkbox" class="custom-input" name="mon-addresse" id="mon-addresse" data-condition="hide:.addr_correspondance" >
                </div>
            </div>
            <div class="addr_correspondance">
                <p><?= $this->lng['etape1']['adresse-de-correspondance'] ?></p>
                <div class="row">
                    <input type="text" id="address2E" name="address2E" title="<?= $this->lng['etape1']['adresse'] ?>" placeholder="<?= $this->lng['etape1']['adresse'] ?>"
                           value="<?= $this->aForm['societe']['address2E'] ?>"
                           class="field field-mega required" data-validators="Presence">
                </div>
                <div class="row row-triple-fields">
                    <input type="text" id="postal2E" name="postal2E" class="field field-small required" data-autocomplete="post_code"
                           placeholder="<?= $this->lng['etape1']['code-postal'] ?>" title="<?= $this->lng['etape1']['code-postal'] ?>"
                           value="<?= $this->aForm['societe']['postal2E'] ?>" />
                    <input type="text" id="ville2E" name="ville2E" class="field field-small required" data-autocomplete="city"
                           placeholder="<?= $this->lng['etape1']['ville'] ?>" title="<?= $this->lng['etape1']['ville'] ?>"
                           value="<?= $this->aForm['societe']['ville2E'] ?>"/>
                    <select name="pays2E" id="pays2E" class="country custom-select required field-small">
                        <option value=""><?= $this->lng['etape1']['pays'] ?></option>
                        <option value=""><?= $this->lng['etape1']['pays'] ?></option>
                        <?php foreach ($this->lPays as $p) : ?>
                            <option <?= ($this->aForm['societe']['pays2E'] == $p['id_pays'] ? 'selected' : ($this->aForm['societe']['pays2E'] == 0 && $p['id_pays'] == 1 ? 'selected' : '')) ?>
                                value="<?= $p['id_pays'] ?>"><?= $p['fr'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="group" > <!-- start GROUP add class "group" -->
        <span class="group-ttl"><?= $this->lng['etape1']['group-name-contact-details'] ?></span> <!-- title of the group optional -->
        <div class="row">
            <span class="pass-field-holder">
                <input type="text" name="email_inscription" title="<?= $this->lng['etape1']['email'] ?>" placeholder="<?= $this->lng['etape1']['email'] ?>"
                       value="<?= $this->aForm['societe']['email_inscription'] ?>"
                       id="email_inscription" class="field field-large required"
                       data-validators="Presence&amp;Email&amp;Format,{ pattern:/^((?!@yopmail.com).)*$/}" onkeyup="checkConf(this.value,'conf_email_inscription')">
                <em><?= $this->lng['etape1']['info-email'] ?></em>
            </span>
            <span class="pass-field-holder">
                <input type="text" name="conf_email_inscription" title="<?= $this->lng['etape1']['confirmation-email'] ?>" placeholder="<?= $this->lng['etape1']['confirmation-email'] ?>"
                       value="<?= $this->aForm['societe']['conf_email_inscription'] ?>"
                       id="conf_email_inscription" class="field field-large required"
                       data-validators="Confirmation,{ match: 'email_inscription' }&amp;Format,{ pattern:/^((?!@yopmail.com).)*$/}">
            </span>
        </div>
        <div class="row">
            <input type="text" name="phone_new_inscription" id="phone_new_inscription" title="<?= $this->lng['etape1']['telephone'] ?>" placeholder="<?= $this->lng['etape1']['telephone'] ?>"
                   value="<?= $this->aForm['societe']['phone_new_inscription'] ?>"
                   class="field field-large required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9,maximum: 14}">

        </div>
    </div>
    <?php if ($this->emprunteurCreatePreteur == false) : ?>
    <div class="group" id="group_identiy"> <!-- start GROUP add class "group" -->
    <span class="group-ttl"><?= $this->lng['etape1']['group-name-security'] ?></span> <!-- title of the group optional -->
        <!-- partie mot de passe societe -->
        <div class="row">
            <span class="pass-field-holder">
                <input type="password" name="passE" id="passE" title="<?= $this->lng['etape1']['mot-de-passe'] ?>" value="" class="field field-large required">
                <em><?= $this->lng['etape1']['info-mdp'] ?></em>
            </span>
            <span class="pass-field-holder">
                <input type="password" name="passE2" id="passE2" title="<?= $this->lng['etape1']['confirmation-de-mot-de-passe'] ?>" value="" class="field field-large " data-validators="Confirmation,{ match: 'passE' }">
            </span>
        </div>
        <div class="row">
            <input type="text" id="secret-questionE" name="secret-questionE"
                   title="<?= $this->lng['etape1']['question-secrete'] ?>" placeholder="<?= $this->lng['etape1']['question-secrete'] ?>"
                   value="<?= $this->aForm['societe']['secret-questionE'] ?>"
                   class="field field-mega required" data-validators="Presence">
            <label class="exInfoBulle"><?= $this->lng['etape1']['info-question-secrete'] ?></label>
        </div>
        <div class="row">
            <input type="text" id="secret-responseE" name="secret-responseE" title="<?= $this->lng['etape1']['response'] ?>" placeholder="<?= $this->lng['etape1']['response'] ?>"
                   value="<?= $this->lng['etape1']['response'] ?>" class="field field-mega required" data-validators="Presence">
        </div>
        <?php endif; ?>
    </div>
    <div class="row">
        <div class="cb-holder">
            <label class="check-societe" for="accept-cgu-societe"><a style="color:#A1A5A7; text-decoration: underline;" class="check-societe" target="_blank" href="<?= $this->lurl . '/cgv_preteurs/morale' ?>"><?= $this->lng['etape3']['jaccepte-les-cgu-dunilend'] ?></a></label>
            <input type="checkbox" class="custom-input required" name="accept-cgu-societe" id="accept-cgu-societe">
            <span class="form-caption"><?= $this->lng['etape1']['champs-obligatoires'] ?></span>
        </div>
    </div>
    <div class="form-foot row row-cols centered">
        <input type="hidden" name="send_form_inscription_preteur_societe_etape_1">
        <button class="btn" onClick="$('#form_inscription_preteur_societe_etape_1').submit();" type="submit"><?= $this->lng['etape1']['suivant'] ?>
            <i class="icon-arrow-next"></i>
        </button>
    </div>
</form>
