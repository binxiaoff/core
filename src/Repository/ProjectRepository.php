<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Unilend\Entity\Project;

class ProjectRepository extends ServiceEntityRepository
{
    /**
     * ProjectRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Project::class);
    }
}
