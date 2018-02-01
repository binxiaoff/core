<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\RiskDataMonitoring;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoring;

class MonitoringManger
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $siren
     * @param string $provider
     * @param bool   $isOngoing
     *
     * @return array
     */
    public function getMonitoredCompanies(string $siren, string $provider, bool $isOngoing = true) : array
    {
        $monitoredCompanies = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->getMonitoredCompaniesBySiren($siren, $provider, $isOngoing);

        return $monitoredCompanies;
    }

    /**
     * @param string $siren
     * @param string $provider
     *
     * @return null|RiskDataMonitoring
     */
    public function getMonitoringForSiren(string $siren, string $provider) : ?RiskDataMonitoring
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
     * @param Projects $project
     *
     * @return bool
     */
    public function projectHasMonitoringEvents(Projects $project) : bool
    {
        $callLogs = $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoringCallLog')->findCallLogsForSirenAfterDate($project->getIdCompany()->getSiren(), $project->getAdded());

        return count($callLogs) > 0;
    }

    /**
     * @return \DateTime
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLastMonitoringEventDate(string $provider) : \DateTime
    {
        $lastEvent = $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoringCallLog')->findLastCallLogForProvider($provider);
        if (null === $lastEvent) {
            return new \DateTime('2013-01-01');
        }

        return $lastEvent->getAdded();
    }
}
