<!--#include virtual="ssi-header.shtml"  -->
		<div class="main">
			<div class="shell">

				<?=$this->fireView('../blocs/depot-de-dossier')?>

				<p><?=$this->lng['etape4']['contenu']?></p>

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
					<form action="" method="post" id="form_etape_4">
						<div class="row">
							<p class="inline-text"><?=$this->lng['etape4']['date-du-dernier-bilan-certifie']?></p>
							<div style="margin-right:20px;float: left;" >
                            <select name="jour" id="jour" class="field-mini custom-select <?=($this->date_dernier_bilan_jour != 0?'':'required')?>">
                            	<option value="0"><?=$this->lng['etape4']['jour']?></option>
                            	<?
								for($i=1;$i<=31;$i++)
								{
									if(strlen($i)<2) $numjour = '0'.$i;
									else $numjour = $i;
									?><option <?=($this->date_dernier_bilan_jour==$i?'selected':'')?> value="<?=$numjour?>"><?=$i?></option><?
								}
								?>
                            </select>
                            </div>
                            <span id="lemois" style="margin-right:20px;float: left;">
                            <select name="mois" id="mois" class="field-mini <?=($this->date_dernier_bilan_mois != 0?'':'required')?> custom-select">
								<option value="0"><?=$this->lng['etape4']['mois']?></option>
								<?
								foreach($this->dates->tableauMois['fr'] as $k => $mois)
								{
									if(strlen($k)<2) $numMois = '0'.$k;
									else $numMois = $k;
									
									if($k > 0) echo '<option '.($this->date_dernier_bilan_mois == $numMois?'selected':'').' '.($numMois==11?'style=""':'').' value="'.$numMois.'">'.$mois.'</option>';
								}
								?>
							</select>
                            </span>
							<select  name="annee" id="annee" class="field-mini <?=($this->date_dernier_bilan_annee != '0000'?'':'required')?> custom-select">
								<option value="0000"><?=$this->lng['etape4']['annee']?></option>
								<?
								for($i=2008;$i<=date('Y');$i++)
								{
									echo '<option '.($this->date_dernier_bilan_annee == $i?'selected':'').' value="'.$i.'">'.$i.'</option>';
								}
								?>
							</select>
						</div><!-- /.row -->
						<script>
						$( "#annee" ).change(function() {
							var val = { 
								annee : $(this).val(),
								mois :  $('#mois').val()
							}
							$.post(add_url + '/ajax/dernier_bilan', val).done(function(data) {
								
								if(data != 'nok')
								{
									$("#lemois").html(data);
								}
							});
						});
						</script>
						<table class="form-table sticked-table">
							<thead>
								<tr>
									<th width="100%"></th>
                                    <?
									foreach($this->lBilans as $b)
									{
										?><th><?=$b['date']?></th><?
									}
									?>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>
										<i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="<?=$this->lng['etape4']['info-chiffe-daffaires']?>"></i>
										<?=$this->lng['etape4']['chiffe-daffaires']?>
									</td>
									
                                    <?
									$a = 1;
									for($i=0;$i<5;$i++)
									{
										?>
										<td <?=($i==0?'class="first-field-cell"':'')?>>
											<div class="field-holder">
												<input type="text" name="ca-<?=$i?>" id="table-field-<?=$a?>" class="field field-tiny euro-field" <?=($i<3?'style="background-color:#E6E6E6;"':'')?> data-validators="Numericality" value="<?=($this->lBilans[$i]['ca']==0?'':number_format($this->lBilans[$i]['ca'],0,'.',' '))?>" onkeyup="lisibilite_nombre(this.value,this.id);">
											</div><!-- /.field-holder -->
										</td>
										<?
										$a++;
									}
									?>
								</tr>
								<tr>
									<td>
										<i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="<?=$this->lng['etape4']['info-resultat-brut-dexploitation']?>"></i>
                                        <?=$this->lng['etape4']['resultat-brut-dexploitation']?>
									</td>
									<?
									for($i=0;$i<5;$i++)
									{
										?>
										<td <?=($i==0?'class="first-field-cell"':'')?>>
											<div class="field-holder">
												<input type="text" name="resultat_brute_exploitation-<?=$i?>" id="table-field-<?=$a?>" class="field field-tiny euro-field" <?=($i<3?'style="background-color:#E6E6E6;"':'')?> data-validators="Numericality" value="<?=($this->lBilans[$i]['resultat_brute_exploitation']==0?'':number_format($this->lBilans[$i]['resultat_brute_exploitation'],0,'.',' '))?>" onkeyup="lisibilite_nombre(this.value,this.id);">
											</div><!-- /.field-holder -->
										</td>
										<?
										$a++;
									}
									?>
								</tr>
								<tr>
									<td>
										<i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="<?=$this->lng['etape4']['info-resultat-dexploitation']?>"></i>
										<?=$this->lng['etape4']['resultat-dexploitation']?>
									</td>
									<?
									for($i=0;$i<5;$i++)
									{
										?>
										<td <?=($i==0?'class="first-field-cell"':'')?>>
											<div class="field-holder">
												<input type="text" name="resultat_exploitation-<?=$i?>" id="table-field-<?=$a?>" class="field field-tiny euro-field" <?=($i<3?'style="background-color:#E6E6E6;"':'')?> data-validators="Numericality" value="<?=($this->lBilans[$i]['resultat_exploitation']==0?'':number_format($this->lBilans[$i]['resultat_exploitation'],0,'.',' '))?>" onkeyup="lisibilite_nombre(this.value,this.id);">
											</div><!-- /.field-holder -->
										</td>
										<?
										$a++;
									}
									?>
								</tr>
								<tr>
									<td>
										<i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="<?=$this->lng['etape4']['info-investissements']?>"></i>
										<?=$this->lng['etape4']['investissements']?>
									</td>
									<?
									for($i=0;$i<5;$i++)
									{
										?>
										<td <?=($i==0?'class="first-field-cell"':'')?>>
											<div class="field-holder">
												<input type="text" name="investissements-<?=$i?>" id="table-field-<?=$a?>" class="field field-tiny euro-field" <?=($i<3?'style="background-color:#E6E6E6;"':'')?> data-validators="Numericality" value="<?=($this->lBilans[$i]['investissements']==0?'':number_format($this->lBilans[$i]['investissements'],0,'.',' '))?>" onkeyup="lisibilite_nombre(this.value,this.id);">
											</div><!-- /.field-holder -->
										</td>
										<?
										$a++;
									}
									?>
								</tr>
							</tbody>
						</table>


						<table class="form-table">
							<tbody>
								<tr>
									<td width="100%">
										<i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="<?=$this->lng['etape4']['info-encours-actuel-de-la-dette-financiere']?>"></i>
										<?=$this->lng['etape4']['encours-actuel-de-la-dette-financiere']?>
									</td>
									<td>
										<div class="field-holder">	
											<input type="text" name="encours_actuel_dette_fianciere" id="table-field-21" class="field field-xx-large euro-field" data-validators="Numericality" value="<?=($this->companies_details->encours_actuel_dette_fianciere==0?'':number_format($this->companies_details->encours_actuel_dette_fianciere,0,'.',' '))?>" onkeyup="lisibilite_nombre(this.value,this.id);">
										</div><!-- /.field-holder -->
									</td>
								</tr>
								<tr>
									<td>
										<i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="<?=$this->lng['etape4']['info-remboursements-a-venir-cette-annee']?>"></i>
										<?=$this->lng['etape4']['remboursements-a-venir-cette-annee']?>
									</td>
									<td>
										<div class="field-holder">	
											<input type="text" name="remb_a_venir_cette_annee" id="table-field-22" class="field field-xx-large euro-field" data-validators="Numericality" value="<?=($this->companies_details->remb_a_venir_cette_annee==0?'':number_format($this->companies_details->remb_a_venir_cette_annee,0,'.',' '))?>" onkeyup="lisibilite_nombre(this.value,this.id);">
										</div><!-- /.field-holder -->
									</td>
								</tr>
								<tr>
									<td>
										<i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="<?=$this->lng['etape4']['info-remboursements-a-venir-lannee-prochaine']?>"></i>
										<?=$this->lng['etape4']['remboursements-a-venir-lannee-prochaine']?>
									</td>
									<td>
										<div class="field-holder">	
											<input type="text" name="remb_a_venir_annee_prochaine" id="table-field-23" class="field field-xx-large euro-field" data-validators="Numericality" value="<?=($this->companies_details->remb_a_venir_annee_prochaine==0?'':number_format($this->companies_details->remb_a_venir_annee_prochaine,0,'.',' '))?>" onkeyup="lisibilite_nombre(this.value,this.id);">
										</div><!-- /.field-holder -->
									</td>
								</tr>
								<tr>
									<td>
										<i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="<?=$this->lng['etape4']['info-tresorerie-disponible-actuellement']?>"></i>
										<?=$this->lng['etape4']['tresorerie-disponible-actuellement']?>
									</td>
									<td>
										<div class="field-holder">	
											<input type="text" name="tresorie_dispo_actuellement" id="table-field-24" class="field field-xx-large euro-field" data-validators="Numericality" value="<?=($this->companies_details->tresorie_dispo_actuellement==0?'':number_format($this->companies_details->tresorie_dispo_actuellement,0,'.',' '))?>" onkeyup="lisibilite_nombre(this.value,this.id);">
										</div><!-- /.field-holder -->
									</td>
								</tr>
								<tr>
									<td>
										<i class="icon-help tooltip-anchor field-help-before" data-placement="right" title="<?=$this->lng['etape4']['info-autres-demandes-de-financements']?>"></i>
										<?=$this->lng['etape4']['autres-demandes-de-financements']?>
									</td>
									<td>
										<div class="field-holder">	
											<input type="text" name="autre_demandes_financements_prevues" id="table-field-25" class="field field-xx-large euro-field" data-validators="Numericality" value="<?=($this->companies_details->autre_demandes_financements_prevues ==0?'':number_format($this->companies_details->autre_demandes_financements_prevues,0,'.',' '))?>" onkeyup="lisibilite_nombre(this.value,this.id);">
										</div><!-- /.field-holder -->
									</td>
								</tr>
							</tbody>
						</table>

						<div class="row">
							<textarea name="precisions" id="textarea" class="field field-mega" title="<?=$this->lng['etape4']['vous-souhaitez-apporter-des-precisions']?>" cols="30" rows="10"><?=($this->companies_details->precisions!=''?$this->companies_details->precisions:$this->lng['etape4']['vous-souhaitez-apporter-des-precisions'])?></textarea>
						</div><!-- /.row -->

						<span class="form-caption"><?=$this->lng['etape4']['champs-obligatoires']?></span>
						<br /><br />
                        <div class="form-foot row row-cols">
							<div class="col">
								<button onclick="$('#form_etape_4').attr('action', '<?=$this->lurl.'/depot_de_dossier/etape4/'.$this->clients->hash.'/stand-by'?>');" type="submit" class="btn btn-warning"><?=$this->lng['etape4']['stand-by']?></button>
							</div><!-- /.col -->
							<div class="col">
                            	<input type="hidden" name="send_form_etape_4" />
								<button class="btn" type="submit"><?=$this->lng['etape4']['suivant']?><i class="icon-arrow-next"></i></button>
							</div><!-- /.col -->
						</div><!-- /.form-foot foot-cols -->

					</form>
				</div><!-- /.register-form -->
			</div><!-- /.shell -->
		</div><!-- /.main -->
<!--#include virtual="ssi-footer.shtml"  -->