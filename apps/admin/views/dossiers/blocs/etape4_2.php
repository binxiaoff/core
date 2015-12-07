<?php $iLastAnnualAccountsId = end(array_keys($this->aBalanceSheets)); ?>
<script type="text/javascript">
    $(function() {
        $('#last_annual_accounts').change(function() {
            $('#last_annual_accounts_form').submit();
        });
    })
</script>
<div class="tab_title" id="title_etape4_2">Etape 4.2 - Bilans</div>
<div class="tab_content" id="etape4_2">
    <form action="/dossiers/edit/<?= $this->projects->id_project ?>" method="post">
        <input type="hidden" name="add_annual_accounts" value="1"/>
        <input type="submit" class="btn_link" value="Ajouter un bilan" style="float:right"/>
    </form>
    <form id="last_annual_accounts_form" action="/dossiers/edit/<?= $this->projects->id_project ?>" method="post">
        <h2>Dernier bilan</h2>
        <select id="last_annual_accounts" name="last_annual_accounts">
        <?php foreach ($this->aAllAnnualAccounts as $aAnnualAccounts): ?>
            <option value="<?= $aAnnualAccounts['id_bilan'] ?>"<?= $aAnnualAccounts['id_bilan'] == $this->projects->id_dernier_bilan ? ' selected' : '' ?>><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</option>
        <?php endforeach; ?>
        </select>
    </form>
    <br/>
    <form id="dossier_etape4_2" action="/dossiers/edit/<?= $this->projects->id_project ?>" method="post" onsubmit="return valid_etape4_2(<?= $this->projects->id_project ?>);">
        <table class="tablesorter annual-accounts" style="text-align:center;">
            <thead>
                <tr>
                    <th colspan="2">Actif</th>
                    <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
                        <th width="200"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
                        <?php if ($aAnnualAccounts['id_bilan'] != $iLastAnnualAccountsId) { ?><th width="50"></th><?php } ?>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <!-- Immobilisations incorporelles -->
                <tr>
                    <td>Capital souscrit non appelé</td>
                    <td>AA</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['AA']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['AA']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['AA']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['AA']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="AA[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['AA']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Frais d'établissement</td>
                    <td>AB</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['AB']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['AB']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['AB']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['AB']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="AB[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['AB']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Frais de développement</td>
                    <td>AD</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['AD']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['AD']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['AD']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['AD']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="AD[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['AD']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Concessions, brevets et droits similaires</td>
                    <td>AF</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['AF']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['AF']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['AF']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['AF']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="AF[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['AF']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Fonds commercial</td>
                    <td>AH</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['AH']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['AH']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['AH']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['AH']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="AH[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['AH']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Autres immos Incorpo</td>
                    <td>AJ</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['AJ']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['AJ']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['AJ']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['AJ']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="AJ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['AJ']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Avances et acomptes sur immos Incorpo</td>
                    <td>AL</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['AL']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['AL']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['AL']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['AL']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="AL[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['AL']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr class="sub-total">
                    <td colspan="2">Immobilisations incorporelles</td>
                    <?php
                    $iPreviousTotal = null;

                    foreach ($this->aBalanceSheets as $aBalanceSheet) {
                        $iTotal = $this->sumBalances(array('AB', 'AD', 'AF', 'AH', 'AJ', 'AL'), $aBalanceSheet);

                        if (false === is_null($iPreviousTotal)) {
                            ?>
                            <td><?= empty($iPreviousTotal) ? '-' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><?= $this->ficelle->formatNumber($iTotal, 0) ?>&nbsp;€</td>
                        <?php
                        $iPreviousTotal = $iTotal;
                    }
                    ?>
                </tr>
                <!-- Immobilisations corporelles -->
                <tr>
                    <td>Terrains</td>
                    <td>AN</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['AN']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['AN']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['AN']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['AN']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="AN[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['AN']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Constructions</td>
                    <td>AP</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['AP']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['AP']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['AP']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['AP']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="AP[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['AP']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>ITMOI</td>
                    <td>AR</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['AR']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['AR']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['AR']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['AR']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="AR[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['AR']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                <tr>
                    <td>Autres immo corpo</td>
                    <td>AT</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['AT']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['AT']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['AT']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['AT']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="AT[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['AT']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Immos en cours</td>
                    <td>AV</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['AV']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['AV']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['AV']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['AV']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="AV[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['AV']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Avances et acomptes sur immos corpo</td>
                    <td>AX</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['AX']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['AX']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['AX']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['AX']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="AX[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['AX']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr class="sub-total">
                    <td colspan="2">Immobilisations corporelles</td>
                    <?php
                    $iPreviousTotal = null;

                    foreach ($this->aBalanceSheets as $aBalanceSheet) {
                        $iTotal = $this->sumBalances(array('AN', 'AP', 'AR', 'AT', 'AV', 'AX'), $aBalanceSheet);

                        if (false === is_null($iPreviousTotal)) {
                            ?>
                            <td><?= empty($iPreviousTotal) ? '-' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><?= $this->ficelle->formatNumber($iTotal, 0) ?>&nbsp;€</td>
                        <?php
                        $iPreviousTotal = $iTotal;
                    }
                    ?>
                </tr>
                <!-- Immobilisations financières -->
                <tr>
                    <td>Participations évaluées selon la méthode</td>
                    <td>CS</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['CS']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['CS']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['CS']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['CS']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="CS[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['CS']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Autres participations</td>
                    <td>CU</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['CU']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['CU']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['CU']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['CU']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="CU[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['CU']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Créances rattachées à des participations</td>
                    <td>BB</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['BB']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['BB']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['BB']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['BB']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="BB[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['BB']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Autres titres immobilisés</td>
                    <td>BD</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['BD']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['BD']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['BD']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['BD']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="BD[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['BD']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Prêts</td>
                    <td>BF</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['BF']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['BF']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['BF']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['BF']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="BF[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['BF']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Autres immobilisations financières</td>
                    <td>BH</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['BH']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['BH']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['BH']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['BH']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="BH[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['BH']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr class="sub-total">
                    <td colspan="2">Immobilisations financières</td>
                    <?php
                    $iPreviousTotal = null;

                    foreach ($this->aBalanceSheets as $aBalanceSheet) {
                        $iTotal = $this->sumBalances(array('CS', 'CU', 'BB', 'BD', 'BF', 'BH'), $aBalanceSheet);

                        if (false === is_null($iPreviousTotal)) {
                            ?>
                            <td><?= empty($iPreviousTotal) ? '-' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><?= $this->ficelle->formatNumber($iTotal, 0) ?>&nbsp;€</td>
                        <?php
                        $iPreviousTotal = $iTotal;
                    }
                    ?>
                </tr>
                <!-- Stocks -->
                <tr>
                    <td>Total actif immobilisé</td>
                    <td>BJ</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['BJ']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['BJ']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['BJ']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['BJ']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="BJ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['BJ']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Matières premières</td>
                    <td>BL</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['BL']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['BL']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['BL']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['BL']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="BL[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['BL']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>En-cours de bien</td>
                    <td>BN</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['BN']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['BN']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['BN']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['BN']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="BN[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['BN']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>En-cours de services</td>
                    <td>BP</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['BP']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['BP']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['BP']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['BP']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="BP[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['BP']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Produits Intermédiaires et finis</td>
                    <td>BR</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['BR']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['BR']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['BR']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['BR']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="BR[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['BR']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Marchandises</td>
                    <td>BT</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['BT']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['BT']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['BT']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['BT']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="BT[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['BT']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr class="sub-total">
                    <td colspan="2">Stocks</td>
                    <?php
                    $iPreviousTotal = null;

                    foreach ($this->aBalanceSheets as $aBalanceSheet) {
                        $iTotal = $this->sumBalances(array('BL', 'BN', 'BP', 'BR', 'BT'), $aBalanceSheet);

                        if (false === is_null($iPreviousTotal)) {
                            ?>
                            <td><?= empty($iPreviousTotal) ? '-' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><?= $this->ficelle->formatNumber($iTotal, 0) ?>&nbsp;€</td>
                        <?php
                        $iPreviousTotal = $iTotal;
                    }
                    ?>
                </tr>
                <!-- Créances clients et autres -->
                <tr>
                    <td>Avances et acomptes versés sur commande</td>
                    <td>BV</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['BV']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['BV']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['BV']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['BV']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="BV[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['BV']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Clients et comptes rattachés</td>
                    <td>BX</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['BX']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['BX']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['BX']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['BX']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="BX[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['BX']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Autres créances + K souscrit non appelé</td>
                    <td>BZ</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['BZ']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['BZ']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['BZ']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['BZ']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="BZ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['BZ']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Capital souscrit appelé non versé</td>
                    <td>CB</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['CB']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['CB']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['CB']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['CB']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="CB[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['CB']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr class="sub-total">
                    <td colspan="2">Créances clients et autres</td>
                    <?php
                    $iPreviousTotal = null;

                    foreach ($this->aBalanceSheets as $aBalanceSheet) {
                        $iTotal = $this->sumBalances(array('BV', 'BX', 'BZ', 'CB'), $aBalanceSheet);

                        if (false === is_null($iPreviousTotal)) {
                            ?>
                            <td><?= empty($iPreviousTotal) ? '-' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><?= $this->ficelle->formatNumber($iTotal, 0) ?>&nbsp;€</td>
                        <?php
                        $iPreviousTotal = $iTotal;
                    }
                    ?>
                </tr>
                <!-- Trésorerie -->
                <tr>
                    <td>Disponibilités</td>
                    <td>CF</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['CF']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['CF']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['CF']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['CF']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="CF[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['CF']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>VMP</td>
                    <td>CD</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['CD']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['CD']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['CD']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['CD']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="CD[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['CD']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr class="sub-total">
                    <td colspan="2">Trésorerie</td>
                    <?php
                    $iPreviousTotal = null;

                    foreach ($this->aBalanceSheets as $aBalanceSheet) {
                        $iTotal = $this->sumBalances(array('CF', 'CD'), $aBalanceSheet);

                        if (false === is_null($iPreviousTotal)) {
                            ?>
                            <td><?= empty($iPreviousTotal) ? '-' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><?= $this->ficelle->formatNumber($iTotal, 0) ?>&nbsp;€</td>
                        <?php
                        $iPreviousTotal = $iTotal;
                    }
                    ?>
                </tr>
                <!-- Comptes de régularisation -->
                <tr>
                    <td>Charges constatées d'avance</td>
                    <td>CH</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['CH']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['CH']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['CH']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['CH']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="CH[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['CH']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Total actif circulant</td>
                    <td>CJ</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['CJ']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['CJ']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['CJ']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['CJ']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="CJ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['CJ']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Frais d'émission d'emprunt à étaler</td>
                    <td>CW</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['CW']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['CW']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['CW']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['CW']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="CW[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['CW']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Primes de remboursement des obligations</td>
                    <td>CM</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['CM']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['CM']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['CM']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['CM']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="CM[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['CM']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Ecarts de conversion actif</td>
                    <td>CN</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['CN']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['CN']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['CN']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['CN']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="CN[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['CN']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr class="sub-total">
                    <td colspan="2">Comptes de régularisation</td>
                    <?php
                    $iPreviousTotal = null;

                    foreach ($this->aBalanceSheets as $aBalanceSheet) {
                        $iTotal = $this->sumBalances(array('CH', 'CW', 'CM', 'CN'), $aBalanceSheet);

                        if (false === is_null($iPreviousTotal)) {
                            ?>
                            <td><?= empty($iPreviousTotal) ? '-' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><?= $this->ficelle->formatNumber($iTotal, 0) ?>&nbsp;€</td>
                        <?php
                        $iPreviousTotal = $iTotal;
                    }
                    ?>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">Total actif</th>
                    <?php
                    $iPreviousTotal = null;

                    foreach ($this->aBalanceSheets as $aBalanceSheet) {
                        $iTotal = $this->sumBalances(array('AA', 'AB', 'AD', 'AF', 'AH', 'AJ', 'AL', 'AN', 'AP', 'AR', 'AT', 'AV', 'AX', 'CS', 'CU', 'BB', 'BD', 'BF', 'BH', 'BL', 'BN', 'BP', 'BR', 'BT', 'BV', 'BX', 'BZ', 'CB', 'CF', 'CD', 'CH', 'CW', 'CM', 'CN'), $aBalanceSheet);

                        if (false === is_null($iPreviousTotal)) {
                            ?>
                            <th><?= empty($iPreviousTotal) ? '-' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . '&nbsp;%' ?></th>
                            <?php
                        }
                        ?>
                        <th><?= $this->ficelle->formatNumber($iTotal, 0) ?>&nbsp;€</th>
                        <?php
                        $iPreviousTotal = $iTotal;
                    }
                    ?>
                </tr>
            </tfoot>
        </table>
        <br/>
        <table class="tablesorter annual-accounts" style="text-align:center;">
            <thead>
                <tr>
                    <th colspan="2">Passif</th>
                    <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
                        <th width="200"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
                        <?php if ($aAnnualAccounts['id_bilan'] != $iLastAnnualAccountsId) { ?><th width="50"></th><?php } ?>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <!-- Total fonds propres -->
                <tr>
                    <td>Capital social</td>
                    <td width="20">DA</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['DA']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['DA']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['DA']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['DA']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="DA[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['DA']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Total capitaux propres</td>
                    <td>DL</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['DL']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['DL']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['DL']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['DL']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="DL[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['DL']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Autres fonds propres</td>
                    <td>DO</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['DO']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['DO']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['DO']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['DO']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="DO[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['DO']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr class="sub-total">
                    <td colspan="2">Total fonds propres</td>
                    <?php
                    $iPreviousTotal = null;

                    foreach ($this->aBalanceSheets as $aBalanceSheet) {
                        $iTotal = $this->sumBalances(array('DL', 'DO'), $aBalanceSheet);

                        if (false === is_null($iPreviousTotal)) {
                            ?>
                            <td><?= empty($iPreviousTotal) ? '-' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><?= $this->ficelle->formatNumber($iTotal, 0) ?>&nbsp;€</td>
                        <?php
                        $iPreviousTotal = $iTotal;
                    }
                    ?>
                </tr>
                <!-- Dettes financières -->
                <tr>
                    <td>Amortissements sur Immobilisations</td>
                    <td>BK</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['BK']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['BK']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['BK']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['BK']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="BK[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['BK']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Dépréciation de l'actif circulant</td>
                    <td>CK</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['CK']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['CK']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['CK']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['CK']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="CK[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['CK']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Provisions pour risques et charges</td>
                    <td>DR</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['DR']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['DR']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['DR']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['DR']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="DR[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['DR']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Emprunts obligataires convertibles</td>
                    <td>DS</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['DS']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['DS']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['DS']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['DS']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="DS[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['DS']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Autres emprunts obligataires</td>
                    <td>DT</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['DT']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['DT']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['DT']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['DT']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="DT[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['DT']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Emprunts et dettes auprès des établissements de crédit</td>
                    <td>DU</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['DU']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['DU']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['DU']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['DU']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="DU[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['DU']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Emprunts et dettes financières divers</td>
                    <td>DV</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['DV']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['DV']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['DV']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['DV']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="DV[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['DV']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr class="sub-total">
                    <td colspan="2">Dettes financières</td>
                    <?php
                    $iPreviousTotal = null;

                    foreach ($this->aBalanceSheets as $aBalanceSheet) {
                        $iTotal = $this->sumBalances(array('DS', 'DT', 'DU', 'DV'), $aBalanceSheet);

                        if (false === is_null($iPreviousTotal)) {
                            ?>
                            <td><?= empty($iPreviousTotal) ? '-' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><?= $this->ficelle->formatNumber($iTotal, 0) ?>&nbsp;€</td>
                        <?php
                        $iPreviousTotal = $iTotal;
                    }
                    ?>
                </tr>
                <!-- Dettes fournisseurs -->
                <tr>
                    <td>Avances et accomptes reçus</td>
                    <td>DW</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['DW']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['DW']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['DW']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['DW']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="DW[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['DW']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Dettes fournisseurs et comptes rattachés</td>
                    <td>DX</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['DX']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['DX']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['DX']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['DX']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="DX[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['DX']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr class="sub-total">
                    <td colspan="2">Dettes fournisseurs</td>
                    <?php
                    $iPreviousTotal = null;

                    foreach ($this->aBalanceSheets as $aBalanceSheet) {
                        $iTotal = $this->sumBalances(array('DW', 'DX'), $aBalanceSheet);

                        if (false === is_null($iPreviousTotal)) {
                            ?>
                            <td><?= empty($iPreviousTotal) ? '-' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><?= $this->ficelle->formatNumber($iTotal, 0) ?>&nbsp;€</td>
                        <?php
                        $iPreviousTotal = $iTotal;
                    }
                    ?>
                </tr>
                <!-- Autres dettes -->
                <tr>
                    <td>Dettes fiscales et sociales</td>
                    <td>DY</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['DY']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['DY']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['DY']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['DY']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="DY[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['DY']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Dettes sur immobilisations et comptes rattachés</td>
                    <td>DZ</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['DZ']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['DZ']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['DZ']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['DZ']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="DZ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['DZ']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Autres dettes</td>
                    <td>EA</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['EA']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['EA']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['EA']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['EA']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="EA[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['EA']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr class="sub-total">
                    <td colspan="2">Autres dettes</td>
                    <?php
                    $iPreviousTotal = null;

                    foreach ($this->aBalanceSheets as $aBalanceSheet) {
                        $iTotal = $this->sumBalances(array('DY', 'DZ', 'EA'), $aBalanceSheet);

                        if (false === is_null($iPreviousTotal)) {
                            ?>
                            <td><?= empty($iPreviousTotal) ? '-' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><?= $this->ficelle->formatNumber($iTotal, 0) ?>&nbsp;€</td>
                        <?php
                        $iPreviousTotal = $iTotal;
                    }
                    ?>
                </tr>
                <!-- Comptes de régularisation -->
                <tr>
                    <td>Produits constatés d'avance</td>
                    <td>EB</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['EB']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['EB']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['EB']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['EB']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="EB[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['EB']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Écarts de conversion passif</td>
                    <td>ED</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['ED']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['ED']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['ED']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['ED']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="ED[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['ED']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr class="sub-total">
                    <td colspan="2">Comptes de régularisation</td>
                    <?php
                    $iPreviousTotal = null;

                    foreach ($this->aBalanceSheets as $aBalanceSheet) {
                        $iTotal = $this->sumBalances(array('EB', 'ED'), $aBalanceSheet);

                        if (false === is_null($iPreviousTotal)) {
                            ?>
                            <td><?= empty($iPreviousTotal) ? '-' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><?= $this->ficelle->formatNumber($iTotal, 0) ?>&nbsp;€</td>
                        <?php
                        $iPreviousTotal = $iTotal;
                    }
                    ?>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">Total passif</th>
                    <?php
                    $iPreviousTotal = null;

                    foreach ($this->aBalanceSheets as $aBalanceSheet) {
                        $iTotal = $this->sumBalances(array('DL', 'DO', 'BK', 'CK', 'DR', 'DS', 'DT', 'DU', 'DV', 'DW', 'DX', 'DY', 'DZ', 'EA', 'EB', 'ED'), $aBalanceSheet);

                        if (false === is_null($iPreviousTotal)) {
                            ?>
                            <th><?= empty($iPreviousTotal) ? '-' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . '&nbsp;%' ?></th>
                            <?php
                        }
                        ?>
                        <th><?= $this->ficelle->formatNumber($iTotal, 0) ?>&nbsp;€</th>
                        <?php
                        $iPreviousTotal = $iTotal;
                    }
                    ?>
                </tr>
            </tfoot>
        </table>
        <br/>
        <table class="tablesorter annual-accounts" style="text-align:center;">
            <thead>
                <tr>
                    <th colspan="2">Autres infos</th>
                    <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
                        <th width="200"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
                        <?php if ($aAnnualAccounts['id_bilan'] != $iLastAnnualAccountsId) { ?><th width="50"></th><?php } ?>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>2 : CBC, et soldes créditeurs de banques et CCP</td>
                    <td width="20">EH</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['EH']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['EH']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['EH']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['EH']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="EH[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['EH']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>2 : Prêt participatif</td>
                    <td>EI</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['EI']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['EI']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['EI']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['EI']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="EI[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['EI']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>4 : Crédit bail Mobilier</td>
                    <td>HP</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['HP']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['HP']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['HP']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['HP']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="HP[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['HP']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>4 : Crédit bail Immobilier</td>
                    <td>HQ</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['HQ']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['HQ']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['HQ']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['HQ']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="HQ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['HQ']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>A5 : Investissements</td>
                    <td>0J</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['0J']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['0J']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['0J']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['0J']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="0J[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['0J']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>A8 : Dettes à rembourser ds l'année (à plus de 1 an à l'origine)</td>
                    <td>VH</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['VH']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['VH']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['VH']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['VH']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="VH[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['VH']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>A8 : Groupes et associés (placés en "Emprunts et dettes diverses")</td>
                    <td>VI</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['VI']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="VI[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['VI']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
            </tbody>
        </table>
        <br/>
        <table class="tablesorter annual-accounts" style="text-align:center;">
            <thead>
                <tr>
                    <th colspan="2">Compte de résultat</th>
                    <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
                        <th width="200"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
                        <?php if ($aAnnualAccounts['id_bilan'] != $iLastAnnualAccountsId) { ?><th width="50"></th><?php } ?>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Chiffre d'Affaires nets</strong></td>
                    <td width="20">FL</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['FL']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['FL']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['FL']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['FL']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="FL[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['FL']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Production stockée</td>
                    <td>FM</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['FM']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['FM']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['FM']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['FM']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="FM[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['FM']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Production immobilisée</td>
                    <td>FN</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['FN']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['FN']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['FN']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['FN']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="FN[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['FN']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Subventions d'exploitation</td>
                    <td>FO</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['FO']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['FO']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['FO']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['FO']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="FO[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['FO']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Reprises sur amort. et prov., transferts de charges</td>
                    <td>FP</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['FP']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['FP']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['FP']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['FP']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="FP[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['FP']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Autres produits</td>
                    <td>FQ</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['FQ']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['FQ']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['FQ']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['FQ']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="FQ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['FQ']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Achats de marchandises</td>
                    <td>FS</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['FS']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['FS']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['FS']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['FS']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="FS[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['FS']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Variation de stock (marchandises)</td>
                    <td>FT</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['FT']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['FT']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['FT']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['FT']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="FT[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['FT']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Achats de matières premières et autres approv.</td>
                    <td>FU</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['FU']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['FU']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['FU']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['FU']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="FU[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['FU']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Variation de stock (matières premiières et approv.)</td>
                    <td>FV</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['FV']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['FV']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['FV']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['FV']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="FV[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['FV']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Autres achats et charges externes</td>
                    <td>FW</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['FW']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['FW']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['FW']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['FW']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="FW[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['FW']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Impots, taxes et versements assimilés</td>
                    <td>FX</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['FX']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['FX']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['FX']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['FX']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="FX[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['FX']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Salaires et traitements</td>
                    <td>FY</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['FY']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['FY']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['FY']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['FY']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="FY[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['FY']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Charges sociales</td>
                    <td>FZ</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['FZ']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['FZ']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['FZ']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['FZ']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="FZ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['FZ']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Dotations aux amortissements</td>
                    <td>GA</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['GA']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['GA']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['GA']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['GA']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="GA[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['GA']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Dotations aux provisions</td>
                    <td>GB</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['GB']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['GB']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['GB']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['GB']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="GB[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['GB']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Dotations aux provisions (sur actif circulant)</td>
                    <td>GC</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['GC']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['GC']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['GC']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['GC']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="GC[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['GC']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Dotation aux provisions (pour risques et charges)</td>
                    <td>GD</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['GD']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['GD']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['GD']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['GD']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="GD[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['GD']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Autres charges</td>
                    <td>GE</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['GE']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['GE']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['GE']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['GE']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="GE[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['GE']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td><strong>Résultat d'Exploitation</strong></td>
                    <td>GG</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['GG']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['GG']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['GG']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['GG']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="GG[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['GG']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Résultat financier</td>
                    <td>GV</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['GV']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['GV']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['GV']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['GV']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="GV[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['GV']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Reprises sur amort. et prov., transferts de charges fi</td>
                    <td>GM</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['GM']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['GM']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['GM']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['GM']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="GM[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['GM']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Dotations financières aux amort. Et prov.</td>
                    <td>GQ</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['GQ']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['GQ']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['GQ']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['GQ']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="GQ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['GQ']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Total Charges Financières</td>
                    <td>GU</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['GU']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['GU']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['GU']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['GU']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="GU[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['GU']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>RCAI</td>
                    <td>GW</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['GW']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['GW']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['GW']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['GW']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="GW[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['GW']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Produits exceptionnels sur opérations de gestion</td>
                    <td>HA</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['HA']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['HA']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['HA']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['HA']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="HA[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['HA']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Produits exceptionnels sur opérations de capital</td>
                    <td>HB</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['HB']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['HB']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['HB']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['HB']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="HB[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['HB']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Reprises sur provisions et transferts de charges</td>
                    <td>HC</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['HC']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['HC']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['HC']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['HC']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="HC[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['HC']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Charges exceptionnelles sur opérations de gestion</td>
                    <td>HE</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['HE']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['HE']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['HE']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['HE']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="HE[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['HE']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Charges exceptionnelles sur opérations en capital</td>
                    <td>HF</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['HF']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['HF']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['HF']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['HF']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="HF[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['HF']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Dotations exceptionnelles aux amts et provisions</td>
                    <td>HG</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['HG']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['HG']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['HG']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['HG']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="HG[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['HG']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td><strong>Résultat net</strong></td>
                    <td>HN</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet[$this->aBalanceCodes['HN']['id_balance_type']]) ? '-' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId][$this->aBalanceCodes['HN']['id_balance_type']] - $aBalanceSheet[$this->aBalanceCodes['HN']['id_balance_type']]) / $aBalanceSheet[$this->aBalanceCodes['HN']['id_balance_type']] * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" name="HN[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet[$this->aBalanceCodes['HN']['id_balance_type']], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
            </tbody>
        </table>
        <div id="valid_etape4_2" class="valid_etape"><br/>Données sauvegardées</div>
        <div class="btnDroite">
            <input type="submit" class="btn_link" value="Sauvegarder">
        </div>
    </form>
</div>
