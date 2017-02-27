<?php
namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
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
}
