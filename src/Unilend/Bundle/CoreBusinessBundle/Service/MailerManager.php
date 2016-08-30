<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage;
use \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

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

    /** @var EntityManager */
    private $oEntityManager;

    /** @var TemplateMessageProvider */
    private $messageProvider;

    /** @var \Swift_Mailer */
    private $mailer;

    public function __construct(
        ContainerInterface $container,
        EntityManager $oEntityManager,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        $defaultLanguage,
        Packages $assetsPackages,
        $schema,
        $frontHost,
        $adminHost
    ) {
        $this->container       = $container;
        $this->oEntityManager  = $oEntityManager;
        $this->messageProvider = $messageProvider;
        $this->mailer          = $mailer;

        $this->oSettings     = $this->oEntityManager->getRepository('settings');
        $this->oMailTemplate = $this->oEntityManager->getRepository('mail_templates');

        $this->oFicelle    = Loader::loadLib('ficelle');
        $this->oDate       = Loader::loadLib('dates');
        $this->oWorkingDay = Loader::loadLib('jours_ouvres');

        $this->sLanguage = $defaultLanguage;

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
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');
        /** @var \companies $oCompany */
        $oCompany = $this->oEntityManager->getRepository('companies');
        /** @var \projects $oProject */
        $oProject = $this->oEntityManager->getRepository('projects');
        /** @var \tree $oTree */
        $oTree = $this->oEntityManager->getRepository('tree');
        /** @var \bids $oBid */
        $oBid = $this->oEntityManager->getRepository('bids');

        if ($oLenderAccount->get($oNotification->id_lender) && $oBid->get($oNotification->id_bid) && $oClient->get($oLenderAccount->id_client_owner)) {
            if (empty($oBid->id_autobid)) {
                $sMailTemplate = 'confirmation-bid';
            } else {
                $sMailTemplate = 'confirmation-autobid';
            }

            $timeAdd      = strtotime($oBid->added);
            $month        = $this->oDate->tableauMois['fr'][date('n', $timeAdd)];
            $pageProjects = $oTree->getSlug(4, $this->sLanguage);

            $oProject->get($oBid->id_project);
            $oCompany->get($oProject->id_company, 'id_company');

            $varMail = array(
                'surl'           => $this->sSUrl,
                'url'            => $this->sFUrl,
                'prenom_p'       => $oClient->prenom,
                'nom_entreprise' => $oCompany->name,
                'project_name'   => $oProject->title,
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
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');
        /** @var \transactions $oTransaction */
        $oTransaction = $this->oEntityManager->getRepository('transactions');
        /** @var \companies $oCompany */
        $oCompany = $this->oEntityManager->getRepository('companies');
        /** @var \bids $oBid */
        $oBid = $this->oEntityManager->getRepository('bids');

        $aBidList = $oBid->select('id_project = ' . $oProject->id_project, 'rate ASC, added ASC');
        foreach ($aBidList as $aBid) {
            $oBid->get($aBid['id_bid']);
            $oLenderAccount->get($oBid->id_lender_account);
            $oClient->get($oLenderAccount->id_client_owner);
            if ($oClient->status == 1) {
                $oProject->get($oBid->id_project, 'id_project');
                $oCompany->get($oProject->id_company);

                $fBalance = $oTransaction->getSolde($oClient->id_client);
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
                    'motif_virement'        => $oClient->getLenderPattern($oClient->id_client),
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
        $oCompany = $this->oEntityManager->getRepository('companies');
        /** @var \clients $oBorrower */
        $oBorrower = $this->oEntityManager->getRepository('clients');

        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info(
                'Project funded - sending email to borrower (project ' . $oProject->id_project . ')',
                array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project)
            );
        }

        $oCompany->get($oProject->id_company, 'id_company');
        $oBorrower->get($oCompany->id_client_owner, 'id_client');

        if ($oBorrower->status == 1) {
            $endHour = substr($oProject->date_retrait_full, 11, 5);

            if ($endHour == '00:00') {
                $this->oSettings->get('Heure fin periode funding', 'type');
                $endHour = $this->oSettings->value;
            }

            $inter = $this->oDate->intervalDates(date('Y-m-d H:i:s'), $oProject->date_retrait . ' ' . $endHour . ':00');

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
     * @param \projects $oProject
     */
    public function sendFundedAndFinishedToBorrower(\projects $oProject)
    {
        /** @var \companies $oCompany */
        $oCompany = $this->oEntityManager->getRepository('companies');
        /** @var \clients $oBorrower */
        $oBorrower = $this->oEntityManager->getRepository('clients');
        /** @var \echeanciers_emprunteur $oBorrowerPaymentSchedule */
        $oBorrowerPaymentSchedule = $this->oEntityManager->getRepository('echeanciers_emprunteur');

        $oCompany->get($oProject->id_company, 'id_company');
        $oBorrower->get($oCompany->id_client_owner, 'id_client');

        if ($oBorrower->status == 1) {
            $fWeightedAvgRate = $this->oFicelle->formatNumber($oProject->getAverageInterestRate(), 1);

            $oBorrowerPaymentSchedule->get($oProject->id_project, 'ordre = 1 AND id_project');
            $fMonthlyPayment = $oBorrowerPaymentSchedule->montant + $oBorrowerPaymentSchedule->commission + $oBorrowerPaymentSchedule->tva;
            $fMonthlyPayment = ($fMonthlyPayment / 100);

            $sLinkMandat  = $this->sFUrl . '/pdf/mandat/' . $oBorrower->hash . '/' . $oProject->id_project;
            $sLinkPouvoir = $this->sFUrl . '/pdf/pouvoir/' . $oBorrower->hash . '/' . $oProject->id_project;

            $varMail = array(
                'surl'                   => $this->sSUrl,
                'url'                    => $this->sFUrl,
                'prenom_e'               => $oBorrower->prenom,
                'nom_e'                  => $oCompany->name,
                'mensualite'             => $this->oFicelle->formatNumber($fMonthlyPayment),
                'montant'                => $this->oFicelle->formatNumber($oProject->amount, 0),
                'taux_moyen'             => $fWeightedAvgRate,
                'link_compte_emprunteur' => $this->sFUrl . '/projects/detail/' . $oProject->id_project,
                'link_mandat'            => $sLinkMandat,
                'link_pouvoir'           => $sLinkPouvoir,
                'projet'                 => $oProject->title,
                'lien_fb'                => $this->getFacebookLink(),
                'lien_tw'                => $this->getTwitterLink()
            );

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage('emprunteur-dossier-funde-et-termine', $varMail);
            $message->setTo($oBorrower->email);
            $this->mailer->send($message);

            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->info(
                    'Email emprunteur-dossier-funde-et-termine sent (project ' . $oProject->id_project . ')',
                    array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project)
                );
            }
        }
    }

    public function sendFundedToStaff(\projects $project)
    {
        /** @var \loans $loan */
        $loan = $this->oEntityManager->getRepository('loans');

        $endHour = substr($project->date_retrait_full, 11, 5);

        if ($endHour == '00:00') {
            $this->oSettings->get('Heure fin periode funding', 'type');
            $endHour = $this->oSettings->value;
        }

        $inter = $this->oDate->intervalDates(date('Y-m-d H:i:s'), $project->date_retrait . ' ' . $endHour . ':00');

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
            '$surl'         => $this->sSUrl,
            '$id_projet'    => $project->id_project,
            '$title_projet' => $project->title,
            '$nbPeteurs'    => $loan->getNbPreteurs($project->id_project),
            '$tx'           => $this->oFicelle->formatNumber($project->getAverageInterestRate(), 1),
            '$periode'      => $remainingDuration
        );

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
        $oLoan = $this->oEntityManager->getRepository('loans');
        /** @var \companies $oCompany */
        $oCompany = $this->oEntityManager->getRepository('companies');
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');
        /** @var \echeanciers $oPaymentSchedule */
        $oPaymentSchedule = $this->oEntityManager->getRepository('echeanciers');
        /** @var \accepted_bids $oAcceptedBid */
        $oAcceptedBid = $this->oEntityManager->getRepository('accepted_bids');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');

        $aLendersIds       = $oLoan->getProjectLoansByLender($oProject->id_project);
        $iNbLenders        = count($aLendersIds);
        $iNbTreatedLenders = 0;

        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info(
                $iNbLenders . ' lenders to send email (project ' . $oProject->id_project . ')',
                array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project)
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
                    $aLoanIFP               = $oLoan->select('id_project = ' . $oProject->id_project . ' AND id_lender = ' . $oLenderAccount->id_lender_account . ' AND id_type_contract = ' . \loans::TYPE_CONTRACT_IFP);
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

                    switch ($aLoan['id_type_contract']) {
                        case \loans::TYPE_CONTRACT_BDC:
                            $sContractType = 'Bon de caisse';
                            break;
                        case \loans::TYPE_CONTRACT_IFP:
                            $sContractType = 'Contrat de pr&ecirc;t';
                            break;
                        default:
                            $sContractType = '';
                            break;
                    }
                    $sLoansDetails .= '<tr>
                                               <td style="' . $sStyleTD . '">' . $this->oFicelle->formatNumber($aLoan['amount'] / 100) . ' &euro;</td>
                                               <td style="' . $sStyleTD . '">' . $this->oFicelle->formatNumber($aLoan['rate']) . ' %</td>
                                               <td style="' . $sStyleTD . '">' . $oProject->period . ' mois</td>
                                               <td style="' . $sStyleTD . '">' . $this->oFicelle->formatNumber($aFirstPayment['montant'] / 100) . ' &euro;</td>
                                               <td style="' . $sStyleTD . '">' . $sContractType . '</td>
                                               </tr>';
                }

                $varMail = array(
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
                );

                /** @var TemplateMessage $message */
                $message = $this->messageProvider->newMessage('preteur-bid-ok', $varMail);
                $message->setTo($oClient->email);
                $this->mailer->send($message);

                if ($this->oLogger instanceof LoggerInterface) {
                    $this->oLogger->info(
                        'Email preteur-bid-ok sent for lender ' . $oLenderAccount->id_lender_account . ' (project ' . $oProject->id_project . ')',
                        array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project)
                    );
                }
            }

            $iNbTreatedLenders++;

            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->info(
                    'Loan notification emails sent to ' . $iNbTreatedLenders . '/' . $iNbLenders . ' lenders  (project ' . $oProject->id_project . ')',
                    array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project)
                );
            }
        }
    }

    public function sendBidRejected(\notifications $oNotification)
    {
        /** @var \bids $oBid */
        $oBid = $this->oEntityManager->getRepository('bids');
        /** @var \companies $oCompany */
        $oCompany = $this->oEntityManager->getRepository('companies');
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \projects $oProject */
        $oProject = $this->oEntityManager->getRepository('projects');
        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->oEntityManager->getRepository('autobid');

        $oBid->get($oNotification->id_bid);
        $oLenderAccount->get($oBid->id_lender_account);
        $oClient->get($oLenderAccount->id_client_owner);

        if ($oClient->status == 1) {
            $oProject->get($oBid->id_project);
            $oCompany->get($oProject->id_company);

            /** @var \settings $oSettings */
            $oSettings = $this->oEntityManager->getRepository('settings');
            $oEndDate  = new \DateTime($oProject->date_retrait_full);
            if ($oProject->date_fin != '0000-00-00 00:00:00') {
                $oEndDate = new \DateTime($oProject->date_fin);
            }
            if ($oEndDate->format('H') === '00') {
                $oSettings->get('Heure fin periode funding', 'type');
                $iEndHour = (int)$oSettings->value;
                $oEndDate->add(new \DateInterval('PT' . $iEndHour . 'H'));
            }

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

            $varMail = array(
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
            );

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage($sMailTemplate, $varMail);
            $message->setTo($oClient->email);
            $this->mailer->send($message);
        }
    }

    public function sendFundFailedToBorrower(\projects $oProject)
    {
        /** @var \companies $oCompany */
        $oCompany = $this->oEntityManager->getRepository('companies');
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');

        $oCompany->get($oProject->id_company, 'id_company');
        $oClient->get($oCompany->id_client_owner, 'id_client');

        if ($oClient->status == 1) {
            $varMail = array(
                'surl'     => $this->sSUrl,
                'url'      => $this->sFUrl,
                'prenom_e' => $oClient->prenom,
                'projet'   => $oProject->title,
                'lien_fb'  => $this->getFacebookLink(),
                'lien_tw'  => $this->getTwitterLink()
            );

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage('emprunteur-dossier-funding-ko', $varMail);
            $message->setTo($oClient->email);
            $this->mailer->send($message);

            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->info(
                    'Email emprunteur-dossier-funding-ko sent (project ' . $oProject->id_project . ')',
                    array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project)
                );
            }
        }
    }

    public function sendProjectFinishedToStaff(\projects $oProject)
    {
        /** @var \loans $oLoan */
        $oLoan = $this->oEntityManager->getRepository('loans');
        /** @var \companies $oCompany */
        $oCompany = $this->oEntityManager->getRepository('companies');
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');
        /** @var \bids $oBid */
        $oBid = $this->oEntityManager->getRepository('bids');

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
        $this->oMailTemplate->get('notification-projet-fini', 'locale = "' . $this->sLanguage . '" AND type');

        $varMail = array(
            '$surl'         => $this->sSUrl,
            '$url'          => $this->sFUrl,
            '$id_projet'    => $oProject->id_project,
            '$title_projet' => $oProject->title,
            '$nbPeteurs'    => $iLendersNb,
            '$montant_pret' => $oProject->amount,
            '$montant'      => $iBidTotal,
            '$sujetMail'    => htmlentities($this->oMailTemplate->subject),
            '$taux_moyen'   => $this->oFicelle->formatNumber($oProject->getAverageInterestRate(), 1)
        );
        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage($this->oMailTemplate->type, $varMail, false);
        $message->setTo(explode(';', str_replace(' ', '', $sRecipient)));
        $this->mailer->send($message);
    }

    public function sendFirstAutoBidActivation(\notifications $oNotification)
    {
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');

        $oLenderAccount->get($oNotification->id_lender);
        $oClient->get($oLenderAccount->id_client_owner, 'id_client');

        if ($oClient->status == 1) {
            $varMail = array(
                'surl'             => $this->sSUrl,
                'url'              => $this->sFUrl,
                'prenom_p'         => $oClient->prenom,
                'heure_activation' => $this->getActivationTime($oClient)->format('G\hi'),
                'motif_virement'   => $oClient->getLenderPattern($oClient->id_client),
                'lien_fb'          => $this->getFacebookLink(),
                'lien_tw'          => $this->getTwitterLink(),
                'annee'            => date('Y')
            );

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
        $oClientSettings = $this->oEntityManager->getRepository('client_settings');

        if ($oClientSettings->get($oClient->id_client, 'id_type = ' . \client_setting_type::TYPE_AUTO_BID_SWITCH . ' AND id_client')) {
            $oActivationTime = new \DateTime($oClientSettings->added);
        } else {
            $oActivationTime = new \DateTime();
        }
        return $oActivationTime;
    }

    public function sendProjectNotificationToStaff($sNotificationType, \projects $oProject, $sRecipient)
    {
        /** @var \companies $oCompanies */
        $oCompanies = $this->oEntityManager->getRepository('companies');
        $oCompanies->get($oProject->id_company, 'id_company');

        $aReplacements = array(
            '[SURL]'           => $this->sSUrl,
            '[ID_PROJET]'      => $oProject->id_project,
            '[MONTANT]'        => $oProject->amount,
            '[RAISON_SOCIALE]' => $oCompanies->name,
            '[LIEN_REPRISE]'   => $this->sAUrl . '/depot_de_dossier/reprise/' . $oProject->hash,
            '[LIEN_BO_PROJET]' => $this->sAUrl . '/dossiers/edit/' . $oProject->id_project
        );

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage($sNotificationType, $aReplacements, false);
        $message->setTo(explode(';', str_replace(' ', '', $sRecipient)));
        $this->mailer->send($message);
    }

    public function sendProjectOnlineToBorrower(\projects $oProject)
    {
        /** @var \companies $oCompanies */
        $oCompanies = $this->oEntityManager->getRepository('companies');
        /** @var \clients $oClients */
        $oClients = $this->oEntityManager->getRepository('clients');
        $oCompanies->get($oProject->id_company);

        if (false === empty($oCompanies->prenom_dirigeant) && false === empty($oCompanies->email_dirigeant)) {
            $sFirstName  = $oCompanies->prenom_dirigeant;
            $sMailClient = $oCompanies->email_dirigeant;
        } else {
            $oClients->get($oCompanies->id_client_owner);
            $sFirstName  = $oClients->prenom;
            $sMailClient = $oClients->email;
        }

        $oPublicationDate = $oProject->date_publication_full != '0000-00-00 00:00:00' ? new \DateTime($oProject->date_publication_full) : new \DateTime($oProject->date_publication);
        $oEndDate         = $oProject->date_retrait_full != '0000-00-00 00:00:00' ? new \DateTime($oProject->date_retrait_full) : $oEndDate = new \DateTime($oProject->date_retrait);

        $oFundingTime = $oPublicationDate->diff($oEndDate);
        $iFundingTime = $oFundingTime->d + ($oFundingTime->h > 0 ? 1 : 0);
        $sFundingTime = $iFundingTime . ($iFundingTime == 1 ? ' jour' : ' jours');

        $aMail = array(
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
        );

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
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        $oLenderAccount->get($iClientId, 'id_client_owner');

        $aMail = array(
            'aurl'       => $this->sAUrl,
            'id_client'  => $iClientId,
            'id_lender'  => $oLenderAccount->id_lender_account,
            'first_name' => $_SESSION['user']['firstname'],
            'name'       => $_SESSION['user']['name'],
            'user_id'    => $_SESSION['user']['id_user'],
            'old_iban'   => $sCurrentIban,
            'new_iban'   => $sNewIban
        );

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
        $loans = $this->oEntityManager->getRepository('loans');

        /** @var \companies $companies */
        $companies = $this->oEntityManager->getRepository('companies');
        $companies->get($project->id_company, 'id_company');

        /** @var \clients_gestion_notifications $clientNotifications */
        $clientNotifications = $this->oEntityManager->getRepository('clients_gestion_notifications');

        /** @var \lenders_accounts $lender */
        $lender = $this->oEntityManager->getRepository('lenders_accounts');

        $aLendersIds = $loans->getProjectLoansByLender($project->id_project);

        foreach ($aLendersIds as $lendersId) {
            $loans->get($lendersId['loans']);
            $lender->get($loans->id_lender);

            /** @var \clients $client */
            $client = $this->oEntityManager->getRepository('clients');
            $client->get($lender->id_client_owner, 'id_client');

            /** @var \echeanciers $paymentSchedule */
            $paymentSchedule = $this->oEntityManager->getRepository('echeanciers');

            /** @var \accepted_bids $acceptedBids */
            $acceptedBids = $this->oEntityManager->getRepository('accepted_bids');

            if ($clientNotifications->getNotif($lender->id_client_owner, \notifications::TYPE_LOAN_ACCEPTED, 'immediatement') == true) {
                $lenderLoans         = $loans->select('id_project = ' . $project->id_project . ' AND id_lender = ' . $lender->id_lender_account, 'id_type_contract DESC');
                $iSumMonthlyPayments = bcmul($paymentSchedule->getTotalAmount(array('id_lender' => $lender->id_lender_account, 'id_project' => $project->id_project, 'ordre' => 1)), 100);
                $aFirstPayment       = $paymentSchedule->getPremiereEcheancePreteur($project->id_project, $lender->id_lender_account);
                $sDateFirstPayment   = $aFirstPayment['date_echeance'];
                $sLoansDetails       = '';
                $sLinkExplication    = '';
                $sContract           = '';
                $sStyleTD            = 'border: 1px solid; padding: 5px; text-align: center; text-decoration:none;';

                if ($lender->isNaturalPerson($lender->id_lender_account)) {
                    $aLoanIFP               = $loans->select('id_project = ' . $project->id_project . ' AND id_lender = ' . $lender->id_lender_account . ' AND id_type_contract = ' . \loans::TYPE_CONTRACT_IFP);
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
                    switch ($aLoan['id_type_contract']) {
                        case \loans::TYPE_CONTRACT_BDC:
                            $sContractType = 'Bon de caisse';
                            break;
                        case \loans::TYPE_CONTRACT_IFP:
                            $sContractType = 'Contrat de pr&ecirc;t';
                            break;
                        default:
                            $sContractType = '';
                            break;
                    }

                    $sLoansDetails .= '<tr>
                                        <td style="' . $sStyleTD . '">' . $this->oFicelle->formatNumber($aLoan['amount'] / 100) . ' &euro;</td>
                                        <td style="' . $sStyleTD . '">' . $this->oFicelle->formatNumber($aLoan['rate']) . ' %</td>
                                        <td style="' . $sStyleTD . '">' . $project->period . ' mois</td>
                                        <td style="' . $sStyleTD . '">' . $this->oFicelle->formatNumber($aFirstPayment['montant'] / 100) . ' &euro;</td>
                                        <td style="' . $sStyleTD . '">' . $sContractType . '</td></tr>';

                    if ($clientNotifications->getNotif($lender->id_client_owner, 4, 'immediatement') == true) {
                        /** @var \clients_gestion_mails_notif $clientMailNotifications */
                        $clientMailNotifications = $this->oEntityManager->getRepository('clients_gestion_mails_notif');
                        $clientMailNotifications->get($aLoan['id_loan'], 'id_client = ' . $lender->id_client_owner . ' AND id_loan');
                        $clientMailNotifications->immediatement = 1;
                        $clientMailNotifications->update();
                    }
                }

                $sTimeAdd = strtotime($sDateFirstPayment);
                $sMonth   = $this->oDate->tableauMois['fr'][date('n', $sTimeAdd)];

                $varMail = array(
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
                );

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
        $companies = $this->oEntityManager->getRepository('companies');
        $companies->get($project->id_company, 'id_company');

        /** @var \clients $client */
        $client = $this->oEntityManager->getRepository('clients');
        $client->get($companies->id_client_owner, 'id_client');

        $varMail = array(
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
        );

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
            $this->oLogger->debug('New projects notifications start', array('class' => __CLASS__, 'function' => __FUNCTION__));
            $this->oLogger->debug('Number of customers to process: ' . count($aCustomerId), array('class' => __CLASS__, 'function' => __FUNCTION__));
        }

        /** @var \clients $oCustomer */
        $oCustomer = $this->oEntityManager->getRepository('clients');
        /** @var \projects $oProject */
        $oProject = $this->oEntityManager->getRepository('projects');
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = $this->oEntityManager->getRepository('clients_gestion_mails_notif');
        /** @var \clients_gestion_notifications $oCustomerNotificationSettings */
        $oCustomerNotificationSettings = $this->oEntityManager->getRepository('clients_gestion_notifications');
        /** @var \textes $translations */
        $translations                    = $this->oEntityManager->getRepository('textes');
        $aTranslations['email-synthese'] = $translations->selectFront('email-synthese', 'fr');

        /** @var \clients_gestion_notif_log $oNotificationsLog */
        $oNotificationsLog           = $this->oEntityManager->getRepository('clients_gestion_notif_log');
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
            $aCustomerMailNotifications = array();
            foreach ($oCustomerNotificationSettings->getCustomersNotifications($aPartialCustomerId, $sFrequency, \clients_gestion_type_notif::TYPE_NEW_PROJECT) as $aMailNotifications) {
                $aCustomerMailNotifications[$aMailNotifications['id_client']][] = $aMailNotifications;
            }

            foreach ($aCustomerMailNotifications as $iCustomerId => $aMailNotifications) {
                try {
                    $sProjectsListHTML = '';
                    $iProjectsCount    = count($aMailNotifications);

                    foreach ($aMailNotifications as $aMailNotification) {
                        $oMailNotification->get($aMailNotification['id_clients_gestion_mails_notif']);
                        $oMailNotification->{$sFrequency}                   = 1;
                        $oMailNotification->{'status_check_' . $sFrequency} = 1;
                        $oMailNotification->update();

                        $oProject->get($aMailNotification['id_project']);

                        $oProject->get($aMailNotification['id_project']);

                        /** @var \projects_status $oProjectStatus */
                        $oProjectStatus = $this->loadData('projects_status');
                        $oProjectStatus->getLastStatut($oProject->id_project);

                        if (\projects_status::EN_FUNDING == $oProjectStatus->status) {
                            $sProjectsListHTML .= '
                                <tr style="color:#b20066;">
                                    <td  style="font-family:Arial;font-size:14px;height: 25px;">
                                       <a style="color:#b20066;text-decoration:none;font-family:Arial;" href="' . $this->lurl . '/projects/detail/' . $oProject->slug . '">' . $oProject->title . '</a>
                                    </td>
                                    <td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber($oProject->amount, 0) . '&nbsp;&euro;</td>
                                    <td align="right" style="font-family:Arial;font-size:14px;">' . $oProject->period . ' mois</td>
                                </tr>';
                            $iProjectsCount += 1;
                        }
                    }

                    if ($iProjectsCount >= 1) {
                        $oCustomer->get($iCustomerId);

                        if (1 === $iProjectsCount && 'quotidienne' === $sFrequency) {
                            $sContent = $aTranslations['email-synthese']['contenu-synthese-nouveau-projet-du-jour-singulier'];
                            $sObject  = $aTranslations['email-synthese']['objet-synthese-nouveau-projet-du-jour-singulier'];
                            $sSubject = $aTranslations['email-synthese']['sujet-nouveau-projet-du-jour-singulier'];
                        } elseif (1 < $iProjectsCount && 'quotidienne' === $sFrequency) {
                            $sContent = $aTranslations['email-synthese']['contenu-synthese-nouveau-projet-du-jour-pluriel'];
                            $sObject  = $aTranslations['email-synthese']['objet-synthese-nouveau-projet-du-jour-pluriel'];
                            $sSubject = $aTranslations['email-synthese']['sujet-nouveau-projet-du-jour-pluriel'];
                        } elseif (1 === $iProjectsCount && 'hebdomadaire' === $sFrequency) {
                            $sContent = $aTranslations['email-synthese']['contenu-synthese-nouveau-projet-hebdomadaire-singulier'];
                            $sObject  = $aTranslations['email-synthese']['objet-synthese-nouveau-projet-hebdomadaire-singulier'];
                            $sSubject = $aTranslations['email-synthese']['sujet-nouveau-projet-hebdomadaire-singulier'];
                        } elseif (1 < $iProjectsCount && 'hebdomadaire' === $sFrequency) {
                            $sContent = $aTranslations['email-synthese']['contenu-synthese-nouveau-projet-hebdomadaire-pluriel'];
                            $sObject  = $aTranslations['email-synthese']['objet-synthese-nouveau-projet-hebdomadaire-pluriel'];
                            $sSubject = $aTranslations['email-synthese']['sujet-nouveau-projet-hebdomadaire-pluriel'];
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
     * Send accepted bids summary email
     *
     * @param array  $aCustomerId
     * @param string $sFrequency
     */
    public function sendPlacedBidsSummaryEmail(array $aCustomerId, $sFrequency)
    {
        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->debug('Placed bids notifications start', array('class' => __CLASS__, 'function' => __FUNCTION__));
            $this->oLogger->debug('Number of customer to process: ' . count($aCustomerId), array('class' => __CLASS__, 'function' => __FUNCTION__));
        }

        /** @var \bids $oBid */
        $oBid = $this->oEntityManager->getRepository('bids');
        /** @var \clients $oCustomer */
        $oCustomer = $this->oEntityManager->getRepository('clients');
        /** @var \notifications $oNotification */
        $oNotification = $this->oEntityManager->getRepository('notifications');
        /** @var \projects $oProject */
        $oProject = $this->oEntityManager->getRepository('projects');
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = $this->oEntityManager->getRepository('clients_gestion_mails_notif');
        /** @var \clients_gestion_notifications $oCustomerNotificationSettings */
        $oCustomerNotificationSettings = $this->oEntityManager->getRepository('clients_gestion_notifications');
        /** @var \textes $translations */
        $translations                    = $this->oEntityManager->getRepository('textes');
        $aTranslations['email-synthese'] = $translations->selectFront('email-synthese', 'fr');

        /** @var \clients_gestion_notif_log $oNotificationsLog */
        $oNotificationsLog           = $this->oEntityManager->getRepository('clients_gestion_notif_log');
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
                        $sContent = $aTranslations['email-synthese']['contenu-synthese-offre-placee-quotidienne-singulier'];
                        $sObject  = $aTranslations['email-synthese']['objet-synthese-offre-placee-quotidienne-singulier'];
                        $sSubject = $aTranslations['email-synthese']['sujet-synthese-quotidienne-offre-placee-singulier'];
                    } elseif (1 < $iPlacedBidsCount && 'quotidienne' === $sFrequency) {
                        $sContent = $aTranslations['email-synthese']['contenu-synthese-offre-placee-quotidienne-pluriel'];
                        $sObject  = $aTranslations['email-synthese']['objet-synthese-offre-placee-quotidienne-pluriel'];
                        $sSubject = $aTranslations['email-synthese']['sujet-synthese-quotidienne-offre-placee-pluriel'];
                    } else {
                        trigger_error('Frequency and number of placed bids not handled: ' . $sFrequency . ' / ' . $iPlacedBidsCount, E_USER_WARNING);
                        continue;
                    }

                    $aReplacements = array(
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
                    );

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
        $oBid = $this->oEntityManager->getRepository('bids');
        /** @var \clients $oCustomer */
        $oCustomer = $this->oEntityManager->getRepository('clients');
        /** @var \notifications $oNotification */
        $oNotification = $this->oEntityManager->getRepository('notifications');
        /** @var \projects $oProject */
        $oProject = $this->oEntityManager->getRepository('projects');
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = $this->oEntityManager->getRepository('clients_gestion_mails_notif');
        /** @var \clients_gestion_notifications $oCustomerNotificationSettings */
        $oCustomerNotificationSettings = $this->oEntityManager->getRepository('clients_gestion_notifications');
        /** @var \textes $translations */
        $translations                    = $this->oEntityManager->getRepository('textes');
        $aTranslations['email-synthese'] = $translations->selectFront('email-synthese', 'fr');

        /** @var \clients_gestion_notif_log $oNotificationsLog */
        $oNotificationsLog           = $this->oEntityManager->getRepository('clients_gestion_notif_log');
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
            $aCustomerMailNotifications = array();
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
                        $sContent = $aTranslations['email-synthese']['contenu-synthese-offres-refusees-quotidienne-singulier'];
                        $sObject  = $aTranslations['email-synthese']['objet-synthese-offres-refusees-quotidienne-singulier'];
                        $sSubject = $aTranslations['email-synthese']['sujet-synthese-offres-refusees-quotidienne-singulier'];
                    } elseif (1 < $iRejectedBidsCount && 'quotidienne' === $sFrequency) {
                        $sContent = $aTranslations['email-synthese']['contenu-synthese-offres-refusees-quotidienne-pluriel'];
                        $sObject  = $aTranslations['email-synthese']['objet-synthese-offres-refusees-quotidienne-pluriel'];
                        $sSubject = $aTranslations['email-synthese']['sujet-synthese-offres-refusees-quotidienne-pluriel'];
                    } else {
                        trigger_error('Frequency and number of rejected bids not handled: ' . $sFrequency . ' / ' . $iRejectedBidsCount, E_USER_WARNING);
                        continue;
                    }

                    $aReplacements = array(
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
                    );

                    /** @var TemplateMessage $message */
                    $message = $this->messageProvider->newMessage($sMail, $aReplacements);
                    $message->setTo($oCustomer->email);

                    $this->mailer->send($message);
                } catch (\Exception $oException) {
                    if ($this->oLogger instanceof LoggerInterface) {
                        $this->oLogger->error(
                            'Could not send rejected bids summary email for customer ' . $iCustomerId . ' - Message: ' . $oException->getMessage(),
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
     * Send accepted loans summary email
     *
     * @param array  $aCustomerId
     * @param string $sFrequency
     */
    public function sendAcceptedLoansSummaryEmail(array $aCustomerId, $sFrequency)
    {
        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->debug('Accepted loans notifications start', array('class' => __CLASS__, 'function' => __FUNCTION__));
            $this->oLogger->debug('Number of customer to process: ' . count($aCustomerId), array('class' => __CLASS__, 'function' => __FUNCTION__));
        }

        /** @var \clients $oCustomer */
        $oCustomer = $this->oEntityManager->getRepository('clients');
        /** @var \lenders_accounts $oLender */
        $oLender = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \loans $oLoan */
        $oLoan = $this->oEntityManager->getRepository('loans');
        /** @var \notifications $oNotification */
        $oNotification = $this->oEntityManager->getRepository('notifications');
        /** @var \projects $oProject */
        $oProject = $this->oEntityManager->getRepository('projects');
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = $this->oEntityManager->getRepository('clients_gestion_mails_notif');
        /** @var \clients_gestion_notifications $oCustomerNotificationSettings */
        $oCustomerNotificationSettings = $this->oEntityManager->getRepository('clients_gestion_notifications');

        /** @var \textes $translations */
        $translations                    = $this->oEntityManager->getRepository('textes');
        $aTranslations['email-synthese'] = $translations->selectFront('email-synthese', 'fr');

        /** @var \clients_gestion_notif_log $oNotificationsLog */
        $oNotificationsLog           = $this->oEntityManager->getRepository('clients_gestion_notif_log');
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
            $aCustomerMailNotifications = array();
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

                        switch ($oLoan->id_type_contract) {
                            case \loans::TYPE_CONTRACT_BDC:
                                $sContractType = 'Bon de caisse';
                                break;
                            case \loans::TYPE_CONTRACT_IFP:
                                $sContractType = 'Contrat de pr&ecirc;t';
                                break;
                            default:
                                $sContractType = '';
                                trigger_error('Unknown contract type: ' . $oLoan->id_type_contract, E_USER_WARNING);
                                break;
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
                        $sContent = $aTranslations['email-synthese']['contenu-synthese-quotidienne-offres-acceptees-singulier'];
                        $sObject  = $aTranslations['email-synthese']['objet-synthese-quotidienne-offres-acceptees-singulier'];
                        $sSubject = $aTranslations['email-synthese']['sujet-synthese-quotidienne-offres-acceptees-singulier'];
                    } elseif (1 < $iAcceptedLoansCount && 'quotidienne' === $sFrequency) {
                        $sContent = $aTranslations['email-synthese']['contenu-synthese-quotidienne-offres-acceptees-pluriel'];
                        $sObject  = $aTranslations['email-synthese']['objet-synthese-quotidienne-offres-acceptees-pluriel'];
                        $sSubject = $aTranslations['email-synthese']['sujet-synthese-quotidienne-offres-acceptees-pluriel'];
                    } elseif (1 === $iAcceptedLoansCount && 'hebdomadaire' === $sFrequency) {
                        $sContent = $aTranslations['email-synthese']['contenu-synthese-hebdomadaire-offres-acceptees-singulier'];
                        $sObject  = $aTranslations['email-synthese']['objet-synthese-hebdomadaire-offres-acceptees-singulier'];
                        $sSubject = $aTranslations['email-synthese']['sujet-synthese-hebdomadaire-offres-acceptees-singulier'];
                    } elseif (1 < $iAcceptedLoansCount && 'hebdomadaire' === $sFrequency) {
                        $sContent = $aTranslations['email-synthese']['contenu-synthese-hebdomadaire-offres-acceptees-pluriel'];
                        $sObject  = $aTranslations['email-synthese']['objet-synthese-hebdomadaire-offres-acceptees-pluriel'];
                        $sSubject = $aTranslations['email-synthese']['sujet-synthese-hebdomadaire-offres-acceptees-pluriel'];
                    } elseif (1 === $iAcceptedLoansCount && 'mensuelle' === $sFrequency) {
                        $sContent = $aTranslations['email-synthese']['contenu-synthese-mensuelle-offres-acceptees-singulier'];
                        $sObject  = $aTranslations['email-synthese']['objet-synthese-mensuelle-offres-acceptees-singulier'];
                        $sSubject = $aTranslations['email-synthese']['sujet-synthese-mensuelle-offres-acceptees-singulier'];
                    } elseif (1 < $iAcceptedLoansCount && 'mensuelle' === $sFrequency) {
                        $sContent = $aTranslations['email-synthese']['contenu-synthese-mensuelle-offres-acceptees-pluriel'];
                        $sObject  = $aTranslations['email-synthese']['objet-synthese-mensuelle-offres-acceptees-pluriel'];
                        $sSubject = $aTranslations['email-synthese']['sujet-synthese-mensuelle-offres-acceptees-pluriel'];
                    } else {
                        trigger_error('Frequency and number of accepted loans not handled: ' . $sFrequency . ' / ' . $iAcceptedLoansCount, E_USER_WARNING);
                        continue;
                    }

                    $aReplacements = array(
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
                    );

                    /** @var TemplateMessage $message */
                    $message = $this->messageProvider->newMessage($sMail, $aReplacements);
                    $message->setTo($oCustomer->email);

                    $this->mailer->send($message);
                } catch (\Exception $oException) {
                    if ($this->oLogger instanceof LoggerInterface) {
                        $this->oLogger->error(
                            'Could not send accepted loan summary email for customer ' . $iCustomerId . ' - Message: ' . $oException->getMessage(),
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
     * Send repayment summary email
     *
     * @param array  $aCustomerId
     * @param string $sFrequency
     */
    public function sendRepaymentsSummaryEmail(array $aCustomerId, $sFrequency)
    {
        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->debug('Repayments notifications start', array('class' => __CLASS__, 'function' => __FUNCTION__));
            $this->oLogger->debug('Number of customer to process: ' . count($aCustomerId), array('class' => __CLASS__, 'function' => __FUNCTION__));
        }

        /** @var \clients $oCustomer */
        $oCustomer = $this->oEntityManager->getRepository('clients');
        /** @var \echeanciers $oLenderRepayment */
        $oLenderRepayment = $this->oEntityManager->getRepository('echeanciers');
        /** @var \notifications $oNotification */
        $oNotification = $this->oEntityManager->getRepository('notifications');
        /** @var \projects $oProject */
        $oProject = $this->oEntityManager->getRepository('projects');
        /** @var \transactions $oTransaction */
        $oTransaction = $this->oEntityManager->getRepository('transactions');
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = $this->oEntityManager->getRepository('clients_gestion_mails_notif');
        /** @var \clients_gestion_notifications $oCustomerNotificationSettings */
        $oCustomerNotificationSettings = $this->oEntityManager->getRepository('clients_gestion_notifications');

        /** @var \textes $translations */
        $translations                    = $this->oEntityManager->getRepository('textes');
        $aTranslations['email-synthese'] = $translations->selectFront('email-synthese', 'fr');

        /** @var \clients_gestion_notif_log $oNotificationsLog */
        $oNotificationsLog           = $this->oEntityManager->getRepository('clients_gestion_notif_log');
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
                $this->oLogger->debug('Customer mail notifications: ' . var_export($aCustomerMailNotifications, true) , array('class' => __CLASS__, 'function' => __FUNCTION__));
            }

            foreach ($aCustomerMailNotifications as $iCustomerId => $aMailNotifications) {
                try {
                    $oCustomer->get($iCustomerId);

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
                            $oCompanies = $this->oEntityManager->getRepository('companies');
                            $oCompanies->get($oProject->id_company);

                            /** @var \lenders_accounts $oLender */
                            $oLender = $this->oEntityManager->getRepository('lenders_accounts');
                            $oLender->get($oCustomer->id_client, 'id_client_owner');

                            /** @var \loans $oLoan */
                            $oLoan = $this->oEntityManager->getRepository('loans');

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
                            $tax = $this->oEntityManager->getRepository('tax');
                            $oLenderRepayment->get($oTransaction->id_echeancier);

                            $fRepaymentCapital              = bcdiv($oLenderRepayment->capital, 100, 2);
                            $fRepaymentInterestsTaxIncluded = bcdiv($oLenderRepayment->interets, 100, 2);
                            $fRepaymentTax                  = bcdiv($tax->getAmountByRepaymentId($oLenderRepayment->id_echeancier), 100, 2);
                            $fRepaymentAmount               = bcsub(bcadd($fRepaymentCapital, $fRepaymentInterestsTaxIncluded, 2), $fRepaymentTax, 2);
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
                        $sSubject = $aTranslations['email-synthese']['sujet-synthese-quotidienne-singulier'];
                        $sContent = $aTranslations['email-synthese']['contenu-synthese-quotidienne-singulier'];
                    } elseif (1 < $iRepaymentsCount && 'quotidienne' === $sFrequency) {
                        $sSubject = $aTranslations['email-synthese']['sujet-synthese-quotidienne-pluriel'];
                        $sContent = $aTranslations['email-synthese']['contenu-synthese-quotidienne-pluriel'];
                    } elseif (1 === $iRepaymentsCount && 'hebdomadaire' === $sFrequency) {
                        $sSubject = $aTranslations['email-synthese']['sujet-synthese-hebdomadaire-singulier'];
                        $sContent = $aTranslations['email-synthese']['contenu-synthese-quotidienne-singulier'];
                    } elseif (1 < $iRepaymentsCount && 'hebdomadaire' === $sFrequency) {
                        $sSubject = $aTranslations['email-synthese']['sujet-synthese-hebdomadaire-pluriel'];
                        $sContent = $aTranslations['email-synthese']['contenu-synthese-hebdomadaire-pluriel'];
                    } elseif (1 === $iRepaymentsCount && 'mensuelle' === $sFrequency) {
                        $sSubject = $aTranslations['email-synthese']['sujet-synthese-mensuelle-singulier'];
                        $sContent = $aTranslations['email-synthese']['contenu-synthese-quotidienne-singulier'];
                    } elseif (1 < $iRepaymentsCount && 'mensuelle' === $sFrequency) {
                        $sSubject = $aTranslations['email-synthese']['sujet-synthese-mensuelle-pluriel'];
                        $sContent = $aTranslations['email-synthese']['contenu-synthese-mensuelle-pluriel'];
                    } else {
                        trigger_error('Frequency and number of repayments not handled: ' . $sFrequency . ' / ' . $iRepaymentsCount, E_USER_WARNING);
                        continue;
                    }

                    $aReplacements = array(
                        'surl'                   => $this->sSUrl,
                        'url'                    => $this->sFUrl,
                        'prenom_p'               => $oCustomer->prenom,
                        'liste_offres'           => $sRepaymentsListHTML,
                        'motif_virement'         => $oCustomer->getLenderPattern($oCustomer->id_client),
                        'gestion_alertes'        => $this->sFUrl . '/profile',
                        'montant_dispo'          => $this->oFicelle->formatNumber($oTransaction->getSolde($oCustomer->id_client)),
                        'remboursement_anticipe' => $sEarlyRepaymentContent,
                        'contenu'                => $sContent,
                        'sujet'                  => $sSubject,
                        'lien_fb'                => $this->getFacebookLink(),
                        'lien_tw'                => $this->getTwitterLink(),
                        'annee'                  => date('Y')
                    );

                    /** @var TemplateMessage $message */
                    $message = $this->messageProvider->newMessage($sMail, $aReplacements);
                    $message->setTo($oCustomer->email);

                    $this->mailer->send($message);
                } catch (\Exception $oException) {
                    if ($this->oLogger instanceof LoggerInterface) {
                        $this->oLogger->error(
                            'Could not repayments summary send email for customer ' . $iCustomerId . ' - Message: ' . $oException->getMessage(),
                            array('class' => __CLASS__, 'function' => __FUNCTION__)
                        );
                    }
                }
            }
        }

        $oNotificationsLog->fin = date('Y-m-d H:i:s');
        $oNotificationsLog->update();
    }
}
