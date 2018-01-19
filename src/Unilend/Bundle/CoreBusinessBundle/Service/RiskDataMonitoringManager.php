<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Companies, CompanyRating, CompanyRatingHistory, CompanyStatus, ProjectEligibilityRuleSet, ProjectsComments, ProjectsStatus, RiskDataMonitoring, RiskDataMonitoringAssessment, RiskDataMonitoringCallLog, RiskDataMonitoringType, Users
};
use Unilend\Bundle\WSClientBundle\Entity\Euler\CompanyRating as EulerCompanyRating;
use Unilend\Bundle\WSClientBundle\Service\AltaresManager;
use Unilend\Bundle\WSClientBundle\Service\EulerHermesManager;

class RiskDataMonitoringManager
{
    const LONG_TERM_MONITORING_EXCLUDED_PROJECTS_STATUS = [
        ProjectsStatus::ABANDONED,
        ProjectsStatus::COMMERCIAL_REJECTION,
        ProjectsStatus::ANALYSIS_REJECTION,
        ProjectsStatus::COMITY_REJECTION,
        ProjectsStatus::PRET_REFUSE,
        ProjectsStatus::FUNDING_KO,
        ProjectsStatus::REMBOURSE,
        ProjectsStatus::REMBOURSEMENT_ANTICIPE
    ];

    const LONG_TERM_MONITORING_EXCLUDED_COMPANY_STATUS = [
        CompanyStatus::STATUS_PRECAUTIONARY_PROCESS,
        CompanyStatus::STATUS_RECEIVERSHIP,
        CompanyStatus::STATUS_COMPULSORY_LIQUIDATION
    ];

    const PROVIDER_EULER   = 'euler';
    const PROVIDER_ALTARES = 'altares';

