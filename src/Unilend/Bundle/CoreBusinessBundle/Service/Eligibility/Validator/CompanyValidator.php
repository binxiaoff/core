<?php

/**
 * @todo
 * - WS managers may throw a specific exception when response is unexpected that may be catched in the validate method and return a "\projects_status::UNEXPECTED_RESPONSE . 'WS_NAME'" error
 * - Add memcache cache on WS calls
 * - Save data when calling WS
 */
namespace Unilend\Bundle\CoreBusinessBundle\Service\Eligibility\Validator;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityAssessment;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Xerfi;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyBalanceSheet;
use Unilend\Bundle\WSClientBundle\Entity\Euler\CompanyRating as EulerHermesCompanyRating;
use Unilend\Bundle\WSClientBundle\Service\AltaresManager;
use Unilend\Bundle\WSClientBundle\Service\CodinfManager;
use Unilend\Bundle\WSClientBundle\Service\EulerHermesManager;
use Unilend\Bundle\WSClientBundle\Service\InfogreffeManager;
use Unilend\Bundle\WSClientBundle\Service\InfolegaleManager;

class CompanyValidator
{
    /** @var EntityManager */
    private $entityManager;
    /** @var AltaresManager */
    private $altaresManager;
    /** @var EulerHermesManager */
    private $eulerHermesManager;
    /** @var CodinfManager */
    private $codinfManager;
    /** @var InfolegaleManager */
    private $infolegaleManager;
    /** @var InfogreffeManager */
    private $infogreffeManager;

    private static $checkMethodReferences = [
        'checkSiren'                    => 'TC-RISK-001',
        'checkActiveCompany'            => 'TC-RISK-002',
        'checkCollectiveProceeding'     => 'TC-RISK-003',
        'checkPaymentIncidents'         => 'TC-RISK-005',
        'checkAltaresScore'             => 'TC-RISK-006',
        'checkCapitalStock'             => 'TC-RISK-007',
        'checkGrossOperatingSurplus'    => 'TC-RISK-008',
        'checkEliminationXerfiScore'    => 'TC-RISK-009',
        'checkAltaresScoreVsXerfiScore' => 'TC-RISK-010',
        'checkEulerHermesTrafficLight'  => 'TC-RISK-011',
        'checkInfolegaleScore'          => 'TC-RISK-013',
        'checkEulerHermesGrade'         => 'TC-RISK-015'
    ];

    /**
     * @param EntityManager      $entityManager
     * @param AltaresManager     $altaresManager
     * @param EulerHermesManager $eulerHermesManager
     * @param CodinfManager      $codinfManager
     * @param InfolegaleManager  $infolegaleManager
     * @param InfogreffeManager  $infogreffeManager
     */
    public function __construct(
        EntityManager $entityManager,
        AltaresManager $altaresManager,
        EulerHermesManager $eulerHermesManager,
        CodinfManager $codinfManager,
        InfolegaleManager $infolegaleManager,
        InfogreffeManager $infogreffeManager
    )
    {
        $this->entityManager      = $entityManager;
        $this->altaresManager     = $altaresManager;
        $this->eulerHermesManager = $eulerHermesManager;
        $this->codinfManager      = $codinfManager;
        $this->infolegaleManager  = $infolegaleManager;
        $this->infogreffeManager  = $infogreffeManager;
    }

