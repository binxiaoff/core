<div class="banner">
    <div class="banner-content">
        <img class="ribbon person-left" src="<?= $this->surl ?>/styles/default/images/person-1.png" alt="" width="190" height="313">
        <img class="ribbon person-right" src="<?= $this->surl ?>/styles/default/images/person-2.png" alt="" width="197" height="312">
        <span class="pointer-left"></span>
        <span class="pointer-right"></span>
        <h2><?= $this->lng['home']['presentation'] ?></h2>
        <div style="padding-bottom:18px;font-size:24px;"><?= $this->lng['home']['decouvrez-comment'] ?></div>
        <script>
            $('.youtube').colorbox({iframe: true, innerWidth: 640, innerHeight: 390, opacity: 0.5, maxWidth: '90%'});
            var sCurrentUrl = document.location.href;

            if (0 < parseInt(sCurrentUrl.search('video=1'))) {
                $('.youtube').colorbox({open: true});
            }

            $(window).on('resize', function () {
                if ($(this).width() < 640) {
                    $.colorbox.resize({
                        innerWidth: 300,
                        innerHeight: 150
                    });
                } else {
                    $.colorbox.resize({
                        innerWidth: 640,
                        innerHeight: 390
                    });
                }
            });

            $(window).on('load resize', function () {
                if ($(window).width() < 768) {
                    $('#btn_pret').attr("href", "<?= $this->lurl ?>/LP_inscription_preteurs/");
                }
            });
        </script>

        <?php if ($this->clients->checkAccess() && $this->bIsLender) { ?>
            <a href="<?= $this->lurl ?>/projects" class="btn btn-mega btn-info">
                <i class="icon-arrow-medium-next right"></i>
                <?= $this->lng['home']['pretez'] ?>
            </a>
        <?php } else { ?>
            <a href="<?= $this->lurl . '/' . $this->tree->getSlug(127, $this->language) ?>" class="btn btn-mega btn-info" id="btn_pret">
                <i class="icon-arrow-medium-next right"></i>
                <?= $this->lng['home']['pretez'] ?>
            </a>
        <?php } ?>
        <a href="<?= $this->lurl . '/' . $this->tree->getSlug(236, $this->language) ?>" class="btn btn-mega">
            <i class="icon-arrow-medium-next right"></i>
            <?= $this->lng['home']['empruntez'] ?>
        </a>
    </div><!-- /.banner-content -->
</div><!-- /.banner -->

<div class="hp-counter">
    <div class="shell">
        <div style="text-align:right;padding-right:193px;">
            <p class="hp"><?= $this->lng['home']['deja'] ?></p>
            <div class="counter-holder">
                <?= $this->compteur ?>
            </div>
            <p class="hp"><?= $this->lng['home']['demprunte-sur-unilend'] ?></p>
        </div>
    </div>
