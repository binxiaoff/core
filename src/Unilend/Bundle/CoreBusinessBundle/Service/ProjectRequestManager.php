<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Service\SourceManager;
use Unilend\Bundle\WSClientBundle\Entity\Altares\BalanceSheetList;

class ProjectRequestManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var ProjectManager */
    private $projectManager;
    /** @var SourceManager */
    private $sourceManager;
    /** @var PartnerManager */
    private $partnerManager;
    /** @var CompanyFinanceCheck */
    private $companyFinanceCheck;
    /** @var CompanyScoringCheck */
    private $companyScoringCheck;
    /** @var CompanyBalanceSheetManager */
    private $companyBalanceSheetManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * ProjectRequestManager constructor.
     * @param EntityManager              $entityManager
     * @param ProjectManager             $projectManager
     * @param SourceManager              $sourceManager
     * @param PartnerManager             $partnerManager
     * @param CompanyFinanceCheck        $companyFinanceCheck
     * @param CompanyScoringCheck        $companyScoringCheck
     * @param CompanyBalanceSheetManager $companyBalanceSheetManager
     * @param LoggerInterface            $logger
     */
    public function __construct(
        EntityManager $entityManager,
        ProjectManager $projectManager,
        SourceManager $sourceManager,
        PartnerManager $partnerManager,
        CompanyFinanceCheck $companyFinanceCheck,
        CompanyScoringCheck $companyScoringCheck,
        CompanyBalanceSheetManager $companyBalanceSheetManager,
        LoggerInterface $logger
    )
    {
        $this->entityManager              = $entityManager;
        $this->projectManager             = $projectManager;
        $this->sourceManager              = $sourceManager;
        $this->partnerManager             = $partnerManager;
        $this->companyFinanceCheck        = $companyFinanceCheck;
        $this->companyScoringCheck        = $companyScoringCheck;
        $this->companyBalanceSheetManager = $companyBalanceSheetManager;
        $this->logger                     = $logger;
    }

    public function getMonthlyRateEstimate()
    {
        /** @var \projects $projects */
        $projects = $this->entityManager->getRepository('projects');

        return round($projects->getGlobalAverageRateOfFundedProjects(50), 1);
    }

    public function getMonthlyPaymentEstimate($amount, $period, $estimatedRate)
    {
        /** @var \PHPExcel_Calculation_Financial $oFinancial */
        $oFinancial = new \PHPExcel_Calculation_Financial();

        /** @var \tax_type $taxType */
        $taxType = $this->entityManager->getRepository('tax_type');
        $taxType->get(\tax_type::TYPE_VAT);
        $fVATRate = $taxType->rate / 100;

        $fCommission    = ($oFinancial->PMT(round(bcdiv(\projects::DEFAULT_COMMISSION_RATE_REPAYMENT, 100, 4), 2) / 12, $period, - $amount) - $oFinancial->PMT(0, $period, - $amount)) * (1 + $fVATRate);
        $monthlyPayment = round($oFinancial->PMT($estimatedRate / 100 / 12, $period, - $amount) + $fCommission);

        return $monthlyPayment;
    }

    public function saveSimulatorRequest($aFormData)
    {
        /** @var \projects $project */
        $project = $this->entityManager->getRepository('projects');
        /** @var \clients $client */
        $client = $this->entityManager->getRepository('clients');
        /** @var \clients_adresses $clientAddress */
        $clientAddress = $this->entityManager->getRepository('clients_adresses');
        /** @var \companies $company */
        $company = $this->entityManager->getRepository('companies');

        if (empty($aFormData['email']) || false === filter_var($aFormData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
        }
        if (empty($aFormData['siren']) || false === preg_match('/^([0-9]{9}|[0-9]{14})$/', $aFormData['siren'])) {
            throw new \InvalidArgumentException('Invalid SIREN');
        }
        if (empty($aFormData['amount']) || false === filter_var($aFormData['amount'], FILTER_VALIDATE_INT)) {
            throw new \InvalidArgumentException('Invalid amount');
        }
        if (empty($aFormData['duration']) || false === filter_var($aFormData['duration'], FILTER_VALIDATE_INT)) {
            throw new \InvalidArgumentException('Invalid duration');
        }
        if (empty($aFormData['reason']) || false === filter_var($aFormData['reason'], FILTER_VALIDATE_INT)) {
            throw new \InvalidArgumentException('Invalid reason');
        }

        $client->id_langue    = 'fr';
        $client->email        = $client->existEmail($aFormData['email']) ? $aFormData['email'] . '-' . time() : $aFormData['email'];
        $client->source       = $this->sourceManager->getSource(SourceManager::SOURCE1);
        $client->source2      = $this->sourceManager->getSource(SourceManager::SOURCE2);
        $client->source3      = $this->sourceManager->getSource(SourceManager::SOURCE3);
        $client->slug_origine = $this->sourceManager->getSource(SourceManager::ENTRY_SLUG);
        $client->status       = \clients::STATUS_ONLINE;
        $client->create();

        $clientAddress->id_client = $client->id_client;
        $clientAddress->create();

        $aFormData['siren'] = str_replace(' ', '', $aFormData['siren']);

        $company->id_client_owner               = $client->id_client;
        $company->siren                         = substr($aFormData['siren'], 0, 9);
        $company->siret                         = strlen($aFormData['siren']) === 14 ? $aFormData['siren'] : '';
        $company->status_adresse_correspondance = 1;
        $company->email_dirigeant               = $aFormData['email'];
        $company->create();

        $project->id_company                           = $company->id_company;
        $project->amount                               = $aFormData['amount'];
        $project->period                               = $aFormData['duration'];
        $project->id_borrowing_motive                  = $aFormData['reason'];
        $project->ca_declara_client                    = 0;
        $project->resultat_exploitation_declara_client = 0;
        $project->fonds_propres_declara_client         = 0;
        $project->status                               = \projects_status::INCOMPLETE_REQUEST;
        $project->id_partner                           = $this->partnerManager->getDefaultPartner()->id;
        $project->commission_rate_funds                = \projects::DEFAULT_COMMISSION_RATE_FUNDS;
        $project->commission_rate_repayment            = \projects::DEFAULT_COMMISSION_RATE_REPAYMENT;
        $project->create();

        $this->projectManager->addProjectStatus(\users::USER_ID_FRONT, \projects_status::INCOMPLETE_REQUEST, $project);

        return $project;
    }

    /**
     * @param \projects $project
     * @param int       $userId
     * @return null|array
     */
    public function checkProjectRisk(\projects &$project, $userId)
    {
        /** @var \company_rating_history $companyRatingHistory */
        $companyRatingHistory             = $this->entityManager->getRepository('company_rating_history');
        $companyRatingHistory->id_company = $project->id_company;
        $companyRatingHistory->id_user    = $userId;
        $companyRatingHistory->action     = \company_rating_history::ACTION_WS;
        $companyRatingHistory->create();

        /** @var \company_rating $companyRating */
        $companyRating = $this->entityManager->getRepository('company_rating');

        if (false === empty($project->id_company_rating_history)) {
            foreach ($companyRating->getHistoryRatingsByType($project->id_company_rating_history) as $rating => $value) {
                if (false === in_array($rating, \company_rating::$ratingTypes)) {
                    $companyRating->id_company_rating_history = $companyRatingHistory->id_company_rating_history;
                    $companyRating->type                      = $rating;
                    $companyRating->value                     = $value['value'];
                    $companyRating->create();
                }
            }
        }

        /** @var \companies $company */
        $company = $this->entityManager->getRepository('companies');
        $company->get($project->id_company);

        $project->balance_count             = '0000-00-00' === $company->date_creation ? 0 : \DateTime::createFromFormat('Y-m-d', $company->date_creation)->diff(new \DateTime())->y;
        $project->id_company_rating_history = $companyRatingHistory->id_company_rating_history;
        $project->update();

        if (
            false === $this->companyFinanceCheck->isCompanySafe($company, $rejectionReason)
            || true === $this->companyFinanceCheck->hasCodinfPaymentIncident($company->siren, $rejectionReason)
        ) {
            return $this->addRejectionProjectStatus($rejectionReason, $project, $userId);
        }

        $altaresScore = $this->companyScoringCheck->getAltaresScore($company->siren);

        if (true === $this->companyScoringCheck->isAltaresScoreLow($altaresScore, $companyRatingHistory, $companyRating, $rejectionReason)) {
            return $this->addRejectionProjectStatus($rejectionReason, $project, $userId);
        }

        /** @var BalanceSheetList $balanceSheetList */
        $balanceSheetList = $this->companyFinanceCheck->getBalanceSheets($company->siren);

        if (null !== $balanceSheetList) {
            $this->companyBalanceSheetManager->setCompanyBalance($company, $project, $balanceSheetList);
        }

        if (null !== $balanceSheetList && (new \DateTime())->diff($balanceSheetList->getLastBalanceSheet()->getCloseDate())->days <= \company_balance::MAX_COMPANY_BALANCE_DATE) {
            if (
                true === $this->companyFinanceCheck->hasNegativeCapitalStock($balanceSheetList, $company->siren, $rejectionReason)
                || true === $this->companyFinanceCheck->hasNegativeRawOperatingIncomes($balanceSheetList, $company->siren, $rejectionReason)
            ) {
                return $this->addRejectionProjectStatus($rejectionReason, $project, $userId);
            }
        }

        if (
            false === $this->companyScoringCheck->isXerfiUnilendOk($company->code_naf, $companyRatingHistory, $companyRating, $rejectionReason)
            || false === $this->companyScoringCheck->combineAltaresScoreAndUnilendXerfi($altaresScore, $company->code_naf, $rejectionReason)
            || false === $this->companyScoringCheck->combineEulerTrafficLightXerfiAltaresScore($altaresScore, $company, $companyRatingHistory, $companyRating, $rejectionReason)
            || true === $this->companyScoringCheck->isInfolegaleScoreLow($company->siren, $companyRatingHistory, $companyRating, $rejectionReason)
            || false === $this->companyScoringCheck->combineEulerGradeUnilendXerfiAltaresScore($altaresScore, $company, $companyRatingHistory, $companyRating, $rejectionReason)
            || true === $this->companyFinanceCheck->hasInfogreffePrivileges($company->siren, $rejectionReason)
        ) {
            return $this->addRejectionProjectStatus($rejectionReason, $project, $userId);
        }

        return null;
    }

    /**
     * @param string    $motive
     * @param \projects $project
     * @param int       $userId
     * @return array
     */
    private function addRejectionProjectStatus($motive, &$project, $userId)
    {
        $status = substr($motive, 0, strlen(\projects_status::UNEXPECTED_RESPONSE)) === \projects_status::UNEXPECTED_RESPONSE
            ? \projects_status::IMPOSSIBLE_AUTO_EVALUATION
            : \projects_status::NOT_ELIGIBLE;

        $this->projectManager->addProjectStatus($userId, $status, $project, 0, $motive);
        $this->logger->info('Project rejection reason: ' . $motive . ' - Project status: ' . $status . ' - Added by: ' . $userId, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]);

        return ['motive' => $motive, 'status' => $status];
    }
}
