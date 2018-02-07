<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\RiskDataMonitoring;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Companies, CompanyStatus, ProjectsStatus, RiskDataMonitoring
};

class MonitoringCycleManager
{
    const LONG_TERM_MONITORING_EXCLUDED_PROJECTS_STATUS = [
        ProjectsStatus::NOT_ELIGIBLE,
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
    /** @var DataWriter */
    private $dataWriter;
    /** @var MonitoringManger */
    private $monitoringManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManager           $entityManager
     * @param EulerHermesManager      $eulerHermesManager
     * @param AltaresManager          $altaresManager
     * @param DataWriter              $dataWriter
     * @param LoggerInterface         $logger
     */
    public function __construct(
        EntityManager $entityManager,
        EulerHermesManager $eulerHermesManager,
        AltaresManager $altaresManager,
        DataWriter $dataWriter,
        MonitoringManger $monitoringManager,
        LoggerInterface $logger
    ) {
        $this->entityManager      = $entityManager;
        $this->eulerHermesManager = $eulerHermesManager;
        $this->altaresManager     = $altaresManager;
        $this->dataWriter         = $dataWriter;
        $this->monitoringManager  = $monitoringManager;
        $this->logger             = $logger;
    }

    /**
     * @param string $siren
     */
    public function activateMonitoringForSiren(string $siren) : void
    {
        try {
            $this->eulerHermesManager->activateMonitoring($siren);
            $this->altaresManager->activateMonitoring($siren);
        } catch (\Exception $exception) {
            $this->logger->error('Risk data monitoring could not be activated for siren ' . $siren . '. Exception: ' . $exception->getMessage(), [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'siren' => $siren
            ]);
        }
    }

    /**
     * @param string $siren
     * @param string $provider
     *
     * @throws \Exception
     */
    public function saveEndOfMonitoringPeriodNotification(string $siren, string $provider) : void
    {
        $currentMonitoring = $this->monitoringManager->getMonitoringForSiren($siren, $provider);
        if (null !== $currentMonitoring) {
            $this->dataWriter->stopMonitoringPeriod($currentMonitoring);
        }

        /** @var Companies $company */
        foreach ($this->monitoringManager->getMonitoredCompanies($siren, $provider, false) as $company) {
            if (
                EulerHermesManager::PROVIDER_NAME === $provider
                && $this->eulerHermesManager->eligibleForEulerLongTermMonitoring($company)
            ) {
                $this->eulerHermesManager->activateLongTermMonitoring($siren);
                break;
            }
        }
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
                        case EulerHermesManager::PROVIDER_NAME:
                            $monitoringStopped = $this->eulerHermesManager->stopMonitoring($monitoring->getSiren());
                            break;
                        case AltaresManager::PROVIDER_NAME:
                            $monitoringStopped = $this->altaresManager->stopMonitoring($monitoring->getSiren());
                            break;
                        default:
                            $monitoringStopped = false;
                            break;
                    }

                    if ($monitoringStopped) {
                        $this->dataWriter->stopMonitoringPeriod($monitoring);
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
     * @param string $siren
     */
    public function reactivateMonitoringForSiren(string $siren) : void
    {
        $companies = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findBy(['siren' => $siren]);
        foreach ($companies as $company) {
            try {
                if ($this->eulerHermesManager->eligibleForEulerLongTermMonitoring($company)) {
                    $this->altaresManager->activateMonitoring($siren);
                    $this->eulerHermesManager->activateLongTermMonitoring($siren);
                    break;
                }
            } catch (\Exception $exception) {
                $this->logger->error('Risk data monitoring could not be re-activated for siren ' . $siren . '. Exception: ' . $exception->getMessage(), [
                    'file'  => $exception->getFile(),
                    'line'  => $exception->getLine(),
                    'siren' => $siren
                ]);
            }
        }
    }
}
