<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;

class ProjectPaymentManager
{
    /** @var EntityManager */
    private $entityManager;

    /** @var ProjectRepaymentTaskManager */
    private $projectRepaymentTaskManager;

    /** @var ProjectRepaymentScheduleManager */
    private $projectRepaymentScheduleManager;

    /**
     * ProjectRepaymentManager constructor.
     *
     * @param EntityManager                   $entityManager
     * @param ProjectRepaymentTaskManager     $projectRepaymentTaskManager
     * @param ProjectRepaymentScheduleManager $projectRepaymentScheduleManager
     */
    public function __construct(
        EntityManager $entityManager,
        ProjectRepaymentTaskManager $projectRepaymentTaskManager,
        ProjectRepaymentScheduleManager $projectRepaymentScheduleManager
    )
    {
        $this->entityManager                   = $entityManager;
        $this->projectRepaymentTaskManager     = $projectRepaymentTaskManager;
        $this->projectRepaymentScheduleManager = $projectRepaymentScheduleManager;
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
        $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');

        $amount  = round(bcdiv($reception->getMontant(), 100, 4), 2);
        $project = $reception->getIdProject();

        $netMonthlyAmount = $this->projectRepaymentScheduleManager->getNetMonthlyAmount($project);
        $commission       = $this->projectRepaymentScheduleManager->getUnilendCommissionVatIncl($project);

        if ($project->getStatus() >= ProjectsStatus::PROBLEME) {
            return true;
        }

        $unpaidPaymentSchedules = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findUnFinishedSchedules($project);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            foreach ($unpaidPaymentSchedules as $paymentSchedule) {
                if (1 !== bccomp($amount, 0, 2)) {
                    break;
                }

                $paidNetAmount  = round(bcdiv($paymentSchedule->getPaidAmount(), 100, 4), 2);
                $paidCommission = round(bcdiv($paymentSchedule->getPaidCommissionVatIncl(), 100, 4), 2);

                $unpaidNetMonthlyAmount = round(bcsub($netMonthlyAmount, $paidNetAmount, 4), 2);
                $unpaidCommission       = round(bcsub($commission, $paidCommission, 4), 2);
                $unpaidMonthlyAmount    = round(bcadd($unpaidNetMonthlyAmount, $unpaidCommission, 4), 2);

                $compareResult = bccomp($amount, $unpaidMonthlyAmount, 2);
                if (0 === $compareResult || 1 === $compareResult) {
                    $netRepaymentAmount  = $unpaidNetMonthlyAmount;
                    $repaymentCommission = $unpaidCommission;
                } else {
                    $proportion = bcdiv($amount, $unpaidMonthlyAmount, 4);

                    $repaymentProportion = round(bcmul($unpaidNetMonthlyAmount, $proportion, 4), 2);
                    $netRepaymentAmount  = min($unpaidNetMonthlyAmount, $repaymentProportion);

                    $commissionProportion = round(bcsub($amount, $netRepaymentAmount, 4), 2);
                    $repaymentCommission  = min($unpaidCommission, $commissionProportion);

                    $difference = round(bcsub($commissionProportion, $repaymentCommission, 4), 2);
                    if (1 == bccomp($difference, 0, 2)) {
                        $netRepaymentAmount = round(bcadd($netRepaymentAmount, $difference, 4), 2);
                    }
                }

                $paymentSchedule->setPaidAmount($paymentSchedule->getPaidAmount() + bcmul($netRepaymentAmount, 100))
                    ->setPaidCommissionVatIncl($paymentSchedule->getPaidCommissionVatIncl() + bcmul($repaymentCommission, 100));

                $paidNetAmount  = round(bcdiv($paymentSchedule->getPaidAmount(), 100, 4), 2);
                $paidCommission = round(bcdiv($paymentSchedule->getPaidCommissionVatIncl(), 100, 4), 2);

                if (0 === bccomp($netMonthlyAmount, $paidNetAmount, 2) && 0 === bccomp($commission, $paidCommission, 2)) {
                    $paymentSchedule->setStatusEmprunteur(EcheanciersEmprunteur::STATUS_PAID)
                        ->setDateEcheanceEmprunteurReel(new \DateTime());
                } else {
                    $paymentSchedule->setStatusEmprunteur(EcheanciersEmprunteur::STATUS_PARTIALLY_PAID);
                }

                $this->entityManager->flush($paymentSchedule);

                $repaymentSchedule = $repaymentScheduleRepository->findOneBy([
                    'idProject' => $project,
                    'ordre'     => $paymentSchedule->getOrdre()
                ]);

                $this->projectRepaymentTaskManager->planRepaymentTask($repaymentSchedule, $netRepaymentAmount, $repaymentCommission, $reception, $user);

                $paidAmount = round(bcadd($netRepaymentAmount, $repaymentCommission, 4), 2);
                $amount     = round(bcsub($amount, $paidAmount, 4), 2);
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
        $paymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');
        $project                   = $reception->getIdProject();

        $projectRepaymentTasksToCancel = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')
            ->findBy(['idProject' => $project, 'idWireTransferIn' => $reception->getIdReceptionRejected()]);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            foreach ($projectRepaymentTasksToCancel as $task) {
                $paymentSchedule = $paymentScheduleRepository->findOneBy(['idProject' => $project, 'ordre' => $task->getSequence()]);

                $paidNetAmount  = $paymentSchedule->getPaidAmount() - bcmul($task->getAmount(), 100);
                $paidCommission = $paymentSchedule->getPaidCommissionVatIncl() - bcmul($task->getCommissionUnilend(), 100);

                if (0 === bccomp($paidNetAmount, 0, 2) && 0 === bccomp($paidCommission, 0, 2)) {
                    $status = EcheanciersEmprunteur::STATUS_PENDING;
                } else {
                    $status = EcheanciersEmprunteur::STATUS_PARTIALLY_PAID;
                }

                $paymentSchedule->setPaidAmount($paidNetAmount)
                    ->setPaidCommissionVatIncl($paidCommission)
                    ->setStatusEmprunteur($status)
                    ->setDateEcheanceEmprunteurReel(null);

                $this->entityManager->flush($paymentSchedule);

                $this->projectRepaymentTaskManager->cancelRepaymentTask($task, $user);
            }
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw $exception;
        }
    }
}
