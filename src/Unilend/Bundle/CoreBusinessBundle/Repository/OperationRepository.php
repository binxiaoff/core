<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Operation;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\PaysV2;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Doctrine\DBAL\Connection;

class OperationRepository extends EntityRepository
{
    public function getOperationByTypeAndAmount($typeLabel, $amount)
    {
        $criteria = [
            'idType' => $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => $typeLabel]),
            'amount' => $amount
        ];
        $operator = [
            'idType' => Comparison::EQ,
            'amount' => Comparison::GTE
        ];

        return $this->getOperationBy($criteria, $operator);
    }

    /**
     * @param Wallet    $wallet
     * @param double    $amount
     * @param \DateTime $added
     *
     * @return Operation[]
     */
    public function getWithdrawOperationByWallet(Wallet $wallet, $amount, \DateTime $added)
    {
        $criteria = [
            'idType'         => $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_WITHDRAW]),
            'idWalletDebtor' => $wallet,
            'amount'         => $amount,
            'added'          => $added
        ];
        $operator = [
            'idType'         => Comparison::EQ,
            'idWalletDebtor' => Comparison::EQ,
            'amount'         => Comparison::GTE,
            'added'          => Comparison::GTE
        ];

        return $this->getOperationBy($criteria, $operator);
    }

    /**
     * @param Wallet    $wallet
     * @param \DateTime $date
     *
     * @return Operation[]
     */
    public function getWithdrawAndProvisionOperationByDateAndWallet(Wallet $wallet, \DateTime $date)
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.idType IN (:walletType)')
            ->setParameter('walletType', [
                $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_WITHDRAW])->getId(),
                $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_PROVISION])->getId(),
            ])
            ->andWhere('o.idWalletCreditor = :idWallet OR o.idWalletDebtor = :idWallet')
            ->setParameter('idWallet', $wallet)
            ->andWhere('o.added >= :added')
            ->setParameter('added', $date);

        return $qb->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);
    }

    /**
     * @param array $criteria [field => value]
     * @param array $operator [field => operator]
     *
     * @return Operation[]
     */
    private function getOperationBy(array $criteria = [], array $operator = [])
    {
        $qb = $this->createQueryBuilder('op');
        $qb->select('op');

        foreach ($criteria as $field => $value) {
            $qb->andWhere('op.' . $field . $operator[$field] . ':' . $field)
                ->setParameter($field, $value);
        }
        $qb->orderBy('op.added', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Wallet $creditorWallet
     * @param array  $operationTypes
     * @param int    $year
     *
     * @return mixed
     */
    public function sumCreditOperationsByTypeAndYear(Wallet $creditorWallet, $operationTypes, $year = null)
    {
        $qb = $this->createQueryBuilder('o');
        $qb->select('SUM(o.amount)')
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label IN (:operationTypes)')
            ->andWhere('o.idWalletCreditor = :idWallet')
            ->setParameter('operationTypes', $operationTypes, Connection::PARAM_STR_ARRAY)
            ->setParameter('idWallet', $creditorWallet);

        if (null !== $year) {
            $qb->andWhere('YEAR(o.added) = :year')
                ->setParameter('year', $year);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /***
     * @param Wallet $debtorWallet
     * @param array  $operationTypes
     * @param int    $year
     *
     * @return mixed
     */
    public function sumDebitOperationsByTypeAndYear(Wallet $debtorWallet, $operationTypes, $year = null)
    {
        $qb = $this->createQueryBuilder('o');
        $qb->select('SUM(o.amount)')
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label IN (:operationTypes)')
            ->andWhere('o.idWalletDebtor = :idWallet')
            ->setParameter('operationTypes', $operationTypes, Connection::PARAM_STR_ARRAY)
            ->setParameter('idWallet', $debtorWallet);

        if (null !== $year) {
            $qb->andWhere('YEAR(o.added) = :year')
                ->setParameter('year', $year);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Wallet $wallet
     * @param int $year
     *
     * @return bool|string
     */
    public function sumCapitalRepaymentsInEeaExceptFrance(Wallet $wallet, $year)
    {
        $query = 'SELECT
                     SUM(IF(tlih.id_pays IN (:eeaCountries),o_capital.amount, 0)) AS capital
                  FROM operation o_capital
                     LEFT JOIN (SELECT
                                  o.id_wallet_creditor,
                                  o.id,
                                  (SELECT lih.id_pays
                                   FROM lenders_imposition_history lih
                                   WHERE id_lender = am.id_lender_account AND DATE(lih.added) <= DATE(o.added)
                                   ORDER BY lih.added DESC
                                   LIMIT 1) AS id_pays
                                FROM operation o
                                  INNER JOIN account_matching am ON o.id_wallet_creditor = am.id_wallet
                                  INNER JOIN operation_type ot ON o.id_type = ot.id
                                WHERE ot.label = "' . OperationType::CAPITAL_REPAYMENT . '" AND o.id_wallet_creditor = :idWallet) AS tlih ON o_capital.id = tlih.id
                    WHERE o_capital.id_type = (SELECT id FROM operation_type WHERE label = "' . OperationType::CAPITAL_REPAYMENT . '")
                    AND LEFT(o_capital.added, 4) = :year
                    AND o_capital.id_wallet_creditor = :idWallet';

        $bind  = [
            'year'         => $year,
            'idWallet'     => $wallet->getId(),
            'eeaCountries' => PaysV2::EUROPEAN_ECONOMIC_AREA
        ];
        $types = ['year'         => \PDO::PARAM_INT,
                  'idWallet'     => \PDO::PARAM_INT,
                  'eeaCountries' => Connection::PARAM_INT_ARRAY
        ];

        return $this->getEntityManager()->getConnection()->executeQuery($query, $bind, $types)->fetchColumn();
    }

    /**
     * @param Wallet $wallet
     * @param int    $year
     *
     * @return bool|string
     */
    public function sumNetInterestRepaymentsNotInEeaExceptFrance(Wallet $wallet, $year)
    {
        $taxTypes = [
            OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS,
            OperationType::TAX_FR_SOCIAL_DEDUCTIONS,
            OperationType::TAX_FR_CSG,
            OperationType::TAX_FR_CRDS,
            OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS,
            OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE,
            OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS
        ];

        $query = 'SELECT
                      SUM(IF(tlih.id_pays IN (:eeaCountries), o_interest.amount, 0)) AS interest
                    FROM operation o_interest
                     LEFT JOIN (SELECT 
                                  SUM(amount) AS amount,
                                  id_repayment_schedule
                                 FROM operation
                                  WHERE operation.id_wallet_debtor = :idWallet
                                  AND operation.id_type IN (SELECT id FROM operation_type WHERE label IN (:taxOperations))
                                  GROUP BY id_repayment_schedule) AS o_taxes ON o_interest.id_repayment_schedule = o_taxes.id_repayment_schedule
                     LEFT JOIN (SELECT
                                  o.id_wallet_creditor,
                                  o.id,
                                  (SELECT lih.id_pays
                                   FROM lenders_imposition_history lih
                                   WHERE id_lender = am.id_lender_account AND DATE(lih.added) <= DATE(o.added)
                                   ORDER BY lih.added DESC
                                   LIMIT 1) AS id_pays
                                FROM operation o
                                  INNER JOIN account_matching am ON o.id_wallet_creditor = am.id_wallet
                                  INNER JOIN operation_type ot ON o.id_type = ot.id
                                WHERE ot.label = "' . OperationType::GROSS_INTEREST_REPAYMENT . '" AND o.id_wallet_creditor = :idWallet) AS tlih ON o_interest.id = tlih.id
                    WHERE o_interest.id_type = (SELECT id FROM operation_type WHERE label = "' . OperationType::GROSS_INTEREST_REPAYMENT . '")
                    AND LEFT(o_interest.added, 4) = :year
                         AND o_interest.id_wallet_creditor = :idWallet';

        $bind  = [
            'year'          => $year,
            'idWallet'      => $wallet->getId(),
            'taxOperations' => $taxTypes,
            'eeaCountries'  => PaysV2::EUROPEAN_ECONOMIC_AREA
        ];
        $types = [
            'year'          => \PDO::PARAM_INT,
            'idWallet'      => \PDO::PARAM_INT,
            'taxOperations' => Connection::PARAM_STR_ARRAY,
            'eeaCountries'  => Connection::PARAM_INT_ARRAY
        ];

        return $this->getEntityManager()->getConnection()->executeQuery($query, $bind, $types)->fetchColumn();
    }

    /**
     * @param Wallet $wallet
     * @param int    $year
     *
     * @return bool|string
     */
    public function getGrossInterestPaymentsInFrance(Wallet $wallet, $year)
    {
        $query = 'SELECT
                      SUM(IF(tlih.id_pays = 1 OR tlih.id_pays IS NULL OR tlih.id_pays = "", o_interest.amount, 0)) AS sum66
                    FROM operation o_interest
                      LEFT JOIN (SELECT
                                   o.id_wallet_creditor,
                                   o.id,
                                   (SELECT lih.id_pays
                                    FROM lenders_imposition_history lih
                                      WHERE id_lender = am.id_lender_account AND added <= o.added
                                    ORDER BY added DESC
                                    LIMIT 1) AS id_pays
                                 FROM operation o
                                   INNER JOIN account_matching am ON o.id_wallet_creditor = am.id_wallet
                                   INNER JOIN operation_type ot ON o.id_type = ot.id
                                 WHERE ot.label = "' . OperationType::GROSS_INTEREST_REPAYMENT . '" AND o.id_wallet_creditor = :idWallet) AS tlih ON o_interest.id = tlih.id
                    WHERE o_interest.id_type = (SELECT id FROM operation_type WHERE label = "' . OperationType::GROSS_INTEREST_REPAYMENT . '")
                      AND LEFT(o_interest.added, 4) = :year 
                      AND o_interest.id_wallet_creditor =  :idWallet';

        return $this->getEntityManager()->getConnection()->executeQuery($query, ['year' => $year, 'idWallet' => $wallet->getId()])->fetchColumn();
    }

    /**
     * @param int $idRepaymentSchedule
     *
     * @return null|float
     */
    public function getTaxAmountByRepaymentScheduleId($idRepaymentSchedule)
    {
        $qb = $this->createQueryBuilder('o');
        $qb->select('SUM(amount')
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label IN (:taxTypes)')
            ->andWhere('o.idRepaymentSchedule = :idRepaymentSchedule')
            ->setParameter('taxTypes', OperationType::TAX_TYPES_FR, Connection::PARAM_STR_ARRAY)
            ->setParameter('idRepaymentSchedule', $idRepaymentSchedule)
            ->setCacheable(true);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $idRepaymentSchedule
     *
     * @return mixed
     */
    public function getDetailByRepaymentScheduleId($idRepaymentSchedule)
    {
        $query = '
                SELECT
                  o_capital.amount AS capital,
                  o_interest.amount AS interest,
                  (SELECT SUM(amount) FROM operation INNER JOIN operation_type ON operation.id_type = operation_type.id AND operation_type.label IN ("' . implode('","', OperationType::TAX_TYPES_FR) . '") WHERE operation.id_repayment_schedule = o_interest.id_repayment_schedule) AS taxes,
                  (SELECT available_balance
                    FROM wallet_balance_history wbh 
                    INNER JOIN operation o ON wbh.id_operation = o.id 
                    WHERE o.id_repayment_schedule = o_interest.id_repayment_schedule ANd id_wallet = o_interest.id_wallet_creditor ORDER BY wbh.id DESC LIMIT 1) AS available_balance
                FROM operation o_capital
                  INNER JOIN operation_type ot_capital ON o_capital.id_type = ot_capital.id AND ot_capital.label = "' . OperationType::CAPITAL_REPAYMENT . '"
                  LEFT JOIN operation o_interest ON o_capital.id_repayment_schedule = o_interest.id_repayment_schedule
                  INNER JOIN operation_type ot_interest ON o_interest.id_type = ot_interest.id AND ot_interest.label = "' . OperationType::GROSS_INTEREST_REPAYMENT . '"
                WHERE o_capital.id_repayment_schedule = :idRepaymentSchedule';

        $qcProfile = new QueryCacheProfile(\Unilend\librairies\CacheKeys::DAY, md5(__METHOD__ . $idRepaymentSchedule));
        $statement = $this->getEntityManager()->getConnection()->executeCacheQuery($query, ['idRepaymentSchedule' => $idRepaymentSchedule], ['idRepaymentSchedule' => \PDO::PARAM_INT], $qcProfile);
        $result    = $statement->fetch();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param Wallet           $wallet
     * @param string|\DateTime $date
     *
     * @return mixed
     */
    public function getLenderRecoveryRepaymentDetailByDate(Wallet $wallet, $date)
    {
        if ($date instanceof \DateTime) {
            $date = $date->format('y-m-d H:i:s');
        }

        $query = '
                    SELECT
                      o_capital.amount AS capital,
                      o_recovery.amount AS commission,
                      (SELECT available_balance
                       FROM wallet_balance_history wbh
                      WHERE wbh.id_operation = o_recovery.id AND wbh.id_wallet = o_recovery.id_wallet_debtor) AS available_balance
                    FROM operation o_capital
                      INNER JOIN operation o_recovery ON o_capital.id_project = o_recovery.id_project AND
                                                         o_capital.id_wallet_creditor = o_recovery.id_wallet_debtor AND
                                                         o_recovery.id_type = (SELECT id
                                                                               FROM operation_type
                                                                               WHERE label = "' . OperationType::COLLECTION_COMMISSION_LENDER . '")
                                                         AND o_capital.added = o_recovery.added
                    WHERE o_capital.id_wallet_creditor = :idWallet
                      AND o_capital.added = :date
                      AND o_capital.id_type = (SELECT id FROM operation_type WHERE label = "' . OperationType::CAPITAL_REPAYMENT . '")
                    GROUP BY IF(o_capital.id_repayment_schedule IS NOT NULL, o_capital.id_repayment_schedule, o_capital.id)';

        $qcProfile = new QueryCacheProfile(\Unilend\librairies\CacheKeys::DAY, md5(__METHOD__ . $wallet->getId()));
        $statement = $this->getEntityManager()->getConnection()->executeCacheQuery($query, ['idWallet' => $wallet->getId(), 'date' => $date], ['idWallet' => \PDO::PARAM_INT, 'date' => \PDO::PARAM_STR], $qcProfile);
        $result    = $statement->fetch();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function sumMovementsForDailyState(\DateTime $start, \DateTime $end, array $operationTypes)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $query = 'SELECT
                  LEFT(o.added, 10) AS day,
                  SUM(o.amount) AS amount,
                  CASE ot.label
                   WHEN "'. OperationType::LENDER_PROVISION . '" THEN
                      IF(o.id_backpayline IS NOT NULL,
                       "lender_provision_credit_card",
                        IF(o.id_wire_transfer_in IS NOT NULL,
                           "lender_provision_wire_transfer_in",
                           NULL)
                        )
                     WHEN "'. OperationType::BORROWER_COMMISSION . '" THEN
                       IF(o.id_payment_schedule IS NULL, "borrower_commission_project", "borrower_commission_payment")
                      ELSE ot.label END AS movement
                FROM operation o USE INDEX (idx_operation_added_id_type)
                INNER JOIN operation_type ot ON o.id_type = ot.id
                WHERE
                  o.added BETWEEN :start AND :end
                  AND ot.label IN ("' . implode('","', $operationTypes) . '")
                GROUP BY day, movement
                ORDER BY o.added ASC;';

        $result = $this->getEntityManager()->getConnection()
            ->executeQuery($query,
                ['start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s')])->fetchAll(\PDO::FETCH_ASSOC);

        $movements = [];
        foreach ($result as $row) {
            $movements[$row['day']][$row['movement']] = $row['amount'];
        }

        return $movements;
    }

    public function sumMovementsForDailyStateByMonth($year, array $operationTypes)
    {
        $start = new \DateTime('First day of january ' . $year);
        $start->setTime(0,0,0);
        $end = new \DateTime('Last day of december' . $year);
        $end->setTime(23,59,59);

        $query = 'SELECT
                  MONTH(o.added) AS month,
                  SUM(o.amount) AS amount,
                  CASE ot.label
                   WHEN "'. OperationType::LENDER_PROVISION . '" THEN
                      IF(o.id_backpayline IS NOT NULL,
                       "lender_provision_credit_card",
                        IF(o.id_wire_transfer_in IS NOT NULL,
                           "lender_provision_wire_transfer_in",
                           NULL)
                        )
                     WHEN "'. OperationType::BORROWER_COMMISSION . '" THEN
                       IF(o.id_payment_schedule IS NULL, "borrower_commission_project", "borrower_commission_payment")
                      ELSE ot.label END AS movement
                FROM operation o USE INDEX (idx_operation_added_id_type)
                INNER JOIN operation_type ot ON o.id_type = ot.id
                WHERE
                  o.added BETWEEN :start AND :end
                  AND ot.label IN ("' . implode('","', $operationTypes) . '")
                GROUP BY month, movement
                ORDER BY o.added ASC;';

        $result = $this->getEntityManager()->getConnection()
            ->executeQuery($query, ['start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s')])->fetchAll(\PDO::FETCH_ASSOC);

        $movements = [];
        foreach ($result as $row) {
            $movements[$row['month']][$row['movement']] = $row['amount'];
        }

        return $movements;
    }
}
