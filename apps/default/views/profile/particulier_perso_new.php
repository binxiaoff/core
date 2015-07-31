
<?php /*?><style type="text/css">
.form-page .field-small{width:215px !important;}
</style><?php */?>

<?php
	//Ajout CM 06/08/14
	$dateDepartControlPays = strtotime('2014-07-31 18:00:00');
	
	// on ajoute une petite restriction de date pour rendre certains champs obligatoires
	if(strtotime($this->clients->added) >= $dateDepartControlPays)
	{
		$required = 'required';
	}

?>

<div class="account-data">
    <h2><?=$this->lng['profile']['titre-1']?></h2>
	
	<?
	if(isset($_SESSION['reponse_profile_perso']) && $_SESSION['reponse_profile_perso'] != ''){
		?><div class="reponseProfile"><?=$_SESSION['reponse_profile_perso']?></div><?
		unset($_SESSION['reponse_profile_perso']);
	}
	if(isset($_SESSION['reponse_email']) && $_SESSION['reponse_email'] != ''){
		?><div class="reponseProfile" style="color:#c84747;"><?=$_SESSION['reponse_email']?></div><?
		unset($_SESSION['reponse_email']);
	}
	?>
    
    <p><?=$this->lng['profile']['contenu-partie-1']?></p>
	
    <form action="<?=$this->lurl?>/profile/particulier/3" method="post" name="form_particulier_perso" id="form_particulier_perso" enctype="multipart/form-data">
        <div class="row" id="radio_sex">
            <div class="form-choose fixed">
                <span class="title"><?=$this->lng['etape1']['civilite']?></span>

                <div class="radio-holder validationRadio1">
                    <label for="female"><?=$this->lng['etape1']['madame']?></label>

                    <input <?=($this->clients->civilite=='Mme'?'checked="checked"':'')?> type="radio" class="custom-input" name="sex" id="female"  value="Mme" checked="checked">
                </div><!-- /.radio-holder -->

                <div class="radio-holder validationRadio2">
                    <label for="male"><?=$this->lng['etape1']['monsieur']?></label>

                    <input <?=($this->clients->civilite=='M.'?'checked="checked"':'')?> type="radio" class="custom-input" name="sex" id="male"  value="M.">
                </div><!-- /.radio-holder -->
            </div><!-- /.form-choose -->
        </div><!-- /.row -->

        <div class="row">
            <input type="text" name="nom-famille" id="nom-famille" title="<?=$this->lng['etape1']['nom-de-famille']?>" value="<?=($this->clients->nom!=''?$this->clients->nom:$this->lng['etape1']['nom-de-famille'])?>" class="field field-large required" data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}" >

            <input type="text" name="nom-dusage" id="nom-dusage" title="<?=$this->lng['etape1']['nom-dusage']?>" value="<?=($this->clients->nom_usage!=''?$this->clients->nom_usage:$this->lng['etape1']['nom-dusage'])?>" class="field field-large " data-validators="Presence&amp;Format,{  pattern:/^([^0-9]*)$/}">
        </div><!-- /.row -->

        <div class="row">
            <input type="text" name="prenom" id="prenom" title="<?=$this->lng['etape1']['prenom']?>" value="<?=($this->clients->prenom!=''?$this->clients->prenom:$this->lng['etape1']['prenom'])?>" class="field field-large required" data-validators="Presence">

            <em class="change_identite"><?=$this->lng['profile']['les-informations-relatives-a-votre-identite-ont-ete-modifiees']?></em>
        </div><!-- /.row -->
		
        <div class="row">
            <span class="pass-field-holder">
                <input type="text" name="email" id="email" title="<?=$this->lng['etape1']['email']?>" value="<?=($this->clients->email!=''?$this->clients->email:$this->lng['etape1']['email'])?>" class="field field-large required" data-validators="Presence&amp;Email&amp;Format,{ pattern:/^((?!@yopmail.com).)*$/}"  onkeyup="checkConf(this.value,'conf_email')" >
                <em><?=$this->lng['etape1']['info-email']?></em>
            </span>
            
            <span class="pass-field-holder">
                <input type="text" name="conf_email" id="conf_email" title="<?=$this->lng['etape1']['confirmation-email']?>" value="<?=($this->clients->email!=''?$this->clients->email:$this->lng['etape1']['confirmation-email'])?>" class="field field-large required" data-validators="Confirmation,{ match: 'email' }&amp;Format,{ pattern:/^((?!@yopmail.com).)*$/}" >
            </span>
        </div><!-- /.row -->
        
        <!--  -->
        
        <div class="row row-alt">
        	<span class="inline-text inline-text-alt"><?=$this->lng['etape1']['telephone']?> :</span>
        
            <input type="text" name="phone" id="phone" value="<?=($this->clients->telephone!=''?$this->clients->telephone:$this->lng['etape1']['telephone'])?>" title="<?=$this->lng['etape1']['telephone']?>" class="field field-small required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9,maximum: 14}">
            
            <span class="inline-text inline-text-alt" style="width:121px;"><?=$this->lng['etape1']['nationalite']?> :</span>
            
            <select name="nationalite" id="nationalite" class="custom-select <?=$required?> field-small">
                <option><?=$this->lng['etape1']['nationalite']?></option>
               	<option><?=$this->lng['etape1']['nationalite']?></option>
                <?
                foreach($this->lNatio as $p){
                    ?><option <?=($this->clients->id_nationalite == $p['id_nationalite']?'selected':'')?> value="<?=$p['id_nationalite']?>"><?=$p['fr_f']?></option><?	
                }
                ?>
            </select>
            
        </div><!-- /.row -->
        <div class="row etranger" <?=($this->etranger > 0?'':'style="display:none;"')?>>
            <div class="cb-holder">
                <label style="margin-left:524px;" class="check_etranger" for="check_etranger"><?=$this->lng['etape1']['checkbox-etranger']?></label>
                <input <?=($this->etranger > 0?'checked':'')?> type="checkbox" class="custom-input" name="check_etranger" id="check_etranger">
            </div><!-- /.cb-holder -->
            <p class="message_check_etranger" ><?=$this->lng['etape1']['checkbox-etranger-message']?></p>
        </div><!-- /.row -->
        
        <div class="row small-select">
            <span class="inline-text inline-text-alt"><?=$this->lng['etape1']['date-de-naissance']?> :</span>
            <select name="jour_naissance" id="jour_naissance" class="custom-select required field-tiny">
                <option><?=$this->lng['etape1']['jour']?></option>
                <option><?=$this->lng['etape1']['jour']?></option>
                <?
                for($i=1;$i<=31;$i++){
                    ?><option <?=($this->jour == $i?'selected':'')?> value="<?=$i?>"><?=$i?></option><?	
                }
                ?>
            </select>

            <select name="mois_naissance" id="mois_naissance" class="custom-select required field-tiny">
				<option ><?=$this->lng['etape1']['mois']?></option>
               	<option><?=$this->lng['etape1']['mois']?></option>
                <?
                foreach($this->dates->tableauMois['fr'] as $k => $mois)
                {
                    if($k > 0) echo '<option '.($this->mois == $k?"selected":"").' value="'.$k.'">'.$mois.'</option>';
                }
                ?>
            </select>

            <select name="annee_naissance" id="annee_naissance" class="custom-select required field-tiny">
                <option><?=$this->lng['etape1']['annee']?></option>
                <option><?=$this->lng['etape1']['annee']?></option>
                <?
                for($i=date('Y')-18;$i>=1910;$i--)
                {
                    echo '<option '.($this->annee == $i?"selected":"").' value="'.$i.'">'.$i.'</option>';
                }
                ?>
            </select>
            <div style="clear: both;"></div>
            <em class="error_age"><?=$this->lng['etape1']['erreur-age']?></em>
            <span class="check_age" style="display:none">true</span>
        </div>
        
        <div class="row row-triple-fields row-triple-fields-alt">
        	<span class="inline-text inline-text-alt inline-text-alt-small"><?=$this->lng['etape1']['commune-de-naissance']?> :</span>
            
            <input type="text" name="naissance" title="<?=$this->lng['etape1']['commune-de-naissance']?>" value="<?=($this->clients->ville_naissance!=''?$this->clients->ville_naissance:$this->lng['etape1']['commune-de-naissance'])?>" id="naissance" class="field field-small required" data-validators="Presence" data-autocomplete="cities">
            
            <span class="inline-text inline-text-alt inline-text-alt-small"><?=$this->lng['etape1']['pays-de-naissance']?> :</span>
            
            <select name="pays3" id="pays3" class="custom-select <?=$required?> field-small">
                <option value=""><?=$this->lng['etape1']['pays-de-naissance']?></option>
                <option value=""><?=$this->lng['etape1']['pays-de-naissance']?></option>
                <?
                foreach($this->lPays as $p){
                    ?><option <?=($this->clients->id_pays_naissance == $p['id_pays']?'selected':'')?> value="<?=$p['id_pays']?>"><?=$p['fr']?></option><?	
                }
                ?>
            </select>
        </div>
  

		<div class="row row-upload etranger1" <?=($this->etranger == 1?'':'style="display:none;"')?>>
			<label class="inline-text">
				<i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?=$this->lng['etape2']['document-fiscal-1']?>"></i>

				<?=$this->lng['etape2']['document-fiscal-1']?>
			</label>

			<div class="uploader">
				<input id="text_document_fiscal_1" type="text" class="field" readonly value="<?=($this->lenders_accounts->fichier_document_fiscal!=''?$this->lenders_accounts->fichier_document_fiscal:$this->lng['etape2']['aucun-fichier-selectionne'])?>">

				<div class="file-holder">
					<span class="btn btn-small">
						+
						<span class="file-upload">
							<input type="file" class="file-field" name="document_fiscal_1">
						</span>

						<small><?=$this->lng['profile']['telecharger-un-autre-document-fiscal']?></small>
					</span>
				</div>
			</div><!-- /.uploader -->
		</div>
        
        <div class="row row-upload etranger2" <?=($this->etranger == 2?'':'style="display:none;"')?>>
			<label class="inline-text">
				<i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?=$this->lng['etape2']['document-fiscal-2']?>"></i>

				<?=$this->lng['etape2']['document-fiscal-2']?>
			</label>

			<div class="uploader">
				<input id="text_document_fiscal_2" type="text" class="field" readonly value="<?=($this->lenders_accounts->fichier_document_fiscal!=''?$this->lenders_accounts->fichier_document_fiscal:$this->lng['etape2']['aucun-fichier-selectionne'])?>">

				<div class="file-holder">
					<span class="btn btn-small">
						+
						<span class="file-upload">
							<input type="file" class="file-field" name="document_fiscal_2">
						</span>

						<small><?=$this->lng['profile']['telecharger-un-autre-document-fiscal']?></small>
					</span>
				</div>
			</div><!-- /.uploader -->
		</div>
			
        
        <!--  -->
        <div class="row row-upload">
            <label class="inline-text">
                <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?=$this->lng['etape2']['info-cni']?>"></i>

                <?=$this->lng['etape2']['piece-didentite']?>
            </label>

            <div class="uploader">
                <input id="text_ci" type="text" class="field" readonly value="<?=($this->lenders_accounts->fichier_cni_passeport!=''?$this->lenders_accounts->fichier_cni_passeport:$this->lng['etape2']['aucun-fichier-selectionne'])?>">

                <div class="file-holder">
                    <span class="btn btn-small">
                        +
                        <span class="file-upload">
                            <input type="file" class="file-field" name="ci" id="file-ci">
                        </span>

                        <small><?=$this->lng['profile']['telecharger-un-autre-document-didentite']?></small>
                    </span>
                </div>
            </div><!-- /.uploader -->
        </div>

        <div class="les_deux">
            <p>
             <?=$this->lng['etape1']['adresse-fiscale']?>  <i class="icon-help tooltip-anchor" data-placement="right" title="<?=$this->lng['etape1']['info-adresse-fiscale']?>"></i>
        	</p>
        
            <div class="row">
                <input type="text" id="adresse_inscription" name="adresse_inscription" title="<?=$this->lng['etape1']['adresse']?>" value="<?=($this->clients_adresses->adresse_fiscal!= ''?$this->clients_adresses->adresse_fiscal:$this->lng['etape1']['adresse'])?>" class="field field-mega required" data-validators="Presence">
            </div><!-- /.row -->
    
            <div class="row row-triple-fields">
                <input type="text" id="ville_inscription" name="ville_inscription" title="<?=$this->lng['etape1']['ville']?>" value="<?=($this->clients_adresses->ville_fiscal!=''?$this->clients_adresses->ville_fiscal:$this->lng['etape1']['ville'])?>" class="field field-small required" data-validators="Presence"  data-autocomplete="cities" onBlur="autocompleteCp(this.value,'postal');">
    
                <input type="text" name="postal" id="postal" data-autocomplete="postCodes" title="<?=$this->lng['etape1']['code-postal']?>" value="<?=($this->clients_adresses->cp_fiscal!=0?$this->clients_adresses->cp_fiscal:$this->lng['etape1']['code-postal'])?>"  class="field field-small required" onBlur="checkCp('ville_inscription',this.id);" onkeyup="checkCp('ville_inscription',this.id);">
                
				<?php //Ajout CM 06/08/14 ?>
                <select name="pays1" id="pays1" class="custom-select <?=$required?> field-small">
                    <option><?=$this->lng['etape1']['pays']?></option>
                    <option><?=$this->lng['etape1']['pays']?></option>
                    <?
                    foreach($this->lPays as $p)
                    {
                        ?><option <?=($this->clients_adresses->id_pays == $p['id_pays']?'selected':'')?> value="<?=$p['id_pays']?>"><?=$p['fr']?></option><?	
                    }
                    ?>
                </select>
                
                <em class="change_addr_fiscale"><?=$this->lng['profile']['les-informations-relatives-a-votre-adresse-fiscale-ont-ete-modifiees']?></em>
            </div><!-- /.row -->
			
            <div class="row row-upload">
                <label class="inline-text">
                    <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?=$this->lng['etape2']['info-justificatif-de-domicile']?>"></i>

                    <?=$this->lng['etape2']['justificatif-de-domicile']?>
                </label>

                <div class="uploader">
                    <input id="text_just_dom" type="text" class="field" readonly value="<?=($this->lenders_accounts->fichier_justificatif_domicile!=''?$this->lenders_accounts->fichier_justificatif_domicile:$this->lng['etape2']['aucun-fichier-selectionne'])?>">

                    <div class="file-holder">
                        <span class="btn btn-small">
                            +
                            <span class="file-upload">
                                <input type="file" class="file-field" name="justificatif_de_domicile" id="file_just_dom">
                            </span>
    
                            <small><?=$this->lng['profile']['telecharger-un-autre-document-justificatif-de-domicile']?></small>
                        </span>
                    </div>
                </div><!-- /.uploader -->
            </div>
                
              
            <div class="row">
                <div class="cb-holder">
                    <label for="mon-addresse"><?=$this->lng['etape1']['meme-adresse']?></label>
    
                    <input <?=($this->clients_adresses->meme_adresse_fiscal == 0?'':'checked="checked"')?> type="checkbox" class="custom-input" name="mon-addresse" id="mon-addresse" data-condition="hide:.add-address">
                </div><!-- /.cb-holder -->
            </div><!-- /.row -->
            
            <div class="add-address">
                <p><?=$this->lng['etape1']['adresse-de-correspondance']?></p>
    
                <div class="row">
                    <input type="text" id="address2" name="adress2" title="<?=$this->lng['etape1']['adresse']?>" value="<?=($this->clients_adresses->adresse1!=''?$this->clients_adresses->adresse1:$this->lng['etape1']['adresse'])?>" class="field field-mega required" data-validators="Presence">
                </div><!-- /.row -->
    
                <div class="row row-triple-fields">
                    <input type="text" id="ville2" name="ville2" title="<?=$this->lng['etape1']['ville']?>" value="<?=($this->clients_adresses->ville!=''?$this->clients_adresses->ville:$this->lng['etape1']['ville'])?>" class="field field-small required" data-validators="Presence"  data-autocomplete="cities" onBlur="autocompleteCp(this.value,'postal2');">
    
                    <input type="text" id="postal2" name="postal2" data-autocomplete="postCodes" value="<?=($this->clients_adresses->cp!=0?$this->clients_adresses->cp:$this->lng['etape1']['code-postal'])?>" title="<?=$this->lng['etape1']['code-postal']?>" class="field field-small required" onBlur="checkCp('ville2',this.id);" onkeyup="checkCp('ville2',this.id);">
    
					<?php //Ajout CM 06/08/14 ?>
                    <select name="pays2" id="pays2" class="custom-select <?=$required?> field-small">
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
		</div>
        
         
        
        <span class="form-caption"><?=$this->lng['etape1']['champs-obligatoires']?></span>

        <div class="form-foot row row-cols centered">
        	<input type="hidden" name="send_form_particulier_perso">
            <button class="btn" type="button" onClick='$( "#form_particulier_perso" ).submit();'><?=$this->lng['etape1']['valider']?> <i class="icon-arrow-next"></i></button>
        </div><!-- /.form-foot foot-cols -->

        
    



