<?php

namespace Unilend\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\{Asset\Packages, Routing\RouterInterface, Translation\TranslatorInterface};
use Unilend\Entity\{ClientsGestionTypeNotif, CloseOutNettingPayment, CloseOutNettingRepayment, CompanyStatus, CompanyStatusHistory, Echeanciers, EcheanciersEmprunteur, Loans, Notifications, Operation,
    Projects, ProjectsStatus, Settings, Wallet};
use Unilend\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\SwiftMailer\TemplateMessageProvider;

class ProjectStatusNotificationSender
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var TranslatorInterface */
    private $translator;
    /** @var EntityManagerInterface */
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
     * @param EntityManagerInterface  $entityManager
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
        EntityManagerInterface $entityManager,
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
        $keywords               = [];
        $paymentSchedule        = $this->entityManager->getRepository(EcheanciersEmprunteur::class);
        $closeOutNettingPayment = $this->entityManager->getRepository(CloseOutNettingPayment::class)->findOneBy(['idProject' => $project]);

        if (null === $closeOutNettingPayment) {
            throw new \Exception('Could not send close out netting borrower email for project: ' . $project->getIdProject() . ': No close out netting payment found.');
        }

        if ($closeOutNettingPayment->getIdEmailContent()) {
            $keywords['mailContent'] = $closeOutNettingPayment->getIdEmailContent()->getBorrowerContent();
        } else {
            $keywords['mailContent'] = '';
        }

        $overdueScheduleCount = $paymentSchedule->getOverdueScheduleCount($project);

        if (0 === $overdueScheduleCount) {
            $mailType            = 'emprunteur-projet-recouvrement-sans-retard-paiement';
            $remainingCapitalDue = $closeOutNettingPayment->getCapital();
        } else {
            $mailType               = 'emprunteur-projet-recouvrement';
            $nextPaymentSchedule    = $paymentSchedule->getNextPaymentSchedule($project);
            $remainingCapitalDue    = $paymentSchedule->getRemainingCapitalFrom($project, $nextPaymentSchedule->getOrdre());
            $overDueScheduleAmounts = $paymentSchedule->getTotalOverdueAmounts($project);
            $totalOverdueAmount     = round(bcadd(bcadd($overDueScheduleAmounts['capital'], $overDueScheduleAmounts['interest'], 4), $overDueScheduleAmounts['commission'], 4), 2);

            $overdueScheduleCountAndAmount             = $this->translator->transChoice(
                'borrower-close-out-netting-email_payments-count-and-amount',
                $overdueScheduleCount,
                [
                    '%overdueScheduleCount%' => $this->numberFormatter->format($overdueScheduleCount),
                    '%totalOverdueAmount%'   => $this->currencyFormatter->formatCurrency($totalOverdueAmount, 'EUR')
                ]
            );
            $keywords['overduePaymentsCountAndAmount'] = $overdueScheduleCountAndAmount;
        }

        $keywords['owedCapitalAmount'] = $this->currencyFormatter->formatCurrency($remainingCapitalDue, 'EUR');

        $this->sendBorrowerEmail($project, $mailType, $keywords);
    }

    /**
     * @param Projects $project
     *
     * @throws \Exception
     */
    public function sendProblemStatusEmailToBorrower(Projects $project)
    {
        $paymentRepository  = $this->entityManager->getRepository(EcheanciersEmprunteur::class);
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
        $fundingDate          = $projectStatusHistory->select('id_project = ' . $project->getIdProject() . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . ProjectsStatus::STATUS_REPAYMENT . ')', 'added ASC, id_project_status_history ASC', 0, 1);
        $fundingDate          = strtotime($fundingDate[0]['added']);

        $settingsRepository  = $this->entityManager->getRepository(Settings::class);
        $bic                 = $settingsRepository->findOneBy(['type' => 'Virement - BIC'])->getValue();
        $iban                = $settingsRepository->findOneBy(['type' => 'Virement - IBAN'])->getValue();
        $borrowerPhoneNumber = $settingsRepository->findOneBy(['type' => 'Téléphone emprunteur'])->getValue();
        $borrowerEmail       = $settingsRepository->findOneBy(['type' => 'Adresse emprunteur'])->getValue();

        $keywords = $keywords + [
                'companyName'                => $company->getName(),
                'directorName'               => (empty($clientOwner->getTitle()) ? 'M.' : $clientOwner->getTitle()) . ' ' . $clientOwner->getLastName(),
                'projectAmount'              => $this->numberFormatter->format($project->getAmount()),
                'fundingDate'                => strftime('%B %G', $fundingDate),
                'lendersCount'               => $loans->getNbPreteurs($project->getIdProject()),
                'projectId'                  => $project->getIdProject(),
                'bic'                        => $bic,
                'iban'                       => $iban,
                'borrowerServicePhoneNumber' => $borrowerPhoneNumber,
                'borrowerServiceEmail'       => $borrowerEmail
            ];

        /** @var \Unilend\SwiftMailer\TemplateMessage $message */
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
     *
     * @throws \Exception
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
            case ProjectsStatus::STATUS_LOSS:
                $this->sendProjectLossNotificationToLenders($project, $keywords);
                break;
        }
    }

    /**
     * @param Projects $project
     * @param array    $keywords
     *
     * @throws \Exception
     */
    private function sendProjectLossNotificationToLenders(Projects $project, array $keywords = [])
    {
        $companyStatusHistoryRepository = $this->entityManager->getRepository(CompanyStatusHistory::class);
        $companyStatusRepository        = $this->entityManager->getRepository(CompanyStatus::class);

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
        $keywords               = [];
        $repaymentSchedule      = $this->entityManager->getRepository(Echeanciers::class);
        $closeOutNettingPayment = $this->entityManager->getRepository(CloseOutNettingPayment::class)->findOneBy(['idProject' => $project]);

        if (null === $closeOutNettingPayment) {
            throw new \Exception('Could not send close out netting lenders email for project: ' . $project->getIdProject() . ': No close out netting payment found.');
        }

        if ($closeOutNettingPayment->getIdEmailContent()) {
            $keywords['mailContent'] = $closeOutNettingPayment->getIdEmailContent()->getLendersContent();
        } else {
            $keywords['mailContent'] = '';
        }

        $overdueRepaymentScheduleCount = $repaymentSchedule->getOverdueRepaymentCountByProject($project);

        if (0 === $overdueRepaymentScheduleCount) {
            $mailType = 'preteur-projet-recouvrement-sans-retard-paiement';
        } else {
            $mailType = 'preteur-projet-recouvrement';
            $keywords = $keywords + [
                    'overdueRepaymentCount' => $this->translator->transChoice(
                        'lender-close-out-netting-email_repayments-count',
                        $overdueRepaymentScheduleCount,
                        ['%overdueScheduleRepaymentCount%' => $this->numberFormatter->format($overdueRepaymentScheduleCount)]
                    )
                ];
        }

        $this->sendLenderNotifications($project, Notifications::TYPE_PROJECT_RECOVERY, $mailType, $mailType, $keywords);
    }

    /**
     * @param Projects $project
     *
     * @throws \Exception
     */
    public function sendCollectiveProceedingStatusNotificationsToLenders(Projects $project)
    {
        $companyStatusHistoryRepository = $this->entityManager->getRepository(CompanyStatusHistory::class);
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
    private function sendLenderNotifications(
        Projects $project,
        int $notificationType,
        string $mailTypePerson,
        string $mailTypeLegalEntity,
        array $keywords = [],
        bool $forceNotification = false
    ): void
    {
        $walletRepository                   = $this->entityManager->getRepository(Wallet::class);
        $operationRepository                = $this->entityManager->getRepository(Operation::class);
        $lenderRepaymentRepository          = $this->entityManager->getRepository(Echeanciers::class);
        $closeOutNettingRepaymentRepository = $this->entityManager->getRepository(CloseOutNettingRepayment::class);
        $loansRepository                    = $this->entityManager->getRepository(Loans::class);

        /** @var \notifications $notificationsData */
        $notificationsData = $this->entityManagerSimulator->getRepository('notifications');
        /** @var \clients_gestion_notifications $clientsGestionNotifications */
        $clientsGestionNotifications = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $clientsGestionMailsNotif */
        $clientsGestionMailsNotif = $this->entityManagerSimulator->getRepository('clients_gestion_mails_notif');

        $lenderLoans = $loansRepository->getProjectLoanDetailsForEachLender($project);

        if (is_array($lenderLoans)) {
            $repaymentRepository = null === $project->getCloseOutNettingDate() ? $lenderRepaymentRepository : $closeOutNettingRepaymentRepository;

            foreach ($lenderLoans as $loanDetails) {
                /** @var Wallet $wallet */
                $wallet = $walletRepository->find($loanDetails['idLender']);

                $loansAmount      = round(bcdiv($loanDetails['amount'], 100, 4), 2);
                $allLoans         = explode(',', $loanDetails['loans']);
                $remainingCapital = $repaymentRepository->getRemainingCapitalByLoan($allLoans);
                $netRepayment     = $operationRepository->getNetRepaidAmountByWalletAndProject($wallet, $project);

                $notificationsData->type       = $notificationType;
                $notificationsData->id_lender  = $loanDetails['idLender'];
                $notificationsData->id_project = $project->getIdProject();
                $notificationsData->amount     = bcsub($loansAmount, $netRepayment);
                $notificationsData->id_bid     = 0;
                $notificationsData->create();

                if (
                    $forceNotification
                    || $clientsGestionNotifications->getNotif($wallet->getIdClient()->getIdClient(), ClientsGestionTypeNotif::TYPE_PROJECT_PROBLEM, 'immediatement')
                ) {
                    $clientsGestionMailsNotif->id_client       = $wallet->getIdClient()->getIdClient();
                    $clientsGestionMailsNotif->id_notif        = ClientsGestionTypeNotif::TYPE_PROJECT_PROBLEM;
                    $clientsGestionMailsNotif->id_notification = $notificationsData->id_notification;
                    $clientsGestionMailsNotif->date_notif      = date('Y-m-d H:i:s');
                    $clientsGestionMailsNotif->id_loan         = 0;
                    $clientsGestionMailsNotif->immediatement   = 1;
                    $clientsGestionMailsNotif->create();

                    $lenderKeywords = $keywords + [
                            'firstName'         => $wallet->getIdClient()->getFirstName(),
                            'loansAmount'       => $this->numberFormatter->format($loansAmount),
                            'companyName'       => $project->getIdCompany()->getName(),
                            'repaidAmount'      => $this->currencyFormatter->formatCurrency($netRepayment, 'EUR'),
                            'owedCapitalAmount' => $this->currencyFormatter->formatCurrency($remainingCapital, 'EUR'),
                            'lenderPattern'     => $wallet->getWireTransferPattern(),
                        ];

                    $mailType = $wallet->getIdClient()->isNaturalPerson() ? $mailTypePerson : $mailTypeLegalEntity;

                    /** @var \Unilend\SwiftMailer\TemplateMessage $message */
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
