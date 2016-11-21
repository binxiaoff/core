<?php
$aAnnualAccountsYears    = array_keys($this->aBalanceSheets);
$iOldestAnnualAccountsId = end($aAnnualAccountsYears);
?>
<table class="tablesorter annual-accounts" style="text-align:center;">
    <thead>
    <tr>
        <th colspan="2">
            <img class="collapse_expand expanded" src="<?= $this->surl ?>/images/admin/up.png" alt="Déplier/replier"/>
            Liasse 2035
        </th>
        <?php foreach ($this->lbilans as $aAnnualAccounts): ?>
            <th width="180" class="annual_accounts_dates" data-closing="<?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?>" data-duration="<?= $aAnnualAccounts['duree_exercice_fiscal'] ?>" data-annual-account="<?= $aAnnualAccounts['id_bilan'] ?>"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
            <?php if ($aAnnualAccounts['id_bilan'] != $iOldestAnnualAccountsId) { ?><th width="50"></th><?php } ?>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php
    echo $this->generateBalanceLineHtml(['AA', 'AB', 'AC'], \company_tax_form_type::FORM_2035);
    $codeRecettes = ['AD', 'AE', 'AF'];
    echo $this->generateBalanceGroupHtml('Total de AD à AF (case AG)', $codeRecettes, \company_tax_form_type::FORM_2035);

    $codeDepenses = ['BA', 'BB', 'BC', 'BD', 'JY', 'BS', 'BV', 'BF', 'BG', 'BH', 'BJ', 'BK', 'BM', 'BN', 'BP'];
    echo $this->generateBalanceGroupHtml('Total de BA à BP (case BR)', $codeDepenses, \company_tax_form_type::FORM_2035);

    $codeExcedent         = ['BA', 'BB', 'BC', 'BD', 'JY', 'BS', 'BV', 'BF', 'BG', 'BH', 'BJ', 'BK', 'BM', 'BN', 'BP'];
    $codeDepensesNegative = array_map([$this, 'negtive'], $codeDepenses);
    echo $this->generateBalanceSubTotalLineHtml('Excédent (AG - BR) (case CA)', array_merge($codeRecettes, $codeDepensesNegative), \company_tax_form_type::FORM_2035);

    $codeBenefice = ['CB', 'CC', 'CD'];
    echo $this->generateBalanceLineHtml($codeBenefice, \company_tax_form_type::FORM_2035);
    $codeCE = array_merge($codeRecettes, $codeDepensesNegative, $codeBenefice);
    echo $this->generateBalanceSubTotalLineHtml('Total CA, CB, CC et CD (case CE)', $codeCE, \company_tax_form_type::FORM_2035);

    $codeRecettesNegative = array_map([$this, 'negtive'], $codeRecettes);
    $codeCF               = array_merge($codeDepenses, $codeRecettesNegative);
    echo $this->generateBalanceSubTotalLineHtml('Insuffisance (BR - AG) (case CF)', $codeCF, \company_tax_form_type::FORM_2035);

    $codeFrais = ['CG', 'CH', 'CK', 'CL', 'CM'];
    echo $this->generateBalanceLineHtml($codeFrais, \company_tax_form_type::FORM_2035);

    $codeCN = array_merge($codeCF, $codeFrais);
    echo $this->generateBalanceSubTotalLineHtml('Total CF, CG, CH, CK, CL et CM (case CN)', $codeCN, \company_tax_form_type::FORM_2035);

    $codeCNNegative = array_map([$this, 'negtive'], $codeCN);
    echo $this->generateBalanceSubTotalLineHtml('Bénéfice (CE - CN) (case CP)', array_merge($codeCE, $codeCNNegative), \company_tax_form_type::FORM_2035);

    $codeCENegative = array_map([$this, 'negtive'], $codeCE);
    echo $this->generateBalanceSubTotalLineHtml('Déficit (CN - CE) (case CR)', array_merge($codeCN, $codeCENegative), \company_tax_form_type::FORM_2035);
    ?>
    </tbody>
</table>
