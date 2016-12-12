<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;


class WalletRepository extends EntityRepository
{

    public function getWalletBankAccountUsage($walletId, $bankAccountUsageType)
    {
        $cb = $this->createQueryBuilder('w');
        $cb->select('bau')
            ->innerJoin('UnilendCoreBusinessBundle:BankAccountUsage', 'bau', Join::WITH, 'bau.idWallet = w.id')
            ->innerJoin('UnilendCoreBusinessBundle:BankAccountUsageType', 'baut', Join::WITH, 'bau.idUsageType = baut.id')
            ->where('w.id = :walletId')
            ->andWhere('baut.label = :bankAccountUsageType')
            ->setParameters(['walletId' => $walletId, 'bankAccountUsageType' => $bankAccountUsageType]);
        $query = $cb->getQuery();
        $result = $query->getOneOrNullResult();

        return $result;
    }
}
