<?
if ($this->lurl == 'http://prets-entreprises-unilend.capital.fr' || $this->lurl == 'http://partenaire.unilend.challenges.fr' || $this->lurl == 'http://figaro.unilend.fr' || $this->lurl == 'http://financementparticipatifpme.lefigaro.fr'
) {
    ?>
    <style type="text/css">
        #form_tri{display:none;}
        .section-c h2 {margin-bottom:5px;}
    </style>
    <?
}
?>


<!--#include virtual="ssi-header-login.shtml"  -->
<div class="main">
    <div class="shell">
        <div class="section-c section-c-desktop">
            <h2><?= $this->lng['preteur-projets']['decouvrez-les'] ?> <?= $this->nbProjects ?> <?= $this->lng['preteur-projets']['projets-en-cours'] ?></h2>
            <p><?= $this->content['contenu-180'] ?></p>

            <form action="" method="post" id="form_tri" name="form_tri">
                <div class="row clearfix">
                    <select name="temps" id="temps" class="custom-select field-almost-small">
                        <option value="0"><?= $this->lng['preteur-projets']['tri-par-temps-restant'] ?></option>
                        <option value="1"><?= $this->lng['preteur-projets']['se-termine-bientot'] ?></option>
                        <option value="2"><?= $this->lng['preteur-projets']['nouveaute'] ?></option>
                    </select>
                    <select name="taux" id="taux" class="custom-select field-almost-small">
                        <option value=""><?= $this->lng['preteur-projets']['tri-par-taux'] ?></option>
                        <?
                        foreach ($this->triPartx as $k => $tx) {
                            ?><option value="<?= $k + 1 ?>"><?= $tx ?></option><?
                        }
                        ?>
                    </select>
                    <select name="type" id="type" class="custom-select field-almost-small">
                        <option value="0"><?= $this->lng['preteur-projets']['tri-par-type-de-projet'] ?></option>
                        <option value="1"><?= $this->lng['preteur-projets']['tous-les-projets'] ?></option>
                        <?php /* ?><option value="2"><?=$this->lng['preteur-projets']['projets-suivis']?></option>
                          <option value="3"><?=$this->lng['preteur-projets']['projets-bide']?></option><?php */ ?>
                        <option value="4"><?= $this->lng['preteur-projets']['projets-termines'] ?></option>
                    </select>

                    <button style="margin-left:10px;margin-top:7px;overflow:visible;" class="btn btn-pinky btn-small multi" type="reset" name="rest" id="rest" ><?= $this->lng['preteur-projets']['reset'] ?></button> 
                </div>

            </form>
            <style>
                .unProjet td a.lien{color:#727272;text-decoration:none;}
            </style>
            <div id="table_tri">
                <table class="table" >
                    <tr>
                        <th width="350">
                    <div class="th-wrap"><i title="<?= $this->lng['preteur-projets']['info-nom-projet'] ?>" class="icon-person tooltip-anchor"></i></div>
                    </th>
                    <th width="90">
                    <div class="th-wrap"><i title="<?= $this->lng['preteur-projets']['info-capacite-remboursement'] ?>" class="icon-gauge tooltip-anchor"></i></div>
                    </th>
                    <th width="90">
                    <div class="th-wrap"><i title="<?= $this->lng['preteur-projets']['info-montant'] ?>" class="icon-bank tooltip-anchor"></i></div>
                    </th>
                    <th width="60">
                    <div class="th-wrap"><i title="<?= $this->lng['preteur-projets']['info-duree'] ?>" class="icon-calendar tooltip-anchor"></i></div>
                    </th>
                    <th width="60">
                    <div class="th-wrap"><i title="<?= $this->lng['preteur-projets']['info-tx-cible'] ?>" class="icon-graph tooltip-anchor"></i></div>
                    </th>
                    <th width="110">
                    <div class="th-wrap"><i title="<?= $this->lng['preteur-projets']['info-temps-restant'] ?>" class="icon-clock tooltip-anchor"></i></div>
                    </th>
                    <th width="120">
                    <div class="th-wrap"><i title="<?= $this->lng['preteur-projets']['info-cta'] ?>" class="icon-arrow-next tooltip-anchor"></i></div>
                    </th>
                    </tr>
                    <?
                    $this->loans = $this->loadData('loans');
                    foreach ($this->lProjetsFunding as $pf) {
                        $this->projects_status->getLastStatut($pf['id_project']);

                        // On recupere les info companies
                        $this->companies->get($pf['id_company'], 'id_company');
                        $this->companies_details->get($pf['id_company'], 'id_company');

                        // date fin 21h a chaque fois
                        $inter = $this->dates->intervalDates(date('Y-m-d h:i:s'), $pf['date_retrait_full']);

                        if ($inter['mois'] > 0)
                            $dateRest = $inter['mois'] . ' ' . $this->lng['preteur-projets']['mois'];
                        else
                            $dateRest = '';

                        // dates pour le js
                        $mois_jour = $this->dates->formatDate($pf['date_retrait'], 'F d');
                        $annee = $this->dates->formatDate($pf['date_retrait'], 'Y');

                        $CountEnchere = $this->bids->counter('id_project = ' . $pf['id_project']);
                        //$avgRate = $this->bids->getAVG($pf['id_project'],'rate');
                        // moyenne pondéré
                        $montantHaut = 0;
                        $montantBas = 0;
                        // si fundé ou remboursement
                        if ($this->projects_status->status == 60 || $this->projects_status->status >= 80) {
                            foreach ($this->loans->select('id_project = ' . $pf['id_project']) as $b) {
                                $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                                $montantBas += ($b['amount'] / 100);
                            }
                        }
                        // funding ko
                        elseif ($this->projects_status->status == 70) {
                            foreach ($this->bids->select('id_project = ' . $pf['id_project']) as $b) {
                                $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                                $montantBas += ($b['amount'] / 100);
                            }
                        }
                        // emprun refusé
                        elseif ($this->projects_status->status == 75) {
                            foreach ($this->bids->select('id_project = ' . $pf['id_project'] . ' AND status = 1') as $b) {
                                $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                                $montantBas += ($b['amount'] / 100);
                            }
                        } else {
                            foreach ($this->bids->select('id_project = ' . $pf['id_project'] . ' AND status = 0') as $b) {
                                $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                                $montantBas += ($b['amount'] / 100);
                            }
                        }

                        if ($montantHaut > 0 && $montantBas > 0)
                            $avgRate = ($montantHaut / $montantBas);
                        else
                            $avgRate = 0;

                        // favori
                        if ($this->favoris->get($this->clients->id_client, 'id_project = ' . $pf['id_project'] . ' AND id_client'))
                            $favori = 'active';
                        else
                            $favori = '';
                        ?>

                        <tr class="unProjet" id="project<?= $pf['id_project'] ?>">
                            <td>
                                <?
                                if ($this->projects_status->status >= 60) {
                                    $dateRest = $this->lng['preteur-projets']['termine'];
                                } else {
                                    $tab_date_retrait = explode(' ', $pf['date_retrait_full']);
                                    $tab_date_retrait = explode(':', $tab_date_retrait[1]);
                                    $heure_retrait = $tab_date_retrait[0] . ':' . $tab_date_retrait[1];
                                    ?>
                                    <script>
                                        var cible<?= $pf['id_project'] ?> = new Date('<?= $mois_jour ?>, <?= $annee ?> <?= $heure_retrait ?>:00');
                                        var letime<?= $pf['id_project'] ?> = parseInt(cible<?= $pf['id_project'] ?>.getTime() / 1000, 10);
                                        setTimeout('decompte(letime<?= $pf['id_project'] ?>,"val<?= $pf['id_project'] ?>")', 500);
                                    </script>
                                    <?
                                }

                                if ($pf['photo_projet'] != '') {
                                    ?><a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $pf['slug'] ?>"><img src="<?= $this->photos->display($pf['photo_projet'], 'photos_projets', 'photo_projet_min') ?>" alt="<?= $pf['photo_projet'] ?>" class="thumb"></a><?
                                }
                                ?>
                                <div class="description">
                                    <h5><a href="<?= $this->lurl ?>/projects/detail/<?= $pf['slug'] ?>"><?= $pf['title'] ?></a></h5>
                                    <h6><?= $this->companies->city . ($this->companies->zip != '' ? ', ' : '') . $this->companies->zip ?></h6>
                                    <p><?= $pf['nature_project'] ?></p>
                                </div><!-- /.description -->
                            </td>
                            <td>
                                <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $pf['slug'] ?>">
                                    <div class="cadreEtoiles"><div class="etoile <?= $this->lNotes[$pf['risk']] ?>"></div></div>
                                </a>
                            </td>
                            <td style="white-space:nowrap;">
                                <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $pf['slug'] ?>">
                                    <?= number_format($pf['amount'], 0, ',', ' ') ?>€
                                </a>
                            </td>
                            <td style="white-space:nowrap;">
                                <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $pf['slug'] ?>">
                                    <?= ($pf['period'] == 1000000 ? $this->lng['preteur-projets']['je-ne-sais-pas'] : $pf['period'] . ' ' . $this->lng['preteur-projets']['mois']) ?>
                                </a>
                            </td>
                            <td>
                                <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $pf['slug'] ?>">
                                    <?
                                    if ($CountEnchere > 0) {
                                        ?><?= number_format($avgRate, 1, ',', ' ') ?>%<?
                                    } else {
                                        ?><?= ($pf['target_rate'] == '-' ? $pf['target_rate'] : number_format($pf['target_rate'], 1, ',', ' %')) ?><?
                                    }
                                    ?>
                                </a>
                            </td>
                            <td>
                                <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $pf['slug'] ?>">
                                    <strong id="val<?= $pf['id_project'] ?>"><?= $dateRest ?></strong>
                                </a>
                            </td>
                            <td>
                                <?
                                if ($this->projects_status->status >= 60) {
                                    ?><a href="<?= $this->lurl ?>/projects/detail/<?= $pf['slug'] ?>" class="btn btn-info btn-small multi  grise1 btn-grise"><?= $this->lng['preteur-projets']['voir-le-projet'] ?></a><?
                                } else {
                                    ?><a href="<?= $this->lurl ?>/projects/detail/<?= $pf['slug'] ?>" class="btn btn-info btn-small"><?= $this->lng['preteur-projets']['pretez'] ?></a><?
                                }

                                if (isset($_SESSION['client'])) {
                                    ?>
                                    <a class="fav-btn <?= $favori ?>" id="fav<?= $pf['id_project'] ?>" onclick="favori(<?= $pf['id_project'] ?>, 'fav<?= $pf['id_project'] ?>',<?= $this->clients->id_client ?>, 0);"><?= $this->lng['preteur-projets']['favori'] ?> <i></i></a>
                                    <?
                                }
                                ?>
                            </td>
                        </tr>
                        <?
                    }
                    ?>

                </table><!-- /.table -->
                <div id="positionStart" style="display:none;"><?= $this->lProjetsFunding[0]['positionStart'] ?></div>
                <div class="loadmore" style="display:none;">
                    <?= $this->lng['preteur-projets']['chargement-en-cours'] ?>
                </div>
                <div class="nbProjet" style="display:none;"><?= $this->nbProjects ?></div>
                <div id="ordreProject" style="display:none;"><?= $this->ordreProject ?></div>
                <div id="where" style="display:none;"><?= $this->where ?></div>
                <div id="valType" style="display:none;"><?= $this->type ?></div>

            </div>
        </div>
        
        <div class="section-projects-mobile">
            <h3 class="section-projects-mobile-title">Liste des projets</h3>

            <?
            foreach ($this->lProjetsFunding as $pf) {
                $this->projects_status->getLastStatut($pf['id_project']);

                // On recupere les info companies
                $this->companies->get($pf['id_company'], 'id_company');
                $this->companies_details->get($pf['id_company'], 'id_company');

                // date fin 21h a chaque fois
                $inter = $this->dates->intervalDates(date('Y-m-d h:i:s'), $pf['date_retrait_full']);
                if ($inter['mois'] > 0)
                    $dateRest = $inter['mois'] . ' ' . $this->lng['preteur-projets']['mois'];
                else
                    $dateRest = '';

                $CountEnchere = $this->bids->counter('id_project = ' . $pf['id_project']);
                //$avgRate = $this->bids->getAVG($pf['id_project'],'rate');
                // moyenne pondéré
                $montantHaut = 0;
                $montantBas = 0;
                // si fundé ou remboursement
                if ($this->projects_status->status == 60 || $this->projects_status->status >= 80) {
                    foreach ($this->loans->select('id_project = ' . $pf['id_project']) as $b) {
                        $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                        $montantBas += ($b['amount'] / 100);
                    }
                }
                // funding ko
                elseif ($this->projects_status->status == 70) {
                    foreach ($this->bids->select('id_project = ' . $pf['id_project']) as $b) {
                        $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                        $montantBas += ($b['amount'] / 100);
                    }
                }
                // emprun refusé
                elseif ($this->projects_status->status == 75) {
                    foreach ($this->bids->select('id_project = ' . $pf['id_project'] . ' AND status = 1') as $b) {
                        $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                        $montantBas += ($b['amount'] / 100);
                    }
                } else {
                    foreach ($this->bids->select('id_project = ' . $pf['id_project'] . ' AND status = 0') as $b) {
                        $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                        $montantBas += ($b['amount'] / 100);
                    }
                }
                if ($montantHaut > 0 && $montantBas > 0)
                    $avgRate = ($montantHaut / $montantBas);
                else
                    $avgRate = 0;

                // dates pour le js
                $mois_jour = $this->dates->formatDate($pf['date_retrait'], 'F d');
                $annee = $this->dates->formatDate($pf['date_retrait'], 'Y');

                // favori
                if ($this->favoris->get($this->clients->id_client, 'id_project = ' . $pf['id_project'] . ' AND id_client')) {
                    $favori = 'active';
                } else {
                    $favori = '';
                }

                if ($this->projects_status->status >= 60) {
                    $dateRest = $this->lng['preteur-projets']['termine'];
                } else {
                    $heure_retrait = date('H:i', strtotime($pf['date_retrait_full']));
                    ?>
                    <script type="text/javascript">
                        var cible<?= $pf['id_project'] ?> = new Date('<?= $mois_jour ?>, <?= $annee ?> <?= $heure_retrait ?>:00');
                        var letime<?= $pf['id_project'] ?> = parseInt(cible<?= $pf['id_project'] ?>.getTime() / 1000, 10);
                        setTimeout('decompte(letime<?= $pf['id_project'] ?>,"min_val<?= $pf['id_project'] ?>")', 500);
                    </script>
                    <?
                }
                ?>

                <div class="project-mobile">
                    <div class="project-mobile-image">
                        <img src="<?= $this->photos->display($pf['photo_projet'], 'photos_projets', 'photo_projet_moy') ?>" alt="" />

                        <div class="project-mobile-image-caption">
                            <p><?= number_format($pf['amount'], 0, ',', ' ') ?>€ | 
                                <span class="cadreEtoiles" style="margin-right: 12px; top: 8px;display: inline-block;">
                                    <span style="display: inline-block;" class="etoile <?= $this->lNotes[$pf['risk']] ?>"></span>
                                </span> | 
                                <?
                                if ($CountEnchere > 0) {
                                    ?><?= number_format($avgRate, 1, ',', ' ') ?>%<?
                                } else {
                                    ?><?= ($pf['target_rate'] == '-' ? $pf['target_rate'] : number_format($pf['target_rate'], 1, ',', ' %')) ?><?
                                }
                                ?> 
                                | <?= ($pf['period'] == 1000000 ? $this->lng['preteur-projets']['je-ne-sais-pas'] : $pf['period'] . ' ' . $this->lng['preteur-projets']['mois']) ?></p>
                        </div>
                    </div>

                    <div class="project-mobile-content">
                        <h3><?= $pf['title'] ?></h3>

                        <h4><?= $this->companies->city . ($this->companies->zip != '' ? ', ' : '') . $this->companies->zip ?></h4>

                        <h5>
                            <i class="ico-clock"></i>

                            <strong id="min_val<?= $pf['id_project'] ?>"><?= $dateRest ?></strong>
                        </h5>

                        <p>
                            <?
                            if ($this->projects_status->status >= 60) {
                                ?>
                                <a href="<?= $this->lurl ?>/projects/detail/<?= $pf['slug'] ?>" class="btn btn-info btn-small multi  grise1 btn-grise" style="line-height: 14px;padding: 4px 11px;"><?= $this->lng['preteur-projets']['voir-le-projet'] ?></a>
                                <?
                            } else {
                                ?><a href="<?= $this->lurl ?>/projects/detail/<?= $pf['slug'] ?>" class="btn"><?= $this->lng['preteur-projets']['pretez'] ?></a><?
                            }
                            ?>
                            <?= $pf['nature_project'] ?>
                        </p>
                    </div><!-- /.project-mobile-content -->
                </div><!-- /.project-mobile -->

                <?php
            }
            ?>
        </div><!-- /.section-projects-mobile -->
        
    </div>

</div>

<!--#include virtual="ssi-footer.shtml"  -->


<script type="text/javascript">

    $(document).ready(function () {


        var load = false;
        var offset = $('.unProjet:last').offset();

        $(window).scroll(function () { // On surveille l'évènement scroll

            /* Si l'élément offset est en bas de scroll, si aucun chargement 
             n'est en cours, si le nombre de projet affiché est supérieur 
             à 5 et si tout les projets ne sont pas affichés, alors on 
             lance la fonction. */
            if ((offset.top - $(window).height() <= $(window).scrollTop())
                    && load == false && ($('.unProjet').size() >= 10) &&
                    ($('.unProjet').size() != $('.nbProjet').text())) {

                // la valeur passe à vrai, on va charger
                load = true;

                //On récupère l'id du dernier projet affiché
                var last_id = $('.unProjet:last').attr('id');

                //On affiche un loader
                $('.loadmore').show();

                //On lance la fonction ajax
                var val = {last: last_id, positionStart: $('#positionStart').html(), ordreProject: $('#ordreProject').html(), where: $('#where').html(), type: $('#valType').html()}
                $.post(add_url + '/ajax/load_project', val).done(function (data) {

                    obj = JSON.parse(data);
                    var positionStart = obj.positionStart;
                    var affichage = obj.affichage;

                    //On masque le loader
                    $('.loadmore').fadeOut(500);
                    /* On affiche le résultat après
                     le dernier projet */
                    $('.unProjet:last').after(affichage);
                    /* On actualise la valeur offset
                     du dernier projet */
                    offset = $('.unProjet:last').offset();
                    //On remet la valeur à faux car c'est fini
                    load = false;

                    $('#positionStart').html(positionStart);
                });
            }
        });

    });


    $("select").change(function () {
        var val = $(this).val();
        var id = $(this).attr('id');

        $.post(add_url + '/ajax/triProject', {val: val, id: id}).done(function (data) {
            $('#table_tri').html(data)
        });
    })

    $("#rest").click(function () {
        $.post(add_url + '/ajax/triProject', {rest_val: 1}).done(function (data) {
            $('#table_tri').html(data);
        });
    })



</script>

