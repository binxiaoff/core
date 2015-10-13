<div class="main">
    <div class="shell">
        <p><?php printf($this->lng['etape2']['contenu'], $this->projects->amount, $this->iAverageFundingDuration); ?></p>
        <div class="register-form">
            <form action="" method="post" id="form_depot_dossier" name="form_depot_dossier" enctype="multipart/form-data">
                <div class="row">
                    <p><?= $this->lng['etape2']['raison-sociale'] ?></p>
                    <input type="text" name="raison-sociale" id="raison-sociale"
                           placeholder="<?= $this->lng['etape2']['raison-sociale'] ?>"
                           title="<?= $this->lng['etape2']['raison-sociale'] ?>"
                           value="<?= empty($this->companies->name) ? '' : $this->companies->name ?>"
                           class="field field-large required" data-validators="Presence">
                </div>
                <div class="row">
                    <div class="form-choose fixed">
                        <div class="radio-holder">
                            <label style="width: 192px;"
                                   for="radio1-1-about"><?= $this->lng['etape2']['dirigeant-entreprise'] ?></label>
                            <input<?= isset($this->companies->status_client) ? ($this->companies->status_client == 1 ? ' checked' : '') : ' checked' ?>
                                type="radio" class="custom-input" name="gerant" id="radio1-1-about" value="1"
                                data-condition="show:.check" >
                        </div>
                        <div class="radio-holder">
                            <label style="width: 192px;"
                                   for="radio1-3-about"><?= $this->lng['etape2']['conseil-externe-entreprise'] ?></label>
                            <input<?= isset($this->companies->status_conseil_externe_entreprise) && $this->companies->status_conseil_externe_entreprise == 1 ? ' checked' : ''  ?>
                                type="radio" class="custom-input" name="gerant" id="radio1-3-about" value="3"
                                data-condition="show:.identification">
                        </div>
                    </div>
                </div>
                <div class=row">
                    <p><?= $this->lng['etape2']['vos-coordonnees'] ?></p>
                </div>
                <div class="about-sections">
                    <div class="about-section identification">
                        <div class="row">
                            <div class="form-choose fixed radio_sex_prescripteur">
                                <span class="title"><?= $this->lng['etape2']['civilite'] ?></span>
                                <div class="radio-holder">
                                    <label for="female_prescripteur"><?= $this->lng['etape2']['madame'] ?></label>
                                    <input type="radio" class="custom-input" name="gender_prescripteur"
                                           id="female_prescripteur"
                                           value="Mme"<?= isset($this->prescripteurs->civilite) && $this->prescripteurs->civilite === 'Mme' ? ' checked' : '' ?>>
                                </div>
                                <div class="radio-holder">
                                    <label for="male_prescripteur"><?= $this->lng['etape2']['monsieur'] ?></label>
                                    <input type="radio" class="custom-input" name="gender_prescripteur"
                                           id="male_prescripteur"
                                           value="M."<?= isset($this->prescripteurs->civilite) && $this->prescripteurs->civilite === 'M.' ? ' checked' : '' ?>>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <input type="text" name="prescripteur_prenom" id="prescripteur_prenom"
                                   placeholder="<?= $this->lng['etape2']['prenom'] ?>"
                                   title="<?= $this->lng['etape2']['prenom'] ?>"
                                   value="<?= empty($this->prescripteurs->prenom) ? '' : $this->prescripteurs->prenom ?>"
                                   class="field field-large required"
                                   data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">
                            <input type="text" name="prescripteur_nom" id="prescripteur_nom"
                                   placeholder="<?= $this->lng['etape2']['nom'] ?>"
                                   title="<?= $this->lng['etape2']['nom'] ?>"
                                   value="<?= empty($this->prescripteurs->nom) ? '' : $this->prescripteurs->nom ?>"
                                   class="field field-large required"
                                   data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">
                        </div>
                        <div class="row">
                            <input type="text" name="prescripteur_email" id="prescripteur_email"
                                   placeholder="<?= $this->lng['etape2']['email'] ?>"
                                   title="<?= $this->lng['etape2']['email'] ?>"
                                   value="<?= empty($this->prescripteurs->email) ? '' : $this->prescripteurs->email ?>"
                                   class="field field-large required" data-validators="Presence&amp;Email"
                                   onkeyup="checkConf(this.value,'conf_email')">
                            <input type="text" name="prescripteur_conf_email" id="prescripteur_conf_email"
                                   placeholder="<?= $this->lng['etape2']['confirmation-email'] ?>"
                                   title="<?= $this->lng['etape2']['confirmation-email'] ?>"
                                   value="<?= empty($this->prescripteurs->prenom) ? '' : $this->prescripteurs->email ?>"
                                   class="field field-large required"
                                   data-validators="Confirmation,{ match: 'email' }">
                        </div>
                        <div class="row">
                            <input type="text" name="prescripteur_phone" id="prescripteur_phone"
                                   placeholder="<?= $this->lng['etape2']['telephone'] ?>"
                                   title="<?= $this->lng['etape2']['telephone'] ?>"
                                   value="<?= empty($this->prescripteurs->mobile) ? '' : $this->prescripteurs->mobile ?>"
                                   class="field field-large required"
                                   data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9, maximum: 14}">
                        </div>
                        <p><?= $this->lng['etape2']['identite-du-representant-de-la-societe'] ?></p>
                    </div>
                </div>
                <div class="row">
                    <div class="form-choose fixed radio_sex_representative">
                        <span class="title"><?= $this->lng['etape2']['civilite'] ?></span>
                        <div class="radio-holder">
                            <label for="female_representative"><?= $this->lng['etape2']['madame'] ?></label>
                            <input type="radio" class="custom-input" name="sex_representative"
                                   id="female_representative"
                                   value="Mme"<?= isset($this->clients->civilite) && $this->clients->civilite == 'Mme' ? ' checked' : '' ?>>
                        </div>
                        <div class="radio-holder">
                            <label for="male_representative"><?= $this->lng['etape2']['monsieur'] ?></label>
                            <input type="radio" class="custom-input" name="sex_representative"
                                   id="male_representative"
                                   value="M."<?= isset($this->clients->civilite) && $this->clients->civilite == 'M.' ? ' checked' : '' ?>>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <input type="text" name="nom_representative" id="nom_representative"
                           placeholder="<?= $this->lng['etape2']['nom'] ?>"
                           title="<?= $this->lng['etape2']['nom'] ?>"
                           value="<?= empty($this->clients->nom) ? '' : $this->clients->nom ?>"
                           class="field field-large required"
                           data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">

                    <input type="text" name="prenom_representative" id="prenom_representative"
                           placeholder="<?= $this->lng['etape2']['prenom'] ?>"
                           title="<?= $this->lng['etape2']['prenom'] ?>"
                           value="<?= empty($this->clients->prenom) ? '' : $this->clients->prenom ?>"
                           class="field field-large required"
                           data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">
                </div>
                <div class="row">
                    <input
                        type="email" name="email_representative" id="email_representative"
                        placeholder="<?= $this->lng['etape2']['email'] ?>"
                        title="<?= $this->lng['etape2']['email'] ?>"
                        value="<?= empty($this->clients->email) ? '' : $this->clients->email ?>"
                        class="field field-large required" data-validators="Presence&amp;Email"
                        onkeyup="checkConf(this.value,'conf_email_representative')">
                    <input
                        type="email" name="conf_email_representative" id="conf_email_representative"
                        placeholder="Confirmation Email*"
                        title="Confirmation Email*"
                        value="<?= empty($this->clients->email) ? '' : $this->clients->email ?>"
                        class="field field-large required"
                        data-validators="Confirmation, { match: 'email_representative' }">
                </div>
                <div class="row">
                    <input type="text" name="fonction_representative" id="fonction_representative"
                           placeholder="<?= $this->lng['etape2']['fonction'] ?>"
                           title="<?= $this->lng['etape2']['fonction'] ?>"
                           value="<?= empty($this->clients->fonction) ? '' : $this->clients->fonction ?>"
                           class="field field-large required"
                           data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">
                    <input type="text" name="portable_representative" id="portable_representative"
                           placeholder="<?= $this->lng['etape2']['telephone'] ?>"
                           title="<?= $this->lng['etape2']['telephone'] ?>"
                           value="<?= empty($this->clients->mobile) ? '' : $this->clients->mobile ?>"
                           class="field field-large required"
                           data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9, maximum: 14}">
                </div>
                <?php if (true === $this->bAnnualAccountsQuestion) { ?>
                    <div class="row">
                        <div class="form-choose radio_comptables">
                            <span class="title"><?= $this->lng['etape2']['exercices-comptables'] ?></span>
                            <div class="radio-holder">
                                <label for="comptables-oui"><?= $this->lng['etape2']['oui'] ?></label>
                                <input type="radio" class="custom-input" name="comptables" id="comptables-oui" value="1">
                            </div>
                            <div class="radio-holder">
                                <label for="comptables-non"><?= $this->lng['etape2']['non'] ?></label>
                                <input type="radio" class="custom-input" name="comptables" id="comptables-non" value="0">
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <div class="row">
                    <label for="radio1-3-about"><?= $this->lng['etape2']['label-toutes-informations-utiles'] ?></label>
                    <textarea name="comments" id="comments" cols="30" rows="10"
                              placeholder="<?= $this->lng['etape2']['toutes-informations-utiles'] ?>"
                              title="<?= $this->lng['etape2']['toutes-informations-utiles'] ?>"
                              class="field field-mega"><?= empty($this->projects->comments) ? '' : $this->projects->comments ?></textarea>
                </div>
                <div class="row">
                    <table>
                        <tr>
                            <td style="vertical-align:middle;"><label for="duree"><?php printf($this->lng['etape2']['duree-amortissement'], $this->projects->amount); ?> &nbsp;</label></td>
                            <td>
                                <select name="duree" id="duree" class="field field-small required custom-select">
                                    <option value="0"><?= $this->lng['etape1']['duree'] ?></option>
                                    <?php foreach ($this->dureePossible as $duree): ?>
                                        <option value="<?= $duree ?>"><?= $duree ?> mois</option>
                                    <?php endforeach ?>
                                </select></td>
                        </tr>
                    </table>
                </div>
                <div class="row">
                    <div class="cb-holder">
                        <label class="check" for="accept-cgu"><?= $this->lng['etape2']['je-reconnais-avoir-pris-connaissance'] ?>
                            <a style="color:#A1A5A7; text-decoration: underline;" class="check" target="_blank"
                               href="<?= $this->lurl . '/' . $this->tree->getSlug($this->lienConditionsGenerales, $this->language) ?>"><?= $this->lng['etape2']['des-conditions-generales-de-vente'] ?></a></label>
                        <input type="checkbox" class="custom-input required" name="accept-cgu" id="accept-cgu">
                    </div>
                </div>
                <span class="form-caption"><?= $this->lng['etape2']['champs-obligatoires'] ?></span>
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

<style type="text/css">
    .file-upload {overflow: visible;}
    .uploader {overflow: hidden;}
</style>
<script>
    $(document).ready(function() {
        $('#conf_email_representative').bind('paste', function(e) {
            e.preventDefault();
        });
        $('#conf_prescripteur_email').bind('paste', function(e) {
            e.preventDefault();
        });
    });

    $('input.file-field').on('change', function() {
        var $self = $(this),
            val = $self.val()

        if (val.length != 0 || val != '') {
            $self.closest('.uploader').find('input.field').val(val);
        }
        ;
    });

    $("#form_depot_dossier").submit(function(event) {
        var radio = true;

        if ($('input[type=radio][name=radio1-about]:checked').attr('value') == '3') {
            if ($('input[type=radio][name=sex]:checked').length) {
                $('.radio_sex').css('color', '#727272');
            } else {
                $('.radio_sex').css('color', '#C84747');
                radio = false
            }
        } else {
            $('.radio_sex').css('color', '#727272');
        }

        if ($('#accept-cgu').is(':checked') == false) {
            $('.check').css('color', '#C84747');
            radio = false
        } else {
            $('.check').css('color', '#727272');
        }

        if (radio == false) {
            event.preventDefault();
        }
    });
</script>

<?php if ($this->Config['env'] == 'prod') { ?>
    <img src="https://ext.ligatus.com/conversion/?c=65835&a=7195" width="1" height="1">
<?php } ?>
