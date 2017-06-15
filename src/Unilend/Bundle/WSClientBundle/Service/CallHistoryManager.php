<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Stopwatch\Stopwatch;
use Unilend\Bundle\CoreBusinessBundle\Entity\WsCallHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\WsExternalResource;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\CoreBusinessBundle\Service\SlackManager;
use Unilend\Bundle\StoreBundle\Document\WsCall;
use Unilend\librairies\CacheKeys;

class CallHistoryManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var CacheItemPoolInterface */
    private $cacheItemPool;
    /** @var Stopwatch */
    private $stopwatch;
    /** @var SlackManager */
    private $slackManager;
    /** @var string */
    private $alertChannel;
    /** @var Packages */
    private $assetPackage;
    /** @var LoggerInterface */
    private $logger;
    /** @var ManagerRegistry */
    private $managerRegistry;
    /** @var LoggerInterface */
    private $mongoDBLogger;
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;

    /**
     * @param EntityManager          $entityManager
     * @param CacheItemPoolInterface $cacheItemPool
     * @param Stopwatch              $stopwatch
     * @param SlackManager           $slackManager
     * @param string                 $alertChannel
     * @param Packages               $assetPackage
     * @param LoggerInterface        $logger
     * @param ManagerRegistry        $managerRegistry
     * @param LoggerInterface        $mongoDBLogger
     * @param EntityManagerSimulator $entityManagerSimulator
     */
    public function __construct(
        EntityManager $entityManager,
        CacheItemPoolInterface $cacheItemPool,
        Stopwatch $stopwatch,
        SlackManager $slackManager,
        $alertChannel,
        Packages $assetPackage,
        LoggerInterface $logger,
        ManagerRegistry $managerRegistry,
        LoggerInterface $mongoDBLogger,
        EntityManagerSimulator $entityManagerSimulator
    )
    {
        $this->entityManager          = $entityManager;
        $this->cacheItemPool          = $cacheItemPool;
        $this->stopwatch              = $stopwatch;
        $this->slackManager           = $slackManager;
        $this->alertChannel           = $alertChannel;
        $this->assetPackage           = $assetPackage;
        $this->logger                 = $logger;
        $this->managerRegistry        = $managerRegistry;
        $this->mongoDBLogger          = $mongoDBLogger;
        $this->entityManagerSimulator = $entityManagerSimulator;
    }

    /**
     * @param WsExternalResource $wsResource
     * @param string             $siren
     * @param bool               $useCache
     *
     * @return \Closure
     */
    public function addResourceCallHistoryLog($wsResource, $siren, $useCache)
    {
        if (null !== $wsResource) {
            $this->stopwatch->start($wsResource->getIdResource());

            return function ($result = null, $callStatus) use ($wsResource, $siren, $useCache) {
                try {
                    $event         = $this->stopwatch->stop($wsResource->getIdResource());
                    $transferTime  = $event->getDuration() / 1000;
                    $wsCallHistory = $this->createLog($wsResource, $siren, $transferTime, $callStatus);

                    if ($useCache) {
                        $this->storeResponse($wsResource, $siren, $result, $wsCallHistory);
                    }

                    return $wsCallHistory;
                } catch (\Exception $exception) {
                    $this->logger->error('Unable to log response time into database. Error message: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren]);
                    unset($exception);
                }

                return null;
            };
        }

        return function () {

        };
    }

    /**
     * @param WsExternalResource $resource
     * @param string             $siren
     * @param float              $transferTime
     * @param null|string        $callStatus
     *
     * @return WsCallHistory
     */
    private function createLog($resource, $siren, $transferTime, $callStatus = null)
    {
        $wsCallHistory = new WsCallHistory();
        $wsCallHistory->setIdResource($resource)
            ->setSiren($siren)
            ->setTransferTime($transferTime)
            ->setCallStatus($callStatus);
        $this->entityManager->persist($wsCallHistory);
        $this->entityManager->flush($wsCallHistory);

        return $wsCallHistory;
    }

    /**
     * @param WsExternalResource $wsResource
     * @param string             $alertType
     * @param string             $extraInfo
     */
    public function sendMonitoringAlert(WsExternalResource $wsResource, $alertType, $extraInfo = null)
    {
        switch ($alertType) {
            case 'down':
                if (false == $wsResource->isIsAvailable()) {
                    return;
                }

                $wsResource->setIsAvailable(WsExternalResource::STATUS_UNAVAILABLE);
                $slackMessage = $wsResource->getProviderName() . '(' . $wsResource->getResourceName() . ') is down  :skull_and_crossbones:';

                if (null !== $extraInfo) {
                    $slackMessage .= "\n> " . $extraInfo;
                }
                break;
            case 'up':
                if ($wsResource->isIsAvailable()) {
                    return;
                }

                $wsResource->setIsAvailable(WsExternalResource::STATUS_AVAILABLE);
                $slackMessage = $wsResource->getProviderName() . '(' . $wsResource->getResourceName() . ') is up  :white_check_mark:';
                break;
            default:
                return;
        }
        $this->entityManager->flush($wsResource);
        $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'provider' => $wsResource->getProviderName()];

        try {
            $response = $this->slackManager->sendMessage($slackMessage, $this->alertChannel);

            if (false == $response->isOk()) {
                $this->logger->warning('Could not send slack notification for ' . $wsResource->getProviderName() . '. Error: ' . $response->getError(), $logContext);
            }
        } catch (\Exception $exception) {
            $this->logger->error('Unable to send slack notification for ' . $wsResource->getProviderName() . '. Error message: ' . $exception->getMessage(), $logContext);
            unset($exception);
        }
    }

    /**
     * @param WsExternalResource $wsResource
     * @param string             $siren
     * @param string             $response
     */
    private function storeResponse(WsExternalResource $wsResource, $siren, $response, WsCallHistory $callHistory)
    {
        try {
            $wsCall = new WsCall();
            $wsCall->setSiren($siren);
            $wsCall->setProvider($wsResource->getProviderName());
            $wsCall->setResource($wsResource->getResourceName());
            $wsCall->setResponse($response);
            $wsCall->setIdWsCallHistory($callHistory->getIdCallHistory());
            $wsCall->setAdded(new \DateTime());

            $documentManager = $this->managerRegistry->getManager();
            $documentManager->persist($wsCall);
            $documentManager->flush();
        } catch (\Exception $exception) {
            $this->logger->warning('Unable to save response to mongoDB: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren]);
        }

        $cachedItem = $this->cacheItemPool->getItem($this->getCacheKey($wsResource));
        $cachedItem->set($response)->expiresAfter(CacheKeys::LONG_TIME);

        $this->cacheItemPool->save($cachedItem);
    }

    /**
     * @param string         $siren
     * @param string         $provider
     * @param string         $resource
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

        return $wsCall;
    }

    /**
     * @param WsExternalResource $wsResource
     * @param string             $siren
     *
     * @return mixed
     */
    public function getStoredResponse(WsExternalResource $wsResource, $siren)
    {
        $cachedItem = $this->cacheItemPool->getItem($this->getCacheKey($wsResource));

        if ($cachedItem->isHit()) {
            return $cachedItem->get();
        }

        $data = $this->fetchLatestDataFromMongo(
            $siren,
            $wsResource->getProviderName(),
            $wsResource->getResourceName(),
            $this->getDateTimeFromPassedDays($wsResource->getValidityDays())
        );

        if ($data instanceof WsCall) {
            $this->logger->debug('Fetched data from mongoDB for ' . $data->getProvider() . '->' . $data->getResource() . ': ' . $data->getResponse() . ' --- Stored at: ' . $data->getAdded()->format('Y-m-d H:i:s'), ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $data->getSiren()]);
            return $data->getResponse();
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

    public function handleMongoDBLogging()
    {
        $setting = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'mongo_log']);

        if (null !== $setting && 'on' === $setting->getValue()) {
            \MongoLog::setModule(\MongoLog::ALL);
            \MongoLog::setLevel(\MongoLog::ALL);
            \MongoLog::setCallback([$this, 'callback']);
        }
    }

    /**
     * @param int    $module
     * @param int    $level
     * @param string $message
     */
    public function callback($module, $level, $message)
    {
        switch ($level) {
            case \MongoLog::WARNING:
                $this->mongoDBLogger->warning($this->module2string($module) . ' ' . $message, ['class' => __CLASS__, 'function' => __FUNCTION__]);
                break;
            case \MongoLog::INFO:
                $this->mongoDBLogger->info($this->module2string($module) . ' ' . $message, ['class' => __CLASS__, 'function' => __FUNCTION__]);
                break;
            default:
                $this->mongoDBLogger->debug($this->module2string($module) . ' ' . $message, ['class' => __CLASS__, 'function' => __FUNCTION__]);
        }
    }

    /**
     * @param int $module
     * @return string
     */
    private function module2string($module)
    {
        switch ($module) {
            case \MongoLog::RS:
                return 'REPLSET';
            case \MongoLog::CON:
                return 'CON';
            case \MongoLog::IO:
                return 'IO';
            case \MongoLog::SERVER:
                return 'SERVER';
            case \MongoLog::PARSE:
                return 'PARSE';
            default:
                return 'UNKNOWN';
        }
    }

    /**
     * @param WsExternalResource $resource
     *
     * @return string
     */
    private function getCacheKey(WsExternalResource $resource)
    {
        return 'WS_call_' . $resource->getLabel();
    }
}
