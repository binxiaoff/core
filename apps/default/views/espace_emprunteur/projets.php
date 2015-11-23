<style>
    table {
        table-layout: fixed;
        margin-bottom: 30px;
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        border-radius: 3px;
    }

    tr {
        font-size: 13px;
    }

    tr:nth-child(even) {
        background-color: #f4f4f4;
    }

    th {
        text-align: left;
        vertical-align: middle;
        padding: 5px;
    }

    .table-head {
        height: 40px;
        background: #b10366 none repeat scroll 0 0;
        color: #f4f4f4;
        padding: 4px;
    }

    td {
        text-align: right;
    }


</style>

<div class="main">
    <div class="shell">

       <?php if (empty($this->projectsPreFunding) === false) { ?>

            <table class="table">
                <tbody>
                <tr class="table-head">
                    <th></th>
                    <th width="40">ID</th>
                    <th width="200">Nature</th>
                    <th width="90">Montant & Durée</th>
                    <th width="90">Date de clôture</th>
                    <th width="90">Statut projet</th>
                    <th></th>
                </tr>
                <?php foreach ($this->projectsPreFunding as $aProject) { ?>
                    <tr>
                        <td><img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $aProject['photo_projet'] ?>"
                                 alt="<?= $aProject['photo_projet'] ?>" class="thumb"></td>
                        <td><?= $aProject['id_project'] ?></td>
                        <td><?= $aProject['nature_project'] ?></td>
                        <td><?= $aProject['amount'] ?> € <br> <?= $aProject['period'] ?> mois</td>
                        <td><?= $this->lng['espace-emprunteur'][$aProject['project_status_label']] ?></td>
                        <td><a href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>">Voir le projet</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php } ?>


        <?php if (empty($this->aProjectsFunding) === false) { ?>
            <table class="table">
                <tbody>
                <tr class="table-head">
                    <th></th>
                    <th width="40">ID</th>
                    <th width="200">Nature</th>
                    <th width="90">Montant & Durée</th>
                    <th width="90">Date de clôture prévue</th>
                    <th width="90">% funding atteint</th>
                    <th width="90">Taux moyen du projet à date</th>
                    <th width="90"></th>
                    <th></th>
                </tr>
                <?php foreach ($this->aProjectsFunding as $aProject) { ?>
                    <tr>
                        <td><img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $aProject['photo_projet'] ?>"
                                 alt="<?= $aProject['photo_projet'] ?>" class="thumb"></td>
                        <td><?= $aProject['id_project'] ?></td>
                        <td><?= $aProject['nature_project'] ?></td>
                        <td><?= $aProject['amount'] ?> € <br> <?= $aProject['period'] ?> mois</td>
                        <td><?= $aProject['date_retrait'] ?></td>
                        <td><?= $aProject['funding-progress'] ?> %</td>
                        <td><?= $aProject['AverageIR'] ?> %</td>
                        <td>
                            <?php if ($aProject['funding-progress'] == 100 && $aProject['date_retrait'] > date('Y-m-d H:i:s')) { ?>
                                <a class="btn btn-info btn-small popup-link"
                                   href="<?= $this->lurl ?>/thickbox/pop_up_anticipation/<?= $aProject['hash'] ?>">Arrêter</a>
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
                <tbody>
                <tr class="table-head">
                    <th></th>
                    <th width="40">ID</th>
                    <th width="200">Nature</th>
                    <th width="90">Montant & Durée</th>
                    <th width="90">Date de clôture</th>
                    <th width="60">Mensualité</th>
                    <th width="60">Taux moyen du projet</th>
                    <th width="90">CRD à date</th>
                    <th width="90">Date de la prochaine échéance</th>
                    <th></th>
                </tr>
                <?php foreach ($this->aProjectsPostFunding as $aProject) { ?>
                    <tr>
                        <td><img src="<?= $this->surl ?>/images/dyn/projets/72/<?= $aProject['photo_projet'] ?>"
                                 alt="<?= $aProject['photo_projet'] ?>" class="thumb"></td>
                        <td><?= $aProject['id_project'] ?></td>
                        <td><?= $aProject['nature_project'] ?></td>
                        <td><?= $aProject['amount'] ?> € <br> <?= $aProject['period'] ?> mois</td>
                        <td><?= $aProject['date_retrait'] ?></td>
                        <td><?= $aProject['MonthlyPayment'] ?> €</td>
                        <td><?= $aProject['AverageIR'] ?> %</td>
                        <td><?= $aProject['RemainingDueCapital'] ?> €</td>
                        <td><?= $aProject['DateNextMonthlyPayment'] ?></td>
                        <td><a href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>">Voir le projet</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php } ?>

    </div>
</div>
