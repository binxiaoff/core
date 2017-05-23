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

    /** @var  LoggerInterface */
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
     * @param                         $frontUrl
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
     * @param          $repaymentSequence
     *
     * @return int
     * @throws \Exception
     */
    public function repay(Projects $project, $repaymentSequence)
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
            $repaymentLog                = new ProjectsRembLog();
            $repaymentLog->setIdProject($project)
                ->setOrdre($repaymentSequence)
                ->setDebut(new \DateTime());

            $repaymentSchedules = $repaymentScheduleRepository->findByProject($project, $repaymentSequence, null, Echeanciers::STATUS_PENDING, EcheanciersEmprunteur::STATUS_PAID);
            $projectRepayment   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsRemb')->findOneBy(['idProject' => $project, 'ordre' => $repaymentSequence]);
            $repaymentNb        = 0;

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
            } else {
                foreach ($repaymentSchedules as $repaymentSchedule) {
                    $this->operationManager->repayment($repaymentSchedule);

                    $repaymentSchedule->setCapitalRembourse($repaymentSchedule->getCapital())
                        ->setInteretsRembourses($repaymentSchedule->getInterets())
                        ->setStatus(Echeanciers::STATUS_REPAID)
                        ->setDateEcheanceReel(new \DateTime());
                    $repaymentNb++;

                    if (0 === $repaymentNb % 50) {
                        $this->entityManager->flush();
                    }
                }
                $this->entityManager->flush();

                $projectRepayment->setDateRembPreteursReel(new \DateTime())
                    ->setStatus(ProjectsRemb::STATUS_REPAID);
                $this->entityManager->flush($projectRepayment);

                $repaymentLog->setFin(new \DateTime())
                    ->setNbPretRemb($repaymentNb);
                $this->entityManager->persist($repaymentLog);
                $this->entityManager->flush($repaymentLog);

                $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy(['idProject' => $project, 'ordre' => $repaymentSequence]);
                $this->operationManager->repaymentCommission($paymentSchedule);

                /** @var \compteur_factures $invoiceCounter */
                $invoiceCounter = $this->entityManagerSimulator->getRepository('compteur_factures');
                /** @var \factures $invoice */
                $invoice = $this->entityManagerSimulator->getRepository('factures');

                $now                      = new \DateTime();
                $invoice->num_facture     = 'FR-E' . $now->format('Ymd') . str_pad($invoiceCounter->compteurJournalier($project->getIdProject(), $now->format('Y-m-d')), 5, '0', STR_PAD_LEFT);
                $invoice->date            = $now->format('Y-m-d H:i:s');
                $invoice->id_company      = $project->getIdCompany()->getIdCompany();
                $invoice->id_project      = $project->getIdProject();
                $invoice->ordre           = $repaymentSequence;
                $invoice->type_commission = Factures::TYPE_COMMISSION_REPAYMENT;
                $invoice->commission      = $project->getCommissionRateRepayment();
                $invoice->montant_ht      = $paymentSchedule->getCommission();
                $invoice->tva             = $paymentSchedule->getTva();
                $invoice->montant_ttc     = bcadd($paymentSchedule->getCommission(), $paymentSchedule->getTva(), 2);
                $invoice->create();

                $this->sendPaymentScheduleInvoiceToBorrower($paymentSchedule);

                $pendingRepaymentSchedule = $repaymentScheduleRepository->findByProject($project, null, null, Echeanciers::STATUS_PENDING, null, 0, 1);

                if (0 === count($pendingRepaymentSchedule)) {
                    $this->projectManager->addProjectStatus($_SESSION['user']['id_user'], ProjectsStatus::REMBOURSE, $project);
                    $this->emailManager->setLogger($this->logger);
                    $this->emailManager->sendInternalNotificationEndOfRepayment($project);
                    $this->emailManager->sendClientNotificationEndOfRepayment($project);
                }
            }
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->error(
                'The repayment has been rollbacked for the project ' . $project->getIdProject() . ' (order: ' . $repaymentSequence . '). Error : ' . $exception->getMessage(),
                ['method' => __METHOD__]
            );
            throw $exception;
        }

        return $repaymentNb;
    }

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
            'montantRemb'     => $this->numberFormatter->format(bcdiv(bcadd(bcadd($paymentSchedule->getMontant(), $paymentSchedule->getCommission()), $paymentSchedule->getTva()), 100, 2))
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
        $lender         = $repaymentSchedule->getIdLoan()->getIdLender()->getIdClient();
        $lenderWallet   = $repaymentSchedule->getIdLoan()->getIdLender();
        $grossRepayment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->getGrossAmountByRepaymentScheduleId($repaymentSchedule);
        $tax            = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->getTaxAmountByRepaymentScheduleId($repaymentSchedule);
        $netRepayment   = bcsub($grossRepayment, $tax, 2);

        $facebook = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Facebook'])->getValue();
        $twitter  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Twitter'])->getValue();

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
        $lender         = $repaymentSchedule->getIdLoan()->getIdLender()->getIdClient();
        $lenderWallet   = $repaymentSchedule->getIdLoan()->getIdLender();
        $grossRepayment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->getGrossAmountByRepaymentScheduleId($repaymentSchedule);
        $tax            = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->getTaxAmountByRepaymentScheduleId($repaymentSchedule);
        $netRepayment   = bcsub($grossRepayment, $tax, 2);

        $sRecoveryCompany = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Cabinet de recouvrement'])->getValue();
        $facebook         = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Facebook'])->getValue();
        $twitter          = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Twitter'])->getValue();

        $varMail = array(
            'surl'             => $this->assetsPackages->getUrl(''),
            'url'              => $this->frontUrl,
            'prenom_p'         => $lender->getPrenom(),
            'cab_recouvrement' => $sRecoveryCompany,
            'mensualite_p'     => $this->currencyFormatter->formatCurrency($netRepayment, 'EUR'),
            'nom_entreprise'   => $repaymentSchedule->getIdLoan()->getProject()->getIdCompany()->getName(),
            'solde_p'          => $this->currencyFormatter->formatCurrency($lenderWallet->getAvailableBalance(), 'EUR'),
            'link_echeancier'  => $this->frontUrl,
            'motif_virement'   => $lenderWallet->getWireTransferPattern(),
            'lien_fb'          => $facebook,
            'lien_tw'          => $twitter,
        );

        $message = $this->messageProvider->newMessage('preteur-dossier-recouvre', $varMail);
        $message->setTo($lender->getEmail());
        $this->mailer->send($message);
    }

    /**
     * @param Echeanciers $repaymentSchedule
     */
    public function sendRegularisationRepaymentMailToLender(Echeanciers $repaymentSchedule)
    {
        $lender         = $repaymentSchedule->getIdLoan()->getIdLender()->getIdClient();
        $lenderWallet   = $repaymentSchedule->getIdLoan()->getIdLender();
        $grossRepayment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->getGrossAmountByRepaymentScheduleId($repaymentSchedule);
        $tax            = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->getTaxAmountByRepaymentScheduleId($repaymentSchedule);
        $netRepayment   = bcsub($grossRepayment, $tax, 2);
        $facebook       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Facebook'])->getValue();
        $twitter        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Twitter'])->getValue();

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
}
