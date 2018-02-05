<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentDetail;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTaskLog;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Service\DebtCollectionFeeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\OperationManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectChargeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class ProjectRepaymentManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var OperationManager */
    private $operationManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var ProjectStatusManager */
    private $projectStatusManager;
    /** @var ProjectRepaymentTaskManager */
    private $projectRepaymentTaskManager;
    /** @var ProjectRepaymentNotificationSender */
    private $projectRepaymentNotificationSender;
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var ProjectChargeManager */
    private $projectChargeManager;
    /** @var DebtCollectionFeeManager */
    private $debtCollectionFeeManager;

    /**
     * @param EntityManager                      $entityManager
     * @param EntityManagerSimulator             $entityManagerSimulator
     * @param OperationManager                   $operationManager
     * @param ProjectStatusManager               $projectStatusManager
     * @param ProjectRepaymentTaskManager        $projectRepaymentTaskManager
     * @param ProjectRepaymentNotificationSender $projectRepaymentNotificationSender
     * @param ProjectChargeManager               $projectChargeManager
     * @param DebtCollectionFeeManager           $debtCollectionFeeManager
     * @param LoggerInterface                    $logger
     */
    public function __construct(
        EntityManager $entityManager,
        EntityManagerSimulator $entityManagerSimulator,
        OperationManager $operationManager,
        ProjectStatusManager $projectStatusManager,
        ProjectRepaymentTaskManager $projectRepaymentTaskManager,
        ProjectRepaymentNotificationSender $projectRepaymentNotificationSender,
        ProjectChargeManager $projectChargeManager,
        DebtCollectionFeeManager $debtCollectionFeeManager,
        LoggerInterface $logger
    )
    {
        $this->entityManager                      = $entityManager;
        $this->entityManagerSimulator             = $entityManagerSimulator;
        $this->operationManager                   = $operationManager;
        $this->logger                             = $logger;
        $this->projectStatusManager               = $projectStatusManager;
        $this->projectRepaymentTaskManager        = $projectRepaymentTaskManager;
        $this->projectRepaymentNotificationSender = $projectRepaymentNotificationSender;
        $this->projectChargeManager               = $projectChargeManager;
        $this->debtCollectionFeeManager           = $debtCollectionFeeManager;
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
            $projectRepaymentTaskLog = $this->projectRepaymentTaskManager->start($projectRepaymentTask);

            $this->projectChargeManager->processProjectCharge($projectRepaymentTask->getIdWireTransferIn());

            $this->processRepayment($projectRepaymentTaskLog);

            $projectRepaymentTaskStatus = ProjectRepaymentTask::STATUS_REPAID;

            $totalTaskPlannedAmount = round(bcadd($projectRepaymentTask->getCapital(), $projectRepaymentTask->getInterest(), 4), 2);
            $totalTaskRepaidAmount  = $this->projectRepaymentTaskManager->getRepaidAmount($projectRepaymentTask);

            if (0 !== bccomp($totalTaskPlannedAmount, $totalTaskRepaidAmount, 2)) {
                $this->projectRepaymentNotificationSender->sendIncompleteRepaymentNotification($projectRepaymentTask->getIdProject(), $projectRepaymentTask->getSequence());
                throw new \Exception('The amount (' . $totalTaskPlannedAmount . ') of the project repayment task (id: ' . $projectRepaymentTask->getId() . ') is different from the repaid amount (' . $totalTaskRepaidAmount . '). The task may not been completely done');
            }

            $this->debtCollectionFeeManager->processDebtCollectionFee($projectRepaymentTask->getIdWireTransferIn());

            $this->operationManager->repaymentCommission($projectRepaymentTaskLog);

        } catch (\Exception $exception) {
            $projectRepaymentTaskStatus = ProjectRepaymentTask::STATUS_ERROR;
            throw $exception;
        } finally {
            $this->projectRepaymentTaskManager->end($projectRepaymentTaskLog, $projectRepaymentTaskStatus);
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
            $this->projectStatusManager->addProjectStatus($userId, ProjectsStatus::REMBOURSE, $projectRepaymentTask->getIdProject());
            $this->projectRepaymentNotificationSender->sendInternalNotificationEndOfRepayment($projectRepaymentTask->getIdProject());
            $this->projectRepaymentNotificationSender->sendClientNotificationEndOfRepayment($projectRepaymentTask->getIdProject());
        }

        return $projectRepaymentTaskLog;
    }

    /**
     * @param ProjectRepaymentTaskLog $projectRepaymentTaskLog
     *
     * @throws \Exception
     */
    private function processRepayment(ProjectRepaymentTaskLog $projectRepaymentTaskLog)
    {
        $projectRepaymentTask = $projectRepaymentTaskLog->getIdTask();

        $this->projectRepaymentTaskManager->prepare($projectRepaymentTask);

        $this->projectRepaymentTaskManager->checkPreparedRepayments($projectRepaymentTask);

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

                $this->projectRepaymentNotificationSender->sendIncompleteRepaymentNotification($project, $repaymentSequence);

                $this->projectRepaymentTaskManager->log($projectRepaymentTaskLog, $repaidAmount, $repaidLoanNb);

                throw $exception;
                break;
            }
        }

        $this->projectRepaymentTaskManager->log($projectRepaymentTaskLog, $repaidAmount, $repaidLoanNb);
    }
}
