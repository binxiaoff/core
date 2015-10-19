<?
if($this->projects_status->status >= 60)
{
	$this->dateRest = $this->lng['preteur-projets']['termine'];
}
else
{
	?>
	<script type="text/javascript">
		var cible = new Date('<?=$this->mois_jour?>, <?=$this->annee?> <?=$this->heureFinFunding?>:00');
		var letime = parseInt(cible.getTime() / 1000, 10);
		setTimeout('decompte(letime,"val")', 500);
	</script>
	<?
}
?>


<!--#include virtual="ssi-header-login.shtml"  -->
		<div class="main">
			<div class="shell">
				<div class="section-c clearfix">
					<div class="page-title clearfix">
						<h1 class="left"><?=$this->lng['preteur-projets']['decouvrez-les']?> <?=$this->nbProjects?> <?=$this->lng['preteur-projets']['projets-en-cours']?></h1>
						<?php /*?><nav class="nav-tools left">
							<ul>
                            	<?
                            	if($this->positionProject['previous'] != '')
								{
									?><li><a class="prev notext" href="<?=$this->lurl?>/projects_emprunteur/detail/<?=$this->positionProject['previous']?>">arrpw</a></li><?
								}
								if($this->positionProject['previous'] != '' && $this->positionProject['next'] != '')
								{
								?>
								<li><a class="view notext" href="<?=$this->lurl?>/projects_emprunteur">view</a></li>
								<?
								}
								if($this->positionProject['next'] != '')
								{
									?><li><a class="next notext" href="<?=$this->lurl?>/projects_emprunteur/detail/<?=$this->positionProject['next']?>">arrow</a></li><?
								}
								?>
							</ul>
						</nav><?php */?>
					</div>

                   <?
				if($this->upload_pouvoir == true)
				{
				?>
				<p id="reponse_pouvoir" style="color:green;text-align:center;"><?=$this->lng['projects']['reponse-pouvoir']?></p>
				<script>
				setTimeout(function() {
					  $("#reponse_pouvoir").slideUp();
				}, 5000);
				</script>
				<?
				}
				?>

					<h2><?=$this->projects->title?></h2>
					<div class="content-col left">
						<div class="project-c">
							<div class="top clearfix">
								<p class="left multi-line" style="margin-top:0px;">
									<em><?=$this->companies->name?></em>
									<?
									// si projet pas terminé
                                    if($this->projects_status->status == 50)
									{
                                   		?><strong class="green-span"><i class="icon-clock-green"></i><?=$this->lng['preteur-projets']['reste']?> <span id="val"><?=$this->dateRest?></span></strong>, <?
									}
									// sinon il est terminé
									else
									{
										?><strong class="red-span"><span id="val"><?=$this->dateRest?></span></strong><?
									}
									?>

                                    <?=$this->lng['preteur-projets']['le']?> <?=$this->date_retrait?> <?=$this->lng['preteur-projets']['a']?> <?=$this->heure_retrait?>h</p>

							</div>
							<div class="main-project-info clearfix">
								<?
								if($this->projects->photo_projet != '')
								{
									?><div class="img-holder borderless left">
										<img src="<?= $this->surl ?>/images/dyn/projets/169/<?= $this->projects->photo_projet ?>" alt="<?=$this->projects->photo_projet?>">
										<?
										if($this->projects->lien_video != '')
										{
											?><a class="link" target="_blank" href="<?=$this->projects->lien_video?>"><?=$this->lng['preteur-projets']['lancer-la-video']?></a><?
										}
										?>
									</div><?
								}
								?>
								<div class="info left">
									<h3><?=$this->companies->name?></h3>
									<?=($this->companies->city!=''?'<p><i class="icon-place"></i>'.$this->lng['preteur-projets']['localisation'].' : '.$this->companies->city.'</p>':'')?>
									<?=($this->companies->sector!=''?'<p>'.$this->lng['preteur-projets']['secteur'].' : '.$this->companies->sector.'</p>':'')?>
									<ul class="stat-list">
										<li>
											<span class="i-holder"><i class="icon-calendar tooltip-anchor" data-placement="right" data-original-title="<?=$this->lng['preteur-projets']['info-periode']?>"></i></span>
											<?=($this->projects->period==1000000?$this->lng['preteur-projets']['je-ne-sais-pas']:'<span>'.$this->projects->period.'</span> <br />'.$this->lng['preteur-projets']['mois'])?>
										</li>
										<li>
											<span class="i-holder"><i class="icon-gauge tooltip-anchor" data-placement="right" data-original-title="<?=$this->lng['preteur-projets']['info-note']?>"></i></span>
											<div class="cadreEtoiles"><div class="etoile <?=$this->lNotes[$this->companies->risk]?>"></div></div>
										</li>

										<li>
											<span class="i-holder"><i class="icon-graph tooltip-anchor" data-placement="right" data-original-title="<?=$this->lng['preteur-projets']['info-taux-moyen']?>"></i></span>
											<?
											if($this->CountEnchere > 0)
											{
												?><span><?=number_format(($this->projects_status->status==60||$this->projects_status->status==80)?$this->AvgLoans:$this->avgRate, 1, ',', ' ').' %'?></span><?
											}
											else
											{
												?><span><?=$this->projects->target_rate.($this->projects->target_rate == '-'?'':' %')?></span><?
											}
											?>
										</li>
									</ul>
								</div>
							</div>
							<nav class="tabs-nav">
								<ul>
									<?
									if($this->projects_status->status == 50) //funding
									{
										?><li class="active"><a href="#"><?=$this->lng['preteur-projets']['carnet-dordres']?></a></li>
										<li><a href="#"><?=$this->lng['preteur-projets']['presentation']?></a></li><?
									}
									else
									{
										?><li class="active"><a href="#"><?=$this->lng['preteur-projets']['presentation']?></a></li><?
									}
									?>

									<li><a href="#"><?=$this->lng['preteur-projets']['comptes']?></a></li>
								</ul>
							</nav>

							<div class="tabs">
								<?
								if($this->projects_status->status == 50) //funding
								{
									?>
									<div class="tab tc" id="bids">
										<?
										if(count($this->lEnchere)>0)
										{
											?>
											<table class="table orders-table">
												<tr>
													<th width="125"><span id="triNum">N°<i class="icon-arrows"></i></span></th>
													<th width="180">
														<span id="triTx"><?=$this->lng['preteur-projets']['taux-dinteret']?> <i class="icon-arrows"></i></span>
														<small><?=$this->lng['preteur-projets']['taux-moyen']?> : <?=number_format($this->avgRate, 1, ',', ' ')?> %</small>
													</th>
													<th width="214">
														<span id="triAmount"><?=$this->lng['preteur-projets']['montant']?> <i class="icon-arrows"></i></span>
														<small><?=$this->lng['preteur-projets']['montant-moyen']?> : <?=number_format($this->avgAmount/100, 2, ',', ' ')?> €</small>
													</th>
													<th width="101"><span id="triStatuts"><?=$this->lng['preteur-projets']['statuts']?> <i class="icon-arrows"></i></span></th>
												</tr>
												<?
												foreach($this->lEnchere as $key => $e)
												{
													if($this->lenders_accounts->id_lender_account == $e['id_lender_account'])$vous = true;
													else $vous = false;

													if($this->CountEnchere >= 12)
													{
														if($e['ordre'] <= 5 || $e['ordre'] > $this->CountEnchere-5)
														{
															?><tr <?=($vous==true?' class="enchereVousColor"':'')?>>
																<td><?=($vous==true?'<span class="enchereVous">'.$this->lng['preteur-projets']['vous'].' : &nbsp;&nbsp;&nbsp;'.$e['ordre'].'</span>':$e['ordre'])?></td>
																<td><?=number_format($e['rate'], 1, ',', ' ')?> %</td>
																<td><?=number_format($e['amount']/100, 0, ',', ' ')?> €</td>
																<td class="<?=($e['status']==1?'green-span':($e['status']==2?'red-span':''))?>"><?=$this->status[$e['status']]?></td>
															</tr><?
														}
														if($e['ordre'] == 6)
														{
															?><tr><td colspan="4" class="nth-table-row">...</td></tr><?
														}

													}
													else
													{
														?><tr <?=($vous==true?' class="enchereVousColor"':'')?>>
															<td><?=($vous==true?'<span class="enchereVous">'.$this->lng['preteur-projets']['vous'].' : &nbsp;&nbsp;&nbsp;'.$e['ordre'].'</span>':$e['ordre'])?></td>
															<td><?=number_format($e['rate'], 1, ',', ' ')?> %</td>
															<td><?=number_format($e['amount']/100, 0, ',', ' ')?> €</td>
															<td class="<?=($e['status']==1?'green-span':($e['status']==2?'red-span':''))?>"><?=$this->status[$e['status']]?></td>
														</tr><?
													}

												}
												?>
											</table>
											<?
											if($this->CountEnchere >= 12){
												?><a class="btn btn-large" id="displayAll"><?=$this->lng['preteur-projets']['voir-tout-le-carnet-dordres']?></a><?
											}
											else{
												?><div id="displayAll"></div><?
											}
											?>
											<script>
												$("#triNum").click(function() {
													$("#tri").html('ordre');
													$("#displayAll").click();
												});

												$("#triTx").click(function() {
													$("#tri").html('rate');
													$("#displayAll").click();
												});

												$("#triAmount").click(function() {
													$("#tri").html('amount');
													$("#displayAll").click();
												});

												$("#triStatuts").click(function() {
													$("#tri").html('status');
													$("#displayAll").click();
												});

												$("#displayAll").click(function() {

													var tri = $("#tri").html();
													var direction = $("#direction").html();
													$.post(add_url + '/ajax/displayAll', {id: <?=$this->projects->id_project?>,tri:tri,direction:direction}).done(function(data) {
														$('#bids').html(data)
													});
												});
											</script>
											<?
										}
										else
										{
											?><p><?=$this->lng['preteur-projets']['aucun-enchere']?></p><?
										}
										?>
									</div><!-- /.tab -->
									<div id="tri" style="display:none;">ordre</div>
									<div id="direction" style="display:none;">1</div>
									<?
								}
                                ?>
								<div class="tab">
									<article class="ex-article">
										<h3><a href="#"><?=$this->lng['preteur-projets']['qui-sommes-nous']?></a><i class="icon-arrow-down"></i></h3>
										<div class="article-entry">
											<p><?=$this->projects->presentation_company?></p>
										</div>
									</article>
									<article class="ex-article">
										<h3><a href="#"><?=$this->lng['preteur-projets']['pourquoi-ce-pret']?></a><i class="icon-arrow-down"></i></h3>
										<div class="article-entry">
											<p><?=$this->projects->objectif_loan?></p>
										</div>
									</article>
									<article class="ex-article">
										<h3><a href="#"><?=$this->lng['preteur-projets']['pourquoi-pouvez-vous-nous-faire-confiance']?></a><i class="icon-arrow-down"></i></h3>
										<div class="article-entry">
											<p><?=$this->projects->means_repayment?></p>
										</div>
									</article>
								</div><!-- /.tab -->

								<div class="tab">
									<div class="statistic-tables year-nav clearfix">
										<ul class="right">
											<li><div class="annee"><?=$this->anneeToday[1]?></div></li>
											<li><div class="annee"><?=$this->anneeToday[2]?></div></li>
											<li><div class="annee"><?=$this->anneeToday[3]?></div></li>
										</ul>
									</div>

									<div class="statistic-table">
										<table>
											<tr>
												<th colspan="4"><?=$this->lng['preteur-projets']['compte-de-resultats']?></th>
											</tr>
											<tr>
												<td class="intitule"><?=$this->lng['preteur-projets']['chiffe-daffaires']?></td>
                                                <?
												for($i=1;$i<=3;$i++)
												{
													echo '<td class="sameSize" style="text-align:right;">'.number_format($this->lBilans[$this->anneeToday[$i]]['ca'], 0, ',', ' ').' €</td>';
												}
												?>
											</tr>
											<tr>
												<td class="intitule"><?=$this->lng['preteur-projets']['resultat-brut-dexploitation']?></td>
												<?
												for($i=1;$i<=3;$i++)
												{
													echo '<td class="sameSize" style="text-align:right;">'.number_format($this->lBilans[$this->anneeToday[$i]]['resultat_brute_exploitation'], 0, ',', ' ').' €</td>';
												}
												?>
											</tr>
											<tr>
												<td class="intitule"><?=$this->lng['preteur-projets']['resultat-dexploitation']?></td>
												<?
												for($i=1;$i<=3;$i++)
												{
													echo '<td class="sameSize" style="text-align:right;">'.number_format($this->lBilans[$this->anneeToday[$i]]['resultat_exploitation'], 0, ',', ' ').' €</td>';
												}
												?>
											</tr>
                                            <tr>
												<td class="intitule"><?=$this->lng['preteur-projets']['investissements']?></td>
												<?
												for($i=1;$i<=3;$i++)
												{
													echo '<td class="sameSize" style="text-align:right;">'.number_format($this->lBilans[$this->anneeToday[$i]]['investissements'], 0, ',', ' ').' €</td>';
												}
												?>
											</tr>
										</table>
									</div>

									<div class="statistic-table">
										<table>
											<tr>
												<th><?=$this->lng['preteur-projets']['bilan']?></th>
											</tr>
											<tr>
												<td class="inner-table" colspan="4">
													<table>
														<tr><th colspan="4"><?=$this->lng['preteur-projets']['actif']?></th></tr>
														<tr>
															<td class="intitule"><?=$this->lng['preteur-projets']['immobilisations-corporelles']?></td>
                                                            <?
															for($i=0;$i<3;$i++)
															{
                                                            	echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['immobilisations_corporelles'], 0, ',', ' ').' €</td>';
															}
															?>
														</tr>
														<tr>
															<td class="intitule"><?=$this->lng['preteur-projets']['immobilisations-incorporelles']?></td>
															<?
															for($i=0;$i<3;$i++)
															{
                                                            	echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['immobilisations_incorporelles'], 0, ',', ' ').' €</td>';
															}
															?>
														</tr>
														<tr>
															<td class="intitule"><?=$this->lng['preteur-projets']['immobilisations-financieres']?></td>
															<?
															for($i=0;$i<3;$i++)
															{
                                                            	echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['immobilisations_financieres'], 0, ',', ' ').' €</td>';
															}
															?>
														</tr>
														<tr>
															<td class="intitule"><?=$this->lng['preteur-projets']['stocks']?></td>
															<?
															for($i=0;$i<3;$i++)
															{
                                                            	echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['stocks'], 0, ',', ' ').' €</td>';
															}
															?>
														</tr>
                                                        <tr>
															<td class="intitule"><?=$this->lng['preteur-projets']['creances-clients']?></td>
															<?
															for($i=0;$i<3;$i++)
															{
                                                            	echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['creances_clients'], 0, ',', ' ').' €</td>';
															}
															?>
														</tr>
                                                        <tr>
															<td class="intitule"><?=$this->lng['preteur-projets']['disponibilites']?></td>
															<?
															for($i=0;$i<3;$i++)
															{
                                                            	echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['disponibilites'], 0, ',', ' ').' €</td>';
															}
															?>
														</tr>
                                                        <tr>
															<td class="intitule"><?=$this->lng['preteur-projets']['valeurs-mobilieres-de-placement']?></td>
															<?
															for($i=0;$i<3;$i++)
															{
                                                            	echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['valeurs_mobilieres_de_placement'], 0, ',', ' ').' €</td>';
															}
															?>
														</tr>

														<tr class="total-row">
															<td class="intitule"><?=$this->lng['preteur-projets']['total-bilan-actifs']?></td>
															<?
															for($i=1;$i<=3;$i++)
															{
                                                            	echo '<td class="sameSize" style="text-align:right;">'.number_format($this->totalAnneeActif[$i], 0, ',', ' ').' €</td>';
															}
															?>
														</tr>
													</table>
												</td>
											</tr>
											<tr>
												<td class="inner-table" colspan="4">
													<table>
														<tr><th colspan="4"><?=$this->lng['preteur-projets']['passif']?></th></tr>
														<tr>
															<td class="intitule"><?=$this->lng['preteur-projets']['capitaux-propres']?></td>
															<?
															for($i=0;$i<3;$i++)
															{
                                                            	echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['capitaux_propres'], 0, ',', ' ').' €</td>';
															}
															?>
														</tr>
														<tr>
															<td class="intitule"><?=$this->lng['preteur-projets']['provisions-pour-risques-charges']?></td>
															<?
															for($i=0;$i<3;$i++)
															{
                                                            	echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['provisions_pour_risques_et_charges'], 0, ',', ' ').' €</td>';
															}
															?>
														</tr>
                                                        <tr>
															<td class="intitule"><?=$this->lng['preteur-projets']['amortissement-sur-immo']?></td>
															<?
															for($i=0;$i<3;$i++)
															{
                                                            	echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['amortissement_sur_immo'], 0, ',', ' ').' €</td>';
															}
															?>
														</tr>
														<tr>
															<td class="intitule"><?=$this->lng['preteur-projets']['dettes-financieres']?></td>
															<?
															for($i=0;$i<3;$i++)
															{
                                                            	echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['dettes_financieres'], 0, ',', ' ').' €</td>';
															}
															?>
														</tr>
														<tr>
															<td class="intitule"><?=$this->lng['preteur-projets']['dettes-fournisseurs']?></td>
															<?
															for($i=0;$i<3;$i++)
															{
                                                            	echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['dettes_fournisseurs'], 0, ',', ' ').' €</td>';
															}
															?>
														</tr>
                                                        <tr>
															<td class="intitule"><?=$this->lng['preteur-projets']['autres-dettes']?></td>
															<?
															for($i=0;$i<3;$i++)
															{
                                                            	echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['autres_dettes'], 0, ',', ' ').' €</td>';
															}
															?>
														</tr>
														<tr class="total-row">
															<td class="intitule"><?=$this->lng['preteur-projets']['total-bilan-passifs']?></td>
															<?
															for($i=1;$i<=3;$i++)
															{
                                                            	echo '<td class="sameSize" style="text-align:right;">'.number_format($this->totalAnneePassif[$i], 0, ',', ' ').' €</td>';
															}
															?>
														</tr>
													</table>
												</td>
											</tr>
										</table>

									</div>



								</div><!-- /.tab -->

							</div>
						</div>
					</div>
                    <?
					// project fundé
					if($this->projects_status->status == 60 || $this->projects_status->status == 80)
					{
						?>
						<div class="sidebar right">
							<aside class="widget widget-info">
								<div class="widget-top">
									<?=$this->lng['preteur-projets']['projet-finance-a-100']?>
								</div>
								<div class="widget-body">
									<div class="article">
										<p><?=$this->lng['preteur-projets']['ce-projet-est-integralement-finance-par']?> <strong class="pinky-span"> <?=number_format($this->NbPreteurs,0, ',', ' ')?> <?=$this->lng['preteur-projets']['preteur']?><?=($this->NbPreteurs>1?'s':'')?></strong> <br /><?=$this->lng['preteur-projets']['au-taux-de']?><strong class="pinky-span"> <?=number_format($this->AvgLoans,1, ',', ' ')?> %</strong> <br /><?=$this->lng['preteur-projets']['en']?> <?=($this->interDebutFin['day']>0?$this->interDebutFin['day'].' jours ':'')?><?=($this->interDebutFin['hour']>0?$this->interDebutFin['hour'].' heures ':'')?> <?=$this->lng['preteur-projets']['et']?> <?=$this->interDebutFin['minute']?> <?=$this->lng['preteur-projets']['minutes']?></p>

									</div>
                                    <div class="widget-cat">
                                        <a style="display:block;" class="btn darker popup-link"  href="<?=$this->lurl?>/thickbox/pop_up_modifier/<?=$this->projects->id_project?>">Modifier</a>
                                    </div>
                                    <?
									/*if($this->projects_pouvoir->exist($this->projects->id_project,'id_project') == false)
									{
									?>
                                    <br />
                                    <div class="widget-cat">
                                        <a style="display:block;" class="btn darker popup-link"  href="<?=$this->lurl?>/thickbox/pop_up_upload_pouvoir/<?=$this->projects->id_project?>">Uploader pouvoir</a>
                                    </div>
                                    <?
									}*/
									?>
								</div>

							</aside>
						</div>
						<?
                    }


					// Funding KO
					elseif($this->projects_status->status == 70)
					{
						?>
						<div class="sidebar right">
							<aside class="widget widget-info">
								<div class="widget-top" style="line-height: 36px;">
									<?=$this->lng['preteur-projets']['projet-na-pas-pu-etre-finance-a-100']?>
								</div>
								<div class="widget-body">
									<div class="article">

										<p>Merci à tous</p>
									</div>
                                    <div class="widget-cat">
                                        <a style="display:block;" class="btn darker popup-link"  href="<?=$this->lurl?>/thickbox/pop_up_modifier/<?=$this->projects->id_project?>">Modifier</a>
                                    </div>
								</div>
							</aside>
						</div>
						<?
                    }
					elseif($this->projects_status->status == 75)
					{
						?>
							<div class="sidebar right">
								<aside class="widget widget-info">
									<div class="widget-top">
										<?=$this->lng['preteur-projets']['projet-pret-rejete-titre']?>
									</div>
									<div class="widget-body">
										<div class="article">

											<p><?=$this->lng['preteur-projets']['projet-pret-rejete']?></p>
										</div>
									</div>
								</aside>
							</div>
							<?
                    }
					// rejeté
					elseif($this->projects_status->status == 30)
					{
						?>
						<div class="sidebar right">
							<aside class="widget widget-info">
								<div class="widget-top" style="line-height: 36px;">
									Projet rejeté
								</div>
								<div class="widget-body">
									<div class="article">

										<p>Merci à tous</p>
									</div>
                                    <div class="widget-cat">
                                        <a style="display:block;" class="btn darker popup-link"  href="<?=$this->lurl?>/thickbox/pop_up_modifier/<?=$this->projects->id_project?>">Modifier</a>
                                    </div>
								</div>
							</aside>
						</div>
						<?
                    }
                    // en funding
                    else
                    {
						?>
						<div class="sidebar right">
							<aside class="widget widget-price">
								<div class="widget-top">
									<i class="icon-pig"></i>
									<?=number_format($this->projects->amount, 0, ',', ' ')?> €
								</div>
								<div class="widget-body">
                                    <div class="widget-cat progress-cat clearfix">
                                        <div class="prices clearfix">
                                            <span class="price less">
                                                <strong><?=number_format($this->payer,$this->decimales, ',', ' ')?> €</strong>
                                                <?=$this->lng['preteur-projets']['de-pretes']?>
                                            </span>
                                            <i class="icon-arrow-gt"></i>
                                            <span class="price">
                                                <strong><?=number_format($this->resteApayer,$this->decimales, ',', ' ')?> €</strong>
                                                <?=$this->lng['preteur-projets']['restent-a-preter']?>
                                            </span>
                                        </div>

                                        <div class="progressBar" data-percent="<?=number_format($this->pourcentage,$this->decimalesPourcentage, '.', '')?>">
                                            <div><span></span></div>
                                        </div>
                                    </div>

                                    <?php /*?><div class="widget-cat">
                                        <p>Le solde du projet a été atteint. Voulez vous terminer la periode de funding ?</p>
                                        <br />
                                        <form action="" method="post">
                                        <input type="hidden" name="id_project" value="<?=$this->projects->id_project?>" />
                                        <button type="submit" name="preter" class="btn btn-medium"><?=$this->lng['preteur-projets']['terminer']?></button>
                                        </form>
                                    </div><?php */?>
                                    <div class="widget-cat">
                                        <a style="display:block;" class="btn darker popup-link"  href="<?=$this->lurl?>/thickbox/pop_up_modifier/<?=$this->projects->id_project?>">Modifier</a>
                                        </form>
                                    </div>
								</div>
							</aside>
                        </div>
						<?
                    }
                    ?>
				</div>
			</div>
		</div>

<!--#include virtual="ssi-footer.shtml"  -->