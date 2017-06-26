<?php

/**
 * @todo
 * - WS managers may throw a specific exception when response is unexpected that may be catched in the validate method and return a "ProjectsStatus::UNEXPECTED_RESPONSE . 'WS_NAME'" error
 */

namespace Unilend\Bundle\CoreBusinessBundle\Service\Eligibility\Validator;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityAssessment;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsNotes;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Xerfi;
use Unilend\Bundle\CoreBusinessBundle\Service\ExternalDataManager;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyBalanceSheet;
use Unilend\Bundle\WSClientBundle\Entity\Euler\CompanyRating as EulerHermesCompanyRating;
use Unilend\Bundle\WSClientBundle\Entity\Infolegale\Executive;
use Unilend\Bundle\WSClientBundle\Entity\Infolegale\Mandate;

class CompanyValidator
{
    /** @var EntityManager */
    private $entityManager;
    /** @var ExternalDataManager */
    private $externalDataManager;

    const CHECK_RULE_METHODS = [
        'TC-RISK-001' => 'checkSiren',
        'TC-RISK-002' => 'checkActiveCompany',
        'TC-RISK-003' => 'checkCollectiveProceeding',
        'TC-RISK-005' => 'checkPaymentIncidents',
        'TC-RISK-006' => 'checkAltaresScore',
        'TC-RISK-007' => 'checkCapitalStock',
        'TC-RISK-008' => 'checkGrossOperatingSurplus',
        'TC-RISK-009' => 'checkEliminationXerfiScore',
        'TC-RISK-010' => 'checkAltaresScoreVsXerfiScore',
        'TC-RISK-011' => 'checkEulerHermesTrafficLight',
        'TC-RISK-012' => 'checkEllispehereReport',
        'TC-RISK-013' => 'checkInfolegaleScore',
        'TC-RISK-014' => 'checkCurrentExecutivesHistory',
        'TC-RISK-015' => 'checkEulerHermesGrade',
        'TC-RISK-018' => 'checkPreviousExecutivesHistory',
    ];

    /**
     * @param EntityManager       $entityManager
     * @param ExternalDataManager $externalDataManager
     */
    public function __construct(EntityManager $entityManager, ExternalDataManager $externalDataManager)
    {
        $this->entityManager       = $entityManager;
        $this->externalDataManager = $externalDataManager;
    }

    /**
     * @param string         $siren
     * @param Companies|null $company
     * @param Projects|null  $project
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

        // TC-RISK-004

        if (Companies::NAF_CODE_NO_ACTIVITY === $this->getNAFCode($siren)) {
            return $this->checkNoActivityCompany($siren, $project);
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

        $altaresScoreVsXerfiScoreCheck = $this->checkRule('TC-RISK-010', $siren, $project);
        if (false === empty($altaresScoreVsXerfiScoreCheck)) {
            return $altaresScoreVsXerfiScoreCheck;
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
        if (
            Companies::INVALID_SIREN_EMPTY === $siren
            || null === $this->externalDataManager->getCompanyIdentity($siren)
        ) {
            return [ProjectsStatus::NON_ELIGIBLE_REASON_UNKNOWN_SIREN];
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
        $companyData = $this->externalDataManager->getCompanyIdentity($siren);
        if (in_array($companyData->getCompanyStatus(), [7, 9])) {
            return [ProjectsStatus::NON_ELIGIBLE_REASON_INACTIVE];
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
        $companyData = $this->externalDataManager->getCompanyIdentity($siren);
        if ($companyData->getCollectiveProcedure()) {
            return [ProjectsStatus::NON_ELIGIBLE_REASON_PROCEEDING];
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
        $nafCode = $this->getNAFCode($siren);
        $xerfi   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Xerfi')->find($nafCode);

        if (Xerfi::UNILEND_ELIMINATION_SCORE === $xerfi->getUnilendRating()) {
            return [ProjectsStatus::NON_ELIGIBLE_REASON_UNILEND_XERFI_ELIMINATION_SCORE];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    private function checkAltaresScoreVsXerfiScore($siren)
    {
        $altaresScore = $this->externalDataManager->getAltaresScore($siren);
        if (null === $altaresScore) {
            return [ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_score'];
        }

        $nafCode = $this->getNAFCode($siren);
        $xerfi   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Xerfi')->find($nafCode);

        if (
            in_array($altaresScore->getScore20(), [4, 5])
            && $xerfi->getScore() <= 75
        ) {
            return [ProjectsStatus::NON_ELIGIBLE_REASON_UNILEND_XERFI_VS_ALTARES_SCORE];
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

        $nafCode = $this->getNAFCode($siren);
        $xerfi   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Xerfi')->find($nafCode);

        if (
            EulerHermesCompanyRating::COLOR_RED === $trafficLight->getColor()
            && $xerfi->getScore() > 75
        ) {
            return [ProjectsStatus::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT_VS_UNILEND_XERFI];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    private function checkEllispehereReport($siren)
    {
        $eligibility      = [];
        $ellisphereReport = $this->externalDataManager->getEllispehereReport($siren);

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
     * @param $siren
     *
     * @return array
     */
    private function checkCurrentExecutivesHistory($siren)
    {
        $executives = $this->externalDataManager->getExecutives($siren);
        foreach ($executives as $executive) {
            if (false === in_array($executive->getChange(), [Mandate::CHANGE_NOMINATION, Mandate::CHANGE_MODIFICATION, Mandate::CHANGE_CONFIRMATION])) {
                continue;
            }
            $this->externalDataManager->refreshExecutiveChanges($executive);

            if ($this->hasIncidentAnnouncements($executive, 5, 1)) {
                return [ProjectsStatus::NON_ELIGIBLE_REASON_INFOLEGALE_CURRENT_MANAGER_INCIDENT];
            }
        }

        return [];
    }

