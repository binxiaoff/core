<style>
    .table_projects table {
        table-layout: fixed;
        margin-bottom: 30px;
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        border-radius: 3px;
    }

    .table_projects tr {
        font-size: 13px;
    }

    .table_projects tr:nth-child(even) {
        background-color: #f4f4f4;
    }

    .table_projects th {
        text-align: left;
        vertical-align: middle;
        padding: 5px;
    }

    .table_projects thead {
        height: 40px;
        background: #b10366 none repeat scroll 0 0;
        color: #f4f4f4;
        padding: 4px;
    }

    .table_projects td {
        text-align: right;
    }


</style>

<div class="main">
    <div class="shell">

       <?php if (empty($this->projectsPreFunding) === false) { ?>

            <table class="table table_projects">
                <thead>
                <tr>
                    <th></th>
                    <th width="40"><?= $this->lng['espace-emprunteur']['table-projets-id'] ?></th>
                    <th width="200"><?= $this->lng['espace-emprunteur']['table-projets-nature'] ?></th>
                    <th width="90"><?= $this->lng['espace-emprunteur']['table-projets-montant-duree'] ?></th>
                    <th width="90"><?= $this->lng['espace-emprunteur']['table-projets-date-de-cloture'] ?></th>
                    <th width="90"><?= $this->lng['espace-emprunteur']['table-projets-status'] ?></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->projectsPreFunding as $aProject) { ?>
                    <tr>
                        <td><img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $aProject['photo_projet'] ?>"
                                 alt="<?= $aProject['photo_projet'] ?>" class="thumb"></td>
                        <td><?= $aProject['id_project'] ?></td>
                        <td><?= $aProject['nature_project'] ?></td>
                        <td><?= $this->ficelle->formatNumber($aProject['amount'],0) ?> € <br> <?= $aProject['period'] ?> mois</td>
                        <td><?= $this->lng['espace-emprunteur'][$aProject['project_status_label']] ?></td>
                        <td><a href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>">Voir le projet</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php } ?>


        <?php if (empty($this->aProjectsFunding) === false) { ?>
            <table class="table table_projects">
                <thead>
                <tr>
                    <th></th>
                    <th width="40"><?= $this->lng['espace-emprunteur']['table-projets-id'] ?></th>
                    <th width="200"><?= $this->lng['espace-emprunteur']['table-projets-nature'] ?></th>
                    <th width="90"><?= $this->lng['espace-emprunteur']['table-projets-montant-duree'] ?></th>
                    <th width="90"><?= $this->lng['espace-emprunteur']['table-projets-date-de-cloture-prevue'] ?></th>
                    <th width="90"><?= $this->lng['espace-emprunteur']['table-projets-funding-atteint'] ?></th>
                    <th width="90"><?= $this->lng['espace-emprunteur']['table-projets-taux-moyen-a-date'] ?></th>
                    <th width="90"></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->aProjectsFunding as $aProject) { ?>
                    <tr>
                        <td><img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $aProject['photo_projet'] ?>"
                                 alt="<?= $aProject['photo_projet'] ?>" class="thumb"></td>
                        <td><?= $aProject['id_project'] ?></td>
                        <td><?= $aProject['nature_project'] ?></td>
                        <td><?= $this->ficelle->formatNumber($aProject['amount'], 0) ?> € <br> <?= $aProject['period'] ?> mois</td>
                        <td><?= $aProject['date_retrait'] ?></td>
                        <td><?= $this->ficelle->formatNumber($aProject['funding-progress']) ?> %</td>
                        <td><?= $this->ficelle->formatNumber($aProject['AverageIR']) ?> %</td>
                        <td>
                            <?php if ($aProject['funding-progress'] >= 100 && $aProject['date_retrait_full'] <= date('Y-m-d H:i:s')) { ?>
                                <a class="btn btn-info btn-small popup-link"
                                   href="<?= $this->lurl ?>/thickbox/pop_up_anticipation/<?= (empty($aProject['hash']) === false) ? $aProject['hash'] : $aProject['id_project'] ?>">Arrêter</a>
                            <?php }
                            ?>
                        </td>
                        <td><a href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>">Voir le projet</a>
                        </td>
                    </tr>
                    <?php
                } ?>
                </tbody>
            </table>
            <?php
        }
        ?>

        <?php if (empty($this->aProjectsPostFunding) === false) { ?>
            <table>
                <thead>
                <tr class="table table_projects">
                    <th></th>
                    <th width="40"><?= $this->lng['espace-emprunteur']['table-projets-id'] ?></th>
                    <th width="200"><?= $this->lng['espace-emprunteur']['table-projets-nature'] ?></th>
                    <th width="90"><?= $this->lng['espace-emprunteur']['table-projets-montant-duree'] ?></th>
                    <th width="90"><?= $this->lng['espace-emprunteur']['table-projets-date-de-cloture'] ?></th>
                    <th width="60"><?= $this->lng['espace-emprunteur']['table-projets-mensualite'] ?></th>
                    <th width="60"><?= $this->lng['espace-emprunteur']['table-projets-taux-moyen'] ?></th>
                    <th width="90"><?= $this->lng['espace-emprunteur']['table-projets-crd'] ?></th>
                    <th width="90"><?= $this->lng['espace-emprunteur']['table-projets-date-prochaine-echeance'] ?></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->aProjectsPostFunding as $aProject) { ?>
                    <tr>
                        <td><img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $aProject['photo_projet'] ?>"
                                 alt="<?= $aProject['photo_projet'] ?>" class="thumb"></td>
                        <td><?= $aProject['id_project'] ?></td>
                        <td><?= $aProject['nature_project'] ?></td>
                        <td><?= $this->ficelle->formatNumber($aProject['amount'], 0) ?> € <br> <?= $aProject['period'] ?> mois</td>
                        <td><?= $aProject['date_retrait'] ?></td>
                        <td><?= $this->ficelle->formatNumber($aProject['MonthlyPayment']) ?> €</td>
                        <td><?= $aProject['AverageIR'] ?> %</td>
                        <td><?= $this->ficelle->formatNumber($aProject['RemainingDueCapital']) ?> €</td>
                        <td><?= $aProject['DateNextMonthlyPayment'] ?></td>
                        <td><a href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>">Voir le projet</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php } ?>
        &nbsp;
        <div style="">
            <a href="<?= $this->lurl ?>/espace_emprunteur/operations"><p><?= $this->lng['espace-emprunteur']['documents-dans-espace-dedie']?></p></a>
        </div>
    </div>
</div>
