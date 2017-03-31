<?php
$aAnnualAccountsYears    = array_keys($this->aBalanceSheets);
$iOldestAnnualAccountsId = end($aAnnualAccountsYears);
?>
<table class="tablesorter annual-accounts" style="text-align:center;">
    <thead>
    <tr>
        <th colspan="2">
            <img class="collapse_expand expanded" src="<?= $this->surl ?>/images/admin/up.png" alt="Déplier/replier"/>
            Liasse BNC
        </th>
        <?php foreach ($this->lbilans as $aAnnualAccounts) : ?>
            <th width="180" class="annual_accounts_dates" data-closing="<?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?>" data-duration="<?= $aAnnualAccounts['duree_exercice_fiscal'] ?>" data-annual-account="<?= $aAnnualAccounts['id_bilan'] ?>"><?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)</th>
            <?php if ($aAnnualAccounts['id_bilan'] != $iOldestAnnualAccountsId) { ?><th width="50"></th><?php } ?>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php
    echo $this->generateBalanceLineHtml(['AA', 'AB', 'AC'], \company_tax_form_type::FORM_2035);

    $codeRecettes = ['AD', 'AE', 'AF'];
    echo $this->generateBalanceGroupHtml($this->translator->trans('income-statement_2035-recettes'), $codeRecettes, \company_tax_form_type::FORM_2035);

    $codeDepenses = ['BA', 'BB', 'BC', 'BD', 'JY', 'BS', 'BV', 'BF', 'BG', 'BH', 'BJ', 'BK', 'BM', 'BN', 'BP'];
    echo $this->generateBalanceGroupHtml($this->translator->trans('income-statement_2035-total-depenses'), $codeDepenses, \company_tax_form_type::FORM_2035);

    $codeDepensesNegative = array_map([$this, 'negative'], $codeDepenses);
    $result               = $this->generateBalanceSubTotalLineHtml($this->translator->trans('income-statement_2035-excedent-brut'), array_merge($codeRecettes, $codeDepensesNegative), \company_tax_form_type::FORM_2035, '', false);
    $cumulativeCell['CA'] = $result['amounts'];
    echo $result['html'];

    $codeBenefice = ['CB', 'CC', 'CD'];
    echo $this->generateBalanceLineHtml($codeBenefice, \company_tax_form_type::FORM_2035);

    $result               = $this->generateBalanceSubTotalLineHtml('Total CA, CB, CC et CD (case CE)', $codeBenefice, \company_tax_form_type::FORM_2035, '', true, $cumulativeCell['CA']);
    $cumulativeCell['CE'] = $result['amounts'];
    echo $result['html'];

    $codeRecettesNegative = array_map([$this, 'negative'], $codeRecettes);
    $codeCF               = array_merge($codeDepenses, $codeRecettesNegative);
    $result               = $this->generateBalanceSubTotalLineHtml($this->translator->trans('income-statement_2035-insuffisance'), $codeCF, \company_tax_form_type::FORM_2035, '', false);
    $cumulativeCell['CF'] = $result['amounts'];
    echo $result['html'];

    $codeFrais = ['CG', 'CH', 'CK', 'CL', 'CM'];
    echo $this->generateBalanceLineHtml($codeFrais, \company_tax_form_type::FORM_2035);

    $result               = $this->generateBalanceSubTotalLineHtml('Total CF, CG, CH, CK, CL et CM (case CN)', $codeFrais, \company_tax_form_type::FORM_2035, '', true, $cumulativeCell['CF']);
    $cumulativeCell['CN'] = $result['amounts'];
    echo $result['html'];

    foreach ($cumulativeCell['CE'] as $key => $value) {
        $cumulativeCell['CP'][$key] = $value - $cumulativeCell['CN'][$key];
    }
    $cumulativeCell['CR'] = array_map([$this, 'negative'], $cumulativeCell['CP']);

    echo $this->generateBalanceSubTotalLineHtml($this->translator->trans('income-statement_2035-benefice-net'), [], \company_tax_form_type::FORM_2035, '', false, $cumulativeCell['CP'])['html'];
    echo $this->generateBalanceSubTotalLineHtml($this->translator->trans('income-statement_2035-deficit'), [], \company_tax_form_type::FORM_2035, '', false, $cumulativeCell['CR'])['html'];
    ?>
    </tbody>
</table>
