<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\RiskDataMonitoring;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Companies, CompanyRating, CompanyRatingHistory
};
use Unilend\Bundle\WSClientBundle\Entity\Euler\CompanyRating as EulerCompanyRating;
use Unilend\Bundle\WSClientBundle\Service\EulerHermesManager as EulerHermesWsClient;

class EulerHermesManager
{
    const PROVIDER_NAME = 'euler_hermes';

    /** @var EntityManager */
    private $entityManager;
    /** @var EulerHermesWsClient */
    private $eulerHermesManager;
    /** @var DataWriter */
    private $dataWriter;
    /** @var MonitoringManger */
    private $monitoringManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManager       $entityManager
     * @param EulerHermesWsClient $eulerHermesManager
     * @param DataWriter          $dataWriter
     * @param MonitoringManger    $monitoringManager
     * @param LoggerInterface     $logger
     */
    public function __construct(
        EntityManager $entityManager,
        EulerHermesWsClient $eulerHermesManager,
        DataWriter $dataWriter,
        MonitoringManger $monitoringManager,
        LoggerInterface $logger
    )
    {
        $this->entityManager          = $entityManager;
        $this->eulerHermesManager     = $eulerHermesManager;
        $this->dataWriter             = $dataWriter;
        $this->monitoringManager = $monitoringManager;
        $this->logger                 = $logger;
    }

    /**
     * @param string $siren
     * @param string $countryCode
     *
     * @return null|EulerCompanyRating
     * @throws \Exception
     */
    public function activateMonitoring(string $siren, string $countryCode) : ?EulerCompanyRating
    {
        $this->eulerHermesManager->setReadFromCache(false);
        $eulerHermesGrade = $this->eulerHermesManager->getGrade($siren, $countryCode, true);
        $this->eulerHermesManager->setReadFromCache(true);

        if (null !== $eulerHermesGrade) {
            $this->dataWriter->startMonitoringPeriod($siren, self::PROVIDER_NAME);
        }

        return $eulerHermesGrade;
    }

    /**
     * @param string $siren
     *
     * @throws OptimisticLockException
     */
    public function saveEulerHermesGradeMonitoringEvent(string $siren) : void
    {
        $monitoring         = $this->monitoringManager->getMonitoringForSiren($siren, self::PROVIDER_NAME);
        $monitoredCompanies = $this->monitoringManager->getMonitoredCompanies($siren, self::PROVIDER_NAME);
        $monitoringType     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoringType')->findOneBy(['provider' => self::PROVIDER_NAME]);

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
            $companyRatingHistory = $this->saveEulerCompanyRating($company, $eulerGrade);
            $monitoringCallLog    = $this->dataWriter->createMonitoringEvent($monitoring, $companyRatingHistory);

            $this->dataWriter->saveAssessment($monitoringType, $monitoringCallLog, $eulerGrade->getGrade());
            $this->dataWriter->saveMonitoringEventInProjectMemos($monitoringCallLog, self::PROVIDER_NAME);
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
        $companyRatingHistory = $this->dataWriter->createCompanyRatingHistory($company);

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
     * @param Companies $company
     *
     * @return bool
     * @throws \Exception
     */
    public function eligibleForEulerLongTermMonitoring(Companies $company) : bool
    {
        if (in_array($company->getIdStatus()->getLabel(), MonitoringCycleManager::LONG_TERM_MONITORING_EXCLUDED_COMPANY_STATUS)) {
            return false;
        }
        foreach ($this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findBy(['idCompany' => $company]) as $project) {
            if (false === in_array($project->getStatus(), MonitoringCycleManager::LONG_TERM_MONITORING_EXCLUDED_PROJECTS_STATUS )) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $siren
     *
     * @return bool
     * @throws \Exception
     */
    public function activateLongTermMonitoring(string $siren) : bool
    {
        if ($this->eulerHermesManager->startLongTermMonitoring($siren, 'fr')) {
            $this->dataWriter->startMonitoringPeriod($siren, EulerHermesManager::PROVIDER_NAME);

            return true;
        }

        return false;
    }

    /**
     * @param string $siren
     *
     * @return bool
     * @throws \Exception
     */
    public function stopMonitoring(string $siren) : bool
    {
        return $this->eulerHermesManager->stopMonitoring($siren, 'fr');
    }

}
