<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class ProjectRepaymentTaskManager
{

    /** @var EntityManager */
    private $entityManager;

    /** @var ProjectRepaymentNotificationSender */
    private $projectRepaymentNotificationSender;

    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;

    /**
     * ProjectRepaymentTaskManager constructor.
     *
     * @param EntityManager                      $entityManager
     * @param EntityManagerSimulator             $entityManagerSimulator
     * @param ProjectRepaymentNotificationSender $projectRepaymentNotificationSender
     * @param LoggerInterface                    $logger
     */
    public function __construct(
        EntityManager $entityManager,
        EntityManagerSimulator $entityManagerSimulator,
        ProjectRepaymentNotificationSender $projectRepaymentNotificationSender,
        LoggerInterface $logger
    )
    {
        $this->entityManager                      = $entityManager;
        $this->entityManagerSimulator             = $entityManagerSimulator;
        $this->logger                             = $logger;
        $this->projectRepaymentNotificationSender = $projectRepaymentNotificationSender;
    }

    /**
     * @param Echeanciers $repaymentSchedule
     * @param float       $amount
     * @param Receptions  $reception
     * @param Users       $user
     *
     * @return bool
     */
    public function planRepaymentTask(Echeanciers $repaymentSchedule, $amount, Receptions $reception, Users $user)
    {
        $project = $repaymentSchedule->getIdLoan()->getProject();

        $finishedProjectRepaymentTasks = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')
            ->findOneBy(['idProject' => $project, 'sequence' => $repaymentSchedule->getOrdre()]);

        if ($finishedProjectRepaymentTasks) {
            $wireTransferIn = $finishedProjectRepaymentTasks->getIdWireTransferIn();

            if (null === $this->entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->findOneBy(['idReceptionRejected' => $wireTransferIn])) {
                $finishedProjectRepaymentTasks->setIdWireTransferIn($reception);
                $this->entityManager->flush($finishedProjectRepaymentTasks);
            } else {
                $this->logger->error('The repayment task is already repaid for project (id: ' . $project->getIdProject() . ') sequence ' . $repaymentSchedule->getOrdre() . '. Please check the data consistency.');
                return false;
            }

            return true;
        }

        $projectRepaymentTask = new ProjectRepaymentTask();
        $projectRepaymentTask->setIdProject($project)
            ->setSequence($repaymentSchedule->getOrdre())
            ->setAmount($amount)
            ->setType(ProjectRepaymentTask::TYPE_REGULAR)
            ->setStatus(ProjectRepaymentTask::STATUS_PENDING)
            ->setRepayAt($repaymentSchedule->getDateEcheance())
            ->setIdUserCreation($user)
            ->setIdWireTransferIn($reception);

        if (Projects::AUTO_REPAYMENT_ON === $project->getRembAuto() && $project->getStatus() < ProjectsStatus::PROBLEME) {
            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_READY)
                ->setIdUserValidation($user);
        }

        $this->entityManager->persist($projectRepaymentTask);
        $this->entityManager->flush($projectRepaymentTask);

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
            $amount = round(bcAdd($projectRepaymentTask->getAmount(), $receivedAmount, 4), 2);
            $projectRepaymentTask->setAmount($amount);
        } else {
            $projectRepaymentTask = new ProjectRepaymentTask();
            $projectRepaymentTask->setAmount($receivedAmount)
                ->setIdProject($project)
                ->setType(ProjectRepaymentTask::TYPE_EARLY)
                ->setStatus(ProjectRepaymentTask::STATUS_PENDING)
                ->setRepayAt(new \DateTime())
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
    public function checkTask(ProjectRepaymentTask $projectRepaymentTask)
    {
        if ($projectRepaymentTask->getRepayAt() > new \DateTime()) {
            $this->logger->warning(
                'The projects repayment task (id: ' . $projectRepaymentTask->getId() . ') is planed for a future date, or the date is null.',
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

        if (in_array($projectRepaymentTask->getType(), [ProjectRepaymentTask::TYPE_REGULAR, ProjectRepaymentTask::TYPE_LATE]) && null === $projectRepaymentTask->getSequence()) {
            $this->logger->warning(
                'The sequence of projects repayment task (id: ' . $projectRepaymentTask->getId() . ') is null. It is not supported by a regular or late repayment.',
                ['method' => __METHOD__]
            );

            return false;
        }

        if (ProjectRepaymentTask::TYPE_EARLY === $projectRepaymentTask->getType()) {
            /** @var \echeanciers_emprunteur $paymentScheduleDate */
            $paymentScheduleDate = $this->entityManagerSimulator->getRepository('echeanciers_emprunteur');

            $nextPayment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')
                ->findOneBy(['idProject' => $projectRepaymentTask->getIdProject(), 'statusEmprunteur' => EcheanciersEmprunteur::STATUS_PENDING], ['ordre', 'ASC']);

            $borrowerOwedCapital = $paymentScheduleDate->reste_a_payer_ra($projectRepaymentTask->getIdProject()->getIdProject(), $nextPayment->getOrdre());

            if ($borrowerOwedCapital !== $projectRepaymentTask->getAmount()) {
                $$projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_ERROR);
                $this->entityManager->flush($projectRepaymentTask);

                $this->logger->error(
                    'The repayment task (id: ' . $projectRepaymentTask->getId() . ') has not enough amount for an early repayment.',
                    ['method' => __METHOD__]
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
    private function getAmountToRepay(ProjectRepaymentTask $projectRepaymentTask)
    {
        $amount                   = $projectRepaymentTask->getAmount();
        $projectRepaymentTaskLogs = $projectRepaymentTask->getTaskLogs();

        foreach ($projectRepaymentTaskLogs as $taskLog) {
            $amount = round(bcsub($amount, $taskLog->getRepaidAmount(), 4), 2);
        }

        return $amount;
    }
}
