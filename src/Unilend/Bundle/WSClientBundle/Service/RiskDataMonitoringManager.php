<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use Doctrine\ORM\EntityManager;

class RiskDataMonitoringManager
{
    /** @var EntityManager */
    private $entityManager;

    /** @var EulerHermesManager */
    private $eulerHermesManager;

    public function __construct(EntityManager $entityManager, EulerHermesManager $eulerHermesManager)
    {
        $this->entityManager      = $entityManager;
        $this->eulerHermesManager = $eulerHermesManager;
    }

    /**
     * @param $companyRating
     * @param $siren
     *
     * @return null|\Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoring
     * @throws \Exception
     */
    public function stopMonitoringPeriod($companyRating, $siren)
    {
        $monitoring = $this->getMonitoring($companyRating, $siren);

        if (false === $monitoring->isOngoing()) {
            throw new \Exception('Monitoring of siren ' . $siren . ' has already ended on ' . $monitoring->getEnd()->format('Y-m-d'));
        }

        $monitoring->setEnd(new \DateTime('NOW'));

        $this->entityManager->flush($monitoring);

        return $monitoring;
    }

    /**
     * @param string $companyRating
     * @param string $siren
     *
     * @return null|\Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoring
     */
    public function getMonitoring($companyRating, $siren)
    {
        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoring')->findOneBy(['companyRating' => $companyRating, 'siren' => $siren]);
    }



}
