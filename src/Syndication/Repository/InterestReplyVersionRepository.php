<?php

declare(strict_types=1);

namespace KLS\Syndication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Syndication\Entity\InterestReplyVersion;

/**
 * @method InterestReplyVersion|null find($id, $lockMode = null, $lockVersion = null)
 * @method InterestReplyVersion|null findOneBy(array $criteria, array $orderBy = null)
 * @method InterestReplyVersion[]    findAll()
 * @method InterestReplyVersion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InterestReplyVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InterestReplyVersion::class);
    }
}
