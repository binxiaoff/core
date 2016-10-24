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
        /** @var \company_balance $companyBanlanceDetails */
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

        $setting->get('Entreprises fundés au passage du risque lot 1', 'type');
        $fundedCompanies = explode(',', $setting->value);

        $companyBalanceSheet->get($balanceSheetId);

        if (in_array($companyBalanceSheet->id_company, $fundedCompanies)) {
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

    public function calculateAnnualAccountFromBalance($balanceSheetId)
    {
        /** @var \settings $setting */
        $setting = $this->entityManager->getRepository('settings');
        /** @var \companies_bilans $companyBalanceSheet */
        $companyBalanceSheet = $this->entityManager->getRepository('companies_bilans');

        $companyBalanceSheet->get($balanceSheetId);

        $setting->get('Entreprises fundés au passage du risque lot 1', 'type');
        $fundedCompanies = explode(',', $setting->value);

        if (in_array($companyBalanceSheet->id_company, $fundedCompanies)) {
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

    public function prepareDisplayForm2033($annualAccounts)
    {
        /** @var \company_tax_form_type $companyBalanceTaxType */
        $companyBalanceTaxType     = $this->entityManager->getRepository('company_tax_form_type');
        /** @var \company_balance_type $companyBalanceDetailsType */
        $companyBalanceDetailsType = $this->entityManager->getRepository('company_balance_type');

        $companyBalanceTaxType->get(\company_tax_form_type::FORM_2033, 'label');
        $allFields = $companyBalanceDetailsType->getAllByType($companyBalanceTaxType->id_type);
        foreach ($allFields as $field) {

        }
    }
}
