<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Core\Entity\{Message, MessageStatus, MessageThread, Staff};

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
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function setMessageStatusesToRead(Staff $recipient, MessageThread $messageThread)
    {
        // @todo set update query with subquery instead
        $messageStatuses = $this->createQueryBuilder('msgst')
            ->innerJoin(Message::class, 'msg', Join::WITH, 'msgst.message = msg.id')
            ->andWhere('msgst.recipient = :recipient')
            ->andWhere('msgst.status = :current_status')
            ->andWhere('msg.messageThread = :message_thread')
            ->setParameters([
                'recipient'      => $recipient,
                'current_status' => MessageStatus::STATUS_UNREAD,
                'message_thread' => $messageThread->getId(),
            ])
            ->getQuery()
            ->getResult();
        foreach ($messageStatuses as $messageStatus) {
            $messageStatus
                ->setStatus(MessageStatus::STATUS_READ)
                ->setUpdated(new DateTimeImmutable());
            $this->persist($messageStatus);
        }
        $this->flush();
    }
}
