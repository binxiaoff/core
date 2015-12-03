<div class="tab_title" id="title_etape4_4">Etape 4.4 - Synthèse financière</div>
<div class="tab_content" id="etape4_4">
    <form method="post" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
        <?php if (count($this->lbilans) > 0): ?>
            <table class="tablesorter" style="text-align:center;">
                <thead>
                <tr>
                    <th>Compte de résultat</th>
                    <?php foreach ($this->aAnnualAccountsDates as $aAnnualAccountsDate): ?>
                        <th width="250"><?= $aAnnualAccountsDate['start']->format('d/m/Y') ?> au <?= $aAnnualAccountsDate['end']->format('d/m/Y') ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Chiffe d'affaires</td>
                    <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
                        <td class="grisfonceBG"><?= empty($aAnnualAccounts['ca']) ? '-' : $this->ficelle->formatNumber($aAnnualAccounts['ca'], 0) . ' €' ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td>Résultat brut d'exploitation</td>
                    <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
                        <td class="grisfonceBG"><?= empty($aAnnualAccounts['resultat_brute_exploitation']) ? '-' : $this->ficelle->formatNumber($aAnnualAccounts['resultat_brute_exploitation'], 0) . ' €' ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td>Résultat d'exploitation</td>
                    <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
                        <td class="grisfonceBG"><?= empty($aAnnualAccounts['resultat_exploitation']) ? '-' : $this->ficelle->formatNumber($aAnnualAccounts['resultat_exploitation'], 0) . ' €' ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td>Investissements</td>
                    <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
                        <td class="grisfonceBG"><?= empty($aAnnualAccounts['investissements']) ? '-' : $this->ficelle->formatNumber($aAnnualAccounts['investissements'], 0) . ' €' ?></td>
                    <?php endforeach; ?>
                </tr>
                </tbody>
            </table>
            <br/>
            <?php

            $aAssets = array(
                'immobilisations_corporelles'     => 'Immobilisations corporelles',
                'immobilisations_incorporelles'   => 'Immobilisations incorporelles',
                'immobilisations_financieres'     => 'Immobilisations financières',
                'stocks'                          => 'Stocks',
                'creances_clients'                => 'Créances clients',
                'disponibilites'                  => 'Disponibilités',
                'valeurs_mobilieres_de_placement' => 'Valeurs mobilières de placement',
            );

            ?>
            <table class="tablesorter actif_passif" style="text-align:center;">
                <thead>
                <tr>
                    <th>Actif</th>
                    <?php foreach ($this->aAnnualAccountsDates as $aAnnualAccountsDate): ?>
                        <th width="250"><?= $aAnnualAccountsDate['start']->format('d/m/Y') ?> au <?= $aAnnualAccountsDate['end']->format('d/m/Y') ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                    <?php foreach ($aAssets as $sFieldName => $sTitle): ?>
                        <tr>
                            <td><?= $sTitle ?></td>
                            <?php foreach ($this->lCompanies_actif_passif as $aAssetsDebts): ?>
                                <td class="grisfonceBG"><?= empty($aAssetsDebts[$sFieldName]) ? '-' : $this->ficelle->formatNumber($aAssetsDebts[$sFieldName], 0) . ' €' ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td>Total</td>
                        <?php foreach ($this->lCompanies_actif_passif as $aAssetsDebts): ?>
                            <?php
                                $iTotal = 0;
                                foreach (array_keys($aAssets) as $sKey) {
                                    $iTotal += $aAssetsDebts[$sKey];
                                }
                            ?>
                            <td class="grisfonceBG"><?= $this->ficelle->formatNumber($iTotal, 0) ?> €</td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
            <br/>
            <?php

            $aDebts = array(
                'capitaux_propres'                   => 'Capitaux propres',
                'provisions_pour_risques_et_charges' => 'Provisions pour risques & charges',
                'amortissement_sur_immo'             => 'Amortissements sur immobilisations',
                'dettes_financieres'                 => 'Dettes financières',
                'dettes_fournisseurs'                => 'Dettes fournisseurs',
                'autres_dettes'                      => 'Autres dettes',
            );

            ?>
            <table class="tablesorter actif_passif" style="text-align:center;">
                <thead>
                <tr>
                    <th>Passif</th>
                    <?php foreach ($this->aAnnualAccountsDates as $aAnnualAccountsDate): ?>
                        <th width="250"><?= $aAnnualAccountsDate['start']->format('d/m/Y') ?> au <?= $aAnnualAccountsDate['end']->format('d/m/Y') ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                    <?php foreach ($aDebts as $sFieldName => $sTitle): ?>
                        <tr>
                            <td><?= $sTitle ?></td>
                            <?php foreach ($this->lCompanies_actif_passif as $aAssetsDebts): ?>
                                <td class="grisfonceBG"><?= empty($aAssetsDebts[$sFieldName]) ? '-' : $this->ficelle->formatNumber($aAssetsDebts[$sFieldName], 0) . ' €' ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td>Total</td>
                        <?php foreach ($this->lCompanies_actif_passif as $aAssetsDebts): ?>
                            <?php
                                $iTotal = 0;
                                foreach (array_keys($aDebts) as $sKey) {
                                    $iTotal += $aAssetsDebts[$sKey];
                                }
                            ?>
                            <td class="grisfonceBG"><?= $this->ficelle->formatNumber($iTotal, 0) ?> €</td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </form>
</div>
