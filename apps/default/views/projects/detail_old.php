<?
/*$this->mois_jour = 'January 3';
$this->heureFinFunding = '13:02';

echo 'mois jour : '.$this->mois_jour.'<br>';
echo 'Années : '.$this->annee.'<br>';
echo 'heure de fin : '.$this->heureFinFunding;
*/

if($this->projects_status->status != 50 || $this->page_attente == true)
{
	$this->dateRest = $this->lng['preteur-projets']['termine'];
}
else
{
?>

<script type="text/javascript">

	var cible = new Date('<?=$this->mois_jour?>, <?=$this->annee?> <?=$this->heureFinFunding?>:00');
	var letime = parseInt((cible.getTime()) / 1000, 10);
	setTimeout('decompteProjetDetail(letime,"val","<?=$this->lurl?>/projects/detail/<?=$this->params[0]?>")', 500);	

	
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
						<nav class="nav-tools left">
							<ul>
                            	<?
                            	if($this->positionProject['previous'] != '')
								{
									?><li><a class="prev notext" href="<?=$this->lurl?>/projects/detail/<?=$this->positionProject['previous']?>">arrpw</a></li><?
								}
								?>
								<li><a class="view notext" href="<?=$this->lurl?>/<?=($_SESSION['page_projet'] == 'projets_fo'?$this->tree->getSlug(4,$this->language):'projects')?>">view</a></li>
								<?
								if($this->positionProject['next'] != '')
								{
									?><li><a class="next notext" href="<?=$this->lurl?>/projects/detail/<?=$this->positionProject['next']?>">arrow</a></li><?
								}
								?>
							</ul>
						</nav>
					</div>
                    
                    <?
					if(isset($_SESSION['messFinEnchere']) && $_SESSION['messFinEnchere'] != false)
					{
                    	?><div class="messFinEnchere" style="float:right;color:#C84747;margin-top:18px;"><?=$_SESSION['messFinEnchere']?></div><?
						unset($_SESSION['messFinEnchere']);
						?>
						<script type="text/javascript">
							setTimeout(function() {
								  $('.messFinEnchere').slideUp();
							}, 5000);
							
							
                        </script>
                        <?
					}
					elseif(isset($_SESSION['messPretOK']) && $_SESSION['messPretOK'] != false)
					{
                    	?><div class="messPretOK" style="float:right;color:#40B34F;margin-top:18px;"><?=$_SESSION['messPretOK']?></div><?
						unset($_SESSION['messPretOK']);
						?>
						<script type="text/javascript">
							setTimeout(function() {
								  $('.messPretOK').slideUp();
							}, 5000);
							
							
                        </script>
                        <?
					}
					?>
                    
					<h2><?=$this->projects->title?></h2>
					<div class="content-col left">
						<div class="project-c">
							<div class="top clearfix">
								<a class="fav-btn right <?=$this->favori?>" id="fav" onclick="favori(<?=$this->projects->id_project?>,'fav',<?=$this->clients->id_client?>,'detail');"><?=($this->favori=='active'?$this->lng['preteur-projets']['retirer-de-mes-favoris']:$this->lng['preteur-projets']['ajouter-a-mes-favoris'])?> <i></i></a>
                                <p class="left multi-line">
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
									
                                    <?=$this->lng['preteur-projets']['le']?> <?=strtolower($this->date_retrait)?> <?=$this->lng['preteur-projets']['a']?> <?=$this->heure_retrait?>h</p>
								
							</div>
							<div class="main-project-info clearfix">
								<?
								if($this->projects->photo_projet != '')
								{
									?><div class="img-holder borderless left">
										<img src="<?=$this->photos->display($this->projects->photo_projet,'photos_projets','photo_projet_moy')?>"  alt="<?=$this->projects->photo_projet?>">
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
									<?=($this->companies->sector!=''?'<p>'.$this->lng['preteur-projets']['secteur'].' : '.$this->lSecteurs[$this->companies->sector].'</p>':'')?>
									<ul class="stat-list">
										<li>
											<span class="i-holder"><i class="icon-calendar tooltip-anchor" data-placement="right" data-original-title="<?=$this->lng['preteur-projets']['info-periode']?>"></i></span>
											<?=($this->projects->period==1000000?$this->lng['preteur-projets']['je-ne-sais-pas']:'<span>'.$this->projects->period.'</span> <br />'.$this->lng['preteur-projets']['mois'])?>
										</li>
										<li>
											<span class="i-holder"><i class="icon-gauge tooltip-anchor" data-placement="right" data-original-title="<?=$this->lng['preteur-projets']['info-note']?>"></i></span>
											<div class="cadreEtoiles"><div class="etoile <?=$this->lNotes[$this->projects->risk]?>"></div></div>
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
									// en funding
									if($this->projects_status->status == 50)
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
                                    <?
									if($this->projects_status->status == 60 || $this->projects_status->status == 80)
									{
										if(isset($_SESSION['client']) && $_SESSION['client']['status_pre_emp'] == 1)
										{
											?><li><a href="#"><?=$this->lng['preteur-projets']['suivi-projet']?></a></li><?
										}
									}
									?>
								</ul>
							</nav>

							<div class="tabs">
								<?
								
								// en funding
								if($this->projects_status->status == 50)
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
															?><tr><td colspan="4" class="nth-table-row displayAll" style="cursor:pointer;">...</td></tr><?
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
												?><a class="btn btn-large displayAll" ><?=$this->lng['preteur-projets']['voir-tout-le-carnet-dordres']?></a><?
											}
											else{
												?><div class="displayAll"></div><?
											}
											?>
											<script>
												$("#triNum").click(function() {
													$("#tri").html('ordre');
													$(".displayAll").click();
												});
											
												$("#triTx").click(function() {
													$("#tri").html('rate');
													$(".displayAll").click();
												});
												
												$("#triAmount").click(function() {
													$("#tri").html('amount');
													$(".displayAll").click();
												});
												
												$("#triStatuts").click(function() {
													$("#tri").html('status');
													$(".displayAll").click();
												});
												
												$(".displayAll").click(function() {
													
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
											?><p>Aucune enchère</p><?
										}
										?>
									</div><!-- /.tab -->
									<div id="tri" style="display:none;">ordre</div>
									<div id="direction" style="display:none;">1</div>
									<?
								}
                                ?>
								<div class="tab">
                                
                                <?
								/*if(!$this->clients->checkAccess())
								//if($this->restriction_ip != true)
								{
									?>
									<div>
									<?=$this->lng['preteur-projets']['contenu-presentation']?>
									</div>
									<br />
									<div style="text-align:center;" >
										<a href="<?=$this->lng['preteur-projets']['cta-lien-presentation']?>" class="btn btn-medium"><?=$this->lng['preteur-projets']['cta-presentation']?></a>
									</div>
									<?	
								}
								else
								{*/
								?>
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
                                <?	
								//}
								?>
								</div><!-- /.tab -->

								<div class="tab">
                                
                                	<?
									if(!$this->clients->checkAccess())
									//if($this->restriction_ip != true)
									{
										?>
										<div>
										<?=$this->lng['preteur-projets']['contenu-comptes-financiers']?>
										</div>
										<br />
										<div style="text-align:center;" >
											<a href="<?=$this->lng['preteur-projets']['cta-lien-comptes-financiers']?>" class="btn btn-medium"><?=$this->lng['preteur-projets']['cta-comptes-financiers']?></a>
										</div>
										<?	
									}
									else
									{
									?>
                                  
                                    
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

									<?
									}
									?>

								</div><!-- /.tab -->
								
                                <?
								// project fundé
								if($this->projects_status->status == 60 || $this->projects_status->status == 80)
								{
								?>
								<div class="tab">
									<div class="article">
										<p><?=$this->lng['preteur-projets']['vous-avez-prete']?> <strong class="pinky-span"><?=number_format($this->bidsvalid['solde'],0, ',', ' ')?> €</strong></p>
										<p><strong class="pinky-span"><?=number_format($this->sumRemb,2, ',', ' ')?> €</strong> <?=$this->lng['preteur-projets']['vous-ont-ete-rembourses-il-vous-reste']?> <strong class="pinky-span"><?=number_format($this->sumRestanteARemb,2, ',', ' ')?> €</strong> <?=$this->lng['preteur-projets']['a-percevoir-sur-une-periode-de']?> <strong class="pinky-span"><?=$this->nbPeriod?> <?=$this->lng['preteur-projets']['mois']?></strong></p>
									</div>
								</div><!-- /.tab -->
								<?
								}
								?>
							</div>
						</div>
					</div>
                    <?

					
					// si on est pas equinoa et que l'on n'est pas connecté
					
					//if($this->restriction_ip == false || !$this->clients->checkAccess()) // <- diff client et equinoa
					if(!$this->clients->checkAccess())
					//if($_SERVER['REMOTE_ADDR'] != '93.26.42.99' || !$this->clients->checkAccess()) // <- diff equinoa
					{
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
											<p><?=$this->lng['preteur-projets']['merci-a-tous']?></p>
										</div>
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
											<p><?=$this->lng['preteur-projets']['ce-projet-a-ete-finance-a']?><?=number_format($this->pourcentage,$this->decimalesPourcentage, ',', '')?>%</p>
											<p><?=$this->lng['preteur-projets']['merci-a-tous']?></p>
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
                        <div class="right" style="width:285px;color:#B10366;">
                        <?=$this->lng['preteur-projets']['bloc-right']?>
                        </div>
                        <?
						}
					}
					else
					{
						if($this->page_attente == true)
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
									</div>
                                    <div class="widget-body">
										<div class="article">
											
											<p style="padding:8px;"><?=$this->lng['preteur-projets']['periode-denchere-du-projet-terminee']?><br /></p>
										</div>
									</div>
								</aside>
							</div>
							<?
						}
						// project fundé
						elseif($this->projects_status->status == 60 || $this->projects_status->status == 80)
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
											<p><?=$this->lng['preteur-projets']['vous-lui-avez-prete']?> <strong class="pinky-span"><?=number_format($this->bidsvalid['solde'],0, ',', ' ')?> €</strong> <br /><?=$this->lng['preteur-projets']['au-taux-moyen-de']?> <strong class="pinky-span"><?=number_format($this->AvgLoansPreteur,1, ',', ' ')?> %</strong></p>
											<p><?=$this->lng['preteur-projets']['merci-a-tous']?></p>
										</div>
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
											<p><?=$this->lng['preteur-projets']['ce-projet-a-ete-finance-a']?><?=number_format($this->pourcentage,$this->decimalesPourcentage, ',', '')?>%</p>
											<p><?=$this->lng['preteur-projets']['merci-a-tous']?></p>
										</div>
									</div>
								</aside>
							</div>
							<?
						}
						// Prêt refusé
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
						// en funding
						else
						{
							// Si profile non validé par unilend
							if($this->clients_status->status < 60)
							{
								?>
                                <div class="sidebar right">
                                    <aside class="widget widget-price">
										<div class="widget-top">
											<i class="icon-pig"></i>
											<?=number_format($this->projects->amount, 0, ',', ' ')?> €
										</div>
                                        <div class="widget-body">
                                            <div class="article">
                                                <p style="padding:20px;"><?=$this->lng['preteur-projets']['completude-message']?>
</p>
                                                
                                            </div>
                                        </div>
                                    </aside>
                                </div>
                                <?
							}
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
										<form action="" method="post">
											<div class="widget-cat progress-cat clearfix">
												<div class="prices clearfix">
													<span class="price less">
														<strong><?=number_format($this->payer,$this->decimales, ',', ' ')?> €</strong>
														<?=$this->lng['preteur-projets']['de-pretes']?>
													</span>
													<i class="icon-arrow-gt"></i>
													<?
													if($this->soldeBid >= $this->projects->amount)
													{
														?>
														<p style="font-size:14px;"><?=$this->lng['preteur-projets']['vous-pouvez-encore-preter-en-proposant-une-offre-de-pret-inferieure-a']?> <?=number_format($this->txLenderMax,1, ',', ' ')?>%</p>
														<?
													}
													else
													{
														?>
														<span class="price">
															<strong><?=number_format($this->resteApayer,$this->decimales, ',', ' ')?> €</strong>
															<?=$this->lng['preteur-projets']['restent-a-preter']?>
														</span>
														<?
													}
													?>
												</div>
		
												<div class="progressBar" data-percent="<?=number_format($this->pourcentage,$this->decimalesPourcentage, '.', '')?>">
													<div><span></span></div>
												</div>
											</div>
                                            <?
											if($this->bidsEncours['nbEncours']>0)
											{
												?>
												<div class="widget-cat">
													<style>
														#plusOffres{cursor:pointer;}
														#lOffres{display:none;}
														#lOffres ul{list-style: none outside none;padding-left: 14px;font-size:15px;}
													</style>
													<h4 id="plusOffres"><?=$this->lng['preteur-projets']['offre-en-cours']?> <i class="icon-plus"></i></h4>
													<p style="font-size:14px;"><?=$this->lng['preteur-projets']['vous-avez']?> : <br /><?=$this->bidsEncours['nbEncours']?> <?=$this->lng['preteur-projets']['offres-en-cours-pour']?> <?=number_format($this->bidsEncours['solde'],0, ',', ' ')?> €</p>
													
													<div id="lOffres">
														<ul>
															<?
															foreach($this->lBids as $b)
															{
																?>
																<li>Offre de <?=number_format($b['amount']/100,0, ',', ' ')?> € au taux de <?=number_format($b['rate'],1, ',', ' ')?>%</li>
																<?
															}
															?>
														</ul>
													</div>
													
												</div>
												<?
											}
											
											// a modifier pour quand mon met preteur/emprunteur
											if($this->clients->status_pre_emp != 2)
											{
											?>
		
											<div class="widget-cat">
												<h4><?=$this->lng['preteur-projets']['faire-une-offre']?></h4>
												<div class="row">
													<label><?=$this->lng['preteur-projets']['je-prete-a']?></label>
													<select name="tx_p" id="tx_p" class="custom-select field-hundred">
														<option value="<?=$this->projects->target_rate?>"><?=$this->projects->target_rate?></option>
														<?
														if($this->soldeBid >= $this->projects->amount)
														{
															if(number_format($this->txLenderMax,1, '.', ' ') > '10.0')
															{
																?><option <?=($this->projects->target_rate == '10.0'?'selected':'')?> value="10.0">10,0%</option><?
															}
															for($i=9;$i>=4;$i--)
															{
																for($a=9;$a>=0;$a--)
																{
																	if(number_format($this->txLenderMax,1, '.', ' ') > $i.'.'.$a)
																	{
																	?><option <?=($this->projects->target_rate == $i.'.'.$a?'selected':'')?> value="<?=$i.'.'.$a?>"><?=$i.','.$a?>%</option><?
																	}
																}
															}
															
														}
														else
														{
															?><option <?=($this->projects->target_rate == '10.0'?'selected':'')?> value="10.0">10,0%</option><?
															for($i=9;$i>=4;$i--)
															{
																for($a=9;$a>=0;$a--)
																{
																	?><option <?=($this->projects->target_rate == $i.'.'.$a?'selected':'')?> value="<?=$i.'.'.$a?>"><?=$i.','.$a?>%</option><?
																}
															}
															
														}
														?>
													</select>
		
												</div>
												<div class="row last-row">
													<label><?=$this->lng['preteur-projets']['la-somme-de']?></label>
													<input name="montant_p" id="montant_p" type="text" title="<?=$this->lng['preteur-projets']['montant-exemple']?>" value="<?=$this->lng['preteur-projets']['montant-exemple']?>" class="field" onkeyup="lisibilite_nombre(this.value,this.id);"/> <span style="margin-left: -15px;position: relative;top: 4px;">€</span>
												</div>
                                               
												<p class="laMensual" style="font-size:14px;display:none;"><?=$this->lng['preteur-projets']['soit-un-remboursement-mensuel-de']?></p>
                                                <div class="laMensual" style="font-size:14px;width:245px;display:none;">
                                                	<div style="text-align:center;"><span id="mensualite">xx</span> €</div>
                                                </div>
                                                <br />
                                                
												<a style="width:76px; display:block;margin:auto;" href="<?=$this->lurl?>/thickbox/pop_valid_pret/<?=$this->projects->id_project?>" class="btn btn-medium popup-link"><?=$this->lng['preteur-projets']['preter']?></a> 
											</div>
                                            <?
											}
											?>
										</form>
									</div>
								</aside>
							</div>
							<?
							}
						}
					}
                    ?>
				</div>
			</div>
		</div>
		
