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
				<?php /*?><div class="logo">
					<img src="<?=$this->surl?>/styles/default/pdf/images/logo.png" alt="" />
				</div><?php */?>

				<h3><?=$this->bloc_pdf_contrat['titre']?> - #<?=$this->loans->id_loan?></h3>
				<h5><?=$this->bloc_pdf_contrat['designation']?></h5>
				<div class="list">
					<ul>
						<li>Raison sociale<div class="col-small"><?=$this->companiesEmprunteur->name?></div></li>
						<li>Forme juridique<div class="col-small"><?=$this->companiesEmprunteur->forme?></div></li>
						<li>Capital social<div class="col-small"><?=number_format($this->companiesEmprunteur->capital, 2, ',', ' ')?> €</div></li>
						<li>Adresse du siège social<div class="col-small"><?=$this->companiesEmprunteur->adresse1?></div></li>
						<li>Code postal<div class="col-small"><?=$this->companiesEmprunteur->zip?></div></li>
						<li>Ville<div class="col-small"><?=$this->companiesEmprunteur->city?></div></li>
						<li>Tribunal de commerce<div class="col-small"><?=$this->companiesEmprunteur->tribunal_com?></div></li>
						<li>R.C.S.<div class="col-small"><?=$this->companiesEmprunteur->rcs?></div></li>
						<li>Activité<div class="col-small"><?=$this->companiesEmprunteur->activite?></div></li>
						<li>Lieu d'exploitation<div class="col-small"><?=$this->companiesEmprunteur->lieu_exploi?></div></li>
					</ul>
				</div>
				<h5><?=$this->bloc_pdf_contrat['designation-p']?></h5>
				<div class="list">
					<ul>
                    	<?
						if($this->clients->type == 1) // particulier
						{
							?>
							<li>Nom<div class="col-small"><?=$this->clients->nom?></div></li>
							<li>Prénom<div class="col-small"><?=$this->clients->prenom?></div></li>
							<li>Date de naissance<div class="col-small"><?=date('d/m/Y',strtotime($this->clients->naissance))?></div></li>
							<li>Adresse<div class="col-small"><?=$this->clients_adresses->adresse1?></div></li>
							<li>Code postal<div class="col-small"><?=$this->clients_adresses->cp?></div></li>
							<li>Ville<div class="col-small"><?=$this->clients_adresses->ville?></div></li>
							<?
						}
						else // morale
						{
							?>
                            <li>Raison sociale<div class="col-small"><?=$this->companiesPreteur->name?></div></li>
                            <li>Forme juridique<div class="col-small"><?=$this->companiesPreteur->forme?></div></li>
                            <li>Capital social<div class="col-small"><?=number_format($this->companiesPreteur->capital, 2, ',', ' ')?> €</div></li>
                            <li>Adresse du siège social<div class="col-small"><?=$this->companiesPreteur->adresse1?></div></li>
                            <li>Code postal<div class="col-small"><?=$this->companiesPreteur->zip?></div></li>
                            <li>Ville<div class="col-small"><?=$this->companiesPreteur->city?></div></li>
                            <li>Tribunal de commerce<div class="col-small"><?=$this->companiesPreteur->tribunal_com?></div></li>
                            <li>R.C.S.<div class="col-small"><?=$this->companiesPreteur->rcs?></div></li>
                            <?
						}
						?>
					</ul>
				</div>
				<h5><?=$this->bloc_pdf_contrat['caracteristiques']?></h5>
				<div class="list">
					<ul>
						<li>
							<div class="col-long">
								<?=$this->bloc_pdf_contrat['montant']?>
							</div>
							<div class="col-small">
								<?=number_format($this->loans->amount/100, 2, ',', ' ')?> €
							</div>
							<div class="cl">&nbsp;</div>
							<br />
						</li>
						<li>
							<div class="col-long">
								<?=$this->bloc_pdf_contrat['taux-i']?> 
							</div>
							<div class="col-small">
								<?=number_format($this->loans->rate, 2, ',', ' ')?> %
							</div>
							<div class="cl">&nbsp;</div>
						</li>
						<li>
							<div class="col-long">
								<?=$this->bloc_pdf_contrat['date-de-creation']?> 
							</div>
							<div class="col-small">
								<?=$this->dateRemb?>
							</div>
							<div class="cl">&nbsp;</div>
						</li>
						<li>
							<div class="col-long">
								<?=$this->bloc_pdf_contrat['date-decheance']?>
							</div>
							<div class="col-small">
								<?=date('d/m/Y',strtotime($this->dateLastEcheance))?>
							</div>
							<div class="cl">&nbsp;</div>
							<br />
						</li>
						<li>
							<?=$this->bloc_pdf_contrat['bon-de-caisse']?><br />
							<?=$this->bloc_pdf_contrat['lemetteur-certifie']?><br />
							<?=$this->bloc_pdf_contrat['la-signature-des']?> 
						</li>
						<li>
							<div class="col-long">
								<?=$this->bloc_pdf_contrat['a-rembourser']?>
							</div>
							<div class="col-small">
								<?=number_format($this->loans->amount/100, 2, ',', ' ')?> €
							</div>
							<div class="cl">&nbsp;</div>
						</li>
						<li>
							<div class="col-long">
								<?=$this->bloc_pdf_contrat['assortie-des-interets-a']?>
							</div>
							<div class="col-small">
								<?=number_format($this->loans->rate, 2, ',', ' ')?> %
							</div>
							<div class="cl">&nbsp;</div>
						</li>
                        <li>
							<div class="col-long">
								<?=$this->bloc_pdf_contrat['selon-echeancier']?>
							</div>
							
							<div class="cl">&nbsp;</div>
							<br />
						</li>
						<li>
							<div class="col-long">
								<?=$this->bloc_pdf_contrat['fait-a']?> <?=$this->dateContrat?><br />
								<strong><?=$this->bloc_pdf_contrat['signe-par']?></strong><br />
								<strong><?=$this->bloc_pdf_contrat['represente-par']?></strong>
							</div>
							<div class="col-small">
								<div class="logo">
                                    <img src="<?=$this->surl?>/styles/default/pdf/images/logo.png" alt="logo" />
                                </div>
							</div>
							<div class="cl">&nbsp;</div>
							
						</li>
						<li>
							<?=$this->bloc_pdf_contrat['le-present-bon']?>
						</li>
					</ul>
				</div>
			</div>
			<!-- End Page Break -->

			<!-- Page Break -->
			<div class="page-break">
				<h3><?=$this->bloc_pdf_contrat['dernier-bilan']?></h3>
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
				<div class="total-row" style="text-align:left;">
					<?=$this->bloc_pdf_contrat['total-actif']?> : <div style="display:inline;float: right;"><?=number_format($this->totalActif, 2, ',', ' ')?> €</div>
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
				<div class="total-row" style="text-align:left;">
					<?=$this->bloc_pdf_contrat['total-passif']?> : <div style="display:inline;float: right;"><?=number_format($this->totalPassif, 2, ',', ' ')?> €</div>
				</div>
				<div class="center-text">
					<?=$this->bloc_pdf_contrat['certifie']?>
				</div>
				<h3><?=$this->bloc_pdf_contrat['conditions']?></h3>
				<p><?=$this->bloc_pdf_contrat['conditions-contenu']?></p>
			</div>
			<!-- End Page Break -->
			
			<!-- Page Break -->
			<div class="page-break">
				<h3><?=$this->bloc_pdf_contrat['echeancier-remboursements']?></h3>

				<div class="dates-table">
					<table width="100%" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<th valign="bottom"><?=$this->bloc_pdf_contrat['date']?></th>
							<th valign="bottom"><?=$this->bloc_pdf_contrat['capital']?></th>
							<th valign="bottom"><?=$this->bloc_pdf_contrat['interet']?></th>
							<th valign="bottom"><?=$this->bloc_pdf_contrat['total']?></th>
							<th valign="bottom"><?=$this->bloc_pdf_contrat['capital-restant']?></th>
						</tr>
                        <?
						$capRestant = $this->capital;
						foreach($this->lRemb as $r)
						{
							$capRestant -= $r['capital'];
							if($capRestant < 0)$capRestant = 0;
							
							?>
							<tr>
								<td><?=$this->dates->formatDate($r['date_echeance'],'d/m/Y')?></td>
								<td><?=number_format($r['capital']/100,2,',',' ')?> €</td>
								<td><?=number_format($r['interets']/100,2,',',' ')?> €</td>
								<td><?=number_format($r['montant']/100,2,',',' ')?> €</td>
								<td><?=number_format($capRestant/100,2,',',' ')?> €</td>
							</tr>
							<?
						}
						?>
						
					</table>
				</div>			
			</div>
			<!-- End Page Break -->

		</div>
		<!-- End Shell -->
	</div>
	<!-- End Doc Wrapper -->

</body>
</html>