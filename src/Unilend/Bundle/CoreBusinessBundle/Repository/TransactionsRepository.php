<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;

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
        $queryBuilder = $this->createQueryBuilder('t')->select('t.montant as amount, t.typeTransaction, t.dateTransaction as added');
        $queryBuilder->where('t.idProject = :projectId')
            ->andWhere('t.idClient = :clientId')
            ->andWhere('t.typeTransaction IN (:transactionTypes)')
            ->setParameter('projectId', $projectId)
            ->setParameter('clientId', $clientId)
            ->setParameter('transactionTypes', [\transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT, \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT], Connection::PARAM_INT_ARRAY)
            ->orderBy('t.added', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }
}