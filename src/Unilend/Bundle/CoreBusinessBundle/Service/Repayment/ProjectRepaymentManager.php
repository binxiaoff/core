<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\Factures;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentDetail;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTaskLog;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
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

    /**
     * ProjectRepaymentManager constructor.
     *
     * @param EntityManager                      $entityManager
     * @param EntityManagerSimulator             $entityManagerSimulator
     * @param OperationManager                   $operationManager
     * @param ProjectManager                     $projectManager
     * @param ProjectRepaymentTaskManager        $projectRepaymentTaskManager
     * @param ProjectRepaymentNotificationSender $projectRepaymentNotificationSender
     * @param LoggerInterface                    $logger
     */
    public function __construct(
        EntityManager $entityManager,
        EntityManagerSimulator $entityManagerSimulator,
        OperationManager $operationManager,
        ProjectManager $projectManager,
        ProjectRepaymentTaskManager $projectRepaymentTaskManager,
        ProjectRepaymentNotificationSender $projectRepaymentNotificationSender,
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
    }

    /**
     * Repay completely or partially a repayment schedule
     *
     * @param ProjectRepaymentTask $projectRepaymentTask
     * @param int                  $userId
     *
     * @return ProjectRepaymentTaskLog|null
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

        $projectRepaymentTaskLog = $this->processRepayment($projectRepaymentTask);

        $totalTaskPlannedAmount = round(bcadd($projectRepaymentTask->getCapital(), $projectRepaymentTask->getInterest(), 4), 2);
        $totalTaskRepaidAmount  = $this->projectRepaymentTaskManager->getRepaidAmount($projectRepaymentTask);

        if (0 === bccomp($totalTaskPlannedAmount, $totalTaskRepaidAmount, 2)) {
            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_REPAID);
            $this->entityManager->flush($projectRepaymentTask);
        } else {
            $this->projectRepaymentNotificationSender->sendIncompleteRepaymentNotification($projectRepaymentTask->getIdProject(), $projectRepaymentTask->getSequence());

            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_ERROR);
            $this->entityManager->flush($projectRepaymentTask);

            $this->logger->error(
                'The amount (' . $totalTaskPlannedAmount . ') in the of the project repayment task (id: ' . $projectRepaymentTask->getId() . ') is different from the repaid amount (' . $totalTaskRepaidAmount . '). The task may not been completely done',
                ['method' => __METHOD__]
            );

            return null;
        }

        $this->payCommission($projectRepaymentTaskLog);

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
     * @param ProjectRepaymentTask $projectRepaymentTask
     *
     * @return ProjectRepaymentTaskLog
     */
    private function processRepayment(ProjectRepaymentTask $projectRepaymentTask)
    {
        $projectRepaymentTaskLog = $this->projectRepaymentTaskManager->start($projectRepaymentTask);

        $repaidLoanNb      = 0;
        $repaidAmount      = 0;
        $repaymentSequence = $projectRepaymentTask->getSequence();
        $project           = $projectRepaymentTask->getIdProject();

        $this->prepare($projectRepaymentTaskLog);

        $projectRepaymentDetails = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentDetail')->findBy([
            'idTaskLog' => $projectRepaymentTaskLog,
            'status'    => ProjectRepaymentDetail::STATUS_PENDING
        ]);

        foreach ($projectRepaymentDetails as $projectRepaymentDetail) {
            if ($projectRepaymentDetail->getCapital() == 0 && $projectRepaymentDetail->getInterest() == 0) {
                $projectRepaymentDetail->setStatus(ProjectRepaymentDetail::STATUS_TREATED);
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

                $projectRepaymentDetail->setStatus(ProjectRepaymentDetail::STATUS_TREATED);

                $repaidLoanNb++;
                $repaidAmount = round(bcadd($repaidAmount, bcadd($projectRepaymentDetail->getCapital(), $projectRepaymentDetail->getInterest(), 4), 4), 2);

                $this->entityManager->flush([$repaymentSchedule, $projectRepaymentDetail]);

                $this->entityManager->commit();
            } catch (\Exception $exception) {
                $this->logger->error(
                    'An error occurs for the repayment detail # ' . $projectRepaymentDetail->getId() . ' of project # ' . $project->getIdProject() . ' (order: ' . $repaymentSequence . '). Error : ' . $exception->getMessage(),
                    ['method' => __METHOD__]
                );

                $this->entityManager->rollback();

                $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_ERROR);
                $this->entityManager->flush($projectRepaymentTask);

                $this->projectRepaymentNotificationSender->sendIncompleteRepaymentNotification($project, $repaymentSequence);

                break;
            }
        }

        return $this->projectRepaymentTaskManager->end($projectRepaymentTaskLog, $repaidAmount, $repaidLoanNb);
    }

    /**
     * @param ProjectRepaymentTaskLog $projectRepaymentTaskLog
     */
    public function prepare(ProjectRepaymentTaskLog $projectRepaymentTaskLog)
    {
        $repaidCapital               = 0;
        $repaidInterest              = 0;
        $projectRepaymentTask        = $projectRepaymentTaskLog->getIdTask();
        $notRepaidCapitalProportion  = $this->getNotRepaidCapitalProportion($projectRepaymentTask);
        $notRepaidInterestProportion = $this->getNotRepaidInterestProportion($projectRepaymentTask);

        $repaymentSchedules = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')
            ->findByProject($projectRepaymentTask->getIdProject(), $projectRepaymentTask->getSequence(), null, [Echeanciers::STATUS_PENDING, Echeanciers::STATUS_PARTIALLY_REPAID]);

        foreach ($repaymentSchedules as $repaymentSchedule) {
            $unRepaidCapital  = round(bcdiv($repaymentSchedule->getCapital() - $repaymentSchedule->getCapitalRembourse(), 100, 4), 2);
            $unRepaidInterest = round(bcdiv($repaymentSchedule->getInterets() - $repaymentSchedule->getInteretsRembourses(), 100, 4), 2);

            $capitalProportion  = round(bcmul($unRepaidCapital, $notRepaidCapitalProportion, 4), 2);
            $interestProportion = round(bcmul($unRepaidInterest, $notRepaidInterestProportion, 4), 2);

            $capitalToRepay  = min($capitalProportion, $unRepaidCapital);
            $interestToRepay = min($interestProportion, $unRepaidInterest);

            $projectRepaymentDetail = new ProjectRepaymentDetail();

            $projectRepaymentDetail->setIdTaskLog($projectRepaymentTaskLog)
                ->setIdloan($repaymentSchedule->getIdLoan())
                ->setIdRepaymentSchedule($repaymentSchedule)
                ->setCapital($capitalToRepay)
                ->setInterest($interestToRepay)
                ->setStatus(ProjectRepaymentDetail::STATUS_PENDING)
                ->setCapitalCompleted(ProjectRepaymentDetail::CAPITAL_UNCOMPLETED)
                ->setInterestCompleted(ProjectRepaymentDetail::INTEREST_UNCOMPLETED);

            if (0 === bccomp($capitalToRepay, $unRepaidCapital, 2)) {
                $projectRepaymentDetail->setCapitalCompleted(ProjectRepaymentDetail::CAPITAL_COMPLETED);
            }

            if (0 === bccomp($interestToRepay, $unRepaidInterest, 2)) {
                $projectRepaymentDetail->setInterestCompleted(ProjectRepaymentDetail::INTEREST_COMPLETED);
            }

            $this->entityManager->persist($projectRepaymentDetail);
            $this->entityManager->flush($projectRepaymentDetail);

            $repaidCapital  = round(bcadd($repaidCapital, $capitalToRepay, 4), 2);
            $repaidInterest = round(bcadd($repaidInterest, $interestToRepay, 4), 2);
        }

        $this->adjustRepaymentAmount($projectRepaymentTaskLog, $repaidCapital, $repaidInterest);
    }

    /**
     * @param ProjectRepaymentTaskLog $projectRepaymentTaskLog
     * @param float                   $repaidCapital
     * @param float                   $repaidInterest
     */
    private function adjustRepaymentAmount(ProjectRepaymentTaskLog $projectRepaymentTaskLog, $repaidCapital, $repaidInterest)
    {
        $capitalCompareResult  = bccomp($projectRepaymentTaskLog->getIdTask()->getCapital(), $repaidCapital, 2);
        $interestCompareResult = bccomp($projectRepaymentTaskLog->getIdTask()->getInterest(), $repaidInterest, 2);

        if (0 !== $capitalCompareResult || 0 !== $interestCompareResult) {
            $projectRepaymentDetailRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentDetail');

            $capitalNumberOfCents = abs(round(bcdiv(round(bcsub($projectRepaymentTaskLog->getIdTask()->getCapital(), $repaidCapital, 4), 2), 0.01, 4)));
            if ($capitalNumberOfCents > 0) {
                $randomRepayments = $projectRepaymentDetailRepository->findRandomlyUncompletedByTaskExecutionForCapital($projectRepaymentTaskLog, $capitalNumberOfCents);

                foreach ($randomRepayments as $projectRepaymentDetail) {
                    if (1 === $capitalCompareResult) {
                        $adjustAmount = 0.01;
                    } else {
                        $adjustAmount = -0.01;
                    }

                    $capital = round(bcadd($projectRepaymentDetail->getCapital(), $adjustAmount, 4), 2);
                    $projectRepaymentDetail->setCapital($capital);

                    $repaymentSchedule = $projectRepaymentDetail->getIdRepaymentSchedule();
                    $unRepaidCapital   = round(bcdiv($repaymentSchedule->getCapital() - $repaymentSchedule->getCapitalRembourse(), 100, 4), 2);

                    if (0 === bccomp($projectRepaymentDetail->getCapital(), $unRepaidCapital, 2)) {
                        $projectRepaymentDetail->setCapitalCompleted(ProjectRepaymentDetail::CAPITAL_COMPLETED);
                    }

                    $repaidCapital = round(bcadd($repaidCapital, $adjustAmount, 4), 2);
                }
            }

            $interestNumberOfCents = abs(round(bcdiv(round(bcsub($projectRepaymentTaskLog->getIdTask()->getInterest(), $repaidInterest, 4), 2), 0.01, 4)));
            if ($interestNumberOfCents > 0) {
                $randomRepayments = $projectRepaymentDetailRepository->findRandomlyUncompletedByTaskExecutionForInterest($projectRepaymentTaskLog, $interestNumberOfCents);

                foreach ($randomRepayments as $projectRepaymentDetail) {
                    if (1 === $interestCompareResult) {
                        $adjustAmount = 0.01;
                    } else {
                        $adjustAmount = -0.01;
                    }

                    $interest = round(bcadd($projectRepaymentDetail->getInterest(), $adjustAmount, 4), 2);
                    $projectRepaymentDetail->setInterest($interest);

                    $repaymentSchedule = $projectRepaymentDetail->getIdRepaymentSchedule();
                    $unRepaidInterest  = round(bcdiv($repaymentSchedule->getInterets() - $repaymentSchedule->getInteretsRembourses(), 100, 4), 2);

                    if (0 === bccomp($projectRepaymentDetail->getInterest(), $unRepaidInterest, 2)) {
                        $projectRepaymentDetail->setInterestCompleted(ProjectRepaymentDetail::INTEREST_COMPLETED);
                    }

                    $repaidInterest = round(bcadd($repaidInterest, $adjustAmount, 4), 2);
                }
            }

            $this->entityManager->flush();

            $this->adjustRepaymentAmount($projectRepaymentTaskLog, $repaidCapital, $repaidInterest);
        }
    }

    /**
     * @param ProjectRepaymentTask $projectRepaymentTask
     *
     * @return string
     */
    private function getNotRepaidCapitalProportion(ProjectRepaymentTask $projectRepaymentTask)
    {
        $unpaidNetRepaymentAmount = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')
            ->getNotRepaidCapitalByProjectAndSequence($projectRepaymentTask->getIdProject(), $projectRepaymentTask->getSequence());

        if (0 === bccomp($unpaidNetRepaymentAmount, 0, 2)) {
            return 0;
        }

        return bcdiv($projectRepaymentTask->getCapital(), $unpaidNetRepaymentAmount, 10);
    }

    /**
     * @param ProjectRepaymentTask $projectRepaymentTask
     *
     * @return string
     */
    private function getNotRepaidInterestProportion(ProjectRepaymentTask $projectRepaymentTask)
    {
        $unpaidNetRepaymentAmount = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')
            ->getNotRepaidInterestByProjectAndSequence($projectRepaymentTask->getIdProject(), $projectRepaymentTask->getSequence());

        if (0 === bccomp($unpaidNetRepaymentAmount, 0, 2)) {
            return 0;
        }

        return bcdiv($projectRepaymentTask->getInterest(), $unpaidNetRepaymentAmount, 10);
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
}
