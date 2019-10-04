<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\{NoResultException, NonUniqueResultException};
use Unilend\Entity\{Project, ProjectParticipation, Staff};
use Unilend\Service\Staff\StaffManager;

/**
 * @method ProjectParticipation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectParticipation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectParticipation[]    findAll()
 * @method ProjectParticipation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectParticipationRepository extends ServiceEntityRepository
{
    /** @var StaffManager */
    private $staffManager;

    /**
     * @param ManagerRegistry $registry
     * @param StaffManager    $staffManager
     */
    public function __construct(ManagerRegistry $registry, StaffManager $staffManager)
    {
        $this->staffManager = $staffManager;
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
        $concernedRoles = $this->staffManager->getConcernedRoles($project->getMarketSegment());
        $queryBuilder   = $this->createQueryBuilder('pp')
            ->innerJoin(Staff::class, 's', Join::WITH, 'pp.company = s.company')
            ->where('pp.project = :project')
            ->andWhere('pp.company = :company')
            ->andWhere('JSON_CONTAINS(s.roles, :concernedRoles) = 1')
            ->andWhere('s.client = :client')
            ->setParameters([
                'project'        => $project,
                'company'        => $staff->getCompany(),
                'concernedRoles' => json_encode($concernedRoles, JSON_THROW_ON_ERROR),
                'client'         => $staff->getClient(),
            ])
            ->setMaxResults(1)
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
