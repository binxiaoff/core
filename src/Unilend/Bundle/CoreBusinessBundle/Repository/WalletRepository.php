<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class WalletRepository extends EntityRepository
{

    /**
     * @return array Wallet[]
     */
    public function getTaxWallets()
    {
        $cb = $this->createQueryBuilder('w');
        $cb->select('w')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->where('wt.label IN (:taxWallets)')
            ->setParameter(
                'taxWallets', [
                WalletType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE,
                WalletType::TAX_FR_ADDITIONAL_CONTRIBUTIONS,
                WalletType::TAX_FR_CRDS,
                WalletType::TAX_FR_CSG,
                WalletType::TAX_FR_SOLIDARITY_DEDUCTIONS,
                WalletType::TAX_FR_STATUTORY_CONTRIBUTIONS,
                WalletType::TAX_FR_SOCIAL_DEDUCTIONS
            ], Connection::PARAM_INT_ARRAY);
        $query = $cb->getQuery();

        return $query->getResult();
    }

    /**
     * @param integer|Clients   $idClient
     * @param string|WalletType $walletType
     *
     * @return Wallet|null
     */
    public function getWalletByType($idClient, $walletType)
    {
        $cb = $this->createQueryBuilder('w');
        $cb->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->where('w.idClient = :idClient')
            ->andWhere('wt.label = :walletType')
            ->setMaxResults(1)
            ->setParameters(['idClient' => $idClient, 'walletType' => $walletType]);
        $query  = $cb->getQuery();
        $result = $query->getOneOrNullResult();

        return $result;
    }

    /**
     * @param \DateTime $inactiveSince
     * @param int       $minAvailableBalance
     *
     * @return array
     */
    public function getInactiveLenderWalletOnPeriod(\DateTime $inactiveSince, $minAvailableBalance)
    {
        $sql = '
        SELECT
          a.walletId,
          GREATEST(a.lastOperationDate,b2.lastOperationDate) AS lastOperationDate,
          w.available_balance AS availableBalance
        FROM (
               SELECT
                 COALESCE(o.id_wallet_creditor, o.id_wallet_debtor) AS walletId,
                 MAX(o.added)                                       AS lastOperationDate
               FROM operation o
                 INNER JOIN operation_type ot ON o.id_type = ot.id AND ot.label IN (:operationType)
               GROUP BY walletId
               HAVING lastOperationDate < :inactiveSince
             ) a
          INNER JOIN wallet w ON a.walletId = w.id AND w.available_balance >= :minAvailableBalance
          INNER JOIN wallet_type wt ON wt.id = w.id_type AND wt.label = :lender
          INNER JOIN (
                       SELECT
                         am.id_wallet AS walletId,
                         MAX(b.added)   AS lastOperationDate
                       FROM bids b
                         INNER JOIN account_matching am ON am.id_lender_account = b.id_lender_account
                       WHERE b.id_autobid IS NULL
                       GROUP BY am.id_wallet
                       HAVING lastOperationDate < :inactiveSince
                     ) b2 ON b2.walletId = a.walletId
        GROUP BY walletId
        ';
        $params = [
            'operationType'       => [OperationType::LENDER_WITHDRAW, OperationType::LENDER_PROVISION],
            'lender'              => WalletType::LENDER,
            'inactiveSince'       => $inactiveSince->format('Y-m-d H:i:s'),
            'minAvailableBalance' => $minAvailableBalance
        ];
        $binds = [
            'operationType'       => Connection::PARAM_STR_ARRAY,
            'lender'              => \PDO::PARAM_STR,
            'inactiveSince'       => \PDO::PARAM_STR,
            'minAvailableBalance' => \PDO::PARAM_INT
        ];

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($sql, $params, $binds)
            ->fetchAll();
    }
}
