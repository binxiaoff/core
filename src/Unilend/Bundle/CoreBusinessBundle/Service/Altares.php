<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Psr\Cache\CacheItemPoolInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\librairies\CacheKeys;

class Altares
{
    const RESPONSE_CODE_WS_ERROR                       = -1;
    const RESPONSE_CODE_INACTIVE                       = 1;
    const RESPONSE_CODE_NOT_REGISTERED                 = 2;
    const RESPONSE_CODE_PROCEDURE                      = 3;
    const RESPONSE_CODE_OLD_ANNUAL_ACCOUNTS            = 4;
    const RESPONSE_CODE_NEGATIVE_CAPITAL_STOCK         = 5;
    const RESPONSE_CODE_NEGATIVE_RAW_OPERATING_INCOMES = 6;
    const RESPONSE_CODE_UNKNOWN_SIREN                  = 7;
    const RESPONSE_CODE_ELIGIBLE                       = 8;
    const RESPONSE_CODE_NO_ANNUAL_ACCOUNTS             = 9;

    const THRESHOLD_SCORE = '3';

    /**
     * @var EntityManager
     */
    private $entityManager;
    /** @var CompanyBalanceSheetManager */
    private $companyBalanceSheetManager;
    /** @var ProjectManager */
    private $projectManager;
    /** @var CacheItemPoolInterface */
    private $cacheItemPool;

    public function __construct(EntityManager $entityManager, CompanyBalanceSheetManager $companyBalanceSheetManager, ProjectManager $projectManager, CacheItemPoolInterface $cacheItemPool)
    {
        ini_set('default_socket_timeout', 60);
        $this->entityManager              = $entityManager;
        $this->companyBalanceSheetManager = $companyBalanceSheetManager;
        $this->projectManager             = $projectManager;
        $this->cacheItemPool             = $cacheItemPool;
    }

    /**
     * Retrieve getEligibility WS data
     * @param int $siren
     * @return mixed
     * @throws \Exception
     */
    public function getEligibility($siren)
    {
        $cachedItem = $this->cacheItemPool->getItem('Altares_getEligibility' . '_' . $siren);

        if (false === $cachedItem->isHit()) {
            $settings = $this->entityManager->getRepository('settings');
            $settings->get('Altares WSDL Eligibility', 'type');

            try {
                $response = $this->soapCall($settings->value, 'getEligibility', array('siren' => $siren));

                if (false === empty($response->exception)) {
                    throw new \Exception(
                        'Altares return an error - code: ' . $response->exception->code . ' - description: ' . $response->exception->description . ' - error: ' . $response->exception->erreur
                    );
                } else {
                    $cachedItem->set($response)->expiresAfter(CacheKeys::SHORT_TIME);
                    $this->cacheItemPool->save($cachedItem);
                }

            } catch (\Exception $exception) {
                throw new \Exception('Altares API error When calling Altares::getEligibility() using SIREN ' . $siren . ' - Exception message: ' . $exception->getMessage());
            }
        } else {
            $response = $cachedItem->get();
        }

        return $response;
    }

    /**
     * Retrieve getDerniersBilans WS data
     * @param int $iSIREN
     * @param int $iSheetsCount
     * @return mixed
     */
    public function getBalanceSheets($iSIREN, $iSheetsCount = 3)
    {
        $settings = $this->entityManager->getRepository('settings');
        $settings->get('Altares WSDL CallistoIdentite', 'type');

        return $this->soapCall($settings->value, 'getDerniersBilans', array('siren' => $iSIREN, 'nbBilans' => $iSheetsCount));
    }

    /**
     * Set main data of the given company according to Altares response
     * @param \companies $oCompany
     */
    public function setCompanyData(\companies &$oCompany)
    {
        $oEligibilityInfo = $this->getEligibility($oCompany->siren)->myInfo;

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
            $oCompany->rcs           = $oEligibilityInfo->identite->rcs;
        }

