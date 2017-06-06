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
     * @param float     $minAvailableBalance
     *
     * @return array
     */
    public function getInactiveLenderWalletOnPeriod(\DateTime $inactiveSince, $minAvailableBalance)
    {
        $operationType = $this->getEntityManager()->createQueryBuilder()
            ->select('ot.id')
            ->from('UnilendCoreBusinessBundle:OperationType', 'ot')
            ->where('ot.label IN (\'lender_withdraw\', \'lender_provision\')');
        $queryBuilder  = $this->createQueryBuilder('w')
            ->select('o.id, b.idBid, IDENTITY(w.idClient) AS idClient, w.availableBalance, w.id AS walletId')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'wt.id = w.idType')
            ->innerJoin('UnilendCoreBusinessBundle:WalletBalanceHistory', 'wbh', Join::WITH, 'w.id = wbh.idWallet')
            ->leftJoin('UnilendCoreBusinessBundle:Operation', 'o', Join::WITH, 'o.id = wbh.idOperation AND o.idType IN (' . $operationType->getDQL() . ')')
            ->leftJoin('UnilendCoreBusinessBundle:Bids', 'b', Join::WITH, 'b.idBid = wbh.idBid AND b.idAutobid IS NULL')
            ->where('wt.label = :lender')
            ->setParameter('lender', WalletType::LENDER)
            ->andWhere('w.availableBalance >= :minAvailableBalance')
            ->setParameter('minAvailableBalance', $minAvailableBalance)
            ->andWhere('wbh.added > :inactiveSince')
            ->setParameter('inactiveSince', $inactiveSince)
            ->groupBy('wbh.idWallet')
            ->having('o.id IS NULL AND b.idBid IS NULL');

        $result = [];

        foreach ($queryBuilder->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR) as $wallet) {
            $result[$wallet['walletId']] = $wallet;
        }

        return $result;
    }

    /**
     * @param array $walletList
     *
     * @return array
     */
    public function getLastLenderWalletActionDate($walletList)
    {
        $sql = 'SELECT
          walletId,
          MAX(lastOperationDate) AS lastOperationDate
        FROM (
            SELECT
                 COALESCE(o.id_wallet_creditor, o.id_wallet_debtor) AS walletId,
                 MAX(o.added)                                       AS lastOperationDate
               FROM operation o
                 INNER JOIN operation_type ot ON o.id_type = ot.id AND ot.label IN (\'lender_withdraw\', \'lender_provision\')
               WHERE o.id_wallet_creditor IN (:walletList) OR o.id_wallet_debtor IN (:walletList)
               GROUP BY walletId
               UNION ALL
               SELECT
                 am.id_wallet AS walletId,
                 MAX(added) AS lastOperationDate
               FROM bids b
                 INNER JOIN account_matching am ON am.id_lender_account = b.id_lender_account
               WHERE am.id_wallet IN (:walletList) AND b.id_autobid IS NULL
               GROUP BY am.id_wallet
             ) a
        GROUP BY walletId';

        $statement = $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                $sql,
                ['walletList' => $walletList],
                ['walletList' => Connection::PARAM_INT_ARRAY]
            );
        $result    = [];

        while ($wallet = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $result[$wallet['walletId']] = $wallet;
        }

        return $result;
    }
}
