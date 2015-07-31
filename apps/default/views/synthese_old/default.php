<!--#include virtual="ssi-header-login.shtml"  -->
		<div class="main">
        	<?
			//unset($_SESSION['qs']);
			// ON CHECK si on a une question ou reponse
			if(in_array('',array($this->clients->secrete_question,$this->clients->secrete_reponse)) && $_SESSION['qs'] != date('d') || isset($_SESSION['qs_ok']) && $_SESSION['qs_ok'] == 'OK'){
				?>
					<script type="text/javascript">
						$.colorbox({ href:add_url+"/thickbox/pop_up_qs", opacity: 0.5, scrolling: false });
					</script>
				<?
				$_SESSION['qs'] = date('d');
			}
			?>
        
			<div class="shell">
				<div class="section-c dashboard clearfix">
					<div class="page-title clearfix">
						<h1 class="left"><?=$this->lng['preteur-synthese']['votre-tableau-de-bord']?></h1>
						<strong class="right">au <?=$this->dates->formatDateComplete(date('Y-m-d H:i:s'))?> à <?=date('H\hi')?></strong>
					</div>
                    
                    <!--------------------------------------------------->
                    <?
					// cgv
					/*if(!in_array($this->clients->id_client,array(1,12))){
						$this->accept_ok = true; // temporaire
					}*/
					//if(in_array($this->clients->id_client,array(1,12)))
					if($this->accept_ok == false){
						?>
						<div class="notification-primary">
							<div class="notification-head">
								<h3 class="notification-title"><?=$this->bloc_cgv['titre-242']?></h3><!-- /.notification-title -->
							</div><!-- /.notification-head -->
							
							<div class="notification-body">
                                <?
								// mise a jour cgv
								if($this->update_accept == true) 
									echo $this->bloc_cgv['content-2'];
								else 
									echo $this->bloc_cgv['content-1'];
								?>
								<div class="form-terms">
									<form action="" method="post">
										<div class="checkbox">
											<input type="checkbox" name="terms" id="terms" />
	
											<label for="terms"><a target="_blank" href="<?=$this->lurl.'/'.$this->tree->getSlug($this->lienConditionsGenerales,$this->language)?>"><?=$this->bloc_cgv['checkbox-cgv']?></a></label>      
										</div><!-- /.checkbox -->
	
										<div class="form-actions">
											<button type="button" id="cta_cgv" class="btn form-btn">
												<?=$this->bloc_cgv['cta-valider']?>
	
												<i class="ico-arrow"></i>
											</button>
										</div><!-- /.form-actions -->
									</form>
								</div><!-- /.form-terms -->
							</div><!-- /.notification-body -->
						</div><!-- /.notification-primary -->
						<script type="text/javascript">
						$( "#cta_cgv" ).click(function() {
							if($("#terms").is(':checked') == true){
								$.post( add_url+"/ajax/accept_cgv", { terms: $("#terms").val(), id_legal_doc: "<?=$this->lienConditionsGenerales?>" }).done(function( data ) {
									$(".notification-primary").fadeOut(); setTimeout(function() { $(".notification-primary").remove(); }, 1000);
								});
							}
							else{ $(".checkbox a").css('color','#c84747'); }
						});
						$( "#terms" ).change(function() { if($(this).is(':checked') == true){ $(".checkbox a").css('color','#727272');} });
						</script>
						<?
					}
					?>
                    <!------------------------------------------------------>
                    
                    <?
					if($this->nblFavP>0 && !isset($_SESSION['lFavP']) || $this->nblRejetB>0 || $this->nblRembB>0)
					{
					?>
					<div class="natification-msg">
						<h3><?=$this->lng['preteur-synthese']['notifications']?></h3>
						<ul>
                        	<?
							if($this->nblFavP>0)
							{
								?>
								<li><strong><?=$this->lng['preteur-synthese']['mes-favoris']?> :</strong>
									<ul>
									<?
									foreach($this->lFavP as $f)
									{
										$this->projects->get($f['id_project'],'id_project');
										$_SESSION['lFavP'] = true;
										?>
										<li>
										<?=($f['datediff']>0?'Plus que '.$f['datediff'].' jours':'Dernier jour')?> 
										
										<?=$this->lng['preteur-synthese']['pour-faire-une-offre-de-pret-sur-le-projet']?> <a href="<?=$this->lurl?>/projects/detail/<?=$this->projects->slug?>"><?=$f['title']?></a>.
										</li>
										<?
									}
									?>
									</ul>
								</li>
								<?
							}
							if($this->nblRejetB>0)
							{
								?>
								<li><strong><?=$this->lng['preteur-synthese']['encheres-rejetes']?> : </strong>
									<ul>
									<?
                                    foreach($this->lRejetB as $r)
                                    {
										$this->bids->get($r['id_bid'],'id_bid');
										$this->projects->get($r['id_project'],'id_project');
										
										if($this->bids->amount != $r['amount'])
										{
											$montant = ($this->bids->amount - $r['amount']);
											?><li><?=$this->lng['preteur-synthese']['attentions-votre-offre-de-pret-a']?><b> <?=number_format($this->bids->rate,2,',',' ')?></b><?=$this->lng['preteur-synthese']['pour-un-montant-de']?><b> <?=number_format($this->bids->amount/100,2,',',' ')?></b><?=$this->lng['preteur-synthese']['sur-le-projet']?> <a href="<?=$this->lurl?>/projects/detail/<?=$this->projects->slug?>"><?=$this->projects->title?></a><?=$this->lng['preteur-synthese']['a-ete-decoupe']?> <b><?=number_format($r['amount']/100,2,',',' ')?></b><?=$this->lng['preteur-synthese']['vous-ont-ete-rendu']?></li><?
										}
										else
										{
                                        ?><li><?=$this->lng['preteur-synthese']['attentions-votre-offre-de-pret-a']?><b> <?=number_format($this->bids->rate,2,',',' ')?></b><?=$this->lng['preteur-synthese']['pour-un-montant-de']?><b> <?=number_format($r['amount']/100,2,',',' ')?></b><?=$this->lng['preteur-synthese']['sur-le-projet']?> <a href="<?=$this->lurl?>/projects/detail/<?=$this->projects->slug?>"><?=$this->projects->title?></a><?=$this->lng['preteur-synthese']['nest-plus-recevable']?></li><?
										}
                                    }
                                    ?>
                                	</ul>
								</li>
								<?
							}
							if($this->nblRembB>0)
							{
							?>
                            <li><strong><?=$this->lng['preteur-synthese']['remboursements']?> : </strong>
                            	<ul>
									<?
                                    foreach($this->lRembB as $r)
                                    {
										$this->projects->get($r['id_project'],'id_project');
										
										?><li><?=$this->lng['preteur-synthese']['vous-venez-de-recevoir-un-remboursement-de']?> <b><?=number_format($r['amount']/100,2,',',' ')?></b><?=$this->lng['preteur-synthese']['pour-le-projet']?> <a href="<?=$this->lurl?>/projects/detail/<?=$this->projects->slug?>"><?=$this->projects->title?></a></li><?
									}
									?>
                            	</ul>
                            </li>
                            <?
							}
							?>
						</ul>
						<a class="esc-btn" href="#"></a>
					</div>
                    <?
					}
					?>

					<div class="col left">

						<div class="graphic-box">
							<header>
								<h2><?=$this->lng['preteur-synthese']['situation-de-votre-compte-unilend']?></h2>
								<p><?=$this->lng['preteur-synthese']['solde-de-mon-compte']?> :<strong> <?=number_format($this->solde,2,',',' ')?> €</strong></p>
							</header>
							<div class="body">
                            	<style>
												#leSolde,#leSoldePourcent,#sumBidsEncours,#sumBidsEncoursPourcent,#sumPrets,#sumPretsPourcent,#sumRembMontant,#nbLoan,#argentPrete,#argentRemb,#interets,#titlePrete,#titleArgentRemb,#titleInteretsRecu{display:none;}

								#cboxLoadedContent{margin-bottom:0px;}
								.popup{background-color:#E3E4E5;}

								
								</style>
								<?
								if($this->solde > 0 || $this->soldePourcent > 0 || $this->sumBidsEncoursPourcent > 0 || $this->sumPretsPourcent > 0)
								{
									// On met ca pour eviter les débordements
									if($this->solde >= 1000) $fondsdispo = str_replace(' ','<br>',$this->lng['preteur-synthese']['de-fond-disponible']);
									else $fondsdispo = $this->lng['preteur-synthese']['de-fond-disponible'];
								?>
								
                                
                                <span id="leSolde"><b><?=number_format($this->solde,2,',',' ')?> € <br /><?=$fondsdispo?></b></span>
                                <span id="leSoldePourcent"><?=number_format($this->soldePourcent,1,'.','')?></span>
                                
                                <span id="sumBidsEncours"><b><?=number_format($this->sumBidsEncours,2,',',' ')?> € <br /><?=$this->lng['preteur-synthese']['de-fond-bloques']?></b></span>
                                <span id="sumBidsEncoursPourcent"><?=number_format($this->sumBidsEncoursPourcent,1,'.','')?></span>
                                
                                <?php /*?><span id="sumPrets"><b><?=number_format($this->sumPrets,2,',',' ')?> € <br /><?=$this->lng['preteur-synthese']['pretes-a']?> <?=$this->nbLoan?> <?=$this->lng['preteur-synthese']['entreprise']?><?=($this->nbLoan>1?'s':'')?> <br /><?=$this->lng['preteur-synthese']['dont']?> <?=number_format($this->sumRembMontant,2,',','')?> <?=$this->lng['preteur-synthese']['de-recouvrement']?></b></span><?php */?>
                                
                                <span id="sumPrets"><b><?=number_format($this->sumRestanteARemb,2,',',' ')?> € <br /><?=$this->lng['preteur-synthese']['pretes-a']?> <?=$this->nbLoan?> <?=$this->lng['preteur-synthese']['entreprise']?><?=($this->nbLoan>1?'s':'')?><br />et restant à rembourser
                                <?
								
								if($this->sumRestanteARemb>0)
								{
									/*?><br /><?=$this->lng['preteur-synthese']['et']?> <?=number_format($this->sumRestanteARemb,2,',','')?> <?=$this->lng['preteur-synthese']['de-recouvrement']?><?*/
								}
								?>
                                </b></span>
                                
                                
                                <span id="sumPretsPourcent"><?=number_format($this->sumPretsPourcent,1,'.','')?></span>
                                
                               	
                                
								<div id="pie-chart"></div>
                                <?
								}
								?>
                                
                                
                                
							</div>
						</div>

						<div class="post-schedule">
							<h2><?=$this->lng['preteur-synthese']['encheres-en-cours']?> <span><?=($this->lProjetsBidsEncours!=false?count($this->lProjetsBidsEncours):0)?> <i class="icon-box-arrow"></i></span></h2>
							<div class="body">
								
                                <?
								if($this->lProjetsBidsEncours != false)
								{
									foreach($this->lProjetsBidsEncours as $f)
									{
										$this->companies->get($f['id_company'],'id_company');
										
										$this->projects_status->getLastStatut($f['id_project']);
										
										// date fin 21h a chaque fois
										$inter = $this->dates->intervalDates(date('Y-m-d H:i:s'),$f['date_retrait'].' '.$this->heureFinFunding.':00');
										if($inter['mois']>0) $dateRest = $inter['mois'].' '.$this->lng['preteur-projets']['mois'];
										else $dateRest = '';
										
										// dates pour le js

										$mois_jour = $this->dates->formatDate($f['date_retrait'],'F d');
										$annee = $this->dates->formatDate($f['date_retrait'],'Y');
										
										// la sum des encheres
										$soldeBid = $this->bids->getSoldeBid($f['id_project']);
										
										// solde payé
										$payer = $soldeBid;
										
										// Reste a payer
										$resteApayer = ($f['amount']-$soldeBid);
										
										$pourcentage = ((1-($resteApayer/$f['amount']))*100);
										
										$decimales = 2;
										$decimalesPourcentage = 2;
										
										if($soldeBid >= $f['amount'])
										{
											$payer = $f['amount'];
											$resteApayer = 0;
											$pourcentage = 100;
											$decimales = 0;
											$decimalesPourcentage = 0;
										}
										
										$CountEnchere = $this->bids->counter('id_project = '.$f['id_project']);
										//$avgRate = $this->bids->getAVG($f['id_project'],'rate');
										// moyenne pondéré
										$montantHaut = 0;
										$montantBas = 0;
										// si fundé ou remboursement
										if($this->projects_status->status==60 || $this->projects_status->status==80)
										{
											foreach($this->loans->select('id_project = '.$f['id_project']) as $b)
											{
												$montantHaut += ($b['rate']*($b['amount']/100));
												$montantBas += ($b['amount']/100);
											}	
										}
										// funding ko
										elseif($this->projects_status->status==70)
										{
											foreach($this->bids->select('id_project = '.$f['id_project']) as $b)
											{
												$montantHaut += ($b['rate']*($b['amount']/100));
												$montantBas += ($b['amount']/100);
											}	
										}
										// emprun refusé
										elseif($this->projects_status->status==75)
										{
											foreach($this->bids->select('id_project = '.$f['id_project'].' AND status = 1') as $b)
											{
												$montantHaut += ($b['rate']*($b['amount']/100));
												$montantBas += ($b['amount']/100);
											}	
										}
										else
										{
											foreach($this->bids->select('id_project = '.$f['id_project'].' AND status = 0') as $b)
											{
												$montantHaut += ($b['rate']*($b['amount']/100));
												$montantBas += ($b['amount']/100);
											}
										}
										if($montantHaut > 0 && $montantBas > 0)
										$avgRate = ($montantHaut/$montantBas);
										
																
										?>
										<div class="post-box clearfix">
											<h3><?=$f['title']?>, <small><?=$this->companies->adresse1?><?=($this->companies->adresse1!=''?',':'')?> <?=$this->companies->zip?></small></h3>
											<?
											if($this->projects_status->status > 50)
											{
												$dateRest = $this->lng['preteur-synthese']['termine'];
												$reste = '';
												;
											}
											else
											{
												$reste = $this->lng['preteur-synthese']['reste'].' ';
												?>
												<script>
													var cible<?=$f['id_project']?> = new Date('<?=$mois_jour?>, <?=$annee?> <?=$this->heureFinFunding?>:00');
													var letime<?=$f['id_project']?> = parseInt(cible<?=$f['id_project']?>.getTime() / 1000, 10);
													setTimeout('decompte(letime<?=$f['id_project']?>,"val<?=$f['id_project']?>")', 500);
												</script>
												<?
											}
											if($f['photo_projet'] != '')
											{
												?><a href="<?=$this->lurl?>/projects/detail/<?=$f['slug']?>" class="img-holder"><img src="<?=$this->photos->display($f['photo_projet'],'photos_projets','photo_projet_min')?>" alt="<?=$f['photo_projet']?>"></a><?
											}
											?>
											<div class="info">
												<ul class="list">	
													<li><i class="icon-pig-gray"></i><?=number_format($f['amount'],0,',',' ')?> €</li>
													<li><i class="icon-clock-gray"></i><?=($reste==''?'':$reste)?> <span id="val<?=$f['id_project']?>"><?=$dateRest?></span></li>
													<li><i class="icon-target"></i><?=$this->lng['preteur-synthese']['couvert-a']?> <?=number_format($pourcentage,$decimalesPourcentage, ',', ' ')?> %</li>
                                                    
                                                    <?
													if($CountEnchere>0)
													{
														?><li><i class="icon-graph-gray"></i><?=number_format($avgRate, 2, ',', ' ')?> %</li><?
													}
													else
													{
														?><li><i class="icon-graph-gray"></i><?=($f['target_rate']=='-'?'-':number_format($f['target_rate'], 2, ',',' ').' %')?></li><?
													}
													?>
													
												</ul>
												
												<a class="btn alone" href="<?=$this->lurl?>/projects/detail/<?=$f['slug']?>"><?=$this->lng['preteur-synthese']['voir-le-projet']?></a>
											</div>
										</div>
										<?
									}
								}
                                ?>
							</div>
						</div>

					</div>

					<div class="col right">

						<div class="graphic-box le-bar-chart">
							<header>
								<h2><?=$this->lng['preteur-synthese']['synthese-de-vos-mouvement']?></h2>
								<p><?=$this->lng['preteur-synthese']['montant-depose']?> :  <strong><?=number_format($this->SumDepot,2,',',' ')?> €</strong></p>
							</header>
							<div class="body">
                            	
                                <span id="titlePrete"><?=$this->lng['preteur-synthese']['argent-prete']?></span>
                                <span id="titleArgentRemb"><?=$this->lng['preteur-synthese']['capital-rembourse']?></span>
                                <span id="titleInteretsRecu"><?=$this->lng['preteur-synthese']['interets-recus']?></span>
                                
                            	<span id="argentPrete"><?=number_format($this->sumPrets,2,'.','')?></span>
                                <span id="argentRemb"><?=number_format($this->sumRembMontant,2,'.','')?></span>
                                <span id="interets"><?=number_format($this->sumInterets,2,'.','')?></span>
                            
								<div id="bar-chart"></div>
                               
							</div>
                             <a class="bottom-link" href="<?=$this->lurl?>/mouvement"><?=$this->lng['preteur-synthese']['voir-mes-operations']?></a>
						</div>

						<div class="post-schedule clearfix">
							<h2><?=$this->lng['preteur-synthese']['revenus-mensuels']?> <span><?=$this->lng['preteur-synthese']['3-mois']?> <i class="icon-box-arrow"></i></span></h2>
                            <div style="display:none;" class="interets_recu"><?=$this->lng['preteur-synthese']['interets-recus-par-mois']?></div>
                            <div style="display:none;" class="capital_rembourse"><?=$this->lng['preteur-synthese']['capital-rembourse-par-mois']?></div>
							<div class="body">
                            	
                                <?
								for($i=1;$i<=12;$i++)
								{
									?>
									<span id="inte<?=$i?>" style="display:none;"><?=$this->sumIntbParMois[$i]?></span>
									<span id="remb<?=$i?>" style="display:none;"><?=$this->sumRembParMois[$i]?></span>
									<?
								}
								?>
                                
								<div class="slider-c">
									<div class="arrow prev notext">arrow</div>
									<div class="arrow next notext">arrow</div>
									<div class="chart-slider">
                                    	<?
										
										for($i=1;$i<=4;$i++)
										{
											?><div id="bar-mensuels-<?=$this->ordre[$i]?>" class="chart-item"></div><?
										}
										?>
									</div>
								</div>
							</div>
							<a class="bottom-link" href="<?=$this->lurl?>/mouvement"><?=$this->lng['preteur-synthese']['voir-mes-operations']?></a>
						</div>

						<div class="post-schedule">
							<h2><i class="icon-heart"></i> <?=$this->lng['preteur-synthese']['mes-favoris']?> <span><?=($this->lProjetsFav!=false?count($this->lProjetsFav):0)?> <i class="icon-box-arrow"></i></span></h2>
							<div class="body">
								
                                <?
								if($this->lProjetsFav != false)
								{
									foreach($this->lProjetsFav as $f)
									{
										$this->companies->get($f['id_company'],'id_company');
										
										$this->projects_status->getLastStatut($f['id_project']);
										
										$fast_ok = false;
										if($this->projects_status->status == 50 && $this->clients_status->status >= 60)
										{
											$fast_ok = true;
										}
										
										// date fin 21h a chaque fois
										$inter = $this->dates->intervalDates(date('Y-m-d H:i:s'),$f['date_retrait'].' '.$this->heureFinFunding.':00');
										if($inter['mois']>0) $dateRest = $inter['mois'].' '.$this->lng['preteur-projets']['mois'];
										else $dateRest = '';
										
										// dates pour le js
										$mois_jour = $this->dates->formatDate($f['date_retrait'],'F d');
										$annee = $this->dates->formatDate($f['date_retrait'],'Y');
										
										// la sum des encheres
										$soldeBid = $this->bids->getSoldeBid($f['id_project']);
										
										// solde payé
										$payer = $soldeBid;
										
										// Reste a payer
										$resteApayer = ($f['amount']-$soldeBid);
										
										$pourcentage = ((1-($resteApayer/$f['amount']))*100);
										
										$decimales = 2;
										$decimalesPourcentage = 2;
										
										if($soldeBid >= $f['amount'])
										{
											$payer = $f['amount'];
											$resteApayer = 0;
											$pourcentage = 100;
											$decimales = 0;
											$decimalesPourcentage = 0;
										}
										
										
										$CountEnchere = $this->bids->counter('id_project = '.$f['id_project']);
										//$avgRate = $this->bids->getAVG($f['id_project'],'rate');
										
										// moyenne pondéré
										$montantHaut = 0;
										$montantBas = 0;
										// si fundé ou remboursement
										if($this->projects_status->status==60 || $this->projects_status->status==80)
										{
											foreach($this->loans->select('id_project = '.$f['id_project']) as $b)
											{
												$montantHaut += ($b['rate']*($b['amount']/100));
												$montantBas += ($b['amount']/100);
											}
										}
										// funding ko
										elseif($this->projects_status->status==70)
										{
											foreach($this->bids->select('id_project = '.$f['id_project']) as $b)
											{
												$montantHaut += ($b['rate']*($b['amount']/100));
												$montantBas += ($b['amount']/100);
											}	
										}
										// emprun refusé
										elseif($this->projects_status->status==75)
										{
											foreach($this->bids->select('id_project = '.$f['id_project'].' AND status = 1') as $b)
											{
												$montantHaut += ($b['rate']*($b['amount']/100));
												$montantBas += ($b['amount']/100);
											}	
										}
										else
										{
											foreach($this->bids->select('id_project = '.$f['id_project'].' AND status = 0') as $b)
											{
												$montantHaut += ($b['rate']*($b['amount']/100));
												$montantBas += ($b['amount']/100);
											}
										}
										if($montantHaut > 0 && $montantBas > 0)
										$avgRate = ($montantHaut/$montantBas);
										else $avgRate = 0;
									?>
									<div class="post-box clearfix">
										<h3><?=$f['title']?>, <small><?=$this->companies->adresse1?><?=($this->companies->adresse1!=''?',':'')?> <?=$this->companies->zip?></small></h3>
										<?
										if($this->projects_status->status > 50)
										{
											$dateRest = $this->lng['preteur-synthese']['termine'];
											$reste = '';
											;
										}
										else
										{
											$reste = $this->lng['preteur-synthese']['reste'].' ';
											?>
											<script>
												var cible<?=$f['id_project']?> = new Date('<?=$mois_jour?>, <?=$annee?> <?=$this->heureFinFunding?>:00');
												var letime<?=$f['id_project']?> = parseInt(cible<?=$f['id_project']?>.getTime() / 1000, 10);
												setTimeout('decompte(letime<?=$f['id_project']?>,"valFav<?=$f['id_project']?>")', 500);
											</script>
											<?
										}
										if($f['photo_projet'] != '')
										{
											?><a href="<?=$this->lurl?>/projects/detail/<?=$f['slug']?>" class="img-holder"><img src="<?=$this->photos->display($f['photo_projet'],'photos_projets','photo_projet_min')?>" alt="<?=$f['photo_projet']?>"></a><?
										}
										?>
										<div class="info">
											<ul class="list">	
												<li><i class="icon-pig-gray"></i><?=number_format($f['amount'],0,',',' ')?> €</li>
												<li><i class="icon-clock-gray"></i><?=($reste==''?'':$reste)?><span id="valFav<?=$f['id_project']?>"><?=$dateRest?></span></li>
												<li><i class="icon-target"></i><?=$this->lng['preteur-synthese']['couvert-a']?> <?=number_format($pourcentage,$decimalesPourcentage, ',', ' ')?> %</li>
												
                                                <?
												if($CountEnchere>0)
												{
													?><li><i class="icon-graph-gray"></i><?=number_format($avgRate, 2, ',', ' ')?> %</li><?
												}
												else
												{
													?><li><i class="icon-graph-gray"></i><?=($f['target_rate']=='-'?'-':number_format($f['target_rate'], 2, ',', ' ').' %')?></li><?
												}
												?>
                                                
											</ul>
											
											<a class="btn <?=($fast_ok==true?'':'alone')?>" href="<?=$this->lurl?>/projects/detail/<?=$f['slug']?>"><?=$this->lng['preteur-synthese']['voir-le-projet']?></a>
											<?
// Si profile non validé par unilend
							
											if($fast_ok == true) 
											{
												?><a class="btn darker popup-link"  href="<?=$this->lurl?>/thickbox/pop_up_fast_pret/<?=$f['id_project']?>"><?=$this->lng['preteur-synthese']['pret-rapide']?></a><?
												
											}
											
											?>
										</div>
									</div>
									<?
									}
								}
								?>	
							</div>
						</div>

					</div>
				</div>
			</div>
		</div>
		
<!--#include virtual="ssi-footer.shtml"  -->
<script type="text/javascript">
$('.chart-slider').carouFredSel({
	width: 420,
	height: 260,
	auto: false,
	prev: '.slider-c .arrow.prev',
	next: '.slider-c .arrow.next',
	items: {
		visible: 1
	}
});
</script>
