<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Companies, CompanyRating, CompanyRatingHistory, CompanyStatus, ProjectsComments, ProjectsStatus, RiskDataMonitoring, RiskDataMonitoringCallLog, Users
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
     * @param string $ratingType
     *
     * @return bool
     */
    public function isSirenMonitored(string $siren, string $ratingType)
    {
        $monitoring = $this->getMonitoringForSiren($siren, $ratingType);

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
    public function getEulerHermesGradeWithMonitoring(string $siren, string $countryCode)
    {
        $eulerHermesGrade   = null;
        $existingMonitoring = $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoring')->findOneBy(['siren' => $siren,'ratingType' => CompanyRating::TYPE_EULER_HERMES_GRADE]);

        if (null !== $existingMonitoring) {
            $eulerHermesGrade = $this->eulerHermesManager->getGrade($siren, $countryCode, false);
        } else {
            try {
                $eulerHermesGrade = $this->eulerHermesManager->getGrade($siren, $countryCode, true);
            } catch (\Exception $exception) {
                if (EulerHermesManager::EULER_ERROR_CODE_FREE_MONITORING_ALREADY_REQUESTED == $exception->getCode()) {
                    $eulerHermesGrade = $this->eulerHermesManager->getGrade($siren, $countryCode, false);
                }
                if (null !== $eulerHermesGrade) {
                    $this->startMonitoringPeriod($siren, CompanyRating::TYPE_EULER_HERMES_GRADE);
                }
            }
        }

        return $eulerHermesGrade;
    }

    /**
     * @param string $siren
     * @param string $ratingType
     *
     * @throws \Exception
     */
    public function saveEndOfMonitoringPeriodNotification(string $siren, string $ratingType)
    {
        $currentMonitoring = $this->getMonitoringForSiren($siren, $ratingType);
        if (null !== $currentMonitoring) {
            $this->stopMonitoringPeriod($currentMonitoring);
        }

        /** @var Companies $company */
        foreach ($this->getMonitoredCompanies($siren, $ratingType, false) as $company) {
            if (
                CompanyRating::TYPE_EULER_HERMES_GRADE === $ratingType
                && $this->eligibleForEulerLongTermMonitoring($company)
            ) {
                if ($this->eulerHermesManager->startLongTermMonitoring($siren, 'fr')) {
                    $this->startMonitoringPeriod($siren, $ratingType);
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
    public function saveEulerHermesGradeMonitoringEvent(string $siren)
    {
        $monitoring         = $this->getMonitoringForSiren($siren, CompanyRating::TYPE_EULER_HERMES_GRADE);
        $monitoredCompanies = $this->getMonitoredCompanies($siren, CompanyRating::TYPE_EULER_HERMES_GRADE);

        /** @var Companies $company */
        foreach ($monitoredCompanies as $company) {
            if ($this->isSirenMonitored($company->getSiren(), CompanyRating::TYPE_EULER_HERMES_GRADE)) {
                $monitoringCallLog = $this->createMonitoringEvent($monitoring);
                $this->entityManager->persist($monitoringCallLog);
                $this->entityManager->flush($monitoringCallLog);

                try {
                    $this->eulerHermesManager->setReadFromCache(false);
                    if (null !== ($eulerGrade = $this->eulerHermesManager->getGrade($siren, 'fr', false))) {
                        $companyRatingHistory = $this->saveCompanyRating($company, $eulerGrade);

                        $monitoringCallLog->setIdCompanyRatingHistory($companyRatingHistory);
                        $this->entityManager->flush($monitoringCallLog);
                    }
                } catch (\Exception $exception) {
                    $this->logger->error(
                        'Could not get Euler grade: EulerHermesManager::getGrade(' . $monitoring->getSiren() . '). Message: ' . $exception->getMessage(),
                        ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren', $monitoring->getSiren()]
                    );
                }
            }
        }
        $this->eulerHermesManager->setReadFromCache(true);
        $this->entityManager->flush();
    }

    /**
     * @param string $siren
     */
    public function stopMonitoringForSiren(string $siren)
    {
        $riskDataMonitoringRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoring');
        $projectRepository            = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');

        try {
            if (0 == $projectRepository->getCountProjectsBySirenAndNotInStatus($siren, self::LONG_TERM_MONITORING_EXCLUDED_PROJECTS_STATUS, self::LONG_TERM_MONITORING_EXCLUDED_COMPANY_STATUS)) {
                /** @var RiskDataMonitoring $monitoring */
                foreach ($riskDataMonitoringRepository->findBy(['siren' => $siren, 'end' => null]) as $monitoring) {
                    switch ($monitoring->getRatingType()) {
                        case CompanyRating::TYPE_EULER_HERMES_GRADE:
                            $monitoringStopped = $this->eulerHermesManager->stopMonitoring($monitoring->getSiren(), 'fr');
                            break;
                        case CompanyRating::TYPE_ALTARES_SCORE_20:
                        case CompanyRating::TYPE_ALTARES_SECTORAL_SCORE_100:
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
    private function stopMonitoringPeriod(RiskDataMonitoring $monitoring)
    {
        if ($monitoring->isOngoing()) {
            $monitoring->setEnd(new \DateTime('NOW'));
            $this->entityManager->flush($monitoring);

            $this->logger->info('End of monitoring period saved for siren ' . $monitoring->getSiren() . ' and type ' . $monitoring->getRatingType(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren', $monitoring->getSiren()]);
        }
    }

    /**
     * @param string $siren
     * @param string $ratingType
     * @param bool   $isOngoing
     *
     * @return null|array
     */
    private function getMonitoredCompanies(string $siren, string $ratingType = null, bool $isOngoing = true)
    {
        $monitoredCompanies = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->getMonitoredCompaniesBySiren($siren, $ratingType, $isOngoing);

        return $monitoredCompanies;
    }


    /**
     * @param Companies $company
     *
     * @return bool
     * @throws \Exception
     */
    private function eligibleForEulerLongTermMonitoring(Companies $company)
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
     * @param string $ratingType
     *
     * @return null|RiskDataMonitoring
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function startMonitoringPeriod(string $siren, string $ratingType)
    {
        if ($this->isSirenMonitored($siren, $ratingType)) {
            $this->logger->warning('Siren ' . $siren . ' is already monitored. Can not start monitoring period for type ' . $ratingType, ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren', $siren]);
            return null;
        }

        $monitoring = new RiskDataMonitoring();
        $monitoring
            ->setSiren($siren)
            ->setRatingType($ratingType)
            ->setStart(new \DateTime('NOW'));

        $this->entityManager->persist($monitoring);
        $this->entityManager->flush($monitoring);

        return $monitoring;
    }

    /**
     * @param RiskDataMonitoring $monitoring
     *
     * @return RiskDataMonitoringCallLog
     */
    private function createMonitoringEvent(RiskDataMonitoring $monitoring) : RiskDataMonitoring
    {
        $monitoringCallLog = new RiskDataMonitoringCallLog();
        $monitoringCallLog
            ->setIdRiskDataMonitoring($monitoring)
            ->setAdded(new \DateTime('NOW'));

        return $monitoringCallLog;
    }

    /**
     * @param CompanyRating $companyRating
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function saveMonitoringEventInProjectMemos(CompanyRating $companyRating)
    {
        $companyProjects = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')
            ->findBy(['idCompany' => $companyRating->getIdCompanyRatingHistory()->getIdCompany()]);

        $memoContent = '<p><b>Evénement monitoring</b> : ' . $this->translator->trans('company-rating_' . $companyRating->getType()) . ' : ' . $companyRating->getValue() . '</p>';

        foreach ($companyProjects as $project) {
            $projectCommentEntity = new ProjectsComments();
            $projectCommentEntity
                ->setIdProject($project)
                ->setIdUser($this->entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_FRONT))
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
    private function saveCompanyRating(Companies $company, EulerCompanyRating $eulerGrade)
    {
        $companyRatingHistory = new CompanyRatingHistory();
        $companyRatingHistory
            ->setIdCompany($company)
            ->setAction(\company_rating_history::ACTION_WS)
            ->setIdUser(Users::USER_ID_FRONT);

        $this->entityManager->persist($companyRatingHistory);
        $this->entityManager->flush($companyRatingHistory);

        $companyRating = new CompanyRating();
        $companyRating
            ->setIdCompanyRatingHistory($companyRatingHistory)
            ->setType(CompanyRating::TYPE_EULER_HERMES_GRADE)
            ->setValue($eulerGrade->getGrade());

        $this->entityManager->persist($companyRating);
        $this->entityManager->flush($companyRating);

        $this->saveMonitoringEventInProjectMemos($companyRating);

        return $companyRatingHistory;
    }

    /**
     * @param string $siren
     * @param string $ratingType
     *
     * @return null|RiskDataMonitoring
     */
    private function getMonitoringForSiren($siren, $ratingType)
    {
        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoring')->findOneBy(['siren' => $siren, 'ratingType' => $ratingType, 'end' => null]);
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
}
