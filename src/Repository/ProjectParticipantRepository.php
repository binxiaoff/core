<?php
declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\{ProjectParticipant, Projects};

class ProjectParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectParticipant::class);
    }

    /**
     * @param Projects $project
     * @param string   $role
     *
     * @return ProjectParticipant[]
     */
    public function findByProjectAndRole(Projects $project, string $role): array
    {
        $queryBuilder = $this->createQueryBuilder('pp');

        $queryBuilder->where('pp.project = :project')
            ->andWhere('JSON_CONTAINS(pp.roles, :role) = 1')
            ->setParameters(['project' => $project, 'role' => json_encode([$role])]);

        return $queryBuilder->getQuery()->getResult();
    }
}
