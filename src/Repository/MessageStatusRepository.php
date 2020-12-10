<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\{MessageStatus, MessageThread, Staff};

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
     */
    public function save(MessageStatus $messageStatus): void
    {
        $this->persist($messageStatus);
        $this->flush();
    }

    /**
     * @param Staff         $recipient
     * @param MessageThread $messageThread
     *
     * @return int|mixed|string
     */
    public function findUnreadStatusByRecipientAndMessageThread(Staff $recipient, MessageThread $messageThread)
    {
        $queryBuilder = $this->createQueryBuilder('msgst');

        return $queryBuilder
            ->innerJoin('msgst.message', 'msg')
            ->innerJoin('msg.messageThread', 'msgthd')
            ->where($queryBuilder->expr()->eq('msgst.recipient', ':recipient'))
            ->andWhere($queryBuilder->expr()->eq('msg.messageThread', ':message_thread'))
            ->andWhere($queryBuilder->expr()->eq('msgst.status', ':message_status_status'))
            ->setParameters([
                'recipient'             => $recipient,
                'message_thread'        => $messageThread,
                'message_status_status' => MessageStatus::STATUS_UNREAD,
            ])
            ->getQuery()
            ->getResult();
    }
}
