<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en-US" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
	<title>UNILEND</title>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<link rel="shortcut icon" href="<?=$this->surl?>/styles/default/pdf_facture/images/favicon.ico" />
	<link rel="stylesheet" href="<?=$this->surl?>/styles/default/pdf_facture/style.css" type="text/css" media="all" />
</head>
<body>
	<div class="container">
		<div class="header clearfix">
			<h1 class="logo"><a href="#">Unilend</a></h1><!-- /.logo -->
		</div><!-- /.header -->

		<div class="main">
			<div class="section clearfix">
				<h2><?=$this->lng['pdf-facture']['facture']?></h2>

				<ul>
					<li>&nbsp;</li>
					<li><?=$this->lng['pdf-facture']['facture-num']?> <?=$this->num_facture?></li>
					<li><?=$this->lng['pdf-facture']['date']?> : <?=date('d/m/Y',strtotime($this->dateRemb))?></li>
					<li><?=$this->lng['pdf-facture']['id-client']?> : <?=$this->clients->id_client?></li>
				</ul>

				<ul>
					<li><?=$this->lng['pdf-facture']['facture-a']?> :</li>
					<li><?=$this->companies->name?></li>
					<li><?=$this->companies->adresse1?></li>
					<li><?=$this->companies->zip?>	<?=$this->companies->city?></li>
				</ul>
			</div><!-- /.section -->

			<div class="block clearfix">
				<h4><?=$this->lng['pdf-facture']['commission-de-financement-du-projet']?> &laquo; <?=$this->projects->nature_project?> &raquo;</h4>

				<ul>
					<li>
						<span><?=$this->lng['pdf-facture']['commission-de-3-h.t.']?> :</span>
						<span><?=number_format($this->ht, 2, ',', ' ')?> €</span>
					</li>
					<li>
						<span><?=$this->lng['pdf-facture']['tva']?>	(<?=number_format(($this->tva*100), 2, ',', ' ')?>%)	:</span>
						<span><?=number_format($this->taxes, 2, ',', ' ')?> €</span>
					</li>
					<li class="total">
						<span><?=$this->lng['pdf-facture']['total-ttc']?> :</span>
						<span><?=number_format($this->ttc, 2, ',', ' ')?> €</span>
					</li>
					<li>
						<span>&nbsp;</span>
					</li>
					<li>
						<span><?=$this->lng['pdf-facture']['echeance-a-reception']?></span>
					</li>
					<li>
						<span>&nbsp;</span>
						<span><strong><?=$this->lng['pdf-facture']['regle-par-prelevement-le']?> <?=date('d/m/Y',strtotime($this->dateRemb))?>.</strong></span>
					</li>
				</ul>
			</div><!-- /.block -->

			<div class="info-block">
				<div class="info-head">
					<h4><?=$this->lng['pdf-facture']['votre-contact']?> :</h4>
					<p><?=$this->lng['pdf-facture']['adresse-contact']?></p>
				</div><!-- /.info-head -->

				<div class="info-body">
					<p><?=$this->lng['pdf-facture']['taux-de-tva-applicable']?>	: <?=number_format(($this->tva*100), 2, ',', ' ')?>%.</p>
					<p><?=$this->lng['pdf-facture']['en-cas-de-non-paiement']?></p>
				</div><!-- /.info-body -->
			</div><!-- /.info-block -->

			
		</div><!-- /.main -->
	</div><!-- /.container -->
</body>
</html>