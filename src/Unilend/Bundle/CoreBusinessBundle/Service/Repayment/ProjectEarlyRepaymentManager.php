<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Unilend\Entity\{Echeanciers, EcheanciersEmprunteur, Loans, Prelevements, ProjectRepaymentTask, ProjectRepaymentTaskLog, ProjectsStatus, Users};
use Unilend\Bundle\CoreBusinessBundle\Service\{OperationManager, ProjectStatusManager};

class ProjectEarlyRepaymentManager
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var OperationManager */
    private $operationManager;
    /** @var ProjectStatusManager */
    private $projectStatusManager;
    /** @var ProjectRepaymentTaskManager */
    private $projectRepaymentTaskManager;
    /** @var ProjectRepaymentNotificationSender */
    private $projectRepaymentNotificationSender;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface             $entityManager
     * @param OperationManager                   $operationManager
     * @param ProjectStatusManager               $projectStatusManager
     * @param ProjectRepaymentTaskManager        $projectRepaymentTaskManager
     * @param ProjectRepaymentNotificationSender $projectRepaymentNotificationSender
     * @param LoggerInterface                    $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        OperationManager $operationManager,
        ProjectStatusManager $projectStatusManager,
        ProjectRepaymentTaskManager $projectRepaymentTaskManager,
        ProjectRepaymentNotificationSender $projectRepaymentNotificationSender,
        LoggerInterface $logger
    )
    {
        $this->entityManager                      = $entityManager;
        $this->operationManager                   = $operationManager;
        $this->projectStatusManager               = $projectStatusManager;
        $this->projectRepaymentTaskManager        = $projectRepaymentTaskManager;
        $this->projectRepaymentNotificationSender = $projectRepaymentNotificationSender;
        $this->logger                             = $logger;
    }

    /**
     * @param ProjectRepaymentTask $projectRepaymentTask
     * @param int                  $idUser
     *
     * @return ProjectRepaymentTaskLog|null
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function repay(ProjectRepaymentTask $projectRepaymentTask, $idUser = Users::USER_ID_CRON) : ?ProjectRepaymentTaskLog
    {
        if (ProjectRepaymentTask::TYPE_EARLY !== $projectRepaymentTask->getType()) {
            $this->logger->warning(
                'The projects repayment task (id: ' . $projectRepaymentTask->getId() . ') is not a early repayment.',
                ['method' => __METHOD__]
            );

            return null;
        }

        if (false === $this->projectRepaymentTaskManager->isReady($projectRepaymentTask)) {
            return null;
        }

        $repaidLoanNb               = 0;
        $repaidAmount               = 0;
        $project                    = $projectRepaymentTask->getIdProject();
        $projectRepaymentTaskLog    = null;
        $projectRepaymentTaskStatus = null;

        try {
            $projectRepaymentTaskLog = $this->projectRepaymentTaskManager->start($projectRepaymentTask);

            $this->entityManager->getRepository(Prelevements::class)->terminatePendingDirectDebits($project);
            $this->entityManager->getRepository(EcheanciersEmprunteur::class)->earlyPayAllPendingSchedules($projectRepaymentTask->getIdWireTransferIn());
            $this->projectRepaymentNotificationSender->createEarlyRepaymentEmail($projectRepaymentTask->getIdWireTransferIn());

            $this->projectStatusManager->addProjectStatus($idUser, ProjectsStatus::STATUS_REPAID, $project);

            $loans = $this->entityManager->getRepository(Loans::class)->findBy(['idProject' => $project]);

            $repaymentScheduleRepository = $this->entityManager->getRepository(Echeanciers::class);

            foreach ($loans as $loan) {
                $this->entityManager->getConnection()->beginTransaction();
                try {
                    $this->entityManager->getConnection()->setTransactionIsolation(Connection::TRANSACTION_READ_COMMITTED);

                    $repaidCapital = $this->operationManager->earlyRepayment($loan, $projectRepaymentTaskLog);

                    $repaidAmount = round(bcadd($repaidAmount, $repaidCapital, 4), 2);
                    if ($repaidCapital > 0) {
                        $repaidLoanNb++;
                    }
                    $repaymentScheduleRepository->earlyRepayAllPendingSchedules($loan);
                    $this->entityManager->commit();
                } catch (\Exception $exception) {
                    $this->entityManager->rollback();

                    $this->logger->error('Early repayment error on loan (id: ' . $loan->getIdLoan() . ') Error message : ' . $exception->getMessage(), [
                        'method' => __METHOD__,
                        'file'   => $exception->getFile(),
                        'line'   => $exception->getLine()
                    ]);

                    throw $exception;
                    break;
                }
            }
            $projectRepaymentTaskStatus = ProjectRepaymentTask::STATUS_REPAID;

            $pendingRepaymentSchedule = $repaymentScheduleRepository->findByProject($project, null, null, Echeanciers::STATUS_PENDING, null, null, 0, 1);
            if (0 !== count($pendingRepaymentSchedule)) {
                throw new \Exception('Early repayment for the project (id: ' . $project->getIdProject() . ') is not completed.');
            }
        } catch (\Exception $exception) {
            $projectRepaymentTaskStatus = ProjectRepaymentTask::STATUS_ERROR;

            throw $exception;
        } finally {
            $this->projectRepaymentTaskManager->log($projectRepaymentTaskLog, $repaidAmount, $repaidLoanNb);
            $this->projectRepaymentTaskManager->end($projectRepaymentTaskLog, $projectRepaymentTaskStatus);
        }

        return $projectRepaymentTaskLog;
    }
}
