
    <h2 id="position_bank"><?=$this->lng['profile']['titre-2']?></h2>
	
    <?
	if(isset($_SESSION['reponse_profile_bank']) && $_SESSION['reponse_profile_bank'] != ''){
		?><div class="reponseProfile"><?=$_SESSION['reponse_profile_bank']?></div><?
		// unset($_SESSION['reponse_profile_bank']); unset dans preteur_perso_bank
		?>
        
        <?
	}
	?>
    
    <p><?=$this->lng['etape2']['compte-beneficiaire-des-virements']?></p>
    <p><?=$this->lng['etape2']['texte-bic-iban']?></p>

        <div class="row row-bank">
            <label for="bic" class="inline-text">
                <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?=$this->lng['etape2']['info-bic']?>"></i>

               <?=$this->lng['etape2']['bic']?>
            </label>
			<span class="field-holder">
                <input type="text" id="bic" name="bic" title="<?=$this->lng['etape2']['bic-exemple']?>" value="<?=($this->lenders_accounts->bic!=''?$this->lenders_accounts->bic:$this->lng['etape2']['bic-exemple'])?>" class="field field-medium "><br />
    
                <em class="error_bic"><?=$this->lng['etape2']['bic-erreur']?></em>
            </span>
        </div><!-- /.row -->

        <div class="row row-bank">
            <label class="inline-text">
                <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?=$this->lng['etape2']['info-iban']?>"></i>

           		<?=$this->lng['etape2']['iban']?>
            </label>

            <span class="field-holder">
                <input maxlength="4" type="text" name="iban-1" id="iban-1" value="<?=$this->iban1?>" title="<?=$this->iban1?>" class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="4" type="text" name="iban-2" id="iban-2" value="<?=$this->iban2?>" class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="4" type="text" name="iban-3" id="iban-3" value="<?=$this->iban3?>" class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="4" type="text" name="iban-4" id="iban-4" value="<?=$this->iban4?>" class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="4" type="text" name="iban-5" id="iban-5" value="<?=$this->iban5?>" class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="4" type="text" name="iban-6" id="iban-6" value="<?=$this->iban6?>" class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,4);" onchange="check_ibanNB(this.id,this.value,4);">
                <input maxlength="3" type="text" name="iban-7" id="iban-7" value="<?=$this->iban7?>" class="field field-extra-tiny" onkeyup="check_ibanNB(this.id,this.value,3);" onchange="check_ibanNB(this.id,this.value,3);">
                <br />
    
                <em class="error_iban"><?=$this->lng['etape2']['iban-erreur']?></em>
            </span>
        </div><!-- /.row -->
        
         <p><?=$this->lng['etape2']['origine-des-fonds']?></p>

        <div class="row">
            <select name="origine_des_fonds" id="origine_des_fonds" class="custom-select field-medium required">
                <option value="0"><?=$this->lng['etape2']['choisir']?></option>
                <option value="0"><?=$this->lng['etape2']['choisir']?></option>
                <?
                foreach($this->origine_fonds as $k => $origine_fonds){
                    ?><option <?=($this->lenders_accounts->origine_des_fonds == $k+1?'selected':'')?> value="<?=$k+1?>" ><?=$origine_fonds?></option><?
                }
                ?>
                <option <?=($this->lenders_accounts->origine_des_fonds == 1000000?'selected':'')?> value="1000000" ><?=$this->lng['etape2']['autre']?></option>
            </select>
        </div><!-- /.row -->
        
        <div class="row" id="row_precision" <?=($this->lenders_accounts->origine_des_fonds == 1000000?'':'style="display:none;"')?>>
            <input type="text" id="preciser" name="preciser" title="<?=$this->lng['etape2']['autre-preciser']?>" value="<?=($this->lenders_accounts->precision!=''?$this->lenders_accounts->precision:$this->lng['etape2']['autre-preciser'])?>" class="field field-mega">
        </div><!-- /.row -->
            
        <p>
            <em class="change_bank"><?=$this->lng['profile']['les-informations-relatives-a-vos-coordonnees-bancaires-ont-ete-modifiees.-merci-de-telecharger-un-nouveau-justificatif-bancaire']?></em>
        </p>
        <em class="error_fichier" <?=($this->error_fichier==true?'style="display:block;"':'')?>><?=$this->lng['etape2']['erreur-fichier']?></em>
        <div class="row row-upload">
            <label class="inline-text">
                <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?=$this->lng['etape2']['info-justificatif-bancaire']?>"></i>

                <?=$this->lng['etape2']['justificatif-bancaire']?>
            </label>

            <div class="uploader">
                <input id="txt_rib" type="text" class="field required <?=($this->error_rib==true?'LV_invalid_field':'')?>" readonly="readonly" value="<?=($this->lenders_accounts->fichier_rib!= ''?$this->lenders_accounts->fichier_rib:$this->lng['etape2']['aucun-fichier-selectionne'])?>" />

                <div class="file-holder">
                    <span class="btn btn-small">
                        <?=$this->lng['etape2']['parcourir']?>

                        <span class="file-upload">
                            <input type="file" class="file-field" name="rib" id="file-rib">
                        </span>
                    </span>
                </div>
            </div><!-- /.uploader -->
        </div><!-- /.row -->

        <span class="form-caption"><?=$this->lng['etape2']['champs-obligatoires']?></span>

        <div class="form-foot row row-cols centered">
        	<input type="hidden" name="send_form_bank_particulier" />
            <button class="btn" type="submit" onClick="$('#form_particulier_perso').submit();"><?=$this->lng['profile']['valider']?> <i class="icon-arrow-next"></i></button>
        </div><!-- /.form-foot foot-cols -->
    </form>
