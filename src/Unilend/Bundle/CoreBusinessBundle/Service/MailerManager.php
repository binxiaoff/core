<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
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
    ) {
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

    public function sendBidConfirmation(\notifications $oNotification)
    {
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->entityManagerSimulator->getRepository('lenders_accounts');
        /** @var \clients $oClient */
        $oClient = $this->entityManagerSimulator->getRepository('clients');
        /** @var \companies $oCompany */
        $oCompany = $this->entityManagerSimulator->getRepository('companies');
        /** @var \projects $project */
        $project = $this->entityManagerSimulator->getRepository('projects');
        /** @var \tree $oTree */
        $oTree = $this->entityManagerSimulator->getRepository('tree');
        /** @var \bids $oBid */
        $oBid = $this->entityManagerSimulator->getRepository('bids');

        if ($oLenderAccount->get($oNotification->id_lender) && $oBid->get($oNotification->id_bid) && $oClient->get($oLenderAccount->id_client_owner)) {
            if (empty($oBid->id_autobid)) {
                $sMailTemplate = 'confirmation-bid';
            } else {
                $sMailTemplate = 'confirmation-autobid';
            }

            $timeAdd      = strtotime($oBid->added);
            $month        = $this->oDate->tableauMois['fr'][date('n', $timeAdd)];
            $pageProjects = $oTree->getSlug(4, substr($this->locale, 0, 2));

            $project->get($oBid->id_project);
            $oCompany->get($project->id_company, 'id_company');

            $varMail = array(
                'surl'           => $this->sSUrl,
                'url'            => $this->sFUrl,
                'prenom_p'       => $oClient->prenom,
                'nom_entreprise' => $oCompany->name,
                'project_name'   => $project->title,
                'valeur_bid'     => $this->oFicelle->formatNumber($oBid->amount / 100),
                'taux_bid'       => $this->oFicelle->formatNumber($oBid->rate, 1),
                'date_bid'       => date('d', $timeAdd) . ' ' . $month . ' ' . date('Y', $timeAdd),
                'heure_bid'      => date('H:i:s', strtotime($oBid->added)),
                'projet-p'       => $this->sFUrl . '/' . $pageProjects,
                'autobid_link'   => $this->sFUrl . '/profile/autolend#parametrage',
                'motif_virement' => $oClient->getLenderPattern($oClient->id_client),
                'lien_fb'        => $this->getFacebookLink(),
                'lien_tw'        => $this->getTwitterLink()
            );

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage($sMailTemplate, $varMail);
            $message->setTo($oClient->email);
            $this->mailer->send($message);
        }
    }

    public function sendFundFailedToLender(\projects $oProject)
    {
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->entityManagerSimulator->getRepository('lenders_accounts');
        /** @var \clients $oClient */
        $oClient = $this->entityManagerSimulator->getRepository('clients');
        /** @var \companies $oCompany */
        $oCompany = $this->entityManagerSimulator->getRepository('companies');
        /** @var \bids $oBid */
        $oBid = $this->entityManagerSimulator->getRepository('bids');

        $aBidList = $oBid->select('id_project = ' . $oProject->id_project, 'rate ASC, added ASC');
        foreach ($aBidList as $aBid) {
            $oBid->get($aBid['id_bid']);
            $oLenderAccount->get($oBid->id_lender_account);
            $oClient->get($oLenderAccount->id_client_owner);
            $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($oClient->id_client, WalletType::LENDER);
            if ($oClient->status == 1) {
                $oProject->get($oBid->id_project, 'id_project');
                $oCompany->get($oProject->id_company);

                $fBalance = $wallet->getAvailableBalance();
                $sAdded   = strtotime($oBid->added);
                $month    = $this->oDate->tableauMois['fr'][date('n', $sAdded)];

                $varMail = array(
                    'surl'                  => $this->sSUrl,
                    'url'                   => $this->sFUrl,
                    'prenom_p'              => $oClient->prenom,
                    'entreprise'            => $oCompany->name,
                    'projet'                => $oProject->title,
                    'montant'               => $this->oFicelle->formatNumber($oBid->amount / 100),
                    'proposition_pret'      => $this->oFicelle->formatNumber($oBid->amount / 100),
                    'date_proposition_pret' => date('d', $sAdded) . ' ' . $month . ' ' . date('Y', $sAdded),
                    'taux_proposition_pret' => $oBid->rate,
                    'compte-p'              => '/projets-a-financer',
                    'motif_virement'        => $wallet->getWireTransferPattern(),
                    'solde_p'               => $fBalance,
                    'lien_fb'               => $this->getFacebookLink(),
                    'lien_tw'               => $this->getTwitterLink()
                );

                /** @var TemplateMessage $message */
                $message = $this->messageProvider->newMessage('preteur-dossier-funding-ko', $varMail);
                $message->setTo($oClient->email);
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

    public function sendBidAccepted(\projects $oProject)
    {
        /** @var \loans $oLoan */
        $oLoan = $this->entityManagerSimulator->getRepository('loans');
        /** @var \companies $oCompany */
        $oCompany = $this->entityManagerSimulator->getRepository('companies');
        /** @var \clients $oClient */
        $oClient = $this->entityManagerSimulator->getRepository('clients');
        /** @var \echeanciers $oPaymentSchedule */
        $oPaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers');
        /** @var \accepted_bids $oAcceptedBid */
        $oAcceptedBid = $this->entityManagerSimulator->getRepository('accepted_bids');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->entityManagerSimulator->getRepository('lenders_accounts');
        /** @var \underlying_contract $contract */
        $contract = $this->entityManagerSimulator->getRepository('underlying_contract');

        $contracts     = $contract->select();
        $contractLabel = [];
        foreach ($contracts as $contractType) {
            $contractLabel[$contractType['id_contract']] = $this->translator->trans('contract-type-label_' . $contractType['label']);
        }

        $aLendersIds       = $oLoan->getProjectLoansByLender($oProject->id_project);
        $iNbLenders        = count($aLendersIds);
        $iNbTreatedLenders = 0;

        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info(
                $iNbLenders . ' lenders to send email (project ' . $oProject->id_project . ')',
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project]
            );
        }

        foreach ($aLendersIds as $aLenderId) {
            $oLenderAccount->get($aLenderId['id_lender'], 'id_lender_account');
            $oClient->get($oLenderAccount->id_client_owner, 'id_client');
            if ($oClient->status == 1) {
                $oCompany->get($oProject->id_company, 'id_company');

                $bLenderIsNaturalPerson  = $oLenderAccount->isNaturalPerson($oLenderAccount->id_lender_account);
                $aLoansOfLender          = $oLoan->select('id_project = ' . $oProject->id_project . ' AND id_lender = ' . $oLenderAccount->id_lender_account, '`id_type_contract` DESC');
                $iNumberOfLoansForLender = count($aLoansOfLender);
                $iNumberOfAcceptedBids   = $oAcceptedBid->getDistinctBidsForLenderAndProject($oLenderAccount->id_lender_account, $oProject->id_project);
                $sLoansDetails           = '';
                $sLinkExplication        = '';
                $sContract               = '';
                $sStyleTD                = 'border: 1px solid; padding: 5px; text-align: center; text-decoration:none;';

                if ($bLenderIsNaturalPerson) {
                    $contract->get(\underlying_contract::CONTRACT_IFP, 'label');
                    $aLoanIFP               = $oLoan->select('id_project = ' . $oProject->id_project . ' AND id_lender = ' . $oLenderAccount->id_lender_account . ' AND id_type_contract = ' .$contract->id_contract);
                    $iNumberOfBidsInLoanIFP = $oAcceptedBid->counter('id_loan = ' . $aLoanIFP[0]['id_loan']);

                    if ($iNumberOfBidsInLoanIFP > 1) {
                        $sContract = '<br>L&rsquo;ensemble de vos offres &agrave; concurrence de 1 000 euros seront regroup&eacute;es sous la forme d&rsquo;un seul contrat de pr&ecirc;t. Son taux d&rsquo;int&eacute;r&ecirc;t correspondra donc &agrave; la moyenne pond&eacute;r&eacute;e de vos <span style="color:#b20066;">' . $iNumberOfBidsInLoanIFP . ' offres de pr&ecirc;t</span>. ';

                        $sLinkExplication = '<br><br>Pour en savoir plus sur les r&egrave;gles de regroupement des offres de pr&ecirc;t, vous pouvez consulter <a style="color:#b20066;" href="' . $this->sSUrl . '/document-de-pret">cette page</a>.';
                    }
                }

                if ($iNumberOfAcceptedBids > 1) {
                    $sSelectedOffers = 'vos offres ont &eacute;t&eacute; s&eacute;lectionn&eacute;es';
                    $sOffers         = 'vos offres';
                    $sDoes           = 'font';
                } else {
                    $sSelectedOffers = 'votre offre a &eacute;t&eacute; s&eacute;lectionn&eacute;e';
                    $sOffers         = 'votre offre';
                    $sDoes           = 'fait';
                }

                $sLoans = ($iNumberOfLoansForLender > 1) ? 'vos pr&ecirc;ts' : 'votre pr&ecirc;t';

                foreach ($aLoansOfLender as $aLoan) {
                    $aFirstPayment = $oPaymentSchedule->getPremiereEcheancePreteurByLoans($aLoan['id_project'], $aLoan['id_lender'], $aLoan['id_loan']);
                    $sContractType = '';
                    if (isset($contractLabel[$aLoan['id_type_contract']])) {
                        $sContractType = $contractLabel[$aLoan['id_type_contract']];
                    }
                    $sLoansDetails .= '<tr>
                                               <td style="' . $sStyleTD . '">' . $this->oFicelle->formatNumber($aLoan['amount'] / 100) . ' &euro;</td>
                                               <td style="' . $sStyleTD . '">' . $this->oFicelle->formatNumber($aLoan['rate']) . ' %</td>
                                               <td style="' . $sStyleTD . '">' . $oProject->period . ' mois</td>
                                               <td style="' . $sStyleTD . '">' . $this->oFicelle->formatNumber($aFirstPayment['montant'] / 100) . ' &euro;</td>
                                               <td style="' . $sStyleTD . '">' . $sContractType . '</td>
                                               </tr>';
                }

                $varMail = [
                    'surl'                  => $this->sSUrl,
                    'url'                   => $this->sFUrl,
                    'offre_s_selectionne_s' => $sSelectedOffers,
                    'prenom_p'              => $oClient->prenom,
                    'nom_entreprise'        => $oCompany->name,
                    'fait'                  => $sDoes,
                    'contrat_pret'          => $sContract,
                    'detail_loans'          => $sLoansDetails,
                    'offre_s'               => $sOffers,
                    'pret_s'                => $sLoans,
                    'projet-p'              => $this->sFUrl . '/projects/detail/' . $oProject->slug,
                    'link_explication'      => $sLinkExplication,
                    'motif_virement'        => $oClient->getLenderPattern($oClient->id_client),
                    'lien_fb'               => $this->getFacebookLink(),
                    'lien_tw'               => $this->getTwitterLink(),
                    'annee'                 => date('Y')
                ];

                /** @var TemplateMessage $message */
                $message = $this->messageProvider->newMessage('preteur-bid-ok', $varMail);
                $message->setTo($oClient->email);
                $this->mailer->send($message);

                if ($this->oLogger instanceof LoggerInterface) {
                    $this->oLogger->info(
                        'Email preteur-bid-ok sent for lender ' . $oLenderAccount->id_lender_account . ' (project ' . $oProject->id_project . ')',
                        ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project]
                    );
                }
            }

            $iNbTreatedLenders++;

            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->info(
                    'Loan notification emails sent to ' . $iNbTreatedLenders . '/' . $iNbLenders . ' lenders  (project ' . $oProject->id_project . ')',
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project]
                );
            }
        }
    }

    public function sendBidRejected(\notifications $oNotification)
    {
        /** @var \bids $oBid */
        $oBid = $this->entityManagerSimulator->getRepository('bids');
        /** @var \companies $oCompany */
        $oCompany = $this->entityManagerSimulator->getRepository('companies');
        /** @var \clients $oClient */
        $oClient = $this->entityManagerSimulator->getRepository('clients');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->entityManagerSimulator->getRepository('lenders_accounts');
        /** @var \projects $oProject */
        $oProject = $this->entityManagerSimulator->getRepository('projects');
        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->entityManagerSimulator->getRepository('autobid');

        $oBid->get($oNotification->id_bid);
        $oLenderAccount->get($oBid->id_lender_account);
        $oClient->get($oLenderAccount->id_client_owner);

        if ($oClient->status == 1) {
            $oProject->get($oBid->id_project);
            $oCompany->get($oProject->id_company);

            $oEndDate   = $oProject->date_fin != '0000-00-00 00:00:00' ? new \DateTime($oProject->date_fin) : new \DateTime($oProject->date_retrait);
            $oNow       = new \DateTime();
            $sInterval  = $this->formatDateDiff($oNow, $oEndDate);
            $bIsAutoBid = false === empty($oBid->id_autobid);

            $bidManager   = $this->container->get('unilend.service.bid_manager');
            $projectRates = $bidManager->getProjectRateRange($oProject);

            if ($bIsAutoBid) {
                $oAutoBid->get($oBid->id_autobid);

                if ($oEndDate <= $oNow) {
                    $sMailTemplate = 'preteur-autobid-ko-apres-fin-de-periode-projet';
                } elseif ($oBid->getProjectMaxRate($oProject) > $projectRates['rate_min']) {
                    $sMailTemplate = 'preteur-autobid-ko';
                } else {
                    $sMailTemplate = 'preteur-autobid-ko-minimum-rate';
                }
            } else {
                if ($oEndDate <= $oNow) {
                    $sMailTemplate = 'preteur-bid-ko-apres-fin-de-periode-projet';
                } elseif ($oBid->getProjectMaxRate($oProject) > $projectRates['rate_min']) {
                    $sMailTemplate = 'preteur-bid-ko';
                } else {
                    $sMailTemplate = 'preteur-bid-ko-minimum-rate';
                }
            }
            $iAddedBid = strtotime($oBid->added);
            $sMonthFr  = $this->oDate->tableauMois['fr'][date('n', $iAddedBid)];

            $varMail = [
                'surl'             => $this->sSUrl,
                'url'              => $this->sFUrl,
                'prenom_p'         => $oClient->prenom,
                'valeur_bid'       => $this->oFicelle->formatNumber($oBid->amount / 100, 0),
                'taux_bid'         => $this->oFicelle->formatNumber($oBid->rate, 1),
                'autobid_rate_min' => $oAutoBid->rate_min,
                'nom_entreprise'   => $oCompany->name,
                'projet-p'         => $this->sFUrl . '/projects/detail/' . $oProject->slug,
                'date_bid'         => date('d', $iAddedBid) . ' ' . $sMonthFr . ' ' . date('Y', $iAddedBid),
                'heure_bid'        => $this->oDate->formatDate($oBid->added, 'H\hi'),
                'fin_chrono'       => $sInterval,
                'projet-bid'       => $this->sFUrl . '/projects/detail/' . $oProject->slug,
                'autobid_link'     => $this->sFUrl . '/profile/autolend#parametrage',
                'motif_virement'   => $oClient->getLenderPattern($oClient->id_client),
                'lien_fb'          => $this->getFacebookLink(),
                'lien_tw'          => $this->getTwitterLink()
            ];

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage($sMailTemplate, $varMail);
            $message->setTo($oClient->email);
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
        /** @var \loans $oLoan */
        $oLoan = $this->entityManagerSimulator->getRepository('loans');
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

        $iLendersNb = $oLoan->getNbPreteurs($oProject->id_project);
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

    public function sendFirstAutoBidActivation(\notifications $oNotification)
    {
        /** @var \clients $oClient */
        $oClient = $this->entityManagerSimulator->getRepository('clients');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->entityManagerSimulator->getRepository('lenders_accounts');

        $oLenderAccount->get($oNotification->id_lender);
        $oClient->get($oLenderAccount->id_client_owner, 'id_client');

        if ($oClient->status == 1) {
            $varMail = [
                'surl'             => $this->sSUrl,
                'url'              => $this->sFUrl,
                'prenom_p'         => $oClient->prenom,
                'heure_activation' => $this->getActivationTime($oClient)->format('G\hi'),
                'motif_virement'   => $oClient->getLenderPattern($oClient->id_client),
                'lien_fb'          => $this->getFacebookLink(),
                'lien_tw'          => $this->getTwitterLink(),
                'annee'            => date('Y')
            ];

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage('preteur-autobid-activation', $varMail);
            $message->setTo($oClient->email);
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

    private function getActivationTime(\clients $oClient)
    {
        /** @var \client_settings $oClientSettings */
        $oClientSettings = $this->entityManagerSimulator->getRepository('client_settings');

        if ($oClientSettings->get($oClient->id_client, 'id_type = ' . \client_setting_type::TYPE_AUTO_BID_SWITCH . ' AND id_client')) {
            $oActivationTime = new \DateTime($oClientSettings->added);
        } else {
            $oActivationTime = new \DateTime();
        }
        return $oActivationTime;
    }

    public function sendProjectOnlineToBorrower(\projects $oProject)
    {
        /** @var \companies $oCompanies */
        $oCompanies = $this->entityManagerSimulator->getRepository('companies');
        /** @var \clients $oClients */
        $oClients = $this->entityManagerSimulator->getRepository('clients');
        $oCompanies->get($oProject->id_company);

        if (false === empty($oCompanies->prenom_dirigeant) && false === empty($oCompanies->email_dirigeant)) {
            $sFirstName  = $oCompanies->prenom_dirigeant;
            $sMailClient = $oCompanies->email_dirigeant;
        } else {
            $oClients->get($oCompanies->id_client_owner);
            $sFirstName  = $oClients->prenom;
            $sMailClient = $oClients->email;
        }

        $oPublicationDate = new \DateTime($oProject->date_publication);
        $oEndDate         = new \DateTime($oProject->date_retrait);

        $oFundingTime = $oPublicationDate->diff($oEndDate);
        $iFundingTime = $oFundingTime->d + ($oFundingTime->h > 0 ? 1 : 0);
        $sFundingTime = $iFundingTime . ($iFundingTime == 1 ? ' jour' : ' jours');

        $aMail = [
            'surl'           => $this->sSUrl,
            'url'            => $this->sFUrl,
            'nom_entreprise' => $oCompanies->name,
            'projet_p'       => $this->sFUrl . '/projects/detail/' . $oProject->slug,
            'montant'        => $this->oFicelle->formatNumber((float)$oProject->amount, 0),
            'heure_debut'    => $oPublicationDate->format('H\hi'),
            'duree'          => $sFundingTime,
            'prenom_e'       => $sFirstName,
            'lien_fb'        => $this->getFacebookLink(),
            'lien_tw'        => $this->getTwitterLink(),
            'annee'          => date('Y')
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('annonce-mise-en-ligne-emprunteur', $aMail);
        $message->setTo($sMailClient);
        $this->mailer->send($message);
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
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->entityManagerSimulator->getRepository('lenders_accounts');
        $oLenderAccount->get($iClientId, 'id_client_owner');

        $aMail = [
            'aurl'       => $this->sAUrl,
            'id_client'  => $iClientId,
            'id_lender'  => $oLenderAccount->id_lender_account,
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
     * @param \projects $project
     */
    public function sendLoanAccepted(\projects $project)
    {
        /** @var \loans $loans */
        $loans = $this->entityManagerSimulator->getRepository('loans');

        /** @var \companies $companies */
        $companies = $this->entityManagerSimulator->getRepository('companies');
        $companies->get($project->id_company, 'id_company');

        /** @var \clients_gestion_notifications $clientNotifications */
        $clientNotifications = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');

        /** @var \lenders_accounts $lender */
        $lender = $this->entityManagerSimulator->getRepository('lenders_accounts');

        $aLendersIds = $loans->getProjectLoansByLender($project->id_project);

        foreach ($aLendersIds as $lendersId) {
            $loans->get($lendersId['loans']);
            $lender->get($loans->id_lender);

            /** @var \clients $client */
            $client = $this->entityManagerSimulator->getRepository('clients');
            $client->get($lender->id_client_owner, 'id_client');

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

            if ($clientNotifications->getNotif($lender->id_client_owner, \notifications::TYPE_LOAN_ACCEPTED, 'immediatement') == true) {
                $lenderLoans         = $loans->select('id_project = ' . $project->id_project . ' AND id_lender = ' . $lender->id_lender_account, 'id_type_contract DESC');
                $iSumMonthlyPayments = $paymentSchedule->getTotalAmount(array('id_lender' => $lender->id_lender_account, 'id_project' => $project->id_project, 'ordre' => 1));
                $aFirstPayment       = $paymentSchedule->getPremiereEcheancePreteur($project->id_project, $lender->id_lender_account);
                $sDateFirstPayment   = $aFirstPayment['date_echeance'];
                $sLoansDetails       = '';
                $sLinkExplication    = '';
                $sContract           = '';
                $sStyleTD            = 'border: 1px solid; padding: 5px; text-align: center; text-decoration:none;';

                if ($lender->isNaturalPerson($lender->id_lender_account)) {
                    $contract->get(\underlying_contract::CONTRACT_IFP, 'label');
                    $aLoanIFP               = $loans->select('id_project = ' . $project->id_project . ' AND id_lender = ' . $lender->id_lender_account . ' AND id_type_contract = ' . $contract->id_contract);
                    $iNumberOfBidsInLoanIFP = $acceptedBids->counter('id_loan = ' . $aLoanIFP[0]['id_loan']);

                    if ($iNumberOfBidsInLoanIFP > 1) {
                        $sContract        = '<br>L&rsquo;ensemble de vos offres &agrave; concurrence de 1 000 euros sont regroup&eacute;es sous la forme d&rsquo;un seul contrat de pr&ecirc;t. Son taux d&rsquo;int&eacute;r&ecirc;t correspond donc &agrave; la moyenne pond&eacute;r&eacute;e de vos <span style="color:#b20066;">' . $iNumberOfBidsInLoanIFP . ' offres de pr&ecirc;t</span>. ';
                        $sLinkExplication = '<br><br>Pour en savoir plus sur les r&egrave;gles de regroupement des offres de pr&ecirc;t, vous pouvez consulter <a style="color:#b20066;" href="' . $this->sSUrl . '/document-de-pret">cette page</a>.';
                    }
                }

                if ($acceptedBids->getDistinctBidsForLenderAndProject($lender->id_lender_account, $project->id_project) > 1) {
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
                                        <td style="' . $sStyleTD . '">' . $project->period . ' mois</td>
                                        <td style="' . $sStyleTD . '">' . $this->oFicelle->formatNumber($aFirstPayment['montant'] / 100) . ' &euro;</td>
                                        <td style="' . $sStyleTD . '">' . $sContractType . '</td></tr>';

                    if ($clientNotifications->getNotif($lender->id_client_owner, \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED, 'immediatement') == true) {
                        $clientMailNotifications->get($aLoan['id_loan'], 'id_client = ' . $lender->id_client_owner . ' AND id_loan');
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
                    'prenom_p'           => $client->prenom,
                    'nom_entreprise'     => $companies->name,
                    'offre_s'            => $sOffers,
                    'pret_s'             => $sLoans,
                    'valeur_bid'         => $this->oFicelle->formatNumber($iSumMonthlyPayments),
                    'detail_loans'       => $sLoansDetails,
                    'mensualite_p'       => $this->oFicelle->formatNumber($iSumMonthlyPayments),
                    'date_debut'         => date('d', $sTimeAdd) . ' ' . $sMonth . ' ' . date('Y', $sTimeAdd),
                    'contrat_s'          => $sContracts,
                    'compte-p'           => $this->sFUrl,
                    'projet-p'           => $this->sFUrl . '/projects/detail/' . $project->slug,
                    'lien_fb'            => $this->getFacebookLink(),
                    'lien_tw'            => $this->getTwitterLink(),
                    'motif_virement'     => $client->getLenderPattern($client->id_client),
                    'link_explication'   => $sLinkExplication,
                    'contrat_pret'       => $sContract,
                    'annee'              => date('Y')
                ];

                /** @var TemplateMessage $message */
                $message = $this->messageProvider->newMessage('preteur-contrat', $varMail);
                $message->setTo($client->email);
                $this->mailer->send($message);
            }
        }
    }

    public function sendBorrowerBill(\projects $project)
    {
        /** @var \companies $companies */
        $companies = $this->entityManagerSimulator->getRepository('companies');
        $companies->get($project->id_company, 'id_company');

        /** @var \clients $client */
        $client = $this->entityManagerSimulator->getRepository('clients');
        $client->get($companies->id_client_owner, 'id_client');

        $varMail = [
            'surl'            => $this->sSUrl,
            'url'             => $this->sFUrl,
            'prenom'          => $client->prenom,
            'entreprise'      => $companies->name,
            'pret'            => $this->oFicelle->formatNumber($project->amount),
            'projet-title'    => $project->title,
            'compte-p'        => $this->sFUrl,
            'projet-p'        => $this->sFUrl . '/projects/detail/' . $project->slug,
            'link_facture'    => $this->sFUrl . '/pdf/facture_EF/' . $client->hash . '/' . $project->id_project . '/',
            'datedelafacture' => date('d') . ' ' . $this->oDate->tableauMois['fr'][date('n')] . ' ' . date('Y'),
            'mois'            => strtolower($this->oDate->tableauMois['fr'][date('n')]),
            'annee'           => date('Y'),
            'lien_fb'         => $this->getFacebookLink(),
            'lien_tw'         => $this->getTwitterLink()
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('facture-emprunteur', $varMail);
        $message->setTo($companies->email_facture);

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

        /** @var \clients $oCustomer */
        $oCustomer = $this->entityManagerSimulator->getRepository('clients');
        /** @var \lenders_accounts $oLender */
        $oLender = $this->entityManagerSimulator->getRepository('lenders_accounts');
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
        $contract = $this->entityManagerSimulator->getRepository('underlying_contract');
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
                    $oCustomer->get($iCustomerId);
                    $oLender->get($oCustomer->id_client, 'id_client_owner');

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
                        'prenom_p'         => $oCustomer->prenom,
                        'liste_offres'     => $sLoansListHTML,
                        'link_explication' => $oLender->isNaturalPerson() ? 'Pour en savoir plus sur les r&egrave;gles de regroupement des offres de pr&ecirc;t, vous pouvez consulter <a style="color:#b20066;" href="' . $this->sSUrl . '/document-de-pret">cette page</a>. ' : '',
                        'motif_virement'   => $oCustomer->getLenderPattern($oCustomer->id_client),
                        'gestion_alertes'  => $this->sFUrl . '/profile',
                        'contenu'          => $sContent,
                        'objet'            => $sObject,
                        'sujet'            => $sSubject,
                        'lien_fb'          => $this->getFacebookLink(),
                        'lien_tw'          => $this->getTwitterLink()
                    ];

                    /** @var TemplateMessage $message */
                    $message = $this->messageProvider->newMessage($sMail, $aReplacements);
                    $message->setTo($oCustomer->email);

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

        /** @var \clients $oCustomer */
        $oCustomer = $this->entityManagerSimulator->getRepository('clients');
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
                    $oCustomer->get($iCustomerId);
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

                            /** @var \lenders_accounts $oLender */
                            $oLender = $this->entityManagerSimulator->getRepository('lenders_accounts');
                            $oLender->get($oCustomer->id_client, 'id_client_owner');

                            /** @var \loans $oLoan */
                            $oLoan = $this->entityManagerSimulator->getRepository('loans');

                            $fRepaymentCapital              = $oTransaction->montant / 100;
                            $fRepaymentInterestsTaxIncluded = 0;
                            $fRepaymentTax                  = 0;

                            $sEarlyRepaymentContent = "
                                Important : le remboursement de <span style='color: #b20066;'>" . $this->oFicelle->formatNumber($oTransaction->montant / 100) . "&nbsp;&euro;</span> correspond au remboursement total du capital restant d&ucirc; de votre pr&egrave;t &agrave; <span style='color: #b20066;'>" . htmlentities($oCompanies->name) . "</span>.
                                Comme le pr&eacute;voient les r&egrave;gles d'Unilend, <span style='color: #b20066;'>" . htmlentities($oCompanies->name) . "</span> a choisi de rembourser son emprunt par anticipation sans frais.
                                <br/><br/>
                                Depuis l'origine, il vous a vers&eacute; <span style='color: #b20066;'>" . $this->oFicelle->formatNumber($oLenderRepayment->getRepaidInterests(['id_loan' => $oTransaction->id_loan_remb])) . "&nbsp;&euro;</span> d'int&eacute;r&ecirc;ts soit un taux d'int&eacute;r&ecirc;t annualis&eacute; moyen de <span style='color: #b20066;'>" . $this->oFicelle->formatNumber($oLoan->getWeightedAverageInterestRateForLender($oLender->id_lender_account,
                                    $oProject->id_project), 1) . " %.</span><br/><br/> ";
                        } else {
                            /** @var \tax $tax */
                            $tax = $this->entityManagerSimulator->getRepository('tax');
                            $oLenderRepayment->get($oTransaction->id_echeancier);

                            $fRepaymentCapital              = bcdiv($oLenderRepayment->capital_rembourse, 100, 2);
                            $fRepaymentInterestsTaxIncluded = bcdiv($oLenderRepayment->interets_rembourses, 100, 2);
                            if (false == empty($oLenderRepayment->id_echeancier)) {
                                $fRepaymentTax = bcdiv($tax->getAmountByRepaymentId($oLenderRepayment->id_echeancier), 100, 2);
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
                        'prenom_p'               => $oCustomer->prenom,
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
                    $message->setTo($oCustomer->email);

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
     * @param \projects $projects
     */
    public function sendInternalNotificationEndOfRepayment(\projects $projects)
    {
        /** @var \companies $company */
        $company = $this->entityManagerSimulator->getRepository('companies');
        $company->get($projects->id_company);

        /** @var \settings $settings */
        $settings = $this->entityManagerSimulator->getRepository('settings');
        $settings->get('Adresse controle interne', 'type');
        $mailBO = $settings->value;

        $varMail = [
            'surl'           => $this->sSUrl,
            'url'            => $this->sFUrl,
            'nom_entreprise' => $company->name,
            'nom_projet'     => $projects->title,
            'id_projet'      => $projects->id_project,
            'annee'          => date('Y')
        ];

        /** @var TemplateMessage $messageBO */
        $messageBO = $this->messageProvider->newMessage('preteur-dernier-remboursement-controle', $varMail);
        $messageBO->setTo($mailBO);
        $this->mailer->send($messageBO);

        $this->oLogger->info('Manual repayment, Send preteur-dernier-remboursement-controle. Data to use: ' . var_export($varMail, true), ['class' => __CLASS__, 'function' => __FUNCTION__]);
    }

    /**
     * @param \projects $projects
     */
    public function sendClientNotificationEndOfRepayment(\projects $projects)
    {
        /** @var \companies $company */
        $company = $this->entityManagerSimulator->getRepository('companies');
        $company->get($projects->id_company);

        /** @var \clients $client */
        $client = $this->entityManagerSimulator->getRepository('clients');
        $client->get($company->id_client_owner);

        /** @var \transactions $transactions */
        $transactions = $this->entityManagerSimulator->getRepository('transactions');
        $transactions->get($projects->id_project . '" AND type_transaction = "' . \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT, 'id_project');

        /** @var \receptions $sfpmeiFeedIncoming */
        $sfpmeiFeedIncoming = $this->entityManagerSimulator->getRepository('receptions');
        $lastRepayment      = $sfpmeiFeedIncoming->select('id_project = ' . $projects->id_project, 'added DESC', 0, 1);

        /** @var \loans $loans */
        $loans = $this->entityManagerSimulator->getRepository('loans');

        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $varMail = [
            'surl'               => $this->sSUrl,
            'url'                => $this->sFUrl,
            'prenom'             => $client->prenom,
            'date_financement'   => \DateTime::createFromFormat('Y-m-d H:i:s', $transactions->added)->format('d/m/Y'),
            'date_remboursement' => \DateTime::createFromFormat('Y-m-d H:i:s', array_shift($lastRepayment)['added'])->format('d/m/Y'),
            'raison_sociale'     => $company->name,
            'montant'            => $ficelle->formatNumber($projects->amount, 0),
            'duree'              => $projects->period,
            'duree_financement'  => (new \DateTime($projects->date_publication))->diff(new \DateTime($projects->date_retrait))->d,
            'nb_preteurs'        => $loans->getNbPreteurs($projects->id_project),
            'lien_fb'            => $this->getFacebookLink(),
            'lien_tw'            => $this->getTwitterLink(),
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('emprunteur-dernier-remboursement', $varMail);
        $message->setTo($client->email);
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
     * @param \projects_pouvoir $proxy
     * @param \clients_mandats $mandate
     */
    public function sendProxyAndMandateSigned(\projects_pouvoir $proxy, \clients_mandats $mandate)
    {
        /** @var \projects $project */
        $project = $this->entityManagerSimulator->getRepository('projects');
        $project->get($proxy->id_project, 'id_project');
        /** @var \companies $company */
        $company = $this->entityManagerSimulator->getRepository('companies');
        $company->get($project->id_company, 'id_company');
        /** @var \clients $client */
        $client = $this->entityManagerSimulator->getRepository('clients');
        $client->get($company->id_client_owner, 'id_client');
        /** @var \settings $setting */
        $setting = $this->entityManagerSimulator->getRepository('settings');
        $setting->get('Adresse notification pouvoir mandat signe', 'type');
        $destinataire = $setting->value;

        $template = [
            '$surl'         => $this->sSUrl,
            '$id_projet'    => $project->id_project,
            '$nomProjet'    => $project->title,
            '$nomCompany'   => $company->name,
            '$lien_pouvoir' => $proxy->url_pdf,
            '$lien_mandat'  => $mandate->url_pdf
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('notification-pouvoir-mandat-signe', $template, false);
        $message->setTo(explode(';', $destinataire));
        $this->mailer->send($message);
    }
}
