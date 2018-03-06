<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentDetail;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTaskLog;
use Unilend\Bundle\CoreBusinessBundle\Service\DebtCollectionFeeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\OperationManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectChargeManager;

class ProjectCloseOutNettingRepaymentManager
{
    /** @var EntityManager */
    private $entityManager;

    /** @var OperationManager */
    private $operationManager;

    /** @var LoggerInterface */
    private $logger;

    /** @var ProjectRepaymentTaskManager */
    private $projectRepaymentTaskManager;

    /** @var ProjectRepaymentNotificationSender */
    private $projectRepaymentNotificationSender;

    /** @var ProjectChargeManager */
    private $projectChargeManager;

    /** @var DebtCollectionFeeManager */
    private $debtCollectionFeeManager;

    /**
     * ProjectRepaymentManager constructor.
     *
     * @param EntityManager                      $entityManager
     * @param OperationManager                   $operationManager
     * @param ProjectRepaymentTaskManager        $projectRepaymentTaskManager
     * @param ProjectRepaymentNotificationSender $projectRepaymentNotificationSender
     * @param ProjectChargeManager               $projectChargeManager
     * @param DebtCollectionFeeManager           $debtCollectionFeeManager
     * @param LoggerInterface                    $logger
     */
    public function __construct(
        EntityManager $entityManager,
        OperationManager $operationManager,
        ProjectRepaymentTaskManager $projectRepaymentTaskManager,
        ProjectRepaymentNotificationSender $projectRepaymentNotificationSender,
        ProjectChargeManager $projectChargeManager,
        DebtCollectionFeeManager $debtCollectionFeeManager,
        LoggerInterface $logger
    )
    {
        $this->entityManager                      = $entityManager;
        $this->operationManager                   = $operationManager;
        $this->logger                             = $logger;
        $this->projectRepaymentTaskManager        = $projectRepaymentTaskManager;
        $this->projectRepaymentNotificationSender = $projectRepaymentNotificationSender;
        $this->projectChargeManager               = $projectChargeManager;
        $this->debtCollectionFeeManager           = $debtCollectionFeeManager;
    }

    /**
     * Repay completely or partially a repayment schedule with or without debt collection fee.
     *
     * @param ProjectRepaymentTask $projectRepaymentTask
     *
     * @return ProjectRepaymentTaskLog|null
     * @throws \Exception
     */
    public function repay(ProjectRepaymentTask $projectRepaymentTask)
    {
        if (ProjectRepaymentTask::TYPE_CLOSE_OUT_NETTING !== $projectRepaymentTask->getType()) {
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

        $closeOutNettingRepaymentRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingRepayment');

        foreach ($projectRepaymentDetails as $projectRepaymentDetail) {
            if ($projectRepaymentDetail->getCapital() == 0 && $projectRepaymentDetail->getInterest() == 0) {
                $projectRepaymentDetail->setStatus(ProjectRepaymentDetail::STATUS_TREATED)
                    ->setIdTaskLog($projectRepaymentTaskLog);

                $this->entityManager->flush($projectRepaymentDetail);

                continue;
            }

            $this->entityManager->getConnection()->beginTransaction();
            try {
                $this->entityManager->getConnection()->setTransactionIsolation(Connection::TRANSACTION_READ_COMMITTED);

                $closeOutNettingRepayment = $closeOutNettingRepaymentRepository->findOneBy(['idLoan' => $projectRepaymentDetail->getIdLoan()]);
                if (null === $closeOutNettingRepayment) {
                    throw new \Exception('Cannot found close out netting repayment for loan (id: ' . $projectRepaymentDetail->getIdLoan()->getIdLoan() .
                        ') of repayment detail (id: ' . $projectRepaymentDetail->getId() . ')');
                }

                $this->operationManager->closeOutNettingRepayment($projectRepaymentDetail->getCapital(), $projectRepaymentDetail->getInterest(), $closeOutNettingRepayment, $projectRepaymentTaskLog);

                $repaidCapital  = round(bcadd($closeOutNettingRepayment->getRepaidCapital(), $projectRepaymentDetail->getCapital(), 4), 2);
                $repaidInterest = round(bcadd($closeOutNettingRepayment->getRepaidInterest(), $projectRepaymentDetail->getInterest(), 4), 2);

                $closeOutNettingRepayment->setRepaidCapital($repaidCapital)->setRepaidInterest($repaidInterest);

                $projectRepaymentDetail->setStatus(ProjectRepaymentDetail::STATUS_TREATED)
                    ->setIdTaskLog($projectRepaymentTaskLog);

                $repaidLoanNb++;
                $repaidAmount = round(bcadd($repaidAmount, bcadd($projectRepaymentDetail->getCapital(), $projectRepaymentDetail->getInterest(), 4), 4), 2);

                $this->entityManager->flush([$closeOutNettingRepayment, $projectRepaymentDetail]);

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
