<?php $this->bIsConnected = $this->clients->checkAccess(); ?>

<?php if ($this->ficelle->is_mobile() == true) : ?>
    <style type="text/css">
        .sidebar-fixed {
            left: auto;
            top: auto;
            margin-left: auto;
            position: relative;
            z-index: auto;
        }
    </style>
<?php endif; ?>
<?php
    $tab_date_retrait      = explode(' ', $this->projects->date_retrait_full);
    $heure                 = $tab_date_retrait[1];
    $tab_heure_sans_minute = explode(':', $heure);
    $heure_sans_minute     = $tab_heure_sans_minute[0] . "h" . $tab_heure_sans_minute[1];

    if ($heure_sans_minute == '00h00') {
        $HfinFunding       = explode(':', $this->heureFinFunding);
        $heure_sans_minute = $HfinFunding[0] . 'h00';
    }

    if ($this->projects_status->status != \projects_status::EN_FUNDING || $this->page_attente) :
        $this->dateRest = $this->lng['preteur-projets']['termine'];
    else :
        $this->heureFinFunding = $tab_heure_sans_minute[0] . ':' . $tab_heure_sans_minute[1]; ?>
    <script type="text/javascript">
        var cible = new Date('<?= $this->mois_jour ?>, <?= $this->annee ?> <?= $this->heureFinFunding ?>:00');
        var letime = parseInt((cible.getTime()) / 1000, 10);
        setTimeout('decompteProjetDetail(letime,"val","<?= $this->lurl ?>/projects/detail/<?= $this->params[0] ?>")', 500);
        setTimeout('decompteProjetDetail(letime,"valM","<?= $this->lurl ?>/projects/detail/<?= $this->params[0] ?>")', 500);
    </script>
<?php endif; ?>

