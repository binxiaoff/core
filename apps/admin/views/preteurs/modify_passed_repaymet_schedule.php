<div id="contenu">
    <h1>Rattrapage des échéanciers</h1>
    <form method="post" name="modify_repayment" id="modify_repayment" enctype="multipart/form-data" action="<?= $this->lurl ?>/preteurs/modify_passed_repaymet_schedule">
        <label for="echeances_csv">Télécharger un CSV des échéances : </label> <input type="file" id="echeances_csv" name="echeances_csv" accept="text/csv">
        <br>
        <input class="btn" type="submit" name="upload" value="Envoyer">
    </form>
    <br>
    <a href="<?= $this->lurl ?>/files/echeanciers_exemple.csv">Voir l'exemple de CSV</a>
    <br><br><br>
    <h1>Rapport de rattrapage des échéanciers</h1>
    <?php if (isset($this->aTemplateVariables['aMatch']) && count($this->aTemplateVariables['aMatch']) > 0) : ?>
        <h2>Échéanciers retouvés</h2>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>ID échéancier</th>
                    <th>ID prêteur</th>
                    <th>ID projet</th>
                    <th>ID loan</th>
                    <th>Ordre</th>
                    <th>Montant</th>
                    <th>Capital</th>
                    <th>Intérêt</th>
                    <th>Prelevement obligatoire</th>
                    <th>Retenues source</th>
                    <th>CSG</th>
                    <th>Prelevement sociaux</th>
                    <th>Contributions additionnelles</th>
                    <th>Prelevement solidarité</th>
                    <th>CRDS</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 0; ?>
                <?php foreach ($this->aTemplateVariables['aMatch'] as $aRepaymentSchedule) : ?>
                    <tr<?= ++$i % 2 == 1 ? '' : ' class="odd"' ?>>
                        <td><?= $aRepaymentSchedule['id_echeancier'] ?></td>
                        <td><?= $aRepaymentSchedule['id_lender'] ?></td>
                        <td><?= $aRepaymentSchedule['id_project'] ?></td>
                        <td><?= $aRepaymentSchedule['id_loan'] ?></td>
                        <td><?= $aRepaymentSchedule['ordre'] ?></td>
                        <td>
                            <?php if (0 != $iDiffMontant = $aRepaymentSchedule['montant'] - $aRepaymentSchedule['montant_old']) : ?>
                                <strong class="red">
                                    <?= $aRepaymentSchedule['montant'] ?>
                                    (<?= $aRepaymentSchedule['montant_old'] ?>)
                                </strong>
                            <?php else : ?>
                                <?= $aRepaymentSchedule['montant'] ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (0 != $iDiffMontant = $aRepaymentSchedule['capital'] - $aRepaymentSchedule['capital_old']) : ?>
                                <strong class="red">
                                    <?= $aRepaymentSchedule['capital'] ?>
                                    (<?= $aRepaymentSchedule['capital_old'] ?>)
                                </strong>
                            <?php else : ?>
                                <?= $aRepaymentSchedule['capital'] ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (0 != $iDiffMontant = $aRepaymentSchedule['interets'] - $aRepaymentSchedule['interets_old']) : ?>
                                <strong class="red">
                                    <?= $aRepaymentSchedule['interets'] ?>
                                    (<?= $aRepaymentSchedule['interets_old'] ?>)
                                </strong>
                            <?php else : ?>
                                <?= $aRepaymentSchedule['interets'] ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (0 != $iDiffMontant = $aRepaymentSchedule['prelevements_obligatoires'] - $aRepaymentSchedule['prelevements_obligatoires_old']) : ?>
                                <strong class="red">
                                    <?= $aRepaymentSchedule['prelevements_obligatoires'] ?>
                                    (<?= $aRepaymentSchedule['prelevements_obligatoires_old'] ?>)
                                </strong>
                            <?php else : ?>
                                <?= $aRepaymentSchedule['prelevements_obligatoires'] ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (0 != $iDiffMontant = $aRepaymentSchedule['retenues_source'] - $aRepaymentSchedule['retenues_source_old']) : ?>
                                <strong class="red">
                                    <?= $aRepaymentSchedule['retenues_source'] ?>
                                    (<?= $aRepaymentSchedule['retenues_source_old'] ?>)
                                </strong>
                            <?php else : ?>
                                <?= $aRepaymentSchedule['retenues_source'] ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (0 != $iDiffMontant = $aRepaymentSchedule['csg'] - $aRepaymentSchedule['csg_old']) : ?>
                                <strong class="red">
                                    <?= $aRepaymentSchedule['csg'] ?>
                                    (<?= $aRepaymentSchedule['csg_old'] ?>)
                                </strong>
                            <?php else : ?>
                                <?= $aRepaymentSchedule['csg'] ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (0 != $iDiffMontant = $aRepaymentSchedule['prelevements_sociaux'] - $aRepaymentSchedule['prelevements_sociaux_old']) : ?>
                                <strong class="red">
                                    <?= $aRepaymentSchedule['prelevements_sociaux'] ?>
                                    (<?= $aRepaymentSchedule['prelevements_sociaux_old'] ?>)
                                </strong>
                            <?php else : ?>
                                <?= $aRepaymentSchedule['prelevements_sociaux'] ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (0 != $iDiffMontant = $aRepaymentSchedule['contributions_additionnelles'] - $aRepaymentSchedule['contributions_additionnelles_old']) : ?>
                                <strong class="red">
                                    <?= $aRepaymentSchedule['contributions_additionnelles'] ?>
                                    (<?= $aRepaymentSchedule['contributions_additionnelles_old'] ?>)
                                </strong>
                            <?php else : ?>
                                <?= $aRepaymentSchedule['contributions_additionnelles'] ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (0 != $iDiffMontant = $aRepaymentSchedule['prelevements_solidarite'] - $aRepaymentSchedule['prelevements_solidarite_old']) : ?>
                                <strong class="red">
                                    <?= $aRepaymentSchedule['prelevements_solidarite'] ?>
                                    (<?= $aRepaymentSchedule['prelevements_solidarite_old'] ?>)
                                </strong>
                            <?php else : ?>
                                <?= $aRepaymentSchedule['prelevements_solidarite'] ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (0 != $iDiffMontant = $aRepaymentSchedule['crds'] - $aRepaymentSchedule['crds_old']) : ?>
                                <strong class="red">
                                    <?= $aRepaymentSchedule['crds'] ?>
                                    (<?= $aRepaymentSchedule['crds_old'] ?>)
                                </strong>
                            <?php else : ?>
                                <?= $aRepaymentSchedule['crds'] ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table><br><br><br>
    <?php endif; ?>
    <?php if (isset($this->aTemplateVariables['aNonMatch']) && count($this->aTemplateVariables['aNonMatch']) > 0) : ?>
        <h2>Échéanciers incohérents due l'eurreur sur ses données</h2>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>ID échéancier</th>
                    <th>ID prêteur</th>
                    <th>ID projet</th>
                    <th>ID loan</th>
                    <th>Ordre</th>
                    <th>Montant</th>
                    <th>Capital</th>
                    <th>Intérêt</th>
                    <th>Prelevement obligatoire</th>
                    <th>Retenues source</th>
                    <th>CSG</th>
                    <th>Prelevement sociaux</th>
                    <th>Contributions additionnelles</th>
                    <th>Prelevement solidarité</th>
                    <th>CRDS</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 0; ?>
                <?php foreach ($this->aTemplateVariables['aNonMatch'] as $aRepaymentSchedule) : ?>
                    <tr<?= ++$i % 2 == 1 ? '' : ' class="odd"' ?>>
                        <td><?= $aRepaymentSchedule['id_echeancier'] ?></td>
                        <td><?= $aRepaymentSchedule['id_lender'] ?></td>
                        <td><?= $aRepaymentSchedule['id_project'] ?></td>
                        <td><?= $aRepaymentSchedule['id_loan'] ?></td>
                        <td><?= $aRepaymentSchedule['ordre'] ?></td>
                        <td><?= $aRepaymentSchedule['montant'] ?></td>
                        <td><?= $aRepaymentSchedule['capital'] ?></td>
                        <td><?= $aRepaymentSchedule['interets'] ?></td>
                        <td><?= $aRepaymentSchedule['prelevements_obligatoires'] ?></td>
                        <td><?= $aRepaymentSchedule['retenues_source'] ?></td>
                        <td><?= $aRepaymentSchedule['csg'] ?></td>
                        <td><?= $aRepaymentSchedule['prelevements_sociaux'] ?></td>
                        <td><?= $aRepaymentSchedule['contributions_additionnelles'] ?></td>
                        <td><?= $aRepaymentSchedule['prelevements_solidarite'] ?></td>
                        <td><?= $aRepaymentSchedule['crds'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <br><br><br>
    <?php endif; ?>
    <?php if (isset($this->aTemplateVariables['aChanges']) && count($this->aTemplateVariables['aChanges']) > 0) : ?>
        <h2>Movements</h2>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>ID échéancier</th>
                    <th>Table BDD</th>
                    <th>ID de la clé primaire</th>
                    <th>Colone</th>
                    <th>Movement</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 0; ?>
                <?php $iCurrentShedule = null; ?>
                <?php foreach ($this->aTemplateVariables['aChanges'] as $iScheduleId => $aChanges) : ?>
                    <?php foreach ($aChanges as $aChange) : ?>
                        <tr<?= ++$i % 2 == 1 ? '' : ' class="odd"' ?>>
                            <?php if ($iCurrentShedule !== $iScheduleId) : ?>
                                <?php $iCurrentShedule = $iScheduleId; ?>
                                <td rowspan="<?= count($aChanges) ?>"><?= $iCurrentShedule ?></td>
                            <?php endif; ?>
                            <td><?= $aChange['table'] ?></td>
                            <td><?= $aChange['id'] ?></td>
                            <td><?= $aChange['column'] ?></td>
                            <td><?= $aChange['movement'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
