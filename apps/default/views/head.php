<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!--[if lt IE 7]>      <html class="no-js lt-ie10 lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie10 lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie10 lt-ie9"> <![endif]-->
<!--[if IE 9]>         <html class="no-js lt-ie10"> <![endif]-->
<!--[if gt IE 9]><!--> <html class="no-js" xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= $this->language ?>" lang="<?= $this->language ?>"> <!--<![endif]-->
    <head>
        <?php
        if ($this->google_webmaster_tools != '')
        {
            ?>    
            <meta name="google-site-verification" content="<?= $this->google_webmaster_tools ?>" />
            <?php
        }
        ?>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
            <title><?= $this->meta_title ?><?= ($this->baseline_title != '' ? ' - ' . $this->baseline_title : '') ?></title>
            <meta name="description" content="<?= $this->meta_description ?>" />
            <meta name="keywords" content="<?= $this->meta_keywords ?>" />
            <!--[if IE]><script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
            <?= ($this->tree->id_tree != '' && $this->tree->indexation == 0 ? '<meta name="robots" content="noindex,nofollow" />' : '') ?>
            <link rel="shortcut icon" href="<?= $this->surl ?>/styles/default/images/favicon.ico" type="image/x-icon" />
            <script type="text/javascript">
                var add_surl = '<?= $this->surl ?>';
                var add_url = '<?= $this->lurl ?>';

            </script>
            <?php $this->callCss(); ?>
            <?php $this->callJs(); ?>	

            <!-- NEW INCLUDES -->

            <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />

            <script>
                if (screen.width > 767 ){
                    var mvp = document.getElementById('viewport');
                    mvp.setAttribute('content', 'width=1024');
                }
            </script>
            
            
            <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/responsive/fonts.css" type="text/css" media="all" />

            <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/responsive/responsive.css" type="text/css" media="all" />

            <script type="text/javascript" src="<?= $this->surl ?>/scripts/default/responsive/responsive.js"></script>

            <!-- END NEW INCLUDES -->


            <?
            // partenaire challanges
            if ($this->lurl == 'http://partenaire.unilend.challenges.fr')
            {

                // PUB challenges
                /* $sas_pageid	= '61933/486256'



                  ?>
                  <script>
                  var sas_tmstp = Math.round(Math.random()*10000000000),
                  sas_pageid = '<?=$sas_pageid?>',
                  sas_target = '',
                  sas_formatids = '23885,23371,27098,21482,21488,21487,21486,21485,27097,21483,21484';
                  document.write('<scr'+'ipt async="true" src="http://ww690.smartadserver.com/call2/pubjallajax/' + sas_pageid + '/' + sas_formatids + '/' + sas_tmstp + '/' + escape(sas_target) + '?"></scr'+'ipt>');
                  </script>
                  <script src="http://referentiel.nouvelobs.com/tools/smart.php"></script>
                  <? */
            }
            ?>
    </head>
    <body class="has-fixed-nav">
        <?
        if ($this->Config['env'] == 'prod')
        {
            // HOME
            /* if ($this->tree->id_template == 8)
              {
              ?><iframe src="https://secure.img-cdn.mediaplex.com/0/27518/universal.html?page_name=landing_page&visite=1&mpuid=<?= $this->clients->id_client ?>" HEIGHT=1 WIDTH=1 FRAMEBORDER=0></iframe><?
              }
              // Validation inscription preteur (template de confirmation)
              if (in_array($this->tree->id_tree, array(16, 130)))
              {

              if (isset($this->params[1]))
              {
              $this->clients->get($this->params[1], 'hash');
              }
              ?><iframe src="https://secure.img-cdn.mediaplex.com/0/27518/universal.html?page_name=conversion_complete&complete=1&mpuid=<?= $this->clients->id_client ?>" HEIGHT=1 WIDTH=1 FRAMEBORDER=0></iframe><?
              // ajout kle, BT 17471
              ?>
              <iframe src="http://nodes.network-touchvibes.com/scripts/tracking.php?params=466|4&track=<?= $this->clients->id_client ?>" width="1" height="1" marginwidth="0" marginheight="0" frameborder="0" scrolling="no"></iframe>
              <?php
              }
              // Confirmation inscription preteur virement
              if (in_array($this->tree->id_tree, array(16)))
              {
              ?>
              <iframe src="https://tracking.unilend-partners.com/mastertags/3.html?action=lead&pid=3&type=12&uniqueid=<?= $this->clients->id_client ?>"  width="1" height="1" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" style="border:none;"></iframe>
              <?
              }
              // Confirmation inscription preteur CB
              if (in_array($this->tree->id_tree, array(130)))
              {
              ?>
              <iframe src="https://tracking.unilend-partners.com/mastertags/3.html?action=lead&pid=3&type=11&uniqueid=<?= $this->clients->id_client ?>"  width="1" height="1" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" style="border:none;"></iframe>
              <?
              }
             */
            // Etape 2 inscription preteur
            if ($this->page_preteur == 2)
            {
                ?>
                                <!--                <iframe src="https://secure.img-cdn.mediaplex.com/0/27518/universal.html?page_name=conversion&inscription=1&mpuid=<?= $this->clients->id_client ?>" HEIGHT=1 WIDTH=1 FRAMEBORDER=0></iframe>

                                                <iframe src="https://tracking.unilend-partners.com/mastertags/3.html?action=lead&pid=3&type=14&uniqueid=<?= $this->clients->id_client ?>"  width="1" height="1" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" style="border:none;"></iframe>-->

                <?php
                // ajout kle, BT 17471
                ?>
                <iframe src="http://nodes.network-touchvibes.com/scripts/tracking.php?params=466|4&track=<?= $this->clients->id_client ?>" width="1" height="1" marginwidth="0" marginheight="0" frameborder="0" scrolling="no"></iframe>

                <?php /* ?><!-- Facebook Conversion Code for Unilend -->
                  <script>(function() {
                  var _fbq = window._fbq || (window._fbq = []);
                  if (!_fbq.loaded) {
                  var fbds = document.createElement('script');
                  fbds.async = true;
                  fbds.src = '//connect.facebook.net/en_US/fbds.js';
                  var s = document.getElementsByTagName('script')[0];
                  s.parentNode.insertBefore(fbds, s);
                  _fbq.loaded = true;
                  }
                  })();
                  window._fbq = window._fbq || [];
                  window._fbq.push(['track', '6024039893052', {'value':'0.00','currency':'EUR'}]);
                  </script>
                  <noscript><img height="1" width="1" alt="" style="display:none" src="https://www.facebook.com/tr?ev=6024039893052&amp;cd[value]=0.00&amp;cd[currency]=EUR&amp;noscript=1" /></noscript><?php */ ?>

                <?php /* ?><!-- Facebook Conversion Code for Unilend -->
                  <script>(function() {
                  var _fbq = window._fbq || (window._fbq = []);
                  if (!_fbq.loaded) {
                  var fbds = document.createElement('script');
                  fbds.async = true;
                  fbds.src = '//connect.facebook.net/en_US/fbds.js';
                  var s = document.getElementsByTagName('script')[0];
                  s.parentNode.insertBefore(fbds, s);
                  _fbq.loaded = true;
                  }
                  })();
                  window._fbq = window._fbq || [];
                  window._fbq.push(['track', '6020057935405', {'value':'0.00','currency':'EUR'}]);
                  </script>
                  <noscript><img height="1" width="1" alt="" style="display:none" src="https://www.facebook.com/tr?ev=6020057935405&amp;cd[value]=0.00&amp;cd[currency]=EUR&amp;noscript=1" /></noscript><?php */ ?>


                <?php /* ?> 13/11/2014 et daplacé dans les landing page offre de bienvenue le 17/12/2014<?php */ ?>
                <?php /* ?><!-- Facebook Conversion Code for Unilend -->
                  <script>(function() {
                  var _fbq = window._fbq || (window._fbq = []);
                  if (!_fbq.loaded) {
                  var fbds = document.createElement('script');
                  fbds.async = true;
                  fbds.src = '//connect.facebook.net/en_US/fbds.js';
                  var s = document.getElementsByTagName('script')[0];
                  s.parentNode.insertBefore(fbds, s);
                  _fbq.loaded = true;
                  }
                  })();
                  window._fbq = window._fbq || [];
                  window._fbq.push(['track', '6021615722883', {'value':'0.00','currency':'EUR'}]);
                  </script>
                  <noscript><img height="1" width="1" alt="" style="display:none" src="https://www.facebook.com/tr?ev=6021615722883&amp;cd[value]=0.00&amp;cd[currency]=EUR&amp;noscript=1" /></noscript><?php */ ?>

                <img src="https://ext.ligatus.com/conversion/?c=77615&a=10723" width="1" height="1" />       

                <?
            }
            // Etape 3 inscription preteur
            if ($this->page_preteur == 3)
            {
                ?>
                <iframe src="https://tracking.unilend-partners.com/mastertags/3.html?action=lead&pid=3&type=4&uniqueid=<?= $this->clients->id_client ?>"  width="1" height="1" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" style="border:none;"></iframe>
                <?
            }

            // tracking apres validation landing page
            if (isset($_SESSION['landing_page']) && $_SESSION['landing_page'] == true)
            {

                // deplacé dans etape 2 preteur
                unset($_SESSION['landing_page']);
            }

            // depot dossier etape 2
            if ($this->page == 2)
            {
                ?><img src="https://ext.ligatus.com/conversion/?c=74703&a=9617" width="1" height="1" /><?
                }
                // confirmation depot de dossier
                elseif ($this->tree->id_tree == 48)
                {
                    ?><img src="https://ms.ligatus.com/de/track/triggerext.php?cn=trcn74703" width="1" height="1"><?
                }

                // projets-a-financer
                if ($this->tree->id_tree == 4)
                {

                    if (isset($_SESSION['LP_id_unique']))
                    {
                        $this->clients->get($_SESSION['LP_id_unique'], 'id_client');
                        unset($_SESSION['LP_id_unique']);
                    }


                    // ajout kle, BT 17471
                    ?>
                    <iframe src="http://nodes.network-touchvibes.com/scripts/tracking.php?params=466|4&track=<?= $this->clients->id_client ?>" width="1" height="1" marginwidth="0" marginheight="0" frameborder="0" scrolling="no"></iframe>


                    <iframe src="https://tracking.unilend-partners.com/mastertags/3.html?action=lead&pid=3&type=13&uniqueid=<?= $this->clients->id_client ?>"  width="1" height="1" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" style="border:none;"></iframe>
                    <?
                }

                // partenaire challanges
                if ($this->lurl == 'http://partenaire.unilend.challenges.fr')
                {
                    ?>
                    <script>
                <!--
                            xtnv = document;
                xtsd = "http://logi150";
                xtsite = " 350533";
                xtn2 = "46";
                xtpage = "Unilend::Global";
                xtdi = "";
                //-->
                    </script>
                    <script type="text/javascript" src="http://referentiel.nouvelobs.com/scripts/xiti/challenges.fr/xtcore.js"></script>
                    <noscript>
                        <img width="1" height="1" alt="" src="http://logi150.xiti.com/hit.xiti?s=350533&amp;s2=46&amp;p=Unilend::Global&amp;di=&amp;" />
                    </noscript>



                    <?php
                    // pub challenges
                    /* ?><script>
                      SmartAdServerAjaxOneCall(sas_pageid, 21487, sas_target);
                      </script>
                      <noscript>
                      <a href="http://ww690.smartadserver.com/call/pubjumpi/<?=$sas_pageid?>/21487/S/<?=time()?>/?" target="_blank">
                      <img src="http://ww690.smartadserver.com/call/pubi/<?=$sas_pageid?>/21487/S/<?=time()?>/?" border="0" alt="" /></a>
                      </noscript>


                      <script>
                      SmartAdServerAjaxOneCall(sas_pageid, 21486, sas_target);
                      </script>
                      <noscript>
                      <a href="http://ww690.smartadserver.com/call/pubjumpi/<?=$sas_pageid?>/21486/S/<?=time()?>/?" target="_blank">
                      <img src="http://ww690.smartadserver.com/call/pubi/<?=$sas_pageid?>/21486/S/<?=time()?>/?" border="0" alt="" /></a>
                      </noscript><?php */
                    ?>




                    <?
                }
            }
            ?>


            <!-- Google Tag Manager -->
            <noscript><iframe src="//www.googletagmanager.com/ns.html?id=GTM-MB66VL"
                              height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
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
                })(window, document, 'script', 'dataLayer', 'GTM-MB66VL');</script>
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
            if (isset($_SESSION['user']['id_user']) && $_SESSION['user']['id_user'] != '' && $_SERVER['REMOTE_ADDR'] == '93.26.42.99')
            {
                // Si les modifications sont actives on desactive les liens
                if ($_SESSION['modification'] == 1)
                {
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
            ?>
            <?php
            if ($this->google_analytics != '')
            {
                ?>    
                <script type="text/javascript">
                    var _gaq = _gaq || [];
                    _gaq.push(['_setAccount', '<?= $this->google_analytics ?>']);
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
                <?php
            }


//unset($_SESSION['login']);
//echo $_SESSION['login']['nb_tentatives_precedentes'];
//gestion du temps d'attente en cas d'echec successifs
            if ($_SESSION['login']['nb_tentatives_precedentes'] > 1)
            {
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

            if ($_SESSION['login']['nb_tentatives_precedentes'] >= 5)
            {
                
            }
            ?>