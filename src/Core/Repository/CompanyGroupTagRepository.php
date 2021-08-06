<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Core\Entity\CompanyGroupTag;

/**
 * @method CompanyGroupTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method CompanyGroupTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method CompanyGroupTag[]    findAll()
 * @method CompanyGroupTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyGroupTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyGroupTag::class);
    }
}
