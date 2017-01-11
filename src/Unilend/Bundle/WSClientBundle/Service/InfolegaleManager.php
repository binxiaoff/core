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
    /** @var  Client */
    private $client;
    /** @var  string */
    private $token;
    /** @var LoggerInterface */
    private $logger;
    /** @var  CallHistoryManager */
    private $callHistoryManager;
    /** @var  Serializer */
    private $serializer;

    /**
     * InfolegaleManager constructor.
     * @param ClientInterface $client
     * @param $token
     * @param LoggerInterface $logger
     * @param CallHistoryManager $callHistoryManager
     * @param Serializer $serializer
     */
    public function __construct(ClientInterface $client, $token, LoggerInterface $logger, CallHistoryManager $callHistoryManager, Serializer $serializer)
    {
        $this->client             = $client;
        $this->token              = $token;
        $this->logger             = $logger;
        $this->callHistoryManager = $callHistoryManager;
        $this->serializer         = $serializer;
    }

    /**
     * @param $siren
     * @return null|ScoreDetails
     */
    public function getScore($siren)
    {
        if (null !== $result = $this->sendRequest($siren, 'getScore', 'get')) {
            return $this->serializer->deserialize($result->scoreInfo[0]->asXML(), ScoreDetails::class, 'xml');
        }

        return null;
    }

    /**
     * @param $siren
     * @return null|\SimpleXMLElement
     */
    public function searchCompany($siren)
    {
        return $this->sendRequest($siren, 'searchCompany', 'get');
    }

    /**
     * @param $siren
     * @return null|\SimpleXMLElement
     */
    public function getIdentity($siren)
    {
        return $this->sendRequest($siren, 'getIdentity', 'get');
    }

    /**
     * @param $siren
     * @return null|\SimpleXMLElement
     */
    public function getListAnnonceLegale($siren)
    {
        return $this->sendRequest($siren, 'getListAnnonceLegale', 'get');
    }

    /**
     * @param string $siren
     * @param string $endpoint
     * @param string $method
     * @return null|\SimpleXMLElement
     */
    private function sendRequest($siren, $endpoint, $method = 'get')
    {
        if (true === empty($siren)) {
            throw new \InvalidArgumentException('Siren is missing');
        }

        $query['token'] = $this->token;
        $query['siren'] = $siren;

        /** @var ResponseInterface $response */
        $response   = $this->client->$method(
            $endpoint,
            [
                'query'    => $query,
                'on_stats' => $this->callHistoryManager->addResourceCallHistoryLog('infolegale', $endpoint, strtoupper($method), $siren)
            ]
        );
        $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'SIREN' => $siren];
        $content    = $response->getBody()->getContents();

        if (200 === $response->getStatusCode()) {
            $xml = new \SimpleXMLElement($content);
            $this->logger->info('Call to ' . $endpoint . '. Result: ' . $content, $logContext);

            return $xml->content[0];
        }
        $this->logger->error('Call to ' . $endpoint . '. Result: ' . $content, $logContext);

        return null;
    }
}
