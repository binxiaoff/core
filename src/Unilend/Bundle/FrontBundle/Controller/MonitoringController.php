<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Cache\Adapter\Memcache\MemcacheCachePool;
use Unilend\Bundle\WSClientBundle\Service\AltaresManager;
use Unilend\Bundle\WSClientBundle\Service\CodinfManager;
use Unilend\Bundle\WSClientBundle\Service\EllisphereManager;
use Unilend\Bundle\WSClientBundle\Service\EulerHermesManager;
use Unilend\Bundle\WSClientBundle\Service\InfogreffeManager;
use Unilend\Bundle\WSClientBundle\Service\InfolegaleManager;
use Unilend\librairies\CacheKeys;

class MonitoringController extends Controller
{
    /** This value is set directly from PingDom BO account */
    const PINGDOM_FREQUENCY    = 5;
    const PINGDOM_IPS_LIST_URL = 'https://my.pingdom.com/probes/ipv4';

    /**
     * @Route("/monitoring/altares", name="monitoring_altares")
     *
     * @return Response
     */
    public function altaresAction()
    {
        if (false === in_array($_SERVER['REMOTE_ADDR'], $this->getPingdomIps())) {
            throw new NotFoundHttpException('Unknown page');
        }

        try {
            $wsMonitoringManager = $this->get('unilend.service.ws_monitoring_manager');
            $siren               = '790766034';
            $altaresManager      = $this->get('unilend.service.ws_client.altares_manager')->setUseCache(false);
            $altaresManager->getCompanyIdentity($siren);
            $altaresManager->getScore($siren);

            $response = $wsMonitoringManager->sendNotifications($wsMonitoringManager->getRateByCallStatus(AltaresManager::RESOURCE_COMPANY_IDENTITY), self::PINGDOM_FREQUENCY);
        } catch (\Exception $exception) {
            $this->get('logger')->error('Altares monitoring error: ' . $exception->getMessage());
            $response = $exception->getMessage();
        }

        return (new Response())->setContent($response);
    }

    /**
     * @Route("/monitoring/codinf", name="monitoring_codinf")
     *
     * @return Response
     */
    public function codinfAction()
    {
        if (false === in_array($_SERVER['REMOTE_ADDR'], $this->getPingdomIps())) {
            throw new NotFoundHttpException('Unknown page');
        }

        try {
            $wsMonitoringManager = $this->get('unilend.service.ws_monitoring_manager');
            $siren               = '790766034';
            $codinfManager       = $this->get('unilend.service.ws_client.codinf_manager')->setUseCache(false);
            $codinfManager->getIncidentList($siren);

            $response = $wsMonitoringManager->sendNotifications($wsMonitoringManager->getRateByCallStatus(CodinfManager::RESOURCE_INCIDENT_LIST), self::PINGDOM_FREQUENCY);
        } catch (\Exception $exception) {
            $this->get('logger')->error('Codinf monitoring error: ' . $exception->getMessage());
            $response = $exception->getMessage();
        }

        return (new Response())->setContent($response);
    }

    /**
     * @Route("/monitoring/euler", name="monitoring_euler")
     *
     * @return Response
     */
    public function eulerAction()
    {
        if (false === in_array($_SERVER['REMOTE_ADDR'], $this->getPingdomIps())) {
            throw new NotFoundHttpException('Unknown page');
        }

        try {
            $wsMonitoringManager = $this->get('unilend.service.ws_monitoring_manager');
            $siren               = '790766034';
            $eulerManager        = $this->get('unilend.service.ws_client.euler_manager')->setUseCache(false);
            $eulerManager->searchCompany($siren, 'fr');

            $response = $wsMonitoringManager->sendNotifications($wsMonitoringManager->getRateByCallStatus(EulerHermesManager::RESOURCE_SEARCH_COMPANY), self::PINGDOM_FREQUENCY);
        } catch (\Exception $exception) {
            $this->get('logger')->error('Euler monitoring error: ' . $exception->getMessage());
            $response = $exception->getMessage();
        }

        return (new Response())->setContent($response);
    }

