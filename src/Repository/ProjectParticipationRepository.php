<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{Project, ProjectParticipation, Staff};

/**
 * @method ProjectParticipation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectParticipation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectParticipation[]    findAll()
 * @method ProjectParticipation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectParticipationRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectParticipation::class);
    }

    /**
     * @param Project $project
     * @param Staff   $staff
     *
     * @throws NonUniqueResultException
     *
     * @return ProjectParticipation|null
     */
    public function findByStaff(Project $project, Staff $staff): ?ProjectParticipation
    {
        $queryBuilder = $this->createQueryBuilder('pp')
            ->innerJoin(Staff::class, 's', Join::WITH, 'pp.company = s.company')
            ->where('pp.project = :project')
            ->andWhere('pp.company = :company')
            ->andWhere('p.marketSegment MEMBER OF s.marketSegments')
            ->andWhere('s.client = :client')
            ->setParameters([
                'project' => $project,
                'company' => $staff->getCompany(),
                'client'  => $staff->getClient(),
            ])
            ->setMaxResults(1)
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
