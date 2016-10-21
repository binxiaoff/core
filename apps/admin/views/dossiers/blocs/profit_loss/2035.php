<?php if (count($this->lbilans) > 0) : ?>
    <?php

    $aAnnualAccountsFields = array(
        'AG' => 'Recettes',
        'BA' => 'Achats',
        'BB' => 'Frais de personnel',
        'BC' => 'Charges sociales',
        'BN' => 'Frais financiers',
        'BR' => 'Total des dépenses',
        'CA' => 'Excédent brut',
        'CF' => 'Frais d\'établissement',
        'CG' => 'Dotation aux ammortissements',
        'CP' => 'Bénéfice net'
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
                <?php
                $previousTotal = null;
                $column = 0;
                foreach ($this->aBalanceSheets as $aBalanceSheet) {
                    if (\company_tax_form_type::FORM_2035 != $aBalanceSheet['form_type']) {
                        echo '<td></td>';
                        if ($column) {
                            echo '<td></td>';
                        }
                    } else {
                        if ($column) : ?>
                            <td><?= empty($aBalanceSheet['details'][$sFieldName]) || empty($previousTotal) ? 'N/A' : round(($previousTotal - $aBalanceSheet['details'][$sFieldName]) / abs($aBalanceSheet['details'][$sFieldName]) * 100) . '&nbsp;%' ?></td>
                        <?php endif; ?>
                        <td class="grisfonceBG"><?= empty($aBalanceSheet['details'][$sFieldName]) ? '-' : $this->ficelle->formatNumber($aBalanceSheet['details'][$sFieldName], 0) . ' €' ?></td>
                        <?php $previousTotal = empty($aBalanceSheet['details'][$sFieldName]) ? null : $aBalanceSheet['details'][$sFieldName];
                    }
                    $column ++;
                } ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>