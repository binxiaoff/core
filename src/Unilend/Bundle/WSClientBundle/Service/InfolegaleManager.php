<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

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

    /**
     * InfolegaleManager constructor.
     * @param ClientInterface $client
     * @param $token
     * @param LoggerInterface $logger
     * @param CallHistoryManager $callHistoryManager
     */
    public function __construct(ClientInterface $client, $token, LoggerInterface $logger, CallHistoryManager $callHistoryManager)
    {
        $this->client             = $client;
        $this->token              = $token;
        $this->logger             = $logger;
        $this->callHistoryManager = $callHistoryManager;
    }

    /**
     * @param $siren
     * @return ResponseInterface
     */
    public function getScore($siren)
    {
        return $this->sendRequest($siren, 'getScore', 'get');
    }

    /**
     * @param $siren
     * @return ResponseInterface
     */
    public function searchCompany($siren)
    {
        return $this->sendRequest($siren, 'searchCompany', 'get');
    }

    /**
     * @param $siren
     * @return ResponseInterface
     */
    public function getIdentity($siren)
    {
        return $this->sendRequest($siren, 'getIdentity', 'get');
    }

    /**
     * @param $siren
     * @return ResponseInterface
     */
    public function getListAnnonceLegale($siren)
    {
        return $this->sendRequest($siren, 'getListAnnonceLegale', 'get');
    }

    /**
     * @param string $siren
     * @param string $endpoint
     * @param string $method
     * @return ResponseInterface
     */
    private function sendRequest($siren, $endpoint, $method = 'get')
    {
        if (true === empty($siren)) {
            throw new \InvalidArgumentException('Siren is missing');
        }

        $query['token'] = $this->token;
        $query['siren'] = $siren;

        return $this->client->$method(
            $endpoint,
            [
                'query'    => $query,
                'on_stats' => $this->callHistoryManager->addResourceCallHistoryLog('infolegale', $endpoint, strtoupper($method))
            ]
        );
    }
}
