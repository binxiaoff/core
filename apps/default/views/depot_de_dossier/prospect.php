<div class="main">
    <div class="shell">
        <p><?= $this->lng['depot-de-dossier']['titre-prospect'] ?></p>
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
                           value="<?= $this->aForm['email'] ?>"
                           class="field required"
                           data-validators="Presence&amp;Email">
                    <input type="text" name="telephone" id="telephone"
                           placeholder="<?= $this->lng['etape2']['telephone'] ?>"
                           value="<?= $this->aForm['telephone'] ?>"
                           class="field required"
                           data-validators="Presence&amp;Numericality&amp;Length,{minimum: 9, maximum: 14}">
                </div>
                <div class="row">
                    <div class="form-choose">
                        <div class="radio-holder">
                            <label for="gerant-oui"><?= $this->lng['etape2']['dirigeant-entreprise'] ?></label>
                            <input type="radio" class="custom-input" name="gerant" id="gerant-oui"
                                   value="oui"<?= $this->aForm['gerant'] === 'oui' ? ' checked' : '' ?>>
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
                    <input type="text" name="telephone_prescripteur" id="telephone_prescripteur"
                           placeholder="<?= $this->lng['etape2']['telephone'] ?>"
                           value="<?= $this->aForm['telephone_prescripteur'] ?>"
                           class="field required"
                           data-validators="Presence&amp;Numericality&amp;Length,{minimum: 9, maximum: 14}">
                </div>
                <div class="row">
                    <span class="form-caption"><?= $this->lng['etape2']['champs-obligatoires'] ?></span>
                </div>
                <div class="form-foot row row-cols centered">
                    <input type="hidden" name="send_form_depot_dossier">
                    <button class="btn" type="submit"><?= $this->lng['depot-de-dossier']['valider'] ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    var validColor = '#727272',
        errorColor = '#C84747';

    $('#form_depot_dossier').submit(function(event) {
        var error = false;

        $('input[type=radio]').on('change click', function() {
            $(this).parent('.radio-holder').css('color', validColor);
        });

        if ($('input[type=radio][name=civilite]:checked').length == 0) {
            $('input[type=radio][name=civilite]').parent('.radio-holder').css('color', errorColor);
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

        if (error) {
            event.preventDefault();
        }
    });
</script>

<?php if ($this->Config['env'] == 'prod') { ?>
    <img src="https://ext.ligatus.com/conversion/?c=65835&a=7195" width="1" height="1">
<?php } ?>
