<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\CloseOutNettingPayment;
use Unilend\Bundle\CoreBusinessBundle\Entity\CloseOutNettingRepayment;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;

class ProjectCloseOutNettingManager
{

    /** @var EntityManager */
    private $entityManager;

    /** @var ProjectStatusManager */
    private $projectStatusManager;

    public function __construct(EntityManager $entityManager, ProjectStatusManager $projectStatusManager)
    {
        $this->entityManager        = $entityManager;
        $this->projectStatusManager = $projectStatusManager;
    }

    /**
     * @param Projects  $project
     * @param \DateTime $closeOutNettingDate
     *
     * @throws \Exception
     */
    public function decline(Projects $project, \DateTime $closeOutNettingDate)
    {
        if ($project->getCloseOutNettingDate()) {
            throw new \Exception('The project (id: ' . $project->getIdProject() . ') has already been declined.');
        }

        if ($project->getStatus() < ProjectsStatus::PROBLEME) {
            throw new \Exception('The project (id: ' . $project->getIdProject() . ') has status ' . $project->getStatus() . '. You cannot decline the repayment schedules.');
        }

        $projectRepaymentTask = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')->findOneBy([
            'idProject' => $project,
            'status'    => [ProjectRepaymentTask::STATUS_PENDING, ProjectRepaymentTask::STATUS_READY, ProjectRepaymentTask::STATUS_IN_PROGRESS, ProjectRepaymentTask::STATUS_ERROR]
        ]);
        if ($projectRepaymentTask) {
            throw new \Exception('There are pending repayment tasks to treat for the project (id: ' . $project->getIdProject() . '). You cannot decline the repayment schedules.');
        }

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $project->setCloseOutNettingDate($closeOutNettingDate);
            $this->entityManager->flush($project);

            $this->buildRepayments($project, $closeOutNettingDate);
            $this->buildPayments($project, $closeOutNettingDate);
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw $exception;
        }
    }

    /**
     * @param Projects $project
     *
     * @return bool
     */
    public function canBeDeclined(Projects $project)
    {
        $projectRepaymentTask = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')->findOneBy([
            'idProject' => $project,
            'status'    => [ProjectRepaymentTask::STATUS_PENDING, ProjectRepaymentTask::STATUS_READY, ProjectRepaymentTask::STATUS_IN_PROGRESS, ProjectRepaymentTask::STATUS_ERROR]
        ]);

        return null === $project->getCloseOutNettingDate() && $project->getStatus() >= ProjectsStatus::PROBLEME && null === $projectRepaymentTask;
    }

    /**
     * @param Projects $project
     *
     * @throws \Exception
     */
    private function buildRepayments(Projects $project)
    {
        if (null === $project->getCloseOutNettingDate()) {
            throw new \Exception('The project (id:' . $project->getIdProject() . ' has not the close out netting date');
        }
        $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');

        $loans = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->findBy(['idProject' => $project]);

        foreach ($loans as $loan) {
            $capital  = $repaymentScheduleRepository->getRemainingCapitalByLoan($loan);
            $interest = $repaymentScheduleRepository->getOverdueInterestByLoan($loan, $project->getCloseOutNettingDate());

            $closeOutNettingRepayment = new CloseOutNettingRepayment();
            $closeOutNettingRepayment->setIdLoan($loan)
                ->setCapital($capital)
                ->setInterest($interest)
                ->setRepaidCapital(0)
                ->setRepaidInterest(0);

            $this->entityManager->persist($closeOutNettingRepayment);
            $this->entityManager->flush($closeOutNettingRepayment);
        }
    }

    /**
     * @param Projects $project
     *
     * @throws \Exception
     */
    private function buildPayments(Projects $project)
    {
        if (null === $project->getCloseOutNettingDate()) {
            throw new \Exception('The project (id:' . $project->getIdProject() . ' has not the close out netting date');
        }
        $paymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');

        $overdueAmounts = $paymentScheduleRepository->getTotalOverdueAmounts($project, $project->getCloseOutNettingDate());
        $capital        = $paymentScheduleRepository->getRemainingCapitalByProject($project);
        $interest       = $overdueAmounts['interest'];
        $commission     = $overdueAmounts['commission'];

        $closeOUtNettingPayment = new CloseOutNettingPayment();
        $closeOUtNettingPayment->setIdProject($project)
            ->setCapital($capital)
            ->setInterest($interest)
            ->setCommissionTaxIncl($commission)
            ->setPaidCapital(0)
            ->setPaidInterest(0)
            ->setPaidCommissionTaxIncl(0)
            ->setNotified(false);

        $this->entityManager->persist($closeOUtNettingPayment);
        $this->entityManager->flush($closeOUtNettingPayment);
    }
}