<script type="text/javascript">

/////////////////////
// change_identite //
/////////////////////

var change_file_identite = false;
var change_txt_identite = false;

// nom famille et prenom
$( "#nom-famille,#prenom" ).change(function() {
	if($(this).val() != "<?=$this->clients->nom?>" && change_file_identite == false){ $("#text_ci").val(''); $(".change_identite").fadeIn();change_txt_identite = true;}
});

// nom usage
$( "#nom-dusage" ).change(function() {
	if($(this).val() != "<?=($this->clients->nom_usage!=''?$this->clients->nom_usage:$this->lng['etape1']['nom-dusage'])?>" && change_file_identite == false){ $("#text_ci").val(''); $(".change_identite").fadeIn();change_txt_identite = true;}
});


$( "#file-ci" ).change(function() {
	if(change_txt_identite == false){ $( "#nom-famille,#prenom" ).val(''); $(".change_identite").fadeIn();change_file_identite = true;}
});


/////////////////////////
// change_addr_fiscale //
/////////////////////////

var change_addr_fiscale_file = false;
var change_addr_fiscale_txt = false;


// rue, ville, cp, pays
$( "#adresse_inscription,#ville_inscription,#postal,#pays1" ).change(function() {
	if($('#adresse_inscription').val() != "<?=$this->clients_adresses->adresse_fiscal?>" && change_addr_fiscale_file == false){ 
		$("#text_just_dom").val(''); $(".change_addr_fiscale").fadeIn();change_addr_fiscale_txt = true;
	}
	if($('#ville_inscription').val() != "<?=$this->clients_adresses->ville_fiscal?>" && change_addr_fiscale_file == false){
		$("#text_just_dom").val(''); $(".change_addr_fiscale").fadeIn();change_addr_fiscale_txt = true;
	}
	if($('#postal').val() != "<?=$this->clients_adresses->cp_fiscal?>" && change_addr_fiscale_file == false){
		$("#text_just_dom").val(''); $(".change_addr_fiscale").fadeIn();change_addr_fiscale_txt = true;
	}
	if($('#pays1').val() != "<?=$this->clients_adresses->id_pays?>" && change_addr_fiscale_file == false){
		$("#text_just_dom").val(''); $(".change_addr_fiscale").fadeIn();change_addr_fiscale_txt = true;
	}
});

