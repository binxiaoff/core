<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Unilend\Entity\{Project, ProjectParticipationMember, Staff};

/**
 * @method ProjectParticipationMember|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectParticipationMember|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectParticipationMember[]    findAll()
 * @method ProjectParticipationMember[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectParticipationMemberRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectParticipationMember::class);
    }

    /**
     * @param Project $project
     * @param Staff   $staff
     *
     * @throws NonUniqueResultException
     *
     * @return ProjectParticipationMember|null
     */
    public function findByProjectAndStaff(Project $project, Staff $staff): ?ProjectParticipationMember
    {
        $queryBuilder = $this->createQueryBuilder('ppc')
            ->innerJoin('ppc.projectParticipation', 'pp')
            ->where('ppc.staff = :staff')
            ->andWhere('pp.company = :company')
            ->andWhere('pp.project = :project')
            ->setParameters([
                'staff'   => $staff,
                'project' => $project,
                'company' => $staff->getCompany(),
            ])
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
