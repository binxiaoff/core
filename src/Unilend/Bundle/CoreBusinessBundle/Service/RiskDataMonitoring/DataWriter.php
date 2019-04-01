<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\RiskDataMonitoring;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Entity\{Companies, CompanyRating, CompanyRatingHistory, ProjectEligibilityRuleSet, Projects, ProjectsComments, RiskDataMonitoring, RiskDataMonitoringAssessment, RiskDataMonitoringCallLog,
    RiskDataMonitoringType, Users};
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager;

class DataWriter
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var TranslatorInterface */
    private $translator;
    /** @var ProjectStatusManager */
    private $projectStatusManager;
    /** @var MonitoringManager */
    private $monitoringManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface    $translator
     * @param ProjectStatusManager   $projectStatusManager
     * @param MonitoringManager      $monitoringManager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        ProjectStatusManager $projectStatusManager,
        MonitoringManager $monitoringManager
    )
    {
        $this->entityManager        = $entityManager;
        $this->translator           = $translator;
        $this->projectStatusManager = $projectStatusManager;
        $this->monitoringManager    = $monitoringManager;
    }

    /**
     * @param Companies $company
     *
     * @return CompanyRatingHistory
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createCompanyRatingHistory(Companies $company): CompanyRatingHistory
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
     * @param string|int|bool           $value
     *
     * @return RiskDataMonitoringAssessment
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveAssessment(RiskDataMonitoringType $monitoringType, RiskDataMonitoringCallLog $monitoringCallLog, $value): RiskDataMonitoringAssessment
    {
        $currentRiskPolicy = null;
        if ($monitoringType->getIdProjectEligibilityRule()) {
            $currentRiskPolicy = $this->entityManager->getRepository(ProjectEligibilityRuleSet::class)
                ->findOneBy(['status' => ProjectEligibilityRuleSet::STATUS_ACTIVE]);
        }

        $assessment = new RiskDataMonitoringAssessment();
        $assessment
            ->setIdProjectEligibilityRuleSet($currentRiskPolicy)
            ->setIdRiskDataMonitoringType($monitoringType)
            ->setIdRiskDataMonitoringCallLog($monitoringCallLog)
            ->setValue($value);

        $this->entityManager->persist($assessment);

        try {
            $this->entityManager->flush($assessment);
        } catch (\PDOException $exception) {
            if ('23000' == $exception->getCode()) {
                $this->entityManager->flush($assessment);
            } else {
                throw $exception;
            }
        }

        return $assessment;
    }

    /**
     * @param RiskDataMonitoringCallLog $callLog
     * @param string                    $provider
     *
     * @return string
     * @throws \Exception
     */
    private function projectRiskEvaluationToHtml(RiskDataMonitoringCallLog $callLog, string $provider): string
    {
        $memoContent             = '';
        $providerMonitoringTypes = $this->entityManager->getRepository(RiskDataMonitoringType::class)->findBy(['provider' => $provider]);

        /** @var RiskDataMonitoringType $type */
        foreach ($providerMonitoringTypes as $type) {
            if (null === $type->getIdProjectEligibilityRule()) {
                continue;
            }

            $assessment  = $this->entityManager->getRepository(RiskDataMonitoringAssessment::class)
                ->findOneBy(['idRiskDataMonitoringType' => $type, 'idRiskDataMonitoringCallLog' => $callLog]);
            $rule        = $type->getIdProjectEligibilityRule()->getDescription();
            $reason      = $this->projectStatusManager->getStatusReasonByLabel($assessment->getValue(), 'rejection');
            $result      = '1' === $assessment->getValue() ? 'ok' : 'echouée, ' . (false === empty($reason['description'])) ? implode(' - ', $reason) : $reason['reason'];
            $memoContent .= '<li>' . $rule . ' :<strong> ' . $result . '</strong></li>';
        }

        $currentRiskPolicy = $this->entityManager->getRepository(ProjectEligibilityRuleSet::class)->findOneBy(['status' => ProjectEligibilityRuleSet::STATUS_ACTIVE]);
        $ruleSet           = '<br><strong>Evaluation des règles concernées selon politique de risque version ' . $currentRiskPolicy->getLabel() . '</strong>';
        $eligibilityHtml   = empty($memoContent) ? '' : $ruleSet . '<ul>' . $memoContent . '</ul>';

        return $eligibilityHtml;
    }

    /**
     * @param RiskDataMonitoringCallLog $callLog
     * @param string                    $provider
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function saveMonitoringEventInProjectMemos(RiskDataMonitoringCallLog $callLog, string $provider): void
    {
        $projectCommentsRepository = $this->entityManager->getRepository(ProjectsComments::class);

        $newData        = $this->companyRatingToHtml($callLog->getIdCompanyRatingHistory());
        $riskEvaluation = $this->projectRiskEvaluationToHtml($callLog, $provider);

        $memoContent = '<strong>Événement surveillance eligibilité ' . $this->getProviderName($provider) . ' </strong><br>';
        $memoContent .= false === empty($newData) ? $newData : '';
        $memoContent .= false === empty($riskEvaluation) ? $riskEvaluation : '';

        $companyProjects = $this->entityManager->getRepository(Projects::class)->findBy(['idCompany' => $callLog->getIdCompanyRatingHistory()->getIdCompany()]);
        foreach ($companyProjects as $project) {
            $commentWithSameContent = $projectCommentsRepository->findOneBy(['idProject' => $project, 'content' => $memoContent], ['added' => 'DESC']);

            if (null === $commentWithSameContent) {
                $projectCommentEntity = new ProjectsComments();
                $projectCommentEntity
                    ->setIdProject($project)
                    ->setIdUser($this->entityManager->getRepository(Users::class)->find(Users::USER_ID_WEBSERVICE))
                    ->setContent($memoContent)
                    ->setPublic(false);

                $this->entityManager->persist($projectCommentEntity);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @param CompanyRatingHistory $companyRatingHistory
     *
     * @return string
     */
    public function companyRatingToHtml(CompanyRatingHistory $companyRatingHistory): string
    {
        $companyRatings = $this->entityManager->getRepository(CompanyRating::class)
            ->findBy(['idCompanyRatingHistory' => $companyRatingHistory->getIdCompanyRatingHistory()]);

        $html = '';
        foreach ($companyRatings as $rating) {
            $html .= $this->translator->trans('company-rating_' . $rating->getType()) . ' : ' . $rating->getValue() . '<br>';
        }

        return $html;
    }

    /**
     * @param string $siren
     * @param string $provider
     *
     * @return RiskDataMonitoring
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function startMonitoringPeriod(string $siren, string $provider): RiskDataMonitoring
    {
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
    public function createMonitoringEvent(RiskDataMonitoring $monitoring, CompanyRatingHistory $companyRatingHistory): RiskDataMonitoringCallLog
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
    public function stopMonitoringPeriod(RiskDataMonitoring $monitoring): void
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
    private function getProviderName(string $provider): string
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
