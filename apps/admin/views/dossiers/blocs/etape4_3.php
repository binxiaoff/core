<?php
$iBalanceSheetsCount     = count($this->aBalanceSheets);
$aOperationalCashFlow    = array();
$aGrossOperatingSurplus  = array();
$aMediumLongTermDebt     = array();
$aBalanceTotal           = array();
$aAnnualAccountsYears    = array_keys($this->aBalanceSheets);
$iLastAnnualAccountsId   = current($aAnnualAccountsYears);
$iOldestAnnualAccountsId = end($aAnnualAccountsYears);

foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
    $aOperationalCashFlow[$iBalanceSheetId] =
        $aBalanceSheet['details']['HN']
        - $aBalanceSheet['details']['FP']
        + $aBalanceSheet['details']['GA']
        + $aBalanceSheet['details']['GB']
        + $aBalanceSheet['details']['GC']
        + $aBalanceSheet['details']['GD']
        - $aBalanceSheet['details']['GM']
        + $aBalanceSheet['details']['GQ']
        - $aBalanceSheet['details']['HB']
        - $aBalanceSheet['details']['HC']
        + $aBalanceSheet['details']['HF']
        + $aBalanceSheet['details']['HG'];

    $aGrossOperatingSurplus[$iBalanceSheetId] =
        $aBalanceSheet['details']['GG']
        + $aBalanceSheet['details']['GA']
        + $aBalanceSheet['details']['GB']
        + $aBalanceSheet['details']['GC']
        + $aBalanceSheet['details']['GD']
        - $aBalanceSheet['details']['FP']
        - $aBalanceSheet['details']['FQ']
        + $aBalanceSheet['details']['GE'];

    $aMediumLongTermDebt[$iBalanceSheetId] =
        $aBalanceSheet['details']['DS']
        + $aBalanceSheet['details']['DT']
        + $aBalanceSheet['details']['DU']
        + $aBalanceSheet['details']['DV']
        - $aBalanceSheet['details']['EH']
        - $aBalanceSheet['details']['VI'];

    $aBalanceTotal[$iBalanceSheetId] =
        $aBalanceSheet['details']['AN']
        + $aBalanceSheet['details']['AP']
        + $aBalanceSheet['details']['AR']
        + $aBalanceSheet['details']['AT']
        + $aBalanceSheet['details']['AV']
        + $aBalanceSheet['details']['AX']
        + $aBalanceSheet['details']['AB']
        + $aBalanceSheet['details']['AD']
        + $aBalanceSheet['details']['AF']
        + $aBalanceSheet['details']['AH']
        + $aBalanceSheet['details']['AJ']
        + $aBalanceSheet['details']['AL']
        + $aBalanceSheet['details']['CS']
        + $aBalanceSheet['details']['CU']
        + $aBalanceSheet['details']['BB']
        + $aBalanceSheet['details']['BD']
        + $aBalanceSheet['details']['BF']
        + $aBalanceSheet['details']['BH']
        + $aBalanceSheet['details']['BL']
        + $aBalanceSheet['details']['BN']
        + $aBalanceSheet['details']['BP']
        + $aBalanceSheet['details']['BR']
        + $aBalanceSheet['details']['BT']
        + $aBalanceSheet['details']['BV']
        + $aBalanceSheet['details']['BX']
        + $aBalanceSheet['details']['BZ']
        + $aBalanceSheet['details']['CB']
        + $aBalanceSheet['details']['CH']
        + $aBalanceSheet['details']['CF']
        + $aBalanceSheet['details']['CD']
        - $aBalanceSheet['details']['BK']
        - $aBalanceSheet['details']['CK'];

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
                        $aBalanceSheet['details']['DS']
                        + $aBalanceSheet['details']['DT']
                        + $aBalanceSheet['details']['DU']
                        + $aBalanceSheet['details']['DV']
                        - $aBalanceSheet['details']['CF']
                        - $aBalanceSheet['details']['CD']
                        - $aBalanceSheet['details']['EH']
                        - $aBalanceSheet['details']['VI'];

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
                    <td><?= $this->ficelle->formatNumber($aOperationalCashFlow[$iBalanceSheetId] - $this->aBalanceSheets[$iBalanceSheetId]['details']['VH2'], 0) ?>&nbsp;€</td>
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
                    <?php list($iLastOperationalCashFlow, $iPreviousOperationalCashFlow, $iSecondToLastOperationalCashFlow) = array_values($aOperationalCashFlow); ?>
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
                    $iCurrentNumber = empty($aOperationalCashFlow[$iBalanceSheetId]) ? '-' : round($aMediumLongTermDebt[$iBalanceSheetId] / $aOperationalCashFlow[$iBalanceSheetId], 1);

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
                    $iCurrentNumber = empty($aGrossOperatingSurplus[$iBalanceSheetId]) ? '-' : round($aMediumLongTermDebt[$iBalanceSheetId] / $aGrossOperatingSurplus[$iBalanceSheetId], 1);

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
                    $iDivisor = $aBalanceSheet['details']['DW']
                        + $aBalanceSheet['details']['DX']
                        + $aBalanceSheet['details']['DY']
                        + $aBalanceSheet['details']['DZ']
                        + $aBalanceSheet['details']['EA']
                        + $aBalanceSheet['details']['EB']
                        + $aBalanceSheet['details']['EH']
                        - $aBalanceSheet['details']['VI'];
                    $iCurrentNumber = empty($iDivisor) ? '-' : round((
                            $aBalanceSheet['details']['BL']
                            + $aBalanceSheet['details']['BN']
                            + $aBalanceSheet['details']['BP']
                            + $aBalanceSheet['details']['BR']
                            + $aBalanceSheet['details']['BT']
                            + $aBalanceSheet['details']['BV']
                            + $aBalanceSheet['details']['BX']
                            + $aBalanceSheet['details']['BZ']
                            + $aBalanceSheet['details']['CB']
                            + $aBalanceSheet['details']['CH']
                        ) / $iDivisor, 1);

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
                    $iDivisor = $aBalanceSheet['details']['DW']
                        + $aBalanceSheet['details']['DX']
                        + $aBalanceSheet['details']['DY']
                        + $aBalanceSheet['details']['DZ']
                        + $aBalanceSheet['details']['EA']
                        + $aBalanceSheet['details']['EB']
                        + $aBalanceSheet['details']['EH']
                        - $aBalanceSheet['details']['VI'];
                    $iCurrentNumber = empty($iDivisor) ? '-' : round((
                            $aBalanceSheet['details']['BL']
                            + $aBalanceSheet['details']['BN']
                            + $aBalanceSheet['details']['BP']
                            + $aBalanceSheet['details']['BR']
                            + $aBalanceSheet['details']['BT']
                            + $aBalanceSheet['details']['BV']
                            + $aBalanceSheet['details']['BX']
                            + $aBalanceSheet['details']['BZ']
                            + $aBalanceSheet['details']['CB']
                            + $aBalanceSheet['details']['CH']
                            + $aBalanceSheet['details']['CF']
                            + $aBalanceSheet['details']['CD']
                        ) / $iDivisor, 1);

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
                    $iDivisor = $aBalanceSheet['details']['DW']
                        + $aBalanceSheet['details']['DX']
                        + $aBalanceSheet['details']['DY']
                        + $aBalanceSheet['details']['DZ']
                        + $aBalanceSheet['details']['EA']
                        + $aBalanceSheet['details']['EB']
                        + $aBalanceSheet['details']['EH']
                        - $aBalanceSheet['details']['VI'];
                    $iCurrentNumber = empty($iDivisor) ? '-' : round((
                            $aBalanceSheet['details']['BV']
                            + $aBalanceSheet['details']['BX']
                            + $aBalanceSheet['details']['BZ']
                            + $aBalanceSheet['details']['CB']
                            + $aBalanceSheet['details']['CH']
                            + $aBalanceSheet['details']['CF']
                            + $aBalanceSheet['details']['CD']
                        ) / $iDivisor, 1);

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
                            $aBalanceSheet['details']['DL']
                            + $aBalanceSheet['details']['DO']
                        ) / $aBalanceTotal[$iBalanceSheetId] * 100);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 0) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>Quasi FP / Total bilan net (%)</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = empty($aBalanceTotal[$iBalanceSheetId]) ? '-' : round((
                            $aBalanceSheet['details']['DL']
                            + $aBalanceSheet['details']['DO']
                            + $aBalanceSheet['details']['EI']
                            + $aBalanceSheet['details']['VI']
                        ) / $aBalanceTotal[$iBalanceSheetId] * 100);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 0) ?></td>
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
                    $iCurrentNumber = empty($aBalanceTotal[$iBalanceSheetId]) ? '-' : round($aMediumLongTermDebt[$iBalanceSheetId] / $aBalanceTotal[$iBalanceSheetId] * 100, 1);

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
                <td>DMLT / Quasi FP (%)</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iDivisor = $aBalanceSheet['details']['DL']
                        + $aBalanceSheet['details']['DO']
                        + $aBalanceSheet['details']['EI']
                        + $aBalanceSheet['details']['VI'];
                    $iCurrentNumber = empty($iDivisor) ? '-' : round($aMediumLongTermDebt[$iBalanceSheetId] / $iDivisor * 100, 1);

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
                    $iDivisor = $aBalanceSheet['details']['DL']
                        + $aBalanceSheet['details']['DO']
                        + $aBalanceSheet['details']['VI'];
                    $iCurrentNumber = empty($iDivisor) ? '-' : round((
                            $aBalanceSheet['details']['DS']
                            + $aBalanceSheet['details']['DT']
                            + $aBalanceSheet['details']['DU']
                            + $aBalanceSheet['details']['DV']
                            - $aBalanceSheet['details']['CF']
                            - $aBalanceSheet['details']['CD']
                            - $aBalanceSheet['details']['EH']
                            - $aBalanceSheet['details']['VI']
                        ) / $iDivisor * 100);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 0) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>Charges fi / EBE (%)</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iDivisor = $aBalanceSheet['details']['GG']
                        + $aBalanceSheet['details']['GA']
                        + $aBalanceSheet['details']['GB']
                        + $aBalanceSheet['details']['GC']
                        + $aBalanceSheet['details']['GD']
                        - $aBalanceSheet['details']['FP']
                        - $aBalanceSheet['details']['FQ']
                        + $aBalanceSheet['details']['GE'];
                    $iCurrentNumber = empty($iDivisor) ? '-' : round(abs($aBalanceSheet['details']['GU']) / $iDivisor * 100);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 0) ?></td>
                    <?php
                    $iPreviousNumber = $iCurrentNumber;
                }
                ?>
            </tr>
            <tr>
                <td>Charges fi / Résultat net (%)</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = empty($aBalanceSheet['details']['HN']) ? '-' : round(abs($aBalanceSheet['details']['GU']) / $aBalanceSheet['details']['HN'] * 100);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 0) ?></td>
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
                    <td><?= $this->ficelle->formatNumber($this->aBalanceSheets[$iLastAnnualAccountsId]['VI'], 0) ?></td>
                    <?php
                } elseif (2 <= $iBalanceSheetsCount) {
                    $iLastNumber = $this->aBalanceSheets[$iLastAnnualAccountsId]['details']['VI'];
                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if ($iBalanceSheetId !== $iLastAnnualAccountsId) {
                            ?>
                            <td><?= $this->ficelle->formatNumber(min($iLastNumber, $aBalanceSheet['details']['VI']), 0) ?>&nbsp;€</td>
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
                            $aBalanceSheet['details']['DL']
                            + $aBalanceSheet['details']['DO']
                            + $aBalanceSheet['details']['DU']
                            - $aBalanceSheet['details']['EH']
                            + $aBalanceSheet['details']['VI']
                            - (
                                $aBalanceSheet['details']['CS']
                                + $aBalanceSheet['details']['CU']
                                + $aBalanceSheet['details']['BB']
                                + $aBalanceSheet['details']['BD']
                                + $aBalanceSheet['details']['BF']
                                + $aBalanceSheet['details']['BH']
                                + $aBalanceSheet['details']['AN']
                                + $aBalanceSheet['details']['AP']
                                + $aBalanceSheet['details']['AR']
                                + $aBalanceSheet['details']['AT']
                                + $aBalanceSheet['details']['AV']
                                + $aBalanceSheet['details']['AX']
                                + $aBalanceSheet['details']['AB']
                                + $aBalanceSheet['details']['AD']
                                + $aBalanceSheet['details']['AF']
                                + $aBalanceSheet['details']['AH']
                                + $aBalanceSheet['details']['AJ']
                                + $aBalanceSheet['details']['AL']
                                - $aBalanceSheet['details']['BK']
                            );

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 0) ?></td>
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
                    $iDivisor       = $aBalanceSheet['details']['FL'] * (1 + $this->fVATRate);
                    $iCurrentNumber = empty($iDivisor) ? '-' : round((
                            $aBalanceSheet['details']['DL']
                            + $aBalanceSheet['details']['DO']
                            + $aBalanceSheet['details']['DU']
                            - $aBalanceSheet['details']['EH']
                            + $aBalanceSheet['details']['VI']
                            - (
                                $aBalanceSheet['details']['CS']
                                + $aBalanceSheet['details']['CU']
                                + $aBalanceSheet['details']['BB']
                                + $aBalanceSheet['details']['BD']
                                + $aBalanceSheet['details']['BF']
                                + $aBalanceSheet['details']['BH']
                                + $aBalanceSheet['details']['AN']
                                + $aBalanceSheet['details']['AP']
                                + $aBalanceSheet['details']['AR']
                                + $aBalanceSheet['details']['AT']
                                + $aBalanceSheet['details']['AV']
                                + $aBalanceSheet['details']['AX']
                                + $aBalanceSheet['details']['AB']
                                + $aBalanceSheet['details']['AD']
                                + $aBalanceSheet['details']['AF']
                                + $aBalanceSheet['details']['AH']
                                + $aBalanceSheet['details']['AJ']
                                + $aBalanceSheet['details']['AL']
                                - $aBalanceSheet['details']['BK']
                            )
                        ) / $iDivisor * 360);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 0) ?></td>
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
                        $aBalanceSheet['details']['BL']
                        + $aBalanceSheet['details']['BN']
                        + $aBalanceSheet['details']['BP']
                        + $aBalanceSheet['details']['BR']
                        + $aBalanceSheet['details']['BT']
                        + $aBalanceSheet['details']['BV']
                        + $aBalanceSheet['details']['BX']
                        + $aBalanceSheet['details']['BZ']
                        + $aBalanceSheet['details']['CB']
                        + $aBalanceSheet['details']['CH']
                        - (
                            $aBalanceSheet['details']['DV']
                            + $aBalanceSheet['details']['DW']
                            + $aBalanceSheet['details']['DX']
                            + $aBalanceSheet['details']['DY']
                            + $aBalanceSheet['details']['DZ']
                            + $aBalanceSheet['details']['EA']
                            + $aBalanceSheet['details']['EB']
                            + $aBalanceSheet['details']['ED']
                            - $aBalanceSheet['details']['VI']
                        );

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 0) ?></td>
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
                    $iDivisor       = $aBalanceSheet['details']['FL'] * (1 + $this->fVATRate);
                    $iCurrentNumber = empty($iDivisor) ? '-' : round((
                            $aBalanceSheet['details']['BL']
                            + $aBalanceSheet['details']['BN']
                            + $aBalanceSheet['details']['BP']
                            + $aBalanceSheet['details']['BR']
                            + $aBalanceSheet['details']['BT']
                            + $aBalanceSheet['details']['BV']
                            + $aBalanceSheet['details']['BX']
                            + $aBalanceSheet['details']['BZ']
                            + $aBalanceSheet['details']['CB']
                            + $aBalanceSheet['details']['CH']
                            - (
                                $aBalanceSheet['details']['DV']
                                + $aBalanceSheet['details']['DW']
                                + $aBalanceSheet['details']['DX']
                                + $aBalanceSheet['details']['DY']
                                + $aBalanceSheet['details']['DZ']
                                + $aBalanceSheet['details']['EA']
                                + $aBalanceSheet['details']['EB']
                                + $aBalanceSheet['details']['ED']
                                - $aBalanceSheet['details']['VI']
                            )
                        ) / $iDivisor * 360);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 0) ?></td>
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
                        $aBalanceSheet['details']['CF']
                        + $aBalanceSheet['details']['CD']
                        - $aBalanceSheet['details']['EH'];

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 0) ?></td>
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
                    $iCurrentNumber = empty($aBalanceSheet['details']['FL']) ? '-' : round((
                            $aBalanceSheet['details']['BL']
                            + $aBalanceSheet['details']['BN']
                            + $aBalanceSheet['details']['BP']
                            + $aBalanceSheet['details']['BR']
                            + $aBalanceSheet['details']['BT']
                        ) / $aBalanceSheet['details']['FL'] * 360);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 0) ?></td>
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
                    $iDivisor       = $aBalanceSheet['details']['FL'] * (1 + $this->fVATRate);
                    $iCurrentNumber = empty($iDivisor) ? '-' : round($aBalanceSheet['details']['BX'] / $iDivisor * 360);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 0) ?></td>
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
                    $iDivisor       = $aBalanceSheet['details']['FL'] * (1 + $this->fVATRate);
                    $iCurrentNumber = empty($iDivisor) ? '-' : round(($aBalanceSheet['details']['DW'] + $aBalanceSheet['details']['DX']) / $iDivisor * 360);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 0) ?></td>
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
                    $iDivisor       = $aBalanceSheet['details']['FL'] * (1 + $this->fVATRate);
                    $iCurrentNumber = empty($iDivisor) ? '-' : round((
                            $aBalanceSheet['details']['BX']
                            - $aBalanceSheet['details']['DW']
                            - $aBalanceSheet['details']['DX']
                        ) / $iDivisor * 360);

                    if (false === is_null($iPreviousNumber)) {
                        ?>
                        <td><?= empty($iCurrentNumber) || $iCurrentNumber === '-' ? 'N/A' : round(($iPreviousNumber - $iCurrentNumber) / abs($iCurrentNumber) * 100) . '&nbsp;%' ?></td>
                        <?php
                    }
                    ?>
                    <td><?= $iCurrentNumber === '-' ? 'N/A' : $this->ficelle->formatNumber($iCurrentNumber, 0) ?></td>
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
                <td>EBE / CA (%)</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = empty($aBalanceSheet['details']['FL']) ? '-' : round($aGrossOperatingSurplus[$iBalanceSheetId] / $aBalanceSheet['details']['FL'] * 100, 1);

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
                <td>Résultat net / CA (%)</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = empty($aBalanceSheet['details']['FL']) ? '-' : round($aBalanceSheet['details']['HN'] / $aBalanceSheet['details']['FL'] * 100, 1);

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
                <td>Disponibilités / Total bilan net (%)</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = empty($aBalanceTotal[$iBalanceSheetId]) ? '-' : round($aBalanceSheet['details']['CF'] / $aBalanceTotal[$iBalanceSheetId] * 100, 1);

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
                <td>Rentabilité des capitaux (%)</td>
                <?php
                $iPreviousNumber = null;

                foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                    $iCurrentNumber = empty($aBalanceSheet['details']['DA']) ? '-' : round($aBalanceSheet['details']['DL'] / $aBalanceSheet['details']['DA'] * 100, 1);

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
</div>
