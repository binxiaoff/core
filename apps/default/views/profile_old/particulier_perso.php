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
	
    <form action="" method="post" name="form_particulier_perso" id="form_particulier_perso" enctype="multipart/form-data">
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
            <input type="text" name="email" id="email" title="<?=$this->lng['etape1']['email']?>" value="<?=($this->clients->email!=''?$this->clients->email:$this->lng['etape1']['email'])?>" class="field field-large required" data-validators="Presence&amp;Email" onkeyup="checkConf(this.value,'conf_email')" >

            
            <input type="text" name="conf_email" id="conf_email" title="<?=$this->lng['etape1']['confirmation-email']?>" value="<?=($this->clients->email!=''?$this->clients->email:$this->lng['etape1']['confirmation-email'])?>" class="field field-large required" data-validators="Confirmation,{ match: 'email' }" >
        </div><!-- /.row -->
        
        <div class="row row-triple-fields">
			<?php //Ajout CM 06/08/14 ?>
            <select name="pays3" id="pays3" class="custom-select <?=$required?> field-small">
                <option><?=$this->lng['etape1']['pays-de-naissance']?></option>
               	<option><?=$this->lng['etape1']['pays-de-naissance']?></option>
                <?
                foreach($this->lPays as $p){
                    ?><option <?=($this->clients->id_pays_naissance == $p['id_pays']?'selected':'')?> value="<?=$p['id_pays']?>"><?=$p['fr']?></option><?	
                }
                ?>
            </select>

            <input type="text" name="naissance" title="<?=$this->lng['etape1']['commune-de-naissance']?>" value="<?=($this->clients->ville_naissance!=''?$this->clients->ville_naissance:$this->lng['etape1']['commune-de-naissance'])?>" id="naissance" class="field field-small required" data-validators="Presence" data-autocomplete="cities">
			
			<?php //Ajout CM 06/08/14 ?>
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

        <div class="row">
            <span class="inline-text"><?=$this->lng['etape1']['date-de-naissance']?></span>

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
        </div><!-- /.row -->

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
                            <input type="file" class="file-field" name="ci">
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
    
                <input type="text" name="postal" id="postal" data-autocomplete="postCodes" title="<?=$this->lng['etape1']['code-postal']?>" value="<?=($this->clients_adresses->cp_fiscal!=0?$this->clients_adresses->cp_fiscal:$this->lng['etape1']['code-postal'])?>"  class="field field-small required" data-validators="Presence&amp;Numericality&amp;Length, {is: 5}">
                
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
			
            <div class="row">
            <input type="text" name="phone" id="phone" value="<?=($this->clients->telephone!=''?$this->clients->telephone:$this->lng['etape1']['telephone'])?>" title="<?=$this->lng['etape1']['telephone']?>" class="field field-small required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9,maximum: 14}">
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
                                <input type="file" class="file-field" name="justificatif_de_domicile">
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
    
                    <input type="text" id="postal2" name="postal2" data-autocomplete="postCodes" value="<?=($this->clients_adresses->cp!=0?$this->clients_adresses->cp:$this->lng['etape1']['code-postal'])?>" title="<?=$this->lng['etape1']['code-postal']?>" class="field field-small required" data-validators="Presence&amp;Numericality&amp;Length, {is: 5}">
    				
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

        
    </form>
</div>

<script type="text/javascript">

/////////////////////
// change_identite //
/////////////////////
// nom famille
$( "#nom-famille" ).change(function() {
	if($(this).val() != "<?=$this->clients->nom?>"){ $("#text_ci").val(''); $(".change_identite").fadeIn();}
});
// prenom
$( "#prenom" ).change(function() {
	if($(this).val() != "<?=$this->clients->prenom?>"){ $("#text_ci").val(''); $(".change_identite").fadeIn();}
});
// nom usage
$( "#nom-dusage" ).change(function() {
	if($(this).val() != "<?=($this->clients->nom_usage!=''?$this->clients->nom_usage:$this->lng['etape1']['nom-dusage'])?>"){ $("#text_ci").val(''); $(".change_identite").fadeIn();}
});
/////////////////////////
// change_addr_fiscale //
/////////////////////////
// rue 
$( "#adresse_inscription" ).change(function() {
	if($(this).val() != "<?=$this->clients_adresses->adresse_fiscal?>"){ $("#text_just_dom").val(''); $(".change_addr_fiscale").fadeIn();}
});
// ville
$( "#ville_inscription" ).change(function() {
	if($(this).val() != "<?=$this->clients_adresses->ville_fiscal?>"){ $("#text_just_dom").val(''); $(".change_addr_fiscale").fadeIn();}
});
// cp
$( "#postal" ).change(function() {
	if($(this).val() != "<?=$this->clients_adresses->cp_fiscal?>"){ $("#text_just_dom").val(''); $(".change_addr_fiscale").fadeIn();}
});
// pays
$( "#pays1" ).change(function() {
	if($(this).val() != "<?=$this->clients_adresses->id_pays?>"){ $("#text_just_dom").val(''); $(".change_addr_fiscale").fadeIn();}
});


</script>