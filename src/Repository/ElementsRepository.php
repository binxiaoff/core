<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\Elements;

/**
 * @method Elements|null find($id, $lockMode = null, $lockVersion = null)
 * @method Elements|null findOneBy(array $criteria, array $orderBy = null)
 * @method Elements[]    findAll()
 * @method Elements[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ElementsRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Elements::class);
    }
}
