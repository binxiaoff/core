<?php

declare(strict_types=1);

namespace KLS\Syndication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Syndication\Entity\InvitationReplyVersion;

/**
 * @method InvitationReplyVersion|null find($id, $lockMode = null, $lockVersion = null)
 * @method InvitationReplyVersion|null findOneBy(array $criteria, array $orderBy = null)
 * @method InvitationReplyVersion[]    findAll()
 * @method InvitationReplyVersion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvitationReplyVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvitationReplyVersion::class);
    }
}
