<style type="text/css">
    .uploader input.field { width: 280px;}
</style>

<div class="main">
    <div class="shell">

        <p>Ici viendra le message dédié pour les "prospects"</p>

        <?
        if(isset($this->params[0]) && $this->params[0] == 'nok'){
            echo $this->lng['etape1']['contenu-non-eligible'];
        }
        elseif(isset($_SESSION['error_pre_empr'])){
            echo $_SESSION['error_pre_empr'];
            unset($_SESSION['error_pre_empr']);
        }
        else{

            if($this->error_email_representative_exist == true || $this->error_email_exist == true){
                ?><p style="color:#c84747;text-align:center;"><?=$this->lng['etape2']['erreur-email']?></p><?
            }
            ?>

            <div class="register-form">
                <form action="" method="post" id="send_form_prospect" name="send_form_prospect" enctype="multipart/form-data">
                    <div class="row">
                        <p><?=$this->lng['etape1']['identite-de-la-societe']?></p>

                        <input type="text" name="raison-sociale" id="raison-sociale"
                               title="<?=$this->lng['etape2']['raison-sociale']?>"
                               value="<?=($this->companies->name != ''?$this->companies->name:$this->lng['etape2']['raison-sociale'])?>"
                               class="field field-large required" data-validators="Presence">

                    </div><!-- /.row raison sociale-->

                    <!--					question si on est gerant ou pas de l'entreprise: -->
                    <div class="row">
                        <div class="form-choose fixed">

                            <div class="radio-holder">
                                <label style ="width: 192px;" for="radio1-1-about"><?=$this->lng['etape2']['dirigeant-entreprise']?></label>
                                <input <?=(isset($_POST['send_form_depot_dossier'])?($this->companies->status_client == 1?'checked':''):'checked')?>
                                    type="radio" class="custom-input" name="gerant" id="radio1-1-about" value="1">
                            </div><!-- /.radio-holder -->

                            <div class="radio-holder">
                                <label style ="width: 192px;" for="radio1-3-about"><?=$this->lng['etape2']['conseil-externe-entreprise']?></label>
                                <input <?=($this->companies->status_client == 3?'checked':'')?>
                                    type="radio" class="custom-input" name="gerant" id="radio1-3-about" value="3" data-condition="show:.identification">
                            </div><!-- /.radio-holder -->
                        </div><!-- /.form-choose -->
                    </div><!-- /.row -->

                    <p><?=$this->lng['etape2']['vos-coordonnees']?></p>
                    <div class="about-sections">

                        <div class="about-section">

                        </div><!-- /.about-section -->

                        <div class="about-section identification">

                            <div class="row" >

                                <div class="form-choose fixed radio_sex_prescripteur">
                                    <span class="title"><?=$this->lng['etape2']['civilite']?></span>
                                    <div class="radio-holder">
                                        <label for="female_prescripteur"><?=$this->lng['etape2']['madame']?></label>
                                        <input type="radio" class="custom-input" name="sex_prescripteur" id="female_prescripteur"  value="Mme" <?=($this->prescripteur->civilite=='Mme'?'checked="checked"':'')?>>
                                    </div><!-- /.radio-holder -->

                                    <div class="radio-holder">
                                        <label for="male_prescripteur"><?=$this->lng['etape2']['monsieur']?></label>
                                        <input type="radio" class="custom-input" name="sex_prescripteur" id="male_prescripteur"  value="M." <?=($this->prescripteur->civilite=='M.'?'checked="checked"':'')?>>
                                    </div><!-- /.radio-holder -->
                                </div><!-- /.form-choose -->
                            </div><!-- /.row -->

                            <div class="row">
                                <input type="text" name="prescripteur_nom-famille" id="prescripteur_nom-famille"
                                       title="<?=$this->lng['etape2']['nom']?>"
                                       value="<?=($this->prescripteur->nom!=''?$this->prescripteur->nom:$this->lng['etape2']['nom'])?>"
                                       class="field field-large required" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">

                                <input type="text" name="prescripteur_prenom" id="prescripteur_prenom"
                                       title="<?=$this->lng['etape2']['prenom']?>"
                                       value="<?=($this->prescripteur->prenom!=''?$this->prescripteur->prenom:$this->lng['etape2']['prenom'])?>"
                                       class="field field-large required" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}" >
                            </div><!-- /.row -->

                            <div class="row">
                                <input type="text" name="prescripteur_email" id="prescripteur_email"
                                       title="<?=$this->lng['etape2']['email']?>"
                                       value="<?=($this->prescripteur->email!=''?$this->prescripteur->email:$this->lng['etape2']['email'])?>"
                                       class="field field-large required" data-validators="Presence&amp;Email" onkeyup="checkConf(this.value,'conf_email')" >

                                <input type="text" name="prescripteur_conf_email" id="prescripteur_conf_email"
                                       title="<?=$this->lng['etape2']['confirmation-email']?>"
                                       value="<?=($this->conf_email!=''?$this->conf_email:$this->lng['etape2']['confirmation-email'])?>"
                                       class="field field-large required" data-validators="Confirmation,{ match: 'email' }" >
                            </div><!-- /.row -->

                            <div class="row">
                                <input type="text" name="prescripteur_phone" id="prescripteur_phone"
                                       value="<?=($this->prescripteur->telephone!=''?$this->prescripteur->telephone:$this->lng['etape2']['telephone'])?>"
                                       title="<?=$this->lng['etape2']['telephone']?>"
                                       class="field field-large required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9, maximum: 14}">

                            </div><!-- /.row -->
                            <p><?=$this->lng['etape2']['identite-du-representant-de-la-societe']?></p>

                        </div><!-- /.about-section -->
                    </div><!-- /.about-sections -->

                    <!--	coordonnées du gérant-->
                    <div class="row">
                        <div class="row" >
                            <div class="form-choose fixed radio_sex_representative">
                                <span class="title"><?=$this->lng['etape2']['civilite']?></span>
                                <div class="radio-holder">
                                    <label for="female_representative"><?=$this->lng['etape2']['madame']?></label>
                                    <input type="radio" class="custom-input" name="sex_representative" id="female_representative"  value="Mme" <?=($this->clients->civilite=='Mme'?'checked="checked"':'')?>>
                                </div><!-- /.radio-holder  -->

                                <div class="radio-holder">
                                    <label for="male_representative"><?=$this->lng['etape2']['monsieur']?></label>
                                    <input type="radio" class="custom-input" name="sex_representative" id="male_representative"  value="M." <?=($this->clients->civilite=='M.'?'checked="checked"':'')?>>
                                </div><!-- /.radio-holder -->
                            </div><!-- /.form-choose -->
                        </div><!-- /.row -->

                        <div class="row">
                            <input type="text" name="nom_representative" id="nom_representative"
                                   title="<?=$this->lng['etape2']['nom']?>"
                                   value="<?=($this->clients->nom!=''?$this->clients->nom:$this->lng['etape2']['nom'])?>"
                                   class="field field-large required" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">

                            <input type="text" name="prenom_representative" id="prenom_representative"
                                   title="<?=$this->lng['etape2']['prenom']?>"
                                   value="<?=($this->clients->prenom!=''?$this->clients->prenom:$this->lng['etape2']['prenom'])?>"
                                   class="field field-large required" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">
                        </div><!-- /.row -->

                        <div class="row">
                            <input type=<?=($this->clients->email!= ''?("hidden"):("text"))?> name="email_representative" id="email_representative"
                                   title="<?=$this->lng['etape2']['email']?>"
                                   value="<?=($this->clients->email!=''?$this->clients->email:$this->lng['etape2']['email'])?>"
                                   class="field field-large required" data-validators="Presence&amp;Email" onkeyup="checkConf(this.value,'conf_email_representative')">

                            <input type=<?=($this->clients->email!= ''?("hidden"):("text"))?> name="conf_email_representative" title="Confirmation Email*" id="conf_email_representative"
                                   value="<?=($this->clients->email != ''?$this->clients->email:'Confirmation Email*')?>"
                                   class="field field-large required" data-validators="Confirmation, { match: 'email_representative' }" >
                        </div><!-- /.row -->


                        <div class="row">

                            <input type="text" name="portable_representative" id="portable_representative"
                                   title="<?=$this->lng['etape2']['telephone']?>"
                                   value="<?=($this->clients->mobile!=''?$this->clients->mobile:$this->lng['etape2']['telephone'])?>"
                                   class="field field-large required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9, maximum: 14}">

                            <input type="text" name="fonction_representative" id="fonction_representative"
                                   title="<?=$this->lng['etape2']['fonction']?>"
                                   value="<?=($this->clients->fonction!=''?$this->clients->fonction:$this->lng['etape2']['fonction'])?>"
                                   class="field field-large required" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">

                        </div><!-- /.row -->

                        <span class="form-caption"><?=$this->lng['etape2']['champs-obligatoires']?></span>
                        <div class="form-foot row row-cols centered">
                            <input type="hidden" name="send_form_depot_dossier" />
                            <button class="btn" type="submit"><?=$this->lng['prospect']['enregistrer-coordonnees']?></button>
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
    if($this->error_email_representative_exist == true){
        ?>
    $("#email_representative").addClass('LV_invalid_field');
    $("#email_representative").removeClass('LV_valid_field');
    <?
}
elseif($this->error_email_exist == true){
    ?>
    $("#prescripteur_email").addClass('LV_invalid_field');
    $("#prescripteur_email").removeClass('LV_valid_field');
    <?
}
?>

    $(document).ready(function () {
        $('#conf_email_representative').bind('paste', function (e) { e.preventDefault(); });
        $('#conf_prescripteur_email').bind('paste', function (e) { e.preventDefault(); });
    });

    $('select#autre').on('change', function() {
        if ($('option:selected', this).val() == '3') { $('#autre-preciser').show(); }
        else { $('#autre-preciser').hide(); };
    });

    $('input.file-field').on('change', function(){
        var $self = $(this),
            val = $self.val()

        if( val.length != 0 || val != '' ){
            $self.closest('.uploader').find('input.field').val(val);
        };
    });

    // form depot de dissuer
    $( "#form_depot_dossier" ).submit(function( event ){
        var radio = true;

        if($('input[type=radio][name=radio1-about]:checked').attr('value') == '3'){
            if($('input[type=radio][name=sex]:checked').length){$('.radio_sex').css('color','#727272');}
            else{$('.radio_sex').css('color','#C84747');radio = false}
        }
        else{$('.radio_sex').css('color','#727272');}

        if($('#accept-cgu').is(':checked') == false){$('.check').css('color','#C84747');radio = false}
        else{$('.check').css('color','#727272');}

        if(radio == false){event.preventDefault();}
    });
</script>

<?
if($this->Config['env'] == 'prod'){
    // TAG Unilend -Ligatus
    ?>
    <img src="https://ext.ligatus.com/conversion/?c=65835&a=7195" width="1" height="1" /><?
}
?>