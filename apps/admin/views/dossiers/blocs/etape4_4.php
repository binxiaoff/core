<div class="tab_title" id="title_etape4_4">Etape 4.4 - Synthèse financière</div>
<div class="tab_content" id="etape4_4">
    <?php if (count($this->lbilans) > 0) : ?>
        <?php

        $aAnnualAccountsFields = array(
            'ca'                          => 'Chiffre d\'affaires',
            'resultat_brute_exploitation' => 'Résultat brut d\'exploitation',
            'resultat_exploitation'       => 'Résultat d\'exploitation',
            'resultat_financier'          => 'Résultat financier',
            'produit_exceptionnel'        => 'Produit exceptionnel',
            'charges_exceptionnelles'     => 'Charges exceptionnelles',
            'resultat_exceptionnel'       => 'Résultat exceptionnel',
            'resultat_net'                => 'Résultat net',
            'investissements'             => 'Investissements'
        );
        $oldestAnnualAccountsId = end($this->lbilans)['id_bilan'];

        ?>
        <table class="tablesorter">
            <thead style="text-align:center;">
            <tr>
                <th>Compte de résultat</th>
                <?php foreach ($this->aAnnualAccountsDates as $index => $aAnnualAccountsDate) : ?>
                    <th width="250"><?= $aAnnualAccountsDate['start']->format('d/m/Y') ?> au <?= $aAnnualAccountsDate['end']->format('d/m/Y') ?></th>
                    <?php if ($index != $oldestAnnualAccountsId) { ?>
                        <th width="50"></th>
                    <?php } ?>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($aAnnualAccountsFields as $sFieldName => $sTitle) : ?>
                <tr>
                    <td><?= $sTitle ?></td>
                    <?php $previousTotal = null;
                    foreach ($this->lbilans as $aAnnualAccounts) {
                        if (false === is_null($previousTotal)) : ?>
                            <td><?= empty($aAnnualAccounts[$sFieldName]) ? 'N/A' : round(($previousTotal - $aAnnualAccounts[$sFieldName]) / abs($aAnnualAccounts[$sFieldName]) * 100) . '&nbsp;%' ?></td>
                        <?php endif; ?>
                        <td class="grisfonceBG"><?= empty($aAnnualAccounts[$sFieldName]) ? '-' : $this->ficelle->formatNumber($aAnnualAccounts[$sFieldName], 0) . ' €' ?></td>
                        <?php $previousTotal = empty($aAnnualAccounts[$sFieldName]) ? null : $aAnnualAccounts[$sFieldName];
                    } ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <br/>
        <?php

        $aAssetsFields = array(
            'immobilisations_corporelles'     => 'Immobilisations corporelles',
            'immobilisations_incorporelles'   => 'Immobilisations incorporelles',
            'immobilisations_financieres'     => 'Immobilisations financières',
            'stocks'                          => 'Stocks',
            'creances_clients'                => 'Créances clients',
            'disponibilites'                  => 'Disponibilités',
            'valeurs_mobilieres_de_placement' => 'Valeurs mobilières de placement',
            'comptes_regularisation_actif'    => 'Comptes de régularisation'
        );

        ?>
        <table class="tablesorter">
            <thead style="text-align:center;">
            <tr>
                <th>Actif</th>
                <?php foreach ($this->aAnnualAccountsDates as $index => $aAnnualAccountsDate): ?>
                    <th width="250"><?= $aAnnualAccountsDate['start']->format('d/m/Y') ?> au <?= $aAnnualAccountsDate['end']->format('d/m/Y') ?></th>
                    <?php if ($index != $oldestAnnualAccountsId) : ?>
                        <th width="50"></th>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($aAssetsFields as $sFieldName => $sTitle): ?>
            <tr>
                <td><?= $sTitle ?></td>
                <?php $previousTotal = null;
                foreach ($this->lCompanies_actif_passif as $aAssetsDebts): ?>
                    <?php if (false === is_null($previousTotal)) : ?>
                        <td><?= empty($aAssetsDebts[$sFieldName]) ? 'N/A' : round(($previousTotal - $aAssetsDebts[$sFieldName]) / abs($aAssetsDebts[$sFieldName]) * 100) . '&nbsp;%' ?></td>
                    <?php endif; ?>
                    <td class="grisfonceBG"><?= empty($aAssetsDebts[$sFieldName]) ? '-' : $this->ficelle->formatNumber($aAssetsDebts[$sFieldName], 0) . ' €' ?></td>
                    <?php $previousTotal = empty($aAssetsDebts[$sFieldName]) ? null : $aAssetsDebts[$sFieldName]; endforeach; ?>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td>Total</td>
                <?php $previousTotal = null;
                foreach ($this->lCompanies_actif_passif as $iIndex => $aAssetsDebts): ?>
                    <?php
                    $iTotal = 0;
                    foreach (array_keys($aAssetsFields) as $sKey) {
                        $iTotal += $aAssetsDebts[$sKey];
                    }
                    if (false === is_null($previousTotal)) : ?>
                        <td><?= empty($iTotal) ? 'N/A' : round(($previousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></td>
                    <?php endif; ?>
                    <td class="grisfonceBG"><?= $this->ficelle->formatNumber($iTotal, 0) ?> €</td>
                    <?php $previousTotal = empty($iTotal) ? null : $iTotal; endforeach; ?>
            </tr>
            </tbody>
        </table>
        <br/>
        <?php

        $aDebtsFields = array(
            'capitaux_propres'                   => 'Capitaux propres',
            'provisions_pour_risques_et_charges' => 'Provisions pour risques & charges',
            'amortissement_sur_immo'             => 'Amortissements sur immobilisations',
            'dettes_financieres'                 => 'Dettes financières',
            'dettes_fournisseurs'                => 'Dettes fournisseurs',
            'autres_dettes'                      => 'Autres dettes',
            'comptes_regularisation_passif'      => 'Comptes de régularisation'
        );

        ?>
        <table class="tablesorter">
            <thead style="text-align:center;">
            <tr>
                <th>Passif</th>
                <?php foreach ($this->aAnnualAccountsDates as $index => $aAnnualAccountsDate) : ?>
                    <th width="250"><?= $aAnnualAccountsDate['start']->format('d/m/Y') ?> au <?= $aAnnualAccountsDate['end']->format('d/m/Y') ?></th>
                    <?php if ($index != $oldestAnnualAccountsId) : ?>
                        <th width="50"></th>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($aDebtsFields as $sFieldName => $sTitle) : ?>
                <tr>
                    <td><?= $sTitle ?></td>
                    <?php $previousTotal = null;
                    foreach ($this->lCompanies_actif_passif as $aAssetsDebts) {
                        if (false === is_null($previousTotal)) : ?>
                            <td><?= empty($aAssetsDebts[$sFieldName]) ? 'N/A' : round(($previousTotal - $aAssetsDebts[$sFieldName]) / abs($aAssetsDebts[$sFieldName]) * 100) . '&nbsp;%' ?></td>
                        <?php endif; ?>
                        <td class="grisfonceBG"><?= empty($aAssetsDebts[$sFieldName]) ? '-' : $this->ficelle->formatNumber($aAssetsDebts[$sFieldName], 0) . ' €' ?></td>
                        <?php $previousTotal = empty($aAssetsDebts[$sFieldName]) ? null : $aAssetsDebts[$sFieldName];
                    } ?>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td>Total</td>
                <?php $previousTotal = null;
                foreach ($this->lCompanies_actif_passif as $iIndex => $aAssetsDebts) : ?>
                    <?php
                    $iTotal = 0;
                    foreach (array_keys($aDebtsFields) as $sKey) {
                        $iTotal += $aAssetsDebts[$sKey];
                    }
                    if (false === is_null($previousTotal)) : ?>
                        <td><?= empty($iTotal) ? 'N/A' : round(($previousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></td>
                    <?php endif; ?>
                    <td class="grisfonceBG"><?= $this->ficelle->formatNumber($iTotal, 0) ?> €</td>
                    <?php $previousTotal = empty($iTotal) ? null : $iTotal;
                endforeach; ?>
            </tr>
            </tbody>
        </table>
    <?php endif; ?>
</div>
