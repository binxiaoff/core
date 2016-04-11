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
        $aBalanceSheet['HN']
        - $aBalanceSheet['FP']
        + $aBalanceSheet['GA']
        + $aBalanceSheet['GB']
        + $aBalanceSheet['GC']
        + $aBalanceSheet['GD']
        - $aBalanceSheet['GM']
        + $aBalanceSheet['GQ']
        - $aBalanceSheet['HB']
        - $aBalanceSheet['HC']
        + $aBalanceSheet['HF']
        + $aBalanceSheet['HG'];

    $aGrossOperatingSurplus[$iBalanceSheetId] =
        $aBalanceSheet['GG']
        + $aBalanceSheet['GA']
        + $aBalanceSheet['GB']
        + $aBalanceSheet['GC']
        + $aBalanceSheet['GD']
        - $aBalanceSheet['FP']
        - $aBalanceSheet['FQ']
        + $aBalanceSheet['GE'];

    $aMediumLongTermDebt[$iBalanceSheetId] =
        $aBalanceSheet['DS']
        + $aBalanceSheet['DT']
        + $aBalanceSheet['DU']
        + $aBalanceSheet['DV']
        - $aBalanceSheet['EH']
        - $aBalanceSheet['VI'];

    $aBalanceTotal[$iBalanceSheetId] =
        $aBalanceSheet['AN']
        + $aBalanceSheet['AP']
        + $aBalanceSheet['AR']
        + $aBalanceSheet['AT']
        + $aBalanceSheet['AV']
        + $aBalanceSheet['AX']
        + $aBalanceSheet['AB']
        + $aBalanceSheet['AD']
        + $aBalanceSheet['AF']
        + $aBalanceSheet['AH']
        + $aBalanceSheet['AJ']
        + $aBalanceSheet['AL']
        + $aBalanceSheet['CS']
        + $aBalanceSheet['CU']
        + $aBalanceSheet['BB']
        + $aBalanceSheet['BD']
        + $aBalanceSheet['BF']
        + $aBalanceSheet['BH']
        + $aBalanceSheet['BL']
        + $aBalanceSheet['BN']
        + $aBalanceSheet['BP']
        + $aBalanceSheet['BR']
        + $aBalanceSheet['BT']
        + $aBalanceSheet['BV']
        + $aBalanceSheet['BX']
        + $aBalanceSheet['BZ']
        + $aBalanceSheet['CB']
        + $aBalanceSheet['CH']
        + $aBalanceSheet['CF']
        + $aBalanceSheet['CD'];

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
                        $aBalanceSheet['DS']
                        + $aBalanceSheet['DT']
                        + $aBalanceSheet['DU']
                        + $aBalanceSheet['DV']
                        - $aBalanceSheet['CF']
                        - $aBalanceSheet['CD']
                        - $aBalanceSheet['EH']
                        - $aBalanceSheet['VI'];

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
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
                        <td><?= empty($iCurrentNumber) ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
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
                    <td><?= $this->ficelle->formatNumber($aOperationalCashFlow[$iBalanceSheetId] - $this->aBalanceSheets[$iBalanceSheetId]['VH'], 0) ?>&nbsp;€</td>
                    <?php
                    break;
                }
                ?>
                <?php if ($iBalanceSheetsCount > 1) : ?>
                    <?= str_repeat('<td></td>', 2 * ($iBalanceSheetsCount - 1)) ?>
                <?php endif; ?>
            </tr>
            <tr>
                <td>CAF moyenne pondérée sur 3 ans</td>
                <?php if (3 === $iBalanceSheetsCount) : ?>
                    <?php list($iSecondToLastOperationalCashFlow, $iPreviousOperationalCashFlow, $iLastOperationalCashFlow) = array_values($aOperationalCashFlow); ?>
                    <td><?= $this->ficelle->formatNumber((2 * $iLastOperationalCashFlow + $iPreviousOperationalCashFlow + 0.5 * $iSecondToLastOperationalCashFlow) / 3.5, 0) ?>&nbsp;€</td>
                <?php elseif ($iBalanceSheetsCount > 0) : ?>
                    <td>N/A</td>
                <?php endif; ?>
                <?php if ($iBalanceSheetsCount > 1) : ?>
                    <?= str_repeat('<td></td>', 2 * ($iBalanceSheetsCount - 1)) ?>
                <?php endif; ?>
            </tr>
            <tr>
                <!-- Dette moyen long terme -->
                <td>DMLT / CAF</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = empty($aOperationalCashFlow[$iBalanceSheetId]) ? '-' : round($aMediumLongTermDebt[$iBalanceSheetId] / $aOperationalCashFlow[$iBalanceSheetId]);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iCurrentNumber = empty($aGrossOperatingSurplus[$iBalanceSheetId]) ? '-' : round($aMediumLongTermDebt[$iBalanceSheetId] / $aGrossOperatingSurplus[$iBalanceSheetId]);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iDivisor = $aBalanceSheet['DW']
                        + $aBalanceSheet['DX']
                        + $aBalanceSheet['EH']
                        - $aBalanceSheet['VI'];
                    $iCurrentNumber = empty($iDivisor) ? '-' : round((
                            $aBalanceSheet['BL']
                            + $aBalanceSheet['BN']
                            + $aBalanceSheet['BP']
                            + $aBalanceSheet['BR']
                            + $aBalanceSheet['BT']
                            + $aBalanceSheet['BV']
                            + $aBalanceSheet['BX']
                            + $aBalanceSheet['BZ']
                            + $aBalanceSheet['CB']
                            + $aBalanceSheet['CH']
                        ) / $iDivisor);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iDivisor = $aBalanceSheet['DW']
                        + $aBalanceSheet['DX']
                        + $aBalanceSheet['DY']
                        + $aBalanceSheet['DZ']
                        + $aBalanceSheet['EA']
                        + $aBalanceSheet['EB']
                        + $aBalanceSheet['ED']
                        - $aBalanceSheet['VI'];
                    $iCurrentNumber = empty($iDivisor) ? '-' : round((
                            $aBalanceSheet['BL']
                            + $aBalanceSheet['BN']
                            + $aBalanceSheet['BP']
                            + $aBalanceSheet['BR']
                            + $aBalanceSheet['BT']
                            + $aBalanceSheet['BV']
                            + $aBalanceSheet['BX']
                            + $aBalanceSheet['BZ']
                            + $aBalanceSheet['CB']
                            + $aBalanceSheet['CH']
                            + $aBalanceSheet['CF']
                            + $aBalanceSheet['CD']
                        ) / $iDivisor);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iDivisor = $aBalanceSheet['DW']
                        + $aBalanceSheet['DX']
                        + $aBalanceSheet['DY']
                        + $aBalanceSheet['DZ']
                        + $aBalanceSheet['EA']
                        + $aBalanceSheet['EB']
                        + $aBalanceSheet['ED']
                        - $aBalanceSheet['VI'];
                    $iCurrentNumber = empty($iDivisor) ? '-' : round((
                            $aBalanceSheet['BV']
                            + $aBalanceSheet['BX']
                            + $aBalanceSheet['BZ']
                            + $aBalanceSheet['CB']
                            + $aBalanceSheet['CH']
                            + $aBalanceSheet['CF']
                            + $aBalanceSheet['CD']
                        ) / $iDivisor);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iCurrentNumber = empty($aBalanceTotal[$iBalanceSheetId]) ? '-' : round((
                            $aBalanceSheet['DL']
                            + $aBalanceSheet['DO']
                        ) / $aBalanceTotal[$iBalanceSheetId] * 100);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iCurrentNumber = empty($aBalanceTotal[$iBalanceSheetId]) ? '-' : round((
                            $aBalanceSheet['DL']
                            + $aBalanceSheet['DO']
                            + $aBalanceSheet['EI']
                            + $aBalanceSheet['VI']
                        ) / $aBalanceTotal[$iBalanceSheetId]);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iCurrentNumber = empty($aBalanceTotal[$iBalanceSheetId]) ? '-' : round($aMediumLongTermDebt[$iBalanceSheetId] / $aBalanceTotal[$iBalanceSheetId] * 100);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iDivisor = $aBalanceSheet['DL']
                        + $aBalanceSheet['DO']
                        + $aBalanceSheet['EI']
                        + $aBalanceSheet['VI'];
                    $iCurrentNumber = empty($iDivisor) ? '-' : round($aMediumLongTermDebt[$iBalanceSheetId] / $iDivisor);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iCurrentNumber = empty($aBalanceTotal[$iBalanceSheetId]) ? '-' : round($aMediumLongTermDebt[$iBalanceSheetId] / $aBalanceTotal[$iBalanceSheetId]);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iDivisor = $aBalanceSheet['DL']
                        + $aBalanceSheet['DO']
                        + $aBalanceSheet['VI'];
                    $iCurrentNumber = empty($iDivisor) ? '-' : round((
                            $aBalanceSheet['DS']
                            + $aBalanceSheet['DT']
                            + $aBalanceSheet['DU']
                            + $aBalanceSheet['DV']
                            - $aBalanceSheet['CF']
                            - $aBalanceSheet['CD']
                            - $aBalanceSheet['EH']
                            - $aBalanceSheet['VI']
                        ) / $iDivisor * 100);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iDivisor = $aBalanceSheet['GG']
                        + $aBalanceSheet['GA']
                        + $aBalanceSheet['GB']
                        + $aBalanceSheet['GC']
                        + $aBalanceSheet['GD']
                        - $aBalanceSheet['FP']
                        - $aBalanceSheet['FQ']
                        + $aBalanceSheet['GE'];
                    $iCurrentNumber = empty($iDivisor) ? '-' : round(abs($aBalanceSheet['GU']) / $iDivisor);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iCurrentNumber = empty($aBalanceSheet['HN']) ? '-' : round(abs($aBalanceSheet['GU']) / $aBalanceSheet['HN']);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    <td><?= $this->ficelle->formatNumber($this->aBalanceSheets[$iLastAnnualAccountsId]['VI'], 1) ?></td>
                    <?php
                } elseif (2 <= $iBalanceSheetsCount) {
                    $iLastNumber = $this->aBalanceSheets[$iLastAnnualAccountsId]['VI'];
                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if ($iBalanceSheetId !== $iLastAnnualAccountsId) {
                            ?>
                            <td><?= $this->ficelle->formatNumber(min($iLastNumber, $aBalanceSheet['VI']), 1) ?>&nbsp;€</td>
                            <?php
                            break;
                        }
                    }
                }
                ?>
                <?php if ($iBalanceSheetsCount > 1) : ?>
                    <?= str_repeat('<td></td>', 2 * ($iBalanceSheetsCount - 1)) ?>
                <?php endif; ?>
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
                            $aBalanceSheet['DL']
                            + $aBalanceSheet['DO']
                            + $aBalanceSheet['DU']
                            - $aBalanceSheet['EH']
                            + $aBalanceSheet['VI']
                            - (
                                $aBalanceSheet['CS']
                                + $aBalanceSheet['CU']
                                + $aBalanceSheet['BB']
                                + $aBalanceSheet['BD']
                                + $aBalanceSheet['BF']
                                + $aBalanceSheet['BH']
                                + $aBalanceSheet['AN']
                                + $aBalanceSheet['AP']
                                + $aBalanceSheet['AR']
                                + $aBalanceSheet['AT']
                                + $aBalanceSheet['AV']
                                + $aBalanceSheet['AX']
                                + $aBalanceSheet['AB']
                                + $aBalanceSheet['AD']
                                + $aBalanceSheet['AF']
                                + $aBalanceSheet['AH']
                                + $aBalanceSheet['AJ']
                                + $aBalanceSheet['AL']
                                - $aBalanceSheet['BK']
                            );

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iDivisor       = $aBalanceSheet['FL'] * (1 + $this->fVATRate);
                    $iCurrentNumber = empty($iDivisor) ? '-' : round((
                            $aBalanceSheet['DL']
                            + $aBalanceSheet['DO']
                            + $aBalanceSheet['DU']
                            - $aBalanceSheet['EH']
                            + $aBalanceSheet['VI']
                            - (
                                $aBalanceSheet['CS']
                                + $aBalanceSheet['CU']
                                + $aBalanceSheet['BB']
                                + $aBalanceSheet['BD']
                                + $aBalanceSheet['BF']
                                + $aBalanceSheet['BH']
                                + $aBalanceSheet['AN']
                                + $aBalanceSheet['AP']
                                + $aBalanceSheet['AR']
                                + $aBalanceSheet['AT']
                                + $aBalanceSheet['AV']
                                + $aBalanceSheet['AX']
                                + $aBalanceSheet['AB']
                                + $aBalanceSheet['AD']
                                + $aBalanceSheet['AF']
                                + $aBalanceSheet['AH']
                                + $aBalanceSheet['AJ']
                                + $aBalanceSheet['AL']
                                - $aBalanceSheet['BK']
                            )
                        ) / $iDivisor * 360);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                        $aBalanceSheet['BL']
                        + $aBalanceSheet['BN']
                        + $aBalanceSheet['BP']
                        + $aBalanceSheet['BR']
                        + $aBalanceSheet['BT']
                        + $aBalanceSheet['BV']
                        + $aBalanceSheet['BX']
                        + $aBalanceSheet['BZ']
                        + $aBalanceSheet['CB']
                        + $aBalanceSheet['CH']
                        - (
                            $aBalanceSheet['DV']
                            + $aBalanceSheet['DW']
                            + $aBalanceSheet['DX']
                            + $aBalanceSheet['DY']
                            + $aBalanceSheet['DZ']
                            + $aBalanceSheet['EA']
                            + $aBalanceSheet['EB']
                            + $aBalanceSheet['ED']
                            - $aBalanceSheet['VI']
                        );

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iDivisor       = $aBalanceSheet['FL'] * (1 + $this->fVATRate);
                    $iCurrentNumber = empty($iDivisor) ? '-' : round((
                            $aBalanceSheet['BL']
                            + $aBalanceSheet['BN']
                            + $aBalanceSheet['BP']
                            + $aBalanceSheet['BR']
                            + $aBalanceSheet['BT']
                            + $aBalanceSheet['BV']
                            + $aBalanceSheet['BX']
                            + $aBalanceSheet['BZ']
                            + $aBalanceSheet['CB']
                            + $aBalanceSheet['CH']
                            - (
                                $aBalanceSheet['DV']
                                + $aBalanceSheet['DW']
                                + $aBalanceSheet['DX']
                                + $aBalanceSheet['DY']
                                + $aBalanceSheet['DZ']
                                + $aBalanceSheet['EA']
                                + $aBalanceSheet['EB']
                                + $aBalanceSheet['ED']
                                - $aBalanceSheet['VI']
                            )
                        ) / $iDivisor * 360);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                        $aBalanceSheet['CF']
                        + $aBalanceSheet['CD']
                        - $aBalanceSheet['EH'];

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iCurrentNumber = empty($aBalanceSheet['FL']) ? '-' : round((
                            $aBalanceSheet['BL']
                            + $aBalanceSheet['BN']
                            + $aBalanceSheet['BP']
                            + $aBalanceSheet['BR']
                            + $aBalanceSheet['BT']
                        ) / $aBalanceSheet['FL'] * 360);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iDivisor       = $aBalanceSheet['FL'] * (1 + $this->fVATRate);
                    $iCurrentNumber = empty($iDivisor) ? '-' : round($aBalanceSheet['BX'] / $iDivisor * 360);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iDivisor       = $aBalanceSheet['FL'] * (1 + $this->fVATRate);
                    $iCurrentNumber = empty($iDivisor) ? '-' : round(($aBalanceSheet['DW'] + $aBalanceSheet['DX']) / $iDivisor * 360);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iDivisor       = $aBalanceSheet['FL'] * (1 + $this->fVATRate);
                    $iCurrentNumber = empty($iDivisor) ? '-' : round((
                            $aBalanceSheet['BX']
                            - $aBalanceSheet['DW']
                            - $aBalanceSheet['DX']
                        ) / $iDivisor * 360);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 1) ?></td>
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
                    $iCurrentNumber = empty($aBalanceSheet['FL']) ? '-' : round($aGrossOperatingSurplus[$iBalanceSheetId] / $aBalanceSheet['FL'] * 100);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 2) ?></td>
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
                    $iCurrentNumber = empty($aBalanceSheet['FL']) ? '-' : round($aBalanceSheet['HN'] / $aBalanceSheet['FL'] * 100);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 2) ?></td>
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
                    $iCurrentNumber = empty($aBalanceTotal[$iBalanceSheetId]) ? '-' : round($aBalanceSheet['CF'] / $aBalanceTotal[$iBalanceSheetId] * 100);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 2) ?></td>
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
                    $iCurrentNumber = empty($aBalanceSheet['DA']) ? '-' : round($aBalanceSheet['DL'] / $aBalanceSheet['DA'] * 100);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 2) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
        </tbody>
    </table>
</div>
