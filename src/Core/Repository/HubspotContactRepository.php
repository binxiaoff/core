<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Core\Entity\HubspotContact;

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
}
