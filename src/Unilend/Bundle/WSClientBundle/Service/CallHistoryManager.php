<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use GuzzleHttp\TransferStats;
use Symfony\Component\Stopwatch\Stopwatch;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class CallHistoryManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var Stopwatch */
    private $stopwatch;

    /**
     * WSProviderCallHistoryManager constructor.
     * @param EntityManager $entityManager
     * @param string $stopwatch
     */
    public function __construct(EntityManager $entityManager, $stopwatch)
    {
        $this->entityManager = $entityManager;
        $this->stopwatch     = new $stopwatch;
    }

    /**
     * @param string $provider
     * @param string $endpoint
     * @param string $method
     * @param string $siren
     * @return \Closure
     */
    public function addResourceCallHistoryLog($provider, $endpoint, $method, $siren)
    {
        $resourceId = $this->getResourceId($provider, $endpoint, $method);
        $this->stopwatch->start($resourceId);

        return function ($stats = null) use ($resourceId, $siren) {
            $event        = $this->stopwatch->stop($resourceId);
            $transferTime = $event->getDuration() / 1000;

            if ($stats instanceof TransferStats && null != $stats) {
                $statusCode = $stats->getResponse()->getStatusCode();
            } else {
                $statusCode = $stats;
            }

            if (false === empty($resourceId)) {
                $this->createLog($resourceId, $siren, $transferTime, $statusCode);
            }
        };
    }

    /**
     * @param string $provider
     * @param string $endpoint
     * @param string $method
     * @return int
     */
    private function getResourceId($provider, $endpoint, $method)
    {
        /** @var \ws_external_resource $externalResource */
        $externalResource = $this->entityManager->getRepository('ws_external_resource');

        return $externalResource->getResource($provider, $endpoint, $method);
    }

    private function createLog($resourceId, $siren, $transferTime, $statusCode)
    {
        /** @var \ws_call_history $wsCallHistory */
        $wsCallHistory                = $this->entityManager->getRepository('ws_call_history');
        $wsCallHistory->id_resource   = $resourceId;
        $wsCallHistory->siren         = $siren;
        $wsCallHistory->transfer_time = $transferTime;
        $wsCallHistory->status_code   = $statusCode;
        $wsCallHistory->create();

    }
}
