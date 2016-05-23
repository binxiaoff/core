<style>
    /* Fix on the tooltip override, to be placed after the line 602 of styles/default/style.css */
    .tooltip.left .tooltip-arrow {
        top: 50%;
        left: 100%;
        margin-top: -8px;
        margin-left: 0px;
        border-width: 8px 0 8px 8px;
        border-color: transparent;
        border-left-color: #a1a5a7;
        border-style:solid;
    }
</style>

<script>
    /**
     * change_rd - change the rendement chart to a targeted lvl
     * @param {integer} lvl - level 0 to 5
     */

    var change_rd = function(lvl) {
        $('.rd-box').removeClass('lvl0 lvl1 lvl2 lvl3 lvl4 lvl5').addClass('lvl'+lvl);
    };

    setTimeout(function() {
        change_rd(<?= $this->iDiversificationLevel ?>);
    }, 200);

    // tooltip init
    $(function(){
        $('[data-toggle="tooltip"]').tooltip({
            placement : window.innerWidth > 770 ? 'left' : 'top',
            trigger : window.innerWidth > 770 ? 'hover' : 'click'
        });
    })
</script>

<div class="graphic-box rd-box lvl0">
    <header>
        <h2><?= $this->lng['preteur-synthese']['rendement-portefeuille'] ?></h2>
        <p><?= $this->lng['preteur-synthese']['nombre-entreprises'] ?><strong><?= $this->iNumberOfCompanies ?></strong>
        </p>
    </header>
    <div class="body">
        <div class="post-box">
            <p><small><?= empty($this->sDate) ? '' : str_replace('[#DATE#]', $this->sDate, $this->lng['preteur-synthese']['message-date-donnees']) ?></small></p>
        </div>
        <div class="rd-meter">
            <div class="rd-data">
                <div class="rd-lvl">
                    <span class="rd-fill"></span>
                </div>
                <em class="rd-fill-marker"></em>
                <p class="rd-desc"><?= $this->lng['preteur-synthese']['niveau-diversification'] ?>
                    <span><?= $this->lng['preteur-synthese']['niveau-' . $this->iDiversificationLevel] ?></span></p>
            </div>
            <div class="rd-mask-cnt"
                 data-toggle="tooltip"
                 title="<?= $this->lng['preteur-synthese']['info-' . $this->sTypeMessageTooltip] ?>">
                <img class="rd-mask" alt="" src="<?= $this->surl . '/styles/default/images/round_mask_unilend.png' ?>">
                <span class="rd-pct"><?= $this->sDisplayedValue ?></span>
            </div>
        </div>
        <p class="rd-unilend"><?= $this->lng['preteur-synthese']['tri-unilend']?><span><?= $this->sIRRUnilend ?> %</span></p>
        <p class="rd-info"><?= $this->sDisplayedMessage  ?></p>
        <p><?= str_replace('[#SURL#]', $this->surl, $this->lng['preteur-synthese']['tri-explication-lien']) ?></p>
    </div>
</div>

<div class="graphic-box le-bar-chart">
    <header>
        <h2><?= $this->lng['preteur-synthese']['synthese-de-vos-mouvement'] ?></h2>
        <p><?= $this->lng['preteur-synthese']['montant-depose'] ?> : <strong><?= $this->ficelle->formatNumber($this->SumDepot) ?> €</strong></p>
    </header>
    <div class="body">
        <span id="titlePrete"><?= $this->lng['preteur-synthese']['argent-prete'] ?></span>
        <span id="titleArgentRemb"><?= $this->lng['preteur-synthese']['capital-rembourse'] ?></span>
        <span id="titleInteretsRecu"><?= $this->lng['preteur-synthese']['interets-recus'] ?></span>
        <span id="argentPrete"><?= number_format($this->sumPrets, 2, '.', '') ?></span>
        <span id="argentRemb"><?= number_format($this->sumRembMontant, 2, '.', '') ?></span>
        <span id="interets"><?= number_format($this->sumInterets, 2, '.', '') ?></span>
        <div id="bar-chart"></div>
    </div>
    <a class="bottom-link" href="<?= $this->lurl ?>/operations"><?= $this->lng['preteur-synthese']['voir-mes-operations'] ?></a>
