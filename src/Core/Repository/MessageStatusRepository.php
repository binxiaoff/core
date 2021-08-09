<?php

declare(strict_types=1);

namespace KLS\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Core\Entity\MessageStatus;
use KLS\Core\Entity\MessageThread;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\UserStatus;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;

/**
 * @method MessageStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method MessageStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method MessageStatus[]    findAll()
 * @method MessageStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageStatus::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(MessageStatus $messageStatus): void
    {
        $this->getEntityManager()->persist($messageStatus);
        $this->getEntityManager()->flush();
    }

    /**
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

    public function setMessageStatusesToUnreadNotified(int $userId): void
    {
        $messageStatusToBeNotified = $this->getQueryBuilderForPeriod()
            ->andWhere('stf.user = :user_id')
            ->setParameter('user_id', $userId)
            ->getQuery()->getArrayResult();

        $this->createQueryBuilder('msgst')->update()
            ->set('msgst.unreadNotified', 'NOW()')
            ->set('msgst.updated', 'NOW()')
            ->where('msgst.id IN (:messageStatusToBeNotified)')
            ->setParameter('messageStatusToBeNotified', $messageStatusToBeNotified)
            ->getQuery()
            ->execute()
        ;
    }

    public function countUnreadMessageByRecipentForPeriod(int $limit = null, int $offset = null): array
    {
        $queryBuilder = $this->getQueryBuilderForPeriod()
            ->select('DISTINCT(u.id) AS id', 'COUNT(msgst.id) AS nb_messages_unread', 'u.email AS email', 'u.firstName AS first_name', 'u.lastName AS last_name')
            ->groupBy('u.id')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
        ;

        return $queryBuilder
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countRecipientsWithUnreadMessageForPeriod(): int
    {
        return (int) $this->getQueryBuilderForPeriod()
            ->select('COUNT(DISTINCT(u.id))')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

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
            ->execute()
        ;
    }

    private function getQueryBuilderForPeriod(): QueryBuilder
    {
        return $this->createQueryBuilder('msgst')
            ->innerJoin('msgst.message', 'msg')
            ->innerJoin('msg.messageThread', 'msgtd')
            ->innerJoin('msgtd.projectParticipation', 'pp')
            ->innerJoin('pp.project', 'p')
            ->innerJoin('p.currentStatus', 'pst')
            ->innerJoin('msgst.recipient', 'stf')
            ->innerJoin('stf.user', 'u')
            ->innerJoin('u.currentStatus', 'us')
            ->where('msgst.status = :status')
            ->andWhere('pst.status > :project_current_status')
            ->andWhere('msgst.unreadNotified IS NULL')
            ->andWhere('us.status = :user_status')
            ->setParameters([
                'status'                 => MessageStatus::STATUS_UNREAD,
                'project_current_status' => ProjectStatus::STATUS_DRAFT,
                'user_status'            => UserStatus::STATUS_CREATED,
            ])
        ;
    }
}
