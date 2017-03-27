<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class CompanyBalanceSheetManager
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getBalanceSheetsByAnnualAccount(array $balanceSheetIds)
    {
        /** @var \companies_bilans $companyBalance */
        $companyBalance = $this->entityManager->getRepository('companies_bilans');
        /** @var \company_balance $companyBalanceDetails */
        $companyBalanceDetails = $this->entityManager->getRepository('company_balance');
        /** @var \company_balance_type $companyBalanceDetailsType */
        $companyBalanceDetailsType = $this->entityManager->getRepository('company_balance_type');
        /** @var \company_tax_form_type $companyTaxFormType */
        $companyTaxFormType = $this->entityManager->getRepository('company_tax_form_type');

        $annualAccounts    = array();

        foreach ($balanceSheetIds as $balanceSheetId) {
            $companyBalance->get($balanceSheetId);
            $companyTaxFormType->get($companyBalance->id_company_tax_form_type);
            $balanceTypes = $companyBalanceDetailsType->getAllByType($companyBalance->id_company_tax_form_type);
            $balanceTypes = array_column($balanceTypes, 'code', 'id_balance_type');

            $annualAccounts[$balanceSheetId]['form_type'] = $companyTaxFormType->label;
            $annualAccounts[$balanceSheetId]['details'] = array_fill_keys($balanceTypes, 0);

            $balanceSheetDetails = $companyBalanceDetails->select('id_bilan =' . $balanceSheetId);
            foreach ($balanceSheetDetails as $field) {
                $annualAccounts[$balanceSheetId]['details'][$balanceTypes[$field['id_balance_type']]] = $field['value'];
            }
        }
        return $annualAccounts;
    }

    public function calculateDebtsAssetsFromBalance($balanceSheetId)
    {
        /** @var \settings $setting */
        $setting = $this->entityManager->getRepository('settings');
        /** @var \companies_bilans $companyBalanceSheet */
        $companyBalanceSheet = $this->entityManager->getRepository('companies_bilans');
        /** @var \companies_actif_passif $oCompanyDebtsAssets */
        $oCompanyDebtsAssets = $this->entityManager->getRepository('companies_actif_passif');
        /** @var \company_tax_form_type $companyTaxFormType */
        $companyTaxFormType = $this->entityManager->getRepository('company_tax_form_type');

        $setting->get('Entreprises fundés au passage du risque lot 1', 'type');
        $fundedCompanies = explode(',', $setting->value);

        $companyBalanceSheet->get($balanceSheetId);
        $companyTaxFormType->get($companyBalanceSheet->id_company_tax_form_type);

        if (in_array($companyBalanceSheet->id_company, $fundedCompanies)
            || $companyTaxFormType->label != \company_tax_form_type::FORM_2033
            || false === $oCompanyDebtsAssets->get($balanceSheetId, 'id_bilan')
        ) {
            return;
        }

        $balances = $this->getBalanceSheetsByAnnualAccount(array($balanceSheetId));

        $oCompanyDebtsAssets->immobilisations_corporelles        = $balances[$balanceSheetId]['details']['AN'] + $balances[$balanceSheetId]['details']['AP'] + $balances[$balanceSheetId]['details']['AR'] + $balances[$balanceSheetId]['details']['AT'] + $balances[$balanceSheetId]['details']['AV'] + $balances[$balanceSheetId]['details']['AX'];
        $oCompanyDebtsAssets->immobilisations_incorporelles      = $balances[$balanceSheetId]['details']['AB'] + $balances[$balanceSheetId]['details']['AD'] + $balances[$balanceSheetId]['details']['AF'] + $balances[$balanceSheetId]['details']['AH'] + $balances[$balanceSheetId]['details']['AJ'] + $balances[$balanceSheetId]['details']['AL'];
        $oCompanyDebtsAssets->immobilisations_financieres        = $balances[$balanceSheetId]['details']['CS'] + $balances[$balanceSheetId]['details']['CU'] + $balances[$balanceSheetId]['details']['BB'] + $balances[$balanceSheetId]['details']['BD'] + $balances[$balanceSheetId]['details']['BF'] + $balances[$balanceSheetId]['details']['BH'];
        $oCompanyDebtsAssets->stocks                             = $balances[$balanceSheetId]['details']['BL'] + $balances[$balanceSheetId]['details']['BN'] + $balances[$balanceSheetId]['details']['BP'] + $balances[$balanceSheetId]['details']['BR'] + $balances[$balanceSheetId]['details']['BT'];
        $oCompanyDebtsAssets->creances_clients                   = $balances[$balanceSheetId]['details']['BV'] + $balances[$balanceSheetId]['details']['BX'] + $balances[$balanceSheetId]['details']['BZ'] + $balances[$balanceSheetId]['details']['CB'];
        $oCompanyDebtsAssets->disponibilites                     = $balances[$balanceSheetId]['details']['CF'];
        $oCompanyDebtsAssets->valeurs_mobilieres_de_placement    = $balances[$balanceSheetId]['details']['CD'];
        $oCompanyDebtsAssets->comptes_regularisation_actif       = $balances[$balanceSheetId]['details']['CH'] + $balances[$balanceSheetId]['details']['CW'] + $balances[$balanceSheetId]['details']['CM'] + $balances[$balanceSheetId]['details']['CN'];
        $oCompanyDebtsAssets->capitaux_propres                   = $balances[$balanceSheetId]['details']['DL'] + $balances[$balanceSheetId]['details']['DO'];
        $oCompanyDebtsAssets->provisions_pour_risques_et_charges = $balances[$balanceSheetId]['details']['CK'] + $balances[$balanceSheetId]['details']['DR'];
        $oCompanyDebtsAssets->amortissement_sur_immo             = $balances[$balanceSheetId]['details']['BK'];
        $oCompanyDebtsAssets->dettes_financieres                 = $balances[$balanceSheetId]['details']['DS'] + $balances[$balanceSheetId]['details']['DT'] + $balances[$balanceSheetId]['details']['DU'] + $balances[$balanceSheetId]['details']['DV'];
        $oCompanyDebtsAssets->dettes_fournisseurs                = $balances[$balanceSheetId]['details']['DW'] + $balances[$balanceSheetId]['details']['DX'];
        $oCompanyDebtsAssets->autres_dettes                      = $balances[$balanceSheetId]['details']['DY'] + $balances[$balanceSheetId]['details']['DZ'] + $balances[$balanceSheetId]['details']['EA'];
        $oCompanyDebtsAssets->comptes_regularisation_passif      = $balances[$balanceSheetId]['details']['EB'] + $balances[$balanceSheetId]['details']['ED'];
        $oCompanyDebtsAssets->update();
    }

    private function getIncomeStatement2033(\companies_bilans $companyBalanceSheet)
    {
        /** @var \settings $setting */
        $setting = $this->entityManager->getRepository('settings');
        $setting->get('Entreprises fundés au passage du risque lot 1', 'type');
        $beforeRiskCompanies = explode(',', $setting->value);

        $balanceSheetId = $companyBalanceSheet->id_bilan;

        $incomeStatement['form_type'] = \company_tax_form_type::FORM_2033;

        if (in_array($companyBalanceSheet->id_company, $beforeRiskCompanies)) {
            $incomeStatement['details'] = [
                'project-detail_finance-column-ca'                         => $companyBalanceSheet->ca,
                'project-detail_finance-column-resultat-brut-exploitation' => $companyBalanceSheet->resultat_brute_exploitation,
                'project-detail_finance-column-resultat-exploitation'      => $companyBalanceSheet->resultat_exploitation,
                'project-detail_finance-column-investissements'            => $companyBalanceSheet->investissements,
            ];
        } else {
            $aBalances = $this->getBalanceSheetsByAnnualAccount([$balanceSheetId]);

            $turnover             = $aBalances[$balanceSheetId]['details']['FL'];
            $grossOperationIncome = $aBalances[$balanceSheetId]['details']['GG'] + $aBalances[$balanceSheetId]['details']['GA'] + $aBalances[$balanceSheetId]['details']['GB']
                + $aBalances[$balanceSheetId]['details']['GC'] + $aBalances[$balanceSheetId]['details']['GD']
                - $aBalances[$balanceSheetId]['details']['FP'] - $aBalances[$balanceSheetId]['details']['FQ'] + $aBalances[$balanceSheetId]['details']['GE'];
            $operationIncome      = $aBalances[$balanceSheetId]['details']['GG'];
            $financialResult      = $aBalances[$balanceSheetId]['details']['GV'];
            $nonRecurringIncome   = $aBalances[$balanceSheetId]['details']['HA'] + $aBalances[$balanceSheetId]['details']['HB'] + $aBalances[$balanceSheetId]['details']['HC'];
            $nonRecurringCharge   = $aBalances[$balanceSheetId]['details']['HE'] + $aBalances[$balanceSheetId]['details']['HF'] + $aBalances[$balanceSheetId]['details']['HG'];
            $nonRecurringResult   = $nonRecurringIncome - $nonRecurringCharge;
            $netIncome            = $aBalances[$balanceSheetId]['details']['HN'];
            $investments          = $aBalances[$balanceSheetId]['details']['0J'];

            $incomeStatement['details'] = [
                'project-detail_finance-column-ca'                         => $turnover,
                'project-detail_finance-column-resultat-brut-exploitation' => $grossOperationIncome,
                'project-detail_finance-column-resultat-exploitation'      => $operationIncome,
                'project-detail_finance-column-resultat-financier'         => $financialResult,
                'project-detail_finance-column-produit-exceptionnel'       => $nonRecurringIncome,
                'project-detail_finance-column-charges-exceptionnelles'    => $nonRecurringCharge,
                'project-detail_finance-column-resultat-exceptionnel'      => $nonRecurringResult,
                'project-detail_finance-column-resultat-net'               => $netIncome,
                'project-detail_finance-column-investissements'            => $investments,
            ];
        }

        return $incomeStatement;
    }

    /**
     * @param \companies | Companies $company
     *
     * @return \company_tax_form_type|null
     */
    public function detectTaxFormType($company)
    {
        $taxFormType = $this->entityManager->getRepository('company_tax_form_type');
        $companyRcs = '';

        if ($company instanceof \companies) {
            $companyRcs = $company->rcs;
        }

        if ($company instanceof Companies) {
            $companyRcs = $company->getRcs();
        }

        if (false === empty($companyRcs)) { // We are only capable of managing the fiscal form for a "RCS"( which is 2033)
            $taxFormType->get(\company_tax_form_type::FORM_2033, 'label');

            return $taxFormType;
        }

        return null;
    }

    /**
     * @param \companies_bilans $companyBalanceSheet
     * @param $box
     * @param $value
     */
    public function saveBalanceSheetDetails(\companies_bilans $companyBalanceSheet, $box, $value)
    {
        /** @var \company_balance_type $companyBalanceDetailsType */
        $companyBalanceDetailsType = $this->entityManager->getRepository('company_balance_type');
        /** @var \company_balance $companyBalanceDetails */
        $companyBalanceDetails = $this->entityManager->getRepository('company_balance');

        if ($companyBalanceDetailsType->get($box, 'id_company_tax_form_type = ' . $companyBalanceSheet->id_company_tax_form_type . ' AND code')) {
            if ($companyBalanceDetails->exist('id_balance_type = ' . $companyBalanceDetailsType->id_balance_type . ' AND id_bilan = ' . $companyBalanceSheet->id_bilan)){
                $companyBalanceDetails->get($companyBalanceDetailsType->id_balance_type, 'id_bilan = ' . $companyBalanceSheet->id_bilan . ' AND id_balance_type');
                $companyBalanceDetails->value = $value;
                $companyBalanceDetails->update();
            } else {
                $companyBalanceDetails->id_bilan        = $companyBalanceSheet->id_bilan;
                $companyBalanceDetails->id_balance_type = $companyBalanceDetailsType->id_balance_type;
                $companyBalanceDetails->value           = $value;
                $companyBalanceDetails->create();
            }
        }
    }

    /**
     * @param \companies_bilans $companyBalanceSheet
     */
    public function removeBalanceSheet(\companies_bilans $companyBalanceSheet, \projects $project)
    {
        /** @var \company_balance $companyBalanceDetails */
        $companyBalanceDetails = $this->entityManager->getRepository('company_balance');
        $companyBalanceDetails->delete($companyBalanceSheet->id_bilan, 'id_bilan');
        $companyBalanceSheet->delete($companyBalanceSheet->id_bilan);

        if ($companyBalanceSheet->id_bilan === $project->id_dernier_bilan) {
            $balance = $companyBalanceSheet->select('id_company = ' . $project->id_company,  'cloture_exercice_fiscal DESC', 0, 1);
            if (empty($balances)) {
                $project->id_dernier_bilan = '';
            } else {
                $project->id_dernier_bilan = $balance[0]['id_bilan'];
            }
            $project->update();
        }
    }

    /**
     * @param \companies_bilans $companyBalanceSheet
     * @param bool              $excludeNonPositiveLines2035
     * @return mixed|null
     */
    public function getIncomeStatement(\companies_bilans $companyBalanceSheet, $excludeNonPositiveLines2035 = false)
    {
        /** @var \company_tax_form_type $companyTaxFormType */
        $companyTaxFormType = $this->entityManager->getRepository('company_tax_form_type');
        $companyTaxFormType->get($companyBalanceSheet->id_company_tax_form_type);

        switch ($companyTaxFormType->label){
            case \company_tax_form_type::FORM_2033 :
                return $this->getIncomeStatement2033($companyBalanceSheet);
            case \company_tax_form_type::FORM_2035 :
                return $this->getIncomeStatement2035($companyBalanceSheet, $excludeNonPositiveLines2035);
        }

        return null;
    }

    /**
     * @param \companies_bilans $companyBalanceSheet
     * @param bool              $excludeNonPositiveLines
     * @return mixed
     */
    private function getIncomeStatement2035(\companies_bilans $companyBalanceSheet, $excludeNonPositiveLines = false)
    {
        $optionalLines                = ['income-statement_2035-excedent-brut', 'income-statement_2035-insuffisance', 'income-statement_2035-benefice-net', 'income-statement_2035-deficit'];
        $incomeStatement['form_type'] = \company_tax_form_type::FORM_2035;

        $balanceSheetId = $companyBalanceSheet->id_bilan;
        $balanceDetails = $this->getBalanceSheetsByAnnualAccount([$balanceSheetId]);

        $AG = $balanceDetails[$balanceSheetId]['details']['AD'] + $balanceDetails[$balanceSheetId]['details']['AE'] + $balanceDetails[$balanceSheetId]['details']['AF'];

        $BR = $balanceDetails[$balanceSheetId]['details']['BA'] + $balanceDetails[$balanceSheetId]['details']['BB'] + $balanceDetails[$balanceSheetId]['details']['BC']
            + $balanceDetails[$balanceSheetId]['details']['BD'] + $balanceDetails[$balanceSheetId]['details']['BF'] + $balanceDetails[$balanceSheetId]['details']['BG']
            + $balanceDetails[$balanceSheetId]['details']['BH'] + $balanceDetails[$balanceSheetId]['details']['BJ'] + $balanceDetails[$balanceSheetId]['details']['BK']
            + $balanceDetails[$balanceSheetId]['details']['BM'] + $balanceDetails[$balanceSheetId]['details']['BN'] + $balanceDetails[$balanceSheetId]['details']['BP']
            + $balanceDetails[$balanceSheetId]['details']['BS'] + $balanceDetails[$balanceSheetId]['details']['BV'] + $balanceDetails[$balanceSheetId]['details']['JY'];

        $CA = $AG - $BR;
        $CA = $CA < 0 ? 0 : $CA;

        $otherFinancialProduct = $balanceDetails[$balanceSheetId]['details']['CB'] + $balanceDetails[$balanceSheetId]['details']['CC'] + $balanceDetails[$balanceSheetId]['details']['CD'];
        $otherObligations      = $balanceDetails[$balanceSheetId]['details']['CG'] + $balanceDetails[$balanceSheetId]['details']['CH'] + $balanceDetails[$balanceSheetId]['details']['CK'] + $balanceDetails[$balanceSheetId]['details']['CL'] + $balanceDetails[$balanceSheetId]['details']['CM'];
        $benefit               = $CA + $otherFinancialProduct - $otherObligations;

        $incomeStatement['details'] = [
            'income-statement_2035-recettes'        => $AG,
            'income-statement_2035-achats'          => $balanceDetails[$balanceSheetId]['details']['BA'],
            'income-statement_2035-frais-personnel' => $balanceDetails[$balanceSheetId]['details']['BB'] + $balanceDetails[$balanceSheetId]['details']['BC'],
            'income-statement_2035-autres-depenses' => $BR - $balanceDetails[$balanceSheetId]['details']['BA'] - $balanceDetails[$balanceSheetId]['details']['BB'] - $balanceDetails[$balanceSheetId]['details']['BC'],
            'income-statement_2035-total-depenses'  => $BR,
            'income-statement_2035-excedent-brut'   => $CA,
            'income-statement_2035-autres-produits' => $otherFinancialProduct,
            'income-statement_2035-autres-charges'  => $otherObligations,
            'income-statement_2035-benefice-net'    => $benefit < 0 ? 0 : $benefit,
            'income-statement_2035-insuffisance'    => ($AG < $BR) ? $BR - $AG : 0,
            'income-statement_2035-deficit'         => (-$benefit) < 0 ? 0 : -$benefit
        ];

        if ($excludeNonPositiveLines) {
            foreach ($optionalLines as $label) {
                if (isset($incomeStatement['details'][$label]) && $incomeStatement['details'][$label] <= 0) {
                    unset($incomeStatement['details'][$label]);
                }
            }
        }

        return $incomeStatement;
    }
}
