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
        <button id="scrollUp" style="display:none"><i class="icon-scroll-up"></i></button>
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
                            <i></i>
                        </a>
                        <p class="left multi-line">
                            <em><?= $this->projects->nature_project ?></em>
                            <?php if ($this->projects_status->status == \projects_status::EN_FUNDING): ?>
                                <strong class="green-span">
                                <i class="icon-clock-green"></i><?= $this->lng['preteur-projets']['reste'] ?>
                                <span id="val"><?= $this->dateRest ?></span></strong>,
                            <?php else: ?>
                                <strong class="red-span"><span id="val"><?= $this->dateRest ?></span></strong>
                            <?php endif; ?>
                            <?= $this->lng['preteur-projets']['le'] ?> <?= strtolower($this->date_retrait) ?> <?= $this->lng['preteur-projets']['a'] ?> <?= $heure_sans_minute ?>
                        </p>
                    </div>
                    <div class="main-project-info clearfix">
                        <?php if ($this->projects->photo_projet != '') : ?>
                        <div class="img-holder borderless left">
                            <img src="<?= $this->surl ?>/images/dyn/projets/169/<?= $this->projects->photo_projet ?>" alt="<?= $this->projects->photo_projet ?>">
                        </div>
                        <?php endif; ?>
                        <div class="info left">
                            <?php $this->companies->get($this->projects->id_company); ?>
                            <h3><?= $this->companies->name ?></h3>
                                <?= ($this->companies->city != '' ? '<p><i class="icon-place"></i>' . $this->lng['preteur-projets']['localisation'] . ' : ' . $this->companies->city . '</p>' : '') ?>
                                <?= ($this->companies->sector != '' ? '<p>' . $this->lng['preteur-projets']['secteur'] . ' : ' . $this->lSecteurs[$this->companies->sector] . '</p>' : '') ?>
                            <ul class="stat-list">
                                <li>
                                    <span class="i-holder">
                                        <i class="icon-calendar tooltip-anchor" data-placement="right" data-original-title="<?= $this->lng['preteur-projets']['info-periode'] ?>"></i>
                                    </span>
                                    <?= ($this->projects->period == 1000000 ? $this->lng['preteur-projets']['je-ne-sais-pas'] : '<span>' . $this->projects->period . '</span> <br />' . $this->lng['preteur-projets']['mois']) ?>
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
                            <li class="active"><a href="#bids">Présentation du projet</a></li>
                            <li class=""><a href="#presentation">Comptes financiers</a></li>
                            <li class=""><a href="#infos">Suivi du projet</a></li>
                        </ul>
                    </nav>
                    <div class="tabs">
                        <?php if ($this->projects_status->status == \projects_status::EN_FUNDING) : ?>
                        <div class="tab tc" id="bids">
                            <?php if (count($this->aBidsOnProject) > 0) : ?>
                            <table class="table orders-table">
                                <tr>
                                    <th width="25%">
                                        <span id="triTaux">taux<i class="icon-grey icon-arrows"></i></span>
                                    </th>
                                    <th width="25%">
                                        <span id="triAmount">montant total<i class="icon-grey icon-arrows"></i></span>
                                    </th>
                                    <th width="25%">
                                        <span id="triOffers">nb offres<i class="icon-grey icon-arrows"></i></span>
                                    </th>
                                    <th width="25%">
                                        <span id="triCurrentOffers">offres en cours<i class="icon-grey icon-arrows"></i></span>
                                    </th>
                                </tr>
                                <tr class="table-body">
                                  <td class="bid-number">
                                      <span class="order-rate">4 % <i class="icon-grey icon-simple-arrow"></i></span>
                                  </td>
                                    <td>
                                      <span class="total-amount">850,15€</span>
                                    </td>
                                    <td>
                                      <span class="number-of-offers">42</span>
                                    </td>
                                    <td>
                                        <span class="offers-rate">100%</span>
                                    </td>
                                </tr>

                                <tr class="table-body">
                                    <td class="bid-number">
                                        <span class="order-rate">4 % <i class="icon-grey icon-simple-arrow"></i></span>
                                    </td>
                                    <td>
                                        <span class="total-amount">850,15€</span>
                                    </td>
                                    <td>
                                        <span class="number-of-offers">42</span>
                                    </td>
                                    <td>
                                        <span class="offers-rate">100%</span>
                                    </td>
                                </tr>

                                <tr class="table-body">
                                    <td class="bid-number">
                                        <span class="order-rate">4 % <i class="icon-grey icon-simple-arrow"></i></span>
                                    </td>
                                    <td>
                                        <span class="total-amount">850,15€</span>
                                    </td>
                                    <td>
                                        <span class="number-of-offers">42</span>
                                    </td>
                                    <td>
                                        <span class="offers-rate">100%</span>
                                    </td>
                                </tr>

                                <tr class="table-body">
                                    <td class="bid-number">
                                        <span class="order-rate">4 % <i class="icon-grey icon-simple-arrow"></i></span>
                                    </td>
                                    <td>
                                        <span class="total-amount">850,15€</span>
                                    </td>
                                    <td>
                                        <span class="number-of-offers">42</span>
                                    </td>
                                    <td>
                                        <span class="offers-rate">100%</span>
                                    </td>
                                </tr>

                            </table>

                            <div id="bottom-nav">
                              <a class="csv-extract">Export CSV</a>
                            </div>
                            <div class="displayAll"></div>
                            <script>



                                $("#triTaux").click(function () {
                                    $("#tri").html('rate');
                                });

                                $("#triAmount").click(function () {
                                    $("#tri").html('globalAmount');
                                });

                                $("#triOffers").click(function () {
                                    $("#tri").html('offers');
                                });

                                $("#triCurrentOffers").click(function () {
                                    $("#tri").html('currentOffers');
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
                        <div class="tab" id="presentation">
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
                        <div class="tab" id="infos">
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
                                <div class="csv-extract">
                                    <a href="<?= $this->lurl ?>/projects/csv/<?= $this->projects->id_project ?>"><img src="<?= $this->surl ?>/images/default/xls_hd.png" alt="<?= $this->lng['preteur-projets']['csv-extract-button'] ?>"/></a>
                                    <?= $this->lng['preteur-projets']['csv-extract-button'] ?>
                                </div>
                                <ul class="right">
                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                    <li>
                                        <div class="annee">
                                            <?= ucfirst(strftime('%d/%m/%Y', strtotime($this->lBilans[$i]['cloture_exercice_fiscal']))) ?><br/>
                                            <?= str_replace('[DURATION]', $this->lBilans[$i]['duree_exercice_fiscal'], $this->lng['preteur-projets']['annual-accounts-duration-months']) ?>
                                        </div>
                                    </li>
                                    <?php endfor; ?>
                                </ul>
                            </div>
                            <div class="statistic-table">
                                <table>
                                    <tr>
                                        <th colspan="4"><?= $this->lng['preteur-projets']['compte-de-resultats'] ?></th>
                                    </tr>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['chiffe-daffaires'] ?></td>
                                        <?php for ($i = 0; $i < 3; $i++): ?>
                                            <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['ca'], 0) ?>&nbsp;€</td>
                                        <?php endfor; ?>
                                    </tr>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['resultat-brut-dexploitation'] ?></td>
                                        <?php for ($i = 0; $i < 3; $i++): ?>
                                            <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['resultat_brute_exploitation'], 0) ?>&nbsp;€</td>
                                        <?php endfor; ?>
                                    </tr>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['resultat-dexploitation'] ?></td>
                                        <?php for ($i = 0; $i < 3; $i++): ?>
                                            <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['resultat_exploitation'], 0) ?>&nbsp;€</td>
                                        <?php endfor; ?>
                                    </tr>
                                    <?php if (false === $this->bPreviousRiskProject) : ?>
                                        <tr>
                                            <td class="intitule"><?= $this->lng['preteur-projets']['resultat-financier'] ?></td>
                                            <?php for ($i = 0; $i < 3; $i++): ?>
                                                <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['resultat_financier'], 0) ?>&nbsp;€</td>
                                            <?php endfor; ?>
                                        </tr>
                                        <tr>
                                            <td class="intitule"><?= $this->lng['preteur-projets']['produit-exceptionnel'] ?></td>
                                            <?php for ($i = 0; $i < 3; $i++): ?>
                                                <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['produit_exceptionnel'], 0) ?>&nbsp;€</td>
                                            <?php endfor; ?>
                                        </tr>
                                        <tr>
                                            <td class="intitule"><?= $this->lng['preteur-projets']['charges-exceptionnelles'] ?></td>
                                            <?php for ($i = 0; $i < 3; $i++): ?>
                                                <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['charges_exceptionnelles'], 0) ?>&nbsp;€</td>
                                            <?php endfor; ?>
                                        </tr>
                                        <tr>
                                            <td class="intitule"><?= $this->lng['preteur-projets']['resultat-exceptionnel'] ?></td>
                                            <?php for ($i = 0; $i < 3; $i++): ?>
                                                <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['resultat_exceptionnel'], 0) ?>&nbsp;€</td>
                                            <?php endfor; ?>
                                        </tr>
                                        <tr>
                                            <td class="intitule"><?= $this->lng['preteur-projets']['resultat-net'] ?></td>
                                            <?php for ($i = 0; $i < 3; $i++): ?>
                                                <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['resultat_net'], 0) ?>&nbsp;€</td>
                                            <?php endfor; ?>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['investissements'] ?></td>
                                        <?php for ($i = 0; $i < 3; $i++): ?>
                                            <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['investissements'], 0) ?>&nbsp;€</td>
                                        <?php endfor; ?>
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
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['immobilisations_corporelles'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['immobilisations-incorporelles'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['immobilisations_incorporelles'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['immobilisations-financieres'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['immobilisations_financieres'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['stocks'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['stocks'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['creances-clients'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['creances_clients'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['disponibilites'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['disponibilites'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['valeurs-mobilieres-de-placement'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['valeurs_mobilieres_de_placement'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <?php if (false === $this->bPreviousRiskProject && ($this->listAP[0]['comptes_regularisation_actif'] != 0 || $this->listAP[1]['comptes_regularisation_actif'] != 0 || $this->listAP[2]['comptes_regularisation_actif'] != 0)) : ?>
                                                    <tr>
                                                        <td class="intitule"><?= $this->lng['preteur-projets']['comptes-regularisation'] ?></td>
                                                        <?php for ($i = 0; $i < 3; $i++): ?>
                                                            <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['comptes_regularisation_actif'], 0) ?>&nbsp;€</td>
                                                        <?php endfor; ?>
                                                    </tr>
                                                <?php endif; ?>
                                                <tr class="total-row">
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['total-bilan-actifs'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->totalAnneeActif[$i], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
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
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['capitaux_propres'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['provisions-pour-risques-charges'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['provisions_pour_risques_et_charges'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['amortissement-sur-immo'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['amortissement_sur_immo'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['dettes-financieres'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['dettes_financieres'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['dettes-fournisseurs'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['dettes_fournisseurs'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['autres-dettes'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['autres_dettes'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <?php if (false === $this->bPreviousRiskProject && ($this->listAP[0]['comptes_regularisation_passif'] != 0 || $this->listAP[1]['comptes_regularisation_passif'] != 0 || $this->listAP[2]['comptes_regularisation_passif'] != 0)) : ?>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['comptes-regularisation'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['comptes_regularisation_passif'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <?php endif; ?>
                                                <tr class="total-row">
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['total-bilan-passifs'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->totalAnneePassif[$i], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
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
                                        <strong class="pinky-span"><?= $this->ficelle->formatNumber($this->bidsvalid['solde']) ?>
                                            &nbsp;€</strong>
                                    </p>
                                    <p>
                                        <strong class="pinky-span"><?= $this->ficelle->formatNumber($this->sumRemb) ?>
                                            &nbsp;€</strong>
                                        <?= $this->lng['preteur-projets']['vous-ont-ete-rembourses-il-vous-reste'] ?>
                                        <strong class="pinky-span"><?= $this->ficelle->formatNumber($this->sumRestanteARemb) ?>
                                            &nbsp;€</strong>
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
                    <a href="<?= $this->lurl ?>/thickbox/pop_up_offer_mobile/<?= $this->projects->id_project ?>" class="btn popup-link"><?= $this->lng['preteur-projets']['preter'] ?></a>
                </div>
            <?php elseif (false === $this->bIsConnected) : ?>
            <div class="single-project-actions">
                <a target="_parent" class="btn login-toggle" id="seconnecter" style="width:210px; display:block;margin:auto; float: none;"><?= $this->lng['preteur-projets']['se-connecter'] ?></a>
                <a href="<?= $this->lurl . '/' . $this->tree->getSlug(127, $this->language) ?>" target="_parent" class="btn sinscrire_cta" id="sinscrire" style=""><?= $this->lng['preteur-projets']['sinscrire'] ?></a>
            </div>
            <?php endif; ?>
            <?php if ($this->projects_status->status == \projects_status::EN_FUNDING) : ?>
                <article class="ex-article">
                    <h3>
                        <a href="#"><?= $this->lng['preteur-projets']['carnet-dordres'] ?></a><i class="icon-arrow-down up"></i>
                    </h3>
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
                    <h5>
                        <a href="#"><?= $this->lng['preteur-projets']['pourquoi-pouvez-vous-nous-faire-confiance'] ?></a>
                    </h5>
                    <div class="article-entry">
                        <p><?= $this->projects->means_repayment ?></p>
                    </div>
                </div>
            </article>
            <article class="ex-article">
                <h3><a href="#"><?= $this->lng['preteur-projets']['comptes'] ?></a><i class="icon-arrow-down up"></i>
                </h3>
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
                                        <?php for ($i = 0; $i < 3; $i++): ?>
                                            <th>
                                                <?= ucfirst(strftime('%b %Y', strtotime($this->lBilans[$i]['cloture_exercice_fiscal']))) ?><br/>
                                                <?= str_replace('[DURATION]', $this->lBilans[$i]['duree_exercice_fiscal'], $this->lng['preteur-projets']['annual-accounts-duration-months']) ?>
                                            </th>
                                        <?php endfor; ?>
                                    </tr>
                                    <tr>
                                        <th colspan="4" style="color:white;"><?= $this->lng['preteur-projets']['compte-de-resultats'] ?></th>
                                    </tr>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['chiffe-daffaires'] ?></td>
                                        <?php for ($i = 0; $i < 3; $i++): ?>
                                            <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['ca'], 0) ?>&nbsp;€</td>
                                        <?php endfor; ?>
                                    </tr>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['resultat-brut-dexploitation'] ?></td>
                                        <?php for ($i = 0; $i < 3; $i++): ?>
                                            <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['resultat_brute_exploitation'], 0) ?>&nbsp;€</td>
                                        <?php endfor; ?>
                                    </tr>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['resultat-dexploitation'] ?></td>
                                        <?php for ($i = 0; $i < 3; $i++): ?>
                                            <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['resultat_exploitation'], 0) ?>&nbsp;€</td>
                                        <?php endfor; ?>
                                    </tr>
                                    <?php if (false === $this->bPreviousRiskProject) : ?>
                                        <tr>
                                            <td class="intitule"><?= $this->lng['preteur-projets']['resultat-financier'] ?></td>
                                            <?php for ($i = 0; $i < 3; $i++): ?>
                                                <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['resultat_financier'], 0) ?>&nbsp;€</td>
                                            <?php endfor; ?>
                                        </tr>
                                        <tr>
                                            <td class="intitule"><?= $this->lng['preteur-projets']['produit-exceptionnel'] ?></td>
                                            <?php for ($i = 0; $i < 3; $i++): ?>
                                                <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['produit_exceptionnel'], 0) ?>&nbsp;€</td>
                                            <?php endfor; ?>
                                        </tr>
                                        <tr>
                                            <td class="intitule"><?= $this->lng['preteur-projets']['charges-exceptionnelles'] ?></td>
                                            <?php for ($i = 0; $i < 3; $i++): ?>
                                                <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['charges_exceptionnelles'], 0) ?>&nbsp;€</td>
                                            <?php endfor; ?>
                                        </tr>
                                        <tr>
                                            <td class="intitule"><?= $this->lng['preteur-projets']['resultat-exceptionnel'] ?></td>
                                            <?php for ($i = 0; $i < 3; $i++): ?>
                                                <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['resultat_exceptionnel'], 0) ?>&nbsp;€</td>
                                            <?php endfor; ?>
                                        </tr>
                                        <tr>
                                            <td class="intitule"><?= $this->lng['preteur-projets']['resultat-net'] ?></td>
                                            <?php for ($i = 0; $i < 3; $i++): ?>
                                                <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['resultat_net'], 0) ?>&nbsp;€</td>
                                            <?php endfor; ?>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td class="intitule"><?= $this->lng['preteur-projets']['investissements'] ?></td>
                                        <?php for ($i = 0; $i < 3; $i++): ?>
                                            <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->lBilans[$i]['investissements'], 0) ?>&nbsp;€</td>
                                        <?php endfor; ?>
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
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['immobilisations_corporelles'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['immobilisations-incorporelles'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['immobilisations_incorporelles'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['immobilisations-financieres'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['immobilisations_financieres'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['stocks'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['stocks'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['creances-clients'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['creances_clients'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['disponibilites'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['disponibilites'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['valeurs-mobilieres-de-placement'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['valeurs_mobilieres_de_placement'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <?php if (false === $this->bPreviousRiskProject && ($this->listAP[0]['comptes_regularisation_actif'] != 0 || $this->listAP[1]['comptes_regularisation_actif'] != 0 || $this->listAP[2]['comptes_regularisation_actif'] != 0)) : ?>
                                                    <tr>
                                                        <td class="intitule"><?= $this->lng['preteur-projets']['comptes-regularisation'] ?></td>
                                                        <?php for ($i = 0; $i < 3; $i++): ?>
                                                            <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['comptes_regularisation_actif'], 0) ?>&nbsp;€</td>
                                                        <?php endfor; ?>
                                                    </tr>
                                                <?php endif; ?>
                                                <tr class="total-row">
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['total-bilan-actifs'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->totalAnneeActif[$i], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
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
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['capitaux_propres'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['provisions-pour-risques-charges'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['provisions_pour_risques_et_charges'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['amortissement-sur-immo'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['amortissement_sur_immo'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['dettes-financieres'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['dettes_financieres'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['dettes-fournisseurs'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['dettes_fournisseurs'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <tr>
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['autres-dettes'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['autres_dettes'], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                                <?php if (false === $this->bPreviousRiskProject && ($this->listAP[0]['comptes_regularisation_passif'] != 0 || $this->listAP[1]['comptes_regularisation_passif'] != 0 || $this->listAP[2]['comptes_regularisation_passif'] != 0)) : ?>
                                                    <tr>
                                                        <td class="intitule"><?= $this->lng['preteur-projets']['comptes-regularisation'] ?></td>
                                                        <?php for ($i = 0; $i < 3; $i++): ?>
                                                            <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->listAP[$i]['comptes_regularisation_passif'], 0) ?>&nbsp;€</td>
                                                        <?php endfor; ?>
                                                    </tr>
                                                <?php endif; ?>
                                                <tr class="total-row">
                                                    <td class="intitule"><?= $this->lng['preteur-projets']['total-bilan-passifs'] ?></td>
                                                    <?php for ($i = 0; $i < 3; $i++): ?>
                                                        <td class="sameSize" style="text-align:right;"><?= $this->ficelle->formatNumber($this->totalAnneePassif[$i], 0) ?>&nbsp;€</td>
                                                    <?php endfor; ?>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="csv-extract">
                                <a href="<?= $this->lurl ?>/projects/csv/<?= $this->projects->id_project ?>">
                                    <img src="<?= $this->surl ?>/images/default/xls_hd.png" alt="<?= $this->lng['preteur-projets']['csv-extract-button'] ?>"/>
                                    <?= $this->lng['preteur-projets']['csv-extract-button'] ?>
                                </a>
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
        var montant = $("#montant_p").val(),
            tx = $("#tx_p").val(),
            form_ok = true;

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
        var montant = $("#montant_p").val(),
            tx = $("#tx_p").val(),
            form_ok = true;

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
