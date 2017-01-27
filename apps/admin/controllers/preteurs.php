<?php

class preteursController extends bootstrap
{
    /**
     * @var attachment_helper
     */
    private $attachmentHelper;

    public function initialize()
    {
        parent::initialize();

        include $this->path . '/apps/default/controllers/pdf.php';

        $this->catchAll = true;

        $this->users->checkAccess('preteurs');

        $this->menu_admin = 'preteurs';
    }

    /**
     * @todo we load to many things here in all cases. Avoid this
     */
    public function loadGestionData()
    {
        $this->clients                = $this->loadData('clients');
        $this->clients_adresses       = $this->loadData('clients_adresses');
        $this->clients_mandats        = $this->loadData('clients_mandats');
        $this->clients_status         = $this->loadData('clients_status');
        $this->clients_status_history = $this->loadData('clients_status_history');
        $this->lenders_accounts       = $this->loadData('lenders_accounts');
        $this->transactions           = $this->loadData('transactions');
        $this->loans                  = $this->loadData('loans');
        $this->bids                   = $this->loadData('bids');
        $this->companies              = $this->loadData('companies');
        $this->projects               = $this->loadData('projects');
        $this->wallets_lines          = $this->loadData('wallets_lines');
        $this->attachment             = $this->loadData('attachment');
        $this->attachment_type        = $this->loadData('attachment_type');
    }

    public function _default()
    {
        header('Location:' . $this->lurl . '/preteurs/search');
        die;
    }

    public function _gestion()
    {
        $this->clients = $this->loadData('clients');

        if (isset($_POST['form_search_preteur'])) {
            if (empty($_POST['id']) && empty($_POST['nom']) && empty($_POST['email']) && empty($_POST['prenom']) && empty($_POST['raison_sociale'])) {
                $_SESSION['error_search'][]  = 'Veuillez remplir au moins un champ';
            }

            $email = empty($_POST['email']) ? '' : filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
            if (false === $email) {
                $_SESSION['error_search'][]  = 'Format de l\'email est non valide';
            }

            $clientId = empty($_POST['id']) ? '' : filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
            if (false === $clientId) {
                $_SESSION['error_search'][]  = 'L\'id du client doit être numérique';
            }

            $lastName = empty($_POST['nom']) ? '' : filter_var($_POST['nom'], FILTER_SANITIZE_STRING);
            if (false === $lastName) {
                $_SESSION['error_search'][]  = 'Le format du nom n\'est pas valide';
            }

            $firstName = empty($_POST['prenom']) ? '' : filter_var($_POST['prenom'], FILTER_SANITIZE_STRING);
            if (false === $firstName) {
                $_SESSION['error_search'][]  = 'Le format du prenom n\'est pas valide';
            }

            $companyName = empty($_POST['raison_sociale']) ? '' : filter_var($_POST['raison_sociale'], FILTER_SANITIZE_STRING);
            if (false === $companyName) {
                $_SESSION['error_search'][]  = 'Le format de la raison sociale n\'est pas valide';
            }

            if (false === empty($_SESSION['error_search'])) {
                header('Location:' . $this->lurl . '/preteurs/search');
                die;
            }

            $nonValide = (isset($_POST['nonValide']) && $_POST['nonValide'] != false) ? 1 : '';

            $this->lPreteurs = $this->clients->searchPreteurs($clientId, $lastName, $email, $firstName, $companyName, $nonValide);

            if (1 == count($this->lPreteurs)) {
                $lender = $this->lPreteurs[0];
                header('Location:' . $this->lurl . '/preteurs/edit/' . $lender['id_lender_account']);
                die;
            }

            $_SESSION['freeow']['title']   = 'Recherche d\'un prêteur';
            $_SESSION['freeow']['message'] = 'La recherche est termin&eacute;e !';
        } else {
            $this->lPreteurs = $this->clients->searchPreteurs('', '', '', '', '', null, '', '0', '300');
        }
    }

    public function _search()
    {
        $this->autoFireHeader = true;
        $this->autoFireHead   = true;
        $this->autoFireFooter = true;
        $this->autoFireDebug  = false;

        $_SESSION['request_url'] = $this->url;
    }

    public function _search_non_inscripts()
    {
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;
    }