<!--#include virtual="ssi-footer.shtml"  -->

<script type="text/javascript" >
	
$("#plusOffres").click(function() {
	$("#lOffres").slideToggle();
});

$("#montant_p").blur(function() {
	var montant = $("#montant_p").val();
	var tx = $("#tx_p").val();
	var form_ok = true;
	
	if(tx == '-')
	{
		form_ok = false;
	}
	else if(montant < <?=$this->pretMin?>)
	{
		form_ok = false;
	}
	
	if(form_ok == true)
	{
		var val = { 
		montant: montant,
		tx: tx,
		nb_echeances : <?=$this->projects->period?>
	}
		$.post(add_url + '/ajax/load_mensual', val).done(function(data) {
			
			if(data != 'nok')
			{
				
				$(".laMensual").slideDown();
				$("#mensualite").html(data);
			}
		});
	}
	
});
$("#tx_p").change(function() {
	var montant = $("#montant_p").val();
	var tx = $("#tx_p").val();
	var form_ok = true;
	
	if(tx == '-')
	{
		form_ok = false;
	}
	else if(montant < <?=$this->pretMin?>)
	{
		form_ok = false;
	}
	
	if(form_ok == true)
	{
		var val = { 
		montant: montant,
		tx: tx,
		nb_echeances : <?=$this->projects->period?>
	}
		$.post(add_url + '/ajax/load_mensual', val).done(function(data) {
			
			if(data != 'nok')
			{
				
				$(".laMensual").slideDown();
				$("#mensualite").html(data);
			}
		});
	}
	
});
</script>
