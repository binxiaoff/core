<?php

namespace Unilend\Service\WebServiceClient;

use GuzzleHttp\{Client, ClientInterface};
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Unilend\Entity\WsExternalResource;
use Unilend\Entity\External\Infolegale\{AnnouncementCollection, AnnouncementDetailsCollection, DirectorAnnouncementCollection, ExecutiveCollection, HomonymCollection, Identity,
    MandateCollection, ScoreDetails};

class InfolegaleManager
{
    const RESOURCE_COMPANY_SCORE          = 'get_score_infolegale';
    const RESOURCE_SEARCH_COMPANY         = 'search_company_infolegale';
    const RESOURCE_COMPANY_IDENTITY       = 'get_identity_infolegale';
    const RESOURCE_ANNOUNCEMENTS_LIST     = 'get_announcements_list_infolegale';
    const RESOURCE_ANNOUNCEMENTS_DETAILS  = 'get_announcements_details_infolegale';
    const RESOURCE_EXECUTIVES             = 'get_executives_infolegale';
    const RESOURCE_MANDATES               = 'get_mandates_infolegale';
    const RESOURCE_HOMONYMS               = 'get_homonyms_infolegale';
    const RESOURCE_ANNOUNCEMENTS_DIRECTOR = 'get_announcements_director_infolegale';

    /** @var Client */
    private $client;
    /** @var string */
    private $token;
    /** @var LoggerInterface */
    private $logger;
    /** @var CallHistoryManager */
    private $callHistoryManager;
    /** @var SerializerInterface */
    private $serializer;
    /** @var ResourceManager */
    private $resourceManager;
    /** @var bool */
    private $saveToCache = true;
    /** @var bool */
    private $readFromCache = true;

    /**
     * @param ClientInterface     $client
     * @param string              $token
     * @param LoggerInterface     $wsClientCallLogger
     * @param CallHistoryManager  $callHistoryManager
     * @param SerializerInterface $serializer
     * @param ResourceManager     $resourceManager
     */
    public function __construct(
        ClientInterface $client,
        $token,
        LoggerInterface $wsClientCallLogger,
        CallHistoryManager $callHistoryManager,
        SerializerInterface $serializer,
        ResourceManager $resourceManager
    )
    {
        $this->client             = $client;
        $this->token              = $token;
        $this->logger             = $wsClientCallLogger;
        $this->callHistoryManager = $callHistoryManager;
        $this->serializer         = $serializer;
        $this->resourceManager    = $resourceManager;
    }

    /**
     * Should be replaced by method parameters instead of class parameters
     * @param bool $saveToCache
     *
     * @return InfolegaleManager
     */
    public function setSaveToCache($saveToCache)
    {
        $this->saveToCache = $saveToCache;

        return $this;
    }

    /**
     * Should be replaced by method parameters instead of class parameters
     * @param bool $readFromCache
     *
     * @return InfolegaleManager
     */
    public function setReadFromCache($readFromCache)
    {
        $this->readFromCache = $readFromCache;

        return $this;
    }

    /**
     * @param string $siren
     *
     * @return null|ScoreDetails
     */
    public function getScore($siren)
    {
        if (
            null !== ($result = $this->sendRequest(self::RESOURCE_COMPANY_SCORE, ['siren' => $siren]))
            && false === empty($result->scoreInfo[0])
        ) {
            return $this->serializer->deserialize($result->scoreInfo[0]->asXML(), ScoreDetails::class, 'xml');
        }

        return null;
    }

    /**
     * @param string $siren
     *
     * @return null|\SimpleXMLElement
     */
    public function searchCompany($siren)
    {
        return $this->sendRequest(self::RESOURCE_SEARCH_COMPANY, ['siren' => $siren]);
    }

    /**
     * @param string $siren
     *
     * @return null|Identity
     */
    public function getIdentity($siren)
    {
        if (null !== ($result = $this->sendRequest(self::RESOURCE_COMPANY_IDENTITY, ['siren' => $siren]))) {
            return $this->serializer->deserialize($result->asXML(), Identity::class, 'xml');
        }

        return null;
    }

    /**
     * @param string $siren
     *
     * @return AnnouncementCollection|null
     */
    public function getAnnouncements($siren)
    {
        if (null !== ($result = $this->sendRequest(self::RESOURCE_ANNOUNCEMENTS_LIST, ['siren' => $siren]))) {
            return $this->serializer->deserialize($result->asXML(), AnnouncementCollection::class, 'xml');
        }
        return null;
    }

    /**
     * @param string[] $announcementsId
     *
     * @return AnnouncementDetailsCollection|null
     */
    public function getAnnouncementsDetails(array $announcementsId): ?AnnouncementDetailsCollection
    {
        if (null !== ($result = $this->sendRequest(self::RESOURCE_ANNOUNCEMENTS_DETAILS, ['adsId' => $announcementsId]))) {
            return $this->serializer->deserialize($result->asXML(), AnnouncementDetailsCollection::class, 'xml');
        }
        return null;
    }

