<?php if (count($this->lbilans) > 0) : ?>
    <?php
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
        <?php
        $index = array_search(\company_tax_form_type::FORM_2033, array_column($this->incomeStatements, 'form_type'));
        foreach (array_keys(array_values($this->incomeStatements)[$index]['details']) as $label) : ?>
            <tr>
                <td><?= $this->translator->trans($label) ?></td>
                <?php
                $previousTotal = null;
                $column = 0;
                foreach ($this->incomeStatements as $incomeStatement) {
                    if (\company_tax_form_type::FORM_2033 != $incomeStatement['form_type']) {
                        echo '<td></td>';
                        if ($column) {
                            echo '<td></td>';
                        }
                    } else {
                        if ($column) : ?>
                            <td><?= empty($incomeStatement['details'][$label]) || empty($previousTotal) ? 'N/A' : round(($previousTotal - $incomeStatement['details'][$label]) / abs($incomeStatement['details'][$label]) * 100) . '&nbsp;%' ?></td>
                        <?php endif; ?>
                        <td class="grisfonceBG"><?= empty($incomeStatement['details'][$label]) ? '-' : $this->ficelle->formatNumber($incomeStatement['details'][$label], 0) . ' €' ?></td>
                        <?php $previousTotal = empty($incomeStatement['details'][$label]) ? null : $incomeStatement['details'][$label];
                    }
                    $column ++;
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
            <?php foreach ($this->aAnnualAccountsDates as $index => $aAnnualAccountsDate) : ?>
                <th width="250"><?= $aAnnualAccountsDate['start']->format('d/m/Y') ?> au <?= $aAnnualAccountsDate['end']->format('d/m/Y') ?></th>
                <?php if ($index != $oldestAnnualAccountsId) : ?>
                    <th width="50"></th>
                <?php endif; ?>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($aAssetsFields as $sFieldName => $sTitle) : ?>
            <tr>
                <td><?= $sTitle ?></td>
                <?php
                $previousTotal = null;
                $column = 0;
                $balanceIds = array_keys($this->aAnnualAccountsDates);
                foreach ($balanceIds as $balanceId):
                    $index = array_search($balanceId, array_column($this->lCompanies_actif_passif, 'id_bilan'));
                    if (false !== $index) :
                        $aAssetsDebts = $this->lCompanies_actif_passif[$index];
                        if ($column) : ?>
                            <td><?= empty($aAssetsDebts[$sFieldName]) || empty($previousTotal) ? 'N/A' : round(($previousTotal - $aAssetsDebts[$sFieldName]) / abs($aAssetsDebts[$sFieldName]) * 100) . '&nbsp;%' ?></td>
                        <?php endif; ?>
                        <td class="grisfonceBG"><?= empty($aAssetsDebts[$sFieldName]) ? '-' : $this->ficelle->formatNumber($aAssetsDebts[$sFieldName], 0) . ' €' ?></td>
                        <?php $previousTotal = empty($aAssetsDebts[$sFieldName]) ? null : $aAssetsDebts[$sFieldName]; ?>
                    <?php else : ?>
                        <td></td>
                        <?php if ($column) : ?>
                            <td></td>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php
                    $column ++;
                endforeach;
                ?>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td>Total</td>
            <?php
            $previousTotal = null;
            $column = 0;
            $balanceIds = array_keys($this->aAnnualAccountsDates);
            foreach ($balanceIds as $balanceId):
                $index = array_search($balanceId, array_column($this->lCompanies_actif_passif, 'id_bilan'));
                if (false !== $index) :
                    $aAssetsDebts = $this->lCompanies_actif_passif[$index];
                    $iTotal = 0;
                    foreach (array_keys($aAssetsFields) as $sKey) {
                        $iTotal += $aAssetsDebts[$sKey];
                    }
                    if ($column) : ?>
                        <td><?= empty($iTotal) || empty($previousTotal) ? 'N/A' : round(($previousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></td>
                    <?php endif; ?>
                    <td class="grisfonceBG"><?= $this->ficelle->formatNumber($iTotal, 0) ?> €</td>
                    <?php $previousTotal = empty($iTotal) ? null : $iTotal; ?>
                <?php else : ?>
                    <td></td>
                    <?php if ($column) : ?>
                        <td></td>
                    <?php endif; ?>
                    <?php
                endif;
                $column ++;
            endforeach;
            ?>
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
                <?php
                $previousTotal = null;
                $column = 0;
                $balanceIds = array_keys($this->aAnnualAccountsDates);
                foreach ($balanceIds as $balanceId):
                    $index = array_search($balanceId, array_column($this->lCompanies_actif_passif, 'id_bilan'));
                    if (false !== $index) :
                        $aAssetsDebts = $this->lCompanies_actif_passif[$index];
                        if ($column) : ?>
                            <td><?= empty($aAssetsDebts[$sFieldName]) || empty($previousTotal) ? 'N/A' : round(($previousTotal - $aAssetsDebts[$sFieldName]) / abs($aAssetsDebts[$sFieldName]) * 100) . '&nbsp;%' ?></td>
                        <?php endif; ?>
                        <td class="grisfonceBG"><?= empty($aAssetsDebts[$sFieldName]) ? '-' : $this->ficelle->formatNumber($aAssetsDebts[$sFieldName], 0) . ' €' ?></td>
                        <?php $previousTotal = empty($aAssetsDebts[$sFieldName]) ? null : $aAssetsDebts[$sFieldName]; ?>
                    <?php else : ?>
                        <td></td>
                        <?php if ($column) : ?>
                            <td></td>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php
                    $column ++;
                endforeach;
                ?>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td>Total</td>
            <?php
            $previousTotal = null;
            $column = 0;
            $balanceIds = array_keys($this->aAnnualAccountsDates);
            foreach ($balanceIds as $balanceId):
                $index = array_search($balanceId, array_column($this->lCompanies_actif_passif, 'id_bilan'));
                if (false !== $index) :
                    $aAssetsDebts = $this->lCompanies_actif_passif[$index];
                    $iTotal = 0;
                    foreach (array_keys($aDebtsFields) as $sKey) {
                        $iTotal += $aAssetsDebts[$sKey];
                    }
                    if ($column) : ?>
                        <td><?= empty($iTotal) || empty($previousTotal) ? 'N/A' : round(($previousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></td>
                    <?php endif; ?>
                    <td class="grisfonceBG"><?= $this->ficelle->formatNumber($iTotal, 0) ?> €</td>
                    <?php $previousTotal = empty($iTotal) ? null : $iTotal; ?>
                <?php else : ?>
                    <td></td>
                    <?php if ($column) : ?>
                        <td></td>
                    <?php endif; ?>
                    <?php
                endif;
                $column ++;
            endforeach;
            ?>
        </tr>
        </tbody>
    </table>
<?php endif; ?>