<?php

namespace Unilend\librairies;

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
     * @var \bdd
     */
    private $oDatabase;

    /**
     * @var \settings
     */
    private $oSettings;

    /**
     * @param \bdd $oDatabase
     */
    public function __construct(\bdd $oDatabase)
    {
        ini_set('default_socket_timeout', 60);

        require_once __DIR__ . '/../data/settings.data.php';

        $this->oDatabase = $oDatabase;
        $this->oSettings = new \settings($oDatabase);

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

        $this->setCompanyFinancial($oCompany->id_company, $oEligibilityInfo);
    }

    /**
     * Set Altares notation of project
     * @param \projects $oProject
     * @param \StdClass|null $oEligibilityInfo
     */
    public function setProjectData(\projects &$oProject, $oEligibilityInfo = null)
    {
        if (is_null($oEligibilityInfo)) {
            $oCompany = new \companies($this->oDatabase);
            $oCompany->get($oProject->id_company);

            $oEligibilityInfo = $this->getEligibility($oCompany->siren)->myInfo;
        }

        /** @var \company_rating_history $oCompanyRatingHistory */
        $oCompanyRatingHistory = new \company_rating_history($this->oDatabase);
        $oCompanyRatingHistory->id_company = $oProject->id_company;
        $oCompanyRatingHistory->id_user    = isset($_SESSION['user']['id_user']) ? $_SESSION['user']['id_user'] : 0;
        $oCompanyRatingHistory->action     = \company_rating_history::ACTION_WS;
        $oCompanyRatingHistory->create();

        /** @var \company_rating $oCompanyRating */
        $oCompanyRating = new \company_rating($this->oDatabase);

        if (false === empty($oProject->id_company_rating_history)) {
            foreach ($oCompanyRating->getHistoryRatingsByType($oProject->id_company_rating_history) as $sRating => $mValue) {
                if (false === in_array($sRating, array('eligibilite_altares', 'code_retour_altares', 'motif_altares', 'date_valeur_altares', 'score_altares', 'score_sectorial_altares'))) {
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
            $oCompanyRating->type                      = 'score_sectorial_altares';
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
            $oCompanyAssetsDebts    = new \companies_actif_passif($this->oDatabase);
            $oCompanyAnnualAccounts = new \companies_bilans($this->oDatabase);
            $oCompanyBalance        = new \company_balance($this->oDatabase);
            $oCompaniesBalanceTypes = new \company_balance_type($this->oDatabase);

            $aCodes = $oCompaniesBalanceTypes->getAllByCode();

            foreach ($oBalanceSheets->myInfo->bilans as $oBalanceSheet) {
                $aCompanyBalances = array();
                $aAnnualAccounts  = $oCompanyAnnualAccounts->select('id_company = ' . $oCompany->id_company . ' AND cloture_exercice_fiscal = "' . $oBalanceSheet->dateClotureN . '"');

                if (empty($aAnnualAccounts)) {
                    $oCompanyAnnualAccounts->id_company              = $oCompany->id_company;
                    $oCompanyAnnualAccounts->cloture_exercice_fiscal = $oBalanceSheet->dateClotureN;
                    $oCompanyAnnualAccounts->duree_exercice_fiscal   = $oBalanceSheet->dureeN;
                    $oCompanyAnnualAccounts->create();
                    $oCompanyAnnualAccounts->calcultateFromBalance();

                    $oCompanyAssetsDebts->id_bilan = $oCompanyAnnualAccounts->id_bilan;
                    $oCompanyAssetsDebts->create();
                    $oCompanyAssetsDebts->calcultateFromBalance();
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
            }
        }
    }

    /**
     * Set financial data of the given company according to Altares response
     * @param integer $iCompanyId
     * @param \stdClass $oEligibilityInfo
     */
    private function setCompanyFinancial($iCompanyId, $oEligibilityInfo)
    {
        $oCompanyAnnualAccounts = new \companies_bilans($this->oDatabase);
        $oCompanyAssetsDebts    = new \companies_actif_passif($this->oDatabase);

        if (isset($oEligibilityInfo->bilans) && is_array($oEligibilityInfo->bilans)) {
            $aAnnualAccounts = array();
            foreach ($oEligibilityInfo->bilans as $oAnnualAccounts) {
                $aAnnualAccounts[substr($oAnnualAccounts->bilan->dateClotureN, 0, 10)] = $oAnnualAccounts;
            }

            ksort($aAnnualAccounts);

            foreach ($aAnnualAccounts as $sClosingDate => $oAnnualAccounts) {
                $aFormattedAssetsDebt = array();
                $aAssetsDebt          = array_merge($oAnnualAccounts->bilanRetraiteInfo->posteActifList, $oAnnualAccounts->bilanRetraiteInfo->postePassifList);

                foreach ($aAssetsDebt as $oAssetsDebtLine) {
                    $aFormattedAssetsDebt[$oAssetsDebtLine->posteCle] = $oAssetsDebtLine->montant;
                }

                $oCompanyAnnualAccounts->get($iCompanyId . '" AND cloture_exercice_fiscal = "' . $sClosingDate, 'id_company');
                $oCompanyAnnualAccounts->id_company                  = $iCompanyId;
                $oCompanyAnnualAccounts->cloture_exercice_fiscal     = $sClosingDate;
                $oCompanyAnnualAccounts->duree_exercice_fiscal       = $oAnnualAccounts->bilan->dureeN;
                $oCompanyAnnualAccounts->ca                          = $oAnnualAccounts->syntheseFinanciereInfo->syntheseFinanciereList[0]->montantN;
                $oCompanyAnnualAccounts->resultat_brute_exploitation = $oAnnualAccounts->soldeIntermediaireGestionInfo->SIGList[9]->montantN;
                $oCompanyAnnualAccounts->resultat_exploitation       = $oAnnualAccounts->syntheseFinanciereInfo->syntheseFinanciereList[1]->montantN;
                $oCompanyAnnualAccounts->investissements             = $oAnnualAccounts->bilan->posteList[0]->valeur;
                empty($oCompanyAnnualAccounts->id_bilan) ? $oCompanyAnnualAccounts->create() : $oCompanyAnnualAccounts->update();

                $oCompanyAssetsDebts->get($oCompanyAnnualAccounts->id_bilan, 'id_bilan');
                $oCompanyAssetsDebts->id_bilan                           = $oCompanyAnnualAccounts->id_bilan;
                $oCompanyAssetsDebts->immobilisations_corporelles        = $aFormattedAssetsDebt['posteBR_IMCOR'];
                $oCompanyAssetsDebts->immobilisations_incorporelles      = $aFormattedAssetsDebt['posteBR_IMMINC'];
                $oCompanyAssetsDebts->immobilisations_financieres        = $aFormattedAssetsDebt['posteBR_IMFI'];
                $oCompanyAssetsDebts->stocks                             = $aFormattedAssetsDebt['posteBR_STO'];
                $oCompanyAssetsDebts->creances_clients                   = $aFormattedAssetsDebt['posteBR_BV'] + $aFormattedAssetsDebt['posteBR_BX'] + $aFormattedAssetsDebt['posteBR_ACCCA'] + $aFormattedAssetsDebt['posteBR_ACHE_']; // Créances_clients = avances et acomptes + créances clients + autres créances et cca + autres créances hors exploitation
                $oCompanyAssetsDebts->disponibilites                     = $aFormattedAssetsDebt['posteBR_CF'];
                $oCompanyAssetsDebts->valeurs_mobilieres_de_placement    = $aFormattedAssetsDebt['posteBR_CD'];
                $oCompanyAssetsDebts->capitaux_propres                   = $aFormattedAssetsDebt['posteBR_CPRO'] + $aFormattedAssetsDebt['posteBR_NONVAL']; // capitaux propres = capitaux propres + non valeurs
                $oCompanyAssetsDebts->provisions_pour_risques_et_charges = $aFormattedAssetsDebt['posteBR_PROVRC'] + $aFormattedAssetsDebt['posteBR_PROAC']; // provisions pour risques et charges = provisions pour risques et charges + provisions actif circulant
                $oCompanyAssetsDebts->amortissement_sur_immo             = $aFormattedAssetsDebt['posteBR_AMPROVIMMO'];
                $oCompanyAssetsDebts->dettes_financieres                 = $aFormattedAssetsDebt['posteBR_EMP'] + $aFormattedAssetsDebt['posteBR_VI'] + $aFormattedAssetsDebt['posteBR_EH']; // dettes financières = emprunts + dettes groupe et associés + concours bancaires courants
                $oCompanyAssetsDebts->dettes_fournisseurs                = $aFormattedAssetsDebt['posteBR_DW'] + $aFormattedAssetsDebt['posteBR_DX']; // dettes fournisseurs = avances et acomptes clients + dettes fournisseurs
                $oCompanyAssetsDebts->autres_dettes                      = $aFormattedAssetsDebt['posteBR_AUTDETTEXPL'] + $aFormattedAssetsDebt['posteBR_DZ'] + $aFormattedAssetsDebt['posteBR_AUTDETTHEXPL']; // autres dettes = autres dettes exploitation + dettes sur immos et comptes rattachés + autres dettes hors exploitation
                empty($oCompanyAssetsDebts->id_actif_passif) ? $oCompanyAssetsDebts->create() : $oCompanyAssetsDebts->update();
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
