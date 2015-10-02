<?php
// rend le bloque fixe sur mobiles
if ($this->ficelle->is_mobile() == true) {
    ?>
    <style type="text/css">
        .sidebar-fixed {
            left: auto;
            top: auto;
            margin-left: auto;
            position: relative;
            z-index: auto;
        }
    </style>
    <?php
}
// récupération de l'heure de retrait du projet qui n'est plus en params
$tab_date_retrait = explode(' ', $this->projects->date_retrait_full);
$heure = $tab_date_retrait[1];
$tab_heure_sans_minute = explode(':', $heure);
$heure_sans_minute = $tab_heure_sans_minute[0] . "h" . $tab_heure_sans_minute[1];

if ($heure_sans_minute == '00h00') {
    $HfinFunding = explode(':', $this->heureFinFunding);
    $heure_sans_minute = $HfinFunding[0] . 'h00';
}

if ($this->projects_status->status != 50 || $this->page_attente == true) {
    $this->dateRest = $this->lng['preteur-projets']['termine'];
} else {
    $this->heureFinFunding = $tab_heure_sans_minute[0] . ':' . $tab_heure_sans_minute[1];
    ?>
    <script type="text/javascript">
        var cible = new Date('<?=$this->mois_jour?>, <?=$this->annee?> <?=$this->heureFinFunding?>:00');
        var letime = parseInt((cible.getTime()) / 1000, 10);
        setTimeout('decompteProjetDetail(letime,"val","<?=$this->lurl?>/projects/detail/<?=$this->params[0]?>")', 500);
    </script>
    <?php
}
?>


