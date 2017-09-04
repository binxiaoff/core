<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTaskLog;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\core\Loader;

class ProjectRepaymentTaskManager
{
    /** @var EntityManager */
    private $entityManager;

    /** @var ProjectRepaymentNotificationSender */
    private $projectRepaymentNotificationSender;

    /** @var ProjectRepaymentScheduleManager */
    private $projectRepaymentScheduleManager;

    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;

    /** @var \jours_ouvres */
    private $businessDays;

    /**
     * ProjectRepaymentTaskManager constructor.
     *
     * @param EntityManager                      $entityManager
     * @param EntityManagerSimulator             $entityManagerSimulator
     * @param ProjectRepaymentNotificationSender $projectRepaymentNotificationSender
     * @param projectRepaymentScheduleManager    $projectRepaymentScheduleManager
     * @param LoggerInterface                    $logger
     */
    public function __construct(
        EntityManager $entityManager,
        EntityManagerSimulator $entityManagerSimulator,
        ProjectRepaymentNotificationSender $projectRepaymentNotificationSender,
        ProjectRepaymentScheduleManager $projectRepaymentScheduleManager,
        LoggerInterface $logger
    )
    {
        $this->entityManager                      = $entityManager;
        $this->entityManagerSimulator             = $entityManagerSimulator;
        $this->logger                             = $logger;
        $this->projectRepaymentNotificationSender = $projectRepaymentNotificationSender;
        $this->projectRepaymentScheduleManager    = $projectRepaymentScheduleManager;
        $this->businessDays                       = Loader::loadLib('jours_ouvres');
    }

    /**
     * @param Echeanciers $repaymentSchedule
     * @param float       $repaymentAmount
     * @param float       $commission
     * @param Receptions  $reception
     * @param Users       $user
     *
     * @throws \Exception
     */
    public function planRepaymentTask(Echeanciers $repaymentSchedule, $repaymentAmount, $commission, Receptions $reception, Users $user)
    {
        $project = $repaymentSchedule->getIdLoan()->getProject();

        $repaymentTasks = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')
            ->findBy(['idProject' => $project, 'sequence' => $repaymentSchedule->getOrdre()]);

        $plannedAmount     = 0;
        $plannedCommission = 0;
        foreach ($repaymentTasks as $task) {
            $plannedAmount     = round(bcadd($plannedAmount, $task->getAmount(), 4), 2);
            $plannedCommission = round(bcadd($plannedCommission, $task->getCommissionUnilend(), 4), 2);
        }

        $plannedAmount     = round(bcadd($plannedAmount, $repaymentAmount, 4), 2);
        $plannedCommission = round(bcadd($plannedCommission, $commission, 4), 2);

        $netMonthlyAmount  = $this->projectRepaymentScheduleManager->getNetMonthlyAmount($project);
        $monthlyCommission = $this->projectRepaymentScheduleManager->getUnilendCommissionVatIncl($project);

        if (1 === bccomp($plannedAmount, $netMonthlyAmount)) {
            throw new \Exception('The total amount of the repayment tasks for project (id: ' . $project->getIdProject() . ') sequence ' . $repaymentSchedule->getOrdre() . ' is more then the monthly amount. Please check the data consistency.');
        }

        if (1 === bccomp($plannedCommission, $monthlyCommission)) {
            throw new \Exception('The total commission of the repayment tasks for project (id: ' . $project->getIdProject() . ') sequence ' . $repaymentSchedule->getOrdre() . ' is more then the monthly commission. Please check the data consistency.');
        }

        $projectRepaymentTask = new ProjectRepaymentTask();
        $projectRepaymentTask->setIdProject($project)
            ->setSequence($repaymentSchedule->getOrdre())
            ->setAmount($repaymentAmount)
            ->setCommissionUnilend($commission)
            ->setType(ProjectRepaymentTask::TYPE_REGULAR)
            ->setStatus(ProjectRepaymentTask::STATUS_PENDING)
            ->setRepayAt($repaymentSchedule->getDateEcheance())
            ->setIdUserCreation($user)
            ->setIdWireTransferIn($reception);

        if (
            Projects::AUTO_REPAYMENT_ON === $project->getRembAuto()
            && $project->getStatus() < ProjectsStatus::PROBLEME
            && 0 === bccomp($repaymentAmount, $this->projectRepaymentScheduleManager->getNetMonthlyAmount($project), 2)
            && 0 === bccomp($commission, $this->projectRepaymentScheduleManager->getUnilendCommissionVatIncl($project), 2)
        ) {
            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_READY)
                ->setIdUserValidation($user);
        }

