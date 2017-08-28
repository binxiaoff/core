<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\Factures;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTaskLog;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
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

    /**
     * ProjectRepaymentManager constructor.
     *
     * @param EntityManager                      $entityManager
     * @param EntityManagerSimulator             $entityManagerSimulator
     * @param OperationManager                   $operationManager
     * @param ProjectManager                     $projectManager
     * @param ProjectRepaymentTaskManager        $projectRepaymentTaskManager
     * @param ProjectRepaymentNotificationSender $projectRepaymentNotificationSender
     * @param LoggerInterface                    $logger
     */
    public function __construct(
        EntityManager $entityManager,
        EntityManagerSimulator $entityManagerSimulator,
        OperationManager $operationManager,
        ProjectManager $projectManager,
        ProjectRepaymentTaskManager $projectRepaymentTaskManager,
        ProjectRepaymentNotificationSender $projectRepaymentNotificationSender,
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
    }

    /**
     * Repay entirely a repayment schedule
     *
     * @param ProjectRepaymentTask $projectRepaymentTask
     * @param int                  $idUser
     *
     * @return ProjectRepaymentTaskLog|null
     */
    public function repay(ProjectRepaymentTask $projectRepaymentTask, $idUser = Users::USER_ID_CRON)
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

        $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        $repaidLoanNb                = 0;
        $repaidAmount                = 0;

        $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_IN_PROGRESS);
        $this->entityManager->flush($projectRepaymentTask);

        $repaymentSchedules = $repaymentScheduleRepository->findBy([
            'idProject' => $projectRepaymentTask->getIdProject(),
            'ordre'     => $projectRepaymentTask->getSequence(),
            'status'    => Echeanciers::STATUS_PENDING
        ]);

        if (0 === count($repaymentSchedules)) {
            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_ERROR);
            $this->entityManager->flush($projectRepaymentTask);

            $this->logger->warning(
                'Cannot find payment or repayment schedule to repay for the repayment task (id: ' . $projectRepaymentTask->getId() . '). Please check the data consistency.',
                ['method' => __METHOD__]
            );

            return null;
        }

        $repaymentSequence = $projectRepaymentTask->getSequence();
        $project           = $projectRepaymentTask->getIdProject();

        $projectRepaymentTaskLog = new ProjectRepaymentTaskLog();
        $projectRepaymentTaskLog->setIdTask($projectRepaymentTask)
            ->setStarted(new \DateTime())
            ->setRepaidAmount($repaidAmount)
            ->setRepaymentNb($repaidLoanNb);
        $this->entityManager->persist($projectRepaymentTaskLog);
        $this->entityManager->flush($projectRepaymentTaskLog);

        foreach ($repaymentSchedules as $repaymentSchedule) {
            $this->entityManager->getConnection()->beginTransaction();
            try {
                $this->operationManager->repayment($repaymentSchedule, $projectRepaymentTaskLog);

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

                break;
            }
        }

        $projectRepaymentTaskLog->setRepaidAmount($repaidAmount)
            ->setRepaymentNb($repaidLoanNb);

        $this->entityManager->flush($projectRepaymentTaskLog);

        $unpaidRepaymentSchedules = $repaymentScheduleRepository->findByProject($project, $repaymentSequence, null, Echeanciers::STATUS_PENDING, EcheanciersEmprunteur::STATUS_PAID, null, 0, 1);

        if (0 === count($unpaidRepaymentSchedules)) {
            $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy(['idProject' => $project, 'ordre' => $repaymentSequence]);
            $this->operationManager->repaymentCommission($paymentSchedule, $projectRepaymentTaskLog);

            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_REPAID);
            $this->entityManager->flush($projectRepaymentTask);

            $this->createPaymentScheduleInvoice($paymentSchedule);
            $this->projectRepaymentNotificationSender->sendPaymentScheduleInvoiceToBorrower($paymentSchedule);

            $pendingRepaymentSchedule = $repaymentScheduleRepository->findByProject($project, null, null, Echeanciers::STATUS_PENDING, null, null, 0, 1);

            if (0 === count($pendingRepaymentSchedule)) {
                $this->projectManager->addProjectStatus($idUser, ProjectsStatus::REMBOURSE, $project);
                $this->projectRepaymentNotificationSender->sendInternalNotificationEndOfRepayment($project);
                $this->projectRepaymentNotificationSender->sendClientNotificationEndOfRepayment($project);
            }
        } else {
            $this->projectRepaymentNotificationSender->sendIncompleteRepaymentNotification($project, $repaymentSequence);
        }

        $projectRepaymentTaskLog->setEnded(new \DateTime());
        $this->entityManager->flush($projectRepaymentTaskLog);

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
}
