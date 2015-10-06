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
    <style type="text/css">
        .page-landing .signup form {
            padding: 5px 30px 21px;
        }

        .page-landing .signup .field {
            margin-bottom: 0px;
        }

        .page-landing .process ul li p {
            text-align: center;
        }
    </style>
</head>
<body>
<?php if ($this->google_analytics != '') { ?>
    <script type="text/javascript">
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
    <!-- Google Tag Manager -->
    <noscript>
        <iframe src="//www.googletagmanager.com/ns.html?id=GTM-MB66VL" height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
    <script>(function (w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start': new Date().getTime(), event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src =
                '//www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', 'GTM-MB66VL');</script>
    <!-- End Google Tag Manager -->
<?php } ?>
<div class="shell page-landing">
    <div class="container cf">
        <section class="content left">
            <header class="page-header cf">
                <a target="_blank" href="<?= $this->content['lp-lien-logo-221'] ?>" class="logo left">
                    <img src="<?= $this->url ?>/landing-page/css/images/logo.png" alt="">
                </a>
                <?php if ($this->content['lp-emprunteur-empruntis'] != '') { ?>
                    <img class="empruntis" src="<?= $this->photos->display($this->content['lp-emprunteur-empruntis']) ?>" alt="Empruntis">
                <?php } ?>
            </header>
            <section class="process">
                <h1><?= $this->content['lp-titre-landing-page-222'] ?></h1>
                <ul class="cf">
                    <li>
                        <img src="<?= $this->photos->display($this->content['lp-gauche-image-223']) ?>" alt="">
                        <p>
                            <strong><?= $this->content['lp-gauche-chiffre-224'] ?></strong>
                            <span style="color:#727272;font-size:20px;"><?= $this->content['lp-gauche-texte-225'] ?></span>
                        </p>
                    </li>
                    <li>
                        <img src="<?= $this->photos->display($this->content['lp-centre-image-226']) ?>" alt="">
                        <p>
                            <strong><?= $this->content['lp-centre-chiffre-227'] ?></strong>
                            <span style="color:#727272;font-size:20px;"><?= $this->content['lp-centre-texte-228'] ?></span>
                        </p>
                    </li>
                    <li>
                        <img src="<?= $this->photos->display($this->content['lp-droite-image-229']) ?>" alt="">
                        <p>
                            <strong><?= $this->content['lp-droite-chiffre-230'] ?></strong>
                            <span style="color:#727272;font-size:20px;"><?= $this->content['lp-droite-texte-231'] ?></span>
                        </p>
                    </li>
                </ul>
                <div class="button-cta emprunteur">
                    <?= $this->content['lp-creer-compte-232'] ?>
                    <span class="after"></span>
                </div>
            </section>
        </section>
        <aside class="signup right">
            <h2><?= $this->content['lp-titre-formulaire'] ?></h2>
            <form action="<?= $this->lurl ?>/depot_de_dossier/etape1" method="post" id="depot_de_dossier" name="depot_de_dossier">
                <?php if (isset($this->retour_form)) { ?>
                <div class="form-row" style="display:inline;">
                    <span style="text-align:center; color:#C84747;"><?= $this->retour_form ?></span>
                </div>
                <?php } ?>
                <div class="form-row">
                    <span class="euro-sign">€</span>
                    <input type="text" class="field required" placeholder="<?= $this->lng['landing-page']['montant-souhaite'] ?>" value="<?= isset($_POST['montant']) ? $_POST['montant'] : '' ?>" title="<?= $this->lng['landing-page']['montant-souhaite'] ?>" name="montant" id="montant" data-validators="Presence&amp;Numericality, {maximum:<?= $this->sommeMax ?>}&amp;Numericality, {minimum: <?= $this->sommeMin ?>}" onkeyup="lisibilite_nombre(this.value,this.id);">
                    <em class="jusqua"><?= $this->lng['landing-page']['jusqua'] ?></em>
                </div>
                <div style="clear:both;"></div>
                <div class="form-row">
                    <input type="text" class="field required" placeholder="<?= $this->lng['landing-page']['siren'] ?>" value="<?= isset($_POST['siren']) ? $_POST['siren'] : '' ?>" title="<?= $this->lng['landing-page']['siren'] ?>" name="siren" id="siren" data-validators="Presence&amp;Numericality&amp;Length, {minimum: 9, maximum: 9}">
                    <em class="caractmax"><?= $this->lng['landing-page']['9-caracteres-numeriques'] ?></em>
                </div>
                <div class="form-row">
                    <input type="text" placeholder="<?= $this->lng['landing-page']['email'] ?>" value="<?= isset($_POST['email']) ? $_POST['email'] : '' ?>" title="<?= $this->lng['landing-page']['email'] ?>" name="email" id="email">
                </div>
                <input type="hidden" name="spy_inscription_landing_page_depot_dossier" value="1">
                <button type="submit" class="button" style="font-family: 'TrendSansOne'; font-weight:normal;text-transform: uppercase;">
                    <?= $this->content['lp-bouton-formulaire-241'] ?>
                    <span class="arrow"></span>
                </button>
            </form>
        </aside>
    </div>
    <?php if (count($this->nbProjects) > 0) { ?>
        <section class="featured-articles">
            <div class="carousel">
                <ul class="slides cf">
                    <?php
                    foreach ($this->lProjetsFunding as $project) {
                        ?>
                        <li style="list-style:none;">
                            <div class="slide">
                                <img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $project['photo_projet'] ?>" alt="<?= $project['photo_projet'] ?>">

                                <strong><?= $project['title'] ?></strong>

                                <span></span>

                                <p><?= $project['nature_project'] ?></p>
                            </div>
                        </li>
                        <?php
                    }
                    ?>
                </ul>
                <a href="#" class="prev-slide"></a>
                <a href="#" class="next-slide"></a>
            </div>
        </section>
    <?php } ?>
    <section class="partners cf">
        <span><img src="<?= $this->photos->display($this->content['lp-image-1-233'], '', 'partenaires_landing_page') ?>" alt=""></span>
        <span><img src="<?= $this->photos->display($this->content['lp-image-2-234'], '', 'partenaires_landing_page') ?>" alt=""></span>
        <span><img src="<?= $this->photos->display($this->content['lp-image-3-235'], '', 'partenaires_landing_page') ?>" alt=""></span>
        <span class="mobile-hidden"><img src="<?= $this->photos->display($this->content['lp-image-4-236'], '', 'partenaires_landing_page') ?>" alt=""></span>
        <span class="mobile-hidden"><img src="<?= $this->photos->display($this->content['lp-image-5-237'], '', 'partenaires_landing_page') ?>" alt=""></span>
        <span class="tablet-hidden"><img src="<?= $this->photos->display($this->content['lp-image-6-238'], '', 'partenaires_landing_page') ?>" alt=""></span>
        <span class="tablet-hidden"><img src="<?= $this->photos->display($this->content['lp-image-7-239'], '', 'partenaires_landing_page') ?>" alt=""></span>
    </section>
</div>
</body>
</html>
