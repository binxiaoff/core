<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Repository\ClientsRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\BankAccountManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use \Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Attachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderOperationsManager;
use Unilend\Bundle\CoreBusinessBundle\Repository\LenderStatisticRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\LenderStatistic;
use Unilend\Bundle\CoreBusinessBundle\Repository\WalletRepository;

class preteursController extends bootstrap
{
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

            if (false === empty($clientId) && 1 == count($this->lPreteurs)) {
                header('Location:' . $this->lurl . '/preteurs/edit/' . $this->lPreteurs[0]['id_lender_account']);
                die;
            }

            $_SESSION['freeow']['title']   = 'Recherche d\'un prêteur';
            $_SESSION['freeow']['message'] = 'La recherche est termin&eacute;e !';
        } else {
            header('Location: ' . $this->lurl . '/preteurs/search');
            die;
        }
    }

    public function _search()
    {
        $this->autoFireHeader = true;
        $this->autoFireHead   = true;
        $this->autoFireFooter = true;

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
        $this->translator = $this->get('translator');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AttachmentManager $attachmentManager */
        $attachmentManager = $this->get('unilend.service.attachment_manager');
        /** @var LenderOperationsManager $lenderOperationsManager */
        $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');

        $this->projects      = $this->loadData('projects');
        $this->transactions  = $this->loadData('transactions');
        $this->wallets_lines = $this->loadData('wallets_lines');

        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $this->clients = $this->loadData('clients');

        if ($this->lenders_accounts->get($this->params[0], 'id_lender_account') && $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client')) {
            $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->lenders_accounts->id_client_owner);
            $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->getIdClient(), WalletType::LENDER);
            $this->clients_adresses = $this->loadData('clients_adresses');
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            $this->companies = $this->loadData('companies');
            if (in_array($this->clients->type, [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
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

            $this->attachments     = $client->getAttachments();
            $this->attachmentTypes = $attachmentManager->getAllTypesForLender();

            /** @var \lender_tax_exemption $oLenderTaxExemption */
            $oLenderTaxExemption   = $this->loadData('lender_tax_exemption');
            $this->aExemptionYears = array_column($oLenderTaxExemption->select('id_lender = ' . $this->lenders_accounts->id_lender_account, 'year DESC'), 'year');

            $this->solde        = $wallet->getAvailableBalance();
            $this->soldeRetrait = $this->transactions->sum('status = ' . \transactions::STATUS_VALID . ' AND type_transaction = ' . \transactions_types::TYPE_LENDER_WITHDRAWAL . ' AND id_client = ' . $this->clients->id_client, 'montant');
            $this->soldeRetrait = abs($this->soldeRetrait / 100);

            //TODO after merge of different branches check that there are not several wallets
            $wallet                 = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->clients->id_client, WalletType::LENDER);
            $start                  = new \DateTime('First day of january this year');
            $end                    = new \DateTime('NOW');
            $this->lenderOperations = $lenderOperationsManager->getLenderOperations($wallet, $start, $end, null, LenderOperationsManager::ALL_TYPES);

            $this->transfers = $entityManager->getRepository('UnilendCoreBusinessBundle:Transfer')->findTransferByClient($client);

            $this->getMessageAboutClientStatus();
            $this->setClientVigilanceStatusData();
        }
    }

    /**
     * @param Attachment[]     $attachments
     * @param AttachmentType[] $attachmentTypes
     */
    private function setAttachments($attachments, $attachmentTypes)
    {
        $identityGroup      = [
            AttachmentType::CNI_PASSPORTE,
            AttachmentType::CNI_PASSPORTE_VERSO,
            AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT,
            AttachmentType::CNI_PASSPORTE_DIRIGEANT
        ];
        $bankAndFiscalGroup = [
            AttachmentType::RIB,
            AttachmentType::JUSTIFICATIF_FISCAL
        ];
        $fiscalAddressGroup = [
            AttachmentType::JUSTIFICATIF_DOMICILE,
            AttachmentType::ATTESTATION_HEBERGEMENT_TIERS
        ];

        $identityTypes      = [];
        $bankAndFiscalTypes = [];
        $fiscalAddressTypes = [];
        $otherTypes         = [];

        $identityAttachments      = [];
        $bankAndFiscalAttachments = [];
        $fiscalAddressAttachments = [];
        $otherAttachments         = [];

        foreach ($attachmentTypes as $attachmentType) {
            $typeId = $attachmentType->getId();
            if (in_array($typeId, $identityGroup)) {
                $identityTypes[$typeId] = $attachmentType;
            } elseif (in_array($typeId, $bankAndFiscalGroup)) {
                $bankAndFiscalTypes[$typeId] = $attachmentType;
            } elseif (in_array($typeId, $fiscalAddressGroup)) {
                $fiscalAddressTypes[$typeId] = $attachmentType;
            } else {
                $otherTypes[$typeId] = $attachmentType;
            }
        }
        foreach ($attachments as $attachment) {
            $typeId = $attachment->getType()->getId();
            if (in_array($typeId, $identityGroup)) {
                unset($identityTypes[$typeId]);
                $identityAttachments[] = $attachment;
            } elseif (in_array($typeId, $bankAndFiscalGroup)) {
                unset($bankAndFiscalTypes[$typeId]);
                $bankAndFiscalAttachments[] = $attachment;
            } elseif (in_array($typeId, $fiscalAddressGroup)) {
                unset($fiscalAddressTypes[$typeId]);
                $fiscalAddressAttachments[] = $attachment;
            } else {
                unset($otherTypes[$typeId]);
                $otherAttachments[] = $attachment;
            }
        }

        $this->attachmentGroups = [
            [
                'title'       => 'Identité',
                'attachments' => $identityAttachments,
                'typeToAdd'   => $identityTypes
            ],
            [
                'title'       => 'RIB et Justificatif fiscal',
                'attachments' => $bankAndFiscalAttachments,
                'typeToAdd'   => $bankAndFiscalTypes
            ],
            [
                'title'       => 'Justificatif de domicile',
                'attachments' => $fiscalAddressAttachments,
                'typeToAdd'   => $fiscalAddressTypes
            ],
            [
                'title'       => 'Autre',
                'attachments' => $otherAttachments,
                'typeToAdd'   => $otherTypes
            ]
        ];
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
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AttachmentManager $attachmentManager */
        $attachmentManager = $this->get('unilend.service.attachment_manager');

        if ($this->lenders_accounts->get($this->params[0], 'id_lender_account') && $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client')) {
            $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->lenders_accounts->id_client_owner);
            $this->clients_adresses = $this->loadData('clients_adresses');
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            $this->companies = $this->loadData('companies');

            if (in_array($this->clients->type, [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
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

            /** @var ClientsRepository $clientRepository */
            $clientRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients');
            /** @var Clients $clientEntity */
            $clientEntity = $clientRepository->find($this->clients->id_client);
            /** @var BankAccount $currentBankAccount */
            $this->currentBankAccount = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:BankAccount')->getLastModifiedBankAccount($clientEntity);

            if (null === $this->currentBankAccount) {
                $this->currentBankAccount = new BankAccount();
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

            $attachments     = $client->getAttachments();
            $attachmentTypes = $attachmentManager->getAllTypesForLender();
            $this->setAttachments($attachments, $attachmentTypes);

            $this->loadJs('default/component/add-file-input');

            $this->treeRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Tree');
            $this->legalDocuments = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:AcceptationsLegalDocs')->findBy(['idClient' => $this->clients->id_client]);

            $identityDocument = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findOneClientAttachmentByType($client, AttachmentType::CNI_PASSPORTE);
            if ($identityDocument && $identityDocument->getGreenpointAttachment()) {
                $this->lenderIdentityMRZData = $identityDocument->getGreenpointAttachment()->getGreenpointAttachmentDetail();
            }

            $hostIdentityDocument = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findOneClientAttachmentByType($client, AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT);
            if ($hostIdentityDocument && $hostIdentityDocument->getGreenpointAttachment()) {
                $this->hostIdentityMRZData = $hostIdentityDocument->getGreenpointAttachment()->getGreenpointAttachmentDetail();
            }

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

                if (in_array($this->clients->type, [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) {

                    if (false === empty($_POST['meme-adresse'])) {
                        $this->clients_adresses->meme_adresse_fiscal = 1;
                    } else {
                        $this->clients_adresses->meme_adresse_fiscal = 0;
                    }
                    $applyTaxCountry                        = false === empty($_POST['id_pays_fiscal']) && $this->clients_adresses->id_pays_fiscal != $_POST['id_pays_fiscal'];
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

                    $this->clients->telephone           = str_replace(' ', '', $_POST['phone']);
                    $this->clients->mobile              = str_replace(' ', '', $_POST['mobile']);
                    $this->clients->ville_naissance     = $_POST['com-naissance'];
                    $this->clients->insee_birth         = $_POST['insee_birth'];
                    $this->clients->naissance           = $oBirthday->format('Y-m-d');
                    $this->clients->id_pays_naissance   = $_POST['id_pays_naissance'];
                    $this->clients->id_nationalite      = $_POST['nationalite'];
                    $this->clients->id_langue           = 'fr';
                    $this->clients->type                = 1;
                    $this->clients->fonction            = '';
                    $this->clients->funds_origin        = $_POST['origine_des_fonds'];
                    $this->clients->funds_origin_detail = $this->clients->funds_origin == '1000000' ? $_POST['preciser'] : '';
                    $this->clients->update();

                    $this->lenders_accounts->id_company_owner = 0;
                    $attachmentTypeRepository                 = $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType');
                    foreach ($this->request->files->all() as $attachmentTypeId => $uploadedFile) {
                        if ($uploadedFile) {
                            $attachmentType   = $attachmentTypeRepository->find($attachmentTypeId);
                            if ($attachmentType) {
                                $attachmentManager->upload($client, $attachmentType, $uploadedFile);
                            }
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

                    $serialize = serialize(['id_client' => $this->clients->id_client, 'post'      => $_POST, 'files'     => $_FILES]);
                    $this->users_history->histo(\users_history::FORM_ID_LENDER, 'modif info preteur', $_SESSION['user']['id_user'], $serialize);

                    if (isset($_POST['statut_valider_preteur']) && 1 == $_POST['statut_valider_preteur']) {
                        /** @var \Psr\Log\LoggerInterface $logger */
                        $logger = $this->get('logger');

                        $aExistingClient       = $this->clients->getDuplicates($this->clients->nom, $this->clients->prenom, $this->clients->naissance);
                        $aExistingClient       = array_shift($aExistingClient);
                        $iOriginForUserHistory = 3;

                        if (false === empty($aExistingClient) && $aExistingClient['id_client'] != $this->clients->id_client) {
                            $this->changeClientStatus($this->clients, Clients::STATUS_OFFLINE, $iOriginForUserHistory);
                            $clientStatusManager->addClientStatus($this->clients, $_SESSION['user']['id_user'], \clients_status::CLOSED_BY_UNILEND, 'Doublon avec client ID : ' . $aExistingClient['id_client']);
                            header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $this->lenders_accounts->id_lender_account);
                            die;
                        } elseif (1 == $this->clients->origine && 0 == $this->clients_status_history->counter('id_client = ' . $this->clients->id_client . ' AND id_client_status = (SELECT cs.id_client_status FROM clients_status cs WHERE cs.status = ' . \clients_status::VALIDATED . ')')) {
                            $response = $welcomeOfferManager->createWelcomeOffer($this->clients);
                            $logger->info('Client ID: ' . $this->clients->id_client . ' Welcome offer creation result: ' . json_encode($response), ['class'     => __CLASS__, 'function'  => __FUNCTION__, 'id_lender' => $this->clients->id_client ]);
                        } else {
                            $logger->info('Client ID: ' . $this->clients->id_client . ' Welcome offer not created. The client has been validated by the past or the origine != 1.', [ 'class'     => __CLASS__, 'function'  => __FUNCTION__, 'id_lender' => $this->clients->id_client]);
                        }

                        $this->validateBankAccount($_POST['id_bank_account']);
                        $clientStatusManager->addClientStatus($this->clients, $_SESSION['user']['id_user'], \clients_status::VALIDATED);

                        if ($this->clients_status_history->counter('id_client = ' . $this->clients->id_client . ' AND id_client_status = 5') > 0) {
                            $mailerManager->sendClientValidationEmail($this->clients, 'preteur-validation-modification-compte');
                        } else {
                            $mailerManager->sendClientValidationEmail($this->clients, 'preteur-confirmation-activation');
                        }

                        $_SESSION['compte_valide'] = true;
                        $applyTaxCountry           = true;
                    }

                    if (true === $applyTaxCountry) {
                        $taxManager->addTaxToApply($this->clients, $this->lenders_accounts, $this->clients_adresses, $_SESSION['user']['id_user']);
                    }
                    header('location:' . $this->lurl . '/preteurs/edit_preteur/' . $this->lenders_accounts->id_lender_account);
                    die;
                } elseif (in_array($this->clients->type, [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
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

                    $this->clients->funds_origin        = $_POST['origine_des_fonds'];
                    $this->clients->funds_origin_detail = $this->clients->funds_origin == '1000000' ? $_POST['preciser'] : '';
                    $this->clients->update();

                    $attachmentTypeRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType');
                    foreach ($this->request->files->all() as $attachmentTypeId => $uploadedFile) {
                        if ($uploadedFile) {
                            $attachmentType   = $attachmentTypeRepository->find($attachmentTypeId);
                            if ($attachmentType) {
                                $attachmentManager->upload($client, $attachmentType, $uploadedFile);
                            }
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

                    // On met a jour le client
                    $this->clients->update();
                    // On met a jour l'adresse client
                    $this->clients_adresses->update();

                    // Histo user //
                    $serialize = serialize(['id_client' => $this->clients->id_client, 'post'      => $_POST, 'files'     => $_FILES ]);
                    $this->users_history->histo(\users_history::FORM_ID_LENDER, 'modif info preteur personne morale', $_SESSION['user']['id_user'], $serialize);

                    if (isset($_POST['statut_valider_preteur']) && $_POST['statut_valider_preteur'] == 1) {
                        $this->validateBankAccount($_POST['id_bank_account']);
                        $clientStatusManager->addClientStatus($this->clients, $_SESSION['user']['id_user'], \clients_status::VALIDATED);

                        if ($this->clients_status_history->counter('id_client = ' . $this->clients->id_client . ' AND id_client_status = (SELECT cs.id_client_status FROM clients_status cs WHERE cs.status = ' . \clients_status::VALIDATED . ')') > 1) {
                            $mailerManager->sendClientValidationEmail($this->clients, 'preteur-validation-modification-compte');
                        } else {
                            $welcomeOfferManager->createWelcomeOffer($this->clients);;
                            $mailerManager->sendClientValidationEmail($this->clients, 'preteur-confirmation-activation');
                        }

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
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->url;
    }

    public function _completude_preview()
    {
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->url;

        $this->clients          = $this->loadData('clients');
        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $this->mail_template->get('completude', 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND locale = "' . $this->getParameter('locale') . '" AND type');

        $this->clients->get($this->params[0], 'id_client');
        $this->lenders_accounts->get($this->params[0], 'id_client_owner');
    }

    public function _completude_preview_iframe()
    {
        $this->hideDecoration();

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
        $this->echeanciers             = $this->loadData('echeanciers');
        $this->tax                     = $this->loadData('tax');
        /** @var \loans loan */
        $this->loan = $this->loadData('loans');
        /** @var underlying_contract contract */
        $this->contract  = $this->loadData('underlying_contract');
        /** @var \Symfony\Component\Translation\TranslatorInterface translator */
        $this->translator  = $this->get('translator');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LoanManager loanManager */
        $this->loanManager = $this->get('unilend.service.loan_manager');
        /** @var \loans loan */
        $this->loan = $this->loadData('loans');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LenderManager lenderManager */
        $lenderManager = $this->get('unilend.service.lender_manager');
        /** @var LenderStatisticRepository $lenderStatisticsRepository */
        $lenderStatisticsRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:LenderStatistic');
        /** @var WalletRepository $walletRepository */
        $walletRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet');

        if ($this->lenders_accounts->get($this->params[0], 'id_lender_account') && $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client')) {
            $wallet = $walletRepository->getWalletByType($this->clients->id_client, WalletType::LENDER);
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            if (in_array($this->clients->type, [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
                $this->companies->get($this->lenders_accounts->id_company_owner, 'id_company');
            }

            $this->lSumLoans       = $this->loans->getSumLoansByProject($this->lenders_accounts->id_lender_account);
            $this->aProjectsInDebt = $this->projects->getProjectsInDebt();

            /** @var LenderStatistic $lastIRR */
            $this->IRR = $lenderStatisticsRepository->findOneBy(['idWallet' => $wallet, 'typeStat' => LenderStatistic::TYPE_STAT_IRR], ['added' => 'DESC']);

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
        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\MailQueue $mailQueue */
        $mailQueue = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:MailQueue')->find($this->params[0]);
        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $email */
        $email = $mailQueueManager->getMessage($mailQueue);
        /** @var \DateTime $sentAt */
        $sentAt = $mailQueue->getSentAt();

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

        $lapage = (in_array($this->clients->type, [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) ? 'particulier_doc' : 'societe_doc';

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
        $clientStatusManager       = $this->get('unilend.service.client_status_manager');
        $currentStatus             = $clientStatusManager->getLastClientStatus($this->clients);
        $creationTime              = strtotime($this->clients->added);
        $this->clientStatusMessage = '';

        switch ($currentStatus) {
            case \clients_status::TO_BE_CHECKED :
                $this->clientStatusMessage = '<div class="attention">Attention : compte non validé - créé le '. date('d/m/Y', $creationTime) . '</div>';
                break;
            case \clients_status::COMPLETENESS :
            case \clients_status::COMPLETENESS_REMINDER:
            case \clients_status::COMPLETENESS_REPLY:
                $this->clientStatusMessage = '<div class="attention" style="background-color:#F9B137">Attention : compte en complétude - créé le ' . date('d/m/Y', $creationTime) . ' </div>';
                break;
            case \clients_status::MODIFICATION:
                $this->clientStatusMessage = '<div class="attention" style="background-color:#F2F258">Attention : compte en modification - créé le ' . date('d/m/Y', $creationTime) . '</div>';
                break;
            case \clients_status::CLOSED_LENDER_REQUEST:
                $this->clientStatusMessage = '<div class="attention">Attention : compte clôturé (mis hors ligne) à la demande du prêteur</div>';
                break;
            case \clients_status::CLOSED_BY_UNILEND:
                $this->clientStatusMessage = '<div class="attention">Attention : compte clôturé (mis hors ligne) par Unilend</div>';
                break;
            case \clients_status::VALIDATED:
                $this->clientStatusMessage = '';
                break;
            case \clients_status::CLOSED_DEFINITELY:
                $this->clientStatusMessage = '<div class="attention">Attention : compte définitivement fermé </div>';
                break;
            default:
                /** @var \Psr\Log\LoggerInterface $logger */
                $logger = $this->get('logger');
                $logger->warning('Unknown client status "' . $currentStatus . '"', ['client' => $this->clients->id_client]);
                break;
        }
    }

    private function setClientVigilanceStatusData()
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $client                       = $em->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->clients->id_client);
        $this->vigilanceStatusHistory = $em->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory')->findBy(['client' => $client], ['id' => 'DESC']);

        if (empty($this->vigilanceStatusHistory)) {
            $this->vigilanceStatus = [
                'status'  => VigilanceRule::VIGILANCE_STATUS_LOW,
                'message' => 'Vigilance standard'
            ];
            $this->userEntity      = $em->getRepository('UnilendCoreBusinessBundle:Users');
            $this->lendersAccount  = $em->getRepository('UnilendCoreBusinessBundle:LendersAccounts');

            return;
        }
        $this->clientAtypicalOperations = $em->getRepository('UnilendCoreBusinessBundle:ClientAtypicalOperation')->findBy(['client' => $client], ['added' => 'DESC']);

        switch ($this->vigilanceStatusHistory[0]->getVigilanceStatus()) {
            case VigilanceRule::VIGILANCE_STATUS_LOW:
                $this->vigilanceStatus = [
                    'status'  => VigilanceRule::VIGILANCE_STATUS_LOW,
                    'message' => 'Vigilance standard. Dernière MAJ le :' . $this->vigilanceStatusHistory[0]->getAdded()->format('d/m/Y H\hi')
                ];
                break;
            case VigilanceRule::VIGILANCE_STATUS_MEDIUM:
                $this->vigilanceStatus = [
                    'status'  => VigilanceRule::VIGILANCE_STATUS_MEDIUM,
                    'message' => 'Vigilance intermédiaire. Dernière MAJ le :' . $this->vigilanceStatusHistory[0]->getAdded()->format('d/m/Y H\hi')
                ];
                break;
            case VigilanceRule::VIGILANCE_STATUS_HIGH:
                $this->vigilanceStatus = [
                    'status'  => VigilanceRule::VIGILANCE_STATUS_HIGH,
                    'message' => 'Vigilance Renforcée. Dernière MAJ le :' . $this->vigilanceStatusHistory[0]->getAdded()->format('d/m/Y H\hi')
                ];
                break;
            case VigilanceRule::VIGILANCE_STATUS_REFUSE:
                $this->vigilanceStatus = [
                    'status'  => VigilanceRule::VIGILANCE_STATUS_REFUSE,
                    'message' => 'Vigilance Refus. Dernière MAJ le :' . $this->vigilanceStatusHistory[0]->getAdded()->format('d/m/Y H\hi')
                ];
                break;
            default:
                trigger_error('Unknown vigilance status :' . $this->vigilanceStatusHistory[0]->getVigilanceStatus(), E_USER_NOTICE);
        }
        /** @var \Symfony\Component\Translation\Translator translator */
        $this->translator                   = $this->get('translator');
        $this->userEntity                   = $em->getRepository('UnilendCoreBusinessBundle:Users');
        $this->lendersAccount               = $em->getRepository('UnilendCoreBusinessBundle:LendersAccounts');
        $this->clientVigilanceStatusHistory = $em->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory');
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
            if ($this->params[2] == Clients::STATUS_OFFLINE) {
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
        $clientsWithSameEmailAddress = $clientEntity->select('email = "' . $email . '" AND id_client != ' . $clientEntity->id_client . ' AND status = ' . Clients::STATUS_ONLINE);
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

    /**
     * @param string $idBankAccount
     *
     * @throws Exception
     */
    private function validateBankAccount($idBankAccount)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        /** @var BankAccount $currentBankAccount */
        $bankAccount = $em->getRepository('UnilendCoreBusinessBundle:BankAccount')->find($idBankAccount);

        if (null === $bankAccount) {
            throw new Exception('BankAccount could not be found with id : ' . $idBankAccount );
        }

        /** @var BankAccountManager $bankAccountManager */
        $bankAccountManager = $this->get('unilend.service.bank_account_manager');
        $bankAccountManager->validateBankAccount($bankAccount);
    }

    public function _operations_export()
    {
        if (
            isset($_POST['dateStart']) && 1 === preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $_POST['dateStart'])
            && isset($_POST['dateEnd']) && 1 === preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $_POST['dateEnd'])
        ) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var LenderOperationsManager $lenderOperationsManager */
            $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');
            $wallet                  = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->params[0], WalletType::LENDER);
            $start                   = \DateTime::createFromFormat('m/d/Y', $_POST['dateStart']);
            $end                     = \DateTime::createFromFormat('m/d/Y', $_POST['dateEnd']);

            $document = $lenderOperationsManager->getOperationsExcelFile($wallet, $start, $end, null, LenderOperationsManager::ALL_TYPES);
            $fileName = 'operations_' . date('Y-m-d_H:i:s');

            /** @var \PHPExcel_Writer_Excel2007 $writer */
            $writer = PHPExcel_IOFactory::createWriter($document, 'Excel2007');

            header('Content-Type: application/force-download; charset=utf-8');
            header('Content-Disposition: attachment;filename=' . $fileName . '.xlsx');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Expires: 0');

            $writer->save('php://output');

            die;
        }
    }
}
