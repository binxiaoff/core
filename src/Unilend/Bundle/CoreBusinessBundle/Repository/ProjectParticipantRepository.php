<?php
declare(strict_types=1);

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectParticipant;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;

class ProjectParticipantRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
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
