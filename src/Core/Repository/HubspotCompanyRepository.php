<?php

declare(strict_types=1);

namespace KLS\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Core\Entity\HubspotCompany;

/**
 * @method HubspotCompany|null find($id, $lockMode = null, $lockVersion = null)
 * @method HubspotCompany|null findOneBy(array $criteria, array $orderBy = null)
 * @method HubspotCompany[]    findAll()
 * @method HubspotCompany[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HubspotCompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HubspotCompany::class);
    }
}
