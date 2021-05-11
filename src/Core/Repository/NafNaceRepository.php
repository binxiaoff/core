<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Core\Entity\NafNace;

/**
 * @method NafNace|null find($id, $lockMode = null, $lockVersion = null)
 * @method NafNace|null findOneBy(array $criteria, array $orderBy = null)
 * @method NafNace[]    findAll()
 * @method NafNace[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NafNaceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, NafNace::class);
    }
}
