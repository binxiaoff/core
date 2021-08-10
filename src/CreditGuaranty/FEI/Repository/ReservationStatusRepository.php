<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;

/**
 * @method ReservationStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReservationStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReservationStatus[]    findAll()
 * @method ReservationStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, ReservationStatus::class);
    }
}
