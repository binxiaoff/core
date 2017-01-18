<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Unilend\Bundle\WSClientBundle\Entity\Euler\CompanyIdentity;
use Unilend\Bundle\WSClientBundle\Entity\Euler\CompanyRating;

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
    /** @var  LoggerInterface */
    private $logger;
    /** @var CallHistoryManager */
    private $callHistoryManager;
    /** @var  Serializer */
    private $serializer;
    /** @var  string */
    private $accountPwd;
    /** @var  string */
    private $accountEmail;
    /** @var bool */
    private $monitoring;

    /**
     * EulerHermesManager constructor.
     * @param Client $client
     * @param string $coverageApiKey
     * @param string $gradingApiKey
     * @param string $accountApiKey
     * @param string $accountPwd
     * @param string $accountEmail
     * @param LoggerInterface $logger
     * @param CallHistoryManager $callHistoryManager
     * @param Serializer $serializer
     */
    public function __construct(Client $client, $coverageApiKey, $gradingApiKey, $accountApiKey, $accountPwd, $accountEmail, LoggerInterface $logger, CallHistoryManager $callHistoryManager, Serializer $serializer)
    {
        $this->client             = $client;
        $this->coverageApiKey     = $coverageApiKey;
        $this->gradingApiKey      = $gradingApiKey;
        $this->callHistoryManager = $callHistoryManager;
        $this->serializer         = $serializer;
        $this->logger             = $logger;
        $this->accountKey         = $accountApiKey;
        $this->accountPwd         = $accountPwd;
        $this->accountEmail       = $accountEmail;
    }

    /**
     * @param boolean $activate
     */
    public function setMonitoring($activate)
    {
        $this->monitoring = $activate;
    }

    /**
     * @param string $siren
     * @param string $countryCode
     * @return CompanyIdentity
     * @throws \Exception
     */
    public function searchCompany($siren, $countryCode)
    {
        if (true === empty($siren)) {
            throw new \InvalidArgumentException('SIREN parameter is missing');
        }

        if (true === empty($countryCode)) {
            throw new \InvalidArgumentException('Country code parameter is missing');
        }

        if (null !== $result = $this->sendRequest('transactor/' . strtolower($countryCode) . '/siren/' . $siren, $this->coverageApiKey, $siren, __FUNCTION__)) {
            return $this->serializer->deserialize($result, CompanyIdentity::class, 'json');
        }

        return null;
    }

    /**
     * @param string $siren
     * @param string $countryCode
     * @return null|CompanyRating
     * @throws \Exception
     */
    public function getTrafficLight($siren, $countryCode)
    {
        /** @var CompanyIdentity $company */
        $company = $this->searchCompany($siren, $countryCode);

        if (null !== $company) {
            if (null !== $result = $this->sendRequest('trafficLight/' . $company->getSingleInvoiceId(), $this->gradingApiKey, $siren, __FUNCTION__)) {
                return $this->serializer->deserialize($result, CompanyRating::class, 'json');
            }
        }

        return null;
    }

    /**
     * @param string $siren
     * @param string $countryCode
     * @return null|CompanyRating
     * @throws \Exception
     */
    public function getGrade($siren, $countryCode)
    {
        /** @var CompanyIdentity $company */
        $company = $this->searchCompany($siren, $countryCode);

        if (null !== $company) {
            if (null !== $result = $this->sendRequest('transactor/grade/' . $company->getSingleInvoiceId(), $this->gradingApiKey, $siren, __FUNCTION__)) {
                return $this->serializer->deserialize($result, CompanyRating::class, 'json');
            }
        }

        return null;
    }

    /**
     * @param string $uri
     * @param string $apiKey
     * @param string $siren
     * @param string $caller
     * @return null|string
     */
    private function sendRequest($uri, $apiKey, $siren, $caller)
    {
        $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'SIREN' => $siren];
        $result     = null;
        try {
            $response = $this->client->get(
                $uri,
                [
                    'headers'  => ['apikey' => $apiKey],
                    'on_stats' => $this->callHistoryManager->addResourceCallHistoryLog('euler', $caller, 'GET', $siren)
                ]
            );

            if (200 === $response->getStatusCode()) {
                $result    = $response->getBody()->getContents();
                $alertType = 'up';
                $this->logger->info('Call to ' . $uri . '. Result: ' . $result, $logContext);
            } else {
                $alertType = 'down';
                $this->logger->error('Call to ' . $uri . '. Result: ' . $response->getBody()->getContents(), $logContext);
            }
        } catch (\Exception $exception) {
            $alertType = 'down';
            $this->logger->error('Call to ' . $uri . '. Error message: ' . $exception->getMessage(), $logContext);
        }

        if ($this->monitoring) {
            $this->callHistoryManager->sendMonitoringAlert('Euler', 'Euler status', $alertType);
        }

        return $result;
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
                    'json'    => ['email' => $this->accountEmail, 'password' => $this->accountPwd]
                ]);

        return $response;
    }
}
