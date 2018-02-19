<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMission;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentDetail;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTaskLog;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\core\Loader;

class ProjectRepaymentTaskManager
{
    /** @var EntityManager */
    private $entityManager;

    /** @var ProjectRepaymentNotificationSender */
    private $projectRepaymentNotificationSender;

    /** @var LoggerInterface */
    private $logger;

    /** @var \jours_ouvres */
    private $businessDays;

    /**
     * ProjectRepaymentTaskManager constructor.
     *
     * @param EntityManager                      $entityManager
     * @param ProjectRepaymentNotificationSender $projectRepaymentNotificationSender
     * @param LoggerInterface                    $logger
     */
    public function __construct(EntityManager $entityManager, ProjectRepaymentNotificationSender $projectRepaymentNotificationSender, LoggerInterface $logger)
    {
        $this->entityManager                      = $entityManager;
        $this->logger                             = $logger;
        $this->projectRepaymentNotificationSender = $projectRepaymentNotificationSender;
        $this->businessDays                       = Loader::loadLib('jours_ouvres');
    }

    /**
     * @param Echeanciers                $repaymentSchedule
     * @param float                      $capitalToRepay
     * @param float                      $interestToRepay
     * @param float                      $commissionToPay
     * @param Receptions                 $reception
     * @param Users                      $user
     * @param \DateTime|null             $repayOn
     * @param DebtCollectionMission|null $debtCollectionMission
     *
     * @throws \Exception
     * @return boolean
     */
    public function planRepaymentTask(
        Echeanciers $repaymentSchedule,
        $capitalToRepay,
        $interestToRepay,
        $commissionToPay,
        \DateTime $repayOn = null,
        Receptions $reception,
        Users $user,
        DebtCollectionMission $debtCollectionMission = null
    )
    {
        $project  = $repaymentSchedule->getIdLoan()->getProject();
        $sequence = $repaymentSchedule->getOrdre();

        // a repaid task or a already launched task
        $repaidProjectRepaymentTask = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')->findOneBy([
            'idProject' => $project,
            'sequence'  => $sequence,
            'status'    => [ProjectRepaymentTask::STATUS_IN_PROGRESS, ProjectRepaymentTask::STATUS_ERROR, ProjectRepaymentTask::STATUS_REPAID]
        ]);

        if ($repaidProjectRepaymentTask) {
            /** @var Receptions $wireTransferIn */
            $wireTransferIn = $repaidProjectRepaymentTask->getIdWireTransferIn();
            if ($this->entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->findOneBy(['idReceptionRejected' => $wireTransferIn])
                || $wireTransferIn->getStatusPrelevement() === Receptions::DIRECT_DEBIT_STATUS_REJECTED // for legacy compatibility
                || $wireTransferIn->getStatusVirement() === Receptions::WIRE_TRANSFER_STATUS_REJECTED // for legacy compatibility
            ) {
                // Regularize "Unilend loss" (the direct debit is rejected after that we have repaid the lenders) by putting the regularization wire transfer on the repaid task if the old one has been rejected.
                $repaidProjectRepaymentTask->setIdWireTransferIn($reception);
                $this->entityManager->flush($repaidProjectRepaymentTask);

                return true;
            }
        }

        if (
            0 === bccomp($capitalToRepay, 0, 2)
            && 0 === bccomp($interestToRepay, 0, 2)
            && 0 === bccomp($commissionToPay, 0, 2)
        ) {
            throw new \Exception('The repayment amount of project (id: ' . $project->getIdProject() . ') sequence ' . $sequence . ' is 0. Please check the data consistency.');
        }

        if (null === $repayOn || $repayOn < $repaymentSchedule->getDateEcheance()) {
            $repayOn = $repaymentSchedule->getDateEcheance();
        }

        $projectRepaymentTask = new ProjectRepaymentTask();
        $projectRepaymentTask->setIdProject($project)
            ->setSequence($sequence)
            ->setCapital($capitalToRepay)
            ->setInterest($interestToRepay)
            ->setCommissionUnilend($commissionToPay)
            ->setType(ProjectRepaymentTask::TYPE_REGULAR)
            ->setStatus(projectRepaymentTask::STATUS_PENDING)
            ->setRepayAt($repayOn)
            ->setIdUserCreation($user)
            ->setIdDebtCollectionMission($debtCollectionMission)
            ->setIdWireTransferIn($reception);

        $paymentSchedule   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy(['idProject' => $project, 'ordre' => $sequence]);
        $totalCapital      = round(bcdiv($paymentSchedule->getCapital(), 100, 4), 2);
        $totalInterest     = round(bcdiv($paymentSchedule->getInterets(), 100, 4), 2);
        $monthlyCommission = round(bcdiv($paymentSchedule->getCommission() + $paymentSchedule->getTva(), 100, 4), 2);

        if (
            Projects::AUTO_REPAYMENT_ON === $project->getRembAuto()
            && $project->getStatus() < ProjectsStatus::PROBLEME
            && 0 === bccomp($totalCapital, $capitalToRepay, 2)
            && 0 === bccomp($totalInterest, $interestToRepay, 2)
            && 0 === bccomp($monthlyCommission, $commissionToPay, 2)
        ) {
            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_READY)
                ->setIdUserValidation($user);
        }

        $this->entityManager->persist($projectRepaymentTask);
        $this->entityManager->flush($projectRepaymentTask);

        $this->checkPlannedTaskAmount($project, $sequence);

        return true;
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
            $amount = round(bcadd($projectRepaymentTask->getCapital(), $receivedAmount, 4), 2);
            $projectRepaymentTask->setCapital($amount);
        } else {
            $limitDate = $this->getLimitDate(new \DateTime('today midnight'));

            $nextRepayment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')->findNextPendingScheduleAfter($limitDate, $project);

            $projectRepaymentTask = new ProjectRepaymentTask();
            $projectRepaymentTask->setCapital($receivedAmount)
                ->setInterest(0)
                ->setCommissionUnilend(0)
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
     * @param float                      $capitalToRepay
     * @param float                      $interestToRepay
     * @param  float                     $commissionToPay
     * @param \DateTime                  $repayOn
     * @param Receptions                 $reception
     * @param Users                      $user
     * @param DebtCollectionMission|null $debtCollectionMission
     *
     * @throws \Exception
     */
    public function planCloseOutNettingRepaymentTask(
        $capitalToRepay,
        $interestToRepay,
        $commissionToPay,
        \DateTime $repayOn,
        Receptions $reception,
        Users $user,
        DebtCollectionMission $debtCollectionMission = null
    )
    {
        $project = $reception->getIdProject();

        if (
            0 === bccomp($capitalToRepay, 0, 2)
            && 0 === bccomp($interestToRepay, 0, 2)
            && 0 === bccomp($commissionToPay, 0, 2)
        ) {
            throw new \Exception('The close out netting repayment amount of project (id: ' . $project->getIdProject() . ') is 0. Please check the data consistency.');
        }

        $projectRepaymentTask = new ProjectRepaymentTask();
        $projectRepaymentTask->setIdProject($project)
            ->setSequence(null)
            ->setCapital($capitalToRepay)
            ->setInterest($interestToRepay)
            ->setCommissionUnilend($commissionToPay)
            ->setType(ProjectRepaymentTask::TYPE_CLOSE_OUT_NETTING)
            ->setStatus(projectRepaymentTask::STATUS_PENDING)
            ->setRepayAt($repayOn)
            ->setIdUserCreation($user)
            ->setIdDebtCollectionMission($debtCollectionMission)
            ->setIdWireTransferIn($reception);

        $this->entityManager->persist($projectRepaymentTask);
        $this->entityManager->flush($projectRepaymentTask);

        $this->checkPlannedTaskAmount($project);
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
                'The project of the repayment task (id: ' . $projectRepaymentTask->getId() . ') does not exist',
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

        if (0 == $amountToRepay && 0 == $projectRepaymentTask->getCommissionUnilend()) {
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
            $paymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');

            $nextPayment = $paymentScheduleRepository->findOneBy(
                ['idProject' => $projectRepaymentTask->getIdProject(), 'statusEmprunteur' => EcheanciersEmprunteur::STATUS_PENDING],
                ['ordre' => 'ASC']
            );

            if ($nextPayment) {
                $borrowerOwedCapital = $paymentScheduleRepository->getRemainingCapitalByProject($projectRepaymentTask->getIdProject());

                if (0 !== bccomp($borrowerOwedCapital, $projectRepaymentTask->getCapital(), 2)) {
                    $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_ERROR);
                    $this->entityManager->flush($projectRepaymentTask);

                    $this->logger->error(
                        'The repayment task (id: ' . $projectRepaymentTask->getId() . ') has not the right amount for an early repayment.',
                        ['method' => __METHOD__]
                    );

                    return false;
                }
            }
        } else {
            try {
                $this->checkPlannedTaskAmount($projectRepaymentTask->getIdProject(), $projectRepaymentTask->getSequence());
            } catch (\Exception $exception) {
                $this->logger->warning(
                    $exception->getMessage(),
                    ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                );

                return false;
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
        $amountToRepay = round(bcadd($projectRepaymentTask->getCapital(), $projectRepaymentTask->getInterest(), 4), 2);

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
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function start(ProjectRepaymentTask $projectRepaymentTask, float $repaidAmount = 0.00, int $repaidLoanNb = 0) : ProjectRepaymentTaskLog
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
     * @param int                     $projectRepaymentTaskStatus
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function end(ProjectRepaymentTaskLog $projectRepaymentTaskLog, int $projectRepaymentTaskStatus) : void
    {
        $projectRepaymentTaskLog->setEnded(new \DateTime());

        $this->entityManager->flush($projectRepaymentTaskLog);

        $projectRepaymentTask = $projectRepaymentTaskLog->getIdTask();
        $projectRepaymentTask->setStatus($projectRepaymentTaskStatus);

        $this->entityManager->flush($projectRepaymentTask);
    }

    /**
     * @param ProjectRepaymentTaskLog $projectRepaymentTaskLog
     * @param float                   $repaidAmount
     * @param int                     $repaidLoanNb
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function log(ProjectRepaymentTaskLog $projectRepaymentTaskLog, float $repaidAmount, int $repaidLoanNb) : void
    {
        $projectRepaymentTaskLog
            ->setRepaidAmount($repaidAmount)
            ->setRepaymentNb($repaidLoanNb);

        $this->entityManager->flush($projectRepaymentTaskLog);
    }

    /**
     * It checks if a planned repayment task is a complete repayment.
     *
     * @param ProjectRepaymentTask $projectRepaymentTask
     *
     * @return bool
     */
    public function isCompleteRepayment(ProjectRepaymentTask $projectRepaymentTask)
    {
        $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy([
            'idProject' => $projectRepaymentTask->getIdProject(),
            'ordre'     => $projectRepaymentTask->getSequence()
        ]);

        if (
            0 === bccomp($projectRepaymentTask->getCapital(), round(bcdiv($paymentSchedule->getCapital(), 100, 4), 2), 2)
            && 0 === bccomp($projectRepaymentTask->getInterest(), round(bcdiv($paymentSchedule->getInterets(), 100, 4), 2), 2)
            && 0 === bccomp($projectRepaymentTask->getCommissionUnilend(), round(bcdiv($paymentSchedule->getCommission() + $paymentSchedule->getTva(), 100, 4), 2), 2)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param Projects $project
     * @param int      $sequence
     *
     * @throws \Exception
     */
    private function checkPlannedTaskAmount(Projects $project, $sequence = null)
    {
        if (null === $project->getCloseOutNettingDate() && null != $sequence) {
            $this->checkPlannedRegularRepaymentTaskAmount($project, $sequence);
        } else {
            $this->checkPlannedCloseOutNettingRepaymentTaskAmount($project);
        }
    }

    private function checkPlannedRegularRepaymentTaskAmount(Projects $project, $sequence)
    {
        $repaymentTasks = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')
            ->findBy([
                'idProject' => $project,
                'type'      => [
                    ProjectRepaymentTask::TYPE_REGULAR,
                    ProjectRepaymentTask::TYPE_LATE,
                ],
                'sequence'  => $sequence,
                'status'    => [
                    ProjectRepaymentTask::STATUS_ERROR,
                    ProjectRepaymentTask::STATUS_PENDING,
                    ProjectRepaymentTask::STATUS_READY,
                    ProjectRepaymentTask::STATUS_IN_PROGRESS,
                    ProjectRepaymentTask::STATUS_REPAID
                ]
            ]);

        $plannedCapital    = 0;
        $plannedInterest   = 0;
        $plannedCommission = 0;
        foreach ($repaymentTasks as $task) {
            $plannedCapital    = round(bcadd($plannedCapital, $task->getCapital(), 4), 2);
            $plannedInterest   = round(bcadd($plannedInterest, $task->getInterest(), 4), 2);
            $plannedCommission = round(bcadd($plannedCommission, $task->getCommissionUnilend(), 4), 2);
        }

        $paymentSchedule   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy(['idProject' => $project, 'ordre' => $sequence]);
        $totalCapital      = round(bcdiv($paymentSchedule->getCapital(), 100, 4), 2);
        $totalInterest     = round(bcdiv($paymentSchedule->getInterets(), 100, 4), 2);
        $monthlyCommission = round(bcdiv($paymentSchedule->getCommission() + $paymentSchedule->getTva(), 100, 4), 2);

        $compareCapital = bccomp($plannedCapital, $totalCapital, 2);
        if (1 === $compareCapital) {
            throw new \Exception('The total capital (' . $plannedCapital . ') of all the repayment tasks for project (id: ' . $project->getIdProject() . ') sequence ' . $sequence . ' is more then the monthly capital (' . $totalCapital . '). Please check the data consistency.');
        }

        $compareInterest = bccomp($plannedInterest, $totalInterest, 2);
        if (1 === $compareInterest) {
            throw new \Exception('The total interest (' . $plannedInterest . ') of all the repayment tasks for project (id: ' . $project->getIdProject() . ') sequence ' . $sequence . ' is more then the monthly interest (' . $totalInterest . '). Please check the data consistency.');
        }

        $compareCommission = bccomp($plannedCommission, $monthlyCommission, 2);
        if (1 === $compareCommission) {
            throw new \Exception('The total commission (' . $plannedCommission . ') of all the repayment tasks for project (id: ' . $project->getIdProject() . ') sequence ' . $sequence . ' is more then the monthly commission (' . $monthlyCommission . '). Please check the data consistency.');
        }
    }

    private function checkPlannedCloseOutNettingRepaymentTaskAmount(Projects $project)
    {
        $repaymentTasks = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')
            ->findBy([
                'idProject' => $project,
                'type'      => [
                    ProjectRepaymentTask::TYPE_CLOSE_OUT_NETTING,
                ],
                'status'    => [
                    ProjectRepaymentTask::STATUS_ERROR,
                    ProjectRepaymentTask::STATUS_PENDING,
                    ProjectRepaymentTask::STATUS_READY,
                    ProjectRepaymentTask::STATUS_IN_PROGRESS,
                    ProjectRepaymentTask::STATUS_REPAID
                ]
            ]);

        $plannedCapital    = 0;
        $plannedInterest   = 0;
        $plannedCommission = 0;
        foreach ($repaymentTasks as $task) {
            $plannedCapital    = round(bcadd($plannedCapital, $task->getCapital(), 4), 2);
            $plannedInterest   = round(bcadd($plannedInterest, $task->getInterest(), 4), 2);
            $plannedCommission = round(bcadd($plannedCommission, $task->getCommissionUnilend(), 4), 2);
        }

        $closeOutNettingPayment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingPayment')->findOneBy(['idProject' => $project]);

        $compareCapital = bccomp($plannedCapital, $closeOutNettingPayment->getCapital(), 2);
        if (1 === $compareCapital) {
            throw new \Exception('The total capital (' . $plannedCapital . ') of all the close out netting repayment tasks for project (id: ' . $project->getIdProject() . ') is more then the total remaining capital (' . $closeOutNettingPayment->getCapital() . '). Please check the data consistency.');
        }

        $compareInterest = bccomp($plannedInterest, $closeOutNettingPayment->getInterest(), 2);
        if (1 === $compareInterest) {
            throw new \Exception('The total interest (' . $plannedInterest . ') of all the close out netting repayment tasks for project (id: ' . $project->getIdProject() . ') is more then the total remaining interest (' . $closeOutNettingPayment->getInterest() . '). Please check the data consistency.');
        }

        $compareCommission = bccomp($plannedCommission, $closeOutNettingPayment->getCommissionTaxIncl(), 2);
        if (1 === $compareCommission) {
            throw new \Exception('The total commission (' . $plannedCommission . ') of all the close out netting repayment tasks for project (id: ' . $project->getIdProject() . ') is more then the total remaining commission (' . $closeOutNettingPayment->getCommissionTaxIncl() . '). Please check the data consistency.');
        }

    }

    /**
     * @param ProjectRepaymentTask $projectRepaymentTask
     */
    public function prepare(ProjectRepaymentTask $projectRepaymentTask)
    {
        if (0 < count($this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentDetail')->findBy(['idTask' => $projectRepaymentTask]))) {
            return;
        }

        if (in_array($projectRepaymentTask->getType(), [ProjectRepaymentTask::TYPE_REGULAR, ProjectRepaymentTask::TYPE_LATE])) {
            $this->prepareRegularRepayment($projectRepaymentTask);
        } elseif (ProjectRepaymentTask::TYPE_CLOSE_OUT_NETTING === $projectRepaymentTask->getType()) {
            $this->prepareCloseOutNettingRepayment($projectRepaymentTask);
        }
    }

    private function prepareRegularRepayment(ProjectRepaymentTask $projectRepaymentTask)
    {
        $repaidCapital               = 0;
        $repaidInterest              = 0;
        $coverageOnNotRepaidCapital  = $this->getCoverageOnNotRepaidCapital($projectRepaymentTask);
        $coverageOnNotRepaidInterest = $this->getCoverageOnNotRepaidInterest($projectRepaymentTask);

        $repaymentSchedules = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')
            ->findByProject($projectRepaymentTask->getIdProject(), $projectRepaymentTask->getSequence(), null, [Echeanciers::STATUS_PENDING, Echeanciers::STATUS_PARTIALLY_REPAID]);

        foreach ($repaymentSchedules as $repaymentSchedule) {
            $unRepaidCapital  = round(bcdiv($repaymentSchedule->getCapital() - $repaymentSchedule->getCapitalRembourse(), 100, 4), 2);
            $unRepaidInterest = round(bcdiv($repaymentSchedule->getInterets() - $repaymentSchedule->getInteretsRembourses(), 100, 4), 2);

            $capitalToRepay  = $this->calculateAmountToRepay($unRepaidCapital, $coverageOnNotRepaidCapital);
            $interestToRepay = $this->calculateAmountToRepay($unRepaidInterest, $coverageOnNotRepaidInterest);

            $this->addRepaymentDetail($projectRepaymentTask, $repaymentSchedule->getIdLoan(), $repaymentSchedule, $capitalToRepay, $interestToRepay, $unRepaidCapital, $unRepaidInterest);

            $repaidCapital  = round(bcadd($repaidCapital, $capitalToRepay, 4), 2);
            $repaidInterest = round(bcadd($repaidInterest, $interestToRepay, 4), 2);
        }

        $this->adjustRepaymentAmount($projectRepaymentTask, $repaidCapital, $repaidInterest);
    }

    private function prepareCloseOutNettingRepayment(ProjectRepaymentTask $projectRepaymentTask)
    {
        $repaidCapital               = 0;
        $repaidInterest              = 0;
        $coverageOnNotRepaidCapital  = $this->getCoverageOnNotRepaidCapital($projectRepaymentTask);
        $coverageOnNotRepaidInterest = $this->getCoverageOnNotRepaidInterest($projectRepaymentTask);

        $closeOutNettingRepayments = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingRepayment')->findByProject($projectRepaymentTask->getIdProject());

        foreach ($closeOutNettingRepayments as $closeOutNettingRepayment) {
            $unRepaidCapital  = round(bcsub($closeOutNettingRepayment->getCapital(), $closeOutNettingRepayment->getRepaidCapital(), 4), 2);
            $unRepaidInterest = round(bcsub($closeOutNettingRepayment->getInterest(), $closeOutNettingRepayment->getRepaidInterest(), 4), 2);

            $capitalToRepay  = $this->calculateAmountToRepay($unRepaidCapital, $coverageOnNotRepaidCapital);
            $interestToRepay = $this->calculateAmountToRepay($unRepaidInterest, $coverageOnNotRepaidInterest);

            $this->addRepaymentDetail($projectRepaymentTask, $closeOutNettingRepayment->getIdLoan(), null, $capitalToRepay, $interestToRepay, $unRepaidCapital, $unRepaidInterest);

            $repaidCapital  = round(bcadd($repaidCapital, $capitalToRepay, 4), 2);
            $repaidInterest = round(bcadd($repaidInterest, $interestToRepay, 4), 2);
        }

        $this->adjustRepaymentAmount($projectRepaymentTask, $repaidCapital, $repaidInterest);
    }

    /**
     * @param float $unRepaidAmount
     * @param float $coverageOnNotRepaidAmount
     *
     * @return float
     */
    private function calculateAmountToRepay($unRepaidAmount, $coverageOnNotRepaidAmount)
    {
        $capitalProportion = round(bcmul($unRepaidAmount, $coverageOnNotRepaidAmount, 4), 2);

        return min($capitalProportion, $unRepaidAmount);
    }

    /**
     * @param ProjectRepaymentTask $projectRepaymentTask
     * @param Loans                $loan
     * @param Echeanciers|null     $repaymentSchedule
     * @param float                $capitalToRepay
     * @param float                $interestToRepay
     * @param float                $unRepaidCapital
     * @param float                $unRepaidInterest
     */
    private function addRepaymentDetail(
        ProjectRepaymentTask $projectRepaymentTask,
        Loans $loan,
        Echeanciers $repaymentSchedule = null,
        $capitalToRepay,
        $interestToRepay,
        $unRepaidCapital,
        $unRepaidInterest
    )
    {
        $projectRepaymentDetail = new ProjectRepaymentDetail();

        $projectRepaymentDetail->setIdTask($projectRepaymentTask)
            ->setIdloan($loan)
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
    }

    /**
     * @param ProjectRepaymentTask $projectRepaymentTask
     * @param float                $repaidCapital
     * @param float                $repaidInterest
     */
    private function adjustRepaymentAmount(ProjectRepaymentTask $projectRepaymentTask, $repaidCapital, $repaidInterest)
    {
        $capitalCompareResult  = bccomp($projectRepaymentTask->getCapital(), $repaidCapital, 2);
        $interestCompareResult = bccomp($projectRepaymentTask->getInterest(), $repaidInterest, 2);

        if (0 !== $capitalCompareResult || 0 !== $interestCompareResult) {
            $projectRepaymentDetailRepository   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentDetail');
            $closeOutNettingRepaymentRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingRepayment');

            $capitalNumberOfCents = abs(round(bcdiv(round(bcsub($projectRepaymentTask->getCapital(), $repaidCapital, 4), 2), 0.01, 4)));
            if ($capitalNumberOfCents > 0) {
                $randomRepayments = $projectRepaymentDetailRepository->findRandomlyUncompletedByTaskExecutionForCapital($projectRepaymentTask, $capitalNumberOfCents);

                foreach ($randomRepayments as $projectRepaymentDetail) {
                    if (1 === $capitalCompareResult) {
                        $adjustAmount = 0.01;
                    } else {
                        $adjustAmount = -0.01;
                    }

                    $capital = round(bcadd($projectRepaymentDetail->getCapital(), $adjustAmount, 4), 2);
                    $projectRepaymentDetail->setCapital($capital);

                    $repaymentSchedule = $projectRepaymentDetail->getIdRepaymentSchedule();
                    if ($repaymentSchedule) {
                        $unRepaidCapital = round(bcdiv($repaymentSchedule->getCapital() - $repaymentSchedule->getCapitalRembourse(), 100, 4), 2);
                    } else {
                        $loan                     = $projectRepaymentDetail->getIdLoan();
                        $closeOutNettingRepayment = $closeOutNettingRepaymentRepository->findOneBy(['idLoan' => $loan]);
                        $unRepaidCapital          = round(bcsub($closeOutNettingRepayment->getCapital(), $closeOutNettingRepayment->getRepaidCapital(), 4), 2);
                    }
                    if (0 === bccomp($projectRepaymentDetail->getCapital(), $unRepaidCapital, 2)) {
                        $projectRepaymentDetail->setCapitalCompleted(ProjectRepaymentDetail::CAPITAL_COMPLETED);
                    }

                    $repaidCapital = round(bcadd($repaidCapital, $adjustAmount, 4), 2);

                    $this->entityManager->flush($projectRepaymentDetail);
                }
            }

            $interestNumberOfCents = abs(round(bcdiv(round(bcsub($projectRepaymentTask->getInterest(), $repaidInterest, 4), 2), 0.01, 4)));
            if ($interestNumberOfCents > 0) {
                $randomRepayments = $projectRepaymentDetailRepository->findRandomlyUncompletedByTaskExecutionForInterest($projectRepaymentTask, $interestNumberOfCents);

                foreach ($randomRepayments as $projectRepaymentDetail) {
                    if (1 === $interestCompareResult) {
                        $adjustAmount = 0.01;
                    } else {
                        $adjustAmount = -0.01;
                    }

                    $interest = round(bcadd($projectRepaymentDetail->getInterest(), $adjustAmount, 4), 2);
                    $projectRepaymentDetail->setInterest($interest);

                    $repaymentSchedule = $projectRepaymentDetail->getIdRepaymentSchedule();
                    if ($repaymentSchedule) {
                        $unRepaidInterest = round(bcdiv($repaymentSchedule->getInterets() - $repaymentSchedule->getInteretsRembourses(), 100, 4), 2);
                    } else {
                        $loan                     = $projectRepaymentDetail->getIdLoan();
                        $closeOutNettingRepayment = $closeOutNettingRepaymentRepository->findOneBy(['idLoan' => $loan]);
                        $unRepaidInterest         = round(bcsub($closeOutNettingRepayment->getInterest(), $closeOutNettingRepayment->getRepaidInterest(), 4), 2);
                    }
                    if (0 === bccomp($projectRepaymentDetail->getInterest(), $unRepaidInterest, 2)) {
                        $projectRepaymentDetail->setInterestCompleted(ProjectRepaymentDetail::INTEREST_COMPLETED);
                    }

                    $repaidInterest = round(bcadd($repaidInterest, $adjustAmount, 4), 2);

                    $this->entityManager->flush($projectRepaymentDetail);
                }
            }

            $this->adjustRepaymentAmount($projectRepaymentTask, $repaidCapital, $repaidInterest);
        }
    }

    /**
     * Get the task coverage rate on the non-repaid capital
     *
     * @param ProjectRepaymentTask $projectRepaymentTask
     *
     * @return string
     */
    private function getCoverageOnNotRepaidCapital(ProjectRepaymentTask $projectRepaymentTask)
    {
        $unpaidCapital = 0;
        if ($projectRepaymentTask->getSequence()) {
            $unpaidCapital = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')
                ->getNotRepaidCapitalByProjectAndSequence($projectRepaymentTask->getIdProject(), $projectRepaymentTask->getSequence());
        } elseif (ProjectRepaymentTask::TYPE_CLOSE_OUT_NETTING === $projectRepaymentTask->getType()) {
            $unpaidCapital = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingRepayment')
                ->getNotRepaidCapitalByProject($projectRepaymentTask->getIdProject());
        }

        if (0 === bccomp($unpaidCapital, 0, 2)) {
            return 0;
        }

        return bcdiv($projectRepaymentTask->getCapital(), $unpaidCapital, 10);
    }

    /**
     * @param ProjectRepaymentTask $projectRepaymentTask
     *
     * @return string
     */
    private function getCoverageOnNotRepaidInterest(ProjectRepaymentTask $projectRepaymentTask)
    {
        $unpaidInterest = 0;
        if ($projectRepaymentTask->getSequence()) {

            $unpaidInterest = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')
                ->getNotRepaidInterestByProjectAndSequence($projectRepaymentTask->getIdProject(), $projectRepaymentTask->getSequence());
        } elseif (ProjectRepaymentTask::TYPE_CLOSE_OUT_NETTING === $projectRepaymentTask->getType()) {
            $unpaidInterest = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingRepayment')
                ->getNotRepaidInterestByProject($projectRepaymentTask->getIdProject());
        }

        if (0 === bccomp($unpaidInterest, 0, 2)) {
            return 0;
        }

        return bcdiv($projectRepaymentTask->getInterest(), $unpaidInterest, 10);
    }

    /**
     * @param ProjectRepaymentTask $projectRepaymentTask
     *
     * @throws \Exception
     */
    public function checkPreparedRepayments(ProjectRepaymentTask $projectRepaymentTask)
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
