<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletBalanceHistory;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderOperationsManager;

class WalletBalanceHistoryRepository extends EntityRepository
{
    /**
     * @param  Wallet|integer $wallet
     * @param \DateTime       $date
     *
     * @return null|WalletBalanceHistory
     */
    public function getBalanceOfTheDay($wallet, \DateTime $date)
    {
        if ($wallet instanceof Wallet) {
            $wallet = $wallet->getId();
        }

        $date->setTime('23', '59', '59');

        $qb = $this->createQueryBuilder('w');
        $qb->andWhere('w.idWallet = :wallet')
           ->andWhere('w.added <= :dateTime')
           ->setParameters(['wallet' => $wallet, 'dateTime' => $date])
           ->orderBy('w.added', 'DESC')
           ->addOrderBy('w.id', 'DESC')
           ->setMaxResults(1);
        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    public function getLenderOperationHistory(Wallet $wallet, \DateTime $start, \DateTime $end)
    {
        $start->setTime(00, 00, 00);
        $end->setTime(23, 59,59);

        $query = 'SELECT
                      wbh.id AS id,
                      wbh.available_balance,
                      wbh.committed_balance,
                      ROUND(
                          IF(
                              wbh.id_operation IS NOT NULL,
                              IF(
                                  o.id_wallet_creditor = wbh.id_wallet,
                                  o.amount,
                                  IF(ot.label = "' . OperationType::LENDER_LOAN . '", o.amount, o.amount*-1)
                              ),
                              IF(
                                  wbh.id_bid IS NULL,
                                  IF(
                                      1 = (SELECT COUNT(*) FROM wallet_balance_history wbh_bid WHERE wbh_bid.id_bid = wbh.id_bid),
                                      b.amount/-100,
                                      IF(EXISTS(SELECT * FROM wallet_balance_history wbh_bid WHERE wbh_bid.id_bid = wbh.id_bid AND wbh.added < wbh_bid.added), b.amount/-100, b.amount/-100)
                                  ),
                                  IF(
                                      1 = (SELECT COUNT(*) FROM wallet_balance_history wbh_bid WHERE wbh_bid.id_autobid = wbh.id_autobid AND wbh_bid.id_project = wbh.id_project),
                                      b.amount/-100,
                                      IF(EXISTS(SELECT * FROM wallet_balance_history wbh_bid WHERE wbh_bid.id_autobid = wbh.id_autobid AND wbh_bid.id_project = wbh.id_project AND wbh.added < wbh_bid.added), b.amount/-100, b.amount/-100)
                                  )
                              )
                          )
                          , 2) as amount,
                      IF(
                          ot.label IS NOT NULL,
                          ot.label,
                          IF(
                              wbh.id_bid IS NULL,
                              "'. LenderOperationsManager::OP_REFUSED_BID . '",
                              IF(
                                  wbh.id_autobid IS NULL,
                                  IF(
                                      1 = (SELECT COUNT(*) FROM wallet_balance_history wbh_bid WHERE wbh_bid.id_bid = wbh.id_bid),
                                      "' . LenderOperationsManager::OP_BID . '",
                                      IF(EXISTS(SELECT * FROM wallet_balance_history wbh_bid WHERE wbh_bid.id_bid = wbh.id_bid AND wbh.added < wbh_bid.added), "' . LenderOperationsManager::OP_BID . '", "' . LenderOperationsManager::OP_REFUSED_BID . '")
                                  ),
                                  IF(
                                      1 = (SELECT COUNT(*) FROM wallet_balance_history wbh_bid WHERE wbh_bid.id_autobid = wbh.id_autobid AND wbh_bid.id_project = wbh.id_project),
                                      "' . LenderOperationsManager::OP_AUTOBID . '",
                                      IF(EXISTS(SELECT * FROM wallet_balance_history wbh_bid WHERE wbh_bid.id_autobid = wbh.id_autobid AND wbh_bid.id_project = wbh.id_project AND wbh.added < wbh_bid.added), "' . LenderOperationsManager::OP_AUTOBID . '" , "' . LenderOperationsManager::OP_REFUSED_AUTOBID .'")
                                  )
                              )
                          )) AS label,
                      IF(o.id_project IS NOT NULL, o.id_project, wbh.id_project) as id_project,
                      DATE(wbh.added) AS date,
                      o.added AS operationDate,
                      wbh.id_bid,
                      wbh.id_autobid,
                      IF(wbh.id_loan IS NOT NULL, wbh.id_loan, IF(o.id_loan IS NOT NULL, o.id_loan, IF(e.id_loan IS NOT NULL, e.id_loan, ""))) AS id_loan,
                      o.id_repayment_schedule,
                      p.title
                    FROM wallet_balance_history wbh
                      INNER JOIN wallet w ON wbh.id_wallet = w.id
                      LEFT JOIN operation o ON wbh.id_operation = o.id
                      LEFT JOIN operation_type ot ON ot.id = o.id_type
                      LEFT JOIN echeanciers e ON e.id_echeancier = o.id_repayment_schedule
                      LEFT JOIN bids b ON wbh.id_bid = b.id_bid
                      LEFT JOIN projects p ON IF(o.id_project IS NULL, wbh.id_project, o.id_project) = p.id_project
                    WHERE wbh.id_wallet = :idWallet
                    AND wbh.added BETWEEN :startDate AND :endDate
                    GROUP BY IF(id_repayment_schedule IS NULL, wbh.id, o.id_repayment_schedule)
                    ORDER BY wbh.added DESC, id_bid DESC, id_loan DESC, id_repayment_schedule DESC';

        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, [
            'idWallet'  => $wallet->getId(),
            'startDate' => $start->format('Y-m-d H:i:s'),
            'endDate'   => $end->format('Y-m-d H:i:s')
        ]);


        //TODO implement cache

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param Wallet $wallet
     * @param $idWalletBalanceHistory
     *
     * @return mixed
     */
    public function getPreviousLIneForWallet(Wallet $wallet, $idWalletBalanceHistory)
    {
        $qb = $this->createQueryBuilder('wbh');
        $qb->where('wbh.id < :idWbh')
            ->andWhere('wbh.idWallet = :idWallet')
            ->orderBy('wbh.id', 'DESC')
            ->setMaxResults(1)
            ->setParameter('idWallet', $wallet)
            ->setParameter('idWbh', $idWalletBalanceHistory);

        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }
}
