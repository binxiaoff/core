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

    /**
     * ProjectRepaymentManager constructor.
     *
     * @param EntityManager               $entityManager
     * @param ProjectRepaymentTaskManager $projectRepaymentTaskManager
     */
    public function __construct(
        EntityManager $entityManager,
        ProjectRepaymentTaskManager $projectRepaymentTaskManager
    )
    {
        $this->entityManager               = $entityManager;
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
        $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');

        $project = $reception->getIdProject();

        if ($project->getStatus() >= ProjectsStatus::PROBLEME) {
            return true;
        }

        $amount = round(bcdiv($reception->getMontant(), 100, 4), 2);

        $unpaidPaymentSchedules = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findUnFinishedSchedules($project);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            foreach ($unpaidPaymentSchedules as $paymentSchedule) {
                if (1 !== bccomp($amount, 0, 2)) {
                    break;
                }

                $unpaidCapital            = round(bcdiv($paymentSchedule->getCapital() - $paymentSchedule->getPaidCapital(), 100, 4), 2);
                $unpaidInterest           = round(bcdiv($paymentSchedule->getInterets() - $paymentSchedule->getPaidInterest(), 100, 4), 2);
                $unpaidCommission         = round(bcdiv($paymentSchedule->getCommission() + $paymentSchedule->getTva() - $paymentSchedule->getPaidCommissionVatIncl(), 100, 4), 2);
                $unpaidNetRepaymentAmount = round(bcadd($unpaidCapital, $unpaidInterest, 4), 2);
                $unpaidMonthlyAmount      = round(bcadd($unpaidNetRepaymentAmount, $unpaidCommission, 4), 2);

                $compareResult = bccomp($amount, $unpaidMonthlyAmount, 2);
                if (0 === $compareResult || 1 === $compareResult) {
                    $capitalToPay    = $unpaidCapital;
                    $interestToPay   = $unpaidInterest;
                    $commissionToPay = $unpaidCommission;
                } else {
                    $proportion         = bcdiv($amount, $unpaidMonthlyAmount, 10);
                    $netRepaymentAmount = round(bcmul($unpaidNetRepaymentAmount, $proportion, 4), 2);

                    $commissionProportion = round(bcsub($amount, $netRepaymentAmount, 4), 2);
                    $commissionToPay      = min($unpaidCommission, $commissionProportion);

                    $difference = round(bcsub($commissionProportion, $commissionToPay, 4), 2);
                    if (1 == bccomp($difference, 0, 2)) {
                        $netRepaymentAmount = round(bcadd($netRepaymentAmount, $difference, 4), 2);
                    }

                    $capitalToPay  = min($netRepaymentAmount, $unpaidCapital);
                    $interestToPay = round(bcsub($netRepaymentAmount, $capitalToPay, 4), 2);
                }

                $paymentSchedule->setPaidCapital($paymentSchedule->getPaidCapital() + bcmul($capitalToPay, 100))
                    ->setPaidInterest($paymentSchedule->getPaidInterest() + bcmul($interestToPay, 100))
                    ->setPaidCommissionVatIncl($paymentSchedule->getPaidCommissionVatIncl() + bcmul($commissionToPay, 100));
                if (
                    $paymentSchedule->getCapital() == $paymentSchedule->getPaidCapital()
                    && $paymentSchedule->getInterets() == $paymentSchedule->getPaidInterest()
                    && $paymentSchedule->getPaidCommissionVatIncl() == $paymentSchedule->getCommission() + $paymentSchedule->getTva()
                ) {
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

                $this->projectRepaymentTaskManager->planRepaymentTask($repaymentSchedule, $capitalToPay, $interestToPay, $commissionToPay, $reception, $user);

                $paidAmount = round(bcadd(bcadd($capitalToPay, $interestToPay, 4), $commissionToPay, 4), 2);
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

                $paidCapital    = $paymentSchedule->getPaidCapital() - bcmul($task->getCapital(), 100);
                $paidInterest   = $paymentSchedule->getPaidInterest() - bcmul($task->getInterest(), 100);
                $paidCommission = $paymentSchedule->getPaidCommissionVatIncl() - bcmul($task->getCommissionUnilend(), 100);

                if (
                    0 === bccomp($paidCapital, 0, 2)
                    && 0 === bccomp($paidInterest, 0, 2)
                    && 0 === bccomp($paidCommission, 0, 2)
                ) {
                    $status = EcheanciersEmprunteur::STATUS_PENDING;
                } else {
                    $status = EcheanciersEmprunteur::STATUS_PARTIALLY_PAID;
                }

                $paymentSchedule->setPaidCapital($paidCapital)
                    ->setPaidInterest($paidInterest)
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
