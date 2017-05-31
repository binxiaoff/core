<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class TransactionsRepository extends EntityRepository
{
    /**
     * @param int $projectId
     * @param int $clientId
     *
     * @return array
     */
    public function getLenderAnticipatedAndEarlyTransactions($projectId, $clientId)
    {
        $queryBuilder = $this->createQueryBuilder('t')->select('t.montant AS amount, t.typeTransaction, t.dateTransaction');
        $queryBuilder->where('t.idProject = :projectId')
            ->andWhere('t.idClient = :clientId')
            ->andWhere('t.typeTransaction IN (:transactionTypes)')
            ->setParameter('projectId', $projectId)
            ->setParameter('clientId', $clientId)
            ->setParameter('transactionTypes', [\transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT, \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT], Connection::PARAM_INT_ARRAY)
            ->orderBy('t.added', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param $projectId
     * @param $clientId
     *
     * @return array
     */
    public function getLenderScheduledRepayments($projectId, $clientId)
    {
        $queryBuilder = $this->createQueryBuilder('t')->select('SUM(t.montant) AS amount, t.typeTransaction, t.dateTransaction');
        $queryBuilder->innerJoin('UnilendCoreBusinessBundle:Echeanciers', 'e', Join::WITH, 'e.idEcheancier = t.idEcheancier AND e.idProject = :projectId')
            ->where('t.idClient = :clientId')
            ->andWhere('t.typeTransaction IN(:transactionTypes)')
            ->setParameter('projectId', $projectId)
            ->setParameter('clientId', $clientId)
            ->setParameter('transactionTypes', [\transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL, \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS], Connection::PARAM_INT_ARRAY)
            ->groupBy('e.idEcheancier')
            ->orderBy('t.dateTransaction', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }
}