<!--#include virtual="ssi-header-login.shtml"  -->
<div class="main">
    <div class="shell">
        <div class="section-c clearfix">
            <div class="page-title clearfix">
                <h1 class="left"><?= $this->lng['preteur-projets']['decouvrez-les'] ?><?= $_SESSION['page_projet'] ?> <?= $this->nbProjects ?> <?= $this->lng['preteur-projets']['projets-en-cours'] ?></h1>
                <nav class="nav-tools left">
                    <ul>
                        <?php
                        if ($this->positionProject['previous'] != '') {
                            ?>
                            <li>
                                <a class="prev notext" href="<?= $this->lurl ?>/projects/detail/<?= $this->positionProject['previous'] ?>">arrow</a>
                            </li>
                        <?php
                        }
                        ?>
                        <li><a class="view notext"
                               href="<?= $this->lurl ?>/<?= (false === $this->clients->checkAccess()) ? $this->tree->getSlug(4, $this->language) : 'projects' ?>">view</a>
                        </li>
                        <?php
                        if ($this->positionProject['next'] != '') {
                            ?>
                            <li>
                                <a class="next notext" href="<?= $this->lurl ?>/projects/detail/<?= $this->positionProject['next'] ?>">arrow</a>
                            </li>
                        <?php
                        }
                        ?>
                    </ul>
                </nav>
            </div>

            <?php
            if (isset($_SESSION['messFinEnchere']) && $_SESSION['messFinEnchere'] != false) {
            ?>
                <div class="messFinEnchere" style="float:right;color:#C84747;margin-top:18px;"><?= $_SESSION['messFinEnchere'] ?></div>
            <?php
            unset($_SESSION['messFinEnchere']);
            ?>
                <script type="text/javascript">
                    setTimeout(function () {
                        $('.messFinEnchere').slideUp();
                    }, 5000);


                </script>
            <?php
            } elseif (isset($_SESSION['messPretOK']) && $_SESSION['messPretOK'] != false) {
            ?>
                <div class="messPretOK" style="float:right;color:#40B34F;margin-top:18px;"><?= $_SESSION['messPretOK'] ?></div>
            <?php
            unset($_SESSION['messPretOK']);
            ?>
                <script type="text/javascript">
                    setTimeout(function () {
                        $('.messPretOK').slideUp();
                    }, 5000);


                </script>
            <?php
            }
            ?>

            <h2><?= $this->projects->title ?></h2>

            <div class="content-col left">
                <div class="project-c">
                    <div class="top clearfix">

                        <a <?= (!$this->clients->checkAccess() ? 'style="visibility:hidden;"' : '') ?>  class="fav-btn right <?= $this->favori ?>" id="fav" onclick="favori(<?= $this->projects->id_project ?>, 'fav',<?= $this->clients->id_client ?>, 'detail');"><?= ($this->favori == 'active' ? $this->lng['preteur-projets']['retirer-de-mes-favoris'] : $this->lng['preteur-projets']['ajouter-a-mes-favoris']) ?> <i></i></a>
                        <p class="left multi-line">
                            <em><?= $this->projects->nature_project ?></em>
                            <?php
                            // si projet pas terminé
                            if ($this->projects_status->status == 50) {
                            ?>
                                <strong class="green-span">
                                    <i class="icon-clock-green"></i>
                                    <?= $this->lng['preteur-projets']['reste'] ?>
                                    <span id="val"><?= $this->dateRest ?></span></strong>
                            <?php
                            } else {// sinon il est terminé
                            ?>
                                <strong class="red-span"><span id="val"><?= $this->dateRest ?></span></strong>
                            <?php
                            }
                            ?>

                            <?= $this->lng['preteur-projets']['le'] ?> <?= strtolower($this->date_retrait) ?> <?= $this->lng['preteur-projets']['a'] ?> <?= $heure_sans_minute ?>
                        </p>

                    </div>
                    <div class="main-project-info clearfix">
                        <?php
                        if ($this->projects->photo_projet != '') {
                        ?>
                        <div class="img-holder borderless left">
                            <img src="<?= $this->surl ?>/images/dyn/projets/169/<?= $this->projects->photo_projet ?>" alt="<?= $this->projects->photo_projet ?>">
                            <?php
                            if ($this->projects->lien_video != '') {
                            ?>
                                <a class="link" target="_blank" href="<?= $this->projects->lien_video ?>"><?= $this->lng['preteur-projets']['lancer-la-video'] ?></a>
                            <?php
                            }
                            ?>
                        </div>
                        <?php
                        }
                        ?>

                        <div class="info left">
                            <h3><?= $this->companies->name ?></h3>
                            <?= ($this->companies->city!=''?'<p><i class="icon-place"></i>'.$this->lng['preteur-projets']['localisation'].' : '.$this->companies->city.'</p>':'') ?>
							<?= ($this->companies->sector!=''?'<p>'.$this->lng['preteur-projets']['secteur'].' : '.$this->lSecteurs[$this->companies->sector].'</p>':'') ?>
                            <ul class="stat-list">
                                <li>
                                    <span class="i-holder">
                                        <i class="icon-calendar tooltip-anchor" data-placement="right" data-original-title="<?= $this->lng['preteur-projets']['info-periode'] ?>"></i>
                                    </span>
                                    <?= ($this->projects->period==1000000?$this->lng['preteur-projets']['je-ne-sais-pas']:'<span>'.$this->projects->period.'</span> <br />'.$this->lng['preteur-projets']['mois']) ?>
                                </li>
                                <li>
                                    <span class="i-holder">
                                        <i class="icon-gauge tooltip-anchor" data-placement="right" data-original-title="<?= $this->lng['preteur-projets']['info-note'] ?>"></i>
                                    </span>
                                    <div class="cadreEtoiles">
                                        <div class="etoile <?= $this->lNotes[$this->projects->risk] ?>"></div>
                                    </div>
                                </li>
                                <li>
                                    <span class="i-holder">
                                        <i class="icon-graph tooltip-anchor" data-placement="right" data-original-title="<?= $this->lng['preteur-projets']['info-taux-moyen'] ?>"></i>
                                    </span>
                                    <?php
                                    if ($this->CountEnchere > 0) {
                                    ?>
                                    <span><?= number_format(($this->projects_status->status==60||$this->projects_status->status>=80)?$this->AvgLoans:$this->avgRate, 1, ',', ' ').' %' ?></span>
                                    <?php
                                    } else {
                                    ?>
                                    <span><?= $this->projects->target_rate.($this->projects->target_rate == '-'?'':' %') ?></span>
                                    <?php
                                    }
                                    ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <nav class="tabs-nav">
                        <ul>
                            <?php
                            // en funding
                            if ($this->projects_status->status == 50) {
                            ?>
                            <li class="active">
                                <a href="#"><?= $this->lng['preteur-projets']['carnet-dordres'] ?></a>
                            </li>
                            <li>
                                <a href="#"><?= $this->lng['preteur-projets']['presentation'] ?></a>
                            </li>
                            <?php
                            } else {
                            ?>
                            <li class="active">
                                <a href="#"><?= $this->lng['preteur-projets']['presentation'] ?></a>
                            </li>
                            <?php
                            }
                            ?>
                            <li>
                                <a href="#"><?= $this->lng['preteur-projets']['comptes'] ?></a>
                            </li>
                            <?php
                            if ($this->projects_status->status == 60 || $this->projects_status->status >= 80) {
                                if (isset($_SESSION['client']) && $_SESSION['client']['status_pre_emp'] == 1) {
                            ?>
                            <li>
                                <a href="#"><?= $this->lng['preteur-projets']['suivi-projet'] ?></a>
                            </li>
                            <?php
                                }
                            }
                            ?>
                        </ul>
                    </nav>
                    <div class="tabs">
                        <?php
                        // en funding
                        if ($this->projects_status->status == 50) {
                        ?>
                        <div class="tab tc" id="bids">
                            <?php
                            if (count($this->lEnchere)>0) {
                            ?>
                            <table class="table orders-table">
                                <tr>
                                    <th width="125"><span id="triNum">N°<i class="icon-arrows"></i></span></th>
                                    <th width="180">
                                        <span id="triTx"><?= $this->lng['preteur-projets']['taux-dinteret'] ?>
                                            <i class="icon-arrows"></i>
                                        </span>
                                        <small>
                                            <?= $this->lng['preteur-projets']['taux-moyen'] ?> : <?= number_format($this->avgRate, 1, ',', ' ') ?> %
                                        </small>
                                    </th>
                                    <th width="214">
                                        <span id="triAmount">
                                            <?= $this->lng['preteur-projets']['montant'] ?>
                                            <i class="icon-arrows"></i>
                                        </span>
                                        <small>
                                            <?= $this->lng['preteur-projets']['montant-moyen'] ?> : <?= number_format($this->avgAmount/100, 2, ',', ' ') ?> €
                                        </small>
                                    </th>
                                    <th width="101">
                                        <span id="triStatuts">
                                            <?= $this->lng['preteur-projets']['statuts'] ?>
                                            <i class="icon-arrows"></i>
                                        </span>
                                    </th>
                                </tr>
                                <?
                                foreach ($this->lEnchere as $key => $e) {
                                    if ($this->lenders_accounts->id_lender_account == $e['id_lender_account']) {
                                        $vous = true;
                                    } else {
                                        $vous = false;
                                    }

                                    if ($this->CountEnchere >= 12) {
                                        if ($e['ordre'] <= 5 || $e['ordre'] > $this->CountEnchere-5) {
                                    ?>
                                    <tr <?= ($vous==true?' class="enchereVousColor"':'') ?>>
                                        <td><?= ($vous==true?'<span class="enchereVous">'.$this->lng['preteur-projets']['vous'].' : &nbsp;&nbsp;&nbsp;'.$e['ordre'].'</span>':$e['ordre']) ?></td>
                                        <td><?= number_format($e['rate'], 1, ',', ' ') ?> %</td>
                                        <td><?= number_format($e['amount']/100, 0, ',', ' ') ?> €</td>
                                        <td class="<?= ($e['status']==1?'green-span':($e['status']==2?'red-span':'')) ?>"><?= $this->status[$e['status']] ?></td>
                                    </tr>
                                    <?php
                                        }
                                        if ($e['ordre'] == 6) {
                                    ?>
                                            <tr>
                                                <td colspan="4" class="nth-table-row displayAll" style="cursor:pointer;">...</td>
                                            </tr>
                                    <?php
                                        }
                                    } else {
                                    ?>
                                    <tr <?= ($vous==true?' class="enchereVousColor"':'') ?>>
                                        <td><?= ($vous==true?'<span class="enchereVous">'.$this->lng['preteur-projets']['vous'].' : &nbsp;&nbsp;&nbsp;'.$e['ordre'].'</span>':$e['ordre']) ?></td>
                                        <td><?= number_format($e['rate'], 1, ',', ' ') ?> %</td>
                                        <td><?= number_format($e['amount']/100, 0, ',', ' ') ?> €</td>
                                        <td class="<?= ($e['status']==1?'green-span':($e['status']==2?'red-span':'')) ?>"><?= $this->status[$e['status']] ?></td>
                                    </tr>
                                    <?php
                                    }
                                }
                                ?>
                            </table>
                            <?php
                            if ($this->CountEnchere >= 12) {
                            ?>
                            <a class="btn btn-large displayAll"><?= $this->lng['preteur-projets']['voir-tout-le-carnet-dordres'] ?></a>
                            <?php
                            } else {
                            ?>
                            <div class="displayAll"></div>
                            <?php
                            }
                            ?>
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
                                        id: <?=$this->projects->id_project?>,
                                        tri: tri,
                                        direction: direction
                                    }).done(function (data) {
                                        $('#bids').html(data)
                                    });
                                });
                            </script>
                            <?php
                            } else {
                            ?>
                            <p><?= $this->lng['preteur-projets']['aucun-enchere'] ?></p>
                            <?php
                            }
                            ?>
                        </div>
                    <!-- /.tab -->
                        <div id="tri" style="display:none;">ordre</div>
                        <div id="direction" style="display:none;">1</div>
                        <?php
                        }
                        ?>
                        <div class="tab">
                            <article class="ex-article">
                                <h3><a href="#"><?= $this->lng['preteur-projets']['qui-sommes-nous'] ?></a>
                                    <i class="icon-arrow-down"></i></h3>
                                <div class="article-entry">
                                    <p><?= $this->projects->presentation_company ?></p>
                                </div>
                            </article>
                            <article class="ex-article">
                                <h3>
                                    <a href="#"><?= $this->lng['preteur-projets']['pourquoi-ce-pret'] ?></a>
                                    <i class="icon-arrow-down"></i>
                                </h3>
                                <div class="article-entry">
                                    <p><?= $this->projects->objectif_loan ?></p>
                                </div>
                            </article>
                            <article class="ex-article">
                                <h3>
                                    <a href="#"><?= $this->lng['preteur-projets']['pourquoi-pouvez-vous-nous-faire-confiance'] ?></a>
                                    <i class="icon-arrow-down"></i>
                                </h3>
                                <div class="article-entry">
                                    <p><?= $this->projects->means_repayment ?></p>
                                </div>
                            </article>
                        </div>
                    <!-- /.tab -->

                        <div class="tab">
                            <?php
                            if (!$this->clients->checkAccess()) {
                            ?>
                            <div>
                                <?= $this->lng['preteur-projets']['contenu-comptes-financiers'] ?>
                            </div>
                        <br/>
                            <div style="text-align:center;">
                                <a target="_parent" href="<?= $this->lng['preteur-projets']['cta-lien-comptes-financiers'] ?>" class="btn btn-medium">
                                    <?= $this->lng['preteur-projets']['cta-comptes-financiers'] ?>
                                </a>
                            </div>
                            <?
                            } else {
                            ?>
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
                                        <?
                                        for ($i = 1; $i<=3;$i++) {
                                            echo '<td class="sameSize" style="text-align:right;">'.number_format($this->lBilans[$this->anneeToday[$i]]['ca'], 0, ',', ' ').' €</td>';
                                        }
                                        ?>
                                    </tr>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['resultat-brut-dexploitation'] ?></td>
                                        <?
                                        for ($i = 1; $i<=3; $i++) {
                                            echo '<td class="sameSize" style="text-align:right;">'.number_format($this->lBilans[$this->anneeToday[$i]]['resultat_brute_exploitation'], 0, ',', ' ').' €</td>';
                                        }
                                        ?>
                                    </tr>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['resultat-dexploitation'] ?></td>
                                        <?
                                        for ($i = 1; $i<=3; $i++) {
                                            echo '<td class="sameSize" style="text-align:right;">'.number_format($this->lBilans[$this->anneeToday[$i]]['resultat_exploitation'], 0, ',', ' ').' €</td>';
                                        }
                                        ?>
                                    </tr>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['investissements'] ?></td>
                                        <?
                                        for ($i = 1; $i<=3; $i++) {
                                            echo '<td class="sameSize" style="text-align:right;">'.number_format($this->lBilans[$this->anneeToday[$i]]['investissements'], 0, ',', ' ').' €</td>';
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
                                                    <?
                                                    for ($i = 0; $i<3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['immobilisations_corporelles'], 0, ',', ' ').' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['immobilisations-incorporelles'] ?></td>
                                                    <?
                                                    for ($i = 0; $i<3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['immobilisations_incorporelles'], 0, ',', ' ').' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['immobilisations-financieres'] ?></td>
                                                    <?
                                                    for ($i = 0; $i<3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['immobilisations_financieres'], 0, ',', ' ').' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['stocks'] ?></td>
                                                    <?
                                                    for ($i = 0; $i<3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['stocks'], 0, ',', ' ').' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['creances-clients'] ?></td>
                                                    <?
                                                    for ($i = 0; $i<3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['creances_clients'], 0, ',', ' ').' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['disponibilites'] ?></td>
                                                    <?
                                                    for ($i = 0; $i<3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['disponibilites'], 0, ',', ' ').' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['valeurs-mobilieres-de-placement'] ?></td>
                                                    <?
                                                    for ($i = 0; $i<3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['valeurs_mobilieres_de_placement'], 0, ',', ' ').' €</td>';
                                                    }
                                                    ?>
                                                </tr>

                                                <tr class="total-row">
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['total-bilan-actifs'] ?></td>
                                                    <?
                                                    for ($i = 1; $i<=3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">'.number_format($this->totalAnneeActif[$i], 0, ',', ' ').' €</td>';
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
                                                    <?
                                                    for ($i = 0; $i<3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['capitaux_propres'], 0, ',', ' ').' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['provisions-pour-risques-charges'] ?></td>
                                                    <?
                                                    for ($i = 0; $i<3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['provisions_pour_risques_et_charges'], 0, ',', ' ').' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['amortissement-sur-immo'] ?></td>
                                                    <?
                                                    for ($i = 0; $i<3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['amortissement_sur_immo'], 0, ',', ' ').' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['dettes-financieres'] ?></td>
                                                    <?
                                                    for ($i = 0; $i<3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['dettes_financieres'], 0, ',', ' ').' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['dettes-fournisseurs'] ?></td>
                                                    <?
                                                    for ($i = 0; $i<3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['dettes_fournisseurs'], 0, ',', ' ').' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['autres-dettes'] ?></td>
                                                    <?
                                                    for ($i = 0; $i<3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">'.number_format($this->listAP[$i]['autres_dettes'], 0, ',', ' ').' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                                <tr class="total-row">
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['total-bilan-passifs'] ?></td>
                                                    <?
                                                    for ($i = 1; $i<=3; $i++) {
                                                        echo '<td class="sameSize" style="text-align:right;">'.number_format($this->totalAnneePassif[$i], 0, ',', ' ').' €</td>';
                                                    }
                                                    ?>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>

                            </div>

                            <?
                            }
                            ?>

                        </div>
                        <!-- /.tab -->
                        <?php
                        // project fundé
                        if ($this->projects_status->status == 60 || $this->projects_status->status >= 80) {
                        ?>
                        <div class="tab">
                            <div class="article">
                                <p>
                                    <?= $this->lng['preteur-projets']['vous-avez-prete'] ?>
                                    <strong class="pinky-span"><?= number_format($this->bidsvalid['solde'], 0, ',', ' ') ?> €</strong>
                                </p>

                                <p>
                                    <strong class="pinky-span"><?= number_format($this->sumRemb, 2, ',', ' ') ?> €</strong>
                                    <?= $this->lng['preteur-projets']['vous-ont-ete-rembourses-il-vous-reste'] ?>
                                    <strong class="pinky-span"><?= number_format($this->sumRestanteARemb, 2, ',', ' ') ?> €</strong>
                                    <?= $this->lng['preteur-projets']['a-percevoir-sur-une-periode-de'] ?>
                                    <strong class="pinky-span"><?= $this->nbPeriod ?> <?= $this->lng['preteur-projets']['mois'] ?></strong>
                                </p>
                            </div>
                        </div>
                        <!-- /.tab -->
                        <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
                $this->fireView('../blocs/sidebar-project');
            ?>
        </div>
    </div>
</div>

<!--#include virtual="ssi-footer.shtml"  -->

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
        }
        else if (montant < <?=$this->pretMin?>) {
            form_ok = false;
        }

        if (form_ok == true) {
            var val = {
                montant: montant,
                tx: tx,
                nb_echeances: <?=$this->projects->period?>
            }
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
        }
        else if (montant < <?=$this->pretMin?>) {
            form_ok = false;
        }

        if (form_ok == true) {
            var val = {
                montant: montant,
                tx: tx,
                nb_echeances: <?=$this->projects->period?>
            }
            $.post(add_url + '/ajax/load_mensual', val).done(function (data) {

                if (data != 'nok') {

                    $(".laMensual").slideDown();
                    $("#mensualite").html(data);
                }
            });
        }
    });
</script>