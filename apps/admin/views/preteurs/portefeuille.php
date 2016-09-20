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
        <a href="<?= $this->lurl ?>/preteurs/bids/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Enchères</a>
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
    <div id="autobid">
        <h2>Autolend Settings</h2>
        <div id="allow-beta-user" style="padding-bottom: 15px;">
            <span>
            <?php if ($this->bIsBetaTester) : ?>
                <img alt="ON" src="<?=$this->surl?>/images/admin/check_on.png">
            <?php else: ?>
                <img alt="OFF" src="<?=$this->surl?>/images/admin/check_off.png">
            <?php endif; ?>
                    BetaTester
            </span>
        <span style="padding-left: 50px"></span>
            <input type="hidden" value="<?= ($this->bIsBetaTester) ? 'off' : 'on' ?>" id="NewSettingValue">
            <a class="btn_link" href="<?= $this->lurl ?>/preteurs/saveBetaTesterSetting/" onclick="$(this).attr('href', '<?= $this->lurl ?>/preteurs/saveBetaTesterSetting/<?= $this->clients->id_client?>/'+ $('#NewSettingValue').val());">
                <?= ($this->bIsBetaTester) ? 'Desactiver BetaTester' : 'Activer BetaTester'?>
            </a>
        </div>
        <?php if (empty($this->aAutoBidSettings)) : ?>
            <div style="margin: 25px 25px;">
            <span style="font-weight: bold; background-color:#F2F258; padding: 10px 10px;">
            Le preteur n'a pas encore défini ses paramètres.
            </span>
            </div>
        <?php else: ?>
        <div>
            <span>Activation le : <?= isset($this->aSettingsDates['on']) ? $this->aSettingsDates['on']->format('d/m/Y') : '' ?></span>
            <span style="padding-left: 400px;">Désactivation le : <?= isset($this->aSettingsDates['off']) ? $this->aSettingsDates['off']->format('d/m/Y') : '' ?></span>
        </div>
        <div style="padding-bottom: 15px;">Dernière mise à jour des settings: <?= isset($this->sValidationDate) ? $this->sValidationDate : '' ?></div>

        <div style="margin-bottom: 15px;">
            <span>Montant: </span>
            <input type="text" name="autobid-amount" id="autobid-amount"
                   value="<?= (isset($this->aAutoBidSettings[1][0]['amount'])) ? $this->aAutoBidSettings[1][0]['amount'] : '' ?>"
                   disabled="disabled"/>
        </div>
        <div class="autobid-param-advanced autobid-param-advanced-locked autobid-block" id="autobid-block">
            <table class="autobid-param-advanced-table">
                <tr>
                    <th class="empty"></th>
                    <th scope="col" colspan="5" class="table-title"><?= $this->lng['autobid']['expert-settings-table-title-risk'] ?></th>
                </tr>
                <tr>
                    <th scope="col" class="table-title"><?= $this->lng['autobid']['expert-settings-table-title-period'] ?></th>
                    <?php foreach (array_keys(array_values($this->aAutoBidSettings)[0]) as $evaluation) : ?>
                        <th><?=constant('\projects::RISK_' . $evaluation)?>*</th>
                    <?php endforeach; ?>
                </tr>
            <?php foreach ($this->aAutoBidSettings as $iPeriodId => $aPeriodSettings) : ?>
                <tr>
                    <th scope="row"><?= str_replace('[#SEPARATOR#]', '<br />', $this->lng['autobid']['autobid-period-' . $iPeriodId]) ?></th>
                    <?php foreach ($aPeriodSettings as $aSetting) : ?>
                        <td class="<?= (\autobid::STATUS_INACTIVE == $aSetting['status']) ? 'param-off' : '' ?>
                        <?= ($aSetting['rate_min'] <= round($aSetting['AverageRateUnilend'], 1) || empty($aSetting['AverageRateUnilend'])) ? '' : 'param-over' ?>">
                            <div class="cell-inner">
                                <label class="param-advanced-label"><?= empty($aSetting['rate_min']) ? 'off' : $this->ficelle->formatNumber($aSetting['rate_min'], 1) ?>%</label>
                            </div>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
                <tr>
                    <td class="empty"></td>
                    <td colspan="5" class="empty">
                        <div class="table-legend">
                            <span><span class="rate-legend legend-green"></span><?= $this->lng['autobid']['expert-settings-legend-inferior-rate'] ?></span>
                            <span><span class="rate-legend legend-gray"></span><?= $this->lng['autobid']['expert-settings-legend-deactivated'] ?></span>
                            <span><span class="rate-legend legend-red"></span><?= $this->lng['autobid']['expert-settings-legend-superior-rate'] ?></span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php endif; ?>

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
                <th style="text-align: left">Statut</th>
                <th style="text-align: left">Montant prêté</th>
                <th style="text-align: left">Taux d'intérêt</th>
                <th style="text-align: left">Début</th>
                <th style="text-align: left">Prochaine</th>
                <th style="text-align: left">Fin</th>
                <th style="text-align: left">Dernière échéance perçue</th>
                <th style="text-align: left">Documents <br> à télécharger</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($this->lSumLoans as $iLoanIndex => $aProjectLoans): ?>
                <tr class="<?= $iLoanIndex % 2 ? '' : 'odd' ?>">
                    <td<?php if ($aProjectLoans['nb_loan'] > 1): ?> rowspan="<?= ($aProjectLoans['nb_loan'] + 1) ?>" <?php endif; ?>><?= $aProjectLoans['id_project'] ?></td>
                    <td<?php if ($aProjectLoans['nb_loan'] > 1): ?> rowspan="<?= ($aProjectLoans['nb_loan'] + 1) ?>" <?php endif; ?>><h5><a href="/dossiers/edit/<?= $aProjectLoans['id_project'] ?>"><?= $aProjectLoans['name'] ?></a></h5></td>
                    <td<?php if ($aProjectLoans['nb_loan'] > 1): ?> rowspan="<?= ($aProjectLoans['nb_loan'] + 1) ?>" <?php endif; ?>><?= $aProjectLoans['project_status_label'] ?></td>
                    <td><?= $this->ficelle->formatNumber($aProjectLoans['amount'], 0) ?> €</td>
                    <td><?= $this->ficelle->formatNumber($aProjectLoans['rate'], 1) ?> %</td>
                    <td<?php if ($aProjectLoans['nb_loan'] > 1): ?> rowspan="<?= ($aProjectLoans['nb_loan'] + 1) ?>" <?php endif; ?>><?= $this->dates->formatDate($aProjectLoans['debut'], 'd/m/Y') ?></td>
                    <?php if (in_array($aProjectLoans['project_status'], array(\projects_status::REMBOURSEMENT_ANTICIPE, \projects_status::REMBOURSE))) : ?>
                        <td<?php if ($aProjectLoans['nb_loan'] > 1): ?> rowspan="<?= ($aProjectLoans['nb_loan'] + 1) ?>" <?php endif; ?> colspan="3"><p>Remboursé intégralementle <?= $this->dates->formatDate($aProjectLoans['status_change'], 'd/m/Y') ?></p></td>
                    <?php else: ?>
                        <td<?php if ($aProjectLoans['nb_loan'] > 1): ?> rowspan="<?= ($aProjectLoans['nb_loan'] + 1) ?>" <?php endif; ?>><?= $this->dates->formatDate($aProjectLoans['next_echeance'], 'd/m/Y') ?></td>
                        <td<?php if ($aProjectLoans['nb_loan'] > 1): ?> rowspan="<?= ($aProjectLoans['nb_loan'] + 1) ?>" <?php endif; ?>><?= $this->dates->formatDate($aProjectLoans['fin'], 'd/m/Y') ?></td>
                        <td><?= $this->ficelle->formatNumber($aProjectLoans['last_perceived_repayment']) ?> € / mois</td>
                    <?php endif; ?>
                    <td>
                        <?php if ($aProjectLoans['nb_loan'] == 1): ?>
                            <?php if ($aProjectLoans['project_status'] >= \projects_status::REMBOURSEMENT): ?>
                                <a href="<?= $this->lurl ?>/protected/contrat/<?= $this->clients->hash ?>/<?= $aProjectLoans['id_loan_if_one_loan'] ?>">
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
                                <a href="<?= $this->lurl ?>/protected/declaration_de_creances/<?= $this->clients->hash ?>/<?= $aProjectLoans['id_loan_if_one_loan'] ?>">Declaration de créances</a>
                            <?php endif; ?>
                        <?php else: ?>
                            &nbsp;
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($aProjectLoans['nb_loan'] > 1): ?>
                    <?php foreach ($this->loans->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND id_project = ' . $aProjectLoans['id_project']) as $aLoan):
                        ?>
                        <tr class="sub_loan<?= $iLoanIndex % 2 ? '' : ' odd' ?>">
                            <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aLoan['amount']/100, 0) ?> €</td>
                            <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aLoan['rate'], 1) ?> %</td>
                            <?php if (false === in_array($aProjectLoans['project_status'], array(\projects_status::REMBOURSEMENT_ANTICIPE, \projects_status::REMBOURSE))) :
                                $aRepayment    = $this->echeanciers->select('id_loan = ' . $aLoan['id_loan'] . ' AND status in(' . \echeanciers::STATUS_PARTIALLY_REPAID . ', ' . \echeanciers::STATUS_REPAID . ')', 'ordre ASC', 0, 1);
                                $iLoanAmount = $aRepayment[0]['montant'] - $this->tax->getAmountByRepaymentId($aRepayment[0]['id_echeancier']); ?>
                                <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber(round($iLoanAmount / 100, 2)) .' *** ' . $iLoanAmount  ?> € / mois</td>
                            <?php endif; ?>
                            <td>
                                <?php if ($aProjectLoans['project_status'] >= \projects_status::REMBOURSEMENT): ?>
                                    <a href="<?= $this->lurl ?>/protected/contrat/<?= $this->clients->hash ?>/<?= $aLoan['id_loan'] ?>">
                                        <?= ($aLoan['id_type_contract'] == \loans::TYPE_CONTRACT_IFP) ? 'Contrat IFP' : 'Bon de Caisse' ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (in_array($aProjectLoans['id_project'], $this->aProjectsInDebt)): ?>
                                    <br />
                                    <a href="<?= $this->lurl ?>/protected/declaration_de_creances/<?= $this->clients->hash ?>/<?= $aLoan['id_loan'] ?>">Declaration de créances</a>
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
