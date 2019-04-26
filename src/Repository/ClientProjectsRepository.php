<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\{Clients, ProjectParticipant, Projects};

class ClientProjectsRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Projects::class);
    }

    /**
     * @param Clients $user
     *
     * @return array
     */
    public function getBorrowerProjects(Clients $user): array
    {
        return $this
            ->createQueryBuilder('p')
            ->distinct()
            ->where('p.idCompany = :userCompany')
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
    public function getSubmitterProjects(Clients $user): array
    {
        return $this
            ->createQueryBuilder('p')
            ->distinct()
            ->where('p.idCompanySubmitter = :userCompany')
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
            ->innerJoin('p.projectParticipants', 'pp')
            ->where('pp.company = :userCompany')
            ->andWhere('JSON_CONTAINS(pp.roles, :roleArranger) = 1 OR JSON_CONTAINS(pp.roles, :roleRun) = 1')
            ->setParameter('userCompany', $user->getCompany())
            ->setParameter('roleArranger', json_encode([ProjectParticipant::COMPANY_ROLE_ARRANGER]))
            ->setParameter('roleRun', json_encode([ProjectParticipant::COMPANY_ROLE_RUN]))
            ->getQuery()
            ->getResult()
        ;
    }
}
