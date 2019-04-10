<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\{Loans, DebtCollectionFeeDetail, Receptions, Wallet};

class DebtCollectionFeeDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DebtCollectionFeeDetail::class);
    }

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
        $update = 'UPDATE debt_collection_fee_detail SET status = :status, updated = NOW() WHERE id_wire_transfer_in = :wireTransferIn AND id_wallet_debtor = :debtor';

        return $this->getEntityManager()->getConnection()->executeUpdate($update, ['wireTransferIn' => $wireTransferIn, 'status' => $status, 'debtor' => $debtorWallet]);
    }

    /**
     * @param Loans|int      $loan
     * @param Receptions|int $wireTransferIn
     *
     * @return array
     */
    public function getAmountsByLoanAndWireTransferIn($loan, $wireTransferIn)
    {
        $queryBuilder = $this->createQueryBuilder('d');
        $queryBuilder->select('SUM(d.amountTaxIncl) as amountTaxIncl, SUM(d.vat) as vat')
            ->where('d.idWireTransferIn = :wireTransferIn')
            ->andWhere('d.idLoan = :loan')
            ->setParameter('wireTransferIn', $wireTransferIn)
            ->setParameter('loan', $loan);

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * @param int            $type
     * @param Receptions|int $wireTransferIn
     *
     * @return array
     */
    public function getAmountsByTypeAndWireTransferIn($type, $wireTransferIn)
    {
        $queryBuilder = $this->createQueryBuilder('d');
        $queryBuilder->select('SUM(d.amountTaxIncl) as amountTaxIncl, SUM(d.vat) as vat')
            ->where('d.idWireTransferIn = :wireTransferIn')
            ->andWhere('d.idType = :type')
            ->setParameter('wireTransferIn', $wireTransferIn)
            ->setParameter('type', $type);

        return $queryBuilder->getQuery()->getSingleResult();
    }

    public function deleteFeesByWireTransferIn($wireTransferIn)
    {
        if ($wireTransferIn instanceof Receptions) {
            $wireTransferIn = $wireTransferIn->getIdReception();
        }

        $delete = 'DELETE FROM debt_collection_fee_detail WHERE id_wire_transfer_in = :wireTransferIn AND status = :pending';

        return $this->getEntityManager()->getConnection()->executeUpdate($delete, ['wireTransferIn' => $wireTransferIn, 'pending' => DebtCollectionFeeDetail::STATUS_PENDING]);
    }
}
