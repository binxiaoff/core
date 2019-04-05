<?php

namespace Unilend\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Connection;
use Unilend\Entity\{Clients, ClientsStatus, ClientsStatusHistory, WalletType};

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
        $queryBuilder
            ->where('csh.idClient = :idClient')
            ->andWhere('csh.idStatus = :status')
            ->orderBy('csh.added', 'ASC')
            ->addOrderBy('csh.id', 'ASC')
            ->setMaxResults(1)
            ->setParameter('idClient', $idClient)
            ->setParameter('status', ClientsStatus::STATUS_VALIDATED);
        $query  = $queryBuilder->getQuery();
        $result = $query->getOneOrNullResult();

        return $result;
    }

    /**
     * @param \DateTime  $start
     * @param \DateTime  $end
     * @param array|null $types
     *
     * @return int
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function countLendersValidatedBetweenDatesByType(\DateTime $start, \DateTime $end, array $types = null): int
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $query = '
            SELECT COUNT(DISTINCT c.id_client)
            FROM (
              SELECT MIN(added) AS added, id_client
              FROM clients_status_history
              WHERE id_status = ' . ClientsStatus::STATUS_VALIDATED . '
              GROUP BY id_client
            ) AS min_csh_validated
            INNER JOIN clients_status_history csh ON min_csh_validated.added = csh.added AND min_csh_validated.id_client = csh.id_client
            INNER JOIN clients c ON csh.id_client = c.id_client
            INNER JOIN wallet w ON c.id_client = w.id_client
            INNER JOIN wallet_type wt ON w.id_type = wt.id AND wt.label = "' . WalletType::LENDER . '"
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

        return (int) $result;
    }

    /**
     * @param int $client
     *
     * @return array|ClientsStatusHistory[]
     */
    public function findLastTwoClientStatus($client)
    {
        $queryBuilder = $this->createQueryBuilder('csh');
        $queryBuilder
            ->where('csh.idClient = :clientId')
            ->setParameter('clientId', $client)
            ->orderBy('csh.added', 'DESC')
            ->addOrderBy('csh.id', 'DESC')
            ->setMaxResults(2);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int $client
     *
     * @return int
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getValidationsCount(int $client): int
    {
        $queryBuilder = $this->createQueryBuilder('csh');
        $queryBuilder
            ->select('COUNT(csh.id)')
            ->where('csh.idClient = :idClient')
            ->andWhere('csh.idStatus = :validated')
            ->setParameter('idClient', $client)
            ->setParameter('validated', ClientsStatus::STATUS_VALIDATED);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
