<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

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

        if (in_array($companyBalanceSheet->id_company, $fundedCompanies) || $companyTaxFormType->label != \company_tax_form_type::FORM_2033) {
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
        /** @var \company_tax_form_type $companyTaxFormType */
        $companyTaxFormType = $this->entityManager->getRepository('company_tax_form_type');

        $balanceSheetId = $companyBalanceSheet->id_bilan;
        $companyTaxFormType->get($companyBalanceSheet->id_company_tax_form_type);

        $setting->get('Entreprises fundés au passage du risque lot 1', 'type');
        $fundedCompanies = explode(',', $setting->value);

        if (in_array($companyBalanceSheet->id_company, $fundedCompanies) || $companyTaxFormType->label != \company_tax_form_type::FORM_2033) {
            return;
        }

        $aBalances = $this->getBalanceSheetsByAnnualAccount(array($balanceSheetId));

        $companyBalanceSheet->ca                          = $aBalances[$balanceSheetId]['details']['FL'];
        $companyBalanceSheet->resultat_brute_exploitation = $aBalances[$balanceSheetId]['details']['GG'] + $aBalances[$balanceSheetId]['details']['GA'] + $aBalances[$balanceSheetId]['details']['GB'] + $aBalances[$balanceSheetId]['details']['GC'] + $aBalances[$balanceSheetId]['details']['GD'] - $aBalances[$balanceSheetId]['details']['FP'] - $aBalances[$balanceSheetId]['details']['FQ'] + $aBalances[$balanceSheetId]['details']['GE'];
        $companyBalanceSheet->resultat_exploitation       = $aBalances[$balanceSheetId]['details']['GG'];
        $companyBalanceSheet->resultat_financier          = $aBalances[$balanceSheetId]['details']['GV'];
        $companyBalanceSheet->produit_exceptionnel        = $aBalances[$balanceSheetId]['details']['HA'] + $aBalances[$balanceSheetId]['details']['HB'] + $aBalances[$balanceSheetId]['details']['HC'];
        $companyBalanceSheet->charges_exceptionnelles     = $aBalances[$balanceSheetId]['details']['HE'] + $aBalances[$balanceSheetId]['details']['HF'] + $aBalances[$balanceSheetId]['details']['HG'];
        $companyBalanceSheet->resultat_exceptionnel       = $companyBalanceSheet->produit_exceptionnel - $companyBalanceSheet->charges_exceptionnelles;
        $companyBalanceSheet->resultat_net                = $aBalances[$balanceSheetId]['details']['HN'];
        $companyBalanceSheet->investissements             = $aBalances[$balanceSheetId]['details']['0J'];
        $companyBalanceSheet->update();
    }

    /**
     * @param \companies $company
     *
     * @return \company_tax_form_type|null
     */
    public function detectTaxFormType(\companies $company)
    {
        $taxFormType = $this->entityManager->getRepository('company_tax_form_type');
        if (false === empty($company->rcs)) { // We are only capable of managing the fiscal form for a "RCS"( which is 2033)
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

    public function getIncomeStatement(\companies_bilans $companyBalanceSheet)
    {
        /** @var \company_tax_form_type $companyTaxFormType */
        $companyTaxFormType = $this->entityManager->getRepository('company_tax_form_type');
        $companyTaxFormType->get($companyBalanceSheet->id_company_tax_form_type);

        switch ($companyTaxFormType->label){
            case \company_tax_form_type::FORM_2033 :
                return $this->getIncomeStatement2033($companyBalanceSheet);
            case \company_tax_form_type::FORM_2035 :
                return $this->getIncomeStatement2035($companyBalanceSheet);
        }
    }

    private function getIncomeStatement2035(\companies_bilans $companyBalanceSheet)
    {
        $balanceSheetId = $companyBalanceSheet->id_bilan;
        $balanceDetails = $this->getBalanceSheetsByAnnualAccount(array($balanceSheetId));

        $AG = $balanceDetails[$balanceSheetId]['details']['AD'] + $balanceDetails[$balanceSheetId]['details']['AE'] + $balanceDetails[$balanceSheetId]['details']['AF'];

        $BR = $balanceDetails[$balanceSheetId]['details']['BA'] + $balanceDetails[$balanceSheetId]['details']['BB'] + $balanceDetails[$balanceSheetId]['details']['BC']
            + $balanceDetails[$balanceSheetId]['details']['BD'] + $balanceDetails[$balanceSheetId]['details']['BF'] + $balanceDetails[$balanceSheetId]['details']['BG']
            + $balanceDetails[$balanceSheetId]['details']['BH'] + $balanceDetails[$balanceSheetId]['details']['BJ'] + $balanceDetails[$balanceSheetId]['details']['BK']
            + $balanceDetails[$balanceSheetId]['details']['BM'] + $balanceDetails[$balanceSheetId]['details']['BN'] + $balanceDetails[$balanceSheetId]['details']['BP']
            + $balanceDetails[$balanceSheetId]['details']['BS'] + $balanceDetails[$balanceSheetId]['details']['BV'] + $balanceDetails[$balanceSheetId]['details']['JY'];

        $CA = $AG - $BR;

        $CE = $CA +  $balanceDetails[$balanceSheetId]['details']['CB'] + $balanceDetails[$balanceSheetId]['details']['CC'] + $balanceDetails[$balanceSheetId]['details']['CD'];

        $CF = $BR - $AG;

        $CN = $CF + $balanceDetails[$balanceSheetId]['details']['CG'] + $balanceDetails[$balanceSheetId]['details']['CH'] + $balanceDetails[$balanceSheetId]['details']['CK']
            + $balanceDetails[$balanceSheetId]['details']['CL'] + $balanceDetails[$balanceSheetId]['details']['CM'];

        $incomeStatement['details'] = [
            'AG' => [
                'label' => 'company-balance_2035-recettes',
                'value' => $AG
            ],
            'BA' => [
                'label' => 'company-balance_2035-achats',
                'value' => $balanceDetails[$balanceSheetId]['details']['BA']
            ],
            'BB' => [
                'label' => 'company-balance_2035-frais-personnel',
                'value' => $balanceDetails[$balanceSheetId]['details']['BB']
            ],
            'BC' => [
                'label' => 'company-balance_2035-charges-sociales',
                'value' => $balanceDetails[$balanceSheetId]['details']['BC']
            ],
            'BN' => [
                'label' => 'company-balance_2035-frais-financiers',
                'value' => $balanceDetails[$balanceSheetId]['details']['BN']
            ],
            'BR' => [
                'label' => 'company-balance_2035-total-depenses',
                'value' => $BR
            ],
            'CA' => [
                'label' => 'company-balance_2035-excedent-brut',
                'value' => $CA
            ],
            'CF' => [
                'label' => 'company-balance_2035-frais-etablissement',
                'value' => $BR - $AG
            ],
            'CG' => [
                'label' => 'company-balance_2035-dotation-aux-ammortissements',
                'value' => $balanceDetails[$balanceSheetId]['details']['CG']
            ],
            'CP' => [
                'label' => 'company-balance_2035-benefice-net',
                'value' => $CE - $CN
            ],
        ];

        $incomeStatement['form_type'] = \company_tax_form_type::FORM_2035;

        return $incomeStatement;
    }
}
