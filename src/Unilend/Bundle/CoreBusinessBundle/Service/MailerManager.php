<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsMandats;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsPouvoir;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Notifications;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage;
use \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class MailerManager
{
    /** @var \settings */
    private $oSettings;

    /** @var \mail_templates */
    private $oMailTemplate;

    /** @var LoggerInterface */
    private $oLogger;

    /** @var \ficelle */
    private $oFicelle;

    /** @var \dates */
    private $oDate;

    /** @var \jours_ouvres */
    private $oWorkingDay;
    private $sSUrl;
    private $sFUrl;
    private $sAUrl;

    /** @var ContainerInterface */
    private $container;

    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;

    /** @var  EntityManager */
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
        TranslatorInterface $translator
    )
    {
        $this->container              = $container;
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->messageProvider        = $messageProvider;
        $this->mailer                 = $mailer;
        $this->translator             = $translator;

        $this->oSettings     = $this->entityManagerSimulator->getRepository('settings');
        $this->oMailTemplate = $this->entityManagerSimulator->getRepository('mail_templates');

        $this->oFicelle    = Loader::loadLib('ficelle');
        $this->oDate       = Loader::loadLib('dates');
        $this->oWorkingDay = Loader::loadLib('jours_ouvres');

        $this->locale = $defaultLocale;

        $this->sSUrl = $assetsPackages->getUrl('');
        $this->sFUrl = $schema . '://' . $frontHost;
        $this->sAUrl = $schema . '://' . $adminHost;
    }

    /**
     * @param LoggerInterface $oLogger
     */
    public function setLogger(LoggerInterface $oLogger)
    {
        $this->oLogger = $oLogger;
    }

    public function sendBidConfirmation(\notifications $notification)
    {
        /** @var \tree $tree */
        $tree = $this->entityManagerSimulator->getRepository('tree');
        /** @var Bids $bid */
        $bid = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->find($notification->id_bid);

        if (null !== $bid) {
            $mailTemplate = $bid->getAutobid() ? 'confirmation-bid' : 'confirmation-autobid';
            $pageProjects = $tree->getSlug(4, substr($this->locale, 0, 2));

            $varMail = [
                'surl'           => $this->sSUrl,
                'url'            => $this->sFUrl,
                'prenom_p'       => $bid->getIdLenderAccount()->getIdClient()->getPrenom(),
                'nom_entreprise' => $bid->getProject()->getIdCompany()->getName(),
                'project_name'   => $bid->getProject()->getTitle(),
                'valeur_bid'     => $this->oFicelle->formatNumber($bid->getAmount() / 100),
                'taux_bid'       => $this->oFicelle->formatNumber($bid->getRate(), 1),
                'date_bid'       => strftime('%d-%B-%G', $bid->getAdded()->getTimestamp()),
                'heure_bid'      => $bid->getAdded()->format('H:i:s'),
                'projet-p'       => $this->sFUrl . '/' . $pageProjects,
                'autobid_link'   => $this->sFUrl . '/profile/autolend#parametrage',
                'motif_virement' => $bid->getIdLenderAccount()->getWireTransferPattern(),
                'lien_fb'        => $this->getFacebookLink(),
                'lien_tw'        => $this->getTwitterLink()
            ];

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage($mailTemplate, $varMail);
            $message->setTo($bid->getIdLenderAccount()->getIdClient()->getEmail());
            $this->mailer->send($message);
        }
    }

    /**
     * @param \projects $oProject
     */
    public function sendFundFailedToLender(\projects $oProject)
    {
        $bids = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->findBy(['idProject' => $oProject->id_project], ['rate' => 'ASC', 'added' => 'ASC']);

        /** @var Bids $bid */
        foreach ($bids as $bid) {
            $wallet = $bid->getIdLenderAccount();
            if (Clients::STATUS_ONLINE === $wallet->getIdClient()->getStatus()) {
                $fBalance = $wallet->getAvailableBalance();
                $varMail = [
                    'surl'                  => $this->sSUrl,
                    'url'                   => $this->sFUrl,
                    'prenom_p'              => $wallet->getIdClient()->getPrenom(),
                    'entreprise'            => $bid->getProject()->getIdCompany()->getName(),
                    'projet'                => $oProject->title,
                    'montant'               => $this->oFicelle->formatNumber($bid->getAmount() / 100),
                    'proposition_pret'      => $this->oFicelle->formatNumber($bid->getAmount() / 100),
                    'date_proposition_pret' => strftime('%d %B %G', $bid->getAdded()->getTimestamp()),
                    'taux_proposition_pret' => $bid->getRate(),
                    'compte-p'              => '/projets-a-financer',
                    'motif_virement'        => $wallet->getWireTransferPattern(),
                    'solde_p'               => $fBalance,
                    'lien_fb'               => $this->getFacebookLink(),
                    'lien_tw'               => $this->getTwitterLink()
                ];

                /** @var TemplateMessage $message */
                $message = $this->messageProvider->newMessage('preteur-dossier-funding-ko', $varMail);
                $message->setTo($wallet->getIdClient()->getEmail());
                $this->mailer->send($message);
            }
        }
    }

    public function sendFundedToBorrower(\projects $oProject)
    {
        /** @var \companies $oCompany */
        $oCompany = $this->entityManagerSimulator->getRepository('companies');
        /** @var \clients $oBorrower */
        $oBorrower = $this->entityManagerSimulator->getRepository('clients');

        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info(
                'Project funded - sending email to borrower (project ' . $oProject->id_project . ')',
                array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project)
            );
        }

        $oCompany->get($oProject->id_company, 'id_company');
        $oBorrower->get($oCompany->id_client_owner, 'id_client');

        if ($oBorrower->status == 1) {
            $inter = $this->oDate->intervalDates(date('Y-m-d H:i:s'), $oProject->date_retrait);

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

            $keywords = array(
                'surl'          => $this->sSUrl,
                'url'           => $this->sFUrl,
                'prenom_e'      => $oBorrower->prenom,
                'taux_moyen'    => $this->oFicelle->formatNumber($oProject->getAverageInterestRate(), 1),
                'temps_restant' => $remainingDuration,
                'projet'        => $oProject->title,
                'lien_fb'       => $this->getFacebookLink(),
                'lien_tw'       => $this->getTwitterLink()
            );

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage('emprunteur-dossier-funde', $keywords);
            $message->setTo($oBorrower->email);
            $this->mailer->send($message);
        }
    }

    /**
     * @param \projects $project
     */
    public function sendFundedAndFinishedToBorrower(\projects $project)
    {
        /** @var \companies $company */
        $company = $this->entityManagerSimulator->getRepository('companies');
        /** @var \clients $borrower */
        $borrower = $this->entityManagerSimulator->getRepository('clients');
        /** @var \echeanciers_emprunteur $borrowerPaymentSchedule */
        $borrowerPaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers_emprunteur');

        $company->get($project->id_company, 'id_company');
        $borrower->get($company->id_client_owner, 'id_client');

        $borrowerPaymentSchedule->get($project->id_project, 'ordre = 1 AND id_project');
        $monthlyPayment = $borrowerPaymentSchedule->montant + $borrowerPaymentSchedule->commission + $borrowerPaymentSchedule->tva;
        $monthlyPayment = $monthlyPayment / 100;

        $varMail = [
            'surl'                   => $this->sSUrl,
            'url'                    => $this->sFUrl,
            'prenom_e'               => $borrower->prenom,
            'nom_e'                  => $company->name,
            'mensualite'             => $this->oFicelle->formatNumber($monthlyPayment),
            'montant'                => $this->oFicelle->formatNumber($project->amount, 0),
            'taux_moyen'             => $this->oFicelle->formatNumber($project->getAverageInterestRate(), 1),
            'link_compte_emprunteur' => $this->sFUrl . '/projects/detail/' . $project->id_project,
            'link_mandat'            => $this->sFUrl . '/pdf/mandat/' . $borrower->hash . '/' . $project->id_project,
            'link_pouvoir'           => $this->sFUrl . '/pdf/pouvoir/' . $borrower->hash . '/' . $project->id_project,
            'projet'                 => $project->title,
            'lien_fb'                => $this->getFacebookLink(),
            'lien_tw'                => $this->getTwitterLink()
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('emprunteur-dossier-funde-et-termine', $varMail);
        $message->setTo($borrower->email);
        $this->mailer->send($message);

        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info(
                'Email emprunteur-dossier-funde-et-termine sent (project ' . $project->id_project . ')',
                array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project)
            );
        }
    }

    public function sendFundedToStaff(\projects $project)
    {
        /** @var \bids $bid */
        $bid = $this->entityManagerSimulator->getRepository('bids');

        $inter = $this->oDate->intervalDates(date('Y-m-d H:i:s'), $project->date_retrait);

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

        $keywords = [
            '$surl'         => $this->sSUrl,
            '$id_projet'    => $project->id_project,
            '$title_projet' => $project->title,
            '$nbPeteurs'    => $bid->countLendersOnProject($project->id_project),
            '$tx'           => $this->oFicelle->formatNumber($project->getAverageInterestRate(), 1),
            '$periode'      => $remainingDuration
        ];

        $this->oSettings->get('Adresse notification projet funde a 100', 'type');
        $recipient = $this->oSettings->value;

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('notification-projet-funde-a-100', $keywords, false);
        $message->setTo(explode(';', str_replace(' ', '', $recipient)));
        $this->mailer->send($message);
    }

    public function sendBidAccepted(\projects $project)
    {
        /** @var \loans $loan */
        $loan = $this->entityManagerSimulator->getRepository('loans');
        /** @var \companies $oCompany */
        $company = $this->entityManagerSimulator->getRepository('companies');
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

        $lenders          = $loan->getProjectLoansByLender($project->id_project);
        $nbLenders        = count($lenders);
        $nbTreatedLenders = 0;

        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info(
                $nbLenders . ' lenders to send email (project ' . $project->id_project . ')',
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]
            );
        }

        foreach ($lenders as $idLender) {
            $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($idLender);

            if (Clients::STATUS_ONLINE === $wallet->getIdClient()->getStatus()) {
                $company->get($project->id_company, 'id_company');

                $lenderIsNaturalPerson  = in_array($wallet->getIdClient()->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER]);
                $loansOfLender          = $loan->select('id_project = ' . $project->id_project . ' AND id_lender = ' . $wallet->getId(), '`id_type_contract` DESC');
                $numberOfLoansForLender = count($loansOfLender);
                $numberOfAcceptedBids   = $acceptedBid->getDistinctBidsForLenderAndProject($wallet->getId(), $project->id_project);
                $loansDetails           = '';
                $linkExplication        = '';
                $contractText           = '';
                $styleTD                = 'border: 1px solid; padding: 5px; text-align: center; text-decoration:none;';

                if ($lenderIsNaturalPerson) {
                    $contract->get(\underlying_contract::CONTRACT_IFP, 'label');
                    $loanIFP               = $loan->select('id_project = ' . $project->id_project . ' AND id_lender = ' . $wallet->getId() . ' AND id_type_contract = ' .$contract->id_contract);
                    $numberOfBidsInLoanIFP = $acceptedBid->counter('id_loan = ' . $loanIFP[0]['id_loan']);

                    if ($numberOfBidsInLoanIFP > 1) {
                        $contractText = '<br>L&rsquo;ensemble de vos offres &agrave; concurrence de 1 000 euros seront regroup&eacute;es sous la forme d&rsquo;un seul contrat de pr&ecirc;t. Son taux d&rsquo;int&eacute;r&ecirc;t correspondra donc &agrave; la moyenne pond&eacute;r&eacute;e de vos <span style="color:#b20066;">' . $numberOfBidsInLoanIFP . ' offres de pr&ecirc;t</span>. ';

                        $linkExplication = '<br><br>Pour en savoir plus sur les r&egrave;gles de regroupement des offres de pr&ecirc;t, vous pouvez consulter <a style="color:#b20066;" href="' . $this->sSUrl . '/document-de-pret">cette page</a>.';
                    }
                }

                if ($numberOfAcceptedBids > 1) {
                    $selectedOffers = 'vos offres ont &eacute;t&eacute; s&eacute;lectionn&eacute;es';
                    $offers         = 'vos offres';
                    $does           = 'font';
                } else {
                    $selectedOffers = 'votre offre a &eacute;t&eacute; s&eacute;lectionn&eacute;e';
                    $offers         = 'votre offre';
                    $does           = 'fait';
                }

                $loansText = ($numberOfLoansForLender > 1) ? 'vos pr&ecirc;ts' : 'votre pr&ecirc;t';

                foreach ($loansOfLender as $loan) {
                    $firstPayment = $repaymentSchedule->getPremiereEcheancePreteurByLoans($loan['id_project'], $loan['id_lender'], $loan['id_loan']);
                    $contractType = '';
                    if (isset($contractLabel[$loan['id_type_contract']])) {
                        $contractType = $contractLabel[$loan['id_type_contract']];
                    }
                    $loansDetails .= '<tr>
                                               <td style="' . $styleTD . '">' . $this->oFicelle->formatNumber($loan['amount'] / 100) . ' &euro;</td>
                                               <td style="' . $styleTD . '">' . $this->oFicelle->formatNumber($loan['rate']) . ' %</td>
                                               <td style="' . $styleTD . '">' . $project->period . ' mois</td>
                                               <td style="' . $styleTD . '">' . $this->oFicelle->formatNumber($firstPayment['montant'] / 100) . ' &euro;</td>
                                               <td style="' . $styleTD . '">' . $contractType . '</td>
                                               </tr>';
                }

                $varMail = [
                    'surl'                  => $this->sSUrl,
                    'url'                   => $this->sFUrl,
                    'offre_s_selectionne_s' => $selectedOffers,
                    'prenom_p'              => $wallet->getIdClient()->getPrenom(),
                    'nom_entreprise'        => $company->name,
                    'fait'                  => $does,
                    'contrat_pret'          => $contractText,
                    'detail_loans'          => $loansDetails,
                    'offre_s'               => $offers,
                    'pret_s'                => $loansText,
                    'projet-p'              => $this->sFUrl . '/projects/detail/' . $project->slug,
                    'link_explication'      => $linkExplication,
                    'motif_virement'        => $wallet->getWireTransferPattern(),
                    'lien_fb'               => $this->getFacebookLink(),
                    'lien_tw'               => $this->getTwitterLink(),
                    'annee'                 => date('Y')
                ];

                /** @var TemplateMessage $message */
                $message = $this->messageProvider->newMessage('preteur-bid-ok', $varMail);
                $message->setTo($wallet->getIdClient()->getEmail());
                $this->mailer->send($message);

                if ($this->oLogger instanceof LoggerInterface) {
                    $this->oLogger->info(
                        'Email preteur-bid-ok sent for client ' . $wallet->getIdClient()->getIdClient() . ' (project ' . $project->id_project . ')',
                        ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]
                    );
                }
            }

            $nbTreatedLenders++;

            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->info(
                    'Loan notification emails sent to ' . $nbTreatedLenders . '/' . $nbLenders . ' lenders  (project ' . $project->id_project . ')',
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]
                );
            }
        }
    }

    public function sendBidRejected(\notifications $notification)
    {
        /** @var \bids $bids */
        $bids = $this->entityManagerSimulator->getRepository('bids');
        /** @var \projects $project */
        $project = $this->entityManagerSimulator->getRepository('projects');
        /** @var Bids $bid */
        $bid = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->find($notification->id_bid);

        if (Clients::STATUS_ONLINE === $bid->getIdLenderAccount()->getIdClient()->getStatus()) {
            $project->get($bid->getProject()->getIdProject());

            $now          = new \DateTime();
            $interval     = $this->formatDateDiff($now, $bid->getIdProject()->getDateFin());
            $bidManager   = $this->container->get('unilend.service.bid_manager');
            $projectRates = $bidManager->getProjectRateRange($project);

            if ($bid->getAutobid()) {
                if ($bid->getProject()->getDateFin() <= $now) {
                    $mailTemplate = 'preteur-autobid-ko-apres-fin-de-periode-projet';
                } elseif ($bids->getProjectMaxRate($project) > $projectRates['rate_min']) {
                    $mailTemplate = 'preteur-autobid-ko';
                } else {
                    $mailTemplate = 'preteur-autobid-ko-minimum-rate';
                }
            } else {
                if ($bid->getProject()->getDateFin() <= $now) {
                    $mailTemplate = 'preteur-bid-ko-apres-fin-de-periode-projet';
                } elseif ($bids->getProjectMaxRate($project) > $projectRates['rate_min']) {
                    $mailTemplate = 'preteur-bid-ko';
                } else {
                    $mailTemplate = 'preteur-bid-ko-minimum-rate';
                }
            }

            $varMail = [
                'surl'             => $this->sSUrl,
                'url'              => $this->sFUrl,
                'prenom_p'         => $bid->getIdLenderAccount()->getIdClient()->getPrenom(),
                'valeur_bid'       => $this->oFicelle->formatNumber($bid->getAmount() / 100, 0),
                'taux_bid'         => $this->oFicelle->formatNumber($bid->getRate(), 1),
                'autobid_rate_min' => $bid->getAutobid()->getRateMin(),
                'nom_entreprise'   => $bid->getProject()->getIdCompany()->getName(),
                'projet-p'         => $this->sFUrl . '/projects/detail/' . $bid->getProject()->getSlug(),
                'date_bid'         => strftime('%d-%B-%G', $bid->getAdded()->getTimestamp()),
                'heure_bid'        => $bid->getAdded()->format('H\hi'),
                'fin_chrono'       => $interval,
                'projet-bid'       => $this->sFUrl . '/projects/detail/' . $bid->getIdProject()->getSlug(),
                'autobid_link'     => $this->sFUrl . '/profile/autolend#parametrage',
                'motif_virement'   => $bid->getIdLenderAccount()->getWireTransferPattern(),
                'lien_fb'          => $this->getFacebookLink(),
                'lien_tw'          => $this->getTwitterLink()
            ];

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage($mailTemplate, $varMail);
            $message->setTo($bid->getIdLenderAccount()->getIdClient()->getEmail());
            $this->mailer->send($message);
        }
    }

    public function sendFundFailedToBorrower(\projects $oProject)
    {
        /** @var \companies $oCompany */
        $oCompany = $this->entityManagerSimulator->getRepository('companies');
        /** @var \clients $oClient */
        $oClient = $this->entityManagerSimulator->getRepository('clients');

        $oCompany->get($oProject->id_company, 'id_company');
        $oClient->get($oCompany->id_client_owner, 'id_client');

        if ($oClient->status == 1) {
            $varMail = [
                'surl'     => $this->sSUrl,
                'url'      => $this->sFUrl,
                'prenom_e' => $oClient->prenom,
                'projet'   => $oProject->title,
                'lien_fb'  => $this->getFacebookLink(),
                'lien_tw'  => $this->getTwitterLink()
            ];

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage('emprunteur-dossier-funding-ko', $varMail);
            $message->setTo($oClient->email);
            $this->mailer->send($message);

            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->info(
                    'Email emprunteur-dossier-funding-ko sent (project ' . $oProject->id_project . ')',
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project]
                );
            }
        }
    }

    public function sendProjectFinishedToStaff(\projects $oProject)
    {
        /** @var \loans $loan */
        $loan = $this->entityManagerSimulator->getRepository('loans');
        /** @var \companies $oCompany */
        $oCompany = $this->entityManagerSimulator->getRepository('companies');
        /** @var \clients $oClient */
        $oClient = $this->entityManagerSimulator->getRepository('clients');
        /** @var \bids $oBid */
        $oBid = $this->entityManagerSimulator->getRepository('bids');

        $oCompany->get($oProject->id_company, 'id_company');
        $oClient->get($oCompany->id_client_owner, 'id_client');

        $this->oSettings->get('Adresse notification projet fini', 'type');
        $sRecipient = $this->oSettings->value;

        $iBidTotal = $oBid->getSoldeBid($oProject->id_project);
        // si le solde des enchere est supperieur au montant du pret on affiche le montant du pret
        if ($iBidTotal > $oProject->amount) {
            $iBidTotal = $oProject->amount;
        }

        $iLendersNb = $loan->getNbPreteurs($oProject->id_project);
        $this->oMailTemplate->get('notification-projet-fini', 'locale = "' . $this->locale . '" AND status = ' . \mail_templates::STATUS_ACTIVE . ' AND type');

        $varMail = [
            '$surl'         => $this->sSUrl,
            '$url'          => $this->sFUrl,
            '$id_projet'    => $oProject->id_project,
            '$title_projet' => $oProject->title,
            '$nbPeteurs'    => $iLendersNb,
            '$montant_pret' => $oProject->amount,
            '$montant'      => $iBidTotal,
            '$sujetMail'    => htmlentities($this->oMailTemplate->subject),
            '$taux_moyen'   => $this->oFicelle->formatNumber($oProject->getAverageInterestRate(), 1)
        ];
        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage($this->oMailTemplate->type, $varMail, false);
        $message->setTo(explode(';', str_replace(' ', '', $sRecipient)));
        $this->mailer->send($message);
    }

    public function sendFirstAutoBidActivation(\notifications $notification)
    {
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($notification->id_lender);

        if (Clients::STATUS_ONLINE === $wallet->getIdClient()->getStatus()) {
            $varMail = [
                'surl'             => $this->sSUrl,
                'url'              => $this->sFUrl,
                'prenom_p'         => $wallet->getIdClient()->getPrenom(),
                'heure_activation' => $this->getActivationTime($wallet->getIdClient()->getIdClient())->format('G\hi'),
                'motif_virement'   => $wallet->getWireTransferPattern(),
                'lien_fb'          => $this->getFacebookLink(),
                'lien_tw'          => $this->getTwitterLink(),
                'annee'            => date('Y')
            ];

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage('preteur-autobid-activation', $varMail);
            $message->setTo($wallet->getIdClient()->getEmail());
            $this->mailer->send($message);
        }
    }

    private function getFacebookLink()
    {
        $this->oSettings->get('Facebook', 'type');
        return $this->oSettings->value;
    }

    private function getTwitterLink()
    {
        $this->oSettings->get('Twitter', 'type');
        return $this->oSettings->value;
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

        if ($oClientSettings->get($idClient, 'id_type = ' . \client_setting_type::TYPE_AUTO_BID_SWITCH . ' AND id_client')) {
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
                $client     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($company->getIdClientOwner());
                $firstName  = $client->getPrenom();
                $mailClient = $client->getEmail();
            }

            $publicationDate = (null === $project->getDatePublication()) ? new \DateTime() : $project->getDatePublication();
            $endDate         = (null === $project->getDateRetrait()) ? new \DateTime() : $project->getDateRetrait();

            $fundingTime = $publicationDate->diff($endDate);
            $fundingDay  = $fundingTime->d + ($fundingTime->h > 0 ? 1 : 0);

        $mailVariables = [
            'surl'           => $this->sSUrl,
            'url'            => $this->sFUrl,
            'nom_entreprise' => $company->getName(),
            'projet_p'       => $this->sFUrl . '/projects/detail/' . $project->getSlug(),
            'montant'        => $this->oFicelle->formatNumber((float)$project->getAmount(), 0),
            'heure_debut'    => $publicationDate->format('H\hi'),
            'duree'          => $fundingDay . ($fundingDay == 1 ? ' jour' : ' jours'),
            'prenom_e'       => $firstName,
            'lien_fb'        => $this->getFacebookLink(),
            'lien_tw'        => $this->getTwitterLink(),
            'annee'          => date('Y')
        ];

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage('annonce-mise-en-ligne-emprunteur', $mailVariables);
            $message->setTo($mailClient);
            $this->mailer->send($message);
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
            $loan = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->find($lendersId['loan']);
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

            if ($clientNotifications->getNotif($loan->getIdLender()->getIdClient()->getIdClient(), Notifications::TYPE_LOAN_ACCEPTED, 'immediatement') == true) {
                $lenderLoans         = $loans->select('id_project = ' . $project->getIdProject() . ' AND id_lender = ' . $loan->getIdLender()->getId(), 'id_type_contract DESC');
                $iSumMonthlyPayments = $paymentSchedule->getTotalAmount(['id_lender' => $loan->getIdLender()->getId(), 'id_project' => $project->getIdProject(), 'ordre' => 1]);
                $aFirstPayment       = $paymentSchedule->getPremiereEcheancePreteur($project->getIdProject(), $loan->getIdLender()->getId());
                $sDateFirstPayment   = $aFirstPayment['date_echeance'];
                $sLoansDetails       = '';
                $sLinkExplication    = '';
                $sContract           = '';
                $sStyleTD            = 'border: 1px solid; padding: 5px; text-align: center; text-decoration:none;';

                if (in_array($loan->getIdLender()->getIdClient()->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) {
                    $contract->get(\underlying_contract::CONTRACT_IFP, 'label');
                    $loanIFP               = $loans->select('id_project = ' . $project->getIdProject() . ' AND id_lender = ' . $loan->getIdLender()->getId() . ' AND id_type_contract = ' . $contract->id_contract);
                    $numberOfBidsInLoanIFP = $acceptedBids->counter('id_loan = ' . $loanIFP[0]['id_loan']);

                    if ($numberOfBidsInLoanIFP > 1) {
                        $sContract        = '<br>L&rsquo;ensemble de vos offres &agrave; concurrence de 1 000 euros sont regroup&eacute;es sous la forme d&rsquo;un seul contrat de pr&ecirc;t. Son taux d&rsquo;int&eacute;r&ecirc;t correspond donc &agrave; la moyenne pond&eacute;r&eacute;e de vos <span style="color:#b20066;">' . $numberOfBidsInLoanIFP . ' offres de pr&ecirc;t</span>. ';
                        $sLinkExplication = '<br><br>Pour en savoir plus sur les r&egrave;gles de regroupement des offres de pr&ecirc;t, vous pouvez consulter <a style="color:#b20066;" href="' . $this->sSUrl . '/document-de-pret">cette page</a>.';
                    }
                }

                if ($acceptedBids->getDistinctBidsForLenderAndProject($loan->getIdLender()->getId(), $project->getIdProject()) > 1) {
                    $sAcceptedOffers = 'vos offres ont &eacute;t&eacute; accept&eacute;es';
                    $sOffers         = 'vos offres';
                } else {
                    $sAcceptedOffers = 'votre offre a &eacute;t&eacute; accept&eacute;e';
                    $sOffers         = 'votre offre';
                }

                if (count($lenderLoans) > 1) {
                    $sContracts = 'Vos contrats sont disponibles';
                    $sLoans     = 'vos pr&ecirc;ts';
                } else {
                    $sContracts = 'Votre contrat est disponible';
                    $sLoans     = 'votre pr&ecirc;t';
                }

                foreach ($lenderLoans as $aLoan) {
                    $aFirstPayment = $paymentSchedule->getPremiereEcheancePreteurByLoans($aLoan['id_project'], $aLoan['id_lender'], $aLoan['id_loan']);
                    $sContractType = '';
                    if (isset($contractLabel[$aLoan['id_type_contract']])) {
                        $sContractType = $contractLabel[$aLoan['id_type_contract']];
                    }
                    $sLoansDetails .= '<tr>
                                        <td style="' . $sStyleTD . '">' . $this->oFicelle->formatNumber($aLoan['amount'] / 100) . ' &euro;</td>
                                        <td style="' . $sStyleTD . '">' . $this->oFicelle->formatNumber($aLoan['rate']) . ' %</td>
                                        <td style="' . $sStyleTD . '">' . $project->getPeriod() . ' mois</td>
                                        <td style="' . $sStyleTD . '">' . $this->oFicelle->formatNumber($aFirstPayment['montant'] / 100) . ' &euro;</td>
                                        <td style="' . $sStyleTD . '">' . $sContractType . '</td></tr>';

                    if (true == $clientNotifications->getNotif($loan->getIdLender()->getIdClient()->getIdClient(), \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED, 'immediatement')) {
                        $clientMailNotifications->get($aLoan['id_loan'], 'id_client = ' . $loan->getIdLender()->getIdClient()->getIdClient() . ' AND id_loan');
                        $clientMailNotifications->immediatement = 1;
                        $clientMailNotifications->update();
                    }
                }

                $sTimeAdd = strtotime($sDateFirstPayment);
                $sMonth   = $this->oDate->tableauMois['fr'][date('n', $sTimeAdd)];

                $varMail = [
                    'surl'               => $this->sSUrl,
                    'url'                => $this->sFUrl,
                    'offre_s_acceptee_s' => $sAcceptedOffers,
                    'prenom_p'           => $loan->getIdLender()->getIdClient()->getPrenom(),
                    'nom_entreprise'     => $project->getIdCompany()->getName(),
                    'offre_s'            => $sOffers,
                    'pret_s'             => $sLoans,
                    'valeur_bid'         => $this->oFicelle->formatNumber($iSumMonthlyPayments),
                    'detail_loans'       => $sLoansDetails,
                    'mensualite_p'       => $this->oFicelle->formatNumber($iSumMonthlyPayments),
                    'date_debut'         => date('d', $sTimeAdd) . ' ' . $sMonth . ' ' . date('Y', $sTimeAdd),
                    'contrat_s'          => $sContracts,
                    'compte-p'           => $this->sFUrl,
                    'projet-p'           => $this->sFUrl . '/projects/detail/' . $project->getSlug(),
                    'lien_fb'            => $this->getFacebookLink(),
                    'lien_tw'            => $this->getTwitterLink(),
                    'motif_virement'     => $loan->getIdLender()->getWireTransferPattern(),
                    'link_explication'   => $sLinkExplication,
                    'contrat_pret'       => $sContract,
                    'annee'              => date('Y')
                ];

                /** @var TemplateMessage $message */
                $message = $this->messageProvider->newMessage('preteur-contrat', $varMail);
                $message->setTo($loan->getIdLender()->getIdClient()->getEmail());
                $this->mailer->send($message);
            }
        }
    }

    /**
     * @param Projects $project
     */
    public function sendBorrowerBill(Projects $project)
    {
        $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($project->getIdCompany()->getIdClientOwner());

        $varMail = [
            'surl'            => $this->sSUrl,
            'url'             => $this->sFUrl,
            'prenom'          => $client->getPrenom(),
            'entreprise'      => $project->getIdCompany()->getName(),
            'pret'            => $this->oFicelle->formatNumber($project->getAmount()),
            'projet-title'    => $project->getTitle(),
            'compte-p'        => $this->sFUrl,
            'projet-p'        => $this->sFUrl . '/projects/detail/' . $project->getSlug(),
            'link_facture'    => $this->sFUrl . '/pdf/facture_EF/' . $client->getHash() . '/' . $project->getIdProject() . '/',
            'datedelafacture' => date('d') . ' ' . $this->oDate->tableauMois['fr'][date('n')] . ' ' . date('Y'),
            'mois'            => strtolower($this->oDate->tableauMois['fr'][date('n')]),
            'annee'           => date('Y'),
            'lien_fb'         => $this->getFacebookLink(),
            'lien_tw'         => $this->getTwitterLink()
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('facture-emprunteur', $varMail);
        $message->setTo($project->getIdCompany()->getEmailFacture());

        $this->mailer->send($message);
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
            $this->oLogger->debug('Number of customers to process: ' . count($aCustomerId), array('class' => __CLASS__, 'function' => __FUNCTION__));
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
        $oNotificationsLog->id_notif = \clients_gestion_type_notif::TYPE_NEW_PROJECT;
        $oNotificationsLog->type     = $sFrequency;
        $oNotificationsLog->debut    = date('Y-m-d H:i:s');
        $oNotificationsLog->fin      = '0000-00-00 00:00:00';
        $oNotificationsLog->create();

        switch ($sFrequency) {
            case 'quotidienne':
                $sMail = 'nouveaux-projets-du-jour';
                break;
            case 'hebdomadaire':
                $sMail = 'nouveaux-projets-de-la-semaine';
                break;
            default:
                trigger_error('Unknown frequency for new projects summary email: ' . $sFrequency, E_USER_WARNING);
                return;
        }
        foreach (array_chunk($aCustomerId, 100) as $aPartialCustomerId) {
            $aCustomerMailNotifications = [];
            foreach ($oCustomerNotificationSettings->getCustomersNotifications($aPartialCustomerId, $sFrequency, \clients_gestion_type_notif::TYPE_NEW_PROJECT) as $aMailNotifications) {
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
                                <tr style="color:#b20066;">
                                    <td  style="font-family:Arial;font-size:14px;height: 25px;">
                                       <a style="color:#b20066;text-decoration:none;font-family:Arial;" href="' . $this->sFUrl . '/projects/detail/' . $oProject->slug . '">' . $oProject->title . '</a>
                                    </td>
                                    <td align="right" style="font-family:Arial;font-size:14px;">' . $this->oFicelle->formatNumber($oProject->amount, 0) . '&nbsp;&euro;</td>
                                    <td align="right" style="font-family:Arial;font-size:14px;">' . $oProject->period . ' mois</td>
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

                        $aReplacements = [
                            'surl'            => $this->sSUrl,
                            'url'             => $this->sFUrl,
                            'prenom_p'        => $oCustomer->prenom,
                            'liste_projets'   => $sProjectsListHTML,
                            'projet-p'        => $this->sFUrl . '/projets-a-financer',
                            'motif_virement'  => $oCustomer->getLenderPattern($oCustomer->id_client),
                            'gestion_alertes' => $this->sFUrl . '/profile',
                            'contenu'         => $sContent,
                            'objet'           => $sObject,
                            'sujet'           => $sSubject,
                            'lien_fb'         => $this->getFacebookLink(),
                            'lien_tw'         => $this->getTwitterLink()
                        ];

                        /** @var TemplateMessage $message */
                        $message = $this->messageProvider->newMessage($sMail, $aReplacements);
                        $message->setTo($oCustomer->email);

                        $this->mailer->send($message);
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
        $oNotificationsLog->id_notif = \clients_gestion_type_notif::TYPE_BID_PLACED;
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
            foreach ($oCustomerNotificationSettings->getCustomersNotifications($aPartialCustomerId, $sFrequency, \clients_gestion_type_notif::TYPE_BID_PLACED) as $aMailNotifications) {
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
                            <tr style="color:#b20066;">
                                <td>' . $sSpanAutobid . '</td>
                                <td  style="height:25px;font-family:Arial;font-size:14px;"><a style="color:#b20066;text-decoration:none;" href="' . $this->sFUrl . '/projects/detail/' . $oProject->slug . '">' . $oProject->title . '</a></td>
                                <td align="right" style="font-family:Arial;font-size:14px;">' . $this->oFicelle->formatNumber($oBid->amount / 100, 0) . '&nbsp;&euro;</td>
                                <td align="right" style="font-family:Arial;font-size:14px;">' . $this->oFicelle->formatNumber($oBid->rate, 1) . ' %</td>
                            </tr>';
                    }

                    $sBidsListHTML .= '
                        <tr>
                            <td></td>
                            <td style="height:25px;border-top:1px solid #727272;color: #727272;font-family:Arial;font-size:14px;">Total de vos offres</td>
                            <td align="right" style="border-top:1px solid #727272;color:#b20066;font-family:Arial;font-size:14px;">' . $this->oFicelle->formatNumber($iSumBidsPlaced, 0) . '&nbsp;&euro;</td>
                            <td style="border-top:1px solid #727272;color: #727272;font-family:Arial;font-size:14px;"></td>
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

                    $aReplacements = [
                        'surl'            => $this->sSUrl,
                        'url'             => $this->sFUrl,
                        'prenom_p'        => $oCustomer->prenom,
                        'liste_offres'    => $sBidsListHTML,
                        'motif_virement'  => $oCustomer->getLenderPattern($oCustomer->id_client),
                        'gestion_alertes' => $this->sFUrl . '/profile',
                        'contenu'         => $sContent,
                        'objet'           => $sObject,
                        'sujet'           => $sSubject,
                        'lien_fb'         => $this->getFacebookLink(),
                        'lien_tw'         => $this->getTwitterLink()
                    ];

                    /** @var TemplateMessage $message */
                    $message = $this->messageProvider->newMessage($sMail, $aReplacements);
                    $message->setTo($oCustomer->email);

                    $this->mailer->send($message);
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
        $oNotificationsLog->id_notif = \clients_gestion_type_notif::TYPE_BID_REJECTED;
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
            foreach ($oCustomerNotificationSettings->getCustomersNotifications($aPartialCustomerId, $sFrequency, \clients_gestion_type_notif::TYPE_BID_REJECTED) as $aMailNotifications) {
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
                            <tr style="color:#b20066;">
                                <td>' . $sSpanAutobid . '</td>
                                <td  style="height:25px;font-family:Arial;font-size:14px;"><a style="color:#b20066;text-decoration:none;" href="' . $this->sFUrl . '/projects/detail/' . $oProject->slug . '">' . $oProject->title . '</a></td>
                                <td align="right" style="font-family:Arial;font-size:14px;">' . $this->oFicelle->formatNumber($oNotification->amount / 100, 0) . '&nbsp;&euro;</td>
                                <td align="right" style="font-family:Arial;font-size:14px;">' . $this->oFicelle->formatNumber($oBid->rate, 1) . ' %</td>
                            </tr>';
                    }

                    $sBidsListHTML .= '
                        <tr>
                            <td></td>
                            <td style="height:25px;border-top:1px solid #727272;color:#727272;font-family:Arial;font-size:14px;">Total de vos offres</td>
                            <td align="right" style="border-top:1px solid #727272;color:#b20066;font-family:Arial;font-size:14px;">' . $this->oFicelle->formatNumber($iSumRejectedBids, 0) . '&nbsp;&euro;</td>
                            <td style="border-top:1px solid #727272;"></td>
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

                    $aReplacements = [
                        'surl'            => $this->sSUrl,
                        'url'             => $this->sFUrl,
                        'prenom_p'        => $oCustomer->prenom,
                        'liste_offres'    => $sBidsListHTML,
                        'motif_virement'  => $oCustomer->getLenderPattern($oCustomer->id_client),
                        'gestion_alertes' => $this->sFUrl . '/profile',
                        'contenu'         => $sContent,
                        'objet'           => $sObject,
                        'sujet'           => $sSubject,
                        'lien_fb'         => $this->getFacebookLink(),
                        'lien_tw'         => $this->getTwitterLink()
                    ];

                    /** @var TemplateMessage $message */
                    $message = $this->messageProvider->newMessage($sMail, $aReplacements);
                    $message->setTo($oCustomer->email);

                    $this->mailer->send($message);
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
        $oNotificationsLog->id_notif = \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED;
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
            foreach ($oCustomerNotificationSettings->getCustomersNotifications($aPartialCustomerId, $sFrequency, \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED) as $aMailNotifications) {
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
                        $sContractType = '';
                        if (isset($contractLabel[$oLoan->id_type_contract])) {
                            $sContractType = $contractLabel[$oLoan->id_type_contract];
                        } else {
                            trigger_error('Unknown contract type: ' . $oLoan->id_type_contract, E_USER_WARNING);
                        }

                        $sLoansListHTML .= '
                            <tr style="color:#b20066;">
                                <td  style="height:25px;font-family:Arial;font-size:14px;"><a style="color:#b20066;text-decoration:none;" href="' . $this->sFUrl . '/projects/detail/' . $oProject->slug . '">' . $oProject->title . '</a></td>
                                <td align="right" style="font-family:Arial;font-size:14px;">' . $this->oFicelle->formatNumber($oLoan->amount / 100, 0) . '&nbsp;&euro;</td>
                                <td align="right" style="font-family:Arial;font-size:14px;">' . $this->oFicelle->formatNumber($oLoan->rate, 1) . ' %</td>
                                <td align="right" style="font-family:Arial;font-size:14px;">' . $sContractType . '</td>
                            </tr>';
                    }

                    $sLoansListHTML .= '
                        <tr>
                            <td style="height:25px;border-top:1px solid #727272;color:#727272;font-family:Arial;font-size:14px;">Total de vos offres</td>
                            <td align="right" style="border-top:1px solid #727272;color:#b20066;font-family:Arial;font-size:14px;">' . $this->oFicelle->formatNumber($iSumAcceptedLoans, 0) . '&nbsp;&euro;</td>
                            <td style="border-top:1px solid #727272;font-family:Arial;font-size:14px;"></td>
                            <td style="border-top:1px solid #727272;font-family:Arial;font-size:14px;"></td>
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

                    $aReplacements = [
                        'surl'             => $this->sSUrl,
                        'url'              => $this->sFUrl,
                        'prenom_p'         => $wallet->getIdClient()->getPrenom(),
                        'liste_offres'     => $sLoansListHTML,
                        'link_explication' => in_array($wallet->getIdClient()->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER]) ? 'Pour en savoir plus sur les r&egrave;gles de regroupement des offres de pr&ecirc;t, vous pouvez consulter <a style="color:#b20066;" href="' . $this->sSUrl . '/document-de-pret">cette page</a>. ' : '',
                        'motif_virement'   => $wallet->getWireTransferPattern(),
                        'gestion_alertes'  => $this->sFUrl . '/profile',
                        'contenu'          => $sContent,
                        'objet'            => $sObject,
                        'sujet'            => $sSubject,
                        'lien_fb'          => $this->getFacebookLink(),
                        'lien_tw'          => $this->getTwitterLink()
                    ];

                    /** @var TemplateMessage $message */
                    $message = $this->messageProvider->newMessage($sMail, $aReplacements);
                    $message->setTo($wallet->getIdClient()->getEmail());

                    $this->mailer->send($message);
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
        /** @var \transactions $oTransaction */
        $oTransaction = $this->entityManagerSimulator->getRepository('transactions');
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = $this->entityManagerSimulator->getRepository('clients_gestion_mails_notif');
        /** @var \clients_gestion_notifications $oCustomerNotificationSettings */
        $oCustomerNotificationSettings = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');

        /** @var \clients_gestion_notif_log $oNotificationsLog */
        $oNotificationsLog           = $this->entityManagerSimulator->getRepository('clients_gestion_notif_log');
        $oNotificationsLog->id_notif = \clients_gestion_type_notif::TYPE_REPAYMENT;
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
            $aCustomerMailNotifications = array();
            foreach ($oCustomerNotificationSettings->getCustomersNotifications($aPartialCustomerId, $sFrequency, \clients_gestion_type_notif::TYPE_REPAYMENT) as $aMailNotifications) {
                $aCustomerMailNotifications[$aMailNotifications['id_client']][] = $aMailNotifications;
            }

            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->debug('Customer IDs in mail notifications: ' . json_encode(array_keys($aCustomerMailNotifications)) , array('class' => __CLASS__, 'function' => __FUNCTION__));
            }

            foreach ($aCustomerMailNotifications as $iCustomerId => $aMailNotifications) {
                try {
                    $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($iCustomerId, WalletType::LENDER);

                    $sEarlyRepaymentContent     = '';
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
                        $oTransaction->get($aMailNotification['id_transaction']);

                        if (\transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT == $oTransaction->type_transaction) {
                            /** @var \companies $oCompanies */
                            $oCompanies = $this->entityManagerSimulator->getRepository('companies');
                            $oCompanies->get($oProject->id_company);

                            /** @var \loans $oLoan */
                            $oLoan = $this->entityManagerSimulator->getRepository('loans');

                            $fRepaymentCapital              = $oTransaction->montant / 100;
                            $fRepaymentInterestsTaxIncluded = 0;
                            $fRepaymentTax                  = 0;

                            $sEarlyRepaymentContent = "
                                Important : le remboursement de <span style='color: #b20066;'>" . $this->oFicelle->formatNumber($oTransaction->montant / 100) . "&nbsp;&euro;</span> correspond au remboursement total du capital restant d&ucirc; de votre pr&egrave;t &agrave; <span style='color: #b20066;'>" . htmlentities($oCompanies->name) . "</span>.
                                Comme le pr&eacute;voient les r&egrave;gles d'Unilend, <span style='color: #b20066;'>" . htmlentities($oCompanies->name) . "</span> a choisi de rembourser son emprunt par anticipation sans frais.
                                <br/><br/>
                                Depuis l'origine, il vous a vers&eacute; <span style='color: #b20066;'>" . $this->oFicelle->formatNumber($oLenderRepayment->getRepaidInterests(['id_loan' => $oTransaction->id_loan_remb])) . "&nbsp;&euro;</span> d'int&eacute;r&ecirc;ts soit un taux d'int&eacute;r&ecirc;t annualis&eacute; moyen de <span style='color: #b20066;'>" . $this->oFicelle->formatNumber($oLoan->getWeightedAverageInterestRateForLender($wallet->getId(), $oProject->id_project), 1) . " %.</span><br/><br/> ";
                        } else {
                            $oLenderRepayment->get($oTransaction->id_echeancier);

                            $fRepaymentCapital              = bcdiv($oLenderRepayment->capital_rembourse, 100, 2);
                            $fRepaymentInterestsTaxIncluded = bcdiv($oLenderRepayment->interets_rembourses, 100, 2);
                            if (false == empty($oLenderRepayment->id_echeancier)) {
                                $fRepaymentTax = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->getTaxAmountByRepaymentScheduleId($oLenderRepayment->id_echeancier);
                            } else {
                                $fRepaymentTax = 0;
                            }
                            $fRepaymentAmount = bcsub(bcadd($fRepaymentCapital, $fRepaymentInterestsTaxIncluded, 2), $fRepaymentTax, 2);
                        }

                        $fTotalAmount += $fRepaymentAmount;
                        $fTotalCapital += $fRepaymentCapital;
                        $fTotalInterestsTaxIncluded += $fRepaymentInterestsTaxIncluded;
                        $fTotalInterestsTaxFree += $fRepaymentInterestsTaxIncluded - $fRepaymentTax;

                        $sRepaymentsListHTML .= '
                            <tr style="color:#b20066;">
                                <td  style="height:25px;font-family:Arial;font-size:14px;"><a style="color:#b20066;text-decoration:none;" href="' . $this->sFUrl . '/projects/detail/' . $oProject->slug . '">' . $oProject->title . '</a></td>
                                <td align="right" style="font-family:Arial;font-size:14px;">' . $this->oFicelle->formatNumber($fRepaymentAmount) . '&nbsp;&euro;</td>
                                <td align="right" style="font-family:Arial;font-size:14px;">' . $this->oFicelle->formatNumber($fRepaymentCapital) . '&nbsp;&euro;</td>
                                <td align="right" style="font-family:Arial;font-size:14px;">' . $this->oFicelle->formatNumber($fRepaymentInterestsTaxIncluded) . '&nbsp;&euro;</td>
                                <td align="right" style="font-family:Arial;font-size:14px;">' . $this->oFicelle->formatNumber($fRepaymentInterestsTaxIncluded - $fRepaymentTax) . '&nbsp;&euro;</td>
                            </tr>';
                    }

                    $sRepaymentsListHTML .= '
                        <tr>
                            <td style="height:25px;font-family:Arial;font-size:14px;border-top:1px solid #727272;color:#727272;">Total</td>
                            <td align="right" style="font-family:Arial;font-size:14px;color:#b20066;border-top:1px solid #727272;">' . $this->oFicelle->formatNumber($fTotalAmount) . '&nbsp;&euro;</td>
                            <td align="right" style="font-family:Arial;font-size:14px;color:#b20066;border-top:1px solid #727272;">' . $this->oFicelle->formatNumber($fTotalCapital) . '&nbsp;&euro;</td>
                            <td align="right" style="font-family:Arial;font-size:14px;color:#b20066;border-top:1px solid #727272;">' . $this->oFicelle->formatNumber($fTotalInterestsTaxIncluded) . '&nbsp;&euro;</td>
                            <td align="right" style="font-family:Arial;font-size:14px;color:#b20066;border-top:1px solid #727272;">' . $this->oFicelle->formatNumber($fTotalInterestsTaxFree) . '&nbsp;&euro;</td>
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

                    $aReplacements = [
                        'surl'                   => $this->sSUrl,
                        'url'                    => $this->sFUrl,
                        'prenom_p'               => $wallet->getIdClient()->getPrenom(),
                        'liste_offres'           => $sRepaymentsListHTML,
                        'motif_virement'         => $wallet->getWireTransferPattern(),
                        'gestion_alertes'        => $this->sFUrl . '/profile',
                        'montant_dispo'          => $this->oFicelle->formatNumber($wallet->getAvailableBalance()),
                        'remboursement_anticipe' => $sEarlyRepaymentContent,
                        'contenu'                => $sContent,
                        'sujet'                  => $sSubject,
                        'lien_fb'                => $this->getFacebookLink(),
                        'lien_tw'                => $this->getTwitterLink(),
                        'annee'                  => date('Y')
                    ];

                    /** @var TemplateMessage $message */
                    $message = $this->messageProvider->newMessage($sMail, $aReplacements);
                    $message->setTo($wallet->getIdClient()->getEmail());

                    $this->mailer->send($message);
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
            'lien_fb' => $this->getFacebookLink(),
            'lien_tw' => $this->getTwitterLink(),
            'annee'   => date('Y'),
            'mdp'     => $newPassword
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('user-nouveau-mot-de-passe', $replacements);
        $message->setTo(trim($user->email));
        $this->mailer->send($message);
    }

    /**
     * @param \users $user
     */
    public function sendPasswordModificationEmail(\users $user)
    {
        $replacements = [
            'surl'    => $this->sSUrl,
            'url'     => $this->sFUrl,
            'email'   => trim($user->email),
            'lien_fb' => $this->getFacebookLink(),
            'lien_tw' => $this->getTwitterLink(),
            'annee'   => date('Y')
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('admin-nouveau-mot-de-passe', $replacements);
        $message->setTo(trim($user->email));

        /** @var \settings $settings */
        $settings = $this->entityManagerSimulator->getRepository('settings');
        $settings->get('alias_tracking_log', 'type');

        if (false === empty($settings->value)) {
            $message->setBcc($settings->value);
        }
        $this->mailer->send($message);
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
        $loans = $this->entityManagerSimulator->getRepository('loans');

        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $varMail = [
            'surl'               => $this->sSUrl,
            'url'                => $this->sFUrl,
            'prenom'             => $client->getPrenom(),
            'date_financement'   => $borrowerWithdrawal->getAdded()->format('d/m/Y'),
            'date_remboursement' => $lastRepayment->getAdded()->format('d/m/Y'),
            'raison_sociale'     => $company->getName(),
            'montant'            => $ficelle->formatNumber($project->getAmount(), 0),
            'duree'              => $project->getPeriod(),
            'duree_financement'  => $project->getDatePublication()->diff($project->getDateRetrait())->d,
            'nb_preteurs'        => $loans->getNbPreteurs($project->getIdProject()),
            'lien_fb'            => $this->getFacebookLink(),
            'lien_tw'            => $this->getTwitterLink(),
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('emprunteur-dernier-remboursement', $varMail);
        $message->setTo($client->getEmail());
        $this->mailer->send($message);
    }

    /**
     * @param \clients $client
     * @param string   $mailType
     */
    public function sendClientValidationEmail(\clients $client, $mailType)
    {
        $varMail = [
            'surl'    => $this->sSUrl,
            'url'     => $this->sFUrl,
            'prenom'  => $client->prenom,
            'projets' => $this->sFUrl . '/projets-a-financer',
            'lien_fb' => $this->getFacebookLink(),
            'lien_tw' => $this->getTwitterLink(),
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage($mailType, $varMail);
        $message->setTo($client->email);
        $this->mailer->send($message);
    }

    /**
     * @param \clients $client
     * @param \offres_bienvenues $welcomeOffer
     */
    public function sendWelcomeOfferEmail(\clients $client, \offres_bienvenues $welcomeOffer)
    {
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $varMail = [
            'surl'            => $this->sSUrl,
            'url'             => $this->sFUrl,
            'prenom_p'        => $client->prenom,
            'projets'         => $this->sFUrl . '/projets-a-financer',
            'offre_bienvenue' => $ficelle->formatNumber($welcomeOffer->montant / 100),
            'lien_fb'         => $this->getFacebookLink(),
            'lien_tw'         => $this->getTwitterLink(),
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('offre-de-bienvenue', $varMail);
        $message->setTo($client->email);
        $this->mailer->send($message);
    }

    /**
     * @param ProjectsPouvoir $proxy
     * @param ClientsMandats $mandate
     */
    public function sendProxyAndMandateSigned(ProjectsPouvoir $proxy, ClientsMandats $mandate)
    {
        if ($proxy->getIdProject() && $proxy->getIdProject()->getIdCompany()) {
            /** @var \settings $setting */
            $setting = $this->entityManagerSimulator->getRepository('settings');
            $setting->get('Adresse notification pouvoir mandat signe', 'type');
            $destinataire = $setting->value;

            $template = [
                '$surl'         => $this->sSUrl,
                '$id_projet'    => $proxy->getIdProject()->getIdProject(),
                '$nomProjet'    => $proxy->getIdProject()->getTitle(),
                '$nomCompany'   => $proxy->getIdProject()->getIdCompany()->getName(),
                '$lien_pouvoir' => $proxy->getUrlPdf(),
                '$lien_mandat'  => $mandate->getUrlPdf()
            ];

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage('notification-pouvoir-mandat-signe', $template, false);
            $message->setTo(explode(';', $destinataire));
            $this->mailer->send($message);
        }
    }
}
