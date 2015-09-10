<!--#include virtual="ssi-header.shtml"  -->
		<div class="main">
			<div class="shell">

				<?=$this->fireView('../blocs/depot-de-dossier')?>
				
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
                
				<p><?=$this->lng['etape1']['contenu']?></p>

				<div class="register-form">
					<h1>Etape 1 - encore utliséé??? </h1>
					<form action="" method="post" id="form_etape_1">

						<div class="row rel">
							<input type="text" name="montant" id="montant" title="<?=$this->lng['etape1']['montant']?>" value="<?=$this->lng['etape1']['montant']?>" class="field field-large required euro-field" data-validators="Presence&amp;Numericality, { maximum:<?=$this->sommeMax?> }&amp;Numericality, { minimum:<?=$this->sommeMin?> }" onkeyup="lisibilite_nombre(this.value,this.id);">
							<select name="duree" id="duree" class="field field-large required custom-select">
                                <option value="0"><?=$this->lng['etape1']['duree']?></option>
								<?foreach($this->dureePossible as $duree):?>
									<option value="<?=$duree?>"><?=$duree?> mois</option>
								<?endforeach?>
                            </select>
							<span class="field-caption"><?=$this->lng['etape1']['maximum']?></span>

						</div><!-- /.row -->

						<div class="row">
							<div class="form-choose radio_comptables">
								<span class="title"><?=$this->lng['etape1']['exercices-comptables']?></span>
								
                                <div class="radio-holder">
									<label for="comptables-oui"><?=$this->lng['etape1']['oui']?></label>
									<input type="radio" class="custom-input" name="comptables" id="comptables-oui" value="1">
								</div><!-- /.radio-holder -->

								<div class="radio-holder">
									<label for="comptables-non"><?=$this->lng['etape1']['non']?></label>
									<input type="radio" class="custom-input" name="comptables" id="comptables-non" value="0">
								</div><!-- /.radio-holder -->
                                
							</div><!-- /.form-choose -->
						</div><!-- /.row -->

						<div class="row">
							<input type="text" name="siren" id="siren" title="<?=$this->lng['etape1']['siren']?>" value="<?=$this->lng['etape1']['siren']?>" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9, maximum: 9}" class="field required">
                            <span class="field-caption"><?=$this->lng['etape1']['info-siren']?></span>
						</div><!-- /.row -->

						<span class="form-caption"><?=$this->lng['etape1']['champs-obligatoires']?></span>
						<div class="form-foot row row-cols centered">
                        	<input type="hidden" name="send_form_etape_1" />
							<button class="btn" type="submit"><?=$this->lng['etape1']['suivant']?><i class="icon-arrow-next"></i></button>
						</div><!-- /.form-foot foot-cols -->

					</form>
				</div><!-- /.register-form -->
                <?
				}
				?>
			</div><!-- /.shell -->
		</div><!-- /.main -->
<!--#include virtual="ssi-footer.shtml"  -->

<script>
$( "#form_etape_1" ).submit(function( event ) {
	var radio = true;
	
	if($('input[type=radio][name=comptables]:checked').length)
	{
		$('.radio_comptables').css('color','#727272');
	}
	else
	{
		$('.radio_comptables').css('color','#C84747');
		radio = false
	}
	
	if(radio == false)
	{
		event.preventDefault();	
	}
});
</script>