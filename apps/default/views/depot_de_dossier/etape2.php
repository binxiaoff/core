<div class="main">
    <div class="shell">
        <p class="introduction"><?php printf($this->lng['etape2']['contenu'], $this->ficelle->formatNumber($this->projects->amount), $this->iAverageFundingDuration); ?></p>
        <div class="register-form">
            <form action="<?= $_SERVER['REQUEST_URI'] ?>" method="post" id="form_depot_dossier" name="form_depot_dossier" enctype="multipart/form-data">
                <div class="row">
                    <input type="text" name="raison_sociale" id="raison_sociale"
                           placeholder="<?= $this->lng['etape2']['raison-sociale'] ?>"
                           value="<?= $this->aForm['raison_sociale'] ?>"
                           class="field field-large required<?= isset($this->aErrors['raison_sociale']) ? ' LV_invalid_field' : '' ?>" data-validators="Presence">
                </div>
                <div class="row"><?= $this->lng['etape2']['identite-du-representant-de-la-societe'] ?></div>
                <div class="row">
                    <div class="form-choose fixed">
                        <div class="radio-holder">
                            <label for="civilite_madame"><?= $this->lng['etape2']['madame'] ?></label>
                            <input type="radio" class="custom-input" name="civilite" id="civilite_madame"
                                   value="Mme"<?= $this->aForm['civilite'] == 'Mme' ? ' checked' : '' ?>>
                        </div>
                        <div class="radio-holder">
                            <label for="civilite_monsieur"><?= $this->lng['etape2']['monsieur'] ?></label>
                            <input type="radio" class="custom-input" name="civilite" id="civilite_monsieur"
                                   value="M."<?= $this->aForm['civilite'] == 'M.' ? ' checked' : '' ?>>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <input type="text" name="prenom" id="prenom"
                           placeholder="<?= $this->lng['etape2']['prenom'] ?>"
                           value="<?= $this->aForm['prenom'] ?>"
                           class="field required"
                           data-validators="Presence&amp;Format,{pattern:/^([^0-9]*)$/}">
                    <input type="text" name="nom" id="nom"
                           placeholder="<?= $this->lng['etape2']['nom'] ?>"
                           value="<?= $this->aForm['nom'] ?>"
                           class="field required"
                           data-validators="Presence&amp;Format,{pattern:/^([^0-9]*)$/}">
                    <input type="text" name="fonction" id="fonction"
                           placeholder="<?= $this->lng['etape2']['fonction'] ?>"
                           value="<?= $this->aForm['fonction'] ?>"
                           class="field required"
                           data-validators="Presence&amp;Format,{pattern:/^([^0-9]*)$/}">
                </div>
                <div class="row">
                    <input type="email" name="email" id="email"
                           placeholder="<?= $this->lng['etape2']['email'] ?>"
                           value="<?= empty($this->aForm['email']) && false === empty($this->sStep1Email) ? $this->sStep1Email : $this->aForm['email'] ?>"
                           class="field required"
                           data-validators="Presence&amp;Email">
                    <input type="text" name="mobile" id="mobile"
                           placeholder="<?= $this->lng['etape2']['telephone'] ?>"
                           value="<?= $this->aForm['mobile'] ?>"
                           class="field required"
                           data-validators="Presence&amp;Numericality&amp;Length,{minimum: 9, maximum: 14}">
                </div>
                <div class="row">
                    <div class="form-choose">
                        <div class="radio-holder">
                            <label for="gerant-oui"><?= $this->lng['etape2']['dirigeant-entreprise'] ?></label>
                            <input type="radio" class="custom-input" name="gerant" id="gerant-oui"
                                   value="oui"<?= $this->aForm['gerant'] === 'oui' ? ' checked' : '' ?>
                                   data-condition="show:.cgv">
                        </div>
                        <div class="radio-holder">
                            <label for="gerant-non"><?= $this->lng['etape2']['conseil-externe-entreprise'] ?></label>
                            <input type="radio" class="custom-input" name="gerant" id="gerant-non"
                                   value="non"<?= $this->aForm['gerant'] === 'non' ? ' checked' : '' ?>
                                   data-condition="show:.prescripteur">
                        </div>
                    </div>
                </div>
                <div class="row prescripteur"><?= $this->lng['etape2']['vos-coordonnees'] ?></div>
                <div class="row prescripteur">
                    <div class="form-choose fixed">
                        <div class="radio-holder">
                            <label for="civilite_prescripteur_madame"><?= $this->lng['etape2']['madame'] ?></label>
                            <input type="radio" class="custom-input" name="civilite_prescripteur" id="civilite_prescripteur_madame"
                                   value="Mme"<?= $this->aForm['civilite_prescripteur'] == 'Mme' ? ' checked' : '' ?>>
                        </div>
                        <div class="radio-holder">
                            <label for="civilite_prescripteur_monsieur"><?= $this->lng['etape2']['monsieur'] ?></label>
                            <input type="radio" class="custom-input" name="civilite_prescripteur" id="civilite_prescripteur_monsieur"
                                   value="M."<?= $this->aForm['civilite_prescripteur'] == 'M.' ? ' checked' : '' ?>>
                        </div>
                    </div>
                </div>
                <div class="row prescripteur">
                    <input type="text" name="prenom_prescripteur" id="prenom_prescripteur"
                           placeholder="<?= $this->lng['etape2']['prenom'] ?>"
                           value="<?= $this->aForm['prenom_prescripteur'] ?>"
                           class="field required"
                           data-validators="Presence&amp;Format,{pattern:/^([^0-9]*)$/}">
                    <input type="text" name="nom_prescripteur" id="nom_prescripteur"
                           placeholder="<?= $this->lng['etape2']['nom'] ?>"
                           value="<?= $this->aForm['nom_prescripteur'] ?>"
                           class="field required"
                           data-validators="Presence&amp;Format,{pattern:/^([^0-9]*)$/}">
                    <input type="text" name="fonction_prescripteur" id="fonction_prescripteur"
                           placeholder="<?= $this->lng['etape2']['fonction'] ?>"
                           value="<?= $this->aForm['fonction_prescripteur'] ?>"
                           class="field required"
                           data-validators="Presence&amp;Format,{pattern:/^([^0-9]*)$/}">
                </div>
                <div class="row prescripteur">
                    <input type="email" name="email_prescripteur" id="email_prescripteur"
                           placeholder="<?= $this->lng['etape2']['email'] ?>"
                           value="<?= $this->aForm['email_prescripteur'] ?>"
                           class="field required"
                           data-validators="Presence&amp;Email">
                    <input type="text" name="mobile_prescripteur" id="mobile_prescripteur"
                           placeholder="<?= $this->lng['etape2']['telephone'] ?>"
                           value="<?= $this->aForm['mobile_prescripteur'] ?>"
                           class="field required"
                           data-validators="Presence&amp;Numericality&amp;Length,{minimum: 9, maximum: 14}">
                </div>
                <?php if (true === $this->bAnnualAccountsQuestion) { ?>
                    <div class="row">
                        <div class="form-choose radio_comptables">
                            <span class="title"><?= $this->lng['etape2']['exercices-comptables'] ?></span>
                            <div class="radio-holder">
                                <label for="bilans-oui"><?= $this->lng['etape2']['oui'] ?></label>
                                <input type="radio" class="custom-input" name="bilans" id="bilans-oui" value="oui"<?= $this->aForm['bilans'] === 'oui' ? ' checked' : '' ?>>
                            </div>
                            <div class="radio-holder">
                                <label for="bilans-non"><?= $this->lng['etape2']['non'] ?></label>
                                <input type="radio" class="custom-input" name="bilans" id="bilans-non" value="non"<?= $this->aForm['bilans'] === 'non' ? ' checked' : '' ?>>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <div class="row">
                    <label for="commentaires"><?= $this->lng['etape2']['label-toutes-informations-utiles'] ?></label>
                    <textarea name="commentaires" id="commentaires" cols="30" rows="10"
                              placeholder="<?= $this->lng['etape2']['toutes-informations-utiles'] ?>"
                              class="field field-mega"><?= $this->aForm['commentaires'] ?></textarea>
                </div>
                <div class="row">
                    <table>
                        <tr>
                            <td style="vertical-align:middle;"><label for="duree"><?php printf($this->lng['etape2']['choisir-duree-de-remboursement'], $this->ficelle->formatNumber($this->projects->amount)); ?> &nbsp;</label></td>
                            <td>
                                <select name="duree" id="duree" class="field field-small required custom-select">
                                    <option value="0"><?= $this->lng['etape1']['duree'] ?></option>
                                    <?php foreach ($this->dureePossible as $duree): ?>
                                        <option value="<?= $duree ?>"<?= $duree == $this->aForm['duree'] ? ' selected' : '' ?>><?= $duree ?> mois</option>
                                    <?php endforeach ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="row">
                    <div class="cb-holder">
                        <label class="cgv" for="cgv"><?= $this->lng['etape2']['je-reconnais-avoir-pris-connaissance'] ?>
                            <a style="color:#A1A5A7; text-decoration: underline;" class="cgv" target="_blank" href="<?= $this->lurl . '/' . $this->tree->getSlug($this->lienConditionsGenerales, $this->language) ?>"><?= $this->lng['etape2']['des-conditions-generales-de-vente'] ?></a>
                        </label>
                        <input type="checkbox" class="custom-input" name="cgv" id="cgv">
                    </div>
                </div>
                <div class="row">
                    <span class="form-caption"><?= $this->lng['etape2']['champs-obligatoires'] ?></span>
                </div>
                <div class="form-foot row row-cols centered">
                    <input type="hidden" name="send_form_depot_dossier"/>
                    <button class="btn" style="height: 70px; line-height: 1.2em;" type="submit">
                        <?= $this->lng['etape2']['deposer-son-dossier'] ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    var validColor = '#727272',
        errorColor = '#C84747';

    $(function() {
        $('input[type=radio]').on('change click', function() {
            $(this).parent('.radio-holder').css('color', validColor).css('font-weight', '');
        });

        $('#cgv').on('change click', function() {
            $('.cgv').css('color', validColor).css('font-weight', '');
        });

        <?php if (empty($this->aForm['email']) && false === empty($this->sStep1Email)) { ?>
        $('input[type=radio][name=gerant]').on('click change', function() {
            if ($(this).val() == 'oui') {
                $('#email').val('<?= $this->sStep1Email ?>');
                $('#email_prescripteur').val('');
            } else {
                $('#email').val('');
                $('#email_prescripteur').val('<?= $this->sStep1Email ?>');
            }
            $('#email,#email_prescripteur').removeClass('LV_valid_field LV_invalid_field')
        });
        <?php } ?>

        $('#form_depot_dossier').submit(function(event) {
            var error = false;

            if ($('input[type=radio][name=civilite]:checked').length == 0) {
                $('input[type=radio][name=civilite]').parent('.radio-holder').css('color', errorColor).css('font-weight', 'bold');
                error = true;
            }
            if ($('input[type=radio][name=gerant]:checked').length == 0) {
                $('input[type=radio][name=gerant]').parent('.radio-holder').css('color', errorColor);
                error = true;
            }
            if ($('input[type=radio][name=gerant]:checked').val() == 'non' && $('input[type=radio][name=civilite_prescripteur]:checked').length == 0) {
                $('input[type=radio][name=civilite_prescripteur]').parent('.radio-holder').css('color', errorColor);
                error = true;
            }
            if ($('input[type=radio][name=gerant]:checked').val() == 'oui' && $('#cgv').is(':checked') == false) {
                $('.cgv').css('color', errorColor).css('font-weight', 'bold');
                error = true;
            }
            if ($('input[type=radio][name=bilans]').length && $('input[type=radio][name=bilans]:checked').length == 0) {
                $('input[type=radio][name=bilans]').parent('.radio-holder').css('color', errorColor);
                error = true;
            }

            if (error) {
                event.preventDefault();
            }
        });
    });
</script>

<?php if ($this->Config['env'] == 'prod') { ?>
    <img src="https://ext.ligatus.com/conversion/?c=65835&a=7195" width="1" height="1">
<?php } ?>
