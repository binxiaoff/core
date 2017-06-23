<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\ClientInterface;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Unilend\Bundle\CoreBusinessBundle\Entity\WsExternalResource;
use Unilend\Bundle\WSClientBundle\Entity\Ellisphere\Report;

class EllisphereManager
{
    const WEBSERVICE_VERSION     = '2.1';
    const WEBSERVICE_LANG        = 'FR';
    const APP_ID                 = 'WSOM';
    const APP_VERSION            = 1;
    const ORDER_PRODUCT_RANGE    = '101003';
    const ORDER_PRODUCT_VERSION  = 5;
    const DELIVERY_OUTPUT_METHOD = 'raw';

    const RESOURCE_ONLINE_ORDER = 'get_online_order_ellisphere';

    /** @var int */
    private $contractId;

    /** @var string */
    private $userPrefix;

    /** @var string */
    private $userId;

    /** @var string */
    private $password;

    /** @var LoggerInterface */
    private $logger;

    /** @var ClientInterface */
    private $client;

    /** @var CallHistoryManager */
    private $callHistoryManager;

    /** @var bool */
    private $useCache = true;

    /** @var EntityManager */
    private $entityManager;

    /** @var SerializerInterface */
    private $serializer;

    /**
     * EllisphereManager constructor.
     *
     * @param EntityManager       $entityManager
     * @param ClientInterface     $client
     * @param CallHistoryManager  $callHistoryManager
     * @param SerializerInterface $serializer
     * @param LoggerInterface     $logger
     * @param string              $contractId
     * @param string              $userPrefix
     * @param string              $userId
     * @param string              $password
     */
    public function __construct(
        EntityManager $entityManager,
        ClientInterface $client,
        CallHistoryManager $callHistoryManager,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        $contractId,
        $userPrefix,
        $userId,
        $password
    )
    {
        $this->contractId         = $contractId;
        $this->userPrefix         = $userPrefix;
        $this->userId             = $userId;
        $this->password           = $password;
        $this->logger             = $logger;
        $this->client             = $client;
        $this->callHistoryManager = $callHistoryManager;
        $this->entityManager      = $entityManager;
        $this->serializer         = $serializer;
    }

    /**
     * @param $siren
     *
     * @return Report|null
     */
    public function getReport($siren)
    {
        $wsResource = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WsExternalResource')->findOneBy(['label' => self::RESOURCE_ONLINE_ORDER]);

        if (null !== ($result = $this->sendRequest($wsResource, ['siren' => $siren]))) {
            return $this->serializer->deserialize($result->response->report->asXML(), Report::class, 'xml');
        }

        return null;
    }

    /**
     * @param WsExternalResource $wsResource
     * @param array              $parameters
     *
     * @return null|\SimpleXMLElement
     */
    private function sendRequest(WsExternalResource $wsResource, array $parameters)
    {
        $endpoint   = $wsResource->getResourceName();
        $logContext = ['method' => __METHOD__, 'resource' => $endpoint];
        $siren      = null;

        if (isset($parameters['siren'])) {
            $logContext['siren'] = $parameters['siren'];
            $siren               = $parameters['siren'];

            if ($content = $this->getStoredResponse($wsResource, $siren)) {
                return new \SimpleXMLElement($content);
            }

            $callback = $this->callHistoryManager->addResourceCallHistoryLog($wsResource, $siren, $this->useCache);
        }

        $headers = ['Content-type' => 'application/xml'];
        $body    = $this->generateXMLRequest($endpoint, $parameters)->asXML();

        try {
            $response = $this->client->request(strtolower($wsResource->getMethod()), $endpoint, ['body' => $this->generateXMLRequest($endpoint, $parameters)->asXML(), 'headers' => $headers]);
            $validity = $this->isValidResponse($response, $logContext);
            $content  = $validity['content'];

            call_user_func($callback, $content, $validity['status']);

            if ('error' === $validity['status']) {
                $this->callHistoryManager->sendMonitoringAlert($wsResource, 'down');
            } else {
                $this->callHistoryManager->sendMonitoringAlert($wsResource, 'up');
            }
            if ('valid' === $validity['status']) {
                return new \SimpleXMLElement($content);
            }
        } catch (\Exception $exception) {
            call_user_func($callback, isset($content) ? $content : '', 'error');
            $message = 'Call to ' . $wsResource->getResourceName() . ' using params: ' . $body . '. Error message: ' . $exception->getMessage() . ' Error code: ' . $exception->getCode();
            if (isset($content)) {
                $message .= $content;
            }
            $this->logger->error($message, $logContext);
            $this->callHistoryManager->sendMonitoringAlert($wsResource, 'down');
        }

        return null;
    }

