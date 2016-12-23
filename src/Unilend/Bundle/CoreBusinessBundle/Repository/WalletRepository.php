<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;


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
                    WalletType::TAX_RETENUES_A_LA_SOURCE,
                    WalletType::TAX_CONTRIBUTIONS_ADDITIONNELLES,
                    WalletType::TAX_CRDS,
                    WalletType::TAX_CSG,
                    WalletType::TAX_PRELEVEMENTS_DE_SOLIDARITE,
                    WalletType::TAX_PRELEVEMENTS_OBLIGATOIRES,
                    WalletType::TAX_PRELEVEMENTS_SOCIAUX], Connection::PARAM_INT_ARRAY);
        $query = $cb->getQuery();
        return$query->getResult();
    }
}
