<?php
namespace Unilend\Service;

use Unilend\core\Loader;
use Unilend\librairies\ULogger;

class MailerManager
{
    /** @var \settings */
    private $oSettings;

    /** @var \mails_filer */
    private $oMailFiler;

    /** @var \mails_text */
    private $oMailText;

    /** @var ULogger */
    private $oLogger;

    /** @var \email */
    private $oEmail;

    /** @var \ficelle */
    private $oFicelle;

    /** @var array */
    private $aConfig;

    /** @var \dates */
    private $oDate;

    /** @var \tnmp */
    private $oTNMP;

    /** @var \jours_ouvres */
    private $oWorkingDay;
    private $sSUrl;
    private $sLUrl;
    private $sFUrl;

    public function __construct()
    {
        $this->aConfig = Loader::loadConfig();

        $this->oSettings  = Loader::loadData('settings');
        $this->oMailFiler = Loader::loadData('mails_filer');
        $this->oMailText  = Loader::loadData('mails_text');

        $this->oNMP       = Loader::loadData('nmp');
        $this->oNMPDesabo = Loader::loadData('nmp_desabo');

        $this->oTNMP       = Loader::loadLib('tnmp', array($this->oNMP, $this->oNMPDesabo, $this->aConfig['env']));
        $this->oEmail      = Loader::loadLib('email');
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
        $oLenderAccount = Loader::loadData('lenders_accounts');
        /** @var \clients $oClient */
        $oClient = Loader::loadData('clients');
        /** @var \companies $oCompany */
        $oCompany = Loader::loadData('companies');
        /** @var \projects $oProject */
        $oProject = Loader::loadData('projects');
        /** @var \tree $oTree */
        $oTree = Loader::loadData('tree');
        /** @var \bids $oBid */
        $oBid = Loader::loadData('bids');

        if ($oLenderAccount->get($oNotification->id_lender) && $oBid->get($oNotification->id_bid) && $oClient->get($oLenderAccount->id_client_owner)) {
            if (empty($oBid->id_autobid)) {
                $sMailTemplate = 'confirmation-bid';
            } else {
                $sMailTemplate = 'confirmation-autobid';
            }

            $this->oMailText->get($sMailTemplate, 'lang = "' . $this->sLanguage . '" AND type');

            $sPurpose = $oClient->getLenderPattern($oClient->id_client);

            $timeAdd = strtotime($oBid->added);
            $month   = $this->oDate->tableauMois['fr'][date('n', $timeAdd)];

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
                'motif_virement' => $sPurpose,
                'lien_fb'        => $this->getFacebookLink(),
                'lien_tw'        => $this->getTwitterLink()
            );

            $tabVars   = $this->oTNMP->constructionVariablesServeur($varMail);
            $sujetMail = strtr(utf8_decode($this->oMailText->subject), $tabVars);
            $texteMail = strtr(utf8_decode($this->oMailText->content), $tabVars);
            $exp_name  = strtr(utf8_decode($this->oMailText->exp_name), $tabVars);

            $this->oEmail->setFrom($this->oMailText->exp_email, $exp_name);
            $this->oEmail->setSubject(stripslashes($sujetMail));
            $this->oEmail->setHTMLBody(stripslashes($texteMail));

            if ($this->aConfig['env'] === 'prod') {
                \Mailer::sendNMP($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail, $oClient->email, $tabFiler);
                $this->oTNMP->sendMailNMP($tabFiler, $varMail, $this->oMailText->nmp_secure, $this->oMailText->id_nmp, $this->oMailText->nmp_unique, $this->oMailText->mode);
            } else {
                $this->oEmail->addRecipient(trim($oClient->email));
                \Mailer::send($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail);
            }
        }
    }

    public function sendFundFailedToLender(\projects $oProject)
    {
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = Loader::loadData('lenders_accounts');
        /** @var \clients $oClient */
        $oClient = Loader::loadData('clients');
        /** @var \transactions $oTransaction */
        $oTransaction = Loader::loadData('transactions');
        /** @var \companies $oCompany */
        $oCompany = Loader::loadData('companies');
        /** @var \bids $oBid */
        $oBid = Loader::loadData('bids');

        $aBidList = $oBid->select('id_project = ' . $oProject->id_project, 'rate ASC, added ASC');
        foreach ($aBidList as $aBid) {
            $oBid->get($aBid['id_bid']);
            $oLenderAccount->get($oBid->id_lender_account);
            $oClient->get($oLenderAccount->id_client_owner);
            if ($oClient->status == 1) {
                $oProject->get($oBid->id_project, 'id_project');

                $oCompany->get($oProject->id_company);

                $fBalance = $oTransaction->getSolde($oClient->id_client);

                $this->oMailText->get('preteur-dossier-funding-ko', 'lang = "' . $this->sLanguage . '" AND type');

                $sAdded = strtotime($oBid->added);
                $month  = $this->oDate->tableauMois['fr'][date('n', $sAdded)];

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

                $tabVars = $this->oTNMP->constructionVariablesServeur($varMail);

                $sujetMail = strtr(utf8_decode($this->oMailText->subject), $tabVars);
                $texteMail = strtr(utf8_decode($this->oMailText->content), $tabVars);
                $exp_name  = strtr(utf8_decode($this->oMailText->exp_name), $tabVars);

                $this->oEmail->setFrom($this->oMailText->exp_email, $exp_name);
                $this->oEmail->setSubject(stripslashes($sujetMail));
                $this->oEmail->setHTMLBody(stripslashes($texteMail));
                if ($this->aConfig['env'] === 'prod') {
                    \Mailer::sendNMP($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail, $oClient->email, $tabFiler);
                    $this->oTNMP->sendMailNMP($tabFiler, $varMail, $this->oMailText->nmp_secure, $this->oMailText->id_nmp, $this->oMailText->nmp_unique, $this->oMailText->mode);
                } else {
                    $this->oEmail->addRecipient(trim($oClient->email));
                    \Mailer::send($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail);
                }
                if ($this->oLogger instanceof ULogger) {
                    $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' : email preteur-dossier-funding-ko sent');
                }
            }
        }
    }

    public function sendFundedToBorrower(\projects $oProject)
    {
        /** @var \bids $oBid */
        $oBid = Loader::loadData('bids');
        /** @var \companies $oCompany */
        $oCompany = Loader::loadData('companies');
        /** @var \clients $oBorrower */
        $oBorrower = Loader::loadData('clients');

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
            $this->oMailText->get('emprunteur-dossier-funde', 'lang = "' . $this->sLanguage . '" AND type');

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

            $tabVars = $this->oTNMP->constructionVariablesServeur($varMail);

            $sujetMail = strtr(utf8_decode($this->oMailText->subject), $tabVars);
            $texteMail = strtr(utf8_decode($this->oMailText->content), $tabVars);
            $exp_name  = strtr(utf8_decode($this->oMailText->exp_name), $tabVars);

            $this->oEmail->setFrom($this->oMailText->exp_email, $exp_name);
            $this->oEmail->setSubject(stripslashes($sujetMail));
            $this->oEmail->setHTMLBody(stripslashes($texteMail));

            if ($this->aConfig['env'] === 'prod') {
                \Mailer::sendNMP($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail, $oBorrower->email, $tabFiler);
                $this->oTNMP->sendMailNMP($tabFiler, $varMail, $this->oMailText->nmp_secure, $this->oMailText->id_nmp, $this->oMailText->nmp_unique, $this->oMailText->mode);
            } else {
                $this->oEmail->addRecipient(trim($oBorrower->email));
                \Mailer::send($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail);
            }
        }
        //*** ENVOI DU MAIL NOTIFICATION FUNDE 100% ***//

        $this->oSettings->get('Adresse notification projet funde a 100', 'type');
        $destinataire = $this->oSettings->value;

        $nbPeteurs = $oBid->getNbPreteurs($oProject->id_project);

        $this->oMailText->get('notification-projet-funde-a-100', 'lang = "' . $this->sLanguage . '" AND type');

        $surl         = $this->sSUrl;
        $url          = $this->sLUrl;
        $id_projet    = $oProject->title;
        $title_projet = utf8_decode($oProject->title);
        $nbPeteurs    = $nbPeteurs;
        $tx           = $fWeightedAvgRate;
        $periode      = $tempsRest;

        $sujetMail = htmlentities($this->oMailText->subject);
        eval("\$sujetMail = \"$sujetMail\";");

        $texteMail = $this->oMailText->content;
        eval("\$texteMail = \"$texteMail\";");

        $exp_name = $this->oMailText->exp_name;
        eval("\$exp_name = \"$exp_name\";");

        $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
        $exp_name  = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

        $this->oEmail->setFrom($this->oMailText->exp_email, $exp_name);
        $this->oEmail->addRecipient(trim($destinataire));

        $this->oEmail->setSubject('=?UTF-8?B?' . base64_encode(html_entity_decode($sujetMail)) . '?=');
        $this->oEmail->setHTMLBody($texteMail);
        \Mailer::send($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail);
    }

    /**
     * @param \projects $oProject
     */
    public function sendFundedAndFinishedToBorrower(\projects $oProject)
    {
        /** @var \companies $oCompany */
        $oCompany = Loader::loadData('companies');
        /** @var \clients $oBorrower */
        $oBorrower = Loader::loadData('clients');
        /** @var \echeanciers_emprunteur $oBorrowerPaymentSchedule */
        $oBorrowerPaymentSchedule = Loader::loadData('echeanciers_emprunteur');

        $oCompany->get($oProject->id_company, 'id_company');
        $oBorrower->get($oCompany->id_client_owner, 'id_client');

        if ($oBorrower->status == 1) {
            $this->oMailText->get('emprunteur-dossier-funde-et-termine', 'lang = "' . $this->sLanguage . '" AND type');
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

            $tabVars = $this->oTNMP->constructionVariablesServeur($varMail);

            $sujetMail = strtr(utf8_decode($this->oMailText->subject), $tabVars);
            $texteMail = strtr(utf8_decode($this->oMailText->content), $tabVars);
            $exp_name  = strtr(utf8_decode($this->oMailText->exp_name), $tabVars);

            $this->oEmail->setFrom($this->oMailText->exp_email, $exp_name);

            $this->oEmail->setSubject(stripslashes($sujetMail));
            $this->oEmail->setHTMLBody(stripslashes($texteMail));

            if ($this->aConfig['env'] === 'prod') {
                \Mailer::sendNMP($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail, $oBorrower->email, $tabFiler);
                $this->oTNMP->sendMailNMP($tabFiler, $varMail, $this->oMailText->nmp_secure, $this->oMailText->id_nmp, $this->oMailText->nmp_unique, $this->oMailText->mode);
            } else {
                $this->oEmail->addRecipient(trim($oBorrower->email));
                \Mailer::send($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail);
            }
            if ($this->oLogger instanceof ULogger) {
                $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' : email emprunteur-dossier-funde-et-termine sent');
            }
        }
    }

    public function sendFundedToStaff(\projects $oProject)
    {
        /** @var \companies $oCompany */
        $oCompany = Loader::loadData('companies');
        /** @var \bids $oBid */
        $oBid = Loader::loadData('bids');
        /** @var \loans $oLoan */
        $oLoan = Loader::loadData('loans');

        $oCompany->get($oProject->id_company, 'id_company');

        $this->oSettings->get('Adresse notification projet funde a 100', 'type');
        $sRecipient = $this->oSettings->value;

        $fWeightedAvgRate = $this->oFicelle->formatNumber($oProject->getAverageInterestRate());

        $iBidTotal = $oBid->getSoldeBid($oProject->id_project);
        // si le solde des enchere est supperieur au montant du pret on affiche le montant du pret
        if ($iBidTotal > $oProject->amount) {
            $iBidTotal = $oProject->amount;
        }

        $iLenderNb = $oLoan->getNbPreteurs($oProject->id_project);

        $this->oMailText->get('notification-projet-funde-a-100', 'lang = "' . $this->sLanguage . '" AND type');

        $surl         = $this->sSUrl;
        $url          = $this->sLUrl;
        $id_projet    = $oProject->id_project;
        $title_projet = utf8_decode($oProject->title);
        $nbPeteurs    = $iLenderNb;
        $tx           = $fWeightedAvgRate;
        $montant_pret = $oProject->amount;
        $montant      = $iBidTotal;
        $periode      = $oProject->period;

        $sujetMail = htmlentities($this->oMailText->subject);
        eval("\$sujetMail = \"$sujetMail\";");

        $texteMail = $this->oMailText->content;
        eval("\$texteMail = \"$texteMail\";");
        $exp_name = $this->oMailText->exp_name;
        eval("\$exp_name = \"$exp_name\";");

        $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
        $exp_name  = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

        $this->oEmail->setFrom($this->oMailText->exp_email, $exp_name);
        $this->oEmail->addRecipient(trim($sRecipient));

        $this->oEmail->setSubject('=?UTF-8?B?' . base64_encode(html_entity_decode($sujetMail)) . '?=');
        $this->oEmail->setHTMLBody($texteMail);
        \Mailer::send($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail);
    }

    public function sendBidAccepted(\projects $oProject)
    {
        /** @var \loans $oLoan */
        $oLoan = Loader::loadData('loans');
        /** @var \companies $oCompany */
        $oCompany = Loader::loadData('companies');
        /** @var \clients $oClient */
        $oClient = Loader::loadData('clients');
        /** @var \echeanciers $oPaymentSchedule */
        $oPaymentSchedule = Loader::loadData('echeanciers');
        /** @var \accepted_bids $oAcceptedBid */
        $oAcceptedBid = Loader::loadData('accepted_bids');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = Loader::loadData('lenders_accounts');

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

                $this->oMailText->get('preteur-bid-ok', 'lang = "' . $this->sLanguage . '" AND type');

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

                $tabVars = $this->oTNMP->constructionVariablesServeur($varMail);

                $this->oEmail->setFrom($this->oMailText->exp_email, strtr(utf8_decode($this->oMailText->exp_name), $tabVars));
                $this->oEmail->setSubject(stripslashes(strtr(utf8_decode($this->oMailText->subject), $tabVars)));
                $this->oEmail->setHTMLBody(stripslashes(strtr(utf8_decode($this->oMailText->content), $tabVars)));

                if ($this->aConfig['env'] === 'prod') {
                    \Mailer::sendNMP($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail, $oClient->email, $tabFiler);
                    $this->oTNMP->sendMailNMP($tabFiler, $varMail, $this->oMailText->nmp_secure, $this->oMailText->id_nmp, $this->oMailText->nmp_unique, $this->oMailText->mode);
                } else {
                    $this->oEmail->addRecipient(trim($oClient->email));
                    \Mailer::send($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail);
                }
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
        $oBid = Loader::loadData('bids');
        /** @var \companies $oCompany */
        $oCompany = Loader::loadData('companies');
        /** @var \clients $oClient */
        $oClient = Loader::loadData('clients');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = Loader::loadData('lenders_accounts');
        /** @var \projects $oProject */
        $oProject = Loader::loadData('projects');
        /** @var \autobid $oAutoBid */
        $oAutoBid = Loader::loadData('autobid');

        $oBid->get($oNotification->id_bid);
        $oLenderAccount->get($oBid->id_lender_account);
        $oClient->get($oLenderAccount->id_client_owner);

        if ($oClient->status == 1) {
            $oProject->get($oBid->id_project);
            $oCompany->get($oProject->id_company);

            $oEndDate   = ProjectManager::getProjectEndDate($oProject);
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
            $this->oMailText->get($sMailTemplate, 'lang = "' . $this->sLanguage . '" AND type');
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

            $tabVars = $this->oTNMP->constructionVariablesServeur($varMail);

            $sujetMail = strtr(utf8_decode($this->oMailText->subject), $tabVars);
            $texteMail = strtr(utf8_decode($this->oMailText->content), $tabVars);
            $exp_name  = strtr(utf8_decode($this->oMailText->exp_name), $tabVars);

            $this->oEmail->setFrom($this->oMailText->exp_email, $exp_name);
            $this->oEmail->setSubject(stripslashes($sujetMail));
            $this->oEmail->setHTMLBody(stripslashes($texteMail));


            if ($this->aConfig['env'] === 'prod') {
                \Mailer::sendNMP($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail, $oClient->email, $tabFiler);
                $this->oTNMP->sendMailNMP($tabFiler, $varMail, $this->oMailText->nmp_secure, $this->oMailText->id_nmp, $this->oMailText->nmp_unique, $this->oMailText->mode);
            } else {
                $this->oEmail->addRecipient(trim($oClient->email));
                \Mailer::send($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail);
            }
        }
    }

    public function sendFundFailedToBorrower(\projects $oProject)
    {
        /** @var \companies $oCompany */
        $oCompany = Loader::loadData('companies');
        /** @var \clients $oClient */
        $oClient = Loader::loadData('clients');

        $oCompany->get($oProject->id_company, 'id_company');
        $oClient->get($oCompany->id_client_owner, 'id_client');

        if ($oClient->status == 1) {
            $this->oMailText->get('emprunteur-dossier-funding-ko', 'lang = "' . $this->sLanguage . '" AND type');

            $varMail = array(
                'surl'     => $this->sSUrl,
                'url'      => $this->sLUrl,
                'prenom_e' => $oClient->prenom,
                'projet'   => $oProject->title,
                'lien_fb'  => $this->getFacebookLink(),
                'lien_tw'  => $this->getTwitterLink()
            );

            $tabVars = $this->oTNMP->constructionVariablesServeur($varMail);

            $sujetMail = strtr(utf8_decode($this->oMailText->subject), $tabVars);
            $texteMail = strtr(utf8_decode($this->oMailText->content), $tabVars);
            $exp_name  = strtr(utf8_decode($this->oMailText->exp_name), $tabVars);

            $this->oEmail->setFrom($this->oMailText->exp_email, $exp_name);
            $this->oEmail->setSubject(stripslashes($sujetMail));
            $this->oEmail->setHTMLBody(stripslashes($texteMail));

            if ($this->aConfig['env'] === 'prod') {
                \Mailer::sendNMP($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail, $oClient->email, $tabFiler);
                $this->oTNMP->sendMailNMP($tabFiler, $varMail, $this->oMailText->nmp_secure, $this->oMailText->id_nmp, $this->oMailText->nmp_unique, $this->oMailText->mode);
            } else {
                $this->oEmail->addRecipient(trim($oClient->email));
                \Mailer::send($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail);
            }
            if ($this->oLogger instanceof ULogger) {
                $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' : email emprunteur-dossier-funding-ko sent');
            }
        }
    }

    public function sendProjectFinishedToStaff(\projects $oProject)
    {
        /** @var \loans $oLoan */
        $oLoan = Loader::loadData('loans');
        /** @var \companies $oCompany */
        $oCompany = Loader::loadData('companies');
        /** @var \clients $oClient */
        $oClient = Loader::loadData('clients');
        /** @var \bids $oBid */
        $oBid = Loader::loadData('bids');

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

        $surl         = $this->sSUrl;
        $url          = $this->sLUrl;
        $id_projet    = $oProject->id_project;
        $title_projet = utf8_decode($oProject->title);
        $nbPeteurs    = $iLendersNb;
        $tx           = $oProject->target_rate;
        $montant_pret = $oProject->amount;
        $montant      = $iBidTotal;
        $sujetMail    = htmlentities($this->oMailText->subject);

        eval("\$sujetMail = \"$sujetMail\";");

        $texteMail = $this->oMailText->content;
        eval("\$texteMail = \"$texteMail\";");

        $exp_name = $this->oMailText->exp_name;
        eval("\$exp_name = \"$exp_name\";");

        $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
        $exp_name  = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

        $this->oEmail->setFrom($this->oMailText->exp_email, $exp_name);
        $this->oEmail->addRecipient(trim($sRecipient));

        $this->oEmail->setSubject('=?UTF-8?B?' . base64_encode(html_entity_decode($sujetMail)) . '?=');
        $this->oEmail->setHTMLBody($texteMail);
        \Mailer::send($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail);
    }

    public function sendFirstAutoBidActivation(\notifications $oNotification)
    {
        /** @var \clients $oClient */
        $oClient = Loader::loadData('clients');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = Loader::loadData('lenders_accounts');
        /** @var AutoBidSettingsManager $oAutoBidSettingsManager */
        $oAutoBidSettingsManager = Loader::loadService('AutoBidSettingsManager');

        $oLenderAccount->get($oNotification->id_lender);
        $oClient->get($oLenderAccount->id_client_owner, 'id_client');

        if ($oClient->status == 1) {
            $this->oMailText->get('preteur-autobid-activation', 'lang = "' . $this->sLanguage . '" AND type');

            $sSUrl    = $this->aConfig['static_url'][$this->aConfig['env']];
            $sLUrl    = $this->aConfig['url'][$this->aConfig['env']]['default'] . ($this->aConfig['multilanguage']['enabled'] ? '/' . $this->sLanguage : '');

            $varMail = array(
                'surl'             => $sSUrl,
                'url'              => $sLUrl,
                'prenom_p'         => $oClient->prenom,
                'heure_activation' => $oAutoBidSettingsManager->getActivationTime($oClient)->format('G\hi'),
                'motif_virement'   => $oClient->getLenderPattern($oClient->id_client),
                'lien_fb'          => $this->getFacebookLink(),
                'lien_tw'          => $this->getTwitterLink(),
                'annee'            => date('Y')
            );

            $tabVars = $this->oTNMP->constructionVariablesServeur($varMail);

            $sujetMail = strtr(utf8_decode($this->oMailText->subject), $tabVars);
            $texteMail = strtr(utf8_decode($this->oMailText->content), $tabVars);
            $exp_name  = strtr(utf8_decode($this->oMailText->exp_name), $tabVars);

            $this->oEmail->setFrom($this->oMailText->exp_email, $exp_name);
            $this->oEmail->setSubject(stripslashes($sujetMail));
            $this->oEmail->setHTMLBody(stripslashes($texteMail));

            if ($this->aConfig['env'] === 'prod') {
                \Mailer::sendNMP($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail, $oClient->email, $tabFiler);
                $this->oTNMP->sendMailNMP($tabFiler, $varMail, $this->oMailText->nmp_secure, $this->oMailText->id_nmp, $this->oMailText->nmp_unique, $this->oMailText->mode);
            } else {
                $this->oEmail->addRecipient(trim($oClient->email));
                \Mailer::send($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail);
            }
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

    public function sendProjectNotificationToStaff($sNotificationType, \projects $oProject, $sRecipient)
    {
        $this->oMailText->get($sNotificationType, 'lang = "fr" AND type');

        /** @var \companies $oCompanies */
        $oCompanies = Loader::loadData('companies');
        $oCompanies->get($oProject->id_company, 'id_company');

        $aReplacements = array(
            '[SURL]'           => $this->aConfig['static_url'][ENVIRONMENT],
            '[ID_PROJET]'      => $oProject->id_project,
            '[MONTANT]'        => $oProject->amount,
            '[RAISON_SOCIALE]' => utf8_decode($oCompanies->name),
            '[LIEN_REPRISE]'   => $this->aConfig['url'][ENVIRONMENT]['admin'] . '/depot_de_dossier/reprise/' . $oProject->hash,
            '[LIEN_BO_PROJET]' => $this->aConfig['url'][ENVIRONMENT]['admin'] . '/dossiers/edit/' . $oProject->id_project
        );

        $this->oEmail->setFrom($this->oMailText->exp_email, utf8_decode($this->oMailText->exp_name));
        $this->oEmail->setSubject(utf8_decode($this->oMailText->subject));
        $this->oEmail->setHTMLBody(str_replace(array_keys($aReplacements), array_values($aReplacements), $this->oMailText->content));
        $this->oEmail->addRecipient($sRecipient);

        \Mailer::send($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail);
    }

    public function sendProjectOnlineToBorrower(\projects $oProject)
    {
        /** @var \companies $oCompanies */
        $oCompanies = Loader::loadData('companies');
        /** @var \clients $oClients */
        $oClients = Loader::loadData('clients');
        $oCompanies->get($oProject->id_company);
        $this->oMailText->get('annonce-mise-en-ligne-emprunteur', 'lang = "' . $this->sLanguage . '" AND type');

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

        $aVars        = $this->oTNMP->constructionVariablesServeur($aMail);
        $sMailSubject = strtr(utf8_decode($this->oMailText->subject), $aVars);
        $sMailBody    = strtr(utf8_decode($this->oMailText->content), $aVars);
        $sSender      = strtr(utf8_decode($this->oMailText->exp_name), $aVars);

        $this->oEmail->setFrom($this->oMailText->exp_email, $sSender);
        $this->oEmail->setSubject(stripslashes($sMailSubject));
        $this->oEmail->setHTMLBody(stripslashes($sMailBody));

        if ($this->aConfig['env'] == 'prod') {
            \Mailer::sendNMP($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail, $sMailClient, $tabFiler);
            $this->oTNMP->sendMailNMP($tabFiler, $aMail, $this->oMailText->nmp_secure, $this->oMailText->id_nmp, $this->oMailText->nmp_unique, $this->oMailText->mode);
        } else {
            $this->oEmail->addRecipient(trim($sMailClient));
            \Mailer::send($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail);
        }
    }

    /**
     * @param int $iClientId
     * @param string $sCurrentIban
     * @param string $sNewIban
     * @return bool
     */
    public function sendIbanUpdateToStaff($iClientId, $sCurrentIban, $sNewIban)
    {
        $this->oMailText->get('uninotification-modification-iban-bo', 'lang = "' . $this->sLanguage . '" AND type');

        $aMail        = array(
            'id_client'  => $iClientId,
            'first_name' => $_SESSION['user']['firstname'],
            'name'       => $_SESSION['user']['name'],
            'user_id'    => $_SESSION['user']['id_user'],
            'old_iban'   => $sCurrentIban,
            'new_iban'   => $sNewIban
        );
        $aVars        = $this->oTNMP->constructionVariablesServeur($aMail);
        $sMailBody    = strtr(utf8_decode($this->oMailText->content), $aVars);
        $sSender      = strtr(utf8_decode($this->oMailText->exp_name), $aVars);

        $this->oEmail->setFrom($this->oMailText->exp_email, $sSender);
        $this->oEmail->setSubject(stripslashes($this->oMailText->subject));
        $this->oEmail->setHTMLBody(stripslashes($sMailBody));
        $this->oEmail->addRecipient('controle_interne@unilend.fr');

        return \Mailer::send($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail);
    }
}
