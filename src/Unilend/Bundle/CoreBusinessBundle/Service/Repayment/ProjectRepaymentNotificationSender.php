<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\RemboursementAnticipeMailAEnvoyer;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class ProjectRepaymentNotificationSender
{
    /** @var EntityManagerInterface */
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
     * @param EntityManagerInterface  $entityManager
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
        EntityManagerInterface $entityManager,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        \NumberFormatter $numberFormatter,
        \NumberFormatter $currencyFormatter,
        RouterInterface $router,
        LoggerInterface $logger,
        Packages $assetsPackages,
        string $frontUrl
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
    public function sendRegularisationRepaymentMailToLender(Echeanciers $repaymentSchedule)
    {
        $lenderWallet   = $repaymentSchedule->getIdLoan()->getWallet();
        $lender         = $lenderWallet->getIdClient();
        $netRepayment   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->getNetAmountByRepaymentScheduleId($repaymentSchedule);

        $keywords = [
            'companyName'     => $repaymentSchedule->getIdLoan()->getProject()->getIdCompany()->getName(),
            'firstName'       => $lender->getPrenom(),
            'repaymentAmount' => $this->currencyFormatter->formatCurrency($netRepayment, 'EUR'),
            'lenderPattern'   => $lenderWallet->getWireTransferPattern()
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->messageProvider->newMessage('preteur-regularisation-remboursement', $keywords);

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
     * @param int      $repaymentSequence
     */
    public function sendIncompleteRepaymentNotification(Projects $project, $repaymentSequence)
    {
        $alertMailIT = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'DebugMailIt'])->getValue();

        $keywords = [
            'project_id'    => $project->getIdProject(),
            'project_title' => $project->getTitle(),
            'order'         => $repaymentSequence
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->messageProvider->newMessage('incomplete-repayment-notification', $keywords);

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
        $project           = $paymentSchedule->getIdProject();
        $company           = $project->getIdCompany();
        $borrower          = $company->getIdClientOwner();
        $lastProjectStatus = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory')->findOneBy(
            ['idProject' => $project->getIdProject()],
            ['added' => 'DESC', 'idProjectStatusHistory' => 'DESC']
        );

        $keywords = [
            'wireTransferOutDate' => strftime('%d %B %G', $lastProjectStatus->getAdded()->getTimestamp()),
            'companyName'         => stripslashes(trim($company->getName())),
            'loanAmount'          => $this->numberFormatter->format($project->getAmount()),
            'paymentAmount'       => $this->numberFormatter->format(round(bcdiv($paymentSchedule->getCapital() + $paymentSchedule->getInterets() + $paymentSchedule->getCommission() + $paymentSchedule->getTva(), 100, 4), 2)),
            'month'               => strftime('%B', $paymentSchedule->getDateEcheanceEmprunteur()->getTimestamp()),
            'invoiceLink'         => $this->frontUrl . '/pdf/facture_ER/' . $borrower->getHash() . '/' . $project->getIdProject() . '/' . $paymentSchedule->getOrdre()
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->messageProvider->newMessage('facture-emprunteur-remboursement', $keywords);
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
        $lenderWallet        = $repaymentSchedule->getIdLoan()->getWallet();
        $netRepayment        = $operationRepository->getNetAmountByRepaymentScheduleId($repaymentSchedule);

        $keywords = [
            'firstName'             => $lenderWallet->getIdClient()->getPrenom(),
            'netRepayment'          => $this->currencyFormatter->formatCurrency($netRepayment, 'EUR'),
            'companyName'           => $repaymentSchedule->getIdLoan()->getProject()->getIdCompany()->getName(),
            'balance'               => $this->currencyFormatter->formatCurrency($lenderWallet->getAvailableBalance(), 'EUR'),
            'lenderPattern'         => $lenderWallet->getWireTransferPattern(),
            'loanDate'              => strftime('%A %d %B %G', $repaymentSchedule->getIdLoan()->getAdded()->getTimestamp()),
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
        $message = $this->messageProvider->newMessage($mailTemplate, $keywords);
        try {
            $message->setTo($lenderWallet->getIdClient()->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning(
                'Could not send email: ' . $mailTemplate . ' - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $lenderWallet->getIdClient()->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * @param Receptions $reception
     */
    public function sendInComingEarlyRepaymentNotification(Receptions $reception)
    {
        $email    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse notification nouveau remboursement anticipe'])->getValue();
        $keywords = [
            '$surl'       => $this->assetsPackages->getUrl(''),
            '$url'        => $this->frontUrl,
            '$id_projet'  => $reception->getIdProject()->getIdProject(),
            '$montant'    => bcdiv($reception->getMontant(), 100, 2),
            '$nom_projet' => $reception->getIdProject()->getTitle()
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('notification-nouveau-remboursement-anticipe', $keywords, false);

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
        $client                 = $project->getIdCompany()->getIdClientOwner();
        $borrowerWithdrawalType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::BORROWER_WITHDRAW]);
        $borrowerWithdrawal     = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:Operation')
            ->findOneBy(['idType' => $borrowerWithdrawalType, 'idProject' => $project], ['added' => 'ASC']);
        $lastRepayment          = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->findOneBy(['idProject' => $project], ['added' => 'DESC']);

        $keywords = [
            'firstName'            => $client->getPrenom(),
            'fundingDate'          => $borrowerWithdrawal->getAdded()->format('d/m/Y'),
            'companyName'          => $company->getName(),
            'projectAmount'        => $this->numberFormatter->format($project->getAmount(), 0),
            'projectDuration'      => $project->getPeriod(),
            'lendersCount'         => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->getLenderNumber($project),
            'repaymentDate'        => $lastRepayment->getAdded()->format('d/m/Y'),
            'borrowerServiceEmail' => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse emprunteur'])->getValue()
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('emprunteur-dernier-remboursement', $keywords);

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

        $keywords = [
            'companyName' => $company->getName(),
            'projectName' => $project->getTitle(),
            'projectId'   => $project->getIdProject()
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('preteur-dernier-remboursement-controle', $keywords);

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
