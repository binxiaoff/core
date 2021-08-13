<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Core\Entity\User;
use KLS\Syndication\Agency\Entity\AgentMember;
use KLS\Syndication\Agency\Entity\Project;

/**
 * @method AgentMemberRepository|null find($id, $lockMode = null, $lockVersion = null)
 * @method AgentMemberRepository|null findOneBy(array $criteria, array $orderBy = null)
 * @method AgentMemberRepository[]    findAll()
 * @method AgentMemberRepository[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AgentMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, AgentMember::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByProjectAndUserAndActive(Project $project, User $user): ?AgentMember
    {
        return $this->createQueryBuilder('am')
            ->innerJoin('am.agent', 'a')
            ->where('a.project = :project')
            ->andWhere('am.user = :user')
            ->andWhere('am.archivingDate IS NULL')
            ->setParameters([
                'user'    => $user,
                'project' => $project,
            ])
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
