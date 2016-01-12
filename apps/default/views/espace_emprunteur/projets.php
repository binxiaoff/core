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
        text-align: center;
        vertical-align: middle;
        padding: 5px;
    }

    .table_projects thead {
        height: 50px;
        background: #b10366 none repeat scroll 0 0;
        color: #f4f4f4;
        padding: 4px;
    }

    .table_projects td {
        text-align: center;
        padding-top: 10px;
    }
    .table_projects td.nature {
        text-align: left;
        padding-top: 10px;
    }

</style>

<div class="main">
    <div class="shell">

        <?php if (empty($this->aProjectsPostFunding) === false) : ?>

            <div class="row row-btn" style="margin-bottom: 30px;">
                <a class="btn btn-info btn-small popup-link"
                   href="<?= $this->lurl ?>/thickbox/pop_up_nouveau_projet"><?= $this->lng['espace-emprunteur']['nouvelle-demande'] ?></a>
                <?php if (isset($_SESSION['forms']['nouvelle-demande']['errors'])) : ?>
                    <div class="row error_login" style="margin-right: 50px;"><?= $this->lng['espace-emprunteur']['nouvelle-demande-non-abouti'] ?></div>
                <?php endif;?>
            </div>

        <?php endif; ?>

        <div>

            <?php if (empty($this->aProjectsPreFunding) === false) : ?>

                <table class="table_projects" style="table-layout: fixed;">
                    <thead>
                    <tr>
                        <th width="10%"><?= $this->lng['espace-emprunteur']['table-projets-id'] ?></th>
                        <th width="22%"><?= $this->lng['espace-emprunteur']['table-projets-nature'] ?></th>
                        <th width="11%"><?= $this->lng['espace-emprunteur']['table-projets-montant-duree'] ?></th>
                        <th width="22%"><?= $this->lng['espace-emprunteur']['table-projets-status'] ?></th>
                        <th width="17%"></th>
                        <th width="18%"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->aProjectsPreFunding as $aProject) : ?>
                        <tr>
                            <td><?= $aProject['id_project'] ?></td>
                            <td class="nature"><?= $aProject['nature_project'] ?></td>
                            <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aProject['amount'], 0) ?> € /  <?= $aProject['period'] ?>
                                <?= $this->lng['espace-emprunteur']['mois'] ?></td>
                            <td><?= $this->lng['espace-emprunteur'][ $aProject['project_status_label'] ] ?></td>
                            <?php if ($aProject['project_status_label'] === 'en-attente-de-pieces' ): ?>
                            <td>
                                <a href="<?= $this->url . '/depot_de_dossier/fichiers/' . $aProject['hash'] ?>">
                                    <?= $this->lng['espace-emprunteur']['deposer-des-pieces'] ?></a>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                &nbsp;
            <?php endif ?>

            <?php if (empty($this->aProjectsFunding) === false) : ?>
                <table class="table_projects" style="table-layout: fixed;">
                    <thead>
                    <tr>
                        <th width="10%"><?= $this->lng['espace-emprunteur']['table-projets-id'] ?></th>
                        <th width="22%"><?= $this->lng['espace-emprunteur']['table-projets-nature'] ?></th>
                        <th width="11%"><?= $this->lng['espace-emprunteur']['table-projets-montant-duree'] ?></th>
                        <th width="12%"><?= $this->lng['espace-emprunteur']['table-projets-date-de-cloture-prevue'] ?></th>
                        <th width="11%"><?= $this->lng['espace-emprunteur']['table-projets-funding-atteint'] ?></th>
                        <th width="11%"><?= $this->lng['espace-emprunteur']['table-projets-taux-moyen-a-date'] ?></th>
                        <th width="11%"></th>
                        <th width="12%"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->aProjectsFunding as $aProject) : ?>
                        <tr>
                            <td><?= $aProject['id_project'] ?></td>
                            <td class="nature"><?= $aProject['nature_project'] ?></td>
                            <td><?= $this->ficelle->formatNumber($aProject['amount'], 0) ?> €
                                <br> <?= $aProject['period'] ?> <?= $this->lng['espace-emprunteur']['mois'] ?></td>
                            <td><?= $this->dates->formatDateMysqltoFr($aProject['date_retrait']) ?></td>
                            <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aProject['funding-progress']) ?> %</td>
                            <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aProject['AverageIR']) ?> %</td>
                            <td style="white-space: nowrap;">
                                <?php
                                if ($aProject['funding-progress'] >= 100 && $aProject['oInterval'] >= 0 && isset($_SESSION['cloture_anticipe']) === false) : ?>
                                    <div style="margin: 10px;"><a class="btn btn-info btn-small popup-link"
                                       href="<?= $this->lurl ?>/thickbox/pop_up_anticipation/<?= (empty($aProject['hash']) === false) ? $aProject['hash'] : $aProject['id_project'] ?>"><?= $this->lng['espace-emprunteur']['bouton-arret-funding'] ?></a></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>"><?= $this->lng['espace-emprunteur']['voir-le-projet'] ?></a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
                &nbsp;
            <?php endif ?>

            <?php if (empty($this->aProjectsPostFunding) === false) : ?>
                <table class="table_projects" style="table-layout: fixed;">
                    <thead>
                    <tr class=table_projects" style="table-layout: fixed;">
                        <th width="10%"><?= $this->lng['espace-emprunteur']['table-projets-id'] ?></th>
                        <th width="22%"><?= $this->lng['espace-emprunteur']['table-projets-nature'] ?></th>
                        <th width="10%"><?= $this->lng['espace-emprunteur']['table-projets-montant-duree'] ?></th>
                        <th width="10%"><?= $this->lng['espace-emprunteur']['table-projets-date-de-cloture'] ?></th>
                        <th width="10%"><?= $this->lng['espace-emprunteur']['table-projets-mensualite'] ?></th>
                        <th width="7%"><?= $this->lng['espace-emprunteur']['table-projets-taux-moyen'] ?></th>
                        <th width="10%"><?= $this->lng['espace-emprunteur']['table-projets-crd'] ?></th>
                        <th width="10%"><?= $this->lng['espace-emprunteur']['table-projets-date-prochaine-echeance'] ?></th>
                        <th width="11%"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->aProjectsPostFunding as $aProject) : ?>
                        <tr>
                            <td><?= $aProject['id_project'] ?></td>
                            <td class="nature"><?= $aProject['nature_project'] ?></td>
                            <td><?= $this->ficelle->formatNumber($aProject['amount'], 0) ?> €
                                <br> <?= $aProject['period'] ?> <?= $this->lng['espace-emprunteur']['mois'] ?></td>
                            <td><?= $this->dates->formatDateMysqltoFr($aProject['date_retrait']) ?></td>
                            <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aProject['MonthlyPayment']) ?> €</td>
                            <td style="white-space: nowrap;"><?= $aProject['AverageIR'] ?> %</td>
                            <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aProject['RemainingDueCapital']) ?> €</td>
                            <td><?= ($aProject['RemainingDueCapital'] == 0) ? $this->lng['espace-emprunteur']['rembourse-integralement'] : $this->dates->formatDateMysqltoFr_HourOut($aProject['DateNextMonthlyPayment']) ?></td>
                            <td>
                                <a href="<?= $this->lurl ?>/projects/detail/<?= $aProject['slug'] ?>"><?= $this->lng['espace-emprunteur']['voir-le-projet'] ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            &nbsp;
            <div>
                <a href="<?= $this->lurl ?>/espace_emprunteur/operations">
                    <p><?= $this->lng['espace-emprunteur']['documents-dans-espace-dedie'] ?></p></a>
            </div>
        </div>
    </div>
</div>
<div style="clear: both"></div>
