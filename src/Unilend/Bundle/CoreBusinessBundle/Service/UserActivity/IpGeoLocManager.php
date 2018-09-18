<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\UserActivity;

use Cravler\MaxMindGeoIpBundle\Service\GeoIpService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Unilend\Bundle\CoreBusinessBundle\Entity\Pays;

class IpGeoLocManager
{
    const DATABASE_TYPE = 'city';

    /** @var GeoIpService */
    private $geoIpService;
    /** @var string */
    private $defaultLocal;
    /** @var RequestStack */
    private $requestStack;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * IpGeoLocManager constructor.
     *
     * @param GeoIpService           $geoIpService
     * @param string                 $defaultLocale
     * @param RequestStack           $requestStack
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(GeoIpService $geoIpService, string $defaultLocale, RequestStack $requestStack, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->geoIpService  = $geoIpService;
        $this->defaultLocal  = $defaultLocale;
        $this->requestStack  = $requestStack;
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
    }

    /**
     * @param string|null $ip
     *
     * @return string|null
     */
    public function getCountryCode(?string $ip = null): ?string
    {
        if ($geoIp = $this->getGeoIpRecord($ip)) {
            return $geoIp->country->isoCode;
        }

        return null;
    }

    /**
     * @param string|null $ip
     *
     * @return Pays|null
     */
    public function getCountry(?string $ip = null): ?Pays
    {
        $countryIsoCode = $this->getCountryCode($ip);

        if ($countryIsoCode) {
            return $this->entityManager->getRepository('UnilendCoreBusinessBundle:Pays')->findOneBy(['iso' => $countryIsoCode]);
        }

        return null;
    }

    /**
     * @param string|null $ip
     *
     * @return string|null
     */
    public function getCity(?string $ip = null)
    {
        if ($geoIp = $this->getGeoIpRecord($ip)) {
            return $geoIp->city->name;
        }

        return null;
    }

    /**
     * @param string|null $ip
     *
     * @return array|null
     */
    public function getCountryAndCity(?string $ip = null): ?array
    {
        if ($geoIp = $this->getGeoIpRecord($ip)) {
            return [
                'countryIsoCode' => $geoIp->country->isoCode,
                'city'           => $geoIp->city->name
            ];
        }

        return null;
    }

    /**
     * @param string|null
     *
     * @return \GeoIp2\Model\City|bool
     */
    private function getGeoIpRecord(?string $ip = null)
    {
        if (null === $ip && null !== $this->requestStack && null !== $this->requestStack->getCurrentRequest()) {
            $ip = $this->requestStack->getCurrentRequest()->getClientIp();
        }

        try {
            if (null !== $ip) {
                return $this->geoIpService->getRecord($ip, self::DATABASE_TYPE, [$this->defaultLocal]);
            }
        } catch (\Exception $exception) {
            $this->logger->error('Could not initialize IP GeoLoc service. Error: ' . $exception->getMessage(), [
                'client_ip' => $this->requestStack->getCurrentRequest()->getClientIp(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);
        }

        return false;
    }
}
