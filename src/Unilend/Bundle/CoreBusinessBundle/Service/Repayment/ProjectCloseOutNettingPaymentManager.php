<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Entity\{CloseOutNettingPayment, CloseOutNettingRepayment, DebtCollectionMission, ProjectCharge, ProjectRepaymentTask, Receptions, TaxType, Users, Wallet, WalletType};
use Unilend\Bundle\CoreBusinessBundle\Service\{DebtCollectionFeeManager, DebtCollectionMissionManager, ProjectChargeManager};

class ProjectCloseOutNettingPaymentManager
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ProjectRepaymentTaskManager */
    private $projectRepaymentTaskManager;

    /** @var DebtCollectionMissionManager */
    private $debtCollectionMissionManager;

    /** @var DebtCollectionFeeManager */
    private $debtCollectionFeeManager;

    /** @var ProjectChargeManager */
    private $projectChargeManager;

    /**
     * @param EntityManagerInterface       $entityManager
     * @param ProjectRepaymentTaskManager  $projectRepaymentTaskManager
     * @param DebtCollectionMissionManager $debtCollectionMissionManager
     * @param DebtCollectionFeeManager     $debtCollectionFeeManager
     * @param ProjectChargeManager         $projectChargeManager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ProjectRepaymentTaskManager $projectRepaymentTaskManager,
        DebtCollectionMissionManager $debtCollectionMissionManager,
        DebtCollectionFeeManager $debtCollectionFeeManager,
        ProjectChargeManager $projectChargeManager
    )
    {
        $this->entityManager                = $entityManager;
        $this->projectRepaymentTaskManager  = $projectRepaymentTaskManager;
        $this->debtCollectionMissionManager = $debtCollectionMissionManager;
        $this->debtCollectionFeeManager     = $debtCollectionFeeManager;
        $this->projectChargeManager         = $projectChargeManager;
    }

    /**
     * @param Receptions                 $wireTransferIn
     * @param Users                      $user
     * @param \DateTime                  $repayOn
     * @param DebtCollectionMission|null $debtCollectionMission
     * @param float|null                 $debtCollectionFeeRate
     * @param ProjectCharge[]|null       $projectCharges
     *
     * @throws \Exception
     */
    public function pay(Receptions $wireTransferIn, Users $user, \DateTime $repayOn, DebtCollectionMission $debtCollectionMission = null, $debtCollectionFeeRate = null, $projectCharges = null)
    {
        $walletRepository                 = $this->entityManager->getRepository(Wallet::class);
        $closeOutNettingPaymentRepository = $this->entityManager->getRepository(CloseOutNettingPayment::class);

        $project                          = $wireTransferIn->getIdProject();
        $amount                           = round(bcdiv($wireTransferIn->getMontant(), 100, 4), 2);
        $isDebtCollectionFeeDueToBorrower = $this->debtCollectionMissionManager->isDebtCollectionFeeDueToBorrower($project);

        $ongoingProjectRepaymentTask = $this->entityManager->getRepository(ProjectRepaymentTask::class)->findOneBy([
            'idProject' => $project,
            'status'    => ProjectRepaymentTask::STATUS_IN_PROGRESS
        ]);

        if (null !== $ongoingProjectRepaymentTask) {
            throw new \Exception('Another repayment task of the same project (id : ' . $project->getIdProject() . ') is in progress. The task creation of this project is temporarily disabled.');
        }

        $borrowerWallet = $walletRepository->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        if (-1 === bccomp($borrowerWallet->getAvailableBalance(), $amount, 2)) {
            throw new \Exception('The borrower balance (' . $borrowerWallet->getAvailableBalance() . ') is lower than the amount (' . $amount . ') to treat.');
        }

        $closeOutNettingPayment = $closeOutNettingPaymentRepository->findOneBy(['idProject' => $project]);
        $unpaidCapital          = round(bcsub($closeOutNettingPayment->getCapital(), $closeOutNettingPayment->getPaidCapital(), 4), 2);
        $unpaidInterest         = round(bcsub($closeOutNettingPayment->getInterest(), $closeOutNettingPayment->getPaidInterest(), 4), 2);
        $unpaidCommission       = round(bcsub($closeOutNettingPayment->getCommissionTaxIncl(), $closeOutNettingPayment->getPaidCommissionTaxIncl(), 4), 2);
        $totalUnpaidAmount      = round(bcadd($unpaidCommission, bcadd($unpaidCapital, $unpaidInterest, 4), 4), 2);

        $debtCollectorWallet = null;
        if ($debtCollectionMission) {
            $debtCollectorWallet = $walletRepository->getWalletByType($debtCollectionMission->getIdClientDebtCollector(), WalletType::DEBT_COLLECTOR);
            if (null === $debtCollectorWallet) {
                throw new \Exception('The wallet for the debt collector (id client : ' . $debtCollectionMission->getIdClientDebtCollector()->getIdClient() . ')is not defined.');
            }
        }

        $vatTax = $this->entityManager->getRepository(TaxType::class)->find(TaxType::TYPE_VAT);
        if (null === $vatTax) {
            throw new \Exception('The VAT rate is not defined.');
        }
        $vatTaxRate = round(bcdiv($vatTax->getRate(), 100, 5), 4);

        $totalAppliedCharge = $this->projectChargeManager->applyProjectCharge($wireTransferIn, $projectCharges);

        $amount = round(bcsub($amount, $totalAppliedCharge, 4), 2);

        if ($debtCollectionMission && $debtCollectionFeeRate) {
            $feeOnProjectCharge = $this->debtCollectionFeeManager->applyFeeOnProjectCharge($totalAppliedCharge, $wireTransferIn, $debtCollectionMission, $debtCollectionFeeRate);
            if ($isDebtCollectionFeeDueToBorrower) {
                $amount = round(bcsub($amount, $feeOnProjectCharge, 4), 2);
            }
            $notPaidCommissionProportion = bcdiv($unpaidCommission, $totalUnpaidAmount, 10);
            $predictCommission           = round(bcmul($amount, $notPaidCommissionProportion, 4), 2);

            $debtCollectionFeeOnCommission = $this->debtCollectionFeeManager->applyFeeOnUnilendCommission($predictCommission, $wireTransferIn, $debtCollectionMission, $debtCollectionFeeRate);

            $debtCollectionFeeOnRepayment = 0;

            $closeOutNettingRepayments = $this->entityManager->getRepository(CloseOutNettingRepayment::class)->findByProject($project);

            foreach ($closeOutNettingRepayments as $closeOutNettingRepayment) {
                $notRepaidCapital       = round(bcsub($closeOutNettingRepayment->getCapital(), $closeOutNettingRepayment->getRepaidCapital(), 4), 2);
                $notRepaidInterest      = round(bcsub($closeOutNettingRepayment->getInterest(), $closeOutNettingRepayment->getRepaidInterest(), 4), 2);
                $notRepaidAmount        = round(bcadd($notRepaidCapital, $notRepaidInterest, 4), 2);
                $notRepaidProportion    = bcdiv($notRepaidAmount, $totalUnpaidAmount, 10);
                $predictRepaymentAmount = round(bcmul($amount, $notRepaidProportion, 4), 2);

                $debtCollectionFeeOnLoan = $this->debtCollectionFeeManager
                    ->applyFeeOnRepayment(
                        $predictRepaymentAmount, $closeOutNettingRepayment->getIdLoan(), $wireTransferIn,
                        $debtCollectionMission, $debtCollectionFeeRate, $isDebtCollectionFeeDueToBorrower,
                        $vatTaxRate, $debtCollectorWallet, $borrowerWallet
                    );

                $debtCollectionFeeOnRepayment = round(bcadd($debtCollectionFeeOnRepayment, $debtCollectionFeeOnLoan, 4), 2);
            }

            if ($isDebtCollectionFeeDueToBorrower) {
                $amount = round(bcsub($amount, $debtCollectionFeeOnCommission, 4), 2);
                $amount = round(bcsub($amount, $debtCollectionFeeOnRepayment, 4), 2);
            }
        }

        $unpaidAmountWithoutCommission = round(bcadd($unpaidCapital, $unpaidInterest, 4), 2);
        $unpaidAmountWithCommission    = round(bcadd($unpaidAmountWithoutCommission, $unpaidCommission, 4), 2);

        $compareResult = bccomp($amount, $unpaidAmountWithCommission, 2);
        if (0 === $compareResult) {
            $capitalToPay    = $unpaidCapital;
            $interestToPay   = $unpaidInterest;
            $commissionToPay = $unpaidCommission;
        } elseif (-1 === $compareResult) {
            $proportion         = bcdiv($amount, $unpaidAmountWithCommission, 10);
            $netRepaymentAmount = round(bcmul($unpaidAmountWithoutCommission, $proportion, 4), 2);

            $restOfAmount    = round(bcsub($amount, $netRepaymentAmount, 4), 2);
            $commissionToPay = min($unpaidCommission, $restOfAmount);

            $stillRest = round(bcsub($restOfAmount, $commissionToPay, 4), 2);
            if (1 == bccomp($stillRest, 0, 2)) {
                $netRepaymentAmount = round(bcadd($netRepaymentAmount, $stillRest, 4), 2);
            }

            $capitalToPay  = min($netRepaymentAmount, $unpaidCapital);
            $interestToPay = round(bcsub($netRepaymentAmount, $capitalToPay, 4), 2);
        } else {
            throw new \Exception('The received amount (' . $amount . ') in reception (id : ' . $wireTransferIn->getIdReception() . ') is more than the remaining amount (' . $unpaidAmountWithCommission . ') on the project (id : ' . $project->getIdProject() . ').');
        }

        $paidCapital    = round(bcadd($closeOutNettingPayment->getPaidCapital(), $capitalToPay, 4), 2);
        $paidInterest   = round(bcadd($closeOutNettingPayment->getPaidInterest(), $interestToPay, 4), 2);
        $paidCommission = round(bcadd($closeOutNettingPayment->getPaidCommissionTaxIncl(), $commissionToPay, 4), 2);

        $closeOutNettingPayment->setPaidCapital($paidCapital)
            ->setPaidInterest($paidInterest)
            ->setPaidCommissionTaxIncl($paidCommission);

        $this->entityManager->flush($closeOutNettingPayment);

        $this->projectRepaymentTaskManager->planCloseOutNettingRepaymentTask($capitalToPay, $interestToPay, $commissionToPay, $repayOn, $wireTransferIn, $user, $debtCollectionMission);
    }

    /**
     * @param Receptions $wireTransferIn
     * @param Users      $user
     *
     * @throws \Exception
     */
    public function rejectPayment(Receptions $wireTransferIn, Users $user)
    {
        $project                      = $wireTransferIn->getIdProject();
        $projectRepaymentTaskToCancel = $this->entityManager->getRepository(ProjectRepaymentTask::class)
            ->findOneBy([
                'idProject'        => $project,
                'idWireTransferIn' => $wireTransferIn,
                'type'             => ProjectRepaymentTask::TYPE_CLOSE_OUT_NETTING,
                'status'           => [
                    ProjectRepaymentTask::STATUS_ERROR,
                    ProjectRepaymentTask::STATUS_PENDING,
                    ProjectRepaymentTask::STATUS_READY,
                    ProjectRepaymentTask::STATUS_IN_PROGRESS,
                    ProjectRepaymentTask::STATUS_REPAID
                ]
            ]);

        if ($projectRepaymentTaskToCancel) {
            $closeOutNettingPaymentRepository = $this->entityManager->getRepository(CloseOutNettingPayment::class);
            $this->entityManager->getConnection()->beginTransaction();
            try {
                $closeOutNettingPayment = $closeOutNettingPaymentRepository->findOneBy(['idProject' => $project]);

                $paidCapital    = round(bcsub($closeOutNettingPayment->getPaidCapital(), $projectRepaymentTaskToCancel->getCapital()), 2);
                $paidInterest   = round(bcsub($closeOutNettingPayment->getPaidInterest(), $projectRepaymentTaskToCancel->getInterest()), 2);
                $paidCommission = round(bcsub($closeOutNettingPayment->getPaidCommissionTaxIncl(), $projectRepaymentTaskToCancel->getCommissionUnilend()), 2);

                $closeOutNettingPayment->setPaidCapital($paidCapital)
                    ->setPaidInterest($paidInterest)
                    ->setPaidCommissionTaxIncl($paidCommission);

                $this->entityManager->flush($closeOutNettingPayment);

                $this->projectRepaymentTaskManager->cancelRepaymentTask($projectRepaymentTaskToCancel, $user);

                $this->projectChargeManager->cancelProjectCharge($wireTransferIn);

                $this->debtCollectionFeeManager->cancelFee($wireTransferIn);

                $this->entityManager->getConnection()->commit();
            } catch (\Exception $exception) {
                $this->entityManager->getConnection()->rollBack();
                throw $exception;
            }
        }
    }
}
