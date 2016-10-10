
<?php
//Ajout CM 06/08/14
$dateDepartControlPays = strtotime('2014-07-31 18:00:00');

// on ajoute une petite restriction de date pour rendre certains champs obligatoires
if(strtotime($this->clients->added) >= $dateDepartControlPays)
{
    $required = 'required';
} else {
    $required = '';
}

?>
<div class="account-data">
    <h2><?= $this->lng['profile']['titre-1'] ?></h2>

    <?php if (isset($_SESSION['reponse_profile_perso']) && $_SESSION['reponse_profile_perso'] != '') :  ?>
        <div class="reponseProfile"><?= $_SESSION['reponse_profile_perso'] ?></div>
        <?php unset($_SESSION['reponse_profile_perso']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['reponse_email']) && $_SESSION['reponse_email'] != '') : ?>
        <div class="reponseProfile" style="color:#c84747;"><?= $_SESSION['reponse_email'] ?></div>
        <?php unset($_SESSION['reponse_email']); ?>
    <?php endif; ?>

    <p><?= $this->lng['profile']['contenu-partie-1'] ?></p>
    <form action="<?= $this->lurl ?>/profile/particulier/3" method="post" name="form_particulier_perso" id="form_particulier_perso" enctype="multipart/form-data">

            <input type="text" name="nom-dusage" id="nom-dusage" title="<?= $this->lng['etape1']['nom-dusage'] ?>" value="<?= ($this->clients->nom_usage != '' ? $this->clients->nom_usage : $this->lng['etape1']['nom-dusage']) ?>" class="field field-large " data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">
        </div><!-- /.row -->



        <div class="row">
            <span class="pass-field-holder">
                <input type="text" name="email" id="email" title="<?= $this->lng['etape1']['email'] ?>" value="<?= ($this->clients->email != '' ? $this->clients->email : $this->lng['etape1']['email']) ?>" class="field field-large required" data-validators="Presence&amp;Email&amp;Format,{ pattern:/^((?!@yopmail.com).)*$/}" placeholder="<?= $this->lng['etape1']['email'] ?>" onkeyup="checkConf(this, this.value,'conf_email')">
                <em><?= $this->lng['etape1']['info-email'] ?></em>
            </span>

            <span class="pass-field-holder">
                <input type="text" name="conf_email" id="conf_email" title="<?= $this->lng['etape1']['confirmation-email'] ?>" value="<?= ($this->clients->email != '' ? $this->clients->email : $this->lng['etape1']['confirmation-email']) ?>" class="field field-large required" data-validators="Confirmation,{ match: 'email' }&amp;Format,{ pattern:/^((?!@yopmail.com).)*$/}" placeholder="<?= $this->lng['etape1']['confirmation-email'] ?>" onkeyup="checkConf(this, this.value,'email')">
            </span>
        </div><!-- /.row -->

        <div class="row row-alt">
            <span class="inline-text inline-text-alt"><?= $this->lng['etape1']['telephone'] ?> :</span>
            <input type="text" name="phone" id="phone" value="<?= ($this->clients->telephone != '' ? $this->clients->telephone : $this->lng['etape1']['telephone']) ?>" title="<?= $this->lng['etape1']['telephone'] ?>" class="field field-small required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9,maximum: 14}">
            <span class="inline-text inline-text-alt" style="width:121px;"><?= $this->lng['etape1']['nationalite'] ?>
                :</span>
            <select name="nationalite" id="nationalite" class="custom-select <?=$required?> field-small">
                <option><?= $this->lng['etape1']['nationalite'] ?></option>
                <option><?= $this->lng['etape1']['nationalite'] ?></option>
                <?php foreach ($this->lNatio as $p) : ?>
                    <option <?= ($this->clients->id_nationalite == $p['id_nationalite'] ? 'selected' : '') ?> value="<?= $p['id_nationalite'] ?>"><?= $p['fr_f'] ?></option>
                <?php endforeach; ?>
            </select>
        </div><!-- /.row -->

            <select name="pays3" id="pays3" class="country custom-select <?=$required?> field-small">
                <option value=""><?=$this->lng['etape1']['pays-de-naissance']?></option>
                <option value=""><?=$this->lng['etape1']['pays-de-naissance']?></option>
                <?
                foreach($this->lPays as $p){
                    ?><option <?=($this->clients->id_pays_naissance == $p['id_pays']?'selected':'')?> value="<?=$p['id_pays']?>"><?=$p['fr']?></option><?
                }
                ?>
            </select>
        </div>
        <div class="row row-upload etranger1" <?= ($this->etranger == 1 ? '' : 'style="display:none;"') ?>>
            <label class="inline-text">
                <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?= $this->lng['etape2']['document-fiscal-1'] ?>"></i>
                <?= $this->lng['etape2']['document-fiscal-1'] ?>
            </label>
            <div class="uploader">
                <input id="text_document_fiscal_1" type="text" class="field" readonly value="<?= empty($this->attachments[\attachment_type::JUSTIFICATIF_FISCAL]['path']) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[\attachment_type::JUSTIFICATIF_FISCAL]['path'] ?>">
                <div class="file-holder">
                    <span class="btn btn-small">
                        +
                        <span class="file-upload">
                            <input type="file" class="file-field" name="document_fiscal" >
                        </span>
                        <small><?= $this->lng['profile']['telecharger-un-autre-document-fiscal'] ?></small>
                    </span>
                </div>
            </div><!-- /.uploader -->
        </div>
        <div class="row row-upload etranger2" <?= ($this->etranger == 2 ? '' : 'style="display:none;"') ?>>
            <label class="inline-text">
                <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?= $this->lng['etape2']['document-fiscal-2'] ?>"></i>
                <?= $this->lng['etape2']['document-fiscal-2'] ?>
            </label>
            <div class="uploader">
                <input id="text_document_fiscal_2" type="text" class="field" readonly value="<?= empty($this->attachments[\attachment_type::JUSTIFICATIF_FISCAL]['path']) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[\attachment_type::JUSTIFICATIF_FISCAL]['path'] ?>">
                <div class="file-holder">
                    <span class="btn btn-small">
                        +
                        <span class="file-upload">
                            <input type="file" class="file-field" name="document_fiscal">
                        </span>
                        <small><?= $this->lng['profile']['telecharger-un-autre-document-fiscal'] ?></small>
                    </span>
                </div>
            </div><!-- /.uploader -->
        </div>
        <div class="row row-upload identite">
            <div class="row"> <!--row CNI -->
                <label class="inline-text">
                    <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?= $this->lng['etape2']['info-cni'] ?>"></i>
                    <?= $this->lng['etape2']['piece-didentite'] ?>
                </label>
                <div class="uploader">
                    <input id="text_ci" type="text" class="field"
                           readonly value="<?= empty($this->attachments[\attachment_type::CNI_PASSPORTE]['path']) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[\attachment_type::CNI_PASSPORTE]['path'] ?>">
                    <div class="file-holder">
                    <span class="btn btn-small">
                        <?php if (empty($this->attachments[\attachment_type::CNI_PASSPORTE]['path'])) : ?>
                            +
                        <?php else : ?>
                            &hArr;
                        <?php endif; ?>
                        <span class="file-upload">
                            <input type="file" class="file-field" name="cni_passeport" id="file-ci">
                        </span>
                        <small><?= $this->lng['profile']['telecharger-un-autre-document-didentite'] ?></small>
                    </span>
                    </div>
                </div><!-- /.uploader -->
            </div><!--row CNI -->
            <div class="row"><!--row CNI Verso-->
                <label class="inline-text">
                    <?= $this->lng['etape2']['piece-didentite-verso'] ?>
                </label>
                <div class="uploader">
                    <input id="text_ci_verso" type="text" class="field"
                           readonly value="<?= empty($this->attachments[\attachment_type::CNI_PASSPORTE_VERSO]['path']) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[\attachment_type::CNI_PASSPORTE_VERSO]['path'] ?>">
                    <div class="file-holder">
                    <span class="btn btn-small">
                        +
                        <span class="file-upload">
                            <input type="file" class="file-field" name="cni_passeport_verso" id="file-ci-verso">
                        </span>
                        <small><?= $this->lng['profile']['telecharger-un-autre-document-didentite'] ?></small>
                    </span>
                    </div>
                </div><!-- /.uploader -->
            </div><!--row CNI verso -->
        </div>
        <div class="les_deux">
            <p><?= $this->lng['etape1']['adresse-fiscale'] ?>
                <i class="icon-help tooltip-anchor" data-placement="right" title="<?= $this->lng['etape1']['info-adresse-fiscale'] ?>"></i>
            </p>
            <div class="row">
                <input type="text" id="adresse_inscription" name="adresse_inscription" title="<?= $this->lng['etape1']['adresse'] ?>" value="<?= ($this->clients_adresses->adresse_fiscal != '' ? $this->clients_adresses->adresse_fiscal : $this->lng['etape1']['adresse']) ?>" class="field field-mega required" data-validators="Presence">
            </div><!-- /.row -->
            <div class="row row-triple-fields">
                <input type="text" id="postal" name="postal" class="field field-small required" data-autocomplete="post_code"
                       placeholder="<?= $this->lng['etape1']['code-postal'] ?>" title="<?= $this->lng['etape1']['code-postal'] ?>" value="<?= $this->clients_adresses->cp_fiscal ?>"/>
                <input type="text" id="ville_inscription" name="ville_inscription" class="field field-small required" data-autocomplete="city"
                       placeholder="<?=$this->lng['etape1']['ville']?>" title="<?=$this->lng['etape1']['ville']?>" value="<?=$this->clients_adresses->ville_fiscal?>"/>

                <?php //Ajout CM 06/08/14 ?>
                <select name="pays1" id="pays1" class="country custom-select <?=$required?> field-small">
                    <option><?=$this->lng['etape1']['pays']?></option>
                    <option><?=$this->lng['etape1']['pays']?></option>
                    <?
                    foreach($this->lPays as $p)
                    {
                        ?><option <?=($this->clients_adresses->id_pays == $p['id_pays']?'selected':'')?> value="<?=$p['id_pays']?>"><?=$p['fr']?></option><?
                    }
                    ?>
                </select>
                <em class="change_addr_fiscale"><?= $this->lng['profile']['les-informations-relatives-a-votre-adresse-fiscale-ont-ete-modifiees'] ?></em>
            </div><!-- /.row -->
            <div class="row row-upload domicile">
                <div class="row"><!-- row Justificatif -->
                    <label class="inline-text">
                        <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?= $this->lng['etape2']['info-justificatif-de-domicile'] ?>"></i>
                        <?= $this->lng['etape2']['justificatif-de-domicile'] ?>
                    </label>
                    <div class="uploader"><!-- début uploader -->
                        <input id="text_just_dom" type="text" class="field" readonly value="<?= empty($this->attachments[\attachment_type::JUSTIFICATIF_DOMICILE]["path"]) ?  $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[\attachment_type::JUSTIFICATIF_DOMICILE]["path"] ?>">
                        <div class="file-holder">
                        <span class="btn btn-small">
                            <?= empty($this->attachments[\attachment_type::JUSTIFICATIF_DOMICILE]["path"]) ? '+' : '&hArr;' ?>
                            <span class="file-upload">
                                <input type="file" class="file-field" name="justificatif_domicile" id="file_just_dom">
                            </span>
                            <small><?= $this->lng['profile']['telecharger-un-autre-document-justificatif-de-domicile'] ?></small>
                        </span>
                        </div><!-- end fileholder -->
                    </div><!-- end uploader -->
                </div><!-- end row Justificatif -->
                <div class="row"><!-- début row ATTESTATION_HEBERGEMENT_TIERS -->
                    <label class="inline-text">
                        <?= $this->lng['etape2']['attestation-hebergement'] ?>
                    </label>
                    <div class="uploader"><!-- début uploader -->
                        <input id="text_att_herb_tiers" type="text" class="field" readonly value="<?= empty($this->attachments[\attachment_type::ATTESTATION_HEBERGEMENT_TIERS]['path']) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[\attachment_type::ATTESTATION_HEBERGEMENT_TIERS]['path'] ?>">
                        <div class="file-holder">
                        <span class="btn btn-small">
                        <?= empty($this->attachments[\attachment_type::ATTESTATION_HEBERGEMENT_TIERS]['path']) ? '+' : '&hArr;' ?>
                            <span class="file-upload">
                                <input type="file" class="file-field" name="attestation_hebergement_tiers" id="file_att_herb_tiers">
                            </span>
                            <small><?= $this->lng['profile']['ajouter'] ?></small>
                        </span>
                        </div><!-- end file holder -->
                    </div><!-- end row uploader -->
                </div><!-- end row ATTESTATION_HEBERGEMENT_TIERS -->
                <div class="row"><!-- row CNI_PASSPORT_TIERS_HEBERGEANT -->
                    <label class="inline-text">
                        <?= $this->lng['profile']['cni-tiers-hebergeant'] ?>
                    </label>
                    <div class="uploader"><!-- début uploader-->
                        <input id="text_cni_tiers_herb" type="text" class="field" readonly value="<?= empty($this->attachments[\attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT]['path']) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[\attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT]['path'] ?>">
                        <div class="file-holder">
                        <span class="btn btn-small">
                            +
                            <span class="file-upload">
                                <input type="file" class="file-field" name="cni_passport_tiers_hebergeant" id="file_cni_tiers_herb">
                            </span>
                            <small><?= $this->lng['profile']['ajouter'] ?></small>
                        </span>
                        </div><!-- end file holder -->
                    </div><!-- end  uploader -->
                </div><!-- end row CNI_PASSPORT_TIERS_HEBERGEANT -->
                <div class="row"><!-- row AUTRE1 -->
                    <label class="inline-text">
                        <?= $this->lng['profile']['autre-fichier'] ?>
                    </label>
                    <div class="uploader">
                        <input id="text_autre1" type="text" class="field" readonly value="<?= empty($this->attachments[\attachment_type::AUTRE1]['path']) ? $this->lng['etape2']['aucun-fichier-selectionne'] : $this->attachments[\attachment_type::AUTRE1]['path'] ?>">
                        <div class="file-holder">
                        <span class="btn btn-small">
                            +
                            <span class="file-upload">
                                <input type="file" class="file-field" name="autre1" id="file_autre1">
                            </span>
                            <small><?= $this->lng['profile']['ajouter'] ?></small>
                        </span>
                        </div><!-- enf file holder -->
                    </div><!-- end uploader -->
                </div><!-- end row AUTRE1 -->
            </div><!-- end row uploader -->
        </div>
        <div class="row">
            <div class="cb-holder">
                <label for="mon-addresse"><?= $this->lng['etape1']['meme-adresse'] ?></label>
                <input <?= ($this->clients_adresses->meme_adresse_fiscal == 0 ? '' : 'checked="checked"') ?> type="checkbox" class="custom-input" name="mon-addresse" id="mon-addresse" data-condition="hide:.add-address">
            </div><!-- /.cb-holder -->
        </div><!-- /.row -->
        <div class="add-address">
            <p><?= $this->lng['etape1']['adresse-de-correspondance'] ?></p>
            <div class="row">
                <input type="text" id="address2" name="adress2" title="<?= $this->lng['etape1']['adresse'] ?>" value="<?= ($this->clients_adresses->adresse1 != '' ? $this->clients_adresses->adresse1 : $this->lng['etape1']['adresse']) ?>" class="field field-mega required" data-validators="Presence">
            </div><!-- /.row -->
            <div class="row row-triple-fields">
                <input type="text" id="postal2" name="postal2" class="field field-small required" data-autocomplete="post_code"
                       placeholder="<?= $this->lng['etape1']['code-postal'] ?>" value="<?= $this->clients_adresses->cp ?>" title="<?= $this->lng['etape1']['code-postal'] ?>"/>
                <input type="text" id="ville2" name="ville2" class="field field-small required" data-autocomplete="city"
                       placeholder="<?=$this->lng['etape1']['ville']?>" title="<?=$this->lng['etape1']['ville']?>" value="<?=$this->clients_adresses->ville?>" />
                <?php //Ajout CM 06/08/14 ?>
                <select name="pays2" id="pays2" class="country custom-select <?=$required?> field-small">
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
        </div><!-- /.add-address -->

        <span class="form-caption"><?= $this->lng['etape1']['champs-obligatoires'] ?></span>

        <div class="form-foot row row-cols centered">
            <input type="hidden" name="send_form_particulier_perso">
            <button class="btn" type="button" onclick='$( "#form_particulier_perso" ).submit();'><?= $this->lng['etape1']['valider'] ?>
                <i class="icon-arrow-next"></i></button>
        </div><!-- /.form-foot foot-cols -->

        <script type="text/javascript">

            /////////////////////
            // change_identite //
            /////////////////////

            var change_file_identite = false;
            var change_txt_identite = false;

            // nom usage
            $("#nom-dusage").change(function () {
                if ($(this).val() != "<?=($this->clients->nom_usage != '' ? $this->clients->nom_usage : $this->lng['etape1']['nom-dusage'])?>" && change_file_identite == false) {
                    $("#text_ci").val('');
                    $(".change_identite").fadeIn();
                    change_txt_identite = true;
                }
            });

            /////////////////////////
            // change_addr_fiscale //
            /////////////////////////

            var change_addr_fiscale_file = false;
            var change_addr_fiscale_txt = false;


            // rue, ville, cp, pays
            $("#adresse_inscription,#ville_inscription,#postal,#pays1").change(function () {
                if ($('#adresse_inscription').val() != "<?=$this->clients_adresses->adresse_fiscal?>" && change_addr_fiscale_file == false) {
                    $("#text_just_dom").val('');
                    $(".change_addr_fiscale").fadeIn();
                    change_addr_fiscale_txt = true;
                }
                if ($('#ville_inscription').val() != "<?=$this->clients_adresses->ville_fiscal?>" && change_addr_fiscale_file == false) {
                    $("#text_just_dom").val('');
                    $(".change_addr_fiscale").fadeIn();
                    change_addr_fiscale_txt = true;
                }
                if ($('#postal').val() != "<?=$this->clients_adresses->cp_fiscal?>" && change_addr_fiscale_file == false) {
                    $("#text_just_dom").val('');
                    $(".change_addr_fiscale").fadeIn();
                    change_addr_fiscale_txt = true;
                }
                if ($('#pays1').val() != "<?=$this->clients_adresses->id_pays?>" && change_addr_fiscale_file == false) {
                    $("#text_just_dom").val('');
                    $(".change_addr_fiscale").fadeIn();
                    change_addr_fiscale_txt = true;
                }
            });

            $("#file_just_dom").change(function () {
                if (change_addr_fiscale_txt == false) {
                    $("#adresse_inscription,#ville_inscription,#postal,#pays1").val('');
                    $(".change_addr_fiscale").fadeIn();
                    change_addr_fiscale_file = true;
                }
            });

            $(document).ready(function () {

                // confirmation email preteur particulier
                $('#conf_email').bind('paste', function (e) {
                    e.preventDefault();
                });
                $('#email').bind('paste', function (e) {
                    e.preventDefault();
                });

                ////////////////////////////////////////////
                $("#jour_naissance").change(function () {
                    var d = $('#jour_naissance').val();
                    var m = $('#mois_naissance').val();
                    var y = $('#annee_naissance').val();

                    $.post(add_url + "/ajax/controleAge", {d: d, m: m, y: y}).done(function (data) {
                        if (data == 'ok') {
                            $(".check_age").html('true');
                            $(".error_age").slideUp();
                        }
                        else {
                            radio = false;
                            $(".check_age").html('false');
                            $(".error_age").slideDown();
                        }
                    });
                });

                $("#mois_naissance").change(function () {
                    var d = $('#jour_naissance').val();
                    var m = $('#mois_naissance').val();
                    var y = $('#annee_naissance').val();

                    $.post(add_url + "/ajax/controleAge", {d: d, m: m, y: y}).done(function (data) {
                        if (data == 'ok') {
                            $(".check_age").html('true');
                            $(".error_age").slideUp();

                        }
                        else {
                            radio = false;
                            $(".check_age").html('false');
                            $(".error_age").slideDown();
                        }
                    });
                });

                $("#annee_naissance").change(function () {
                    var d = $('#jour_naissance').val();
                    var m = $('#mois_naissance').val();
                    var y = $('#annee_naissance').val();

                    $.post(add_url + "/ajax/controleAge", {d: d, m: m, y: y}).done(function (data) {
                        if (data == 'ok') {
                            $(".check_age").html('true');
                            $(".error_age").slideUp();
                        }
                        else {
                            radio = false;
                            $(".check_age").html('false');
                            $(".error_age").slideDown();
                        }
                    });
                });

        // particulier etranger
        $("#pays1,#nationalite").change(function() {
            var pays1 = $('#pays1').val();

            //resident etranger
            if(pays1 > 1){
                $(".etranger").slideDown();
            } else {
                $(".etranger").slideUp();
            }
        });

        // particulier messagge check_etranger
        $("#check_etranger").change(function() {
            if($(this).is(':checked') == true){
                $(".message_check_etranger").slideUp();
                $("#text_document_fiscal").val('');
            }
            else{$(".message_check_etranger").slideDown();}
        });

                //CInput2.init();
                initAutocompleteCity();
            });


            // perso
            $("#form_particulier_perso").submit(function (event) {
                var form_ok = true;
                var text_ci = $("#text_ci");
                var text_just_dom = $("#text_just_dom");

                if ($(".check_age").html() == 'false') {
                    form_ok = false;
                }

                // controle cp
                if (controlePostCodeCity($('#postal'), $('#ville_inscription'), $('#pays1'), false) == false) {
                    form_ok = false
                }

                if ($('#mon-addresse').is(':checked') == false) {
                    // controle cp
                    if (controlePostCodeCity($('#postal2'), $('#ville2'), $('#pays2'), false) == false) {
                        form_ok = false
                    }
                }

                if ('' == $("#naissance").val() || ('' == $('#insee_birth').val() && 1 == $('#pays3').val()) || controleCity($('#naissance'), $('#pays3'), false) == false) {
                    $("#naissance").removeClass("LV_valid_field");
                    $("#naissance").addClass("LV_invalid_field");
                    form_ok = false;
                }

        //resident etranger
        var pays1 = $('#pays1').val();
        if(pays1 > 1){
            // check_etranger
            if($('#check_etranger').is(':checked') == false){$('.check_etranger').css('color','#C84747'); $('#check_etranger').addClass('LV_invalid_field');$('#check_etranger').removeClass('LV_valid_field');  form_ok = false; }
            else{ $('#check_etranger').addClass('LV_valid_field');$('#check_etranger').removeClass('LV_invalid_field'); $('.check_etranger').css('color','#727272');}

            var text_document_fiscal = $("#text_document_fiscal");

                    if (text_document_fiscal.val() == '' || text_document_fiscal.val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>') {
                        form_ok = false;
                        text_document_fiscal.addClass('LV_invalid_field');
                        text_document_fiscal.removeClass('LV_valid_field');
                    }
                    else {
                        text_document_fiscal.addClass('LV_valid_field');
                        text_document_fiscal.removeClass('LV_invalid_field');
                    }
                }

                // ci
                if (text_ci.val() == '') {
                    form_ok = false;
                    text_ci.addClass('LV_invalid_field');
                    text_ci.removeClass('LV_valid_field');
                }
                else {
                    text_ci.addClass('LV_valid_field');
                    text_ci.removeClass('LV_invalid_field');
                }
                // just domicile
                if (text_just_dom.val() == '') {
                    form_ok = false;
                    text_just_dom.addClass('LV_invalid_field');
                    text_just_dom.removeClass('LV_valid_field');
                }
                else {
                    text_just_dom.addClass('LV_valid_field');
                    text_just_dom.removeClass('LV_invalid_field');
                }

                if (form_ok == false) {
                    event.preventDefault();
                }
            });
        </script>
