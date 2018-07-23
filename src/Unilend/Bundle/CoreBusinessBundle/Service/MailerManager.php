<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\{
    EntityManager, ORMException
};
use Psr\Log\LoggerInterface;
use Symfony\Component\{
    Asset\Packages, DependencyInjection\ContainerInterface, Translation\TranslatorInterface
};
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Bids, Clients, ClientSettingType, ClientsGestionTypeNotif, ClientsMandats, ClientsStatus, Companies, Loans, Notifications, Operation, OperationSubType, ProjectCgv, Projects, ProjectsPouvoir,
    Settings, UnderlyingContract, UniversignEntityInterface, Wallet, WalletType
};
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\{
    TemplateMessage, TemplateMessageProvider
};
use Unilend\core\Loader;

class MailerManager
{
    /** old transaction type for backwards compatibility. It can be removed one all transaction id is null in clients_gestion_mails_notif */
    const TYPE_TRANSACTION_LENDER_ANTICIPATED_REPAYMENT = 23;

    /** @var Settings */
    private $settingsRepository;
    /** @var LoggerInterface */
    private $oLogger;
    /** @var \ficelle */
    private $oFicelle;
    /** @var \dates */
    private $oDate;
    /** @var \jours_ouvres */
    private $oWorkingDay;
    /** @var string */
    private $sAUrl;
    /** @var string */
    private $sSUrl;
    /** @var string */
    private $sFUrl;
    /** @var ContainerInterface */
    private $container;
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManager */
    private $entityManager;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;
    /** @var string */
    private $locale;
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        ContainerInterface $container,
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        $defaultLocale,
        Packages $assetsPackages,
        $schema,
        $frontHost,
        $adminHost,
        TranslatorInterface $translator,
        LoggerInterface $logger
    )
    {
        $this->container              = $container;
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->messageProvider        = $messageProvider;
        $this->mailer                 = $mailer;
        $this->translator             = $translator;
        $this->settingsRepository     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings');

        $this->oFicelle    = Loader::loadLib('ficelle');
        $this->oDate       = Loader::loadLib('dates');
        $this->oWorkingDay = Loader::loadLib('jours_ouvres');

        $this->locale = $defaultLocale;

        $this->sSUrl   = $assetsPackages->getUrl('');
        $this->sFUrl   = $schema . '://' . $frontHost;
        $this->sAUrl   = $schema . '://' . $adminHost;
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
        $bid = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->findOneBy(['idBid' => $notification->id_bid, 'idAutobid' => null]);

        if (null !== $bid) {
            $keywords     = [
                'firstName'     => $bid->getIdLenderAccount()->getIdClient()->getPrenom(),
                'companyName'   => $bid->getProject()->getIdCompany()->getName(),
                'projectName'   => $bid->getProject()->getTitle(),
                'bidAmount'     => $this->oFicelle->formatNumber($bid->getAmount() / 100, 0),
                'bidRate'       => $this->oFicelle->formatNumber($bid->getRate(), 1),
                'bidDate'       => strftime('%d %B %G', $bid->getAdded()->getTimestamp()),
                'bidTime'       => $bid->getAdded()->format('H:i:s'),
                'lenderPattern' => $bid->getIdLenderAccount()->getWireTransferPattern()
            ];

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage('confirmation-bid', $keywords);

            try {
                $message->setTo($bid->getIdLenderAccount()->getIdClient()->getEmail());
                $this->mailer->send($message);
            } catch (\Exception $exception){
                $this->oLogger->warning(
                    'Could not send email: "confirmation-bid" - Exception: ' . $exception->getMessage(),
                    ['method' => __METHOD__, 'id_mail_template' => $message->getTemplateId(), 'id_client' => $bid->getIdLenderAccount()->getIdClient()->getIdClient(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
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
            ->getRepository('UnilendCoreBusinessBundle:Bids')
            ->findBy(['idProject' => $project], ['rate' => 'ASC', 'added' => 'ASC']);

        foreach ($bids as $bid) {
            $wallet       = $bid->getIdLenderAccount();
            $clientStatus = $wallet->getIdClient()->getIdClientStatusHistory()->getIdStatus()->getId();

            if (in_array($clientStatus, ClientsStatus::GRANTED_LOGIN)) {
                $keywords = [
                    'companyName'   => $bid->getProject()->getIdCompany()->getName(),
                    'firstName'     => $wallet->getIdClient()->getPrenom(),
                    'bidDate'       => strftime('%d %B %G', $bid->getAdded()->getTimestamp()),
                    'bidAmount'     => $this->oFicelle->formatNumber($bid->getAmount() / 100, 0),
                    'bidRate'       => $this->oFicelle->formatNumber($bid->getRate()),
                    'balance'       => $this->oFicelle->formatNumber($wallet->getAvailableBalance()),
                    'lenderPattern' => $wallet->getWireTransferPattern()
                ];

                $message = $this->messageProvider->newMessage('preteur-dossier-funding-ko', $keywords);

                try {
                    $message->setTo($wallet->getIdClient()->getEmail());
                    $this->mailer->send($message);
                } catch (\Exception $exception){
                    $this->oLogger->warning('Could not send email "preteur-dossier-funding-ko". Exception: ' . $exception->getMessage(), [
                        'id_mail_template' => $message->getTemplateId(),
                        'id_client'        => $wallet->getIdClient()->getIdClient(),
                        'class'            => __CLASS__,
                        'function'         => __FUNCTION__,
                        'file'             => $exception->getFile(),
                        'line'             => $exception->getLine()
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
                'function'   => __FUNCTION__
            ]);
            return;
        }

        $borrower = $project->getIdCompany()->getIdClientOwner();

        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info('Project funded - sending email to borrower (project ' . $project->getIdProject() . ')',[
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__
            ]);
        }

        $clientStatus = $borrower->getIdClientStatusHistory()->getIdStatus()->getId();

        if (ClientsStatus::STATUS_VALIDATED === $clientStatus) {
            $currentDate = new \DateTime();
            $interval    = $project->getDateRetrait()->diff($currentDate);

            if ($interval->m > 0) {
                $remainingDuration = $interval->m . ' mois';
            } elseif ($interval->d > 0) {
                $remainingDuration = $interval->d . ' jours';
            } elseif ($interval->h > 0 && $interval->i >= 120) {
                $remainingDuration = $interval->h . ' heures';
            } elseif ($interval->i > 0 && $interval->i < 120) {
                $remainingDuration = $interval->i . ' min';
            } else {
                $remainingDuration = $interval->s . ' secondes';
            }

            $projectRepository   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
            $averageInterestRate = $projectRepository->getAverageInterestRate($project);
            $keywords            = [
                'firstName'         => $borrower->getPrenom(),
                'averageRate'       => $this->oFicelle->formatNumber($averageInterestRate, 1),
                'remainingDuration' => $remainingDuration,
            ];

            $message = $this->messageProvider->newMessage('emprunteur-dossier-funde', $keywords);

            try {
                $message->setTo($borrower->getEmail());
                $this->mailer->send($message);
            } catch (\Exception $exception){
                $this->oLogger->warning('Could not send email "emprunteur-dossier-funde". Exception: ' . $exception->getMessage(), [
                    'id_mail_template' => $message->getTemplateId(),
                    'id_client'        => $borrower->getIdClient(),
                    'class'            => __CLASS__,
                    'function'         => __FUNCTION__,
                    'file'             => $exception->getFile(),
                    'line'             => $exception->getLine()
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
            ->getRepository('UnilendCoreBusinessBundle:ClientsMandats')
            ->findOneBy(['idProject' => $project, 'status' => UniversignEntityInterface::STATUS_SIGNED], ['added' => 'DESC']);
        $proxy = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:ProjectsPouvoir')
            ->findOneBy(['idProject' => $project, 'status' => UniversignEntityInterface::STATUS_SIGNED], ['added' => 'DESC']);

        $documents = '';
        if (null === $mandate) {
            $documents .= $this->translator->trans('universign_mandate-description-for-email');
        }

        if (null === $proxy) {
            $documents .= $this->translator->trans('universign_proxy-description-for-email');
        }

        if (false === in_array($project->getIdCompany()->getLegalFormCode(), BeneficialOwnerManager::BENEFICIAL_OWNER_DECLARATION_EXEMPTED_LEGAL_FORM_CODES)) {
            $beneficialOwnerDeclaration = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectBeneficialOwnerUniversign')
                ->findOneBy(['idProject' => $project, 'status' => UniversignEntityInterface::STATUS_SIGNED], ['added' => 'DESC']);
            if (null === $beneficialOwnerDeclaration) {
                $documents .= $this->translator->trans('universign_beneficial-owner-description-for-email');
            }
        }

        $averageInterestRate = $project->getInterestRate();
        if (empty($averageInterestRate)) {
            $projectRepository   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
            $averageInterestRate = $projectRepository->getAverageInterestRate($project, false);
        }

        $keywords = [
            'firstName'      => $borrower->prenom,
            'companyName'    => $project->getIdCompany()->getName(),
            'projectAmount'  => $this->oFicelle->formatNumber($project->getAmount(), 0),
            'averageRate'    => $this->oFicelle->formatNumber($averageInterestRate, 1),
            'monthlyPayment' => $this->oFicelle->formatNumber($monthlyPayment),
            'signatureLink'  => $this->sFUrl . '/pdf/projet/' . $borrower->hash . '/' . $project->getIdProject(),
            'documentsList'  => $documents
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
                'line'             => $exception->getLine()
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
        $bid   = $this->entityManagerSimulator->getRepository('bids');
        $inter = $this->oDate->intervalDates(date('Y-m-d H:i:s'), $project->getDateRetrait()->format('Y-m-d H:i:s'));

        if ($inter['mois'] > 0) {
            $remainingDuration = $inter['mois'] . ' mois';
        } elseif ($inter['jours'] > 0) {
            $remainingDuration = $inter['jours'] . ' jours';
        } elseif ($inter['heures'] > 0 && $inter['minutes'] >= 120) {
            $remainingDuration = $inter['heures'] . ' heures';
        } elseif ($inter['minutes'] > 0 && $inter['minutes'] < 120) {
            $remainingDuration = $inter['minutes'] . ' min';
        } else {
            $remainingDuration = $inter['secondes'] . ' secondes';
        }

        $projectRepository   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $averageInterestRate = $projectRepository->getAverageInterestRate($project, false);
        $keywords            = [
            '$surl'         => $this->sSUrl,
            '$id_projet'    => $project->getIdProject(),
            '$title_projet' => $project->getTitle(),
            '$nbPeteurs'    => $bid->countLendersOnProject($project->getIdProject()),
            '$tx'           => $this->oFicelle->formatNumber($averageInterestRate, 1),
            '$periode'      => $remainingDuration
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
                'line'             => $exception->getLine()
            ]);
        }
    }

    /**
     * @param Projects $project
     */
    public function sendBidAccepted(Projects $project): void
    {
        /** @var \loans $loanData */
        $loanData = $this->entityManagerSimulator->getRepository('loans');
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers');
        /** @var \accepted_bids $acceptedBid */
        $acceptedBid = $this->entityManagerSimulator->getRepository('accepted_bids');
        /** @var \underlying_contract $contract */
        $contract = $this->entityManagerSimulator->getRepository('underlying_contract');

        $contracts     = $contract->select();
        $contractLabel = [];
        foreach ($contracts as $contractType) {
            $contractLabel[$contractType['id_contract']] = $this->translator->trans('contract-type-label_' . $contractType['label']);
        }

        $lenders          = $loanData->getProjectLoansByLender($project->getIdProject());
        $nbLenders        = count($lenders);
        $nbTreatedLenders = 0;

        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info($nbLenders . ' lenders to send email (project ' . $project->getIdProject() . ')', [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__
            ]);
        }

        if (null === $project->getIdCompany()) {
            $companyName = '';
            $this->oLogger->error('No company found for project ' . $project->getIdProject(), [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__
            ]);
        } else {
            $companyName = $project->getIdCompany()->getName();
        }

        foreach ($lenders as $lender) {
            /** @var Wallet $wallet */
            $wallet       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($lender['id_lender']);
            $clientStatus = $wallet->getIdClient()->getIdClientStatusHistory()->getIdStatus()->getId();

            if (in_array($clientStatus, ClientsStatus::GRANTED_LOGIN)) {
                $loansOfLender          = $loanData->select('id_project = ' . $project->getIdProject() . ' AND id_lender = ' . $wallet->getId(), '`id_type_contract` DESC');
                $numberOfLoansForLender = count($loansOfLender);
                $numberOfAcceptedBids   = $acceptedBid->getDistinctBidsForLenderAndProject($wallet->getId(), $project->getIdProject());
                $loansDetails           = '';
                $multiBidsDisclaimer    = '';
                $multiBidsExplanation   = '';

                if ($wallet->getIdClient()->isNaturalPerson()) {
                    $contract->get(UnderlyingContract::CONTRACT_IFP, 'label');
                    $loanIFP               = $loanData->select('id_project = ' . $project->getIdProject() . ' AND id_lender = ' . $wallet->getId() . ' AND id_type_contract = ' . $contract->id_contract);
                    $numberOfBidsInLoanIFP = $acceptedBid->counter('id_loan = ' . $loanIFP[0]['id_loan']);

                    if ($numberOfBidsInLoanIFP > 1) {
                        $multiBidsDisclaimer  = '<p>L\'ensemble de vos offres à concurrence de <span class="text-primary" style="color: #b20066;">2&nbsp;000&nbsp;€</span> seront regroupées sous la forme d\'un seul contrat de prêt. Son taux d\'intérêt correspondra donc à la moyenne pondérée de vos <span class="text-primary" style="color: #b20066;">' . $numberOfBidsInLoanIFP . ' offres de prêt</span>.</p>';
                        $multiBidsExplanation = '<p>Pour en savoir plus sur les règles de regroupement des offres de prêt, vous pouvez consulter <a href="' . $this->sSUrl . '/document-de-pret" style="color: #b20066; font-weight: normal; text-decoration: none;">cette page</a>.</p>';
                    }
                }

                if ($numberOfAcceptedBids > 1) {
                    $selectedOffers = 'vos offres ont été sélectionnées';
                    $offers         = 'vos offres';
                    $does           = 'font';
                } else {
                    $selectedOffers = 'votre offre a été sélectionnée';
                    $offers         = 'votre offre';
                    $does           = 'fait';
                }

                $loansText = ($numberOfLoansForLender > 1) ? 'vos prêts' : 'votre prêt';

                foreach ($loansOfLender as $loan) {
                    $firstPayment = $repaymentSchedule->getPremiereEcheancePreteurByLoans($loan['id_project'], $loan['id_lender'], $loan['id_loan']);
                    $contractType = '';
                    if (isset($contractLabel[$loan['id_type_contract']])) {
                        $contractType = $contractLabel[$loan['id_type_contract']];
                    }
                    $loansDetails .= '<tr>
                                           <td class="td text-center">' . $this->oFicelle->formatNumber($loan['amount'] / 100, 0) . '&nbsp;€</td>
                                           <td class="td text-center">' . $this->oFicelle->formatNumber($loan['rate']) . '&nbsp;%</td>
                                           <td class="td text-center">' . $project->getPeriod() . ' mois</td>
                                           <td class="td text-center">' . $this->oFicelle->formatNumber($firstPayment['montant'] / 100) . '&nbsp;€</td>
                                           <td class="td text-center">' . $contractType . '</td>
                                      </tr>';
                }

                $keywords = [
                    'firstName'            => $wallet->getIdClient()->getPrenom(),
                    'companyName'          => $companyName,
                    'bidWording'           => $offers,
                    'doesWording'          => $does,
                    'loanWording'          => $loansText,
                    'selectedOfferWording' => $selectedOffers,
                    'loansDetails'         => $loansDetails,
                    'multiBidsDisclaimer'  => $multiBidsDisclaimer,
                    'multiBidsExplanation' => $multiBidsExplanation,
                    'lenderPattern'        => $wallet->getWireTransferPattern()
                ];

                /** @var TemplateMessage $message */
                $message = $this->messageProvider->newMessage('preteur-bid-ok', $keywords);

                try {
                    $message->setTo($wallet->getIdClient()->getEmail());
                    $this->mailer->send($message);
                } catch (\Exception $exception){
                    $this->oLogger->warning(
                        'Could not send email: preteur-bid-ok - Exception: ' . $exception->getMessage(),
                        ['id_mail_template' => $message->getTemplateId(), 'id_client' => $wallet->getIdClient()->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                    );
                }
            }

            $nbTreatedLenders++;

            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->info('Loan notification emails sent to ' . $nbTreatedLenders . '/' . $nbLenders . ' lenders  (project ' . $project->getIdProject() . ')', [
                    'id_project' => $project->getIdProject(),
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__
                ]);
            }
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
        $bidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');
        $bid           = $bidRepository->find($notification->id_bid);
        $clientStatus  = $bid->getIdLenderAccount()->getIdClient()->getIdClientStatusHistory()->getIdStatus()->getId();

        if (in_array($clientStatus, ClientsStatus::GRANTED_LOGIN)) {
            /**
             * Using the projects.data object is a workaround while projects has not been completely migrated on Doctrine Entity
             * and date_fin cannot be NULL
             */
            $project->get($bid->getProject()->getIdProject());

            $now          = new \DateTime();
            /**
             * We use: new \DateTime($project->date_fin) instead of: $bid->getProject()->getDateFin() because
             * it seems like that the $bid->getProject() returns a not up-to-date entity data, while $project->date_fin is updated in another process
             */
            $endDate      = '0000-00-00 00:00:00' === $project->date_fin ? $bid->getProject()->getDateRetrait() : new \DateTime($project->date_fin);

            if (false === $endDate instanceof \DateTime) {
                $datesUsed = ['endDate' => $endDate, 'date_fin' => $project->date_fin, 'getDateRetrait' => $bid->getProject()->getDateRetrait()];
                $this->oLogger->warning(
                    'Could not determine the project end date using following values: ' . json_encode($datesUsed) .
                    ' No mail will be sent to the client ' . $bid->getIdLenderAccount()->getIdClient()->getIdClient(),
                    ['method' => __METHOD__, 'id_project' => $project->id_project, 'id_notification' => $notification->id_notification]
                );

                return;
            }
            $interval     = $this->formatDateDiff($now, $endDate);
            $bidManager   = $this->container->get('unilend.service.bid_manager');
            $projectRates = $bidManager->getProjectRateRange($project);

            if ($bid->getAutobid()) {
                $keyWords = [
                    'lenderPattern' => $bid->getIdLenderAccount()->getWireTransferPattern(),
                    'firstName'     => $bid->getIdLenderAccount()->getIdClient()->getPrenom(),
                    'companyName'   => $bid->getProject()->getIdCompany()->getName(),
                ];

                if (0 === bccomp($bid->getRate(), $projectRates['rate_min'], 1)) {
                    $mailTemplate = 'preteur-autobid-ko-minimum-rate';
                    $keyWords     += [
                        'autolendLink' => $this->sFUrl . '/profile/autolend#parametrage',
                    ];
                } elseif ($endDate <= $now) {
                    $mailTemplate = 'preteur-autobid-ko-apres-fin-de-periode-projet';
                    $keyWords     += [
                        'projectLink' => $this->sFUrl . '/projects/detail/' . $bid->getProject()->getSlug()
                    ];
                } else {
                    $mailTemplate = 'preteur-autobid-ko';
                    $keyWords     += [
                        'bidRemainingTime' => $interval,
                        'projectLink'      => $this->sFUrl . '/projects/detail/' . $bid->getProject()->getSlug()
                    ];
                }
            } else {
                $keyWords = [
                    'lenderPattern' => $bid->getIdLenderAccount()->getWireTransferPattern(),
                    'firstName'     => $bid->getIdLenderAccount()->getIdClient()->getPrenom(),
                    'companyName'   => $bid->getProject()->getIdCompany()->getName(),
                    'bidAmount'     => $this->oFicelle->formatNumber($bid->getAmount() / 100, 0),
                    'bidRate'       => $this->oFicelle->formatNumber($bid->getRate(), 1)
                ];

                if ($endDate <= $now) {
                    $mailTemplate = 'preteur-bid-ko-apres-fin-de-periode-projet';
                    $keyWords     += [
                        'bidDate'          => $bid->getAdded()->format('%d %B %G'),
                        'bidTime'          => $bid->getAdded()->format('H\hi'),
                        'projectLink'      => $this->sFUrl . '/projects/detail/' . $bid->getProject()->getSlug(),
                        'projectsListLink' => $this->sFUrl . '/projects-a-financer'
                    ];
                } elseif ($bidRepository->getProjectMaxRate($project->id_project) > $projectRates['rate_min']) {
                    $mailTemplate = 'preteur-bid-ko';
                    $keyWords     += [
                        'projectLink' => $this->sFUrl . '/projects/detail/' . $bid->getProject()->getSlug()
                    ];
                } else {
                    $mailTemplate = 'preteur-bid-ko-minimum-rate';
                    $keyWords     += [
                        'bidDate' => $bid->getAdded()->format('%d %B %G'),
                        'bidTime' => $bid->getAdded()->format('H\hi'),
                    ];
                }
            }

            $message = $this->messageProvider->newMessage($mailTemplate, $keyWords);

            try {
                $message->setTo($bid->getIdLenderAccount()->getIdClient()->getEmail());
                $this->mailer->send($message);
            } catch (\Exception $exception){
                $this->oLogger->warning(
                    'Could not send email: ' . $mailTemplate . ' - Exception: ' . $exception->getMessage(),
                    ['id_mail_template' => $message->getTemplateId(), 'id_client' => $bid->getIdLenderAccount()->getIdClient()->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
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
                'function'   => __FUNCTION__
            ]);
        }

        $borrower     = $project->getIdCompany()->getIdClientOwner();
        $clientStatus = $borrower->getIdClientStatusHistory()->getIdStatus()->getId();

        if (ClientsStatus::STATUS_VALIDATED === $clientStatus) {
            $keywords = [
                'firstName' => $borrower->getPrenom()
            ];

            $message = $this->messageProvider->newMessage('emprunteur-dossier-funding-ko', $keywords);

            try {
                $message->setTo($borrower->getEmail());
                $this->mailer->send($message);
            } catch (\Exception $exception){
                $this->oLogger->warning('Could not send email "emprunteur-dossier-funding-ko". Exception: ' . $exception->getMessage(), [
                    'id_mail_template' => $message->getTemplateId(),
                    'id_client'        => $borrower->getIdClient(),
                    'class'            => __CLASS__,
                    'function'         => __FUNCTION__,
                    'file'             => $exception->getFile(),
                    'line'             => $exception->getLine()
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
            $totalBidsAmount = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->getProjectTotalAmount($project);
        } catch (ORMException $exception) {
            $this->oLogger->error('Cannot calculate bids total amount for sending finished project email to staff. Exception: ' . $exception->getMessage(), [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
                'file'       => $exception->getFile(),
                'line'       => $exception->getLine()
            ]);
            return;
        }

        $averageInterestRate = $project->getInterestRate();
        if (empty($averageInterestRate)) {
            $projectRepository   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
            $averageInterestRate = $projectRepository->getAverageInterestRate($project, false);
        }

        $lendersCount    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->getLenderNumber($project);
        $totalBidsAmount = min($totalBidsAmount, $project->getAmount());
        $keywords        = [
            '$surl'         => $this->sSUrl,
            '$url'          => $this->sFUrl,
            '$id_projet'    => $project->getIdProject(),
            '$title_projet' => $project->getTitle(),
            '$nbPeteurs'    => $lendersCount,
            '$montant_pret' => $project->getAmount(),
            '$montant'      => $totalBidsAmount,
            '$taux_moyen'   => $this->oFicelle->formatNumber($averageInterestRate, 1)
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
                'line'             => $exception->getLine()
            ]);
        }
    }

    /**
     * @param \notifications $notification
     */
    public function sendFirstAutoBidActivation(\notifications $notification): void
    {
        /** @var Wallet $wallet */
        $wallet       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($notification->id_lender);
        $clientStatus = $wallet->getIdClient()->getIdClientStatusHistory()->getIdStatus()->getId();

        if (in_array($clientStatus, ClientsStatus::GRANTED_LOGIN)) {
            $keyWords = [
                'firstName'              => $wallet->getIdClient()->getPrenom(),
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

    /**
     * @param \DateTime $oStart
     * @param \DateTime $oEnd
     *
     * @return string
     */
    private function formatDateDiff(\DateTime $oStart, \DateTime $oEnd)
    {
        $interval = $oEnd->diff($oStart);

        $format = array();
        if ($interval->y !== 0) {
            $format[] = "%y " . self::plural($interval->y, 'année');
        }
        if ($interval->m !== 0) {
            $format[] = "%m " . self::plural($interval->m, 'mois');
        }
        if ($interval->d !== 0) {
            $format[] = "%d " . self::plural($interval->d, 'jour');
        }
        if ($interval->h !== 0) {
            $format[] = "%h " . self::plural($interval->h, 'heure');
        }
        if ($interval->i !== 0) {
            $format[] = "%i " . self::plural($interval->i, "minute");
        }
        if ($interval->s !== 0) {
            if (! count($format)) {
                return 'moins d\'une minute';
            } else {
                $format[] = "%s " . self::plural($interval->s, "seconde");
            }
        }

        if (count($format) > 1) {
            $format = array_shift($format) . " et " . array_shift($format);
        } else {
            $format = array_pop($format);
        }

        return $interval->format($format);
    }

    private static function plural($iNumber, $sTerm)
    {
        if ('s' === substr($sTerm, -1, 1)) {
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

    public function sendProjectOnlineToBorrower(Projects $project)
    {
        $company = $project->getIdCompany();
        if ($company) {
            if (false === empty($company->getPrenomDirigeant()) && false === empty($company->getEmailDirigeant())) {
                $firstName  = $company->getPrenomDirigeant();
                $mailClient = $company->getEmailDirigeant();
            } else {
                $client     = $company->getIdClientOwner();
                $firstName  = $client->getPrenom();
                $mailClient = $client->getEmail();
            }

            $publicationDate = (null === $project->getDatePublication()) ? new \DateTime() : $project->getDatePublication();
            $endDate         = (null === $project->getDateRetrait()) ? new \DateTime() : $project->getDateRetrait();
            $fundingTime     = $publicationDate->diff($endDate);
            $fundingDay      = $fundingTime->d + ($fundingTime->h > 0 ? 1 : 0);

            $keywords = [
                'firstName'                  => $firstName,
                'companyName'                => $company->getName(),
                'fundingDuration'            => $fundingDay . ($fundingDay == 1 ? ' jour' : ' jours'),
                'projectAmount'              => $this->oFicelle->formatNumber($project->getAmount(), 0),
                'startingTime'               => $publicationDate->format('H\hi'),
                'projectLink'                => $this->sFUrl . '/projects/detail/' . $project->getSlug(),
                'borrowerServicePhoneNumber' => $this->settingsRepository->findOneBy(['type' => 'Téléphone emprunteur'])->getValue(),
                'borrowerServiceEmail'       => $this->settingsRepository->findOneBy(['type' => 'Adresse emprunteur'])->getValue()
            ];

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage('annonce-mise-en-ligne-emprunteur', $keywords);

            try {
                $message->setTo($mailClient);
                $this->mailer->send($message);
            } catch (\Exception $exception){
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
            'new_iban'   => $sNewIban
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('uninotification-modification-iban-bo', $aMail);
        $message->setTo('controle_interne@unilend.fr');
        $this->mailer->send($message);
    }

    /**
     * @param Projects $project
     */
    public function sendLoanAccepted(Projects $project)
    {
        /** @var \loans $loans */
        $loans = $this->entityManagerSimulator->getRepository('loans');

        /** @var \clients_gestion_notifications $clientNotifications */
        $clientNotifications = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');

        foreach ($loans->getProjectLoansByLender($project->getIdProject()) as $lendersId) {
            /** @var Loans $loan */
            $loan = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->find($lendersId['loans']);
            /** @var \echeanciers $paymentSchedule */
            $paymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers');
            /** @var \accepted_bids $acceptedBids */
            $acceptedBids = $this->entityManagerSimulator->getRepository('accepted_bids');
            /** @var \underlying_contract $contract */
            $contract = $this->entityManagerSimulator->getRepository('underlying_contract');
            /** @var \clients_gestion_mails_notif $clientMailNotifications */
            $clientMailNotifications = $this->entityManagerSimulator->getRepository('clients_gestion_mails_notif');

            $contracts     = $contract->select();
            $contractLabel = [];
            foreach ($contracts as $contractType) {
                $contractLabel[$contractType['id_contract']] = $this->translator->trans('contract-type-label_' . $contractType['label']);
            }

            if ($clientNotifications->getNotif($loan->getIdLender()->getIdClient()->getIdClient(), Notifications::TYPE_LOAN_ACCEPTED, 'immediatement')) {
                $lenderLoans          = $loans->select('id_project = ' . $project->getIdProject() . ' AND id_lender = ' . $loan->getIdLender()->getId(), 'id_type_contract DESC');
                $iSumMonthlyPayments  = $paymentSchedule->getTotalAmount(['id_lender' => $loan->getIdLender()->getId(), 'id_project' => $project->getIdProject(), 'ordre' => 1]);
                $aFirstPayment        = $paymentSchedule->getPremiereEcheancePreteur($project->getIdProject(), $loan->getIdLender()->getId());
                $sDateFirstPayment    = $aFirstPayment['date_echeance'];
                $sLoansDetails        = '';
                $multiBidsDisclaimer  = '';
                $multiBidsExplanation = '';

                if (in_array($loan->getIdLender()->getIdClient()->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) {
                    $contract->get(UnderlyingContract::CONTRACT_IFP, 'label');
                    $loanIFP               = $loans->select('id_project = ' . $project->getIdProject() . ' AND id_lender = ' . $loan->getIdLender()->getId() . ' AND id_type_contract = ' . $contract->id_contract);
                    $numberOfBidsInLoanIFP = $acceptedBids->counter('id_loan = ' . $loanIFP[0]['id_loan']);

                    if ($numberOfBidsInLoanIFP > 1) {
                        $multiBidsDisclaimer  = '<p>L\'ensemble de vos offres à concurrence de 2&nbsp;000 euros sont regroupées sous la forme d\'un seul contrat de prêt. Son taux d\'intérêt correspond donc à la moyenne pondérée de vos <span class="text-primary" style="color: #b20066;">' . $numberOfBidsInLoanIFP . ' offres de prêt</span>.</p>';
                        $multiBidsExplanation = '<p>Pour en savoir plus sur les règles de regroupement des offres de prêt, vous pouvez consulter <a href="' . $this->sSUrl . '/document-de-pret" style="color: #b20066; font-weight: normal; text-decoration: none;">cette page</a>.</p>';
                    }
                }

                if ($acceptedBids->getDistinctBidsForLenderAndProject($loan->getIdLender()->getId(), $project->getIdProject()) > 1) {
                    $offers = 'vos offres';
                } else {
                    $offers = 'votre offre';
                }

                if (count($lenderLoans) > 1) {
                    $contractsWording = 'Vos contrats sont disponibles';
                    $loansWording     = 'vos prêts';
                } else {
                    $contractsWording = 'Votre contrat est disponible';
                    $loansWording     = 'votre prêt';
                }

                foreach ($lenderLoans as $aLoan) {
                    $aFirstPayment = $paymentSchedule->getPremiereEcheancePreteurByLoans($aLoan['id_project'], $aLoan['id_lender'], $aLoan['id_loan']);
                    $sContractType = '';
                    if (isset($contractLabel[$aLoan['id_type_contract']])) {
                        $sContractType = $contractLabel[$aLoan['id_type_contract']];
                    }
                    $sLoansDetails .= '<tr>
                                        <td class="td text-center">' . $this->oFicelle->formatNumber($aLoan['amount'] / 100, 0) . '&nbsp;€</td>
                                        <td class="td text-center">' . $this->oFicelle->formatNumber($aLoan['rate']) . ' %</td>
                                        <td class="td text-center">' . $project->getPeriod() . ' mois</td>
                                        <td class="td text-center">' . $this->oFicelle->formatNumber($aFirstPayment['montant'] / 100) . '&nbsp;€</td>
                                        <td class="td text-center">' . $sContractType . '</td></tr>';

                    if (true == $clientNotifications->getNotif($loan->getIdLender()->getIdClient()->getIdClient(), ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED, 'immediatement')) {
                        $clientMailNotifications->get($aLoan['id_loan'], 'id_client = ' . $loan->getIdLender()->getIdClient()->getIdClient() . ' AND id_loan');
                        $clientMailNotifications->immediatement = 1;
                        $clientMailNotifications->update();
                    }
                }

                $sTimeAdd = strtotime($sDateFirstPayment);
                $sMonth   = $this->oDate->tableauMois['fr'][date('n', $sTimeAdd)];

                $keywords = [
                    'firstName'            => $loan->getIdLender()->getIdClient()->getPrenom(),
                    'companyName'          => $project->getIdCompany()->getName(),
                    'bidWording'           => $offers,
                    'loanWording'          => $loansWording,
                    'contractWording'      => $contractsWording,
                    'loansDetails'         => $sLoansDetails,
                    'monthlyRepayment'     => $this->oFicelle->formatNumber($iSumMonthlyPayments),
                    'firstRepaymentDate'   => date('d', $sTimeAdd) . ' ' . $sMonth . ' ' . date('Y', $sTimeAdd),
                    'multiBidsDisclaimer'  => $multiBidsDisclaimer,
                    'multiBidsExplanation' => $multiBidsExplanation,
                    'lenderPattern'        => $loan->getIdLender()->getWireTransferPattern(),
                ];

                /** @var TemplateMessage $message */
                $message = $this->messageProvider->newMessage('preteur-contrat', $keywords);

                try {
                    $message->setTo($loan->getIdLender()->getIdClient()->getEmail());
                    $this->mailer->send($message);
                } catch (\Exception $exception){
                    $this->oLogger->warning(
                        'Could not send email: preteur-contrat - Exception: ' . $exception->getMessage(),
                        ['id_mail_template' => $message->getTemplateId(), 'id_client' => $loan->getIdLender()->getIdClient()->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                    );
                }
            }
        }
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
            'invoiceLink'         => $this->sFUrl . '/pdf/facture_EF/' . $client->getHash() . '/' . $project->getIdProject() . '/'
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('facture-emprunteur', $keywords);

        try {
            $message->setTo($project->getIdCompany()->getEmailFacture());
            $this->mailer->send($message);
        } catch (\Exception $exception){
            $this->oLogger->warning(
                'Could not send email: facture-emprunteur - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * Send new projects summary email
     *
     * @param array  $aCustomerId
     * @param string $sFrequency (quotidienne/hebdomadaire)
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
            $aCustomerMailNotifications = [];
            foreach ($oCustomerNotificationSettings->getCustomersNotifications($aPartialCustomerId, $sFrequency, ClientsGestionTypeNotif::TYPE_NEW_PROJECT) as $aMailNotifications) {
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

                        if (\projects_status::EN_FUNDING == $oProject->status) {
                            $sProjectsListHTML .= '
                                <tr>
                                    <td class="td">
                                       <a href="' . $this->sFUrl . '/projects/detail/' . $oProject->slug . '">' . $oProject->title . '</a>
                                    </td>
                                    <td class="td text-right">' . $this->oFicelle->formatNumber($oProject->amount, 0) . ' €</td>
                                    <td class="td text-right">' . $oProject->period . ' mois</td>
                                </tr>';
                            $iProjectsCount += 1;
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
                            'lenderPattern' => $oCustomer->getLenderPattern($oCustomer->id_client)
                        ];

                        /** @var TemplateMessage $message */
                        $message = $this->messageProvider->newMessage($mailType, $keywords);
                        try {
                            $message->setTo($oCustomer->email);
                            $this->mailer->send($message);
                        } catch (\Exception $exception){
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
     * Send accepted bids summary email
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

        foreach (array_chunk($aCustomerId, 100) as $aPartialCustomerId) {
            $aCustomerMailNotifications = array();
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

                        $sSpanAutobid = empty($oBid->id_autobid) ? '' : '<span style="border-radius: 6px; color: #ffffff; font-weight: bold; background: #d9aa34; padding: 3px 6px 3px 6px; text-decoration: none; margin: 3px">A</span>';

                        $sBidsListHTML .= '
                            <tr>
                                <td class="td">' . $sSpanAutobid . '</td>
                                <td class="td"><a href="' . $this->sFUrl . '/projects/detail/' . $oProject->slug . '" style="color: #b20066; font-weight: normal; text-decoration: none;">' . $oProject->title . '</a></td>
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
                        'lenderPattern' => $oCustomer->getLenderPattern($oCustomer->id_client)
                    ];

                    /** @var TemplateMessage $message */
                    $message = $this->messageProvider->newMessage($sMail, $keywords);
                    try {
                        $message->setTo($oCustomer->email);
                        $this->mailer->send($message);
                    } catch (\Exception $exception){
                        $this->oLogger->warning(
                            'Could not send email: ' . $sMail . ' - Exception: ' . $exception->getMessage(),
                            ['id_mail_template' => $message->getTemplateId(), 'id_client' => $oCustomer->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
                        );
                    }
                } catch (\Exception $oException) {
                    if ($this->oLogger instanceof LoggerInterface) {
                        $this->oLogger->error(
                            'Could not send placed bids summary email for customer ' . $iCustomerId . ' - Message: ' . $oException->getMessage(),
                            array('class' => __CLASS__, 'function' => __FUNCTION__)
                        );
                    }
                }
            }
        }

        $oNotificationsLog->fin = date('Y-m-d H:i:s');
        $oNotificationsLog->update();
    }

    /**
     * Send rejected bids summary email
     *
     * @param array  $aCustomerId
     * @param string $sFrequency
     */
    public function sendRejectedBidsSummaryEmail(array $aCustomerId, $sFrequency)
    {
        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->debug('Rejected bids notifications start', array('class' => __CLASS__, 'function' => __FUNCTION__));
            $this->oLogger->debug('Number of customer to process: ' . count($aCustomerId), array('class' => __CLASS__, 'function' => __FUNCTION__));
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

        foreach (array_chunk($aCustomerId, 100) as $aPartialCustomerId) {
            $aCustomerMailNotifications = [];
            foreach ($oCustomerNotificationSettings->getCustomersNotifications($aPartialCustomerId, $sFrequency, ClientsGestionTypeNotif::TYPE_BID_REJECTED) as $aMailNotifications) {
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

                        $sSpanAutobid = empty($oBid->id_autobid) ? '' : '<span style="border-radius: 6px; color: #ffffff; font-weight: bold; background: #d9aa34; padding: 3px 6px 3px 6px; text-decoration: none; margin: 3px">A</span>';

                        $sBidsListHTML .= '
                            <tr>
                                <td class="td">' . $sSpanAutobid . '</td>
                                <td class="td"><a href="' . $this->sFUrl . '/projects/detail/' . $oProject->slug . '" style="color: #b20066; font-weight: normal; text-decoration: none;">' . $oProject->title . '</a></td>
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
                        'lenderPattern' => $oCustomer->getLenderPattern($oCustomer->id_client)
                    ];

                    /** @var TemplateMessage $message */
                    $message = $this->messageProvider->newMessage($sMail, $keywords);

                    try {
                        $message->setTo($oCustomer->email);
                        $this->mailer->send($message);
                    } catch (\Exception $exception){
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
     * Send accepted loans summary email
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
            foreach ($oCustomerNotificationSettings->getCustomersNotifications($aPartialCustomerId, $sFrequency, ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED) as $aMailNotifications) {
                $aCustomerMailNotifications[$aMailNotifications['id_client']][] = $aMailNotifications;
            }

            foreach ($aCustomerMailNotifications as $iCustomerId => $aMailNotifications) {
                try {
                    $wallet              = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($iCustomerId, WalletType::LENDER);
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
                        $sContractType     = '';
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

                    $keyWords = [
                        'firstName'               => $wallet->getIdClient()->getPrenom(),
                        'acceptedLoans'           => $sLoansListHTML,
                        'content'                 => $sContent,
                        'loanGroupingExplication' => $wallet->getIdClient()->isNaturalPerson() ? 'Pour en savoir plus sur les règles de regroupement des offres de prêt, vous pouvez consulter <a href="' . $this->sSUrl . '/document-de-pret">cette page</a>. ' : '',
                        'lenderPattern'           => $wallet->getWireTransferPattern(),
                        'subject'                 => $sSubject,
                        'title'                   => $sObject
                    ];

                    /** @var TemplateMessage $message */
                    $message = $this->messageProvider->newMessage($sMail, $keyWords);

                    try {
                        $message->setTo($wallet->getIdClient()->getEmail());
                        $this->mailer->send($message);
                    } catch (\Exception $exception){
                        $this->oLogger->warning(
                            'Could not send email: ' . $sMail . ' - Exception: ' . $exception->getMessage(),
                            ['id_mail_template' => $message->getTemplateId(), 'id_client' => $wallet->getIdClient()->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
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
     * Send repayment summary email
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
                $this->oLogger->debug('Customer IDs in mail notifications: ' . json_encode(array_keys($aCustomerMailNotifications)), ['class' => __CLASS__, 'function' => __FUNCTION__]);
            }

            foreach ($aCustomerMailNotifications as $iCustomerId => $aMailNotifications) {
                try {
                    $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($iCustomerId, WalletType::LENDER);

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

                        $walletBalanceHistory = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory')->find($aMailNotification['id_wallet_balance_history']);
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

                            $earlyRepaymentText = "
                                Le remboursement de <span class=\"text-primary\">" . $this->oFicelle->formatNumber($amount) . "&nbsp;€</span> correspond au remboursement total du capital restant dû de votre prêt à <span class=\"text-primary\">" . htmlentities($oCompanies->name) . "</span>.
                                Comme le prévoient les règles d'Unilend, <span class=\"text-primary\">" . htmlentities($oCompanies->name) . "</span> a choisi de rembourser son emprunt par anticipation sans frais.<br/>
                                Depuis l'origine, il vous a versé <span class=\"text-primary\">" . $this->oFicelle->formatNumber($oLenderRepayment->getRepaidInterests(['id_loan' => $loanId])) . "&nbsp;€</span> d'intérêts soit un taux d'intérêt annualisé moyen de <span class=\"text-primary\">" . $this->oFicelle->formatNumber($oLoan->getWeightedAverageInterestRateForLender($wallet->getId(), $oProject->id_project), 1) . "&nbsp;%.</span>";

                            $earlyRepaymentContent ='<table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                        <tr>
                                                            <td class="alert">
                                                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                                    <tr>
                                                                        <td class="left" valign="top">
                                                                            <img src="https://www.local.unilend.fr/images/emails/alert.png" width="36" height="36" alt="Important">
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
                                $fRepaymentTax = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->getTaxAmountByRepaymentScheduleId($oLenderRepayment->id_echeancier);
                            } else {
                                $fRepaymentTax = 0;
                            }
                            $fRepaymentAmount = bcsub(bcadd($fRepaymentCapital, $fRepaymentInterestsTaxIncluded, 2), $fRepaymentTax, 2);
                        }

                        $fTotalAmount               += $fRepaymentAmount;
                        $fTotalCapital              += $fRepaymentCapital;
                        $fTotalInterestsTaxIncluded += $fRepaymentInterestsTaxIncluded;
                        $fTotalInterestsTaxFree     += $fRepaymentInterestsTaxIncluded - $fRepaymentTax;

                        if (strlen($oProject->title) > 20) {
                            $sRepaymentsListHTML .= '
                                                    <tr><td class="td" colspan="5"><a href="' . $this->sFUrl . '/projects/detail/' . $oProject->slug . '">' . $oProject->title . '</a></td></tr>
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
                        'firstName'      => $wallet->getIdClient()->getPrenom(),
                        'content'        => $sContent,
                        'repayments'     => $sRepaymentsListHTML,
                        'earlyRepayment' => $earlyRepaymentContent,
                        'balance'        => $this->oFicelle->formatNumber($wallet->getAvailableBalance()),
                        'lenderPattern'  => $wallet->getWireTransferPattern(),
                        'subject'        => $sSubject
                    ];

                    /** @var TemplateMessage $message */
                    $message = $this->messageProvider->newMessage($sMail, $keywords);
                    try {
                        $message->setTo($wallet->getIdClient()->getEmail());
                        $this->mailer->send($message);
                    } catch (\Exception $exception){
                        $this->oLogger->warning(
                            'Could not send email: ' . $sMail . ' - Exception: ' . $exception->getMessage(),
                            ['id_mail_template' => $message->getTemplateId(), 'id_client' => $wallet->getIdClient()->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
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
     * @param \users $user
     * @param string $newPassword
     */
    public function sendNewPasswordEmail(\users $user, $newPassword)
    {
        $replacements = [
            'surl'    => $this->sSUrl,
            'url'     => $this->sFUrl,
            'email'   => trim($user->email),
            'lien_fb' => $this->settingsRepository->findOneBy(['type' => 'Facebook'])->getValue(),
            'lien_tw' => $this->settingsRepository->findOneBy(['type' => 'Twitter'])->getValue(),
            'annee'   => date('Y'),
            'mdp'     => $newPassword
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('user-nouveau-mot-de-passe', $replacements);
        try {
            $message->setTo(trim($user->email));
            $this->mailer->send($message);
        } catch (\Exception $exception){
            $this->oLogger->warning(
                'Could not send email: user-nouveau-mot-de-passe - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_user' => $user->id_user, 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * @param \users $user
     */
    public function sendAdminPasswordModificationEmail(\users $user)
    {
        $keywords = [
            'firstName' => $user->firstname
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
        $settings = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse controle interne']);

        $varMail = [
            'surl'           => $this->sSUrl,
            'url'            => $this->sFUrl,
            'nom_entreprise' => $company->getName(),
            'nom_projet'     => $project->getTitle(),
            'id_projet'      => $project->getIdProject(),
            'annee'          => date('Y')
        ];

        /** @var TemplateMessage $messageBO */
        $messageBO = $this->messageProvider->newMessage('preteur-dernier-remboursement-controle', $varMail);
        $messageBO->setTo($settings->getValue());
        $this->mailer->send($messageBO);

        $this->oLogger->info('Manual repayment, Send preteur-dernier-remboursement-controle. Data to use: ' . var_export($varMail, true), ['class' => __CLASS__, 'function' => __FUNCTION__]);
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
                '$lien_mandat'  => $mandate->getUrlPdf()
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
        $token     = $this->entityManagerSimulator->getRepository('temporary_links_login')->generateTemporaryLink($client->getIdClient(), \temporary_links_login::PASSWORD_TOKEN_LIFETIME_LONG);
        $variables = [
            'firstName'                  => $client->getPrenom(),
            'activationLink'             => $this->sFUrl . $this->container->get('router')->generate('partner_security', ['securityToken' => $token]),
            'borrowerServicePhoneNumber' => $this->settingsRepository->findOneBy(['type' => 'Téléphone emprunteur'])->getValue(),
            'borrowerServiceEmail'       => $this->settingsRepository->findOneBy(['type' => 'Adresse emprunteur'])->getValue(),
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('ouverture-espace-partenaire', $variables);

        try {
            $message->setTo($client->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception){
            $this->oLogger->warning(
                'Could not send email: ouverture-espace-partenaire - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
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
            'firstName'                  => $client->getPrenom(),
            'amount'                     => $this->oFicelle->formatNumber($termsOfSale->getIdProject()->getAmount(), 0),
            'companyName'                => $termsOfSale->getIdProject()->getIdCompany()->getName(),
            'universignTosLink'          => $this->sFUrl . $termsOfSale->getUrlPath(),
            'fundsCommissionRate'        => $this->oFicelle->formatNumber($termsOfSale->getIdProject()->getCommissionRateFunds(), 1),
            'repaymentCommissionRate'    => $this->oFicelle->formatNumber($termsOfSale->getIdProject()->getCommissionRateRepayment(), 1),
            'borrowerServicePhoneNumber' => $this->settingsRepository->findOneBy(['type' => 'Téléphone emprunteur'])->getValue(),
            'borrowerServiceEmail'       => $this->settingsRepository->findOneBy(['type' => 'Adresse emprunteur'])->getValue()
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
        } catch (\Exception $exception){
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
            $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        }

        $clientStatus = $client->getIdClientStatusHistory()->getIdStatus()->getId();

        if (ClientsStatus::STATUS_VALIDATED === $clientStatus) {
            /** @var \temporary_links_login $temporaryLink */
            $temporaryLink = $this->entityManagerSimulator->getRepository('temporary_links_login');
            $keywords      = [
                'firstName'            => $client->getPrenom(),
                'temporaryToken'       => $temporaryLink->generateTemporaryLink($client->getIdClient(), \temporary_links_login::PASSWORD_TOKEN_LIFETIME_LONG),
                'borrowerServiceEmail' => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse emprunteur'])->getValue()
            ];

            $message = $this->messageProvider->newMessage($email, $keywords);

            try {
                $message->setTo($client->getEmail());
                $this->mailer->send($message);
            } catch (\Exception $exception) {
                $this->oLogger->warning(
                    'Could not send email: ' . $email . ' - Exception: ' . $exception->getMessage(),
                    ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                );
            }
        }
    }
}
