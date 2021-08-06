<?php

declare(strict_types=1);

namespace Unilend\Syndication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Syndication\Entity\ProjectOrganizer;

/**
 * @method ProjectOrganizer|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectOrganizer|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectOrganizer[]    findAll()
 * @method ProjectOrganizer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectOrganizerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectOrganizer::class);
    }
}