<div class="main">
    <div class="shell">
        <div class="section-c clearfix section-single-project">
            <div class="page-title clearfix">
                <h1 class="left"><?= $this->lng['preteur-projets']['decouvrez-les'] ?> <?= $this->nbProjects ?> <?= $this->lng['preteur-projets']['projets-en-cours'] ?></h1>
                <nav class="nav-tools left">
                    <ul>
                        <?php if ($this->positionProject['previous'] != '') : ?>
                            <li>
                            <a class="prev notext" href="<?= $this->lurl ?>/projects/detail/<?= $this->positionProject['previous'] ?>">arrpw</a>
                            </li>
                        <?php endif; ?>
                            <li <?= ($this->positionProject['previous'] == '' || $this->positionProject['next'] == '' ? 'class="listpro"' : '') ?> >
                                <a class="view notext" href="<?= $this->lurl ?>/<?= (false === isset($_SESSION['page_projet']) || $_SESSION['page_projet'] === 'projets_fo' ? $this->tree->getSlug(4, $this->language) : 'projects') ?>">view</a>
                            </li>
                        <?php if ($this->positionProject['next'] != '') : ?>
                            <li>
                            <a class="next notext" href="<?= $this->lurl ?>/projects/detail/<?= $this->positionProject['next'] ?>">arrow</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>

            <?php if (isset($_SESSION['messFinEnchere']) && $_SESSION['messFinEnchere'] != false) : ?>
                <div class="messFinEnchere" style="float:right;color:#C84747;margin-top:18px;"><?= $_SESSION['messFinEnchere'] ?></div>
                <?php unset($_SESSION['messFinEnchere']); ?>
                <script type="text/javascript">
                    setTimeout(function () {
                        $('.messFinEnchere').slideUp();
                    }, 5000);
                </script>
            <?php elseif (isset($_SESSION['messPretOK']) && $_SESSION['messPretOK'] != false) : ?>
                <div class="messPretOK" style="float:right;color:#40B34F;margin-top:18px;"><?= $_SESSION['messPretOK'] ?></div>
                <?php unset($_SESSION['messPretOK']); ?>
                <script type="text/javascript">
                    setTimeout(function () {
                        $('.messPretOK').slideUp();
                    }, 5000);
                </script>
            <?php endif; ?>

            <h2><?= $this->projects->title ?></h2>
            <div class="content-col left">
                <div class="project-c">
                    <div class="top clearfix">

                        <a <?= ($this->bIsConnected ? '' : 'style="visibility:hidden;"') ?> class="fav-btn right <?= $this->favori ?>" id="fav" onclick="favori(<?= $this->projects->id_project ?>, 'fav',<?= $this->clients->id_client ?>, 'detail');"><?= ($this->favori == 'active' ? $this->lng['preteur-projets']['retirer-de-mes-favoris'] : $this->lng['preteur-projets']['ajouter-a-mes-favoris']) ?>
                            <i></i></a>
                        <p class="left multi-line">
                            <em><?= $this->projects->nature_project ?></em>
                            <?php if ($this->projects_status->status == \projects_status::EN_FUNDING) : ?>
                                <strong class="green-span">
                                <i class="icon-clock-green"></i><?= $this->lng['preteur-projets']['reste'] ?>
                                <span id="val"><?= $this->dateRest ?></span></strong>,
                            <?php else : ?>
                            <strong class="red-span"><span id="val"><?= $this->dateRest ?></span></strong>
                            <?php endif; ?>
                            <?= $this->lng['preteur-projets']['le'] ?> <?= strtolower($this->date_retrait) ?> <?= $this->lng['preteur-projets']['a'] ?> <?= $heure_sans_minute ?>
                        </p>
                    </div>
                    <div class="main-project-info clearfix">
                        <?php if ($this->projects->photo_projet != '') : ?>
                            <div class="img-holder borderless left">
                                <img src="<?= $this->surl ?>/images/dyn/projets/169/<?= $this->projects->photo_projet ?>" alt="<?= $this->projects->photo_projet ?>">
                            <?php if ($this->projects->lien_video != '') : ?>
                                <a class="link" target="_blank" href="<?= $this->projects->lien_video ?>"><?= $this->lng['preteur-projets']['lancer-la-video'] ?></a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="info left">
                            <?php $this->companies->get($this->projects->id_company); ?>
                            <h3><?= $this->companies->name ?></h3>
                                <?= ($this->companies->city != '' ? '<p><i class="icon-place"></i>' . $this->lng['preteur-projets']['localisation'] . ' : ' . $this->companies->city . '</p>' : '') ?>
                                <?= ($this->companies->sector != '' ? '<p>' . $this->lng['preteur-projets']['secteur'] . ' : ' . $this->lSecteurs[$this->companies->sector] . '</p>' : '') ?>
                            <ul class="stat-list">
                                <li>
                                    <span class="i-holder"><i class="icon-calendar tooltip-anchor" data-placement="right" data-original-title="<?= $this->lng['preteur-projets']['info-periode'] ?>"></i></span>
                                    <?= ($this->projects->period == 1000000 ? $this->lng['preteur-projets']['je-ne-sais-pas'] : '<span>' . $this->projects->period . '</span> <br />' . $this->lng['preteur-projets']['mois']) ?>
                                </li>
                                <li>
                                    <span class="i-holder"><i class="icon-gauge tooltip-anchor" data-placement="right" data-original-title="<?= $this->lng['preteur-projets']['info-note'] ?>"></i></span>

                                    <div class="cadreEtoiles">
                                        <div class="etoile <?= $this->lNotes[$this->projects->risk] ?>"></div>
                                    </div>
                                </li>

                                <li>
                                    <span class="i-holder"><i class="icon-graph tooltip-anchor" data-placement="right" data-original-title="<?= $this->lng['preteur-projets']['info-taux-moyen'] ?>"></i></span>
                                    <?php if ($this->CountEnchere > 0) : ?>
                                        <span><?= $this->ficelle->formatNumber($this->avgRate, 1) . ' %' ?></span>
                                    <?php else : ?>
                                        <span><?= $this->projects->target_rate . ($this->projects->target_rate == '-' ? '' : ' %') ?></span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <nav class="tabs-nav">
                        <ul>
                            <?php if ($this->projects_status->status == \projects_status::EN_FUNDING) :  ?>
                                <li class="active"><a href="#"><?= $this->lng['preteur-projets']['carnet-dordres'] ?></a></li>
                                <li><a href="#"><?= $this->lng['preteur-projets']['presentation'] ?></a></li>
                            <?php else : ?>
                                <li class="active"><a href="#"><?= $this->lng['preteur-projets']['presentation'] ?></a></li>
                            <?php endif; ?>
                            <li><a href="#"><?= $this->lng['preteur-projets']['comptes'] ?></a></li>
                            <?php if ($this->projects_status->status == \projects_status::FUNDE || $this->projects_status->status >= \projects_status::REMBOURSEMENT) : ?>
                                <?php if (isset($_SESSION['client']) && $this->bIsLender) : ?>
                                    <li><a href="#"><?= $this->lng['preteur-projets']['suivi-projet'] ?></a></li>
                                <?php endif;?>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <div class="tabs">
                        <?php if ($this->projects_status->status == \projects_status::EN_FUNDING) : ?>
                        <div class="tab tc" id="bids">
                            <?php if (count($this->lEnchere) > 0) : ?>
                            <table class="table orders-table">
                                <tr>
                                    <th width="125"><span id="triNum">N°<i class="icon-arrows"></i></span></th>
                                    <th width="180">
                                        <span id="triTx"><?= $this->lng['preteur-projets']['taux-dinteret'] ?>
                                            <i class="icon-arrows"></i></span>
                                        <small><?= $this->lng['preteur-projets']['taux-moyen'] ?> : <?= $this->ficelle->formatNumber($this->avgRate, 1) ?> %</small>
                                    </th>
                                    <th width="214">
                                        <span id="triAmount"><?= $this->lng['preteur-projets']['montant'] ?>
                                            <i class="icon-arrows"></i></span>
                                        <small><?= $this->lng['preteur-projets']['montant-moyen'] ?> : <?= $this->ficelle->formatNumber($this->avgAmount / 100) ?> €</small>
                                    </th>
                                    <th width="101">
                                        <span id="triStatuts"><?= $this->lng['preteur-projets']['statuts'] ?>
                                            <i class="icon-arrows"></i></span></th>
                                </tr>
                                <?php foreach ($this->lEnchere as $key => $e) : ?>
                                    <?php $vous = ($this->lenders_accounts->id_lender_account == $e['id_lender_account']) ?>
                                    <?php if ($this->CountEnchere >= 12) : ?>
                                        <?php if ($e['ordre'] <= 5 || $e['ordre'] > $this->CountEnchere - 5) : ?>
                                            <tr <?= ($vous == true ? ' class="enchereVousColor"' : '') ?>>
                                                <td><?= ($vous == true ? '<span class="enchereVous">' . $this->lng['preteur-projets']['vous'] . ' : &nbsp;&nbsp;&nbsp;' . $e['ordre'] . '</span>' : $e['ordre']) ?></td>
                                                <td><?= $this->ficelle->formatNumber($e['rate'], 1) ?> %</td>
                                                <td><?= $this->ficelle->formatNumber($e['amount'] / 100, 0) ?> €</td>
                                                <td class="<?= ($e['status'] == 1 ? 'green-span' : ($e['status'] == 2 ? 'red-span' : '')) ?>"><?= $this->status[$e['status']] ?></td>
                                            </tr>
                                        <?php elseif ($e['ordre'] == 6) : ?>
                                        <tr>
                                            <td colspan="4" class="nth-table-row displayAll" style="cursor:pointer;">...</td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php else : ?>
                                        <tr <?= ($vous == true ? ' class="enchereVousColor"' : '') ?>>
                                            <td><?= ($vous == true ? '<span class="enchereVous">' . $this->lng['preteur-projets']['vous'] . ' : &nbsp;&nbsp;&nbsp;' . $e['ordre'] . '</span>' : $e['ordre']) ?></td>
                                            <td><?= $this->ficelle->formatNumber($e['rate'], 1) ?> %</td>
                                            <td><?= $this->ficelle->formatNumber($e['amount'] / 100, 0) ?> €</td>
                                            <td class="<?= ($e['status'] == 1 ? 'green-span' : ($e['status'] == 2 ? 'red-span' : '')) ?>"><?= $this->status[$e['status']] ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </table>
                            <?php if ($this->CountEnchere >= 12) : ?>
                            <a class="btn btn-large displayAll"><?= $this->lng['preteur-projets']['voir-tout-le-carnet-dordres'] ?></a>
                            <?php else: ?>
                            <div class="displayAll"></div>
                            <?php endif; ?>
                            <script>
                                $("#triNum").click(function () {
                                    $("#tri").html('ordre');
                                    $(".displayAll").click();
                                });

                                $("#triTx").click(function () {
                                    $("#tri").html('rate');
                                    $(".displayAll").click();
                                });

                                $("#triAmount").click(function () {
                                    $("#tri").html('amount');
                                    $(".displayAll").click();
                                });

                                $("#triStatuts").click(function () {
                                    $("#tri").html('status');
                                    $(".displayAll").click();
                                });

                                $(".displayAll").click(function () {

                                    var tri = $("#tri").html();
                                    var direction = $("#direction").html();
                                    $.post(add_url + '/ajax/displayAll', {
                                        id: <?= $this->projects->id_project ?>,
                                        tri: tri,
                                        direction: direction
                                    }).done(function (data) {
                                        $('#bids').html(data)
                                    });
                                });
                            </script>
                        <?php else : ?>
                            <p><?= $this->lng['preteur-projets']['aucun-enchere'] ?></p>
                        <?php endif; ?>
                        </div>
                        <div id="tri" style="display:none;">ordre</div>
                        <div id="direction" style="display:none;">1</div>
                        <?php endif; ?>
                        <div class="tab">
                            <article class="ex-article">
                                <h3>
                                    <a href="#"><?= $this->lng['preteur-projets']['qui-sommes-nous'] ?></a><i class="icon-arrow-down"></i>
                                </h3>
                                <div class="article-entry">
                                    <p><?= $this->projects->presentation_company ?></p>
                                </div>
                            </article>
                            <article class="ex-article">
                                <h3>
                                    <a href="#"><?= $this->lng['preteur-projets']['pourquoi-ce-pret'] ?></a><i class="icon-arrow-down"></i>
                                </h3>

                                <div class="article-entry">
                                    <p><?= $this->projects->objectif_loan ?></p>
                                </div>
                            </article>
                            <article class="ex-article">
                                <h3>
                                    <a href="#"><?= $this->lng['preteur-projets']['pourquoi-pouvez-vous-nous-faire-confiance'] ?></a><i class="icon-arrow-down"></i>
                                </h3>

                                <div class="article-entry">
                                    <p><?= $this->projects->means_repayment ?></p>
                                </div>
                            </article>
                        </div>
                        <div class="tab">
                        <?php if (false === $this->bIsConnected) : ?>
                            <div>
                                <?= $this->lng['preteur-projets']['contenu-comptes-financiers'] ?>
                            </div>
                            <br/>
                            <div style="text-align:center;">
                                <a target="_parent" href="<?= $this->lng['preteur-projets']['cta-lien-comptes-financiers'] ?>" class="btn btn-medium"><?= $this->lng['preteur-projets']['cta-comptes-financiers'] ?></a>
                            </div>
                        <?php  else : ?>
                            <div class="statistic-tables year-nav clearfix">
                                <ul class="right">
                                    <li>
                                        <div class="annee"><?= $this->anneeToday[1] ?></div>
                                    </li>
                                    <li>
                                        <div class="annee"><?= $this->anneeToday[2] ?></div>
                                    </li>
                                    <li>
                                        <div class="annee"><?= $this->anneeToday[3] ?></div>
                                    </li>
                                </ul>
                            </div>
                            <div class="statistic-table">
                                <table>
                                    <tr>
                                        <th colspan="4"><?= $this->lng['preteur-projets']['compte-de-resultats'] ?></th>
                                    </tr>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['chiffe-daffaires'] ?></td>
                                        <?php
                                        for ($i = 1; $i <= 3; $i++) {
                                            echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->lBilans[$this->anneeToday[$i]]['ca'], 0) . ' €</td>';
                                        }
                                        ?>
                                    </tr>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['resultat-brut-dexploitation'] ?></td>
                                        <?php
                                        for ($i = 1; $i <= 3; $i++) {
                                            echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->lBilans[$this->anneeToday[$i]]['resultat_brute_exploitation'], 0) . ' €</td>';
                                        }
                                        ?>
                                    </tr>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['resultat-dexploitation'] ?></td>
                                        <?php
                                        for ($i = 1; $i <= 3; $i++) {
                                            echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->lBilans[$this->anneeToday[$i]]['resultat_exploitation'], 0) . ' €</td>';
                                        }
                                        ?>
                                    </tr>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['investissements'] ?></td>
                                        <?php
                                        for ($i = 1; $i <= 3; $i++) {
                                            echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->lBilans[$this->anneeToday[$i]]['investissements'], 0) . ' €</td>';
                                        }
                                        ?>
                                    </tr>
                                </table>
                            </div>

                            <div class="statistic-table">
                                <table>
                                    <tr>
                                        <th><?= $this->lng['preteur-projets']['bilan'] ?></th>
                                    </tr>
                                    <tr>
                                        <td class="inner-table" colspan="4">
                                            <table>
                                                <tr>
                                                    <th colspan="4"><?= $this->lng['preteur-projets']['actif'] ?></th>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['immobilisations-corporelles'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['immobilisations_corporelles'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['immobilisations-incorporelles'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['immobilisations_incorporelles'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['immobilisations-financieres'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['immobilisations_financieres'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['stocks'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['stocks'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['creances-clients'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['creances_clients'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['disponibilites'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['disponibilites'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['valeurs-mobilieres-de-placement'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['valeurs_mobilieres_de_placement'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>

                                                <tr class="total-row">
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['total-bilan-actifs'] ?></td>
                                                    <?php
                                                    for ($i = 1; $i <= 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->totalAnneeActif[$i], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="inner-table" colspan="4">
                                            <table>
                                                <tr>
                                                    <th colspan="4"><?= $this->lng['preteur-projets']['passif'] ?></th>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['capitaux-propres'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['capitaux_propres'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['provisions-pour-risques-charges'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['provisions_pour_risques_et_charges'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['amortissement-sur-immo'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['amortissement_sur_immo'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['dettes-financieres'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['dettes_financieres'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['dettes-fournisseurs'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['dettes_fournisseurs'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['autres-dettes'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['autres_dettes'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr class="total-row">
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['total-bilan-passifs'] ?></td>
                                                    <?php
                                                    for ($i = 1; $i <= 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->totalAnneePassif[$i], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        <?php endif; ?>
                        </div>
                        <?php if ($this->projects_status->status == \projects_status::FUNDE || $this->projects_status->status >= \projects_status::REMBOURSEMENT): ?>
                            <div class="tab">
                                <div class="article">
                                    <p>
                                        <?= $this->lng['preteur-projets']['vous-avez-prete'] ?>
                                        <strong class="pinky-span"><?= $this->ficelle->formatNumber($this->bidsvalid['solde']) ?>&nbsp;€</strong>
                                    </p>
                                    <p>
                                        <strong class="pinky-span"><?= $this->ficelle->formatNumber($this->sumRemb) ?>&nbsp;€</strong>
                                        <?= $this->lng['preteur-projets']['vous-ont-ete-rembourses-il-vous-reste'] ?>
                                        <strong class="pinky-span"><?= $this->ficelle->formatNumber($this->sumRestanteARemb) ?>&nbsp;€</strong>
                                        <?= $this->lng['preteur-projets']['a-percevoir-sur-une-periode-de'] ?>
                                        <strong class="pinky-span"><?= $this->nbPeriod ?> <?= $this->lng['preteur-projets']['mois'] ?></strong>
                                    </p>
                                </div>
                                <?php if ($this->bidsvalid['solde'] > 0) : ?>
                                    <?php foreach ($this->aStatusHistory as $aHistory): ?>
                                        <?php if (isset($this->lng['preteur-projets']['titre-historique-statut-' . $aHistory['status']])): ?>
                                            <p>
                                                <?= date('d/m/Y', strtotime($aHistory['added'])) ?>
                                                <strong class="pinky-span"><?= $this->lng['preteur-projets']['titre-historique-statut-' . $aHistory['status']] ?></strong>
                                                <br/>
                                                <?php if (false === empty($aHistory['site_content'])): ?>
                                                    <?= nl2br($aHistory['site_content']) ?>
                                                    <?php if (1 == $aHistory['failure']): ?>
                                                        <p>Vous avez récupéré <strong class="pinky-span"><?= $this->ficelle->formatNumber($this->sumRemb / $this->bidsvalid['solde'] * 100) ?>&nbsp;%</strong> de votre capital.</p>
                                                    <?php endif; ?>
                                                    <br/><br/>
                                                <?php endif; ?>
                                            </p>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php $this->fireView('../blocs/sidebar-project'); ?>
        </div>

        <div class="single-project-mobile">
            <h3><?= $this->projects->title ?></h3>
            <p><?= $this->projects->nature_project ?></p>
            <?php if ($this->projects_status->status == \projects_status::EN_FUNDING) : ?>
                <strong class="green-span"><i class="icon-clock-green"></i><?= $this->lng['preteur-projets']['reste'] ?>
                <span id="valM"><?= $this->dateRest ?></span></strong>,
            <?php else : ?>
                <strong class="red-span"><span id="valM"><?= $this->dateRest ?></span></strong>
            <?php endif; ?>
            <?= $this->lng['preteur-projets']['le'] ?> <?= strtolower($this->date_retrait) ?> <?= $this->lng['preteur-projets']['a'] ?> <?= $heure_sans_minute ?>
            <?php $this->fireView('../blocs/project-mobile-header'); ?>
            <img src="<?= $this->surl ?>/images/dyn/projets/169/<?= $this->projects->photo_projet ?>" alt="<?= $this->projects->photo_projet ?>">
            <?php if ($this->bIsConnected && false === $this->page_attente && $this->clients_status->status == \projects_status::FUNDE) : ?>
                <div class="single-project-actions">
                    <a href="<?= $this->lurl . '/thickbox/pop_up_offer_mobile/' . $this->projects->id_project ?>" class="btn popup-link"><?= $this->lng['preteur-projets']['preter'] ?></a>
                </div>
            <?php elseif (false === $this->bIsConnected) : ?>
            <div class="single-project-actions">
                <a target="_parent" class="btn login-toggle" id="seconnecter" style="width:210px; display:block;margin:auto; float: none;"><?= $this->lng['preteur-projets']['se-connecter'] ?></a>
                <a href="<?= $this->lurl . '/' . $this->tree->getSlug(127, $this->language) ?>" target="_parent" class="btn sinscrire_cta" id="sinscrire" style=""><?= $this->lng['preteur-projets']['sinscrire'] ?></a>
            </div>
            <?php endif; ?>
            <?php if ($this->projects_status->status == \projects_status::EN_FUNDING) : ?>
                <article class="ex-article">
                    <h3><a href="#"><?= $this->lng['preteur-projets']['carnet-dordres'] ?></a><i class="icon-arrow-down up"></i></h3>
                    <div class="article-entry" style="display: none;">
                        <div id="bids_mobile"><?= $this->fireView('../ajax/displayAll_mobile') ?></div>
                        <div id="tri_mobile" style="display:none;">ordre</div>
                        <div id="direction_mobile" style="display:none;">1</div>
                    </div>
                </article>
            <?php endif; ?>
            <article class="ex-article">
                <h3>
                    <a href="#"><?= $this->lng['preteur-projets']['presentation'] ?></a><i class="icon-arrow-down up"></i>
                </h3>
                <div class="article-entry" style="display: none;">
                    <h5><a href="#"><?= $this->lng['preteur-projets']['qui-sommes-nous'] ?></a></h5>
                    <div class="article-entry">
                        <p><?= $this->projects->presentation_company ?></p>
                    </div>
                    <h5><a href="#"><?= $this->lng['preteur-projets']['pourquoi-ce-pret'] ?></a></h5>
                    <div class="article-entry">
                        <p><?= $this->projects->objectif_loan ?></p>
                    </div>
                    <h5><a href="#"><?= $this->lng['preteur-projets']['pourquoi-pouvez-vous-nous-faire-confiance'] ?></a></h5>
                    <div class="article-entry">
                        <p><?= $this->projects->means_repayment ?></p>
                    </div>
                </div>
            </article>
            <article class="ex-article">
                <h3><a href="#"><?= $this->lng['preteur-projets']['comptes'] ?></a><i class="icon-arrow-down up"></i></h3>
                <div class="article-entry" style="display: none;">
                    <p>
                        <div class="tab">
                        <?php if (false === $this->bIsConnected) : ?>
                            <div>
                                <?= $this->lng['preteur-projets']['contenu-comptes-financiers'] ?>
                            </div>
                            <br/>
                            <div style="text-align:center;">
                                <a target="_parent" href="<?= $this->lng['preteur-projets']['cta-lien-comptes-financiers'] ?>" class="btn btn-medium"><?= $this->lng['preteur-projets']['cta-comptes-financiers'] ?></a>
                            </div>
                        <?php else : ?>
                            <div class="statistic-table">
                                <table>
                                    <tr class="year-nav">
                                        <th></th>
                                        <th><?= $this->anneeToday[1] ?></th>
                                        <th><?= $this->anneeToday[2] ?></th>
                                        <th><?= $this->anneeToday[3] ?></th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" style="color:white;"><?= $this->lng['preteur-projets']['compte-de-resultats'] ?></th>
                                    </tr>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['chiffe-daffaires'] ?></td>
                                        <?php
                                        for ($i = 1; $i <= 3; $i++) {
                                            echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->lBilans[$this->anneeToday[$i]]['ca'], 0) . ' €</td>';
                                        }
                                        ?>
                                    </tr>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['resultat-brut-dexploitation'] ?></td>
                                        <?php
                                        for ($i = 1; $i <= 3; $i++) {
                                            echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->lBilans[$this->anneeToday[$i]]['resultat_brute_exploitation'], 0) . ' €</td>';
                                        }
                                        ?>
                                    </tr>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['resultat-dexploitation'] ?></td>
                                        <?php
                                        for ($i = 1; $i <= 3; $i++) {
                                            echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->lBilans[$this->anneeToday[$i]]['resultat_exploitation'], 0) . ' €</td>';
                                        }
                                        ?>
                                    </tr>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['investissements'] ?></td>
                                        <?php
                                        for ($i = 1; $i <= 3; $i++) {
                                            echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->lBilans[$this->anneeToday[$i]]['investissements'], 0) . ' €</td>';
                                        }
                                        ?>
                                    </tr>
                                    <tr>
                                        <th colspan="4"><?= $this->lng['preteur-projets']['bilan'] ?></th>
                                    </tr>
                                    <tr>
                                        <td class="inner-table" colspan="4">
                                            <table>
                                                <tr>
                                                    <th colspan="4"><?= $this->lng['preteur-projets']['actif'] ?></th>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['immobilisations-corporelles'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['immobilisations_corporelles'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['immobilisations-incorporelles'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['immobilisations_incorporelles'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['immobilisations-financieres'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['immobilisations_financieres'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['stocks'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['stocks'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['creances-clients'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['creances_clients'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['disponibilites'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['disponibilites'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['valeurs-mobilieres-de-placement'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['valeurs_mobilieres_de_placement'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>

                                                <tr class="total-row">
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['total-bilan-actifs'] ?></td>
                                                    <?php
                                                    for ($i = 1; $i <= 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->totalAnneeActif[$i], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="inner-table" colspan="4">
                                            <table>
                                                <tr>
                                                    <th colspan="4"><?= $this->lng['preteur-projets']['passif'] ?></th>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['capitaux-propres'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['capitaux_propres'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['provisions-pour-risques-charges'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['provisions_pour_risques_et_charges'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['amortissement-sur-immo'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['amortissement_sur_immo'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['dettes-financieres'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['dettes_financieres'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['dettes-fournisseurs'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['dettes_fournisseurs'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['autres-dettes'] ?></td>
                                                    <?php
                                                    for ($i = 0; $i < 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->listAP[$i]['autres_dettes'], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr class="total-row">
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['total-bilan-passifs'] ?></td>
                                                    <?php
                                                    for ($i = 1; $i <= 3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">' . $this->ficelle->formatNumber($this->totalAnneePassif[$i], 0) . ' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        <?php endif; ?>
                        </div>
                    </p>
                </div>
            </article>
            <?php if (($this->projects_status->status == \projects_status::FUNDE || $this->projects_status->status >= \projects_status::REMBOURSEMENT) && isset($_SESSION['client']) && $this->bIsLender) : ?>
                <article class="ex-article">
                    <h3>
                        <a href="#"><?= $this->lng['preteur-projets']['suivi-projet'] ?></a><i class="icon-arrow-down up"></i>
                    </h3>
                    <div class="article-entry" style="display: none;">
                        <p>
                            <div class="tab">
                                <div class="article">
                                    <p><?= $this->lng['preteur-projets']['vous-avez-prete'] ?>
                                        <strong class="pinky-span"><?= $this->ficelle->formatNumber($this->bidsvalid['solde'], 0) ?> €</strong>
                                    </p>
                                    <p>
                                        <strong class="pinky-span"><?= $this->ficelle->formatNumber($this->sumRemb) ?> €</strong> <?= $this->lng['preteur-projets']['vous-ont-ete-rembourses-il-vous-reste'] ?>
                                        <strong class="pinky-span"><?= $this->ficelle->formatNumber($this->sumRestanteARemb) ?> €</strong> <?= $this->lng['preteur-projets']['a-percevoir-sur-une-periode-de'] ?>
                                        <strong class="pinky-span"><?= $this->nbPeriod ?> <?= $this->lng['preteur-projets']['mois'] ?></strong>
                                    </p>
                                </div>
                            </div>
                        </p>
                        <?php if ($this->bidsvalid['solde'] > 0) : ?>
                            <?php foreach ($this->aStatusHistory as $aHistory): ?>
                                <?php if (isset($this->lng['preteur-projets']['titre-historique-statut-' . $aHistory['status']])): ?>
                                    <p>
                                        <?= date('d/m/Y', strtotime($aHistory['added'])) ?>
                                        <strong class="pinky-span"><?= $this->lng['preteur-projets']['titre-historique-statut-' . $aHistory['status']] ?></strong>
                                        <br/>
                                        <?php if (false === empty($aHistory['site_content'])): ?>
                                            <?= nl2br($aHistory['site_content']) ?>
                                            <?php if (1 == $aHistory['failure']): ?>
                                                <p>Vous avez récupéré <strong class="pinky-span"><?= $this->ficelle->formatNumber($this->sumRemb / $this->bidsvalid['solde'] * 100) ?>&nbsp;%</strong> de votre capital.</p>
                                            <?php endif; ?>
                                            <br/>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $("#plusOffres").click(function () {
        $("#lOffres").slideToggle();
    });

    $("#montant_p").blur(function () {
        var montant = $("#montant_p").val();
        var tx = $("#tx_p").val();
        var form_ok = true;

        if (tx == '-') {
            form_ok = false;
        } else if (montant < <?= $this->pretMin ?>) {
            form_ok = false;
        }

        if (form_ok == true) {
            var val = {
                montant: montant,
                tx: tx,
                nb_echeances: <?= $this->projects->period ?>
            };
            $.post(add_url + '/ajax/load_mensual', val).done(function (data) {
                if (data != 'nok') {
                    $(".laMensual").slideDown();
                    $("#mensualite").html(data);
                }
            });
        }
    });

    $("#tx_p").change(function () {
        var montant = $("#montant_p").val();
        var tx = $("#tx_p").val();
        var form_ok = true;

        if (tx == '-') {
            form_ok = false;
        } else if (montant < <?= $this->pretMin ?>) {
            form_ok = false;
        }

        if (form_ok == true) {
            var val = {
                montant: montant,
                tx: tx,
                nb_echeances: <?= $this->projects->period ?>
            };
            $.post(add_url + '/ajax/load_mensual', val).done(function (data) {
                if (data != 'nok') {
                    $(".laMensual").slideDown();
                    $("#mensualite").html(data);
                }
            });
        }
    });
</script>
