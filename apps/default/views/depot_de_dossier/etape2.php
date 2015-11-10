<style type="text/css">
    .uploader input.field { width: 280px;}
</style>


<?php
/* Pour la version responsive, on masque les champs d'upload fichier et on enregistrera du coup l'emprunteur en tant que prospect */
?>
<script>
    var is_responsive = false; // pour debloquer le ctrl des champs file
    
    $(document).ready(function() {
        $('.liasse').attr("id", "dernière-liasse-fiscale");
        $('.liasse').addClass('required');
        
        if ($(window).width() < 768) {
            $('#upload_files').hide();
            $('#is_responsive').val('true');
            $('.liasse').removeClass('required');
            $('.liasse').removeAttr("data-validators");
            $('.liasse').removeAttr("id");
            is_responsive = true;
        }
        else
        {
            $('#upload_files').show();
            $('#is_responsive').val('false');            
            $('.liasse').attr("id", "dernière-liasse-fiscale");
            $('.liasse').addClass('required');
            is_responsive = false;
        }
        
    });
    
    
    $(window).on('load resize', function () {
        if ($(window).width() < 768) {
            $('#upload_files').hide();
            $('#is_responsive').val('true');
            $('.liasse').removeClass('required');
            $('.liasse').removeAttr("data-validators");
            $('.liasse').removeAttr("id");
            is_responsive = true;
        }
        else
        {
            $('#upload_files').show();
            $('#is_responsive').val('false');            
            $('.liasse').attr("id", "dernière-liasse-fiscale");
            $('.liasse').addClass('required');
            is_responsive = false;
        }

    });
</script>


