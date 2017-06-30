<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use JMS\Serializer\Serializer;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\WsExternalResource;
use Unilend\Bundle\WSClientBundle\Entity\Euler\CompanyIdentity;
use Unilend\Bundle\WSClientBundle\Entity\Euler\CompanyRating;
use Unilend\librairies\CacheKeys;

class EulerHermesManager
{
    const RESOURCE_SEARCH_COMPANY         = 'search_company_euler';
    const RESOURCE_EULER_GRADE            = 'get_grade_euler';
    const RESOURCE_TRAFFIC_LIGHT          = 'get_traffic_light_euler';
    const RESOURCE_EULER_GRADE_MONITORING = 'start_euler_grade_monitoring';

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
    /** @var ResourceManager */
    private $resourceManager;
    /** @var bool */
    private $useCache = true;
    /** @var CacheItemPoolInterface */
    private $cachePool;

    /**
     * @param Client                 $client
     * @param string                 $gradingApiKey
     * @param string                 $accountApiKey
     * @param string                 $accountPassword
     * @param string                 $accountEmail
     * @param LoggerInterface        $logger
     * @param CallHistoryManager     $callHistoryManager
     * @param Serializer             $serializer
     * @param ResourceManager        $resourceManager
     * @param CacheItemPoolInterface $cachePool;
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
        ResourceManager $resourceManager,
        CacheItemPoolInterface $cachePool
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
        $this->cachePool          = $cachePool;
    }

    /**
     * @param bool $useCache
     *
     * @return EulerHermesManager
     */
    public function setUseCache($useCache)
    {
        $this->useCache = $useCache;

        return $this;
    }

    /**
     * @param string $siren
     * @param string $countryCode
     *
     * @return CompanyIdentity
     *
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
     *
     * @return null|CompanyRating
     *
     * @throws \Exception
     */
    public function getTrafficLight($siren, $countryCode)
    {
        /** @var CompanyIdentity $company */
        $company = $this->searchCompany($siren, $countryCode);

        if (null !== $company && null !== $company->getSingleInvoiceId()) {
            if (null !== $result = $this->sendRequest(self::RESOURCE_TRAFFIC_LIGHT, $company->getSingleInvoiceId(), $this->gradingApiKey, $siren)) {
                return $this->serializer->deserialize($result, CompanyRating::class, 'json');
            }
        }

        return null;
    }

