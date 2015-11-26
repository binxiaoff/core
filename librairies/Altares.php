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
     * @param integer $iCompanyId
     * @param \stdClass|null $oEligibilityInfo
     */
    public function setCompanyData($iCompanyId, $oEligibilityInfo = null)
    {
        $oCompany = new \companies($this->oDatabase);
        $oCompany->get($iCompanyId);

        $oCompanyDetails = new \companies_details($this->oDatabase);
        $oCompanyDetails->get($iCompanyId);

        if (is_null($oEligibilityInfo)) {
            $oEligibilityInfo = $this->getEligibility($oCompany->siren)->myInfo;
        }

        $oCompany->altares_eligibility = isset($oEligibilityInfo->eligibility) ? $oEligibilityInfo->eligibility : '';
        $oCompany->altares_codeRetour  = isset($oEligibilityInfo->codeRetour) ? $oEligibilityInfo->codeRetour : '';
        $oCompany->altares_motif       = isset($oEligibilityInfo->motif) ? $oEligibilityInfo->motif : '';
        $oCompany->phone               = isset($oEligibilityInfo->siege->telephone) ? str_replace(' ', '', $oEligibilityInfo->siege->telephone) : '';

        if (isset($oEligibilityInfo->score) && is_object($oEligibilityInfo->score)) {
            $oCompany->altares_niveauRisque       = $oEligibilityInfo->score->niveauRisque;
            $oCompany->altares_scoreVingt         = $oEligibilityInfo->score->scoreVingt;
            $oCompany->altares_scoreSectorielCent = $oEligibilityInfo->score->scoreSectorielCent;
            $oCompany->altares_dateValeur         = substr($oEligibilityInfo->score->dateValeur, 0, 10);
        }

        if (isset($oEligibilityInfo->identite) && is_object($oEligibilityInfo->identite)) {
            $oCompany->name          = $oEligibilityInfo->identite->raisonSociale;
            $oCompany->forme         = $oEligibilityInfo->identite->formeJuridique;
            $oCompany->capital       = $oEligibilityInfo->identite->capital;
            $oCompany->code_naf      = $oEligibilityInfo->identite->naf5EntreCode;
            $oCompany->libelle_naf   = $oEligibilityInfo->identite->naf5EntreLibelle;
            $oCompany->adresse1      = $oEligibilityInfo->identite->rue;
            $oCompany->city          = $oEligibilityInfo->identite->ville;
            $oCompany->zip           = $oEligibilityInfo->identite->codePostal;
            $oCompany->rcs           = $oEligibilityInfo->identite->rcs;
            $oCompany->siret         = $oEligibilityInfo->identite->siret;
            $oCompany->date_creation = substr($oEligibilityInfo->identite->dateCreation, 0, 10);

            // @todo
            $sLastAccountStatementDate                  = isset($oEligibilityInfo->identite->dateDernierBilan) && strlen($oEligibilityInfo->identite->dateDernierBilan) > 0 ? substr($oEligibilityInfo->identite->dateDernierBilan, 0, 10) : (date('Y') - 1) . '-12-31';
            $aLastAccountStatementDate                  = explode('-', $sLastAccountStatementDate);
            $oCompanyDetails->date_dernier_bilan        = $sLastAccountStatementDate;
            $oCompanyDetails->date_dernier_bilan_mois   = $aLastAccountStatementDate[1];
            $oCompanyDetails->date_dernier_bilan_annee  = $aLastAccountStatementDate[0];
            $oCompanyDetails->date_dernier_bilan_publie = $sLastAccountStatementDate;
            $oCompanyDetails->update();
        }

        $oCompany->update();

        $this->setCompanyFinancial($iCompanyId, $oEligibilityInfo);
    }

    /**
     * Set financial data of the given company according to Altares response
     * @param integer $iCompanyId
     * @param \stdClass|null $oEligibilityInfo
     */
    public function setCompanyFinancial($iCompanyId, $oEligibilityInfo = null)
    {
        $oCompanyAnnualAccounts = new \companies_bilans($this->oDatabase);
        $oCompanyAssetsDebts    = new \companies_actif_passif($this->oDatabase);

        if (is_null($oEligibilityInfo)) {
            $oCompany = new \companies($this->oDatabase);
            $oCompany->get($iCompanyId);

            $oEligibilityInfo = $this->getEligibility($oCompany->siren)->myInfo;
        }

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
