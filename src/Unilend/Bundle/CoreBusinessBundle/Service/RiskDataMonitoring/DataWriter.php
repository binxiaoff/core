<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\RiskDataMonitoring;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Companies, CompanyRatingHistory, ProjectEligibilityRuleSet, ProjectsComments, RiskDataMonitoring, RiskDataMonitoringAssessment, RiskDataMonitoringCallLog, RiskDataMonitoringType, Users
};
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager;

class DataWriter
{
    /** @var EntityManager */
    private $entityManager;
    /** @var TranslatorInterface */
    private $translator;
    /** @var ProjectStatusManager */
    private $projectStatusManager;
    /** @var MonitoringManger  */
    private $monitoringManager;

    /**
     * @param EntityManager        $entityManager
     * @param TranslatorInterface  $translator
     * @param ProjectStatusManager $projectStatusManager
     * @param MonitoringManger     $monitoringManager
     */
    public function __construct(
        EntityManager $entityManager,
        TranslatorInterface $translator,
        ProjectStatusManager $projectStatusManager,
        MonitoringManger $monitoringManager
    )
    {
        $this->entityManager           = $entityManager;
        $this->translator              = $translator;
        $this->projectStatusManager = $projectStatusManager;
        $this->monitoringManager    = $monitoringManager;
    }

    /**
     * @param Companies $company
     *
     * @return CompanyRatingHistory
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createCompanyRatingHistory(Companies $company) : CompanyRatingHistory
    {
        $companyRatingHistory = new CompanyRatingHistory();
        $companyRatingHistory
            ->setIdCompany($company)
            ->setAction(\company_rating_history::ACTION_WS)
            ->setIdUser(Users::USER_ID_WEBSERVICE);

        $this->entityManager->persist($companyRatingHistory);
        $this->entityManager->flush($companyRatingHistory);

        return $companyRatingHistory;
    }

    /**
     * @param RiskDataMonitoringType    $monitoringType
     * @param RiskDataMonitoringCallLog $monitoringCallLog
     * @param                           $value
     *
     * @return RiskDataMonitoringAssessment
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveAssessment(RiskDataMonitoringType $monitoringType, RiskDataMonitoringCallLog $monitoringCallLog, $value) : RiskDataMonitoringAssessment
    {
        $currentRiskPolicy = null;
        if ($monitoringType->getIdProjectEligibilityRule()) {
            $currentRiskPolicy = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityRuleSet')
                ->findOneBy(['status' => ProjectEligibilityRuleSet::STATUS_ACTIVE]);
        }

        $assessment = new RiskDataMonitoringAssessment();
        $assessment
            ->setIdProjectEligibilityRuleSet($currentRiskPolicy)
            ->setIdRiskDataMonitoringType($monitoringType)
            ->setIdRiskDataMonitoringCallLog($monitoringCallLog)
            ->setValue($value);

        $this->entityManager->persist($assessment);
        $this->entityManager->flush($assessment);

        return $assessment;
    }

    /**
     * @param RiskDataMonitoringCallLog $callLog
     * @param string                    $provider
     *
     * @return string
     */
    public function projectRiskEvaluationToHtml(RiskDataMonitoringCallLog $callLog, string $provider) : string
    {
        $memoContent             = '';
        $providerMonitoringTypes = $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoringType')->findBy(['provider' => $provider]);

        /** @var RiskDataMonitoringType $type */
        foreach ($providerMonitoringTypes as $type) {
            if (null === $type->getIdProjectEligibilityRule()) {
                continue;
            }

            $assessment  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoringAssessment')
                ->findOneBy(['idRiskDataMonitoringType' => $type, 'idRiskDataMonitoringCallLog' => $callLog]);
            $rule        = $type->getIdProjectEligibilityRule()->getDescription();
            $result      = '1' === $assessment->getValue() ? 'ok' : 'echouée, ' . $this->projectStatusManager->getRejectionReasonTranslation($assessment->getValue());
            $memoContent .= '<li>' . $rule . ' :<strong> ' . $result . '</strong></li>';
        }

        $currentRiskPolicy = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityRuleSet')->findOneBy(['status' => ProjectEligibilityRuleSet::STATUS_ACTIVE]);
        $ruleSet           = '<br><strong>Evaluation des règles concernées selon politique de risque version ' . $currentRiskPolicy->getLabel() . '</strong>';
        $eligibilityHtml   = empty($memoContent) ? '' : $ruleSet . '<ul>' . $memoContent . '</ul>';

        return $eligibilityHtml;
    }