        $oCompany->update();
    }

    /**
     * Set Altares notation of project
     * @param \projects $oProject
     */
    public function setProjectData(\projects &$oProject)
    {
        /** @var \companies $oCompany */
        $oCompany = $this->entityManager->getRepository('companies');
        $oCompany->get($oProject->id_company);

        $oEligibilityInfo = $this->getEligibility($oCompany->siren)->myInfo;

        /** @var \company_rating_history $oCompanyRatingHistory */
        $oCompanyRatingHistory = $this->entityManager->getRepository('company_rating_history');
        $oCompanyRatingHistory->id_company = $oProject->id_company;
        $oCompanyRatingHistory->id_user    = isset($_SESSION['user']['id_user']) ? $_SESSION['user']['id_user'] : 0;
        $oCompanyRatingHistory->action     = \company_rating_history::ACTION_WS;
        $oCompanyRatingHistory->create();

        /** @var \company_rating $oCompanyRating */
        $oCompanyRating = $this->entityManager->getRepository('company_rating');

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
     * @param bool $bRecalculate
     */
    public function setCompanyBalance(\companies &$oCompany, $bRecalculate = true)
    {
        $taxFormType = $this->companyBalanceSheetManager->detectTaxFormType($oCompany);
        if ($taxFormType) {
            $oBalanceSheets = $this->getBalanceSheets($oCompany->siren);
            if (isset($oBalanceSheets->myInfo->bilans) && (is_array($oBalanceSheets->myInfo->bilans) || is_object($oBalanceSheets->myInfo->bilans)) ) {
                /** @var \companies_actif_passif $oCompanyAssetsDebts */
                $oCompanyAssetsDebts = $this->entityManager->getRepository('companies_actif_passif');
                /** @var \companies_bilans $oCompanyAnnualAccounts */
                $oCompanyAnnualAccounts = $this->entityManager->getRepository('companies_bilans');
                /** @var \company_balance $oCompanyBalance */
                $oCompanyBalance = $this->entityManager->getRepository('company_balance');
                /** @var \company_balance_type $oCompaniesBalanceTypes */
                $oCompaniesBalanceTypes = $this->entityManager->getRepository('company_balance_type');

                $aCodes = $oCompaniesBalanceTypes->getAllByCode($taxFormType->id_type);

                if (is_array($oBalanceSheets->myInfo->bilans)) {
                    $balances = $oBalanceSheets->myInfo->bilans;
                } else {
                    $balances = array($oBalanceSheets->myInfo->bilans);
                }

                foreach ($balances as $oBalanceSheet) {
                    $aCompanyBalances = array();
                    $aAnnualAccounts  = $oCompanyAnnualAccounts->select('id_company = ' . $oCompany->id_company . ' AND cloture_exercice_fiscal = "' . $oBalanceSheet->dateClotureN . '"');

                    if (empty($aAnnualAccounts)) {
                        $oCompanyAnnualAccounts->id_company               = $oCompany->id_company;
                        $oCompanyAnnualAccounts->id_company_tax_form_type = $taxFormType->id_type;
                        $oCompanyAnnualAccounts->cloture_exercice_fiscal  = $oBalanceSheet->dateClotureN;
                        $oCompanyAnnualAccounts->duree_exercice_fiscal    = $oBalanceSheet->dureeN;
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

                    if ($bRecalculate) {
                        $this->companyBalanceSheetManager->getIncomeStatement($oCompanyAnnualAccounts);
                        $this->companyBalanceSheetManager->calculateDebtsAssetsFromBalance($oCompanyAnnualAccounts->id_bilan);
                    }
                }
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
        $settings = $this->entityManager->getRepository('settings');
        $settings->get('Altares login', 'type');

        $identification = $settings->value;

        $settings->get('Altares mot de passe', 'type');
        $identification .= '|' . $settings->value;

        $oClient = new \SoapClient($sWSDLUrl, array('trace' => 1, 'exception' => true));
        $oResult = $oClient->__soapCall(
            $sWSName,
            array(array('identification' => $identification, 'refClient' => 'sffpme') + $aParameters)
        );
        return isset($oResult->return) ? $oResult->return : null;
    }

    public function isEligible(\projects $project)
    {
        /** @var \companies $company */
        $company = $this->entityManager->getRepository('companies');

        $company->get($project->id_company);

        $result = $this->getEligibility($company->siren);

        $eligible = true;
        $reason   = [];

        if ($result->myInfo->codeRetour == self::RESPONSE_CODE_INACTIVE) {
            $eligible = false;
            $reason[] = \projects_status::NON_ELIGIBLE_REASON_INACTIVE;
        }

        if ($result->myInfo->codeRetour == self::RESPONSE_CODE_UNKNOWN_SIREN) {
            $eligible = false;
            $reason[] = \projects_status::NON_ELIGIBLE_REASON_UNKNOWN_SIREN;
        }

        if ($result->myInfo->codeRetour == self::RESPONSE_CODE_NEGATIVE_CAPITAL_STOCK) {
            $eligible = false;
            $reason[] = \projects_status::NON_ELIGIBLE_REASON_NEGATIVE_CAPITAL_STOCK;
        }

        if ($result->myInfo->codeRetour == self::RESPONSE_CODE_NEGATIVE_RAW_OPERATING_INCOMES) {
            $eligible = false;
            $reason[] = \projects_status::NON_ELIGIBLE_REASON_NEGATIVE_RAW_OPERATING_INCOMES;
        }

        if ($result->myInfo->codeRetour == self::RESPONSE_CODE_PROCEDURE || 'OUI' === $result->myInfo->identite->procedureCollective) {
            $eligible = false;
            $reason[] = \projects_status::NON_ELIGIBLE_REASON_PROCEEDING;
        }

        if (self::THRESHOLD_SCORE >= $result->myInfo->score->scoreVingt) {
            $eligible = false;
            $reason[] = \projects_status::NON_ELIGIBLE_REASON_LOW_SCORE;
        }

        return ['eligible' => $eligible, 'reason' => $reason];
    }
}