    /**
     * @param $siren
     *
     * @return ExecutiveCollection|null
     */
    public function getExecutives($siren)
    {
        if (null !== ($result = $this->sendRequest(self::RESOURCE_EXECUTIVES, ['siren' => $siren]))) {
            return $this->serializer->deserialize($result->asXML(), ExecutiveCollection::class, 'xml');
        }
        return null;
    }

    /**
     * @param $executiveId
     *
     * @return MandateCollection|null
     */
    public function getMandates($executiveId)
    {
        if (null !== ($result = $this->sendRequest(self::RESOURCE_MANDATES, ['execId' => $executiveId]))) {
            return $this->serializer->deserialize($result->asXML(), MandateCollection::class, 'xml');
        }
        return null;
    }

    /**
     * @param $executiveId
     *
     * @return HomonymCollection|null
     */
    public function getHomonyms($executiveId)
    {
        if (null !== ($result = $this->sendRequest(self::RESOURCE_HOMONYMS, ['execId' => $executiveId]))) {
            return $this->serializer->deserialize($result->asXML(), HomonymCollection::class, 'xml');
        }
        return null;
    }

    /**
     * @param $executiveId
     *
     * @return DirectorAnnouncementCollection|null
     */
    public function getDirectorAnnouncements($executiveId)
    {
        if (null !== ($result = $this->sendRequest(self::RESOURCE_ANNOUNCEMENTS_DIRECTOR, ['execId' => $executiveId]))) {
            return $this->serializer->deserialize($result->asXML(), DirectorAnnouncementCollection::class, 'xml');
        }
        return null;
    }

    /**
     * @param string $resourceLabel
     * @param array  $parameters
     *
     * @return null|\SimpleXMLElement
     */
    private function sendRequest($resourceLabel, $parameters = [])
    {
        $wsResource = $this->resourceManager->getResource($resourceLabel);
        $logContext = ['class' => __CLASS__, 'resource' => $wsResource->getResourceName()];
        $siren      = null;
        $query      = [
            'token' => $this->token
        ];

        if (isset($parameters['siren'])) {
            $siren               = $parameters['siren'];
            $logContext['siren'] = $siren;
            $query['siren']      = $siren;
        }

        $query = array_merge($query, $parameters);

        try {
            if ($storedResponse = $this->getStoredResponse($wsResource, $siren, $parameters)) {
                $storedData = $this->getContentAndErrors($storedResponse);

                if (empty($storedData['errors'])) {
                    return $storedData['content'];
                }
            }
            $callback = $this->callHistoryManager->addResourceCallHistoryLog($wsResource, $siren, $this->saveToCache);
            /** @var ResponseInterface $response */
            $response = $this->client->{strtolower($wsResource->getMethod())} (
                $wsResource->getResourceName(),
                ['query' => $query]
            );
            $validity = $this->isValidResponse($response, $logContext);

            $stream = $response->getBody();
            $stream->rewind();
            $content = $stream->getContents();
            call_user_func($callback, $content, $validity['status'], $parameters);

            if ($validity['is_valid']) {
                return $validity['content'];
            } else {
                return null;
            }
        } catch (\Exception $exception) {
            if (isset($callback)) {
                call_user_func($callback, isset($content) ? $content : '', 'error');
            }
            $message = 'Call to ' . $wsResource->getResourceName() . ' using params: ' . json_encode($query) . '. Error message: ' . $exception->getMessage() . ' Error code: ' . $exception->getCode();
            if (isset($content)) {
                $message .= $content;
            }
            $this->logger->error($message, $logContext);

            return null;
        }
    }

    /**
     * @param WsExternalResource $resource
     * @param string             $siren
     * @param array              $parameters
     *
     * @return bool|string
     */
    private function getStoredResponse(WsExternalResource $resource, $siren, $parameters = [])
    {
        $storedResponse = $this->callHistoryManager->getStoredResponse($resource, $siren, $parameters);

        if (
            $this->readFromCache
            && false !== $storedResponse
            && false !== simplexml_load_string($storedResponse)
        ) {
            return $storedResponse;
        }

        return false;
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

        if (200 === $response->getStatusCode()) {
            $data = $this->getContentAndErrors($content);

            if (false === empty($data['errors'])) {
                $this->logger->warning('Infolegale response error: ' . json_encode($data['errors']), $logContext);
            }
            return [
                'status'   => empty($data['errors']) ? 'valid' : 'warning',
                'is_valid' => empty($data['errors']),
                'content'  => $data['content']
            ];
        }
        $this->logger->error('Infolegale response status code ' . $response->getStatusCode() . '. Response: ' . json_encode($content), $logContext);

        return ['status' => 'error', 'is_valid' => false, 'content' => null];
    }

    /**
     * @param string $content
     *
     * @return array
     */
    private function getContentAndErrors($content)
    {
        $xml = new \SimpleXMLElement($content);
        $xml->registerXPathNamespace('ilg', 'Infolegale/Webservices/Main');
        $errors = $xml->xpath('.//ilg:errors')[0];

        return ['content' => $xml->content[0], 'errors' => $errors];
    }
}
