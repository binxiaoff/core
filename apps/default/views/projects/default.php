<div class="main">
    <div class="shell">
        <div class="section-c section-c-desktop">
            <h2><?= str_replace('[#NB_PROJECTS#]', $this->nbProjects, $this->lng['preteur-projets']['decouvrez-les']) ?> <?= $this->nbProjects ?> <?= $this->lng['preteur-projets']['projets-en-cours'] ?></h2>
            <p><?= $this->lng['preteur-projets']['contenu'] ?></p>
            <p><?= str_replace('[#BALANCE#]', $this->ficelle->formatNumber($this->solde), $this->lng['preteur-projets']['vous-avez-actuellement']) ?> <strong class="green-span"><?= $this->ficelle->formatNumber($this->solde) ?> €</strong> <?= $this->lng['preteur-projets']['de-disponible-sur-votre-compte-unilend'] ?>.</p>
            <form action="" method="post" id="form_tri" name="form_tri">
                <div class="row clearfix">
                    <select name="temps" id="temps" class="custom-select field-almost-small">
                        <option value="0"><?= $this->lng['preteur-projets']['tri-par-temps-restant'] ?></option>
                        <option value="1"><?= $this->lng['preteur-projets']['se-termine-bientot'] ?></option>
                        <option value="2"><?= $this->lng['preteur-projets']['nouveaute'] ?></option>
                    </select>
                    <select name="taux" id="taux" class="custom-select field-almost-small">
                        <option value=""><?= $this->lng['preteur-projets']['tri-par-taux'] ?></option>
                        <?php foreach ($this->triPartx as $k => $tx) : ?>
                            <option value="<?= $k + 1 ?>"><?= $tx ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="type" id="type" class="custom-select field-almost-small">
                        <option value="0"><?= $this->lng['preteur-projets']['tri-par-type-de-projet'] ?></option>
                        <option value="1"><?= $this->lng['preteur-projets']['tous-les-projets'] ?></option>
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
                    foreach ($this->lProjetsFunding as $project) {
                        $this->projects_status->getLastStatut($project['id_project']);
                        $this->companies->get($project['id_company'], 'id_company');

                        $inter = $this->dates->intervalDates(date('Y-m-d h:i:s'), $project['date_retrait_full']);
                        if ($inter['mois'] > 0)
                            $dateRest = $inter['mois'] . ' ' . $this->lng['preteur-projets']['mois'];
                        else
                            $dateRest = 'Terminé';

                        $iSumbids = $this->bids->counter('id_project = ' . $project['id_project']);
                        $avgRate  = $this->projects->getAverageInterestRate($project['id_project'], $this->projects_status->status);

                        // dates pour le js
                        $mois_jour = $this->dates->formatDate($project['date_retrait'], 'F d');
                        $annee = $this->dates->formatDate($project['date_retrait'], 'Y'); ?>

                        <tr class="unProjet" id="project<?= $project['id_project'] ?>">
                            <td>
                                <?
                                if ($this->projects_status->status >= \projects_status::FUNDE) {
                                    $dateRest = $this->lng['preteur-projets']['termine'];
                                } else {
                                    $tab_date_retrait = explode(' ', $project['date_retrait_full']);
                                    $tab_date_retrait = explode(':', $tab_date_retrait[1]);
                                    $heure_retrait = $tab_date_retrait[0] . ':' . $tab_date_retrait[1];
                                    ?>
                                    <script>
                                        var cible<?= $project['id_project'] ?> = new Date('<?= $mois_jour ?>, <?= $annee ?> <?= $heure_retrait ?>:00');
                                        var letime<?= $project['id_project'] ?> = parseInt(cible<?= $project['id_project'] ?>.getTime() / 1000, 10);
                                        setTimeout('decompte(letime<?= $project['id_project'] ?>,"val<?= $project['id_project'] ?>")', 500);
                                    </script>
                                    <?
                                }
                                if ($project['photo_projet'] != '') {
                                    ?><a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>"><img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $project['photo_projet'] ?>" alt="<?= $project['photo_projet'] ?>" class="thumb"></a><?
                                }
                                ?>
                                <div class="description">
                                    <h5><a href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>"><?= $project['title'] ?></a></h5>
                                    <h6><?= $this->companies->city . ($this->companies->zip != '' ? ', ' : '') . $this->companies->zip ?></h6>
                                    <p><?= $project['nature_project'] ?></p>
                                </div><!-- /.description -->
                            </td>
                            <td>
                                <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>">
                                    <div class="cadreEtoiles"><div class="etoile <?= $this->lNotes[$project['risk']] ?>"></div></div>
                                </a>
                            </td>
                            <td style="white-space:nowrap;">
                                <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>">
                                    <?= $this->ficelle->formatNumber($project['amount'], 0) ?>€
                                </a>
                            </td>
                            <td style="white-space:nowrap;">
                                <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>">
                                    <?= ($project['period'] == 1000000 ? $this->lng['preteur-projets']['je-ne-sais-pas'] : $project['period'] . ' ' . $this->lng['preteur-projets']['mois']) ?>
                                </a>
                            </td>
                            <td>
                                <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>">
                                    <?
                                    if ($iSumbids > 0) {
                                        ?><?= $this->ficelle->formatNumber($avgRate, 1) ?>%<?
                                    } else {
                                        ?><?= ($project['target_rate'] == '-' ? $project['target_rate'] : $this->ficelle->formatNumber($project['target_rate'], 1)) ?>%<?
                                    }
                                    ?>
                                </a>
                            </td>
                            <td>
                                <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>">
                                    <strong id="val<?= $project['id_project'] ?>"><?= $dateRest ?></strong>
                                </a>
                            </td>
                            <td>
                                <?
                                if ($this->projects_status->status >= \projects_status::FUNDE) {
                                    ?>
                                    <a href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>" class="btn btn-info btn-small multi  grise1 btn-grise"><?= $this->lng['preteur-projets']['voir-le-projet'] ?></a>
                                    <?
                                } else {
                                    ?><a href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>" class="btn btn-info btn-small">PRÊTez</a><?
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
                <div id="where" style="display:none;"><?= empty($this->where) ?: 1 ?></div>
                <div id="valType" style="display:none;"><?= $this->type ?></div>
            </div>
        </div>
        <div class="section-projects-mobile">
            <h3 class="section-projects-mobile-title">Liste des projets</h3>
            <?php foreach ($this->lProjetsFunding as $project) :
                $this->projects_status->getLastStatut($project['id_project']);
                $this->companies->get($project['id_company'], 'id_company');
                $this->companies_details->get($project['id_company'], 'id_company');

                // date fin 21h a chaque fois
                $inter = $this->dates->intervalDates(date('Y-m-d h:i:s'), $project['date_retrait_full']);
                if ($inter['mois'] > 0)
                    $dateRest = $inter['mois'] . ' ' . $this->lng['preteur-projets']['mois'];
                else
                    $dateRest = '';

                $iSumbids = $this->bids->counter('id_project = ' . $project['id_project']);
                $avgRate  = $this->projects->getAverageInterestRate($project['id_project'], $this->projects_status->status);

                // dates pour le js
                $mois_jour = $this->dates->formatDate($project['date_retrait'], 'F d');
                $annee = $this->dates->formatDate($project['date_retrait'], 'Y');

            if ($this->projects_status->status < \projects_status::FUNDE) {

                $heure_retrait = date('H:i', strtotime($project['date_retrait_full']));
                ?>
                <script type="text/javascript">
                    var cible<?= $project['id_project'] ?> = new Date('<?= $mois_jour ?>, <?= $annee ?> <?= $heure_retrait ?>:00');
                    var letime<?= $project['id_project'] ?> = parseInt(cible<?= $project['id_project'] ?>.getTime() / 1000, 10);
                    setTimeout('decompte(letime<?= $project['id_project'] ?>,"min_val<?= $project['id_project'] ?>")', 500);
                </script>
            <?
            } else {
                $dateRest = $this->lng['preteur-projets']['termine'];
            }
            ?>
                <div class="project-mobile">
                    <div class="project-mobile-image">
                        <img src="<?= $this->surl ?>/images/dyn/projets/169/<?= $project['photo_projet'] ?>" alt="<?= $project['photo_projet'] ?>">
                        <div class="project-mobile-image-caption">
                            <p><?= $this->ficelle->formatNumber($project['amount'], 0) ?>€ |
                                <span class="cadreEtoiles" style="margin-right: 12px; top: 8px;display: inline-block;">
                                    <span style="display: inline-block;" class="etoile <?= $this->lNotes[$project['risk']] ?>"></span>
                                </span> |
                                <?
                                if ($iSumbids > 0) {
                                    ?><?= $this->ficelle->formatNumber($avgRate, 1) ?>%<?
                                } else {
                                    ?><?= ($project['target_rate'] == '-' ? $project['target_rate'] : number_format($project['target_rate'], 1, ',', ' %')) ?><?
                                }
                                ?>
                                | <?= ($project['period'] == 1000000 ? $this->lng['preteur-projets']['je-ne-sais-pas'] : $project['period'] . ' ' . $this->lng['preteur-projets']['mois']) ?></p>
                        </div>
                    </div>
                    <div class="project-mobile-content">
                        <h3><?= $project['title'] ?></h3>
                        <h4><?= $this->companies->city . ($this->companies->zip != '' ? ', ' : '') . $this->companies->zip ?></h4>
                        <h5>
                            <i class="ico-clock"></i>
                            <strong id="min_val<?= $project['id_project'] ?>"><?= $dateRest ?></strong>
                        </h5>
                        <p>
                            <?
                            if ($this->projects_status->status >= \projects_status::FUNDE) {
                                ?>
                                <a href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>" class="btn btn-info btn-small multi  grise1 btn-grise" style="line-height: 14px;padding: 4px 11px;"><?= $this->lng['preteur-projets']['voir-le-projet'] ?></a>
                                <?
                            } else {
                                ?><a href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>" class="btn"><?= $this->lng['preteur-projets']['pretez'] ?></a><?
                            }
                            ?>
                            <?= $project['nature_project'] ?>
                        </p>
                    </div><!-- /.project-mobile-content -->
                </div><!-- /.project-mobile -->
                <?php endforeach; ?>
        </div><!-- /.section-projects-mobile -->
    </div>
</div><!--#include virtual="ssi-footer.shtml"  -->

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
                var val = {last: last_id, positionStart: $('#positionStart').html(), ordreProject: $('#ordreProject').html(), where: $('#where').html(), type: $('#valType').html()};
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
    });

    $("#rest").click(function () {
        $.post(add_url + '/ajax/triProject', {rest_val: 1}).done(function (data) {
            $('#table_tri').html(data);
        });
    })

</script>