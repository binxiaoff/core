<?php

/**
 * @todo
 * - WS managers may throw a specific exception when response is unexpected that may be catched in the validate method
 * and return a "ProjectsStatus::UNEXPECTED_RESPONSE . 'WS_NAME'" error
 */

namespace Unilend\Bundle\CoreBusinessBundle\Service\Eligibility\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Companies, ProjectEligibilityAssessment, ProjectEligibilityRule, ProjectEligibilityRuleSet, ProjectRejectionReason, Projects, ProjectsNotes,
    ProjectsStatus, Xerfi};
use Unilend\Bundle\CoreBusinessBundle\Service\ExternalDataManager;
use Unilend\Bundle\WSClientBundle\Entity\{Altares\CompanyBalanceSheet, Altares\CompanyIdentityDetail, Euler\CompanyRating as EulerHermesCompanyRating, Infolegale\AnnouncementDetails,
    Infolegale\AnnouncementEvent, Infolegale\ContentiousParticipant};

class CompanyValidator
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var ExternalDataManager */
    private $externalDataManager;
    /** @var LoggerInterface */
    private $logger;

    const CHECK_RULE_METHODS = [
        'TC-RISK-001' => 'checkSiren',
        'TC-RISK-002' => 'checkActiveCompany',
        'TC-RISK-003' => 'checkCollectiveProceeding',
        'TC-RISK-004' => 'checkLocation',
        'TC-RISK-005' => 'checkPaymentIncidents',
        'TC-RISK-006' => 'checkAltaresScore',
        'TC-RISK-007' => 'checkCapitalStock',
        'TC-RISK-008' => 'checkGrossOperatingSurplus',
        'TC-RISK-009' => 'checkEliminationXerfiScore',
        'TC-RISK-011' => 'checkEulerHermesTrafficLight',
        'TC-RISK-012' => 'checkEllisphereReport',
        'TC-RISK-013' => 'checkInfolegaleScore',
        'TC-RISK-014' => 'checkCurrentExecutivesEventsOtherManagerCompanies',
        'TC-RISK-015' => 'checkEulerHermesGrade',
        'TC-RISK-018' => 'checkPreviousExecutivesHistory',
        'TC-RISK-019' => 'checkCurrentExecutivesEventsDepositorCompanyRoleTarget',
        'TC-RISK-020' => 'checkCurrentExecutivesEventsDepositorCompanyRoleComplainant',
        'TC-RISK-021' => 'checkCurrentExecutivesEventsDepositorCompanyNoRole',
        'TC-RISK-022' => 'checkCurrentExecutivesEventsDepositorCompanyCollectiveProceeding',
        'TC-RISK-023' => 'checkNoLegalStatus',
    ];

    /**
     * @param EntityManagerInterface $entityManager
     * @param ExternalDataManager    $externalDataManager
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, ExternalDataManager $externalDataManager, LoggerInterface $logger)
    {
        $this->entityManager       = $entityManager;
        $this->externalDataManager = $externalDataManager;
        $this->logger              = $logger;
    }

    /**
     * @param string         $siren
     * @param Companies|null $company
     * @param Projects|null  $project
     *
     * @throws \Exception
     *
     * @return array
     */
    public function validate($siren, Companies $company = null, Projects $project = null)
    {
        return [];

        if (null !== $company) {
            $lastCompanyRatingHistory = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyRatingHistory')->findOneBy(
                ['idCompany' => $company->getIdCompany()],
                ['added' => 'DESC']
            );
            $this->externalDataManager->setCompanyRatingHistory($lastCompanyRatingHistory);
        }

        $sirenCheck = $this->checkRule('TC-RISK-001', $siren, $project);
        if (false === empty($sirenCheck)) {
            return $sirenCheck;
        }

        $sirenCheck = $this->checkRule('TC-RISK-023', $siren, $project);
        if (false === empty($sirenCheck)) {
            return $sirenCheck;
        }

        $activeCompanyCheck = $this->checkRule('TC-RISK-002', $siren, $project);
        if (false === empty($activeCompanyCheck)) {
            return $activeCompanyCheck;
        }

        $collectiveProceedingCheck = $this->checkRule('TC-RISK-003', $siren, $project);
        if (false === empty($collectiveProceedingCheck)) {
            return $collectiveProceedingCheck;
        }

        $locationCheck = $this->checkRule('TC-RISK-004', $siren, $project);
        if (false === empty($locationCheck)) {
            return $locationCheck;
        }

        try {
            if (Companies::NAF_CODE_NO_ACTIVITY === $this->getNAFCode($siren)) {
                return $this->checkNoActivityCompany($siren, $project);
            }
        } catch (\Exception $exception) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_identity'];
        }

        $paymentIncidentsCheck = $this->checkRule('TC-RISK-005', $siren, $project);
        if (false === empty($paymentIncidentsCheck)) {
            return $paymentIncidentsCheck;
        }

        $altaresScoreCheck = $this->checkRule('TC-RISK-006', $siren, $project);
        if (false === empty($altaresScoreCheck)) {
            return $altaresScoreCheck;
        }

        $capitalStockCheck = $this->checkRule('TC-RISK-007', $siren, $project);
        if (false === empty($capitalStockCheck)) {
            return $capitalStockCheck;
        }

        $grossOperatingSurplusCheck = $this->checkRule('TC-RISK-008', $siren, $project);
        if (false === empty($grossOperatingSurplusCheck)) {
            return $grossOperatingSurplusCheck;
        }

        $eliminationXerfiScoreCheck = $this->checkRule('TC-RISK-009', $siren, $project);
        if (false === empty($eliminationXerfiScoreCheck)) {
            return $eliminationXerfiScoreCheck;
        }

        $eulerHermesTrafficLightCheck = $this->checkRule('TC-RISK-011', $siren, $project);
        if (false === empty($eulerHermesTrafficLightCheck)) {
            return $eulerHermesTrafficLightCheck;
        }

        $ellisphereReportCheck = $this->checkRule('TC-RISK-012', $siren, $project);
        if (false === empty($ellisphereReportCheck)) {
            return $ellisphereReportCheck;
        }

        $infolegaleScoreCheck = $this->checkRule('TC-RISK-013', $siren, $project);
        if (false === empty($infolegaleScoreCheck)) {
            return $infolegaleScoreCheck;
        }

        $currentExecutivesHistory = $this->checkRule('TC-RISK-019', $siren, $project);
        if (false === empty($currentExecutivesHistory)) {
            return $currentExecutivesHistory;
        }
        $currentExecutivesHistory = $this->checkRule('TC-RISK-020', $siren, $project);
        if (false === empty($currentExecutivesHistory)) {
            return $currentExecutivesHistory;
        }
        $currentExecutivesHistory = $this->checkRule('TC-RISK-021', $siren, $project);
        if (false === empty($currentExecutivesHistory)) {
            return $currentExecutivesHistory;
        }
        $currentExecutivesHistory = $this->checkRule('TC-RISK-022', $siren, $project);
        if (false === empty($currentExecutivesHistory)) {
            return $currentExecutivesHistory;
        }
        $currentExecutivesHistory = $this->checkRule('TC-RISK-014', $siren, $project);
        if (false === empty($currentExecutivesHistory)) {
            return $currentExecutivesHistory;
        }

        $eulerHermesGradeCheck = $this->checkRule('TC-RISK-015', $siren, $project);
        if (false === empty($eulerHermesGradeCheck)) {
            return $eulerHermesGradeCheck;
        }

        $previousExecutivesHistory = $this->checkRule('TC-RISK-018', $siren, $project);
        if (false === empty($previousExecutivesHistory)) {
            return $previousExecutivesHistory;
        }

        if (null !== $project) {
            $this->calculatePreScoring($project);
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    private function checkSiren($siren)
    {
        try {
            if (
                Companies::INVALID_SIREN_EMPTY === $siren
                || null === $this->externalDataManager->getCompanyIdentity($siren)
            ) {
                return [ProjectRejectionReason::UNKNOWN_SIREN];
            }
        } catch (\Exception $exception) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_identity'];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    private function checkActiveCompany($siren)
    {
        try {
            $companyData = $this->externalDataManager->getCompanyIdentity($siren);
            if (in_array($companyData->getCompanyStatus(), [7, 9])) {
                return [ProjectRejectionReason::ENTITY_INACTIVE];
            }
        } catch (\Exception $exception) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_identity'];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    private function checkCollectiveProceeding($siren)
    {
        try {
            $companyData = $this->externalDataManager->getCompanyIdentity($siren);
            if ($companyData->getCollectiveProcedure()) {
                return [ProjectRejectionReason::IN_PROCEEDING];
            }
        } catch (\Exception $exception) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_identity'];
        }

        return [];
    }

    private function checkLocation($siren)
    {
        try {
            $companyData = $this->externalDataManager->getCompanyIdentity($siren);
            if (
                substr($companyData->getPostCode(), 0, 2) === '20' // Corse
                || in_array(substr($companyData->getPostCode(), 0, 3), ['973', '976']) // Guyane et Mayotte
            ) {
                return [ProjectRejectionReason::COMPANY_LOCATION];
            }
        } catch (\Exception $exception) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_identity'];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    private function checkPaymentIncidents($siren)
    {
        $nonAllowedIncident = [2, 3, 4, 5, 6];
        $startDate          = (new \DateTime())->sub(new \DateInterval('P1Y'));
        $currentDate        = new \DateTime();
        $incidentList       = $this->externalDataManager->getPaymentIncidents($siren, $startDate, $currentDate);

        if (null === $incidentList) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'codinf_incident'];
        }

        $incidents = $incidentList->getIncidentList();

        if (count($incidents) > 2) {
            return [ProjectRejectionReason::TOO_MUCH_PAYMENT_INCIDENT];
        }

        foreach ($incidents as $incident) {
            $diff   = $currentDate->diff($incident->getDate());
            $period = (int) $diff->format('%y') * 12 + (int) $diff->format('%m');

            if (true === in_array($incident->getType(), $nonAllowedIncident) && 12 >= $period) {
                return [ProjectRejectionReason::NON_ALLOWED_PAYMENT_INCIDENT];
            }
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    private function checkAltaresScore($siren)
    {
        $altaresScore = $this->externalDataManager->getAltaresScore($siren);
        if (null === $altaresScore) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_score'];
        }

        if ($altaresScore->getScore20() < 4) {
            return [ProjectRejectionReason::LOW_ALTARES_SCORE];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    private function checkCapitalStock($siren)
    {
        $lastBalanceSheet = $this->getLastBalanceSheetUpToDate($siren);
        if (null === $lastBalanceSheet) {
            return [];
        }

        $financialSummary = $this->externalDataManager->getFinancialSummary($siren, $lastBalanceSheet);
        if (null === $financialSummary) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_fpro'];
        }

        $capitalStockPost = null;
        foreach ($financialSummary->getFinancialSummaryList() as $post) {
            if ($post->getPost() === 'posteSF_FPRO') {
                $capitalStockPost = $post;
                break;
            }
        }

        if (null === $capitalStockPost) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_fpro'];
        }

        if ($capitalStockPost->getAmountY() < 0) {
            return [ProjectRejectionReason::NEGATIVE_CAPITAL_STOCK];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    private function checkGrossOperatingSurplus($siren)
    {
        $lastBalanceSheet = $this->getLastBalanceSheetUpToDate($siren);
        if (null === $lastBalanceSheet) {
            return [];
        }

        $balanceManagementLine = $this->externalDataManager->getBalanceManagementLine($siren, $lastBalanceSheet);
        if (null === $balanceManagementLine) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_ebe'];
        }

        $grossOperatingSurplus = null;
        foreach ($balanceManagementLine->getBalanceManagementLine() as $post) {
            if ($post->getKeyPost() === 'posteSIG_EBE') {
                $grossOperatingSurplus = $post;
                break;
            }
        }

        if (null === $grossOperatingSurplus) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_ebe'];
        }

        if ($grossOperatingSurplus->getAmountY() < 0) {
            return [ProjectRejectionReason::NEGATIVE_RAW_OPERATING_INCOMES];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    private function checkEliminationXerfiScore($siren)
    {
        try {
            $nafCode = $this->getNAFCode($siren);

            if (empty($nafCode)) {
                return [];
            }

            $xerfi = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Xerfi')->find($nafCode);

            if ($xerfi instanceof Xerfi && Xerfi::UNILEND_ELIMINATION_SCORE === $xerfi->getUnilendRating()) {
                return [ProjectRejectionReason::UNILEND_XERFI_ELIMINATION_SCORE];
            }
        } catch (\Exception $exception) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_identity'];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    private function checkEulerHermesTrafficLight($siren)
    {
        $trafficLight = $this->externalDataManager->getEulerHermesTrafficLight($siren);

        if (null === $trafficLight) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'euler_traffic_light_score'];
        }

        if (in_array($trafficLight->getColor(), [EulerHermesCompanyRating::COLOR_WHITE, EulerHermesCompanyRating::COLOR_GREEN, EulerHermesCompanyRating::COLOR_YELLOW])) {
            return [];
        }

        if (EulerHermesCompanyRating::COLOR_BLACK === $trafficLight->getColor()) {
            return [ProjectRejectionReason::EULER_TRAFFIC_LIGHT];
        }

        $altaresScore = $this->externalDataManager->getAltaresScore($siren);
        if (null === $altaresScore) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_score'];
        }

        if (
            EulerHermesCompanyRating::COLOR_RED === $trafficLight->getColor()
            && $altaresScore->getScore20() < 12
        ) {
            return [ProjectRejectionReason::EULER_TRAFFIC_LIGHT_VS_ALTARES_SCORE];
        }

        try {
            $nafCode = $this->getNAFCode($siren);
            $xerfi   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Xerfi')->find($nafCode);

            if (
                EulerHermesCompanyRating::COLOR_RED === $trafficLight->getColor()
                && $xerfi->getScore() > 75
            ) {
                return [ProjectRejectionReason::EULER_TRAFFIC_LIGHT_VS_UNILEND_XERFI];
            }
        } catch (\Exception $exception) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_identity'];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    private function checkEllisphereReport($siren)
    {
        $eligibility      = [];
        $ellisphereReport = $this->externalDataManager->getEllisphereReport($siren);

        if (null === $ellisphereReport) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'ellisphere_report'];
        }

        if (null !== $ellisphereReport->getDefaults()->getDefaultsNoted()) {
            $eligibility[] = ProjectRejectionReason::ELLISPHERE_DEFAULT;
        }

        if ($ellisphereReport->getDefaults()->getSocialSecurityPrivilegesCount()->getCount()) {
            $eligibility[] = ProjectRejectionReason::ELLISPHERE_SOCIAL_SECURITY_PRIVILEGES;
        }

        if ($ellisphereReport->getDefaults()->getTreasuryTaxPrivilegesCount()->getCount()) {
            $eligibility[] = ProjectRejectionReason::ELLISPHERE_TREASURY_TAX_PRIVILEGES;
        }

        return $eligibility;
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    private function checkInfolegaleScore($siren)
    {
        $infolegaleScore = $this->externalDataManager->getInfolegaleScore($siren);
        if (null === $infolegaleScore) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'infolegale_score'];
        }

        if ($infolegaleScore->getScore() < 5) {
            return [ProjectRejectionReason::LOW_INFOLEGALE_SCORE];
        }

        return [];
    }

    /**
     * TC-RISK-019
     *
     * @param string $siren
     *
     * @return array
     */
    private function checkCurrentExecutivesEventsDepositorCompanyRoleTarget(string $siren) : array
    {
        try {
            $hasPejorativeEvent = $this->hasEventCodeInList($siren, AnnouncementEvent::PEJORATIVE_EVENT_CODE_DEPOSITOR_WITH_ROLE, 1, ContentiousParticipant::TYPE_TARGET);

            if ($hasPejorativeEvent) {
                return [ProjectRejectionReason::INFOLEGALE_CURRENT_MANAGER_DEPOSITOR_ROLE_TARGET_INCIDENT];
            }
        } catch (\UnexpectedValueException $exception) {
            return [$exception->getMessage()];
        } catch (\BadMethodCallException $exception) {
            $this->logger->critical(
                'Could not check infolegale pejorative event rule on SIREN: ' . $siren . ' Error: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );

            return ['runtime_error'];
        }

        return [];
    }

    /**
     * TC-RISK-020
     *
     * @param string $siren
     *
     * @return array
     */
    private function checkCurrentExecutivesEventsDepositorCompanyRoleComplainant(string $siren) : array
    {
        try {
            $hasPejorativeEvent = $this->hasEventCodeInList($siren, AnnouncementEvent::PEJORATIVE_EVENT_CODE_DEPOSITOR_WITH_ROLE, 1, ContentiousParticipant::TYPE_COMPLAINANT);
            if ($hasPejorativeEvent) {
                return [ProjectRejectionReason::INFOLEGALE_CURRENT_MANAGER_DEPOSITOR_ROLE_COMPLAINANT_INCIDENT];
            }
        } catch (\UnexpectedValueException $exception) {
            return [$exception->getMessage()];
        } catch (\BadMethodCallException $exception) {
            $this->logger->critical(
                'Could not check infolegale pejorative event rule on SIREN: ' . $siren . ' Error: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );

            return ['runtime_error'];
        }

        return [];
    }

    /**
     * TC-RISK-021
     *
     * @param string $siren
     *
     * @return array
     */
    private function checkCurrentExecutivesEventsDepositorCompanyNoRole(string $siren) : array
    {
        try {
            if ($this->hasEventCodeInList($siren, AnnouncementEvent::PEJORATIVE_EVENT_CODE_DEPOSITOR_NO_ROLE_12MONTHS, 1)) {
                return [ProjectRejectionReason::INFOLEGALE_CURRENT_MANAGER_DEPOSITOR_NO_ROLE_12_MONTHS_INCIDENT];
            }
        } catch (\BadMethodCallException $exception) {
            $this->logger->critical(
                'Could not check infolegale pejorative event rule on SIREN: ' . $siren . ' Error: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );

            return ['runtime_error'];
        }

        return [];
    }

    /**
     * TC-RISK-022
     *
     * @param string $siren
     *
     * @return array
     */
    private function checkCurrentExecutivesEventsDepositorCompanyCollectiveProceeding(string $siren) : array
    {
        try {
            if ($this->hasEventCodeInList($siren, AnnouncementEvent::PEJORATIVE_EVENT_CODE_DEPOSITOR_COLLECTIVE_PROCEEDING, 3)) {
                return [ProjectRejectionReason::INFOLEGALE_CURRENT_MANAGER_DEPOSITOR_CP_INCIDENT];
            }
        } catch (\BadMethodCallException $exception) {
            $this->logger->critical(
                'Could not check infolegale pejorative event rule on SIREN: ' . $siren . ' Error: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );

            return ['runtime_error'];
        }

        return [];
    }

    /**
     * TC-RISK-014
     *
     * @param $depositorSiren $siren
     *
     * @throws \Exception
     *
     * @return array
     */
    private function checkCurrentExecutivesEventsOtherManagerCompanies($depositorSiren)
    {
        $otherCompaniesOfActiveExecutives = $this->externalDataManager->getAllMandatesExceptGivenSirenOnActiveExecutives($depositorSiren, new \DateTime('5 years ago'));

        try {
            foreach ($otherCompaniesOfActiveExecutives as $siren) {
                if ($this->hasEventCodeInList($siren['siren'], AnnouncementEvent::PEJORATIVE_EVENT_CODE_OTHER_MANAGER_COMPANIES, 3)) {
                    return [ProjectRejectionReason::INFOLEGALE_CURRENT_MANAGER_OTHER_COMPANIES_INCIDENT];
                }
            }
        } catch (\BadMethodCallException $exception) {
            $this->logger->critical(
                'Could not check infolegale pejorative event rule on SIREN: ' . $depositorSiren . ' Error: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );

            return ['runtime_error'];
        }

        return [];
    }

    /**
     * TC-RISK-018
     * For all previous executives (last 3 years), get all mandates. For each mandate get all events happened between
     * the mandate start date and 1 year after the mandate end date. And then check if there are pejorative ones
     *
     * @param string $siren
     *
     * @throws \Exception
     *
     * @return array
     */
    private function checkPreviousExecutivesHistory($siren)
    {
        $allMandates = $this->externalDataManager->getAllPreviousExecutivesMandatesSince($siren, new \DateTime('3 years ago'));

        try {
            foreach ($allMandates as $mandate) {
                $mandatePeriod      = $this->externalDataManager->getPeriodForExecutiveInACompany($mandate->getIdExecutive(), $mandate->getSiren(), 1);
                $hasPejorativeEvent = $this->hasEventCodeInList($mandate->getSiren(), AnnouncementEvent::PEJORATIVE_EVENT_CODE_OTHER_MANAGER_COMPANIES, null, null, $mandatePeriod);

                if ($hasPejorativeEvent) {
                    return [ProjectRejectionReason::INFOLEGALE_PREVIOUS_MANAGER_INCIDENT];
                }
            }
        } catch (\BadMethodCallException $exception) {
            $this->logger->critical(
                'Could not check infolegale pejorative event rule on SIREN: ' . $siren . ' Error: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );

            return ['runtime_error'];
        }

        return [];
    }

    /**
     * TC-RISK-023
     * Exclude SIREN with legal form which does not have a legal personality
     * Il s'agit ici de prévenir un risque de non-conformité légale i.e. les contrats de prêt (IFP), ou les minibons (CIP)
     * ne peuvent pas respectivement être engagés, ou émis, par des entités qui sont dépourvues de la personnalité juridique.
     *
     * @param $siren
     *
     * @return array
     */
    private function checkNoLegalStatus($siren)
    {
        try {
            if (null !== $companyIdentity = $this->externalDataManager->getCompanyIdentity($siren)) {
                if (in_array($companyIdentity->getLegalFormCode(), CompanyIdentityDetail::COMPANIES_WITHOUT_LEGAL_STATUS_CODES)) {
                    return [ProjectRejectionReason::NO_LEGAL_STATUS];
                }
            } else {
                return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_identity'];
            }
        } catch (\Exception $exception) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_identity'];
        }

        return [];
    }

    /**
     * @param string      $siren
     * @param array       $eventCodeList
     * @param int|null    $eventYearsOld
     * @param string|null $participantType
     * @param array|null  $mandatePeriod
     *
     * @throws \UnexpectedValueException
     * @throws \BadMethodCallException
     * @return bool
     */
    private function hasEventCodeInList(string $siren, array $eventCodeList, ?int $eventYearsOld = null, ?string $participantType = null, ?array $mandatePeriod = null) : bool
    {
        if (false === ($eventYearsOld xor $mandatePeriod)) {
            throw new \BadMethodCallException('You must provide either event years old Or mandate period');
        }

        $incidentAnnouncements = $this->externalDataManager->getAnnouncements($siren);
        $now                   = new \DateTime();
        $requestedEvents       = 0;

        if (empty($incidentAnnouncements)) {
            return false;
        }

        foreach ($incidentAnnouncements as $announcement) {
            foreach ($announcement->getAnnouncementEvents() as $event) {
                $eventDate = $this->getEventDateToUse($event, $announcement);

                if (false === $eventDate) {
                    $this->logger->warning('Escaping check on announcement ID: ' . $announcement->getId() .
                        '. All dates from event effective date, event resolution date and announcement publish date are null',
                        ['method' => __METHOD__, 'siren' => $siren, 'announcementID' => $announcement->getId()]
                    );
                    continue;
                }

                if (in_array($event->getCode(), $eventCodeList)) {

                    /** for current executives */
                    if (null !== $eventYearsOld && false === $this->isOlderThan($eventDate->diff($now), $eventYearsOld)) {

                        /** For rules that didn't requires Role check */
                        if (null === $participantType) {
                            return true;
                        } else {
                            /** This part checks the role of the company in this event "Sollicité" / "Demandeur" */
                            if (count($announcement->getContentiousParticipants()) > 0) {
                                foreach ($announcement->getContentiousParticipants() as $participant) {
                                    if ($participant->getType() === $participantType) {
                                        if ($participant->getType() === ContentiousParticipant::TYPE_COMPLAINANT) {
                                            $requestedEvents++;
                                            if ($requestedEvents >= 3) {
                                                return true;
                                            }
                                        }

                                        if ($participant->getType() === ContentiousParticipant::TYPE_TARGET) {
                                            return true;
                                        }
                                    }
                                }
                            } else {
                                $this->logger->warning('The Siren ' . $siren . ' will be rejected: Checking infolegale pejorative events: The event code : ' . $event->getCode() . ' of announcement details ID : ' . $announcement->getId() .
                                    ' matched DEPOSITOR_WITH_ROLE. The event has no role (contentious participants). Events codes in this list should specify whether the company is "complainant" (Demandeur) or solicited (Solicité)', [
                                    'method' => __METHOD__,
                                    'siren'  => $siren,
                                    'line'   => __LINE__
                                ]);
                                throw new \UnexpectedValueException(ProjectRejectionReason::INFOLEGALE_CURRENT_MANAGER_DEPOSITOR_ROLE_MISSING_INCIDENT);
                            }
                        }
                        /** for previous Executives */
                    } elseif ($mandatePeriod['started'] <= $eventDate && $mandatePeriod['ended'] >= $eventDate) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param AnnouncementEvent   $event
     * @param AnnouncementDetails $announcement
     *
     * @return bool|\DateTime
     */
    private function getEventDateToUse(AnnouncementEvent $event, AnnouncementDetails $announcement)
    {
        if ($event->getEffectiveDate() instanceof \DateTime) {
            return $event->getEffectiveDate();
        } elseif ($event->getResolutionDate() instanceof \DateTime) {
            return $event->getResolutionDate();
        } elseif ($announcement->getPublishedDate() instanceof \DateTime) {
            return $announcement->getPublishedDate();
        }

        return false;
    }

    /**
     * Returns true if the elapsed time (Y, m, d) is greater than specified years.
     *
     * @param \DateInterval $interval
     * @param int           $numberOfYears
     *
     * @return bool
     */
    private function isOlderThan(\DateInterval $interval, $numberOfYears)
    {
        if ($numberOfYears === $interval->y) {
            return ($interval->m + $interval->d) > 0;
        }

        return $interval->y > $numberOfYears;
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    private function checkEulerHermesGrade($siren)
    {
        $trafficLight = $this->externalDataManager->getEulerHermesTrafficLight($siren);

        if (null === $trafficLight) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'euler_traffic_light_score'];
        }

        if (EulerHermesCompanyRating::COLOR_WHITE === $trafficLight->getColor()) {
            return [];
        }

        $eulerHermesGrade = $this->externalDataManager->getEulerHermesGrade($siren);
        if (null === $eulerHermesGrade) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'euler_grade'];
        }

        $altaresScore = $this->externalDataManager->getAltaresScore($siren);
        if (null === $altaresScore) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_score'];
        }

        if (
            $eulerHermesGrade->getGrade() >= 5 && $altaresScore->getScore20() == 4
            || $eulerHermesGrade->getGrade() >= 7 && $altaresScore->getScore20() == 5
        ) {
            return [ProjectRejectionReason::EULER_GRADE_VS_ALTARES_SCORE];
        }

        return [];
    }

    /**
     * @param string        $siren
     * @param Projects|null $project
     *
     * @throws \Exception
     *
     * @return array
     */
    private function checkNoActivityCompany($siren, Projects $project = null)
    {
        $altaresScoreCheck = $this->checkRule('TC-RISK-006', $siren, $project);
        if (false === empty($altaresScoreCheck)) {
            return $altaresScoreCheck;
        }

        $infolegalePejorativeEvent = $this->checkRule('TC-RISK-019', $siren, $project);
        if (false === empty($infolegalePejorativeEvent)) {
            return $infolegalePejorativeEvent;
        }

        $infolegalePejorativeEvent = $this->checkRule('TC-RISK-020', $siren, $project);
        if (false === empty($infolegalePejorativeEvent)) {
            return $infolegalePejorativeEvent;
        }

        $infolegalePejorativeEvent = $this->checkRule('TC-RISK-021', $siren, $project);
        if (false === empty($infolegalePejorativeEvent)) {
            return $infolegalePejorativeEvent;
        }

        $infolegalePejorativeEvent = $this->checkRule('TC-RISK-022', $siren, $project);
        if (false === empty($infolegalePejorativeEvent)) {
            return $infolegalePejorativeEvent;
        }

        $infolegalePejorativeEvent = $this->checkRule('TC-RISK-014', $siren, $project);
        if (false === empty($infolegalePejorativeEvent)) {
            return $infolegalePejorativeEvent;
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return CompanyBalanceSheet|null
     */
    private function getLastBalanceSheetUpToDate($siren)
    {
        $balanceSheetList = $this->externalDataManager->getBalanceSheets($siren);
        if (null === $balanceSheetList) {
            return null;
        }

        $lastBalanceSheet = $balanceSheetList->getLastBalanceSheet();
        if (null === $lastBalanceSheet) {
            return null;
        }

        $lastBalanceSheetAge = (new \DateTime())->diff($lastBalanceSheet->getCloseDate())->days;
        if (\company_balance::MAX_COMPANY_BALANCE_DATE < $lastBalanceSheetAge) {
            return null;
        }

        return $lastBalanceSheet;
    }

    /**
     * @param string $siren
     *
     * @return string
     *
     * @throws \Exception
     */
    private function getNAFCode($siren)
    {
        $companyData = $this->externalDataManager->getCompanyIdentity($siren);

        return $companyData->getNAFCode();
    }

    /**
     * @param Projects $project
     *
     * @throws \Exception
     */
    private function calculatePreScoring(Projects $project)
    {
        $projectNotes = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsNotes')->findOneBy(['idProject' => $project]);

        if (null === $projectNotes || null === $projectNotes->getPreScoring()) {
            $siren            = $project->getIdCompany()->getSiren();
            $preScoring       = null;
            $altaresScore     = $this->externalDataManager->getAltaresScore($siren);
            $infolegaleScore  = $this->externalDataManager->getInfolegaleScore($siren);
            $eulerHermesGrade = $this->externalDataManager->getEulerHermesGrade($siren);

            if (null === $eulerHermesGrade && EulerHermesCompanyRating::COLOR_WHITE === $this->externalDataManager->getEulerHermesTrafficLight($siren)) {
                $eulerHermesGrade = new EulerHermesCompanyRating();
                $eulerHermesGrade->setGrade(EulerHermesCompanyRating::GRADE_UNKNOWN);
            }

            if (false === in_array(null, [$altaresScore, $infolegaleScore, $eulerHermesGrade], true)) {
                $preScoringEntity = $this->entityManager->getRepository('UnilendCoreBusinessBundle:PreScoring')->findOneBy([
                    'altares'          => $altaresScore->getScore20(),
                    'infolegale'       => $infolegaleScore->getScore(),
                    'eulerHermesGrade' => $eulerHermesGrade->getGrade()
                ]);

                if (null !== $preScoringEntity) {
                    $preScoring = $preScoringEntity->getNote();
                }
            }

            if (null === $projectNotes) {
                $projectNotes = new ProjectsNotes();
                $projectNotes->setIdProject($project);

                $this->entityManager->persist($projectNotes);
            }

            $projectNotes->setPreScoring($preScoring);

            $this->entityManager->flush($projectNotes);
        }
    }

    /**
     * @param string        $ruleName
     * @param string        $siren
     * @param Projects|null $project
     *
     * @throws \Exception
     *
     * @return array
     */
    public function checkRule($ruleName, $siren, Projects $project = null)
    {
        $method = new \ReflectionMethod($this, self::CHECK_RULE_METHODS[$ruleName]);
        $method->setAccessible(true);
        $result = $method->invoke($this, $siren);

        if ($project instanceof Projects) {
            $rule    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityRule')->findOneBy(['label' => $ruleName]);
            $ruleSet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityRuleSet')->findOneBy(['status' => ProjectEligibilityRuleSet::STATUS_ACTIVE]);
            $this->logCheck($project, $rule, $ruleSet, $result);
        }

        return $result;
    }

    /**
     * @param Projects                  $project
     * @param ProjectEligibilityRule    $rule
     * @param ProjectEligibilityRuleSet $ruleSet
     * @param                           $result
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function logCheck(Projects $project, ProjectEligibilityRule $rule, ProjectEligibilityRuleSet $ruleSet, $result)
    {
        $assessment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityAssessment')->findOneBy([
            'idProject' => $project,
            'idRule'    => $rule,
            'idRuleSet' => $ruleSet
        ], ['added' => 'DESC']);

        $checkStatus = empty($result);
        if (null === $assessment || $checkStatus !== $assessment->getStatus()) {
            $assessment = new ProjectEligibilityAssessment();

            $assessment->setIdProject($project)
                ->setIdRule($rule)
                ->setIdRuleSet($ruleSet)
                ->setStatus($checkStatus);

            $this->entityManager->persist($assessment);
            $this->entityManager->flush($assessment);
        }
    }
}
