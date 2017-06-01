<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\WsExternalResource;
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
    /** @var ResourceManager */
    private $resourceManager;
    /** @var bool */
    private $useCache = true;

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
            $this->callHistoryManager->sendMonitoringAlert($wsResource, 'up');

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
        }
        $this->callHistoryManager->sendMonitoringAlert($wsResource, 'down', $exception->getMessage());

        return null;
    }

    /**
     * @param ResponseInterface  $response
     * @param WsExternalResource $resource
     * @param array              $logContext
     *
     * @return array
     *
     * @throws \Exception
     */
    private function isValidResponse(ResponseInterface $response, WsExternalResource $resource, $logContext = [])
    {
        $stream = $response->getBody();
        $stream->rewind();
        $content = $stream->getContents();

        if (200 === $response->getStatusCode()) {
            $contentValidity = $this->isValidContent($content, $resource);

            if (false === $contentValidity && false === empty($logContext)) {
                $this->logger->warning('Call to ' . $resource->getResourceName() . ' Response code: ' . $response->getStatusCode() . '. Response content: ' . $content, $logContext);
            }

            return ['status' => $contentValidity ? 'valid' : 'warning', 'is_valid' => $contentValidity];
        } else {
            $level = 'error';

            if (401 === $response->getStatusCode()) {
                throw new \Exception($content, 401);
            } elseif (404 === $response->getStatusCode()) {
                $level = 'warning';
            }
            if (false === empty($logContext)) {
                $this->logger->{$level}('Call to ' . $resource->getResourceName() . ' Response code: ' . $response->getStatusCode() . '. Response content: ' . $content, $logContext);
            }

            return ['status' => $level, 'is_valid' => false];
        }
    }

    /**
     * @param                    $content
     * @param WsExternalResource $resource
     *
     * @return bool
     */
    private function isValidContent($content, WsExternalResource $resource)
    {
        if ($response = json_decode($content)) {
            switch ($resource->getLabel()) {
                case self::RESOURCE_TRAFFIC_LIGHT:
                    return isset($response->Color);
                case self::RESOURCE_SEARCH_COMPANY:
                    return isset($response->Id);
                case self::RESOURCE_EULER_GRADE:
                    return isset($response->message, $response->code);
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
        if ($this->useCache
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
}
