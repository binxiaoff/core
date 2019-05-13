<?php

namespace Unilend\Service;

use Doctrine\ORM\{EntityManagerInterface, ORMException};
use Psr\Log\LoggerInterface;
use Symfony\Component\{Asset\Packages, DependencyInjection\ContainerInterface, Routing\RouterInterface,
    Translation\TranslatorInterface};
use Unilend\core\Loader;
use Unilend\Entity\{
    Bids,
    ClientSettingType,
    Clients,
    ClientsGestionTypeNotif,
    ClientsMandats,
    Companies,
    Loans,
    Operation,
    OperationSubType,
    ProjectBeneficialOwnerUniversign,
    ProjectCgv,
    Projects,
    ProjectsPouvoir,
    ProjectsStatus,
    Settings,
    TemporaryLinksLogin,
    UniversignEntityInterface,
    Users,
    Wallet,
    WalletBalanceHistory,
    WalletType
};
use Unilend\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\SwiftMailer\{TemplateMessage, TemplateMessageProvider};

class MailerManager
{
    /** @var Settings */
    private $settingsRepository;
    /** @var LoggerInterface */
    private $oLogger;
    /** @var \ficelle */
    private $oFicelle;
    /** @var string */
    private $sAUrl;
    /** @var string */
    private $sSUrl;
    /** @var string */
    private $sFUrl;
    /**
     * @deprecated inject the service instead
     *
     * @var ContainerInterface
     */
    private $container;
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;
    /** @var string */
    private $locale;
    /** @var TranslatorInterface */
    private $translator;
    /** @var RouterInterface */
    private $router;

    /**
     * @param ContainerInterface      $container
     * @param RouterInterface         $router
     * @param EntityManagerSimulator  $entityManagerSimulator
     * @param EntityManagerInterface  $entityManager
     * @param TemplateMessageProvider $messageProvider
     * @param \Swift_Mailer           $mailer
     * @param string                  $defaultLocale
     * @param Packages                $assetsPackages
     * @param string                  $frontUrl
     * @param string                  $adminUrl
     * @param TranslatorInterface     $translator
     * @param LoggerInterface         $logger
     */
    public function __construct(
        ContainerInterface $container,
        RouterInterface $router,
        EntityManagerSimulator $entityManagerSimulator,
        EntityManagerInterface $entityManager,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        string $defaultLocale,
        Packages $assetsPackages,
        string $frontUrl,
        string $adminUrl,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->container              = $container;
        $this->router                 = $router;
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->messageProvider        = $messageProvider;
        $this->mailer                 = $mailer;
        $this->translator             = $translator;
        $this->settingsRepository     = $this->entityManager->getRepository(Settings::class);

        $this->oFicelle = Loader::loadLib('ficelle');

        $this->locale = $defaultLocale;

        $this->sSUrl   = $assetsPackages->getUrl('');
        $this->sFUrl   = $frontUrl;
        $this->sAUrl   = $adminUrl;
        $this->oLogger = $logger;
    }

    /**
     * @param LoggerInterface $oLogger
     */
    public function setLogger(LoggerInterface $oLogger)
    {
        $this->oLogger = $oLogger;
    }

    /**
     * @param \notifications $notification
     */
    public function sendBidConfirmation(\notifications $notification)
    {
        /** @var Bids $bid */
        $bid = $this->entityManager->getRepository(Bids::class)->findOneBy(['idBid' => $notification->id_bid, 'idAutobid' => null]);

        if (null !== $bid) {
            $keywords = [
                'firstName'     => $bid->getWallet()->getIdClient()->getFirstName(),
                'companyName'   => $bid->getTranche()->getIdCompany()->getName(),
                'projectName'   => $bid->getTranche()->getTitle(),
                'bidAmount'     => $this->oFicelle->formatNumber($bid->getAmount() / 100, 0),
                'bidRate'       => $this->oFicelle->formatNumber($bid->getRate()->getMargin(), 1),
                'bidDate'       => strftime('%d %B %G', $bid->getAdded()->getTimestamp()),
                'bidTime'       => $bid->getAdded()->format('H:i:s'),
                'lenderPattern' => $bid->getWallet()->getWireTransferPattern(),
            ];

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage('confirmation-bid', $keywords);

            try {
                $message->setTo($bid->getWallet()->getIdClient()->getEmail());
                $this->mailer->send($message);
            } catch (\Exception $exception) {
                $this->oLogger->warning(
                    'Could not send email: "confirmation-bid" - Exception: ' . $exception->getMessage(),
                    [
                        'method'           => __METHOD__,
                        'id_mail_template' => $message->getTemplateId(),
                        'id_client'        => $bid->getWallet()->getIdClient()->getIdClient(),
                        'file'             => $exception->getFile(), 'line' => $exception->getLine(),
                    ]
                );
            }
        }
    }

    /**
     * @param Projects $project
     */
    public function sendFundFailedToLender(Projects $project): void
    {
        $bids = $this->entityManager
            ->getRepository(Bids::class)
            ->findBy(['idProject' => $project], ['rate' => 'ASC', 'added' => 'ASC'])
        ;

        foreach ($bids as $bid) {
            $wallet = $bid->getWallet();

            if ($wallet->getIdClient()->isGrantedLogin()) {
                $keywords = [
                    'companyName'   => $bid->getTranche()->getIdCompany()->getName(),
                    'firstName'     => $wallet->getIdClient()->getFirstName(),
                    'bidDate'       => strftime('%d %B %G', $bid->getAdded()->getTimestamp()),
                    'bidAmount'     => $this->oFicelle->formatNumber($bid->getAmount() / 100, 0),
                    'bidRate'       => $this->oFicelle->formatNumber($bid->getRate()->getMargin()),
                    'balance'       => $this->oFicelle->formatNumber($wallet->getAvailableBalance()),
                    'lenderPattern' => $wallet->getWireTransferPattern(),
                ];

                $message = $this->messageProvider->newMessage('preteur-dossier-funding-ko', $keywords);

                try {
                    $message->setTo($wallet->getIdClient()->getEmail());
                    $this->mailer->send($message);
                } catch (\Exception $exception) {
                    $this->oLogger->warning('Could not send email "preteur-dossier-funding-ko". Exception: ' . $exception->getMessage(), [
                        'id_mail_template' => $message->getTemplateId(),
                        'id_client'        => $wallet->getIdClient()->getIdClient(),
                        'class'            => __CLASS__,
                        'function'         => __FUNCTION__,
                        'file'             => $exception->getFile(),
                        'line'             => $exception->getLine(),
                    ]);
                }
            }
        }
    }

    /**
     * @param Projects $project
     */
    public function sendFundedToBorrower(Projects $project): void
    {
        if (null === $project->getIdCompany() || null === $project->getIdCompany()->getIdClientOwner()) {
            $this->oLogger->error('Cannot send funded project email to borrower for project ' . $project->getIdProject() . ': no borrower set for project', [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
            ]);

            return;
        }

        $borrower = $project->getIdCompany()->getIdClientOwner();

        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info('Project funded - sending email to borrower (project ' . $project->getIdProject() . ')', [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
            ]);
        }

