<?php $iOldestAnnualAccountsId = end(array_keys($this->aBalanceSheets)); ?>
<script type="text/javascript">
    $(function() {
        $('#last_annual_accounts').change(function() {
            $('#last_annual_accounts_form').submit();
        });

        $('.annual_accounts_dates').click(function() {
            $box = $('#annual_accounts_dates_popup').clone();
            $box.find('[name=duree_exercice_fiscal]').val($(this).data('duration'));
            $box.find('[name=id_annual_accounts]').val($(this).data('annual-account'));
            $box.find('[name=cloture_exercice_fiscal]').val($(this).data('closing')).datepicker({
                showOn: 'both',
                buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
                buttonImageOnly: true,
                changeMonth: true,
                changeYear: true,
                yearRange: '<?= (date('Y') - 5) ?>:<?= (date('Y') + 5) ?>'
            });
            $.colorbox({html: $box.show()});
        });

        $('.annual-accounts .numbers').on('focus', function() {
            $(this).closest('tr').addClass('highlighted');
        }).on('blur', function() {
            $(this).closest('tr').removeClass('highlighted');
        });

        $('.collapse_expand').on('click', function() {
            if ($(this).hasClass('expanded')) {
                $(this).attr('src', '<?= $this->surl ?>/images/admin/down.png');
                $(this).closest('table').children('tbody').hide();
                $(this).removeClass('expanded').addClass('collapsed');
            } else {
                $(this).attr('src', '<?= $this->surl ?>/images/admin/up.png');
                $(this).closest('table').children('tbody').show();
                $(this).removeClass('collapsed').addClass('expanded');
            }
        });
    });
</script>
<style type="text/css">
    #annual_accounts_dates_popup {
        background-color: #FFF;
        padding: 20px;
        border: 2px solid #E3E4E5;
        border-radius: 10px;
    }

    .collapse_expand {
        cursor: pointer;
        float: left;
    }
</style>

<div id="annual_accounts_dates_popup" style="display: none;">
    <h2>Modifier l'exercice fiscal</h2>
    <form action="/dossiers/edit/<?= $this->projects->id_project ?>" method="post">
        <input type="text" name="cloture_exercice_fiscal" class="numbers input_dp datepicker" placeholder="Date de cloture"/>
        <input type="text" name="duree_exercice_fiscal" class="numbers input_court numbers" placeholder="Durée (mois)"/> mois
        <br/><br/>
        <div style="text-align: right">
            <input type="hidden" name="id_annual_accounts"/>
            <input type="hidden" name="change_annual_accounts_info" value="1"/>
            <input type="submit" value="Sauvegarder" class="btn_link"/>
        </div>
    </form>
