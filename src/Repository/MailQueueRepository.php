<?php

declare(strict_types=1);

namespace Unilend\Repository;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use Unilend\Entity\{MailQueue, MailTemplate};

/**
 * @method MailQueue|null find($id, $lockMode = null, $lockVersion = null)
 * @method MailQueue|null findOneBy(array $criteria, array $orderBy = null)
 * @method MailQueue[]    findAll()
 * @method MailQueue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MailQueueRepository extends ServiceEntityRepository
{
    /**
     * MailQueueRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailQueue::class);
    }

    /**
     * @param int $limit
     *
     * @throws Exception
     *
     * @return MailQueue[]
     */
    public function getPendingMails($limit): array
    {
        $qb = $this->createQueryBuilder('mq');
        $qb->where('mq.status = :pending')
            ->setParameter('pending', MailQueue::STATUS_PENDING)
            ->andWhere(
                $qb->expr()->orX('mq.toSendAt <= :now', $qb->expr()->isNull('mq.toSendAt'))
            )
            ->setParameter('now', new DateTime())
            ->orderBy('mq.id', 'ASC')
            ->groupBy('mq.recipient')
        ;

        if (is_numeric($limit)) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int $templateId
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    public function existsTemplateInMailQueue($templateId): bool
    {
        $queryBuilder = $this->createQueryBuilder('mq');
        $queryBuilder
            ->select('COUNT(mq.id)')
            ->innerJoin(MailTemplate::class, 'mt', Join::WITH, 'mq.mailTemplate = mt.id')
            ->where('mt.id = :templateId')
            ->setParameter('templateId', $templateId)
        ;

        return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @param int|null      $clientId
     * @param string|null   $sender
     * @param string|null   $recipient
     * @param string|null   $subject
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     *
     * @return array
     */
    public function searchSentEmails($clientId = null, $sender = null, $recipient = null, $subject = null, DateTime $startDate = null, DateTime $endDate = null): array
    {
        $queryBuilder = $this->createQueryBuilder('mq');
        $queryBuilder
            ->select(
                '
                mq.id,
                mq.sentAt,
                mq.recipient,
                mt.senderName,
                mt.senderEmail,
                mt.subject'
            )
            ->innerJoin(MailTemplate::class, 'mt', Join::WITH, 'mq.mailTemplate = mt.id')
            ->where('mq.status = :sentStatus')
            ->setParameter('sentStatus', MailQueue::STATUS_SENT)
            ->orderBy('mq.sentAt', 'DESC')
        ;

        if (false === (null === $clientId)) {
            $queryBuilder
                ->andWhere('mq.client = :clientId')
                ->setParameter('clientId', $clientId)
            ;
        }

        if (false === (null === $sender)) {
            $queryBuilder
                ->andWhere('mt.senderName LIKE :sender')
                ->setParameter('sender', '%' . $sender . '%')
            ;
        }

        if (false === (null === $recipient)) {
            $queryBuilder
                ->andWhere('mq.recipient LIKE :recipient')
                ->setParameter('recipient', '%' . $recipient . '%')
            ;
        }

        if (false === (null === $subject)) {
            $queryBuilder
                ->andWhere('mt.subject LIKE :subject')
                ->setParameter('subject', '%' . $subject . '%')
            ;
        }

        if (false === (null === $startDate)) {
            $queryBuilder
                ->andWhere('mq.sentAt >= :startDate')
                ->setParameter('startDate', $startDate->format('Y-m-d 00:00:00'))
            ;
        }

        if (false === (null === $endDate)) {
            $queryBuilder
                ->andWhere('mq.sentAt <= :endDate')
                ->setParameter('endDate', $endDate->format('Y-m-d 23:59:59'))
            ;
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @throws DBALException
     *
     * @return array
     */
    public function getMailTemplateSendFrequency(): array
    {
        $query = '
            SELECT
              id_mail_template,
              SUM(IF(sent_at >= DATE_SUB(NOW(), INTERVAL 1 DAY), 1, 0))   AS day,
              SUM(IF(sent_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK), 1, 0))  AS week,
              SUM(IF(sent_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH), 1, 0)) AS month
            FROM mail_queue
            WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            GROUP BY id_mail_template';

        return $this->getEntityManager()->getConnection()->executeQuery($query)->fetchAll();
    }
}