</div>

<div class="post-schedule clearfix">
    <div style="float:right;margin-right:70px;margin-top:12px;">
        <select name="duree" id="duree" class="custom-select field-tiny-plus">
            <option value="mois">Mois</option>
            <option value="mois">Mois</option>
            <option value="trimestres">Trimestres</option>
            <option value="annees">Années</option>
        </select>
    </div>

    <h2><?= $this->lng['preteur-synthese']['revenus-mensuels'] ?> <span><i class="icon-box-arrow"></i></span></h2>

    <div class="body duree_content">
        <div style="display:none;" class="interets_recu"><?= $this->lng['preteur-synthese']['interets-recus-par-mois'] ?></div>
        <div style="display:none;" class="capital_rembourse"><?= $this->lng['preteur-synthese']['capital-rembourse-par-mois'] ?></div>
        <div style="display:none;" class="prelevements_fiscaux"><?= $this->lng['preteur-synthese']['prelevements-fiscaux'] ?></div>
        <div class="slider-c">
            <div class="arrow prev notext">arrow</div>
            <div class="arrow next notext">arrow</div>
            <div class="chart-slider">
                <?php
                foreach ($this->ordre as $key => $o) {
                    ?><div id="bar-mensuels-<?= $o ?>" class="chart-item"></div><?php
                }
                ?>
            </div>
        </div>
    </div>
    <a class="bottom-link" href="<?= $this->lurl ?>/operations"><?= $this->lng['preteur-synthese']['voir-mes-operations'] ?></a>

    <script type="text/javascript">
        $("#duree").change(function () {
            $.post(add_url + "/ajax/syntheses_mouvements", {duree: $(this).val()}, function (data) {
                $('.duree_content').html(data);
            });
        });

        <?php
        $old = 0;
        foreach ($this->lesmois as $key => $o) {
            $tab = explode('_', $key);
            $annee = $tab[0];
            $mois = $tab[1];

            $intParMois = $this->sumIntbParMois[$annee][$mois];
            $rembParMois = $this->sumRembParMois[$annee][$mois];
            $revenueFiscalsParMois = $this->sumRevenuesfiscalesParMois[$annee][$mois];
            ?>

        var remb_<?= $key ?> = parseFloat('<?= $rembParMois ?>');
        var inte_<?= $key ?> = parseFloat('<?= $intParMois ?>');
        var fiscal_<?= $key ?> = parseFloat('<?= $revenueFiscalsParMois ?>');

        <?php
    }

    foreach ($this->lesmois as $key => $o) {
        // Si diff on créer le script
        if ($old != $o) {
            if ($old == 0) {
                prev($this->lesmois);
            }
            $a = key($this->lesmois);
            $tab = explode('_', $a);
            $a_annee = $tab[0];
            $a_mois = $tab[1];

            next($this->lesmois);
            $b = key($this->lesmois);
            $tab = explode('_', $b);
            $b_annee = $tab[0];
            $b_mois = $tab[1];

            next($this->lesmois);
            $c = key($this->lesmois);
            $tab = explode('_', $c);
            $c_annee = $tab[0];
            $c_mois = $tab[1];

            next($this->lesmois);

            ?>

        $('#bar-mensuels-<?= $o ?>').highcharts({
            chart: {
                type: 'column',
                backgroundColor: '#fafafa',
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false
            },
            colors: ['#8462a7', '#ee5396', '#b10366'],
            title: {
                text: ''
            },
            xAxis: {
                color: '#a1a5a7',
                title: {
                    enabled: null,
                    text: null
                },
                categories: [' <b><?= $this->arrayMois[$a_mois] . ' - ' . $a_annee ?></>', ' <b><?= $this->arrayMois[$b_mois] . ' - ' . $b_annee ?></b>', ' <b><?= $this->arrayMois[$c_mois] . ' - ' . $c_annee ?></b>']
            },
            yAxis: {
                reversedStacks: false,
                title: {
                    enabled: null,
                    text: null
                },
                min: 0
            },
            legend: {
                borderColor: '#ffffff',
                enabled: true
            },
            plotOptions: {
                column: {
                    pointWidth: 80,
                    stacking: 'normal',
                    dataLabels: {
                        color: '#fff',
                        enabled: true,
                        format: '{point.name}'
                    }
                }
            },
            tooltip: {
                valueSuffix: ' €'
            },
            series: [
                {
                    name: ' <b>' + $('.capital_rembourse').html() + '</b>',
                    data: [
                        [' <b>' + remb_<?= $a ?>.toString().replace('.', ',') + '€</b>', remb_<?= $a ?>],
                        [' <b>' + remb_<?= $b ?>.toString().replace('.', ',') + '€</b>', remb_<?= $b ?>],
                        [' <b>' + remb_<?= $c ?>.toString().replace('.', ',') + '€</b>', remb_<?= $c ?>]
                    ]
                },
                {
                    name: ' <b>' + $('.interets_recu').html() + '</b>',
                    data: [
                        [' <b>' + inte_<?= $a ?>.toString().replace('.', ',') + ' €</b>', inte_<?= $a ?>],
                        [' <b>' + inte_<?= $b ?>.toString().replace('.', ',') + ' €</b>', inte_<?= $b ?>],
                        [' <b>' + inte_<?= $c ?>.toString().replace('.', ',') + ' €</b>', inte_<?= $c ?>]]
                },
                {
                    name: ' <b>' + $('.prelevements_fiscaux').html() + '</b>',
                    data: [
                        [' <b>' + fiscal_<?= $a ?>.toString().replace('.', ',') + '€</b>', fiscal_<?= $a ?>],
                        [' <b>' + fiscal_<?= $b ?>.toString().replace('.', ',') + '€</b>', fiscal_<?= $b ?>],
                        [' <b>' + fiscal_<?= $c ?>.toString().replace('.', ',') + '€</b>', fiscal_<?= $c ?>]
                    ]
                }]
        });

        <?php
    }
    $old = $o;
}