</div>

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
    <form id="dossier_etape4_2" action="/ajax/valid_etapes" method="post">
        <input type="hidden" name="id_project" value="<?= $this->projects->id_project ?>"/>
        <input type="hidden" name="etape" value="4.2"/>
        <table class="tablesorter annual-accounts" style="text-align:center;">
            <thead>
                <tr>
                    <th colspan="2">
                        <img class="collapse_expand expanded" src="<?= $this->surl ?>/images/admin/up.png" alt="Déplier/replier"/>
                        Actif
                    </th>
                    <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
                        <th width="200" class="annual_accounts_dates" data-closing="<?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?>" data-duration="<?= $aAnnualAccounts['duree_exercice_fiscal'] ?>" data-annual-account="<?= $aAnnualAccounts['id_bilan'] ?>"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
                        <?php if ($aAnnualAccounts['id_bilan'] != $iOldestAnnualAccountsId) { ?><th width="50"></th><?php } ?>
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
                            <td><?= empty($aBalanceSheet['AA']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AA'] - $aBalanceSheet['AA']) / abs($aBalanceSheet['AA']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="AA[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['AA'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['AB']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AB'] - $aBalanceSheet['AB']) / abs($aBalanceSheet['AB']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="AB[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['AB'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>Frais de développement</td>
                    <td>AD / CX</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet['AD']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AD'] - $aBalanceSheet['AD']) / abs($aBalanceSheet['AD']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="AD[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['AD'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['AF']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AF'] - $aBalanceSheet['AF']) / abs($aBalanceSheet['AF']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="AF[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['AF'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['AH']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AH'] - $aBalanceSheet['AH']) / abs($aBalanceSheet['AH']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="AH[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['AH'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['AJ']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AJ'] - $aBalanceSheet['AJ']) / abs($aBalanceSheet['AJ']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="AJ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['AJ'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['AL']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AL'] - $aBalanceSheet['AL']) / abs($aBalanceSheet['AL']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="AL[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['AL'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($iTotal) ? 'N/A' : round(($iPreviousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></td>
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
                            <td><?= empty($aBalanceSheet['AN']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AN'] - $aBalanceSheet['AN']) / abs($aBalanceSheet['AN']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="AN[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['AN'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['AP']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AP'] - $aBalanceSheet['AP']) / abs($aBalanceSheet['AP']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="AP[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['AP'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['AR']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AR'] - $aBalanceSheet['AR']) / abs($aBalanceSheet['AR']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="AR[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['AR'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['AT']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AT'] - $aBalanceSheet['AT']) / abs($aBalanceSheet['AT']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="AT[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['AT'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['AV']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AV'] - $aBalanceSheet['AV']) / abs($aBalanceSheet['AV']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="AV[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['AV'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['AX']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AX'] - $aBalanceSheet['AX']) / abs($aBalanceSheet['AX']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="AX[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['AX'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($iTotal) ? 'N/A' : round(($iPreviousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></td>
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
                            <td><?= empty($aBalanceSheet['CS']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CS'] - $aBalanceSheet['CS']) / abs($aBalanceSheet['CS']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="CS[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['CS'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['CU']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CU'] - $aBalanceSheet['CU']) / abs($aBalanceSheet['CU']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="CU[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['CU'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['BB']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BB'] - $aBalanceSheet['BB']) / abs($aBalanceSheet['BB']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="BB[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['BB'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['BD']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BD'] - $aBalanceSheet['BD']) / abs($aBalanceSheet['BD']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="BD[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['BD'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['BF']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BF'] - $aBalanceSheet['BF']) / abs($aBalanceSheet['BF']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="BF[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['BF'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['BH']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BH'] - $aBalanceSheet['BH']) / abs($aBalanceSheet['BH']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="BH[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['BH'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($iTotal) ? 'N/A' : round(($iPreviousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></td>
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
                <tr class="sub-total">
                    <td>Total actif immobilisé</td>
                    <td>BJ</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet['BJ']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BJ'] - $aBalanceSheet['BJ']) / abs($aBalanceSheet['BJ']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="BJ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['BJ'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['BL']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BL'] - $aBalanceSheet['BL']) / abs($aBalanceSheet['BL']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="BL[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['BL'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['BN']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BN'] - $aBalanceSheet['BN']) / abs($aBalanceSheet['BN']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="BN[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['BN'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['BP']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BP'] - $aBalanceSheet['BP']) / abs($aBalanceSheet['BP']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="BP[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['BP'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['BR']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BR'] - $aBalanceSheet['BR']) / abs($aBalanceSheet['BR']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="BR[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['BR'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['BT']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BT'] - $aBalanceSheet['BT']) / abs($aBalanceSheet['BT']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="BT[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['BT'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($iTotal) ? 'N/A' : round(($iPreviousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></td>
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
                            <td><?= empty($aBalanceSheet['BV']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BV'] - $aBalanceSheet['BV']) / abs($aBalanceSheet['BV']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="BV[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['BV'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['BX']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BX'] - $aBalanceSheet['BX']) / abs($aBalanceSheet['BX']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="BX[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['BX'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['BZ']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BZ'] - $aBalanceSheet['BZ']) / abs($aBalanceSheet['BZ']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="BZ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['BZ'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['CB']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CB'] - $aBalanceSheet['CB']) / abs($aBalanceSheet['CB']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="CB[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['CB'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($iTotal) ? 'N/A' : round(($iPreviousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></td>
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
                            <td><?= empty($aBalanceSheet['CF']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CF'] - $aBalanceSheet['CF']) / abs($aBalanceSheet['CF']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="CF[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['CF'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['CD']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CD'] - $aBalanceSheet['CD']) / abs($aBalanceSheet['CD']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="CD[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['CD'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($iTotal) ? 'N/A' : round(($iPreviousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></td>
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
                            <td><?= empty($aBalanceSheet['CH']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CH'] - $aBalanceSheet['CH']) / abs($aBalanceSheet['CH']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="CH[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['CH'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr class="sub-total">
                    <td>Total actif circulant</td>
                    <td>CJ</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet['CJ']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CJ'] - $aBalanceSheet['CJ']) / abs($aBalanceSheet['CJ']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="CJ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['CJ'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['CW']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CW'] - $aBalanceSheet['CW']) / abs($aBalanceSheet['CW']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="CW[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['CW'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['CM']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CM'] - $aBalanceSheet['CM']) / abs($aBalanceSheet['CM']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="CM[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['CM'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['CN']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CN'] - $aBalanceSheet['CN']) / abs($aBalanceSheet['CN']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="CN[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['CN'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($iTotal) ? 'N/A' : round(($iPreviousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></td>
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
                    $iIndex         = 0;
                    $iPreviousTotal = null;

                    foreach ($this->aBalanceSheets as $aBalanceSheet) {
                        $iTotal = $this->sumBalances(array('AA', 'AB', 'AD', 'AF', 'AH', 'AJ', 'AL', 'AN', 'AP', 'AR', 'AT', 'AV', 'AX', 'CS', 'CU', 'BB', 'BD', 'BF', 'BH', 'BL', 'BN', 'BP', 'BR', 'BT', 'BV', 'BX', 'BZ', 'CB', 'CF', 'CD', 'CH', 'CW', 'CM', 'CN'), $aBalanceSheet);

                        if (false === is_null($iPreviousTotal)) {
                            ?>
                            <th><?= empty($iTotal) ? 'N/A' : round(($iPreviousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></th>
                            <?php
                        }
                        ?>
                        <th id="total_actif_<?= $iIndex++ ?>" data-total="<?= $iTotal ?>"><?= $this->ficelle->formatNumber($iTotal, 0) ?>&nbsp;€</th>
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
                    <th colspan="2">
                        <img class="collapse_expand expanded" src="<?= $this->surl ?>/images/admin/up.png" alt="Déplier/replier"/>
                        Passif
                    </th>
                    <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
                        <th width="200"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
                        <?php if ($aAnnualAccounts['id_bilan'] != $iOldestAnnualAccountsId) { ?><th width="50"></th><?php } ?>
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
                            <td><?= empty($aBalanceSheet['DA']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DA'] - $aBalanceSheet['DA']) / abs($aBalanceSheet['DA']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="DA[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['DA'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['DL']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DL'] - $aBalanceSheet['DL']) / abs($aBalanceSheet['DL']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="DL[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['DL'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['DO']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DO'] - $aBalanceSheet['DO']) / abs($aBalanceSheet['DO']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="DO[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['DO'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($iTotal) ? 'N/A' : round(($iPreviousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></td>
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
                <tr class="sub-total">
                    <td>Amortissements sur Immobilisations</td>
                    <td>BK</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet['BK']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BK'] - $aBalanceSheet['BK']) / abs($aBalanceSheet['BK']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="BK[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['BK'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr class="sub-total">
                    <td>Dépréciation de l'actif circulant</td>
                    <td>CK</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet['CK']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CK'] - $aBalanceSheet['CK']) / abs($aBalanceSheet['CK']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="CK[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['CK'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr class="sub-total">
                    <td>Provisions pour risques et charges</td>
                    <td>DR</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet['DR']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DR'] - $aBalanceSheet['DR']) / abs($aBalanceSheet['DR']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="DR[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['DR'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['DS']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DS'] - $aBalanceSheet['DS']) / abs($aBalanceSheet['DS']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="DS[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['DS'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['DT']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DT'] - $aBalanceSheet['DT']) / abs($aBalanceSheet['DT']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="DT[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['DT'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['DU']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DU'] - $aBalanceSheet['DU']) / abs($aBalanceSheet['DU']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="DU[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['DU'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['DV']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DV'] - $aBalanceSheet['DV']) / abs($aBalanceSheet['DV']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="DV[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['DV'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($iTotal) ? 'N/A' : round(($iPreviousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></td>
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
                            <td><?= empty($aBalanceSheet['DW']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DW'] - $aBalanceSheet['DW']) / abs($aBalanceSheet['DW']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="DW[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['DW'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['DX']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DX'] - $aBalanceSheet['DX']) / abs($aBalanceSheet['DX']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="DX[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['DX'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($iTotal) ? 'N/A' : round(($iPreviousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></td>
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
                            <td><?= empty($aBalanceSheet['DY']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DY'] - $aBalanceSheet['DY']) / abs($aBalanceSheet['DY']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="DY[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['DY'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['DZ']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DZ'] - $aBalanceSheet['DZ']) / abs($aBalanceSheet['DZ']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="DZ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['DZ'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['EA']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['EA'] - $aBalanceSheet['EA']) / abs($aBalanceSheet['EA']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="EA[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['EA'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($iTotal) ? 'N/A' : round(($iPreviousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></td>
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
                            <td><?= empty($aBalanceSheet['EB']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['EB'] - $aBalanceSheet['EB']) / abs($aBalanceSheet['EB']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="EB[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['EB'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['ED']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['ED'] - $aBalanceSheet['ED']) / abs($aBalanceSheet['ED']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="ED[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['ED'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($iTotal) ? 'N/A' : round(($iPreviousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></td>
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
                    $iIndex         = 0;
                    $iPreviousTotal = null;

                    foreach ($this->aBalanceSheets as $aBalanceSheet) {
                        $iTotal = $this->sumBalances(array('DL', 'DO', 'BK', 'CK', 'DR', 'DS', 'DT', 'DU', 'DV', 'DW', 'DX', 'DY', 'DZ', 'EA', 'EB', 'ED'), $aBalanceSheet);

                        if (false === is_null($iPreviousTotal)) {
                            ?>
                            <th><?= empty($iTotal) ? 'N/A' : round(($iPreviousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%' ?></th>
                            <?php
                        }
                        ?>
                        <th id="total_passif_<?= $iIndex++ ?>" data-total="<?= $iTotal ?>"><?= $this->ficelle->formatNumber($iTotal, 0) ?>&nbsp;€</th>
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
                    <th colspan="2">
                        <img class="collapse_expand expanded" src="<?= $this->surl ?>/images/admin/up.png" alt="Déplier/replier"/>
                        Autres infos
                    </th>
                    <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
                        <th width="200"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
                        <?php if ($aAnnualAccounts['id_bilan'] != $iOldestAnnualAccountsId) { ?><th width="50"></th><?php } ?>
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
                            <td><?= empty($aBalanceSheet['EH']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['EH'] - $aBalanceSheet['EH']) / abs($aBalanceSheet['EH']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="EH[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['EH'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['EI']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['EI'] - $aBalanceSheet['EI']) / abs($aBalanceSheet['EI']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="EI[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['EI'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['HP']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HP'] - $aBalanceSheet['HP']) / abs($aBalanceSheet['HP']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="HP[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['HP'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['HQ']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HQ'] - $aBalanceSheet['HQ']) / abs($aBalanceSheet['HQ']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="HQ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['HQ'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
                <tr>
                    <td>4 : Transfert de charges</td>
                    <td>A1</td>
                    <?php
                    $iColumn = 0;
                    $iPreviousBalanceSheetId = null;

                    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                        if (false === is_null($iPreviousBalanceSheetId)) {
                            ?>
                            <td><?= empty($aBalanceSheet['A1']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['A1'] - $aBalanceSheet['A1']) / abs($aBalanceSheet['A1']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="A1[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['A1'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['0J']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['0J'] - $aBalanceSheet['0J']) / abs($aBalanceSheet['0J']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="0J[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['0J'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['VH']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['VH'] - $aBalanceSheet['VH']) / abs($aBalanceSheet['VH']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="VH[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['VH'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['VI']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['VI'] - $aBalanceSheet['VI']) / abs($aBalanceSheet['VI']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="VI[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['VI'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                    <th colspan="2">
                        <img class="collapse_expand expanded" src="<?= $this->surl ?>/images/admin/up.png" alt="Déplier/replier"/>
                        Compte de résultat
                    </th>
                    <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
                        <th width="200"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
                        <?php if ($aAnnualAccounts['id_bilan'] != $iOldestAnnualAccountsId) { ?><th width="50"></th><?php } ?>
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
                            <td><?= empty($aBalanceSheet['FL']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FL'] - $aBalanceSheet['FL']) / abs($aBalanceSheet['FL']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="FL[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['FL'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['FM']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FM'] - $aBalanceSheet['FM']) / abs($aBalanceSheet['FM']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="FM[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['FM'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['FN']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FN'] - $aBalanceSheet['FN']) / abs($aBalanceSheet['FN']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="FN[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['FN'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['FO']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FO'] - $aBalanceSheet['FO']) / abs($aBalanceSheet['FO']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="FO[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['FO'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['FP']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FP'] - $aBalanceSheet['FP']) / abs($aBalanceSheet['FP']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="FP[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['FP'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['FQ']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FQ'] - $aBalanceSheet['FQ']) / abs($aBalanceSheet['FQ']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="FQ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['FQ'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['FS']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FS'] - $aBalanceSheet['FS']) / abs($aBalanceSheet['FS']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="FS[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['FS'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['FT']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FT'] - $aBalanceSheet['FT']) / abs($aBalanceSheet['FT']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="FT[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['FT'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['FU']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FU'] - $aBalanceSheet['FU']) / abs($aBalanceSheet['FU']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="FU[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['FU'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['FV']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FV'] - $aBalanceSheet['FV']) / abs($aBalanceSheet['FV']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="FV[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['FV'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['FW']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FW'] - $aBalanceSheet['FW']) / abs($aBalanceSheet['FW']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="FW[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['FW'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['FX']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FX'] - $aBalanceSheet['FX']) / abs($aBalanceSheet['FX']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="FX[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['FX'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['FY']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FY'] - $aBalanceSheet['FY']) / abs($aBalanceSheet['FY']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="FY[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['FY'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['FZ']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FZ'] - $aBalanceSheet['FZ']) / abs($aBalanceSheet['FZ']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="FZ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['FZ'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['GA']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GA'] - $aBalanceSheet['GA']) / abs($aBalanceSheet['GA']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="GA[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['GA'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['GB']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GB'] - $aBalanceSheet['GB']) / abs($aBalanceSheet['GB']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="GB[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['GB'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['GC']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GC'] - $aBalanceSheet['GC']) / abs($aBalanceSheet['GC']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="GC[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['GC'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['GD']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GD'] - $aBalanceSheet['GD']) / abs($aBalanceSheet['GD']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="GD[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['GD'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['GE']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GE'] - $aBalanceSheet['GE']) / abs($aBalanceSheet['GE']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="GE[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['GE'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['GG']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GG'] - $aBalanceSheet['GG']) / abs($aBalanceSheet['GG']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="GG[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['GG'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['GV']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GV'] - $aBalanceSheet['GV']) / abs($aBalanceSheet['GV']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="GV[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['GV'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['GM']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GM'] - $aBalanceSheet['GM']) / abs($aBalanceSheet['GM']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="GM[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['GM'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['GQ']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GQ'] - $aBalanceSheet['GQ']) / abs($aBalanceSheet['GQ']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="GQ[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['GQ'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['GU']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GU'] - $aBalanceSheet['GU']) / abs($aBalanceSheet['GU']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="GU[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['GU'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['GW']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GW'] - $aBalanceSheet['GW']) / abs($aBalanceSheet['GW']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="GW[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['GW'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['HA']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HA'] - $aBalanceSheet['HA']) / abs($aBalanceSheet['HA']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="HA[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['HA'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['HB']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HB'] - $aBalanceSheet['HB']) / abs($aBalanceSheet['HB']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="HB[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['HB'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['HC']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HC'] - $aBalanceSheet['HC']) / abs($aBalanceSheet['HC']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="HC[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['HC'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['HE']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HE'] - $aBalanceSheet['HE']) / abs($aBalanceSheet['HE']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="HE[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['HE'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['HF']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HF'] - $aBalanceSheet['HF']) / abs($aBalanceSheet['HF']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="HF[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['HF'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['HG']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HG'] - $aBalanceSheet['HG']) / abs($aBalanceSheet['HG']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="HG[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['HG'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
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
                            <td><?= empty($aBalanceSheet['HN']) ? 'N/A' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HN'] - $aBalanceSheet['HN']) / abs($aBalanceSheet['HN']) * 100) . '&nbsp;%' ?></td>
                            <?php
                        }
                        ?>
                        <td><input type="text" class="numbers" name="HN[<?= $iBalanceSheetId ?>]" value="<?= $this->ficelle->formatNumber($aBalanceSheet['HN'], 0) ?>" tabindex="<?= 420 + ++$iColumn ?>"/>&nbsp;€</td>
                        <?php
                        $iPreviousBalanceSheetId = $iBalanceSheetId;
                    }
                    ?>
                </tr>
            </tbody>
        </table>
        <div class="btnDroite">
            <input type="submit" class="btn_link" value="Sauvegarder les bilans">
        </div>
    </form>
</div>
