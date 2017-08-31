<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class ProjectRepaymentScheduleManager
{
    /** @var EntityManager */
    private $entityManager;

    /** @var ProjectRepaymentTaskManager */
    private $projectRepaymentTaskManager;

    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;

    /**
     * ProjectRepaymentManager constructor.
     *
     * @param EntityManager               $entityManager
     * @param EntityManagerSimulator      $entityManagerSimulator
     * @param ProjectRepaymentTaskManager $projectRepaymentTaskManager
     */
    public function __construct(
        EntityManager $entityManager,
        EntityManagerSimulator $entityManagerSimulator,
        ProjectRepaymentTaskManager $projectRepaymentTaskManager
    )
    {
        $this->entityManager               = $entityManager;
        $this->entityManagerSimulator      = $entityManagerSimulator;
        $this->projectRepaymentTaskManager = $projectRepaymentTaskManager;
    }

    /**
     * @param Receptions $reception
     * @param            $user
     *
     * @return bool
     * @throws \Exception
     */
    public function pay(Receptions $reception, $user)
    {
        /** @var \echeanciers $echeanciers */
        $echeanciers                 = $this->entityManagerSimulator->getRepository('echeanciers');
        $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');

        $amount  = round(bcdiv($reception->getMontant(), 100, 4), 2);
        $project = $reception->getIdProject();

        $netRepaymentAmount = $this->getNetMonthlyAmount($project);
        $monthlyAmount      = $this->getMonthlyAmount($project);

        if ($project->getStatus() >= ProjectsStatus::PROBLEME) {
            return true;
        }

        $unpaidPaymentSchedules = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')
            ->findBy(['idProject' => $project, 'statusEmprunteur' => EcheanciersEmprunteur::STATUS_PENDING], ['ordre' => 'ASC']);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            foreach ($unpaidPaymentSchedules as $paymentSchedule) {
                if ($monthlyAmount <= $amount) {
                    $echeanciers->updateStatusEmprunteur($project->getIdProject(), $paymentSchedule->getOrdre());

                    $paymentSchedule->setStatusEmprunteur(EcheanciersEmprunteur::STATUS_PAID)
                        ->setDateEcheanceEmprunteurReel(new \DateTime());
                    $this->entityManager->flush($paymentSchedule);

                    $repaymentSchedule = $repaymentScheduleRepository->findOneBy(['idProject' => $project, 'ordre' => $paymentSchedule->getOrdre()]);

                    $this->projectRepaymentTaskManager->planRepaymentTask($repaymentSchedule, $netRepaymentAmount, $reception, $user);

                    $amount = round(bcsub($amount, $monthlyAmount, 4), 2);
                } else {
                    break;
                }
            }
            $this->entityManager->getConnection()->commit();

            return true;
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw $exception;
        }
    }

    /**
     * @param Receptions $reception
     * @param Users      $user
     *
     * @throws \Exception
     */
    public function rejectPayment(Receptions $reception, Users $user)
    {
        /** @var \echeanciers $echeanciers */
        $echeanciers               = $this->entityManagerSimulator->getRepository('echeanciers');
        $paymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');
        $project                   = $reception->getIdProject();

        $projectRepaymentTasksToCancel = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')
            ->findBy(['idProject' => $project, 'idWireTransferIn' => $reception->getIdReceptionRejected()]);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            foreach ($projectRepaymentTasksToCancel as $task) {
                $paymentSchedule = $paymentScheduleRepository->findOneBy(['idProject' => $project, 'ordre' => $task->getSequence()]);
                $paymentSchedule->setStatusEmprunteur(EcheanciersEmprunteur::STATUS_PENDING)
                    ->setDateEcheanceEmprunteurReel(null);
                $this->entityManager->flush($paymentSchedule);

                $echeanciers->updateStatusEmprunteur($project->getIdProject(), $task->getSequence(), 'annuler');

                $this->projectRepaymentTaskManager->cancelRepaymentTask($task, $user);
            }
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw $exception;
        }
    }

    /**
     * @param Projects $project
     *
     * @return float
     */
    private function getMonthlyAmount(Projects $project)
    {
        $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy(['idProject' => $project]);
        return round(bcdiv(bcadd(bcadd($paymentSchedule->getMontant(), $paymentSchedule->getCommission()), $paymentSchedule->getTva()), 100, 4), 2);
    }

    /**
     * @param Projects $project
     *
     * @return float
     */
    public function getNetMonthlyAmount(Projects $project)
    {
        $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy(['idProject' => $project]);
        return round(bcdiv($paymentSchedule->getMontant(), 100, 4), 2);
    }

    /**
     * @param Projects $project
     *
     * @return float
     */
    public function getUnpaidNetMonthlyAmount(Projects $project)
    {
        $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy(['idProject' => $project]);
        return round(bcdiv($paymentSchedule->getMontant(), 100, 4), 2);
    }
}
