<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use JMS\Serializer\Serializer;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Unilend\Bundle\WSClientBundle\Entity\Infolegale\ScoreDetails;

class InfolegaleManager
{
    const RESOURCE_COMPANY_SCORE    = 'get_score_infolegale';
    const RESOURCE_SEARCH_COMPANY   = 'search_company_infolegale';
    const RESOURCE_COMPANY_IDENTITY = 'get_identity_infolegale';
    const RESOURCE_LEGAL_NOTICE     = 'get_legal_notice_infolegale';

    /** @var Client */
    private $client;
    /** @var string */
    private $token;
    /** @var LoggerInterface */
    private $logger;
    /** @var CallHistoryManager */
    private $callHistoryManager;
    /** @var Serializer */
    private $serializer;
    /** @var boolean */
    private $monitoring;
    /** @var ResourceManager */
    private $resourceManager;

    /**
     * @param ClientInterface    $client
     * @param string             $token
     * @param LoggerInterface    $logger
     * @param CallHistoryManager $callHistoryManager
     * @param Serializer         $serializer
     * @param ResourceManager    $resourceManager
     */
    public function __construct(ClientInterface $client, $token, LoggerInterface $logger, CallHistoryManager $callHistoryManager, Serializer $serializer, ResourceManager $resourceManager)
    {
        $this->client             = $client;
        $this->token              = $token;
        $this->logger             = $logger;
        $this->callHistoryManager = $callHistoryManager;
        $this->serializer         = $serializer;
        $this->resourceManager    = $resourceManager;
    }

    public function setMonitoring($validate)
    {
        $this->monitoring = $validate;
    }

    /**
     * @param string $siren
     * @return null|ScoreDetails
     */
    public function getScore($siren)
    {
        if (null !== ($result = $this->sendRequest(self::RESOURCE_COMPANY_SCORE, $siren))) {
            return $this->serializer->deserialize($result->scoreInfo[0]->asXML(), ScoreDetails::class, 'xml');
        }

        return null;
    }

    /**
     * @param string $siren
     * @return null|\SimpleXMLElement
     */
    public function searchCompany($siren)
    {
        return $this->sendRequest(self::RESOURCE_SEARCH_COMPANY, $siren);
    }

    /**
     * @param string $siren
     * @return null|\SimpleXMLElement
     */
    public function getIdentity($siren)
    {
        return $this->sendRequest(self::RESOURCE_COMPANY_IDENTITY, $siren);
    }

    /**
     * @param string $siren
     * @return null|\SimpleXMLElement
     */
    public function getListAnnonceLegale($siren)
    {
        return $this->sendRequest(self::RESOURCE_LEGAL_NOTICE, $siren);
    }

    /**
     * @param string $resourceLabel
     * @param string $siren
     * @param string $method
     * @return null|\SimpleXMLElement
     */
    private function sendRequest($resourceLabel, $siren, $method = 'get')
    {
        if (true === empty($siren)) {
            throw new \InvalidArgumentException('SIREN is missing');
        }

        $query['token'] = $this->token;
        $query['siren'] = $siren;
        $logContext     = ['class' => __CLASS__, 'function' => __FUNCTION__, 'SIREN' => $siren];
        $result         = null;
        $alertType      = 'down';
        $wsResource     = $this->resourceManager->getResource($resourceLabel);

        try {
            if (false === ($content = $this->callHistoryManager->getStoredResponse($wsResource, $siren))) {
                /** @var ResponseInterface $response */
                $response = $this->client->$method($wsResource->resource_name, [
                    'query'    => $query,
                    'on_stats' => $this->callHistoryManager->addResourceCallHistoryLog($wsResource, $siren)
                ]);

                if (200 === $response->getStatusCode()) {
                    $alertType = 'up';
                    $content   = $response->getBody()->getContents();
                } else {
                    $this->logger->error('Call to ' . $wsResource->resource_name . ' using params: ' . json_encode($query) . '. Response status code: ' . $response->getStatusCode(), $logContext);
                }
            } else {
                $this->setMonitoring(false);
            }

            if (false === empty($content)) {
                $xml    = new \SimpleXMLElement($content);
                $result = $xml->content[0];
            }
        } catch (\Exception $exception) {
            $alertType = 'down';
            $message   = 'Call to ' . $wsResource->resource_name . ' using params: ' . json_encode($query) . '. Error message: ' . $exception->getMessage();
            if (isset($content)) {
                $message .= $content;
            }
            $this->logger->error($message, $logContext);
            $this->setMonitoring(true);
        }

        if ($this->monitoring) {
            $this->callHistoryManager->sendMonitoringAlert($wsResource, $alertType);
        }

        return $result;
    }
}
