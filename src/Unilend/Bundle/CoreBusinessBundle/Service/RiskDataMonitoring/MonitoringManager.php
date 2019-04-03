<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\RiskDataMonitoring;

use Doctrine\ORM\{EntityManagerInterface, NoResultException};
use Unilend\Entity\{Companies, Projects, RiskDataMonitoring, RiskDataMonitoringCallLog};

class MonitoringManager
{
    const PROVIDERS = [
        AltaresManager::PROVIDER_NAME,
        EulerHermesManager::PROVIDER_NAME
    ];

    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param string|null $siren
     * @param string      $provider
     * @param bool        $isOngoing
     *
     * @return array
     */
    public function getMonitoredCompanies(?string $siren, string $provider, bool $isOngoing = true): array
    {
        $monitoredCompanies = $this->entityManager->getRepository(Companies::class)->getMonitoredCompaniesBySiren($siren, $provider, $isOngoing);

        return $monitoredCompanies;
    }

    /**
     * @param string|null $siren
     * @param string      $provider
     *
     * @return null|RiskDataMonitoring
     */
    public function getMonitoringForSiren(?string $siren, string $provider): ?RiskDataMonitoring
    {
        return $this->entityManager->getRepository(RiskDataMonitoring::class)->findOneBy(['siren' => $siren, 'provider' => $provider, 'end' => null]);
    }

    /**
     * @param string|null $siren
     *
     * @return bool
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function hasMonitoringEvent(?string $siren): bool
    {
        $countCallLogs = 0;
        if (false === empty($siren)) {
            $countCallLogs = $this->entityManager->getRepository(RiskDataMonitoringCallLog::class)->getCountCallLogsForSiren($siren);
        }

        return $countCallLogs > 0;
    }

    /**
     * @param string|null $siren
     * @param string      $provider
     *
     * @return bool
     */
    public function isSirenMonitored(?string $siren, string $provider): bool
    {
        $monitoring = $this->getMonitoringForSiren($siren, $provider);

        if (null !== $monitoring) {
            return $monitoring->isOngoing();
        }

        return false;
    }

    /**
     * @param Projects $project
     *
     * @return bool
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function projectHasMonitoringEvents(Projects $project): bool
    {
        if (empty($project->getIdCompany()->getSiren())) {
            return false;
        }

        $countCallLogs = $this->entityManager->getRepository(RiskDataMonitoringCallLog::class)->getCountCallLogsForSirenAfterDate($project->getIdCompany()->getSiren(), $project->getAdded());

        return $countCallLogs > 0;
    }

    /**
     * @param string $provider
     *
     * @return \DateTime
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLastMonitoringEventDate(string $provider): \DateTime
    {
        $lastEvent = $this->entityManager->getRepository(RiskDataMonitoringCallLog::class)->findLastCallLogForProvider($provider);
        if (null === $lastEvent) {
            return new \DateTime('2013-01-01');
        }

        return $lastEvent->getAdded();
    }
}
