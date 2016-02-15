<?php
$iBalanceSheetsCount     = count($this->aBalanceSheets);
$aOperationalCashFlow    = array();
$aGrossOperatingSurplus  = array();
$aMediumLongTermDebt     = array();
$aBalanceTotal           = array();
$iLastAnnualAccountsId   = current(array_keys($this->aBalanceSheets));
$iOldestAnnualAccountsId = end(array_keys($this->aBalanceSheets));

foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
    $aOperationalCashFlow[$iBalanceSheetId] =
        $aBalanceSheet[$this->aBalanceCodes['HN']['id_balance_type']]
        - $aBalanceSheet[$this->aBalanceCodes['FP']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['GA']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['GB']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['GC']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['GD']['id_balance_type']]
        - $aBalanceSheet[$this->aBalanceCodes['GM']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['GQ']['id_balance_type']]
        - $aBalanceSheet[$this->aBalanceCodes['HB']['id_balance_type']]
        - $aBalanceSheet[$this->aBalanceCodes['HC']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['HF']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['HG']['id_balance_type']];

    $aGrossOperatingSurplus[$iBalanceSheetId] =
        $aBalanceSheet[$this->aBalanceCodes['GG']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['GA']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['GB']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['GC']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['GD']['id_balance_type']]
        - $aBalanceSheet[$this->aBalanceCodes['FP']['id_balance_type']]
        - $aBalanceSheet[$this->aBalanceCodes['FQ']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['GE']['id_balance_type']];

    $aMediumLongTermDebt[$iBalanceSheetId] =
        $aBalanceSheet[$this->aBalanceCodes['DS']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['DT']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['DU']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['DV']['id_balance_type']]
        - $aBalanceSheet[$this->aBalanceCodes['EH']['id_balance_type']]
        - $aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']];

    $aBalanceTotal[$iBalanceSheetId] =
        $aBalanceSheet[$this->aBalanceCodes['AN']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['AP']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['AR']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['AT']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['AV']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['AX']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['AB']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['AD']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['AF']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['AH']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['AJ']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['AL']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['CS']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['CU']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['BB']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['BD']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['BF']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['BH']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['BL']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['BN']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['BP']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['BR']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['BT']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['BV']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['BX']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['BZ']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['CB']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['CH']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['CF']['id_balance_type']]
        + $aBalanceSheet[$this->aBalanceCodes['CD']['id_balance_type']];

}
?>
<div class="tab_title" id="title_etape4_3">Etape 4.3 - Ratios et analyses</div>
<div class="tab_content" id="etape4_3">
    <table class="tablesorter annual-accounts">
        <thead style="text-align:center;">
                <tr>
                    <th>Solvabilité</th>
                    <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
                        <th width="200"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
                        <?php if ($aAnnualAccounts['id_bilan'] != $iOldestAnnualAccountsId) { ?><th width="50"></th><?php } ?>
                    <?php endforeach; ?>
                </tr>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Dette financière nette</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber =
                        $aBalanceSheet[$this->aBalanceCodes['DS']['id_balance_type']]
                        + $aBalanceSheet[$this->aBalanceCodes['DT']['id_balance_type']]
                        + $aBalanceSheet[$this->aBalanceCodes['DU']['id_balance_type']]
                        + $aBalanceSheet[$this->aBalanceCodes['DV']['id_balance_type']]
                        - $aBalanceSheet[$this->aBalanceCodes['CF']['id_balance_type']]
                        - $aBalanceSheet[$this->aBalanceCodes['CD']['id_balance_type']]
                        - $aBalanceSheet[$this->aBalanceCodes['EH']['id_balance_type']]
                        - $aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']];

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 0) ?>&nbsp;€</td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>CAF</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = $aOperationalCashFlow[$iBalanceSheetId];

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 0) ?>&nbsp;€</td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>CAF disponible</td>
                <?php
                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    ?>
                    <td><?= $this->ficelle->formatNumber($aOperationalCashFlow[$iBalanceSheetId] - $this->aBalanceSheets[$iBalanceSheetId][$this->aBalanceCodes['VH']['id_balance_type']], 0) ?>&nbsp;€</td>
                    <?php
                    break;
                }
                ?>
                <?= str_repeat('<td></td>', 2 * ($iBalanceSheetsCount - 1)) ?>
            </tr>
            <tr>
                <td>CAF moyenne pondérée sur 3 ans</td>
                <?php if (3 === $iBalanceSheetsCount) : ?>
                    <?php list($iSecondToLastOperationalCashFlow, $iPreviousOperationalCashFlow, $iLastOperationalCashFlow) = array_values($aOperationalCashFlow); ?>
                    <td><?= $this->ficelle->formatNumber((2 * $iLastOperationalCashFlow + $iPreviousOperationalCashFlow + 0.5 * $iSecondToLastOperationalCashFlow) / 3.5, 0) ?>&nbsp;€</td>
                    <?= str_repeat('<td></td>', 2 * ($iBalanceSheetsCount - 1)) ?>
                <?php else : ?>
                    -
                <?php endif; ?>
            </tr>
            <tr>
                <!-- Dette moyen long terme -->
                <td>DMLT / CAF</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = $aMediumLongTermDebt[$iBalanceSheetId] / $aOperationalCashFlow[$iBalanceSheetId];

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>DMLT / EBE</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = $aMediumLongTermDebt[$iBalanceSheetId] / $aGrossOperatingSurplus[$iBalanceSheetId];

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>Solvabilité générale</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = (
                            $aBalanceSheet[$this->aBalanceCodes['BL']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BN']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BP']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BR']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BT']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BV']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BX']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BZ']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['CB']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['CH']['id_balance_type']]
                        ) / (
                            $aBalanceSheet[$this->aBalanceCodes['DW']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DX']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['EH']['id_balance_type']]
                            - $aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']]
                        );

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>Liquidité générale</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = (
                            $aBalanceSheet[$this->aBalanceCodes['BL']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BN']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BP']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BR']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BT']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BV']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BX']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BZ']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['CB']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['CH']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['CF']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['CD']['id_balance_type']]
                        ) / (
                            $aBalanceSheet[$this->aBalanceCodes['DW']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DX']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DY']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DZ']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['EA']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['EB']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['ED']['id_balance_type']]
                            - $aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']]
                        );

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>Liquidité réduite</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = (
                            $aBalanceSheet[$this->aBalanceCodes['BV']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BX']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BZ']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['CB']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['CH']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['CF']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['CD']['id_balance_type']]
                        ) / (
                            $aBalanceSheet[$this->aBalanceCodes['DW']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DX']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DY']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DZ']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['EA']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['EB']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['ED']['id_balance_type']]
                            - $aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']]
                        );

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
        </tbody>
    </table>
    <br/>
    <table class="tablesorter annual-accounts">
        <thead style="text-align:center;">
                <tr>
                    <th>Endettement et structure</th>
                    <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
                        <th width="200"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
                        <?php if ($aAnnualAccounts['id_bilan'] != $iOldestAnnualAccountsId) { ?><th width="50"></th><?php } ?>
                    <?php endforeach; ?>
                </tr>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>FP / Total bilan net (%)</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = (
                            $aBalanceSheet[$this->aBalanceCodes['DL']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DO']['id_balance_type']]
                        ) / $aBalanceTotal[$iBalanceSheetId] * 100;

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>Quasi FP / Total bilan net</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = (
                            $aBalanceSheet[$this->aBalanceCodes['DL']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DO']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['EI']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']]
                        ) / $aBalanceTotal[$iBalanceSheetId];

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>DMLT / Total bilan net (%)</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = $aMediumLongTermDebt[$iBalanceSheetId] / $aBalanceTotal[$iBalanceSheetId] * 100;

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>DMLT / Quasi FP</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = $aMediumLongTermDebt[$iBalanceSheetId] / (
                            $aBalanceSheet[$this->aBalanceCodes['DL']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DO']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['EI']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']]
                        );

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>DMLT / Total bilan net</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = $aMediumLongTermDebt[$iBalanceSheetId] / $aBalanceTotal[$iBalanceSheetId];

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>GEARING (%)</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = (
                            $aBalanceSheet[$this->aBalanceCodes['DS']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DT']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DU']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DV']['id_balance_type']]
                            - $aBalanceSheet[$this->aBalanceCodes['CF']['id_balance_type']]
                            - $aBalanceSheet[$this->aBalanceCodes['CD']['id_balance_type']]
                            - $aBalanceSheet[$this->aBalanceCodes['EH']['id_balance_type']]
                            - $aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']]
                        ) / (
                            $aBalanceSheet[$this->aBalanceCodes['DL']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DO']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']]
                        ) * 100;

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>Charges fi / EBE</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = abs($aBalanceSheet[$this->aBalanceCodes['GU']['id_balance_type']]) / (
                            $aBalanceSheet[$this->aBalanceCodes['GG']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['GA']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['GB']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['GC']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['GD']['id_balance_type']]
                            - $aBalanceSheet[$this->aBalanceCodes['FP']['id_balance_type']]
                            - $aBalanceSheet[$this->aBalanceCodes['FQ']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['GE']['id_balance_type']]
                        );

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>Charges fi / Résultat net</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = abs($aBalanceSheet[$this->aBalanceCodes['GU']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['HN']['id_balance_type']];

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>CCA stables</td>
                <?php
                if (1 === $iBalanceSheetsCount) {
                    ?>
                    <td><?= $this->ficelle->formatNumber($this->aBalanceSheets[$iLastAnnualAccountsId][$this->aBalanceCodes['VI']['id_balance_type']], 1) ?></td>
                    <?php
                } elseif (2 <= $iBalanceSheetsCount) {
                    $iLastNumber = $this->aBalanceSheets[$iLastAnnualAccountsId][$this->aBalanceCodes['VI']['id_balance_type']];
                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if ($iBalanceSheetId !== $iLastAnnualAccountsId) {
                            ?>
                            <td><?= $this->ficelle->formatNumber(min($iLastNumber, $aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']]), 1) ?>&nbsp;€</td>
                            <?php
                            break;
                        }
                    }
                }
                ?>
                <?= str_repeat('<td></td>', 2 * ($iBalanceSheetsCount - 1)) ?>
            </tr>
        </tbody>
    </table>
    <br/>
    <table class="tablesorter annual-accounts">
        <thead style="text-align:center;">
                <tr>
                    <th>Rotations</th>
                    <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
                        <th width="200"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
                        <?php if ($aAnnualAccounts['id_bilan'] != $iOldestAnnualAccountsId) { ?><th width="50"></th><?php } ?>
                    <?php endforeach; ?>
                </tr>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>FR</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber =
                            $aBalanceSheet[$this->aBalanceCodes['DL']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DO']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DU']['id_balance_type']]
                            - $aBalanceSheet[$this->aBalanceCodes['EH']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']]
                            - (
                                $aBalanceSheet[$this->aBalanceCodes['CS']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['CU']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['BB']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['BD']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['BF']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['BH']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AN']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AP']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AR']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AT']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AV']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AX']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AB']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AD']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AF']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AH']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AJ']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AL']['id_balance_type']]
                                - $aBalanceSheet[$this->aBalanceCodes['BK']['id_balance_type']]
                            );

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>FR (en jours de CA)</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = (
                            $aBalanceSheet[$this->aBalanceCodes['DL']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DO']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DU']['id_balance_type']]
                            - $aBalanceSheet[$this->aBalanceCodes['EH']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']]
                            - (
                                $aBalanceSheet[$this->aBalanceCodes['CS']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['CU']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['BB']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['BD']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['BF']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['BH']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AN']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AP']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AR']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AT']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AV']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AX']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AB']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AD']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AF']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AH']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AJ']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['AL']['id_balance_type']]
                                - $aBalanceSheet[$this->aBalanceCodes['BK']['id_balance_type']]
                            )
                        ) / ($aBalanceSheet[$this->aBalanceCodes['FL']['id_balance_type']] * (1 + $this->fVATRate)) * 360;

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>BFR</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber =
                        $aBalanceSheet[$this->aBalanceCodes['BL']['id_balance_type']]
                        + $aBalanceSheet[$this->aBalanceCodes['BN']['id_balance_type']]
                        + $aBalanceSheet[$this->aBalanceCodes['BP']['id_balance_type']]
                        + $aBalanceSheet[$this->aBalanceCodes['BR']['id_balance_type']]
                        + $aBalanceSheet[$this->aBalanceCodes['BT']['id_balance_type']]
                        + $aBalanceSheet[$this->aBalanceCodes['BV']['id_balance_type']]
                        + $aBalanceSheet[$this->aBalanceCodes['BX']['id_balance_type']]
                        + $aBalanceSheet[$this->aBalanceCodes['BZ']['id_balance_type']]
                        + $aBalanceSheet[$this->aBalanceCodes['CB']['id_balance_type']]
                        + $aBalanceSheet[$this->aBalanceCodes['CH']['id_balance_type']]
                        - (
                            $aBalanceSheet[$this->aBalanceCodes['DV']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DW']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DX']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DY']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['DZ']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['EA']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['EB']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['ED']['id_balance_type']]
                            - $aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']]
                        );

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>BFR (en jours de CA)</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = (
                            $aBalanceSheet[$this->aBalanceCodes['BL']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BN']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BP']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BR']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BT']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BV']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BX']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BZ']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['CB']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['CH']['id_balance_type']]
                            - (
                                $aBalanceSheet[$this->aBalanceCodes['DV']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['DW']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['DX']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['DY']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['DZ']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['EA']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['EB']['id_balance_type']]
                                + $aBalanceSheet[$this->aBalanceCodes['ED']['id_balance_type']]
                                - $aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']]
                            )
                        ) / ($aBalanceSheet[$this->aBalanceCodes['FL']['id_balance_type']] * (1 + $this->fVATRate)) * 360;

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>Trésorerie nette</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber =
                        $aBalanceSheet[$this->aBalanceCodes['CF']['id_balance_type']]
                        + $aBalanceSheet[$this->aBalanceCodes['CD']['id_balance_type']]
                        - $aBalanceSheet[$this->aBalanceCodes['EH']['id_balance_type']];

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>Stocks (en jours de CA)</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = (
                            $aBalanceSheet[$this->aBalanceCodes['BL']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BN']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BP']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BR']['id_balance_type']]
                            + $aBalanceSheet[$this->aBalanceCodes['BT']['id_balance_type']]
                        ) / $aBalanceSheet[$this->aBalanceCodes['FL']['id_balance_type']] * 360;

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>Créances clients (en jours de CA)</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = $aBalanceSheet[$this->aBalanceCodes['BX']['id_balance_type']] / ($aBalanceSheet[$this->aBalanceCodes['FL']['id_balance_type']] * (1 + $this->fVATRate)) * 360;

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>Dettes fournisseurs (en jours de CA)</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = ($aBalanceSheet[$this->aBalanceCodes['DW']['id_balance_type']] + $aBalanceSheet[$this->aBalanceCodes['DX']['id_balance_type']]) / ($aBalanceSheet[$this->aBalanceCodes['FL']['id_balance_type']] * (1 + $this->fVATRate)) * 360;

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>Écart créances clients - Dettes fournisseurs</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = (
                            $aBalanceSheet[$this->aBalanceCodes['BX']['id_balance_type']]
                            - $aBalanceSheet[$this->aBalanceCodes['DW']['id_balance_type']]
                            - $aBalanceSheet[$this->aBalanceCodes['DX']['id_balance_type']]
                        ) / ($aBalanceSheet[$this->aBalanceCodes['FL']['id_balance_type']] * (1 + $this->fVATRate)) * 360;

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
        </tbody>
    </table>
    <br/>
    <table class="tablesorter annual-accounts">
        <thead style="text-align:center;">
                <tr>
                    <th>Rentabilité</th>
                    <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
                        <th width="200"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
                        <?php if ($aAnnualAccounts['id_bilan'] != $iOldestAnnualAccountsId) { ?><th width="50"></th><?php } ?>
                    <?php endforeach; ?>
                </tr>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>EBE / CA</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = $aGrossOperatingSurplus[$iBalanceSheetId] / $aBalanceSheet[$this->aBalanceCodes['FL']['id_balance_type']];

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>Résultat net / CA</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = $aBalanceSheet[$this->aBalanceCodes['HN']['id_balance_type']] / $aBalanceSheet[$this->aBalanceCodes['FL']['id_balance_type']];

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>Disponibilités / Total bilan net</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = $aBalanceSheet[$this->aBalanceCodes['CF']['id_balance_type']] / $aBalanceTotal[$iBalanceSheetId];

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>Rentabilité des capitaux</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = $aBalanceSheet[$this->aBalanceCodes['DL']['id_balance_type']] / $aBalanceSheet[$this->aBalanceCodes['DA']['id_balance_type']];

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? '-' : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
        </tbody>
    </table>
</div>
