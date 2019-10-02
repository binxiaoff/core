<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{NoResultException, NonUniqueResultException};
use Unilend\Entity\{Clients, Project, ProjectParticipationContact};

/**
 * @method ProjectParticipationContact|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectParticipationContact|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectParticipationContact[]    findAll()
 * @method ProjectParticipationContact[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectParticipationContactRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectParticipationContact::class);
    }

    /**
     * @param Project $project
     * @param Clients $client
     *
     * @throws NonUniqueResultException
     *
     * @return ProjectParticipationContact|null
     */
    public function findByProjectAndClient(Project $project, Clients $client): ?ProjectParticipationContact
    {
        $queryBuilder = $this->createQueryBuilder('ppc')
            ->innerJoin('ppc.projectParticipation', 'pp')
            ->where('ppc.client = :client')
            ->andWhere('pp.project = :project')
            ->setParameters([
                'client'  => $client,
                'project' => $project,
            ])
            ->setMaxResults(1)
        ;

        try {
            $result = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $exception) {
            $result = null;
        }

        return $result;
    }
}
