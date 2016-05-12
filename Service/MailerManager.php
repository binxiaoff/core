<?php
namespace Unilend\Service;

use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage;
use \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;
use Unilend\core\Loader;
use Unilend\librairies\ULogger;
use Unilend\Service\Simulator\EntityManager;

class MailerManager
{
    /** @var \settings */
    private $oSettings;

    /** @var \mails_text */
    private $oMailText;

    /** @var ULogger */
    private $oLogger;

    /** @var \ficelle */
    private $oFicelle;

    /** @var array */
    private $aConfig;

    /** @var \dates */
    private $oDate;

    /** @var \jours_ouvres */
    private $oWorkingDay;
    private $sSUrl;
    private $sLUrl;
    private $sFUrl;

    /** @var EntityManager  */
    private $oEntityManager;

    /** @var TemplateMessageProvider */
    private $messageProvider;

    /** @var \Swift_Mailer */
    private $mailer;

    public function __construct(EntityManager $oEntityManager, TemplateMessageProvider $messageProvider, \Swift_Mailer $mailer)
    {
        $this->aConfig = Loader::loadConfig();

        $this->oEntityManager  = $oEntityManager;
        $this->messageProvider = $messageProvider;
        $this->mailer          = $mailer;

        $this->oSettings  = $this->oEntityManager->getRepository('settings');
        $this->oMailText  = $this->oEntityManager->getRepository('mails_text');

        $this->oFicelle    = Loader::loadLib('ficelle');
        $this->oDate       = Loader::loadLib('dates');
        $this->oWorkingDay = Loader::loadLib('jours_ouvres');

        $this->sLanguage = 'fr';

        $this->sSUrl = $this->aConfig['static_url'][$this->aConfig['env']];
        $this->sLUrl = $this->aConfig['url'][$this->aConfig['env']]['default'] . ($this->aConfig['multilanguage']['enabled'] ? '/' . $this->sLanguage : '');
        $this->sFUrl = $this->aConfig['url'][$this->aConfig['env']]['default'];

    }

    /**
     * @param ULogger $oLogger
     */
    public function setLogger(ULogger $oLogger)
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
                'url'            => $this->sLUrl,
                'prenom_p'       => $oClient->prenom,
                'nom_entreprise' => $oCompany->name,
                'project_name'   => $oProject->title,
                'valeur_bid'     => $this->oFicelle->formatNumber($oBid->amount / 100),
                'taux_bid'       => $this->oFicelle->formatNumber($oBid->rate, 1),
                'date_bid'       => date('d', $timeAdd) . ' ' . $month . ' ' . date('Y', $timeAdd),
                'heure_bid'      => date('H:i:s', strtotime($oBid->added)),
                'projet-p'       => $this->sLUrl . '/' . $pageProjects,
                'autobid_link'   => $this->sLUrl . '/profile/autolend#parametrage',
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
                    'url'                   => $this->sLUrl,
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
        /** @var \bids $oBid */
        $oBid = $this->oEntityManager->getRepository('bids');
        /** @var \companies $oCompany */
        $oCompany = $this->oEntityManager->getRepository('companies');
        /** @var \clients $oBorrower */
        $oBorrower = $this->oEntityManager->getRepository('clients');

        // EMAIL EMPRUNTEUR FUNDE //
        if ($this->oLogger instanceof ULogger) {
            $this->oLogger->addRecord(ULogger::INFO, 'Project funded - send email to borrower', array('Project ID' => $oProject->id_project));
        }

        $this->oSettings->get('Heure fin periode funding', 'type');
        $iFinFunding = $this->oSettings->value;

        $oCompany->get($oProject->id_company, 'id_company');
        $oBorrower->get($oCompany->id_client_owner, 'id_client');

        $tab_date_retrait = explode(' ', $oProject->date_retrait_full);
        $tab_date_retrait = explode(':', $tab_date_retrait[1]);
        $heure_retrait    = $tab_date_retrait[0] . ':' . $tab_date_retrait[1];

        if ($heure_retrait == '00:00') {
            $heure_retrait = $iFinFunding;
        }

        $inter = $this->oDate->intervalDates(date('Y-m-d H:i:s'), $oProject->date_retrait . ' ' . $heure_retrait . ':00');

        if ($inter['mois'] > 0) {
            $tempsRest = $inter['mois'] . ' mois';
        } elseif ($inter['jours'] > 0) {
            $tempsRest = $inter['jours'] . ' jours';
        } elseif ($inter['heures'] > 0 && $inter['minutes'] >= 120) {
            $tempsRest = $inter['heures'] . ' heures';
        } elseif ($inter['minutes'] > 0 && $inter['minutes'] < 120) {
            $tempsRest = $inter['minutes'] . ' min';
        } else {
            $tempsRest = $inter['secondes'] . ' secondes';
        }

