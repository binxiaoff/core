<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMission;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectCharge;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\DebtCollectionFeeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\DebtCollectionMissionManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectChargeManager;

class ProjectCloseOutNettingPaymentManager
{
    /** @var EntityManager */
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
     * ProjectRepaymentManager constructor.
     *
     * @param EntityManager                $entityManager
     * @param ProjectRepaymentTaskManager  $projectRepaymentTaskManager
     * @param DebtCollectionMissionManager $debtCollectionMissionManager
     * @param DebtCollectionFeeManager     $debtCollectionFeeManager
     * @param ProjectChargeManager         $projectChargeManager
     */
    public function __construct(
        EntityManager $entityManager,
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
        $walletRepository                 = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $closeOutNettingPaymentRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingPayment');

        $project                          = $wireTransferIn->getIdProject();
        $amount                           = round(bcdiv($wireTransferIn->getMontant(), 100, 4), 2);
        $isDebtCollectionFeeDueToBorrower = $this->debtCollectionMissionManager->isDebtCollectionFeeDueToBorrower($project);

        $closeOutNettingPayment = $closeOutNettingPaymentRepository->findOneBy(['idProject' => $project]);
        $unpaidCapital          = round(bcsub($closeOutNettingPayment->getCapital(), $closeOutNettingPayment->getPaidCapital(), 4), 2);
        $unpaidInterest         = round(bcsub($closeOutNettingPayment->getInterest(), $closeOutNettingPayment->getPaidInterest(), 4), 2);
        $unpaidCommission       = round(bcsub($closeOutNettingPayment->getCommission(), $closeOutNettingPayment->getPaidCommission(), 4), 2);
        $totalUnpaidAmount      = round(bcadd($unpaidCommission, bcadd($unpaidCapital, $unpaidInterest, 4), 4), 2);

        $debtCollectorWallet = null;
        if ($debtCollectionMission) {
            $debtCollectorWallet = $walletRepository->getWalletByType($debtCollectionMission->getIdClientDebtCollector(), WalletType::DEBT_COLLECTOR);
            if (null === $debtCollectorWallet) {
                throw new \Exception('The wallet for the debt collector (id client : ' . $debtCollectionMission->getIdClientDebtCollector()->getIdClient() . ')is not defined.');
            }
        }
        $borrowerWallet = $walletRepository->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);

        $vatTax = $this->entityManager->getRepository('UnilendCoreBusinessBundle:TaxType')->find(TaxType::TYPE_VAT);
        if (null === $vatTax) {
            throw new \Exception('The VAT rate is not defined.');
        }
        $vatTaxRate = round(bcdiv($vatTax->getRate(), 100, 5), 4);

        $totalAppliedCharge = $this->projectChargeManager->applyProjectCharge($wireTransferIn, $projectCharges);

        $amount = round(bcsub($amount, $totalAppliedCharge, 4), 2);

        if ($debtCollectionMission && $debtCollectionFeeRate) {
            $feeOnProjectCharge = $this->debtCollectionFeeManager->applyFeeOnProjectCharge($totalAppliedCharge, $wireTransferIn, $debtCollectionMission, $debtCollectionFeeRate);
            $amount             = round(bcsub($amount, $feeOnProjectCharge, 4), 2);

            $notPaidCommissionProportion = bcdiv($unpaidCommission, $totalUnpaidAmount, 10);
            $predictCommission           = round(bcmul($amount, $notPaidCommissionProportion, 4), 2);

            $debtCollectionFeeOnCommission = $this->debtCollectionFeeManager->applyFeeOnUnilendCommission($predictCommission, $wireTransferIn, $debtCollectionMission, $debtCollectionFeeRate);

            $debtCollectionFeeOnRepayment = 0;

            $closeOutNettingRepayments = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingRepayment')->findBy(['idProject' => $project]);

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
        $closeOutNettingPaymentRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingPayment');

        $project = $wireTransferIn->getIdProject();

        $projectRepaymentTaskToCancel = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')
            ->findOneBy(['idProject' => $project, 'idWireTransferIn' => $wireTransferIn->getIdReceptionRejected()]);

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
