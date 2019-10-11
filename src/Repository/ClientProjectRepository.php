<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\{Clients, Project, ProjectParticipation};

/**
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientProjectRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * @param Clients $user
     *
     * @return array
     */
    public function getBorrowerSubmitterProjects(Clients $user): array
    {
        return $this
            ->createQueryBuilder('p')
            ->distinct()
            ->where('p.borrowerCompany = :userCompany')
            ->orWhere('p.submitterCompany = :userCompany')
            ->setParameter('userCompany', $user->getCompany())
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param Clients $user
     *
     * @return array
     */
    public function getArrangerRunProjects(Clients $user): array
    {
        return $this
            ->createQueryBuilder('p')
            ->distinct()
            ->innerJoin('p.projectParticipations', 'pp')
            ->where('pp.company = :userCompany')
            ->andWhere('JSON_CONTAINS(pp.roles, :roleArranger) = 1 OR JSON_CONTAINS(pp.roles, :roleRun) = 1')
            ->setParameter('userCompany', $user->getCompany())
            ->setParameter('roleArranger', json_encode([ProjectParticipation::DUTY_PROJECT_PARTICIPATION_ARRANGER]))
            ->setParameter('roleRun', json_encode([ProjectParticipation::DUTY_PROJECT_PARTICIPATION_RUN]))
            ->getQuery()
            ->getResult()
        ;
    }
}
