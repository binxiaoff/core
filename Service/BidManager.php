<?php
namespace Unilend\Service;

use Unilend\core\Loader;

/**
 * Class BidManager
 * @package Unilend\Service
 */
class BidManager
{
    /** @var \settings */
    private $oSettings;
    /** @var \lenders_accounts */
    private $oLenderAccount;
    /** @var \clients_status */
    private $oClientStatus;
    /** @var \transactions */
    private $oTransaction;
    /** @var \wallets_lines */
    private $oWalletsLine;
    /** @var \bids */
    private $oBid;
    /** @var \offres_bienvenues_details */
    private $oWelcomeOfferDetails;
    /** @var \notifications */
    private $oNotification;
    /** @var \clients_gestion_notifications */
    private $oNotificationSettings;
    /** @var \clients_gestion_mails_notif */
    private $oMailNotification;
    /** @var \clients */
    private $oClient;
    private $sLanguage;
    /** @var \mails_text */
    private $oMailText;
    /** @var \dates */
    private $oDate;
    /** @var \tree */
    private $oTree;
    /** @var \ficelle */
    private $oFicelle;
    /** @var \companies */
    private $oCompany;
    /** @var \mails_filer */
    private $oMailFiler;
    /** @var \tnmp */
    private $oTNMP;
    /** @var \email */
    private $oEmail;
    /** @var array */
    private $aConfig;
    /** @var  ClientManager */
    private $oClientManager;

    public function __construct()
    {
        $this->oSettings             = Loader::loadData('settings');
        $this->oLenderAccount        = Loader::loadData('lenders_accounts');
        $this->oClientStatus         = Loader::loadData('clients_status');
        $this->oTransaction          = Loader::loadData('transactions');
        $this->oWalletsLine          = Loader::loadData('wallets_lines');
        $this->oBid                  = Loader::loadData('bids');
        $this->oWelcomeOfferDetails  = Loader::loadData('offres_bienvenues_details');
        $this->oNotification         = Loader::loadData('notifications');
        $this->oNotificationSettings = Loader::loadData('clients_gestion_notifications');
        $this->oMailNotification     = Loader::loadData('clients_gestion_mails_notif');
        $this->oClient               = Loader::loadData('clients');
        $this->oCompany              = Loader::loadData('companies');
        $this->oMailFiler            = Loader::loadData('mails_filer');
        $this->oMailText             = Loader::loadData('mails_text');
        $this->oTree                 = Loader::loadData('tree');

        $this->oDate    = Loader::loadLib('dates');
        $this->oFicelle = Loader::loadLib('ficelle');
        $this->oTNMP    = Loader::loadLib('tnmp');
        $this->oEmail   = Loader::loadLib('email');

        $this->aConfig = Loader::loadConfig();

        $this->oClientManager = Loader::loadService('ClientManager');

        $this->sLanguage = 'fr';
    }

