<!DOCTYPE html>
<html lang="fr">
<head>
	<title><?=$this->meta_title?><?=($this->baseline_title!=''?' - '.$this->baseline_title:'')?></title>
	<meta name="description" content="<?=$this->meta_description?>" />
    <meta name="keywords" content="<?=$this->meta_keywords?>" />

	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=0" />

	<link rel="shortcut icon" href="<?=$this->url?>/landing-page/css/images/favicon.ico" />

	<link rel="stylesheet" href="<?=$this->url?>/landing-page/css/jquery.c2selectbox.css" type="text/css" />
	<link rel="stylesheet" href="<?=$this->url?>/landing-page/css/style.css" type="text/css" />

	<script src="<?=$this->url?>/landing-page/js/modernizr.js"></script>
	<script src="<?=$this->url?>/landing-page/js/jquery-1.11.0.min.js"></script>
	<script src="<?=$this->url?>/scripts/default/livevalidation_standalone.compressed.js"></script>
	<script src="<?=$this->url?>/landing-page/js/jquery.touchSwipe.min.js"></script>
	<script src="<?=$this->url?>/landing-page/js/jquery.carouFredSel-6.2.1-packed.js"></script>
    <script src="<?=$this->url?>/landing-page/js/jquery.c2selectbox.js"></script>
	<script src="<?=$this->url?>/landing-page/js/functions.js"></script>
  
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


	<div class="shell page-landing">
		<div class="container cf">
			<section class="content left">
				<header class="page-header cf">
					<a href="<?=$this->content['lp-lien-logo']?>" class="logo left">                    	
						<img src="<?=$this->url?>/landing-page/css/images/logo.png" alt="" />
					</a>

					<span class="language-select right tablet-hidden">
						<img src="<?=$this->url?>/landing-page/css/images/flag-france.png" alt="" />

						<?=$this->content['lp-entreprises-francaises']?>
					</span>
				</header><!-- /.page-header -->

				<section class="process">
					<h1><?=$this->content['lp-titre-landing-page']?></h1>

					<ul class="cf">
						<li>
							<img src="<?=$this->photos->display($this->content['lp-gauche-image'],'','picto_landing_page')?>" alt="" />
							<p>
								<strong><?=$this->content['lp-gauche-chiffre']?></strong>

								<?=$this->content['lp-gauche-texte']?>
							</p>
						</li>

						<li>
							<img src="<?=$this->photos->display($this->content['lp-centre-image'],'','picto_landing_page')?>" alt="" />

							<p>
								<strong><?=$this->content['lp-centre-chiffre']?></strong>

								<?=$this->content['lp-centre-texte']?>
							</p>
						</li>

						<li>
							<img src="<?=$this->photos->display($this->content['lp-droite-image'],'','picto_landing_page')?>" alt="" />

							<p>
								<strong><?=$this->content['lp-droite-chiffre']?></strong>

								<?=$this->content['lp-droite-texte']?>
							</p>
						</li>
					</ul><!-- /.cf -->

					<div class="button-cta">
						<?=$this->content['lp-creer-compte']?>

						<span class="after"></span>
					</div>
				</section><!-- /.process -->
			</section><!-- /.content -->

			<aside class="signup right">
				<h2><?=$this->content['lp-titre-formulaire-inscription']?></h2>

				<form action="" method="post" id="inscription" name="inscription">
					
                    <div class="form-row">                    	
                    	<span style="text-align:center; color:#C84747;"><?=$this->retour_form?></span>
                    </div>
                    <div class="form-row">
						<input type="text" class="field required" value="<?=(isset($_POST['nom'])?$_POST['nom']:$this->lng['landing-page']['nom'])?>" title="<?=$this->lng['landing-page']['nom']?>" name="nom" id="signup-first-name" data-validators="Presence" onkeyup="noNumber(this.value,this.id);"/>
					</div><!-- /.form-row -->

					<div class="form-row">
						<input type="text" class="field required" value="<?=(isset($_POST['prenom'])?$_POST['prenom']:$this->lng['landing-page']['prenom'])?>" title="<?=$this->lng['landing-page']['prenom']?>" name="prenom" id="signup-last-name" data-validators="Presence" onkeyup="noNumber(this.value,this.id);"/>
					</div><!-- /.form-row -->

					<div class="form-row">
						<input type="text" class="field required" value="<?=(isset($_POST['email'])?$_POST['email']:$this->lng['landing-page']['email'])?>" title="<?=$this->lng['landing-page']['email']?>" name="email" id="signup-email" data-validators="Presence&amp;Email" oncopy="return false;" oncut="return false;" onKeyUp="check_conf_mail()"/>
					</div><!-- /.form-row -->

					<div class="form-row">
						<input type="text" class="field " value="<?=(isset($_POST['email-confirm'])?$_POST['email-confirm']:$this->lng['landing-page']['confirmation-email'])?>" title="<?=$this->lng['landing-page']['confirmation-email']?>" name="email-confirm" id="signup-email-confirm" onpast="return false;" onKeyUp="check_conf_mail()"/>
					</div><!-- /.form-row -->
					<input type="hidden" name="spy_inscription_landing_page" value="1"/> 
					<button type="submit" class="button" style="font-family: 'TrendSansOne'; font-weight:normal;">
						<?=$this->content['lp-bouton-formulaire']?>
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
                                	<img src="<?=$this->photos->display($project['photo_projet'],'photos_projets','img_carousel_landing_page')?>"  alt="<?=$project['photo_projet']?>">                     						
                                    
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

		<section class="partners cf">
			<span>
				<img src="<?=$this->photos->display($this->content['lp-image-1'],'','partenaires_landing_page')?>" alt="" />
			</span>

			<span>
				<img src="<?=$this->photos->display($this->content['lp-image-2'],'','partenaires_landing_page')?>" alt="" />
			</span>

			<span>
				<img src="<?=$this->photos->display($this->content['lp-image-3'],'','partenaires_landing_page')?>" alt="" />
			</span>

			<span class="mobile-hidden">
				<img src="<?=$this->photos->display($this->content['lp-image-4'],'','partenaires_landing_page')?>" alt="" />
			</span>

			<span class="mobile-hidden">
				<img src="<?=$this->photos->display($this->content['lp-image-5'],'','partenaires_landing_page')?>" alt="" />
			</span>

			<span class="tablet-hidden">
				<img src="<?=$this->photos->display($this->content['lp-image-6'],'','partenaires_landing_page')?>" alt="" />
			</span>

			<span class="tablet-hidden">
				<img src="<?=$this->photos->display($this->content['lp-image-7'],'','partenaires_landing_page')?>" alt="" />
			</span>
		</section><!-- /.partners -->
	</div><!-- /.shell -->
</body>
</html>