<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en-US" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
	<title>SFF docs</title>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" href="<?=$this->surl?>/styles/default/pdf/style.css" type="text/css" media="all" />
</head>
<body>
<script>var dataLayer = [<?= json_encode($this->aDataLayer) ?>];</script>
<!-- Google Tag Manager -->
<noscript><iframe src="//www.googletagmanager.com/ns.html?id=GTM-MB66VL"
				  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
			new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
			j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
			'//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','GTM-MB66VL');</script>
<!-- End Google Tag Manager -->

    <style type="text/css">
	.pdf-wrapper .shell{padding:0px;}
	.pdf-wrapper .logo{padding: 0 0 5px;}
	ul.refUnique{border: 1px solid #000000;padding-left: 1px; width: 416px;}
	ul.refUnique li{border: 1px solid #000000;display: inline-block; height: 15px; margin-left: -1px;width: 15px;}
	</style>

	<!-- Pdf Wrapper -->
	<div class="pdf-wrapper">
		<!-- Shell -->
		<div class="shell">
			<div class="logo">
<!--				<img src="--><?//=$this->surl?><!--/styles/default/pdf/images/logo.png" alt="" />-->
			</div>

			<h2>MANDAT SEPA</h2>

			<!-- Container -->
			<div class="container">

				<!-- Case 1 -->
				<div class="case-1">
					<table width="100%" cellpadding="0" cellspacing="0" border="0">
						<tr>
							<td class="col-1">&nbsp;</td>
							<td class="col-2" valign="top">
								<h4>Mandat de Prélèvement SEPA</h4>
								<ul class="refUnique">
                                	<?
									for($i=0;$i<26;$i++)
									{
										?><li style="text-align:center;"><?=($this->motif[$i]!= ''?$this->motif[$i]:'&nbsp;')?></li><?
									}
									?>
                                </ul>
								<p>Référence unique du mandat</p>
							</td>
							<td class="col-3">
								<?=$this->creancier?><br />
                                <img src="<?=$this->surl?>/styles/default/images/favicon.png" />
							</td>
						</tr>
					</table>
				</div>
				<!-- End Case 1 -->

				<!-- Case 2 -->
				<div class="case-2">
					<p>En signant ce formulaire de mandat, vous autorisez (A) <em class="big"><?=$this->creancier?></em> à envoyer des instructions à votre banque pour débiter votre compte, et (B) votre banque à débiter votre compte conformément aux instructions de <em class="big"><?=$this->creancier?></em>.<br /> Vous bénéficiez du droit d'être remboursé par votre banque selon les conditions décrites dans la convention que vous avez passée avec elle. Une demande de remboursement doit être présentée:<br />- dans les 8 semaines suivant la date de débit de votre compte pour un prélèvement autorisé,<br />- sans tarder et au plus tard dans les 13 mois en cas de prélèvement non autorisé.<br /><em>Veuillez compléter les champs marqués *</em></p>
				</div>
				<!-- End Case 2 -->

				<!-- Case 3 -->
				<div class="case-3">

					<table width="100%" cellpadding="0" cellspacing="0" border="0">
						<tr>
							<td class="col-1">
								Votre Nom
							</td>
							<td class="col-2">
								*
							</td>
							<td class="col-3">

								<!-- Row -->
								<div class="row">

									<div class="text-box"><?=($this->entreprise==false?$this->clients->nom.' '.$this->clients->prenom:$this->companies->name.' / R.C.S. '.$this->companies->rcs)?></div>
									<span class="small-text">Nom / Prénoms du débiteur</span>
								</div>
								<!-- End Row -->

							</td>
							<td class="col-4">
								1
							</td>
						</tr>
						<tr>
							<td class="col-1">
								Votre adresse
							</td>
							<td class="col-2">
								*
							</td>
							<td class="col-3">

								<!-- Row -->
								<div class="row">
									<div class="text-box"><?=($this->entreprise==false?$this->clients_adresses->adresse1:$this->companies->adresse1)?></div>
									<span class="small-text">Numéro et nom de la rue</span>
								</div>
								<!-- End Row -->
								<!-- Row -->
								<div class="row">
									<div class="box box-w-1">
										<span class="star">*</span>
										<div class="text-box"><?=($this->entreprise==false?$this->clients_adresses->cp:$this->companies->zip)?></div>
										<span class="small-text">Code postal</span>
									</div>
									<div class="box box-w-2">
										<span class="star">*</span>
										<div class="text-box"><?=($this->entreprise==false?$this->clients_adresses->ville:$this->companies->city)?></div>
										<span class="small-text">Ville</span>
									</div>
									<div class="cl">&nbsp;</div>
									<span class="number">3</span>
								</div>
								<!-- End Row -->
								<!-- Row -->
								<div class="row">
									<div class="text-box"><?=$this->pays->fr?></div>
									<span class="small-text">Pays</span>
									<span class="star">*</span>
									<span class="number">4</span>
								</div>
								<!-- End Row -->

							</td>
							<td class="col-4">
								2
							</td>
						</tr>
						<tr>
							<td class="col-1">
								Les coordonnées <br />de votre compte
							</td>
							<td class="col-2">
								*
							</td>
							<td class="col-3">


                               	<?
								if($this->leIban != false)
								{

									for($i=1;$i<=7;$i++)
									{
										?><span style="margin-right:10px;"><?=$this->iban[$i]?></span><?
									}
								}
								else
								{
									?><div class="text-box"	>&nbsp;</div><?
								}
                                ?>
									<div class="cl">&nbsp;</div>
									<span class="small-text">Numéro d'identification international du compte bancaire - IBAN (International Bank Account Number)</span>
								</div>
								<div class="row">
                                	<?
									if($this->oLendersAccounts->bic != false || $this->companies->bic !=false)
									{
										?><div><?=($this->entreprise==false?$this->oLendersAccounts->bic:$this->companies->bic)?></div><?
									}
									else
									{
									?><div class="text-box">&nbsp;</div><?
									}
                                    ?>
									<span class="small-text">Code international d'identification de votre banque - BIC (Bank Identifier Code)</span>
									<span class="star">*</span>
									<span class="number">6</span>
								</div>

							</td>
							<td class="col-4">
								5
							</td>
						</tr>
						<tr>
							<td class="col-1">
								Nom du créancier
							</td>
							<td class="col-2">
								*
							</td>
							<td class="col-3">

								<div class="row">
									<div class="text-box"><?=$this->creancier?></div>
									<span class="small-text">Nom du créancier</span>
								</div>
								<div class="row">
									<div class="text-box"><?=$this->creancier_identifiant?></div>
									<span class="small-text">Identifiant du créancier</span>
									<span class="star">*</span>
									<span class="number">8</span>
								</div>
								<div class="row">
									<div class="text-box"><?=$this->creancier_adresse?></div>
									<span class="small-text">Numéro et nom de la rue</span>
									<span class="star">*</span>
									<span class="number">9</span>
								</div>
								<div class="row">
									<div class="box box-w-1">
										<span class="star">*</span>
										<div class="text-box"><?=$this->creancier_cp?></div>
										<span class="small-text">Code postal</span>
									</div>
									<div class="box box-w-2">
										<span class="star">*</span>
										<div class="text-box"><?=$this->creancier_ville?></div>
										<span class="small-text">Ville</span>
									</div>
									<div class="cl">&nbsp;</div>
									<span class="number">10</span>
								</div>
								<div class="row">
									<div class="text-box"><?=$this->creancier_pays?></div>
									<span class="small-text">Pays</span>
									<span class="star">*</span>
									<span class="number">11</span>
								</div>

							</td>
							<td class="col-4">
								7
							</td>
						</tr>
						<tr>
							<td class="col-1">
								Type de paiement
							</td>
							<td class="col-2">
								*
							</td>
							<td class="col-3">

								<div class="row">
									<div class="check-item">
										<span class="text">Paiement récurrent / répétitif</span>
										<ul class="refUnique" style="border: medium none;display: inline;width: 15px;">
											<li style="text-align:center;">X</li>
                                        </ul>
									</div>
									<div class="check-item">
										<span class="text">Paiement ponctuel</span>
										<ul class="refUnique" style="border: medium none;display: inline;width: 15px;">
											<li>&nbsp;</li>
                                        </ul>
									</div>
									<div class="cl">&nbsp;</div>
								</div>

							</td>
							<td class="col-4">
								12
							</td>
						</tr>
						<tr>
							<td class="col-1">
								Signé à
							</td>
							<td class="col-2">
								*
							</td>
							<td class="col-3">

								<div class="row">
									<div class="box box-w-3">
										<div class="text-box">&nbsp; <span class="small-number">(1)</span></div>
										<span class="small-text">Lieu</span>
									</div>
									<div class="box box-w-4">

                                    	<ul class="refUnique" style="border: medium none;display: inline;width: 30px;">
											<?
                                            for($i=0;$i<2;$i++)
                                            {
                                                ?><li>&nbsp;</li><?
                                            }
                                            ?>
                                        </ul>

                                        <ul class="refUnique" style="border: medium none;display: inline;width: 30px;">
											<?
                                            for($i=0;$i<2;$i++)
                                            {
                                                ?><li>&nbsp;</li><?
                                            }
                                            ?>
                                        </ul>

                                        <ul class="refUnique" style="border: medium none;display: inline;width: 90px;">
											<?
                                            for($i=0;$i<4;$i++)
                                            {
                                                ?><li>&nbsp;</li><?
                                            }
                                            ?>
                                        </ul>


										<div class="cl">&nbsp;</div>
										<span class="small-text">Date : jj/mm/aaaa</span>
									</div>
									<div class="cl">&nbsp;</div>
								</div>

							</td>
							<td class="col-4">
								13
							</td>
						</tr>
						<tr>
							<td class="col-1">
								Signature(s)
							</td>
							<td class="col-2">&nbsp;

							</td>
							<td class="col-3">

								<div class="row">
									<span class="l-text">Veuillez signer ici</span>
									<textarea class="field"></textarea>
								</div>

							</td>
							<td class="col-4">&nbsp;

							</td>
						</tr>
					</table>

					<div class="bottom-text">
						Note : Vos droits concernant le présent mandat sont expliqués dans un document que vous pouvez obtenir auprès de votre banque.
					</div>

				</div>
				<!-- End Case 3 -->

				<!-- Case 3 -->
				<?php /*?><div class="case-3 alone"></div><?php */?>
				<!-- End Case 3 -->

				<!-- Case 3 -->
				<?php /*?><div class="case-3">

					<h4>Informations relatives au contrat entre le créancier et le débiteur - fournies seulement à titre indicatif.</h4>
					<table width="100%" cellpadding="0" cellspacing="0" border="0">
						<tr>
							<td class="col-1">
								Code identifiant <br />du débiteur
							</td>
							<td class="col-2">&nbsp;

							</td>
							<td class="col-3">

								<!-- Row -->
								<div class="row">
									<div class="text-box">&nbsp;</div>
									<span class="small-text">Indiquer ici tout code que vous souhaitez voir restitué par votre banque</span>
								</div>
								<!-- End Row -->

							</td>
							<td class="col-4">
								14
							</td>
						</tr>
						<tr>
							<td class="col-1">
								Tiers débiteur pour <br />le compte duquel le <br />paiement est effectué<br />(si différent du débiteur <br />lui-même)
							</td>
							<td class="col-2">&nbsp;

							</td>
							<td class="col-3">

								<div class="row">
									<div class="text-box">&nbsp;</div>
									<span class="small-text">Nom du tiers débiteur : si votre paiement concerne un accord passé entre <?=$this->creancier?> et un tiers <br />(par exemple, vous payez la facture d'une autre personne), veuillez indiquer ici son nom. <br />Si vous payez pour votre propre compte, ne pas remplir.</span>
								</div>
								<div class="row">
									<div class="text-box">&nbsp;</div>
									<span class="small-text">Code identifiant du tiers débiteur</span>
									<span class="number">16</span>
								</div>
								<div class="row">
									<div class="text-box">&nbsp;</div>
									<span class="small-text">Nom du tiers créancier : le créancier doit compléter cette section s'il remet des prélèvements pour le compte d'un tiers.</span>
									<span class="number">17</span>
								</div>
								<div class="row">
									<div class="text-box"><?=$this->creancier_code_id?></div>
									<span class="small-text">Code identifiant du tiers créancier</span>
									<span class="number">18</span>
								</div>
							</td>
							<td class="col-4">
								15
							</td>
						</tr>
						<tr>
							<td class="col-1">
								Contrat concerné
							</td>
							<td class="col-2">&nbsp;

							</td>
							<td class="col-3">

								<div class="row">
									<div class="text-box">&nbsp;</div>
									<span class="small-text">Numéro d'identification du contrat</span>
								</div>
								<div class="row">
									<div class="text-box">&nbsp;</div>
									<span class="small-text">Description du contrat</span>
									<span class="number">20</span>
								</div>
							</td>
							<td class="col-4">
								19
							</td>
						</tr>
					</table>

				</div><?php */?>
				<!-- End Case 3 -->

				<!-- Case 4 -->
				<div class="case-4">
					<table width="100%" cellpadding="0" cellspacing="0" border="0">
						<tr>
							<td class="col-1">
								A retourner à : <?=$this->adresse_retour?>
							</td>
							<td class="col-2">
								Zone réservée à l'usage exclusif du créancier
							</td>
						</tr>
					</table>
				</div>
				<!-- End Case 4 -->

			</div>
			<!-- End Container -->
			<div class="case-5">
				(1) Cette ligne a une longueur maximum de 35 caractères
			</div>

		</div>
		<!-- End Shell -->
	</div>
	<!-- End Pdf Wrapper -->

</body>
</html>