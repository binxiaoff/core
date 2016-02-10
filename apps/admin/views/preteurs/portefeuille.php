<?php if (isset($_SESSION['freeow'])): ?>
    <script type="text/javascript">
        $(function() {
            var title = "<?=$_SESSION['freeow']['title']?>",
                message = "<?=$_SESSION['freeow']['message']?>",
                opts = {},
                container;
            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
        });
        <?php unset($_SESSION['freeow']); ?>
    </script>
<?php endif; ?>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/preteurs" title="Prêteurs">Prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/gestion" title="Gestion prêteurs">Gestion prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/gestion" title="Gestion prêteurs">Detail prêteur</a> -</li>
        <li>Portefeuille & Performances</li>
    </ul>

    <div><?= $this->sClientStatusMessage ?></div>

    <h1>Detail prêteur : <?= $this->clients->prenom . ' ' . $this->clients->nom ?></h1>
    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/preteurs/edit/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Consulter Prêteur</a>
        <a href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Modifier Prêteur</a>
        <a href="<?= $this->lurl ?>/preteurs/email_history/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Historique des emails</a>
    </div>

    <div>
        <h2>Portefeuille</h2>

        <h3>TRI du portefeuille : <?= (is_null($this->IRRValue)) ? 'Ce prêteur est trop récent. Son TRI n\'a pas encore été calculé.' : $this->IRRValue . '%' ?>
            <?= (is_null($this->IRRDate)) ? '' : '(calculé le ' . $this->dates->formatDateMysqltoShortFR($this->IRRDate) . ')' ?>
        </h3>
        <h3>Nombre de projets à probleme dans le portefeuille : <?= $this->problProjects ?></h3>
        <h3>Nombre de projets total dans le portefeuille : <?= $this->totalProjects ?></h3>
        <h3>Nombre de projets mis en ligne depuis son inscription : <?= $this->projectsPublished ?><h2>
    </div>
    <br/>
    <h2>Prêts</h2>
    <div class="table-filter clearfix">
        <p class="left">Historique des projets financés depuis le compte Unilend n°<?= $this->clients->id_client ?></p>
    </div>
    <div>
        <table class="tablesorter">
            <thead>
            <tr>
                <th style="text-align: left">ID Projet</th>
                <th style="text-align: left">Nom</th>
                <th style="text-align: left">Note</th>
                <th style="text-align: left">Montant prêté</th>
                <th style="text-align: left">Taux d'intérêt</th>
                <th style="text-align: left">Début</th>
                <th style="text-align: left">Prochaine</th>
                <th style="text-align: left">Fin</th>
                <th style="text-align: left">Mensualité</th>
                <th style="text-align: left">Documents <br> à télécharger</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($this->lSumLoans as $iLoanIndex => $aProjectLoans): ?>
                <tr class="<?= $iLoanIndex % 2 ? '' : 'odd' ?>">
                    <td<?php if ($aProjectLoans['nb_loan'] > 1): ?> rowspan="<?= ($aProjectLoans['nb_loan'] + 1) ?>" <?php endif; ?>><?= $aProjectLoans['id_project'] ?></td>
                    <td<?php if ($aProjectLoans['nb_loan'] > 1): ?> rowspan="<?= ($aProjectLoans['nb_loan'] + 1) ?>" <?php endif; ?>><h5><a href="/dossiers/edit/<?= $aProjectLoans['id_project'] ?>"><?= $aProjectLoans['name'] ?></a></h5></td>
                    <td<?php if ($aProjectLoans['nb_loan'] > 1): ?> rowspan="<?= ($aProjectLoans['nb_loan'] + 1) ?>" <?php endif; ?>><?= $aProjectLoans['risk'] ?></td>
                    <td><?= $this->ficelle->formatNumber($aProjectLoans['amount'], 0) ?> €</td>
                    <td><?= $this->ficelle->formatNumber($aProjectLoans['rate'], 1) ?> %</td>
                    <?php if (in_array($aProjectLoans['project_status'], array(\projects_status::REMBOURSEMENT_ANTICIPE, \projects_status::REMBOURSE))) : ?>
                        <td<?php if ($aProjectLoans['nb_loan'] > 1): ?> rowspan="<?= ($aProjectLoans['nb_loan'] + 1) ?>" <?php endif; ?>><?= $this->dates->formatDate($aProjectLoans['debut'], 'd/m/Y') ?></td>
                        <td<?php if ($aProjectLoans['nb_loan'] > 1): ?> rowspan="<?= ($aProjectLoans['nb_loan'] + 1) ?>" <?php endif; ?> colspan="3"><p>Remboursé intégralementle <?= $this->dates->formatDate($aProjectLoans['status_change'], 'd/m/Y') ?></p></td>
                    <?php else: ?>
                        <td<?php if ($aProjectLoans['nb_loan'] > 1): ?> rowspan="<?= ($aProjectLoans['nb_loan'] + 1) ?>" <?php endif; ?>><?= $this->dates->formatDate($aProjectLoans['debut'], 'd/m/Y') ?></td>
                        <td<?php if ($aProjectLoans['nb_loan'] > 1): ?> rowspan="<?= ($aProjectLoans['nb_loan'] + 1) ?>" <?php endif; ?>><?= $this->dates->formatDate($aProjectLoans['next_echeance'], 'd/m/Y') ?></td>
                        <td<?php if ($aProjectLoans['nb_loan'] > 1): ?> rowspan="<?= ($aProjectLoans['nb_loan'] + 1) ?>" <?php endif; ?>><?= $this->dates->formatDate($aProjectLoans['fin'], 'd/m/Y') ?></td>
                        <td><?= $this->ficelle->formatNumber($aProjectLoans['mensuel']) ?> € / mois</td>
                    <?php endif; ?>
                    <td>
                        <?php if ($aProjectLoans['nb_loan'] == 1): ?>
                            <?php if ($aProjectLoans['project_status'] >= \projects_status::REMBOURSEMENT): ?>
                                <a href="<?= $this->furl ?>/pdf/contrat/<?= $this->clients->hash ?>/<?= $aProjectLoans['id_loan_if_one_loan'] ?>">
                                    <?php switch($aProjectLoans['id_type_contract']) {
                                        case \loans::TYPE_CONTRACT_IFP:
                                            echo 'Contrat IFP';
                                            break;
                                        case \loans::TYPE_CONTRACT_BDC:
                                            echo 'Bon de Caisse';
                                            break;
                                        default :
                                            trigger_error('Type de contrat inconnue', E_USER_NOTICE);
                                            break;
                                    } ?>
                                </a>
                            <?php endif; ?>
                            <?php if (in_array($aProjectLoans['id_project'], $this->aProjectsInDebt)): ?>
                                <br />
                                <a href="<?= $this->furl ?>/pdf/declaration_de_creances/<?= $this->clients->hash ?>/<?= $aProjectLoans['id_loan_if_one_loan'] ?>">Declaration de créances</a>
                            <?php endif; ?>
                        <?php else: ?>
                            &nbsp;
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($aProjectLoans['nb_loan'] > 1): ?>
                    <?php foreach ($this->loans->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND id_project = ' . $aProjectLoans['id_project']) as $aLoan):
                        $SumAremb    = $this->echeanciers->select('id_loan = ' . $aLoan['id_loan'] . ' AND status = 0', 'ordre ASC', 0, 1);
                        $fLoanAmount = round($SumAremb[0]['montant'] / 100, 2) - round($SumAremb[0]['prelevements_obligatoires'] + $SumAremb[0]['retenues_source'] + $SumAremb[0]['csg'] + $SumAremb[0]['prelevements_sociaux'] + $SumAremb[0]['contributions_additionnelles'] + $SumAremb[0]['prelevements_solidarite'] + $SumAremb[0]['crds'], 2); ?>
                        <tr class="sub_loan<?= $iLoanIndex % 2 ? '' : ' odd' ?>">
                            <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aLoan['amount']/100, 0) ?> €</td>
                            <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aLoan['rate'], 1) ?> %</td>
                            <?php if (false === in_array($aProjectLoans['project_status'], array(\projects_status::REMBOURSEMENT_ANTICIPE, \projects_status::REMBOURSE))) : ?>
                                <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($fLoanAmount) ?> € / mois</td>
                            <?php endif; ?>
                            <td>
                                <?php if ($aProjectLoans['project_status'] >= \projects_status::REMBOURSEMENT): ?>
                                    <a href="<?= $this->furl ?>/pdf/contrat/<?= $this->clients->hash ?>/<?= $aLoan['id_loan'] ?>">
                                        <?= ($aLoan['id_type_contract'] == \loans::TYPE_CONTRACT_IFP) ? 'Contrat IFP' : 'Bon de Caisse' ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (in_array($aProjectLoans['id_project'], $this->aProjectsInDebt)): ?>
                                    <br />
                                    <a href="<?= $this->furl ?>/pdf/declaration_de_creances/<?= $this->clients->hash ?>/<?= $aLoan['id_loan'] ?>">Declaration de créances</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
