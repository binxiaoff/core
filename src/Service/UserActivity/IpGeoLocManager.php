<?php

declare(strict_types=1);

namespace Unilend\Service\UserActivity;

use Cravler\MaxMindGeoIpBundle\Service\GeoIpService;
use Exception;
use GeoIp2\Model\City;
use Psr\Log\LoggerInterface;
use Unilend\Entity\ClientSuccessfulLogin;

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
     * @param ClientSuccessfulLogin $clientLoginHistory
     * @param string                $ip
     *
     * @return ClientSuccessfulLogin
     */
    public function setClientLoginLocation(ClientSuccessfulLogin $clientLoginHistory, string $ip): ClientSuccessfulLogin
    {
        $geoIp = $this->getGeoIpRecord($ip);

        if (null !== $geoIp) {
            $clientLoginHistory->setCountryIsoCode($geoIp->country->isoCode);
            $clientLoginHistory->setCity($geoIp->city->name);
        }

        return $clientLoginHistory;
    }

    /**
     * @param string $ip
     *
     * @return City|null
     */
    private function getGeoIpRecord(string $ip): ?City
    {
        try {
            return $this->geoIpService->getRecord($ip, self::DATABASE_TYPE, [$this->defaultLocal]);
        } catch (Exception $exception) {
            $this->logger->error('Could not initialize IP GeoLoc service. Error: ' . $exception->getMessage(), [
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
