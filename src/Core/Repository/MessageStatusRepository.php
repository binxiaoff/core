<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\{NoResultException, NonUniqueResultException, ORMException, OptimisticLockException};
use Unilend\Core\Entity\{MessageStatus, MessageThread, Staff};
use Unilend\Syndication\Entity\ProjectStatus;

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
     * @param int               $userId
     * @param DateTimeImmutable $from
     * @param DateTimeImmutable $to
     */
    public function setMessageStatusesToUnreadNotified(int $userId, DateTimeImmutable $from, DateTimeImmutable $to): void
    {
        $messageStatusToBeNotified = $this->getQueryBuilderForPeriod($from, $to)
            ->andWhere('stf.user = :user_id')
            ->setParameter('user_id', $userId)
            ->getQuery()->getArrayResult();

        $this->createQueryBuilder('msgst')->update()
            ->set('msgst.unreadNotified', 'NOW()')
            ->set('msgst.updated', 'NOW()')
            ->where('msgst.id IN (:messageStatusToBeNotified)')
            ->setParameter('messageStatusToBeNotified', $messageStatusToBeNotified)
            ->getQuery()
            ->execute();
    }

    /**
     * @param DateTimeImmutable $from
     * @param DateTimeImmutable $to
     * @param int|null          $limit
     * @param int|null          $offset
     *
     * @return array
     */
    public function countUnreadMessageByRecipentForPeriod(DateTimeImmutable $from, DateTimeImmutable $to, int $limit = null, int $offset = null): array
    {
        $queryBuilder = $this->getQueryBuilderForPeriod($from, $to)
            ->select('DISTINCT(u.id) AS id', 'COUNT(msgst.id) AS nb_messages_unread', 'u.email AS email', 'u.firstName AS first_name', 'u.lastName AS last_name')
            ->groupBy('u.id');

        if (null !== $limit && null !== $offset) {
            $queryBuilder
                ->setMaxResults($limit)
                ->setFirstResult($offset);
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
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
    public function countRecipientsWithUnreadMessageForPeriod(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return (int) $this->getQueryBuilderForPeriod($from, $to)
            ->select('COUNT(DISTINCT(u.id))')
            ->getQuery()
            ->getSingleScalarResult();
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

    /**
     * @param DateTimeImmutable $from
     * @param DateTimeImmutable $to
     *
     * @return QueryBuilder
     */
    private function getQueryBuilderForPeriod(DateTimeImmutable $from, DateTimeImmutable $to): QueryBuilder
    {
        return $this->createQueryBuilder('msgst')
            ->innerJoin('msgst.message', 'msg')
            ->innerJoin('msg.messageThread', 'msgtd')
            ->innerJoin('msgtd.projectParticipation', 'pp')
            ->innerJoin('pp.project', 'p')
            ->innerJoin('p.currentStatus', 'pst')
            ->innerJoin('msgst.recipient', 'stf')
            ->innerJoin('stf.user', 'u')
            ->where('msgst.status = :status')
            ->andWhere('msgst.added BETWEEN :from AND :to')
            ->andWhere('pst.status > :project_current_status')
            ->andWhere('msgst.unreadNotified IS NULL')
            ->setParameters([
                'status'                 => MessageStatus::STATUS_UNREAD,
                'from'                   => $from->format('Y-m-d H:i:s'),
                'to'                     => $to->format('Y-m-d H:i:s'),
                'project_current_status' => ProjectStatus::STATUS_DRAFT,
            ]);
    }
}
