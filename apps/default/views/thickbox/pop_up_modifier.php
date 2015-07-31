<div class="popup" style="width: 1050px;height:550px;">
	<a href="#" class="popup-close">close</a>

	<div class="popup-head">
		<h2>Modifier</h2>
	</div>

	<div class="popup-cnt" style="padding:10px;">
		<div class="register-form" style="overflow:scroll; height:501px;">
                	<?
					if(isset($_SESSION['confirmation']['valid']) && $_SESSION['confirmation']['valid'] != '')
					{
                		echo '<p id="valid-stand-by" style="color: #3FBD5D;text-align:center;">'.$_SESSION['confirmation']['valid'].'</p>';
						unset($_SESSION['confirmation']['valid']);
						
						?><script>
						setTimeout(function() {
							$("#valid-stand-by").slideUp();
						}, 8000);
						</script><?
					}
					?>
					<form action="" method="post" id="form_update_project">

						<div class="row rel">
							<input type="text" name="montant" id="montant" title="<?=$this->lng['create-project']['montant']?>" value="<?=($this->projects->amount != 0?number_format($this->projects->amount,2,'.',' '):$this->lng['create-project']['montant'])?>" class="field field-large required euro-field" data-validators="Presence&amp;Numericality" onkeyup="lisibilite_nombre(this.value,this.id);">
							<select name="duree" id="duree" class="field field-large <?=($this->projects->period != 0?'':'required')?> custom-select">
								<option <?=($this->projects->period == 0?'selected':'')?> value="0"><?=$this->lng['create-project']['duree']?></option>
                                <option value="0"><?=$this->lng['etape1']['duree']?></option>
                                <option <?=($this->projects->period == '12'?'selected':'')?> value="12">12 mois</option>
                                <option <?=($this->projects->period == '24'?'selected':'')?> value="24">24 mois</option>
                                <option <?=($this->projects->period == '36'?'selected':'')?> value="36">36 mois</option>
                                <option <?=($this->projects->period == '48'?'selected':'')?> value="48">48 mois</option>
                                <option <?=($this->projects->period == '60'?'selected':'')?> value="60">60 mois</option>
                                <option <?=($this->projects->period == '1000000'?'selected':'')?> value="1000000">je ne sais pas</option>
							</select>
						</div><!-- /.row -->

						<div class="row">
							

							<input type="text" name="project-title" id="project-title" value="<?=($this->projects->title != ''?$this->projects->title:$this->lng['create-project']['titre-projet'])?>" title="<?=$this->lng['create-project']['titre-projet']?>" class="field field-mega required" data-validators="Presence">
						</div><!-- /.row -->

						<div class="row">
							<textarea name="credit-objective" id="credit-objective" class="field field-mega required" title="<?=$this->lng['create-project']['objectif-du-credit']?>" data-validators="Presence" cols="30" rows="10"><?=($this->projects->objectif_loan != ''?$this->projects->objectif_loan:$this->lng['create-project']['objectif-du-credit'])?></textarea>
						</div><!-- /.row -->

						<div class="row">

							<textarea name="presentation" id="presentation" class="field field-mega required" title="<?=$this->lng['create-project']['presentation-de-la-societe']?>" data-validators="Presence" cols="30" rows="10"><?=($this->projects->presentation_company!=''?$this->projects->presentation_company:$this->lng['create-project']['presentation-de-la-societe'])?></textarea>
						</div><!-- /.row -->

						<div class="row">

							<textarea name="moyen" id="moyen" class="field field-mega required" title="<?=$this->lng['create-project']['moyen-de-remboursement-prevu']?>" data-validators="Presence" cols="30" rows="10"><?=($this->projects->means_repayment!=''?$this->projects->means_repayment:$this->lng['create-project']['moyen-de-remboursement-prevu'])?></textarea>
						</div><!-- /.row -->

						<span class="form-caption"><?=$this->lng['create-project']['champs-obligatoires']?></span>
						<div class="form-foot row row-cols">
							
							<div class="col">
                            	<input type="hidden" name="send_form_update_project" />
								<button class="btn" type="submit"><?=$this->lng['create-project']['valider']?><i class="icon-arrow-next"></i></button>
							</div><!-- /.col -->
						</div><!-- /.form-foot foot-cols -->

					</form>
				</div>
	</div>
	<!-- /popup-cnt -->

</div>

