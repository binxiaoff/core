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

    /** @var ProjectRepaymentScheduleManager */
    private $projectRepaymentScheduleManager;

    /**
     * ProjectRepaymentManager constructor.
     *
     * @param EntityManager                      $entityManager
     * @param EntityManagerSimulator             $entityManagerSimulator
     * @param OperationManager                   $operationManager
     * @param ProjectManager                     $projectManager
     * @param ProjectRepaymentTaskManager        $projectRepaymentTaskManager
     * @param ProjectRepaymentNotificationSender $projectRepaymentNotificationSender
     * @param ProjectRepaymentScheduleManager    $projectRepaymentScheduleManager
     * @param LoggerInterface                    $logger
     */
    public function __construct(
        EntityManager $entityManager,
        EntityManagerSimulator $entityManagerSimulator,
        OperationManager $operationManager,
        ProjectManager $projectManager,
        ProjectRepaymentTaskManager $projectRepaymentTaskManager,
        ProjectRepaymentNotificationSender $projectRepaymentNotificationSender,
        ProjectRepaymentScheduleManager $projectRepaymentScheduleManager,
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
        $this->projectRepaymentScheduleManager    = $projectRepaymentScheduleManager;
    }

    /**
     * Repay entirely a repayment schedule
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
                'The projects repayment task (id: ' . $projectRepaymentTask->getId() . ') is not a regular or late repayment.',
                ['method' => __METHOD__]
            );

            return null;
        }

        if (false === $this->projectRepaymentTaskManager->isReady($projectRepaymentTask)) {
            return null;
        }

        $netRepaymentAmount = $this->projectRepaymentScheduleManager->getNetRepaymentAmount($projectRepaymentTask->getIdProject());

        if ($netRepaymentAmount === (float) $projectRepaymentTask->getAmount()) {
            $projectRepaymentTaskLog = $this->processCompleteRepayment($projectRepaymentTask);
        } else {
            $projectRepaymentTaskLog = $this->processPartialRepayment($projectRepaymentTask);
        }

        $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_REPAID);
        $this->entityManager->flush($projectRepaymentTask);

        $pendingRepaymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')
            ->findByProject($projectRepaymentTask->getIdProject(), null, null, Echeanciers::STATUS_PENDING, null, null, 0, 1);

        if (0 === count($pendingRepaymentSchedule)) {
            $this->projectManager->addProjectStatus($userId, ProjectsStatus::REMBOURSE, $projectRepaymentTask->getIdProject());
            $this->projectRepaymentNotificationSender->sendInternalNotificationEndOfRepayment($projectRepaymentTask->getIdProject());
            $this->projectRepaymentNotificationSender->sendClientNotificationEndOfRepayment($projectRepaymentTask->getIdProject());
        }

        return $projectRepaymentTaskLog;
    }

    /**
     * @param EcheanciersEmprunteur $paymentSchedule
     */
    private function createPaymentScheduleInvoice(EcheanciersEmprunteur $paymentSchedule)
    {
        /** @var \compteur_factures $invoiceCounter */
        $invoiceCounter = $this->entityManagerSimulator->getRepository('compteur_factures');
        /** @var \factures $invoice */
        $invoice = $this->entityManagerSimulator->getRepository('factures');
        $project = $paymentSchedule->getIdProject();
        $now     = new \DateTime();

        $invoice->num_facture     = 'FR-E' . $now->format('Ymd') . str_pad($invoiceCounter->compteurJournalier($project->getIdProject(), $now->format('Y-m-d')), 5, '0', STR_PAD_LEFT);
        $invoice->date            = $now->format('Y-m-d H:i:s');
        $invoice->id_company      = $project->getIdCompany()->getIdCompany();
        $invoice->id_project      = $project->getIdProject();
        $invoice->ordre           = $paymentSchedule->getOrdre();
        $invoice->type_commission = Factures::TYPE_COMMISSION_REPAYMENT;
        $invoice->commission      = $project->getCommissionRateRepayment();
        $invoice->montant_ht      = $paymentSchedule->getCommission();
        $invoice->tva             = $paymentSchedule->getTva();
        $invoice->montant_ttc     = bcadd($paymentSchedule->getCommission(), $paymentSchedule->getTva(), 2);
        $invoice->create();
    }

    private function processCompleteRepayment(ProjectRepaymentTask $projectRepaymentTask)
    {
        $projectRepaymentTaskLog = $this->projectRepaymentTaskManager->start($projectRepaymentTask);

        $repaidLoanNb      = 0;
        $repaidAmount      = 0;
        $repaymentSequence = $projectRepaymentTask->getSequence();
        $project           = $projectRepaymentTask->getIdProject();

        $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        $repaymentSchedules          = $repaymentScheduleRepository->findBy([
            'idProject' => $projectRepaymentTask->getIdProject(),
            'ordre'     => $projectRepaymentTask->getSequence(),
            'status'    => Echeanciers::STATUS_PENDING
        ]);

        foreach ($repaymentSchedules as $repaymentSchedule) {
            $this->entityManager->getConnection()->beginTransaction();
            try {
                $this->operationManager->repayment($repaymentSchedule->getCapital(), $repaymentSchedule->getInterets(), $repaymentSchedule, $projectRepaymentTaskLog);

                $repaymentSchedule->setCapitalRembourse($repaymentSchedule->getCapital())
                    ->setInteretsRembourses($repaymentSchedule->getInterets())
                    ->setStatus(Echeanciers::STATUS_REPAID)
                    ->setDateEcheanceReel(new \DateTime());

                $this->entityManager->flush($repaymentSchedule);

                $repaidLoanNb++;
                $currentRepaidAmount = bcadd(bcdiv($repaymentSchedule->getCapitalRembourse(), 100, 4), bcdiv($repaymentSchedule->getInteretsRembourses(), 100, 4), 4);
                $repaidAmount        = round(bcadd($repaidAmount, $currentRepaidAmount, 4), 2);

                $this->entityManager->commit();
            } catch (\Exception $exception) {
                $this->entityManager->rollback();

                $this->logger->error(
                    'An error occurs for the repayment # ' . $repaymentSchedule->getIdEcheancier() . ' of project # ' . $project->getIdProject() . ' (order: ' . $repaymentSequence . '). Error : ' . $exception->getMessage(),
                    ['method' => __METHOD__]
                );

                $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_ERROR);
                $this->entityManager->flush($projectRepaymentTask);

                $this->projectRepaymentNotificationSender->sendIncompleteRepaymentNotification($project, $repaymentSequence);

                break;
            }
        }

        $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy(['idProject' => $project, 'ordre' => $repaymentSequence]);
        $this->operationManager->repaymentCommission($paymentSchedule, $projectRepaymentTaskLog);
        $this->createPaymentScheduleInvoice($paymentSchedule);
        $this->projectRepaymentNotificationSender->sendPaymentScheduleInvoiceToBorrower($paymentSchedule);

        return $this->projectRepaymentTaskManager->end($projectRepaymentTaskLog, $repaidAmount, $repaidLoanNb);
    }

    private function processPartialRepayment(ProjectRepaymentTask $projectRepaymentTask)
    {
        $projectRepaymentTaskLog = $this->projectRepaymentTaskManager->start($projectRepaymentTask);

        $repaidLoanNb      = 0;
        $repaidAmount      = 0;
        $repaymentSequence = $projectRepaymentTask->getSequence();
        $project           = $projectRepaymentTask->getIdProject();

        $this->prepare($projectRepaymentTask);

        $projectRepaymentDetails     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentDetail')->findBy([
            'idTask' => $projectRepaymentTask,
            'status' => ProjectRepaymentDetail::STATUS_PENDING
        ]);
        $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        foreach ($projectRepaymentDetails as $projectRepaymentDetail) {
            if ($projectRepaymentDetail->getCapital() == 0 && $projectRepaymentDetail->getInterest() == 0) {
                continue;
            }

            $this->entityManager->getConnection()->beginTransaction();
            try {
                $repaymentSchedules = $repaymentScheduleRepository->findByProject($project, $repaymentSequence, $projectRepaymentDetail->getIdWallet()->getIdClient());
                if (1 !== count($repaymentSchedules) || empty($repaymentSchedules[0])) {
                    throw new \Exception('Cannot found repayment schedule for project (id: ' . $project->getIdProject() . '), sequence ' . $repaymentSequence . ' and client (id: ' . $projectRepaymentDetail->getIdWallet()
                            ->getIdClient() . ')');
                }
                $repaymentSchedule = $repaymentSchedules[0];

                $this->operationManager->repayment($projectRepaymentDetail->getCapital(), $projectRepaymentDetail->getInterest(), $repaymentSchedule, $projectRepaymentTaskLog);

                $totalCapital  = round(bcdiv($repaymentSchedule->getCapital(), 100, 4), 2);
                $totalInterest = round(bcdiv($repaymentSchedule->getInterets(), 100, 4), 2);

                $repaidCapital  = round(bcadd(bcdiv($repaymentSchedule->getCapitalRembourse(), 100, 4), $projectRepaymentDetail->getCapital(), 4), 2);
                $repaidInterest = round(bcadd(bcdiv($repaymentSchedule->getInteretsRembourses(), 100, 4), $projectRepaymentDetail->getInterest(), 4), 2);

                $repaymentSchedule->setCapitalRembourse($repaidCapital)
                    ->setInteretsRembourses($repaidInterest);

                if (0 === bccomp($totalCapital, $repaidCapital, 2) && 0 === bccomp($totalInterest, $repaidInterest, 2)) {
                    $repaymentSchedule->setStatus(Echeanciers::STATUS_REPAID)
                        ->setDateEcheanceReel(new \DateTime());
                } else {
                    $repaymentSchedule->setStatus(Echeanciers::STATUS_PARTIALLY_REPAID);
                }

                $this->entityManager->flush($repaymentSchedule);

                $repaidLoanNb++;
                $currentRepaidAmount = bcadd($projectRepaymentDetail->getCapital(), $projectRepaymentDetail->getInterest(), 4);
                $repaidAmount        = round(bcadd($repaidAmount, $currentRepaidAmount, 4), 2);

                $this->entityManager->commit();
            } catch (\Exception $exception) {
                $this->entityManager->rollback();

                $this->logger->error(
                    'An error occurs for the repayment detail # ' . $projectRepaymentDetail->getId() . ' of project # ' . $project->getIdProject() . ' (order: ' . $repaymentSequence . '). Error : ' . $exception->getMessage(),
                    ['method' => __METHOD__]
                );

                $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_ERROR);
                $this->entityManager->flush($projectRepaymentTask);

                $this->projectRepaymentNotificationSender->sendIncompleteRepaymentNotification($project, $repaymentSequence);

                break;
            }
        }

    }

    private function prepare(ProjectRepaymentTask $projectRepaymentTask)
    {
        $netRepaymentAmount  = $this->projectRepaymentScheduleManager->getNetRepaymentAmount($projectRepaymentTask->getIdProject());
        $availableAmount     = $projectRepaymentTask->getAmount();
        $repaymentProportion = bcdiv($availableAmount, $netRepaymentAmount, 4);

        $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        $repaymentSchedules          = $repaymentScheduleRepository->findUnfinishedSchedule($projectRepaymentTask->getIdProject(), $projectRepaymentTask->getSequence());
        $repaidAmount                = 0;
        foreach ($repaymentSchedules as $repaymentSchedule) {
            $totalCapital  = round(bcdiv($repaymentSchedule->getCapital(), 100, 4), 2);
            $totalInterest = round(bcdiv($repaymentSchedule->getInterets(), 100, 4), 2);

            $repaidCapital  = round(bcdiv($repaymentSchedule->getCapitalRembourse(), 100, 4), 2);
            $repaidInterest = round(bcdiv($repaymentSchedule->getInteretsRembourses(), 100, 4), 2);

            $unRepaidCapital  = round(bcsub($totalCapital, $repaidCapital, 4), 2);
            $unRepaidInterest = round(bcsub($totalInterest, $repaidInterest, 4), 2);

            $capitalProportion  = round(bcmul($totalCapital, $repaymentProportion, 4), 2);
            $interestProportion = round(bcmul($totalInterest, $repaymentProportion, 4), 2);

            $capitalToRepay  = min($unRepaidCapital, $capitalProportion);
            $interestToRepay = min($unRepaidInterest, $interestProportion);

            $projectRepaymentDetail = new ProjectRepaymentDetail();

            $projectRepaymentDetail->setIdTask($projectRepaymentTask)
                ->setIdWallet($repaymentSchedule->getIdLoan()->getIdLender())
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

            $repaidAmount = round(bcadd(bcadd($repaidAmount, $capitalToRepay, 4), $interestToRepay, 4), 2);
        }

        $numberOfCents = abs(round(bcdiv(bcsub($availableAmount, $repaidAmount, 4), 0.01, 4)));
        if (0 != $numberOfCents) {
            $projectRepaymentDetails = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentDetail')->findRandomlyByTask($projectRepaymentTask, $numberOfCents);

            foreach ($projectRepaymentDetails as $projectRepaymentDetail) {
                if (1 === bccomp($availableAmount, $repaidAmount, 2)) {
                    $adjustAmount = 0.01;
                } else {
                    $adjustAmount = -0.01;
                }
                if (ProjectRepaymentDetail::CAPITAL_UNCOMPLETED === $projectRepaymentDetail->getCapitalCompleted()) {
                    $capital = round(bcadd($projectRepaymentDetail->getCapital(), $adjustAmount, 4), 2);
                    $projectRepaymentDetail->setCapital($capital);
                } elseif (ProjectRepaymentDetail::INTEREST_UNCOMPLETED === $projectRepaymentDetail->getInterestCompleted()) {
                    $interest = round(bcadd($projectRepaymentDetail->getInterest(), $adjustAmount, 4), 2);
                    $projectRepaymentDetail->setInterest($interest);
                }
            }
        }

        $this->entityManager->flush();
    }
}
