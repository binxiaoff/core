<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Entity\{DebtCollectionFeeDetail, DebtCollectionMission, Loans, Receptions, TaxType, Wallet, WalletType};

class DebtCollectionFeeManager
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var DebtCollectionMissionManager */
    private $debtCollectionMissionManager;

    /** @var OperationManager */
    private $operationManager;

    /**
     * @param EntityManagerInterface       $entityManager
     * @param DebtCollectionMissionManager $debtCollectionMissionManager
     * @param OperationManager             $operationManager
     */
    public function __construct(EntityManagerInterface $entityManager, DebtCollectionMissionManager $debtCollectionMissionManager, OperationManager $operationManager)
    {
        $this->entityManager                = $entityManager;
        $this->debtCollectionMissionManager = $debtCollectionMissionManager;
        $this->operationManager             = $operationManager;
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
        //Because otherwise, Unilend takes the charges, and the charges have already been paid before (the charges are created in this case with "paid" status).
        if ($projectCharge && $isDebtCollectionFeeDueToBorrower) {
            $walletRepository    = $this->entityManager->getRepository(Wallet::class);
            $borrowerWallet      = $walletRepository->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
            $debtCollectorWallet = $walletRepository->getWalletByType($debtCollectionMission->getIdClientDebtCollector(), WalletType::DEBT_COLLECTOR);
            if (null === $debtCollectorWallet) {
                throw new \Exception('The wallet for the debt collector (id client : ' . $debtCollectionMission->getIdClientDebtCollector()->getIdClient() . ')is not defined.');
            }

            $vatTax = $this->entityManager->getRepository(TaxType::class)->find(TaxType::TYPE_VAT);
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
                ->setAppliedFeeRate($debtCollectionFeeRate)
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

        $vatTax = $this->entityManager->getRepository(TaxType::class)->find(TaxType::TYPE_VAT);
        if (null === $vatTax) {
            throw new \Exception('The VAT rate is not defined.');
        }
        $vatTaxRate = round(bcdiv($vatTax->getRate(), 100, 5), 4);

        $walletRepository    = $this->entityManager->getRepository(Wallet::class);
        $debtCollectorWallet = $walletRepository->getWalletByType($debtCollectionMission->getIdClientDebtCollector(), WalletType::DEBT_COLLECTOR);
        if (null === $debtCollectorWallet) {
            throw new \Exception('The wallet for the debt collector (id client : ' . $debtCollectionMission->getIdClientDebtCollector()->getIdClient() . ')is not defined.');
        }

        $debtCollectionFeeOnCommissionTaxExcl = round(bcmul($commissionUnilend, $debtCollectionFeeRate, 4), 2);
        $debtCollectionFeeOnCommissionVat     = round(bcmul($debtCollectionFeeOnCommissionTaxExcl, $vatTaxRate, 4), 2);
        $debtCollectionFeeOnCommission        = round(bcadd($debtCollectionFeeOnCommissionTaxExcl, $debtCollectionFeeOnCommissionVat, 4), 2);

        $debtCollectionFeeDetail = new DebtCollectionFeeDetail();

        if ($isDebtCollectionFeeDueToBorrower) {
            $borrowerWallet = $walletRepository->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
            $debtCollectionFeeDetail->setIdWalletDebtor($borrowerWallet);
        } else {
            $unilendWalletType = $this->entityManager->getRepository(WalletType::class)->findOneBy(['label' => WalletType::UNILEND]);
            $unilendWallet     = $this->entityManager->getRepository(Wallet::class)->findOneBy(['idType' => $unilendWalletType]);
            $debtCollectionFeeDetail->setIdWalletDebtor($unilendWallet);
        }

        $debtCollectionFeeDetail->setIdWalletCreditor($debtCollectorWallet)
            ->setAmountTaxIncl($debtCollectionFeeOnCommission)
            ->setVat($debtCollectionFeeOnCommissionVat)
            ->setAppliedFeeRate($debtCollectionFeeRate)
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
            $debtCollectionFeeDetail->setIdWalletDebtor($loan->getWallet());
        }

        $debtCollectionFeeDetail->setIdWalletCreditor($debtCollectorWallet)
            ->setIdLoan($loan)
            ->setAmountTaxIncl($debtCollectionFeeOnRepayment)
            ->setVat($debtCollectionFeeOnRepaymentVat)
            ->setAppliedFeeRate($debtCollectionFeeRate)
            ->setIdType(DebtCollectionFeeDetail::TYPE_LOAN)
            ->setStatus(DebtCollectionFeeDetail::STATUS_PENDING)
            ->setIdWireTransferIn($wireTransferIn)
            ->setIdDebtCollectionMission($debtCollectionMission);

        $this->entityManager->persist($debtCollectionFeeDetail);
        $this->entityManager->flush($debtCollectionFeeDetail);

        return $debtCollectionFeeOnRepayment;
    }

    /**
     * @param Receptions $wireTransferIn
     */
    public function cancelFee(Receptions $wireTransferIn)
    {
        $this->entityManager->getRepository(DebtCollectionFeeDetail::class)->deleteFeesByWireTransferIn($wireTransferIn);
    }

    /**
     * @param Receptions $wireTransferIn
     */
    public function processDebtCollectionFee(Receptions $wireTransferIn)
    {
        $project                           = $wireTransferIn->getIdProject();
        $debtCollectionFeeDetailRepository = $this->entityManager->getRepository(DebtCollectionFeeDetail::class);

        $borrowerWallet            = $this->entityManager->getRepository(Wallet::class)->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        $borrowerDebtCollectionFee = $debtCollectionFeeDetailRepository->getTotalDebtCollectionFeeByReception($wireTransferIn, $borrowerWallet, DebtCollectionFeeDetail::STATUS_PENDING);

        if (1 === bccomp($borrowerDebtCollectionFee, 0, 2)) {
            $debtCollectorWallet = $debtCollectionFeeDetailRepository->findOneBy(['idWireTransferIn' => $wireTransferIn])->getIdWalletCreditor();
            $this->operationManager->payDebtCollectionFee($borrowerWallet, $debtCollectorWallet, $borrowerDebtCollectionFee, [$project, $wireTransferIn]);
            $debtCollectionFeeDetailRepository->setDebtCollectionFeeStatusByReception($wireTransferIn, $borrowerWallet, DebtCollectionFeeDetail::STATUS_TREATED);
        }

        $debtCollectionFeeDetails = $debtCollectionFeeDetailRepository->findBy(['idWireTransferIn' => $wireTransferIn, 'status' => DebtCollectionFeeDetail::STATUS_PENDING]);

        foreach ($debtCollectionFeeDetails as $debtCollectionFeeDetail) {
            $this->operationManager->payDebtCollectionFee(
                $debtCollectionFeeDetail->getIdWalletDebtor(),
                $debtCollectionFeeDetail->getIdWalletCreditor(),
                $debtCollectionFeeDetail->getAmountTaxIncl(),
                [$project, $wireTransferIn]
            );
            $debtCollectionFeeDetail->setStatus(DebtCollectionFeeDetail::STATUS_TREATED);
            $this->entityManager->flush($debtCollectionFeeDetail);
        }
    }
}
