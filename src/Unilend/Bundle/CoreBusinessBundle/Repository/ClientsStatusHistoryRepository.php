<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatusHistory;

class ClientsStatusHistoryRepository extends EntityRepository
{
    /**
     * @param integer|Clients $idClient
     *
     * @return ClientsStatusHistory
     */
    public function getFirstClientValidation($idClient)
    {
        if ($idClient instanceof Clients) {
            $idClient = $idClient->getIdClient();
        }

        $queryBuilder = $this->createQueryBuilder('csh');
        $queryBuilder->innerJoin('UnilendCoreBusinessBundle:ClientsStatus', 'cs', Join::WITH, 'csh.idClientStatus = cs.idClientStatus')
            ->where('csh.idClient = :idClient')
            ->andWhere('cs.status = :status')
            ->orderBy('csh.added', 'DESC')
            ->addOrderBy('csh.idClientStatusHistory',  'DESC')
            ->setMaxResults(1)
            ->setParameter('idClient', $idClient)
            ->setParameter('status', ClientsStatus::VALIDATED);
        $query  = $queryBuilder->getQuery();
        $result = $query->getOneOrNullResult();

        return $result;
    }

    /**
     * @param \DateTime  $start
     * @param \DateTime  $end
     * @param array|null $types
     *
     * @return bool|string
     */
    public function countLendersValidatedBetweenDatesByType(\DateTime $start, \DateTime $end, array $types = null)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $query = 'SELECT
                    COUNT(DISTINCT c.id_client)
                  FROM (
                    SELECT MIN(id_client_status_history) AS id_client_status_history 
                    FROM clients_status_history 
                    WHERE id_client_status = 6 GROUP BY id_client) AS min_csh_validated
                  INNER JOIN clients_status_history csh ON min_csh_validated.id_client_status_history = csh.id_client_status_history
                  INNER JOIN clients c ON csh.id_client = c.id_client
                  WHERE csh.added BETWEEN :start AND :end';

        $params    = ['start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s')];
        $bindTypes = ['start' => \PDO::PARAM_STR, 'end' => \PDO::PARAM_STR];

        if (null !== $types) {
            $query     .= ' AND c.type IN (:types)';
            $params    = array_merge($params, ['types' => $types]);
            $bindTypes = array_merge($bindTypes, [ 'types' => Connection::PARAM_INT_ARRAY]);
        }

        $result = $this->getEntityManager()->getConnection()
            ->executeQuery($query, $params, $bindTypes)
            ->fetchColumn();

        return $result;
    }

    /**
     * @param int $client
     *
     * @return array|ClientsStatusHistory[]
     */
    public function findLastTwoClientStatus($client)
    {
        $queryBuilder = $this->createQueryBuilder('csh');
        $queryBuilder->where('csh.idClient = :clientId')
            ->setParameter('clientId', $client)
            ->orderBy('csh.idClientStatusHistory', 'DESC')
            ->setMaxResults(2);

        return $queryBuilder->getQuery()->getResult();
    }
}
