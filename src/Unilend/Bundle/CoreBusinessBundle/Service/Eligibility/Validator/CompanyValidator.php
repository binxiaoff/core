<?php

/**
 * @todo
 * - WS managers may throw a specific exception when response is unexpected that may be catched in the validate method and return a "ProjectsStatus::UNEXPECTED_RESPONSE . 'WS_NAME'" error
 */

namespace Unilend\Bundle\CoreBusinessBundle\Service\Eligibility\Validator;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityAssessment;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRule;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsNotes;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Xerfi;
use Unilend\Bundle\CoreBusinessBundle\Service\ExternalDataManager;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyBalanceSheet;
use Unilend\Bundle\WSClientBundle\Entity\Euler\CompanyRating as EulerHermesCompanyRating;
use Unilend\Bundle\WSClientBundle\Entity\Infolegale\AnnouncementDetails;
use Unilend\Bundle\WSClientBundle\Entity\Infolegale\AnnouncementEvent;
use Unilend\Bundle\WSClientBundle\Entity\Infolegale\ContentiousParticipant;

class CompanyValidator
{
    /** @var EntityManager */
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
        'TC-RISK-014' => 'checkCurrentExecutivesHistory',
        'TC-RISK-015' => 'checkEulerHermesGrade',
        'TC-RISK-018' => 'checkPreviousExecutivesHistory',
        'TC-RISK-019' => 'checkCompanyPejorativeEvents',
    ];

    /**
     * @param EntityManager       $entityManager
     * @param ExternalDataManager $externalDataManager
     * @param LoggerInterface     $logger
     */
    public function __construct(EntityManager $entityManager, ExternalDataManager $externalDataManager, LoggerInterface $logger)
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
                return [ProjectsStatus::NON_ELIGIBLE_REASON_UNKNOWN_SIREN];
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
                return [ProjectsStatus::NON_ELIGIBLE_REASON_INACTIVE];
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
                return [ProjectsStatus::NON_ELIGIBLE_REASON_PROCEEDING];
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
                return [ProjectsStatus::NON_ELIGIBLE_REASON_COMPANY_LOCATION];
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
            return [ProjectsStatus::NON_ELIGIBLE_REASON_TOO_MUCH_PAYMENT_INCIDENT];
        }

        foreach ($incidents as $incident) {
            $diff   = $currentDate->diff($incident->getDate());
            $period = (int) $diff->format('%y') * 12 + (int) $diff->format('%m');

            if (true === in_array($incident->getType(), $nonAllowedIncident) && 12 >= $period) {
                return [ProjectsStatus::NON_ELIGIBLE_REASON_NON_ALLOWED_PAYMENT_INCIDENT];
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
            return [ProjectsStatus::NON_ELIGIBLE_REASON_LOW_ALTARES_SCORE];
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
            return [ProjectsStatus::NON_ELIGIBLE_REASON_NEGATIVE_CAPITAL_STOCK];
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
            return [ProjectsStatus::NON_ELIGIBLE_REASON_NEGATIVE_RAW_OPERATING_INCOMES];
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
                return [ProjectsStatus::NON_ELIGIBLE_REASON_UNILEND_XERFI_ELIMINATION_SCORE];
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
            return [ProjectsStatus::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT];
        }

        $altaresScore = $this->externalDataManager->getAltaresScore($siren);
        if (null === $altaresScore) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_score'];
        }

        if (
            EulerHermesCompanyRating::COLOR_RED === $trafficLight->getColor()
            && $altaresScore->getScore20() < 12
        ) {
            return [ProjectsStatus::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT_VS_ALTARES_SCORE];
        }

        try {
            $nafCode = $this->getNAFCode($siren);
            $xerfi   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Xerfi')->find($nafCode);

            if (
                EulerHermesCompanyRating::COLOR_RED === $trafficLight->getColor()
                && $xerfi->getScore() > 75
            ) {
                return [ProjectsStatus::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT_VS_UNILEND_XERFI];
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
            $eligibility[] = ProjectsStatus::NON_ELIGIBLE_REASON_ELLISPHERE_DEFAULTS;
        }

        if ($ellisphereReport->getDefaults()->getSocialSecurityPrivilegesCount()->getCount()) {
            $eligibility[] = ProjectsStatus::NON_ELIGIBLE_REASON_ELLISPHERE_SOCIAL_SECURITY_PRIVILEGES;
        }

        if ($ellisphereReport->getDefaults()->getTreasuryTaxPrivilegesCount()->getCount()) {
            $eligibility[] = ProjectsStatus::NON_ELIGIBLE_REASON_ELLISPHERE_TREASURY_TAX_PRIVILEGES;
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
            return [ProjectsStatus::NON_ELIGIBLE_REASON_LOW_INFOLEGALE_SCORE];
        }

        return [];
    }

    /**
     * For all CURRENT executives, get all mandates (without end date "this includes the depositor company" or having
     * end date in the 5 last years). For each mandate get all events happened in the 3 last years, And then check if there are pejorative ones
     *
     * @param string $siren
     *
     * @throws \Exception
     *
     * @return array
     */
    private function checkCurrentExecutivesHistory($siren)
    {
        $this->externalDataManager->refreshExecutiveChanges($siren);
        $activeExecutives = $this->entityManager->getRepository('UnilendCoreBusinessBundle:InfolegaleExecutivePersonalChange')->getActiveExecutives($siren);

        if ($this->hasCurrentExecutiveWithPejorativeAnnouncements($siren, $activeExecutives, 5, 3)) {
            return [ProjectsStatus::NON_ELIGIBLE_REASON_INFOLEGALE_CURRENT_MANAGER_INCIDENT];
        }

        return [];
    }

    /**
     * @param string $depositorSiren
     * @param array  $executiveIds
     * @param int    $yearsSinceMandateEnd
     * @param int    $eventYearsOld
     *
     * @return bool
     */
    private function hasCurrentExecutiveWithPejorativeAnnouncements($depositorSiren, array $executiveIds, $yearsSinceMandateEnd, $eventYearsOld)
    {
        $requestedEvents = 0;
        $checkedSirens   = [];
        $now             = new \DateTime();

        $allMandates = $this->entityManager->getRepository('UnilendCoreBusinessBundle:InfolegaleExecutivePersonalChange')
            ->findMandatesByExecutivesSince($executiveIds, new \DateTime($yearsSinceMandateEnd . ' years ago'));
        foreach ($allMandates as $mandate) {
            if (isset($checkedSirens[$mandate->getSiren()])) {
                continue;
            }
            $checkedSirens[$mandate->getSiren()] = $mandate->getSiren();

            $incidentAnnouncements = $this->externalDataManager->getAnnouncements($mandate->getSiren());
            if (empty($incidentAnnouncements)) {
                return false;
            }

            foreach ($incidentAnnouncements as $announcement) {
                foreach ($announcement->getAnnouncementEvents() as $event) {
                    $eventDate = $this->getEventDateToUse($event, $announcement);

                    if (false === $eventDate) {
                        $this->logger->warning('Escaping check TCK-RISK-14 on announcement ID: ' . $announcement->getId() .
                            '. All dates from event effective date, event resolution date and announcement publish date are null',
                            ['siren' => $depositorSiren, 'method' => __METHOD__, 'announcementID' => $announcement->getId()]
                        );
                        continue;
                    }
                    if ($this->isOlderThan($eventDate->diff($now), $eventYearsOld)) {
                        continue;
                    }
                    if ($mandate->getSiren() === $depositorSiren) {
                        if (
                            in_array($event->getCode(), AnnouncementEvent::PEJORATIVE_EVENT_CODE_DEPOSITOR_LIMITED_DATE)
                            && false === $this->isOlderThan($eventDate->diff($now), 1)
                        ) {
                            $this->logger->info(
                                'Event code : ' . $event->getCode() . ' of announcement details ID : ' . $announcement->getId() . ' matched TCK-RISK-14',
                                ['siren' => $depositorSiren, 'method' => __METHOD__, 'line' => __LINE__]
                            );
                            return true;
                        }
                        if (in_array($event->getCode(), AnnouncementEvent::PEJORATIVE_EVENT_CODE_DEPOSITOR_UNLIMITED_DATE)) {
                            $this->logger->info(
                                'Event code : ' . $event->getCode() . ' of announcement details ID : ' . $announcement->getId() . ' matched TCK-RISK-14',
                                ['siren' => $depositorSiren, 'method' => __METHOD__, 'line' => __LINE__]
                            );
                            return true;
                        }

                        if (in_array($event->getCode(), AnnouncementEvent::PEJORATIVE_EVENT_CODE_DEPOSITOR_WITH_ROLE)) {

                            if ($announcement->getContentiousParticipants()) {
                                foreach ($announcement->getContentiousParticipants() as $participant) {
                                    if ($depositorSiren === $participant->getSiren()) {
                                        switch ($participant->getType()) {
                                            case ContentiousParticipant::TYPE_TARGET:
                                                $this->logger->info(
                                                    'Event code : ' . $event->getCode() . ' of announcement details ID : ' . $announcement->getId() . ' matched TCK-RISK-14 (DEPOSITOR_WITH_ROLE)',
                                                    ['siren' => $depositorSiren, 'method' => __METHOD__, 'line' => __LINE__]
                                                );
                                                return true;
                                            case ContentiousParticipant::TYPE_COMPLAINANT:
                                                if (false === $this->isOlderThan($eventDate->diff($now), 1)) {
                                                    $requestedEvents++;
                                                }
                                                if ($requestedEvents >= 3) {
                                                    $this->logger->info(
                                                        'Event code : ' . $event->getCode() . ' of announcement details ID : ' . $announcement->getId() . ' matched TCK-RISK-14 (DEPOSITOR_WITH_ROLE)',
                                                        ['siren' => $depositorSiren, 'method' => __METHOD__, 'line' => __LINE__]
                                                    );
                                                    return true;
                                                }
                                                break;
                                            default:
                                                break;
                                        }
                                    }
                                }
                            } else {
                                $this->logger->info(
                                    'Event code : ' . $event->getCode() . ' of announcement details ID : ' . $announcement->getId() . ' matched TCK-RISK-14 (DEPOSITOR_WITH_ROLE) Because no "contentious participants" found',
                                    ['siren' => $depositorSiren, 'method' => __METHOD__, 'line' => __LINE__]
                                );
                                return true;
                            }
                        }
                    } elseif (in_array($event->getCode(), AnnouncementEvent::PEJORATIVE_EVENT_CODE_OTHER_DIRECTOR_COMPANIES)) {
                        $this->logger->info(
                            'Event code : ' . $event->getCode() . ' of announcement details ID : ' . $announcement->getId() . ' matched TCK-RISK-14 (OTHER_DIRECTOR_COMPANIES)',
                            ['siren' => $depositorSiren, 'method' => __METHOD__, 'line' => __LINE__]
                        );
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * For all previous executives (last 3 years), get all mandates. For each mandate get all events happened between the
     * mandate start date and 1 year after the mandate end date. And then check if there are pejorative ones
     *
     * @param string $siren
     *
     * @throws \Exception
     *
     * @return array
     */
    private function checkPreviousExecutivesHistory($siren)
    {
        $this->externalDataManager->refreshExecutiveChanges($siren);
        $previousExecutives = $this->entityManager->getRepository('UnilendCoreBusinessBundle:InfolegaleExecutivePersonalChange')->getPreviousExecutivesLeftAfter($siren, new \DateTime('3 years ago'));

        if ($this->hasPreviousExecutiveWithPejorativeAnnouncements($previousExecutives, 3, 1)) {
            return [ProjectsStatus::NON_ELIGIBLE_REASON_INFOLEGALE_PREVIOUS_MANAGER_INCIDENT];
        }

        return [];
    }

    /**
     * @param array $executiveIds
     * @param int   $yearsSince
     * @param int   $extendedYears
     *
     * @return bool
     */
    private function hasPreviousExecutiveWithPejorativeAnnouncements(array $executiveIds, $yearsSince, $extendedYears)
    {
        $checkedSirens = [];
        $allMandates   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:InfolegaleExecutivePersonalChange')
            ->findMandatesByExecutivesSince($executiveIds, new \DateTime($yearsSince . ' years ago'));
        foreach ($allMandates as $mandate) {
            if (isset($checkedSirens[$mandate->getSiren()])) {
                continue;
            }
            $checkedSirens[$mandate->getSiren()] = $this->getPeriodForExecutiveInACompany($mandate->getIdExecutive(), $mandate->getSiren(), $extendedYears);

            $incidentAnnouncements = $this->externalDataManager->getAnnouncements($mandate->getSiren());
            if (empty($incidentAnnouncements)) {
                return false;
            }

            foreach ($incidentAnnouncements as $announcement) {
                foreach ($announcement->getAnnouncementEvents() as $event) {
                    $eventDate = $this->getEventDateToUse($event, $announcement);

                    if (false === $eventDate) {
                        $this->logger->warning('Escaping check TCK-RISK-18 on announcement ID: ' . $announcement->getId() .
                            'All dates from event effective date, event resolution date and announcement publish date are null',
                            ['method' => __METHOD__, 'Executive ID ' => $mandate->getIdExecutive(), 'announcementID' => $announcement->getId()]
                        );
                        continue;
                    }
                    if (
                        in_array($event->getCode(), AnnouncementEvent::PEJORATIVE_EVENT_CODE_OTHER_DIRECTOR_COMPANIES)
                        && $checkedSirens[$mandate->getSiren()]['started'] <= $eventDate
                        && $checkedSirens[$mandate->getSiren()]['ended'] >= $eventDate
                    ) {
                        $this->logger->info(
                            'Event code : ' . $event->getCode() . ' of announcement details ID : ' . $announcement->getId() . ' matched TCK-RISK-18',
                            ['mandate_siren' => $mandate->getSiren(), 'method' => __METHOD__, 'line' => __LINE__]
                        );
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
     * @param int    $executiveId
     * @param string $siren
     * @param int    $extended
     *
     * @return array
     */
    private function getPeriodForExecutiveInACompany($executiveId, $siren, $extended)
    {
        $now     = new \DateTime();
        $started = $now;
        $ended   = $now;
        $changes = $this->entityManager->getRepository('UnilendCoreBusinessBundle:InfolegaleExecutivePersonalChange')->findBy([
            'idExecutive' => $executiveId,
            'siren'       => $siren
        ]);
        foreach ($changes as $change) {
            if (null !== $change->getEnded()) {
                $ended = $change->getEnded();
            }

            if (null === $change->getNominated()) {
                $mockedNominatedDate = new \DateTime('5 years ago');
                if ($change->getEnded() > $mockedNominatedDate) {
                    $started = $mockedNominatedDate;
                } else {
                    $started = $change->getEnded();
                }
            } else {
                $started = $change->getNominated();
            }
        }

        return ['started' => $started, 'ended' => $ended->modify('+' . $extended . ' year')];
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
            return [ProjectsStatus::NON_ELIGIBLE_REASON_EULER_GRADE_VS_ALTARES_SCORE];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    private function checkCompanyPejorativeEvents($siren)
    {
        $requestedEvents = 0;
        $announcements   = $this->externalDataManager->getAnnouncements($siren, 1);

        foreach ($announcements as $announcement) {
            foreach ($announcement->getAnnouncementEvents() as $event) {
                if (in_array($event->getCode(), AnnouncementDetails::PEJORATIVE_EVENT_CODE)) {
                    if ($announcement->getContentiousParticipants()) {
                        foreach ($announcement->getContentiousParticipants() as $participant) {
                            if ($siren === $participant->getSiren()) {
                                if (ContentiousParticipant::TYPE_TARGET === $participant->getType()) {
                                    return [ProjectsStatus::NON_ELIGIBLE_REASON_INFOLEGALE_COMPANY_INCIDENT];
                                } elseif (ContentiousParticipant::TYPE_COMPLAINANT === $participant->getType()) {
                                    ++$requestedEvents;

                                    if (3 === $requestedEvents) {
                                        return [ProjectsStatus::NON_ELIGIBLE_REASON_INFOLEGALE_COMPANY_INCIDENT];
                                    }
                                }
                            }
                        }
                    } else {
                        return [ProjectsStatus::NON_ELIGIBLE_REASON_INFOLEGALE_COMPANY_INCIDENT];
                    }
                }
            }
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

        $infolegaleScoreCheck = $this->checkRule('TC-RISK-014', $siren, $project);
        if (false === empty($infolegaleScoreCheck)) {
            return $infolegaleScoreCheck;
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
    private function checkRule($ruleName, $siren, Projects $project = null)
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
