<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;


class WalletRepository extends EntityRepository
{

    /**
     * @param int $walletId
     * @param string $bankAccountUsageType
     *
     * @return mixed
     */
    public function getBankAccountByUsage($walletId, $bankAccountUsageType)
    {
        $cb = $this->createQueryBuilder('w');
        $cb->select('ba')
            ->innerJoin('UnilendCoreBusinessBundle:BankAccountUsage', 'bau', Join::WITH, 'bau.idWallet = w.id')
            ->innerJoin('UnilendCoreBusinessBundle:BankAccountUsageType', 'baut', Join::WITH, 'bau.idUsageType = baut.id')
            ->innerJoin('UnilendCoreBusinessBundle:BankAccount', 'ba', Join::WITH, 'bau.idBankAccount = ba.id')
            ->where('w.id = :walletId')
            ->andWhere('baut.label = :bankAccountUsageType')
            ->setParameters(['walletId' => $walletId, 'bankAccountUsageType' => $bankAccountUsageType]);
        $query = $cb->getQuery();
        $result = $query->getOneOrNullResult();

        return $result;
    }
}
