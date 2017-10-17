<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\MailQueue;
use Unilend\librairies\CacheKeys;

class MailQueueRepository extends EntityRepository
{
    /**
     * @param int $limit
     *
     * @return MailQueue[]
     */
    public function getPendingMails($limit)
    {
        $qb = $this->createQueryBuilder('mq');
        $qb->where('mq.status = :pending')
           ->setParameter('pending', MailQueue::STATUS_PENDING)
           ->andWhere(
               $qb->expr()->orX('mq.toSendAt <= :now', $qb->expr()->isNull('mq.toSendAt'))
           )
           ->setParameter('now', new \DateTime())
           ->orderBy('mq.idQueue', 'ASC')
           ->groupBy('mq.recipient');

        if (is_numeric($limit)) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int $templateId
     *
     * @return bool
     */
    public function existsTemplateInMailQueue($templateId)
    {
        $queryBuilder = $this->createQueryBuilder('mq');
        $queryBuilder
            ->select('COUNT(mq.idQueue)')
            ->innerJoin('UnilendCoreBusinessBundle:MailTemplates', 'mt', Join::WITH, 'mq.idMailTemplate = mt.idMailTemplate')
            ->where('mt.idMailTemplate = :templateId')
            ->orWhere('mt.idHeader = :templateId')
            ->orWhere('mt.idFooter = :templateId')
            ->setParameter('templateId', $templateId);

        return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @param int|null       $clientId
     * @param string|null    $sender
     * @param string|null    $recipient
     * @param string|null    $subject
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     *
     * @return array
     */
    public function searchSentEmails($clientId = null, $sender = null, $recipient = null, $subject = null, \DateTime $startDate = null, \DateTime $endDate = null)
    {
        $queryBuilder = $this->createQueryBuilder('mq');
        $queryBuilder
            ->select('
                mq.idQueue,
                mq.sentAt,
                mq.recipient,
                mt.senderName,
                mt.senderEmail,
                mt.subject'
            )
            ->innerJoin('UnilendCoreBusinessBundle:MailTemplates', 'mt', Join::WITH, 'mq.idMailTemplate = mt.idMailTemplate')
            ->where('mq.status = :sentStatus')
            ->setParameter('sentStatus', MailQueue::STATUS_SENT)
            ->orderBy('mq.sentAt', 'DESC');

        if (false === is_null($clientId)) {
            $queryBuilder
                ->andWhere('mq.idClient = :clientId')
                ->setParameter('clientId', $clientId);
        }

        if (false === is_null($sender)) {
            $queryBuilder
                ->andWhere('mt.senderName LIKE :sender')
                ->setParameter('sender', '%' . $sender . '%');
        }

        if (false === is_null($recipient)) {
            $queryBuilder
                ->andWhere('mq.recipient LIKE :recipient')
                ->setParameter('recipient', '%' . $recipient . '%');
        }

        if (false === is_null($subject)) {
            $queryBuilder
                ->andWhere('mt.subject LIKE :subject')
                ->setParameter('subject', '%' . $subject . '%');
        }

        if (false === is_null($startDate)) {
            $queryBuilder
                ->andWhere('mq.sentAt >= :startDate')
                ->setParameter('startDate', $startDate->format('Y-m-d 00:00:00'));
        }

        if (false === is_null($endDate)) {
            $queryBuilder
                ->andWhere('mq.sentAt <= :endDate')
                ->setParameter('endDate', $endDate->format('Y-m-d 23:59:59'));
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string $type
     *
     * @return mixed
     */
    public function getMailTemplateSendFrequency($type)
    {
        $query = '
            SELECT
              SUM(periods.day)   AS day,
              SUM(periods.week)  AS week,
              SUM(periods.month) AS month 
            FROM (
              SELECT
                COUNT(mq.id_queue) AS day,
                NULL               AS week,
                NULL               AS month
              FROM mail_queue mq
              INNER JOIN mail_templates mt ON mq.id_mail_template = mt.id_mail_template
              WHERE type = :type AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                
              UNION ALL

              SELECT
                NULL               AS day,
                COUNT(mq.id_queue) AS week,
                NULL               AS month
              FROM mail_queue mq
              INNER JOIN mail_templates mt ON mq.id_mail_template = mt.id_mail_template
              WHERE type = :type AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
                
              UNION ALL
                
              SELECT
                NULL               AS day,
                NULL               AS week,
                COUNT(mq.id_queue) AS month
              FROM mail_queue mq
              INNER JOIN mail_templates mt ON mq.id_mail_template = mt.id_mail_template
              WHERE type = :type AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            ) periods';


        $statement = $this->getEntityManager()->getConnection()->executeCacheQuery(
            $query, ['type' => $type], ['type' => \PDO::PARAM_STR],
            new QueryCacheProfile(CacheKeys::LONG_TIME * 6, md5(__METHOD__ . $type))
        );
        $result    = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $result[0];
    }
}
