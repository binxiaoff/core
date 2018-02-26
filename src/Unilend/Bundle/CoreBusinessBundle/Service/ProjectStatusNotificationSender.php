<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    CompanyStatus, Echeanciers, Notifications, Projects, ProjectsStatus, Wallet
};
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class ProjectStatusNotificationSender
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var TranslatorInterface */
    private $translator;
    /** @var EntityManager */
    private $entityManager;
    /** @var \NumberFormatter */
    protected $numberFormatter;
    /** @var \NumberFormatter */
    protected $currencyFormatter;
    /** @var Packages */
    protected $assetsPackages;
    /** @var TemplateMessageProvider */
    protected $messageProvider;
    /** @var \Swift_Mailer */
    protected $mailer;
    /** @var LoggerInterface */
    protected $logger;
    /** @var string */
    protected $frontUrl;
    /** @var RouterInterface */
    protected $router;

    /**
     * @param EntityManagerSimulator  $entityManagerSimulator
     * @param TranslatorInterface     $translator
     * @param EntityManager           $entityManager
     * @param \NumberFormatter        $numberFormatter
     * @param \NumberFormatter        $currencyFormatter
     * @param Packages                $assetsPackage
     * @param TemplateMessageProvider $messageProvider
     * @param \Swift_Mailer           $mailer
     * @param LoggerInterface         $logger
     * @param string                  $frontUrl
     * @param RouterInterface         $router
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        TranslatorInterface $translator,
        EntityManager $entityManager,
        \NumberFormatter $numberFormatter,
        \NumberFormatter $currencyFormatter,
        Packages $assetsPackage,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        LoggerInterface $logger,
        $frontUrl,
        RouterInterface $router
    )
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->translator             = $translator;
        $this->entityManager          = $entityManager;
        $this->numberFormatter        = $numberFormatter;
        $this->currencyFormatter      = $currencyFormatter;
        $this->assetsPackages         = $assetsPackage;
        $this->messageProvider        = $messageProvider;
        $this->mailer                 = $mailer;
        $this->logger                 = $logger;
        $this->frontUrl               = $frontUrl;
        $this->router                 = $router;
    }

    /**
     * @param Projects $project
     *
     * @throws \Exception
     */
    public function sendCloseOutNettingEmailToBorrower(Projects $project)
    {
        $paymentSchedule      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');
        $overdueScheduleCount = $paymentSchedule->getOverdueScheduleCount($project);
        if (0 === $overdueScheduleCount) {
            throw new \Exception('Cannot send email "emprunteur-projet-statut-recouvrement" total overdue amount is empty on project: ' . $project->getIdProject());
        }

        $nextPaymentSchedule    = $paymentSchedule->getNextPaymentSchedule($project);
        $remainingCapitalDue    = $paymentSchedule->getRemainingCapitalFrom($project, $nextPaymentSchedule->getOrdre());
        $overDueScheduleAmounts = $paymentSchedule->getTotalOverdueAmounts($project);
        $totalOverdueAmount     = round(bcadd(bcadd($overDueScheduleAmounts['capital'], $overDueScheduleAmounts['interest'], 4), $overDueScheduleAmounts['commission'], 4), 2);

        $overdueScheduleCountAndAmount = $this->translator->transChoice(
            'borrower-close-out-netting-email_payments-count-and-amount',
            $overdueScheduleCount,
            [
                '%overdueScheduleCount%' => $this->numberFormatter->format($overdueScheduleCount),
                '%totalOverdueAmount%'   => $this->currencyFormatter->formatCurrency($totalOverdueAmount, 'EUR')
            ]
        );

        $keywords = [
            'overduePaymentsCountAndAmount' => $overdueScheduleCountAndAmount,
            'owedCapitalAmount'             => $this->currencyFormatter->formatCurrency($remainingCapitalDue, 'EUR')
        ];

        $this->sendBorrowerEmail($project, 'emprunteur-projet-statut-recouvrement', $keywords);
    }

    /**
     * @param Projects $project
     *
     * @throws \Exception
     */
    public function sendProblemStatusEmailToBorrower(Projects $project)
    {
        $paymentRepository  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');
        $pendingPayments    = $paymentRepository->getTotalOverdueAmounts($project, new \DateTime('yesterday'));
        $totalOverdueAmount = round(bcadd(bcadd($pendingPayments['capital'], $pendingPayments['interest'], 4), $pendingPayments['commission'], 4), 2);
        $keywords           = [
            'latePaymentAmount' => $this->currencyFormatter->formatCurrency($totalOverdueAmount, 'EUR')
        ];

        $this->sendBorrowerEmail($project, 'emprunteur-projet-statut-probleme', $keywords);
    }

    /**
     * @param Projects $project
     * @param string   $mailType
     * @param array    $keywords
     *
     * @throws \Exception
     */
    private function sendBorrowerEmail(Projects $project, $mailType, array $keywords)
    {
        /** @var \loans $loans */
        $loans   = $this->entityManagerSimulator->getRepository('loans');
        $company = $project->getIdCompany();

        $clientOwner = $company->getIdClientOwner();
        if (null === $clientOwner || empty($clientOwner->getIdClient())) {
            throw new \Exception('Client owner not found for company ' . $project->getIdProject());
        }

        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->entityManagerSimulator->getRepository('projects_status_history');
        $fundingDate          = $projectStatusHistory->select('id_project = ' . $project->getIdProject() . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . ProjectsStatus::REMBOURSEMENT . ')', 'added ASC, id_project_status_history ASC', 0, 1);
        $fundingDate          = strtotime($fundingDate[0]['added']);

        $settingsRepository  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings');
        $bic                 = $settingsRepository->findOneBy(['type' => 'Virement - BIC'])->getValue();
        $iban                = $settingsRepository->findOneBy(['type' => 'Virement - IBAN'])->getValue();
        $borrowerPhoneNumber = $settingsRepository->findOneBy(['type' => 'Téléphone emprunteur'])->getValue();
        $borrowerEmail       = $settingsRepository->findOneBy(['type' => 'Adresse emprunteur'])->getValue();

        $keywords = $keywords + [
                'companyName'                => $company->getName(),
                'directorName'               => (empty($clientOwner->getCivilite()) ? 'M.' : $clientOwner->getCivilite()) . ' ' . $clientOwner->getNom(),
                'projectAmount'              => $this->numberFormatter->format($project->getAmount()),
                'fundingDate'                => strftime('%B %G', $fundingDate),
                'lendersCount'               => $loans->getNbPreteurs($project->getIdProject()),
                'projectId'                  => $project->getIdProject(),
                'bic'                        => $bic,
                'iban'                       => $iban,
                'borrowerServicePhoneNumber' => $borrowerPhoneNumber,
                'borrowerServiceEmail'       => $borrowerEmail
            ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->messageProvider->newMessage($mailType, $keywords);

        try {
            $message->setTo($clientOwner->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning(
                'Could not send email: ' . $mailType . ' - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $clientOwner->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * @param Projects $project
     */
    public function sendProblemStatusNotificationsToLenders(Projects $project)
    {
        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->entityManagerSimulator->getRepository('projects_status_history');
        $projectStatusHistory->loadLastProjectHistory($project->getIdProject());

        /** @var \projects_status_history_details $projectStatusHistoryDetails */
        $projectStatusHistoryDetails = $this->entityManagerSimulator->getRepository('projects_status_history_details');
        $projectStatusHistoryDetails->get($projectStatusHistory->id_project_status_history, 'id_project_status_history');

        $keywords = [
            'mailContent' => nl2br($projectStatusHistoryDetails->mail_content)
        ];

        switch ($project->getStatus()) {
            case ProjectsStatus::PROBLEME:
                $this->sendLenderNotifications($project, Notifications::TYPE_PROJECT_PROBLEM, 'preteur-projet-statut-probleme', 'preteur-projet-statut-probleme', $keywords);
                break;
            case ProjectsStatus::LOSS:
                $this->sendProjectLossNotificationToLenders($project, $keywords);
                break;
        }
    }

    /**
     * @param Projects $project
     * @param array    $keywords
     */
    private function sendProjectLossNotificationToLenders(Projects $project, array $keywords = [])
    {
        $companyStatusHistoryRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyStatusHistory');
        $companyStatusRepository        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyStatus');

        $compulsoryLiquidation = $companyStatusHistoryRepository->findOneBy([
            'idCompany' => $project->getIdCompany(),
            'idStatus'  => $companyStatusRepository->findOneBy(['label' => CompanyStatus::STATUS_PRECAUTIONARY_PROCESS])->getId()
        ]);
        if (null !== $compulsoryLiquidation) {
            $keywords['compulsoryLiquidationDate'] = $compulsoryLiquidation->getAdded()->format('d/m/Y');
        }

        $this->sendLenderNotifications($project, Notifications::TYPE_PROJECT_FAILURE, 'preteur-projet-statut-defaut-personne-physique', 'preteur-projet-statut-defaut-personne-morale', $keywords, true);
    }

    /**
     * @param Projects $project
     *
     * @throws \Exception
     */
    public function sendCloseOutNettingNotificationsToLenders(Projects $project)
    {
        $repaymentSchedule             = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        $overdueRepaymentScheduleCount = $repaymentSchedule->getOverdueRepaymentCountByProject($project);

        if (0 === $overdueRepaymentScheduleCount) {
            throw new \Exception('Could not send email "preteur-projet-statut-recouvrement" on project ' . $project->getIdProject() . '. No overdue repayment found');
        }

        $keywords = [
            'overdueRepaymentCount' => $this->translator->transChoice(
                'lender-close-out-netting-email_repayments-count',
                $overdueRepaymentScheduleCount,
                ['%overdueScheduleRepaymentCount%' => $this->numberFormatter->format($overdueRepaymentScheduleCount)]
            )
        ];

        $this->sendLenderNotifications($project, Notifications::TYPE_PROJECT_RECOVERY, 'preteur-projet-statut-recouvrement', 'preteur-projet-statut-recouvrement', $keywords);
    }

    /**
     * @param Projects $project
     *
     * @throws \Exception
     */
    public function sendCollectiveProceedingStatusNotificationsToLenders(Projects $project)
    {
        $companyStatusHistoryRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyStatusHistory');
        $keywords                       = [
            'mailContent' => '',
            'receiver'    => ''
        ];

        switch ($project->getIdCompany()->getIdStatus()->getLabel()) {
            case CompanyStatus::STATUS_PRECAUTIONARY_PROCESS:
                $notificationType = Notifications::TYPE_PROJECT_PRECAUTIONARY_PROCESS;
                $mailType         = 'preteur-projet-statut-procedure-sauvegarde';
                break;
            case CompanyStatus::STATUS_RECEIVERSHIP:
                $notificationType = Notifications::TYPE_PROJECT_RECEIVERSHIP;
                $mailType         = 'preteur-projet-statut-redressement-judiciaire';
                break;
            case CompanyStatus::STATUS_COMPULSORY_LIQUIDATION:
                $notificationType = Notifications::TYPE_PROJECT_COMPULSORY_LIQUIDATION;
                $mailType         = 'preteur-projet-statut-liquidation-judiciaire';
                break;
            default:
                throw new \Exception('Company is not in proceeding status');
        }

        $lastCompanyStatusHistory = $companyStatusHistoryRepository->findOneBy(['idCompany' => $project->getIdCompany()], ['added' => 'DESC', 'id' => 'DESC']);

        if (null !== $lastCompanyStatusHistory && null !== $lastCompanyStatusHistory->getChangedOn()) {
            $keywords['mailContent'] = nl2br($lastCompanyStatusHistory->getMailContent());
            $keywords['receiver']    = nl2br($lastCompanyStatusHistory->getReceiver());
        }

        $this->sendLenderNotifications($project, $notificationType, $mailType, $mailType, $keywords, true);
    }

    /**
     * @param Projects $project
     * @param int      $notificationType
     * @param string   $mailTypePerson
     * @param string   $mailTypeLegalEntity
     * @param array    $keywords
     * @param bool     $forceNotification
     *
     * @throws \Exception
     */
    private function sendLenderNotifications(Projects $project, $notificationType, $mailTypePerson, $mailTypeLegalEntity, array $keywords = [], $forceNotification = false)
    {
        $walletRepository                   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $operationRepository                = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $lenderRepaymentRepository          = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        $closeOutNettingRepaymentRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingRepayment');

        /** @var \notifications $notificationsData */
        $notificationsData = $this->entityManagerSimulator->getRepository('notifications');
        /** @var \clients_gestion_notifications $clientsGestionNotifications */
        $clientsGestionNotifications = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $clientsGestionMailsNotif */
        $clientsGestionMailsNotif = $this->entityManagerSimulator->getRepository('clients_gestion_mails_notif');

        /** @var \loans $loans */
        $loans        = $this->entityManagerSimulator->getRepository('loans');
        $aLenderLoans = $loans->getProjectLoansByLender($project->getIdProject());

        if (is_array($aLenderLoans)) {
            $nextRepayment = $lenderRepaymentRepository->findNextPendingScheduleAfter(new \DateTime('tomorrow'), $project);
            if (null === $nextRepayment) {
                throw new \Exception('There is no pending repayment on the project ' . $project->getIdProject());
            }

            if (null === $project->getCloseOutNettingDate()) {
                $repaymentRepository = $lenderRepaymentRepository;
            } else {
                $repaymentRepository = $closeOutNettingRepaymentRepository;
            }

            foreach ($aLenderLoans as $aLoans) {
                /** @var Wallet $wallet */
                $wallet = $walletRepository->find($aLoans['id_lender']);

                $netRepayment     = 0.0;
                $loansAmount      = round(bcdiv($aLoans['amount'], 100, 4), 2);
                $allLoans         = explode(',', $aLoans['loans']);
                $remainingCapital = $repaymentRepository->getRemainingCapitalByLoan($allLoans);
                $repaidLoans      = $lenderRepaymentRepository->findBy([
                    'idLoan' => $allLoans,
                    'status' => [Echeanciers::STATUS_PARTIALLY_REPAID, Echeanciers::STATUS_REPAID]
                ]);

                foreach ($repaidLoans as $aPayment) {
                    $netRepayment += $operationRepository->getNetAmountByRepaymentScheduleId($aPayment->getIdEcheancier());
                }

                $notificationsData->type       = $notificationType;
                $notificationsData->id_lender  = $aLoans['id_lender'];
                $notificationsData->id_project = $project->getIdProject();
                $notificationsData->amount     = bcsub($loansAmount, $netRepayment);
                $notificationsData->id_bid     = 0;
                $notificationsData->create();

                if (
                    $forceNotification
                    || $clientsGestionNotifications->getNotif($wallet->getIdClient()->getIdClient(), \clients_gestion_type_notif::TYPE_PROJECT_PROBLEM, 'immediatement')
                ) {
                    $clientsGestionMailsNotif->id_client       = $wallet->getIdClient()->getIdClient();
                    $clientsGestionMailsNotif->id_notif        = \clients_gestion_type_notif::TYPE_PROJECT_PROBLEM;
                    $clientsGestionMailsNotif->id_notification = $notificationsData->id_notification;
                    $clientsGestionMailsNotif->date_notif      = date('Y-m-d H:i:s');
                    $clientsGestionMailsNotif->id_loan         = 0;
                    $clientsGestionMailsNotif->immediatement   = 1;
                    $clientsGestionMailsNotif->create();

                    $lenderKeywords = $keywords + [
                            'firstName'         => $wallet->getIdClient()->getPrenom(),
                            'loansAmount'       => $this->numberFormatter->format($loansAmount),
                            'companyName'       => $project->getIdCompany()->getName(),
                            'repaidAmount'      => $this->currencyFormatter->formatCurrency($netRepayment, 'EUR'),
                            'owedCapitalAmount' => $this->currencyFormatter->formatCurrency($remainingCapital, 'EUR'),
                            'lenderPattern'     => $wallet->getWireTransferPattern(),
                        ];

                    $mailType = $wallet->getIdClient()->isNaturalPerson() ? $mailTypePerson : $mailTypeLegalEntity;

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->messageProvider->newMessage($mailType, $lenderKeywords);

                    try {
                        $message->setTo($wallet->getIdClient()->getEmail());
                        $this->mailer->send($message);
                    } catch (\Exception $exception) {
                        $this->logger->warning(
                            'Could not send email: ' . $mailType . ' - Exception: ' . $exception->getMessage(),
                            ['id_mail_template' => $message->getTemplateId(), 'id_client' => $wallet->getIdClient()->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                        );
                    }
                }
            }
        }
    }
}