        // Taux moyen pondéré
        $fWeightedAvgRate = $this->oFicelle->formatNumber($oProject->getAverageInterestRate());

        // Pas de mail si le compte est desactivé
        if ($oBorrower->status == 1) {
            //*** ENVOI DU MAIL FUNDE EMPRUNTEUR ***//
            $varMail = array(
                'surl'          => $this->sSUrl,
                'url'           => $this->sLUrl,
                'prenom_e'      => utf8_decode($oBorrower->prenom),
                'taux_moyen'    => $fWeightedAvgRate,
                'temps_restant' => $tempsRest,
                'projet'        => $oProject->title,
                'lien_fb'       => $this->getFacebookLink(),
                'lien_tw'       => $this->getTwitterLink()
            );

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage('emprunteur-dossier-funde', $varMail);
            $message->setTo($oBorrower->email);
            $this->mailer->send($message);
        }
        //*** ENVOI DU MAIL NOTIFICATION FUNDE 100% ***//

        $this->oSettings->get('Adresse notification projet funde a 100', 'type');
        $destinataire = $this->oSettings->value;

        $varMail = array(
            '$surl'         => $this->sSUrl,
            '$url'          => $this->sLUrl,
            '$id_projet'    => $oProject->title,
            '$title_projet' => utf8_decode($oProject->title),
            '$nbPeteurs'    => $oBid->getNbPreteurs($oProject->id_project),
            '$tx'           => $fWeightedAvgRate,
            '$periode'      => $tempsRest
        );

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('notification-projet-funde-a-100', $varMail, false);
        $message->setTo($destinataire);
        $this->mailer->send($message);
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
            $fWeightedAvgRate = $this->oFicelle->formatNumber($oProject->getAverageInterestRate());

            $oBorrowerPaymentSchedule->get($oProject->id_project, 'ordre = 1 AND id_project');
            $fMonthlyPayment = $oBorrowerPaymentSchedule->montant + $oBorrowerPaymentSchedule->commission + $oBorrowerPaymentSchedule->tva;
            $fMonthlyPayment = ($fMonthlyPayment / 100);

            $sLinkMandat  = $this->sLUrl . '/pdf/mandat/' . $oBorrower->hash . '/' . $oProject->id_project;
            $sLinkPouvoir = $this->sLUrl . '/pdf/pouvoir/' . $oBorrower->hash . '/' . $oProject->id_project;

