<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\Operation;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
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
     * @param int       $idClient
     * @param \DateTime $end
     *
     * @return bool|string
     */
    public function getRemainingDueCapitalAtDate($idClient, \DateTime $end)
    {
        $end->setTime(23, 59, 59);

        $query = '
            SELECT
              SUM(o_loan.amount) - SUM(o_repayment.amount)
            FROM wallet_balance_history wbh
              INNER JOIN wallet w ON wbh.id_wallet = w.id
              LEFT JOIN operation o_loan ON wbh.id_operation = o_loan.id AND o_loan.id_type = (SELECT id FROM operation_type WHERE label = "' . OperationType::LENDER_LOAN . '")
              LEFT JOIN operation o_repayment ON wbh.id_operation = o_repayment.id AND o_repayment.id_type = (SELECT id FROM operation_type WHERE label = "' . OperationType::CAPITAL_REPAYMENT . '")
            WHERE wbh.added <= :end
              AND wbh.id_project IN (SELECT DISTINCT (p.id_project) FROM projects p
                                       INNER JOIN projects_status_history psh ON p.id_project = psh.id_project
                                       INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                                     WHERE ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                                       AND psh.added <= :end
                                       AND p.status != ' . ProjectsStatus::DEFAUT . ')
              AND w.id_client = :idClient';

        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, ['end' => $end->format('Y-m-d H:i:s'), 'idClient' => $idClient]);

        return $statement->fetchColumn();
    }
}
