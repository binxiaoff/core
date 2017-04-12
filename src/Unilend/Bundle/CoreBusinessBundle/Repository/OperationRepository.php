<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\Operation;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

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
     * @param $idRepaymentSchedule
     *
     * @return mixed
     */
    public function getTaxAmountByRepaymentScheduleId($idRepaymentSchedule)
    {
        $qb = $this->createQueryBuilder('o');
        $qb->select('SUM(amount')
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label IN (:taxTypes)')
            ->andWhere('o.idRepaymentSchedule = :idRepaymentSchedule')
            ->setParameter('taxTypes', OperationType::TAX_TYPES_FR, Connection::PARAM_STR_ARRAY)
            ->setParameter('idRepaymentSchedule', $idRepaymentSchedule);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param $idRepaymentSchedule
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

        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, ['idRepaymentSchedule' => $idRepaymentSchedule]);
        //TODO implement cache
        return $statement->fetch();

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


        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, ['idWallet' => $wallet->getId(), 'date' => $date]);
        //TODO implement cache
        return $statement->fetch();
    }
}
