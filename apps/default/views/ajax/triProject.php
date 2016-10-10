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
    foreach ($this->lProjetsFunding as $aProject) : ?>
        <tr class="unProjet" id="project<?= $aProject['id_project'] ?>">
            <td>
                <?php
                if ($aProject['status'] < \projects_status::FUNDE) {
                    $tab_date_retrait = explode(' ', $aProject['date_retrait_full']);
                    $tab_date_retrait = explode(':', $tab_date_retrait[1]);
                    $heure_retrait    = $tab_date_retrait[0] . ':' . $tab_date_retrait[1];

                    ?>
                    <script>
                        var cible<?= $aProject['id_project'] ?> = new Date('<?= $this->dates->formatDate($aProject['date_retrait'], 'F d'); ?>, <?= $this->dates->formatDate($aProject['date_retrait'], 'Y'); ?> <?= $heure_retrait ?>');
                        var letime<?= $aProject['id_project'] ?> = parseInt(cible<?=$aProject['id_project']?>.getTime() / 1000, 10);
                        setTimeout('decompte(letime<?= $aProject['id_project'] ?>,"val<?=$aProject['id_project']?>")', 500);
                    </script>
                    <?php
                } else {
                    if ($aProject['date_fin'] != '0000-00-00 00:00:00') {
                        $endDateTime = new \DateTime($aProject['date_fin']);
                    } else {
                        $endDateTime = new \DateTime($aProject['date_retrait_full']);
                    }
                    $endDate             = strftime('%d %B', $endDateTime->getTimestamp());
                    $aProject['daterest'] = str_replace('[#date#]', $endDate, $this->lng['preteur-projets']['termine']);
                }
                ?>

                <?php if ($aProject['photo_projet'] != '') { ?>
                    <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>">
                        <img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $aProject['photo_projet'] ?>" alt="<?= $aProject['photo_projet'] ?>" class="thumb">
                    </a>
                <?php } ?>
                <div class="description">
                    <h5><a href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>"><?= $aProject['title'] ?></a></h5>
                    <h6><?= $this->companies->city . ($this->companies->zip != '' ? ', ' : '') . $this->companies->zip ?></h6>
                    <p><?= $aProject['nature_project'] ?></p>
                </div>
            </td>
            <td>
                <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>">
                    <div class="cadreEtoiles">
                        <div class="etoile <?= $this->lNotes[$aProject['risk']] ?>"></div>
                    </div>
                </a>
            </td>
            <td style="white-space:nowrap;">
                <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>">
                    <?= $this->ficelle->formatNumber($aProject['amount'], 0) ?>€
                </a>
            </td>
            <td style="white-space:nowrap;">
                <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>">
                    <?= ($aProject['period'] == 1000000 ? $this->lng['preteur-projets']['je-ne-sais-pas'] : $aProject['period'] . ' ' . $this->lng['preteur-projets']['mois']) ?>
                </a>
            </td>
            <td>
                <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>">
                    <?= $aProject['taux'] ?>%
                </a>
            </td>
            <td>
                <a class="lien" href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>">
                    <span id="val<?= $aProject['id_project'] ?>"<?php if ($aProject['status'] >= \projects_status::FUNDE) :?> class="project_ended"<?php endif; ?>><?= $aProject['daterest'] ?></span>
                </a>
            </td>
            <td>
                <?php if ($aProject['status'] >= \projects_status::FUNDE) { ?>
                    <a href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>" class="btn btn-info btn-small multi grise1 btn-grise"><?= $this->lng['preteur-projets']['voir-le-projet'] ?></a>
                <?php } else { ?>
                    <a href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>" class="btn btn-info btn-small">PRÊTEZ</a>
                <?php } ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<div id="positionStart" style="display:none;"><?= isset($sPositionStart) ? $sPositionStart : 0 ?></div>
<div class="loadmore" style="display:none;"><?= $this->lng['preteur-projets']['chargement-en-cours'] ?></div>
<div class="nbProjet" style="display:none;"><?= $this->nbProjects ?></div>
<div id="ordreProject" style="display:none;"><?= $this->ordreProject ?></div>
<div id="where" style="display:none;"><?= empty($this->where) ?: 1 ?></div>
<div id="valType" style="display:none;"><?= $this->type ?></div>

<script>
    $('.tooltip-anchor').tooltip();
</script>
