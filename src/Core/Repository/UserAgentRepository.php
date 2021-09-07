<?php

declare(strict_types=1);

namespace KLS\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Core\Entity\User;
use KLS\Core\Entity\UserAgent;
use WhichBrowser\Model\Browser;
use WhichBrowser\Model\Device;
use WhichBrowser\Model\Version;

/**
 * @method UserAgent|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserAgent|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserAgent[]    findAll()
 * @method UserAgent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserAgentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAgent::class);
    }

    public function findOneByUserAndBrowserAndDevice(
        User $user,
        Browser $browser,
        Device $device
    ): ?UserAgent {
        return $this->findOneBy([
            'user'           => $user,
            'browserName'    => $browser->name,
            'browserVersion' => ($browser->version instanceof Version) ? $browser->version->toString() : null,
            'deviceModel'    => $device->model,
            'deviceBrand'    => $device->getManufacturer() ?: null,
            'deviceType'     => $device->type,
        ])
        ;
    }
}
