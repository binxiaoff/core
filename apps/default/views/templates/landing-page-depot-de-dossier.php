<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="description" content="<?= $this->meta_description ?>">
    <meta name="keywords" content="<?= $this->meta_keywords ?>">
    <?php if (0 == $this->tree->indexation) : ?>
        <meta name="robots" content="noindex">
    <?php endif; ?>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=0">
    <title><?= $this->meta_title ?><?= (empty($this->baseline_title) ? '' : ' - ' . $this->baseline_title) ?></title>
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
        .form-row > em {display: inline-block; margin: 5px;}
        #depot_de_dossier .button.submit {font-family: 'TrendSansOne'; font-weight: normal; margin-top: 25px; text-transform: uppercase;}
    </style>
</head>
<body>
<?php if (strstr($_SERVER['HTTP_HOST'], 'dev.corp') || strstr($_SERVER['HTTP_HOST'], 'demo.corp') || strstr($_SERVER['HTTP_HOST'], 'dev.www')) {
    $sGTMEnv = 'GTM-W2WQKW';
} else {
    $sGTMEnv = 'GTM-MB66VL';
}
?>
    <!-- Google Tag Manager -->
    <noscript>
        <iframe src="//www.googletagmanager.com/ns.html?id=<?= $sGTMEnv ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
    <script>(function (w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start': new Date().getTime(), event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src = '//www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', '<?= $sGTMEnv ?>');</script>
    <!-- End Google Tag Manager -->
<div class="shell page-landing">
    <div class="container cf">
        <section class="content left">
            <header class="page-header cf">
                <a target="_blank" href="<?= $this->content['lp-lien-logo-221'] ?>" class="logo left">
                    <img src="<?= $this->url ?>/landing-page/css/images/logo.png" alt="Unilend">
                </a>
                <?php if ($this->content['lp-emprunteur-empruntis'] != '') : ?>
                    <img class="empruntis" src="<?= $this->photos->display($this->content['lp-emprunteur-empruntis']) ?>" alt="<?= addslashes($this->complement['lp-emprunteur-empruntis']) ?>">
                <?php endif; ?>
            </header>
            <section class="process">
                <h1><?= $this->content['lp-titre-landing-page-222'] ?></h1>
                <ul class="cf">
                    <li>
                        <img src="<?= $this->photos->display($this->content['lp-gauche-image-223']) ?>" alt="<?= addslashes($this->content['lp-gauche-texte-225']) ?>">
                        <p>
                            <strong><?= $this->content['lp-gauche-chiffre-224'] ?></strong>
                            <span><?= $this->content['lp-gauche-texte-225'] ?></span>
                        </p>
                    </li>
                    <li>
                        <img src="<?= $this->photos->display($this->content['lp-centre-image-226']) ?>" alt="<?= addslashes($this->content['lp-centre-texte-228']) ?>">
                        <p>
                            <strong><?= $this->content['lp-centre-chiffre-227'] ?></strong>
                            <span><?= $this->content['lp-centre-texte-228'] ?></span>
                        </p>
                    </li>
                    <li>
                        <img src="<?= $this->photos->display($this->content['lp-droite-image-229']) ?>" alt="<?= addslashes($this->content['lp-droite-texte-231']) ?>">
                        <p>
                            <strong><?= $this->content['lp-droite-chiffre-230'] ?></strong>
                            <span><?= $this->content['lp-droite-texte-231'] ?></span>
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
            <form action="<?= parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?>" method="post" id="depot_de_dossier" name="depot_de_dossier">
                <?php if (isset($this->aForm['response'])) : ?>
                    <div class="form-row error" style="display:inline;"><?= $this->aForm['response'] ?></div>
                <?php endif; ?>
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
                <?php if (false === $this->bShortTunnel) : ?>
                <div class="form-row">
                    <input type="email" name="email" id="email"
                           placeholder="<?= $this->lng['landing-page']['email'] ?>"
                           value="<?= isset($this->aForm['values']['email']) ? $this->aForm['values']['email'] : '' ?>"
                           class="field<?= isset($this->aForm['errors']['email']) ? ' LV_invalid_field' : '' ?>"
                           data-validators="Email">
                </div>
                <?php endif; ?>
                <input type="hidden" name="spy_inscription_landing_page_depot_dossier" value="1">
                <button type="submit" class="button submit">
                    <?= $this->content['lp-bouton-formulaire-241'] ?>
                    <span class="arrow"></span>
                </button>
            </form>
        </aside>
    </div>
    <?php if (count($this->nbProjects) > 0) : ?>
        <section class="featured-articles">
            <div class="carousel">
                <ul class="slides cf">
                <?php foreach ($this->lProjetsFunding as $project) : ?>
                    <li style="list-style:none;">
                        <div class="slide">
                            <img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $project['photo_projet'] ?>" alt="<?= addslashes($project['title']) ?>">
                            <strong><?= $project['title'] ?></strong>
                            <p><?= $project['nature_project'] ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
                </ul>
                <a href="#" class="prev-slide"></a>
                <a href="#" class="next-slide"></a>
            </div>
        </section>
    <?php endif; ?>
    <section class="partners cf">
        <span><img src="<?= $this->photos->display($this->content['lp-image-1-233'], '', 'partenaires_landing_page') ?>"></span>
        <span><img src="<?= $this->photos->display($this->content['lp-image-2-234'], '', 'partenaires_landing_page') ?>"></span>
        <span><img src="<?= $this->photos->display($this->content['lp-image-3-235'], '', 'partenaires_landing_page') ?>"></span>
        <span class="mobile-hidden"><img src="<?= $this->photos->display($this->content['lp-image-4-236'], '', 'partenaires_landing_page') ?>"></span>
        <span class="mobile-hidden"><img src="<?= $this->photos->display($this->content['lp-image-5-237'], '', 'partenaires_landing_page') ?>"></span>
        <span class="tablet-hidden"><img src="<?= $this->photos->display($this->content['lp-image-6-238'], '', 'partenaires_landing_page') ?>"></span>
        <span class="tablet-hidden"><img src="<?= $this->photos->display($this->content['lp-image-7-239'], '', 'partenaires_landing_page') ?>"></span>
    </section>
</div>
<!--[if lte IE 9]>
<script type="text/javascript" src="<?= $this->surl ?>/scripts/default/placeholders.jquery.min.js"></script>
<![endif]-->
</body>
</html>
