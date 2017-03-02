<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;

class BidsRepository extends EntityRepository
{
    /**
     * @param array $criteria
     *
     * @return mixed
     */
    public function countBy(array $criteria = [])
    {
        $qb = $this->createQueryBuilder("b");
        $qb->select('COUNT(b)');
        if (false === empty($criteria)) {
            foreach ($criteria as $field => $value) {
                $qb->andWhere('b.' . $field . ' = :' . $field)
                   ->setParameter($field, $value);
            }
        }
        $query = $qb->getQuery();
        return $query->getSingleScalarResult();
    }

    public function countByClientInPeriod(\DateTime $from, \DateTime $to, $clientId)
    {
        $query = '
            SELECT COUNT(id_bid)
            FROM bids
            WHERE bids.added BETWEEN :from_date AND :to_date
              AND bids.id_lender_account = (SELECT la.id_lender_account FROM lenders_accounts la WHERE la.id_client_owner = :id_client)
        ';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                $query,
                [
                    'from_date' => $from->format('Y-m-d H:i:s'),
                    'to_date'   => $to->format('Y-m-d H:i:s'),
                    'id_client' => $clientId
                ],
                [
                    'from_date' => \PDO::PARAM_STR,
                    'to_date'   => \PDO::PARAM_STR,
                    'id_client' => \PDO::PARAM_INT
                ]
            )->fetchColumn();
    }
}
