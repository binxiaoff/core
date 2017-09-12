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
    public function __construct(
        EntityManager $entityManager,
        ProjectRepaymentNotificationSender $projectRepaymentNotificationSender,
        LoggerInterface $logger
    )
    {
        $this->entityManager                      = $entityManager;
        $this->logger                             = $logger;
        $this->projectRepaymentNotificationSender = $projectRepaymentNotificationSender;
        $this->businessDays                       = Loader::loadLib('jours_ouvres');
    }

    /**
     * @param Echeanciers $repaymentSchedule
     * @param float       $capitalToRepay
     * @param float       $interestToRepay
     * @param float       $commissionToPay
     * @param Receptions  $reception
     * @param Users       $user
     *
     * @throws \Exception
     */
    public function planRepaymentTask(Echeanciers $repaymentSchedule, $capitalToRepay, $interestToRepay, $commissionToPay, Receptions $reception, Users $user)
    {
        $project = $repaymentSchedule->getIdLoan()->getProject();

        if (
            0 === bccomp($capitalToRepay, 0, 2)
            && 0 === bccomp($interestToRepay, 0, 2)
            && 0 === bccomp($commissionToPay, 0, 2)
        ) {
            throw new \Exception('The repayment amount of project (id: ' . $project->getIdProject() . ') sequence ' . $repaymentSchedule->getOrdre() . ' is 0. Please check the data consistency.');
        }

        $projectRepaymentTask = new ProjectRepaymentTask();
        $projectRepaymentTask->setIdProject($project)
            ->setSequence($repaymentSchedule->getOrdre())
            ->setCapital($capitalToRepay)
            ->setInterest($interestToRepay)
            ->setCommissionUnilend($commissionToPay)
            ->setType(ProjectRepaymentTask::TYPE_REGULAR)
            ->setStatus(projectRepaymentTask::STATUS_PENDING)
            ->setRepayAt($repaymentSchedule->getDateEcheance())
            ->setIdUserCreation($user)
            ->setIdWireTransferIn($reception);

        $paymentSchedule   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy(['idProject' => $project, 'ordre' => $repaymentSchedule->getOrdre()]);
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

        $this->checkPlannedTaskAmount($project, $repaymentSchedule->getOrdre());
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
                $borrowerOwedCapital = $paymentScheduleRepository->getRemainingCapitalFrom($projectRepaymentTask->getIdProject(), $nextPayment->getOrdre());

                if (0 !== bccomp($borrowerOwedCapital, $projectRepaymentTask->getCapital(), 2)) {
                    $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_ERROR);
                    $this->entityManager->flush($projectRepaymentTask);

                    $this->logger->error(
                        'The repayment task (id: ' . $projectRepaymentTask->getId() . ') has not enough money for an early repayment.',
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
    private function checkPlannedTaskAmount(Projects $project, $sequence)
    {
        $repaymentTasks = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')
            ->findBy([
                'idProject' => $project,
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
}
