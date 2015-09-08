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
        	<em class="error_fichier" <?=($this->error_fichier==true?'style="display:block;"':'')?>><?=$this->lng['etape2']['erreur-fichier']?></em>
            <form action="" method="post" name="form_upload_doc" id="form_upload_doc" enctype="multipart/form-data">
                <div class="row row-upload">
                    <label class="inline-text">
                        <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?=$this->lng['etape2']['info-cni']?>"></i>
                         <?=$this->lng['etape2']['piece-didentite']?> 
                    </label>
                    <div class="uploader">
                        <input id="txt_ci" type="text" class="field required <?=($this->error_cni==true?'LV_invalid_field':'')?>" readonly="readonly" value="<?=($this->attachments[attachment_type::CNI_PASSPORTE]["path"]!= ''?$this->attachments[attachment_type::CNI_PASSPORTE]["path"]:$this->lng['etape2']['aucun-fichier-selectionne'])?>" />
                        <div class="file-holder">
                            <span class="btn btn-small">
                                <?=$this->lng['etape2']['parcourir']?>
                                <span class="file-upload">
                                    <input type="file" class="file-field" name="cni_passeport">
                                </span>
                            </span>
                        </div>
                    </div><!-- /.uploader -->
                </div><!-- /.row -->
                
                <div class="row row-upload">
                    <label class="inline-text">
                        <div class="row-upload file-uploaded">
                            <div class="uploader">
                                <?=$this->lng['etape2']['piece-didentite-verso']?>
                            </div><!-- /.uploader -->
                        </div><!-- /.row -->
                    </label>
                    <div class="uploader uploader-file">
                        <input id="txt_autre" type="text" class="field required <?=($this->error_autre==true?'LV_invalid_field':'')?>" readonly="readonly" value="<?=($this->attachments[attachment_type::CNI_PASSPORTE_VERSO]["path"]!= ''?$this->attachments[attachment_type::CNI_PASSPORTE_VERSO]["path"]:$this->lng['etape2']['aucun-fichier-selectionne'])?>" />
                        <div class="file-holder">
                            <span class="btn btn-small">
                                <?=$this->lng['etape2']['parcourir']?>
                                <span class="file-upload">
                                    <input type="file" class="file-field" name="cni_passeport_verso">
                                </span>
                            </span>
                        </div>
                    </div><!-- /.uploader -->
                </div><!-- /.row -->
                <script type="text/javascript">
                    $(".btn-add-new-row").click(function() {$(".uploader-file").fadeIn();});
                </script>
                
                <div class="row row-upload">
                    <label class="inline-text">
                        <i class="icon-help tooltip-anchor" data-placement="right" title="" data-original-title="<?=$this->lng['etape2']['info-justificatif-de-domicile']?>"></i>
            
                        <?=$this->lng['etape2']['justificatif-de-domicile']?>
                    </label>
            
                    <div class="uploader">
                        <input id="txt_justificatif_de_domicile" type="text" class="field required <?=($this->error_justificatif_domicile==true?'LV_invalid_field':'')?>" readonly="readonly" value="<?=($this->attachments[attachment_type::JUSTIFICATIF_DOMICILE]["path"]!= ''?$this->attachments[attachment_type::JUSTIFICATIF_DOMICILE]["path"]:$this->lng['etape2']['aucun-fichier-selectionne'])?>" />
            
                        <div class="file-holder">
                            <span class="btn btn-small">
                                <?=$this->lng['etape2']['parcourir']?>
                                <span class="file-upload">
                                    <input type="file" class="file-field" name="justificatif_domicile">
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
                        <input id="txt_rib" type="text" class="field required <?=($this->error_rib==true?'LV_invalid_field':'')?>" readonly="readonly" value="<?=($this->attachments[attachment_type::RIB]["path"]!= ''?$this->attachments[attachment_type::RIB]["path"]:$this->lng['etape2']['aucun-fichier-selectionne'])?>" />
            
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
	if($('#txt_ci').val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>'){
		form_ok = false;
		$("#txt_ci").removeClass("LV_valid_field");$("#txt_ci").addClass("LV_invalid_field");
	}
	else {$("#txt_ci").removeClass("LV_invalid_field");$("#txt_ci").addClass("LV_valid_field");}
	
	// rib
	if($('#txt_rib').val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>'){
		form_ok = false;
		$("#txt_rib").removeClass("LV_valid_field");$("#txt_rib").addClass("LV_invalid_field");
	}
	else {$("#txt_rib").removeClass("LV_invalid_field");$("#txt_rib").addClass("LV_valid_field");}
	
	
	// txt_justificatif_de_domicile
	if($('#txt_justificatif_de_domicile').val() == '<?=$this->lng['etape2']['aucun-fichier-selectionne']?>'){
		form_ok = false;
		$("#txt_justificatif_de_domicile").removeClass("LV_valid_field");$("#txt_justificatif_de_domicile").addClass("LV_invalid_field");
	}
	else {$("#txt_justificatif_de_domicile").removeClass("LV_invalid_field");$("#txt_justificatif_de_domicile").addClass("LV_valid_field");}
	
	if(form_ok == false){ event.preventDefault(); }
});
</script>