<div class="main">
    <div class="shell">
        <p><?= $this->lng['depot-de-dossier']['titre-prospect'] ?></p>
        <div class="register-form">
            <form action="<?= parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?>" method="post" id="form_depot_dossier" name="form_depot_dossier" enctype="multipart/form-data">
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
            $(this).parent('.radio-holder').css('color', validColor).css('font-weight', '');
        });

        if ($('input[type=radio][name=civilite]:checked').length == 0) {
            $('input[type=radio][name=civilite]').parent('.radio-holder').css('color', errorColor).css('font-weight', 'bold');
            error = true;
        }

        if (error) {
            event.preventDefault();
        }
    });
</script>

<?php if ($this->getParameter('kernel.environment') == 'prod') { ?>
    <img src="https://ext.ligatus.com/conversion/?c=65835&a=7195" width="1" height="1">
<?php } ?>
