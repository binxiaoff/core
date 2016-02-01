<?php if (isset($_SESSION['freeow'])): ?>
    <script type="text/javascript">
        $(function () {
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
<style>
    .sub_loan tr, .sub_loan td{
        background-color: #f6f6f6;
        text-align: right;
    }
</style>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/preteurs" title="Prêteurs">Prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/gestion" title="Gestion prêteurs">Gestion prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/gestion" title="Gestion prêteurs">Detail prêteur</a> -</li>
        <li>Portefeuille & Performances</li>
    </ul>

    <?php if ($this->clients_status->status == \clients_status::TO_BE_CHECKED): ?>
        <div class="attention">
            Attention : compte non validé - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
    <?php elseif (in_array($this->clients_status->status, array(\clients_status::COMPLETENESS, \clients_status::COMPLETENESS_REPLY, \clients_status::COMPLETENESS_REMINDER))): ?>
        <div class="attention" style="background-color:#F9B137">
            Attention : compte en complétude - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
    <?php elseif (in_array($this->clients_status->status, array(\clients_status::MODIFICATION))): ?>
        <div class="attention" style="background-color:#F2F258">
            Attention : compte en modification - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
    <?php endif; ?>

    <h1>Detail prêteur : <?= $this->clients->prenom . ' ' . $this->clients->nom ?></h1>
    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/preteurs/edit/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Consulter
            Prêteur</a>
        <a href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->lenders_accounts->id_lender_account ?>"
           class="btn_link">Modifier Prêteur</a>
        <a href="<?= $this->lurl ?>/preteurs/email_history/<?= $this->lenders_accounts->id_lender_account ?>"
           class="btn_link">Historique des emails</a>
    </div>

    <div>
        <h2>Portefeuille</h2>

        <h3>TRI du portefeuille : <?= (empty($this->IRRValue)) ? 'Ce prêteur est trop récent. Son TRI n\'a pas encore été calculé.' : $this->IRRValue . '%' ?>
            <?= (empty($this->IRRDate)) ? '' : '(calculé le ' . $this->dates->formatDateMysqltoShortFR($this->IRRDate) . ')' ?>
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
                    <td><?= $aProjectLoans['id_project'] ?></td>
                    <td><h5><a href="/dossiers/edit/<?= $aProjectLoans['id_project'] ?>"><?= $aProjectLoans['name'] ?></a></h5></td>
                    <td><?= $aProjectLoans['risk'] ?></td>
                    <td><?= $this->ficelle->formatNumber($aProjectLoans['amount'], 0) ?> €</td>
                    <td><?= $this->ficelle->formatNumber($aProjectLoans['rate'], 1) ?> %</td>
                    <?php if (in_array($aProjectLoans['project_status'], array(\projects_status::REMBOURSEMENT_ANTICIPE, \projects_status::REMBOURSE))) : ?>
                        <td><?= $this->dates->formatDate($aProjectLoans['debut'], 'd/m/Y') ?></td>
                        <td colspan="3"><p>Remboursé intégralementle <?= $this->dates->formatDate($aProjectLoans['status_change'], 'd/m/Y') ?></p></td>
                    <?php else: ?>
                        <td><?= $this->dates->formatDate($aProjectLoans['debut'], 'd/m/Y') ?></td>
                        <td><?= $this->dates->formatDate($aProjectLoans['next_echeance'], 'd/m/Y') ?></td>
                        <td><?= $this->dates->formatDate($aProjectLoans['fin'], 'd/m/Y') ?></td>
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
                    <tr>
                    <?php foreach ($this->loans->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND id_project = ' . $aProjectLoans['id_project']) as $aLoan):
                        $SumAremb = $this->echeanciers->select('id_loan = ' . $aLoan['id_loan'], 'ordre ASC', 0, 1);
                        $fLoanAmount = $SumAremb[0]['montant'] / 100 - $SumAremb[0]['prelevements_obligatoires'] + $SumAremb[0]['retenues_source'] + $SumAremb[0]['csg'] + $SumAremb[0]['prelevements_sociaux'] + $SumAremb[0]['contributions_additionnelles'] + $SumAremb[0]['prelevements_solidarite'] + $SumAremb[0]['crds']; ?>
                        <tr class="sub_loan">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aLoan['amount']/100, 0) ?> €</td>
                            <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aLoan['rate'], 1) ?> %</td>
                            <td colspan="3"></td>
                            <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($fLoanAmount) ?> € / mois</td>
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