$( "#file_just_dom" ).change(function() {
	if(change_addr_fiscale_txt == false){ $( "#adresse_inscription,#ville_inscription,#postal,#pays1" ).val(''); $(".change_addr_fiscale").fadeIn();change_addr_fiscale_file = true;}
});

$(document).ready(function () {
		
	// confirmation email preteur particulier
	$('#conf_email').bind('paste', function (e) { e.preventDefault(); });
	$('#email').bind('paste', function (e) { e.preventDefault(); });
	
	////////////////////////////////////////////
	$( "#jour_naissance" ).change(function() {
		var d = $('#jour_naissance').val();
		var m = $('#mois_naissance').val();
		var y = $('#annee_naissance').val();
		
		$.post( add_url+"/ajax/controleAge", { d: d,m:m,y:y }).done(function( data ) {
			if(data == 'ok'){
				$(".check_age").html('true');
				$(".error_age").slideUp();
			}
			else{
				radio = false;
				$(".check_age").html('false');
				$(".error_age").slideDown();
			}
		});
	});
	
	$( "#mois_naissance" ).change(function() {
	var d = $('#jour_naissance').val();
		var m = $('#mois_naissance').val();
		var y = $('#annee_naissance').val();
		
		$.post( add_url+"/ajax/controleAge", { d: d,m:m,y:y }).done(function( data ) {
			if(data == 'ok'){
				$(".check_age").html('true');
				$(".error_age").slideUp();
				
			}
			else{
				radio = false;
				$(".check_age").html('false');
				$(".error_age").slideDown();
			}
		});
	});
	
	$( "#annee_naissance" ).change(function() {
	var d = $('#jour_naissance').val();
		var m = $('#mois_naissance').val();
		var y = $('#annee_naissance').val();
		
		$.post( add_url+"/ajax/controleAge", { d: d,m:m,y:y }).done(function( data ) {
			if(data == 'ok'){
				$(".check_age").html('true');
				$(".error_age").slideUp();
			}
			else{
				radio = false;
				$(".check_age").html('false');
				$(".error_age").slideDown();
			}
		});
	});
////////////////////////////////////////////////////
	
	// particulier etranger
	$("#pays1,#nationalite").change(function() {
		var pays1 = $('#pays1').val();
		var nationalite = $('#nationalite').val();
		
		//resident etranger
		if(nationalite > 0 && pays1 > 1){ 
			$(".etranger").slideDown();
			if(nationalite == 1 && pays1 > 1){ $(".etranger1").slideDown();$(".etranger2").slideUp(); }
			else if(nationalite != 1 && pays1 > 1){ $(".etranger2").slideDown();$(".etranger1").slideUp();}
		}
		else{ $(".etranger").slideUp();$(".etranger1").slideUp();$(".etranger2").slideUp(); }
	});

	// particulier messagge check_etranger
	$("#check_etranger").change(function() {
		if($(this).is(':checked') == true){
			$(".message_check_etranger").slideUp();
			$("#text_document_fiscal_1").val('');
			$("#text_document_fiscal_2").val('');
			//$(".cb_check_etranger").addClass('checked');
		}
		else{$(".message_check_etranger").slideDown();/*$( ".cb_check_etranger" ).removeClass("checked");*/}
	});
	
	//CInput2.init();
});


