<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\{NonUniqueResultException, ORMException, OptimisticLockException};
use Unilend\Entity\{Clients, Project, ProjectParticipation, Staff};

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
     * @param ProjectParticipation $projectParticipation
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectParticipation $projectParticipation): void
    {
        $this->getEntityManager()->persist($projectParticipation);
        $this->getEntityManager()->flush();
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

    /**
     * @param Project $project
     * @param Clients $clients
     *
     * @throws NonUniqueResultException
     *
     * @return ProjectParticipation
     */
    public function findByProjectAndClient(Project $project, Clients $clients): ?ProjectParticipation
    {
        return
            // Check if there is a project participation contact
            $this->createQueryBuilder('pp')
                ->innerJoin('pp.projectParticipationContacts', 'ppc')
                ->where('pp.project = :project')
                ->andWhere('ppc.client = :client')
                ->setParameters(['project' => $project, 'client' => $clients])
                ->getQuery()
                ->getOneOrNullResult() ?? // Check if is a project participation with the user company
            $this->createQueryBuilder('pp')
                ->where('pp.project = :project')
                ->andWhere('pp.company = :company')
                ->setParameters(['project' => $project, 'company' => $clients->getCompany()])
                ->getQuery()
                ->getOneOrNullResult()
        ;
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectParticipation $projectParticipation): void
    {
        $this->getEntityManager()->persist($projectParticipation);
        $this->getEntityManager()->flush($projectParticipation);
    }
}
