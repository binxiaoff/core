<style type="text/css">
	.tabs .tab{display:block;}
	.field-large {width: 422px;}
	.tab .form-choose{margin-bottom:0;}
	.form-page form .row .pass-field-holder {width: 460px;}
	.euro-field.field-large{padding-left:22px;}
</style>

<!--#include virtual="ssi-header-login.shtml"  -->
<div class="main form-page account-page account-page-personal">
    <div class="shell">

        <div class="account-data">
            <h2><?=$this->lng['profile']['validation-compte']?></h2>
        	
             <?
			if(isset($_SESSION['reponse_upload']) && $_SESSION['reponse_upload'] != ''){
				?><div class="reponseProfile"><?=$_SESSION['reponse_upload']?></div><?
				unset($_SESSION['reponse_upload']);
			}
			?> 
            <p><?=$this->lng['profile']['validation-compte-contenu']?></p>
        	<em class="error_fichier" <?=($this->error_fichiers==true?'style="display:block;"':'')?>><?=$this->lng['etape2']['erreur-fichier']?></em>
            <form action="" method="post" name="form_upload_doc" id="form_upload_doc" enctype="multipart/form-data">
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
                
                <div class="form-foot row row-cols centered">
                	<input type="hidden" name="send_form_upload_doc">
                    <button class="btn" type="button" onClick="$('#form_upload_doc').submit();"><?=$this->lng['profile']['valider']?> <i class="icon-arrow-next"></i></button>
                </div><!-- /.form-foot foot-cols -->
            </form>
        </div>
	</div>
</div>

<script type="application/javascript">
// formulaire informations bancaires
$("#form_upload_doc").submit(function( event ) {
	var form_ok = true;
	
	// fichiers
	// ci_dirigeant
	if($('#txt_ci_dirigeant').val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>'){
		form_ok = false;
		$("#txt_ci_dirigeant").removeClass("LV_valid_field");$("#txt_ci_dirigeant").addClass("LV_invalid_field");
	}
	else {$("#txt_ci_dirigeant").removeClass("LV_invalid_field");$("#txt_ci_dirigeant").addClass("LV_valid_field");}
	
	// rib
	if($('#txt_rib').val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>'){
		form_ok = false;
		$("#txt_rib").removeClass("LV_valid_field");$("#txt_rib").addClass("LV_invalid_field");
	}
	else {$("#txt_rib").removeClass("LV_invalid_field");$("#txt_rib").addClass("LV_valid_field");}
	// txt_kbis
	if($('#txt_kbis').val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>'){
		form_ok = false;
		$("#txt_kbis").removeClass("LV_valid_field");$("#txt_kbis").addClass("LV_invalid_field");
	}
	else {$("#txt_kbis").removeClass("LV_invalid_field");$("#txt_kbis").addClass("LV_valid_field");}
	
	if(form_ok == false){ event.preventDefault(); }
});
</script>