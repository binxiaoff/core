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
        $index = array_search(\company_tax_form_type::FORM_2035, array_column($this->incomeStatements, 'form_type'));
        foreach (array_keys(array_values($this->incomeStatements)[$index]['details']) as $label) : ?>
            <tr>
                <td><?= $this->translator->trans($label) ?></td>
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
<?php endif; ?>