<div class="main">
    <div class="shell">

        <?= $this->fireView('../blocs/depot-de-dossier') ?>
        <p><?= $this->lng['etape2']['contenu'] ?></p>
        <?
        if (isset($this->params[0]) && $this->params[0] == 'nok')
        {
            echo $this->lng['etape1']['contenu-non-eligible'];
        }
        elseif (isset($_SESSION['error_pre_empr']))
        {
            echo $_SESSION['error_pre_empr'];
            unset($_SESSION['error_pre_empr']);
        }
        else
        {

            if ($this->error_email_representative_exist == true || $this->error_email_exist == true)
            {
                ?><p style="color:#c84747;text-align:center;"><?= $this->lng['etape2']['erreur-email'] ?></p><?
            }
            ?>

            <div class="register-form">
                <form action="" method="post" id="form_depot_dossier" name="form_depot_dossier" enctype="multipart/form-data">
                    <div class="row">
                        <p><?= $this->lng['etape1']['identite-de-la-societe'] ?></p>

                        <input type="text" name="raison-sociale" id="raison-sociale" title="<?= $this->lng['etape2']['raison-sociale'] ?>" value="<?= ($this->companies->name != '' ? $this->companies->name : $this->lng['etape2']['raison-sociale']) ?>" class="field field-large required" data-validators="Presence">

                    </div><!-- /.row -->

                    <div class="row">
                        <p><?= $this->lng['etape2']['identite-du-representant-de-la-societe'] ?></p>

                        <input type="text" name="nom_representative" title="<?= $this->lng['etape2']['nom'] ?>" value="<?= ($this->nom_dirigeant != '' ? $this->nom_dirigeant : $this->lng['etape2']['nom']) ?>" id="nom_representative" class="field field-large required" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">
                        <input type="text" name="prenom_representative" title="<?= $this->lng['etape2']['prenom'] ?>" value="<?= ($this->prenom_dirigeant != '' ? $this->prenom_dirigeant : $this->lng['etape2']['prenom']) ?>" id="prenom_representative" class="field field-large required" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">
                    </div><!-- /.row -->

                    <div class="row">
                        <input type="text" name="email_representative" title="<?= $this->lng['etape2']['email'] ?>" value="<?= ($this->email_dirigeant != '' ? $this->email_dirigeant : $this->lng['etape2']['email']) ?>" id="email_representative" class="field field-large required" data-validators="Presence&amp;Email" onkeyup="checkConf(this.value, 'conf_email_representative')">
                        <input type="text" name="conf_email_representative" title="Confirmation Email*" value="<?= ($this->conf_email_representative != '' ? $this->conf_email_representative : 'Confirmation Email*') ?>" id="conf_email_representative" class="field field-large required" data-validators="Confirmation, { match: 'email_representative' }" >
                    </div><!-- /.row -->

                    <div class="row">
                        <input type="text" name="phone_representative" id="phone_representative" value="<?= ($this->phone_dirigeant != '' ? $this->phone_dirigeant : $this->lng['etape2']['telephone']) ?>" title="<?= $this->lng['etape2']['telephone'] ?>" class="field field-large required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9, maximum: 14}">
                        <input type="text" name="fonction_representative" title="<?= $this->lng['etape2']['fonction'] ?>" value="<?= ($this->fonction_dirigeant != '' ? $this->fonction_dirigeant : $this->lng['etape2']['fonction']) ?>" id="fonction_representative" class="field field-large required" data-validators="Presence">
                    </div><!-- /.row -->

                    <div class="row">
                        <div class="form-choose fixed">
                            <span class="title"><?= $this->lng['etape2']['vous-etes'] ?></span>

                            <div class="radio-holder-wrap radios-about" style="float: left; width:733px;">
                                <div class="radio-holder" style="width:733px; padding-bottom: 10px;">
                                    <label for="radio1-1-about"><?= $this->lng['etape2']['dirigeant-entreprise'] ?></label>
                                    <input <?= (isset($_POST['send_form_depot_dossier']) ? ($this->companies->status_client == 1 ? 'checked' : '') : 'checked') ?> type="radio" class="custom-input" name="radio1-about" id="radio1-1-about" value="1">
                                </div><!-- /.radio-holder -->

                                <div class="radio-holder" style="width:733px; padding-bottom: 10px;">
                                    <label for="radio1-3-about"><?= $this->lng['etape2']['conseil-externe-entreprise'] ?></label>
                                    <input <?= ($this->companies->status_client == 3 ? 'checked' : '') ?> type="radio" class="custom-input" name="radio1-about" id="radio1-3-about" value="3" data-condition="show:.identification">
                                </div><!-- /.radio-holder -->
                            </div><!-- /.radio-holder-wrap -->
                        </div><!-- /.form-choose -->
                    </div><!-- /.row -->

                    <div class="about-sections">

                        <div class="about-section">

                        </div><!-- /.about-section -->

                        <div class="about-section identification">
                            <div class="row">
                                <select name="autre" style="width:458px;" id="autre" class="field field-large custom-select required">
                                    <option value="0"><?= $this->lng['etape2']['choisir'] ?></option>
                                    <option value="0"><?= $this->lng['etape2']['choisir'] ?></option>
                                    <?
                                    foreach ($this->conseil_externe as $k => $conseil_externe)
                                    {
                                        ?><option <?= ($this->companies->status_conseil_externe_entreprise == $k ? 'selected' : '') ?> value="<?= $k ?>" ><?= $conseil_externe ?></option><?
                                    }
                                    ?>
                                </select>

                                <input style="display:none;" type="text" name="autre-preciser" title="<?= $this->lng['etape2']['autre'] ?>" value="<?= ($this->companies->preciser_conseil_externe_entreprise != '' ? $this->companies->preciser_conseil_externe_entreprise : $this->lng['etape2']['autre']) ?>" id="autre-preciser" class="field field-large">
                            </div><!-- /.row -->

                            <div class="row" >
                                <p><?= $this->lng['etape2']['vos-coordonnees'] ?></p>

                                <div class="form-choose fixed radio_sex">
                                    <span class="title"><?= $this->lng['etape2']['civilite'] ?></span>
                                    <div class="radio-holder">
                                        <label for="female"><?= $this->lng['etape2']['madame'] ?></label>
                                        <input type="radio" class="custom-input" name="sex" id="female"  value="Mme" <?= ($this->clients->civilite == 'Mme' ? 'checked="checked"' : '') ?>>
                                    </div><!-- /.radio-holder -->

                                    <div class="radio-holder">
                                        <label for="male"><?= $this->lng['etape2']['monsieur'] ?></label>
                                        <input type="radio" class="custom-input" name="sex" id="male"  value="M." <?= ($this->clients->civilite == 'M.' ? 'checked="checked"' : '') ?>>
                                    </div><!-- /.radio-holder -->
                                </div><!-- /.form-choose -->
                            </div><!-- /.row -->

                            <div class="row">
                                <input type="text" name="nom-famille" id="nom-famille" title="<?= $this->lng['etape2']['nom'] ?>" value="<?= ($this->clients->nom != '' ? $this->clients->nom : $this->lng['etape2']['nom']) ?>" class="field field-large required" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">
                                <input type="text" name="prenom" id="prenom" title="<?= $this->lng['etape2']['prenom'] ?>" value="<?= ($this->clients->prenom != '' ? $this->clients->prenom : $this->lng['etape2']['prenom']) ?>" class="field field-large required" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}" >
                            </div><!-- /.row -->

                            <div class="row">
                                <input type="text" name="email" id="email" title="<?= $this->lng['etape2']['email'] ?>" value="<?= ($this->clients->email != '' ? $this->clients->email : $this->lng['etape2']['email']) ?>" class="field field-large required" data-validators="Presence&amp;Email" onkeyup="checkConf(this.value, 'conf_email')" >
                                <input type="text" name="conf_email" id="conf_email" title="<?= $this->lng['etape2']['confirmation-email'] ?>" value="<?= ($this->conf_email != '' ? $this->conf_email : $this->lng['etape2']['confirmation-email']) ?>" class="field field-large required" data-validators="Confirmation,{ match: 'email' }" >
                            </div><!-- /.row -->

                            <div class="row">
                                <input type="text" name="phone" id="phone" value="<?= ($this->clients->telephone != '' ? $this->clients->telephone : $this->lng['etape2']['telephone']) ?>" title="<?= $this->lng['etape2']['telephone'] ?>" class="field field-large required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9, maximum: 14}">
                                <input type="text" name="fonction" title="<?= $this->lng['etape2']['fonction'] ?>" value="<?= ($this->clients->fonction != '' ? $this->clients->fonction : $this->lng['etape2']['fonction']) ?>" id="fonction" class="field field-large required" data-validators="Presence">
                            </div><!-- /.row -->
                        </div><!-- /.about-section -->
                    </div><!-- /.about-sections -->

                    <input type="hidden" id="is_responsive" name="is_responsive" value="false">
                    <div class="row" id="upload_files">
                        <p><?= $this->lng['etape2']['documents-financiers'] ?></p>

                        <div class="uploader" data-file="1" style="width:460px; float: left;">
                            <span style="display:block;">Dernière liasse fiscale*</span>
                            <input type="text" class="field required liasse"  data-validators="Presence" readonly="readonly" value="<?= $this->companies_details->fichier_derniere_liasse_fiscale ?>">
                            <div class="file-holder">
                                <span class="btn btn-small" style="line-height:40px; padding: 0 15px; margin-top: 3px;">
                                    <?= $this->lng['etape2']['parcourir'] ?><span class="file-upload">
                                        <input type="file" class="file-field" name="fichier1">
                                    </span>
                                </span>
                            </div>
                            <i style="display:inline-block; color: #b20066;font-size:10px;font-family:Arial, Helvetica, sans-serif;">
                                <?= $this->lng['etape2']['contenu-fichier'] ?>
                            </i>
                        </div>
                        <div class="uploader" data-file="1" style="width:460px; float:left; margin-left: 16px;">
                            <span style="display:block;"><?= $this->lng['etape2']['autre'] ?></span>
                            <input type="text" class="field" readonly="readonly">
                            <div class="file-holder">
                                <span class="btn btn-small" style="line-height:40px; padding: 0 15px; margin-top: 3px;">
                                    <?= $this->lng['etape2']['parcourir'] ?><span class="file-upload">
                                        <input type="file" class="file-field" name="fichier2">
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div><!-- /.row -->

                    <div class="row">
                        <label for="radio1-3-about"><?= $this->lng['etape2']['label-toutes-informations-utiles'] ?></label>
                        <textarea name="comments" cols="30" rows="10" title="<?= $this->lng['etape2']['toutes-informations-utiles'] ?>" id="comments" class="field field-mega"><?= ($this->projects->comments != '' ? $this->projects->comments : $this->lng['etape2']['toutes-informations-utiles']) ?></textarea>
                    </div><!-- /.row -->

                    <div class="row">
                        <div class="cb-holder">
                            <label class="check" for="accept-cgu"><?= $this->lng['etape2']['je-reconnais-avoir-pris-connaissance'] ?> <a style="color:#A1A5A7; text-decoration: underline;" class="check" target="_blank" href="<?= $this->lurl . '/' . $this->tree->getSlug($this->lienConditionsGenerales, $this->language) ?>"><?= $this->lng['etape2']['des-conditions-generales-de-vente'] ?></a></label>
                            <input type="checkbox" class="custom-input required" name="accept-cgu" id="accept-cgu">
                        </div><!-- /.cb-holder -->
                    </div><!-- /.row -->

                    <span class="form-caption"><?= $this->lng['etape2']['champs-obligatoires'] ?></span>
                    <div class="form-foot row row-cols centered">
                        <input type="hidden" name="send_form_depot_dossier" />
                        <button class="btn" type="submit"><?= $this->lng['etape2']['deposer-son-dossier'] ?><i class="icon-arrow-next"></i></button>
                    </div><!-- /.form-foot foot-cols -->
                </form>
            </div><!-- /.register-form -->
            <?
        }
        ?>
    </div><!-- /.shell -->
