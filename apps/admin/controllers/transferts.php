<?php

class transfertsController extends bootstrap
{
    public $Command;

    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        // Controle d'acces à la rubrique
        $this->users->checkAccess('transferts');

        // Activation du menu
        $this->menu_admin = 'transferts';
    }

    public function _default()
    {
        $this->receptions = $this->loadData('receptions');

        // virements
        $this->lvirements = $this->receptions->select('type = 2 AND status_virement = 1 AND id_project = 0', 'remb ASC,id_reception DESC');

        // Status Virement
        $this->statusVirement = array(
            0 => 'Reçu', 1 => 'Attribué manu', 2 => 'Attribué auto', 3 => 'Rejeté', 4 => 'Rejet'
        );
    }

    public function _prelevements()
    {
        $this->receptions = $this->loadData('receptions');

        // virements
        $this->lprelevements = $this->receptions->select('type = 1 AND status_prelevement = 2', 'id_reception DESC');

        // Status Prelevement
        $this->statusPrelevement = array(
            0 => 'Reçu', 1 => 'Attribué manu', 2 => 'Attribué auto', 3 => 'Rejeté', 4 => 'Rejet'
        );
    }

    public function _virements_ra()
    {
        $this->receptions = $this->loadData('receptions');
        $this->projects   = $this->loadData('projects');

        // post d'attribution de projet
        if (isset($_POST['id']) && isset($_POST['id_reception'])) {
            if ($this->projects->get($_POST['id']) && $this->receptions->get($_POST['id_reception'])) {
                // on check ici si le montant edité est rempli
                if (isset($_POST['montant_edite']) &&
                    $_POST['montant_edite'] != "" &&
                    $_POST['montant_edite'] > 0
                ) {
                    $this->receptions->montant = ($_POST['montant_edite'] * 100);
                }

                $this->receptions->motif         = $_POST['motif'];
                $this->receptions->id_project    = $_POST['id'];
                $this->receptions->remb_anticipe = 1;
                $this->receptions->status_bo     = 1;
                $this->receptions->update();
            }
        }

        // virements
        $this->lvirements = $this->receptions->select('type = 2 AND status_virement = 1 AND (remb_anticipe = 1 OR (remb_anticipe = 0 AND id_client = 0))', 'id_reception DESC');

        // Status Virement
        $this->statusVirement = array(
            0 => 'Reçu', 1 => 'Attribué manu', 2 => 'Attribué auto', 3 => 'Rejeté', 4 => 'Rejet'
        );
    }

    public function _attribution()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $this->receptions = $this->loadData('receptions');
        $this->receptions->get($this->params[0], 'id_reception');

        if ($this->receptions->id_client != 0) {
            header('location:' . $this->lurl . '/transferts');
            die;
        }
    }

    public function _attribution_ra()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;
        $this->receptions     = $this->loadData('receptions');

        $this->receptions->get($this->params[0], 'id_reception');

        if ($this->receptions->id_project != 0) {
            header('location:' . $this->lurl . '/transferts');
            die;
        }
    }

    public function _attribution_project()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $this->receptions = $this->loadData('receptions');
        $this->receptions->get($this->params[0], 'id_reception');

        if ($this->receptions->id_client != 0) {
            header('location:' . $this->lurl . '/transferts/prelevements');
            die;
        }
    }

    public function _rattrapage_offre_bienvenue()
    {
        $this->dates      = $this->loadLib('dates');

        $this->offres_bienvenues = $this->loadData('offres_bienvenues');
        $this->clients           = $this->loadData('clients');
        $this->companies         = $this->loadData('companies');
        $oWelcomeOfferDetails    = $this->loadData('offres_bienvenues_details');
        $oTransactions           = $this->loadData('transactions');
        $oWalletsLines           = $this->loadData('wallets_lines');
        $oBankUnilend            = $this->loadData('bank_unilend');
        $oLendersAccounts        = $this->loadData('lenders_accounts');

        $oLendersAccounts->get($this->params[0]);
        $this->offres_bienvenues->get(1, 'status = 0 AND id_offre_bienvenue');

        $this->settings->get('Offre de bienvenue motif', 'type');
        $sMotifOffreBienvenue = $this->settings->value;

        $this->clients->get($oLendersAccounts->id_client_owner);
        $this->companies->get($oLendersAccounts->id_company_owner);

        unset($_SESSION['forms']);

        if (isset($_POST['spy_search'])) {
            if (empty($_POST['dateStart']) === false && empty($_POST['dateEnd']) === false) {

                $oDateTimeStart                     = datetime::createFromFormat('d/m/Y', $_POST['dateStart']);
                $oDateTimeEnd                       = datetime::createFromFormat('d/m/Y', $_POST['dateEnd']);
                $sStartDateSQL                      = '"' . $oDateTimeStart->format('Y-m-d') . ' 00:00:00"';
                $sEndDateSQL                        = '"' . $oDateTimeEnd->format('Y-m-d') . ' 23:59:59"';
                $_SESSION['forms']['sStartDateSQL'] = $sStartDateSQL;
                $_SESSION['forms']['sEndDateSQL']   = $sEndDateSQL;

                $this->aLenders = $oLendersAccounts->getLendersWithNoWelcomeOffer($iLenderId = null, $sStartDateSQL, $sEndDateSQL);
            }

            if (empty($_POST['id']) === false) {
                $this->aLenders          = $oLendersAccounts->getLendersWithNoWelcomeOffer($iLenderId = $_POST['id']);
                $_SESSION['forms']['id'] = $_POST['id'];

            }
        }

        if (isset($_POST['affect_welcome_offer']) && isset($this->params[0]) && isset($this->params[1])) {

            $this->clients->get($this->params[0]);
            $this->offres_bienvenues->get($this->params[1]);

            $oWelcomeOfferDetails->id_offre_bienvenue        = $this->offres_bienvenues->id_offre_bienvenue;
            $oWelcomeOfferDetails->motif                     = $sMotifOffreBienvenue;
            $oWelcomeOfferDetails->id_client                 = $this->clients->id_client;
            $oWelcomeOfferDetails->montant                   = $this->offres_bienvenues->montant;
            $oWelcomeOfferDetails->status                    = 0;
            $oWelcomeOfferDetails->id_offre_bienvenue_detail = $oWelcomeOfferDetails->create();

            $oTransactions->id_client                 = $this->clients->id_client;
            $oTransactions->montant                   = $oWelcomeOfferDetails->montant;
            $oTransactions->id_offre_bienvenue_detail = $oWelcomeOfferDetails->id_offre_bienvenue_detail;
            $oTransactions->id_langue                 = 'fr';
            $oTransactions->date_transaction          = date('Y-m-d H:i:s');
            $oTransactions->status                    = '1';
            $oTransactions->etat                      = '1';
            $oTransactions->ip_client                 = $_SERVER['REMOTE_ADDR'];
            $oTransactions->type_transaction          = 16; // TODO use constant once available
            $oTransactions->transaction               = 2;
            $oTransactions->id_transaction            = $oTransactions->create();

            $oWalletsLines->id_lender                = $oLendersAccounts->id_lender_account;
            $oWalletsLines->type_financial_operation = \wallets_lines::TYPE_MONEY_SUPPLY;
            $oWalletsLines->id_transaction           = $oTransactions->id_transaction;
            $oWalletsLines->status                   = 1;
            $oWalletsLines->type                     = 1;
            $oWalletsLines->amount                   = $oWelcomeOfferDetails->montant;
            $oWalletsLines->id_wallet_line           = $oWalletsLines->create();

            $oBankUnilend->id_transaction = $oTransactions->id_transaction;
            $oBankUnilend->montant        = '-' . $oWelcomeOfferDetails->montant;
            $oBankUnilend->type           = \bank_unilend::TYPE_UNILEND_WELCOME_OFFER_PATRONAGE;
            $oBankUnilend->create();

            $oMailsText = $this->loadData('mails_text');
            $oMailsText->get('offre-de-bienvenue', 'lang = "' . $this->language . '" AND type');

            $this->settings->get('Facebook', 'type');
            $sFacebook = $this->settings->value;

            $this->settings->get('Twitter', 'type');
            $sTwitter = $this->settings->value;

            $aVariables = array(
                'surl'            => $this->surl,
                'url'             => $this->furl,
                'prenom_p'        => $this->clients->prenom,
                'projets'         => $this->furl . '/projets-a-financer',
                'offre_bienvenue' => $this->ficelle->formatNumber($oWelcomeOfferDetails->montant / 100),
                'lien_fb'         => $sFacebook,
                'lien_tw'         => $sTwitter
            );

            $this->email = $this->loadLib('email');
            $this->email->setFrom($oMailsText->exp_email, utf8_decode($oMailsText->exp_name));
            $this->email->setSubject(stripslashes(utf8_decode($oMailsText->subject)));
            $this->email->setHTMLBody(stripslashes(strtr(utf8_decode($oMailsText->content), $this->tnmp->constructionVariablesServeur($aVariables))));

            if ($this->Config['env'] === 'prod') {
                Mailer::sendNMP($this->email, $this->mails_filer, $oMailsText->id_textemail, $this->clients->email, $tabFiler);
                $this->tnmp->sendMailNMP($tabFiler, $aVariables, $oMailsText->nmp_secure, $oMailsText->id_nmp, $oMailsText->nmp_unique, $oMailsText->mode);
            } else {
                $this->email->addRecipient(trim($this->clients->email));
                Mailer::send($this->email, $this->mails_filer, $oMailsText->id_textemail);
            }
        }
    }

    public function _csv_rattrapage_offre_bienvenue()
    {
        $oLendersAccounts = $this->loadData('lenders_accounts');

        if (isset($_SESSION['forms']['sStartDateSQL']) && isset($_SESSION['forms']['sEndDateSQL'])) {
            $this->aLenders = $oLendersAccounts->getLendersWithNoWelcomeOffer(
                $iLenderId = null,
                $_SESSION['forms']['sStartDateSQL'],
                $_SESSION['forms']['sEndDateSQL']
            );
        }

        if (isset($_SESSION['forms']['id'])) {
            $this->aLenders = $oLendersAccounts->getLendersWithNoWelcomeOffer($_SESSION['forms']['id']);
        }

        $sFilename = 'ratrappage_offre_bienvenue';

        $aColumnHeaders = array('ID Lender', 'Nom ou Raison Sociale', 'Prénom', 'Date de création', 'Date de validation');

        foreach ($this->aLenders as $key =>$aLender) {

            $aData[] = array(
                $aLender['id_lender'],
                empty($aLender['company']) ? $aLender['nom'] : $aLender['company'],
                empty($aLender['company']) ? $aLender['prenom'] : '',
                $this->dates->formatDateMysqltoShortFR($aLender['date_creation']),
                (empty($aLender['date_validation']) === false) ? $this->dates->formatDateMysqltoShortFR($aLender['date_validation']) : ''
            );
        }
        $this->exportCSV($aColumnHeaders, $aData, $sFilename);
    }

    private function exportCSV($aColumnHeaders, $aData, $sFilename)
    {
        $sSeparator  = "\t";
        $sEol = "\n";
        $sCSV  =  count($aColumnHeaders) ? '"'. implode('"'.$sSeparator.'"', $aColumnHeaders).'"'.$sEol : '';

        foreach ($aData as $row) {
            $sCSV .= '"'. implode('"'.$sSeparator.'"', $row).'"'.$sEol;
        }

        $sEncodedCSV = mb_convert_encoding($sCSV, 'UTF-16LE', 'UTF-8');

        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="'.$sFilename.'.csv"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: '. strlen($sEncodedCSV));
        echo chr(255) . chr(254) . $sEncodedCSV;
        exit;

    }

    public function _affect_welcome_offer()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $this->offres_bienvenues = $this->loadData('offres_bienvenues');
        $this->clients           = $this->loadData('clients');
        $this->companies         = $this->loadData('companies');
        $oLendersAccounts        = $this->loadData('lenders_accounts');

        $oLendersAccounts->get($this->params[0]);
        $this->offres_bienvenues->get(1, 'status = 0 AND id_offre_bienvenue');

        $this->clients->get($oLendersAccounts->id_client_owner);
        $this->companies->get($oLendersAccounts->id_company_owner);

    }


}
