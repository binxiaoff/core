<form action="" method="post" id="form_inscription_preteur_societe_etape_2" enctype="multipart/form-data">
    <div class="form-header">
        <span><?=$this->lng['etape2']['compte-beneficiaire-des-virements']?></span>

        <span><?=$this->lng['etape2']['texte-bic-iban']?></span>
    </div><!-- /.form-header -->

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
            foreach($this->origine_fonds_E as $k => $origine_fonds){
                ?><option <?=($this->lenders_accounts->origine_des_fonds == $k+1?'selected':'')?> value="<?=$k+1?>" ><?=$origine_fonds?></option><?
            }
            ?>
            <option <?=($this->lenders_accounts->origine_des_fonds == 1000000?'selected':'')?> value="1000000" ><?=$this->lng['etape2']['autre']?></option>
        </select>
    </div><!-- /.row -->
    
    <div class="row" id="row_precision" <?=($this->lenders_accounts->origine_des_fonds == 1000000?'':'style="display:none;"')?>>
        <input type="text" id="preciser" name="preciser" title="<?=$this->lng['etape2']['autre-preciser']?>" value="<?=($this->lenders_accounts->precision!=''?$this->lenders_accounts->precision:$this->lng['etape2']['autre-preciser'])?>" class="field field-mega">
    </div><!-- /.row -->
     
    <p><?=$this->lng['etape2']['documents-a-fournir']?></p>
    <em class="error_fichier" <?=($this->error_fichier==true?'style="display:block;"':'')?>><?=$this->lng['etape2']['erreur-fichier']?></em>
    <div class="row row-upload">
        <label class="inline-text">
            <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?=$this->lng['etape2']['info-extrait-kbis']?>"></i>
             <?=$this->lng['etape2']['extrait-kbis']?> 
        </label>
        <div class="uploader">
            <input id="txt_kbis" type="text" class="field required <?=($this->error_extrait_kbis==true?'LV_invalid_field':'')?>" readonly="readonly" value="<?=($this->lenders_accounts->fichier_extrait_kbis!= ''?$this->lenders_accounts->fichier_extrait_kbis:$this->lng['etape2']['aucun-fichier-selectionne'])?>" />
            <div class="file-holder">
                <span class="btn btn-small">
                    <?=$this->lng['etape2']['parcourir']?>
                    <span class="file-upload">
                        <input type="file" class="file-field" name="kbis">
                    </span>
                </span>
            </div>
        </div><!-- /.uploader -->
    </div><!-- /.row -->    
    
    <div class="row row-upload">
        <label class="inline-text">
            <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?=$this->lng['etape2']['info-delegation-de-pouvoir']?>"></i>

            <?=$this->lng['etape2']['delegation-de-pouvoir']?>
        </label>

        <div class="uploader">
            <input id="txt_delegation_pouvoir" type="text" class="field required <?=($this->error_delegation_pouvoir==true?'LV_invalid_field':'')?>" readonly="readonly" value="<?=($this->lenders_accounts->fichier_delegation_pouvoir!= ''?$this->lenders_accounts->fichier_delegation_pouvoir:$this->lng['etape2']['aucun-fichier-selectionne'])?>" />

            <div class="file-holder">
                <span class="btn btn-small">
                    <?=$this->lng['etape2']['parcourir']?>
                    <span class="file-upload">
                        <input type="file" class="file-field" name="delegation_pouvoir">
                    </span>
                </span>
            </div>
        </div><!-- /.uploader -->
    </div><!-- /.row -->

    <div class="row row-upload">
        <label class="inline-text">
            <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?=$this->lng['etape2']['info-rib']?>"></i>

            <?=$this->lng['etape2']['rib']?>
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
    
    <div class="row row-upload">
        <label class="inline-text">
            <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?=$this->lng['etape2']['info-cni-passeport-dirigeants']?>"></i>
			<?=$this->lng['etape2']['cni-passeport-dirigeants']?>
        </label>

        <div class="uploader">
            <input id="txt_ci_dirigeant" type="text" class="field required <?=($this->error_cni_dirigent==true?'LV_invalid_field':'')?>" readonly="readonly" value="<?=($this->lenders_accounts->fichier_cni_passeport_dirigent!= ''?$this->lenders_accounts->fichier_cni_passeport_dirigent:$this->lng['etape2']['aucun-fichier-selectionne'])?>" />

            <div class="file-holder">
                <span class="btn btn-small">
                    <?=$this->lng['etape2']['parcourir']?>

                    <span class="file-upload">
                        <input type="file" class="file-field" name="ci_dirigeant">
                    </span>
                </span>
            </div>
        </div><!-- /.uploader -->
    </div><!-- /.row -->
    
    <div class="row row-upload">
        <label class="inline-text">
            <div class="row-upload file-uploaded">
                <div class="uploader">
                    <div class="file-holder">
                        <span class="btn btn-small btn-add-new-row">+<small><?=$this->lng['etape2']['telecharger-un-autre-document']?></small></span>
                    </div>
                </div><!-- /.uploader -->
            </div><!-- /.row -->
        </label>
        <div class="uploader uploader-file" <?=($this->lenders_accounts->fichier_autre!= '' || $this->error_autre==true?'':'style="display:none;"')?> >
            <input id="txt_autre" type="text" class="field required <?=($this->error_autre==true?'LV_invalid_field':'')?>" readonly="readonly" value="<?=($this->lenders_accounts->fichier_autre!= ''?$this->lenders_accounts->fichier_autre:$this->lng['etape2']['aucun-fichier-selectionne'])?>" />
            <div class="file-holder">
                <span class="btn btn-small">
                    <?=$this->lng['etape2']['parcourir']?>
                    <span class="file-upload">
                        <input type="file" class="file-field" name="autre">
                    </span>
                </span>
            </div>
        </div><!-- /.uploader -->
    </div><!-- /.row -->
    <script type="text/javascript">
		$(".btn-add-new-row").click(function() {$(".uploader-file").fadeIn();});
    </script>

    <span class="form-caption"><?=$this->lng['etape2']['champs-obligatoires']?></span>

    <div class="form-foot row row-cols centered">
        <input type="hidden" name="send_form_inscription_preteur_societe_etape_2">
        <a class="btn btn-warning" href="<?=$this->lurl?>/inscription_preteur/etape1/<?=$this->clients->hash?>" ><i class="icon-arrow-prev"></i><?=$this->lng['etape2']['precedent']?> </a>
        
        <button id="next_preteur" class="btn" type="submit" onClick="$('#form_inscription_preteur_societe_etape_2').submit();"><?=$this->lng['etape2']['suivant']?><i class="icon-arrow-next"></i></button>

    </div><!-- /.form-foot foot-cols -->
</form>