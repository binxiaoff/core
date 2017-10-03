<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionFeeDetail;
use Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMission;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class DebtCollectionFeeManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var DebtCollectionMissionManager */
    private $debtCollectionMissionManager;

    public function __construct(EntityManager $entityManager, DebtCollectionMissionManager $debtCollectionMissionManager)
    {
        $this->entityManager                = $entityManager;
        $this->debtCollectionMissionManager = $debtCollectionMissionManager;
    }

    /**
     * @param Receptions            $wireTransferIn
     * @param float                 $projectCharge
     * @param DebtCollectionMission $debtCollectionMission
     * @param float                 $debtCollectionFeeRate
     *
     * @return null|float
     * @throws \Exception
     */
    public function applyFeeOnProjectCharge($projectCharge, Receptions $wireTransferIn, DebtCollectionMission $debtCollectionMission, $debtCollectionFeeRate)
    {
        $project                          = $wireTransferIn->getIdProject();
        $isDebtCollectionFeeDueToBorrower = $this->debtCollectionMissionManager->isDebtCollectionFeeDueToBorrower($project);
        $debtCollectionFee                = 0;

        //Treat the project's charges only if the debt collection fee is due to the borrower.
        //Because otherwise, the Unilend takes the charges, and the charges have already been paid before (the charges are created in this case with "paid" status).
        if ($projectCharge && $isDebtCollectionFeeDueToBorrower) {
            $walletRepository    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
            $borrowerWallet      = $walletRepository->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
            $debtCollectorWallet = $walletRepository->getWalletByType($debtCollectionMission->getIdClientDebtCollector(), WalletType::DEBT_COLLECTOR);
            if (null === $debtCollectorWallet) {
                throw new \Exception('The wallet for the debt collector (id client : ' . $debtCollectionMission->getIdClientDebtCollector()->getIdClient() . ')is not defined.');
            }

            $vatTax = $this->entityManager->getRepository('UnilendCoreBusinessBundle:TaxType')->find(TaxType::TYPE_VAT);
            if (null === $vatTax) {
                throw new \Exception('The VAT rate is not defined.');
            }
            $vatTaxRate = round(bcdiv($vatTax->getRate(), 100, 5), 4);

            $debtCollectionFeeOnChargeTaxExcl = round(bcmul($projectCharge, $debtCollectionFeeRate, 4), 2);
            $debtCollectionFeeOnChargeVat     = round(bcmul($debtCollectionFeeOnChargeTaxExcl, $vatTaxRate, 4), 2);
            $debtCollectionFeeOnCharge        = round(bcadd($debtCollectionFeeOnChargeTaxExcl, $debtCollectionFeeOnChargeVat, 4), 2);

            $debtCollectionFeeDetail = new DebtCollectionFeeDetail();
            $debtCollectionFeeDetail->setIdWalletDebtor($borrowerWallet)
                ->setIdWalletCreditor($debtCollectorWallet)
                ->setAmountTaxIncl($debtCollectionFeeOnCharge)
                ->setVat($debtCollectionFeeOnChargeVat)
                ->setIdType(DebtCollectionFeeDetail::TYPE_PROJECT_CHARGE)
                ->setStatus(DebtCollectionFeeDetail::STATUS_PENDING)
                ->setIdWireTransferIn($wireTransferIn)
                ->setIdDebtCollectionMission($debtCollectionMission);

            $this->entityManager->persist($debtCollectionFeeDetail);
            $this->entityManager->flush($debtCollectionFeeDetail);

            $debtCollectionFee = $debtCollectionFeeDetail->getAmountTaxIncl();
        }

        return $debtCollectionFee;
    }

    /**
     * @param float                 $commissionUnilend
     * @param Receptions            $wireTransferIn
     * @param DebtCollectionMission $debtCollectionMission
     * @param float                 $debtCollectionFeeRate
     *
     * @throws \Exception
     * @return float
     */
    public function applyFeeOnUnilendCommission($commissionUnilend, Receptions $wireTransferIn, DebtCollectionMission $debtCollectionMission, $debtCollectionFeeRate)
    {
        $project                          = $wireTransferIn->getIdProject();
        $isDebtCollectionFeeDueToBorrower = $this->debtCollectionMissionManager->isDebtCollectionFeeDueToBorrower($project);

        $vatTax = $this->entityManager->getRepository('UnilendCoreBusinessBundle:TaxType')->find(TaxType::TYPE_VAT);
        if (null === $vatTax) {
            throw new \Exception('The VAT rate is not defined.');
        }
        $vatTaxRate = round(bcdiv($vatTax->getRate(), 100, 5), 4);

        $walletRepository    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $debtCollectorWallet = $walletRepository->getWalletByType($debtCollectionMission->getIdClientDebtCollector(), WalletType::DEBT_COLLECTOR);
        if (null === $debtCollectorWallet) {
            throw new \Exception('The wallet for the debt collector (id client : ' . $debtCollectionMission->getIdClientDebtCollector()->getIdClient() . ')is not defined.');
        }

        $borrowerWallet = $walletRepository->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);

        $debtCollectionFeeOnCommissionTaxExcl = round(bcmul($commissionUnilend, $debtCollectionFeeRate, 4), 2);
        $debtCollectionFeeOnCommissionVat     = round(bcmul($debtCollectionFeeOnCommissionTaxExcl, $vatTaxRate, 4), 2);
        $debtCollectionFeeOnCommission        = round(bcadd($debtCollectionFeeOnCommissionTaxExcl, $debtCollectionFeeOnCommissionVat, 4), 2);

        $debtCollectionFeeDetail = new DebtCollectionFeeDetail();

        if ($isDebtCollectionFeeDueToBorrower) {
            $debtCollectionFeeDetail->setIdWalletDebtor($borrowerWallet);
        } else {
            $unilendWalletType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND]);
            $unilendWallet     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $unilendWalletType]);
            $debtCollectionFeeDetail->setIdWalletDebtor($unilendWallet);
        }

        $debtCollectionFeeDetail->setIdWalletCreditor($debtCollectorWallet)
            ->setAmountTaxIncl($debtCollectionFeeOnCommission)
            ->setVat($debtCollectionFeeOnCommissionVat)
            ->setIdType(DebtCollectionFeeDetail::TYPE_REPAYMENT_COMMISSION)
            ->setStatus(DebtCollectionFeeDetail::STATUS_PENDING)
            ->setIdWireTransferIn($wireTransferIn)
            ->setIdDebtCollectionMission($debtCollectionMission);

        $this->entityManager->persist($debtCollectionFeeDetail);
        $this->entityManager->flush($debtCollectionFeeDetail);

        return $debtCollectionFeeOnCommission;
    }

    /**
     * @param float                 $repaymentAmount
     * @param Loans                 $loan
     * @param Receptions            $wireTransferIn
     * @param DebtCollectionMission $debtCollectionMission
     * @param boolean               $debtCollectionFeeRate
     * @param float                 $isDebtCollectionFeeDueToBorrower
     * @param float                 $vatTaxRate
     * @param Wallet                $debtCollectorWallet
     * @param Wallet                $borrowerWallet
     *
     * @return float
     */
    public function applyFeeOnRepayment(
        $repaymentAmount,
        Loans $loan,
        Receptions $wireTransferIn,
        DebtCollectionMission $debtCollectionMission,
        $debtCollectionFeeRate,
        $isDebtCollectionFeeDueToBorrower,
        $vatTaxRate,
        Wallet $debtCollectorWallet,
        Wallet $borrowerWallet
    )
    {
        $debtCollectionFeeOnRepaymentTaxExcl = round(bcmul($repaymentAmount, $debtCollectionFeeRate, 4), 2);
        $debtCollectionFeeOnRepaymentVat     = round(bcmul($debtCollectionFeeOnRepaymentTaxExcl, $vatTaxRate, 4), 2);
        $debtCollectionFeeOnRepayment        = round(bcadd($debtCollectionFeeOnRepaymentTaxExcl, $debtCollectionFeeOnRepaymentVat, 4), 2);

        $debtCollectionFeeDetail = new DebtCollectionFeeDetail();

        if ($isDebtCollectionFeeDueToBorrower) {
            $debtCollectionFeeDetail->setIdWalletDebtor($borrowerWallet);
        } else {
            $debtCollectionFeeDetail->setIdWalletDebtor($loan->getIdLender());
        }

        $debtCollectionFeeDetail->setIdWalletCreditor($debtCollectorWallet)
            ->setIdLoan($loan)
            ->setAmountTaxIncl($debtCollectionFeeOnRepayment)
            ->setVat($debtCollectionFeeOnRepaymentVat)
            ->setIdType(DebtCollectionFeeDetail::TYPE_LOAN)
            ->setStatus(DebtCollectionFeeDetail::STATUS_PENDING)
            ->setIdWireTransferIn($wireTransferIn)
            ->setIdDebtCollectionMission($debtCollectionMission);

        $this->entityManager->persist($debtCollectionFeeDetail);
        $this->entityManager->flush($debtCollectionFeeDetail);

        return $debtCollectionFeeOnRepayment;
    }
}