    /**
     * Infogreffe was deactivated, so this method will always respond ok
     *
     * @Route("/monitoring/infogreffe", name="monitoring_infogreffe")
     *
     * @return Response
     */
    public function infogreffeAction()
    {
        if (false === in_array($_SERVER['REMOTE_ADDR'], $this->getPingdomIps())) {
            throw new NotFoundHttpException('Unknown page');
        }
        return (new Response())->setContent('ok');

        try {
            $wsMonitoringManager = $this->get('unilend.service.ws_monitoring_manager');
            $siren               = '790766034';
            $infogreffeManager   = $this->get('unilend.service.ws_client.infogreffe_manager')->setUseCache(false)->setMonitoring(true);
            $infogreffeManager->getIndebtedness($siren);

            $response = $wsMonitoringManager->sendNotifications($wsMonitoringManager->getRateByCallStatus(InfogreffeManager::RESOURCE_INDEBTEDNESS), self::PINGDOM_FREQUENCY);
        } catch (\Exception $exception) {
            $this->get('logger')->error('Infogreffe monitoring error: ' . $exception->getMessage());
            $response = $exception->getMessage();
        }

        return (new Response())->setContent($response);
    }

    /**
     * @Route("/monitoring/infolegale", name="monitoring_infolegale")
     *
     * @return Response
     */
    public function infolegaleAction()
    {
        if (false === in_array($_SERVER['REMOTE_ADDR'], $this->getPingdomIps())) {
            throw new NotFoundHttpException('Unknown page');
        }

        try {
            $wsMonitoringManager = $this->get('unilend.service.ws_monitoring_manager');
            $siren               = '790766034';
            $infolegaleManager   = $this->get('unilend.service.ws_client.infolegale_manager')->setUseCache(false);
            $infolegaleManager->getScore($siren);

            $response = $wsMonitoringManager->sendNotifications($wsMonitoringManager->getRateByCallStatus(InfolegaleManager::RESOURCE_COMPANY_SCORE), self::PINGDOM_FREQUENCY);
        } catch (\Exception $exception) {
            $this->get('logger')->error('Infolegale monitoring error: ' . $exception->getMessage());
            $response = $exception->getMessage();
        }

        return (new Response())->setContent($response);
    }

    /**
     * @Route("/monitoring/ellisphere", name="monitoring_ellisphere")
     *
     * @return Response
     */
    public function ellisphereAction()
    {
        if (false === in_array($_SERVER['REMOTE_ADDR'], $this->getPingdomIps())) {
            throw new NotFoundHttpException('Unknown page');
        }

        try {
            $wsMonitoringManager     = $this->get('unilend.service.ws_monitoring_manager');
            $siren                   = '790766034';
            $altaresManager          = $this->get('unilend.service.ws_client.ellisphere_manager')->setUseCache(false);
            $altaresManager->searchBySiren($siren);

            $response = $wsMonitoringManager->sendNotifications($wsMonitoringManager->getRateByCallStatus(EllisphereManager::RESOURCE_SEARCH), self::PINGDOM_FREQUENCY);
        } catch (\Exception $exception) {
            $this->get('logger')->error('Ellisphere monitoring error: ' . $exception->getMessage());
            $response = $exception->getMessage();
        }

        return (new Response())->setContent($response);
    }

    /**
     * @return array
     */
    private function getPingdomIps()
    {
        /** @var MemcacheCachePool $cachePool */
        $cachePool  = $this->get('memcache.default');
        $cachedItem = $cachePool->getItem(__FUNCTION__);

        if (true === $cachedItem->isHit()) {
            $ips = $cachedItem->get();
        } else {
            $ips = [];

            $curlSession = curl_init();
            curl_setopt($curlSession, CURLOPT_URL, self::PINGDOM_IPS_LIST_URL);
            curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);

            $content = curl_exec($curlSession);
            curl_close($curlSession);

            if (false === empty($content)) {
                $ip = strtok($content, "\n");

                while ($ip !== false) {
                    if (false !== filter_var($ip, FILTER_VALIDATE_IP)) {
                        $ips[] = $ip;
                    }

                    $ip = strtok("\n");
                }

                if (false === empty($ips)) {
                    $cachedItem->set($ips)->expiresAfter(CacheKeys::DAY);
                    $cachePool->save($cachedItem);
                }
            }
        }

        return $ips;
    }
}
