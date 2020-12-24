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
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectParticipationMember::class);
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

        return $this->createQueryBuilder('ppm')
            ->innerJoin('ppm.staff', 's')
            ->innerJoin('s.team', 't')
            ->innerJoin('t.descendentsEdges', 'de')
            ->where('de.team = :team OR s.team = :team')
            ->setParameter('team', $manager->getTeam())
            ->getQuery()
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
        return $projectParticipation->getProjectParticipationMembers()->filter(function (ProjectParticipationMember $projectParticipationMember) use ($permission, $manager) {
            return $projectParticipationMember->getPermissions()->has($permission) &&
                (in_array($projectParticipationMember->getStaff()->getTeam(), [...$manager->getTeam()->getDescendents(), $manager->getTeam()], true));
        })->toArray();
    }
}
