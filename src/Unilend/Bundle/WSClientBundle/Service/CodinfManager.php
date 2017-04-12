<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Unilend\Bundle\WSClientBundle\Entity\Codinf\IncidentList;

class CodinfManager
{
    const RESOURCE_INCIDENT_LIST = 'get_incident_list_codinf';

    /** @var Client */
    private $client;
    /** @var string */
    private $user;
    /** @var string */
    private $password;
    /** @var LoggerInterface */
    private $logger;
    /** @var CallHistoryManager */
    private $callHistoryManager;
    /** @var Serializer */
    private $serializer;
    /** @var ResourceManager */
    private $resourceManager;
    /** @var bool */
    private $useCache = true;

    /**
     * @param ClientInterface    $client
     * @param string             $user
     * @param string             $password
     * @param string             $baseUrl
     * @param LoggerInterface    $logger
     * @param CallHistoryManager $callHistoryManager
     * @param Serializer         $serializer
     * @param ResourceManager    $resourceManager
     */
    public function __construct(
        ClientInterface $client,
        $user,
        $password,
        $baseUrl,
        LoggerInterface $logger,
        CallHistoryManager $callHistoryManager,
        Serializer $serializer,
        ResourceManager $resourceManager
    )
    {
        $this->client             = $client;
        $this->user               = $user;
        $this->password           = $password;
        $this->logger             = $logger;
        $this->callHistoryManager = $callHistoryManager;
        $this->serializer         = $serializer;
        $this->resourceManager    = $resourceManager;
    }

    /**
     * @param bool $useCache
     *
     * @return CodinfManager
     */
    public function setUseCache($useCache)
    {
        $this->useCache = $useCache;

        return $this;
    }

    /**
     * @param string         $siren
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @param bool           $includeRegularized
     *
     * @return null|IncidentList
     */
    public function getIncidentList($siren, \DateTime $startDate = null, \DateTime $endDate = null, $includeRegularized = false)
    {
        if (empty($siren)) {
            throw new \InvalidArgumentException('SIREN is missing');
        }

        $query = [
            'siren'      => $siren,
            'allcomites' => 1,
            'usr'        => $this->user,
            'pswd'       => $this->password
        ];

        if (null !== $startDate && $startDate instanceof \DateTime) {
            $query['dd'] = $startDate->format('Y-m-d');
        }

        if (null !== $endDate && $endDate instanceof \DateTime) {
            $query['df'] = $endDate->format('Y-m-d');
        }

        if (false !== $includeRegularized) {
            $query['inc_reg'] = 1;
        }

        $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren];

        try {
            $incidents = $this->sendRequest(self::RESOURCE_INCIDENT_LIST, $query, $siren);

            if (null !== $incidents && 1 === preg_match('/^<\?xml .*\?>(.*)$/s', $incidents, $matches)) {
                try {
                    $this->callHistoryManager->sendMonitoringAlert($this->resourceManager->getResource(self::RESOURCE_INCIDENT_LIST), 'up');
                    return $this->serializer->deserialize('<incidentList>' . $matches[1] . '</incidentList>', IncidentList::class, 'xml');
                } catch (\Exception $exception) {
                    $this->logger->error('Could not deserialize response: ' . $exception->getMessage() . ' SIREN: ' . $siren, $logContext);
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error('Call to get_list_v2 using params: ' . json_encode($query) . ' Error message: ' . $exception->getMessage(), $logContext);
        }

        $this->callHistoryManager->sendMonitoringAlert($this->resourceManager->getResource(self::RESOURCE_INCIDENT_LIST), 'down');
        return null;
    }

    /**
     * @param string $resourceLabel
     * @param array  $query
     * @param string $siren
     *
     * @return null|string
     */
    private function sendRequest($resourceLabel, array $query, $siren)
    {
        $wsResource = $this->resourceManager->getResource($resourceLabel);
        $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren];

        try {
            $content = $this->useCache ? $this->callHistoryManager->getStoredResponse($wsResource, $siren) : false;

            if (false === $content) {
                $response = $this->client->get($wsResource->resource_name, [
                    'query'    => $query,
                    'on_stats' => $this->callHistoryManager->addResourceCallHistoryLog($wsResource, $siren, $this->useCache)
                ]);

                if (200 === $response->getStatusCode()) {
                    return $response->getBody()->getContents();
                } else {
                    $this->logger->error('Call to ' . $wsResource->resource_name . ' using params: ' . json_encode($query) . '. Response: ' . $response->getBody()->getContents(), $logContext);
                }
            } else {
                return $content;
            }
        } catch (\Exception $exception) {
            $this->logger->error('Call to ' . $wsResource->resource_name . 'using params: ' . json_encode($query) . '. Error message: ' . $exception->getMessage(), $logContext);
        }

        return null;
    }
}