    public function bid($iLenderId, $iProjectId, $iAutoBidId, $fAmount, $fRate)
    {
        $this->oSettings->get('Pret min', 'type');
        $iAmountMin = (int)$this->oSettings->value;

        if ($iAmountMin > $fAmount) {
            return false;
        }

        if (false === $this->oLenderAccount->get($iLenderId)) {
            return false;
        }

        $iClientId = $this->oLenderAccount->id_client_owner;

        if ($this->oClientStatus->getLastStatut($iClientId)) {
            if ($this->oClientStatus->status < 60) {
                return false;
            }
        } else {
            return false;
        }

        $iBalance = $this->oTransaction->getSolde($iClientId);
        if ($iBalance < $fAmount) {
            return false;
        }

        $fAmountX100 = $fAmount * 100;

        $this->oBid->id_lender_account = $iLenderId;
        $this->oBid->id_project        = $iProjectId;
        $this->oBid->id_autobid        = $iAutoBidId;
        $this->oBid->amount            = $fAmountX100;
        $this->oBid->rate              = $fRate;

        $this->oTransaction->id_client        = $iClientId;
        $this->oTransaction->montant          = -$fAmountX100;
        $this->oTransaction->id_langue        = 'fr';
        $this->oTransaction->date_transaction = date('Y-m-d H:i:s');
        $this->oTransaction->status           = \transactions::PAYMENT_STATUS_OK;
        $this->oTransaction->etat             = \transactions::STATUS_VALID;
        $this->oTransaction->id_project       = $iProjectId;
        $this->oTransaction->transaction      = \transactions::VIRTUAL;
        $this->oTransaction->type_transaction = \transactions_types::TYPE_LENDER_LOAN;
        $this->oTransaction->create();


        $this->oWalletsLine->id_lender                = $this->oBid->id_lender;
        $this->oWalletsLine->type_financial_operation = \wallets_lines::TYPE_BID;
        $this->oWalletsLine->id_transaction           = $this->oTransaction->id_transaction;
        $this->oWalletsLine->status                   = \wallets_lines::STATUS_VALID;
        $this->oWalletsLine->type                     = \wallets_lines::VIRTUAL;
        $this->oWalletsLine->amount                   = -$fAmountX100;
        $this->oWalletsLine->id_project               = $this->oBid->id_project;
        $this->oWalletsLine->create();


        $iBidNb = $this->oBid->counter('id_project = ' . $this->oBid->id_project);
        $iBidNb += 1;

        $this->oBid->id_lender_wallet_line = $this->oWalletsLine->id_wallet_line;
        $this->oBid->ordre                 = $iBidNb;
        $this->oBid->create();


        // Liste des offres non utilisées
        $aAllOffers = $this->oWelcomeOfferDetails->select('id_client = ' . $iClientId . ' AND status = 0');
        if ($aAllOffers != false) {
            $iOfferTotal = 0;
            foreach ($aAllOffers as $aOffer) {
                if ($iOfferTotal <= $fAmount) {
                    $iOfferTotal += ($aOffer['montant'] / 100); // total des offres

                    $this->oWelcomeOfferDetails->get($aOffer['id_offre_bienvenue_detail'], 'id_offre_bienvenue_detail');
                    $this->oWelcomeOfferDetails->status = \offres_bienvenues_details::STATUS_USED;
                    $this->oWelcomeOfferDetails->id_bid = $this->oBid->id_bid;
                    $this->oWelcomeOfferDetails->update();

                    // Apres addition de la derniere offre on se rend compte que le total depasse
                    if ($iOfferTotal > $fAmount) {
                        // On fait la diff et on créer un remb du trop plein d'offres
                        $iAmountRepayment                               = $iOfferTotal - $fAmount;
                        $this->oWelcomeOfferDetails->id_offre_bienvenue = 0;
                        $this->oWelcomeOfferDetails->id_client          = $iClientId;
                        $this->oWelcomeOfferDetails->id_bid             = 0;
                        $this->oWelcomeOfferDetails->id_bid_remb        = $this->oBid->id_bid;
                        $this->oWelcomeOfferDetails->status             = \offres_bienvenues_details::STATUS_NEW;
                        $this->oWelcomeOfferDetails->type               = \offres_bienvenues_details::TYPE_CUT;
                        $this->oWelcomeOfferDetails->montant            = ($iAmountRepayment * 100);
                        $this->oWelcomeOfferDetails->create();
                    }
                } else {
                    break;
                }
            }
        }

        ///// NOTIFICATION OFFRE PLACEE ///////

        $this->oNotification->type       = \clients_gestion_type_notif::TYPE_BID_PLACED;
        $this->oNotification->id_lender  = $this->oBid->id_lender;
        $this->oNotification->id_project = $this->oBid->id_project;
        $this->oNotification->amount     = $fAmountX100;
        $this->oNotification->id_bid     = $this->oBid->id_bid;
        $this->oNotification->create();
        ///// FIN NOTIFICATION OFFRE PLACEE ///////

        if ($this->oNotificationSettings->getNotif($iClientId, \clients_gestion_type_notif::TYPE_BID_PLACED, 'immediatement') == true) {
            $this->oClient->get($iClientId);
            $sPurpose = $this->oClientManager->getClientTransferPurpose($iClientId);

            //*********************************//
            //*** ENVOI DU MAIL CONFIRM BID ***//
            //*********************************//
            $this->oMailText->get('confirmation-bid', 'lang = "' . $this->sLanguage . '" AND type');

            $this->oSettings->get('Facebook', 'type');
            $lien_fb = $this->oSettings->value;

            $this->oSettings->get('Twitter', 'type');
            $lien_tw = $this->oSettings->value;

            $timeAdd = strtotime($this->oBid->added);
            $month   = $this->oDate->tableauMois['fr'][date('n', $timeAdd)];

            $sSUrl = $this->aConfig['static_url'][$this->aConfig['env']];
            $sLUrl = $this->aConfig['url'][$this->aConfig['env']]['default'] . ($this->aConfig['multilanguage']['enabled'] ? '/' . $this->sLanguage : '');

            $pageProjets = $this->oTree->getSlug(4, $this->sLanguage);

            $varMail = array(
                'surl' => $sSUrl,
                'url' => $sLUrl,
                'prenom_p' => $this->oClient->prenom,
                'nom_entreprise' => $this->oCompany->name,
                'valeur_bid' => $this->oFicelle->formatNumber($fAmount),
                'taux_bid' => $this->oFicelle->formatNumber($this->oBid->rate, 1),
                'date_bid' => date('d', $timeAdd) . ' ' . $month . ' ' . date('Y', $timeAdd),
                'heure_bid' => date('H:i:s', strtotime($this->oBid->added)),
                'projet-p' => $sLUrl . '/' . $pageProjets,
                'motif_virement' => $sPurpose,
                'lien_fb' => $lien_fb,
                'lien_tw' => $lien_tw
            );

            $tabVars   = $this->oTNMP->constructionVariablesServeur($varMail);
            $sujetMail = strtr(utf8_decode($this->oMailText->subject), $tabVars);
            $texteMail = strtr(utf8_decode($this->oMailText->content), $tabVars);
            $exp_name  = strtr(utf8_decode($this->oMailText->exp_name), $tabVars);

            $this->oEmail->setFrom($this->oMailText->exp_email, $exp_name);
            $this->oEmail->setSubject(stripslashes($sujetMail));
            $this->oEmail->setHTMLBody(stripslashes($texteMail));

            if ($this->aConfig['env'] === 'prod') {
                \Mailer::sendNMP($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail, $this->oClient->email, $tabFiler);
                $this->oTNMP->sendMailNMP($tabFiler, $varMail, $this->oMailText->nmp_secure, $this->oMailText->id_nmp, $this->oMailText->nmp_unique, $this->oMailText->mode);
            } else {
                $this->oEmail->addRecipient(trim($this->oClient->email));
                \Mailer::send($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail);
            }
            // fin mail confirmation bid //

            $this->oMailNotification->immediatement = 1;
        } else {
            $this->oMailNotification->immediatement = 0;
        }

        $this->oMailNotification->id_client       = $this->oClient->id_client;
        $this->oMailNotification->id_notif        = \clients_gestion_type_notif::TYPE_BID_PLACED; // offre placée
        $this->oMailNotification->date_notif      = date('Y-m-d H:i:s');
        $this->oMailNotification->id_notification = $this->oNotification->id_notification;
        $this->oMailNotification->id_transaction  = $this->oTransaction->id_transaction;
        $this->oMailNotification->create();
    }

