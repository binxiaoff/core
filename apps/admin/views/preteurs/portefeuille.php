<div id="contenu">
    <?php if (empty($this->clients->id_client)) : ?>
        <div class="attention">Attention : Compte <?= $this->params[0] ?> innconu</div>
    <?php else : ?>
        <div><?= $this->clientStatusMessage ?></div>
        <h1>Detail prêteur : <?= $this->clients->prenom . ' ' . $this->clients->nom ?></h1>
        <div class="btnDroite">
            <a href="<?= $this->lurl ?>/preteurs/bids/<?= $this->clients->id_client ?>" class="btn_link">Enchères</a>
            <a href="<?= $this->lurl ?>/preteurs/edit/<?= $this->clients->id_client ?>" class="btn_link">Consulter Prêteur</a>
            <a href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->clients->id_client ?>" class="btn_link">Modifier Prêteur</a>
            <a href="<?= $this->lurl ?>/preteurs/email_history/<?= $this->clients->id_client ?>" class="btn_link">Historique des emails</a>
        </div>
        <div>
            <h2>Portefeuille</h2>

            <h3>TRI du portefeuille :
                <?php if(null === $this->IRR) : ?>
                    Ce prêteur est trop récent. Son TRI n'a pas encore été calculé.
                <?php else : ?>
                    <?= \Unilend\Bundle\CoreBusinessBundle\Entity\LenderStatistic::STAT_VALID_OK === $this->IRR->getStatus() ? number_format($this->IRR->getValue(), 2, ',', ' ') . '%'  : 'TRI non valide'?>
                    <?= (is_null($this->IRR->getAdded())) ? '' : '(calculé le ' . $this->IRR->getAdded()->format('d/m/Y') . ')' ?>
                <?php endif; ?>
            </h3>
            <h3>Nombre de projets à probleme dans le portefeuille : <?= $this->problProjects ?></h3>
            <h3>Nombre de projets total dans le portefeuille : <?= $this->totalProjects ?></h3>
            <h3>Nombre de projets mis en ligne depuis son inscription : <?= $this->projectsPublished ?></h3>
            <h3>CIP évalué : <?= $this->cipEnabled ? 'Oui (<a href="' . $this->surl  . '/pdf/conseil-cip/' . $this->clients->hash . '" target="_blank"> Télécharger le PDF des conseils</a>)' : 'Non' ?></h3>
        </div>
        <br/>
        <div id="autobid">
            <h2>Autolend Settings</h2>
            <div id="allow-beta-user" style="padding-bottom: 15px;">
                <span>
                    <?php if ($this->bIsBetaTester) : ?>
                        <img alt="ON" src="<?= $this->surl ?>/images/admin/check_on.png">
                    <?php else : ?>
                        <img alt="OFF" src="<?= $this->surl ?>/images/admin/check_off.png">
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
            <?php else : ?>
                <div>
                    <span>Activation le : <?= isset($this->aSettingsDates['on']) ? $this->aSettingsDates['on']->format('d/m/Y') : '' ?></span>
                    <span style="padding-left: 400px;">Désactivation le : <?= isset($this->aSettingsDates['off']) ? $this->aSettingsDates['off']->format('d/m/Y') : '' ?></span>
                </div>
                <div style="padding-bottom: 15px;">Dernière mise à jour des settings: <?= isset($this->sValidationDate) ? $this->sValidationDate : '' ?></div>
                <div style="margin-bottom: 15px;">
                    <span>Montant: </span>
                    <input type="text" name="autobid-amount" id="autobid-amount"
                           value="<?= (isset($this->aAutoBidSettings[1]['A']['amount'])) ? $this->aAutoBidSettings[1]['A']['amount'] : '' ?>"
                           disabled="disabled"/>
                </div>
                <div class="autobid-param-advanced autobid-param-advanced-locked autobid-block" id="autobid-block">
                    <?php
                        $settingsCount = 0;
                        foreach ($this->aAutoBidSettings as $periodSettings) {
                            $settingsCount += count($periodSettings);
                        }
                    ?>
                    <?php if (false === in_array($settingsCount, [0, 25])) : ?>
                        <p style="background-color: #f9babe; padding: 10px; font-weight: bold;">La configuration Autolend de ce client n'est pas correcte. Veuillez vous rapprocher du service informatique, ce cas nécessitant des investigations.</p>
                    <?php else : ?>
                        <table class="autobid-param-advanced-table">
                            <tr>
                                <th class="empty"></th>
                                <th scope="col" colspan="5" class="table-title"><?= $this->translator->trans('autobid_expert-settings-table-title-risk') ?></th>
                            </tr>
                            <tr>
                                <th scope="col" class="table-title"><?= $this->translator->trans('autobid_expert-settings-table-title-period') ?></th>
                                <?php foreach (array_keys(array_values($this->aAutoBidSettings)[0]) as $evaluation) : ?>
                                    <th><?=constant('\projects::RISK_' . $evaluation)?>*</th>
                                <?php endforeach; ?>
                            </tr>
                            <?php foreach ($this->aAutoBidSettings as $aPeriodSettings) : ?>
                                <tr>
                                    <th scope="row"><?= $this->translator->trans('autobid_autobid-period-' . array_values($aPeriodSettings)[0]['id_period'], ['[#SEPARATOR#]' => '<br>']); ?></th>
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
                                        <span><span class="rate-legend legend-green"></span><?= $this->translator->trans('autobid_expert-settings-legend-inferior-rate') ?></span>
                                        <span><span class="rate-legend legend-gray"></span><?= $this->translator->trans('autobid_expert-settings-legend-deactivated') ?></span>
                                        <span><span class="rate-legend legend-red"></span><?= $this->translator->trans('autobid_expert-settings-legend-superior-rate') ?></span>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <h2>Prêts</h2>
        <div class="table-filter clearfix">
            <p class="left">Historique des projets financés depuis le compte Unilend n°<?= $this->clients->id_client ?></p>
        </div>
        <div>
            <table class="tablesorter">
                <thead>
                    <tr>
                        <th style="text-align:left">Projet</th>
                        <th style="text-align:left">Nom</th>
                        <th style="text-align:left">Statut</th>
                        <th style="text-align:left">Montant prêté</th>
                        <th style="text-align:left">Taux</th>
                        <th style="text-align:left">Documents</th>
                        <th style="text-align:left">Début</th>
                        <th style="text-align:left">Prochaine</th>
                        <th style="text-align:left">Fin</th>
                        <?php if ($this->hasTransferredLoans) : ?>
                            <th style="text-align: left">Ancien proprietaire (id client)</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->lSumLoans as $iLoanIndex => $aProjectLoans) : ?>
                        <?php $rowspan = ($aProjectLoans['nb_loan'] > 1) ? ' rowspan="' . ($aProjectLoans['nb_loan'] + 1) . '"': ''; ?>
                        <tr class="<?= $iLoanIndex % 2 ? '' : 'odd' ?>">
                            <td<?= $rowspan ?>><a href="<?= $this->furl ?>/dossiers/edit/<?= $aProjectLoans['id_project'] ?>"><?= $aProjectLoans['id_project'] ?></a></td>
                            <td<?= $rowspan ?>><strong><a href="/dossiers/edit/<?= $aProjectLoans['id_project'] ?>"><?= $aProjectLoans['title'] ?></a></strong></td>
                            <td<?= $rowspan ?>><?= $aProjectLoans['project_status_label'] ?></td>
                            <td><?= $this->ficelle->formatNumber($aProjectLoans['amount'], 0) ?> €</td>
                            <td><?= $this->ficelle->formatNumber($aProjectLoans['rate'], 1) ?> %</td>
                            <?php if ($aProjectLoans['nb_loan'] == 1) : ?>
                                <td>
                                    <?php if ($aProjectLoans['project_status'] >= \projects_status::REMBOURSEMENT) : ?>
                                        <a href="<?= $this->url ?>/protected/contrat/<?= $this->clients->hash ?>/<?= $aProjectLoans['id_loan_if_one_loan'] ?>">
                                            <?php $this->contract->get($aProjectLoans['id_type_contract']); ?>
                                            <?= $this->translator->trans('contract-type-label_' . $this->contract->label) ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (in_array($aProjectLoans['id_project'], $this->aProjectsInDebt)) : ?>
                                        <br/>
                                        <a href="<?= $this->url ?>/protected/declaration_de_creances/<?= $this->clients->hash ?>/<?= $aProjectLoans['id_loan_if_one_loan'] ?>">Déclaration de créances</a>
                                    <?php endif; ?>
                                </td>
                            <?php else : ?>
                                <td>&nbsp;</td>
                            <?php endif; ?>
                            <td<?= $rowspan ?>><?= $this->dates->formatDate($aProjectLoans['debut'], 'd/m/Y') ?></td>
                            <?php if (in_array($aProjectLoans['project_status'], [\projects_status::REMBOURSEMENT_ANTICIPE, \projects_status::REMBOURSE])) : ?>
                                <td<?= $rowspan ?> colspan="2"><p>Remboursé intégralement le <?= $this->dates->formatDate($aProjectLoans['final_repayment_date'], 'd/m/Y') ?></p></td>
                            <?php else : ?>
                                <td<?= $rowspan ?>><?= $this->dates->formatDate($aProjectLoans['next_echeance'], 'd/m/Y') ?></td>
                                <td<?= $rowspan ?>><?= $this->dates->formatDate($aProjectLoans['fin'], 'd/m/Y') ?></td>
                            <?php endif; ?>
                            <?php if ($this->hasTransferredLoans) : ?>
                                <?php if ($aProjectLoans['nb_loan'] == 1) : ?>
                                    <td>
                                        <?php if ($this->loan->get($aProjectLoans['id_loan_if_one_loan']) && false === empty($this->loan->id_transfer)) :
                                            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients $formerOwner */
                                            $formerOwner = $this->loanManager->getFormerOwner($this->loan); ?>
                                            <a href="<?= $this->lurl . '/preteurs/edit/' . $formerOwner->getIdClient() ?>"><?= $formerOwner->id_client_owner ?></a>
                                        <?php endif; ?>
                                    </td>
                                <?php else : ?>
                                    <td>&nbsp;</td>
                                <?php endif; ?>
                            <?php endif; ?>
                        </tr>
                        <?php if ($aProjectLoans['nb_loan'] > 1) : ?>
                            <?php foreach ($this->loans->select('id_lender = ' . $this->wallet->getId() . ' AND id_project = ' . $aProjectLoans['id_project']) as $aLoan) : ?>
                                <tr class="sub_loan<?= $iLoanIndex % 2 ? '' : ' odd' ?>">
                                    <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aLoan['amount']/100, 0) ?> €</td>
                                    <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aLoan['rate'], 1) ?> %</td>
                                    <td>
                                        <?php if ($aProjectLoans['project_status'] >= \projects_status::REMBOURSEMENT) : ?>
                                            <a href="<?= $this->url ?>/protected/contrat/<?= $this->clients->hash ?>/<?= $aLoan['id_loan'] ?>">
                                                <?php $this->contract->get($aLoan['id_type_contract']); ?>
                                                <?= $this->translator->trans('contract-type-label_' . $this->contract->label) ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (in_array($aProjectLoans['id_project'], $this->aProjectsInDebt)) : ?>
                                            <br />
                                            <a href="<?= $this->url ?>/protected/declaration_de_creances/<?= $this->clients->hash ?>/<?= $aLoan['id_loan'] ?>">Déclaration de créances</a>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($this->hasTransferredLoans) : ?>
                                        <td>
                                            <?php if (false === empty($aLoan['id_transfer'])) :
                                                $this->loan->get($aLoan['id_loan']);
                                                /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients $formerOwner */
                                                $formerOwner = $this->loanManager->getFormerOwner($this->loan); ?>
                                                <a href="<?= $this->lurl . '/preteurs/edit/' . $formerOwner->getIdClient() ?>"><?= $formerOwner->getIdClient() ?></a>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
