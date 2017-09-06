<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\RemboursementAnticipeMailAEnvoyer;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class ProjectRepaymentNotificationSender
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var LoggerInterface */
    protected $logger;

    /** @var Packages */
    protected $assetsPackages;

    /** @var string */
    protected $frontUrl;

    /** @var \NumberFormatter */
    protected $numberFormatter;

    /** @var \NumberFormatter */
    protected $currencyFormatter;

    /** @var RouterInterface */
    protected $router;

    /** @var TemplateMessageProvider */
    protected $messageProvider;

    /** @var \Swift_Mailer */
    protected $mailer;

    /**
     * ProjectRepaymentManager constructor.
     *
     * @param EntityManager           $entityManager
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
        $this->entityManager     = $entityManager;
        $this->logger            = $logger;
        $this->assetsPackages    = $assetsPackages;
        $this->frontUrl          = $frontUrl;
        $this->numberFormatter   = $numberFormatter;
        $this->currencyFormatter = $currencyFormatter;
        $this->router            = $router;
        $this->messageProvider   = $messageProvider;
        $this->mailer            = $mailer;
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
     * @param Projects $project
     * @param          $repaymentSequence
     */
    public function sendIncompleteRepaymentNotification(Projects $project, $repaymentSequence)
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
     * @param EcheanciersEmprunteur $paymentSchedule
     */
    public function sendPaymentScheduleInvoiceToBorrower(EcheanciersEmprunteur $paymentSchedule)
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
            'mois'            => strftime('%B', $paymentSchedule->getDateEcheanceEmprunteur()->getTimestamp()),
            'annee'           => date('Y'),
            'lien_fb'         => $facebook,
            'lien_tw'         => $twitter,
            'montantRemb'     => $this->numberFormatter->format(round(bcdiv($paymentSchedule->getCapital() + $paymentSchedule->getInterets() + $paymentSchedule->getCommission() + $paymentSchedule->getTva(),
                100, 4), 2))
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
     * @param Receptions $reception
     */
    public function sendInComingEarlyRepaymentNotification(Receptions $reception)
    {
        $email = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse notification nouveau remboursement anticipe'])->getValue();

        $varMail = [
            '$surl'       => $this->assetsPackages->getUrl(''),
            '$url'        => $this->frontUrl,
            '$id_projet'  => $reception->getIdProject()->getIdProject(),
            '$montant'    => bcdiv($reception->getMontant(), 100, 2),
            '$nom_projet' => $reception->getIdProject()->getTitle()
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
    public function sendClientNotificationEndOfRepayment(Projects $project)
    {
        $company                = $project->getIdCompany();
        $client                 = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($project->getIdCompany()->getIdClientOwner());
        $borrowerWithdrawalType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::BORROWER_WITHDRAW]);
        $borrowerWithdrawal     = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:Operation')
            ->findOneBy(['idType' => $borrowerWithdrawalType, 'idProject' => $project], ['added' => 'ASC']);
        $lastRepayment          = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->findOneBy(['idProject' => $project], ['added' => 'DESC']);

        /** @var \loans $loans */
        $loanRepository     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans');
        $settingsRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings');
        $facebook           = $settingsRepository->findOneBy(['type' => 'Facebook'])->getValue();
        $twitter            = $settingsRepository->findOneBy(['type' => 'Twitter'])->getValue();

        $varMail = [
            'surl'               => $this->assetsPackages->getUrl(''),
            'url'                => $this->frontUrl,
            'prenom'             => $client->getPrenom(),
            'date_financement'   => $borrowerWithdrawal->getAdded()->format('d/m/Y'),
            'date_remboursement' => $lastRepayment->getAdded()->format('d/m/Y'),
            'raison_sociale'     => $company->getName(),
            'montant'            => $this->numberFormatter->format($project->getAmount(), 0),
            'duree'              => $project->getPeriod(),
            'duree_financement'  => $project->getDatePublication()->diff($project->getDateRetrait())->d,
            'nb_preteurs'        => $loanRepository->getLenderNumber($project),
            'lien_fb'            => $facebook,
            'lien_tw'            => $twitter,
        ];

        $message = $this->messageProvider->newMessage('emprunteur-dernier-remboursement', $varMail);
        try {
            $message->setTo($client->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning(
                'Could not send email: emprunteur-dernier-remboursement - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * @param Projects $project
     */
    public function sendInternalNotificationEndOfRepayment(Projects $project)
    {
        $company  = $project->getIdCompany();
        $settings = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse controle interne']);

        $varMail = [
            'surl'           => $this->assetsPackages->getUrl(''),
            'url'            => $this->frontUrl,
            'nom_entreprise' => $company->getName(),
            'nom_projet'     => $project->getTitle(),
            'id_projet'      => $project->getIdProject(),
            'annee'          => date('Y')
        ];

        $message = $this->messageProvider->newMessage('preteur-dernier-remboursement-controle', $varMail);
        try {
            $message->setTo($settings->getValue());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning(
                'Could not send email: preteur-dernier-remboursement-controle - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'email_address' => $settings->getValue(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }

    }

    /**
     * @param Receptions $reception
     */
    public function createEarlyRepaymentEmail(Receptions $reception)
    {
        /** @var \remboursement_anticipe_mail_a_envoyer $earlyRepaymentEmail */
        $earlyRepaymentEmail = new RemboursementAnticipeMailAEnvoyer();
        $earlyRepaymentEmail->setIdReception($reception->getIdReception())
            ->setStatut(RemboursementAnticipeMailAEnvoyer::STATUS_PENDING);
        $this->entityManager->persist($earlyRepaymentEmail);
        $this->entityManager->flush($earlyRepaymentEmail);
    }
}