        if ($borrower->isValidated()) {
            $projectRepository   = $this->entityManager->getRepository(Projects::class);
            $averageInterestRate = $projectRepository->getAverageInterestRate($project);
            $keywords            = [
                'firstName'         => $borrower->getFirstName(),
                'averageRate'       => $this->oFicelle->formatNumber($averageInterestRate, 1),
                'remainingDuration' => $this->diffFromNowForHumans($project->getDateRetrait()),
            ];

            $message = $this->messageProvider->newMessage('emprunteur-dossier-funde', $keywords);

            try {
                $message->setTo($borrower->getEmail());
                $this->mailer->send($message);
            } catch (\Exception $exception) {
                $this->oLogger->warning('Could not send email "emprunteur-dossier-funde". Exception: ' . $exception->getMessage(), [
                    'id_mail_template' => $message->getTemplateId(),
                    'id_client'        => $borrower->getIdClient(),
                    'class'            => __CLASS__,
                    'function'         => __FUNCTION__,
                    'file'             => $exception->getFile(),
                    'line'             => $exception->getLine(),
                ]);
            }
        }
    }

    /**
     * @param Projects $project
     *
     * @return bool
     */
    public function sendFundedAndFinishedToBorrower(Projects $project)
    {
        /** @var \clients $borrower */
        $borrower = $this->entityManagerSimulator->getRepository('clients');
        $borrower->get($project->getIdCompany()->getIdClientOwner()->getIdClient(), 'id_client');

        /** @var \echeanciers_emprunteur $borrowerPaymentSchedule */
        $borrowerPaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers_emprunteur');
        $borrowerPaymentSchedule->get($project->getIdProject(), 'ordre = 1 AND id_project');
        $monthlyPayment = $borrowerPaymentSchedule->montant + $borrowerPaymentSchedule->commission + $borrowerPaymentSchedule->tva;
        $monthlyPayment = $monthlyPayment / 100;

        $mandate = $this->entityManager
            ->getRepository(ClientsMandats::class)
            ->findOneBy(['idProject' => $project, 'status' => UniversignEntityInterface::STATUS_SIGNED], ['added' => 'DESC'])
        ;
        $proxy = $this->entityManager
            ->getRepository(ProjectsPouvoir::class)
            ->findOneBy(['idProject' => $project, 'status' => UniversignEntityInterface::STATUS_SIGNED], ['added' => 'DESC'])
        ;

        $documents = '';
        if (null === $mandate) {
            $documents .= $this->translator->trans('universign_mandate-description-for-email');
        }

        if (null === $proxy) {
            $documents .= $this->translator->trans('universign_proxy-description-for-email');
        }

        if (false === in_array($project->getIdCompany()->getLegalFormCode(), BeneficialOwnerManager::BENEFICIAL_OWNER_DECLARATION_EXEMPTED_LEGAL_FORM_CODES)) {
            $beneficialOwnerDeclaration = $this->entityManager->getRepository(ProjectBeneficialOwnerUniversign::class)
                ->findOneBy(['idProject' => $project, 'status' => UniversignEntityInterface::STATUS_SIGNED], ['added' => 'DESC'])
            ;
            if (null === $beneficialOwnerDeclaration) {
                $documents .= $this->translator->trans('universign_beneficial-owner-description-for-email');
            }
        }

        $averageInterestRate = $project->getInterestRate();
        if (empty($averageInterestRate)) {
            $projectRepository   = $this->entityManager->getRepository(Projects::class);
            $averageInterestRate = $projectRepository->getAverageInterestRate($project, false);
        }

        $keywords = [
            'firstName'      => $borrower->prenom,
            'companyName'    => $project->getIdCompany()->getName(),
            'projectAmount'  => $this->oFicelle->formatNumber($project->getAmount(), 0),
            'averageRate'    => $this->oFicelle->formatNumber($averageInterestRate, 1),
            'monthlyPayment' => $this->oFicelle->formatNumber($monthlyPayment),
            'signatureLink'  => $this->sFUrl . '/pdf/projet/' . $borrower->hash . '/' . $project->getIdProject(),
            'documentsList'  => $documents,
        ];

        $message = $this->messageProvider->newMessage('emprunteur-dossier-funde-et-termine', $keywords);

        try {
            $message->setTo($borrower->email);

            return $this->mailer->send($message) > 0;
        } catch (\Exception $exception) {
            $this->oLogger->warning('Could not send email: emprunteur-dossier-funde-et-termine - Exception: ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'id_client'        => $borrower->id_client,
                'class'            => __CLASS__,
                'function'         => __FUNCTION__,
                'file'             => $exception->getFile(),
                'line'             => $exception->getLine(),
            ]);

            return false;
        }
    }

    /**
     * @param Projects $project
     *
     * @throws \Exception
     */
    public function sendFundedToStaff(Projects $project): void
    {
        /** @var \bids $bid */
        $bid = $this->entityManagerSimulator->getRepository('bids');

        $projectRepository   = $this->entityManager->getRepository(Projects::class);
        $averageInterestRate = $projectRepository->getAverageInterestRate($project, false);
        $keywords            = [
            '$surl'         => $this->sSUrl,
            '$id_projet'    => $project->getIdProject(),
            '$title_projet' => $project->getTitle(),
            '$nbPeteurs'    => $bid->countLendersOnProject($project->getIdProject()),
            '$tx'           => $this->oFicelle->formatNumber($averageInterestRate, 1),
            '$periode'      => $this->diffFromNowForHumans($project->getDateRetrait()),
        ];

        $message   = $this->messageProvider->newMessage('notification-projet-funde-a-100', $keywords, false);
        $recipient = $this->settingsRepository->findOneBy(['type' => 'Adresse notification projet funde a 100'])->getValue();

        try {
            $message->setTo(explode(';', str_replace(' ', '', $recipient)));
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->oLogger->warning('Could not send email "notification-projet-funde-a-100" - Exception: ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'email address'    => explode(';', str_replace(' ', '', $recipient)),
                'class'            => __CLASS__,
                'function'         => __FUNCTION__,
                'file'             => $exception->getFile(),
                'line'             => $exception->getLine(),
            ]);
        }
    }

    /**
     * @param \notifications $notification
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function sendBidRejected(\notifications $notification): void
    {
        /** @var \projects $project */
        $project       = $this->entityManagerSimulator->getRepository('projects');
        $bidRepository = $this->entityManager->getRepository(Bids::class);
        $bid           = $bidRepository->find($notification->id_bid);

        if ($bid->getWallet()->getIdClient()->isGrantedLogin()) {
            /*
             * Using the projects.data object is a workaround while projects has not been completely migrated on Doctrine Entity
             * and date_fin cannot be NULL
             */
            $project->get($bid->getTranche()->getIdProject());

            $now = new \DateTime();
            /**
             * We use: new \DateTime($project->date_fin) instead of: $bid->getProject()->getDateFin() because
             * it seems like that the $bid->getProject() returns a not up-to-date entity data, while $project->date_fin is updated in another process.
             */
            $endDate = '0000-00-00 00:00:00' === $project->date_fin ? $bid->getTranche()->getDateRetrait() : new \DateTime($project->date_fin);

            if (false === $endDate instanceof \DateTime) {
                $datesUsed = ['endDate' => $endDate, 'date_fin' => $project->date_fin, 'getDateRetrait' => $bid->getTranche()->getDateRetrait()];
                $this->oLogger->warning(
                    'Could not determine the project end date using following values: '
                    . json_encode($datesUsed) . ' No mail will be sent to the client '
                    . $bid->getWallet()->getIdClient()->getIdClient(),
                    ['method' => __METHOD__, 'id_project' => $project->id_project, 'id_notification' => $notification->id_notification]
                );

                return;
            }
            $interval     = $this->formatDateDiff($now, $endDate);
            $bidManager   = $this->container->get('unilend.service.bid_manager');
            $projectRates = $bidManager->getProjectRateRange($project);

            if ($bid->getAutobid()) {
                $keyWords = [
                    'lenderPattern' => $bid->getWallet()->getWireTransferPattern(),
                    'firstName'     => $bid->getWallet()->getIdClient()->getFirstName(),
                    'companyName'   => $bid->getTranche()->getIdCompany()->getName(),
                ];

                if (0 === bccomp($bid->getRate()->getMargin(), $projectRates['rate_min'], 1)) {
                    $mailTemplate = 'preteur-autobid-ko-minimum-rate';
                    $keyWords += [
                        'autolendLink' => $this->sFUrl . '/profile/autolend#parametrage',
                    ];
                } elseif ($endDate <= $now) {
                    $mailTemplate = 'preteur-autobid-ko-apres-fin-de-periode-projet';
                    $keyWords += [
                        'projectLink' => $this->sFUrl . '/projects/detail/' . $bid->getTranche()->getSlug(),
                    ];
                } else {
                    $mailTemplate = 'preteur-autobid-ko';
                    $keyWords += [
                        'bidRemainingTime' => $interval,
                        'projectLink'      => $this->sFUrl . '/projects/detail/' . $bid->getTranche()->getSlug(),
                    ];
                }
            } else {
                $keyWords = [
                    'lenderPattern' => $bid->getWallet()->getWireTransferPattern(),
                    'firstName'     => $bid->getWallet()->getIdClient()->getFirstName(),
                    'companyName'   => $bid->getTranche()->getIdCompany()->getName(),
                    'bidAmount'     => $this->oFicelle->formatNumber($bid->getAmount() / 100, 0),
                    'bidRate'       => $this->oFicelle->formatNumber($bid->getRate()->getMargin(), 1),
                ];

                if ($endDate <= $now) {
                    $mailTemplate = 'preteur-bid-ko-apres-fin-de-periode-projet';
                    $keyWords += [
                        'bidDate'          => $bid->getAdded()->format('%d %B %G'),
                        'bidTime'          => $bid->getAdded()->format('H\hi'),
                        'projectLink'      => $this->sFUrl . '/projects/detail/' . $bid->getTranche()->getSlug(),
                        'projectsListLink' => $this->sFUrl . '/projects-a-financer',
                    ];
                } elseif ($bidRepository->getProjectMaxRate($project->id_project) > $projectRates['rate_min']) {
                    $mailTemplate = 'preteur-bid-ko';
                    $keyWords += [
                        'projectLink' => $this->sFUrl . '/projects/detail/' . $bid->getTranche()->getSlug(),
                    ];
                } else {
                    $mailTemplate = 'preteur-bid-ko-minimum-rate';
                    $keyWords += [
                        'bidDate' => $bid->getAdded()->format('%d %B %G'),
                        'bidTime' => $bid->getAdded()->format('H\hi'),
                    ];
                }
            }

            $message = $this->messageProvider->newMessage($mailTemplate, $keyWords);

            try {
                $message->setTo($bid->getWallet()->getIdClient()->getEmail());
                $this->mailer->send($message);
            } catch (\Exception $exception) {
                $this->oLogger->warning(
                    'Could not send email: ' . $mailTemplate . ' - Exception: ' . $exception->getMessage(),
                    [
                        'id_mail_template' => $message->getTemplateId(),
                        'id_client'        => $bid->getWallet()->getIdClient()->getIdClient(),
                        'class'            => __CLASS__, 'function' => __FUNCTION__,
                    ]
                );
            }
        }
    }

    /**
     * @param Projects $project
     */
    public function sendFundFailedToBorrower(Projects $project): void
    {
        if (null === $project->getIdCompany() || null === $project->getIdCompany()->getIdClientOwner()) {
            $this->oLogger->error('Could not send email funding KO email for project ' . $project->getIdProject() . '. Unknown company or client', [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
            ]);
        }

        $borrower = $project->getIdCompany()->getIdClientOwner();

        if ($borrower->isValidated()) {
            $keywords = [
                'firstName' => $borrower->getFirstName(),
            ];

            $message = $this->messageProvider->newMessage('emprunteur-dossier-funding-ko', $keywords);

            try {
                $message->setTo($borrower->getEmail());
                $this->mailer->send($message);
            } catch (\Exception $exception) {
                $this->oLogger->warning('Could not send email "emprunteur-dossier-funding-ko". Exception: ' . $exception->getMessage(), [
                    'id_mail_template' => $message->getTemplateId(),
                    'id_client'        => $borrower->getIdClient(),
                    'class'            => __CLASS__,
                    'function'         => __FUNCTION__,
                    'file'             => $exception->getFile(),
                    'line'             => $exception->getLine(),
                ]);
            }
        }
    }

    /**
     * @param Projects $project
     */
    public function sendProjectFinishedToStaff(Projects $project): void
    {
        try {
            $totalBidsAmount = $this->entityManager->getRepository(Bids::class)->getProjectTotalAmount($project);
        } catch (ORMException $exception) {
            $this->oLogger->error('Cannot calculate bids total amount for sending finished project email to staff. Exception: ' . $exception->getMessage(), [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
                'file'       => $exception->getFile(),
                'line'       => $exception->getLine(),
            ]);

            return;
        }

        $averageInterestRate = $project->getInterestRate();
        if (empty($averageInterestRate)) {
            $projectRepository   = $this->entityManager->getRepository(Projects::class);
            $averageInterestRate = $projectRepository->getAverageInterestRate($project, false);
        }

        $lendersCount    = $this->entityManager->getRepository(Loans::class)->getLenderNumber($project);
        $totalBidsAmount = min($totalBidsAmount, $project->getAmount());
        $keywords        = [
            '$surl'         => $this->sSUrl,
            '$url'          => $this->sFUrl,
            '$id_projet'    => $project->getIdProject(),
            '$title_projet' => $project->getTitle(),
            '$nbPeteurs'    => $lendersCount,
            '$montant_pret' => $project->getAmount(),
            '$montant'      => $totalBidsAmount,
            '$taux_moyen'   => $this->oFicelle->formatNumber($averageInterestRate, 1),
        ];

        $recipient = $this->settingsRepository->findOneBy(['type' => 'Adresse notification projet fini'])->getValue();
        $message   = $this->messageProvider->newMessage('notification-projet-fini', $keywords, false);

        try {
            $message->setTo(explode(';', str_replace(' ', '', $recipient)));
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->oLogger->warning('Could not send email "notification-projet-fini". Exception: ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'email address'    => explode(';', str_replace(' ', '', $recipient)),
                'class'            => __CLASS__,
                'function'         => __FUNCTION__,
                'file'             => $exception->getFile(),
                'line'             => $exception->getLine(),
            ]);
        }
    }

    /**
     * @param \notifications $notification
     */
    public function sendFirstAutoBidActivation(\notifications $notification): void
    {
        /** @var Wallet $wallet */
        $wallet = $this->entityManager->getRepository(Wallet::class)->find($notification->id_lender);

        if ($wallet->getIdClient()->isGrantedLogin()) {
            $keyWords = [
                'firstName'              => $wallet->getIdClient()->getFirstName(),
                'autolendActivationTime' => $this->getActivationTime($wallet->getIdClient()->getIdClient())->format('G\hi'),
                'lenderPattern'          => $wallet->getWireTransferPattern(),
            ];

            $message = $this->messageProvider->newMessage('preteur-autobid-activation', $keyWords);

            try {
                $message->setTo($wallet->getIdClient()->getEmail());
                $this->mailer->send($message);
            } catch (\Exception $exception) {
                $this->oLogger->warning(
                    'Could not send email: preteur-autobid-activation - Exception: ' . $exception->getMessage(),
                    ['id_mail_template' => $message->getTemplateId(), 'id_client' => $wallet->getIdClient()->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                );
            }
        }
    }

    public function sendProjectOnlineToBorrower(Projects $project)
    {
        $company = $project->getIdCompany();
        if ($company) {
            if (false === empty($company->getPrenomDirigeant()) && false === empty($company->getEmailDirigeant())) {
                $firstName  = $company->getPrenomDirigeant();
                $mailClient = $company->getEmailDirigeant();
            } else {
                $client     = $company->getIdClientOwner();
                $firstName  = $client->getFirstName();
                $mailClient = $client->getEmail();
            }

            $publicationDate = (null === $project->getDatePublication()) ? new \DateTime() : $project->getDatePublication();
            $endDate         = (null === $project->getDateRetrait()) ? new \DateTime() : $project->getDateRetrait();
            $fundingTime     = $publicationDate->diff($endDate);
            $fundingDay      = $fundingTime->d + ($fundingTime->h > 0 ? 1 : 0);

            $keywords = [
                'firstName'                  => $firstName,
                'companyName'                => $company->getName(),
                'fundingDuration'            => $fundingDay . (1 == $fundingDay ? ' jour' : ' jours'),
                'projectAmount'              => $this->oFicelle->formatNumber($project->getAmount(), 0),
                'startingTime'               => $publicationDate->format('H\hi'),
                'projectLink'                => $this->sFUrl . '/projects/detail/' . $project->getSlug(),
                'borrowerServicePhoneNumber' => $this->settingsRepository->findOneBy(['type' => 'Téléphone emprunteur'])->getValue(),
                'borrowerServiceEmail'       => $this->settingsRepository->findOneBy(['type' => 'Adresse emprunteur'])->getValue(),
            ];

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage('annonce-mise-en-ligne-emprunteur', $keywords);

            try {
                $message->setTo($mailClient);
                $this->mailer->send($message);
            } catch (\Exception $exception) {
                $this->oLogger->warning(
                    'Could not send email: annonce-mise-en-ligne-emprunteur - Exception: ' . $exception->getMessage(),
                    ['id_mail_template' => $message->getTemplateId(), 'id_client' => $company->getIdClientOwner()->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                );
            }
        }
    }

    /**
     * @param int    $iClientId
     * @param string $sCurrentIban
     * @param string $sNewIban
     *
     * @return bool
     */
    public function sendIbanUpdateToStaff($iClientId, $sCurrentIban, $sNewIban)
    {
        $aMail = [
            'aurl'       => $this->sAUrl,
            'id_client'  => $iClientId,
            'first_name' => $_SESSION['user']['firstname'],
            'name'       => $_SESSION['user']['name'],
            'user_id'    => $_SESSION['user']['id_user'],
            'old_iban'   => $sCurrentIban,
            'new_iban'   => $sNewIban,
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('uninotification-modification-iban-bo', $aMail);
        $message->setTo('controle_interne@ca-lendingservices.com');
        $this->mailer->send($message);
    }

    /**
     * @param Projects $project
     */
    public function sendBorrowerBill(Projects $project)
    {
        $client = $project->getIdCompany()->getIdClientOwner();
        $today  = new \DateTime('NOW');

        $keywords = [
            'wireTransferOutDate' => strftime('%d %B %G', $today->getTimestamp()),
            'projectAmount'       => $this->oFicelle->formatNumber($project->getAmount(), 0),
            'invoiceLink'         => $this->sFUrl . '/pdf/facture_EF/' . $client->getHash() . '/' . $project->getIdProject() . '/',
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('facture-emprunteur', $keywords);

        try {
            $message->setTo($project->getIdCompany()->getEmailFacture());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->oLogger->warning(
                'Could not send email: facture-emprunteur - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * Send new projects summary email.
     *
     * @param array  $aCustomerId
     * @param string $sFrequency  (quotidienne/hebdomadaire)
     */
    public function sendNewProjectsSummaryEmail(array $aCustomerId, $sFrequency)
    {
        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->debug('New projects notifications start', ['class' => __CLASS__, 'function' => __FUNCTION__]);
            $this->oLogger->debug('Number of customers to process: ' . count($aCustomerId), ['class' => __CLASS__, 'function' => __FUNCTION__]);
        }

        /** @var \clients $oCustomer */
        $oCustomer = $this->entityManagerSimulator->getRepository('clients');
        /** @var \projects $oProject */
        $oProject = $this->entityManagerSimulator->getRepository('projects');
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = $this->entityManagerSimulator->getRepository('clients_gestion_mails_notif');
        /** @var \clients_gestion_notifications $oCustomerNotificationSettings */
        $oCustomerNotificationSettings = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');

        /** @var \clients_gestion_notif_log $oNotificationsLog */
        $oNotificationsLog           = $this->entityManagerSimulator->getRepository('clients_gestion_notif_log');
        $oNotificationsLog->id_notif = ClientsGestionTypeNotif::TYPE_NEW_PROJECT;
        $oNotificationsLog->type     = $sFrequency;
        $oNotificationsLog->debut    = date('Y-m-d H:i:s');
        $oNotificationsLog->fin      = '0000-00-00 00:00:00';
        $oNotificationsLog->create();

        switch ($sFrequency) {
            case 'quotidienne':
                $mailType = 'nouveaux-projets-du-jour';

                break;
            case 'hebdomadaire':
                $mailType = 'nouveaux-projets-de-la-semaine';

                break;
            default:
                trigger_error('Unknown frequency for new projects summary email: ' . $sFrequency, E_USER_WARNING);

                return;
        }

        foreach (array_chunk($aCustomerId, 100) as $aPartialCustomerId) {
            $aCustomerMailNotifications  = [];
            $newProjectMailNotifications = $oCustomerNotificationSettings->getCustomersNotifications($aPartialCustomerId, $sFrequency, ClientsGestionTypeNotif::TYPE_NEW_PROJECT);
            foreach ($newProjectMailNotifications as $aMailNotifications) {
                $aCustomerMailNotifications[$aMailNotifications['id_client']][] = $aMailNotifications;
            }

            foreach ($aCustomerMailNotifications as $iCustomerId => $aMailNotifications) {
                try {
                    $sProjectsListHTML = '';
                    $iProjectsCount    = 0;

                    foreach ($aMailNotifications as $aMailNotification) {
                        $oMailNotification->get($aMailNotification['id_clients_gestion_mails_notif']);
                        $oMailNotification->{$sFrequency}                   = 1;
                        $oMailNotification->{'status_check_' . $sFrequency} = 1;
                        $oMailNotification->update();

                        $oProject->get($aMailNotification['id_project']);

                        if (ProjectsStatus::STATUS_PUBLISHED == $oProject->status) {
                            $sProjectsListHTML .= '
                                <tr>
                                    <td class="td">
                                       <a href="' . $this->sFUrl . '/projects/detail/' . $oProject->slug . '">' . $oProject->title . '</a>
                                    </td>
                                    <td class="td text-right">' . $this->oFicelle->formatNumber($oProject->amount, 0) . ' €</td>
                                    <td class="td text-right">' . $oProject->period . ' mois</td>
                                </tr>';
                            ++$iProjectsCount;
                        }
                    }

                    if ($iProjectsCount >= 1) {
                        $oCustomer->get($iCustomerId);

                        if (1 === $iProjectsCount && 'quotidienne' === $sFrequency) {
                            $sContent = $this->translator->trans('email-synthese_contenu-synthese-nouveau-projet-du-jour-singulier');
                            $sObject  = $this->translator->trans('email-synthese_objet-synthese-nouveau-projet-du-jour-singulier');
                            $sSubject = $this->translator->trans('email-synthese_sujet-nouveau-projet-du-jour-singulier');
                        } elseif (1 < $iProjectsCount && 'quotidienne' === $sFrequency) {
                            $sContent = $this->translator->trans('email-synthese_contenu-synthese-nouveau-projet-du-jour-pluriel');
                            $sObject  = $this->translator->trans('email-synthese_objet-synthese-nouveau-projet-du-jour-pluriel');
                            $sSubject = $this->translator->trans('email-synthese_sujet-nouveau-projet-du-jour-pluriel');
                        } elseif (1 === $iProjectsCount && 'hebdomadaire' === $sFrequency) {
                            $sContent = $this->translator->trans('email-synthese_contenu-synthese-nouveau-projet-hebdomadaire-singulier');
                            $sObject  = $this->translator->trans('email-synthese_objet-synthese-nouveau-projet-hebdomadaire-singulier');
                            $sSubject = $this->translator->trans('email-synthese_sujet-nouveau-projet-hebdomadaire-singulier');
                        } elseif (1 < $iProjectsCount && 'hebdomadaire' === $sFrequency) {
                            $sContent = $this->translator->trans('email-synthese_contenu-synthese-nouveau-projet-hebdomadaire-pluriel');
                            $sObject  = $this->translator->trans('email-synthese_objet-synthese-nouveau-projet-hebdomadaire-pluriel');
                            $sSubject = $this->translator->trans('email-synthese_sujet-nouveau-projet-hebdomadaire-pluriel');
                        } else {
                            trigger_error('Frequency and number of projects not handled: ' . $sFrequency . ' / ' . $iProjectsCount, E_USER_WARNING);

                            continue;
                        }

                        $keywords = [
                            'subject'       => $sSubject,
                            'title'         => $sObject,
                            'firstName'     => $oCustomer->prenom,
                            'content'       => $sContent,
                            'projectList'   => $sProjectsListHTML,
                            'lenderPattern' => $oCustomer->getLenderPattern($oCustomer->id_client),
                        ];

                        /** @var TemplateMessage $message */
                        $message = $this->messageProvider->newMessage($mailType, $keywords);

                        try {
                            $message->setTo($oCustomer->email);
                            $this->mailer->send($message);
                        } catch (\Exception $exception) {
                            $this->oLogger->warning(
                                'Could not send email: ' . $mailType . ' - Exception: ' . $exception->getMessage(),
                                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $iCustomerId, 'class' => __CLASS__, 'function' => __FUNCTION__]
                            );
                        }
                    }
                } catch (\Exception $oException) {
                    if ($this->oLogger instanceof LoggerInterface) {
                        $this->oLogger->error(
                            'Could not send new projects summary email for customer ' . $iCustomerId . ' - Message: ' . $oException->getMessage(),
                            ['class' => __CLASS__, 'function' => __FUNCTION__]
                        );
                    }
                }
            }
        }

        $oNotificationsLog->fin = date('Y-m-d H:i:s');
        $oNotificationsLog->update();
    }

    /**
     * Send accepted bids summary email.
     *
     * @param array  $aCustomerId
     * @param string $sFrequency
     */
    public function sendPlacedBidsSummaryEmail(array $aCustomerId, $sFrequency)
    {
        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->debug('Placed bids notifications start', ['class' => __CLASS__, 'function' => __FUNCTION__]);
            $this->oLogger->debug('Number of customer to process: ' . count($aCustomerId), ['class' => __CLASS__, 'function' => __FUNCTION__]);
        }

        /** @var \bids $oBid */
        $oBid = $this->entityManagerSimulator->getRepository('bids');
        /** @var \clients $oCustomer */
        $oCustomer = $this->entityManagerSimulator->getRepository('clients');
        /** @var \notifications $oNotification */
        $oNotification = $this->entityManagerSimulator->getRepository('notifications');
        /** @var \projects $oProject */
        $oProject = $this->entityManagerSimulator->getRepository('projects');
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = $this->entityManagerSimulator->getRepository('clients_gestion_mails_notif');
        /** @var \clients_gestion_notifications $oCustomerNotificationSettings */
        $oCustomerNotificationSettings = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');

        /** @var \clients_gestion_notif_log $oNotificationsLog */
        $oNotificationsLog           = $this->entityManagerSimulator->getRepository('clients_gestion_notif_log');
        $oNotificationsLog->id_notif = ClientsGestionTypeNotif::TYPE_BID_PLACED;
        $oNotificationsLog->type     = $sFrequency;
        $oNotificationsLog->debut    = date('Y-m-d H:i:s');
        $oNotificationsLog->fin      = '0000-00-00 00:00:00';
        $oNotificationsLog->create();

        switch ($sFrequency) {
            case 'quotidienne':
                $sMail = 'vos-offres-du-jour';

                break;
            default:
                trigger_error('Unknown frequency for placed bids summary email: ' . $sFrequency, E_USER_WARNING);

                return;
        }

        $bidStyle = 'border-radius: 6px; color: #ffffff; font-weight: bold; background: #d9aa34; padding: 3px 6px 3px 6px; text-decoration: none; margin: 3px';

        foreach (array_chunk($aCustomerId, 100) as $aPartialCustomerId) {
            $aCustomerMailNotifications = [];
            foreach ($oCustomerNotificationSettings->getCustomersNotifications($aPartialCustomerId, $sFrequency, ClientsGestionTypeNotif::TYPE_BID_PLACED) as $aMailNotifications) {
                $aCustomerMailNotifications[$aMailNotifications['id_client']][] = $aMailNotifications;
            }

            foreach ($aCustomerMailNotifications as $iCustomerId => $aMailNotifications) {
                try {
                    $oCustomer->get($iCustomerId);

                    $sBidsListHTML    = '';
                    $iSumBidsPlaced   = 0;
                    $iPlacedBidsCount = count($aMailNotifications);

                    foreach ($aMailNotifications as $aMailNotification) {
                        $oMailNotification->get($aMailNotification['id_clients_gestion_mails_notif']);
                        $oMailNotification->{$sFrequency}                   = 1;
                        $oMailNotification->{'status_check_' . $sFrequency} = 1;
                        $oMailNotification->update();

                        $oNotification->get($aMailNotification['id_notification']);
                        $oProject->get($oNotification->id_project);
                        $oBid->get($oNotification->id_bid);

                        $iSumBidsPlaced += $oBid->amount / 100;

                        $sSpanAutobid = empty($oBid->id_autobid) ? '' : '<span style="' . $bidStyle . '">A</span>';

                        $sBidsListHTML .= '
                            <tr>
                                <td class="td">' . $sSpanAutobid . '</td>
                                <td class="td"><a href="' . $this->sFUrl . '/projects/detail/' . $oProject->slug . '" style="color: #2bc9af; font-weight: normal; text-decoration: none;">' . $oProject->title . '</a></td>
                                <td class="td text-right" style="text-align: right;">' . $this->oFicelle->formatNumber($oBid->amount / 100, 0) . '&nbsp;€</td>
                                <td class="td text-right" style="text-align: right;">' . $this->oFicelle->formatNumber($oBid->rate, 1) . '&nbsp;%</td>
                            </tr>';
                    }

                    $sBidsListHTML .= '
                        <tr>
                            <td class="tf" style="padding: 3px; font-size: 14px; border-top: 1px solid #E3E4E5;"></td>
                            <td class="tf" style="padding: 3px; font-size: 14px; border-top: 1px solid #E3E4E5;">Total</td>
                            <td class="tf text-right" style="padding: 3px; font-size: 14px; border-top: 1px solid #E3E4E5; text-align: right;">' . $this->oFicelle->formatNumber($iSumBidsPlaced, 0) . '&nbsp;€</td>
                            <td class="tf" style="padding: 3px; font-size: 14px; border-top: 1px solid #E3E4E5;"></td>
                        </tr>';

                    if (1 === $iPlacedBidsCount && 'quotidienne' === $sFrequency) {
                        $sContent = $this->translator->trans('email-synthese_contenu-synthese-offre-placee-quotidienne-singulier');
                        $sObject  = $this->translator->trans('email-synthese_objet-synthese-offre-placee-quotidienne-singulier');
                        $sSubject = $this->translator->trans('email-synthese_sujet-synthese-quotidienne-offre-placee-singulier');
                    } elseif (1 < $iPlacedBidsCount && 'quotidienne' === $sFrequency) {
                        $sContent = $this->translator->trans('email-synthese_contenu-synthese-offre-placee-quotidienne-pluriel');
                        $sObject  = $this->translator->trans('email-synthese_objet-synthese-offre-placee-quotidienne-pluriel');
                        $sSubject = $this->translator->trans('email-synthese_sujet-synthese-quotidienne-offre-placee-pluriel');
                    } else {
                        trigger_error('Frequency and number of placed bids not handled: ' . $sFrequency . ' / ' . $iPlacedBidsCount, E_USER_WARNING);

                        continue;
                    }

                    $keywords = [
                        'subject'       => $sSubject,
                        'headerTitle'   => $sObject,
                        'firstName'     => $oCustomer->prenom,
                        'content'       => $sContent,
                        'bidsList'      => $sBidsListHTML,
                        'lenderPattern' => $oCustomer->getLenderPattern($oCustomer->id_client),
                    ];

                    /** @var TemplateMessage $message */
                    $message = $this->messageProvider->newMessage($sMail, $keywords);

                    try {
                        $message->setTo($oCustomer->email);
                        $this->mailer->send($message);
                    } catch (\Exception $exception) {
                        $this->oLogger->warning(
                            'Could not send email: ' . $sMail . ' - Exception: ' . $exception->getMessage(),
                            ['id_mail_template' => $message->getTemplateId(), 'id_client' => $oCustomer->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
                        );
                    }
                } catch (\Exception $oException) {
                    if ($this->oLogger instanceof LoggerInterface) {
                        $this->oLogger->error(
                            'Could not send placed bids summary email for customer ' . $iCustomerId . ' - Message: ' . $oException->getMessage(),
                            ['class' => __CLASS__, 'function' => __FUNCTION__]
                        );
                    }
                }
            }
        }

        $oNotificationsLog->fin = date('Y-m-d H:i:s');
        $oNotificationsLog->update();
    }

    /**
     * Send rejected bids summary email.
     *
     * @param array  $aCustomerId
     * @param string $sFrequency
     */
    public function sendRejectedBidsSummaryEmail(array $aCustomerId, $sFrequency)
    {
        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->debug('Rejected bids notifications start', ['class' => __CLASS__, 'function' => __FUNCTION__]);
            $this->oLogger->debug('Number of customer to process: ' . count($aCustomerId), ['class' => __CLASS__, 'function' => __FUNCTION__]);
        }

        /** @var \bids $oBid */
        $oBid = $this->entityManagerSimulator->getRepository('bids');
        /** @var \clients $oCustomer */
        $oCustomer = $this->entityManagerSimulator->getRepository('clients');
        /** @var \notifications $oNotification */
        $oNotification = $this->entityManagerSimulator->getRepository('notifications');
        /** @var \projects $oProject */
        $oProject = $this->entityManagerSimulator->getRepository('projects');
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = $this->entityManagerSimulator->getRepository('clients_gestion_mails_notif');
        /** @var \clients_gestion_notifications $oCustomerNotificationSettings */
        $oCustomerNotificationSettings = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_notif_log $oNotificationsLog */
        $oNotificationsLog           = $this->entityManagerSimulator->getRepository('clients_gestion_notif_log');
        $oNotificationsLog->id_notif = ClientsGestionTypeNotif::TYPE_BID_REJECTED;
        $oNotificationsLog->type     = $sFrequency;
        $oNotificationsLog->debut    = date('Y-m-d H:i:s');
        $oNotificationsLog->fin      = '0000-00-00 00:00:00';
        $oNotificationsLog->create();

        switch ($sFrequency) {
            case 'quotidienne':
                $sMail = 'synthese-quotidienne-offres-non-retenues';

                break;
            default:
                trigger_error('Unknown frequency for rejected bids summary email: ' . $sFrequency, E_USER_WARNING);

                return;
        }

        $bidStyle = 'border-radius: 6px; color: #ffffff; font-weight: bold; background: #d9aa34; padding: 3px 6px 3px 6px; text-decoration: none; margin: 3px';

        foreach (array_chunk($aCustomerId, 100) as $aPartialCustomerId) {
            $aCustomerMailNotifications = [];
            $bidRejectedNotifications   = $oCustomerNotificationSettings->getCustomersNotifications($aPartialCustomerId, $sFrequency, ClientsGestionTypeNotif::TYPE_BID_REJECTED);
            foreach ($bidRejectedNotifications as $aMailNotifications) {
                $aCustomerMailNotifications[$aMailNotifications['id_client']][] = $aMailNotifications;
            }

            foreach ($aCustomerMailNotifications as $iCustomerId => $aMailNotifications) {
                try {
                    $oCustomer->get($iCustomerId);

                    $sBidsListHTML      = '';
                    $iSumRejectedBids   = 0;
                    $iRejectedBidsCount = count($aMailNotifications);

                    foreach ($aMailNotifications as $aMailNotification) {
                        $oMailNotification->get($aMailNotification['id_clients_gestion_mails_notif']);
                        $oMailNotification->{$sFrequency}                   = 1;
                        $oMailNotification->{'status_check_' . $sFrequency} = 1;
                        $oMailNotification->update();

                        $oNotification->get($aMailNotification['id_notification']);
                        $oProject->get($oNotification->id_project);
                        $oBid->get($oNotification->id_bid);

                        $iSumRejectedBids += $oNotification->amount / 100;

                        $sSpanAutobid = empty($oBid->id_autobid) ? '' : '<span style="' . $bidStyle . '">A</span>';

                        $sBidsListHTML .= '
                            <tr>
                                <td class="td">' . $sSpanAutobid . '</td>
                                <td class="td"><a href="' . $this->sFUrl . '/projects/detail/' . $oProject->slug . '" style="color: #2bc9af; font-weight: normal; text-decoration: none;">' . $oProject->title . '</a></td>
                                <td class="td text-right" style="text-align: right;">' . $this->oFicelle->formatNumber($oNotification->amount / 100, 0) . '&nbsp;€</td>
                                <td class="td text-right" style="text-align: right;">' . $this->oFicelle->formatNumber($oBid->rate, 1) . '&nbsp;%</td>
                            </tr>';
                    }

                    $sBidsListHTML .= '
                        <tr>
                            <td class="tf" style="padding: 3px; font-size: 14px; border-top: 1px solid #E3E4E5;"></td>
                            <td class="tf" style="padding: 3px; font-size: 14px; border-top: 1px solid #E3E4E5;">Total de vos offres</td>
                            <td class="tf text-right" style="padding: 3px; font-size: 14px; border-top: 1px solid #E3E4E5; text-align: right;">' . $this->oFicelle->formatNumber($iSumRejectedBids, 0) . '&nbsp;€</td>
                            <td class="tf" style="padding: 3px; font-size: 14px; border-top: 1px solid #E3E4E5;"></td>
                        </tr>';

                    if (1 === $iRejectedBidsCount && 'quotidienne' === $sFrequency) {
                        $sContent = $this->translator->trans('email-synthese_contenu-synthese-offres-refusees-quotidienne-singulier');
                        $sObject  = $this->translator->trans('email-synthese_objet-synthese-offres-refusees-quotidienne-singulier');
                        $sSubject = $this->translator->trans('email-synthese_sujet-synthese-offres-refusees-quotidienne-singulier');
                    } elseif (1 < $iRejectedBidsCount && 'quotidienne' === $sFrequency) {
                        $sContent = $this->translator->trans('email-synthese_contenu-synthese-offres-refusees-quotidienne-pluriel');
                        $sObject  = $this->translator->trans('email-synthese_objet-synthese-offres-refusees-quotidienne-pluriel');
                        $sSubject = $this->translator->trans('email-synthese_sujet-synthese-offres-refusees-quotidienne-pluriel');
                    } else {
                        trigger_error('Frequency and number of rejected bids not handled: ' . $sFrequency . ' / ' . $iRejectedBidsCount, E_USER_WARNING);

                        continue;
                    }

                    $keywords = [
                        'subject'       => $sSubject,
                        'headerTitle'   => $sObject,
                        'firstName'     => $oCustomer->prenom,
                        'content'       => $sContent,
                        'bidsList'      => $sBidsListHTML,
                        'lenderPattern' => $oCustomer->getLenderPattern($oCustomer->id_client),
                    ];

                    /** @var TemplateMessage $message */
                    $message = $this->messageProvider->newMessage($sMail, $keywords);

                    try {
                        $message->setTo($oCustomer->email);
                        $this->mailer->send($message);
                    } catch (\Exception $exception) {
                        $this->oLogger->warning(
                            'Could not send email: ' . $sMail . ' - Exception: ' . $exception->getMessage(),
                            ['id_mail_template' => $message->getTemplateId(), 'id_client' => $oCustomer->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
                        );
                    }
                } catch (\Exception $oException) {
                    if ($this->oLogger instanceof LoggerInterface) {
                        $this->oLogger->error(
                            'Could not send rejected bids summary email for customer ' . $iCustomerId . ' - Message: ' . $oException->getMessage(),
                            ['class' => __CLASS__, 'function' => __FUNCTION__]
                        );
                    }
                }
            }
        }

        $oNotificationsLog->fin = date('Y-m-d H:i:s');
        $oNotificationsLog->update();
    }

    /**
     * Send accepted loans summary email.
     *
     * @param array  $aCustomerId
     * @param string $sFrequency
     */
    public function sendAcceptedLoansSummaryEmail(array $aCustomerId, $sFrequency)
    {
        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->debug('Accepted loans notifications start', ['class' => __CLASS__, 'function' => __FUNCTION__]);
            $this->oLogger->debug('Number of customer to process: ' . count($aCustomerId), ['class' => __CLASS__, 'function' => __FUNCTION__]);
        }

        /** @var \loans $oLoan */
        $oLoan = $this->entityManagerSimulator->getRepository('loans');
        /** @var \notifications $oNotification */
        $oNotification = $this->entityManagerSimulator->getRepository('notifications');
        /** @var \projects $oProject */
        $oProject = $this->entityManagerSimulator->getRepository('projects');
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = $this->entityManagerSimulator->getRepository('clients_gestion_mails_notif');
        /** @var \clients_gestion_notifications $oCustomerNotificationSettings */
        $oCustomerNotificationSettings = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');

        /** @var \underlying_contract $contract */
        $contract      = $this->entityManagerSimulator->getRepository('underlying_contract');
        $contracts     = $contract->select();
        $contractLabel = [];
        foreach ($contracts as $contractType) {
            $contractLabel[$contractType['id_contract']] = $this->translator->trans('contract-type-label_' . $contractType['label']);
        }

        /** @var \clients_gestion_notif_log $oNotificationsLog */
        $oNotificationsLog           = $this->entityManagerSimulator->getRepository('clients_gestion_notif_log');
        $oNotificationsLog->id_notif = ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED;
        $oNotificationsLog->type     = $sFrequency;
        $oNotificationsLog->debut    = date('Y-m-d H:i:s');
        $oNotificationsLog->fin      = '0000-00-00 00:00:00';
        $oNotificationsLog->create();

        switch ($sFrequency) {
            case 'quotidienne':
                $sMail = 'synthese-quotidienne-offres-acceptees';

                break;
            case 'hebdomadaire':
                $sMail = 'synthese-hebdomadaire-offres-acceptees';

                break;
            case 'mensuelle':
                $sMail = 'synthese-mensuelle-offres-acceptees';

                break;
            default:
                trigger_error('Unknown frequency for accepted loans summary email: ' . $sFrequency, E_USER_WARNING);

                return;
        }

        foreach (array_chunk($aCustomerId, 100) as $aPartialCustomerId) {
            $aCustomerMailNotifications = [];
            $loanAcceptedNotifications  = $oCustomerNotificationSettings->getCustomersNotifications($aPartialCustomerId, $sFrequency, ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED);
            foreach ($loanAcceptedNotifications as $aMailNotifications) {
                $aCustomerMailNotifications[$aMailNotifications['id_client']][] = $aMailNotifications;
            }

            foreach ($aCustomerMailNotifications as $iCustomerId => $aMailNotifications) {
                try {
                    $wallet              = $this->entityManager->getRepository(Wallet::class)->getWalletByType($iCustomerId, WalletType::LENDER);
                    $sLoansListHTML      = '';
                    $iSumAcceptedLoans   = 0;
                    $iAcceptedLoansCount = count($aMailNotifications);

                    foreach ($aMailNotifications as $aMailNotification) {
                        $oMailNotification->get($aMailNotification['id_clients_gestion_mails_notif']);
                        $oMailNotification->{$sFrequency}                   = 1;
                        $oMailNotification->{'status_check_' . $sFrequency} = 1;
                        $oMailNotification->update();

                        $oNotification->get($aMailNotification['id_notification']);
                        $oProject->get($oNotification->id_project);
                        $oLoan->get($aMailNotification['id_loan']);

                        $iSumAcceptedLoans += $oLoan->amount / 100;
                        $sContractType = '';
                        if (isset($contractLabel[$oLoan->id_type_contract])) {
                            $sContractType = $contractLabel[$oLoan->id_type_contract];
                        } else {
                            trigger_error('Unknown contract type: ' . $oLoan->id_type_contract, E_USER_WARNING);
                        }

                        $sLoansListHTML .= '
                            <tr>
                                <td class="td"><a href="' . $this->sFUrl . '/projects/detail/' . $oProject->slug . '">' . $oProject->title . '</a></td>
                                <td class="td text-right">' . $this->oFicelle->formatNumber($oLoan->amount / 100, 0) . '&nbsp;€</td>
                                <td class="td text-right">' . $this->oFicelle->formatNumber($oLoan->rate, 1) . '&nbsp;%</td>
                                <td class="td text-right">' . $sContractType . '</td>
                            </tr>';
                    }

                    $sLoansListHTML .= '
                        <tr>
                            <td class="tf">Total de vos offres</td>
                            <td class="tf text-right">' . $this->oFicelle->formatNumber($iSumAcceptedLoans, 0) . '&nbsp;€</td>
                            <td class="tf">&nbsp;</td>
                            <td class="tf">&nbsp;</td>
                        </tr>';

                    if (1 === $iAcceptedLoansCount && 'quotidienne' === $sFrequency) {
                        $sContent = $this->translator->trans('email-synthese_contenu-synthese-quotidienne-offres-acceptees-singulier');
                        $sObject  = $this->translator->trans('email-synthese_objet-synthese-quotidienne-offres-acceptees-singulier');
                        $sSubject = $this->translator->trans('email-synthese_sujet-synthese-quotidienne-offres-acceptees-singulier');
                    } elseif (1 < $iAcceptedLoansCount && 'quotidienne' === $sFrequency) {
                        $sContent = $this->translator->trans('email-synthese_contenu-synthese-quotidienne-offres-acceptees-pluriel');
                        $sObject  = $this->translator->trans('email-synthese_objet-synthese-quotidienne-offres-acceptees-pluriel');
                        $sSubject = $this->translator->trans('email-synthese_sujet-synthese-quotidienne-offres-acceptees-pluriel');
                    } elseif (1 === $iAcceptedLoansCount && 'hebdomadaire' === $sFrequency) {
                        $sContent = $this->translator->trans('email-synthese_contenu-synthese-hebdomadaire-offres-acceptees-singulier');
                        $sObject  = $this->translator->trans('email-synthese_objet-synthese-hebdomadaire-offres-acceptees-singulier');
                        $sSubject = $this->translator->trans('email-synthese_sujet-synthese-hebdomadaire-offres-acceptees-singulier');
                    } elseif (1 < $iAcceptedLoansCount && 'hebdomadaire' === $sFrequency) {
                        $sContent = $this->translator->trans('email-synthese_contenu-synthese-hebdomadaire-offres-acceptees-pluriel');
                        $sObject  = $this->translator->trans('email-synthese_objet-synthese-hebdomadaire-offres-acceptees-pluriel');
                        $sSubject = $this->translator->trans('email-synthese_sujet-synthese-hebdomadaire-offres-acceptees-pluriel');
                    } elseif (1 === $iAcceptedLoansCount && 'mensuelle' === $sFrequency) {
                        $sContent = $this->translator->trans('email-synthese_contenu-synthese-mensuelle-offres-acceptees-singulier');
                        $sObject  = $this->translator->trans('email-synthese_objet-synthese-mensuelle-offres-acceptees-singulier');
                        $sSubject = $this->translator->trans('email-synthese_sujet-synthese-mensuelle-offres-acceptees-singulier');
                    } elseif (1 < $iAcceptedLoansCount && 'mensuelle' === $sFrequency) {
                        $sContent = $this->translator->trans('email-synthese_contenu-synthese-mensuelle-offres-acceptees-pluriel');
                        $sObject  = $this->translator->trans('email-synthese_objet-synthese-mensuelle-offres-acceptees-pluriel');
                        $sSubject = $this->translator->trans('email-synthese_sujet-synthese-mensuelle-offres-acceptees-pluriel');
                    } else {
                        trigger_error('Frequency and number of accepted loans not handled: ' . $sFrequency . ' / ' . $iAcceptedLoansCount, E_USER_WARNING);

                        continue;
                    }

                    $explication = 'Pour en savoir plus sur les règles de regroupement des offres de prêt, vous pouvez consulter <a href="'
                        . $this->sSUrl
                        . '/document-de-pret">cette page</a>. ';

                    $keyWords = [
                        'firstName'               => $wallet->getIdClient()->getFirstName(),
                        'acceptedLoans'           => $sLoansListHTML,
                        'content'                 => $sContent,
                        'loanGroupingExplication' => $wallet->getIdClient()->isNaturalPerson() ? $explication : '',
                        'lenderPattern'           => $wallet->getWireTransferPattern(),
                        'subject'                 => $sSubject,
                        'title'                   => $sObject,
                    ];

                    /** @var TemplateMessage $message */
                    $message = $this->messageProvider->newMessage($sMail, $keyWords);

                    try {
                        $message->setTo($wallet->getIdClient()->getEmail());
                        $this->mailer->send($message);
                    } catch (\Exception $exception) {
                        $this->oLogger->warning(
                            'Could not send email: ' . $sMail . ' - Exception: ' . $exception->getMessage(),
                            [
                                'id_mail_template' => $message->getTemplateId(),
                                'id_client'        => $wallet->getIdClient()->getIdClient(),
                                'class'            => __CLASS__, 'function' => __FUNCTION__,
                            ]
                        );
                    }
                } catch (\Exception $oException) {
                    if ($this->oLogger instanceof LoggerInterface) {
                        $this->oLogger->error(
                            'Could not send accepted loan summary email for customer ' . $iCustomerId . ' - Message: ' . $oException->getMessage(),
                            ['class' => __CLASS__, 'function' => __FUNCTION__]
                        );
                    }
                }
            }
        }

        $oNotificationsLog->fin = date('Y-m-d H:i:s');
        $oNotificationsLog->update();
    }

    /**
     * Send repayment summary email.
     *
     * @param array  $aCustomerId
     * @param string $sFrequency
     */
    public function sendRepaymentsSummaryEmail(array $aCustomerId, $sFrequency)
    {
        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->debug('Repayments notifications start', ['class' => __CLASS__, 'function' => __FUNCTION__]);
            $this->oLogger->debug('Number of customer to process: ' . count($aCustomerId), ['class' => __CLASS__, 'function' => __FUNCTION__]);
        }

        /** @var \echeanciers $oLenderRepayment */
        $oLenderRepayment = $this->entityManagerSimulator->getRepository('echeanciers');
        /** @var \notifications $oNotification */
        $oNotification = $this->entityManagerSimulator->getRepository('notifications');
        /** @var \projects $oProject */
        $oProject = $this->entityManagerSimulator->getRepository('projects');
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = $this->entityManagerSimulator->getRepository('clients_gestion_mails_notif');
        /** @var \clients_gestion_notifications $oCustomerNotificationSettings */
        $oCustomerNotificationSettings = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');

        /** @var \clients_gestion_notif_log $oNotificationsLog */
        $oNotificationsLog           = $this->entityManagerSimulator->getRepository('clients_gestion_notif_log');
        $oNotificationsLog->id_notif = ClientsGestionTypeNotif::TYPE_REPAYMENT;
        $oNotificationsLog->type     = $sFrequency;
        $oNotificationsLog->debut    = date('Y-m-d H:i:s');
        $oNotificationsLog->fin      = '0000-00-00 00:00:00';
        $oNotificationsLog->create();

        switch ($sFrequency) {
            case 'quotidienne':
                $sMail = 'synthese-quotidienne-remboursements';

                break;
            case 'hebdomadaire':
                $sMail = 'synthese-hebdomadaire-remboursements';

                break;
            case 'mensuelle':
                $sMail = 'synthese-mensuelle-remboursements';

                break;
            default:
                trigger_error('Unknown frequency for repayment summary email: ' . $sFrequency, E_USER_WARNING);

                return;
        }

        foreach (array_chunk($aCustomerId, 100) as $aPartialCustomerId) {
            $aCustomerMailNotifications = [];
            foreach ($oCustomerNotificationSettings->getCustomersNotifications($aPartialCustomerId, $sFrequency, ClientsGestionTypeNotif::TYPE_REPAYMENT) as $aMailNotifications) {
                $aCustomerMailNotifications[$aMailNotifications['id_client']][] = $aMailNotifications;
            }

            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->debug('Customer IDs in mail notifications: ' . json_encode(array_keys($aCustomerMailNotifications)), [
                    'class'    => __CLASS__,
                    'function' => __FUNCTION__,
                ]);
            }

            foreach ($aCustomerMailNotifications as $iCustomerId => $aMailNotifications) {
                try {
                    $wallet = $this->entityManager->getRepository(Wallet::class)->getWalletByType($iCustomerId, WalletType::LENDER);

                    $earlyRepaymentContent      = '';
                    $sRepaymentsListHTML        = '';
                    $fTotalInterestsTaxFree     = 0;
                    $fTotalInterestsTaxIncluded = 0;
                    $fTotalAmount               = 0;
                    $fTotalCapital              = 0;
                    $iRepaymentsCount           = count($aMailNotifications);

                    foreach ($aMailNotifications as $aMailNotification) {
                        $oMailNotification->get($aMailNotification['id_clients_gestion_mails_notif']);
                        $oMailNotification->{$sFrequency}                   = 1;
                        $oMailNotification->{'status_check_' . $sFrequency} = 1;
                        $oMailNotification->update();

                        $oNotification->get($aMailNotification['id_notification']);
                        $oProject->get($oNotification->id_project);

                        $isEarlyRepayment    = false;
                        $amount              = 0;
                        $loanId              = null;
                        $repaymentScheduleId = null;

                        $walletBalanceHistory = $this->entityManager->getRepository(WalletBalanceHistory::class)->find($aMailNotification['id_wallet_balance_history']);
                        /** @var Operation $operation */
                        $operation = $walletBalanceHistory->getIdOperation();

                        if ($operation) {
                            if ($operation->getSubType() && OperationSubType::CAPITAL_REPAYMENT_EARLY === $operation->getSubType()->getLabel()) {
                                $isEarlyRepayment = true;
                            }
                            $amount              = $operation->getAmount();
                            $loanId              = $operation->getLoan()->getIdLoan();
                            $repaymentScheduleId = null !== $operation->getRepaymentSchedule() ? $operation->getRepaymentSchedule()->getIdEcheancier() : null;
                        }

                        if ($isEarlyRepayment) {
                            /** @var \companies $oCompanies */
                            $oCompanies = $this->entityManagerSimulator->getRepository('companies');
                            $oCompanies->get($oProject->id_company);

                            /** @var \loans $oLoan */
                            $oLoan = $this->entityManagerSimulator->getRepository('loans');

                            $fRepaymentCapital              = $amount;
                            $fRepaymentAmount               = $amount;
                            $fRepaymentInterestsTaxIncluded = 0;
                            $fRepaymentTax                  = 0;

                            $earlyRepaymentText = '
                                Le remboursement de <span class="text-primary">'
                                . $this->oFicelle->formatNumber($amount)
                                . '&nbsp;€</span> correspond au remboursement total du capital restant dû de votre prêt à <span class="text-primary">'
                                . htmlentities($oCompanies->name)
                                . "</span>. Comme le prévoient les règles d'Unilend, <span class=\"text-primary\">"
                                . htmlentities($oCompanies->name)
                                . "</span> a choisi de rembourser son emprunt par anticipation sans frais.<br/>
                                Depuis l'origine, il vous a versé <span class=\"text-primary\">"
                                . $this->oFicelle->formatNumber($oLenderRepayment->getRepaidInterests(['id_loan' => $loanId]))
                                . "&nbsp;€</span> d'intérêts soit un taux d'intérêt annualisé moyen de <span class=\"text-primary\">"
                                . $this->oFicelle->formatNumber($oLoan->getWeightedAverageInterestRateForLender($wallet->getId(), $oProject->id_project), 1) . '&nbsp;%.</span>';

                            $earlyRepaymentContent = '<table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                        <tr>
                                                            <td class="alert">
                                                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                                    <tr>
                                                                        <td class="left" valign="top">
                                                                            <img src="https://www.local.ca-lendingservices.com/images/emails/alert.png"
                                                                             width="36" height="36" alt="Important">
                                                                        </td>
                                                                        <td class="right" valign="top">
                                                                            <p> ' . $earlyRepaymentText . ' </p>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table>';
                        } else {
                            $oLenderRepayment->get($repaymentScheduleId);

                            $fRepaymentCapital              = bcdiv($oLenderRepayment->capital_rembourse, 100, 2);
                            $fRepaymentInterestsTaxIncluded = bcdiv($oLenderRepayment->interets_rembourses, 100, 2);
                            if (false == empty($oLenderRepayment->id_echeancier)) {
                                $fRepaymentTax = $this->entityManager->getRepository(Operation::class)->getTaxAmountByRepaymentScheduleId($oLenderRepayment->id_echeancier);
                            } else {
                                $fRepaymentTax = 0;
                            }
                            $fRepaymentAmount = bcsub(bcadd($fRepaymentCapital, $fRepaymentInterestsTaxIncluded, 2), $fRepaymentTax, 2);
                        }

                        $fTotalAmount               += $fRepaymentAmount;
                        $fTotalCapital              += $fRepaymentCapital;
                        $fTotalInterestsTaxIncluded += $fRepaymentInterestsTaxIncluded;
                        $fTotalInterestsTaxFree     += $fRepaymentInterestsTaxIncluded - $fRepaymentTax;

                        if (mb_strlen($oProject->title) > 20) {
                            $sRepaymentsListHTML .= '
                                                    <tr><td class="td" colspan="5"><a href="' . $this->sFUrl . '/projects/detail/' . $oProject->slug . '">'
                                . $oProject->title . '</a></td></tr>
                                                    <tr>
                                                        <td></td>
                                                        <td class="td text-right">' . $this->oFicelle->formatNumber($fRepaymentAmount) . '&nbsp;€</td>
                                                        <td class="td text-right">' . $this->oFicelle->formatNumber($fRepaymentCapital) . '&nbsp;€</td>
                                                        <td class="td text-right">' . $this->oFicelle->formatNumber($fRepaymentInterestsTaxIncluded) . '&nbsp;€</td>
                                                        <td class="td text-right">' . $this->oFicelle->formatNumber($fRepaymentInterestsTaxIncluded - $fRepaymentTax) . '&nbsp;€</td>
                                                    </tr>';
                        } else {
                            $sRepaymentsListHTML .= '
                                                    <tr>
                                                        <td class="td"><a href="' . $this->sFUrl . '/projects/detail/' . $oProject->slug . '">' . $oProject->title . '</a></td>
                                                        <td class="td text-right">' . $this->oFicelle->formatNumber($fRepaymentAmount) . '&nbsp;€</td>
                                                        <td class="td text-right">' . $this->oFicelle->formatNumber($fRepaymentCapital) . '&nbsp;€</td>
                                                        <td class="td text-right">' . $this->oFicelle->formatNumber($fRepaymentInterestsTaxIncluded) . '&nbsp;€</td>
                                                        <td class="td text-right">' . $this->oFicelle->formatNumber($fRepaymentInterestsTaxIncluded - $fRepaymentTax) . '&nbsp;€</td>
                                                    </tr>';
                        }
                    }

                    $sRepaymentsListHTML .= '
                        <tr>
                            <td class="tf">Total</td>
                            <td class="tf text-right">' . $this->oFicelle->formatNumber($fTotalAmount) . '&nbsp;€</td>
                            <td class="tf text-right">' . $this->oFicelle->formatNumber($fTotalCapital) . '&nbsp;€</td>
                            <td class="tf text-right">' . $this->oFicelle->formatNumber($fTotalInterestsTaxIncluded) . '&nbsp;€</td>
                            <td class="tf text-right">' . $this->oFicelle->formatNumber($fTotalInterestsTaxFree) . '&nbsp;€</td>
                        </tr>';

                    if (1 === $iRepaymentsCount && 'quotidienne' === $sFrequency) {
                        $sSubject = $this->translator->trans('email-synthese_sujet-synthese-quotidienne-singulier');
                        $sContent = $this->translator->trans('email-synthese_contenu-synthese-quotidienne-singulier');
                    } elseif (1 < $iRepaymentsCount && 'quotidienne' === $sFrequency) {
                        $sSubject = $this->translator->trans('email-synthese_sujet-synthese-quotidienne-pluriel');
                        $sContent = $this->translator->trans('email-synthese_contenu-synthese-quotidienne-pluriel');
                    } elseif (1 === $iRepaymentsCount && 'hebdomadaire' === $sFrequency) {
                        $sSubject = $this->translator->trans('email-synthese_sujet-synthese-hebdomadaire-singulier');
                        $sContent = $this->translator->trans('email-synthese_contenu-synthese-quotidienne-singulier');
                    } elseif (1 < $iRepaymentsCount && 'hebdomadaire' === $sFrequency) {
                        $sSubject = $this->translator->trans('email-synthese_sujet-synthese-hebdomadaire-pluriel');
                        $sContent = $this->translator->trans('email-synthese_contenu-synthese-hebdomadaire-pluriel');
                    } elseif (1 === $iRepaymentsCount && 'mensuelle' === $sFrequency) {
                        $sSubject = $this->translator->trans('email-synthese_sujet-synthese-mensuelle-singulier');
                        $sContent = $this->translator->trans('email-synthese_contenu-synthese-quotidienne-singulier');
                    } elseif (1 < $iRepaymentsCount && 'mensuelle' === $sFrequency) {
                        $sSubject = $this->translator->trans('email-synthese_sujet-synthese-mensuelle-pluriel');
                        $sContent = $this->translator->trans('email-synthese_contenu-synthese-mensuelle-pluriel');
                    } else {
                        trigger_error('Frequency and number of repayments not handled: ' . $sFrequency . ' / ' . $iRepaymentsCount, E_USER_WARNING);

                        continue;
                    }

                    $keywords = [
                        'firstName'      => $wallet->getIdClient()->getFirstName(),
                        'content'        => $sContent,
                        'repayments'     => $sRepaymentsListHTML,
                        'earlyRepayment' => $earlyRepaymentContent,
                        'balance'        => $this->oFicelle->formatNumber($wallet->getAvailableBalance()),
                        'lenderPattern'  => $wallet->getWireTransferPattern(),
                        'subject'        => $sSubject,
                    ];

                    /** @var TemplateMessage $message */
                    $message = $this->messageProvider->newMessage($sMail, $keywords);

                    try {
                        $message->setTo($wallet->getIdClient()->getEmail());
                        $this->mailer->send($message);
                    } catch (\Exception $exception) {
                        $this->oLogger->warning(
                            'Could not send email: ' . $sMail . ' - Exception: ' . $exception->getMessage(),
                            [
                                'id_mail_template' => $message->getTemplateId(),
                                'id_client'        => $wallet->getIdClient()->getIdClient(),
                                'class'            => __CLASS__,
                                'function'         => __FUNCTION__,
                            ]
                        );
                    }
                } catch (\Exception $oException) {
                    if ($this->oLogger instanceof LoggerInterface) {
                        $this->oLogger->error(
                            'Could not repayments summary send email for customer ' . $iCustomerId . ' - Message: ' . $oException->getMessage(),
                            ['class' => __CLASS__, 'function' => __FUNCTION__]
                        );
                    }
                }
            }
        }

        $oNotificationsLog->fin = date('Y-m-d H:i:s');
        $oNotificationsLog->update();
    }

    /**
     * @param \users|Users $user
     * @param string       $newPassword
     */
    public function sendNewPasswordEmail($user, string $newPassword)
    {
        if ($user instanceof \users) {
            $user = $this->entityManager->getRepository(Users::class)->find($user->id_user);
        }

        $replacements = [
            'surl'    => $this->sSUrl,
            'url'     => $this->sFUrl,
            'email'   => trim($user->getEmail()),
            'lien_fb' => $this->settingsRepository->findOneBy(['type' => 'Facebook'])->getValue(),
            'lien_tw' => $this->settingsRepository->findOneBy(['type' => 'Twitter'])->getValue(),
            'annee'   => date('Y'),
            'mdp'     => $newPassword,
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('user-nouveau-mot-de-passe', $replacements);

        try {
            $message->setTo(trim($user->getEmail()));
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->oLogger->warning('Could not send email: user-nouveau-mot-de-passe - Exception: ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'id_user'          => $user->getIdUser(),
                'class'            => __CLASS__,
                'function'         => __FUNCTION__,
            ]);
        }
    }

    /**
     * @param \users $user
     */
    public function sendAdminPasswordModificationEmail(\users $user)
    {
        $keywords = [
            'firstName' => $user->firstname,
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('admin-nouveau-mot-de-passe', $keywords);

        try {
            $message->setTo(trim($user->email));

            $logEmail = $this->settingsRepository->findOneBy(['type' => 'alias_tracking_log'])->getValue();

            if ($logEmail) {
                $message->setBcc($logEmail);
            }
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->oLogger->warning(
                'Could not send email : admin-nouveau-mot-de-passe - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'email address' => trim($user->email), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * @param Projects $project
     */
    public function sendInternalNotificationEndOfRepayment(Projects $project)
    {
        $company  = $project->getIdCompany();
        $settings = $this->entityManager->getRepository(Settings::class)->findOneBy(['type' => 'Adresse controle interne']);

        $varMail = [
            'surl'           => $this->sSUrl,
            'url'            => $this->sFUrl,
            'nom_entreprise' => $company->getName(),
            'nom_projet'     => $project->getTitle(),
            'id_projet'      => $project->getIdProject(),
            'annee'          => date('Y'),
        ];

        /** @var TemplateMessage $messageBO */
        $messageBO = $this->messageProvider->newMessage('preteur-dernier-remboursement-controle', $varMail);
        $messageBO->setTo($settings->getValue());
        $this->mailer->send($messageBO);

        $this->oLogger->info('Manual repayment, Send preteur-dernier-remboursement-controle. Data to use: ' . var_export($varMail, true), [
            'class' => __CLASS__, 'function' => __FUNCTION__,
        ]);
    }

    /**
     * @param ProjectsPouvoir $proxy
     * @param ClientsMandats  $mandate
     */
    public function sendProxyAndMandateSigned(ProjectsPouvoir $proxy, ClientsMandats $mandate)
    {
        if ($proxy->getIdProject() && $proxy->getIdProject()->getIdCompany()) {
            $recipient = $this->settingsRepository->findOneBy(['type' => 'Adresse notification pouvoir mandat signe'])->getValue();
            $keywords  = [
                '$surl'         => $this->sSUrl,
                '$id_projet'    => $proxy->getIdProject()->getIdProject(),
                '$nomProjet'    => $proxy->getIdProject()->getTitle(),
                '$nomCompany'   => $proxy->getIdProject()->getIdCompany()->getName(),
                '$lien_pouvoir' => $proxy->getUrlPdf(),
                '$lien_mandat'  => $mandate->getUrlPdf(),
            ];

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage('notification-pouvoir-mandat-signe', $keywords, false);

            try {
                $message->setTo(explode(';', $recipient));
                $this->mailer->send($message);
            } catch (\Exception $exception) {
                $this->oLogger->warning(
                    'Could not send email : notification-pouvoir-mandat-signe - Exception: ' . $exception->getMessage(),
                    ['id_mail_template' => $message->getTemplateId(), 'email address' => explode(';', $recipient), 'class' => __CLASS__, 'function' => __FUNCTION__]
                );
            }
        }
    }

    /**
     * @param Clients $client
     */
    public function sendPartnerAccountActivation(Clients $client)
    {
        $token = $this->entityManager
            ->getRepository(TemporaryLinksLogin::class)
            ->generateTemporaryLink($client, TemporaryLinksLogin::PASSWORD_TOKEN_LIFETIME_LONG)
        ;

        $variables = [
            'firstName'                  => $client->getFirstName(),
            'activationLink'             => $this->sFUrl . $this->router->generate('partner_security', ['securityToken' => $token]),
            'borrowerServicePhoneNumber' => $this->settingsRepository->findOneBy(['type' => 'Téléphone emprunteur'])->getValue(),
            'borrowerServiceEmail'       => $this->settingsRepository->findOneBy(['type' => 'Adresse emprunteur'])->getValue(),
        ];

        $message = $this->messageProvider->newMessage('ouverture-espace-partenaire', $variables);

        try {
            $message->setTo($client->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->oLogger->warning('Could not send email "ouverture-espace-partenaire". Exception: ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'id_client'        => $client->getIdClient(),
                'class'            => __CLASS__,
                'function'         => __FUNCTION__,
                'file'             => $exception->getFile(),
                'line'             => $exception->getLine(),
            ]);
        }
    }

    /**
     * @param ProjectCgv     $termsOfSale
     * @param Companies|null $companySubmitter
     */
    public function sendProjectTermsOfSale(ProjectCgv $termsOfSale, Companies $companySubmitter = null)
    {
        $mailType = 'signature-universign-de-cgv';
        $client   = $termsOfSale->getIdProject()->getIdCompany()->getIdClientOwner();
        $keywords = [
            'firstName'                  => $client->getFirstName(),
            'amount'                     => $this->oFicelle->formatNumber($termsOfSale->getIdProject()->getAmount(), 0),
            'companyName'                => $termsOfSale->getIdProject()->getIdCompany()->getName(),
            'universignTosLink'          => $this->sFUrl . $termsOfSale->getUrlPath(),
            'fundsCommissionRate'        => $this->oFicelle->formatNumber($termsOfSale->getIdProject()->getCommissionRateFunds(), 1),
            'repaymentCommissionRate'    => $this->oFicelle->formatNumber($termsOfSale->getIdProject()->getCommissionRateRepayment(), 1),
            'borrowerServicePhoneNumber' => $this->settingsRepository->findOneBy(['type' => 'Téléphone emprunteur'])->getValue(),
            'borrowerServiceEmail'       => $this->settingsRepository->findOneBy(['type' => 'Adresse emprunteur'])->getValue(),
        ];

        if (null !== $companySubmitter) {
            $mailType               = 'cgv-emprunteurs-depot-partenaire';
            $keywords['agencyName'] = $companySubmitter->getName();
        }

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage($mailType, $keywords);

        try {
            $message->setTo($client->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->oLogger->warning(
                'Could not send email: ' . $mailType . ' - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * @param Clients|\clients $client
     * @param string           $email
     */
    public function sendBorrowerAccount($client, string $email = 'ouverture-espace-emprunteur'): void
    {
        if ($client instanceof \clients) {
            $client = $this->entityManager->getRepository(Clients::class)->find($client->id_client);
        }

        if ($client->isValidated()) {
            $token = $this->entityManager
                ->getRepository(TemporaryLinksLogin::class)
                ->generateTemporaryLink($client, TemporaryLinksLogin::PASSWORD_TOKEN_LIFETIME_LONG)
            ;

            $keywords = [
                'firstName'            => $client->getFirstName(),
                'temporaryToken'       => $token,
                'borrowerServiceEmail' => $this->entityManager->getRepository(Settings::class)->findOneBy(['type' => 'Adresse emprunteur'])->getValue(),
            ];

            $message = $this->messageProvider->newMessage($email, $keywords);

            try {
                $message->setTo($client->getEmail());
                $this->mailer->send($message);
            } catch (\Exception $exception) {
                $this->oLogger->warning('Could not send email: ' . $email . ' - Exception: ' . $exception->getMessage(), [
                    'id_mail_template' => $message->getTemplateId(),
                    'id_client'        => $client->getIdClient(),
                    'class'            => __CLASS__,
                    'function'         => __FUNCTION__,
                ]);
            }
        }
    }

    /**
     * Inspired by Carbon::diffForHumans().
     *
     * @param \DateTime $date
     *
     * @return string
     */
    public function diffFromNowForHumans(\DateTime $date): string
    {
        $duration = '';
        $now      = new \DateTime();

        $diffInterval = $now->diff($date);

        $diffIntervalArray = [
            'month'  => $diffInterval->m,
            'day'    => $diffInterval->d,
            'hour'   => $diffInterval->h,
            'minute' => $diffInterval->i,
            'second' => $diffInterval->s,
        ];

        foreach ($diffIntervalArray as $unit => $count) {
            if ($count > 0) {
                if ('hour' === $unit && $count < 2) {
                    $count    = $count * 60 + $diffIntervalArray['minute'];
                    $duration = $this->translator->transChoice('common_minute', $count, ['%count%' => $count]);
                } else {
                    $duration = $this->translator->transChoice('common_' . $unit, $count, ['%count%' => $count]);
                }

                break;
            }
        }

        unset($diffIntervalArray, $diffInterval);

        return $duration;
    }

    /**
     * @param \DateTime $oStart
     * @param \DateTime $oEnd
     *
     * @return string
     */
    private function formatDateDiff(\DateTime $oStart, \DateTime $oEnd)
    {
        $interval = $oEnd->diff($oStart);

        $format = [];
        if (0 !== $interval->y) {
            $format[] = '%y ' . self::plural($interval->y, 'année');
        }
        if (0 !== $interval->m) {
            $format[] = '%m ' . self::plural($interval->m, 'mois');
        }
        if (0 !== $interval->d) {
            $format[] = '%d ' . self::plural($interval->d, 'jour');
        }
        if (0 !== $interval->h) {
            $format[] = '%h ' . self::plural($interval->h, 'heure');
        }
        if (0 !== $interval->i) {
            $format[] = '%i ' . self::plural($interval->i, 'minute');
        }
        if (0 !== $interval->s) {
            if (!count($format)) {
                return 'moins d\'une minute';
            }
            $format[] = '%s ' . self::plural($interval->s, 'seconde');
        }

        if (count($format) > 1) {
            $format = array_shift($format) . ' et ' . array_shift($format);
        } else {
            $format = array_pop($format);
        }

        return $interval->format($format);
    }

    private static function plural($iNumber, $sTerm)
    {
        if ('s' === mb_substr($sTerm, -1, 1)) {
            return $sTerm;
        }

        return $iNumber > 1 ? $sTerm . 's' : $sTerm;
    }

    private function getActivationTime($idClient)
    {
        /** @var \client_settings $oClientSettings */
        $oClientSettings = $this->entityManagerSimulator->getRepository('client_settings');

        if ($oClientSettings->get($idClient, 'id_type = ' . ClientSettingType::TYPE_AUTOBID_SWITCH . ' AND id_client')) {
            $oActivationTime = new \DateTime($oClientSettings->added);
        } else {
            $oActivationTime = new \DateTime();
        }

        return $oActivationTime;
    }
}
