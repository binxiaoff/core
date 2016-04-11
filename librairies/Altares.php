<?php

namespace Unilend\librairies;

use Unilend\core\Loader;

class Altares
{
    const RESPONSE_CODE_INACTIVE                       = 1;
    const RESPONSE_CODE_NOT_REGISTERED                 = 2;
    const RESPONSE_CODE_PROCEDURE                      = 3;
    const RESPONSE_CODE_OLD_ANNUAL_ACCOUNTS            = 4;
    const RESPONSE_CODE_NEGATIVE_CAPITAL_STOCK         = 5;
    const RESPONSE_CODE_NEGATIVE_RAW_OPERATING_INCOMES = 6;
    const RESPONSE_CODE_UNKNOWN_SIREN                  = 7;
    const RESPONSE_CODE_ELIGIBLE                       = 8;
    const RESPONSE_CODE_NO_ANNUAL_ACCOUNTS             = 9;

    /**
     * @var string
     */
    private $sIdentification;

    /**
     * @var \settings
     */
    private $oSettings;

    public function __construct()
    {
        ini_set('default_socket_timeout', 60);

        $this->oSettings = Loader::loadData('settings');
        $this->oSettings->get('Altares login', 'type');

        $this->sIdentification = $this->oSettings->value;

        $this->oSettings->get('Altares mot de passe', 'type');
        $this->sIdentification .= '|' . $this->oSettings->value;
    }

    /**
     * Retrieve getEligibility WS data
     * @param int $iSIREN
     * @return mixed
     */
    public function getEligibility($iSIREN)
    {
        $this->oSettings->get('Altares WSDL Eligibility', 'type');

        return $this->soapCall($this->oSettings->value, 'getEligibility', array('siren' => $iSIREN));
    }

    /**
     * Retrieve getDerniersBilans WS data
     * @param int $iSIREN
     * @param int $iSheetsCount
     * @return mixed
     */
    public function getBalanceSheets($iSIREN, $iSheetsCount = 3)
    {
        $this->oSettings->get('Altares WSDL CallistoIdentite', 'type');

        return $this->soapCall($this->oSettings->value, 'getDerniersBilans', array('siren' => $iSIREN, 'nbBilans' => $iSheetsCount));
    }

    /**
     * Set main data of the given company according to Altares response
     * @param \companies $oCompany
     * @param \stdClass|null $oEligibilityInfo
     */
    public function setCompanyData(\companies &$oCompany, $oEligibilityInfo = null)
    {
        if (is_null($oEligibilityInfo)) {
            $oEligibilityInfo = $this->getEligibility($oCompany->siren)->myInfo;
        }

        $oCompany->phone = isset($oEligibilityInfo->siege->telephone) ? str_replace(' ', '', $oEligibilityInfo->siege->telephone) : '';

        if (isset($oEligibilityInfo->identite) && is_object($oEligibilityInfo->identite)) {
            $oCompany->name          = $oEligibilityInfo->identite->raisonSociale;
            $oCompany->forme         = $oEligibilityInfo->identite->formeJuridique;
            $oCompany->capital       = $oEligibilityInfo->identite->capital;
            $oCompany->code_naf      = $oEligibilityInfo->identite->naf5EntreCode;
            $oCompany->libelle_naf   = $oEligibilityInfo->identite->naf5EntreLibelle;
            $oCompany->adresse1      = $oEligibilityInfo->identite->rue;
            $oCompany->city          = $oEligibilityInfo->identite->ville;
            $oCompany->zip           = $oEligibilityInfo->identite->codePostal;
            $oCompany->siret         = $oEligibilityInfo->identite->siret;
            $oCompany->date_creation = substr($oEligibilityInfo->identite->dateCreation, 0, 10);
        }

        $oCompany->update();
    }

