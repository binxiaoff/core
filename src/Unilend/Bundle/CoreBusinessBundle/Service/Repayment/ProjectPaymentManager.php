<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMission;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectCharge;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\DebtCollectionFeeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\DebtCollectionMissionManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectChargeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class ProjectPaymentManager
{
    /** @var EntityManager */
    private $entityManager;

    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;

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
     * @param EntityManagerSimulator       $entityManagerSimulator
     * @param ProjectRepaymentTaskManager  $projectRepaymentTaskManager
     * @param DebtCollectionMissionManager $debtCollectionMissionManager
     * @param DebtCollectionFeeManager     $debtCollectionFeeManager
     * @param ProjectChargeManager         $projectChargeManager
     */
    public function __construct(
        EntityManager $entityManager,
        EntityManagerSimulator $entityManagerSimulator,
        ProjectRepaymentTaskManager $projectRepaymentTaskManager,
        DebtCollectionMissionManager $debtCollectionMissionManager,
        DebtCollectionFeeManager $debtCollectionFeeManager,
        ProjectChargeManager $projectChargeManager
    )
    {
        $this->entityManager                = $entityManager;
        $this->entityManagerSimulator       = $entityManagerSimulator;
        $this->projectRepaymentTaskManager  = $projectRepaymentTaskManager;
        $this->debtCollectionMissionManager = $debtCollectionMissionManager;
        $this->debtCollectionFeeManager     = $debtCollectionFeeManager;
        $this->projectChargeManager         = $projectChargeManager;
    }

    /**
     * @param Receptions                 $wireTransferIn
     * @param Users                      $user
     * @param \DateTime|null             $repayOn
     * @param DebtCollectionMission|null $debtCollectionMission
     * @param float|null                 $debtCollectionFeeRate
     * @param ProjectCharge[]|null       $projectCharges
     *
     * @return bool
     * @throws \Exception
     */
    public function pay(Receptions $wireTransferIn, Users $user, \DateTime $repayOn = null, DebtCollectionMission $debtCollectionMission = null, $debtCollectionFeeRate = null, $projectCharges = null)
    {
        $paymentScheduleRepository   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');
        $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        $walletRepository            = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        /** @var \echeanciers $repaymentScheduleData */
        $repaymentScheduleData = $this->entityManagerSimulator->getRepository('echeanciers');

        $project                          = $wireTransferIn->getIdProject();
        $amount                           = round(bcdiv($wireTransferIn->getMontant(), 100, 4), 2);
        $isDebtCollectionFeeDueToBorrower = $this->debtCollectionMissionManager->isDebtCollectionFeeDueToBorrower($project);

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
            if ($isDebtCollectionFeeDueToBorrower) {
                $amount = round(bcsub($amount, $feeOnProjectCharge, 4), 2);
            }

            $overdueAmounts              = $paymentScheduleRepository->getTotalOverdueAmounts($project);
            $totalUnpaidAmount           = round(bcadd($overdueAmounts['commission'], bcadd($overdueAmounts['capital'], $overdueAmounts['interest'], 4), 4), 2);
            $totalUnpaidCommission       = $overdueAmounts['commission'];
            $notPaidCommissionProportion = bcdiv($totalUnpaidCommission, $totalUnpaidAmount, 10);
            $predictCommission           = round(bcmul($amount, $notPaidCommissionProportion, 4), 2);

            $debtCollectionFeeOnCommission = $this->debtCollectionFeeManager->applyFeeOnUnilendCommission($predictCommission, $wireTransferIn, $debtCollectionMission, $debtCollectionFeeRate);

            $debtCollectionFeeOnRepayment = 0;

            $loans = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->findBy(['idProject' => $project]);

            foreach ($loans as $loan) {
                $notRepaidAmount        = $repaymentScheduleRepository->getTotalOverdueAmountByLoan($loan);
                $notRepaidProportion    = bcdiv($notRepaidAmount, $totalUnpaidAmount, 10);
                $predictRepaymentAmount = round(bcmul($amount, $notRepaidProportion, 4), 2);

                $debtCollectionFeeOnLoan = $this->debtCollectionFeeManager
                    ->applyFeeOnRepayment(
                        $predictRepaymentAmount, $loan, $wireTransferIn,
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

        $unpaidPaymentSchedules = $paymentScheduleRepository->findBy([
            'idProject'        => $project,
            'statusEmprunteur' => [EcheanciersEmprunteur::STATUS_PENDING, EcheanciersEmprunteur::STATUS_PARTIALLY_PAID]
        ], ['ordre' => 'ASC']);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            foreach ($unpaidPaymentSchedules as $paymentSchedule) {
                if (1 !== bccomp($amount, 0, 2)) {
                    break;
                }

                $unpaidCapital            = round(bcdiv($paymentSchedule->getCapital() - $paymentSchedule->getPaidCapital(), 100, 4), 2);
                $unpaidInterest           = round(bcdiv($paymentSchedule->getInterets() - $paymentSchedule->getPaidInterest(), 100, 4), 2);
                $unpaidCommission         = round(bcdiv($paymentSchedule->getCommission() + $paymentSchedule->getTva() - $paymentSchedule->getPaidCommissionVatIncl(), 100, 4), 2);
                $unpaidNetRepaymentAmount = round(bcadd($unpaidCapital, $unpaidInterest, 4), 2);
                $unpaidMonthlyAmount      = round(bcadd($unpaidNetRepaymentAmount, $unpaidCommission, 4), 2);

                $compareResult = bccomp($amount, $unpaidMonthlyAmount, 2);
                if (0 === $compareResult || 1 === $compareResult) {
                    $capitalToPay    = $unpaidCapital;
                    $interestToPay   = $unpaidInterest;
                    $commissionToPay = $unpaidCommission;
                } else {
                    $proportion         = bcdiv($amount, $unpaidMonthlyAmount, 10);
                    $netRepaymentAmount = round(bcmul($unpaidNetRepaymentAmount, $proportion, 4), 2);

                    $restOfAmount    = round(bcsub($amount, $netRepaymentAmount, 4), 2);
                    $commissionToPay = min($unpaidCommission, $restOfAmount);

                    $stillRest = round(bcsub($restOfAmount, $commissionToPay, 4), 2);
                    if (1 == bccomp($stillRest, 0, 2)) {
                        $netRepaymentAmount = round(bcadd($netRepaymentAmount, $stillRest, 4), 2);
                    }

                    $capitalToPay  = min($netRepaymentAmount, $unpaidCapital);
                    $interestToPay = round(bcsub($netRepaymentAmount, $capitalToPay, 4), 2);
                }

                $paymentSchedule->setPaidCapital($paymentSchedule->getPaidCapital() + bcmul($capitalToPay, 100))
                    ->setPaidInterest($paymentSchedule->getPaidInterest() + bcmul($interestToPay, 100))
                    ->setPaidCommissionVatIncl($paymentSchedule->getPaidCommissionVatIncl() + bcmul($commissionToPay,
                            100));
                if (
                    $paymentSchedule->getCapital() == $paymentSchedule->getPaidCapital()
                    && $paymentSchedule->getInterets() == $paymentSchedule->getPaidInterest()
                    && $paymentSchedule->getPaidCommissionVatIncl() == $paymentSchedule->getCommission() + $paymentSchedule->getTva()
                ) {
                    $paymentSchedule->setStatusEmprunteur(EcheanciersEmprunteur::STATUS_PAID)
                        ->setDateEcheanceEmprunteurReel(new \DateTime());

                    // todo: this call can be deleted once all migrations have been done on the usage of these 2 columns.
                    $repaymentScheduleData->updateStatusEmprunteur($project->getIdProject(), $paymentSchedule->getOrdre());

                } else {
                    $paymentSchedule->setStatusEmprunteur(EcheanciersEmprunteur::STATUS_PARTIALLY_PAID);
                }

                $this->entityManager->flush($paymentSchedule);

                $repaymentSchedule = $repaymentScheduleRepository->findOneBy([
                    'idProject' => $project,
                    'ordre'     => $paymentSchedule->getOrdre()
                ]);

                $this->projectRepaymentTaskManager->planRepaymentTask($repaymentSchedule, $capitalToPay, $interestToPay, $commissionToPay, $repayOn, $wireTransferIn, $user, $debtCollectionMission);

                $paidAmount = round(bcadd(bcadd($capitalToPay, $interestToPay, 4), $commissionToPay, 4), 2);
                $amount     = round(bcsub($amount, $paidAmount, 4), 2);
            }

            $this->entityManager->getConnection()->commit();

            return true;
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw $exception;
        }
    }

    /**
     * @param Receptions $wireTransferIn
     * @param Users      $user
     *
     * @throws \Exception
     */
    public function rejectPayment(Receptions $wireTransferIn, Users $user)
    {
        $paymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');
        /** @var \echeanciers $repaymentScheduleData */
        $repaymentScheduleData = $this->entityManagerSimulator->getRepository('echeanciers');

        $project                       = $wireTransferIn->getIdProject();
        $projectRepaymentTasksToCancel = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')
            ->findBy([
                'idProject'        => $project,
                'idWireTransferIn' => $wireTransferIn
            ]);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            foreach ($projectRepaymentTasksToCancel as $task) {
                $paymentSchedule = $paymentScheduleRepository->findOneBy(['idProject' => $project, 'ordre' => $task->getSequence()]);

                $paidCapital    = $paymentSchedule->getPaidCapital() - bcmul($task->getCapital(), 100);
                $paidInterest   = $paymentSchedule->getPaidInterest() - bcmul($task->getInterest(), 100);
                $paidCommission = $paymentSchedule->getPaidCommissionVatIncl() - bcmul($task->getCommissionUnilend(), 100);

                if (
                    0 === bccomp($paidCapital, 0, 2)
                    && 0 === bccomp($paidInterest, 0, 2)
                    && 0 === bccomp($paidCommission, 0, 2)
                ) {
                    $status = EcheanciersEmprunteur::STATUS_PENDING;
                } else {
                    $status = EcheanciersEmprunteur::STATUS_PARTIALLY_PAID;
                }

                $paymentSchedule->setPaidCapital($paidCapital)
                    ->setPaidInterest($paidInterest)
                    ->setPaidCommissionVatIncl($paidCommission)
                    ->setStatusEmprunteur($status)
                    ->setDateEcheanceEmprunteurReel(null);

                $this->entityManager->flush($paymentSchedule);

                // todo: this call can be deleted once all migrations have been done on the usage of these 2 columns.
                $repaymentScheduleData->updateStatusEmprunteur($project->getIdProject(), $paymentSchedule->getOrdre(), 'cancel');

                if (in_array($task->getStatus(), [ProjectRepaymentTask::STATUS_PENDING, ProjectRepaymentTask::STATUS_READY])) {
                    $this->projectRepaymentTaskManager->cancelRepaymentTask($task, $user);
                }
            }

            $this->projectChargeManager->cancelProjectCharge($wireTransferIn);

            $this->debtCollectionFeeManager->cancelFee($wireTransferIn);

            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw $exception;
        }
    }
}
