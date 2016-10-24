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
                foreach ($this->incomeStatements as $incomeStatement) {
                    if (\company_tax_form_type::FORM_2035 != $incomeStatement['form_type']) {
                        echo '<td></td>';
                        if ($column) {
                            echo '<td></td>';
                        }
                    } else {
                        if ($column) : ?>
                            <td><?= empty($incomeStatement['details'][$sFieldName]['value']) || empty($previousTotal) ? 'N/A' : round(($previousTotal - $incomeStatement['details'][$sFieldName]['value']) / abs($incomeStatement['details'][$sFieldName]['value']) * 100) . '&nbsp;%' ?></td>
                        <?php endif; ?>
                        <td class="grisfonceBG"><?= empty($incomeStatement['details'][$sFieldName]['value']) ? '-' : $this->ficelle->formatNumber($incomeStatement['details'][$sFieldName]['value'], 0) . ' €' ?></td>
                        <?php $previousTotal = empty($incomeStatement['details'][$sFieldName]['value']) ? null : $incomeStatement['details'][$sFieldName]['value'];
                    }
                    $column ++;
                } ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>