    /**
     * @param $iBidId
     */
    public function reject($iBidId)
    {
        if ($this->oBid->get($iBidId) && ($this->oBid->status == \bids::STATUS_BID_PENDING || $this->oBid->status == \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY)) {
            $this->credit($this->oBid->amount / 100);
            $this->oBid->status = \bids::STATUS_BID_REJECTED;
            $this->oBid->update();
        }


    }

    public function rejectPartially($iBidId, $fRepaymentAmount)
    {
        if ($this->oBid->get($iBidId) && ($this->oBid->status == \bids::STATUS_BID_PENDING || $this->oBid->status == \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY)) {
            $this->credit($fRepaymentAmount);
            // Save new amount of the bid after repayment
            $this->oBid->amount -= $fRepaymentAmount * 100;
            $this->oBid->status = \bids::STATUS_BID_ACCEPTED;
            $this->oBid->update();
        }
    }

    private function credit($fAmount)
    {
        $this->oLenderAccount->get($this->oBid->id_lender_account, 'id_lender_account');

        $fAmountX100 = $fAmount * 100;

        $this->oTransaction->id_client        = $this->oLenderAccount->id_client_owner;
        $this->oTransaction->montant          = $fAmountX100;
        $this->oTransaction->id_langue        = 'fr';
        $this->oTransaction->date_transaction = date('Y-m-d H:i:s');
        $this->oTransaction->status           = \transactions::PAYMENT_STATUS_OK;
        $this->oTransaction->etat             = \transactions::STATUS_VALID;
        $this->oTransaction->id_project       = $this->oBid->id_project;
        $this->oTransaction->ip_client        = $_SERVER['REMOTE_ADDR'];
        $this->oTransaction->type_transaction = \transactions_types::TYPE_LENDER_LOAN;
        $this->oTransaction->id_bid_remb      = $this->oBid->id_bid;
        $this->oTransaction->transaction      = \transactions::VIRTUAL;
        $this->oTransaction->create();

        $this->oWalletsLine->id_lender                = $this->oBid->id_lender_account;
        $this->oWalletsLine->type_financial_operation = \wallets_lines::TYPE_BID;
        $this->oWalletsLine->id_transaction           = $this->oTransaction->id_transaction;
        $this->oWalletsLine->status                   = \wallets_lines::STATUS_VALID;
        $this->oWalletsLine->type                     = \wallets_lines::VIRTUAL;
        $this->oWalletsLine->id_bid_remb              = $this->oBid->id_bid;
        $this->oWalletsLine->amount                   = $fAmountX100;
        $this->oWalletsLine->id_project               = $this->oBid->id_project;
        $this->oWalletsLine->create();

        $iWelcomeOfferTotal = $this->oWelcomeOfferDetails->sum('id_client = ' . $this->oLenderAccount->id_client_owner . ' AND id_bid = ' . $this->oBid->id_bid, 'montant');
        if ($iWelcomeOfferTotal > 0) {
            if ($this->oBid->amount === $fAmountX100) { //Totally credit
                $this->oWelcomeOfferDetails->montant = min($iWelcomeOfferTotal, $fAmountX100);
            } elseif (($this->oBid->amount - $fAmountX100) <= $iWelcomeOfferTotal
            ) { //Partially credit
                $this->oWelcomeOfferDetails->montant = $iWelcomeOfferTotal - ($this->oBid->amount - $fAmountX100);
            }

            if (false === empty($this->oWelcomeOfferDetails->montant)) {
                $this->oWelcomeOfferDetails->id_offre_bienvenue = 0;
                $this->oWelcomeOfferDetails->id_client          = $this->oLenderAccount->id_client_owner;
                $this->oWelcomeOfferDetails->id_bid             = 0;
                $this->oWelcomeOfferDetails->id_bid_remb        = $this->oBid->id_bid;
                $this->oWelcomeOfferDetails->status             = \offres_bienvenues_details::STATUS_NEW;
                $this->oWelcomeOfferDetails->type               = \offres_bienvenues_details::TYPE_PAYBACK;
                $this->oWelcomeOfferDetails->create();
            }
        }

        $this->oNotification->type       = \notifications::TYPE_BID_REJECTED; // rejet
        $this->oNotification->id_lender  = $this->oBid->id_lender_account;
        $this->oNotification->id_project = $this->oBid->id_project;
        $this->oNotification->amount     = $fAmountX100;
        $this->oNotification->id_bid     = $this->oBid->id_bid;
        $this->oNotification->create();

        $this->oMailNotification->id_client       = $this->oLenderAccount->id_client_owner;
        $this->oMailNotification->id_notif        = \clients_gestion_type_notif::TYPE_BID_REJECTED;
        $this->oMailNotification->date_notif      = date('Y-m-d H:i:s');
        $this->oMailNotification->id_notification = $this->oNotification->id_notification;
        $this->oMailNotification->id_transaction  = $this->oTransaction->id_transaction;
        $this->oMailNotification->create();
    }
}