    /**
     * @param $siren
     *
     * @return array
     */
    private function checkPreviousExecutivesHistory($siren)
    {
        $executives = $this->externalDataManager->getExecutives($siren);
        $now        = new \DateTime();
        foreach ($executives as $executive) {
            if ($executive->getChangeDate()->diff($now)->y > 4 || in_array($executive->getChange(), [Mandate::CHANGE_NOMINATION, Mandate::CHANGE_MODIFICATION, Mandate::CHANGE_CONFIRMATION])) {
                continue;
            }
            $this->externalDataManager->refreshExecutiveChanges($executive);

            if ($this->hasIncidentAnnouncements($executive, 1, 0)) {
                return [ProjectsStatus::NON_ELIGIBLE_REASON_INFOLEGALE_PREVIOUS_MANAGER_INCIDENT];
            }
        }

        return [];
    }

    /**
     * @param Executive $executive
     * @param int       $since
     * @param int       $extended
     *
     * @return bool
     */
    private function hasIncidentAnnouncements(Executive $executive, $since, $extended)
    {
        $now                   = new \DateTime();
        $incidentAnnouncements = $this->externalDataManager->getExecutiveAnnouncements($executive->getExecutiveId());
        foreach ($incidentAnnouncements as $announcement) {
            $changes = $this->entityManager->getRepository('UnilendCoreBusinessBundle:InfolegaleExecutivePersonalChange')->findBy([
                'idExecutive' => $executive->getExecutiveId(),
                'siren'       => $announcement->getSiren()
            ]);
            foreach ($changes as $change) {

                $ended = null === $change->getEnded() ? $now : $change->getEnded();

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

                if ($ended->diff($now)->y <= $since) {
                    $ended->modify('+' . $extended . ' year');
                    if ($started <= $announcement->getPublishedDate() && $ended >= $announcement->getPublishedDate()) {
                        return true;
                    }
                }
            }
        }

        return false;
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

        $nafCode = $this->getNAFCode($siren);
        $xerfi   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Xerfi')->find($nafCode);

        if (
            $eulerHermesGrade->getGrade() >= 9
            || $eulerHermesGrade->getGrade() == 8 && $xerfi->getScore() > 75
        ) {
            return [ProjectsStatus::NON_ELIGIBLE_REASON_EULER_GRADE_VS_UNILEND_XERFI];
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
     * @param string        $siren
     * @param Projects|null $project
     *
     * @return array
     */
    private function checkNoActivityCompany($siren, Projects $project = null)
    {
        $altaresScoreCheck = $this->checkRule('TC-RISK-006', $siren, $project);
        if (false === empty($altaresScoreCheck)) {
            return $altaresScoreCheck;
        }

        $infolegaleScoreCheck = $this->checkRule('TC-RISK-013', $siren, $project);
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
     */
    private function getNAFCode($siren)
    {
        $companyData = $this->externalDataManager->getCompanyIdentity($siren);
        return $companyData->getNAFCode();
    }

    /**
     * @param Projects $project
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
                $eulerHermesGrade = EulerHermesCompanyRating::GRADE_UNKNOWN;
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
     * @return array
     */
    private function checkRule($ruleName, $siren, Projects $project = null)
    {
        $method = new \ReflectionMethod($this, self::CHECK_RULE_METHODS[$ruleName]);
        $method->setAccessible(true);
        $result = $method->invoke($this, $siren);

        if ($project instanceof Projects) {
            $ruleRepository    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityRule');
            $ruleSetRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityRuleSet');
            $rule              = $ruleRepository->findOneBy(['label' => $ruleName]);
            $ruleSet           = $ruleSetRepository->findOneBy(['status' => ProjectEligibilityRuleSet::STATUS_ACTIVE]);

            $assessment = new ProjectEligibilityAssessment();
            $assessment->setIdProject($project);
            $assessment->setIdRule($rule);
            $assessment->setIdRuleSet($ruleSet);
            $assessment->setStatus(empty($result));

            $this->entityManager->persist($assessment);
            $this->entityManager->flush($assessment);
        }

        return $result;
    }
}
