<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Entity\InvitationReplyVersion;

/**
 * @method InvitationReplyVersion|null find($id, $lockMode = null, $lockVersion = null)
 * @method InvitationReplyVersion|null findOneBy(array $criteria, array $orderBy = null)
 * @method InvitationReplyVersion[]    findAll()
 * @method InvitationReplyVersion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvitationReplyVersionRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvitationReplyVersion::class);
    }
}
