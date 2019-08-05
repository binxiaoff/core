<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\Loans;

/**
 * @method Loans|null find($id, $lockMode = null, $lockVersion = null)
 * @method Loans|null findOneBy(array $criteria, array $orderBy = null)
 * @method Loans[]    findAll()
 * @method Loans[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoansRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Loans::class);
    }
}
