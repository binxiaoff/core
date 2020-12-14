<?php

declare(strict_types=1);

namespace Unilend\Syndication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Repository\TeamRepository;
use Unilend\Syndication\Entity\{Project, ProjectParticipation, ProjectParticipationMember};

/**
 * @method ProjectParticipationMember|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectParticipationMember|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectParticipationMember[]    findAll()
 * @method ProjectParticipationMember[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectParticipationMemberRepository extends ServiceEntityRepository
{
    /** @var TeamRepository */
    private TeamRepository $teamRepository;

    /**
     * @param ManagerRegistry $registry
     * @param TeamRepository  $teamRepository
     */
    public function __construct(ManagerRegistry $registry, TeamRepository $teamRepository)
    {
        parent::__construct($registry, ProjectParticipationMember::class);
        $this->teamRepository = $teamRepository;
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
            ->andWhere('pp.participant = :company')
            ->andWhere('pp.project = :project')
            ->setParameters([
                'staff'   => $staff,
                'project' => $project,
                'company' => $staff->getCompany(),
            ])
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Staff $manager
     *
     * @return array
     */
    public function findByManager(Staff $manager): array
    {
        if (false === $manager->isManager()) {
            return [];
        }

        $resultSetMapping = $this->createResultSetMappingBuilder('ppm');

        $resultSetMapping->addRootEntityFromClassMetadata(ProjectParticipationMember::class, 'ppm');

        $select = $resultSetMapping->generateSelectClause();

        $cteName = 'tree';

        $cte = $this->teamRepository->getSubtreeTableCommonTableExpression($manager->getTeam(), 'tree');

        $sql = <<<SQL
{$cte} SELECT {$select} FROM syndication_project_participation_member ppm
     INNER JOIN core_staff s ON ppm.id_staff = s.id
     INNER JOIN core_staff_status ss on s.id = ss.id_staff
     INNER JOIN {$cteName} t ON t.id = s.id_team
     WHERE ss.status > 0
     AND ppm.archived IS NULL
SQL;

        return $this->getEntityManager()
            ->createNativeQuery($sql, $resultSetMapping)
            ->setResultSetMapping($resultSetMapping)
            ->getResult();
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Staff                $manager
     * @param int                  $permission
     *
     * @return array
     */
    public function findByProjectParticipationAndManagerAndPermissionEnabled(ProjectParticipation $projectParticipation, Staff $manager, int $permission = 0): array
    {
        if (false === $manager->isManager()) {
            return [];
        }

        $resultSetMapping = $this->createResultSetMappingBuilder('ppm');

        $resultSetMapping->addRootEntityFromClassMetadata(ProjectParticipationMember::class, 'ppm');

        $select = $resultSetMapping->generateSelectClause();


        $cteName = 'tree';

        $cte = $this->teamRepository->getSubtreeTableCommonTableExpression($manager->getTeam(), 'tree');

        $sql = <<<SQL
{$cte} SELECT {$select} FROM syndication_project_participation_member ppm
     INNER JOIN core_staff s ON ppm.id_staff = s.id
     INNER JOIN core_staff_status ss on s.id = ss.id_staff
     INNER JOIN {$cteName} t ON t.id = s.id_team
     WHERE ppm.id_project_participation = :project_participation
     AND ppm.permissions & :permission = :permission
     AND ss.status > 0
     AND ppm.archived IS NULL
SQL;

        return $this->getEntityManager()
            ->createNativeQuery($sql, $resultSetMapping)
            ->setParameter('project_participation', $projectParticipation->getId())
            ->setParameter('permission', $permission)
            ->setResultSetMapping($resultSetMapping)
            ->getResult();
    }
}
