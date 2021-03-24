<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Core\Entity\MessageThread;

/**
 * @method MessageThread|null find($id, $lockMode = null, $lockVersion = null)
 * @method MessageThread|null findOneBy(array $criteria, array $orderBy = null)
 * @method MessageThread[]    findAll()
 * @method MessageThread[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageThreadRepository extends ServiceEntityRepository
{
    /**
     * MessageThreadRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageThread::class);
    }

    /**
     * @param MessageThread $messageThread
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(MessageThread $messageThread): void
    {
        $this->getEntityManager()->persist($messageThread);
        $this->getEntityManager()->flush();
    }
}
