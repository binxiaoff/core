<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use CL\Slack\Payload\ChatPostMessagePayload;
use CL\Slack\Transport\ApiClient;
use GuzzleHttp\TransferStats;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Stopwatch\Stopwatch;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

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

    /**
     * WSProviderCallHistoryManager constructor.
     * @param EntityManager $entityManager
     * @param string $stopwatch
     * @param ApiClient $slack
     * @param string $payload
     * @param string $alertChannel
     * @param Packages $assetPackage
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManager $entityManager, $stopwatch, ApiClient $slack, $payload, $alertChannel, Packages $assetPackage, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->stopwatch     = new $stopwatch;
        $this->slack         = $slack;
        $this->payload       = new $payload;
        $this->alertChannel  = $alertChannel;
        $this->assetPackage  = $assetPackage;
        $this->logger        = $logger;
    }

    /**
     * @param string $provider
     * @param string $endpoint
     * @param string $method
     * @param string $siren
     * @return \Closure
     */
    public function addResourceCallHistoryLog($provider, $endpoint, $method, $siren)
    {
        $resourceId = $this->getResourceId($provider, $endpoint, $method);
        $this->stopwatch->start($resourceId);

        return function ($stats = null) use ($resourceId, $siren) {
            try {
                $event        = $this->stopwatch->stop($resourceId);
                $transferTime = $event->getDuration() / 1000;

                if ($stats instanceof TransferStats && null != $stats) {
                    $statusCode = $stats->getResponse()->getStatusCode();
                } else {
                    $statusCode = $stats;
                }

                if (false === empty($resourceId)) {
                    $this->createLog($resourceId, $siren, $transferTime, $statusCode);
                }
            } catch (\Exception $exception) {
                $this->logger->error('Unable to log response time into database. Error message: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $siren]);
                unset($exception);
            }
        };
    }

    /**
     * @param string $provider
     * @param string $endpoint
     * @param string $method
     * @return int
     */
    private function getResourceId($provider, $endpoint, $method)
    {
        /** @var \ws_external_resource $externalResource */
        $externalResource = $this->entityManager->getRepository('ws_external_resource');

        return $externalResource->getResource($provider, $endpoint, $method);
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
     * @param $provider
     * @param $settingType
     * @param $alertType
     * @param $extraInfo
     */
    public function sendMonitoringAlert($provider, $settingType, $alertType, $extraInfo = null)
    {
        /** @var \settings $setting */
        $setting = $this->entityManager->getRepository('settings');
        $setting->get($settingType, 'type');
        $provider = ucfirst(strtolower($provider));

        switch ($alertType) {
            case 'down':
                if ($setting->value) {
                    $setting->value = '0';
                    $this->setPayload();
                    $this->payload->setText($provider . " is down  :skull_and_crossbones:\n> " . $extraInfo);
                } else {
                    return;
                }
                break;
            case 'up':
                if (! $setting->value) {
                    $setting->value = '1';
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
        $setting->update();
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

    private function setPayload()
    {
        $this->payload->setChannel($this->alertChannel);
        $this->payload->setUsername('Unilend');

        if (file_exists($this->assetPackage->getUrl('') . '/assets/images/slack/unilend.png')) {
            $this->payload->setIconUrl($this->assetPackage->getUrl('') . '/assets/images/slack/unilend.png');
        }
        $this->payload->setAsUser(false);
    }
}
