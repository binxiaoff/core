<table class="table" id="table_tri">
    <tr>
        <th width="350">
            <div class="th-wrap">
                <i title="<?= $this->lng['preteur-projets']['info-nom-projet'] ?>" class="icon-person tooltip-anchor"></i>
            </div>
        </th>
        <th width="90">
            <div class="th-wrap">
                <i title="<?= $this->lng['preteur-projets']['info-capacite-remboursement'] ?>" class="icon-gauge tooltip-anchor"></i>
            </div>
        </th>
        <th width="90">
            <div class="th-wrap">
                <i title="<?= $this->lng['preteur-projets']['info-montant'] ?>" class="icon-bank tooltip-anchor"></i>
            </div>
        </th>
        <th width="60">
            <div class="th-wrap">
                <i title="<?= $this->lng['preteur-projets']['info-duree'] ?>" class="icon-calendar tooltip-anchor"></i>
            </div>
        </th>
        <th width="60">
            <div class="th-wrap">
                <i title="<?= $this->lng['preteur-projets']['info-tx-cible'] ?>" class="icon-graph tooltip-anchor"></i>
            </div>
        </th>
        <th width="110">
            <div class="th-wrap">
                <i title="<?= $this->lng['preteur-projets']['info-temps-restant'] ?>" class="icon-clock tooltip-anchor"></i>
            </div>
        </th>
        <th width="120">
            <div class="th-wrap">
                <i title="<?= $this->lng['preteur-projets']['info-cta'] ?>" class="icon-arrow-next tooltip-anchor"></i>
            </div>
        </th>
    </tr>
    <?php
    foreach ($this->lProjetsFunding as $project) : ?>
        <tr class="unProjet" id="project<?= $project['id_project'] ?>">
            <td>
                <?php
                if ($this->projects_status->status >= \projects_status::FUNDE) {
                    $project['daterest'] = 'Terminé';
                } else {
                    $tab_date_retrait = explode(' ', $project['date_retrait_full']);
                    $tab_date_retrait = explode(':', $tab_date_retrait[1]);
                    $heure_retrait    = $tab_date_retrait[0] . ':' . $tab_date_retrait[1];

                    ?>
                    <script>
                        var cible<?= $project['id_project'] ?> = new Date('<?= $this->dates->formatDate($project['date_retrait'], 'F d'); ?>, <?= $this->dates->formatDate($project['date_retrait'], 'Y'); ?> <?= $heure_retrait ?>');
                        var letime<?= $project['id_project'] ?> = parseInt(cible<?=$project['id_project']?>.getTime() / 1000, 10);
                        setTimeout('decompte(letime <?= $project['id_project'] ?>,"val<?=$project['id_project']?>")', 500);
                    </script>
                    <?php
                }
                ?>

                <?php if ($project['photo_projet'] != '') { ?>
                    <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>">
                        <img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $project['photo_projet'] ?>" alt="<?= $project['photo_projet'] ?>" class="thumb">
                    </a>
                <?php } ?>
                <div class="description">
                    <?php if ($_SESSION['page_projet'] == 'projets_fo') { ?>
                        <h5><a href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>"><?= $project['title'] ?></a></h5>
                    <?php } else { ?>
                        <h5><a href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>"><?= $project['title'] ?></a></h5>
                    <?php } ?>
                    <h6><?= $this->companies->city . ($this->companies->zip != '' ? ', ' : '') . $this->companies->zip ?></h6>
                    <p><?= $project['nature_project'] ?></p>
                </div>
            </td>
            <td>
                <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>">
                    <div class="cadreEtoiles">
                        <div class="etoile <?= $this->lNotes[$project['risk']] ?>"></div>
                    </div>
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
                    <?= $project['taux'] ?>%
                </a>
            </td>
            <td>
                <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>">
                    <strong id="val<?= $project['id_project'] ?>"><?= $project['daterest'] ?></strong>
                </a>
            </td>
            <td>
                <?php if ($this->projects_status->status >= \projects_status::FUNDE) { ?>
                    <a href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>" class="btn btn-info btn-small multi grise1 btn-grise"><?= $this->lng['preteur-projets']['voir-le-projet'] ?></a>
                <?php } else { ?>
                    <a href="<?= $this->lurl ?>/projects/detail/<?= $project['slug'] ?>" class="btn btn-info btn-small">PRÊTEZ</a>
                <?php } ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<div id="positionStart" style="display:none;"><?= $this->lProjetsFunding[0]['positionStart'] ?></div>
<div class="loadmore" style="display:none;"><?= $this->lng['preteur-projets']['chargement-en-cours'] ?></div>
<div class="nbProjet" style="display:none;"><?= $this->nbProjects ?></div>
<div id="ordreProject" style="display:none;"><?= $this->ordreProject ?></div>
<div id="where" style="display:none;"><?= empty($this->where) ?: 1 ?></div>
<div id="valType" style="display:none;"><?= $this->type ?></div>

<script>
    $('.tooltip-anchor').tooltip();
</script>