    /**
     * @param string        $siren
     * @param Projects|null $project
     *
     * @return array
     */
    public function validate($siren, Projects $project = null)
    {
        $sirenCheck = $this->checkRule('checkSiren', $siren, $project);
        if (false === empty($sirenCheck)) {
            return $sirenCheck;
        }

        $activeCompanyCheck = $this->checkRule('checkActiveCompany', $siren, $project);
        if (false === empty($activeCompanyCheck)) {
            return $activeCompanyCheck;
        }

        $collectiveProceedingCheck = $this->checkRule('checkCollectiveProceeding', $siren, $project);
        if (false === empty($collectiveProceedingCheck)) {
            return $collectiveProceedingCheck;
        }

        // TC-RISK-004

        if (Companies::NAF_CODE_NO_ACTIVITY === $this->getNAFCode($siren)) {
            return $this->checkNoActivityCompany($siren, $project);
        }

        $paymentIncidentsCheck = $this->checkRule('checkPaymentIncidents', $siren, $project);
        if (false === empty($paymentIncidentsCheck)) {
            return $paymentIncidentsCheck;
        }

        $altaresScoreCheck = $this->checkRule('checkAltaresScore', $siren, $project);
        if (false === empty($altaresScoreCheck)) {
            return $altaresScoreCheck;
        }

        $capitalStockCheck = $this->checkRule('checkCapitalStock', $siren, $project);
        if (false === empty($capitalStockCheck)) {
            return $capitalStockCheck;
        }

        $grossOperatingSurplusCheck = $this->checkRule('checkGrossOperatingSurplus', $siren, $project);
        if (false === empty($grossOperatingSurplusCheck)) {
            return $grossOperatingSurplusCheck;
        }

        $eliminationXerfiScoreCheck = $this->checkRule('checkEliminationXerfiScore', $siren, $project);
        if (false === empty($eliminationXerfiScoreCheck)) {
            return $eliminationXerfiScoreCheck;
        }

        $altaresScoreVsXerfiScoreCheck = $this->checkRule('checkAltaresScoreVsXerfiScore', $siren, $project);
        if (false === empty($altaresScoreVsXerfiScoreCheck)) {
            return $altaresScoreVsXerfiScoreCheck;
        }

        $eulerHermesTrafficLightCheck = $this->checkRule('checkEulerHermesTrafficLight', $siren, $project);
        if (false === empty($eulerHermesTrafficLightCheck)) {
            return $eulerHermesTrafficLightCheck;
        }

        // TC-RISK-012

        $infolegaleScoreCheck = $this->checkRule('checkInfolegaleScore', $siren, $project);
        if (false === empty($infolegaleScoreCheck)) {
            return $infolegaleScoreCheck;
        }

        // TC-RISK-014

        $eulerHermesGradeCheck = $this->checkRule('checkEulerHermesGrade', $siren, $project);
        if (false === empty($eulerHermesGradeCheck)) {
            return $eulerHermesGradeCheck;
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    public function checkSiren($siren)
    {
        if (
            Companies::INVALID_SIREN_EMPTY === $siren
            || null === $this->altaresManager->getCompanyIdentity($siren)
        ) {
            return [\projects_status::NON_ELIGIBLE_REASON_UNKNOWN_SIREN];
        }
        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    public function checkActiveCompany($siren)
    {
        $companyData = $this->altaresManager->getCompanyIdentity($siren);
        if (in_array($companyData->getCompanyStatus(), [7, 9])) {
            return [\projects_status::NON_ELIGIBLE_REASON_INACTIVE];
        }
        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    public function checkCollectiveProceeding($siren)
    {
        $companyData = $this->altaresManager->getCompanyIdentity($siren);
        if ($companyData->getCollectiveProcedure()) {
            return [\projects_status::NON_ELIGIBLE_REASON_PROCEEDING];
        }
        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    public function checkPaymentIncidents($siren)
    {
        $nonAllowedIncident = [2, 3, 4, 5, 6];
        $startDate          = (new \DateTime())->sub(new \DateInterval('P1Y'));
        $currentDate        = new \DateTime();
        $incidentList       = $this->codinfManager->getIncidentList($siren, $startDate, $currentDate);

        if (null === $incidentList) {
            return [\projects_status::UNEXPECTED_RESPONSE . 'codinf_incident'];
        }

        $incidents = $incidentList->getIncidentList();

        if (count($incidents) > 2) {
            return [\projects_status::NON_ELIGIBLE_REASON_TOO_MUCH_PAYMENT_INCIDENT];
        }

        foreach ($incidents as $incident) {
            $diff   = $currentDate->diff($incident->getDate());
            $period = (int) $diff->format('%y') * 12 + (int) $diff->format('%m');

            if (true === in_array($incident->getType(), $nonAllowedIncident) && 12 >= $period) {
                return [\projects_status::NON_ELIGIBLE_REASON_NON_ALLOWED_PAYMENT_INCIDENT];
            }
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    public function checkAltaresScore($siren)
    {
        $altaresScore = $this->altaresManager->getScore($siren);
        if (null === $altaresScore) {
            return [\projects_status::UNEXPECTED_RESPONSE . 'altares_score'];
        }

        if ($altaresScore->getScore20() < 4) {
            return [\projects_status::NON_ELIGIBLE_REASON_LOW_ALTARES_SCORE];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    public function checkCapitalStock($siren)
    {
        $lastBalanceSheet = $this->getLastBalanceSheetUpToDate($siren);
        if (null === $lastBalanceSheet) {
            return [];
        }

        $financialSummary = $this->altaresManager->getFinancialSummary($siren, $lastBalanceSheet->getBalanceSheetId());
        if (null === $financialSummary) {
            return [\projects_status::UNEXPECTED_RESPONSE . 'altares_fpro'];
        }

        $capitalStockPost = null;
        foreach ($financialSummary->getFinancialSummaryList() as $post) {
            if ($post->getPost() === 'posteSF_FPRO') {
                $capitalStockPost = $post;
                break;
            }
        }

        if (null === $capitalStockPost) {
            return [\projects_status::UNEXPECTED_RESPONSE . 'altares_fpro'];
        }

        if ($capitalStockPost->getAmountY() < 0) {
            return [\projects_status::NON_ELIGIBLE_REASON_NEGATIVE_CAPITAL_STOCK];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    public function checkGrossOperatingSurplus($siren)
    {
        $lastBalanceSheet = $this->getLastBalanceSheetUpToDate($siren);
        if (null === $lastBalanceSheet) {
            return [];
        }

        $balanceManagementLine = $this->altaresManager->getBalanceManagementLine($siren, $lastBalanceSheet->getBalanceSheetId());
        if (null === $balanceManagementLine) {
            return [\projects_status::UNEXPECTED_RESPONSE . 'altares_ebe'];
        }

        $grossOperatingSurplus = null;
        foreach ($balanceManagementLine->getBalanceManagementLine() as $post) {
            if ($post->getPost() === 'posteSIG_EBE') {
                $grossOperatingSurplus = $post;
                break;
            }
        }

        if (null === $grossOperatingSurplus) {
            return [\projects_status::UNEXPECTED_RESPONSE . 'altares_ebe'];
        }

        if ($grossOperatingSurplus->getAmountY() < 0) {
            return [\projects_status::NON_ELIGIBLE_REASON_NEGATIVE_RAW_OPERATING_INCOMES];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    public function checkEliminationXerfiScore($siren)
    {
        $nafCode = $this->getNAFCode($siren);
        $xerfi   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Xerfi')->find($nafCode);

        if (Xerfi::UNILEND_ELIMINATION_SCORE === $xerfi->getUnilendRating()) {
            return [\projects_status::NON_ELIGIBLE_REASON_UNILEND_XERFI_ELIMINATION_SCORE];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    public function checkAltaresScoreVsXerfiScore($siren)
    {
        $altaresScore = $this->altaresManager->getScore($siren);
        if (null === $altaresScore) {
            return [\projects_status::UNEXPECTED_RESPONSE . 'altares_score'];
        }

        $nafCode = $this->getNAFCode($siren);
        $xerfi   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Xerfi')->find($nafCode);

        if (
            in_array($altaresScore->getScore20(), [4, 5])
            && $xerfi->getScore() <= 75
        ) {
            return [\projects_status::NON_ELIGIBLE_REASON_UNILEND_XERFI_VS_ALTARES_SCORE];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    public function checkEulerHermesTrafficLight($siren)
    {
        $trafficLight = $this->eulerHermesManager->getTrafficLight($siren, 'fr');

        if (null === $trafficLight) {
            return [\projects_status::UNEXPECTED_RESPONSE . 'euler_traffic_light_score'];
        }

        if (in_array($trafficLight->getColor(), [EulerHermesCompanyRating::COLOR_WHITE, EulerHermesCompanyRating::COLOR_GREEN, EulerHermesCompanyRating::COLOR_YELLOW])) {
            return [];
        }

        if (EulerHermesCompanyRating::COLOR_BLACK === $trafficLight->getColor()) {
            return [\projects_status::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT];
        }

        $altaresScore = $this->altaresManager->getScore($siren);
        if (null === $altaresScore) {
            return [\projects_status::UNEXPECTED_RESPONSE . 'altares_score'];
        }

        if (
            EulerHermesCompanyRating::COLOR_RED === $trafficLight->getColor()
            && $altaresScore->getScore20() < 12
        ) {
            return [\projects_status::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT_VS_ALTARES_SCORE];
        }

        $nafCode = $this->getNAFCode($siren);
        $xerfi   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Xerfi')->find($nafCode);

        if (
            EulerHermesCompanyRating::COLOR_RED === $trafficLight->getColor()
            && $xerfi->getScore() > 75
        ) {
            return [\projects_status::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT_VS_UNILEND_XERFI];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    public function checkInfolegaleScore($siren)
    {
        $infolegaleScore = $this->infolegaleManager->getScore($siren);
        if (null === $infolegaleScore) {
            return [\projects_status::UNEXPECTED_RESPONSE . 'infolegale_score'];
        }

        if ($infolegaleScore->getScore() < 5) {
            return [\projects_status::NON_ELIGIBLE_REASON_LOW_INFOLEGALE_SCORE];
        }

        return [];
    }

    /**
     * @param string $siren
     *
     * @return array
     */
    public function checkEulerHermesGrade($siren)
    {
        $eulerHermesGrade = $this->eulerHermesManager->getGrade($siren, 'fr');
        if (null === $eulerHermesGrade) {
            return [\projects_status::UNEXPECTED_RESPONSE . 'euler_grade'];
        }

        $nafCode = $this->getNAFCode($siren);
        $xerfi   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Xerfi')->find($nafCode);

        if (
            $eulerHermesGrade->getGrade() >= 9
            || $eulerHermesGrade->getGrade() == 8 && $xerfi->getScore() > 75
        ) {
            return [\projects_status::NON_ELIGIBLE_REASON_EULER_GRADE_VS_UNILEND_XERFI];
        }

        $altaresScore = $this->altaresManager->getScore($siren);
        if (null === $altaresScore) {
            return [\projects_status::UNEXPECTED_RESPONSE . 'altares_score'];
        }

        if (
            $eulerHermesGrade->getGrade() >= 5 && $altaresScore->getScore20() == 4
            || $eulerHermesGrade->getGrade() >= 7 && $altaresScore->getScore20() == 5
        ) {
            return [\projects_status::NON_ELIGIBLE_REASON_EULER_GRADE_VS_ALTARES_SCORE];
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
        $altaresScoreCheck = $this->checkRule('checkAltaresScore', $siren, $project);
        if (false === empty($altaresScoreCheck)) {
            return $altaresScoreCheck;
        }

        $infolegaleScoreCheck = $this->checkRule('checkInfolegaleScore', $siren, $project);
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
        $balanceSheetList = $this->altaresManager->getBalanceSheets($siren);
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
        $companyData = $this->altaresManager->getCompanyIdentity($siren);
        return $companyData->getNAFCode();
    }

    /**
     * @param string        $methodName
     * @param string        $siren
     * @param Projects|null $project
     *
     * @return array
     */
    private function checkRule($methodName, $siren, Projects $project = null)
    {
        $object = new \ReflectionObject($this);
        $method = $object->getMethod($methodName);
        $result = $method->invoke($this, $siren);

        if ($project instanceof Projects) {
            $ruleRepository          = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityRule');
            $ruleSetRepository       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityRuleSet');
            $ruleSetMemberRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityRuleSetMember');
            $ruleSet                 = $ruleSetRepository->findOneBy(['status' => ProjectEligibilityRuleSet::STATUS_ACTIVE]);

            $rule          = $ruleRepository->findOneBy(['label' => self::$checkMethodReferences[$methodName]]);
            $ruleSetMember = $ruleSetMemberRepository->findOneBy([
                'idRuleSet' => $ruleSet,
                'idRule'    => $rule
            ]);

            $assessment = new ProjectEligibilityAssessment();
            $assessment->setIdProject($project);
            $assessment->setIdRuleSetMember($ruleSetMember);
            $assessment->setStatus(empty($result));

            $this->entityManager->persist($assessment);
            $this->entityManager->flush($assessment);
        }

        return $result;
    }
}
