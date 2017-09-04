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
     * Repay completely or partially a repayment schedule
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
                'The project repayment task (id: ' . $projectRepaymentTask->getId() . ') is not a regular or late repayment.',
                ['method' => __METHOD__]
            );

            return null;
        }

        if (false === $this->projectRepaymentTaskManager->isReady($projectRepaymentTask)) {
            return null;
        }

        $netRepaymentAmount = $this->projectRepaymentScheduleManager->getNetMonthlyAmount($projectRepaymentTask->getIdProject());
        $compareResult      = bccomp($netRepaymentAmount, $projectRepaymentTask->getAmount(), 2);

        if (0 === $compareResult) {
            $projectRepaymentTaskLog = $this->processCompleteRepayment($projectRepaymentTask);
        } elseif (1 === $compareResult) {
            $projectRepaymentTaskLog = $this->processPartialRepayment($projectRepaymentTask);
        } else {
            $this->logger->warning(
                'The amount of the project repayment task (id: ' . $projectRepaymentTask->getId() . ') is invalid.',
                ['method' => __METHOD__]
            );

            return null;
        }

        $totalTaskRepaidAmount = $this->projectRepaymentTaskManager->getRepaidAmount($projectRepaymentTask);
        if (0 === bccomp($projectRepaymentTask->getAmount(), $totalTaskRepaidAmount, 2)) {
            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_REPAID);
            $this->entityManager->flush($projectRepaymentTask);
        } else {
            $this->projectRepaymentNotificationSender->sendIncompleteRepaymentNotification($projectRepaymentTask->getIdProject(), $projectRepaymentTask->getSequence());

            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_ERROR);
            $this->entityManager->flush($projectRepaymentTask);

            $this->logger->error(
                'The amount (' . $projectRepaymentTask->getAmount() . ') in the of the project repayment task (id: ' . $projectRepaymentTask->getId() . ') is different from the repaid amount (' . $totalTaskRepaidAmount . '). The task may not been completely done',
                ['method' => __METHOD__]
            );

            return null;
        }

        $this->payCommission($projectRepaymentTaskLog);

        // Invoice generation will be modified in DEV-1544
        $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy([
            'idProject' => $projectRepaymentTask->getIdProject(),
            'ordre'     => $projectRepaymentTask->getSequence()
        ]);
        $this->createPaymentScheduleInvoice($paymentSchedule);
        $this->projectRepaymentNotificationSender->sendPaymentScheduleInvoiceToBorrower($paymentSchedule);

        // Send "end of repayment" notifications
        $pendingRepaymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')
            ->findByProject($projectRepaymentTask->getIdProject(), null, null, [Echeanciers::STATUS_PENDING, Echeanciers::STATUS_PARTIALLY_REPAID], null, null, 0, 1);
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
                $capital  = round(bcdiv($repaymentSchedule->getCapital(), 100, 4), 2);
                $interest = round(bcdiv($repaymentSchedule->getInterets(), 100, 4), 2);
                $this->operationManager->repayment($capital, $interest, $repaymentSchedule, $projectRepaymentTaskLog);

                $repaymentSchedule->setCapitalRembourse($repaymentSchedule->getCapital())
                    ->setInteretsRembourses($repaymentSchedule->getInterets())
                    ->setStatus(Echeanciers::STATUS_REPAID)
                    ->setDateEcheanceReel(new \DateTime());

                $repaidLoanNb++;
                $repaidAmount = round(bcadd($repaidAmount, bcadd($capital, $interest, 4), 4), 2);

                $this->entityManager->flush($repaymentSchedule);

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

        return $this->projectRepaymentTaskManager->end($projectRepaymentTaskLog, $repaidAmount, $repaidLoanNb);
    }

    /**
     * @param ProjectRepaymentTask $projectRepaymentTask
     *
     * @return ProjectRepaymentTaskLog
     */
    private function processPartialRepayment(ProjectRepaymentTask $projectRepaymentTask)
    {
        $projectRepaymentTaskLog = $this->projectRepaymentTaskManager->start($projectRepaymentTask);

        $repaidLoanNb      = 0;
        $repaidAmount      = 0;
        $repaymentSequence = $projectRepaymentTask->getSequence();
        $project           = $projectRepaymentTask->getIdProject();

        $this->prepare($projectRepaymentTaskLog);

        $projectRepaymentDetails = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentDetail')->findBy([
            'idTaskLog' => $projectRepaymentTaskLog,
            'status'    => ProjectRepaymentDetail::STATUS_PENDING
        ]);

        foreach ($projectRepaymentDetails as $projectRepaymentDetail) {
            if ($projectRepaymentDetail->getCapital() == 0 && $projectRepaymentDetail->getInterest() == 0) {
                $projectRepaymentDetail->setStatus(ProjectRepaymentDetail::STATUS_TREATED);
                $this->entityManager->flush($projectRepaymentDetail);
                continue;
            }

            $this->entityManager->getConnection()->beginTransaction();
            try {
                /** @var Echeanciers $repaymentSchedule */
                $repaymentSchedule = $projectRepaymentDetail->getIdRepaymentSchedule();
                if (null === $repaymentSchedule) {
                    throw new \Exception('Cannot found repayment schedule for project (id: ' . $project->getIdProject() . '), sequence ' . $repaymentSequence . ' and client (id: ' . $projectRepaymentDetail->getIdWallet()
                            ->getIdClient() . ')');
                }

                $this->operationManager->repayment($projectRepaymentDetail->getCapital(), $projectRepaymentDetail->getInterest(), $repaymentSchedule, $projectRepaymentTaskLog);

                $repaidCapitalInCents  = $repaymentSchedule->getCapitalRembourse() + bcmul($projectRepaymentDetail->getCapital(), 100);
                $repaidInterestInCents = $repaymentSchedule->getInteretsRembourses() + bcmul($projectRepaymentDetail->getInterest(), 100);

                $repaymentSchedule->setCapitalRembourse($repaidCapitalInCents)
                    ->setInteretsRembourses($repaidInterestInCents);

                if ($repaymentSchedule->getCapital() === $repaymentSchedule->getCapitalRembourse() && $repaymentSchedule->getInterets() === $repaymentSchedule->getInteretsRembourses()) {
                    $repaymentSchedule->setStatus(Echeanciers::STATUS_REPAID)
                        ->setDateEcheanceReel(new \DateTime());
                } else {
                    $repaymentSchedule->setStatus(Echeanciers::STATUS_PARTIALLY_REPAID);
                }

                $projectRepaymentDetail->setStatus(ProjectRepaymentDetail::STATUS_TREATED);

                $repaidLoanNb++;
                $currentRepaidAmount = bcadd($projectRepaymentDetail->getCapital(), $projectRepaymentDetail->getInterest(), 4);
                $repaidAmount        = round(bcadd($repaidAmount, $currentRepaidAmount, 4), 2);

                $this->entityManager->flush([$repaymentSchedule, $projectRepaymentDetail]);

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

        return $this->projectRepaymentTaskManager->end($projectRepaymentTaskLog, $repaidAmount, $repaidLoanNb);
    }

    /**
     * @param ProjectRepaymentTaskLog $projectRepaymentTaskLog
     */
    public function prepare(ProjectRepaymentTaskLog $projectRepaymentTaskLog)
    {
        $repaidAmount         = 0;
        $projectRepaymentTask = $projectRepaymentTaskLog->getIdTask();
        $unpaidProportion     = $this->getUnpaidProportion($projectRepaymentTask);

        $repaymentSchedules = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')
            ->findByProject($projectRepaymentTask->getIdProject(), $projectRepaymentTask->getSequence(), null, [Echeanciers::STATUS_PENDING, Echeanciers::STATUS_PARTIALLY_REPAID]);

        foreach ($repaymentSchedules as $repaymentSchedule) {

            $unRepaidCapital  = round(bcdiv($repaymentSchedule->getCapital() - $repaymentSchedule->getCapitalRembourse(), 100, 4), 2);
            $unRepaidInterest = round(bcdiv($repaymentSchedule->getInterets() - $repaymentSchedule->getInteretsRembourses(), 100, 4), 2);

            $capitalProportion  = round(bcmul($unRepaidCapital, $unpaidProportion, 4), 2);
            $interestProportion = round(bcmul($unRepaidInterest, $unpaidProportion, 4), 2);

            $capitalToRepay  = min($capitalProportion, $unRepaidCapital);
            $interestToRepay = min($interestProportion, $unRepaidInterest);

            $projectRepaymentDetail = new ProjectRepaymentDetail();

            $projectRepaymentDetail->setIdTaskLog($projectRepaymentTaskLog)
                ->setIdWallet($repaymentSchedule->getIdLoan()->getIdLender())
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

            $repaidAmount = round(bcadd(bcadd($repaidAmount, $capitalToRepay, 4), $interestToRepay, 4), 2);
        }

        $numberOfCents = abs(round(bcdiv(bcsub($projectRepaymentTask->getAmount(), $repaidAmount, 4), 0.01, 4)));
        $this->adjustRepaymentAmount($projectRepaymentTaskLog, $repaidAmount, $numberOfCents);
        $this->entityManager->flush();
    }

    /**
     * @param ProjectRepaymentTaskLog $projectRepaymentTaskLog
     * @param float                   $repaidAmount
     * @param int                     $numberOfCents
     */
    private function adjustRepaymentAmount(ProjectRepaymentTaskLog $projectRepaymentTaskLog, $repaidAmount, $numberOfCents)
    {
        if (0 != $numberOfCents) {

            $randomRepayments = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentDetail')->findRandomlyByTaskExecution($projectRepaymentTaskLog, $numberOfCents);

            foreach ($randomRepayments as $projectRepaymentDetail) {
                if (1 === bccomp($projectRepaymentTaskLog->getIdTask()->getAmount(), $repaidAmount, 2)) {
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
                } else {
                    // It's imposable to be here. I add this line for the code readability
                    continue;
                }

                $repaymentSchedule = $projectRepaymentDetail->getIdRepaymentSchedule();
                $unRepaidCapital   = round(bcdiv($repaymentSchedule->getCapital() - $repaymentSchedule->getCapitalRembourse(), 100, 4), 2);
                $unRepaidInterest  = round(bcdiv($repaymentSchedule->getInterets() - $repaymentSchedule->getInteretsRembourses(), 100, 4), 2);

                if (0 === bccomp($projectRepaymentDetail->getCapital(), $unRepaidCapital, 2)) {
                    $projectRepaymentDetail->setCapitalCompleted(ProjectRepaymentDetail::CAPITAL_COMPLETED);
                }

                if (0 === bccomp($projectRepaymentDetail->getInterest(), $unRepaidInterest, 2)) {
                    $projectRepaymentDetail->setInterestCompleted(ProjectRepaymentDetail::INTEREST_COMPLETED);
                }
                $numberOfCents--;
            }
            $this->entityManager->flush();

            $this->adjustRepaymentAmount($projectRepaymentTaskLog, $repaidAmount, $numberOfCents);
        }
    }

    /**
     * @param ProjectRepaymentTask $projectRepaymentTask
     *
     * @return string
     */
    private function getUnpaidProportion(ProjectRepaymentTask $projectRepaymentTask)
    {
        $unpaidNetRepaymentAmount = $this->projectRepaymentScheduleManager->getUnrepaidAmount($projectRepaymentTask->getIdProject(), $projectRepaymentTask->getSequence());

        return bcdiv($projectRepaymentTask->getAmount(), $unpaidNetRepaymentAmount, 4);
    }

    /**
     * @param ProjectRepaymentTaskLog $projectRepaymentTaskLog
     */
    private function payCommission(ProjectRepaymentTaskLog $projectRepaymentTaskLog)
    {
        $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy([
            'idProject' => $projectRepaymentTaskLog->getIdTask()->getIdProject(),
            'ordre'     => $projectRepaymentTaskLog->getIdTask()->getSequence()
        ]);

        $this->operationManager->repaymentCommission($paymentSchedule, $projectRepaymentTaskLog);
    }
}