</div>

<style type="text/css">
    .file-upload { overflow:visible; }
    .uploader { overflow:hidden; }
</style>
<script type="text/javascript">
<?
if ($this->error_email_representative_exist == true)
{
    ?>
        $("#email_representative").addClass('LV_invalid_field');
        $("#email_representative").removeClass('LV_valid_field');
    <?
}
elseif ($this->error_email_exist == true)
{
    ?>
        $("#email").addClass('LV_invalid_field');
        $("#email").removeClass('LV_valid_field');
    <?
}
?>

    $(document).ready(function () {
        $('#conf_email_representative').bind('paste', function (e) {
            e.preventDefault();
        });
        $('#conf_email').bind('paste', function (e) {
            e.preventDefault();
        });
    });

    $('select#autre').on('change', function () {
        if ($('option:selected', this).val() == '3') {
            $('#autre-preciser').show();
        }
        else {
            $('#autre-preciser').hide();
        }
        ;
    });
    
    $('input.file-field').on('change', function () {
        var $self = $(this),
                val = $self.val()

        if (val.length != 0 || val != '') {
            $self.closest('.uploader').find('input.field').val(val);
        }
        ;
    });

// form depot de dissuer
    $("#form_depot_dossier").submit(function (event) {
        var radio = true;

        if ($('input[type=radio][name=radio1-about]:checked').attr('value') == '3') {
            if ($('input[type=radio][name=sex]:checked').length) {
                $('.radio_sex').css('color', '#727272');
            }
            else {
                $('.radio_sex').css('color', '#C84747');
                radio = false
            }
        }
        else {
            $('.radio_sex').css('color', '#727272');
        }

        if ($('#accept-cgu').is(':checked') == false) {
            $('.check').css('color', '#C84747');
            radio = false
        }
        else {
            $('.check').css('color', '#727272');
        }
        
        if (radio == false) {
            event.preventDefault();
        }
    });
</script>

<?
if ($this->Config['env'] == 'prod')
{
    // TAG Unilend -Ligatus
    ?>
    <img src="https://ext.ligatus.com/conversion/?c=65835&a=7195" width="1" height="1" /><?
}
?>