        $this->entityManager->persist($projectRepaymentTask);
        $this->entityManager->flush($projectRepaymentTask);
    }

    /**
     * @param ProjectRepaymentTask $projectRepaymentTaskToCancel
     * @param Users                $user
     */
    public function cancelRepaymentTask(ProjectRepaymentTask $projectRepaymentTaskToCancel, Users $user)
    {
        if (in_array($projectRepaymentTaskToCancel->getStatus(), [ProjectRepaymentTask::STATUS_PENDING, ProjectRepaymentTask::STATUS_READY])) {
            $projectRepaymentTaskToCancel->setStatus(ProjectRepaymentTask::STATUS_CANCELLED)
                ->setIdUserCancellation($user);
            $this->entityManager->flush($projectRepaymentTaskToCancel);
        }
    }

    /**
     * @param Projects   $project
     * @param Receptions $reception
     * @param Users      $user
     *
     * @return ProjectRepaymentTask
     */
    public function planEarlyRepaymentTask(Projects $project, Receptions $reception, Users $user)
    {
        $receivedAmount = round(bcdiv($reception->getMontant(), 100, 4), 2);

        $projectRepaymentTask = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')
            ->findOneBy(['idProject' => $project, 'type' => ProjectRepaymentTask::TYPE_EARLY]);

        if ($projectRepaymentTask) {
            $amount = round(bcAdd($projectRepaymentTask->getAmount(), $receivedAmount, 4), 2);
            $projectRepaymentTask->setAmount($amount);
        } else {
            $limitDate = $this->getLimitDate(new \DateTime('today midnight'));

            $nextRepayment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')->findNextPendingScheduleAfter($limitDate, $project);

            $projectRepaymentTask = new ProjectRepaymentTask();
            $projectRepaymentTask->setAmount($receivedAmount)
                ->setIdProject($project)
                ->setType(ProjectRepaymentTask::TYPE_EARLY)
                ->setStatus(ProjectRepaymentTask::STATUS_PENDING)
                ->setRepayAt($nextRepayment->getDateEcheance())
                ->setIdUserCreation($user)
                ->setIdWireTransferIn($reception);
            $this->entityManager->persist($projectRepaymentTask);
        }
        $this->entityManager->flush($projectRepaymentTask);

        $this->projectRepaymentNotificationSender->sendInComingEarlyRepaymentNotification($reception);

        return $projectRepaymentTask;
    }

    /**
     * @param Projects $project
     */
    public function disableAutomaticRepayment(Projects $project)
    {
        $readyRepaymentTask = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')->findBy(['idProject' => $project, 'status' => ProjectRepaymentTask::STATUS_READY]);

        foreach ($readyRepaymentTask as $task) {
            $task->setStatus(ProjectRepaymentTask::STATUS_PENDING);
        }

        $project->setRembAuto(Projects::AUTO_REPAYMENT_OFF);

        $this->entityManager->flush();
    }

    /**
     * @param Projects $project
     */
    public function enableAutomaticRepayment(Projects $project)
    {
        $pendingRepaymentTask = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')->findBy([
            'idProject' => $project,
            'status'    => ProjectRepaymentTask::STATUS_PENDING
        ]);

        foreach ($pendingRepaymentTask as $task) {
            $task->setStatus(ProjectRepaymentTask::STATUS_READY);
        }

        $project->setRembAuto(Projects::AUTO_REPAYMENT_ON);

        $this->entityManager->flush();
    }

    /**
     * @param ProjectRepaymentTask $projectRepaymentTask
     *
     * @return bool
     */
    public function isReady(ProjectRepaymentTask $projectRepaymentTask)
    {
        if ($projectRepaymentTask->getRepayAt() > new \DateTime()) {
            $this->logger->warning(
                'The projects repayment task (id: ' . $projectRepaymentTask->getId() . ') is planed for a future date.',
                ['method' => __METHOD__]
            );

            return false;
        }

        if (ProjectRepaymentTask::STATUS_READY !== $projectRepaymentTask->getStatus()) {
            $this->logger->warning(
                'The projects repayment task (id: ' . $projectRepaymentTask->getId() . ') is not ready.',
                ['method' => __METHOD__]
            );

            return false;
        }

        $project = $projectRepaymentTask->getIdProject();
        if (null === $project) {
            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_ERROR);
            $this->entityManager->flush($projectRepaymentTask);

            $this->logger->warning(
                'The project of the repayment task (id: ' . $projectRepaymentTask->getId() . ') dose not exist',
                ['method' => __METHOD__]
            );

            return false;
        }

        $amountToRepay = $this->getAmountToRepay($projectRepaymentTask);
        if (0 > $amountToRepay) {
            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_ERROR);
            $this->entityManager->flush($projectRepaymentTask);

            $this->logger->error(
                'The repayment task (id: ' . $projectRepaymentTask->getId() . ') has been over-repaid.',
                ['method' => __METHOD__]
            );

            return false;
        }

        if (0 == $amountToRepay) {
            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_REPAID);
            $this->entityManager->flush($projectRepaymentTask);

            $this->logger->warning(
                'The amount has totally been repaid for the repayment task (id: ' . $projectRepaymentTask->getId() . '). The status of the task is changed to "repaid".',
                ['method' => __METHOD__]
            );

            return false;
        }

        if ($projectRepaymentTask->getSequence()) {
            $repaymentSchedules = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')
                ->findByProject($projectRepaymentTask->getIdProject(), $projectRepaymentTask->getSequence(), null, [Echeanciers::STATUS_PENDING, Echeanciers::STATUS_PARTIALLY_REPAID]);
            if (0 === count($repaymentSchedules)) {
                $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_ERROR);
                $this->entityManager->flush($projectRepaymentTask);

                $this->logger->warning(
                    'Cannot find payment or repayment schedule to repay for the repayment task (id: ' . $projectRepaymentTask->getId() . '). Please check the data consistency.',
                    ['method' => __METHOD__]
                );

                return false;
            }

            if ($repaymentSchedules[0]->getDateEcheance()->setTime(0, 0, 0) > new \DateTime()) {
                $this->logger->warning(
                    'The repayment schedule date of projects repayment task (id: ' . $projectRepaymentTask->getId() . ') is in the future.',
                    ['method' => __METHOD__]
                );

                return false;
            }
        }

        if (in_array($projectRepaymentTask->getType(), [ProjectRepaymentTask::TYPE_REGULAR, ProjectRepaymentTask::TYPE_LATE])) {
            if (null === $projectRepaymentTask->getSequence()) {
                $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_ERROR);
                $this->entityManager->flush($projectRepaymentTask);
                $this->logger->warning(
                    'The sequence of projects repayment task (id: ' . $projectRepaymentTask->getId() . ') is null. It is not supported by a regular or late repayment.',
                    ['method' => __METHOD__]
                );

                return false;
            }
        }

        if (ProjectRepaymentTask::TYPE_EARLY === $projectRepaymentTask->getType()) {
            /** @var \echeanciers_emprunteur $paymentScheduleDate */
            $paymentScheduleDate = $this->entityManagerSimulator->getRepository('echeanciers_emprunteur');

            $nextPayment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')
                ->findOneBy(['idProject' => $projectRepaymentTask->getIdProject(), 'statusEmprunteur' => EcheanciersEmprunteur::STATUS_PENDING], ['ordre' => 'ASC']);

            if ($nextPayment) {
                $borrowerOwedCapital = $paymentScheduleDate->reste_a_payer_ra($projectRepaymentTask->getIdProject()->getIdProject(), $nextPayment->getOrdre());

                if (0 !== bccomp($borrowerOwedCapital, $projectRepaymentTask->getAmount(), 2)) {
                    $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_ERROR);
                    $this->entityManager->flush($projectRepaymentTask);

                    $this->logger->error(
                        'The repayment task (id: ' . $projectRepaymentTask->getId() . ') has not enough amount for an early repayment.',
                        ['method' => __METHOD__]
                    );

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param ProjectRepaymentTask $projectRepaymentTask
     *
     * @return float
     */
    public function getRepaidAmount(ProjectRepaymentTask $projectRepaymentTask)
    {
        $this->entityManager->refresh($projectRepaymentTask);
        $taskLogs = $projectRepaymentTask->getTaskLogs();

        if (empty($taskLogs)) {
            return 0;
        }

        $amount = 0;
        foreach ($taskLogs as $log) {
            $amount = bcadd($amount, $log->getRepaidAmount(), 4);
        }

        return round($amount, 2);
    }

    /**
     * @param ProjectRepaymentTask $projectRepaymentTask
     *
     * @return float
     */
    private function getAmountToRepay(ProjectRepaymentTask $projectRepaymentTask)
    {
        $repaidAmount  = $this->getRepaidAmount($projectRepaymentTask);
        $amountToRepay = $projectRepaymentTask->getAmount();

        return round(bcsub($amountToRepay, $repaidAmount, 4), 2);
    }

    /**
     * @param \DateTime $date
     * @param bool      $countDown
     *
     * @return \DateTime
     */
    private function getLimitDate(\DateTime $date, $countDown = false)
    {
        $interval = new \DateInterval('P1D');

        if ($countDown) {
            $interval->invert = 1;
        }
        $workingDays = 1;

        while ($workingDays <= 5) {
            $date->add($interval);

            if ($this->businessDays->isHoliday($date->getTimestamp())) {
                $workingDays++;
            }
        }

        return $date;
    }

    /**
     * @param ProjectRepaymentTask $projectRepaymentTask
     * @param float                $repaidAmount
     * @param int                  $repaidLoanNb
     *
     * @return ProjectRepaymentTaskLog
     */
    public function start(ProjectRepaymentTask $projectRepaymentTask, $repaidAmount = 0.00, $repaidLoanNb = 0)
    {
        $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_IN_PROGRESS);
        $this->entityManager->flush($projectRepaymentTask);

        $projectRepaymentTaskLog = new ProjectRepaymentTaskLog();
        $projectRepaymentTaskLog->setIdTask($projectRepaymentTask)
            ->setStarted(new \DateTime())
            ->setRepaidAmount($repaidAmount)
            ->setRepaymentNb($repaidLoanNb);
        $this->entityManager->persist($projectRepaymentTaskLog);
        $this->entityManager->flush($projectRepaymentTaskLog);

        return $projectRepaymentTaskLog;
    }

    /**
     * @param ProjectRepaymentTaskLog $projectRepaymentTaskLog
     * @param float                   $repaidAmount
     * @param int                     $repaidLoanNb
     *
     * @return ProjectRepaymentTaskLog
     */
    public function end(ProjectRepaymentTaskLog $projectRepaymentTaskLog, $repaidAmount, $repaidLoanNb)
    {
        $projectRepaymentTaskLog->setRepaidAmount($repaidAmount)
            ->setRepaymentNb($repaidLoanNb)
            ->setEnded(new \DateTime());

        $this->entityManager->flush($projectRepaymentTaskLog);

        return $projectRepaymentTaskLog;
    }
}
