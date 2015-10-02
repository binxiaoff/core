<!DOCTYPE html>
<html lang="fr">
<head>
	<title><?=$this->meta_title?><?=($this->baseline_title!=''?' - '.$this->baseline_title:'')?></title>
	<meta name="description" content="<?=$this->meta_description?>" />
    <meta name="keywords" content="<?=$this->meta_keywords?>" />

	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=0" />

	<link rel="shortcut icon" href="<?=$this->surl?>/landing-page/css/images/favicon.ico" />

	<link rel="stylesheet" href="<?=$this->surl?>/landing-page/css/jquery.c2selectbox.css" type="text/css" />
	<link rel="stylesheet" href="<?=$this->surl?>/landing-page/css/style.css" type="text/css" />

	<script src="<?=$this->surl?>/landing-page/js/modernizr.js"></script>
	<script src="<?=$this->surl?>/landing-page/js/jquery-1.11.0.min.js"></script>
	<script src="<?=$this->surl?>/scripts/default/livevalidation_standalone.compressed.js"></script>
	<script src="<?=$this->surl?>/landing-page/js/jquery.touchSwipe.min.js"></script>
	<script src="<?=$this->surl?>/landing-page/js/jquery.carouFredSel-6.2.1-packed.js"></script>
	<script src="<?=$this->surl?>/landing-page/js/jquery.c2selectbox.js"></script>
    <script src="<?=$this->surl?>/landing-page/js/functions.js"></script>
    <script src="<?=$this->surl?>/scripts/default/main.js"></script>




</head>
<body>

