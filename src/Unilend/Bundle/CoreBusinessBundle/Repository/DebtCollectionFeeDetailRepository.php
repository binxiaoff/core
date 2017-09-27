<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

class DebtCollectionFeeDetailRepository extends EntityRepository
{
    /**
     * @param Receptions|int $wireTransferIn
     * @param Wallet|int     $debtorWallet
     * @param                $status
     *
     * @return float
     */
    public function getTotalDebtCollectionFeeByReception($wireTransferIn, $debtorWallet, $status)
    {
        $queryBuilder = $this->createQueryBuilder('d');
        $queryBuilder->select('SUM(d.amountTaxIncl)')
            ->where('d.idWireTransferIn = :wireTransferIn')
            ->andWhere('d.idWalletDebtor = :debtorWallet')
            ->andWhere('d.status = :status')
            ->setParameter('wireTransferIn', $wireTransferIn)
            ->setParameter('status', $status)
            ->setParameter('debtorWallet', $debtorWallet);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Receptions|int $wireTransferIn
     * @param Wallet|int     $debtorWallet
     * @param int            $status
     *
     * @return int
     */
    public function setDebtCollectionFeeStatusByReception($wireTransferIn, $debtorWallet, $status)
    {
        if ($wireTransferIn instanceof Receptions) {
            $wireTransferIn = $wireTransferIn->getIdReception();
        }

        if ($debtorWallet instanceof Wallet) {
            $debtorWallet = $debtorWallet->getId();
        }
        $update = 'UPDATE debt_collection_fee_detail SET status = :status WHERE id_wire_transfer_in = :wireTransferIn AND id_wallet_debtor = :debtor';

        return $this->getEntityManager()->getConnection()->executeUpdate($update, ['wireTransferIn' => $wireTransferIn, 'status' => $status, 'debtor' => $debtorWallet]);
    }
}
