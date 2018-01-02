<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTaskLog;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Service\OperationManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager;

class ProjectEarlyRepaymentManager
{
    /** @var EntityManager */
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
     * @param EntityManager                      $entityManager
     * @param OperationManager                   $operationManager
     * @param ProjectStatusManager               $projectStatusManager
     * @param ProjectRepaymentTaskManager        $projectRepaymentTaskManager
     * @param ProjectRepaymentNotificationSender $projectRepaymentNotificationSender
     * @param LoggerInterface                    $logger
     */
    public function __construct(
        EntityManager $entityManager,
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
     * @return ProjectRepaymentTaskLog
     */
    public function repay(ProjectRepaymentTask $projectRepaymentTask, $idUser = Users::USER_ID_CRON)
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

        $repaidLoanNb = 0;
        $repaidAmount = 0;
        $project      = $projectRepaymentTask->getIdProject();

        $projectRepaymentTaskLog = $this->projectRepaymentTaskManager->start($projectRepaymentTask);

        $this->entityManager->getRepository('UnilendCoreBusinessBundle:Prelevements')->terminatePendingDirectDebits($project);
        $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->earlyPayAllPendingSchedules($projectRepaymentTask->getIdWireTransferIn());
        $this->projectRepaymentNotificationSender->createEarlyRepaymentEmail($projectRepaymentTask->getIdWireTransferIn());

        $this->projectStatusManager->addProjectStatus($idUser, ProjectsStatus::REMBOURSEMENT_ANTICIPE, $project);

        $loans = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->findBy(['idProject' => $project]);

        $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');

        foreach ($loans as $loan) {
            $this->entityManager->getConnection()->beginTransaction();
            try {
                $repaidCapital = $this->operationManager->earlyRepayment($loan, $projectRepaymentTaskLog);

                $repaidAmount = round(bcadd($repaidAmount, $repaidCapital, 4), 2);
                if ($repaidCapital > 0) {
                    $repaidLoanNb++;
                }
                $repaymentScheduleRepository->earlyRepayAllPendingSchedules($loan);
                $this->entityManager->commit();
            } catch (\Exception $exception) {
                $this->entityManager->rollback();

                $this->logger->error('Early repayment error on loan (id: ' . $loan->getIdLoan() . ') Error message : ' . $exception->getMessage());

                break;
            }
        }

        $pendingRepaymentSchedule = $repaymentScheduleRepository->findByProject($project, null, null, Echeanciers::STATUS_PENDING, null, null, 0, 1);

        if (0 === count($pendingRepaymentSchedule)) {
            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_REPAID);
            $this->entityManager->flush($projectRepaymentTask);
        }

        return $this->projectRepaymentTaskManager->end($projectRepaymentTaskLog, $repaidAmount, $repaidLoanNb);
    }
}