    /**
     * Set Altares notation of project
     * @param \projects $oProject
     * @param \StdClass|null $oEligibilityInfo
     */
    public function setProjectData(\projects &$oProject, $oEligibilityInfo = null)
    {
        if (is_null($oEligibilityInfo)) {
            /** @var \companies $oCompany */
            $oCompany = Loader::loadData('companies');
            $oCompany->get($oProject->id_company);

            $oEligibilityInfo = $this->getEligibility($oCompany->siren)->myInfo;
        }

        /** @var \company_rating_history $oCompanyRatingHistory */
        $oCompanyRatingHistory = Loader::loadData('company_rating_history');
        $oCompanyRatingHistory->id_company = $oProject->id_company;
        $oCompanyRatingHistory->id_user    = isset($_SESSION['user']['id_user']) ? $_SESSION['user']['id_user'] : 0;
        $oCompanyRatingHistory->action     = \company_rating_history::ACTION_WS;
        $oCompanyRatingHistory->create();

        /** @var \company_rating $oCompanyRating */
        $oCompanyRating = Loader::loadData('company_rating');

        if (false === empty($oProject->id_company_rating_history)) {
            foreach ($oCompanyRating->getHistoryRatingsByType($oProject->id_company_rating_history) as $sRating => $mValue) {
                if (false === in_array($sRating, array('eligibilite_altares', 'code_retour_altares', 'motif_altares', 'date_valeur_altares', 'score_altares', 'score_sectoriel_altares'))) {
                    $oCompanyRating->id_company_rating_history = $oCompanyRatingHistory->id_company_rating_history;
                    $oCompanyRating->type                      = $sRating;
                    $oCompanyRating->value                     = $mValue;
                    $oCompanyRating->create();
                }
            }
        }

        if (isset($oEligibilityInfo->eligibility)) {
            $oCompanyRating->id_company_rating_history = $oCompanyRatingHistory->id_company_rating_history;
            $oCompanyRating->type                      = 'eligibilite_altares';
            $oCompanyRating->value                     = $oEligibilityInfo->eligibility;
            $oCompanyRating->create();
        }

        if (isset($oEligibilityInfo->codeRetour)) {
            $oCompanyRating->id_company_rating_history = $oCompanyRatingHistory->id_company_rating_history;
            $oCompanyRating->type                      = 'code_retour_altares';
            $oCompanyRating->value                     = $oEligibilityInfo->codeRetour;
            $oCompanyRating->create();
        }

        if (isset($oEligibilityInfo->motif)) {
            $oCompanyRating->id_company_rating_history = $oCompanyRatingHistory->id_company_rating_history;
            $oCompanyRating->type                      = 'motif_altares';
            $oCompanyRating->value                     = $oEligibilityInfo->motif;
            $oCompanyRating->create();
        }

        if (isset($oEligibilityInfo->score) && is_object($oEligibilityInfo->score)) {
            $oCompanyRating->id_company_rating_history = $oCompanyRatingHistory->id_company_rating_history;
            $oCompanyRating->type                      = 'date_valeur_altares';
            $oCompanyRating->value                     = substr($oEligibilityInfo->score->dateValeur, 0, 10);
            $oCompanyRating->create();

            $oCompanyRating->id_company_rating_history = $oCompanyRatingHistory->id_company_rating_history;
            $oCompanyRating->type                      = 'score_altares';
            $oCompanyRating->value                     = $oEligibilityInfo->score->scoreVingt;
            $oCompanyRating->create();

            $oCompanyRating->id_company_rating_history = $oCompanyRatingHistory->id_company_rating_history;
            $oCompanyRating->type                      = 'score_sectoriel_altares';
            $oCompanyRating->value                     = $oEligibilityInfo->score->scoreSectorielCent;
            $oCompanyRating->create();
        }

        $oProject->id_company_rating_history = $oCompanyRatingHistory->id_company_rating_history;
        $oProject->update();
    }

