<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en-US" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
	<title>SFF docs</title>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<link rel="shortcut icon" href="<?=$this->surl?>/styles/default/pdf/images/favicon.ico" />
	<link rel="stylesheet" href="<?=$this->surl?>/styles/default/pdf/style.css" type="text/css" media="all" />

	<script src="<?=$this->surl?>/scripts/default/pdf/jquery-1.10.2.min.js" type="text/javascript" charset="utf-8"></script>
	<script src="<?=$this->surl?>/scripts/default/pdf/functions.js" type="text/javascript" charset="utf-8"></script>

</head>
<body>
	<!-- Doc Wrapper -->
	<div class="doc-wrapper">
		<!-- Shell -->
		<div class="shell">

			<!-- Page Break -->
			<div class="page-break">
				

				<h3 class="pink"><?=$this->bloc_pouvoir['pouvoir']?></h3>
				<h5><?=$this->bloc_pouvoir['je-soussigne']?></h5>
				
				<div class="list">
					<ul>
						<li>
							<div class="col-long">
								Raison sociale
							</div>
							<div class="col-small">
								<?=$this->companies->name?>
							</div>
							<div class="cl">&nbsp;</div>
						</li>
                        <li>
							<div class="col-long">
								Adresse
							</div>
							<div class="col-small">
								<?=$this->companies->adresse1?>
							</div>
							<div class="cl">&nbsp;</div>
						</li>
                        <li>
							<div class="col-long">
								Code postal
							</div>
							<div class="col-small">
								<?=$this->companies->zip?>
							</div>
							<div class="cl">&nbsp;</div>
						</li>
                        <li>
							<div class="col-long">
								Ville
							</div>
							<div class="col-small">
								<?=$this->companies->city?>
							</div>
							<div class="cl">&nbsp;</div>
						</li>
                        <li>
							<div class="col-long">
								R.C.S.
							</div>
							<div class="col-small">
								<?=$this->companies->siren?>
							</div>
							<div class="cl">&nbsp;</div>
						</li>
					</ul>
				</div>
				<h5><?=$this->bloc_pouvoir['represente-par-142']?></h5>
				<div class="list">
					<ul>
                    	<li>
							<div class="col-long">
								Nom
							</div>
							<div class="col-small">
								<?=$this->clients->nom?>
							</div>
							<div class="cl">&nbsp;</div>
						</li>
                        <li>
							<div class="col-long">
								Prénom
							</div>
							<div class="col-small">
								<?=$this->clients->prenom?>
							</div>
							<div class="cl">&nbsp;</div>
						</li>
                        <li>
							<div class="col-long">
								Fonction
							</div>
							<div class="col-small">
								<?=$this->clients->fonction?>
							</div>
							<div class="cl">&nbsp;</div>
						</li>
					</ul>
				</div>
				
				<h5><?=$this->bloc_pouvoir['donne-pouvoir-a']?></h5>
				<div class="list">
					<ul>
						<li>
							<?=$this->lng['pdf-pouvoir']['adresse']?>
						</li>
						<li>
							<h5><?=$this->bloc_pouvoir['pour-signer-en-mon']?></h5>
						</li>
						<li>
							<div class="col-long">
								<?=$this->bloc_pouvoir['montant-total']?>
							</div>
							<div class="col-small">
								<?=number_format($this->montantPrete, 2, ',', ' ')?> €
							</div>
							<div class="cl">&nbsp;</div>
						</li>
						<li>
							<div class="col-long">
                            	
								<?=$this->bloc_pouvoir['taux-i-annuel']?>
							</div>
							<div class="col-small">
								<?=number_format($this->taux, 2, ',', ' ')?> %
							</div>
							<div class="cl">&nbsp;</div>
						</li>
						<li>
							<div class="col-long">
								<?=$this->bloc_pouvoir['nombre-de-bons']?>
							</div>
							<div class="col-small">
								<?=$this->nbLoans?>
							</div>
							<div class="cl">&nbsp;</div>
						</li>
						<li>
							<div class="col-long">
								<?=$this->bloc_pouvoir['date-de-creation-148']?>
							</div>
							<div class="col-small">
								<?=$this->dateRemb?>
							</div>
							<div class="cl">&nbsp;</div>
						</li>
						<li>
							<div class="col-long">
								<?=$this->bloc_pouvoir['date-decheance-149']?>
							</div>
							<div class="col-small">
								<?=date('d/m/Y',strtotime($this->dateLastEcheance))?>
							</div>
							<div class="cl">&nbsp;</div>
						</li>
						<li>
							<div class="col-long">
								<?=$this->bloc_pouvoir['nombre-mensualites']?>
							</div>
							<div class="col-small">
								<?=$this->projects->period?>
							</div>
							<div class="cl">&nbsp;</div>
						</li>
						<li>
                        	<div class="col-long">
								<?=$this->bloc_pouvoir['montant-mensualites']?>
							</div>
							<div class="col-small">
								<?=number_format($this->rembByMonth, 2, ',', ' ')?> €
							</div>
							<div class="cl">&nbsp;</div>
						</li>
						<li>
							<h5><?=$this->bloc_pouvoir['les-bons-nominatifs']?></h5>
						</li>
					</ul>
				</div>
				<div class="list">
					<ul>
						<li><?=$this->bloc_pouvoir['la-signature-engage']?></li>
						<li>
							<div class="col-long">
								<?=$this->bloc_pouvoir['a-rembourser-153']?>
							</div>
							<div class="col-small">
								<?=number_format($this->montantPrete, 2, ',', ' ')?> €
							</div>
							<div class="cl">&nbsp;</div>
						</li>
						<li>
							<div class="col-long">
								<?=$this->bloc_pouvoir['assortie']?>
							</div>
							<div class="col-small">
								<?=number_format($this->taux, 2, ',', ' ')?> %
							</div>
							<div class="cl">&nbsp;</div>
						</li>
						<li>
							<?=$this->bloc_pouvoir['selon-echeancier-156']?>
						</li>
					</ul>
				</div>
				<div class="list">
					<ul>
						<li>
							<div class="col-long">
								<?=$this->bloc_pouvoir['signature-emetteur']?>
							</div>
							<div class="col-small">
								<div style="background-color:white;border:1px solid #808080;height: 50px;width: 250px;"></div>
							</div>
							<div class="cl">&nbsp;</div>
						</li>
					</ul>
				</div>
				<br />
				<div class="list">
					<ul>
						<li><?=$this->bloc_pouvoir['les-bons-de-caisse']?></li>
					</ul>
				</div>
			</div>
			<!-- End Page Break -->

			<!-- Page Break -->
			<div class="page-break">
				<h3 class="pink"><?=$this->bloc_pdf_contrat['dernier-bilan']?></h3>
				<div class="list">
					<ul>
						<li>
							<?=$this->bloc_pdf_contrat['au']?> <?=$this->date_dernier_bilan_jour?>/<?=$this->date_dernier_bilan_mois?>/<?=$this->date_dernier_bilan_annee?>
						</li>
					</ul>
				</div>
				<h5><?=$this->bloc_pdf_contrat['actif']?></h5>
				<div class="list">
					<ul>
						<li><?=$this->bloc_pdf_contrat['immo-corpo']?><div class="col-small"><?=number_format($this->l_AP[0]['immobilisations_corporelles'], 2, ',', ' ')?> €</div></li>
						<li><?=$this->bloc_pdf_contrat['immo-incorpo']?><div class="col-small"><?=number_format($this->l_AP[0]['immobilisations_incorporelles'], 2, ',', ' ')?> €</div></li>
						<li><?=$this->bloc_pdf_contrat['immo-financieres']?><div class="col-small"><?=number_format($this->l_AP[0]['immobilisations_financieres'], 2, ',', ' ')?> €</div></li>
						<li><?=$this->bloc_pdf_contrat['stocks']?><div class="col-small"><?=number_format($this->l_AP[0]['stocks'], 2, ',', ' ')?> €</div></li>
						<li><?=$this->bloc_pdf_contrat['creances']?><div class="col-small"><?=number_format($this->l_AP[0]['creances_clients'], 2, ',', ' ')?> €</div></li>
						<li><?=$this->bloc_pdf_contrat['dispo']?><div class="col-small"><?=number_format($this->l_AP[0]['disponibilites'], 2, ',', ' ')?> €</div></li>
						<li><?=$this->bloc_pdf_contrat['valeurs-mobilieres']?><div class="col-small"><?=number_format($this->l_AP[0]['valeurs_mobilieres_de_placement'], 2, ',', ' ')?> €</div></li>
					</ul>
				</div>
				<div class="total-row">
					<?=$this->bloc_pdf_contrat['total-actif']?> : <?=number_format($this->totalActif, 2, ',', ' ')?> €
				</div>
				<h5><?=$this->bloc_pdf_contrat['passif']?></h5>
				<div class="list">
					<ul>
						<li><?=$this->bloc_pdf_contrat['capitaux']?><div class="col-small"><?=number_format($this->l_AP[0]['capitaux_propres'], 2, ',', ' ')?> €</div></li>
						<li><?=$this->bloc_pdf_contrat['provisions']?><div class="col-small"><?=number_format($this->l_AP[0]['provisions_pour_risques_et_charges'], 2, ',', ' ')?> €</div></li>
                        <li><?=$this->bloc_pdf_contrat['amortissements-sur-immo']?><div class="col-small"><?=number_format($this->l_AP[0]['amortissement_sur_immo'], 2, ',', ' ')?> €</div></li>
                        
						<li><?=$this->bloc_pdf_contrat['dettes-fi']?><div class="col-small"><?=number_format($this->l_AP[0]['dettes_financieres'], 2, ',', ' ')?> €</div></li>
						<li><?=$this->bloc_pdf_contrat['dettes-fourn']?><div class="col-small"><?=number_format($this->l_AP[0]['dettes_fournisseurs'], 2, ',', ' ')?> €</div></li>
						<li><?=$this->bloc_pdf_contrat['autres-dettes']?><div class="col-small"><?=number_format($this->l_AP[0]['autres_dettes'], 2, ',', ' ')?> €</div></li>
					</ul>
				</div>
				<div class="total-row">
					<?=$this->bloc_pdf_contrat['total-passif']?> : <?=number_format($this->totalPassif, 2, ',', ' ')?> €
				</div>
				<div class="center-text">
					<?=$this->bloc_pdf_contrat['certifie']?>
				</div>
			</div>
			<!-- End Page Break -->
			
            <?
            if($this->projects->period > 48)
			{
				?>
                <!-- Page Break -->
                <div class="page-break">
                    <h3 class="pink"><?=$this->bloc_pdf_contrat['echeancier-remboursements']?></h3>
                    <div class="dates-table">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table-2">
                            <tr>
                                <th valign="bottom"><?=$this->bloc_pdf_contrat['date']?></th>
                                <th valign="bottom"><?=$this->bloc_pdf_contrat['capital']?></th>
                                <th valign="bottom"><?=$this->bloc_pdf_contrat['interet']?></th>
                                <th valign="bottom"><?=$this->bloc_pouvoir['commission']?><br /> <?=$this->bloc_pouvoir['unilend']?></th>
                                <th valign="bottom"><?=$this->bloc_pouvoir['tva']?></th>
                                <th valign="bottom"><?=$this->bloc_pdf_contrat['total']?></th>
                                <th valign="bottom"><?=$this->bloc_pdf_contrat['capital-restant']?></th>
                            </tr>
                            
                            <?
                            
                            
                            $capRestant = $this->capital;
                            foreach($this->lRemb as $r)
                            {
								if($r['ordre'] <= 48)
								{
									$montantEmprunteur = $this->echeanciers->getMontantRembEmprunteur($r['montant'],$r['commission'],$r['tva']);
									
									$capRestant -= $r['capital'];
									if($capRestant < 0)$capRestant = 0;
									
									?>
									<tr>
										<td><?=$this->dates->formatDate($r['date_echeance_emprunteur'],'d/m/Y')?></td>
										<td><?=number_format($r['capital']/100,2,',',' ')?> €</td>
										<td><?=number_format($r['interets']/100,2,',',' ')?> €</td>
										<td><?=number_format($r['commission']/100,2,',',' ')?> €</td>
										<td><?=number_format($r['tva']/100,2,',',' ')?> €</td>
										<td><?=number_format($montantEmprunteur/100,2,',',' ')?> €</td>
										<td><?=number_format($capRestant/100,2,',',' ')?> €</td>
									</tr>
									<?
								}
                            }
                            ?>
                        </table>
                    </div>
                </div>
                <!-- End Page Break -->
                
                <!-- Page Break -->
                <div class="page-break" style="page-break-before:always; margin-top: 40px;padding-top: 20px;">
                    <div class="dates-table">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table-2">
                            <?
                            foreach($this->lRemb as $r)
                            {
								if($r['ordre'] > 48)
								{
									$montantEmprunteur = $this->echeanciers->getMontantRembEmprunteur($r['montant'],$r['commission'],$r['tva']);
									
									$capRestant -= $r['capital'];
									if($capRestant < 0)$capRestant = 0;
									
									?>
									<tr>
										<td><?=$this->dates->formatDate($r['date_echeance_emprunteur'],'d/m/Y')?></td>
										<td><?=number_format($r['capital']/100,2,',',' ')?> €</td>
										<td><?=number_format($r['interets']/100,2,',',' ')?> €</td>
										<td><?=number_format($r['commission']/100,2,',',' ')?> €</td>
										<td><?=number_format($r['tva']/100,2,',',' ')?> €</td>
										<td><?=number_format($montantEmprunteur/100,2,',',' ')?> €</td>
										<td><?=number_format($capRestant/100,2,',',' ')?> €</td>
									</tr>
									<?
								}
                            }
                            ?>
                        </table>
                    </div>
                </div>
                <!-- End Page Break -->
                <?
			}
			else
			{
           		?>
                <!-- Page Break -->
                <div class="page-break">
                    <h3 class="pink"><?=$this->bloc_pdf_contrat['echeancier-remboursements']?></h3>
                    
                    
                    
                    <div class="dates-table">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table-2">
                            <tr>
                                <th valign="bottom"><?=$this->bloc_pdf_contrat['date']?></th>
                                <th valign="bottom"><?=$this->bloc_pdf_contrat['capital']?></th>
                                <th valign="bottom"><?=$this->bloc_pdf_contrat['interet']?></th>
                                <th valign="bottom"><?=$this->bloc_pouvoir['commission']?><br /> <?=$this->bloc_pouvoir['unilend']?></th>
                                <th valign="bottom"><?=$this->bloc_pouvoir['tva']?></th>
                                <th valign="bottom"><?=$this->bloc_pdf_contrat['total']?></th>
                                <th valign="bottom"><?=$this->bloc_pdf_contrat['capital-restant']?></th>
                            </tr>
                            
                            <?
                            
                            
                            $capRestant = $this->capital;
                            foreach($this->lRemb as $r)
                            {
                                $montantEmprunteur = $this->echeanciers->getMontantRembEmprunteur($r['montant'],$r['commission'],$r['tva']);
                                
                                $capRestant -= $r['capital'];
                                if($capRestant < 0)$capRestant = 0;
                                
                                ?>
                                <tr>
                                    <td><?=$this->dates->formatDate($r['date_echeance_emprunteur'],'d/m/Y')?></td>
                                    <td><?=number_format($r['capital']/100,2,',',' ')?> €</td>
                                    <td><?=number_format($r['interets']/100,2,',',' ')?> €</td>
                                    <td><?=number_format($r['commission']/100,2,',',' ')?> €</td>
                                    <td><?=number_format($r['tva']/100,2,',',' ')?> €</td>
                                    <td><?=number_format($montantEmprunteur/100,2,',',' ')?> €</td>
                                    <td><?=number_format($capRestant/100,2,',',' ')?> €</td>
                                </tr>
                                <?
                            }
                            ?>
                        </table>
                    </div>
                </div>
                <!-- End Page Break -->
                <?
			}
			?>
			
			<?
			$var = 0;
			$nb = intval((count($this->lLenders)/26));
			for($a=0;$a<=$nb;$a++)
			{
				if($var == count($this->lLenders))
				{
					break;	
				}
				?>
				<!-- Page Break -->
				<div class="page-break" style="page-break-before:always; padding-top: 30px;">
                	<?
					if($var == 0)
					{
						?><h3><?=$this->bloc_pouvoir['liste-caracteristiques']?></h3><?
					}
					?>
	
					<div class="dates-table">
						<table width="100%" border="0" cellspacing="0" cellpadding="0" class="table-3">
							<?
							if($var == 0)
							{
								
								?>
								<tr>
									<th><?=$this->bloc_pouvoir['nom']?><br /> <?=$this->bloc_pouvoir['raison-sociale']?></th>
									<th><?=$this->bloc_pouvoir['prenom']?><br /> <?=$this->bloc_pouvoir['rcs']?></th>
									<th><?=$this->bloc_pouvoir['adresse']?></th>
									<th><?=$this->bloc_pouvoir['code']?><br /> <?=$this->bloc_pouvoir['postal']?></th>
									<th><?=$this->bloc_pouvoir['ville']?></th>
									<th><?=$this->bloc_pouvoir['montant-172']?></th>
									<th><?=$this->bloc_pouvoir['taux']?><br /> <?=$this->bloc_pouvoir['interet-174']?></th>
								</tr>
								<?
							}
							
							$i=0;
							foreach($this->lLenders as $key => $l)
							{
								
								if($var == $key)
								{
									if($i <= 26)
									{
										$this->lenders_accounts->get($l['id_lender'],'id_lender_account');
										$this->clients->get($this->lenders_accounts->id_client_owner,'id_client');
										$this->clients_adresses->get($this->clients->id_client,'id_client');
										
										
										$nom = $this->clients->nom;
										$prenom = $this->clients->prenom;
										
										if($this->clients->type==2)
										{
											$this->companies->get($this->clients->id_client,'id_client_owner');	
											
											$nom = $this->companies->name;
											$prenom = $this->companies->rcs;
										}
										
										
										?>
										<tr>
											<td><?=$nom?></td>
											<td><?=$prenom?></td>
											<td><?=$this->clients_adresses->adresse1?></td>
											<td><?=$this->clients_adresses->cp?></td>
											<td><?=$this->clients_adresses->ville?></td>
											<td class="nowrap"><?=number_format($l['amount']/100,2,',',' ')?> €</td>
											<td class="nowrap"><?=number_format($l['rate'],2,',',' ')?> %</td>
										</tr>
										<?
										$var++;
										$i++;
									}
								}
								
							}
							?>
						</table>
					</div>
	
					<?php /*?><div class="footer">
						<p style="text-align: center;font-size:8px;"><?=$this->bloc_pouvoir['mention1']?><br>
						<?=$this->bloc_pouvoir['mention2']?></p>
					</div><?php */?>
				</div>
				<!-- End Page Break -->
				<?
				
			}
			?>

		</div>
		<!-- End Shell -->
	</div>
	<!-- End Doc Wrapper -->

</body>
</html>