            $varMail = array(
                'surl'                   => $this->sSUrl,
                'url'                    => $this->sLUrl,
                'prenom_e'               => $oBorrower->prenom,
                'nom_e'                  => $oCompany->name,
                'mensualite'             => $this->oFicelle->formatNumber($fMonthlyPayment),
                'montant'                => $this->oFicelle->formatNumber($oProject->amount, 0),
                'taux_moyen'             => $fWeightedAvgRate,
                'link_compte_emprunteur' => $this->sLUrl . '/projects/detail/' . $oProject->id_project,
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

            if ($this->oLogger instanceof ULogger) {
                $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' : email emprunteur-dossier-funde-et-termine sent');
            }
        }
    }

    public function sendFundedToStaff(\projects $oProject)
    {
        /** @var \companies $oCompany */
        $oCompany = $this->oEntityManager->getRepository('companies');
        /** @var \bids $oBid */
        $oBid = $this->oEntityManager->getRepository('bids');
        /** @var \loans $oLoan */
        $oLoan = $this->oEntityManager->getRepository('loans');

        $oCompany->get($oProject->id_company, 'id_company');
        $this->oSettings->get('Adresse notification projet funde a 100', 'type');
        $sRecipient       = $this->oSettings->value;
        $fWeightedAvgRate = $this->oFicelle->formatNumber($oProject->getAverageInterestRate());
        $iBidTotal        = $oBid->getSoldeBid($oProject->id_project);
        if ($iBidTotal > $oProject->amount) {
            $iBidTotal = $oProject->amount;
        }

        $iLenderNb = $oLoan->getNbPreteurs($oProject->id_project);

        $varMail = array(
            '$surl'         => $this->sSUrl,
            '$url'          => $this->sLUrl,
            '$id_projet'    => $oProject->id_project,
            '$title_projet' => utf8_decode($oProject->title),
            '$nbPeteurs'    => $iLenderNb,
            '$tx'           => $fWeightedAvgRate,
            '$montant_pret' => $oProject->amount,
            '$montant'      => $iBidTotal,
            '$periode'      => $oProject->period
        );

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('notification-projet-funde-a-100', $varMail, false);
        $message->setTo($sRecipient);
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
        if ($this->oLogger instanceof ULogger) {
            $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $iNbLenders . ' lenders to send email');
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

                        $sLinkExplication = '<br><br>Pour en savoir plus sur les r&egrave;gles de regroupement des offres de pr&ecirc;t, vous pouvez consulter <a style="color:#b20066;" href="' . $this->surl . '/document-de-pret">cette page</a>.';
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
                    'url'                   => $this->sLUrl,
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

                if ($this->oLogger instanceof ULogger) {
                    $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' : email preteur-bid-ok sent for lender (' . $oLenderAccount->id_lender_account . ')');
                }
            }
            $iNbTreatedLenders++;
            if ($this->oLogger instanceof ULogger) {
                $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' : ' . $iNbTreatedLenders . '/' . $iNbLenders . ' loan notification mail sent');
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

            if ($bIsAutoBid) {
                $oAutoBid->get($oBid->id_autobid);

                if ($oEndDate <= $oNow) {
                    $sMailTemplate = 'preteur-autobid-ko-apres-fin-de-periode-projet';
                } else {
                    $sMailTemplate = 'preteur-autobid-ko';
                }
            } else {
                if ($oEndDate <= $oNow) {
                    $sMailTemplate = 'preteur-bid-ko-apres-fin-de-periode-projet';
                } else {
                    $sMailTemplate = 'preteur-bid-ko';
                }
            }
            $iAddedBid = strtotime($oBid->added);
            $sMonthFr  = $this->oDate->tableauMois['fr'][date('n', $iAddedBid)];

            $varMail = array(
                'surl'             => $this->sSUrl,
                'url'              => $this->sLUrl,
                'prenom_p'         => $oClient->prenom,
                'valeur_bid'       => $this->oFicelle->formatNumber($oBid->amount / 100),
                'taux_bid'         => $this->oFicelle->formatNumber($oBid->rate),
                'autobid_rate_min' => $oAutoBid->rate_min,
                'nom_entreprise'   => $oCompany->name,
                'projet-p'         => $this->sLUrl . '/projects/detail/' . $oProject->slug,
                'date_bid'         => date('d', $iAddedBid) . ' ' . $sMonthFr . ' ' . date('Y', $iAddedBid),
                'heure_bid'        => $this->oDate->formatDate($oBid->added, 'H\hi'),
                'fin_chrono'       => $sInterval,
                'projet-bid'       => $this->sLUrl . '/projects/detail/' . $oProject->slug,
                'autobid_link'     => $this->sLUrl . '/profile/autolend#parametrage',
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
                'url'      => $this->sLUrl,
                'prenom_e' => $oClient->prenom,
                'projet'   => $oProject->title,
                'lien_fb'  => $this->getFacebookLink(),
                'lien_tw'  => $this->getTwitterLink()
            );

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage('emprunteur-dossier-funding-ko', $varMail);
            $message->setTo($oClient->email);
            $this->mailer->send($message);

            if ($this->oLogger instanceof ULogger) {
                $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' : email emprunteur-dossier-funding-ko sent');
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
        $this->oMailText->get('notification-projet-fini', 'lang = "' . $this->sLanguage . '" AND type');

        $varMail = array(
            '$surl'         => $this->sSUrl,
            '$url'          => $this->sLUrl,
            '$id_projet'    => $oProject->id_project,
            '$title_projet' => utf8_decode($oProject->title),
            '$nbPeteurs'    => $iLendersNb,
            '$tx'           => $oProject->target_rate,
            '$montant_pret' => $oProject->amount,
            '$montant'      => $iBidTotal,
            '$sujetMail'    => htmlentities($this->oMailText->subject)
        );
        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage($this->oMailText->type, $varMail, false);
        $message->setTo($oClient->email);
        $this->mailer->send($sRecipient);

    }

    public function sendAutoBidBalanceInsufficient(\notifications $oNotification)
    {
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');

        $oLenderAccount->get($oNotification->id_lender);
        $oClient->get($oLenderAccount->id_client_owner, 'id_client');

        if ($oClient->status == 1) {
            $sPurpose = $oClient->getLenderPattern($oClient->id_client);

            $this->oSettings->get('Virement - aide par banque', 'type');
            $this->aide_par_banque = $this->oSettings->value;

            $this->oSettings->get('Virement - IBAN', 'type');
            $sIban = chunk_split(strtoupper($this->oSettings->value), 4, ' ');

            $this->oSettings->get('Virement - BIC', 'type');
            $sBic = strtoupper($this->oSettings->value);

            $this->oSettings->get('Virement - titulaire du compte', 'type');
            $sTitulaire = $this->oSettings->value;

            $varMail = array(
                'surl'           => $this->sSUrl,
                'url'            => $this->sLUrl,
                'prenom_p'       => $oClient->prenom,
                'iban'           => $sIban,
                'bic'            => $sBic,
                'titulaire'      => $sTitulaire,
                'autobid_link'   => $this->sLUrl . '/profile/autolend#parametrage',
                'motif_virement' => $sPurpose,
                'lien_fb'        => $this->getFacebookLink(),
                'lien_tw'        => $this->getTwitterLink()
            );

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage('preteur-autobid-solde-insuffisant', $varMail);
            $message->setTo($oClient->email);
            $this->mailer->send($message);
        }
    }

    public function sendAutoBidBalanceLow(\notifications $oNotification)
    {
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \transactions $oTransaction */
        $oTransaction = $this->oEntityManager->getRepository('transactions');

        $oLenderAccount->get($oNotification->id_lender);
        $oClient->get($oLenderAccount->id_client_owner, 'id_client');

        if ($oClient->status == 1) {
            $fBalance = $oTransaction->getSolde($oLenderAccount->id_client_owner);
            $sPurpose = $oClient->getLenderPattern($oClient->id_client);

            $this->oSettings->get('Virement - aide par banque', 'type');
            $this->aide_par_banque = $this->oSettings->value;

            $this->oSettings->get('Virement - IBAN', 'type');
            $sIban = chunk_split(strtoupper($this->oSettings->value), 4, ' ');

            $this->oSettings->get('Virement - BIC', 'type');
            $sBic = strtoupper($this->oSettings->value);

            $this->oSettings->get('Virement - titulaire du compte', 'type');
            $sTitulaire = $this->oSettings->value;

            $varMail = array(
                'surl'           => $this->sSUrl,
                'url'            => $this->sLUrl,
                'prenom_p'       => $oClient->prenom,
                'balance'        => $this->oFicelle->formatNumber($fBalance, 2),
                'iban'           => $sIban,
                'bic'            => $sBic,
                'titulaire'      => $sTitulaire,
                'autobid_link'   => $this->sLUrl . '/profile/autolend#parametrage',
                'motif_virement' => $sPurpose,
                'lien_fb'        => $this->getFacebookLink(),
                'lien_tw'        => $this->getTwitterLink()
            );

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage('preteur-autobid-solde-faible', $varMail);
            $message->setTo($oClient->email);
            $this->mailer->send($message);
        }
    }

    public function sendFirstAutoBidActivation(\notifications $oNotification)
    {
        /** @var \clients $oClient */
        $oClient                = $this->oEntityManager->getRepository('clients');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount         = $this->oEntityManager->getRepository('lenders_accounts');

        $oLenderAccount->get($oNotification->id_lender);
        $oClient->get($oLenderAccount->id_client_owner, 'id_client');

        if ($oClient->status == 1) {
            $varMail = array(
                'surl'             => $this->sSUrl,
                'url'              => $this->sLUrl,
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
            if (!count($format)) {
                return 'moins d\'une minute';
            } else {
                $format[] = "%s " . self::plural($interval->s, "second");
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
            '[SURL]'           => $this->sURl,
            '[ID_PROJET]'      => $oProject->id_project,
            '[MONTANT]'        => $oProject->amount,
            '[RAISON_SOCIALE]' => utf8_decode($oCompanies->name),
            '[LIEN_REPRISE]'   => $this->aConfig['url'][ENVIRONMENT]['admin'] . '/depot_de_dossier/reprise/' . $oProject->hash,
            '[LIEN_BO_PROJET]' => $this->aConfig['url'][ENVIRONMENT]['admin'] . '/dossiers/edit/' . $oProject->id_project
        );

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage($sNotificationType, $aReplacements, false);
        $message->setTo($sRecipient);
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
            'url'            => $this->sLUrl,
            'nom_entreprise' => $oCompanies->name,
            'projet_p'       => $this->sFUrl . '/projects/detail/' . $oProject->slug,
            'montant'        => $this->oFicelle->formatNumber((float) $oProject->amount, 0),
            'heure_debut'    => $oPublicationDate->format('H\hi'),
            'duree'          => $sFundingTime,
            'prenom_e'       => $sFirstName,
            'lien_fb'        => $this->getFacebookLink(),
            'lien_tw'        => $this->getTwitterLink(),
            'annee'          => date('Y')
        );

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('annonce-mise-en-ligne-emprunteure', $aMail);
        $message->setTo($sMailClient);
        $this->mailer->send($message);
    }

}
