<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\MessageFile;

/**
 * @method MessageFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method MessageFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method MessageFile[]    findAll()
 * @method MessageFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageFileRepository extends ServiceEntityRepository
{
    /**
     * MessageFileRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageFile::class);
    }
}

