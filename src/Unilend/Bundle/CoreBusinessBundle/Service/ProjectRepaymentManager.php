<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\Factures;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsRemb;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsRembLog;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
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
     * @param Projects $project
     * @param int      $repaymentSequence
     * @param int|null $idUser
     *
     * @return int
     */
    public function repay(Projects $project, $repaymentSequence, $idUser = null)
    {
        $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        $repaymentNb                 = 0;

        $projectRepayment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsRemb')->findOneBy([
            'idProject' => $project,
            'ordre'     => $repaymentSequence,
            'status'    => ProjectsRemb::STATUS_PENDING
        ]);

        if (null === $projectRepayment) {
            $this->logger->warning(
                'Cannot find pending repayment for project from projects_remb ' . $project->getIdProject() . ' (order: ' . $repaymentSequence . ').',
                ['method' => __METHOD__]
            );

            return $repaymentNb;
        }

        $projectRepayment->setStatus(ProjectsRemb::STATUS_IN_PROGRESS);
        $this->entityManager->flush($projectRepayment);

        $repaymentLog = new ProjectsRembLog();
        $repaymentLog->setIdProject($project)
            ->setOrdre($repaymentSequence)
            ->setDebut(new \DateTime());
        $this->entityManager->persist($repaymentLog);
        $this->entityManager->flush($repaymentLog);

        $repaymentSchedules = $repaymentScheduleRepository->findByProject($project, $repaymentSequence, null, Echeanciers::STATUS_PENDING, EcheanciersEmprunteur::STATUS_PAID);

        if (0 === count($repaymentSchedules)) {
            $projectRepayment->setStatus(ProjectsRemb::STATUS_ERROR);
            $this->entityManager->flush($projectRepayment);

            $repaymentLog->setFin(new \DateTime())
                ->setNbPretRemb($repaymentNb);
            $this->entityManager->persist($repaymentLog);
            $this->entityManager->flush($repaymentLog);

            $this->logger->warning(
                'Cannot find pending lenders\'s repayment schedule to repay for project ' . $project->getIdProject() . ' (order: ' . $repaymentSequence . '). The repayment may have been repaid manually.',
                ['method' => __METHOD__]
            );

            return $repaymentNb;
        }

        foreach ($repaymentSchedules as $repaymentSchedule) {
            $this->entityManager->getConnection()->beginTransaction();
            try {
                $this->operationManager->repayment($repaymentSchedule);

                $repaymentSchedule->setCapitalRembourse($repaymentSchedule->getCapital())
                    ->setInteretsRembourses($repaymentSchedule->getInterets())
                    ->setStatus(Echeanciers::STATUS_REPAID)
                    ->setDateEcheanceReel(new \DateTime());
                $repaymentNb++;

                $this->entityManager->flush($repaymentSchedule);

                $this->entityManager->commit();
            } catch (\Exception $exception) {
                $this->entityManager->rollback();

                $this->logger->error(
                    'An error occurs for the repayment # ' . $repaymentSchedule->getIdEcheancier() . ' of project # ' . $project->getIdProject() . ' (order: ' . $repaymentSequence . '). Error : ' . $exception->getMessage(),
                    ['method' => __METHOD__]
                );

                continue;
            }
        }

        $unpaidRepaymentSchedules = $repaymentScheduleRepository->findByProject($project, $repaymentSequence, null, Echeanciers::STATUS_PENDING, EcheanciersEmprunteur::STATUS_PAID, null, 0, 1);

        if (0 === count($unpaidRepaymentSchedules)) {
            $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy(['idProject' => $project, 'ordre' => $repaymentSequence]);
            $this->operationManager->repaymentCommission($paymentSchedule);

            $projectRepayment->setDateRembPreteursReel(new \DateTime())->setStatus(ProjectsRemb::STATUS_REPAID);
            $this->entityManager->flush($projectRepayment);

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

        $repaymentLog->setFin(new \DateTime())->setNbPretRemb($repaymentNb);
        $this->entityManager->flush($repaymentLog);

        return $repaymentNb;
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
        $message->setTo($borrower->getEmail());
        $this->mailer->send($message);
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
        $message->setTo($lender->getEmail());
        $this->mailer->send($message);
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
        $message->setTo($lender->getEmail());
        $this->mailer->send($message);
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
        $message->setTo($lender->getEmail());
        $this->mailer->send($message);
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
        $message->setTo($alertMailIT);
        $this->mailer->send($message);
    }
}
