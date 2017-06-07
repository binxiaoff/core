<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use JMS\Serializer\Serializer;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\WsExternalResource;
use Unilend\Bundle\WSClientBundle\Entity\Codinf\IncidentList;

class CodinfManager
{
    const RESOURCE_INCIDENT_LIST    = 'get_incident_list_codinf';
    const RESPONSE_MATCHING_PATTERN = '/^<\?xml .*\?>(.*)$/s';

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
                    return $this->serializer->deserialize('<incidentList>' . $matches[1] . '</incidentList>', IncidentList::class, 'xml');
                } catch (\Exception $exception) {
                    $this->logger->error('Could not deserialize response: ' . $exception->getMessage() . ' SIREN: ' . $siren, $logContext);
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error('Call to get_list_v2 using params: ' . json_encode($query) . ' Error message: ' . $exception->getMessage(), $logContext);
        }

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
        $logContext = ['class' => __CLASS__, 'resource' => $wsResource->getLabel(), 'siren' => $siren];

        try {
            if ($storedResponse = $this->getStoredResponse($wsResource, $siren)) {
                return $storedResponse;
            }
            $callable = $this->callHistoryManager->addResourceCallHistoryLog($wsResource, $siren, $this->useCache);
            $response = $this->client->get(
                $wsResource->getResourceName(),
                ['query' => $query]
            );
            $validity = $this->isValidResponse($response, $logContext);
            $stream   = $response->getBody();
            $stream->rewind();
            $content = $stream->getContents();
            call_user_func($callable, $content, $validity['status']);

            if ($validity['is_valid']) {
                $this->callHistoryManager->sendMonitoringAlert($this->resourceManager->getResource(self::RESOURCE_INCIDENT_LIST), 'up');

                return $content;
            }
        } catch (\Exception $exception) {
            if (isset($callable)) {
                call_user_func($callable, isset($response) ? $response : null, 'error');
            }
            $this->logger->error('Call to ' . $wsResource->getResourceName() . 'using params: ' . json_encode($query) . '. Error message: ' . $exception->getMessage(), $logContext);
        }
        $this->callHistoryManager->sendMonitoringAlert($this->resourceManager->getResource(self::RESOURCE_INCIDENT_LIST), 'down');

        return null;
    }

    /**
     * @param ResponseInterface $response
     * @param array             $logContext
     *
     * @return array
     */
    private function isValidResponse(ResponseInterface $response, $logContext)
    {
        $stream = $response->getBody();
        $stream->rewind();
        $content = $stream->getContents();

        if (200 === $response->getStatusCode()
            && 1 === preg_match(self::RESPONSE_MATCHING_PATTERN, $content)
        ) {
            return ['status' => 'valid', 'is_valid' => true];
        } elseif (200 !== $response->getStatusCode()) {
            $this->logger->error('Codinf error: (HTTP code: ' . $response->getStatusCode() . '). Response headers: ' . json_encode($response->getHeaders()) . '. Response: ' . $content, $logContext);

            return ['status' => 'error', 'is_valid' => false];
        } else {
            $this->logger->warning('Unexpected response format from Codinf. Response headers: ' . json_encode($response->getHeaders()) . '. Response: ' . $content, $logContext);

            return ['status' => 'warning', 'is_valid' => false];
        }
    }

    /**
     * @param WsExternalResource $resource
     * @param string             $siren
     *
     * @return bool|mixed
     */
    private function getStoredResponse(WsExternalResource $resource, $siren)
    {
        if ($this->useCache
            && false !== ($storedResponse = $this->callHistoryManager->getStoredResponse($resource, $siren))
            && 1 === preg_match(self::RESPONSE_MATCHING_PATTERN, $storedResponse)
        ) {
            return $storedResponse;
        }

        return false;
    }
}
