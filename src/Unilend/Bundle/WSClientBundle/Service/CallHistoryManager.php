<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ORM\{EntityManagerInterface, ORMException};
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Stopwatch\Stopwatch;
use Unilend\Entity\{WsCallHistory, WsExternalResource};
use Unilend\Bundle\CoreBusinessBundle\Service\SlackManager;
use Unilend\Document\WsCall;
use Unilend\librairies\CacheKeys;

class CallHistoryManager
{
    /** @var EntityManagerInterface */
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
    /** @var bool */
    private $mongoLogEnabled;

    /**
     * @param EntityManagerInterface $entityManager
     * @param CacheItemPoolInterface $cacheItemPool
     * @param Stopwatch              $stopwatch
     * @param SlackManager           $slackManager
     * @param string                 $alertChannel
     * @param Packages               $assetPackage
     * @param LoggerInterface        $logger
     * @param ManagerRegistry        $managerRegistry
     * @param LoggerInterface        $mongoDBLogger
     * @param bool                   $mongoLogEnabled
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        CacheItemPoolInterface $cacheItemPool,
        Stopwatch $stopwatch,
        SlackManager $slackManager,
        $alertChannel,
        Packages $assetPackage,
        LoggerInterface $logger,
        ManagerRegistry $managerRegistry,
        LoggerInterface $mongoDBLogger,
        bool $mongoLogEnabled
    )
    {
        $this->entityManager   = $entityManager;
        $this->cacheItemPool   = $cacheItemPool;
        $this->stopwatch       = $stopwatch;
        $this->slackManager    = $slackManager;
        $this->alertChannel    = $alertChannel;
        $this->assetPackage    = $assetPackage;
        $this->logger          = $logger;
        $this->managerRegistry = $managerRegistry;
        $this->mongoDBLogger   = $mongoDBLogger;
        $this->mongoLogEnabled = $mongoLogEnabled;
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

            return function ($result = null, $callStatus, $parameter = []) use ($wsResource, $siren, $useCache) {
                try {
                    $event         = $this->stopwatch->stop($wsResource->getIdResource());
                    $transferTime  = $event->getDuration() / 1000;
                    $wsCallHistory = $this->createLog($wsResource, $siren, $transferTime, $callStatus);

                    if ($useCache) {
                        $this->storeResponse($wsCallHistory, $parameter, $result);
                    }

                    return $wsCallHistory;
                } catch (\Exception $exception) {
                    $this->logger->error(
                        'Unable to log response time into database. Error message: ' . $exception->getMessage(),
                        ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren]
                    );
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
    private function createLog(WsExternalResource $resource, string $siren, float $transferTime, ?string $callStatus = null)
    {
        $wsCallHistory = new WsCallHistory();
        $wsCallHistory
            ->setIdResource($resource)
            ->setSiren($siren)
            ->setTransferTime(round($transferTime, 3))
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
    public function sendMonitoringAlert(WsExternalResource $wsResource, string $alertType, string $extraInfo = null): void
    {
        switch ($alertType) {
            case 'down':
                if (false == $wsResource->isAvailable()) {
                    return;
                }
                $wsResource
                    ->setIsAvailable(WsExternalResource::STATUS_UNAVAILABLE)
                    ->setUpdated(new \DateTime());
                $slackMessage = $wsResource->getProviderName() . '(' . $wsResource->getResourceName() . ') is down  :skull_and_crossbones:';

                if (null !== $extraInfo) {
                    $slackMessage .= "\n> " . $extraInfo;
                }
                break;
            case 'up':
                if ($wsResource->isAvailable()) {
                    return;
                }
                $wsResource
                    ->setIsAvailable(WsExternalResource::STATUS_AVAILABLE)
                    ->setUpdated(new \DateTime());
                $slackMessage = $wsResource->getProviderName() . '(' . $wsResource->getResourceName() . ') is up  :white_check_mark:';

                if (null !== $extraInfo) {
                    $slackMessage .= "\n> " . $extraInfo;
                }
                break;
            default:
                return;
        }

        try {
            $this->entityManager->flush($wsResource);
        } catch (ORMException $exception) {
            $this->logger->warning('Could not update status for WS ' . $wsResource->getLabel() . '. ' . $exception->getMessage(), [
                'provider' => $wsResource->getProviderName(),
                'resource' => $wsResource->getResourceName(),
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine()
            ]);
            return;
        }

        $response = $this->slackManager->sendMessage($slackMessage, $this->alertChannel);

        if (false === $response) {
            $this->logger->warning('Could not send Slack notification for ' . $wsResource->getLabel() . ' monitoring', [
                'provider' => $wsResource->getProviderName(),
                'resource' => $wsResource->getResourceName(),
                'class'    => __CLASS__,
                'function' => __FUNCTION__
            ]);
        }
    }

    /**
     * @param WsCallHistory $callHistory
     * @param array         $parameter
     * @param string        $response
     * @param WsCallHistory $callHistory
     */
    private function storeResponse(WsCallHistory $callHistory, array $parameter, $response)
    {
        $wsResource = $callHistory->getIdResource();
        $siren      = $callHistory->getSiren();

        try {
            $wsCall = new WsCall();
            $wsCall->setSiren($siren);
            if (false === empty($parameter)) {
                $wsCall->setParameter(json_encode($parameter));
            }
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

        $cachedItem = $this->cacheItemPool->getItem($this->getCacheKey($wsResource, $siren, $parameter));
        $cachedItem->set($response)->expiresAfter(CacheKeys::LONG_TIME);

        $this->cacheItemPool->save($cachedItem);
    }

    /**
     * @param null|string    $siren
     * @param array          $parameter
     * @param string         $provider
     * @param string         $resource
     * @param null|\DateTime $date
     *
     * @return false|WsCall
     */
    public function fetchLatestDataFromMongo($siren, $parameter, $provider, $resource, \DateTime $date)
    {
        $logContext = ['class' => __CLASS__, 'function' => __FUNCTION__, 'query_params' => json_encode([$siren, $provider, $resource, $date->format('Y-m-d H:i:s')])];
        $wsCall     = false;
        $time       = time();
        $this->stopwatch->start(__FUNCTION__ . $time);
        try {
            /** @var Builder $queryBuilder */
            $queryBuilder = $this->managerRegistry->getManager()->createQueryBuilder(WsCall::class);

            if (false === empty($parameter)) {
                $queryBuilder->field('parameter')->equals(json_encode($parameter));
            }

            if ($siren) {
                $queryBuilder->field('siren')->equals((string) $siren);
            }

            $queryBuilder->field('provider')->equals($provider)
                ->field('resource')->equals($resource)
                ->field('added')->gte($date)
                ->sort('added', 'desc')
                ->limit(1)
                ->refresh();

            $wsCall = $queryBuilder->getQuery()->getSingleResult();
        } catch (\Exception $exception) {
            $this->logger->warning('Unable to fetch data from mongoDB: ' . $exception->getMessage(), $logContext);
        }

        return $wsCall;
    }

    /**
     * @param WsExternalResource $wsResource
     * @param string             $siren
     * @param array              $parameter
     *
     * @return mixed
     */
    public function getStoredResponse(WsExternalResource $wsResource, $siren, $parameter = [])
    {
        $cachedItem = $this->cacheItemPool->getItem($this->getCacheKey($wsResource, $siren, $parameter));

        if ($cachedItem->isHit()) {
            return $cachedItem->get();
        }

        $data = $this->fetchLatestDataFromMongo(
            $siren,
            $parameter,
            $wsResource->getProviderName(),
            $wsResource->getResourceName(),
            $this->getDateTimeFromPassedDays($wsResource->getValidityDays())
        );

        if ($data instanceof WsCall) {
            $this->logger->debug(
                'Fetched data from mongoDB for ' . $data->getProvider() . '->' . $data->getResource() . ': ' . $data->getResponse() . ' --- Stored at: ' . $data->getAdded()->format('Y-m-d H:i:s'),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $data->getSiren()]
            );
            return $data->getResponse();
        }

        return false;
    }

    /**
     * @param int $days
     *
     * @return \DateTime
     */
    public function getDateTimeFromPassedDays($days = 3)
    {
        return (new \DateTime())->sub(new \DateInterval('P' . $days . 'D'));
    }

    /**
     * @required
     */
    public function handleMongoDBLogging()
    {
        if (true === $this->mongoLogEnabled) {
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
     *
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
     * @param string             $siren
     * @param array              $parameter
     *
     * @return string
     */
    private function getCacheKey(WsExternalResource $resource, $siren, array $parameter)
    {
        return 'WS_call_' . $resource->getLabel() . '_' . $siren . '_' . md5(json_encode($parameter));
    }
}
