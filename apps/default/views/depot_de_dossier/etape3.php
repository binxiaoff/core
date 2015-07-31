<!--#include virtual="ssi-header.shtml"  -->
		<div class="main">
			<div class="shell">

				<?=$this->fireView('../blocs/depot-de-dossier')?>

				<p><?=$this->lng['etape3']['contenu']?></p>

				<div class="register-form">
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
					<form action="" method="post" id="form_etape_3">

						<div class="row rel">
							<input type="text" name="montant" id="montant" title="<?=$this->lng['etape3']['montant']?>" value="<?=($this->projects->amount != 0?number_format($this->projects->amount,2,'.',' '):$this->lng['etape3']['montant'])?>" class="field field-large required euro-field" data-validators="Presence&amp;Numericality" onkeyup="lisibilite_nombre(this.value,this.id);">
							<select name="duree" id="duree" class="field field-large <?=($this->projects->period != 0?'':'required')?> custom-select">
								<option <?=($this->projects->period == 0?'selected':'')?> value="0"><?=$this->lng['etape3']['duree']?></option>
                                <option value="0"><?=$this->lng['etape1']['duree']?></option>
                                <option <?=($this->projects->period == '24'?'selected':'')?> value="24">24 mois</option>
                                <option <?=($this->projects->period == '36'?'selected':'')?> value="36">36 mois</option>
                                <option <?=($this->projects->period == '48'?'selected':'')?> value="48">48 mois</option>
                                <option <?=($this->projects->period == '60'?'selected':'')?> value="60">60 mois</option>
                                <option <?=($this->projects->period == '1000000'?'selected':'')?> value="1000000">je ne sais pas</option>
							</select>
						</div><!-- /.row -->

						<div class="row">
							<i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="<?=$this->lng['etape3']['info-titre-projet']?>"></i>

							<input type="text" name="project-title" id="project-title" value="<?=($this->projects->title != ''?$this->projects->title:$this->lng['etape3']['titre-projet'])?>" title="<?=$this->lng['etape3']['titre-projet']?>" class="field field-mega required" data-validators="Presence">
						</div><!-- /.row -->

						<div class="row">
							<i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="<?=$this->lng['etape3']['info-objectif-du-credit']?>"></i>

							<textarea name="credit-objective" id="credit-objective" class="field field-mega required" title="<?=$this->lng['etape3']['objectif-du-credit']?>" data-validators="Presence" cols="30" rows="10"><?=($this->projects->objectif_loan != ''?$this->projects->objectif_loan:$this->lng['etape3']['objectif-du-credit'])?></textarea>
						</div><!-- /.row -->

						<div class="row">
							<i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="<?=$this->lng['etape3']['info-presentation-de-la-societe']?>"></i>

							<textarea name="presentation" id="presentation" class="field field-mega required" title="<?=$this->lng['etape3']['presentation-de-la-societe']?>" data-validators="Presence" cols="30" rows="10"><?=($this->projects->presentation_company!=''?$this->projects->presentation_company:$this->lng['etape3']['presentation-de-la-societe'])?></textarea>
						</div><!-- /.row -->

						<div class="row">
							<i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="<?=$this->lng['etape3']['info-moyen-de-remboursement-prevu']?>"></i>

							<textarea name="moyen" id="moyen" class="field field-mega required" title="<?=$this->lng['etape3']['moyen-de-remboursement-prevu']?>" data-validators="Presence" cols="30" rows="10"><?=($this->projects->means_repayment!=''?$this->projects->means_repayment:$this->lng['etape3']['moyen-de-remboursement-prevu'])?></textarea>
						</div><!-- /.row -->

						<span class="form-caption"><?=$this->lng['etape3']['champs-obligatoires']?></span>
						<div class="form-foot row row-cols">
							<div class="col">
								<button onclick="$('#form_etape_3').attr('action', '<?=$this->lurl.'/depot_de_dossier/etape3/'.$this->clients->hash.'/stand-by'?>');" type="submit" class="btn btn-warning"><?=$this->lng['etape3']['stand-by']?></button>
							</div><!-- /.col -->
							<div class="col">
                            	<input type="hidden" name="send_form_etape_3" />
								<button class="btn" type="submit"><?=$this->lng['etape3']['suivant']?><i class="icon-arrow-next"></i></button>
							</div><!-- /.col -->
						</div><!-- /.form-foot foot-cols -->

					</form>
				</div><!-- /.register-form -->
			</div><!-- /.shell -->
		</div><!-- /.main -->
<!--#include virtual="ssi-footer.shtml"  -->