    /** @var EntityManager */
    private $entityManager;
    /** @var EulerHermesManager */
    private $eulerHermesManager;
    /** @var AltaresManager */
    private $altaresManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        EntityManager $entityManager,
        EulerHermesManager $eulerHermesManager,
        AltaresManager $altaresManager,
        LoggerInterface $logger,
        TranslatorInterface $translator
    )
    {
        $this->entityManager      = $entityManager;
        $this->eulerHermesManager = $eulerHermesManager;
        $this->altaresManager     = $altaresManager;
        $this->logger             = $logger;
        $this->translator         = $translator;
    }

    /**
     * @param string $siren
     * @param string $provider
     *
     * @return bool
     */
    public function isSirenMonitored(string $siren, string $provider) : bool
    {
        $monitoring = $this->getMonitoringForSiren($siren, $provider);

        if (null !== $monitoring) {
            return $monitoring->isOngoing();
        }

        return false;
    }

    /**
     * @param string $siren
     * @param string $countryCode
     *
     * @return null|EulerCompanyRating
     * @throws \Exception
     */
    public function activateEulerHermesGradeMonitoring(string $siren, string $countryCode)
    {
        $eulerHermesGrade = $this->eulerHermesManager->getGrade($siren, $countryCode, true);

        if (null !== $eulerHermesGrade) {
            $this->startMonitoringPeriod($siren, self::PROVIDER_EULER);
        }

        return $eulerHermesGrade;
    }

    /**
     * @param string $siren
     * @param string $provider
     *
     * @throws \Exception
     */
    public function saveEndOfMonitoringPeriodNotification(string $siren, string $provider) : void
    {
        $currentMonitoring = $this->getMonitoringForSiren($siren, $provider);
        if (null !== $currentMonitoring) {
            $this->stopMonitoringPeriod($currentMonitoring);
        }

        /** @var Companies $company */
        foreach ($this->getMonitoredCompanies($siren, $provider, false) as $company) {
            if (
                self::PROVIDER_EULER === $provider
                && $this->eligibleForEulerLongTermMonitoring($company)
            ) {
                if ($this->eulerHermesManager->startLongTermMonitoring($siren, 'fr')) {
                    $this->startMonitoringPeriod($siren, $provider);
                    break;
                }
            }
        }
    }

    /**
     * @param string $siren
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveEulerHermesGradeMonitoringEvent(string $siren) : void
    {
        $monitoring         = $this->getMonitoringForSiren($siren, self::PROVIDER_EULER);
        $monitoredCompanies = $this->getMonitoredCompanies($siren, self::PROVIDER_EULER);

        $monitoringType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoringType')->findOneBy(['provider' => self::PROVIDER_EULER]);

        $this->eulerHermesManager->setReadFromCache(false);
        try {
            $eulerGrade = $this->eulerHermesManager->getGrade($siren, 'fr', false);
        } catch(\Exception $exception) {
            $this->logger->error(
                'Could not get Euler grade: EulerHermesManager::getGrade(' . $monitoring->getSiren() . '). Message: ' . $exception->getMessage(),
                ['file' => $exception->getFile(), 'line' => $exception->getLine(), 'siren', $monitoring->getSiren()]
            );
        }
        $this->eulerHermesManager->setReadFromCache(true);

        /** @var Companies $company */
        foreach ($monitoredCompanies as $company) {
            if ($this->isSirenMonitored($company->getSiren(), self::PROVIDER_EULER)) {
                $monitoringCallLog = $this->createMonitoringEvent($monitoring);
                $this->entityManager->persist($monitoringCallLog);
                $this->entityManager->flush($monitoringCallLog);

                $companyRatingHistory = $this->saveEulerCompanyRating($company, $eulerGrade);

                $monitoringCallLog->setIdCompanyRatingHistory($companyRatingHistory);
                $this->entityManager->flush($monitoringCallLog);

                $this->saveAssessment($monitoringType, $monitoringCallLog, $eulerGrade->getGrade());
                $this->saveMonitoringEventInProjectMemos($company, self::PROVIDER_EULER);
            }
        }
        $this->entityManager->flush();
    }

    /**
     * @param string $siren
     */
    public function stopMonitoringForSiren(string $siren) : void
    {
        $riskDataMonitoringRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoring');
        $projectRepository            = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');

        try {
            if (0 == $projectRepository->getCountProjectsBySirenAndNotInStatus($siren, self::LONG_TERM_MONITORING_EXCLUDED_PROJECTS_STATUS, self::LONG_TERM_MONITORING_EXCLUDED_COMPANY_STATUS)) {
                /** @var RiskDataMonitoring $monitoring */
                foreach ($riskDataMonitoringRepository->findBy(['siren' => $siren, 'end' => null]) as $monitoring) {
                    switch ($monitoring->getProvider()) {
                        case self::PROVIDER_EULER:
                            $monitoringStopped = $this->eulerHermesManager->stopMonitoring($monitoring->getSiren(), 'fr');
                            break;
                        case self::PROVIDER_ALTARES:
                            $monitoringStopped = $this->altaresManager->stopMonitoring($monitoring->getSiren());
                            break;
                        default:
                            $monitoringStopped = false;
                            break;
                    }

                    if ($monitoringStopped) {
                        $this->stopMonitoringPeriod($monitoring);
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                'Could not stop monitoring for siren: ' . $siren . ' Error: ' . $exception->getMessage(),
                ['file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }
    }


    /**
     * @param RiskDataMonitoring $monitoring
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function stopMonitoringPeriod(RiskDataMonitoring $monitoring) : void
    {
        if ($monitoring->isOngoing()) {
            $monitoring->setEnd(new \DateTime('NOW'));
            $this->entityManager->flush($monitoring);

            $this->logger->info('End of monitoring period saved for siren ' . $monitoring->getSiren() . ' and provider ' . $monitoring->getProvider(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'siren'    => $monitoring->getSiren()
            ]);
        }
    }

    /**
     * @param string $siren
     * @param string $provider
     * @param bool   $isOngoing
     *
     * @return array
     */
    private function getMonitoredCompanies(string $siren, string $provider, bool $isOngoing = true) : array
    {
        $monitoredCompanies = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->getMonitoredCompaniesBySiren($siren, $provider, $isOngoing);

        return $monitoredCompanies;
    }


    /**
     * @param Companies $company
     *
     * @return bool
     * @throws \Exception
     */
    private function eligibleForEulerLongTermMonitoring(Companies $company) : bool
    {
        if (in_array($company->getIdStatus()->getLabel(), self::LONG_TERM_MONITORING_EXCLUDED_COMPANY_STATUS)) {
            return false;
        }
        foreach ($this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findBy(['idCompany' => $company]) as $project) {
            if (false === in_array($project->getStatus(), self::LONG_TERM_MONITORING_EXCLUDED_PROJECTS_STATUS )) {
                return true;
            }
        }

        return false;
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
        //TODO see if it is easier just to pass the monitoring object here instead of provider. given that all provider should start at the same time ...

        if ($this->isSirenMonitored($siren, $provider)) {
            throw new \Exception('Siren ' . $siren . ' is already monitored. Can not start monitoring period for provider ' . $provider);
        }

        $monitoring = $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoring')->findOneBy(['siren' => $siren, 'provider' => $provider, 'start' => NULL]);
        if (null === $monitoring) {
            throw new \Exception('Monitoring period can not start. There is no entry in risk_data_monitoring for siren ' . $siren . ' and provider ' . $provider);
        }

        $monitoring->setStart(new \DateTime('NOW'));

        $this->entityManager->persist($monitoring);
        $this->entityManager->flush($monitoring);

        return $monitoring;
    }

    /**
     * @param RiskDataMonitoring $monitoring
     *
     * @return RiskDataMonitoringCallLog
     */
    private function createMonitoringEvent(RiskDataMonitoring $monitoring) : RiskDataMonitoringCallLog
    {
        $monitoringCallLog = new RiskDataMonitoringCallLog();
        $monitoringCallLog
            ->setIdRiskDataMonitoring($monitoring)
            ->setAdded(new \DateTime('NOW'));

        return $monitoringCallLog;
    }

    /**
     * @param Companies $company
     * @param string    $provider
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function saveMonitoringEventInProjectMemos(Companies $company, string $provider) : void
    {
        $memoContent             = '<p><b>Evénement surveillance ' . $provider . ' </b></p><ul>';
        $providerMonitoringTypes = $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoringType')->findBy(['provider' => $provider]);

        /** @var RiskDataMonitoringType $type */
        foreach ($providerMonitoringTypes as $type) {
            $assessment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoringAssessment')->findOneBy(['idRiskDataMonitoringType' => $type]);
            if (null !== $type->getIdProjectEligibilityRule() && null !== $assessment->getIdProjectEligibilityRuleSet()) {
                $ruleSet     = 'Politique de Risque : ' . $assessment->getIdProjectEligibilityRuleSet()->getLabel();
                $rule        = $type->getIdProjectEligibilityRule()->getLabel() . ' ' . $type->getIdProjectEligibilityRule()->getLabel();
                $result      = (bool) $assessment->getValue() ? 'ok' : 'echoué';
                $memoContent .= '<li>' . $ruleSet . ' : ' . $rule . ' ' . $result . '<li>';
            } else {
                $memoContent .= '<li>' . $assessment->getIdRiskDataMonitoringType()->getLabel() . ' ' . $assessment->getValue() . '</li>';
            }
            //TODO: translations provider, value
        }
        $memoContent .= '</ul>';

        $companyProjects = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findBy(['idCompany' => $company]);
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
     * @param Companies          $company
     * @param EulerCompanyRating $eulerGrade
     *
     * @return CompanyRatingHistory
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function saveEulerCompanyRating(Companies $company, EulerCompanyRating $eulerGrade) : CompanyRatingHistory
    {
        $companyRatingHistory = new CompanyRatingHistory();
        $companyRatingHistory
            ->setIdCompany($company)
            ->setAction(\company_rating_history::ACTION_WS)
            ->setIdUser(Users::USER_ID_WEBSERVICE);

        $this->entityManager->persist($companyRatingHistory);
        $this->entityManager->flush($companyRatingHistory);

        $companyRating = new CompanyRating();
        $companyRating
            ->setIdCompanyRatingHistory($companyRatingHistory)
            ->setType(CompanyRating::TYPE_EULER_HERMES_GRADE)
            ->setValue($eulerGrade->getGrade());

        $this->entityManager->persist($companyRating);
        $this->entityManager->flush($companyRating);

        return $companyRatingHistory;
    }

    /**
     * @param string $siren
     * @param string $provider
     *
     * @return null|RiskDataMonitoring
     */
    private function getMonitoringForSiren(string $siren, string $provider) : ?RiskDataMonitoring
    {
        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoring')->findOneBy(['siren' => $siren, 'provider' => $provider, 'end' => null]);
    }

    /**
     * @param string $siren
     *
     * @return bool
     */
    public function hasMonitoringEvent($siren) : bool
    {
        $callLogs = $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoringCallLog')->findCallLogsForSiren($siren);

        return count($callLogs) > 0;
    }

    /**
     * @param RiskDataMonitoringType    $monitoringType
     * @param RiskDataMonitoringCallLog $monitoringCallLog
     * @param string|bool|int           $value
     *
     * @return RiskDataMonitoringAssessment
     * @throws OptimisticLockException
     * */
    private function saveAssessment(RiskDataMonitoringType $monitoringType, RiskDataMonitoringCallLog $monitoringCallLog, $value) : RiskDataMonitoringAssessment
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
}
