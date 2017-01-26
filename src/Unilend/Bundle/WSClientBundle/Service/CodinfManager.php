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

    /** @var  Client */
    private $client;
    /** @var string */
    private $user;
    /** @var string */
    private $password;
    /** @var  LoggerInterface */
    private $logger;
    /** @var CallHistoryManager */
    private $callHistoryManager;
    /** @var Serializer */
    private $serializer;
    /** @var  bool */
    private $monitoring;
    /** @var ResourceManager */
    private $resourceManager;

    /**
     * CodinfManager constructor.
     *
     * @param ClientInterface $client
     * @param $user
     * @param $password
     * @param $baseUrl
     * @param LoggerInterface $logger
     * @param CallHistoryManager $callHistoryManager
     * @param Serializer $serializer
     * @param ResourceManager $resourceManager;
     */
    public function __construct(ClientInterface $client, $user, $password, $baseUrl, LoggerInterface $logger, CallHistoryManager $callHistoryManager, Serializer $serializer, ResourceManager $resourceManager)
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
     * @param boolean $activate
     */
    public function setMonitoring($activate)
    {
        $this->monitoring = $activate;
    }

    /**
     * @param $siren
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @param bool $includeRegularized
     * @return null|IncidentList
     */
    public function getIncidentList($siren, \DateTime $startDate = null, \DateTime $endDate = null, $includeRegularized = false)
    {
        if (true === empty($siren)) {
            throw new \InvalidArgumentException('siren is missing');
        }

        $query['siren']      = $siren;
        $query['allcomites'] = 1;
        $query['usr']        = $this->user;
        $query['pswd']       = $this->password;

        if (null !== $startDate && $startDate instanceof \DateTime) {
            $query['dd'] = $startDate->format('Y-m-d');
        }

        if (null !== $endDate && $endDate instanceof \DateTime) {
            $query['df'] = $endDate->format('Y-m-d');
        }

        if (false !== $includeRegularized) {
            $query['inc_reg'] = 1;
        }
        $data       = null;
        $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren];

        try {
            $incidents = $this->sendRequest(self::RESOURCE_INCIDENT_LIST, $query, $siren);

            if (null !== $incidents = preg_replace('/(<\?xml .*\?>).*/', '', $incidents)) {
                try {
                    $data = $this->serializer->deserialize('<incidentList>' . $incidents . '</incidentList>', IncidentList::class, 'xml');
                } catch (\Exception $exception) {
                    $this->logger->error('Could not deserialize response: ' . $exception->getMessage() . ' siren: ' . $siren, $logContext);
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error('Call to get_list_v2 using params: ' . json_encode($query) . ' Error message: ' . $exception->getMessage(), $logContext);
        }

        return $data;
    }

    /**
     * @param string $resourceLabel
     * @param array $query
     * @param string $siren
     * @return null|string
     */
    private function sendRequest($resourceLabel, array $query, $siren)
    {
        $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren];
        $content    = null;
        $wsResource = $this->resourceManager->getResource($resourceLabel);

        try {
            $response = $this->client->get(
                $wsResource->resource_name,
                [
                    'query'    => $query,
                    'on_stats' => $this->callHistoryManager->addResourceCallHistoryLog($wsResource, $siren)
                ]
            );
            $content  = $response->getBody()->getContents();

            if (200 === $response->getStatusCode()) {
                $alertType = 'up';
                $this->logger->info('Call to ' . $wsResource->resource_name . ' using params: ' . json_encode($query) . '. Response: ' . $content, $logContext);
            } else {
                $alertType = 'down';
                $this->logger->error('Call to ' . $wsResource->resource_name . ' using params: ' . json_encode($query) . '. Response: ' . $content, $logContext);
            }
        } catch (\Exception $exception) {
            $alertType = 'down';
            $this->logger->error('Call to ' . $wsResource->resource_name . 'using params: ' . json_encode($query) . '. Error message: ' . $exception->getMessage(), $logContext);
        }

        if ($this->monitoring) {
            $this->callHistoryManager->sendMonitoringAlert($wsResource, $alertType);
        }

        return $content;
    }
}
