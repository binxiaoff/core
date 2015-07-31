<div class="main">
    <div class="shell">
    	<?
		if(isset($this->params[0]) && $this->params[0] == 'nok')
		{
			echo $this->lng['etape1']['contenu-non-eligible'];
		}
		elseif(isset($_SESSION['error_pre_empr']))
		{
			echo $_SESSION['error_pre_empr'];
			unset($_SESSION['error_pre_empr']);	
		}
    	else
		{
			?>
			<div class="register-form">
				<form action="" method="post" id="form_depot_dossier" name="form_depot_dossier" enctype="multipart/form-data">
					<div class="row">
						<p>Identité de la société</p>
	
						<input type="text" name="raison-sociale" id="raison-sociale" title="<?=$this->lng['etape2']['raison-sociale']?>" value="<?=($this->companies->name != ''?$this->companies->name:$this->lng['etape2']['raison-sociale'])?>" class="field field-large required" data-validators="Presence">
	
						<input type="text" name="siren" id="siren" title="<?=$this->lng['etape1']['siren']?>" value="<?=($this->companies->siren != ''?$this->companies->siren:$this->lng['etape1']['siren'])?>" class="field field-large required" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9, maximum: 9}">
	
						<p class="text-right text-small" style="font-size: 11px; font-style:italic; padding-left:480px;"><?=$this->lng['etape1']['info-siren']?></p>
					</div><!-- /.row -->
	
					<div class="row">
						<div class="form-choose fixed radio_comptables">
							<span style="float:left; padding-right: 50px; line-height:16px;"><?=$this->lng['etape1']['exercices-comptables']?></span>
	
							<div class="radio-holder" style="width:40px;">
								<label for="comptables-oui"><?=$this->lng['etape1']['oui']?></label>
								
								<input <?=($this->companies->execices_comptables == 1?'checked':'')?> type="radio" class="custom-input" name="comptables" id="comptables-oui" value="1">
							</div><!-- /.radio-holder -->
	
							<div class="radio-holder" style="width:40px;">
								<label for="comptables-non"><?=$this->lng['etape1']['non']?></label>
								<input <?=($this->companies->execices_comptables == '0'?'checked':'')?> type="radio" class="custom-input" name="comptables" id="comptables-non" value="0">
							</div><!-- /.radio-holder -->
						</div><!-- /.form-choose -->
					</div><!-- /.row -->
					
					<div class="row">
						<p>Votre demande d’emprunt </p>
	
						<input type="text" name="montant" id="montant" title="<?=$this->lng['etape1']['montant']?>" value="<?=($this->projects->amount > 0?$this->projects->amount:$this->lng['etape1']['montant'])?>" class="field field-large required" data-validators="Presence&amp;Numericality, { maximum:<?=$this->sommeMax?> }&amp;Numericality, { minimum:<?=$this->sommeMin?> }" onkeyup="lisibilite_nombre(this.value,this.id);">
						
	
						<select name="duree" id="duree" class="field field-large required custom-select">
							<option value="0"><?=$this->lng['etape1']['duree']?></option>
						   
							<option <?=($this->projects->period == 24?'selected':'')?> value="24">24 mois</option>
							<option <?=($this->projects->period == 36?'selected':'')?> value="36">36 mois</option>
							<option <?=($this->projects->period == 48?'selected':'')?> value="48">48 mois</option>
							<option <?=($this->projects->period == 60?'selected':'')?> value="60">60 mois</option>
						</select>
						<span class="field-caption"><?=$this->lng['etape1']['maximum']?></span>
					</div><!-- /.row -->
	
					<div class="row">
						<p>Identité du représentant de la société</p>
	
						<input type="text" name="nom_representative" title="<?=$this->lng['etape2']['nom']?>" value="<?=($this->companies->nom_dirigeant!=''?$this->companies->nom_dirigeant:$this->lng['etape2']['nom'])?>" id="nom_representative" class="field field-large required" data-validators="Presence" onkeyup="noNumber(this.value,this.id);">
						<input type="text" name="prenom_representative" title="<?=$this->lng['etape2']['prenom']?>" value="<?=($this->companies->prenom_dirigeant!=''?$this->companies->prenom_dirigeant:$this->lng['etape2']['prenom'])?>" id="prenom_representative" class="field field-large required" data-validators="Presence" onkeyup="noNumber(this.value,this.id);">
					</div><!-- /.row -->
	
					<div class="row">
						<input type="text" name="email_representative" title="<?=$this->lng['etape2']['email']?>" value="<?=($this->companies->email_dirigeant!=''?$this->companies->email_dirigeant:$this->lng['etape2']['email'])?>" id="email_representative" class="field field-large required" data-validators="Presence&amp;Email">
						<input type="text" name="conf_email_representative" title="Confirmation Email*" value="<?=($this->conf_email_representative != ''?$this->conf_email_representative:'Confirmation Email*')?>" id="conf_email_representative" class="field field-large required" data-validators="Confirmation, { match: 'email_representative' }">
					</div><!-- /.row -->
	
					<div class="row">
						<input type="text" name="phone_representative" id="phone_representative" value="<?=($this->companies->phone_dirigeant!=''?$this->companies->phone_dirigeant:$this->lng['etape2']['telephone'])?>" title="<?=$this->lng['etape2']['telephone']?>" class="field field-large required" data-validators="Presence&amp;Numericality&amp;Length, {maximum: 10}&amp;Numericality, { minimum:10 }">
                        <input type="text" name="fonction_representative" title="<?=$this->lng['etape2']['fonction']?>" value="<?=($this->clients->fonction!=''?$this->clients->fonction:$this->lng['etape2']['fonction'])?>" id="fonction_representative" class="field field-large required" data-validators="Presence">
					</div><!-- /.row -->
	
					<div class="row">
						<div class="form-choose fixed">
							<span class="title"><?=$this->lng['etape2']['vous-etes']?></span>
	
							<div class="radio-holder-wrap radios-about" style="float: left; width:733px;">
								<div class="radio-holder" style="width:733px; padding-bottom: 10px;">
									<label for="radio1-1-about"><?=$this->lng['etape2']['dirigeant-entreprise']?></label>
									<input <?=(isset($_POST['send_form_depot_dossier'])?($this->companies->status_client == 1?'checked':''):'checked')?> type="radio" class="custom-input" name="radio1-about" id="radio1-1-about" value="1">
								</div><!-- /.radio-holder -->
	
								<div class="radio-holder" style="width:733px; padding-bottom: 10px;">
									<label for="radio1-3-about"><?=$this->lng['etape2']['conseil-externe-entreprise']?></label>
									<input <?=($this->companies->status_client == 3?'checked':'')?> type="radio" class="custom-input" name="radio1-about" id="radio1-3-about" value="3" data-condition="show:.identification">
								</div><!-- /.radio-holder -->
							</div><!-- /.radio-holder-wrap -->
						</div><!-- /.form-choose -->
					</div><!-- /.row -->
	
					<div class="about-sections">
						
						<div class="about-section">
							
						</div><!-- /.about-section -->
	
						<div class="about-section identification">
							<div class="row">
								<select name="autre" style="width:458px;" id="autre" class="field field-large custom-select required">
									<option value="0"><?=$this->lng['etape2']['external-consultant']?></option>
									<?
									foreach($this->conseil_externe as $k => $conseil_externe){
										?><option <?=($this->companies->status_conseil_externe_entreprise == $k+1?'selected':'')?> value="<?=$k+1?>" ><?=$conseil_externe?></option><?
									}
									?>
								</select>
	
								<input style="display:none;" type="text" name="autre-preciser" title="<?=$this->lng['etape2']['autre']?>" value="<?=($this->companies->preciser_conseil_externe_entreprise!=''?$this->companies->preciser_conseil_externe_entreprise:$this->lng['etape2']['autre'])?>" id="autre-preciser" class="field field-large">
							</div><!-- /.row -->
	
							<div class="row" >
								<p><?=$this->lng['etape2']['vos-coordonnees']?></p>
	
								<div class="form-choose fixed radio_sex">
									<span class="title"><?=$this->lng['etape2']['civilite']?></span>
									<div class="radio-holder">
										<label for="female"><?=$this->lng['etape2']['madame']?></label>
										<input type="radio" class="custom-input" name="sex" id="female"  value="Mme" <?=($this->clients->civilite=='Mme'?'checked="checked"':'')?>>
									</div><!-- /.radio-holder -->
	
									<div class="radio-holder">
										<label for="male"><?=$this->lng['etape2']['monsieur']?></label>
										<input type="radio" class="custom-input" name="sex" id="male"  value="M." <?=($this->clients->civilite=='M.'?'checked="checked"':'')?>>
									</div><!-- /.radio-holder -->
								</div><!-- /.form-choose -->
							</div><!-- /.row -->
	
							<div class="row">
								<input type="text" name="nom-famille" id="nom-famille" title="<?=$this->lng['etape2']['nom']?>" value="<?=($this->clients->nom!=''?$this->clients->nom:$this->lng['etape2']['nom'])?>" class="field field-large required" data-validators="Presence" onkeyup="noNumber(this.value,this.id);">
								<input type="text" name="prenom" id="prenom" title="<?=$this->lng['etape2']['prenom']?>" value="<?=($this->clients->prenom!=''?$this->clients->prenom:$this->lng['etape2']['prenom'])?>" class="field field-large required" data-validators="Presence" onkeyup="noNumber(this.value,this.id);">
							</div><!-- /.row -->
	
							<div class="row">
								<input type="text" name="email" id="email" title="<?=$this->lng['etape2']['email']?>" value="<?=($this->clients->email!=''?$this->clients->email:$this->lng['etape2']['email'])?>" class="field field-large required" data-validators="Presence&amp;Email" >
								<input type="text" name="conf_email" id="conf_email" title="<?=$this->lng['etape2']['confirmation-email']?>" value="<?=($this->conf_email!=''?$this->conf_email:$this->lng['etape2']['confirmation-email'])?>" class="field field-large required" data-validators="Confirmation,{ match: 'email' }" >
							</div><!-- /.row -->
	
							<div class="row">
								<input type="text" name="phone" id="phone" value="<?=($this->clients->telephone!=''?$this->clients->telephone:$this->lng['etape2']['telephone'])?>" title="<?=$this->lng['etape2']['telephone']?>" class="field field-large required" data-validators="Presence&amp;Numericality&amp;Length, {maximum: 10}&amp;Numericality, { minimum:10 }">
                                <input type="text" name="fonction" title="<?=$this->lng['etape2']['fonction']?>" value="<?=($this->clients->fonction!=''?$this->clients->fonction:$this->lng['etape2']['fonction'])?>" id="fonction" class="field field-large required" data-validators="Presence">
							</div><!-- /.row -->
						</div><!-- /.about-section -->
					</div><!-- /.about-sections -->
	
					<div class="row">
						<p>Documents financiers</p>
	
						<div class="uploader" data-file="1" style="width:460px; float: left;">
							<span style="display:block;">Dernière liasse fiscale*</span>
							<input type="text" class="field required" id="dernière-liasse-fiscale" data-validators="Presence" readonly="readonly" value="<?=$this->companies_details->fichier_derniere_liasse_fiscale?>">
							<div class="file-holder">
								<span class="btn btn-small" style="line-height:40px; padding: 0 15px; margin-top: 3px;">
									Parcourir<span class="file-upload">
										<input type="file" class="file-field" name="fichier1">
									</span>
								</span>
							</div>
							
						</div>
						<div class="uploader" data-file="1" style="width:460px; float:left; margin-left: 16px;">
							<span style="display:block;">Autre (facultatif)</span>
							<input type="text" class="field" readonly="readonly">
							<div class="file-holder">
								<span class="btn btn-small" style="line-height:40px; padding: 0 15px; margin-top: 3px;">
									Parcourir<span class="file-upload">
										<input type="file" class="file-field" name="fichier2">
									</span>
								</span>
							</div>
						</div>
					</div><!-- /.row -->
	
					<div class="row">
						<textarea name="comments" cols="30" rows="10" title="Toutes informations utiles permettant de mieux comprendre votre demande" id="comments" class="field field-mega"><?=($this->projects->comments!=''?$this->projects->comments:'Toutes informations utiles permettant de mieux comprendre votre demande')?></textarea>
					</div><!-- /.row -->
	
					<div class="row">
						<div class="cb-holder">
							<label class="check" for="accept-cgu">Je reconnais avoir pris connaissance <a style="color:#A1A5A7; text-decoration: underline;" class="check" target="_blank" href="<?=$this->lurl.'/'.$this->tree->getSlug($this->lienConditionsGenerales,$this->language)?>">des conditions générales de vente*</a></label>
							<input type="checkbox" class="custom-input required" name="accept-cgu" id="accept-cgu">
						</div><!-- /.cb-holder -->
					</div><!-- /.row -->
					
	
					<div class="form-foot row row-cols centered">
						<input type="hidden" name="send_form_depot_dossier" />
						<button class="btn" type="submit">Déposer son dossier<i class="icon-arrow-next"></i></button>
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
/*$('.radios-about .radio-holder').on('click', function() {
	$('.about-section').hide().eq($(this).index()).show();
});*/

<?
if($this->error_email_representative_exist == true)
{
	?>
	$("#email_representative").addClass('LV_invalid_field');
	$("#email_representative").removeClass('LV_valid_field');
	<?
}
elseif($this->error_email_exist == true)
{
	?>
	$("#email").addClass('LV_invalid_field');
	$("#email").removeClass('LV_valid_field');
	<?
}
?>

$(document).ready(function () {
  $('#conf_email_representative').bind('paste', function (e) {
	 e.preventDefault();
  });
   $('#conf_email').bind('paste', function (e) {
	 e.preventDefault();
  });
});

$('select#autre').on('change', function() {
	if ($('option:selected', this).val() == '3') {
		$('#autre-preciser').show();
	} else {
		$('#autre-preciser').hide();
	};
});

$('input.file-field').on('change', function(){
	var $self = $(this),
		val = $self.val()

	if( val.length != 0 || val != '' ){
		$self.closest('.uploader').find('input.field').val(val);
	};
});

$( "#form_depot_dossier" ).submit(function( event ){
	var radio = true;
	if($('input[type=radio][name=comptables]:checked').length){$('.radio_comptables').css('color','#727272');}
	else{$('.radio_comptables').css('color','#C84747');radio = false}
	
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