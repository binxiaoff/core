<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\FeeType;

/**
 * @method FeeType|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeeType|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeeType[]    findAll()
 * @method FeeType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeeTypeRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeeType::class);
    }
}
