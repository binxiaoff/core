<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\MailQueue;

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
           ->andWhere('mq.toSendAt <= :now')
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
            ->where('mq.idMailTemplate = :templateId')
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
                mq.*,
                mt.sender_name,
                mt.sender_email,
                mt.subject'
            )
            ->innerJoin('UnilendCoreBusinessBundle:MailTemplates', 'mt', Join::WITH, 'mq.idMailTemplate = mt.idMailTemplate')
            ->where('mq.status = :sent')
            ->setParameter('sent', MailQueue::STATUS_SENT)
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
}
