<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionFeeDetail;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectCharge;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentDetail;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTaskLog;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\DebtCollectionMissionManager;
use Unilend\Bundle\CoreBusinessBundle\Service\OperationManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class ProjectRepaymentManager
{
    /** @var EntityManager */
    private $entityManager;

    /** @var OperationManager */
    private $operationManager;

    /** @var LoggerInterface */
    private $logger;

    /** @var ProjectManager */
    private $projectManager;

    /** @var ProjectRepaymentTaskManager */
    private $projectRepaymentTaskManager;

    /** @var ProjectRepaymentNotificationSender */
    private $projectRepaymentNotificationSender;

    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;

    /** @var DebtCollectionMissionManager */
    private $debtCollectionManager;

    /**
     * ProjectRepaymentManager constructor.
     *
     * @param EntityManager                      $entityManager
     * @param EntityManagerSimulator             $entityManagerSimulator
     * @param OperationManager                   $operationManager
     * @param ProjectManager                     $projectManager
     * @param ProjectRepaymentTaskManager        $projectRepaymentTaskManager
     * @param ProjectRepaymentNotificationSender $projectRepaymentNotificationSender
     * @param DebtCollectionMissionManager       $debtCollectionManager
     * @param LoggerInterface                    $logger
     */
    public function __construct(
        EntityManager $entityManager,
        EntityManagerSimulator $entityManagerSimulator,
        OperationManager $operationManager,
        ProjectManager $projectManager,
        ProjectRepaymentTaskManager $projectRepaymentTaskManager,
        ProjectRepaymentNotificationSender $projectRepaymentNotificationSender,
        DebtCollectionMissionManager $debtCollectionManager,
        LoggerInterface $logger
    )
    {
        $this->entityManager                      = $entityManager;
        $this->entityManagerSimulator             = $entityManagerSimulator;
        $this->operationManager                   = $operationManager;
        $this->logger                             = $logger;
        $this->projectManager                     = $projectManager;
        $this->projectRepaymentTaskManager        = $projectRepaymentTaskManager;
        $this->projectRepaymentNotificationSender = $projectRepaymentNotificationSender;
        $this->debtCollectionManager              = $debtCollectionManager;
    }

    /**
     * Repay completely or partially a repayment schedule with or without debt collection fee.
     *
     * @param ProjectRepaymentTask $projectRepaymentTask
     * @param int                  $userId
     *
     * @return ProjectRepaymentTaskLog|null
     * @throws \Exception
     */
    public function repay(ProjectRepaymentTask $projectRepaymentTask, $userId = Users::USER_ID_CRON)
    {
        if (false === in_array($projectRepaymentTask->getType(), [ProjectRepaymentTask::TYPE_REGULAR, ProjectRepaymentTask::TYPE_LATE])) {
            $this->logger->warning(
                'The project repayment task (id: ' . $projectRepaymentTask->getId() . ') is not a regular or late repayment.',
                ['method' => __METHOD__]
            );

            return null;
        }

        if (false === $this->projectRepaymentTaskManager->isReady($projectRepaymentTask)) {
            return null;
        }

        try {
            $this->processProjectCharge($projectRepaymentTask->getIdWireTransferIn());

            $projectRepaymentTaskLog = $this->processRepayment($projectRepaymentTask);

            $totalTaskPlannedAmount = round(bcadd($projectRepaymentTask->getCapital(), $projectRepaymentTask->getInterest(), 4), 2);
            $totalTaskRepaidAmount  = $this->projectRepaymentTaskManager->getRepaidAmount($projectRepaymentTask);

            if (0 === bccomp($totalTaskPlannedAmount, $totalTaskRepaidAmount, 2)) {
                $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_REPAID);
                $this->entityManager->flush($projectRepaymentTask);
            } else {
                $this->projectRepaymentNotificationSender->sendIncompleteRepaymentNotification($projectRepaymentTask->getIdProject(), $projectRepaymentTask->getSequence());

                throw new \Exception('The amount (' . $totalTaskPlannedAmount . ') in the of the project repayment task (id: ' . $projectRepaymentTask->getId() . ') is different from the repaid amount (' . $totalTaskRepaidAmount . '). The task may not been completely done');
            }

            $this->processDebtCollectionFee($projectRepaymentTask->getIdWireTransferIn());

            $this->payCommission($projectRepaymentTaskLog);

        } catch (\Exception $exception) {
            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_ERROR);
            $this->entityManager->flush($projectRepaymentTask);

            throw $exception;
        }

        if ($this->projectRepaymentTaskManager->isCompleteRepayment($projectRepaymentTask)) {
            $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy([
                'idProject' => $projectRepaymentTask->getIdProject(),
                'ordre'     => $projectRepaymentTask->getSequence()
            ]);

            $this->projectRepaymentNotificationSender->sendPaymentScheduleInvoiceToBorrower($paymentSchedule);
        }

        // Send "end of repayment" notifications
        $pendingRepaymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')
            ->findByProject($projectRepaymentTask->getIdProject(), null, null, [Echeanciers::STATUS_PENDING, Echeanciers::STATUS_PARTIALLY_REPAID], null, null, 0, 1);
        if (0 === count($pendingRepaymentSchedule)) {
            $this->projectManager->addProjectStatus($userId, ProjectsStatus::REMBOURSE, $projectRepaymentTask->getIdProject());
            $this->projectRepaymentNotificationSender->sendInternalNotificationEndOfRepayment($projectRepaymentTask->getIdProject());
            $this->projectRepaymentNotificationSender->sendClientNotificationEndOfRepayment($projectRepaymentTask->getIdProject());
        }

        return $projectRepaymentTaskLog;
    }

    /**
     * @param Receptions $wireTransferIn
     *
     * @throws \Exception
     */
    private function processProjectCharge(Receptions $wireTransferIn)
    {
        $project                          = $wireTransferIn->getIdProject();
        $isDebtCollectionFeeDueToBorrower = $this->debtCollectionManager->isDebtCollectionFeeDueToBorrower($project);

        if ($isDebtCollectionFeeDueToBorrower) {
            $totalAppliedCharges = 0;

            $projectCharges = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectCharge')->findBy([
                'idWireTransferIn' => $wireTransferIn,
                'status'           => ProjectCharge::STATUS_PAID_BY_UNILEND
            ]);

            $this->entityManager->getConnection()->beginTransaction();
            try {
                foreach ($projectCharges as $projectCharge) {
                    $totalAppliedCharges = round(bcadd($totalAppliedCharges, $projectCharge->getAmountInclVat(), 4), 2);
                    $projectCharge->setStatus(ProjectCharge::STATUS_REPAID_BY_BORROWER);

                    $this->entityManager->flush($projectCharge);
                }
                $borrowerWallet = $this->entityManager
                    ->getRepository('UnilendCoreBusinessBundle:Wallet')
                    ->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
                $this->operationManager->repayProjectChargeByBorrower($borrowerWallet, $totalAppliedCharges, [$project, $wireTransferIn]);

                $this->entityManager->commit();
            } catch (\Exception $exception) {
                $this->entityManager->rollback();
                throw $exception;
            }
        }
    }

    /**
     * @param Receptions $wireTransferIn
     */
    public function processDebtCollectionFee(Receptions $wireTransferIn)
    {
        $project                           = $wireTransferIn->getIdProject();
        $debtCollectionFeeDetailRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:DebtCollectionFeeDetail');

        $borrowerWallet         = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
        $totalDebtCollectionFee = $debtCollectionFeeDetailRepository->getTotalDebtCollectionFeeByReception($wireTransferIn, $borrowerWallet, DebtCollectionFeeDetail::STATUS_PENDING);

        if (1 === bccomp($totalDebtCollectionFee, 0, 2)) {
            $debtCollectorWallet = $debtCollectionFeeDetailRepository->findOneBy(['idWireTransferIn' => $wireTransferIn])->getIdWalletCreditor();
            $this->operationManager->payDebtCollectionFee($borrowerWallet, $debtCollectorWallet, $totalDebtCollectionFee, [$project, $wireTransferIn]);
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

    /**
     * @param ProjectRepaymentTask $projectRepaymentTask
     *
     * @return ProjectRepaymentTaskLog
     * @throws \Exception
     */
    private function processRepayment(ProjectRepaymentTask $projectRepaymentTask)
    {
        $this->projectRepaymentTaskManager->prepare($projectRepaymentTask);

        $this->checkPreparedRepayments($projectRepaymentTask);

        $projectRepaymentTaskLog = $this->projectRepaymentTaskManager->start($projectRepaymentTask);

        $repaidLoanNb      = 0;
        $repaidAmount      = 0;
        $repaymentSequence = $projectRepaymentTask->getSequence();
        $project           = $projectRepaymentTask->getIdProject();

        $projectRepaymentDetails = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentDetail')->findBy([
            'idTask' => $projectRepaymentTask,
            'status' => ProjectRepaymentDetail::STATUS_PENDING
        ]);

        foreach ($projectRepaymentDetails as $projectRepaymentDetail) {
            if ($projectRepaymentDetail->getCapital() == 0 && $projectRepaymentDetail->getInterest() == 0) {
                $projectRepaymentDetail->setStatus(ProjectRepaymentDetail::STATUS_TREATED)
                    ->setIdTaskLog($projectRepaymentTaskLog);

                $this->entityManager->flush($projectRepaymentDetail);

                continue;
            }

            $this->entityManager->getConnection()->beginTransaction();
            try {
                /** @var Echeanciers $repaymentSchedule */
                $repaymentSchedule = $projectRepaymentDetail->getIdRepaymentSchedule();
                if (null === $repaymentSchedule) {
                    throw new \Exception('Cannot found repayment schedule for project (id: ' . $project->getIdProject() . '), sequence ' . $repaymentSequence
                        . ' and client (id: ' . $projectRepaymentDetail->getIdLoan()->getIdLender()->getIdClient() . ')');
                }

                $this->operationManager->repayment($projectRepaymentDetail->getCapital(), $projectRepaymentDetail->getInterest(), $repaymentSchedule, $projectRepaymentTaskLog);

                $repaidCapitalInCents  = $repaymentSchedule->getCapitalRembourse() + bcmul($projectRepaymentDetail->getCapital(), 100);
                $repaidInterestInCents = $repaymentSchedule->getInteretsRembourses() + bcmul($projectRepaymentDetail->getInterest(), 100);

                $repaymentSchedule->setCapitalRembourse($repaidCapitalInCents)
                    ->setInteretsRembourses($repaidInterestInCents);

                if ($repaymentSchedule->getCapital() === $repaymentSchedule->getCapitalRembourse() && $repaymentSchedule->getInterets() === $repaymentSchedule->getInteretsRembourses()) {
                    $repaymentSchedule->setStatus(Echeanciers::STATUS_REPAID)
                        ->setDateEcheanceReel(new \DateTime());
                } else {
                    $repaymentSchedule->setStatus(Echeanciers::STATUS_PARTIALLY_REPAID);
                }

                $projectRepaymentDetail->setStatus(ProjectRepaymentDetail::STATUS_TREATED)
                    ->setIdTaskLog($projectRepaymentTaskLog);

                $repaidLoanNb++;
                $repaidAmount = round(bcadd($repaidAmount, bcadd($projectRepaymentDetail->getCapital(), $projectRepaymentDetail->getInterest(), 4), 4), 2);

                $this->entityManager->flush([$repaymentSchedule, $projectRepaymentDetail]);

                $this->entityManager->commit();
            } catch (\Exception $exception) {
                $this->logger->error(
                    'An error occurs for the repayment detail # ' . $projectRepaymentDetail->getId() . ' of project # ' . $project->getIdProject() . ' (order: ' . $repaymentSequence . '). Error : ' . $exception->getMessage(),
                    ['file' => $exception->getFile(), 'line' => $exception->getLine()]
                );

                $this->entityManager->rollback();

                $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_ERROR);
                $this->entityManager->flush($projectRepaymentTask);

                $this->projectRepaymentNotificationSender->sendIncompleteRepaymentNotification($project, $repaymentSequence);

                throw $exception;
                break;
            }
        }

        return $this->projectRepaymentTaskManager->end($projectRepaymentTaskLog, $repaidAmount, $repaidLoanNb);
    }

    /**
     * @param ProjectRepaymentTaskLog $projectRepaymentTaskLog
     */
    private function payCommission(ProjectRepaymentTaskLog $projectRepaymentTaskLog)
    {
        $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy([
            'idProject' => $projectRepaymentTaskLog->getIdTask()->getIdProject(),
            'ordre'     => $projectRepaymentTaskLog->getIdTask()->getSequence()
        ]);

        $this->operationManager->repaymentCommission($paymentSchedule, $projectRepaymentTaskLog);
    }

    /**
     * @param ProjectRepaymentTask $projectRepaymentTask
     *
     * @throws \Exception
     */
    private function checkPreparedRepayments(ProjectRepaymentTask $projectRepaymentTask)
    {
        $projectRepaymentDetailRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentDetail');

        $totalCapitalToRepay = $projectRepaymentDetailRepository->getTotalCapitalToRepay($projectRepaymentTask);
        if (0 !== bccomp($projectRepaymentTask->getCapital(), $totalCapitalToRepay, 2)) {
            throw new \Exception('The total capital ( ' . $totalCapitalToRepay . ') in project_repayment_detail does not equal to the amount in the task (id : ' . $projectRepaymentTask->getId() . ')');
        }
        $totalInterestToRepay = $projectRepaymentDetailRepository->getTotalInterestToRepay($projectRepaymentTask);
        if (0 !== bccomp($projectRepaymentTask->getInterest(), $totalInterestToRepay, 2)) {
            throw new \Exception('The total interest ( ' . $totalInterestToRepay . ') in project_repayment_detail does not equal to the amount in the task (id : ' . $projectRepaymentTask->getId() . ')');
        }
    }
}
