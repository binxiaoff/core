<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\{CloseOutNettingEmailExtraContent, CloseOutNettingPayment, CloseOutNettingRepayment, CompanyStatus, ProjectRepaymentTask, Projects, ProjectsStatus};

class ProjectCloseOutNettingManager
{
    const OVERDUE_LIMIT_DAYS_FIRST_GENERATION_LOANS  = 60;
    const OVERDUE_LIMIT_DAYS_SECOND_GENERATION_LOANS = 180;

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
     * @param Projects    $project
     * @param \DateTime   $closeOutNettingDate
     * @param bool        $includeUnilendCommission
     * @param bool        $sendLendersEmail
     * @param bool        $sendBorrowerEmail
     * @param string|null $lendersEmailContent
     * @param string|null $borrowerEmailContent
     *
     * @throws \Exception
     */
    public function decline(Projects $project, \DateTime $closeOutNettingDate, bool $includeUnilendCommission, bool $sendLendersEmail, bool $sendBorrowerEmail, ?string $lendersEmailContent, ?string $borrowerEmailContent): void
    {
        if ($project->getCloseOutNettingDate()) {
            throw new \Exception('The project (id: ' . $project->getIdProject() . ') has already been declined.');
        }

        if ($project->getStatus() < ProjectsStatus::PROBLEME) {
            throw new \Exception('The project (id: ' . $project->getIdProject() . ') has status ' . $project->getStatus() . '. You cannot decline the repayment schedules.');
        }

        $projectRepaymentTask = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')->findOneBy([
            'idProject' => $project,
            'status'    => ProjectRepaymentTask::STATUS_PLANNED
        ]);
        if ($projectRepaymentTask) {
            throw new \Exception('There are pending repayment tasks to treat for the project (id: ' . $project->getIdProject() . '). You cannot decline the repayment schedules.');
        }

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $project->setCloseOutNettingDate($closeOutNettingDate);
            $this->entityManager->flush($project);

            $this->buildRepayments($project);
            $this->buildPayments($project, $includeUnilendCommission, $sendLendersEmail, $sendBorrowerEmail, $lendersEmailContent, $borrowerEmailContent);
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
            'status'    => ProjectRepaymentTask::STATUS_PLANNED
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
     * @param Projects    $project
     * @param bool        $includeUnilendCommission
     * @param bool        $sendLendersEmail
     * @param bool        $sendBorrowerEmail
     * @param null|string $lendersEmailContent
     * @param null|string $borrowerEmailContent
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    private function buildPayments(Projects $project, bool $includeUnilendCommission, bool $sendLendersEmail, bool $sendBorrowerEmail, ?string $lendersEmailContent, ?string $borrowerEmailContent): void
    {
        if (null === $project->getCloseOutNettingDate()) {
            throw new \Exception('The project (id:' . $project->getIdProject() . ' has not the close out netting date');
        }
        $paymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');

        $overdueAmounts = $paymentScheduleRepository->getTotalOverdueAmounts($project, $project->getCloseOutNettingDate());
        $capital        = $paymentScheduleRepository->getRemainingCapitalByProject($project);
        $interest       = $overdueAmounts['interest'];
        $commission     = $overdueAmounts['commission'];

        if (false === $includeUnilendCommission) {
            $commission = 0;
        }

        $extraMailContent = $this->createCloseOutNettingEmailExtraContent($project, $sendLendersEmail, $sendBorrowerEmail, $lendersEmailContent, $borrowerEmailContent);

        $closeOutNettingPayment = new CloseOutNettingPayment();
        $closeOutNettingPayment
            ->setIdProject($project)
            ->setCapital($capital)
            ->setInterest($interest)
            ->setCommissionTaxIncl($commission)
            ->setPaidCapital(0)
            ->setPaidInterest(0)
            ->setPaidCommissionTaxIncl(0)
            ->setLendersNotified(false === $sendLendersEmail)
            ->setBorrowerNotified(false === $sendBorrowerEmail)
            ->setIdEmailContent($extraMailContent);

        if (CompanyStatus::STATUS_IN_BONIS !== $project->getIdCompany()->getIdStatus()->getLabel()) {
            // As we have notified the lender when passing the company in a collective procedure, we won't notify the close-out netting here (asked by marketing in BLD-82)
            $closeOutNettingPayment
                ->setLendersNotified(true)
                ->setBorrowerNotified(true);
        }

        $this->entityManager->persist($closeOutNettingPayment);
        $this->entityManager->flush($closeOutNettingPayment);
    }

    /**
     * @param Projects    $project
     * @param bool        $sendLendersEmail
     * @param bool        $sendBorrowerEmail
     * @param string|null $lendersEmailContent
     * @param string|null $borrowerEmailContent
     *
     * @return CloseOutNettingEmailExtraContent|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createCloseOutNettingEmailExtraContent(Projects $project, bool $sendLendersEmail, bool $sendBorrowerEmail, ?string $lendersEmailContent, ?string $borrowerEmailContent): ?CloseOutNettingEmailExtraContent
    {
        $extraMailContent = null;

        if ($sendLendersEmail && false === empty($lendersEmailContent) || $sendBorrowerEmail && false === empty($borrowerEmailContent)) {
            $extraMailContent = new CloseOutNettingEmailExtraContent();
            $extraMailContent->setIdProject($project);

            if (false === empty($lendersEmailContent)) {
                $extraMailContent->setLendersContent($lendersEmailContent);
            }
            if (false === empty($borrowerEmailContent)) {
                $extraMailContent->setBorrowerContent($borrowerEmailContent);
            }

            $this->entityManager->persist($extraMailContent);
            $this->entityManager->flush($extraMailContent);
        }

        return $extraMailContent;
    }
}
