<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use CL\Slack\Payload\ChatPostMessagePayload;
use CL\Slack\Transport\ApiClient;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use GuzzleHttp\TransferStats;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Stopwatch\Stopwatch;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\StoreBundle\Document\WsCall;

class CallHistoryManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var Stopwatch */
    private $stopwatch;
    /** @var ApiClient */
    private $slack;
    /** @var  ChatPostMessagePayload */
    private $payload;
    /** @var string */
    private $alertChannel;
    /** @var Packages */
    private $assetPackage;
    /** @var  LoggerInterface */
    private $logger;
    /** @var ManagerRegistry */
    private $managerRegistry;

    /**
     * WSProviderCallHistoryManager constructor.
     * @param EntityManager $entityManager
     * @param Stopwatch $stopwatch
     * @param ApiClient $slack
     * @param string $payload
     * @param string $alertChannel
     * @param Packages $assetPackage
     * @param LoggerInterface $logger
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(EntityManager $entityManager, Stopwatch $stopwatch, ApiClient $slack, $payload, $alertChannel, Packages $assetPackage, LoggerInterface $logger, ManagerRegistry $managerRegistry)
    {
        $this->entityManager   = $entityManager;
        $this->stopwatch       = $stopwatch;
        $this->slack           = $slack;
        $this->payload         = new $payload;
        $this->alertChannel    = $alertChannel;
        $this->assetPackage    = $assetPackage;
        $this->logger          = $logger;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param \ws_external_resource $wsResource
     * @param string $siren
     * @return \Closure
     */
    public function addResourceCallHistoryLog($wsResource, $siren)
    {
        if (false !== $wsResource) {
            $this->stopwatch->start($wsResource->id_resource);

            return function ($stats = null) use ($wsResource, $siren) {
                try {
                    $event        = $this->stopwatch->stop($wsResource->id_resource);
                    $transferTime = $event->getDuration() / 1000;

                    if ($stats instanceof TransferStats && null != $stats && $stats->hasResponse()) {
                        $statusCode = $stats->getResponse()->getStatusCode();
                        $stream     = $stats->getResponse()->getBody();
                        // getContents returns the remaining contents, so that a second call returns nothing unless we seek the position of the stream with rewind
                        $stream->rewind();
                        $result = $stream->getContents();
                    } else {
                        $statusCode = null;
                        $result     = $stats;
                    }
                    $this->createLog($wsResource->id_resource, $siren, $transferTime, $statusCode);
                    $this->storeResponse($wsResource, $siren, $result);
                } catch (\Exception $exception) {
                    $this->logger->error('Unable to log response time into database. Error message: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren]);
                    unset($exception);
                }
            };
        }
        return function () {

        };
    }

    private function createLog($resourceId, $siren, $transferTime, $statusCode)
    {
        /** @var \ws_call_history $wsCallHistory */
        $wsCallHistory                = $this->entityManager->getRepository('ws_call_history');
        $wsCallHistory->id_resource   = $resourceId;
        $wsCallHistory->siren         = $siren;
        $wsCallHistory->transfer_time = $transferTime;
        $wsCallHistory->status_code   = $statusCode;
        $wsCallHistory->create();
    }

    /**
     * @param \ws_external_resource $wsResource
     * @param $alertType
     * @param $extraInfo
     */
    public function sendMonitoringAlert(\ws_external_resource $wsResource, $alertType, $extraInfo = null)
    {
        $provider = ucfirst(strtolower($wsResource->provider_name));

        switch ($alertType) {
            case 'down':
                if ($wsResource->is_available) {
                    $wsResource->is_available = 0;
                    $this->setPayload();
                    $this->payload->setText($provider . " is down  :skull_and_crossbones:\n> " . $extraInfo);
                } else {
                    return;
                }
                break;
            case 'up':
                if (! $wsResource->is_available) {
                    $wsResource->is_available = 1;
                    $this->setPayload();
                    $this->payload->setText($provider . ' is up  :white_check_mark:');
                } else {
                    return;
                }
                break;
            default:
                unset($payload);
                return;
        }
        $wsResource->update();
        $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'provider' => $provider];
        try {
            $response = $this->slack->send($this->payload);

            if (false == $response->isOk()) {
                $this->logger->warning('Could not send slack notification for ' . $provider . '. Error: ' . $response->getError(), $logContext);
            }
        } catch (\Exception $exception) {
            $this->logger->error('Unable to send slack notification for ' . $provider . '. Error message: ' . $exception->getMessage(), $logContext);
            unset($exception);
        }
    }

    /**
     * @param \ws_external_resource $wsResource
     * @param string $siren
     * @param string $response
     */
    private function storeResponse($wsResource, $siren, $response)
    {
        try {
            $wsCall = new WsCall();
            $wsCall->setSiren($siren);
            $wsCall->setProvider($wsResource->provider_name);
            $wsCall->setResource($wsResource->resource_name);
            $wsCall->setResponse($response);
            $wsCall->setAdded(new \DateTime());

            $dm = $this->managerRegistry->getManager();
            $dm->persist($wsCall);
            $dm->flush();
        } catch (\Exception $exception) {
            $this->logger->warning('Unable to save response to mongoDB: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren]);
        }
    }

    private function setPayload()
    {
        $this->payload->setChannel($this->alertChannel);
        $this->payload->setUsername('Unilend');

        if (file_exists($this->assetPackage->getUrl('') . '/assets/images/slack/unilend.png')) {
            $this->payload->setIconUrl($this->assetPackage->getUrl('') . '/assets/images/slack/unilend.png');
        }
        $this->payload->setAsUser(false);
    }

    /**
     * @param string $siren
     * @param string $provider
     * @param string $resource
     * @param null|\DateTime $date
     * @return false|WsCall
     */
    public function fetchLatestDataFromMongo($siren, $provider, $resource, \DateTime $date)
    {
        $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'query_params' => json_encode([$siren, $provider, $resource, $date->format('Y-m-d H:i:s')])];
        $wsCall     = false;
        $time       = time();
        $this->stopwatch->start(__FUNCTION__ . $time);

        try {
            /** @var WsCall $wsCall */
            $wsCall = $this->managerRegistry->getManager()
                ->createQueryBuilder('UnilendStoreBundle:WsCall')
                ->field('siren')->equals($siren)
                ->field('provider')->equals($provider)
                ->field('resource')->equals($resource)
                ->field('added')->gte($date)
                ->sort('added', 'desc')
                ->limit(1)
                ->refresh()
                ->getQuery()
                ->getSingleResult();
        } catch (\Exception $exception) {
            $this->logger->warning('Unable to fetch data from mongoDB: ' . $exception->getMessage(), $logContext);
        }
        $this->logger->info('Data fetch from mongo DB took: ' . $this->stopwatch->stop(__FUNCTION__ . $time)->getDuration() . ' ms', $logContext);

        return $wsCall;
    }

    /**
     * @param \ws_external_resource $wsResource
     * @param string $siren
     * @return mixed
     */
    public function getStoredResponse(\ws_external_resource $wsResource, $siren)
    {
        if ($wsResource->validity_days > 0) {
            $data = $this->fetchLatestDataFromMongo(
                $siren,
                $wsResource->provider_name,
                $wsResource->resource_name,
                $this->getDateTimeFromPassedDays($wsResource->validity_days)
            );

            if ($data instanceof WsCall) {
                $this->logger->debug('Fetched data from mongoDB for: ' . $data->getProvider() . '->' . $data->getResource() . ': ' . $data->getResponse() . ' --- Stored at: ' . $data->getAdded()->format('Y-m-d H:i:s'), ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $data->getSiren()]);
                return json_decode($data->getResponse());
            }
        }

        return false;
    }

    /**
     * @param int $days
     * @return \DateTime
     */
    public function getDateTimeFromPassedDays($days = 3)
    {
        return (new \DateTime())->sub(new \DateInterval('P' . $days . 'D'));
    }
}
