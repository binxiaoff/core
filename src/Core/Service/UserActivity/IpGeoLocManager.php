<?php

declare(strict_types=1);

namespace Unilend\Core\Service\UserActivity;

use Cravler\MaxMindGeoIpBundle\Service\GeoIpService;
use Exception;
use GeoIp2\Model\City;
use Psr\Log\LoggerInterface;

class IpGeoLocManager
{
    public const DATABASE_TYPE = 'city';

    /** @var GeoIpService */
    private $geoIpService;
    /** @var string */
    private $defaultLocal;
    /** @var LoggerInterface */
    private $logger;

    /**
     * IpGeoLocManager constructor.
     *
     * @param GeoIpService    $geoIpService
     * @param string          $defaultLocale
     * @param LoggerInterface $logger
     */
    public function __construct(GeoIpService $geoIpService, string $defaultLocale, LoggerInterface $logger)
    {
        $this->geoIpService = $geoIpService;
        $this->defaultLocal = $defaultLocale;
        $this->logger       = $logger;
    }

    /**
     * @param string $ip
     *
     * @return City|null
     */
    public function getGeoIpRecord(string $ip): ?City
    {
        try {
            return $this->geoIpService->getRecord($ip, self::DATABASE_TYPE, [$this->defaultLocal]);
        } catch (Exception $exception) {
            $this->logger->warning('Could not initialize IP GeoLoc service. Error: ' . $exception->getMessage(), [
                'client_ip' => $ip,
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
            ]);

            return null;
        }
    }
}
