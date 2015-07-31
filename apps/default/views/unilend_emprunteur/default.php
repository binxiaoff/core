<!--#include virtual="ssi-header-login.shtml"  -->
<div class="main">
    <div class="shell">
        <div class="section-c">
        	<?
			if($this->upload_mandat == true)
			{
			?>
			<p id="reponse_mandat" style="color:green;text-align:center;"><?=$this->lng['emprunteur-unilend']['reponse-mandat']?></p>
			<script>
			setTimeout(function() {
				  $("#reponse_mandat").slideUp();
			}, 5000);
			</script>
			<?
			}
			?>
			<?php /*?><div class="row" style="text-align:right;">
				<a href="<?=$this->lurl?>/thickbox/pop_up_upload_mandat/2" class="btn btn-mini popup-link"><?=$this->lng['emprunteur-unilend']['upload-mandat']?></a>
				<br /><br />
			</div><?php */?>
        
            <h2><?=$this->lng['emprunteur-unilend']['titre']?></h2>
            <p><?=$this->lng['emprunteur-unilend']['contenu']?></p>
            <p style="text-align:center; color:green;display:none;" class="reponse"><?=$this->lng['emprunteur-unilend']['modifications-enregistrees']?></p>
            <p style="text-align:center; color:#C84747;;display:none;" class="reponse_email"><?=$this->lng['emprunteur-unilend']['email-de-coordonnees-deja-utilise']?></p>
            <form action="" method="post" id="form_profile">
                <!-- societe-->
                    <!-- /.row -->
                    <div class="add-new-profile"> 
                    	<div class="row">
                            <div class="form-choose">
                                <span class="title"><?=$this->lng['emprunteur-unilend']['civilite']?></span>
                                <div class="radio-holder">
                                    <label for="female"><?=$this->lng['emprunteur-unilend']['madame']?></label>
                                    <input <?=($this->clients->civilite=='Mme'?'checked="checked"':'')?> type="radio" class="custom-input" name="genre" id="female" value="Mme">
                                </div><!-- /.radio-holder -->
                                <div class="radio-holder">
                                    <label for="male"><?=$this->lng['emprunteur-unilend']['monsieur']?></label>
                                    <input type="radio" class="custom-input" name="genre" id="male" <?=($this->clients->civilite=='M.'?'checked="checked"':'')?> value="M.">
                                </div><!-- /.radio-holder -->
                            </div><!-- /.form-choose -->
                        </div><!-- /.row -->
                                   
                        <div class="row">
                            <input type="text" name="nom" title="<?=$this->lng['emprunteur-unilend']['nom']?>" value="<?=($this->clients->nom!=''?$this->clients->nom:$this->lng['emprunteur-unilend']['nom'])?>" id="nom" class="field field-large required" data-validators="Presence" onKeyup="noNumber(this.value,this.id)">
                            <input type="text" name="prenom" title="<?=$this->lng['emprunteur-unilend']['prenom']?>" value="<?=($this->clients->prenom!=''?$this->clients->prenom:$this->lng['emprunteur-unilend']['prenom'])?>" id="prenom" class="field field-large required" data-validators="Presence" onKeyup="noNumber(this.value,this.id)">
                        </div><!-- /.row -->
                        
                        <div class="row">
                            <input type="text" name="fonction" title="<?=$this->lng['emprunteur-unilend']['fonction']?>" value="<?=($this->clients->fonction!=''?$this->clients->fonction:$this->lng['emprunteur-unilend']['fonction'])?>" id="fonction" class="field field-large required" data-validators="Presence">
                            
                            <input type="text" name="phone" id="phone" value="<?=($this->clients->telephone!=''?$this->clients->telephone:$this->lng['emprunteur-unilend']['telephone'])?>" title="<?=$this->lng['emprunteur-unilend']['telephone']?>" class="field field-large" >
                            
                        </div><!-- /.row -->
                        
                        <div class="row">
                            <input type="text" name="email" title="<?=$this->lng['emprunteur-unilend']['email']?>" value="<?=($this->clients->email?$this->clients->email:$this->lng['emprunteur-unilend']['email'])?>" id="email" class="field field-large required" data-validators="Presence&amp;Email">
                            
                            <input type="text" name="conf_email" title="<?=$this->lng['emprunteur-unilend']['confirmation-email']?>" value="<?=($this->clients->email?$this->clients->email:$this->lng['emprunteur-unilend']['confirmation-email'])?>" id="conf_email" class="field field-large required" data-validators="Confirmation,{ match: 'email' }">
                        </div><!-- /.row -->
                        
                        <div class="row">
							<span class="pass-field-holder">
								<input type="password" name="pass" id="pass" title="<?=$this->lng['emprunteur-unilend']['mot-de-passe']?>" value="" class="field field-large required" data-validators="Presence">
							</span>
							
							<span class="pass-field-holder">
								<input type="password" name="pass2" id="pass2" title="<?=$this->lng['emprunteur-unilend']['confirmation-de-mot-de-passe']?>" value="" class="field field-large " data-validators="Confirmation,{ match: 'pass' }">
							</span>
						</div><!-- /.row -->
                        
                        
                        <div class="row">
                            <i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="" data-original-title="<?=$this->lng['emprunteur-unilend']['info-question-secrete']?>"></i>
                            <input type="text" id="secret-questionE" name="secret-questionE" title="<?=$this->lng['emprunteur-unilend']['question-secrete']?>" value="<?=($this->clients->secrete_question!=''?$this->clients->secrete_question:$this->lng['emprunteur-unilend']['question-secrete'])?>" class="field field-mega required" data-validators="Presence">
                        </div><!-- /.row -->

                        <div class="row">
                            <input type="text" id="secret-responseE" name="secret-responseE" title="<?=$this->lng['emprunteur-unilend']['reponse']?>" value="<?=$this->lng['emprunteur-unilend']['reponse']?>" class="field field-mega required" data-validators="Presence">
                        </div><!-- /.row -->
                        
                        
                    </div><!-- /.add-new-profile -->
                
                    
                 <!-- fin societe-->
                <span class="form-caption"><?=$this->lng['emprunteur-unilend']['champs-obligatoires']?></span>
                <div class="form-foot row row-cols centered">
                    <input type="hidden" name="send_form_profile" id="send_form_profile" value="">
                    <button class="btn btn-mega alone-btn" type="submit"><?=$this->lng['emprunteur-unilend']['valider-les-modifications']?><i class="icon-arrow-next"></i></button>
                </div><!-- /.form-foot foot-cols -->
            
            </form>
        </div>
    </div>
</div>
		
<!--#include virtual="ssi-footer.shtml"  -->
<?
if($this->form_ok == true)
{
	?><script>
	$(".reponse").slideDown('slow');
	setTimeout(function() {
		$(".reponse").slideUp('slow');
	}, 5000);
	</script><?
}
if($this->reponse_email == true)
{
	?><script>
	$(".reponse_email").slideDown('slow');
	setTimeout(function() {
		$(".reponse_email").slideUp('slow');
	}, 5000);
	</script><?
}
?>