    /**
     * @param string $siren
     * @param string $countryCode
     *
     * @return null|CompanyRating
     *
     * @throws \Exception
     */
    public function getGrade($siren, $countryCode)
    {
        /** @var CompanyIdentity $company */
        $company = $this->searchCompany($siren, $countryCode);

        if (null !== $company && null !== $company->getSingleInvoiceId()) {
            if (null !== $result = $this->sendRequest(self::RESOURCE_EULER_GRADE, $company->getSingleInvoiceId(), $this->getMonitoringApiKey(), $siren)) {
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
     *
     * @return null|string
     */
    private function sendRequest($resourceLabel, $uri, $apiKey, $siren)
    {
        $wsResource = $this->resourceManager->getResource($resourceLabel);
        $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren];

        try {
            if ($storedResponse = $this->getStoredResponse($wsResource, $siren)) {
                if ($this->isValidContent($storedResponse, $wsResource)) {
                    return $storedResponse;
                }
            }
            $callable = $this->callHistoryManager->addResourceCallHistoryLog($wsResource, $siren, $this->useCache);
            $response = $this->client->get(
                $wsResource->getResourceName() . $uri,
                ['headers' => ['apikey' => $apiKey]]
            );

            $validity = $this->isValidResponse($response, $wsResource, $logContext);
            $stream   = $response->getBody();
            $stream->rewind();
            $content = $stream->getContents();
            call_user_func($callable, $content, $validity['status']);

            if ($validity['is_valid']) {
                return $content;
            } else {
                return null;
            }
        } catch (\Exception $exception) {
            if (isset($callable)) {
                call_user_func($callable, isset($content) ? $content : '', 'error');
            }
            $this->logger->error(
                'Exception at line: ' . __LINE__ . '. Message: ' . $exception->getMessage(),
                ['class' => __CLASS__, 'resource' => $wsResource->getLabel(), 'uri' => $uri]
            );

            return null;
        }
    }

    /**
     * @param ResponseInterface  $response
     * @param WsExternalResource $resource
     * @param array              $logContext
     *
     * @return array
     */
    private function isValidResponse(ResponseInterface $response, WsExternalResource $resource, array $logContext)
    {
        $stream = $response->getBody();
        $stream->rewind();
        $content = $stream->getContents();

        if (200 === $response->getStatusCode()) {
            $contentValidity = $this->isValidContent($content, $resource);

            if (false === $contentValidity) {
                $this->logger->warning('Call to ' . $resource->getResourceName() . ' Response code: ' . $response->getStatusCode() . '. Response content: ' . $content, $logContext);
            }

            return [
                'status'   => $contentValidity ? 'valid' : 'warning',
                'is_valid' => $contentValidity
            ];
        } else {
            $level = 'error';

            if (404 === $response->getStatusCode()) {
                $level = 'warning';
            }
            $this->logger->{$level}('Call to ' . $resource->getResourceName() . ' Response code: ' . $response->getStatusCode() . '. Response content: ' . $content, $logContext);

            return ['status' => $level, 'is_valid' => false];
        }
    }

    /**
     * @param string             $content
     * @param WsExternalResource $resource
     *
     * @return bool
     */
    private function isValidContent($content, WsExternalResource $resource)
    {
        if ($response = json_decode($content)) {
            switch ($resource->getLabel()) {
                case self::RESOURCE_TRAFFIC_LIGHT:
                    return isset($response->Color) && is_string($response->Color) && false === empty($response->Color);
                case self::RESOURCE_SEARCH_COMPANY:
                    return isset($response->Id) && false === empty($response->Id);
                case self::RESOURCE_EULER_GRADE:
                    return isset($response->message) && in_array($response->message, array_merge(range(1, 10), ['NA']));
                default:
                    return false;
            }
        }
        return false;
    }

    /**
     * @param WsExternalResource $resource
     * @param                    $siren
     *
     * @return bool|mixed
     */
    private function getStoredResponse(WsExternalResource $resource, $siren)
    {
        if (
            $this->useCache
            && false !== ($storedResponse = $this->callHistoryManager->getStoredResponse($resource, $siren))
            && json_decode($storedResponse)
        ) {
            return $storedResponse;
        }

        return false;
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

    /**
     * @param string $siren
     * @param string $countryCode
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function startLongTermMonitoring($siren, $countryCode)
    {
        /** @var CompanyIdentity $company */
        $company = $this->searchCompany($siren, $countryCode);

        if (null !== $company && null !== $company->getSingleInvoiceId()) {
            $wsResource = $this->resourceManager->getResource(self::RESOURCE_EULER_GRADE_MONITORING);
            $response = $this->client->post($wsResource->getResourceName() . $company->getSingleInvoiceId(), ['headers' => ['apikey' => $this->getMonitoringApiKey()]]);
            if (200 === $response->getStatusCode()) {
                $this->logger->info('Euler grade long term monitoring has been activated for siren ' . $siren);

                return true;
            } else {
                $this->logger->warning('Long term monitroing could not activated for siren ' . $siren . ' Status Code : ' . $response->getStatusCode() . ' /n Reason : ' . $response->getReasonPhrase() . ' /n Content : ' . $response->getBody()->getContents());
            }
        }

        return false;
    }

    public function getMonitoringApiKey()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::EULER_HERMES_MONITORING_API_KEY);

        if (false === $cachedItem->isHit()) {
            $response         = $this->client->post('account/key', ['headers' => ['apikey' => $this->accountKey], 'json' => ['package' => 'Monitoring']]);
            $content          = json_decode($response->getBody()->getContents(), true);
            $monitoringApiKey = $content['packages']['Monitoring'][0];

            $cachedItem->set($monitoringApiKey)->expiresAfter(CacheKeys::DAY * 30);
            $this->cachePool->save($cachedItem);

            return $monitoringApiKey;
        } else {
            return $cachedItem->get();
        }
    }
}