// perso
$("#form_particulier_perso").submit(function( event ) {
	var form_ok = true;
	var text_ci = $("#text_ci");
	var text_just_dom = $("#text_just_dom");
	
	if($(".check_age").html() == 'false'){
		form_ok = false;
	}
	
	
	// check cp //
	//checkCp('ville_inscription','postal');
	var id_ville = 'ville_inscription';
	var id_cp = 'postal';
	var cp = $('#'+id_cp).val();
	var ville =  $('#'+id_ville).val();
	
	var title_cp = $('#'+id_cp).attr("title");
	var title_ville = $('#'+id_ville).attr("title");
	
	if(title_ville == ville) ville = '';
	if(title_cp == cp) cp = '';
	
	$.post(add_url + '/ajax/checkCp', { ville: ville, cp: cp },function(data){ 
		if(data != 'ok'){ $('#'+id_cp).addClass('LV_invalid_field'); $('#'+id_cp).removeClass('LV_valid_field');form_ok = false; }
		else{ $('#'+id_cp).addClass('LV_valid_field'); $('#'+id_cp).removeClass('LV_invalid_field'); }
	});
	//fin check cp //
	
	if($('#mon-addresse').is(':checked') == false){
		
		//checkCp('ville_inscription','postal');
		var id_ville = 'ville2';
		var id_cp = 'postal2';
		var cp = $('#'+id_cp).val();
		var ville =  $('#'+id_ville).val();
		
		var title_cp = $('#'+id_cp).attr("title");
		var title_ville = $('#'+id_ville).attr("title");
		
		if(title_ville == ville) ville = '';
		if(title_cp == cp) cp = '';
		
		$.post(add_url + '/ajax/checkCp', { ville: ville, cp: cp },function(data){ 
			if(data != 'ok'){ $('#'+id_cp).addClass('LV_invalid_field'); $('#'+id_cp).removeClass('LV_valid_field');form_ok = false; }
			else{ $('#'+id_cp).addClass('LV_valid_field'); $('#'+id_cp).removeClass('LV_invalid_field'); }
		});
		//fin check cp //
	}
	
	
	//resident etranger
	var pays1 = $('#pays1').val();
	var nationalite = $('#nationalite').val();
	if(nationalite > 0 && pays1 > 1){
		// check_etranger
		if($('#check_etranger').is(':checked') == false){$('.check_etranger').css('color','#C84747'); $('#check_etranger').addClass('LV_invalid_field');$('#check_etranger').removeClass('LV_valid_field');  form_ok = false; }
		else{ $('#check_etranger').addClass('LV_valid_field');$('#check_etranger').removeClass('LV_invalid_field'); $('.check_etranger').css('color','#727272');}
		
		var text_document_fiscal = $("#text_document_fiscal_1");
		
		// document fiscal
		if(nationalite == 1 && pays1 > 1){var text_document_fiscal = $("#text_document_fiscal_1"); }
		else if(nationalite != 1 && pays1 > 1){var text_document_fiscal = $("#text_document_fiscal_2");}
			
		if(text_document_fiscal.val() == '' || text_document_fiscal.val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>'){form_ok = false; text_document_fiscal.addClass('LV_invalid_field');text_document_fiscal.removeClass('LV_valid_field');}
		else { text_document_fiscal.addClass('LV_valid_field');text_document_fiscal.removeClass('LV_invalid_field'); }
	}
	
	// ci
	if(text_ci.val() == ''){form_ok = false; text_ci.addClass('LV_invalid_field');text_ci.removeClass('LV_valid_field');}
	else { text_ci.addClass('LV_valid_field');text_ci.removeClass('LV_invalid_field'); }
	// just domicile
	if(text_just_dom.val() == ''){form_ok = false; text_just_dom.addClass('LV_invalid_field');text_just_dom.removeClass('LV_valid_field');}
	else { text_just_dom.addClass('LV_valid_field');text_just_dom.removeClass('LV_invalid_field'); }
	
	if(form_ok == false){event.preventDefault(); }
});

</script>