<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\TeamEdge;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationMember;

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
}
