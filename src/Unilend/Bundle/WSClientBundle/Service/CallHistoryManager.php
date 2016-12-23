<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use GuzzleHttp\TransferStats;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class CallHistoryManager
{
    /** @var EntityManager */
    private $entityManager;

    /**
     * WSProviderCallHistoryManager constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $provider
     * @param string $endpoint
     * @param string $method
     * @return \Closure
     */
    public function addResourceCallHistoryLog($provider, $endpoint, $method)
    {
        $resourceId = $this->getResourceId($provider, $endpoint, $method);

        return function (TransferStats $stats) use($resourceId) {

            if (false === empty($resourceId)) {
                /** @var \ws_call_history $wsCallHistory */
                $wsCallHistory = $this->entityManager->getRepository('ws_call_history');
                $wsCallHistory->id_resource = $resourceId;
                $wsCallHistory->request_uri = $stats->getEffectiveUri()->getPath();
                $wsCallHistory->transfer_time = $stats->getTransferTime();
                $wsCallHistory->status_code = $stats->getResponse()->getStatusCode();
                $wsCallHistory->create();
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
}
