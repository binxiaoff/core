<?php

declare(strict_types=1);

namespace KLS\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Core\Entity\HubspotContact;

/**
 * @method HubspotContact|null find($id, $lockMode = null, $lockVersion = null)
 * @method HubspotContact|null findOneBy(array $criteria, array $orderBy = null)
 * @method HubspotContact[]    findAll()
 * @method HubspotContact[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HubspotContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HubspotContact::class);
    }

    /**
     * @throws ORMException
     */
    public function persist(HubspotContact $hubspotContact): void
    {
        $this->getEntityManager()->persist($hubspotContact);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
