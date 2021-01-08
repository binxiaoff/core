<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\{NoResultException, NonUniqueResultException, ORMException, OptimisticLockException};
use Unilend\Core\Entity\{Message, MessageStatus, MessageThread, Staff, User};
use Unilend\Syndication\Entity\{Project, ProjectParticipation, ProjectStatus};

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
     * @param User              $user
     * @param DateTimeImmutable $from
     * @param DateTimeImmutable $to
     */
    public function setMessageStatusesToNotified(User $user, DateTimeImmutable $from, DateTimeImmutable $to): void
    {
        $messageStatusToBeNotified = $this->createQueryBuilder('msgst')
            ->select('msgst.id')
            ->innerJoin(Message::class, 'msg', Join::WITH, 'msgst.message = msg.id')
            ->innerJoin(MessageThread::class, 'msgtd', Join::WITH, 'msg.messageThread = msgtd.id')
            ->innerJoin(ProjectParticipation::class, 'pp', Join::WITH, 'msgtd.projectParticipation = pp.id')
            ->innerJoin(Project::class, 'p', Join::WITH, 'pp.project = p.id')
            ->innerJoin(ProjectStatus::class, 'pst', Join::WITH, 'p.currentStatus = pst.id')
            ->innerJoin(Staff::class, 'stf', Join::WITH, 'msgst.recipient = stf.id')
            ->innerJoin(User::class, 'u', Join::WITH, 'stf.user = u.id')
            ->where('msgst.status = :status')
            ->andWhere('msgst.added BETWEEN :from AND :to')
            ->andWhere('pst.status > :project_current_status')
            ->andWhere('u.id = :user_id')
            ->setParameters([
                'status'                 => MessageStatus::STATUS_UNREAD,
                'from'                   => $from->format('Y-m-d H:i:s'),
                'to'                     => $to->format('Y-m-d H:i:s'),
                'project_current_status' => ProjectStatus::STATUS_DRAFT,
                'user_id'                => $user->getId(),
            ])
            ->getQuery()->getArrayResult();

        $this->createQueryBuilder('msgst')->update()
            ->set('msgst.notified', 'NOW()')
            ->set('msgst.updated', 'NOW()')
            ->where('msgst.id IN (:messageStatusToBeNotified)')
            ->setParameter('messageStatusToBeNotified', $messageStatusToBeNotified)
            ->getQuery()
            ->execute();
    }

    /**
     * @param DateTimeImmutable $from
     * @param DateTimeImmutable $to
     * @param int               $limit
     * @param int               $offset
     *
     * @return array
     */
    public function getTotalUnreadMessageByRecipientForDateBetween(DateTimeImmutable $from, DateTimeImmutable $to, int $limit, int $offset): array
    {
        return $this->createQueryBuilder('msgst')
            ->select(['DISTINCT(u.id) AS user_id', 'COUNT(msgst.id) AS nb_messages_unread'])
            ->innerJoin(Message::class, 'msg', Join::WITH, 'msgst.message = msg.id')
            ->innerJoin(MessageThread::class, 'msgtd', Join::WITH, 'msg.messageThread = msgtd.id')
            ->innerJoin(ProjectParticipation::class, 'pp', Join::WITH, 'msgtd.projectParticipation = pp.id')
            ->innerJoin(Project::class, 'p', Join::WITH, 'pp.project = p.id')
            ->innerJoin(ProjectStatus::class, 'pst', Join::WITH, 'p.currentStatus = pst.id')
            ->innerJoin(Staff::class, 'stf', Join::WITH, 'msgst.recipient = stf.id')
            ->innerJoin(User::class, 'u', Join::WITH, 'stf.user = u.id')
            ->where('msgst.status = :status')
            ->andWhere('msgst.added BETWEEN :from AND :to')
            ->andWhere('pst.status > :project_current_status')
            ->andWhere('msgst.notified IS NULL')
            ->setParameters([
                'status'                 => MessageStatus::STATUS_UNREAD,
                'from'                   => $from->format('Y-m-d H:i:s'),
                'to'                     => $to->format('Y-m-d H:i:s'),
                'project_current_status' => ProjectStatus::STATUS_DRAFT,
            ])
            ->groupBy('u.id')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param DateTimeImmutable $from
     * @param DateTimeImmutable $to
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     *
     * @return int
     */
    public function countTotalRecipientUnreadMessageForDateBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        $queryBuilder = $this->createQueryBuilder('msgst');
        $queryBuilder
            ->select('COUNT(DISTINCT(u.id))')
            ->innerJoin(Message::class, 'msg', Join::WITH, 'msgst.message = msg.id')
            ->innerJoin(MessageThread::class, 'msgtd', Join::WITH, 'msg.messageThread = msgtd.id')
            ->innerJoin(ProjectParticipation::class, 'pp', Join::WITH, 'msgtd.projectParticipation = pp.id')
            ->innerJoin(Project::class, 'p', Join::WITH, 'pp.project = p.id')
            ->innerJoin(ProjectStatus::class, 'pst', Join::WITH, 'p.currentStatus = pst.id')
            ->innerJoin(Staff::class, 'stf', Join::WITH, 'msgst.recipient = stf.id')
            ->innerJoin(User::class, 'u', Join::WITH, 'stf.user = u.id')
            ->where('msgst.status = :status')
            ->andWhere('msgst.added BETWEEN :from AND :to')
            ->andWhere('pst.status > :project_current_status')
            ->andWhere('msgst.notified IS NULL')
            ->setParameters([
                'status'                 => MessageStatus::STATUS_UNREAD,
                'from'                   => $from->format('Y-m-d H:i:s'),
                'to'                     => $to->format('Y-m-d H:i:s'),
                'project_current_status' => ProjectStatus::STATUS_DRAFT,
            ]);

        return (int) $queryBuilder
            ->getQuery()
            ->getSingleScalarResult()
        ;
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
