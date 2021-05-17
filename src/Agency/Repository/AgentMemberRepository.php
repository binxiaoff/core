<?php

declare(strict_types=1);

namespace Unilend\Agency\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Agency\Entity\AgentMember;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\User;

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
    public function findOneByProjectAndUser(Project $project, User $user): ?AgentMember
    {
        return $this->createQueryBuilder('am')
            ->innerJoin('am.agent', 'a')
            ->where('a.project = :project')
            ->andWhere('am.user = :user')
            ->setParameters([
                'user'    => $user,
                'project' => $project,
            ])
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
