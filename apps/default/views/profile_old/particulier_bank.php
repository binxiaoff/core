<div class="account-data">
    <h2><?=$this->lng['profile']['titre-2']?></h2>
	
    <?
	if(isset($_SESSION['reponse_profile_bank']) && $_SESSION['reponse_profile_bank'] != ''){
		?><div class="reponseProfile"><?=$_SESSION['reponse_profile_bank']?></div><?
		unset($_SESSION['reponse_profile_bank']);
	}
	?>
    
    <p><?=$this->lng['etape2']['compte-beneficiaire-des-virements']?></p>
    <p><?=$this->lng['etape2']['texte-bic-iban']?></p>

    <form action="<?=$this->lurl?>/profile/particulier/2" method="post" name="form_bank" id="form_bank" enctype="multipart/form-data">
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
                            <input type="file" class="file-field" name="rib">
                        </span>
                    </span>
                </div>
            </div><!-- /.uploader -->
        </div><!-- /.row -->

        <span class="form-caption"><?=$this->lng['etape2']['champs-obligatoires']?></span>

        <div class="form-foot row row-cols centered">
        	<input type="hidden" name="send_form_bank_particulier" />
            <button class="btn" type="submit" onClick="$('#form_bank').submit();"><?=$this->lng['profile']['valider']?> <i class="icon-arrow-next"></i></button>
        </div><!-- /.form-foot foot-cols -->
    </form>
</div>