    /**
     * @param RiskDataMonitoringCallLog $callLog
     * @param string                    $provider
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveMonitoringEventInProjectMemos(RiskDataMonitoringCallLog $callLog, string $provider) : void
    {
        $newData        = $this->companyRatingToHtml($callLog->getIdCompanyRatingHistory());
        $riskEvaluation = $this->projectRiskEvaluationToHtml($callLog, $provider);

        $memoContent = '<strong>Événement surveillance eligibilité ' . $this->getProviderName($provider) . ' </strong><br>';
        $memoContent .= false === empty($newData) ? $newData : '';
        $memoContent .= false === empty($riskEvaluation) ? $riskEvaluation : '';

        $companyProjects = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findBy(['idCompany' => $callLog->getIdCompanyRatingHistory()->getIdCompany()]);
        foreach ($companyProjects as $project) {
            $projectCommentEntity = new ProjectsComments();
            $projectCommentEntity
                ->setIdProject($project)
                ->setIdUser($this->entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_WEBSERVICE))
                ->setContent($memoContent)
                ->setPublic(false);

            $this->entityManager->persist($projectCommentEntity);
        }

        $this->entityManager->flush();
    }

    /**
     * @param CompanyRatingHistory $companyRatingHistory
     *
     * @return string
     */
    public function companyRatingToHtml(CompanyRatingHistory $companyRatingHistory) : string
    {
        $companyRatings = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyRating')
            ->findBy(['idCompanyRatingHistory' => $companyRatingHistory->getIdCompanyRatingHistory()]);

        $html = '';

        if (false === empty($companyRatings)) {
            foreach ($companyRatings as $rating) {
                $html .= $this->translator->trans('company-rating_' . $rating->getType()) . ' : ' . $rating->getValue() . '<br>';
            }
        }

        return $html;
    }

    /**
     * @param string $siren
     * @param string $provider
     *
     * @return RiskDataMonitoring
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function startMonitoringPeriod(string $siren, string $provider) : RiskDataMonitoring
    {
        if ($this->monitoringManager->isSirenMonitored($siren, $provider)) {
            throw new \Exception('Siren ' . $siren . ' is already monitored. Can not start monitoring period for provider ' . $provider);
        }

        $monitoring = new RiskDataMonitoring();
        $monitoring->setSiren($siren)
            ->setProvider($provider)
            ->setStart(new \DateTime('NOW'));

        $this->entityManager->persist($monitoring);
        $this->entityManager->flush($monitoring);

        return $monitoring;
    }

    /**
     * @param RiskDataMonitoring   $monitoring
     * @param CompanyRatingHistory $companyRatingHistory
     *
     * @return RiskDataMonitoringCallLog
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createMonitoringEvent(RiskDataMonitoring $monitoring, CompanyRatingHistory $companyRatingHistory) : RiskDataMonitoringCallLog
    {
        $monitoringCallLog = new RiskDataMonitoringCallLog();
        $monitoringCallLog
            ->setIdRiskDataMonitoring($monitoring)
            ->setIdCompanyRatingHistory($companyRatingHistory)
            ->setAdded(new \DateTime('NOW'));

        $this->entityManager->persist($monitoringCallLog);
        $this->entityManager->flush($monitoringCallLog);

        return $monitoringCallLog;
    }

    /**
     * @param RiskDataMonitoring $monitoring
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function stopMonitoringPeriod(RiskDataMonitoring $monitoring) : void
    {
        if ($monitoring->isOngoing()) {
            $monitoring->setEnd(new \DateTime('NOW'));
            $this->entityManager->flush($monitoring);
        }
    }

    /**
     * @param string $provider
     *
     * @return string
     */
    private function getProviderName(string $provider) : string
    {
        switch ($provider) {
            case AltaresManager::PROVIDER_NAME:
                return 'Altares';
            case EulerHermesManager::PROVIDER_NAME:
                return 'Euler Hermes';
            default:
                return '';
        }
    }
}
