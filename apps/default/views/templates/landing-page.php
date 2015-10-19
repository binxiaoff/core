<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="description" content="<?= $this->meta_description ?>">
    <meta name="keywords" content="<?= $this->meta_keywords ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=0">
    <title><?= $this->meta_title ?><?= ($this->baseline_title != '' ? ' - ' . $this->baseline_title : '') ?></title>
    <link rel="shortcut icon" href="<?= $this->url ?>/landing-page/css/images/favicon.ico">
    <link rel="stylesheet" href="<?= $this->url ?>/landing-page/css/jquery.c2selectbox.css" type="text/css">
    <link rel="stylesheet" href="<?= $this->url ?>/landing-page/css/style.css" type="text/css">
    <script src="<?= $this->url ?>/landing-page/js/modernizr.js"></script>
    <script src="<?= $this->url ?>/landing-page/js/jquery-1.11.0.min.js"></script>
    <script src="<?= $this->url ?>/scripts/default/livevalidation_standalone.compressed.js"></script>
    <script src="<?= $this->url ?>/landing-page/js/jquery.touchSwipe.min.js"></script>
    <script src="<?= $this->url ?>/landing-page/js/jquery.carouFredSel-6.2.1-packed.js"></script>
    <script src="<?= $this->url ?>/landing-page/js/jquery.c2selectbox.js"></script>
    <script src="<?= $this->url ?>/landing-page/js/functions.js"></script>
    <script src="<?= $this->url ?>/scripts/default/main.js"></script>
    <style>
        .page-landing .signup form {padding: 5px 30px 21px;}
        .page-landing .signup .field {margin-bottom: 0;}
        .process ul.cf > li > p > span {color: #727272; font-size: 20px;}
        .error {color: #c84747;}
        .form-row.error {margin: 5px;}
    </style>
</head>
<body>
<?php if ($this->google_analytics != '') { ?>
    <script>
        var _gaq = _gaq || [];
        _gaq.push(['_setAccount', '<?=$this->google_analytics?>']);
        _gaq.push(['_trackPageview']);
        (function () {
            var ga = document.createElement('script');
            ga.type = 'text/javascript';
            ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0];
            s.parentNode.insertBefore(ga, s);
        })();
    </script>
<?php } ?>
<div class="shell page-landing">
    <div class="container cf">
        <section class="content left">
            <header class="page-header cf">
                <a href="<?= $this->content['lp-lien-logo'] ?>" class="logo left">
                    <img src="<?= $this->url ?>/landing-page/css/images/logo.png">
                </a>
                <span class="language-select right tablet-hidden">
                    <img src="<?= $this->url ?>/landing-page/css/images/flag-france.png">
                    <?= $this->content['lp-entreprises-francaises'] ?>
                </span>
            </header>
            <section class="process">
                <h1><?= $this->content['lp-titre-landing-page'] ?></h1>
                <ul class="cf">
                    <li>
                        <img src="<?= $this->photos->display($this->content['lp-gauche-image'], '', 'picto_landing_page') ?>">
                        <p>
                            <strong><?= $this->content['lp-gauche-chiffre'] ?></strong>
                            <span><?= $this->content['lp-gauche-texte'] ?></span>
                        </p>
                    </li>
                    <li>
                        <img src="<?= $this->photos->display($this->content['lp-centre-image'], '', 'picto_landing_page') ?>">
                        <p>
                            <strong><?= $this->content['lp-centre-chiffre'] ?></strong>
                            <span><?= $this->content['lp-centre-texte'] ?></span>
                        </p>
                    </li>
                    <li>
                        <img src="<?= $this->photos->display($this->content['lp-droite-image'], '', 'picto_landing_page') ?>">
                        <p>
                            <strong><?= $this->content['lp-droite-chiffre'] ?></strong>
                            <span><?= $this->content['lp-droite-texte'] ?></span>
                        </p>
                    </li>
                </ul>
                <div class="button-cta">
                    <?= $this->content['lp-creer-compte'] ?>
                    <span class="after"></span>
                </div>
            </section>
        </section>
        <aside class="signup right">
            <h2><?= $this->content['lp-titre-formulaire-inscription'] ?></h2>
            <form action="<?= $_SERVER['SERVER_URI'] ?>" method="post" id="inscription" name="inscription">
                <?php if (isset($this->aForm['response'])) { ?>
                    <div class="form-row error" style="display:inline;"><?= $this->aForm['response'] ?></div>
                <?php } ?>
                <div class="form-row">
                    <span class="euro-sign">€</span>
                    <input type="text" name="montant" id="montant"
                           placeholder="<?= $this->lng['landing-page']['montant-souhaite'] ?>"
                           value="<?= isset($this->aForm['values']['montant']) ? $this->aForm['values']['montant'] : '' ?>"
                           class="field required<?= isset($this->aForm['errors']['montant']) ? ' LV_invalid_field' : '' ?>"
                           data-validators="Presence&amp;Numericality, {maximum:<?= $this->sommeMax ?>}&amp;Numericality, {minimum: <?= $this->sommeMin ?>}"
                           onkeyup="lisibilite_nombre(this.value,this.id);">
                    <em class="jusqua<?= isset($this->aForm['errors']['montant']) ? ' error' : '' ?>"><?= $this->lng['landing-page']['jusqua'] ?></em>
                </div>
                <div style="clear:both;"></div>
                <div class="form-row">
                    <input type="text" name="siren" id="siren"
                           placeholder="<?= $this->lng['landing-page']['siren'] ?>"
                           value="<?= isset($this->aForm['values']['siren']) ? $this->aForm['values']['siren'] : '' ?>"
                           class="field required<?= isset($this->aForm['errors']['siren']) ? ' LV_invalid_field' : '' ?>"
                           data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9, maximum: 9}">
                    <em class="caractmax<?= isset($this->aForm['errors']['siren']) ? ' error' : '' ?>"><?= $this->lng['landing-page']['9-caracteres-numeriques'] ?></em>
                </div>
                <div class="form-row">
                    <input type="email" name="email" id="email"
                           placeholder="<?= $this->lng['landing-page']['email'] ?>"
                           value="<?= isset($this->aForm['values']['email']) ? $this->aForm['values']['email'] : '' ?>"
                           class="field<?= isset($this->aForm['errors']['email']) ? ' LV_invalid_field' : '' ?>"
                           data-validators="Email">
                </div>
                <input type="hidden" name="spy_inscription_landing_page_depot_dossier" value="1">
                <button type="submit" class="button" style="font-family: 'TrendSansOne'; font-weight:normal;">
                    <?= $this->content['lp-bouton-formulaire'] ?>
                    <span class="arrow"></span>
                </button>
            </form>
        </aside>
    </div>
    <?php if (count($this->nbProjects) > 0) { ?>
        <section class="featured-articles">
            <div class="carousel">
                <ul class="slides cf">
                <?php foreach ($this->lProjetsFunding as $project) { ?>
                    <li style="list-style:none;">
                        <div class="slide">
                            <img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $project['photo_projet'] ?>" alt="<?= $project['photo_projet'] ?>">
                            <strong><?= $project['title'] ?></strong>
                            <span></span>
                            <p><?= $project['nature_project'] ?></p>
                        </div>
                    </li>
                <?php } ?>
                </ul>
                <a href="#" class="prev-slide"></a>
                <a href="#" class="next-slide"></a>
            </div>
        </section>
    <?php } ?>
    <section class="partners cf">
        <span><img src="<?= $this->photos->display($this->content['lp-image-1'], '', 'partenaires_landing_page') ?>"></span>
        <span><img src="<?= $this->photos->display($this->content['lp-image-2'], '', 'partenaires_landing_page') ?>"></span>
        <span><img src="<?= $this->photos->display($this->content['lp-image-3'], '', 'partenaires_landing_page') ?>"></span>
        <span class="mobile-hidden"><img src="<?= $this->photos->display($this->content['lp-image-4'], '', 'partenaires_landing_page') ?>"></span>
        <span class="mobile-hidden"><img src="<?= $this->photos->display($this->content['lp-image-5'], '', 'partenaires_landing_page') ?>"></span>
        <span class="tablet-hidden"><img src="<?= $this->photos->display($this->content['lp-image-6'], '', 'partenaires_landing_page') ?>"></span>
        <span class="tablet-hidden"><img src="<?= $this->photos->display($this->content['lp-image-7'], '', 'partenaires_landing_page') ?>"></span>
    </section>
</div>
</body>
</html>
