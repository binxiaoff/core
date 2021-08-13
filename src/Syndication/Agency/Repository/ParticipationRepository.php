<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Syndication\Agency\Entity\Participation;

/**
 * @method ParticipationRepository|null find($id, $lockMode = null, $lockVersion = null)
 * @method ParticipationRepository|null findOneBy(array $criteria, array $orderBy = null)
 * @method ParticipationRepository[]    findAll()
 * @method ParticipationRepository[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Participation::class);
    }
}