?>
    </script>
</div>

<div class="post-schedule">
    <h2><i class="icon-heart"></i> <?= $this->lng['preteur-synthese']['mes-favoris'] ?> <span><?= ($this->lProjetsFav != false ? count($this->lProjetsFav) : 0) ?> <i class="icon-box-arrow"></i></span></h2>
    <div class="body">
        <?php
        if ($this->lProjetsFav != false) {
            foreach ($this->lProjetsFav as $f) {
                $this->companies->get($f['id_company'], 'id_company');
                $this->projects_status->getLastStatut($f['id_project']);

                $fast_ok = false;
                if ($this->projects_status->status == \projects_status::EN_FUNDING && $this->clients_status->status >= \clients_status::VALIDATED) {
                    $fast_ok = true;
                }

                // date fin 21h a chaque fois
                $inter     = $this->dates->intervalDates(date('Y-m-d H:i:s'), $f['date_retrait'] . ' ' . $this->heureFinFunding . ':00');
                $dateRest  = $inter['mois'] > 0 ? $inter['mois'] . ' ' . $this->lng['preteur-projets']['mois'] : '';
                $mois_jour = $this->dates->formatDate($f['date_retrait'], 'F d');
                $annee     = $this->dates->formatDate($f['date_retrait'], 'Y');

                $soldeBid = $this->bids->getSoldeBid($f['id_project']);

                $payer = $soldeBid;

                $resteApayer = ($f['amount'] - $soldeBid);
                $pourcentage = ((1 - ($resteApayer / $f['amount'])) * 100);

                $decimales            = 2;
                $decimalesPourcentage = 2;

                if ($soldeBid >= $f['amount']) {
                    $payer                = $f['amount'];
                    $resteApayer          = 0;
                    $pourcentage          = 100;
                    $decimales            = 0;
                    $decimalesPourcentage = 0;
                }

                ?>
                <div class="post-box clearfix">
                    <h3><?= $f['title'] ?>, <small><?= $this->companies->city ?><?= ($this->companies->city != '' ? ',' : '') ?> <?= $this->companies->zip ?></small></h3>
                    <?php
                    if ($this->projects_status->status > \projects_status::EN_FUNDING) {
                        $dateRest = $this->lng['preteur-synthese']['termine'];
                        $reste    = '';
                    } else {
                        $reste = $this->lng['preteur-synthese']['reste'] . ' ';
                        ?>
                        <script>
                            var cible<?= $f['id_project'] ?> = new Date('<?= $mois_jour ?>, <?= $annee ?> <?= $this->heureFinFunding ?>:00');
                            var letime<?= $f['id_project'] ?> = parseInt(cible<?= $f['id_project'] ?>.getTime() / 1000, 10);
                            setTimeout('decompte(letime<?= $f['id_project'] ?>,"valFav<?= $f['id_project'] ?>")', 500);
                        </script>
                        <?php
                    }
                    if ($f['photo_projet'] != '') {
                        ?><a href="<?= $this->lurl ?>/projects/detail/<?= $f['slug'] ?>" class="img-holder"><img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $f['photo_projet'] ?>" alt="<?= $f['photo_projet'] ?>"></a><?php
                    }
                    ?>
                    <div class="info">
                        <ul class="list">
                            <li><i class="icon-pig-gray"></i><?= $this->ficelle->formatNumber($f['amount'], 0) ?> €</li>
                            <li><i class="icon-clock-gray"></i><?= ($reste == '' ? '' : $reste) ?><span id="valFav<?= $f['id_project'] ?>"><?= $dateRest ?></span></li>
                            <li><i class="icon-target"></i><?= $this->lng['preteur-synthese']['couvert-a'] ?> <?= $this->ficelle->formatNumber($pourcentage, $decimalesPourcentage) ?> %</li>
                            <li><i class="icon-graph-gray"></i><?= $this->ficelle->formatNumber($f['avgrate'], 1) ?> %</li>
                        </ul>
                        <a class="btn <?= ($fast_ok == true ? '' : 'alone') ?>" href="<?= $this->lurl ?>/projects/detail/<?= $f['slug'] ?>"><?= $this->lng['preteur-synthese']['voir-le-projet'] ?></a>
                        <?php
                        // Si profile non validé par unilend

                        if ($fast_ok == true) {
                            // on check si on a coché les cgv ou pas
                            // cgu societe
                            if (in_array($this->clients->type, array(2, 4))) {
                                $this->settings->get('Lien conditions generales inscription preteur societe', 'type');
                                $this->lienConditionsGenerales_header = $this->settings->value;
                            } // cgu particulier
                            else {
                                $this->settings->get('Lien conditions generales inscription preteur particulier', 'type');
                                $this->lienConditionsGenerales_header = $this->settings->value;
                            }

                            // liste des cgv accpeté
                            $listeAccept_header = $this->acceptations_legal_docs->selectAccepts('id_client = ' . $this->clients->id_client);
                            //$listeAccept = array();
                            // Initialisation de la variable
                            $this->update_accept_header = false;

                            // On cherche si on a déjà le cgv
                            if (in_array($this->lienConditionsGenerales, $listeAccept_header)) {
                                $this->accept_ok_header = true;
                            } else {
                                $this->accept_ok_header = false;
                                // Si on a deja des cgv d'accepté
                                if ($listeAccept_header != false) {
                                    $this->update_accept_header = true;
                                }
                            }
                            ?><a class="btn darker popup-link <?= (!$this->accept_ok_header ? 'thickbox' : '') ?>" href="<?= (!$this->accept_ok_header ? $this->lurl . '/thickbox/pop_up_cgv' : $this->lurl . '/thickbox/pop_up_fast_pret/' . $f['id_project']) ?>"><?= $this->lng['preteur-synthese']['pret-rapide'] ?></a><?php
                        }
                        ?>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>
</div>
