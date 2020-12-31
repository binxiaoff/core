<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Core\Entity\{MessageStatus, MessageThread, Staff};

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
        $this->getEntityManager()->persist($messageStatus);
        $this->getEntityManager()->flush();
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

    /**
     * @param Staff         $recipient
     * @param MessageThread $messageThread
     */
    public function setMessageStatusesToRead(Staff $recipient, MessageThread $messageThread): void
    {
        // Doctrine update doesn't support inner joint, so we do a sub-query here.
        $messageStatusToBeUpdated = $this->createQueryBuilder('msgst')
            ->select('msgst.id')
            ->innerJoin('msgst.message', 'msg')
            ->where('msgst.recipient = :recipient')
            ->andWhere('msgst.status = :unread')
            ->andWhere('msg.messageThread = :messageThread')
            ->setParameters([
                'recipient'     => $recipient,
                'unread'        => MessageStatus::STATUS_UNREAD,
                'messageThread' => $messageThread,
            ])
            ->getQuery()->getArrayResult(); //Cannot use sub DQL query directly because of MySQL 1093 error (We could do a deeper nested query, but it doesn't worth it).

        $this->createQueryBuilder('ms')->update()
            ->set('ms.status', ':read')
            ->set('ms.updated', 'NOW()')
            ->where('ms.id IN (:unreadMessageStatus)')
            ->setParameters([
                'read'                => MessageStatus::STATUS_READ,
                'unreadMessageStatus' => $messageStatusToBeUpdated,
            ])
            ->getQuery()
            ->execute();
    }
}