</div>
<!-- /.counter-holder -->
<div class="main">
    <div class="shell">
        <div class="section-projects-landing">
            <?php if (count($this->lProjetsFunding) > 0) : ?>
                <a href="<?= $this->lurl . '/' . $this->tree->getSlug(4, $this->language) ?>" class="view-projects-link"><i class="arrow-right"></i><?= $this->lng['home']['decouvrez-tous-les-projets'] ?></a>
                <h1><?= $this->lng['home']['les-projets-en-cours'] ?></h1>
                <table class="table">
                    <tr>
                        <th width="350">
                            <div class="th-wrap"><i title="<?= $this->lng['home']['info-nom-projet'] ?>" class="icon-person tooltip-anchor"></i></div>
                        </th>
                        <th width="90">
                            <div class="th-wrap"><i title="<?= $this->lng['home']['info-capacite-remboursement'] ?>" class="icon-gauge tooltip-anchor"></i></div>
                        </th>
                        <th width="90">
                            <div class="th-wrap"><i title="<?= $this->lng['home']['info-montant'] ?>" class="icon-bank tooltip-anchor"></i></div>
                        </th>
                        <th width="60">
                            <div class="th-wrap"><i title="<?= $this->lng['home']['info-duree'] ?>" class="icon-calendar tooltip-anchor"></i></div>
                        </th>
                        <th width="60">
                            <div class="th-wrap"><i title="<?= $this->lng['home']['info-tx-cible'] ?>" class="icon-graph tooltip-anchor"></i></div>
                        </th>
                        <th width="110">
                            <div class="th-wrap"><i title="<?= $this->lng['home']['info-temps-restant'] ?>" class="icon-clock tooltip-anchor"></i></div>
                        </th>
                        <th width="120">
                            <div class="th-wrap"><i title="<?= $this->lng['home']['info-cta'] ?>" class="icon-arrow-next tooltip-anchor"></i></div>
                        </th>
                    </tr>
                    <?php
                    $this->loans = $this->loadData('loans');
                    foreach ($this->lProjetsFunding as $aProject) :
                        $this->projects_status->getLastStatut($aProject['id_project']);
                        $this->companies->get($aProject['id_company'], 'id_company');
                        $inter = $this->dates->intervalDates(date('Y-m-d h:i:s'), $aProject['date_retrait'] . ' 23:59:59');

                        if ($inter['mois'] > 0)
                            $dateRest = $inter['mois'] . ' mois';
                        else
                            $dateRest = '';

                        $avgRate = $this->projects->getAverageInterestRate($aProject['id_project'], $aProject['status']);

                        // dates pour le js
                        $mois_jour = $this->dates->formatDate($aProject['date_retrait'], 'F d');
                        $annee     = $this->dates->formatDate($aProject['date_retrait'], 'Y');
                        ?>
                        <tr class="unProjet" id="project<?= $aProject['id_project'] ?>">
                            <td>
                                <?php
                                if ($this->projects_status->status >= \projects_status::FUNDE) {
                                    if ($aProject['date_fin'] != '0000-00-00 00:00:00') {
                                        $endDateTime = new \DateTime($aProject['date_fin']);
                                    } else {
                                        $endDateTime = new \DateTime($aProject['date_retrait_full']);
                                    }
                                    $endDate = $endDateTime->format('d/m/Y');
                                    $endTime= $endDateTime->format('H:i');
                                    $dateRest = str_replace(['[#date#]', '[#time#]'], [$endDate, $endTime], $this->lng['home']['termine']);
                                } else {
                                    $tab_date_retrait = explode(' ', $aProject['date_retrait_full']);
                                    $tab_date_retrait = explode(':', $tab_date_retrait[1]);
                                    $heure_retrait    = $tab_date_retrait[0] . ':' . $tab_date_retrait[1];
                                    ?>
                                    <script>
                                        var cible<?= $aProject['id_project'] ?> = new Date('<?= $mois_jour ?>, <?= $annee ?> <?= $heure_retrait ?>');
                                        var letime<?= $aProject['id_project'] ?> = parseInt(cible<?= $aProject['id_project'] ?>.getTime() / 1000, 10);
                                        setTimeout('decompte(letime<?= $aProject['id_project'] ?>,"val<?= $aProject['id_project'] ?>")', 500);
                                    </script>
                                    <?php
                                }

                                if ($aProject['photo_projet'] != '') {
                                    ?><a href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>"><img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $aProject['photo_projet'] ?>" alt="<?= $aProject['photo_projet'] ?>" class="thumb"></a><?php
                                }
                                ?>
                                <div class="description">
                                    <h5><a href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>"><?= $aProject['title'] ?></a></h5>
                                    <h6><?= $this->companies->city . ($this->companies->zip != '' ? ', ' : '') . $this->companies->zip ?></h6>
                                    <p><?= $aProject['nature_project'] ?></p>
                                </div><!-- /.description -->
                            </td>
                            <td><div class="cadreEtoiles"><div class="etoile <?= $this->lNotes[$aProject['risk']] ?>"></div></div></td>
                            <td style="white-space:nowrap;"><?= $this->ficelle->formatNumber($aProject['amount'], 0) ?>&euro;</td>
                            <td style="white-space:nowrap;"><?= $aProject['period'] ?> mois</td>
                            <td><?= $this->ficelle->formatNumber($avgRate, 1) ?>&nbsp;%</td>
                            <td><strong id="val<?= $aProject['id_project'] ?>"><?= $dateRest ?></strong></td>
                            <td>
                                <?php
                                if ($this->projects_status->status >= \projects_status::FUNDE) {
                                    ?><a href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>" class="btn grise1 btn-info btn-small multi btn-grise"><?= $this->lng['home']['cta-voir-le-projet'] ?></a><?php
                                } else {
                                    ?><a href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>" class="btn btn-info btn-small"><?= $this->lng['home']['cta-pretez'] ?></a><?php
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table><!-- /.table -->
            <?php endif; ?>
        </div><!-- /.section projects landing -->
        <div class="section-projects-mobile">
            <?php foreach ($this->lProjetsFunding as $aProject) :
                $this->projects_status->getLastStatut($aProject['id_project']);
                $this->companies->get($aProject['id_company'], 'id_company');
                $inter = $this->dates->intervalDates(date('Y-m-d h:i:s'), $aProject['date_retrait'] . ' 23:59:59');

                if ($inter['mois'] > 0)
                    $dateRest = $inter['mois'] . ' mois';
                else
                    $dateRest = '';

                $avgRate = $this->projects->getAverageInterestRate($aProject['id_project'], $aProject['status']);

                // dates pour le js
                $mois_jour = $this->dates->formatDate($aProject['date_retrait'], 'F d');
                $annee     = $this->dates->formatDate($aProject['date_retrait'], 'Y');
                ?>
                <div class="project-mobile">
                    <div class="project-mobile-image">
                        <?php
                        if ($this->projects_status->status >= \projects_status::FUNDE) {
                            $dateRest = $this->lng['home']['termine'];
                        } else {
                            $heure_retrait = date('H:i', strtotime($aProject['date_retrait_full']));
                            ?>
                            <script>
                                var cible<?= $aProject['id_project'] ?> = new Date('<?= $mois_jour ?>, <?= $annee ?> <?= $heure_retrait ?>');
                                var letime<?= $aProject['id_project'] ?> = parseInt(cible<?= $aProject['id_project'] ?>.getTime() / 1000, 10);
                                setTimeout('decompte(letime<?= $aProject['id_project'] ?>,"min_val<?= $aProject['id_project'] ?>")', 500);
                            </script>
                            <?php
                        }
                        if ($aProject['photo_projet'] != '') {
                            ?><a href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>"><img src="<?= $this->surl ?>/images/dyn/projets/169/<?= $aProject['photo_projet'] ?>" alt="<?= $aProject['photo_projet'] ?>"></a><?php
                        }

                        $nb_etoile_on = $this->lNotes[$aProject['risk']];
                        if ($nb_etoile_on == 1) {
                            $nb_etoile_on = 3;
                        }
                        $nb_etoile_off = 5;
                        $nb_etoile_restant_afficher = $nb_etoile_on;
                        $html_etoile = "";

                        for ($i = 1; $i <= 5; $i++) {
                            if ($nb_etoile_on > 0) {
                                $html_etoile .= '<i class="ico-star-on"></i>';
                                $nb_etoile_on--;
                            } else {
                                $html_etoile .= '<i class="ico-star-off"></i>';
                            }
                        }

                        $pourcent_affichage = $this->ficelle->formatNumber($avgRate, 1) . '%';
                        ?>
                        <div class="project-mobile-image-caption">
                            <?= $this->ficelle->formatNumber($aProject['amount'], 0) ?>&euro; | <div class="cadreEtoiles" style="display: inline-block; top:7px;left: -1px;"><div class="etoile <?= $this->lNotes[$aProject['risk']] ?>"></div></div> | <?= $pourcent_affichage ?> | <?= $aProject['period'] ?> mois
                        </div>
                    </div>
                    <div class="project-mobile-content">
                        <h3><?= $aProject['title'] ?></h3>
                        <h4><?= $this->companies->city . ($this->companies->zip != '' ? ', ' : '') . $this->companies->zip ?></h4>
                        <h5>
                            <i class="ico-clock"></i>
                            <strong id="min_val<?= $aProject['id_project'] ?>"><?= $dateRest ?></strong>
                        </h5>
                        <p>
                            <?php if ($this->projects_status->status >= \projects_status::FUNDE) : ?>
                                <a href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>" class="btn btn-info btn-small multi  grise1 btn-grise" style="line-height: 14px;padding: 4px 11px;"><?= $this->lng['home']['cta-voir-le-projet'] ?></a>
                            <?php else : ?>
                                <a href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>" class="btn"><?= $this->lng['home']['cta-pretez'] ?></a>
                            <?php endif; ?>
                            <?= $aProject['nature_project'] ?>
                        </p>
                    </div><!-- /.project-mobile-content -->
                </div><!-- /.project-mobile -->
                <?php endforeach; ?>
        </div><!-- /.section-projects-mobile -->
    </div><!-- /.shell -->
</div><!-- /.main -->
<?= $this->fireView('../blocs/ils-parlent-de-nous') ?>

<script type="text/javascript">
    // Pixel name = Unilendlead
    var fb_param = {};
    fb_param.pixel_id = '6012822951950';
    fb_param.value = '0.00';
    fb_param.currency = 'EUR';
    (function () {
        var fpw = document.createElement('script');
        fpw.async = true;
        fpw.src = '//connect.facebook.net/en_US/fp.js';
        var ref = document.getElementsByTagName('script')[0];
        ref.parentNode.insertBefore(fpw, ref);
    })();
</script>

<noscript>
    <img height="1" width="1" alt="" style="display:none"
         src="https://www.facebook.com/offsite_event.php?id=6012822951950&value=0.00&currency=EUR" />
</noscript>

<script type="text/javascript">
    // Pixel name = Unilendachat
    var fb_param = {};
    fb_param.pixel_id = '6012822975550';
    fb_param.value = '0.00';
    fb_param.currency = 'EUR';
    (function () {
        var fpw = document.createElement('script');
        fpw.async = true;
        fpw.src = '//connect.facebook.net/en_US/fp.js';
        var ref = document.getElementsByTagName('script')[0];
        ref.parentNode.insertBefore(fpw, ref);
    })();
</script>

<noscript>
    <img height="1" width="1" alt="" style="display:none"
         src="https://www.facebook.com/offsite_event.php?id=6012822975550&value=0.00&currency=EUR" />
</noscript>

<script type="text/javascript">
    /* <![CDATA[ */
    var google_conversion_id = 990740266;
    var google_custom_params = window.google_tag_params;
    var google_remarketing_only = true;
    /* ]]> */
</script>
<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js"></script>
<noscript>
    <div style="display:inline;">
        <img height="1" width="1" style="border-style:none;" alt="" src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/990740266/?value=0&amp;guid=ON&amp;script=0"/>
    </div>
</noscript>