    /**
     * @param string $endpoint
     * @param array  $parameters
     *
     * @return \SimpleXMLElement
     */
    private function generateXMLRequest($endpoint, $parameters)
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><' . $endpoint . 'Request/>');
        $xml->addAttribute('lang', self::WEBSERVICE_LANG);
        $xml->addAttribute('version', self::WEBSERVICE_VERSION);
        $this->addIdentification($xml);
        $this->addRequest($xml, $endpoint, $parameters);

        return $xml;
    }

    /**
     * @param \SimpleXMLElement $element
     */
    private function addIdentification(\SimpleXMLElement $element)
    {
        $identification = $element->addChild('admin');

        $client = $identification->addChild('client');
        $client->addChild('contractId', $this->contractId);
        $client->addChild('userPrefix', $this->userPrefix);
        $client->addChild('userId', $this->userId);
        $client->addChild('password', $this->password);

        $context = $identification->addChild('context');
        $appId   = $context->addChild('appId', self::APP_ID);
        $appId->addAttribute('version', self::APP_VERSION);
        $context->addChild('date', (new \DateTime())->format('c'));
    }

    /**
     * @param \SimpleXMLElement $element
     * @param sting             $endpoint
     * @param array             $parameters
     */
    private function addRequest(\SimpleXMLElement $element, $endpoint, $parameters)
    {
        $request = $element->addChild('request');
        switch ($endpoint) {
            case 'svcOnlineOrder':
                if (isset($parameters['siren'])) {
                    $id = $request->addChild('id', $parameters['siren']);
                    $id->addAttribute('type', 'register');
                    $id->addAttribute('idName', 'SIREN');
                    $product = $request->addChild('product');
                    $product->addAttribute('range', self::ORDER_PRODUCT_RANGE);
                    $product->addAttribute('version', self::ORDER_PRODUCT_VERSION);
                    $deliveryOptions = $request->addChild('deliveryOptions');
                    $deliveryOptions->addChild('outputMethod', self::DELIVERY_OUTPUT_METHOD);
                } else {
                    $this->logger->error('Siren is not set.', ['method' => __METHOD__, 'resource' => $endpoint]);
                }
                break;
            default:
                $this->logger->error('The endpoint' . $endpoint . 'is not supported by EllisphereManager.');
                break;
        }
    }

    /**
     * @param WsExternalResource $resource
     * @param string             $siren
     *
     * @return bool|string
     */
    private function getStoredResponse(WsExternalResource $resource, $siren)
    {
        $storedResponse = $this->callHistoryManager->getStoredResponse($resource, $siren);

        if (
            $this->useCache
            && false !== $storedResponse
            && false !== simplexml_load_string($storedResponse)
        ) {
            return $storedResponse;
        }

        return false;
    }

    /**
     * @param bool $useCache
     *
     * @return $this
     */
    public function setUseCache($useCache)
    {
        $this->useCache = $useCache;

        return $this;
    }

    /**
     * @param ResponseInterface $response
     * @param array             $logContext
     *
     * @return array
     */
    private function isValidResponse(ResponseInterface $response, $logContext)
    {
        if (500 <= $response->getStatusCode()) {
            return ['status' => 'error', 'is_valid' => false, 'content' => null];
        }
        try {
            $stream = $response->getBody();
            $stream->rewind();
            $content = $stream->getContents();
            $xml     = new \SimpleXMLElement($content);
            $result  = $xml->xpath('result');

            if ('OK' !== (string) $result[0]->attributes()) {
                if (isset($xml->result->majorMessage, $xml->result->minorMessage)) {
                    $error = $xml->result->majorMessage . ' ' . $xml->result->minorMessage;
                    if (isset($xml->result->additionalInfo)) {
                        $error .= $xml->result->additionalInfo;
                    }
                    $this->logger->warning('Ellisphere response status code ' . $response->getStatusCode() . '. Error: ' . $error, $logContext);
                    return ['status' => 'warning', 'is_valid' => false, 'content' => $content];
                }
            }

            return [
                'status'  => 'valid',
                'content' => $content
            ];
        } catch (Exception $exception) {
            $this->logger->error('Error occurs when parse the Ellisphere response. Error messages : ' . $exception->getMessage(), $logContext);
            return ['status' => 'error', 'is_valid' => false, 'content' => null];
        }
    }
}
