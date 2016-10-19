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

        $annualAccounts    = array();
        foreach ($balanceSheetIds as $balanceSheetId) {
            $companyBalance->get($balanceSheetId);
            $balanceTypes = $companyBalanceDetailsType->getAllByType($companyBalance->id_company_tax_form_type);
            $balanceTypes = array_column($balanceTypes, 'code', 'id_balance_type');

            $annualAccounts[$balanceSheetId] = array_fill_keys($balanceTypes, 0);

            $balanceSheetDetails = $companyBalanceDetails->select('id_bilan =' . $balanceSheetId);
            foreach ($balanceSheetDetails as $field) {
                $annualAccounts[$balanceSheetId][$balanceTypes[$field['id_balance_type']]] = $field['value'];
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

        $oCompanyDebtsAssets->immobilisations_corporelles        = $balances[$balanceSheetId]['AN'] + $balances[$balanceSheetId]['AP'] + $balances[$balanceSheetId]['AR'] + $balances[$balanceSheetId]['AT'] + $balances[$balanceSheetId]['AV'] + $balances[$balanceSheetId]['AX'];
        $oCompanyDebtsAssets->immobilisations_incorporelles      = $balances[$balanceSheetId]['AB'] + $balances[$balanceSheetId]['AD'] + $balances[$balanceSheetId]['AF'] + $balances[$balanceSheetId]['AH'] + $balances[$balanceSheetId]['AJ'] + $balances[$balanceSheetId]['AL'];
        $oCompanyDebtsAssets->immobilisations_financieres        = $balances[$balanceSheetId]['CS'] + $balances[$balanceSheetId]['CU'] + $balances[$balanceSheetId]['BB'] + $balances[$balanceSheetId]['BD'] + $balances[$balanceSheetId]['BF'] + $balances[$balanceSheetId]['BH'];
        $oCompanyDebtsAssets->stocks                             = $balances[$balanceSheetId]['BL'] + $balances[$balanceSheetId]['BN'] + $balances[$balanceSheetId]['BP'] + $balances[$balanceSheetId]['BR'] + $balances[$balanceSheetId]['BT'];
        $oCompanyDebtsAssets->creances_clients                   = $balances[$balanceSheetId]['BV'] + $balances[$balanceSheetId]['BX'] + $balances[$balanceSheetId]['BZ'] + $balances[$balanceSheetId]['CB'];
        $oCompanyDebtsAssets->disponibilites                     = $balances[$balanceSheetId]['CF'];
        $oCompanyDebtsAssets->valeurs_mobilieres_de_placement    = $balances[$balanceSheetId]['CD'];
        $oCompanyDebtsAssets->comptes_regularisation_actif       = $balances[$balanceSheetId]['CH'] + $balances[$balanceSheetId]['CW'] + $balances[$balanceSheetId]['CM'] + $balances[$balanceSheetId]['CN'];
        $oCompanyDebtsAssets->capitaux_propres                   = $balances[$balanceSheetId]['DL'] + $balances[$balanceSheetId]['DO'];
        $oCompanyDebtsAssets->provisions_pour_risques_et_charges = $balances[$balanceSheetId]['CK'] + $balances[$balanceSheetId]['DR'];
        $oCompanyDebtsAssets->amortissement_sur_immo             = $balances[$balanceSheetId]['BK'];
        $oCompanyDebtsAssets->dettes_financieres                 = $balances[$balanceSheetId]['DS'] + $balances[$balanceSheetId]['DT'] + $balances[$balanceSheetId]['DU'] + $balances[$balanceSheetId]['DV'];
        $oCompanyDebtsAssets->dettes_fournisseurs                = $balances[$balanceSheetId]['DW'] + $balances[$balanceSheetId]['DX'];
        $oCompanyDebtsAssets->autres_dettes                      = $balances[$balanceSheetId]['DY'] + $balances[$balanceSheetId]['DZ'] + $balances[$balanceSheetId]['EA'];
        $oCompanyDebtsAssets->comptes_regularisation_passif      = $balances[$balanceSheetId]['EB'] + $balances[$balanceSheetId]['ED'];
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

        $companyBalanceSheet->ca                          = $aBalances[$balanceSheetId]['FL'];
        $companyBalanceSheet->resultat_brute_exploitation = $aBalances[$balanceSheetId]['GG'] + $aBalances[$balanceSheetId]['GA'] + $aBalances[$balanceSheetId]['GB'] + $aBalances[$balanceSheetId]['GC'] + $aBalances[$balanceSheetId]['GD'] - $aBalances[$balanceSheetId]['FP'] - $aBalances[$balanceSheetId]['FQ'] + $aBalances[$balanceSheetId]['GE'];
        $companyBalanceSheet->resultat_exploitation       = $aBalances[$balanceSheetId]['GG'];
        $companyBalanceSheet->resultat_financier          = $aBalances[$balanceSheetId]['GV'];
        $companyBalanceSheet->produit_exceptionnel        = $aBalances[$balanceSheetId]['HA'] + $aBalances[$balanceSheetId]['HB'] + $aBalances[$balanceSheetId]['HC'];
        $companyBalanceSheet->charges_exceptionnelles     = $aBalances[$balanceSheetId]['HE'] + $aBalances[$balanceSheetId]['HF'] + $aBalances[$balanceSheetId]['HG'];
        $companyBalanceSheet->resultat_exceptionnel       = $companyBalanceSheet->produit_exceptionnel - $companyBalanceSheet->charges_exceptionnelles;
        $companyBalanceSheet->resultat_net                = $aBalances[$balanceSheetId]['HN'];
        $companyBalanceSheet->investissements             = $aBalances[$balanceSheetId]['0J'];
        $companyBalanceSheet->update();
    }

}
