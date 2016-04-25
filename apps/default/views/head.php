<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!--[if lt IE 7]>      <html class="no-js lt-ie10 lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie10 lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie10 lt-ie9"> <![endif]-->
<!--[if IE 9]>         <html class="no-js lt-ie10"> <![endif]-->
<!--[if gt IE 9]><!--> <html class="no-js<?= empty($this->error_login) ? '' : 'show-login' ?>" xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= $this->language ?>" lang="<?= $this->language ?>"> <!--<![endif]-->
    <head>
        <?php if ($this->google_webmaster_tools != '') { ?>
            <meta name="google-site-verification" content="<?= $this->google_webmaster_tools ?>" />
        <?php } ?>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title><?= (isset($this->meta_title)) ? $this->meta_title : 'Unilend' ?><?= ($this->baseline_title != '' ? ' - ' . $this->baseline_title : '') ?></title>
        <meta name="description" content="<?= (isset($this->meta_description)) ? $this->meta_description : '' ?>" />
        <meta name="keywords" content="<?= (isset($this->meta_keywords)) ? $this->meta_keywords : '' ?>" />
        <!--[if IE]><script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
        <?= ($this->tree->id_tree != '' && $this->tree->indexation == 0 ? '<meta name="robots" content="noindex,nofollow" />' : '') ?>
        <link rel="shortcut icon" href="<?= $this->surl ?>/styles/default/images/favicon.ico" type="image/x-icon" />
        <script>
            var add_surl = '<?= $this->surl ?>';
            var add_url = '<?= $this->lurl ?>';
        </script>
        <?php $this->callCss(); ?>
        <?php $this->callJs(); ?>
        <meta name="viewport" id="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
        <script>
            if (screen.width > 767) {
                var mvp = document.getElementById('viewport');
                mvp.setAttribute('content', 'width=1024');
            }
        </script>
        <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/responsive/fonts.css" type="text/css" media="all" />
        <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/responsive/responsive.css" type="text/css" media="all" />
        <script type="text/javascript" src="<?= $this->surl ?>/scripts/default/responsive/responsive.js"></script>
    </head>
    <body class="has-fixed-nav">
        <?php
        if ($this->Config['env'] == 'prod') {
            // Etape 2 inscription preteur
            if ($this->page_preteur == 2) {
                ?>
                <iframe src="https://nodes.network-touchvibes.com/scripts/tracking.php?params=466|4&track=<?= $this->clients->id_client ?>" width="1" height="1" marginwidth="0" marginheight="0" frameborder="0" scrolling="no"></iframe>
                <img src="https://ext.ligatus.com/conversion/?c=77615&a=10723" width="1" height="1" />
                <?php
            }
            // Etape 3 inscription preteur
            if ($this->page_preteur == 3) {
                ?>
                <iframe src="https://tracking.unilend-partners.com/mastertags/3.html?action=lead&pid=3&type=4&uniqueid=<?= $this->clients->id_client ?>"  width="1" height="1" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" style="border:none;"></iframe>
                <?php
            }
            // tracking apres validation landing page
            if (isset($_SESSION['landing_page']) && $_SESSION['landing_page'] == true) {
                // deplacé dans etape 2 preteur
                unset($_SESSION['landing_page']);
            }
            if (isset($this->bDisplayTouchvibes) && true === $this->bDisplayTouchvibes) {
            ?>
                <iframe src="https://nodes.network-touchvibes.com/scripts/tracking.php?params=526|4&track=<?= $this->clients->id_client ?>" width="1" height="1" marginwidth="0" marginheight="0" frameborder="0" scrolling="no"></iframe>
            <?php
            }
            if ($this->page == 'depot_dossier_2') {
            ?>
                <img src="https://ext.ligatus.com/conversion/?c=74703&a=9617" width="1" height="1" />
            <?php
                } elseif ($this->tree->id_tree == 48) {// confirmation depot de dossier
            ?>
                <img src="https://ms.ligatus.com/de/track/triggerext.php?cn=trcn74703" width="1" height="1">
            <?php
                }

                // projets-a-financer
                if ($this->tree->id_tree == 4) {
                    if (isset($_SESSION['LP_id_unique'])) {
                        $this->clients->get($_SESSION['LP_id_unique'], 'id_client');
                        unset($_SESSION['LP_id_unique']);
                    }
            ?>
                    <iframe src="https://nodes.network-touchvibes.com/scripts/tracking.php?params=466|4&track=<?= $this->clients->id_client ?>" width="1" height="1" marginwidth="0" marginheight="0" frameborder="0" scrolling="no"></iframe>
                    <iframe src="https://tracking.unilend-partners.com/mastertags/3.html?action=lead&pid=3&type=13&uniqueid=<?= $this->clients->id_client ?>"  width="1" height="1" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" style="border:none;"></iframe>
            <?php
                }
            }
            ?>
            <script>var dataLayer = [<?= json_encode($this->aDataLayer) ?>];</script>
            <!-- Google Tag Manager -->
            <noscript><iframe src="//www.googletagmanager.com/ns.html?id=<?= $this->google_tag_manager ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
            <script>(function (w, d, s, l, i) {
                    w[l] = w[l] || [];
                    w[l].push({'gtm.start':
                                new Date().getTime(), event: 'gtm.js'});
                    var f = d.getElementsByTagName(s)[0],
                            j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
                    j.async = true;
                    j.src =
                            '//www.googletagmanager.com/gtm.js?id=' + i + dl;
                    f.parentNode.insertBefore(j, f);
                })(window, document, 'script', 'dataLayer', '<?= $this->google_tag_manager ?>');</script>
            <!-- End Google Tag Manager -->

            <div id="fb-root"></div>
            <script>(function (d, s, id) {
                    var js, fjs = d.getElementsByTagName(s)[0];
                    if (d.getElementById(id))
                        return;
                    js = d.createElement(s);
                    js.id = id;
                    js.src = "//connect.facebook.net/fr_FR/sdk.js#xfbml=1&appId=644523195564400&version=v2.0";
                    fjs.parentNode.insertBefore(js, fjs);
                }(document, 'script', 'facebook-jssdk'));</script>

            <?php
            // Bouton pour les traductions
            if (isset($_SESSION['user']['id_user']) && $_SESSION['user']['id_user'] != '' && $_SERVER['REMOTE_ADDR'] == '93.26.42.99') {
                // Si les modifications sont actives on desactive les liens
                if ($_SESSION['modification'] == 1) {
                    ?>
                    <script type="text/javascript">
                        $(document).ready(function () {
                            $('a').click(function () {
                                return false;
                            });
                        });
                    </script>
                    <?php
                }
                ?>
                <div class="blocAdminIzI">
                    <a onclick="activeModificationsTraduction(<?= ($_SESSION['modification'] == 1 ? 0 : 1) ?>, '<?= $_SERVER['REQUEST_URI'] ?>');" class="boutonAdm" title="<?= ($_SESSION['modification'] == 1 ? 'Masquer les traductions' : 'Modifier les traductions') ?>">
                        <img src="<?= $this->surl ?>/images/admin/traductions_<?= ($_SESSION['modification'] == 1 ? 'on' : 'off') ?>.png" alt="<?= ($_SESSION['modification'] == 1 ? 'Masquer les traductions' : 'Modifier les traductions') ?>" />
                    </a>
                </div>
                <?php
            }
            //gestion du temps d'attente en cas d'echec successifs
            if (isset($_SESSION['login']['nb_tentatives_precedentes']) && $_SESSION['login']['nb_tentatives_precedentes'] > 1) {
                ?>
                <script type="text/javascript">
                    $(document).ready(function () {
                        setTimeout(function () {
                            $('.error_wait').hide();
                            $('.btn_login').show();
                        }, <?= ($_SESSION['login']['duree_waiting'] * 1000) ?>);
                    });
                </script>
                <?php
            }
            ?>