</div>


<script type="text/javascript">
	// info bank //
	
	var info_bank_txt = false;
	var info_bank_file = false;
	
	// BIC
	$("#bic").keyup(function() { check_bic($(this).val()); });
	$("#bic").change(function() { check_bic($(this).val()); if($(this).val() != "<?=$this->lenders_accounts->bic?>" && info_bank_file == false){$(".change_bank").fadeIn(); $('#txt_rib').val(''); info_bank_txt = true;}});
	
	// IBAN
	for(var i=2;i<=7;i++){
		$('#iban-'+i).change(function() {
			$(".change_bank").fadeIn();
			if(info_bank_file == false){
				$('#txt_rib').val('');
				info_bank_txt = true;
			}
		});	
	}
	
	$('#iban-1').change(function() {
		if($("#iban-1").val().substring(0,2).toLowerCase() != 'fr'){	
			$("#iban-1").addClass('LV_invalid_field');$("#iban-1").removeClass('LV_valid_field');
			$(".error_iban").html('<?=$this->lng['etape2']['iban-erreur-2']?>');
			$(".error_iban").slideDown();
		}
		else{ 
			$("#iban-1").addClass('LV_valid_field');$("#iban-1").removeClass('LV_invalid_field');
			$(".error_iban").slideUp();
		}
	});
	
	$( "#file-rib" ).change(function() {
	if(info_bank_txt == false){ 
		$("#bic").val(''); 
		for(var i=2;i<=7;i++){ $('#iban-'+i).val(''); }
		$(".change_bank").fadeIn();info_bank_file = true;
	}
});
	
	
	// formulaire informations bancaires
	$("#form_particulier_perso").submit(function( event ) {
		var form_ok = true;


		
		// origine
		if($('#origine_des_fonds').val() == 0){
			$('#origine_des_fonds').addClass('LV_invalid_field');$('#origine_des_fonds').removeClass('LV_valid_field');
			form_ok = false;
		}
		else{$('#origine_des_fonds').addClass('LV_valid_field');$('#origine_des_fonds').removeClass('LV_invalid_field');}
		
		// rib 
		if($('#txt_rib').val() == ''){ form_ok = false; $('#txt_rib').addClass('LV_invalid_field');$('#txt_rib').removeClass('LV_valid_field');}
		else { $('#txt_rib').addClass('LV_valid_field');$('#txt_rib').removeClass('LV_invalid_field'); }
		
		// check BIC
		if(check_bic($("#bic").val()) == false){form_ok = false;}
	
		//check IBAN
		var iban_ok = true;
		var size_iban = true;
		var new_iban = '';
		for(var i=1;i<=7;i++){
			// 4 caracteres
			if(i < 7){
				if($('#iban-'+i).val().length < 4 || $('#iban-'+i).val().length > 4){
					check_ibanNB('iban-'+i,$('#iban-'+i).val(),4);
					size_iban = false
				}
				else new_iban = new_iban+$('#iban-'+i).val();
			}
			// 3 caracteres
			else{
				if($('#iban-'+i).val().length < 3 || $('#iban-'+i).val().length > 3){
					check_ibanNB('iban-'+i,$('#iban-'+i).val(),3);
					size_iban = false	
				}
				else new_iban = new_iban+$('#iban-'+i).val();
			}
		}
		
		if($("#iban-1").val().substring(0,2).toLowerCase() != 'fr'){	
			$("#iban-1").addClass('LV_invalid_field');$("#iban-1").removeClass('LV_valid_field');
			$(".error_iban").html('<?=$this->lng['etape2']['iban-erreur-2']?>');
			form_ok = false;
			iban_ok = false;	
		}
		else{ 
			$("#iban-1").addClass('LV_valid_field');$("#iban-1").removeClass('LV_invalid_field');
			$(".error_iban").html('<?=$this->lng['etape2']['iban-erreur']?>');
		}
		
		// Lorsque l'on a le bon nombre de caracteres
		if(size_iban == true)
		{
			// On verifie si l'IBAN est bon
			if(validateIban(new_iban) == false){
				for(var i=1;i<=7;i++){
					$("#iban-"+i).addClass('LV_invalid_field');$("#iban-"+i).removeClass('LV_valid_field');
				}
				iban_ok = false;
			}
			else{
				for(var i=1;i<=7;i++){
					$("#iban-"+i).addClass('LV_valid_field');$("#iban-"+i).removeClass('LV_invalid_field');
				}
			}
		}
		else{
			iban_ok = false;
		}
		
		
		
		// Resultat check IBAN
		if(iban_ok == false){
			$(".error_iban").show();
			form_ok = false
		}
		else $(".error_iban").hide();

		if($('#origine_des_fonds').val() == '1000000'){
			if($('#preciser').val() == '' || $('#preciser').val() == $('#preciser').attr('title')){
				form_ok = false;
				$("#preciser").removeClass("LV_valid_field");$("#preciser").addClass("LV_invalid_field");
			}
			else{ $("#preciser").removeClass("LV_invalid_field");$("#preciser").addClass("LV_valid_field"); }
		}
		
		if(form_ok == false){
			event.preventDefault();
		}
	});
</script>