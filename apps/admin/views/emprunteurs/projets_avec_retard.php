<div class="row">
    <div class="col-md-12">
        <div class="block block-rounded" style="min-height: 462px;">
            <div class="block-content block-content-full">
                <h2 class="h3 push-30">
                    Retards et Recouvrements
                </h2>
                <div class="row push-30">
                    <div class="col-md-3">
                        <div class="font-s12 font-w600 text-uppercase">Restant à recouvrer</div>
                        <a class="h2 font-w300 text-primary"><?= $this->ficelle->formatNumber($this->lateRepaymentSummary['remainingAmountToCollect'], 0) ?>&nbsp;€</a>
                    </div>
                    <div class="col-md-3">
                        <div class="font-s12 font-w600 text-uppercase">Réceptions à traiter</div>
                        <a class="h2 font-w300 text-primary"><?= $this->ficelle->formatNumber($this->lateRepaymentSummary['pendingReceiptAmount'], 0) ?>&nbsp;€</a>
                    </div>
                    <div class="col-md-3">
                        <div class="font-s12 font-w600 text-uppercase">Projets en retard</div>
                        <a class="h2 font-w300 text-primary"><?= $this->ficelle->formatNumber($this->lateRepaymentSummary['projectsWithLateRepayment'], 0) ?></a>
                    </div>
                    <div class="col-md-3">
                        <div class="font-s12 font-w600 text-uppercase">Projets en recouvrement</div>
                        <a class="h2 font-w300 text-primary"><?= $this->ficelle->formatNumber($this->lateRepaymentSummary['projectsInDeptCollection'], 0) ?></a>
                    </div>
                </div>
                <hr>
                <?php if (count($this->projectsWithLateRepayments) > 0) : ?>
                    <table class="table table-bordered table-header-bg table-hover js-dataTable-simple">
                        <thead>
                        <tr>
                            <th style="width: 20%">
                                Raison Sociale
                            </th>
                            <th style="width: 20%">
                                Projet
                            </th>
                            <th style="width: 15%">
                                Statut
                            </th>
                            <th style="width: 10%">
                                Restant à recouvrer
                            </th>
                            <th style="width: 10%">
                                Confié au recouvreur
                            </th>
                            <th style="width: 10%">
                                Réceptions à traiter
                            </th>
                            <th style="width: 5%">
                                &nbsp;&nbsp;
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->projectsWithLateRepayments as $project) : ?>
                            <tr>
                                <td>
                                    <?= $project['companyName'] ?><br>
                                    <span class="text-muted">Siren: <?= $project['siren'] ?></span>
                                </td>
                                <td>
                                    <?= $project['projectTitle'] ?><br>
                                    <span class="text-muted">ID: <?= $project['idProject'] ?></span>
                                </td>
                                <!-- @todo set label by project status : must define mapping between class and project status range to make it dynamic-->
                                <td>
                                    <span class="label label-default"><?= $project['projectStatusLabel'] ?></span>
                                </td>
                                <td>
                                    <?= $this->ficelle->formatNumber($project['owedAmount'], 0) ?>&nbsp;€
                                </td>
                                <!-- @todo adapt check entrustedToCollector and pendingReceipt to display € symbol -->
                                <td>
                                    <?= ($project['entrustedToCollector'] !== 'N/A') ? $this->ficelle->formatNumber($project['entrustedToCollector'], 0) . ' %' : 'N/A' ?>
                                </td>
                                <td>
                                    <?= ($project['pendingReceipt'] !== 'N/A') ? $this->ficelle->formatNumber($project['pendingReceipt'], 0) . ' €' : 'N/A' ?>
                                </td>
                                <!-- @todo use real ID from the future tables -->
                                <td class="text-center">
                                    <a href="/recouvrement/details/293823" class="btn btn-xs btn-primary" data-toggle="tooltip" data-original-title="Voir détails"><i class="fa fa-arrow-right"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
