<?php
namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\ORM\EntityRepository;
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
     * @param string $type
     *
     * @return mixed
     */
    public function getSendFrequencyForMailTemplate($type)
    {
        $typesQuery = 'SELECT id_mail_template FROM mail_templates WHERE type = :type';
        $types      = $this->getEntityManager()->getConnection()->executeQuery($typesQuery, ['type' => $type])->fetchAll()[0];

        $query = 'SELECT
                  SUM(periods.24h) AS "24h",
                  SUM(periods.7d)  AS "7d",
                  SUM(periods.30d) AS "30d" 
                FROM (
                       SELECT
                         COUNT(id_queue) AS "24h",
                         NULL            AS "7d",
                         NULL            AS "30d"
                       FROM mail_queue
                       WHERE id_mail_template IN (' . implode(',', $types) . ') AND sent_at >= (DATE_SUB(NOW(), INTERVAL 1 DAY))
                
                       UNION ALL
                       SELECT
                         NULL,
                         COUNT(id_queue),
                         NULL
                       FROM mail_queue
                       WHERE id_mail_template IN (' . implode(',', $types) . ') AND sent_at >= (DATE_SUB(NOW(), INTERVAL 7 DAY))
                
                       UNION ALL
                
                       SELECT
                         NULL,
                         NULL,
                         COUNT(id_queue)
                       FROM mail_queue
                       WHERE id_mail_template IN (' . implode(',', $types) . ') AND sent_at >= (DATE_SUB(NOW(), INTERVAL 30 DAY))) AS periods';


        $statement = $this->getEntityManager()->getConnection()->executeCacheQuery(
            $query, [], [],
            new QueryCacheProfile(CacheKeys::LONG_TIME * 6, md5(__METHOD__ . $type))
        );
        $result    = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $result[0];
    }
}