    public function _edit()
    {
        /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
        $translator = $this->get('translator');

        $this->projects      = $this->loadData('projects');
        $this->transactions  = $this->loadData('transactions');
        $this->wallets_lines = $this->loadData('wallets_lines');

        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $this->clients = $this->loadData('clients');

        if ($this->lenders_accounts->get($this->params[0], 'id_lender_account') && $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client')) {

            $this->clients_adresses = $this->loadData('clients_adresses');
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            $this->companies = $this->loadData('companies');
            if (in_array($this->clients->type, [clients::TYPE_LEGAL_ENTITY, clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
                $this->companies->get($this->lenders_accounts->id_company_owner, 'id_company');
            }

            $this->loans    = $this->loadData('loans');
            $this->nb_pret  = $this->loans->counter('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND status = ' . \loans::STATUS_ACCEPTED);
            $this->txMoyen  = $this->loans->getAvgPrets($this->lenders_accounts->id_lender_account);
            $this->sumPrets = $this->loans->sumPrets($this->lenders_accounts->id_lender_account);

            if (isset($this->params[1])) {
                $this->lEncheres = $this->loans->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND YEAR(added) = ' . $this->params[1] . ' AND status = ' . \loans::STATUS_ACCEPTED);
            } else {
                $this->lEncheres = $this->loans->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND YEAR(added) = YEAR(CURDATE()) AND status = ' . \loans::STATUS_ACCEPTED);
            }

            $this->SumDepot       = $this->transactions->getLenderDepositedAmount($this->lenders_accounts);
            $this->SumInscription = $this->wallets_lines->getSumDepot($this->lenders_accounts->id_lender_account, '10');

            $this->echeanciers = $this->loadData('echeanciers');
            $this->sumRembInte = $this->echeanciers->getRepaidInterests(['id_lender' => $this->lenders_accounts->id_lender_account]);

            try {
                $this->nextRemb = $this->echeanciers->getNextRepaymentAmountInDateRange($this->lenders_accounts->id_lender_account, (new \DateTime('first day of next month'))->format('Y-m-d 00:00:00'), (new \DateTime('last day of next month'))->format('Y-m-d 23:59:59'));
            } catch (\Exception $exception) {
                /** @var \Psr\Log\LoggerInterface $logger */
                $logger = $this->get('logger');
                $logger->error('Could not get next repayment amount (id_lender = ' . $this->lenders_accounts->id_lender_account . ')', ['class'     => __CLASS__, 'function'  => __FUNCTION__, 'id_lender' => $this->lenders_accounts->id_lender_account ]);
                $this->nextRemb = 0;
            }

            $this->sumRembMontant = $this->echeanciers->getRepaidAmount(['id_lender' => $this->lenders_accounts->id_lender_account]);

            $this->bids           = $this->loadData('bids');
            $this->avgPreteur     = $this->bids->getAvgPreteur($this->lenders_accounts->id_lender_account, 'amount', '1,2');
            $this->sumBidsEncours = $this->bids->sumBidsEncours($this->lenders_accounts->id_lender_account);
            $this->lBids          = $this->bids->select('id_lender_account = ' . $this->lenders_accounts->id_lender_account . ' AND status = 0', 'added DESC');
            $this->NbBids         = count($this->lBids);

            $this->clients_mandats = $this->loadData('clients_mandats');
            $this->clients_mandats->get($this->clients->id_client, 'id_client');

            $this->attachment       = $this->loadData('attachment');
            $this->attachment_type  = $this->loadData('attachment_type');
            $this->attachments      = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);
            $this->aAttachmentTypes = $this->attachment_type->getAllTypesForLender($this->language);

            $this->aAvailableAttachments = [];

            $this->setAttachments($this->lenders_accounts->id_client_owner, $this->aAttachmentTypes);
            $this->aAvailableAttachments = $this->aIdentity + $this->aDomicile + $this->aRibAndFiscale + $this->aOther;

            /** @var \lender_tax_exemption $oLenderTaxExemption */
            $oLenderTaxExemption   = $this->loadData('lender_tax_exemption');
            $this->aExemptionYears = array_column($oLenderTaxExemption->select('id_lender = ' . $this->lenders_accounts->id_lender_account, 'year DESC'), 'year');

            $this->lesStatuts = [
                \transactions_types::TYPE_LENDER_SUBSCRIPTION            => $translator->trans('preteur-profile_versement-initial'),
                \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT      => $translator->trans('preteur-profile_alimentation-cb'),
                \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT    => $translator->trans('preteur-profile_alimentation-virement'),
                \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL       => 'Remboursement de capital',
                \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS     => 'Remboursement d\'intérêts',
                \transactions_types::TYPE_DIRECT_DEBIT                   => $translator->trans('preteur-profile_alimentation-prelevement'),
                \transactions_types::TYPE_LENDER_WITHDRAWAL              => $translator->trans('preteur-profile_retrait'),
                \transactions_types::TYPE_LENDER_REGULATION              => 'Régularisation prêteur',
                \transactions_types::TYPE_WELCOME_OFFER                  => 'Offre de bienvenue',
                \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION     => 'Retrait offre de bienvenue',
                \transactions_types::TYPE_SPONSORSHIP_SPONSORED_REWARD   => $translator->trans('preteur-operations-vos-operations_gain-filleul'),
                \transactions_types::TYPE_SPONSORSHIP_SPONSOR_REWARD     => $translator->trans('preteur-operations-vos-operations_gain-parrain'),
                \transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT => $translator->trans('preteur-operations-vos-operations_remboursement-anticipe'),
                \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT   => $translator->trans('preteur-operations-vos-operations_remboursement-anticipe-preteur'),
                \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT      => $translator->trans('preteur-operations-vos-operations_remboursement-recouvrement-preteur'),
                \transactions_types::TYPE_LENDER_BALANCE_TRANSFER        => $translator->trans('preteur-operations-vos-operations_balance-transfer')
            ];

            $this->solde        = $this->transactions->getSolde($this->clients->id_client);
            $this->soldeRetrait = $this->transactions->sum('status = ' . \transactions::STATUS_VALID . ' AND type_transaction = ' . \transactions_types::TYPE_LENDER_WITHDRAWAL . ' AND id_client = ' . $this->clients->id_client, 'montant');
            $this->soldeRetrait = abs($this->soldeRetrait / 100);
            $this->lTrans       = $this->transactions->select('type_transaction IN (' . implode(', ', array_keys($this->lesStatuts)) . ') AND status = ' . \transactions::STATUS_VALID . ' AND id_client = ' . $this->clients->id_client . ' AND YEAR(date_transaction) = ' . date('Y'), 'added DESC');

            /** @var \transfer $transfer */
            $transfer           = $this->loadData('transfer');
            $transfersForClient = $transfer->select('id_client_origin = ' . $this->clients->id_client . ' OR id_client_receiver = ' . $this->clients->id_client);

            $this->transferDocuments = [];
            foreach ($transfersForClient as $transfer) {
                $transferDocument = $this->attachment->select('id_owner = ' . $transfer['id_transfer'] . ' AND id_type = ' . \attachment_type::TRANSFER_CERTIFICATE);
                if (false === empty($transferDocument)) {
                    $transferDocument                                   = $transferDocument[0];
                    $this->transferDocuments[$transferDocument['path']] = $transferDocument;
                }
            }

            $this->getMessageAboutClientStatus();
        }
    }

    private function setAttachments($iIdClient, $oAttachmentTypes)
    {
        /** @var \greenpoint_attachment $oGreenPointAttachment */
        $oGreenPointAttachment       = $this->loadData('greenpoint_attachment');
        $aGreenpointAttachmentStatus = [];
        $this->aIdentity             = [];
        $this->aDomicile             = [];
        $this->aRibAndFiscale        = [];
        $this->aOther                = [];
        $this->aRibAndFiscaleToAdd   = [];
        $this->aIdentityToAdd        = [];
        $this->aDomicileToAdd        = [];
        $this->aOtherToAdd           = [];

        foreach ($oGreenPointAttachment->select('id_client = ' . $iIdClient) as $aGPAS) {
            $aGreenpointAttachmentStatus[$aGPAS['id_attachment']] = $aGPAS;
        }

        foreach ($oAttachmentTypes as $aAttachmentType) {
            $iType = $aAttachmentType['id'];
            switch ($iType) {
                case attachment_type::CNI_PASSPORTE:
                case attachment_type::CNI_PASSPORTE_VERSO:
                case attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT:
                case attachment_type::CNI_PASSPORTE_DIRIGEANT:
                    $this->organizeAttachments($this->aIdentity, $this->aIdentityToAdd, $aGreenpointAttachmentStatus, $iType, $aAttachmentType);
                    break;
                case attachment_type::RIB:
                case attachment_type::JUSTIFICATIF_FISCAL:
                    $this->organizeAttachments($this->aRibAndFiscale, $this->aRibAndFiscaleToAdd, $aGreenpointAttachmentStatus, $iType, $aAttachmentType);
                    break;
                case attachment_type::JUSTIFICATIF_DOMICILE:
                case attachment_type::ATTESTATION_HEBERGEMENT_TIERS:
                    $this->organizeAttachments($this->aDomicile, $this->aDomicileToAdd, $aGreenpointAttachmentStatus, $iType, $aAttachmentType);
                    break;
                default:
                    $this->organizeAttachments($this->aOther, $this->aOtherToAdd, $aGreenpointAttachmentStatus, $iType, $aAttachmentType);
                    break;
            }
        }
    }

    public function _edit_preteur()
    {
        $this->clients_mandats         = $this->loadData('clients_mandats');
        $this->nationalites            = $this->loadData('nationalites_v2');
        $this->pays                    = $this->loadData('pays_v2');
        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
        $this->lNatio                  = $this->nationalites->select();
        $this->lPays                   = $this->pays->select('', 'ordre ASC');
        $this->settings                = $this->loadData('settings');
        /** @var \Unilend\Bundle\TranslationBundle\Service\TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        $this->completude_wording = $translationManager->getAllTranslationsForSection('lender-completeness');

        $this->settings->get("Liste deroulante conseil externe de l'entreprise", 'type');
        $this->conseil_externe = json_decode($this->settings->value, true);

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager $clientStatusManager */
        $clientStatusManager = $this->get('unilend.service.client_status_manager');

        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $this->clients = $this->loadData('clients');

        if ($this->lenders_accounts->get($this->params[0], 'id_lender_account') && $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client')) {
            $this->clients_adresses = $this->loadData('clients_adresses');
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            $this->companies = $this->loadData('companies');

            if (in_array($this->clients->type, [clients::TYPE_LEGAL_ENTITY, clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
                $this->companies->get($this->lenders_accounts->id_company_owner, 'id_company');

                $this->meme_adresse_fiscal = $this->companies->status_adresse_correspondance;
                $this->adresse_fiscal      = $this->companies->adresse1;
                $this->city_fiscal         = $this->companies->city;
                $this->zip_fiscal          = $this->companies->zip;

                $this->settings->get("Liste deroulante origine des fonds societe", 'status = 1 AND type');
                $this->origine_fonds = $this->settings->value;
                $this->origine_fonds = explode(';', $this->origine_fonds);

            } else {
                $this->meme_adresse_fiscal = $this->clients_adresses->meme_adresse_fiscal;
                $this->adresse_fiscal      = $this->clients_adresses->adresse_fiscal;
                $this->city_fiscal         = $this->clients_adresses->ville_fiscal;
                $this->zip_fiscal          = $this->clients_adresses->cp_fiscal;

                /** @var \lender_tax_exemption $oLenderTaxExemption */
                $oLenderTaxExemption   = $this->loadData('lender_tax_exemption');
                $this->taxExemption    = $oLenderTaxExemption->getLenderExemptionHistory($this->lenders_accounts->id_lender_account);
                $this->aExemptionYears = array_column($this->taxExemption, 'year');
                $this->iNextYear       = date('Y') + 1;

                $this->settings->get("Liste deroulante origine des fonds", 'status = 1 AND type');
                $this->origine_fonds                 = $this->settings->value;
                $this->origine_fonds                 = explode(';', $this->origine_fonds);
                $this->taxExemptionUserHistoryAction = $this->getTaxExemptionHistoryActionDetails($this->users_history->getTaxExemptionHistoryAction($this->clients->id_client));
            }

            if ($birthDate = \DateTime::createFromFormat('Y-m-d', $this->clients->naissance)) {
                $this->naissance = $birthDate->format('d/m/Y');
            } else {
                $this->naissance = '';
            }

            if ($this->lenders_accounts->iban != '') {
                $this->iban1 = substr($this->lenders_accounts->iban, 0, 4);
                $this->iban2 = substr($this->lenders_accounts->iban, 4, 4);
                $this->iban3 = substr($this->lenders_accounts->iban, 8, 4);
                $this->iban4 = substr($this->lenders_accounts->iban, 12, 4);
                $this->iban5 = substr($this->lenders_accounts->iban, 16, 4);
                $this->iban6 = substr($this->lenders_accounts->iban, 20, 4);
                $this->iban7 = substr($this->lenders_accounts->iban, 24, 3);
            }

            if ($this->clients->telephone != '') {
                trim(chunk_split($this->clients->telephone, 2, ' '));
            }
            if ($this->companies->phone != '') {
                $this->companies->phone = trim(chunk_split($this->companies->phone, 2, ' '));
            }
            if ($this->companies->phone_dirigeant != '') {
                $this->companies->phone_dirigeant = trim(chunk_split($this->companies->phone_dirigeant, 2, ' '));
            }

            $this->clients_status = $this->loadData('clients_status');
            $this->clients_status->getLastStatut($this->clients->id_client);

            $this->clients_status_history   = $this->loadData('clients_status_history');
            $this->oClientsStatusForHistory = $this->loadData('clients_status');
            $this->lActions                 = $this->clients_status_history->select('id_client = ' . $this->clients->id_client, 'added DESC');
            $this->aTaxationCountryHistory  = $this->getTaxationHistory($this->lenders_accounts->id_lender_account);

            $this->getMessageAboutClientStatus();

            $this->attachment       = $this->loadData('attachment');
            $this->attachment_type  = $this->loadData('attachment_type');
            $this->attachments      = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);
            $this->aAttachmentTypes = $this->attachment_type->getAllTypesForLender($this->language);

            $this->setAttachments($this->lenders_accounts->id_client_owner, $this->aAttachmentTypes);

            $this->loadJs('default/component/add-file-input');

            $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
            $this->lAcceptCGV              = $this->acceptations_legal_docs->select('id_client = ' . $this->clients->id_client);

            /** @var \greenpoint_attachment_detail $greenPointDetail */
            $greenPointDetail            = $this->loadData('greenpoint_attachment_detail');
            $this->lenderIdentityMRZData = $greenPointDetail->getIdentityData($this->clients->id_client, \attachment_type::CNI_PASSPORTE);
            $this->hostIdentityMRZData   = $greenPointDetail->getIdentityData($this->clients->id_client, \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT);

            if (isset($_POST['send_completude'])) {
                $this->sendCompletenessRequest();
                $clientStatusManager->addClientStatus($this->clients, $_SESSION['user']['id_user'], \clients_status::COMPLETENESS, $_SESSION['content_email_completude'][$this->clients->id_client]);

                unset($_SESSION['content_email_completude'][$this->clients->id_client]);

                $_SESSION['email_completude_confirm'] = true;

                header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $this->lenders_accounts->id_lender_account);
                die;
            } elseif (isset($_POST['send_edit_preteur'])) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\WelcomeOfferManager $welcomeOfferManager */
                $welcomeOfferManager = $this->get('unilend.service.welcome_offer_manager');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
                $mailerManager = $this->get('unilend.service.email_manager');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\TaxManager $taxManager */
                $taxManager = $this->get('unilend.service.tax_manager');

                if (in_array($this->clients->type, [\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER])) {

                    if (false === empty($_POST['meme-adresse'])) {
                        $this->clients_adresses->meme_adresse_fiscal = 1;
                    } else {
                        $this->clients_adresses->meme_adresse_fiscal = 0;
                    }
                    $bTaxCountryChanged                     = false === empty($_POST['id_pays_fiscal']) && $this->clients_adresses->id_pays_fiscal != $_POST['id_pays_fiscal'];
                    $this->clients_adresses->adresse_fiscal = $_POST['adresse'];
                    $this->clients_adresses->ville_fiscal   = $_POST['ville'];
                    $this->clients_adresses->cp_fiscal      = $_POST['cp'];
                    $this->clients_adresses->id_pays_fiscal = $_POST['id_pays_fiscal'];

                    if ($this->clients_adresses->meme_adresse_fiscal == 0) {
                        $this->clients_adresses->adresse1 = $_POST['adresse2'];
                        $this->clients_adresses->ville    = $_POST['ville2'];
                        $this->clients_adresses->cp       = $_POST['cp2'];
                        $this->clients_adresses->id_pays  = $_POST['id_pays'];
                    } else {
                        $this->clients_adresses->adresse1 = $_POST['adresse'];
                        $this->clients_adresses->ville    = $_POST['ville'];
                        $this->clients_adresses->cp       = $_POST['cp'];
                        $this->clients_adresses->id_pays  = $_POST['id_pays_fiscal'];
                    }

                    $this->clients->civilite  = $_POST['civilite'];
                    $this->clients->nom       = $this->ficelle->majNom($_POST['nom-famille']);
                    $this->clients->nom_usage = $this->ficelle->majNom($_POST['nom-usage']);
                    $this->clients->prenom    = $this->ficelle->majNom($_POST['prenom']);

                    //// check doublon mail ////
                    if ($this->isEmailUnique($_POST['email'], $this->clients)) {
                        $this->clients->email = $_POST['email'];
                    }

                    $oBirthday = new \DateTime(str_replace('/', '-', $_POST['naissance']));

                    $this->clients->telephone         = str_replace(' ', '', $_POST['phone']);
                    $this->clients->mobile            = str_replace(' ', '', $_POST['mobile']);
                    $this->clients->ville_naissance   = $_POST['com-naissance'];
                    $this->clients->insee_birth       = $_POST['insee_birth'];
                    $this->clients->naissance         = $oBirthday->format('Y-m-d');
                    $this->clients->id_pays_naissance = $_POST['id_pays_naissance'];
                    $this->clients->id_nationalite    = $_POST['nationalite'];
                    $this->clients->id_langue         = 'fr';
                    $this->clients->type              = 1;
                    $this->clients->fonction          = '';
                    $this->clients->update();

                    $this->lenders_accounts->id_company_owner = 0;

                    $this->lenders_accounts->origine_des_fonds = $_POST['origine_des_fonds'];
                    if ($this->lenders_accounts->origine_des_fonds == '1000000') {
                        $this->lenders_accounts->precision = $_POST['preciser'];
                    } else {
                        $this->lenders_accounts->precision = '';
                    }

                    foreach ($_FILES as $field => $file) {
                        // Field name = attachment type id
                        $iAttachmentType = $field;
                        if ('' !== $file['name']) {
                            $this->uploadAttachment($this->lenders_accounts->id_lender_account, $field, $iAttachmentType);
                        }
                    }

                    if (isset($_FILES['mandat']) && $_FILES['mandat']['name'] != '') {
                        if ($this->clients_mandats->get($this->clients->id_client, 'id_client')) {
                            $create = false;
                        } else {
                            $create = true;
                        }

                        $this->upload->setUploadDir($this->path, 'protected/pdf/mandat/');
                        if ($this->upload->doUpload('mandat')) {
                            if ($this->clients_mandats->name != '') {
                                @unlink($this->path . 'protected/pdf/mandat/' . $this->clients_mandats->name);
                            }
                            $this->clients_mandats->name          = $this->upload->getName();
                            $this->clients_mandats->id_client     = $this->clients->id_client;
                            $this->clients_mandats->id_universign = 'no_universign';
                            $this->clients_mandats->url_pdf       = '/pdf/mandat/' . $this->clients->hash . '/';
                            $this->clients_mandats->status        = \clients_mandats::STATUS_SIGNED;

                            if ($create == true) {
                                $this->clients_mandats->create();
                            } else {
                                $this->clients_mandats->update();
                            }
                        }
                    }

                    if (isset($_POST['tax_exemption'])) {
                        foreach ($_POST['tax_exemption'] as $iExemptionYear => $iExemptionValue) {
                            if (false === in_array($iExemptionYear, $this->aExemptionYears)) {
                                /** @var \lender_tax_exemption $oLenderTaxExemption */
                                $oLenderTaxExemption              = $this->loadData('lender_tax_exemption');
                                $oLenderTaxExemption->id_lender   = $this->lenders_accounts->id_lender_account;
                                $oLenderTaxExemption->iso_country = 'FR';
                                $oLenderTaxExemption->year        = $iExemptionYear;
                                $oLenderTaxExemption->id_user     = $_SESSION['user']['id_user'];
                                $oLenderTaxExemption->create();
                                $taxExemptionHistory[] = ['year' => $oLenderTaxExemption->year, 'action' => 'adding'];
                            }
                        }
                    }

                    if (in_array($this->iNextYear, $this->aExemptionYears) && false === isset($_POST['tax_exemption'][$this->iNextYear])) {
                        $oLenderTaxExemption->get($this->lenders_accounts->id_lender_account . '" AND year = ' . $this->iNextYear . ' AND iso_country = "FR', 'id_lender');
                        $taxExemptionHistory[] = ['year' => $oLenderTaxExemption->year, 'action' => 'deletion'];
                        $oLenderTaxExemption->delete($oLenderTaxExemption->id_lender_tax_exemption);
                    }

                    if (false === empty($taxExemptionHistory)) {
                        $this->users_history->histo(\users_history::FORM_ID_LENDER, \users_history::FORM_NAME_TAX_EXEMPTION, $_SESSION['user']['id_user'], serialize([
                            'id_client'     => $this->clients->id_client,
                            'modifications' => $taxExemptionHistory
                        ]));
                    }

                    $this->clients_adresses->update();
                    $this->lenders_accounts->update();
                    $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);

                    $serialize = serialize(['id_client' => $this->clients->id_client, 'post'      => $_POST, 'files'     => $_FILES]);
                    $this->users_history->histo(\users_history::FORM_ID_LENDER, 'modif info preteur', $_SESSION['user']['id_user'], $serialize);

                    if (isset($_POST['statut_valider_preteur']) && 1 == $_POST['statut_valider_preteur']) {
                        /** @var \Psr\Log\LoggerInterface $logger */
                        $logger = $this->get('logger');

                        $aExistingClient       = $this->clients->getDuplicates($this->clients->nom, $this->clients->prenom, $this->clients->naissance);
                        $aExistingClient       = array_shift($aExistingClient);
                        $iOriginForUserHistory = 3;

                        if (false === empty($aExistingClient) && $aExistingClient['id_client'] != $this->clients->id_client) {
                            $this->changeClientStatus($this->clients, \clients::STATUS_OFFLINE, $iOriginForUserHistory);
                            $clientStatusManager->addClientStatus($this->clients, $_SESSION['user']['id_user'], \clients_status::CLOSED_BY_UNILEND, 'Doublon avec client ID : ' . $aExistingClient['id_client']);
                            header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $this->lenders_accounts->id_lender_account);
                            die;
                        } elseif (1 == $this->clients->origine && 0 == $this->clients_status_history->counter('id_client = ' . $this->clients->id_client . ' AND id_client_status = (SELECT cs.id_client_status FROM clients_status cs WHERE cs.status = ' . \clients_status::VALIDATED . ')')) {
                            $response = $welcomeOfferManager->createWelcomeOffer($this->clients);
                            $logger->info('Client ID: ' . $this->clients->id_client . ' Welcome offer creation result: ' . json_encode($response), ['class'     => __CLASS__, 'function'  => __FUNCTION__, 'id_lender' => $this->clients->id_client ]);
                        } else {
                            $logger->info('Client ID: ' . $this->clients->id_client . ' Welcome offer not created. The client has been validated by the past or the origine != 1.', [ 'class'     => __CLASS__, 'function'  => __FUNCTION__, 'id_lender' => $this->clients->id_client]);
                        }

                        $clientStatusManager->addClientStatus($this->clients, $_SESSION['user']['id_user'], \clients_status::VALIDATED);

                        /** @var \clients_gestion_notifications clients_gestion_notifications */
                        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');

                        $this->lNotifs = $this->clients_gestion_notifications->select('id_client = ' . $this->clients->id_client);

                        if ($this->clients_status_history->counter('id_client = ' . $this->clients->id_client . ' AND id_client_status = 5') > 0) {
                            $mailerManager->sendClientValidationEmail($this->clients, 'preteur-validation-modification-compte');
                        } else {
                            $mailerManager->sendClientValidationEmail($this->clients, 'preteur-confirmation-activation');
                        }
                        $taxManager->addTaxToApply($this->clients, $this->lenders_accounts, $this->clients_adresses, $_SESSION['user']['id_user']);

                        if (true === $bTaxCountryChanged) {
                            $bTaxCountryChanged = false;
                        }
                        $_SESSION['compte_valide'] = true;
                    }

                    if (true === $bTaxCountryChanged) {
                        $taxManager->addTaxToApply($this->clients, $this->lenders_accounts, $this->clients_adresses, $_SESSION['user']['id_user']);
                    }

                    $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);
                    header('location:' . $this->lurl . '/preteurs/edit_preteur/' . $this->lenders_accounts->id_lender_account);
                    die;
                } elseif (in_array($this->clients->type, [\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
                    $this->companies->name         = $_POST['raison-sociale'];
                    $this->companies->forme        = $_POST['form-juridique'];
                    $this->companies->capital      = str_replace(' ', '', $_POST['capital-sociale']);
                    $this->companies->siren        = $_POST['siren'];
                    $this->companies->siret        = $_POST['siret']; //(19/11/2014)
                    $this->companies->phone        = str_replace(' ', '', $_POST['phone-societe']);
                    $this->companies->tribunal_com = $_POST['tribunal_com'];

                    ////////////////////////////////////
                    // On verifie meme adresse ou pas //
                    ////////////////////////////////////
                    if (false === empty($_POST['meme-adresse'])) {
                        $this->companies->status_adresse_correspondance = '1';
                    } else {
                        $this->companies->status_adresse_correspondance = '0';
                    }
                    // adresse fiscal (siege de l'entreprise)
                    $this->companies->adresse1 = $_POST['adresse'];
                    $this->companies->city     = $_POST['ville'];
                    $this->companies->zip      = $_POST['cp'];

                    // adresse fiscal (dans client entreprise) on vide car c'est pour les particulier ca
                    $this->clients_adresses->adresse_fiscal = '';
                    $this->clients_adresses->ville_fiscal   = '';
                    $this->clients_adresses->cp_fiscal      = '';

                    // pas la meme
                    if ($this->companies->status_adresse_correspondance == 0) {
                        $this->clients_adresses->adresse1 = $_POST['adresse2'];
                        $this->clients_adresses->ville    = $_POST['ville2'];
                        $this->clients_adresses->cp       = $_POST['cp2'];
                    } else {
                        $this->clients_adresses->adresse1 = $_POST['adresse'];
                        $this->clients_adresses->ville    = $_POST['ville'];
                        $this->clients_adresses->cp       = $_POST['cp'];
                    }

                    $this->companies->status_client = $_POST['enterprise']; // radio 1 dirigeant 2 pas dirigeant 3 externe

                    $this->clients->civilite = $_POST['civilite_e'];
                    $this->clients->nom      = $this->ficelle->majNom($_POST['nom_e']);
                    $this->clients->prenom   = $this->ficelle->majNom($_POST['prenom_e']);
                    $this->clients->fonction = $_POST['fonction_e'];

                    //// check doublon mail ////
                    if ($this->isEmailUnique($_POST['email_e'], $this->clients)) {
                        $this->clients->email = $_POST['email_e'];
                    }

                    $this->clients->telephone = str_replace(' ', '', $_POST['phone_e']);

                    //extern ou non dirigeant
                    if ($this->companies->status_client == 2 || $this->companies->status_client == 3) {
                        $this->companies->civilite_dirigeant = $_POST['civilite2_e'];
                        $this->companies->nom_dirigeant      = $this->ficelle->majNom($_POST['nom2_e']);
                        $this->companies->prenom_dirigeant   = $this->ficelle->majNom($_POST['prenom2_e']);
                        $this->companies->fonction_dirigeant = $_POST['fonction2_e'];
                        $this->companies->email_dirigeant    = $_POST['email2_e'];
                        $this->companies->phone_dirigeant    = str_replace(' ', '', $_POST['phone2_e']);

                        // externe
                        if ($this->companies->status_client == 3) {
                            $this->companies->status_conseil_externe_entreprise   = $_POST['status_conseil_externe_entreprise'];
                            $this->companies->preciser_conseil_externe_entreprise = $_POST['preciser_conseil_externe_entreprise'];
                        }
                    } else {
                        $this->companies->civilite_dirigeant = '';
                        $this->companies->nom_dirigeant      = '';
                        $this->companies->prenom_dirigeant   = '';
                        $this->companies->fonction_dirigeant = '';
                        $this->companies->email_dirigeant    = '';
                        $this->companies->phone_dirigeant    = '';
                    }

                    // Si form societe ok
                    $this->clients->id_langue       = 'fr';
                    $this->clients->type            = 2;
                    $this->clients->nom_usage       = '';
                    $this->clients->naissance       = '0000-00-00';
                    $this->clients->ville_naissance = '';

                    if ($this->companies->exist($this->clients->id_client, 'id_client_owner')) {
                        $this->companies->update();
                    } else {
                        $this->companies->id_client_owner = $this->clients->id_client;
                        $this->companies->create();
                    }

                    $this->lenders_accounts->origine_des_fonds = $_POST['origine_des_fonds'];
                    if ($this->lenders_accounts->origine_des_fonds == '1000000') {
                        $this->lenders_accounts->precision = $_POST['preciser'];
                    } else {
                        $this->lenders_accounts->precision = '';
                    }

                    foreach ($_FILES as $field => $file) {
                        $iAttachmentType = $field;
                        if ('' !== $file['name']) {
                            $this->uploadAttachment($this->lenders_accounts->id_lender_account, $field, $iAttachmentType);
                        }
                    }

                    if (isset($_FILES['mandat']) && $_FILES['mandat']['name'] != '') {
                        if ($this->clients_mandats->get($this->clients->id_client, 'id_client')) {
                            $create = false;
                        } else {
                            $create = true;
                        }

                        $this->upload->setUploadDir($this->path, 'protected/pdf/mandat/');
                        if ($this->upload->doUpload('mandat')) {
                            if ($this->clients_mandats->name != '') {
                                @unlink($this->path . 'protected/pdf/mandat/' . $this->clients_mandats->name);
                            }
                            $this->clients_mandats->name          = $this->upload->getName();
                            $this->clients_mandats->id_client     = $this->clients->id_client;
                            $this->clients_mandats->id_universign = 'no_universign';
                            $this->clients_mandats->url_pdf       = '/pdf/mandat/' . $this->clients->hash . '/';
                            $this->clients_mandats->status        = \clients_mandats::STATUS_SIGNED;

                            if ($create == true) {
                                $this->clients_mandats->create();
                            } else {
                                $this->clients_mandats->update();
                            }
                        }
                    }

                    // fin fichier //

                    // On met a jour le lender
                    $this->lenders_accounts->id_company_owner = $this->companies->id_company;
                    $this->lenders_accounts->update();
                    $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);

                    // On met a jour le client
                    $this->clients->update();
                    // On met a jour l'adresse client
                    $this->clients_adresses->update();

                    // Histo user //
                    $serialize = serialize(['id_client' => $this->clients->id_client, 'post'      => $_POST, 'files'     => $_FILES ]);
                    $this->users_history->histo(\users_history::FORM_ID_LENDER, 'modif info preteur personne morale', $_SESSION['user']['id_user'], $serialize);

                    if (isset($_POST['statut_valider_preteur']) && $_POST['statut_valider_preteur'] == 1) {
                        $clientStatusManager->addClientStatus($this->clients, $_SESSION['user']['id_user'], \clients_status::VALIDATED);

                        if ($this->clients_status_history->counter('id_client = ' . $this->clients->id_client . ' AND id_client_status = (SELECT cs.id_client_status FROM clients_status cs WHERE cs.status = ' . \clients_status::VALIDATED . ')') > 1) {
                            $sTypeMail = 'preteur-validation-modification-compte';
                        } else {
                            $welcomeOfferManager->createWelcomeOffer($this->clients);;
                            $sTypeMail = 'preteur-confirmation-activation';
                        }

                        $this->settings->get('Facebook', 'type');
                        $lien_fb = $this->settings->value;

                        $this->settings->get('Twitter', 'type');
                        $lien_tw = $this->settings->value;

                        $varMail = [
                            'surl'    => $this->surl,
                            'url'     => $this->furl,
                            'prenom'  => $this->clients->prenom,
                            'projets' => $this->furl . '/projets-a-financer',
                            'lien_fb' => $lien_fb,
                            'lien_tw' => $lien_tw
                        ];

                        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage($sTypeMail, $varMail);
                        $message->setTo($this->clients->email);
                        $mailer = $this->get('mailer');
                        $mailer->send($message);

                        $_SESSION['compte_valide'] = true;
                    }

                    header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $this->lenders_accounts->id_lender_account);
                    die;
                }
            }
        }
    }

    /**
     * @param $lenderId
     * @return array
     */
    private function getTaxationHistory($lenderId)
    {
        /** @var \lenders_imposition_history $lendersImpositionHistory */
        $lendersImpositionHistory = $this->loadData('lenders_imposition_history');
        try {
            $aResult = $lendersImpositionHistory->getTaxationHistory($lenderId);
        } catch (Exception $exception) {
            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->error('Could not get lender taxation history (id_lender = ' . $lenderId . ') Exception message : ' . $exception->getMessage(), array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_lender' => $lenderId));
            $aResult = ['error' => 'Impossible de charger l\'historique de changement d\'adresse fiscale'];
        }

        return $aResult;
    }

    /**
     * @param array $aDataToDisplay
     * @param array $aDataToAdd
     * @param array $aGPAttachmentStatus
     * @param int $iType
     * @param array $aAttachmentType
     */
    private function organizeAttachments(&$aDataToDisplay, &$aDataToAdd, array $aGPAttachmentStatus, $iType, array $aAttachmentType)
    {
        if (isset($this->attachments[$iType]['path'])) {
            $aDataToDisplay[$iType] = [
                'label' => $aAttachmentType['label'],
                'path'  => $this->attachments[$iType]['path'],
                'id'    => $this->attachments[$iType]['id']
            ];

            if (false === empty($aGPAttachmentStatus[$this->attachments[$iType]['id']]['validation_status_label']) && \greenpoint_attachment::REVALIDATE_NO == $aGPAttachmentStatus[$this->attachments[$iType]['id']]['revalidate']) {
                $aDataToDisplay[$iType]['greenpoint_label'] = $aGPAttachmentStatus[$this->attachments[$iType]['id']]['validation_status_label'];

                if (1 == $aGPAttachmentStatus[$this->attachments[$iType]['id']]['final_status']) {
                    $aDataToDisplay[$iType]['final_status'] = 'Statut définitif';
                } elseif(8 > $aGPAttachmentStatus[$this->attachments[$iType]['id']]['validation_status']) {
                    $aDataToDisplay[$iType]['final_status'] = 'Attente de confirmation Green Point';
                } else {
                    $aDataToDisplay[$iType]['final_status'] = '';
                }

                if ('0' === $aGPAttachmentStatus[$this->attachments[$iType]['id']]['validation_status']) {
                    $aDataToDisplay[$iType]['color'] = 'error';
                } elseif (8 > $aGPAttachmentStatus[$this->attachments[$iType]['id']]['validation_status']) {
                    $aDataToDisplay[$iType]['color'] = 'warning';
                } else {
                    $aDataToDisplay[$iType]['color'] = 'valid';
                }
            } else {
                $aDataToDisplay[$iType]['greenpoint_label'] = 'Non Contrôlé par GreenPoint';
                $aDataToDisplay[$iType]['color']            = 'error';
                $aDataToDisplay[$iType]['final_status']     = '';
            }
        } else {
            $aDataToAdd[$iType]['label'] = $aAttachmentType['label'];
        }
    }

    public function _activation()
    {
        $this->clients      = $this->loadData('clients');
        $this->transactions = $this->loadData('transactions');
        $this->companies    = $this->loadData('companies');

        $aStatusNotValidated = [
            \clients_status::TO_BE_CHECKED,
            \clients_status::COMPLETENESS,
            \clients_status::COMPLETENESS_REMINDER,
            \clients_status::COMPLETENESS_REPLY,
            \clients_status::MODIFICATION
        ];

        $this->lPreteurs     = $this->clients->selectPreteursByStatus(
            implode(',', $aStatusNotValidated),
            '',
            'CASE status_client
            WHEN ' . \clients_status::TO_BE_CHECKED . ' THEN 1
            WHEN ' . \clients_status::COMPLETENESS_REPLY . ' THEN 2
            WHEN ' . \clients_status::MODIFICATION . ' THEN 3
            WHEN ' . \clients_status::COMPLETENESS . ' THEN 4
            WHEN ' . \clients_status::COMPLETENESS_REPLY . ' THEN 5
            WHEN ' . \clients_status::COMPLETENESS_REMINDER . ' THEN 6
            END ASC, c.added DESC');

        if (false === empty($this->lPreteurs)) {
            /** @var \greenpoint_kyc $oGreenPointKYC */
            $oGreenPointKYC = $this->loadData('greenpoint_kyc');

            /** @var array aGreenPointStatus */
            $this->aGreenPointStatus = [];

            foreach ($this->lPreteurs as $aLender) {
                if ($oGreenPointKYC->get($aLender['id_client'], 'id_client')) {
                    $this->aGreenPointStatus[$aLender['id_client']] = $oGreenPointKYC->status;
                }
            }
        }
    }

    public function _completude()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $_SESSION['request_url'] = $this->url;
    }

    public function _completude_preview()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $_SESSION['request_url'] = $this->url;

        $this->clients          = $this->loadData('clients');
        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $this->mail_template->get('completude', 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND locale = "' . $this->getParameter('locale') . '" AND type');

        $this->clients->get($this->params[0], 'id_client');
        $this->lenders_accounts->get($this->params[0], 'id_client_owner');
    }

    public function _completude_preview_iframe()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $_SESSION['request_url'] = $this->url;

        $this->clients                = $this->loadData('clients');
        $this->clients_status_history = $this->loadData('clients_status_history');
        $this->mail_template          = $this->loadData('mail_templates');
        $this->settings               = $this->loadData('settings');

        $this->clients->get($this->params[0], 'id_client');
        $this->mail_template->get('completude', 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND locale = "' . $this->getParameter('locale') . '" AND type');

        $this->settings->get('Facebook', 'type');
        $lien_fb = $this->settings->value;

        $this->settings->get('Twitter', 'type');
        $lien_tw = $this->settings->value;

        $this->lActions = $this->clients_status_history->select('id_client = ' . $this->clients->id_client, 'added DESC');
        $timeCreate     = (false === empty($this->lActions[0]['added'])) ? strtotime($this->lActions[0]['added']) : strtotime($this->clients->added);
        $month          = $this->dates->tableauMois['fr'][date('n', $timeCreate)];

        $varMail = [
            'furl'          => $this->furl,
            'surl'          => $this->surl,
            'prenom_p'      => $this->clients->prenom,
            'date_creation' => date('d', $timeCreate) . ' ' . $month . ' ' . date('Y', $timeCreate),
            'content'       => utf8_encode($_SESSION['content_email_completude'][$this->clients->id_client]),
            'lien_upload'   => $this->furl . '/profile/documents',
            'lien_fb'       => $lien_fb,
            'lien_tw'       => $lien_tw
        ];

        $tabVars = [];
        foreach ($varMail as $key => $value) {
            $tabVars['[EMV DYN]' . $key . '[EMV /DYN]'] = $value;
        }

        echo strtr($this->mail_template->content, $tabVars);
        die;
    }

    public function _offres_de_bienvenue()
    {
        $offres_bienvenues         = $this->loadData('offres_bienvenues');
        $offres_bienvenues_details = $this->loadData('offres_bienvenues_details');
        $transactions              = $this->loadData('transactions');
        $this->clients             = $this->loadData('clients');
        $this->settings            = $this->loadData('settings');

        $this->settings->get("Offre de bienvenue motif", 'type');
        $this->motifOffreBienvenue = $this->settings->value;

        if ($offres_bienvenues->get(1, 'id_offre_bienvenue')) {
            $create = false;

            $debut       = explode('-', $offres_bienvenues->debut);
            $this->debut = $debut[2] . '/' . $debut[1] . '/' . $debut[0];

            $fin       = explode('-', $offres_bienvenues->fin);
            $this->fin = $fin[2] . '/' . $fin[1] . '/' . $fin[0];

            $this->montant       = str_replace('.', ',', ($offres_bienvenues->montant / 100));
            $this->montant_limit = str_replace('.', ',', ($offres_bienvenues->montant_limit / 100));
        } else {
            $create = true;
        }

        // form send offres de Bienvenues
        if (isset($_POST['form_send_offres'])) {

            $this->debut         = $_POST['debut'];
            $this->fin           = $_POST['fin'];
            $this->montant       = $_POST['montant'];
            $this->montant_limit = $_POST['montant_limit'];

            $form_ok = true;

            if (!isset($_POST['debut']) || strlen($_POST['debut']) == 0) {
                $form_ok = false;
            }
            if (!isset($_POST['fin']) || strlen($_POST['fin']) == 0) {
                $form_ok = false;
            }
            if (!isset($_POST['montant']) || strlen($_POST['montant']) == 0) {
                $form_ok = false;
            } elseif (is_numeric(str_replace(',', '.', $_POST['montant'])) == false) {
                $form_ok = false;
            }
            if (!isset($_POST['montant_limit']) || strlen($_POST['montant_limit']) == 0) {
                $form_ok = false;
            } elseif (is_numeric(str_replace(',', '.', $_POST['montant_limit'])) == false) {
                $form_ok = false;
            }

            if ($form_ok == true) {
                // debut
                $debut = explode('/', $_POST['debut']);
                $debut = $debut[2] . '-' . $debut[1] . '-' . $debut[0];
                // fin
                $fin = explode('/', $_POST['fin']);
                $fin = $fin[2] . '-' . $fin[1] . '-' . $fin[0];
                // montant
                $montant = str_replace(',', '.', $_POST['montant']);
                // montant limit
                $montant_limit = str_replace(',', '.', $_POST['montant_limit']);

                // Enregistrement
                $offres_bienvenues->debut         = $debut;
                $offres_bienvenues->fin           = $fin;
                $offres_bienvenues->montant       = ($montant * 100);
                $offres_bienvenues->montant_limit = ($montant_limit * 100);
                $offres_bienvenues->id_user       = $_SESSION['user']['id_user'];

                if ($create == false) {
                    $offres_bienvenues->update();
                } else {
                    $offres_bienvenues->id_offre_bienvenue = $offres_bienvenues->create();
                }

                $_SESSION['freeow']['title']   = 'Offre de bienvenue';
                $_SESSION['freeow']['message'] = 'Offre de bienvenue ajouté';
            } else {
                $_SESSION['freeow']['title']   = 'Offre de bienvenue';
                $_SESSION['freeow']['message'] = 'Erreur offre de bienvenue';
            }
        }

        $this->sumOffres                  = $offres_bienvenues_details->sum('type = 0 AND id_offre_bienvenue = ' . $offres_bienvenues->id_offre_bienvenue . ' AND status != 2', 'montant');
        $this->lOffres                    = $offres_bienvenues_details->select('type = 0 AND id_offre_bienvenue = ' . $offres_bienvenues->id_offre_bienvenue . ' AND status != 2', 'added DESC');
        $sumVirementUnilendOffres         = $transactions->sum('status = ' . \transactions::STATUS_VALID . ' AND type_transaction = ' . \transactions_types::TYPE_UNILEND_WELCOME_OFFER_BANK_TRANSFER, 'montant');
        $sumOffresTransac                 = $transactions->sum('status = ' . \transactions::STATUS_VALID . ' AND type_transaction IN(' . \transactions_types::TYPE_WELCOME_OFFER . ', ' . \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION . ')', 'montant');
        $this->sumDispoPourOffres         = $sumVirementUnilendOffres - $sumOffresTransac;
        $this->sumDispoPourOffresSelonMax = $this->montant_limit * 100 - $sumOffresTransac;
    }

    /**
     * @param integer $iOwnerId
     * @param integer $field
     * @param integer $iAttachmentType
     * @return int|bool
     */
    private function uploadAttachment($iOwnerId, $field, $iAttachmentType)
    {
        if (false === isset($this->upload) || false === $this->upload instanceof upload) {
            $this->upload = $this->loadLib('upload');
        }

        if (false === isset($this->attachment) || false === $this->attachment instanceof attachment) {
            $this->attachment = $this->loadData('attachment');
        }

        if (false === isset($this->attachment_type) || false === $this->attachment_type instanceof attachment_type) {
            $this->attachment_type = $this->loadData('attachment_type');
        }

        if (false === isset($this->attachmentHelper) || false === $this->attachmentHelper instanceof attachment_helper) {
            $this->attachmentHelper = $this->loadLib('attachment_helper', [$this->attachment, $this->attachment_type, $this->path]);
        }

        $sNewName = '';
        if (isset($_FILES[$field]['name']) && $aFileInfo = pathinfo($_FILES[$field]['name'])) {
            $sNewName = mb_substr($aFileInfo['filename'], 0, 30) . '_' . $iOwnerId;
        }


        if (false === isset($this->oGreenPointAttachment) || false === $this->oGreenPointAttachment instanceof greenpoint_attachment) {
            /** @var greenpoint_attachment oGreenPointAttachment */
            $this->oGreenPointAttachment = $this->loadData('greenpoint_attachment');
        }
        $mResult = $this->attachmentHelper->attachmentExists($this->attachment, $iOwnerId, attachment::LENDER, $iAttachmentType);

        if (is_numeric($mResult)) {
            $this->oGreenPointAttachment->get($mResult, 'id_attachment');
            $this->oGreenPointAttachment->revalidate   = \greenpoint_attachment::REVALIDATE_YES;
            $this->oGreenPointAttachment->final_status = \greenpoint_attachment::FINAL_STATUS_NO;
            $this->oGreenPointAttachment->update();
        }
        $resultUpload = $this->attachmentHelper->upload($iOwnerId, attachment::LENDER, $iAttachmentType, $field, $this->upload, $sNewName);

        return $resultUpload;
    }

    public function _email_history()
    {
        $this->clients                = $this->loadData('clients');
        $this->lenders_accounts       = $this->loadData('lenders_accounts');

        if ($this->lenders_accounts->get($this->params[0], 'id_lender_account') && $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client')) {
            if (isset($_POST['send_dates'])) {
                $_SESSION['FilterMails']['StartDate'] = $_POST['debut'];
                $_SESSION['FilterMails']['EndDate']   = $_POST['fin'];

                header('Location: ' . $this->lurl . '/preteurs/email_history/' . $this->params[0]);
                die;
            }

            $oClientsNotifications       = $this->loadData('clients_gestion_notifications');
            $this->aClientsNotifications = $oClientsNotifications->getNotifs($this->clients->id_client);

            $this->aNotificationPeriode = \clients_gestion_notifications::getAllPeriod();

            $this->aInfosNotifications['vos-offres-et-vos-projets']['title'] = 'Offres et Projets';
            $this->aInfosNotifications['vos-offres-et-vos-projets']['notifications'] = [
                \clients_gestion_type_notif::TYPE_NEW_PROJECT => [
                    'title'           => 'Annonce des nouveaux projets',
                    'available_types' => [
                        \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ],
                \clients_gestion_type_notif::TYPE_BID_PLACED => [
                    'title'           => 'Offres réalisées',
                    'available_types' => [
                        \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ],
                \clients_gestion_type_notif::TYPE_BID_REJECTED => [
                    'title'           => 'Offres refusées',
                    'available_types' => [
                        \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ],
                \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED => [
                    'title'           => 'Offres acceptées',
                    'available_types' => [
                        \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_MONTHLY,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ],
                \clients_gestion_type_notif::TYPE_PROJECT_PROBLEM => [
                    'title'           => 'Problème sur un projet',
                    'available_types' => [
                        \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ],
                \clients_gestion_type_notif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID => [
                    'title'           => 'Autolend : offre réalisée ou rejetée',
                    'available_types' => [
                        \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ]
            ];
            $this->aInfosNotifications['vos-remboursements']['title'] = 'Offres et Projets';
            $this->aInfosNotifications['vos-remboursements']['notifications'] = [
                \clients_gestion_type_notif::TYPE_REPAYMENT => [
                    'title'           => 'Remboursement(s)',
                    'available_types' => [
                        \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_MONTHLY,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ]
            ];
            $this->aInfosNotifications['mouvements-sur-votre-compte']['title'] = 'Mouvements sur le compte';
            $this->aInfosNotifications['mouvements-sur-votre-compte']['notifications'] = [
                \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT => [
                    'title'           => 'Alimentation de votre compte par virement',
                    'available_types' => [
                        \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ],
                \clients_gestion_type_notif::TYPE_CREDIT_CARD_CREDIT => [
                    'title'           => 'Alimentation de votre compte par carte bancaire',
                    'available_types' => [
                        \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ],
                \clients_gestion_type_notif::TYPE_DEBIT => [
                    'title'           => 'retrait',
                    'available_types' => [
                        \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                        \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ]
            ];

            if (isset($_SESSION['FilterMails'])) {
                $oDateTimeStart = \DateTime::createFromFormat('d/m/Y', $_SESSION['FilterMails']['StartDate']);
                $oDateTimeEnd   = \DateTime::createFromFormat('d/m/Y', $_SESSION['FilterMails']['EndDate']);

                unset($_SESSION['FilterMails']);
            } else {
                $oDateTimeStart = new \DateTime('NOW - 1 year');
                $oDateTimeEnd   = new \DateTime('NOW');
            }

            $this->sDisplayDateTimeStart = $oDateTimeStart->format('d/m/Y');
            $this->sDisplayDateTimeEnd   = $oDateTimeEnd->format('d/m/Y');

            /** @var \Unilend\Bundle\MessagingBundle\Service\MailQueueManager $oMailQueueManager */
            $oMailQueueManager = $this->get('unilend.service.mail_queue');
            $this->aEmailsSentToClient = $oMailQueueManager->searchSentEmails($this->clients->id_client, null, null, null, $oDateTimeStart, $oDateTimeEnd);
            $this->getMessageAboutClientStatus();
        }
    }

    public function _portefeuille()
    {
        $this->loadGestionData();

        $this->projects_status         = $this->loadData('projects_status');
        $this->indexage_vos_operations = $this->loadData('indexage_vos_operations');
        $this->echeanciers             = $this->loadData('echeanciers');
        $this->tax                     = $this->loadData('tax');
        /** @var underlying_contract contract */
        $this->contract                = $this->loadData('underlying_contract');
        /** @var \Symfony\Component\Translation\TranslatorInterface translator */
        $this->translator              = $this->get('translator');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LoanManager loanManager */
        $this->loanManager = $this->get('unilend.service.loan_manager');
        /** @var \loans loan */
        $this->loan = $this->loadData('loans');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LenderManager lenderManager */
        $lenderManager = $this->get('unilend.service.lender_manager');

        if ($this->lenders_accounts->get($this->params[0], 'id_lender_account') && $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client')) {
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            if (in_array($this->clients->type, [\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
                $this->companies->get($this->lenders_accounts->id_company_owner, 'id_company');
            }

            $this->lSumLoans       = $this->loans->getSumLoansByProject($this->lenders_accounts->id_lender_account);
            $this->aProjectsInDebt = $this->projects->getProjectsInDebt();

            $this->IRRValue = null;
            $this->IRRDate  = null;

            /** @var \lenders_account_stats $oLenderAccountStats */
            $oLenderAccountStats = $this->loadData('lenders_account_stats');
            $aIRR                = $oLenderAccountStats->getLastIRRForLender($this->lenders_accounts->id_lender_account);

            if (false === is_null($aIRR)) {
                $this->IRR = $aIRR;
            }

            $statusOk                = [\projects_status::EN_FUNDING, \projects_status::FUNDE, \projects_status::FUNDING_KO, \projects_status::PRET_REFUSE, \projects_status::REMBOURSEMENT, \projects_status::REMBOURSE, \projects_status::REMBOURSEMENT_ANTICIPE];
            $statusKo                = [\projects_status::PROBLEME, \projects_status::RECOUVREMENT, \projects_status::DEFAUT, \projects_status::PROBLEME_J_X, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE];
            $this->projectsPublished = $this->projects->countProjectsSinceLendersubscription($this->clients->id_client, array_merge($statusOk, $statusKo));
            $this->problProjects     = $this->projects->countProjectsByStatusAndLender($this->lenders_accounts->id_lender_account, $statusKo);
            $this->totalProjects     = $this->loans->getProjectsCount($this->lenders_accounts->id_lender_account);

            $this->getMessageAboutClientStatus();

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AutoBidSettingsManager $oAutoBidSettingsManager */
            $oAutoBidSettingsManager   = $this->get('unilend.service.autobid_settings_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ClientManager $oClientManager */
            $oClientManager            = $this->get('unilend.service.client_manager');

            $this->bAutoBidOn          = $oAutoBidSettingsManager->isOn($this->lenders_accounts);
            $this->aSettingsDates      = $oAutoBidSettingsManager->getLastDateOnOff($this->clients->id_client);
            if (0 < count($this->aSettingsDates)) {
                $this->sValidationDate = $oAutoBidSettingsManager->getValidationDate($this->lenders_accounts)->format('d/m/Y');
            }
            $this->fAverageRateUnilend = round($this->projects->getAvgRate(), 1);
            $this->bIsBetaTester       = $oClientManager->isBetaTester($this->clients);

            $this->settings->get('date-premier-projet-tunnel-de-taux', 'type');
            $startingDate = $this->settings->value;
            $this->aAutoBidSettings = [];
            /** @var autobid $autobid */
            $autobid          = $this->loadData('autobid');
            $aAutoBidSettings = $autobid->getSettings($this->lenders_accounts->id_lender_account, null, null, [\autobid::STATUS_ACTIVE, \autobid::STATUS_INACTIVE]);
            foreach ($aAutoBidSettings as $aSetting) {
                $aSetting['AverageRateUnilend']                                          = $this->projects->getAvgRate($aSetting['evaluation'], $aSetting['period_min'], $aSetting['period_max'], $startingDate);
                $this->aAutoBidSettings[$aSetting['id_period']][$aSetting['evaluation']] = $aSetting;
            }

            $this->hasTransferredLoans = $lenderManager->hasTransferredLoans($this->lenders_accounts);

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\CIPManager $cipManager */
            $cipManager       = $this->get('unilend.service.cip_manager');
            $this->cipEnabled = $cipManager->hasValidEvaluation($this->lenders_accounts);
        }
    }

    public function _control_fiscal_city()
    {
        /** @var lenders_accounts $oLenders */
        $oLenders       = $this->loadData('lenders_accounts');
        $this->aLenders = $oLenders->getLendersToMatchCity(200);
    }

    public function _control_birth_city()
    {
        /** @var lenders_accounts $oLenders */
        $oLenders       = $this->loadData('lenders_accounts');
        $this->aLenders = $oLenders->getLendersToMatchBirthCity(200);
    }

    public function _email_history_preview()
    {
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;

        /** @var \Unilend\Bundle\MessagingBundle\Service\MailQueueManager $mailQueueManager */
        $mailQueueManager = $this->get('unilend.service.mail_queue');
        /** @var mail_queue $mailQueue */
        $mailQueue = $this->loadData('mail_queue');
        $mailQueue->get($this->params[0]);
        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $email */
        $email = $mailQueueManager->getMessage($mailQueue);
        /** @var \DateTime $sentAt */
        $sentAt = new \DateTime($mailQueue->sent_at);

        $from = $email->getFrom();
        $to   = $email->getTo();

        $this->email = [
            'date'    => $sentAt->format('d/m/Y H:i'),
            'from'    => array_shift($from),
            'to'      => array_shift($to),
            'subject' => $email->getSubject(),
            'body'    => $email->getBody()
        ];
    }

    private function changeClientStatus(\clients $oClient, $iStatus, $iOrigin)
    {
        if (false === $oClient->isBorrower()) {
            $oClient->status = $iStatus;
            $oClient->update();

            $serialize = serialize(['id_client' => $oClient->id_client, 'status' => $oClient->status]);
            switch ($iOrigin) {
                case 1:
                    $this->users_history->histo($iOrigin, 'status preteur', $_SESSION['user']['id_user'], $serialize);
                    $_SESSION['freeow']['title']   = 'Statut du preteur';
                    $_SESSION['freeow']['message'] = 'Le statut du preteur a bien &eacute;t&eacute; modifi&eacute; !';
                    break;
                case \users_history::FORM_ID_LENDER:
                    $this->users_history->histo($iOrigin, 'status offline d\'un preteur doublon', $_SESSION['user']['id_user'], $serialize);
                    $_SESSION['freeow']['title']   = 'Doublon client';
                    $_SESSION['freeow']['message'] = 'Attention, homonyme d\'un autre client. Client mis hors ligne !';
                    break;
                case 12:
                    $this->users_history->histo($iOrigin, 'status offline-online preteur non inscrit', $_SESSION['user']['id_user'], $serialize);
                    $_SESSION['freeow']['title']   = 'Statut du preteur non inscrit';
                    $_SESSION['freeow']['message'] = 'Le statut du preteur non inscrit a bien &eacute;t&eacute; modifi&eacute; !';
                    break;
            }
        } else {
            $_SESSION['freeow']['title']   = 'Statut du preteur non modifiable';
            $_SESSION['freeow']['message'] = 'Le client est &eacute;galement un emprunteur et ne peux &ecirc;tre mis hors ligne !';

            $oLendersAccounts = $this->loadData('lenders_accounts');
            $oLendersAccounts->get($oClient->id_client, 'id_client_owner');

            header('Location: ' . $this->lurl . '/preteurs/edit/' . $oLendersAccounts->id_lender_account);
            die;
        }
    }

    private function sendEmailClosedAccount(\clients $oClient)
    {
        $oSettings = $this->loadData('settings');
        $oSettings->get('Facebook', 'type');
        $sFB = $oSettings->value;
        $oSettings->get('Twitter', 'type');
        $sTW = $oSettings->value;

        $aVariablesMail = [
            'surl'    => $this->surl,
            'url'     => $this->furl,
            'prenom'  => $oClient->prenom,
            'lien_fb' => $sFB,
            'lien_tw' => $sTW
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('confirmation-fermeture-compte-preteur', $aVariablesMail);
        $message->setTo($oClient->email);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    private function sendCompletenessRequest()
    {
        $oSettings = $this->loadData('settings');

        $oSettings->get('Facebook', 'type');
        $lien_fb = $oSettings->value;

        $oSettings->get('Twitter', 'type');
        $lien_tw = $oSettings->value;

        $lapage = (in_array($this->clients->type, [\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER])) ? 'particulier_doc' : 'societe_doc';

        $timeCreate = (false === empty($this->lActions[0]['added'])) ? strtotime($this->lActions[0]['added']) : strtotime($this->clients->added);
        $month      = $this->dates->tableauMois['fr'][ date('n', $timeCreate) ];

        $varMail = [
            'furl'          => $this->furl,
            'surl'          => $this->surl,
            'prenom_p'      => $this->clients->prenom,
            'date_creation' => date('d', $timeCreate) . ' ' . $month . ' ' . date('Y', $timeCreate),
            'content'       => utf8_encode($_SESSION['content_email_completude'][$this->clients->id_client]),
            'lien_upload'   => $this->furl . '/profile/' . $lapage,
            'lien_fb'       => $lien_fb,
            'lien_tw'       => $lien_tw
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('completude', $varMail);
        $message->setTo($this->clients->email);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    private function getMessageAboutClientStatus()
    {
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager $clientStatusManager */
        $clientStatusManager= $this->get('unilend.service.client_status_manager');
        $sTimeCreate                = strtotime($this->clients->added);
        $this->sClientStatusMessage = '';
        $currentStatus = $clientStatusManager->getLastClientStatus($this->clients);

        switch ($currentStatus) {
            case \clients_status::TO_BE_CHECKED :
                $this->sClientStatusMessage = '<div class="attention">Attention : compte non validé - créé le '. date('d/m/Y', $sTimeCreate) . '</div>';
                break;
            case \clients_status::COMPLETENESS :
            case \clients_status::COMPLETENESS_REMINDER:
            case \clients_status::COMPLETENESS_REPLY:
                $this->sClientStatusMessage = '<div class="attention" style="background-color:#F9B137">Attention : compte en complétude - créé le ' . date('d/m/Y', $sTimeCreate) . ' </div>';
                break;
            case \clients_status::MODIFICATION:
                $this->sClientStatusMessage = '<div class="attention" style="background-color:#F2F258">Attention : compte en modification - créé le ' . date('d/m/Y', $sTimeCreate) . '</div>';
                break;
            case \clients_status::CLOSED_LENDER_REQUEST:
                $this->sClientStatusMessage = '<div class="attention">Attention : compte clôturé (mis hors ligne) à la demande du prêteur</div>';
                break;
            case \clients_status::CLOSED_BY_UNILEND:
                $this->sClientStatusMessage = '<div class="attention">Attention : compte clôturé (mis hors ligne) par Unilend</div>';
                break;
            case \clients_status::VALIDATED:
                $this->sClientStatusMessage = '';
                break;
            case \clients_status::CLOSED_DEFINITELY:
                $this->sClientStatusMessage = '<div class="attention">Attention : compte définitivement fermé </div>';
                break;
            default;
                trigger_error('Unknown Client Status : ' . $currentStatus, E_USER_NOTICE);
                break;
        }
    }

    public function _saveBetaTesterSetting()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $oClientSettingsManager = $this->get('unilend.service.client_settings_manager');
        $oClient                = $this->loadData('clients');
        $oLendersAccount        = $this->loadData('lenders_accounts');

         if(isset($this->params[0]) && is_numeric($this->params[0]) && isset($this->params[1]) && in_array($this->params[1], ['on', 'off'])){
             $oClient->get($this->params[0]);
             $oLendersAccount->get($oClient->id_client, 'id_client_owner');
             $sValue = ('on' == $this->params[1]) ? \client_settings::BETA_TESTER_ON : \client_settings::BETA_TESTER_OFF;
             $oClientSettingsManager->saveClientSetting($oClient, \client_setting_type::TYPE_BETA_TESTER, $sValue);

             header('Location: ' . $this->lurl . '/preteurs/portefeuille/' . $oLendersAccount->id_lender_account);
             die;
         }
    }

    public function _change_bank_account()
    {
        $this->hideDecoration();
        $this->autoFireView = false;
        $iClientId          = filter_var($_POST['id_client'], FILTER_VALIDATE_INT);

        if (false === $iClientId) {
            echo json_encode(['text' => 'Une erreur est survenue', 'severity' => 'error']);
            return;
        }
        /** @var \lenders_accounts $oLendersAccounts */
        $oLendersAccounts = $this->loadData('lenders_accounts');
        $oLendersAccounts->get($iClientId, 'id_client_owner');

        $sCurrrentBic = $oLendersAccounts->bic;
        $sNewBic      = str_replace(' ', '', strtoupper($_POST['bic']));
        $sIban        = '';

        for ($i = 1; $i <= 7; $i++) {
            if (empty($_POST['iban' . $i])) {
                echo json_encode(['text' => 'IBAN incorrect', 'severity' => 'error']);
                return;
            }
            $sIban .= strtoupper($_POST['iban' . $i]);
        }
        $sCurrentIban = $oLendersAccounts->iban;
        $sNewIban     = str_replace(' ', '', $sIban);
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $oMailerManager */
        $oMailerManager = $this->get('unilend.service.email_manager');

        if ($sCurrrentBic !== $sNewBic && $sCurrentIban !== $sNewIban) {
            if ($this->validateBic($sNewBic, $oLendersAccounts) && $this->validateIban($sNewIban, $oLendersAccounts)) {
                $oMailerManager->sendIbanUpdateToStaff($iClientId, $sCurrentIban, $sNewIban);
                $sMessage = 'Bic et IBAN modifiés';
                $sSeverity   = 'valid';
                $oLendersAccounts->update();
            } else {
                $sMessage = 'BIC / IBAN incorrect';
                $sSeverity   = 'error';
            }
        } elseif ($sCurrrentBic !== $sNewBic) {
            if ($this->validateBic($sNewBic, $oLendersAccounts)) {
                $sMessage = 'BIC modifié';
                $sSeverity   = 'valid';
                $oLendersAccounts->update();
            } else {
                $sMessage = 'BIC incorrect';
                $sSeverity   = 'error';
            }
        } elseif ($sCurrentIban !== $sNewIban) {
            if ($this->validateIban($sNewIban, $oLendersAccounts)) {
                $oMailerManager->sendIbanUpdateToStaff($iClientId, $sCurrentIban, $sNewIban);
                $sMessage = 'IBAN modifié';
                $sSeverity   = 'valid';
                $oLendersAccounts->update();
            } else {
                $sMessage = 'IBAN incorrect';
                $sSeverity   = 'error';
            }
        } else {
            echo json_encode(['text' => 'Aucune modification', 'severity' => 'warning']);
            return;
        }
        echo json_encode(['text' => $sMessage, 'severity' => $sSeverity]);
    }

    /**
     * @param string $sNewBic
     * @param \lenders_accounts $oLendersAccounts
     * @return bool
     */
    private function validateBic($sNewBic, \lenders_accounts &$oLendersAccounts)
    {
        if ($this->ficelle->swift_validate($sNewBic)) {
            $oLendersAccounts->bic = $sNewBic;
            return true;
        }
        return false;
    }

    /**
     * @param string $sNewIban
     * @param \lenders_accounts $oLendersAccounts
     * @return bool
     */
    private function validateIban($sNewIban, \lenders_accounts &$oLendersAccounts)
    {
        if ($this->ficelle->isIBAN($sNewIban)) {
            $oLendersAccounts->iban = $sNewIban;
            return true;
        }
        return false;
    }

    public function _lenderOnlineOffline()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var \clients_status_history $oClientsStatusHistory */
        $oClientsStatusHistory = $this->loadData('clients_status_history');
        /** @var \lenders_accounts $oLendersAccount */
        $oLendersAccount = $this->loadData('lenders_accounts');
        /** @var \clients $oClient */
        $oClient = $this->loadData('clients');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager $clientStatusManager */
        $clientStatusManager = $this->get('unilend.service.client_status_manager');

        $oLendersAccount->get($this->params[1],'id_lender_account');
        $oClient->get($oLendersAccount->id_client_owner, 'id_client');

        if (isset($this->params[0]) && $this->params[0] == 'status') {
            $this->changeClientStatus($oClient, $this->params[2], 1);
            if ($this->params[2] == \clients::STATUS_OFFLINE) {
                $clientStatusManager->addClientStatus($oClient, $_SESSION['user']['id_user'], \clients_status::CLOSED_BY_UNILEND);
            } else {
                $aLastTwoStatus = $oClientsStatusHistory->select('id_client =  ' . $oClient->id_client, 'id_client_status_history DESC', null, 2);
                /** @var \clients_status $oClientStatus */
                $oClientStatus  = $this->loadData('clients_status');
                $oClientStatus->get($aLastTwoStatus[1]['id_client_status']);
                $sContent = 'Compte remis en ligne par Unilend';
                $clientStatusManager->addClientStatus($oClient, $_SESSION['user']['id_user'], $oClientStatus->status, $sContent);
            }
            header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $oLendersAccount->id_lender_account);
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'deactivate') {
            $this->changeClientStatus($oClient, $this->params[2], 1);
            $this->sendEmailClosedAccount($oClient);
            $clientStatusManager->addClientStatus($oClient, $_SESSION['user']['id_user'], \clients_status::CLOSED_LENDER_REQUEST);
            header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $oLendersAccount->id_lender_account);
            die;
        }
    }

    public function _bids()
    {
        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $this->clients          = $this->loadData('clients');

        if ($this->lenders_accounts->get($this->params[0], 'id_lender_account') && $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client')) {
            $this->getMessageAboutClientStatus();

            if (isset($_POST['send_dates'])) {
                $_SESSION['FilterBids']['StartDate'] = $_POST['debut'];
                $_SESSION['FilterBids']['EndDate']   = $_POST['fin'];

                header('Location: ' . $this->lurl . '/preteurs/bids/' . $this->params[0]);
                die;
            }

            if (isset($_SESSION['FilterBids'])) {
                $dateTimeStart = \DateTime::createFromFormat('d/m/Y', $_SESSION['FilterBids']['StartDate']);
                $dateTimeEnd   = \DateTime::createFromFormat('d/m/Y', $_SESSION['FilterBids']['EndDate']);

                unset($_SESSION['FilterBids']);
            } else {
                $dateTimeStart = new \DateTime('NOW - 1 year');
                $dateTimeEnd   = new \DateTime('NOW');
            }

            $this->sDisplayDateTimeStart = $dateTimeStart->format('d/m/Y');
            $this->sDisplayDateTimeEnd   = $dateTimeEnd->format('d/m/Y');
            $this->bidList               = [];

            /** @var \bids $bids */
            $bids = $this->loadData('bids');
            foreach ($bids->getBidsByLenderAndDates($this->lenders_accounts, $dateTimeStart, $dateTimeEnd) as $key => $value) {
                $this->bidList[$key] = $value;
            }
        }
    }

    public function _extract_bids_csv()
    {
        /** @var \lenders_accounts $lender */
        $lender = $this->loadData('lenders_accounts');
        /** @var \bids $bids */
        $bids = $this->loadData('bids');

        $lender->get($this->params[0], 'id_lender_account');
        $lenderBids = $bids->getBidsByLenderAndDates($lender);

        $this->autoFireView = false;
        $this->hideDecoration();

        $header = ['Id projet', 'Id bid', 'Client', 'Date bid', 'Statut bid', 'Montant', 'Taux'];

        PHPExcel_Settings::setCacheStorageMethod(
            PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
            ['memoryCacheSize' => '2048MB', 'cacheTime' => 1200]
        );

        $document    = new PHPExcel();
        $activeSheet = $document->setActiveSheetIndex(0);

        foreach ($header as $index => $columnName) {
            $activeSheet->setCellValueByColumnAndRow($index, 1, $columnName);
        }

        foreach ($lenderBids as $rowIndex => $row) {
            $colIndex = 0;
            foreach ($row as $cellValue) {
                $activeSheet->setCellValueByColumnAndRow($colIndex++, $rowIndex + 2, $cellValue);
            }
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=bids_lender_' . $lender->id_lender_account . '.csv');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        /** @var \PHPExcel_Writer_CSV $writer */
        $writer = PHPExcel_IOFactory::createWriter($document, 'CSV');
        $writer->setUseBOM(true);
        $writer->setDelimiter(';');
        $writer->save('php://output');
    }

    /**
     * @param string $email
     * @param \clients $clientEntity
     * @return bool
     */
    private function isEmailUnique($email, \clients $clientEntity)
    {
        $clientsWithSameEmailAddress = $clientEntity->select('email = "' . $email . '" AND id_client != ' . $clientEntity->id_client . ' AND status = ' . \clients::STATUS_ONLINE);
        if (count($clientsWithSameEmailAddress) > 0) {
            $ClientIdWithSameEmail = '';
            foreach ($clientsWithSameEmailAddress as $client) {
                $ClientIdWithSameEmail .= ' ' . $client['id_client'];
            }
            $_SESSION['error_email_exist'] = 'Impossible de modifier l\'adresse email. Cette adresse est déjà utilisé par le compte id ' . $ClientIdWithSameEmail;
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param array $history
     * @return array
     */
    private function getTaxExemptionHistoryActionDetails(array $history)
    {
        /** @var \users $user */
        $data = [];
        $user = $this->loadData('users');

        if (false === empty($history)) {
            foreach ($history as $row) {
                $data[] = [
                    'modifications' => unserialize($row['serialize'])['modifications'],
                    'user'          => $user->getName($row['id_user']),
                    'date'          => $row['added']
                ];
            }
        }
        return $data;
    }
}
