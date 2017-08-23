<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\Factures;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTaskLog;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class ProjectRepaymentManager
{
    /** @var EntityManager */
    private $entityManager;

    /** @var OperationManager */
    private $operationManager;

    /** @var LoggerInterface */
    private $logger;

    /** @var Packages */
    private $assetsPackages;

    /** @var string */
    private $frontUrl;

    /** @var \NumberFormatter */
    private $numberFormatter;

    /** @var \NumberFormatter */
    private $currencyFormatter;

    /** @var RouterInterface */
    private $router;

    /** @var TemplateMessageProvider */
    private $messageProvider;

    /** @var \Swift_Mailer */
    private $mailer;

    /** @var ProjectManager */
    private $projectManager;

    /** @var MailerManager */
    private $emailManager;

    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;

    /**
     * ProjectRepaymentManager constructor.
     *
     * @param EntityManager           $entityManager
     * @param EntityManagerSimulator  $entityManagerSimulator
     * @param OperationManager        $operationManager
     * @param ProjectManager          $projectManager
     * @param MailerManager           $emailManager
     * @param TemplateMessageProvider $messageProvider
     * @param \Swift_Mailer           $mailer
     * @param \NumberFormatter        $numberFormatter
     * @param \NumberFormatter        $currencyFormatter
     * @param RouterInterface         $router
     * @param LoggerInterface         $logger
     * @param Packages                $assetsPackages
     * @param string                  $frontUrl
     */
    public function __construct(
        EntityManager $entityManager,
        EntityManagerSimulator $entityManagerSimulator,
        OperationManager $operationManager,
        ProjectManager $projectManager,
        MailerManager $emailManager,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        \NumberFormatter $numberFormatter,
        \NumberFormatter $currencyFormatter,
        RouterInterface $router,
        LoggerInterface $logger,
        Packages $assetsPackages,
        $frontUrl
    )
    {
        $this->entityManager          = $entityManager;
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->operationManager       = $operationManager;
        $this->logger                 = $logger;
        $this->assetsPackages         = $assetsPackages;
        $this->frontUrl               = $frontUrl;
        $this->numberFormatter        = $numberFormatter;
        $this->currencyFormatter      = $currencyFormatter;
        $this->router                 = $router;
        $this->messageProvider        = $messageProvider;
        $this->mailer                 = $mailer;
        $this->projectManager         = $projectManager;
        $this->emailManager           = $emailManager;
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
        $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        $repaidLoanNb                = 0;
        $repaidAmount                = 0;

        if ($projectRepaymentTask->getRepayAt() > new \DateTime()) {
            $this->logger->warning(
                'The projects repayment task (id: ' . $projectRepaymentTask->getId() . ') is planed for a future date, or the date is null.',
                ['method' => __METHOD__]
            );

            return null;
        }

        if (ProjectRepaymentTask::STATUS_READY !== $projectRepaymentTask->getStatus()) {
            $this->logger->warning(
                'The projects repayment task (id: ' . $projectRepaymentTask->getId() . ') is not ready.',
                ['method' => __METHOD__]
            );

            return null;
        }

        if (null === $projectRepaymentTask->getSequence()) {
            $this->logger->warning(
                'The sequence of projects repayment task (id: ' . $projectRepaymentTask->getId() . ') is null. It is not supported by this repayment method',
                ['method' => __METHOD__]
            );

            return null;
        }

        $project = $projectRepaymentTask->getIdProject();
        if (null === $project) {
            $this->logger->warning(
                'The project of the repayment task (id: ' . $projectRepaymentTask->getId() . ') dose not exist',
                ['method' => __METHOD__]
            );

            return null;
        }

        $amountToRepay = $this->getAmountToRepay($projectRepaymentTask);
        if (0 > $amountToRepay) {
            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_ERROR);
            $this->entityManager->flush($projectRepaymentTask);

            $this->logger->error(
                'The repayment task (id: ' . $projectRepaymentTask->getId() . ') has been over-repaid.',
                ['method' => __METHOD__]
            );

            return null;
        }

        if (0 == $amountToRepay) {
            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_REPAID);
            $this->entityManager->flush($projectRepaymentTask);

            $this->logger->warning(
                'The amount has totally been repaid for the repayment task (id: ' . $projectRepaymentTask->getId() . '). The status of the task is changed to "repaid".',
                ['method' => __METHOD__]
            );

            return null;
        }

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

        $repaymentTaskLog = new ProjectRepaymentTaskLog();
        $repaymentTaskLog->setIdTask($projectRepaymentTask)
            ->setStarted(new \DateTime())
            ->setRepaidAmount($repaidAmount)
            ->setRepaymentNb($repaidLoanNb);
        $this->entityManager->persist($repaymentTaskLog);
        $this->entityManager->flush($repaymentTaskLog);

        foreach ($repaymentSchedules as $repaymentSchedule) {
            $this->entityManager->getConnection()->beginTransaction();
            try {
                $this->operationManager->repayment($repaymentSchedule, $repaymentTaskLog);

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

                $repaymentTaskLog->setRepaidAmount($repaidAmount)
                    ->setRepaymentNb($repaidLoanNb);

                $this->entityManager->flush($repaymentTaskLog);

                $this->logger->error(
                    'An error occurs for the repayment # ' . $repaymentSchedule->getIdEcheancier() . ' of project # ' . $project->getIdProject() . ' (order: ' . $repaymentSequence . '). Error : ' . $exception->getMessage(),
                    ['method' => __METHOD__]
                );

                continue;
            }
        }

        $repaymentTaskLog->setRepaidAmount($repaidAmount)
            ->setRepaymentNb($repaidLoanNb);

        $this->entityManager->flush($repaymentTaskLog);

        $unpaidRepaymentSchedules = $repaymentScheduleRepository->findByProject($project, $repaymentSequence, null, Echeanciers::STATUS_PENDING, EcheanciersEmprunteur::STATUS_PAID, null, 0, 1);

        if (0 === count($unpaidRepaymentSchedules)) {
            $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy(['idProject' => $project, 'ordre' => $repaymentSequence]);
            $this->operationManager->repaymentCommission($paymentSchedule, $repaymentTaskLog);

            $projectRepaymentTask->setStatus(ProjectRepaymentTask::STATUS_REPAID);
            $this->entityManager->flush($projectRepaymentTask);

            $this->createPaymentScheduleInvoice($paymentSchedule);
            $this->sendPaymentScheduleInvoiceToBorrower($paymentSchedule);

            $pendingRepaymentSchedule = $repaymentScheduleRepository->findByProject($project, null, null, Echeanciers::STATUS_PENDING, null, null, 0, 1);

            if (0 === count($pendingRepaymentSchedule)) {
                $this->projectManager->addProjectStatus($idUser, ProjectsStatus::REMBOURSE, $project);
                $this->emailManager->setLogger($this->logger);
                $this->emailManager->sendInternalNotificationEndOfRepayment($project);
                $this->emailManager->sendClientNotificationEndOfRepayment($project);
            }
        } else {
            $this->sendIncompleteRepaymentNotification($project, $repaymentSequence);
        }

        $repaymentTaskLog->setEnded(new \DateTime());
        $this->entityManager->flush($repaymentTaskLog);

        return $repaymentTaskLog;
    }

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

    /**
     * @param EcheanciersEmprunteur $paymentSchedule
     */
    private function sendPaymentScheduleInvoiceToBorrower(EcheanciersEmprunteur $paymentSchedule)
    {
        $borrower          = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($paymentSchedule->getIdProject()->getIdCompany()->getIdClientOwner());
        $project           = $paymentSchedule->getIdProject();
        $company           = $paymentSchedule->getIdProject()->getIdCompany();
        $lastProjectStatus = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory')->findOneBy(
            ['idProject' => $project->getIdProject()],
            ['added' => 'DESC', 'idProjectStatusHistory' => 'DESC']
        );

        $facebook = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Facebook'])->getValue();
        $twitter  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Twitter'])->getValue();

        $varMail = [
            'surl'            => $this->assetsPackages->getUrl(''),
            'url'             => $this->frontUrl,
            'prenom'          => $borrower->getPrenom(),
            'pret'            => $this->numberFormatter->format($project->getAmount()),
            'entreprise'      => stripslashes(trim($company->getName())),
            'projet-title'    => $project->getTitle(),
            'compte-p'        => $this->frontUrl,
            'projet-p'        => $this->frontUrl . $this->router->generate('project_detail', ['projectSlug' => $project->getSlug()]),
            'link_facture'    => $this->frontUrl . '/pdf/facture_ER/' . $borrower->getHash() . '/' . $project->getIdProject() . '/' . $paymentSchedule->getOrdre(),
            'datedelafacture' => strftime('%d %B %G', $lastProjectStatus->getAdded()->getTimestamp()),
            'mois'            => strftime('%B', $lastProjectStatus->getAdded()->getTimestamp()),
            'annee'           => date('Y'),
            'lien_fb'         => $facebook,
            'lien_tw'         => $twitter,
            'montantRemb'     => $this->numberFormatter->format(round(bcdiv(bcadd(bcadd($paymentSchedule->getMontant(), $paymentSchedule->getCommission()), $paymentSchedule->getTva()), 100, 3), 2))
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->messageProvider->newMessage('facture-emprunteur-remboursement', $varMail);
        try {
            $message->setTo($borrower->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning(
                'Could not send email: facture-emprunteur-remboursement - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $borrower->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * @param Echeanciers $repaymentSchedule
     */
    public function sendRepaymentMailToLender(Echeanciers $repaymentSchedule)
    {
        $operationRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $settingsRepository  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings');

        $lenderWallet   = $repaymentSchedule->getIdLoan()->getIdLender();
        $lender         = $lenderWallet->getIdClient();
        $grossRepayment = $operationRepository->getGrossAmountByRepaymentScheduleId($repaymentSchedule);
        $netRepayment   = $operationRepository->getNetAmountByRepaymentScheduleId($repaymentSchedule);
        $facebook       = $settingsRepository->findOneBy(['type' => 'Facebook'])->getValue();
        $twitter        = $settingsRepository->findOneBy(['type' => 'Twitter'])->getValue();

        $varMail                  = [
            'surl'                  => $this->assetsPackages->getUrl(''),
            'url'                   => $this->frontUrl,
            'prenom_p'              => $lender->getPrenom(),
            'mensualite_p'          => $this->currencyFormatter->formatCurrency($netRepayment, 'EUR'),
            'mensualite_avantfisca' => $this->currencyFormatter->formatCurrency($grossRepayment, 'EUR'),
            'nom_entreprise'        => $repaymentSchedule->getIdLoan()->getProject()->getIdCompany()->getName(),
            'date_bid_accepte'      => strftime('%d %B %G', $repaymentSchedule->getIdLoan()->getAdded()->getTimestamp()),
            'solde_p'               => $this->currencyFormatter->formatCurrency($lenderWallet->getAvailableBalance(), 'EUR'),
            'motif_virement'        => $lenderWallet->getWireTransferPattern(),
            'lien_fb'               => $facebook,
            'lien_tw'               => $twitter,
            'annee'                 => date('Y'),
            'date_pret'             => strftime('%A %d %B %G', $repaymentSchedule->getIdLoan()->getAdded()->getTimestamp()),
        ];
        $pendingRepaymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')->findOneBy([
            'idLoan' => $repaymentSchedule->getIdLoan(),
            'status' => Echeanciers::STATUS_PENDING
        ]);

        if ($pendingRepaymentSchedule) {
            $mailTemplate = 'preteur-remboursement';
        } else {
            $mailTemplate = 'preteur-dernier-remboursement';
        }
        $message = $this->messageProvider->newMessage($mailTemplate, $varMail);
        try {
            $message->setTo($lender->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning(
                'Could not send email: ' . $mailTemplate . ' - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $lender->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * @param Echeanciers $repaymentSchedule
     */
    public function sendDebtCollectionRepaymentMailToLender(Echeanciers $repaymentSchedule)
    {
        $settingsRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings');

        $lenderWallet  = $repaymentSchedule->getIdLoan()->getIdLender();
        $lender        = $lenderWallet->getIdClient();
        $netRepayment  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->getNetAmountByRepaymentScheduleId($repaymentSchedule);
        $debtCollector = $settingsRepository->findOneBy(['type' => 'Cabinet de recouvrement'])->getValue();
        $facebook      = $settingsRepository->findOneBy(['type' => 'Facebook'])->getValue();
        $twitter       = $settingsRepository->findOneBy(['type' => 'Twitter'])->getValue();

        $varMail = [
            'surl'             => $this->assetsPackages->getUrl(''),
            'url'              => $this->frontUrl,
            'prenom_p'         => $lender->getPrenom(),
            'cab_recouvrement' => $debtCollector,
            'mensualite_p'     => $this->currencyFormatter->formatCurrency($netRepayment, 'EUR'),
            'nom_entreprise'   => $repaymentSchedule->getIdLoan()->getProject()->getIdCompany()->getName(),
            'solde_p'          => $this->currencyFormatter->formatCurrency($lenderWallet->getAvailableBalance(), 'EUR'),
            'link_echeancier'  => $this->frontUrl,
            'motif_virement'   => $lenderWallet->getWireTransferPattern(),
            'lien_fb'          => $facebook,
            'lien_tw'          => $twitter,
        ];

        $message = $this->messageProvider->newMessage('preteur-dossier-recouvre', $varMail);
        try {
            $message->setTo($lender->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning(
                'Could not send email: preteur-dossier-recouvre - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $lender->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * @param Echeanciers $repaymentSchedule
     */
    public function sendRegularisationRepaymentMailToLender(Echeanciers $repaymentSchedule)
    {
        $operationRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $settingsRepository  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings');

        $lenderWallet   = $repaymentSchedule->getIdLoan()->getIdLender();
        $lender         = $lenderWallet->getIdClient();
        $grossRepayment = $operationRepository->getGrossAmountByRepaymentScheduleId($repaymentSchedule);
        $netRepayment   = $operationRepository->getNetAmountByRepaymentScheduleId($repaymentSchedule);
        $facebook       = $settingsRepository->findOneBy(['type' => 'Facebook'])->getValue();
        $twitter        = $settingsRepository->findOneBy(['type' => 'Twitter'])->getValue();

        $varMail = [
            'surl'                  => $this->assetsPackages->getUrl(''),
            'url'                   => $this->frontUrl,
            'prenom_p'              => $lender->getPrenom(),
            'mensualite_p'          => $this->currencyFormatter->formatCurrency($netRepayment, 'EUR'),
            'mensualite_avantfisca' => $this->currencyFormatter->formatCurrency($grossRepayment, 'EUR'),
            'nom_entreprise'        => $repaymentSchedule->getIdLoan()->getProject()->getIdCompany()->getName(),
            'date_bid_accepte'      => strftime('%d %B %G', $repaymentSchedule->getIdLoan()->getAdded()->getTimestamp()),
            'solde_p'               => $this->currencyFormatter->formatCurrency($lenderWallet->getAvailableBalance(), 'EUR'),
            'motif_virement'        => $lenderWallet->getWireTransferPattern(),
            'lien_fb'               => $facebook,
            'lien_tw'               => $twitter,
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->messageProvider->newMessage('preteur-regularisation-remboursement', $varMail);
        try {
            $message->setTo($lender->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning(
                'Could not send email: preteur-regularisation-remboursement - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $lender->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     *
     * @param Projects $project
     * @param int      $repaymentSequence
     */
    private function sendIncompleteRepaymentNotification(Projects $project, $repaymentSequence)
    {
        $alertMailIT = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'DebugMailIt'])->getValue();

        $varMail = [
            'project_id'    => $project->getIdProject(),
            'project_title' => $project->getTitle(),
            'order'         => $repaymentSequence
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->messageProvider->newMessage('incomplete-repayment-notification', $varMail);
        try {
            $message->setTo($alertMailIT);
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning(
                'Could not send email : incomplete-repayment-notification - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'email_address' => $alertMailIT, 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * Find repayment schedules to repay. When a repayment task is ready to be treated, the borrow payment schedule should already been paid.
     * So, we take the paid payment schedules and search for each payment sequence until we find a non-repaid repayment sequence.
     * The function returns all non-repaid loans in the same repayment sequence.
     *
     * @param ProjectRepaymentTask $projectRepaymentTask
     *
     * @return Echeanciers[]|null
     */
    private function findRepaymentSchedulesToRepay(ProjectRepaymentTask $projectRepaymentTask)
    {
        $paidPaymentSchedules = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findBy(
            ['idProject' => $projectRepaymentTask->getIdProject(), 'statusEmprunteur' => EcheanciersEmprunteur::STATUS_PAID],
            ['ordre' => 'ASC']
        );

        $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');

        foreach ($paidPaymentSchedules as $paidPaymentSchedule) {
            $repaymentSchedules = $repaymentScheduleRepository->findByProject($projectRepaymentTask->getIdProject(), $paidPaymentSchedule->getOrdre(), null, Echeanciers::STATUS_PENDING);

            if (0 < count($repaymentSchedules)) {
                return $repaymentSchedules;
            }
        }

        return null;
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

    /**
     * @param Receptions $reception
     * @param            $user
     *
     * @throws \Exception
     */
    public function pay(Receptions $reception, $user)
    {
        /** @var \echeanciers $echeanciers */
        $echeanciers                 = $this->entityManagerSimulator->getRepository('echeanciers');
        $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');

        $amount  = round(bcdiv($reception->getMontant(), 100, 4), 2);
        $project = $reception->getIdProject();

        $unpaidPaymentSchedules = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')
            ->findBy(['idProject' => $project, 'statusEmprunteur' => EcheanciersEmprunteur::STATUS_PENDING], ['ordre' => 'ASC']);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            foreach ($unpaidPaymentSchedules as $paymentSchedule) {
                $monthlyAmount = round(bcadd(bcadd(bcdiv($paymentSchedule->getMontant(), 100, 4), bcdiv($paymentSchedule->getCommission(), 100, 4), 4), bcdiv($paymentSchedule->getTva(), 100, 4), 4),
                    2);

                if ($monthlyAmount <= $amount) {
                    $echeanciers->updateStatusEmprunteur($project->getIdProject(), $paymentSchedule->getOrdre());

                    $paymentSchedule->setStatusEmprunteur(EcheanciersEmprunteur::STATUS_PAID)
                        ->setDateEcheanceEmprunteurReel(new \DateTime());
                    $this->entityManager->flush($paymentSchedule);

                    $repaymentSchedule = $repaymentScheduleRepository->findOneBy(['idProject' => $project, 'ordre' => $paymentSchedule->getOrdre()]);
                    $this->planRepaymentTask($repaymentSchedule, $monthlyAmount, $reception, $user);

                    $amount = round(bcsub($amount, $monthlyAmount, 4), 2);
                } else {
                    break;
                }
            }
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw $exception;
        }
    }

    /**
     * @param Echeanciers $repaymentSchedule
     * @param float       $amount
     * @param Receptions  $reception
     * @param Users       $user
     */
    private function planRepaymentTask(Echeanciers $repaymentSchedule, $amount, $reception, $user)
    {
        $project              = $repaymentSchedule->getIdLoan()->getProject();
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

        $repaymentTaskToCancel = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRepaymentTask')
            ->findBy(['idProject' => $project, 'idWireTransferIn' => $reception->getIdReceptionRejected()]);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            foreach ($repaymentTaskToCancel as $task) {
                if (in_array($task->getStatus(), [ProjectRepaymentTask::STATUS_PENDING, ProjectRepaymentTask::STATUS_READY])) {
                    $paymentSchedule = $paymentScheduleRepository->findOneBy(['idProject' => $project, 'ordre' => $task->getSequence()]);

                    $echeanciers->updateStatusEmprunteur($project->getIdProject(), $task->getSequence(), 'annuler');

                    $paymentSchedule->setStatusEmprunteur(EcheanciersEmprunteur::STATUS_PENDING)
                        ->setDateEcheanceEmprunteurReel(null);
                    $this->entityManager->flush($paymentSchedule);

                    $task->setStatus(ProjectRepaymentTask::STATUS_CANCELLED)
                        ->setRepayAt(null)
                        ->setIdUserCancellation($user);
                    $this->entityManager->flush($task);
                }
            }
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw $exception;
        }
    }

    /**
     * @param Projects   $project
     * @param Receptions $reception
     * @param Users      $user
     */
    public function planEarlyRepayment(Projects $project, Receptions $reception, Users $user)
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

        $email = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse notification nouveau remboursement anticipe'])->getValue();

        $varMail = [
            '$surl'       => $this->assetsPackages->getUrl(''),
            '$url'        => $this->frontUrl,
            '$id_projet'  => $project->getIdProject(),
            '$montant'    => bcdiv($reception->getMontant(), 100, 2),
            '$nom_projet' => $project->getTitle()
        ];

        $message = $this->messageProvider->newMessage('notification-nouveau-remboursement-anticipe', $varMail, false);
        try {
            $message->setTo($email);
            $mailer = $this->mailer;
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning(
                'Could not send email : notification-nouveau-remboursement-anticipe - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'email address' => $email, 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
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
}
