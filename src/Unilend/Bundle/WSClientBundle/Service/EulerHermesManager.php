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
    const RESOURCE_SEARCH_COMPANY = 'search_company_euler';
    const RESOURCE_EULER_GRADE    = 'get_grade_euler';
    const RESOURCE_TRAFFIC_LIGHT  = 'get_traffic_light_euler';

    /** @var Client */
    private $client;
    /** @var string */
    private $gradingApiKey;
    /** @var string */
    private $accountKey;
    /** @var LoggerInterface */
    private $logger;
    /** @var CallHistoryManager */
    private $callHistoryManager;
    /** @var Serializer */
    private $serializer;
    /** @var string */
    private $accountPassword;
    /** @var string */
    private $accountEmail;
    /** @var bool */
    private $monitoring;
    /** @var ResourceManager */
    private $resourceManager;

    /**
     * @param Client             $client
     * @param string             $gradingApiKey
     * @param string             $accountApiKey
     * @param string             $accountPassword
     * @param string             $accountEmail
     * @param LoggerInterface    $logger
     * @param CallHistoryManager $callHistoryManager
     * @param Serializer         $serializer
     * @param ResourceManager    $resourceManager
     */
    public function __construct(
        Client $client,
        $gradingApiKey,
        $accountApiKey,
        $accountPassword,
        $accountEmail,
        LoggerInterface $logger,
        CallHistoryManager $callHistoryManager,
        Serializer $serializer,
        ResourceManager $resourceManager
    )
    {
        $this->client             = $client;
        $this->gradingApiKey      = $gradingApiKey;
        $this->callHistoryManager = $callHistoryManager;
        $this->serializer         = $serializer;
        $this->logger             = $logger;
        $this->accountKey         = $accountApiKey;
        $this->accountPassword    = $accountPassword;
        $this->accountEmail       = $accountEmail;
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

        if (null !== $result = $this->sendRequest(self::RESOURCE_SEARCH_COMPANY, strtolower($countryCode) . '/siren/' . $siren, $this->gradingApiKey, $siren)) {
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
            if (null !== $result = $this->sendRequest(self::RESOURCE_TRAFFIC_LIGHT, $company->getSingleInvoiceId(), $this->gradingApiKey, $siren)) {
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
            if (null !== $result = $this->sendRequest(self::RESOURCE_EULER_GRADE, $company->getSingleInvoiceId(), $this->gradingApiKey, $siren)) {
                return $this->serializer->deserialize($result, CompanyRating::class, 'json');
            }
        }

        return null;
    }

    /**
     * @param string $resourceLabel
     * @param string $uri
     * @param string $apiKey
     * @param string $siren
     * @return null|string
     */
    private function sendRequest($resourceLabel, $uri, $apiKey, $siren)
    {
        $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'SIREN' => $siren];
        $result     = null;
        $wsResource = $this->resourceManager->getResource($resourceLabel);

        try {
            if (false === $result = $this->callHistoryManager->getStoredResponse($wsResource, $siren)) {
                $response = $this->client->get(
                    $wsResource->resource_name . $uri,
                    [
                        'headers'  => ['apikey' => $apiKey],
                        'on_stats' => $this->callHistoryManager->addResourceCallHistoryLog($wsResource, $siren)
                    ]
                );

                if (200 === $response->getStatusCode()) {
                    $result    = $response->getBody()->getContents();
                    $alertType = 'up';
                    $this->logger->info('Call to ' . $wsResource->resource_name . '. Result: ' . $result, $logContext);
                } else {
                    $alertType = 'down';
                    $this->logger->error('Call to ' . $wsResource->resource_name . '. Result: ' . $response->getBody()->getContents(), $logContext);
                }
            } else {
                $this->setMonitoring(false);
            }
        } catch (\Exception $exception) {
            $alertType = 'down';
            $this->logger->error('Call to ' . $wsResource->resource_name . '. Error message: ' . $exception->getMessage(), $logContext);
        }

        if ($this->monitoring) {
            $this->callHistoryManager->sendMonitoringAlert($wsResource, $alertType);
        }

        return $result;
    }

    /**
     * @return ResponseInterface
     */
    public function account()
    {
        return $this->client->post('Account/Login', [
            'headers' => ['apikey' => $this->accountKey],
            'json'    => ['email' => $this->accountEmail, 'password' => $this->accountPassword]
        ]);
    }
}
