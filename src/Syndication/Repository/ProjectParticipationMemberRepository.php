<?php

declare(strict_types=1);

namespace Unilend\Syndication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\TeamEdge;
use Unilend\Syndication\Entity\ProjectParticipation;
use Unilend\Syndication\Entity\ProjectParticipationMember;

/**
 * @method ProjectParticipationMember|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectParticipationMember|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectParticipationMember[]    findAll()
 * @method ProjectParticipationMember[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectParticipationMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectParticipationMember::class);
    }

    public function findActiveByManager(Staff $manager): array
    {
        if (false === $manager->isManager()) {
            return [];
        }

        return $this->createQueryBuilder('ppm')
            ->innerJoin('ppm.staff', 's')
            ->leftJoin(TeamEdge::class, 't', Join::WITH, 't.descendent = s.team')
            ->where('t.ancestor = :team OR s.team = :team')
            ->andWhere('ppm.archived is NULL')
            ->setParameter('team', $manager->getTeam())
            ->getQuery()
            ->getResult()
        ;
    }

    public function findActiveByProjectParticipationAndManagerAndPermissionEnabled(ProjectParticipation $projectParticipation, Staff $manager, int $permission = 0): array
    {
        if (false === $manager->isManager()) {
            return [];
        }

        return $projectParticipation->getProjectParticipationMembers()->filter(function (ProjectParticipationMember $projectParticipationMember) use ($permission, $manager) {
            return $projectParticipationMember->getPermissions()->has($permission) && false === $projectParticipationMember->isArchived()
                && (\in_array($projectParticipationMember->getStaff()->getTeam(), [...$manager->getTeam()->getDescendents(), $manager->getTeam()], true));
        })->toArray();
    }
}
