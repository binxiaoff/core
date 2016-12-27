<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class EulerHermesManager
{
    /** @var  Client */
    private $client;
    /** @var  string */
    private $coverageApiKey;
    /** @var  string */
    private $gradingApiKey;
    /** @var string */
    private $accountKey;
    /** @var  \Psr\Log\LoggerInterface */
    private $logger;
    /** @var CallHistoryManager */
    private $callHistoryManager;

    public function __construct(ClientInterface $client, $coverageApiKey, $gradingApiKey, $accountApiKey, LoggerInterface $logger, CallHistoryManager $callHistoryManager)
    {
        $this->client             = $client;
        $this->coverageApiKey     = $coverageApiKey;
        $this->gradingApiKey      = $gradingApiKey;
        $this->callHistoryManager = $callHistoryManager;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $siren
     * @param string $countryCode
     * @return ResponseInterface
     */
    public function searchCompany($siren, $countryCode)
    {
        if (true === empty($siren)) {
            throw new \InvalidArgumentException('Country code parameter is missing');
        }

        if (true === empty($countryCode)) {
            throw new \InvalidArgumentException('SIREN parameter is missing');
        }

        return $this->client->get(
            'transactor/' . $countryCode . '/siren/' . $siren,
            [
                'headers'  => ['apikey' => $this->coverageApiKey],
                'on_stats' => $this->callHistoryManager->addResourceCallHistoryLog('euler', __FUNCTION__, 'GET')
            ]
        );
    }

    /**
     * @param string $siren
     * @param string $countryCode
     * @return null|ResponseInterface
     */
    public function getTrafficLight($siren, $countryCode)
    {
        /** @var ResponseInterface $company */
        $companyResponse = $this->searchCompany($siren, $countryCode);

        if (200 === $companyResponse->getStatusCode()) {
            $data = json_decode($companyResponse->getBody()->getContents(), true);
            unset($companyResponse);
            $trafficLightResponse = $this->client->get(
                'trafficLight/' . $data['Id'],
                [
                    'headers' => ['apikey' => $this->gradingApiKey],
                    'on_stats' => $this->callHistoryManager->addResourceCallHistoryLog('euler', __FUNCTION__, 'GET')
                ]
            );

            return $trafficLightResponse;
        }

        return null;
    }

    /**
     * @param string $siren
     * @param string $countryCode
     * @return null|ResponseInterface
     */
    public function getGrade($siren, $countryCode)
    {
        /** @var ResponseInterface $company */
        $companyResponse = $this->searchCompany($siren, $countryCode);

        if (200 === $companyResponse->getStatusCode()) {
            $data = json_decode($companyResponse->getBody()->getContents(), true);
            unset($companyResponse);
            $grade = $this->client->get(
                'transactor/grade/' . $data['Id'],
                [
                    'headers'   => ['apikey' => $this->gradingApiKey],
                    'on_stats' => $this->callHistoryManager->addResourceCallHistoryLog('euler', __FUNCTION__, 'GET')
                ]
            );

            return $grade;
        }

        return null;
    }

    /**
     * @return ResponseInterface
     */
    public function account()
    {
        $response = $this->client
            ->post('Account/Login',
                [
                    'headers' => ['apikey' => $this->accountKey],
                    'json'    => ['email' => 'equipeit@unilend.fr', 'password' => ')XZpX~x4]"Lr6)BR']
                ]);

        return $response;
    }
}