<?php
if($this->google_analytics != '')
{
?>
    <script type="text/javascript">
        var _gaq = _gaq || [];
        _gaq.push(['_setAccount', '<?=$this->google_analytics?>']);
        _gaq.push(['_trackPageview']);
        (function() {
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
        })();
    </script>
<?php
}
?>


    <style type="text/css">
    .page-landing .signup form{ padding: 5px 30px 21px; }
	.page-landing .signup .field{margin-bottom:0px;}
	.page-landing .process ul li p{text-align:center;}
	.page-landing {width: 1092px;}
    </style>

	<div class="shell page-landing">
		<div class="container cf">
			<section class="content left">
				<header class="page-header cf">
					<a target="_blank" href="<?=$this->content['lp-lien-logo-247']?>" class="logo left">
						<img src="<?=$this->surl?>/landing-page/css/images/logo.png" alt="" />
					</a>


				</header><!-- /.page-header -->

				<section class="process">
					<h1><?=$this->content['lp-titre-landing-page-248']?></h1>

					<ul class="cf">
						<li>
							<img src="<?=$this->photos->display($this->content['lp-gauche-image-249'])?>" alt="" />
							<p>
								<strong><?=$this->content['lp-gauche-chiffre-250']?></strong>

								<span style="color:#727272;font-size:20px;"><?=$this->content['lp-gauche-texte-251']?></span>
							</p>
						</li>

						<li>
							<img src="<?=$this->photos->display($this->content['lp-centre-image-252'])?>" alt="" />

							<p>
								<strong><?=$this->content['lp-centre-chiffre-253']?></strong>

								<span style="color:#727272;font-size:20px;"><?=$this->content['lp-centre-texte-254']?></span>
							</p>
						</li>

						<li>
							<img src="<?=$this->photos->display($this->content['lp-droite-image-255'])?>" alt="" />

							<p>
								<strong><?=$this->content['lp-droite-chiffre-256']?></strong>

								<span style="color:#727272;font-size:20px;"><?=$this->content['lp-droite-texte-257']?></span>
							</p>
						</li>
					</ul><!-- /.cf -->

					<div class="button-cta emprunteur">
						<?=$this->content['lp-creer-compte-258']?>

						<span class="after"></span>
					</div>
				</section><!-- /.process -->
			</section><!-- /.content -->

			<aside class="signup right">
				<h2><?=$this->content['lp-titre-formulaire-266']?></h2>

                                <form target="_parent" action="http://emprunt-entreprise.lentreprise.lexpress.fr/<?=$this->le_slug?>" method="post" id="depot_de_dossier" name="depot_de_dossier">

                    <div class="form-row" style="display:inline;">
                    	<span style="text-align:center; color:#C84747;"><?=$this->retour_form?></span>
                    </div>
                    <div class="form-row">
                    	<span class="euro-sign">€</span>
						<input type="text" class="field required" value="<?=(isset($_POST['montant'])?$_POST['montant']:$this->lng['landing-page']['montant-souhaite'])?>" title="<?=$this->lng['landing-page']['montant-souhaite']?>" name="montant" id="montant" data-validators="Presence&amp;Numericality, { maximum:<?=$this->sommeMax?> }&amp;Numericality, { minimum:<?=$this->sommeMin?> }" onkeyup="lisibilite_nombre(this.value,this.id);" />

                        <em class="jusqua"><?=$this->lng['landing-page']['jusqua']?></em>
					</div><!-- /.form-row -->



					<div class="form-row">


                        <select name="duree" id="duree" class="field field-large required custom-select selectbox_duree" >
                            <option value="0"><?=$this->lng['landing-page']['duree-de-remboursement']?></option>

                            <option value="24">24 mois</option>
                            <option value="36">36 mois</option>
                            <option value="48">48 mois</option>
                            <option value="60">60 mois</option>
                        </select>

					</div><!-- /.form-row -->

                    <div style="clear:both;"></div>

					<div class="form-row">
						<input type="text" class="field required" value="<?=(isset($_POST['siren'])?$_POST['siren']:$this->lng['landing-page']['siren'])?>" title="<?=$this->lng['landing-page']['siren']?>" name="siren" id="siren" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9, maximum: 9}" />
                        <em class="caractmax"><?=$this->lng['landing-page']['9-caracteres-numeriques']?></em>
					</div><!-- /.form-row -->

                    <div class="form-row exercice_comptable_check">

                        <p style="font-size:16px;"><?=$this->lng['landing-page']['lentreprise-a-t-elle-cloture-au-moins-trois-exercices-comptables']?></p>

                        <div style="display:inline-block;margin-top: 5px;" class="radio-holder exercice_comptable_check">
                        	<input type="radio" name="exercices_comptables" id="exercices_comptables_oui" value="1" class="custom-input">
                            <label for="exercices_comptables_oui"><?=$this->lng['landing-page']['oui']?></label>
                        </div>
                        &nbsp;&nbsp;&nbsp;
                        <div style="display:inline-block;" class="radio-holder exercice_comptable_check">

                            <input type="radio" name="exercices_comptables" id="exercices_comptables_non" value="0" class="custom-input">
                            <label for="exercices_comptables_non"><?=$this->lng['landing-page']['non']?></label>
                        </div>

                    </div><!-- /.form-row -->


					<input type="hidden" name="spy_inscription_landing_page_depot_dossier" value="1"/>
					<button type="submit" class="button" style="font-family: 'TrendSansOne'; font-weight:normal;text-transform: uppercase;">
						<?=$this->content['lp-bouton-formulaire-267']?>
						<span class="arrow"></span>
					</button>
				</form>
			</aside><!-- /.signup -->
		</div><!-- /.container -->


		<?php
		if(count($this->nbProjects) > 0)
		{
			?>

            <section class="featured-articles">
                <div class="carousel">
                    <ul class="slides cf">
                    	<?php
						foreach($this->lProjetsFunding as $project)
						{
							?>
                            <li style="list-style:none;">
                                <div class="slide">
                                	<img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $project['photo_projet'] ?>"  alt="<?=$project['photo_projet']?>">

                                    <strong><?=$project['title']?></strong>

                                    <span></span>

                                    <p><?=$project['nature_project']?></p>
                                </div>
                            </li>
                            <?php
						}
                    	?>
                    </ul><!-- /.slides -->
                    <a href="#" class="prev-slide"></a>
					<a href="#" class="next-slide"></a>

                </div><!-- /.carousel -->
            </section><!-- /.featured-articles -->
            <?php
		}
		?>

		<?php /*?><section class="partners cf">
			<span>
				<img src="<?=$this->photos->display($this->content['lp-image-1-259'],'','partenaires_landing_page')?>" alt="" />
			</span>

			<span>
				<img src="<?=$this->photos->display($this->content['lp-image-2-260'],'','partenaires_landing_page')?>" alt="" />
			</span>

			<span>
				<img src="<?=$this->photos->display($this->content['lp-image-3-261'],'','partenaires_landing_page')?>" alt="" />
			</span>

			<span class="mobile-hidden">
				<img src="<?=$this->photos->display($this->content['lp-image-4-262'],'','partenaires_landing_page')?>" alt="" />
			</span>

			<span class="mobile-hidden">
				<img src="<?=$this->photos->display($this->content['lp-image-5-263'],'','partenaires_landing_page')?>" alt="" />
			</span>

			<span class="tablet-hidden">
				<img src="<?=$this->photos->display($this->content['lp-image-6-264'],'','partenaires_landing_page')?>" alt="" />
			</span>

			<span class="tablet-hidden">
				<img src="<?=$this->photos->display($this->content['lp-image-7-265'],'','partenaires_landing_page')?>" alt="" />
			</span>
		</section><!-- /.partners --><?php */?>
	</div><!-- /.shell -->

</body>
</html>