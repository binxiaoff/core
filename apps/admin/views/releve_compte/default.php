<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en-US" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
	<title>SFF print</title>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<link rel="shortcut icon" href="<?=$this->surl?>/styles/default/pdf_releve_compte/images/favicon.ico" />
	<link rel="stylesheet" href="<?=$this->surl?>/styles/default/pdf_releve_compte/style.css" type="text/css" media="all" />
</head>
<body>

<form style="width: 700px;margin:auto;margin-top:20px;" action="" method="post">
<table >
    <tr>
    	<th>id preteur : </th>
        <td><input name="id_client" type="text" value="<?=$_POST['id_client']?>" /></td>
    	<th>Mois : </th>
        <td><input name="month" type="text" value="<?=$_POST['month']?>" /></td>
        <th>Année : </th>
        <td><input name="year" type="text" value="<?=$_POST['year']?>" /></td>
        <td><input name="Valider" type="submit" value="Valider" /></td>
    </tr>
</table>
</form>

<div class="container">
	<div class="shell">
		<div class="header clearfix">
			<div class="logo">
				<img src="<?=$this->surl?>/styles/default/pdf_releve_compte/images/logo.png" alt="" />
			</div><!-- /.logo -->

			<div class="address">
				<p>
					Monsieur <?=$this->clients->prenom?> <?=$this->clients->nom?><br />
					<?=$this->clients_adresses->adresse1?><br />
					<?=$this->clients_adresses->cp?> <?=$this->clients_adresses->ville?>
				</p>
			</div><!-- /.address -->
		</div><!-- /.header clearfix -->

		<div class="welcome">
			<h2>Relevé de votre compte Unilend n&#186; <strong>XXXXX</strong> du 01/<?=$this->month?>/<?=$this->year?> au <?=$this->dayEndMonths?>/<?=$this->month?>/<?=$this->year?></h2>
		</div><!-- /.welcome -->

		<div class="table">
			<div class="table-head">
				<h3>Synthèse des opérations</h3>
			</div><!-- /.table-head -->

			<div class="table-body">
				<table width="100%" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<th width="50%" >Argent disponible en début de période</th>
						<th width="50%" ><?=number_format($this->argentDispoDebutPeriode, 2, ',', ' ')?> &euro;</th>
					</tr>
				
					<tr>
						<td>Apport d’argent</td>
						<td><?=number_format($this->apportArgent, 2, ',', ' ')?>  &euro;</td>
					</tr>
				
					<tr>
						<td>Retrait d’argent</td>
						<td><?=number_format($this->retraitArgent, 2, ',', ' ')?> &euro;</td>
					</tr>
				
					<tr>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					</tr>
				
					<tr>
						<td>Argent bloqué/débloqué pour offre de prêt</td>
						<td><?=number_format($this->argentBloque, 2, ',', ' ')?> &euro;</td>
					</tr>
				
					<tr>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					</tr>

					<tr>
						<td>Argent prêté</td>
						<td><?=number_format($this->agentPrete, 2, ',', ' ')?> &euro;</td>
					</tr>

					<tr>
						<td>Argent remboursé</td>
						<td><?=number_format($this->argentRemb, 2, ',', ' ')?> &euro;</td>
					</tr>

					<tr>
						<td>Intérêts bruts reçus</td>
						<td><?=number_format($this->interets, 2, ',', ' ')?> &euro;</td>
					</tr>
                    <tr>
						<td>Retenues fiscales</td>
						<td><?=number_format($this->retenuesFiscales, 2, ',', ' ')?> &euro;</td>
					</tr>

					<tr class="total">
						<td>Argent disponible en fin de période</td>
						<td><?=number_format($this->argentDispoFinPeriode, 2, ',', ' ')?> &euro;</td>
					</tr><!-- /.total -->
				</table>
			</div><!-- /.table-body -->
		</div><!-- /.table -->

		<div class="table table-big">
			<div class="table-head">
				<h3>Détail des opérations</h3>

				<div class="table-body">
					<table width="100%" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<th width="46" >Date</th>
							<th width="300" >Opération</th>
							<th width="109" >Entrée</th>
							<th >Sortie</th>
						</tr>
						<?
						
						$this->typesTransac = array(1 => 'Inscription preteur',3 => 'Apport d\'argent – carte bancaire',4 => 'Apport d\'argent – virement bancaire',5 => 'Remboursement',8 => 'Retrait d\'argent – virement bancaire');
						$entre = 0;
						$sortie = 0;
						foreach($this->lesTransac as $t)
						{
							?>
							<tr>
								<td><?=date('d/m',strtotime($t['date_transaction']))?></td>
								
                                <?
								// enchere
								if($t['type_transaction'] == 2)
								{
									
									// deblocage
									if(strpos($t['montant'],'-') === false)
									{
										$this->bids->get($t['id_bid_remb'],'id_bid');
										$this->projects->get($this->bids->id_project,'id_project');
										
                                		?><td>Déblocage pour offre non retenue – <?=$this->projects->title?></td><?
									}
									// blocage
									else
									{
										$this->wallets_lines->get($t['id_transaction'],'id_transaction');
										$this->bids->get($this->wallets_lines->id_wallet_line,'id_lender_wallet_line');
										$this->projects->get($this->bids->id_project,'id_project');
										
										/*if($this->bids->status == 1)
										{
											?><td>Prêt – <?=$this->projects->title?></td><?	
										}
										else
										{*/
											?><td>Blocage pour offre de prêt – <?=$this->projects->title?></td><?	
										//}
									}
								}
								// remb
								elseif($t['type_transaction'] == 5)
								{
									$this->echeanciers->get($t['id_echeancier'],'id_echeancier');
									$this->projects->get($this->echeanciers->id_project,'id_project');
									

									?><td>
                                    <div><?=$this->typesTransac[$t['type_transaction']]?> - <?=$this->projects->title?></div>
                                    <div style="text-align:right">dont retenue fiscale</div>
                                    </td><?
								}
								// pret
								elseif($t['type_transaction'] == 'pret')
								{
									$this->projects->get($t['id_project'],'id_project');
									
									?><td>Prêt – <?=$this->projects->title?></td><?	
								}
								// bid-pret
								elseif($t['type_transaction'] == 'bid-pret')
								{
									$this->projects->get($t['id_project'],'id_project');
									
									?><td>Offre acceptée – <?=$this->projects->title?></td><?	
								}
								else
								{
									?><td><?=$this->typesTransac[$t['type_transaction']]?></td><?
								}
								?>
                                
                                <?
								if(strpos($t['montant'],'-') === false)
								{
									
									$retenuefiscale = 0;
									if($t['type_transaction'] == 5)
									{
										
										$this->echeanciers->get($t['id_echeancier'],'id_echeancier');
									
										$retenuefiscale = $this->echeanciers->prelevements_obligatoires + $this->echeanciers->retenues_source + $this->echeanciers->csg + $this->echeanciers->prelevements_sociaux + $this->echeanciers->contributions_additionnelles + $this->echeanciers->prelevements_solidarite + $this->echeanciers->crds;
										$sortie += ('-'.$retenuefiscale*100);
										?>
                                        <td style="vertical-align:top;"><?=number_format(($t['montant']/100)+$retenuefiscale, 2, ',', ' ')?> &euro;</td>
                                        <td style="vertical-align:bottom;"><?=number_format('-'.$retenuefiscale, 2, ',', ' ')?> &euro;</td>
                                        <?
										
									}
									else
									{
										?>
										<td><?=number_format($t['montant']/100, 2, ',', ' ')?> &euro;</td>
										<td>&nbsp;</td>
										<?
									}
									
									$entre += $t['montant'] + ($retenuefiscale*100);
								}
								else
								{
									$sortie += $t['montant'];
									?>
									<td>&nbsp;</td>
                                    <td><?=number_format($t['montant']/100, 2, ',', ' ')?> &euro;</td>
									<?
								}
								?>
							</tr>
							<?
						}
                        ?>				

						<tr class="total">
							<td>&nbsp;</td>
							<td>Total</td>
							<td><?=number_format($entre/100, 2, ',', ' ')?> &euro;</td>
							<td><?=number_format($sortie/100, 2, ',', ' ')?> &euro;</td>
						</tr>			
					</table>
				</div><!-- /.table-body -->
			</div><!-- /.table-head -->
		</div><!-- /.table -->
	</div><!-- /.shell -->

	<div class="footer">
		<div class="shell">
			<p>UNILEND &bull; SOCIETE FRANÇAISE POUR LE FINANCEMENT DES PME – SFF PME &bull; SAS AU CAPITAL DE 300 000 &euro; &bull; www.unilend.fr</p>
			
			<p>86 AVENUE DE SAINT-OUEN &bull; 75018 PARIS &bull; 01 82 28 51 20 &bull; 790 766 034 R.C.S. PARIS &bull; APE 6619B &bull; TVA INTRACOMMUNAUTAIRE FR57790766034</p>
		</div><!-- /.shell -->
	</div><!-- /.footer -->
</div><!-- /.container -->
</body>
</html>