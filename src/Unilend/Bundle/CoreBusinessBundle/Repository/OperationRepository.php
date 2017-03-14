<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\EntityRepository;
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
}
