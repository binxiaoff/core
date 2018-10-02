<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\UserActivity;

use Cravler\MaxMindGeoIpBundle\Service\GeoIpService;
use Psr\Log\LoggerInterface;

class IpGeoLocManager
{
    const DATABASE_TYPE = 'city';

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
     * @return array|null
     */
    public function getCountryAndCity(string $ip): ?array
    {
        $geoIp = $this->getGeoIpRecord($ip);
        if (null !== $geoIp) {
            return [
                'countryIsoCode' => $geoIp->country->isoCode,
                'city'           => $geoIp->city->name
            ];
        }

        return null;
    }

    /**
     * @param string
     *
     * @return \GeoIp2\Model\City|null
     */
    private function getGeoIpRecord(string $ip)
    {
        try {
            return $this->geoIpService->getRecord($ip, self::DATABASE_TYPE, [$this->defaultLocal]);
        } catch (\Exception $exception) {
            $this->logger->error('Could not initialize IP GeoLoc service. Error: ' . $exception->getMessage(), [
                'client_ip' => $ip,
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);

            return null;
        }
    }
}
