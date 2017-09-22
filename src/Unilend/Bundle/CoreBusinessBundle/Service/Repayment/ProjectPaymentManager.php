<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMission;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectCharge;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\DebtCollectionMissionManager;
use Unilend\Bundle\CoreBusinessBundle\Service\OperationManager;

class ProjectPaymentManager
{
    /** @var EntityManager */
    private $entityManager;

    /** @var ProjectRepaymentTaskManager */
    private $projectRepaymentTaskManager;

    /** @var DebtCollectionMissionManager */
    private $debtCollectionManager;

    /** @var OperationManager */
    private $operationManager;

    /**
     * ProjectRepaymentManager constructor.
     *
     * @param EntityManager                $entityManager
     * @param ProjectRepaymentTaskManager  $projectRepaymentTaskManager
     * @param DebtCollectionMissionManager $debtCollectionManager
     * @param OperationManager             $operationManager
     */
    public function __construct(
        EntityManager $entityManager,
        ProjectRepaymentTaskManager $projectRepaymentTaskManager,
        DebtCollectionMissionManager $debtCollectionManager,
        OperationManager $operationManager
    )
    {
        $this->entityManager               = $entityManager;
        $this->projectRepaymentTaskManager = $projectRepaymentTaskManager;
        $this->debtCollectionManager       = $debtCollectionManager;
        $this->operationManager            = $operationManager;
    }

    /**
     * @param Receptions                 $reception
     * @param Users                      $user
     * @param DebtCollectionMission|null $debtCollectionMission
     * @param float|null                 $debtCollectionFeeRate
     * @param ProjectCharge[]|null       $projectCharges
     *
     * @return bool
     * @throws \Exception
     */
    public function pay(Receptions $reception, Users $user, DebtCollectionMission $debtCollectionMission = null, $debtCollectionFeeRate = null, $projectCharges = null)
    {
        $repaymentScheduleRepository      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        $projectRepaymentDetailRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentDetail');
        $walletRepository                 = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');

        $project                          = $reception->getIdProject();
        $amount                           = round(bcdiv($reception->getMontant(), 100, 4), 2);
        $isDebtCollectionFeeDueToBorrower = $this->debtCollectionManager->isDebtCollectionFeeDueToBorrower($project);

        $borrowerWallet  = null;
        $collectorWallet = null;
        if ($debtCollectionMission && $debtCollectionFeeRate && $isDebtCollectionFeeDueToBorrower) {
            $borrowerWallet  = $walletRepository->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
            $collectorWallet = $walletRepository->getWalletByType($debtCollectionMission->getIdClientDebtCollector(), WalletType::DEBT_COLLECTOR);
        }

        $debtCollectionFeeOnRepaymentAmount = 0;
        $debtCollectionFeeOnCommission      = 0;
        $debtCollectionFeeOnCharge          = 0;

        if ($projectCharges) {
            if ($isDebtCollectionFeeDueToBorrower) {
                $totalAppliedCharges = 0;
                foreach ($projectCharges as $projectCharge) {
                    $totalAppliedCharges = round(bcadd($totalAppliedCharges, $projectCharge->getAmountInclVat(), 4), 2);
                    $projectCharge->setStatus(ProjectCharge::STATUS_PAID)
                        ->setIdWireTransferIn($reception);

                    $this->entityManager->flush($projectCharge);
                }

                $debtCollectionFeeOnCharge = round(bcmul($totalAppliedCharges, $debtCollectionFeeRate, 4), 2);
                $amount                    = round(bcsub($amount, bcadd($totalAppliedCharges, $debtCollectionFeeOnCharge, 4), 4), 2);
            }
        }

        $unpaidPaymentSchedules = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findUnFinishedSchedules($project);

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

                $repaymentSchedule = $repaymentScheduleRepository->findOneBy([
                    'idProject' => $project,
                    'ordre'     => $paymentSchedule->getOrdre()
                ]);

                $projectRepaymentTask = $this->projectRepaymentTaskManager->planRepaymentTask($repaymentSchedule, $capitalToPay, $interestToPay, $commissionToPay, $reception, $user,
                    $debtCollectionMission, $debtCollectionFeeRate, $projectCharges);
                $this->projectRepaymentTaskManager->prepare($projectRepaymentTask);

                if ($debtCollectionMission && $debtCollectionFeeRate && $this->debtCollectionManager->isDebtCollectionFeeDueToBorrower($projectRepaymentTask->getIdProject())) {
                    $debtCollectionFeeOnRepaymentAmount = $projectRepaymentDetailRepository->getTotalDebtCollectionFeeToPayByTask($projectRepaymentTask);
                    $debtCollectionFeeOnCommission      = round(bcmul($commissionToPay, $debtCollectionFeeRate, 4), 2);
                }

                $paidAmount = round(bcadd(bcadd($capitalToPay, $interestToPay, 4), $commissionToPay, 4), 2);
                $amount     = round(bcsub($amount, $paidAmount, 4), 2);

                if (1 === bccomp($amount, 0, 2)) {
                    //todo: sub the fee on the repayment or on the amount
                }

                $this->projectRepaymentTaskManager->checkPlannedTaskAmount($paymentSchedule->getIdProject(), $repaymentSchedule->getOrdre());

                $paymentSchedule->setPaidCapital($paymentSchedule->getPaidCapital() + bcmul($capitalToPay, 100))
                    ->setPaidInterest($paymentSchedule->getPaidInterest() + bcmul($interestToPay, 100))
                    ->setPaidCommissionVatIncl($paymentSchedule->getPaidCommissionVatIncl() + bcmul($commissionToPay, 100));
                if (
                    $paymentSchedule->getCapital() == $paymentSchedule->getPaidCapital()
                    && $paymentSchedule->getInterets() == $paymentSchedule->getPaidInterest()
                    && $paymentSchedule->getPaidCommissionVatIncl() == $paymentSchedule->getCommission() + $paymentSchedule->getTva()
                ) {
                    $paymentSchedule->setStatusEmprunteur(EcheanciersEmprunteur::STATUS_PAID)
                        ->setDateEcheanceEmprunteurReel(new \DateTime());
                } else {
                    $paymentSchedule->setStatusEmprunteur(EcheanciersEmprunteur::STATUS_PARTIALLY_PAID);
                }

                $this->entityManager->flush($paymentSchedule);
            }

            $debtCollectionFeeToPayByBorrower = round(bcadd($debtCollectionFeeOnRepaymentAmount, bcadd($debtCollectionFeeOnCharge, $debtCollectionFeeOnCommission, 4), 4), 2);
            if (1 === bccomp($debtCollectionFeeToPayByBorrower, 0, 2)) {

                $this->operationManager->payCollectionCommissionByBorrower($borrowerWallet, $collectorWallet, $debtCollectionFeeToPayByBorrower, [$project, $reception]);
            }

            $this->entityManager->getConnection()->commit();

            return true;
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw $exception;
        }
    }

    /**
     * @param Receptions $reception
     * @param Users      $user
     *
     * @throws \Exception
     */
    public function rejectPayment(Receptions $reception, Users $user)
    {
        $paymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');
        $project                   = $reception->getIdProject();

        $projectRepaymentTasksToCancel = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')
            ->findBy(['idProject' => $project, 'idWireTransferIn' => $reception->getIdReceptionRejected()]);

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

                $this->projectRepaymentTaskManager->cancelRepaymentTask($task, $user);
            }
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw $exception;
        }
    }
}