    /**
     * Set company balance sheets
     * @param \companies $oCompany
     */
    public function setCompanyBalance(\companies &$oCompany)
    {
        $oBalanceSheets = $this->getBalanceSheets($oCompany->siren);

        if (isset($oBalanceSheets->myInfo->bilans) && is_array($oBalanceSheets->myInfo->bilans)) {
            /** @var \companies_actif_passif $oCompanyAssetsDebts */
            $oCompanyAssetsDebts = Loader::loadData('companies_actif_passif');
            /** @var \companies_bilans $oCompanyAnnualAccounts */
            $oCompanyAnnualAccounts = Loader::loadData('companies_bilans');
            /** @var \company_balance $oCompanyBalance */
            $oCompanyBalance = Loader::loadData('company_balance');
            /** @var \company_balance_type $oCompaniesBalanceTypes */
            $oCompaniesBalanceTypes = Loader::loadData('company_balance_type');

            $aCodes = $oCompaniesBalanceTypes->getAllByCode();

            foreach ($oBalanceSheets->myInfo->bilans as $oBalanceSheet) {
                $aCompanyBalances = array();
                $aAnnualAccounts  = $oCompanyAnnualAccounts->select('id_company = ' . $oCompany->id_company . ' AND cloture_exercice_fiscal = "' . $oBalanceSheet->dateClotureN . '"');

                if (empty($aAnnualAccounts)) {
                    $oCompanyAnnualAccounts->id_company              = $oCompany->id_company;
                    $oCompanyAnnualAccounts->cloture_exercice_fiscal = $oBalanceSheet->dateClotureN;
                    $oCompanyAnnualAccounts->duree_exercice_fiscal   = $oBalanceSheet->dureeN;
                    $oCompanyAnnualAccounts->create();

                    $oCompanyAssetsDebts->id_bilan = $oCompanyAnnualAccounts->id_bilan;
                    $oCompanyAssetsDebts->create();
                } else {
                    $oCompanyAnnualAccounts->get($aAnnualAccounts[0]['id_bilan'], 'id_bilan');
                    foreach ($oCompanyBalance->select('id_bilan = ' . $oCompanyAnnualAccounts->id_bilan) as $aBalance) {
                        $aCompanyBalances[$aBalance['id_balance_type']] = $aBalance;
                    }
                }

                foreach ($oBalanceSheet->posteList as $oBalance) {
                    if (isset($aCodes[$oBalance->poste])) {
                        if (false === isset($aCompanyBalances[$aCodes[$oBalance->poste]['id_balance_type']])) {
                            $oCompanyBalance->id_bilan        = $oCompanyAnnualAccounts->id_bilan;
                            $oCompanyBalance->id_balance_type = $aCodes[$oBalance->poste]['id_balance_type'];
                            $oCompanyBalance->value           = $oBalance->valeur;
                            $oCompanyBalance->create();
                        } elseif ($aCompanyBalances[$aCodes[$oBalance->poste]['id_balance_type']]['value'] != $oBalance->valeur) {
                            $oCompanyBalance->get($aCompanyBalances[$aCodes[$oBalance->poste]['id_balance_type']]['id_balance'], 'id_balance');
                            $oCompanyBalance->value = $oBalance->valeur;
                            $oCompanyBalance->update();
                        }
                    }
                }

                $oCompanyAnnualAccounts->calcultateFromBalance();

                $oCompanyAssetsDebts->get($oCompanyAnnualAccounts->id_bilan, 'id_bilan');
                $oCompanyAssetsDebts->calcultateFromBalance();
            }
        }
    }

    /**
     * Make SOAP call to Altares WS
     * @param string $sWSDLUrl
     * @param string $sWSName
     * @param array $aParameters
     * @return mixed
     */
    private function soapCall($sWSDLUrl, $sWSName, array $aParameters = array())
    {
        $oClient = new \SoapClient($sWSDLUrl, array('trace' => 1, 'exception' => true));
        $oResult = $oClient->__soapCall(
            $sWSName,
            array(array('identification' => $this->sIdentification, 'refClient' => 'sffpme') + $aParameters)
        );
        return isset($oResult->return) ? $oResult->return : null;
    }
}
