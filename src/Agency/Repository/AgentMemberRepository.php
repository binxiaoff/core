<?php

declare(strict_types=1);

namespace Unilend\Agency\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Agency\Entity\AgentMember;

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
}
