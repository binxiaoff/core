<?php
$aAnnualAccountsYears    = array_keys($this->aBalanceSheets);
$iOldestAnnualAccountsId = end($aAnnualAccountsYears);
?>
<table class="tablesorter annual-accounts" style="text-align:center;">
<thead>
<tr>
    <th colspan="2">
        <img class="collapse_expand expanded" src="<?= $this->surl ?>/images/admin/up.png" alt="Déplier/replier"/>
        Actif
    </th>
    <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
        <th width="180" class="annual_accounts_dates" data-closing="<?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?>" data-duration="<?= $aAnnualAccounts['duree_exercice_fiscal'] ?>" data-annual-account="<?= $aAnnualAccounts['id_bilan'] ?>"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
        <?php if ($aAnnualAccounts['id_bilan'] != $iOldestAnnualAccountsId) { ?><th width="50"></th><?php } ?>
    <?php endforeach; ?>
</tr>
</thead>
<tbody>
    <!-- Immobilisations incorporelles -->
    <?php
    $codeImmoIncorp = ['AA', 'AB', 'AD', 'AF', 'AH', 'AJ', 'AL'];
    echo $this->generateBalanceGroupHtml('Immobilisations incorporelles', $codeImmoIncorp, \company_tax_form_type::FORM_2033);
    ?>

    <!-- Immobilisations corporelles -->
    <?php
    $codeImmoCorp = ['AN', 'AP', 'AR', 'AT', 'AV', 'AX'];
    echo $this->generateBalanceGroupHtml('Immobilisations corporelles', $codeImmoCorp, \company_tax_form_type::FORM_2033);
    ?>

    <!-- Immobilisations financières -->
    <?php
    $codeFin = ['CS', 'CU', 'BB', 'BD', 'BF', 'BH'];
    echo $this->generateBalanceGroupHtml('Immobilisations financières', $codeFin, \company_tax_form_type::FORM_2033);
    echo $this->generateBalanceLineHtml(['BJ'], \company_tax_form_type::FORM_2033, 'sub-total');
    ?>

    <!-- Stocks -->
    <?php
    $codeStock = ['BL', 'BN', 'BP', 'BR', 'BT'];
    echo $this->generateBalanceGroupHtml('Stocks', $codeStock, \company_tax_form_type::FORM_2033);
    ?>

    <!-- Créances clients et autres -->
    <?php
    $codeCreance = ['BV', 'BX', 'BZ', 'CB'];
    echo $this->generateBalanceGroupHtml('Créances clients et autres', $codeCreance, \company_tax_form_type::FORM_2033);
    ?>

    <!-- Trésorerie -->
    <?php
    $codeTresor = ['CD', 'CF'];
    echo $this->generateBalanceGroupHtml('Trésorerie', $codeTresor, \company_tax_form_type::FORM_2033);
    ?>

    <?php
    $codeCharge = ['CH'];
    echo $this->generateBalanceLineHtml($codeCharge, \company_tax_form_type::FORM_2033);
    echo $this->generateBalanceLineHtml(['CJ'], \company_tax_form_type::FORM_2033, 'sub-total');
    ?>

    <!-- Comptes de régularisation -->
    <?php
    $codeRegul = ['CW', 'CM', 'CN'];
    echo $this->generateBalanceGroupHtml('Comptes de régularisation', $codeRegul, \company_tax_form_type::FORM_2033);
    ?>
</tbody>
<tfoot>
<?php
$iIndex = 0;
$domId = 'total_actif_';
echo $this->generateBalanceTotalLineHtml('Total actif', array_merge($codeImmoIncorp, $codeImmoCorp, $codeFin, $codeStock, $codeCreance, $codeTresor, $codeCharge, $codeRegul), \company_tax_form_type::FORM_2033, $domId);
?>
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
            <th width="180"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
            <?php if ($aAnnualAccounts['id_bilan'] != $iOldestAnnualAccountsId) { ?><th width="50"></th><?php } ?>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <!-- Total fonds propres -->
    <?php
    echo $this->generateBalanceLineHtml(['DA'], \company_tax_form_type::FORM_2033);
    $codeFondsPropres = ['DL', 'DO'];
    echo $this->generateBalanceGroupHtml('Total fonds propres', $codeFondsPropres, \company_tax_form_type::FORM_2033);
    ?>
    <!-- Dettes financières -->
    <?php
    $codeAmorti = ['BK', 'CK', 'DR'];
    echo $this->generateBalanceLineHtml($codeAmorti, \company_tax_form_type::FORM_2033, 'sub-total');
    $codeDettes = ['DS', 'DT', 'DU', 'DV'];
    echo $this->generateBalanceGroupHtml('Dettes financières', $codeDettes, \company_tax_form_type::FORM_2033);
    ?>
    <!-- Dettes fournisseurs -->
    <?php
    $codeDettesFour = ['DW', 'DX'];
    echo $this->generateBalanceGroupHtml('Dettes fournisseurs', $codeDettesFour, \company_tax_form_type::FORM_2033);
    ?>
    <!-- Autres dettes -->
    <?php
    $codeDettesAutres = ['DY', 'DZ', 'EA'];
    echo $this->generateBalanceGroupHtml('Autres dettes', $codeDettesAutres, \company_tax_form_type::FORM_2033);
    ?>
    <!-- Comptes de régularisation -->
    <?php
    $codeRegul2 = ['EB', 'ED'];
    echo $this->generateBalanceGroupHtml('Comptes de régularisation', $codeRegul2, \company_tax_form_type::FORM_2033);
    ?>
    </tbody>
    <tfoot>
    <?php
    $domId = 'total_passif_';
    echo $this->generateBalanceTotalLineHtml('Total passif', array_merge($codeFondsPropres, $codeAmorti, $codeDettes, $codeDettesFour, $codeDettesAutres, $codeRegul2), \company_tax_form_type::FORM_2033, $domId);
    ?>
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
            <th width="180"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
            <?php if ($aAnnualAccounts['id_bilan'] != $iOldestAnnualAccountsId) { ?><th width="50"></th><?php } ?>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php
    $codeAutresInfos = ['EH', 'EI', 'HP', 'HQ', 'A1', '0J', 'VH2', 'VI'];
    echo $this->generateBalanceLineHtml($codeAutresInfos, \company_tax_form_type::FORM_2033);
    ?>
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
            <th width="180"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
            <?php if ($aAnnualAccounts['id_bilan'] != $iOldestAnnualAccountsId) { ?><th width="50"></th><?php } ?>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php
    $codeCompteResultat = ['FL', 'FM', 'FN', 'FO', 'FP', 'FQ', 'FS', 'FT', 'FU', 'FV', 'FW', 'FX', 'FY', 'FZ', 'GA', 'GB', 'GC', 'GD', 'GE', 'GG', 'GV', 'GM', 'GQ', 'GU', 'GW', 'HA', 'HB', 'HC', 'HE', 'HF', 'HG', 'HN', 'YP'];
    echo $this->generateBalanceLineHtml($codeCompteResultat, \company_tax_form_type::FORM_2033);
    ?>
    </tbody>
</table>
