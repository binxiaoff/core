<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\Clients;
use Unilend\Entity\UserAgent;
use UserAgentParser\Model\Browser;
use UserAgentParser\Model\Device;

/**
 * @method UserAgent|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserAgent|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserAgent[]    findAll()
 * @method UserAgent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserAgentRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAgent::class);
    }

    /**
     * @param Clients $client
     * @param Browser $browser
     * @param Device  $device
     *
     * @return UserAgent|null
     */
    public function findOneByClientAndBrowserAndDevice(
        Clients $client,
        Browser $browser,
        Device $device
    ): ?UserAgent {
        return $this->findOneBy([
            'client'         => $client,
            'browserName'    => $browser->getName(),
            'browserVersion' => $browser->getVersion()->getComplete() ?: null,
            'deviceModel'    => $device->getModel(),
            'deviceBrand'    => $device->getBrand(),
            'deviceType'     => $device->getType(),
        ])
        ;
    }
}
