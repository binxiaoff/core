<?php
//Ajout CM 06/08/14
$dateDepartControlPays = strtotime('2014-07-31 18:00:00');

// on ajoute une petite restriction de date pour rendre certains champs obligatoires
if (strtotime($this->companies->added) >= $dateDepartControlPays) {
    $required = 'required';
    echo "REQ: " . $required;
}
?>

<div class="account-data">
    <h2><?= $this->lng['profile']['titre-1'] ?></h2>

    <?php if (isset($_SESSION['reponse_profile_perso']) && $_SESSION['reponse_profile_perso'] != '') : ?>
        <div class="reponseProfile"><?= $_SESSION['reponse_profile_perso'] ?></div>
    <?php unset($_SESSION['reponse_profile_perso']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['reponse_email']) && $_SESSION['reponse_email'] != '') : ?>
        <div class="reponseProfile" style="color:#c84747;"><?= $_SESSION['reponse_email'] ?></div>
    <?php unset($_SESSION['reponse_email']); ?>
    <?php endif; ?>

    <form action="<?= $this->lurl ?>/profile/societe/3" method="post" id="form_societe_perso" name="form_societe_perso" enctype="multipart/form-data">
        <div class="part_societe1">
            <div class="row">
                <input type="text" name="raison_sociale_inscription" id="raison_sociale_inscription" value="<?= ($this->companies->name != '' ? $this->companies->name : $this->lng['etape1']['raison-sociale']) ?>" title="<?= $this->lng['etape1']['raison-sociale'] ?>" class="field field-large required" data-validators="Presence">
                <input type="text" name="forme_juridique_inscription" id="forme_juridique_inscription" value="<?= ($this->companies->forme != '' ? $this->companies->forme : $this->lng['etape1']['forme-juridique']) ?>" title="<?= $this->lng['etape1']['forme-juridique'] ?>" class="field field-large required" data-validators="Presence">
            </div><!-- /.row -->
            <div class="row rel">
                <input type="text" name="capital_social_inscription" id="capital_social_inscription" title="<?= $this->lng['etape1']['capital-sociale'] ?>" value="<?= ($this->companies->capital != 0 ? number_format($this->companies->capital, 2, '.', ' ') : $this->lng['etape1']['capital-sociale']) ?>" class="field field-large euro-field required" onkeyup="lisibilite_nombre(this.value,this.id);" data-validators="Presence&amp;Numericality">
                <input type="text" name="siren_inscription" id="siren_inscription" title="<?= $this->lng['etape1']['siren'] ?>" value="<?= ($this->companies->siren != '' ? $this->companies->siren : $this->lng['etape1']['siren']) ?>" class="field field-large required" disabled="disabled" />
            </div><!-- /.row -->
            <div class="row">
                <input type="text" name="phone_inscription" id="phone_inscription" value="<?= ($this->companies->phone != '' ? str_replace(' ', '', $this->companies->phone) : $this->lng['etape1']['telephone']) ?>" title="<?= $this->lng['etape1']['telephone'] ?>" class="field field-large required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9,maximum: 14}">
                <input type="text" name="siret_inscription" id="siret_inscription" title="<?= $this->lng['etape1']['siret'] ?>" value="<?= ($this->companies->siret != '' ? $this->companies->siret : $this->lng['etape1']['siret']) ?>" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 14, maximum: 14}" class="field field-large required">
            </div><!-- /.row -->
        </div>

        <div class="les_deux">
            <p>
                <?= $this->lng['etape1']['adresse-fiscale'] ?>
                <i class="icon-help tooltip-anchor" data-placement="right" title="<?= $this->lng['etape1']['info-adresse-fiscale'] ?>"></i>
            </p>

            <div class="row">
                <input type="text" id="adresse_inscriptionE" name="adresse_inscriptionE" title="<?= $this->lng['etape1']['adresse'] ?>" value="<?= ($this->companies->adresse1 != '' ? $this->companies->adresse1 : $this->lng['etape1']['adresse']) ?>" class="field field-mega required" data-validators="Presence">
            </div><!-- /.row -->

            <div class="row row-triple-fields">
                <input type="text" name="postalE" id="postalE" class="field field-small required" data-autocomplete="post_code"
                       placeholder="<?= $this->lng['etape1']['code-postal'] ?>" title="<?= $this->lng['etape1']['code-postal'] ?>" value="<?= ($this->companies->zip != 0 ? $this->companies->zip : '') ?>"/>
                <input type="text" id="ville_inscriptionE" name="ville_inscriptionE" class="field field-small required" data-autocomplete="city"
                       placeholder="<?= $this->lng['etape1']['ville'] ?>" title="<?= $this->lng['etape1']['ville'] ?>" value="<?= ($this->companies->city != '' ? $this->companies->city : '') ?>"/>

                <?php //Ajout CM 06/08/14 ?>
                <select name="pays1E" id="pays1E" class="country custom-select <?=$required?> field-small">
                    <option><?=$this->lng['etape1']['pays']?></option>
                    <option><?=$this->lng['etape1']['pays']?></option>
                    <?
                    foreach($this->lPays as $p)
                    {
                        ?><option <?=($this->companies->id_pays == $p['id_pays']?'selected':'')?> value="<?=$p['id_pays']?>"><?=$p['fr']?></option><?
                    }
                    ?>
                </select>
            </div><!-- /.row -->

            <div class="row">
                <div class="cb-holder cb-mon-addresse <?= ($this->companies->status_adresse_correspondance == 0 ? '' : 'checked') ?>">
                    <label for="mon-addresse"><?= $this->lng['etape1']['meme-adresse'] ?></label>

                    <input <?= ($this->companies->status_adresse_correspondance == 0 ? '' : 'checked="checked"') ?> type="checkbox" class="custom-input" name="mon-addresse" id="mon-addresse" data-condition="hide:.add-address">
                </div><!-- /.cb-holder -->
            </div><!-- /.row -->

            <div class="add-address">
                <p><?= $this->lng['etape1']['adresse-de-correspondance'] ?></p>

                <div class="row">
                    <input type="text" id="address2E" name="adress2E" title="<?= $this->lng['etape1']['adresse'] ?>" value="<?= ($this->clients_adresses->adresse1 != '' ? $this->clients_adresses->adresse1 : $this->lng['etape1']['adresse']) ?>" class="field field-mega required" data-validators="Presence">
                </div><!-- /.row -->

                <div class="row row-triple-fields">
                    <input type="text" id="postal2E" name="postal2E" class="field field-small required" data-autocomplete="post_code"
                           placeholder="<?= $this->lng['etape1']['code-postal'] ?>" value="<?= ($this->clients_adresses->cp != 0 ? $this->clients_adresses->cp : '') ?>" title="<?= $this->lng['etape1']['code-postal'] ?>"/>
                    <input type="text" id="ville2E" name="ville2E" class="field field-small required" data-autocomplete="city"
                           placeholder="<?=$this->lng['etape1']['ville']?>" title="<?= $this->lng['etape1']['ville'] ?>" value="<?= ($this->clients_adresses->ville != '' ? $this->clients_adresses->ville : '') ?>" />
                    <?php //Ajout CM 06/08/14 ?>
                    <select name="pays2E" id="pays2E" class="country custom-select <?=$required?> field-small">
                        <option><?=$this->lng['etape1']['pays']?></option>
                        <option><?=$this->lng['etape1']['pays']?></option>
                        <?
                        foreach($this->lPays as $p)
                        {
                            ?><option <?=($this->clients_adresses->id_pays == $p['id_pays']?'selected':'')?> value="<?=$p['id_pays']?>"><?=$p['fr']?></option><?
                        }
                        ?>
                    </select>
                </div><!-- /.row -->
            </div>

            <div class="part_societe2">
                <div class="row">
                    <div class="form-choose list-view">
                        <span class="title"><?= $this->lng['etape1']['vous-etes'] ?> :</span>
                        <div class="radio-holder">
                            <label for="enterprise-1"><?= $this->lng['etape1']['je-suis-le-dirigeant-de-lentreprise'] ?></label>
                            <input <?= ($this->companies->status_client == 1 ? 'checked="checked"' : '') ?> value="1" type="radio" class="custom-input" name="enterprise" id="enterprise-1" data-condition="show:.add-new-profile">
                        </div><!-- /.radio-holder -->
                        <div class="radio-holder">
                            <label for="enterprise-2"><?= $this->lng['etape1']['je-ne-suis-pas-le-dirigeant-de-lentreprise'] ?></label>
                            <input <?= ($this->companies->status_client == 2 ? 'checked="checked"' : '') ?> value="2" type="radio" class="custom-input" name="enterprise" id="enterprise-2" data-condition="show:.add-new-profile, .identification">
                        </div><!-- /.radio-holder -->
                        <div class="radio-holder">
                            <label for="enterprise-3"><?= $this->lng['etape1']['je-suis-un-conseil-externe-de-lenterprise'] ?></label>
                            <input <?= ($this->companies->status_client == 3 ? 'checked="checked"' : '') ?> value="3" type="radio" class="custom-input" name="enterprise" id="enterprise-3" data-condition="show:.add-new-profile, .identification, .external-consultant">
                        </div><!-- /.radio-holder -->
                    </div><!-- /.form-choose -->
                </div><!-- /.row -->

                <div class="external-consultant">
                    <div class="row">
                        <select name="external-consultant" style="width:458px;" id="external-consultant" class="field field-large custom-select required">
                            <option><?= $this->lng['etape1']['choisir'] ?></option>
                            <option><?= $this->lng['etape1']['choisir'] ?></option>
                            <?php foreach ($this->conseil_externe as $k => $conseil_externe) :?>
                                <option <?= ($this->companies->status_conseil_externe_entreprise == $k ? 'selected' : '') ?> value="<?= $k ?>" ><?= $conseil_externe ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input <?= ($this->modif == false ? 'style="display:none;"' : ($this->companies->status_conseil_externe_entreprise == 3 ? 'style="display:block;"' : '')) ?> type="text" name="autre_inscription" title="<?= $this->lng['etape1']['autre'] ?>" value="<?= ($this->companies->preciser_conseil_externe_entreprise != '' ? $this->companies->preciser_conseil_externe_entreprise : $this->lng['etape1']['autre']) ?>" id="autre_inscription" class="field field-large">
                    </div><!-- /.row -->
                </div><!-- /.external-consultant -->

                <div class="add-new-profile">
                    <p><?= $this->lng['etape1']['vos-coordonnees'] ?></p>
                    <div class="row" id="radio_genre1">
                        <div class="form-choose">
                            <span class="title"><?= $this->lng['etape1']['civilite'] ?></span>
                            <div class="radio-holder">
                                <label for="female1"><?= $this->lng['etape1']['madame'] ?></label>
                                <input <?= ($this->clients->civilite == 'Mme' ? 'checked="checked"' : '') ?> type="radio" class="custom-input" name="genre1" id="female1" value="Mme">
                            </div><!-- /.radio-holder -->
                            <div class="radio-holder">
                                <label for="male1"><?= $this->lng['etape1']['monsieur'] ?></label>
                                <input type="radio" class="custom-input" name="genre1" id="male1" <?= ($this->clients->civilite == 'M.' ? 'checked="checked"' : '') ?> value="M.">
                            </div><!-- /.radio-holder -->
                        </div><!-- /.form-choose -->
                    </div><!-- /.row -->
                    <div class="row">
                        <input type="text" name="nom_inscription" title="<?= $this->lng['etape1']['nom'] ?>" value="<?= ($this->clients->nom != '' ? $this->clients->nom : $this->lng['etape1']['nom']) ?>" id="nom_inscription" class="field field-large required" data-validators="Presence&Format,{  pattern:/^([^0-9]*)$/}">
                        <input type="text" name="prenom_inscription" title="<?= $this->lng['etape1']['prenom'] ?>" value="<?= ($this->clients->prenom != '' ? $this->clients->prenom : $this->lng['etape1']['prenom']) ?>" id="prenom_inscription" class="field field-large required" data-validators="Presence&Format,{  pattern:/^([^0-9]*)$/}">
                    </div><!-- /.row -->
                    <div class="row">
                        <input type="text" name="fonction_inscription" title="<?= $this->lng['etape1']['fonction'] ?>" value="<?= ($this->clients->fonction != '' ? $this->clients->fonction : $this->lng['etape1']['fonction']) ?>" id="fonction_inscription" class="field field-large required" data-validators="Presence">
                        <input type="text" name="phone_new_inscription" id="phone_new_inscription" value="<?= ($this->clients->telephone != '' ? $this->clients->telephone : $this->lng['etape1']['telephone']) ?>" title="<?= $this->lng['etape1']['telephone'] ?>" class="field field-large required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9,maximum: 14}">

                    </div><!-- /.row -->
                    <div class="row">
                        <input type="text" name="email_inscription" title="<?= $this->lng['etape1']['email'] ?>" value="<?= ($this->clients->email ? $this->clients->email : $this->lng['etape1']['email']) ?>" id="email_inscription" class="field field-large required" data-validators="Presence&amp;Email&amp;Format,{ pattern:/^((?!@yopmail.com).)*$/}" onkeyup="checkConf(this.value,'conf_email_inscription')">
                        <input type="text" name="conf_email_inscription" title="<?= $this->lng['etape1']['confirmation-email'] ?>" value="<?= ($this->clients->email ? $this->clients->email : $this->lng['etape1']['confirmation-email']) ?>" id="conf_email_inscription" class="field field-large required" data-validators="Confirmation,{ match: 'email_inscription' }&amp;Format,{ pattern:/^((?!@yopmail.com).)*$/}">
                    </div><!-- /.row -->
                </div><!-- /.add-new-profile -->

                <div class="identification">
                    <p><?= $this->lng['etape1']['identification-du-dirigeant'] ?></p>
                    <div class="row" id="radio_genre2">
                        <div class="form-choose">
                            <span class="title"><?= $this->lng['etape1']['civilite'] ?></span>
                            <div class="radio-holder">
                                <label for="female2"><?= $this->lng['etape1']['madame'] ?></label>
                                <input <?= ($this->companies->civilite_dirigeant == 'Mme' ? 'checked="checked"' : '') ?> type="radio" class="custom-input" name="genre2" id="female2" value="Mme">
                            </div><!-- /.radio-holder -->
                            <div class="radio-holder">
                                <label for="male2"><?= $this->lng['etape1']['monsieur'] ?></label>
                                <input type="radio" class="custom-input" name="genre2" id="male2" <?= ($this->companies->civilite_dirigeant == 'M.' ? 'checked="checked"' : '') ?> value="M.">
                            </div><!-- /.radio-holder -->
                        </div><!-- /.form-choose -->
                    </div><!-- /.row -->

                    <div class="row">
                        <input type="text" name="nom2_inscription" title="<?= $this->lng['etape1']['nom'] ?>" value="<?= ($this->companies->nom_dirigeant != '' ? $this->companies->nom_dirigeant : $this->lng['etape1']['nom']) ?>" id="nom2_inscription" class="field field-large required" data-validators="Presence&Format,{  pattern:/^([^0-9]*)$/}">
                        <input type="text" name="prenom2_inscription" title="<?= $this->lng['etape1']['prenom'] ?>" value="<?= ($this->companies->prenom_dirigeant != '' ? $this->companies->prenom_dirigeant : $this->lng['etape1']['prenom']) ?>" id="prenom2_inscription" class="field field-large required" data-validators="Presence&Format,{  pattern:/^([^0-9]*)$/}">
                    </div><!-- /.row -->
                    <div class="row">
                        <input type="text" name="fonction2_inscription" title="<?= $this->lng['etape1']['fonction'] ?>" value="<?= ($this->companies->fonction_dirigeant != '' ? $this->companies->fonction_dirigeant : $this->lng['etape1']['fonction']) ?>" id="fonction2_inscription" class="field field-large required" data-validators="Presence">
                        <input type="text" name="email2_inscription" title="<?= $this->lng['etape1']['email'] ?>" value="<?= ($this->companies->email_dirigeant ? $this->companies->email_dirigeant : $this->lng['etape1']['email']) ?>" id="email2_inscription" class="field field-large required" data-validators="Presence&amp;Email">
                    </div><!-- /.row -->
                    <div class="row">
                        <input type="text" name="phone_new2_inscription" id="phone_new2_inscription" value="<?= ($this->companies->phone_dirigeant != '' ? $this->companies->phone_dirigeant : $this->lng['etape1']['telephone']) ?>" title="<?= $this->lng['etape1']['telephone'] ?>" class="field field-large required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9,maximum: 14}">
                    </div><!-- /.row -->

                    <p><?= $this->lng['etape1']['contenu-dirigeant'] ?></p>

                </div><!-- /.identification -->
            </div>

            <em class="change_identite"><?= $this->lng['profile']['les-informations-relatives-a-votre-identite-ont-ete-modifiees'] ?></em>
        </div>

        <div class="row">
            <div class="cb-holder">

                <span class="form-caption"><?= $this->lng['etape1']['champs-obligatoires'] ?></span>
            </div><!-- /.cb-holder -->
        </div><!-- /.row -->

        <div class="form-foot row row-cols centered">
            <input type="hidden" name="send_form_societe_perso">
            <button class="btn" onClick="$('#form_societe_perso').submit();" type="button"><?= $this->lng['etape1']['valider'] ?>
                <i class="icon-arrow-next"></i></button>
        </div><!-- /.form-foot foot-cols -->

        <script type="text/javascript">
            $(document).ready(function () {
                $('#conf_email').bind('paste', function (e) {
                    e.preventDefault();
                });
                // confirmation email preteur societe
                $('#conf_email_inscription').bind('paste', function (e) {
                    e.preventDefault();
                });
                $('#email_inscription').bind('paste', function (e) {
                    e.preventDefault();
                });

                $('select#external-consultant').on('change', function () {
                    if ($('option:selected', this).val() == '3') {
                        $('#autre_inscription').show();
                    }
                    else {
                        $('#autre_inscription').hide();
                    }
                });

                initAutocompleteCity();
            });

            // Submit formulaire inscription preteur societe
            $("#form_societe_perso").submit(function (event) {
                var radio = true;

                // controle cp
                if (controlePostCodeCity($('#postalE'), $('#ville_inscriptionE'), $('#pays1E'), false) == false) {
                    radio = false
                }

                if ($('#mon-addresse').is(':checked') == false) {
                    // controle cp
                    if (controlePostCodeCity($('#postal2E'), $('#ville2E'), $('#pays2E'), false) == false) {
                        radio = false
                    }
                }

                // Civilite vos cordonnées
                if ($('input[type=radio][name=genre1]:checked').length) {
                    $('#radio_genre1').css('color', '#727272');
                }
                else {
                    $('#radio_genre1').css('color', '#C84747');
                    radio = false;
                }

                // type d'utilisateur
                var radio_enterprise = $('input[type=radio][name=enterprise]:checked').attr('value');

                if (radio_enterprise == 2 || radio_enterprise == 3) {
                    if ($('input[type=radio][name=genre2]:checked').length) {
                        $('#radio_genre2').css('color', '#727272');
                    }
                    else {
                        $('#radio_genre2').css('color', '#C84747');
                        radio = false;
                    }
                }
                else $('#radio_genre2').css('color', '#727272');

                if (radio == false) {
                    event.preventDefault();
                }

            });

        </script>
