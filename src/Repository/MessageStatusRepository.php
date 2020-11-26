<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Unilend\Entity\MessageStatus;

/**
 * @method MessageStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method MessageStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method MessageStatus[]    findAll()
 * @method MessageStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageStatusRepository extends ServiceEntityRepository
{
    /**
     * MessageStatusRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageStatus::class);
    }

    /**
     * @param MessageStatus $messageStatus
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(MessageStatus $messageStatus): void
    {
        $this->persist($messageStatus);
        $this->flush();
    }

    /**
     * @param MessageStatus $messageStatus
     *
     * @throws ORMException
     */
    public function persist(MessageStatus $messageStatus): void
    {
        $this->getEntityManager()->persist($messageStatus);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
