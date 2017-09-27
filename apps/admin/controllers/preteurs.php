<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\Attachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\LenderStatistic;
use Unilend\Bundle\CoreBusinessBundle\Entity\MailTemplates;
use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenues;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectNotification;
use Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;
use Unilend\Bundle\CoreBusinessBundle\Repository\LenderStatisticRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderOperationsManager;
use Unilend\Bundle\CoreBusinessBundle\Service\BankAccountManager;

class preteursController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_LENDERS);

        $this->menu_admin = 'preteurs';

        include $this->path . '/apps/default/controllers/pdf.php';
    }

    public function _default()
    {
        header('Location: ' . $this->lurl . '/preteurs/search');
        die;
    }

    public function _gestion()
    {
        if (isset($_POST['form_search_preteur'])) {
            if (empty($_POST['id']) && empty($_POST['nom']) && empty($_POST['email']) && empty($_POST['prenom']) && empty($_POST['raison_sociale']) && empty($_POST['siren'])) {
                $_SESSION['error_search'][]  = 'Veuillez remplir au moins un champ';
            }

            $email = empty($_POST['email']) ? null : filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
            if (false === $email) {
                $_SESSION['error_search'][]  = 'Format de l\'email est non valide';
            }

            $clientId = empty($_POST['id']) ? null : filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
            if (false === $clientId) {
                $_SESSION['error_search'][]  = 'L\'id du client doit être numérique';
            }

            $lastName = empty($_POST['nom']) ? null : filter_var($_POST['nom'], FILTER_SANITIZE_STRING);
            if (false === $lastName) {
                $_SESSION['error_search'][]  = 'Le format du nom n\'est pas valide';
            }

            $firstName = empty($_POST['prenom']) ? null : filter_var($_POST['prenom'], FILTER_SANITIZE_STRING);
            if (false === $firstName) {
                $_SESSION['error_search'][]  = 'Le format du prenom n\'est pas valide';
            }

            $companyName = empty($_POST['raison_sociale']) ? null : filter_var($_POST['raison_sociale'], FILTER_SANITIZE_STRING);
            if (false === $companyName) {
                $_SESSION['error_search'][]  = 'Le format de la raison sociale n\'est pas valide';
            }

            $siren = empty($_POST['siren']) ? null : trim(filter_var($_POST['siren'], FILTER_SANITIZE_STRING));
            if (false === $siren) {
                $_SESSION['error_search'][]  = 'Le format du siren n\'est pas valide';
            }

            if (false === empty($_SESSION['error_search'])) {
                header('Location: ' . $this->lurl . '/preteurs/search');
                die;
            }

            $online = (isset($_POST['nonValide']) && true == $_POST['nonValide']) ? false : true;
            /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\ClientsRepository $clientRepository */
            $clientRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients');
            $this->lPreteurs = $clientRepository->findLenders($clientId, $email, $lastName, $firstName, $companyName, $siren, $online);

            if (false === empty($this->lPreteurs) && 1 == count($this->lPreteurs)) {
                header('Location: ' . $this->lurl . '/preteurs/edit/' . $this->lPreteurs[0]['id_client']);
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
        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = $this->get('logger');

        $this->clients          = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');
        $this->echeanciers      = $this->loadData('echeanciers');
        $this->projects         = $this->loadData('projects');
        $this->clients_mandats  = $this->loadData('clients_mandats');
        $this->companies        = $this->loadData('companies');
        /** @var \loans $loans */
        $loans  = $this->loadData('loans');
        /** @var \bids $bids */
        $bids = $this->loadData('bids');

        if (
            $this->params[0]
            && is_numeric($this->params[0])
            && $this->clients->get($this->params[0], 'id_client')
            && null !== $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->params[0], WalletType::LENDER)
        ) {
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            if (in_array($this->clients->type, [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
                $this->companies->get($this->clients->id_client, 'id_client_owner');
            }

            $this->nb_pret  = $loans->counter('id_lender = ' . $wallet->getId() . ' AND status = ' . \loans::STATUS_ACCEPTED);
            $this->txMoyen  = $loans->getAvgPrets($wallet->getId());
            $this->sumPrets = $loans->sumPrets($wallet->getId());

            if (isset($this->params[1])) {
                $this->lEncheres = $loans->select('id_lender = ' . $wallet->getId() . ' AND YEAR(added) = ' . $this->params[1] . ' AND status = ' . \loans::STATUS_ACCEPTED);
            } else {
                $this->lEncheres = $loans->select('id_lender = ' . $wallet->getId() . ' AND YEAR(added) = YEAR(CURDATE()) AND status = ' . \loans::STATUS_ACCEPTED);
            }

            $this->SumDepot = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->sumCreditOperationsByTypeAndYear($wallet, [OperationType::LENDER_PROVISION]);
            $provisionType  = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneByLabel(OperationType::LENDER_PROVISION);
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Operation $firstProvision */
            $firstProvision       = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->findOneBy(['idWalletCreditor' => $wallet, 'idType' => $provisionType], ['id' => 'ASC']);
            $this->SumInscription = null !== $firstProvision ? $firstProvision->getAmount() : 0;

            $this->echeanciers = $this->loadData('echeanciers');
            $this->sumRembInte = $this->echeanciers->getRepaidInterests(['id_lender' => $wallet->getId()]);

            try {
                $this->nextRemb = $this->echeanciers->getNextRepaymentAmountInDateRange($wallet->getId(), (new \DateTime('first day of next month'))->format('Y-m-d 00:00:00'), (new \DateTime('last day of next month'))->format('Y-m-d 23:59:59'));
            } catch (\Exception $exception) {
                $logger->error('Could not get next repayment amount (id_client = ' . $this->clients->id_client . ')', ['class'     => __CLASS__, 'function'  => __FUNCTION__, 'id_lender' => $wallet->getId() ]);
                $this->nextRemb = 0;
            }

            $this->sumRembMontant = $this->echeanciers->getRepaidAmount(['id_lender' => $wallet->getId()]);
            $this->avgPreteur     = $bids->getAvgPreteur($wallet->getId(), 'amount', implode(', ', [Bids::STATUS_BID_ACCEPTED, Bids::STATUS_BID_REJECTED]));
            $this->sumBidsEncours = $bids->sumBidsEncours($wallet->getId());
            $this->lBids          = $bids->select('id_lender_account = ' . $wallet->getId() . ' AND status = ' . Bids::STATUS_BID_PENDING, 'added DESC');
            $this->NbBids         = count($this->lBids);

            $this->attachments     = $wallet->getIdClient()->getAttachments();
            $this->attachmentTypes = $attachmentManager->getAllTypesForLender();

            /** @var \lender_tax_exemption $lenderTaxExemption */
            $lenderTaxExemption   = $this->loadData('lender_tax_exemption');
            $this->exemptionYears = array_column($lenderTaxExemption->select('id_lender = ' . $wallet->getId(), 'year DESC'), 'year');

            $this->solde        = $wallet->getAvailableBalance();
            $this->soldeRetrait = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->sumDebitOperationsByTypeAndYear($wallet, [OperationType::LENDER_WITHDRAW]);

            $start                  = new \DateTime('First day of january this year');
            $end                    = new \DateTime('NOW');
            $this->lenderOperations = $lenderOperationsManager->getLenderOperations($wallet, $start, $end, null, LenderOperationsManager::ALL_TYPES);
            $this->transfers        = $entityManager->getRepository('UnilendCoreBusinessBundle:Transfer')->findTransferByClient($wallet->getIdClient());

            $this->clientStatusMessage = $this->getMessageAboutClientStatus();
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
        $this->loadJs('default/component/add-file-input');

        $this->clients_mandats          = $this->loadData('clients_mandats');
        $this->nationalites             = $this->loadData('nationalites_v2');
        $this->pays                     = $this->loadData('pays_v2');
        $this->acceptations_legal_docs  = $this->loadData('acceptations_legal_docs');
        $this->settings                 = $this->loadData('settings');
        $this->clients                  = $this->loadData('clients');
        $this->clients_adresses         = $this->loadData('clients_adresses');
        $this->clients_status           = $this->loadData('clients_status');
        $this->clients_status_history   = $this->loadData('clients_status_history');
        $this->clientsStatusForHistory  = $this->loadData('clients_status');
        $this->acceptations_legal_docs  = $this->loadData('acceptations_legal_docs');
        $this->companies                = $this->loadData('companies');

        $this->lNatio                   = $this->nationalites->select();
        $this->lPays                    = $this->pays->select('', 'ordre ASC');

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AttachmentManager $attachmentManager */
        $attachmentManager = $this->get('unilend.service.attachment_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager $clientStatusManager */
        $clientStatusManager = $this->get('unilend.service.client_status_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LenderValidationManager $lenderValidationManager */
        $lenderValidationManager = $this->get('unilend.service.lender_validation_manager');
        /** @var \Unilend\Bundle\TranslationBundle\Service\TranslationManager $translationManager */
        $translationManager       = $this->get('unilend.service.translation_manager');
        $this->completude_wording = $translationManager->getAllTranslationsForSection('lender-completeness');

        $this->settings->get("Liste deroulante conseil externe de l'entreprise", 'type');
        $this->conseil_externe = json_decode($this->settings->value, true);
        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Users $user */
        $user = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);

        if (
            $this->params[0]
            && is_numeric($this->params[0])
            && $this->clients->get($this->params[0], 'id_client')
            && null !== $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->params[0], WalletType::LENDER)
        ) {
            $client = $wallet->getIdClient();
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            if (in_array($this->clients->type, [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
                $this->companies->get($this->clients->id_client, 'id_client_owner');

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
                $this->taxExemption    = $oLenderTaxExemption->getLenderExemptionHistory($wallet->getId());
                $this->aExemptionYears = array_column($this->taxExemption, 'year');
                $this->iNextYear       = date('Y') + 1;

                $this->settings->get("Liste deroulante origine des fonds", 'status = 1 AND type');
                $this->origine_fonds                 = $this->settings->value;
                $this->origine_fonds                 = explode(';', $this->origine_fonds);
                $this->taxExemptionUserHistoryAction = $this->getTaxExemptionHistoryActionDetails($this->users_history->getTaxExemptionHistoryAction($this->clients->id_client));
            }

            if (false === empty($client->getNaissance())) {
                $this->naissance = $client->getNaissance()->format('d/m/Y');
            } else {
                $this->naissance = '';
            }

            /** @var BankAccount $currentBankAccount */
            $this->currentBankAccount = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:BankAccount')->getLastModifiedBankAccount($client);

            if ($this->clients->telephone != '') {
                trim(chunk_split($this->clients->telephone, 2, ' '));
            }
            if ($this->companies->phone != '') {
                $this->companies->phone = trim(chunk_split($this->companies->phone, 2, ' '));
            }
            if ($this->companies->phone_dirigeant != '') {
                $this->companies->phone_dirigeant = trim(chunk_split($this->companies->phone_dirigeant, 2, ' '));
            }

            $this->clients_status->getLastStatut($this->clients->id_client);
            $this->lActions                 = $this->clients_status_history->select('id_client = ' . $this->clients->id_client, 'added DESC');
            $this->aTaxationCountryHistory  = $this->getTaxationHistory($wallet->getId());

            $this->clientStatusMessage = $this->getMessageAboutClientStatus();

            $attachments     = $client->getAttachments();
            $attachmentTypes = $attachmentManager->getAllTypesForLender();
            $this->setAttachments($attachments, $attachmentTypes);
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
                $clientStatusManager->addClientStatus($this->clients, $this->userEntity->getIdUser(), \clients_status::COMPLETENESS, $_SESSION['content_email_completude'][$this->clients->id_client]);

                unset($_SESSION['content_email_completude'][$this->clients->id_client]);
                $_SESSION['email_completude_confirm'] = true;

                header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $this->clients->id_client);
                die;
            } elseif (isset($_POST['send_edit_preteur'])) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\TaxManager $taxManager */
                $taxManager = $this->get('unilend.service.tax_manager');

                if (in_array($this->clients->type, [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) {

                    if (false === empty($_POST['meme-adresse'])) {
                        $this->clients_adresses->meme_adresse_fiscal = ClientsAdresses::SAME_ADDRESS_FOR_POSTAL_AND_FISCAL;
                    } else {
                        $this->clients_adresses->meme_adresse_fiscal = ClientsAdresses::DIFFERENT_ADDRESS_FOR_POSTAL_AND_FISCAL;
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

                    $birthday = null;
                    if (isset($_POST['naissance']) && 1 === preg_match("#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#", $_POST['naissance'])) {
                        $birthday = \DateTime::createFromFormat('d/m/Y', $_POST['naissance']);
                    }

                    if (null === $birthday) {
                        $_SESSION['freeow']['title']   = 'Erreur de données clients';
                        $_SESSION['freeow']['message'] = 'Le format de la date de naissance n\'est pas correct';
                        header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $this->clients->id_client);
                        die;
                    }

                    $this->clients->telephone           = str_replace(' ', '', $_POST['phone']);
                    $this->clients->mobile              = str_replace(' ', '', $_POST['mobile']);
                    $this->clients->ville_naissance     = $_POST['com-naissance'];
                    $this->clients->insee_birth         = $_POST['insee_birth'];
                    $this->clients->naissance           = $birthday->format('Y-m-d');
                    $this->clients->id_pays_naissance   = $_POST['id_pays_naissance'];
                    $this->clients->id_nationalite      = $_POST['nationalite'];
                    $this->clients->id_langue           = 'fr';
                    $this->clients->type                = ($_POST['id_pays_naissance'] == \nationalites_v2::NATIONALITY_FRENCH) ? Clients::TYPE_PERSON : Clients::TYPE_PERSON_FOREIGNER;
                    $this->clients->fonction            = '';
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
                            $this->clients_mandats->status        = UniversignEntityInterface::STATUS_SIGNED;

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
                                $oLenderTaxExemption->id_lender   = $wallet->getId();
                                $oLenderTaxExemption->iso_country = 'FR';
                                $oLenderTaxExemption->year        = $iExemptionYear;
                                $oLenderTaxExemption->id_user     = $this->userEntity->getIdUser();
                                $oLenderTaxExemption->create();
                                $taxExemptionHistory[] = ['year' => $oLenderTaxExemption->year, 'action' => 'adding'];
                            }
                        }
                    }

                    if (in_array($this->iNextYear, $this->aExemptionYears) && false === isset($_POST['tax_exemption'][$this->iNextYear])) {
                        $oLenderTaxExemption->get($wallet->getId() . '" AND year = ' . $this->iNextYear . ' AND iso_country = "FR', 'id_lender');
                        $taxExemptionHistory[] = ['year' => $oLenderTaxExemption->year, 'action' => 'deletion'];
                        $oLenderTaxExemption->delete($oLenderTaxExemption->id_lender_tax_exemption);
                    }

                    if (false === empty($taxExemptionHistory)) {
                        $this->users_history->histo(\users_history::FORM_ID_LENDER, \users_history::FORM_NAME_TAX_EXEMPTION, $this->userEntity->getIdUser(), serialize([
                            'id_client'     => $this->clients->id_client,
                            'modifications' => $taxExemptionHistory
                        ]));
                    }

                    $this->clients_adresses->update();

                    $serialize = serialize(['id_client' => $this->clients->id_client, 'post'  => $_POST, 'files' => $_FILES]);
                    $this->users_history->histo(\users_history::FORM_ID_LENDER, 'modif info preteur', $this->userEntity->getIdUser(), $serialize);

                    if (isset($_POST['statut_valider_preteur']) && 1 == $_POST['statut_valider_preteur']) {
                        $validation = $lenderValidationManager->validateClient($this->clients, $this->userEntity);
                        if (true !== $validation) {
                            $this->changeClientStatus($this->clients, Clients::STATUS_OFFLINE, \users_history::FORM_ID_LENDER);
                            header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $this->clients->id_client);
                            die;
                        }

                        $this->validateBankAccount($_POST['id_bank_account']);
                        $_SESSION['compte_valide'] = true;
                        $applyTaxCountry           = true;
                    }

                    if (true === $applyTaxCountry) {
                        $taxManager->addTaxToApply($wallet->getIdClient(), $this->clients_adresses, $this->userEntity->getIdUser());
                    }
                    header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $this->clients->id_client);
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
                        $this->companies->status_adresse_correspondance = Companies::SAME_ADDRESS_FOR_POSTAL_AND_FISCAL;
                        $this->clients_adresses->adresse1               = $_POST['adresse'];
                        $this->clients_adresses->ville                  = $_POST['ville'];
                        $this->clients_adresses->cp                     = $_POST['cp'];
                    } else {
                        $this->companies->status_adresse_correspondance = Companies::DIFFERENT_ADDRESS_FOR_POSTAL_AND_FISCAL;
                        $this->clients_adresses->adresse1               = $_POST['adresse2'];
                        $this->clients_adresses->ville                  = $_POST['ville2'];
                        $this->clients_adresses->cp                     = $_POST['cp2'];
                    }

                    $this->companies->adresse1 = $_POST['adresse'];
                    $this->companies->city     = $_POST['ville'];
                    $this->companies->zip      = $_POST['cp'];

                    $this->companies->status_client = $_POST['enterprise'];

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
                    if (Companies::CLIENT_STATUS_DELEGATION_OF_POWER == $this->companies->status_client || Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT == $this->companies->status_client) {
                        $this->companies->civilite_dirigeant = $_POST['civilite2_e'];
                        $this->companies->nom_dirigeant      = $this->ficelle->majNom($_POST['nom2_e']);
                        $this->companies->prenom_dirigeant   = $this->ficelle->majNom($_POST['prenom2_e']);
                        $this->companies->fonction_dirigeant = $_POST['fonction2_e'];
                        $this->companies->email_dirigeant    = $_POST['email2_e'];
                        $this->companies->phone_dirigeant    = str_replace(' ', '', $_POST['phone2_e']);

                        // externe
                        if (Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT == $this->companies->status_client) {
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
                    $this->clients->type            = Clients::TYPE_LEGAL_ENTITY;
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

                    $attachmentTypeRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType');
                    foreach ($this->request->files->all() as $attachmentTypeId => $uploadedFile) {
                        if ($uploadedFile) {
                            $attachmentType   = $attachmentTypeRepository->find($attachmentTypeId);
                            if ($attachmentType) {
                                $attachmentManager->upload($client, $attachmentType, $uploadedFile);
                            }
                        }
                    }

                    $this->clients->update();
                    $this->clients_adresses->update();

                    $serialize = serialize(['id_client' => $this->clients->id_client, 'post'      => $_POST, 'files'     => $_FILES ]);
                    $this->users_history->histo(\users_history::FORM_ID_LENDER, 'modif info preteur personne morale', $this->userEntity->getIdUser(), $serialize);

                    if (isset($_POST['statut_valider_preteur']) && $_POST['statut_valider_preteur'] == 1) {
                        $lenderValidationManager->validateClient($this->clients, $this->userEntity);
                        $this->validateBankAccount($_POST['id_bank_account']);
                        $_SESSION['compte_valide'] = true;
                    }

                    header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $this->clients->id_client);
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
        $this->companies    = $this->loadData('companies');

        $statusOrderedByPriority = [
            \clients_status::TO_BE_CHECKED,
            \clients_status::MODIFICATION,
            \clients_status::COMPLETENESS_REPLY,
            \clients_status::COMPLETENESS,
            \clients_status::COMPLETENESS_REMINDER
        ];

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\ClientsRepository $clientsRepository */
        $clientsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $this->lPreteurs   = $clientsRepository->getClientsToValidate($statusOrderedByPriority);

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

        $this->clients = $this->loadData('clients');
        $this->clients->get($this->params[0], 'id_client');

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager      = $this->get('doctrine.orm.entity_manager');
        $this->mailTemplate = $entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates')->findOneBy([
            'type'   => 'completude',
            'locale' => $this->getParameter('locale'),
            'status' => MailTemplates::STATUS_ACTIVE,
            'part'   => MailTemplates::PART_TYPE_CONTENT
        ]);
    }

    public function _completude_preview_iframe()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $_SESSION['request_url'] = $this->url;

        $this->clients                = $this->loadData('clients');
        $this->clients_status_history = $this->loadData('clients_status_history');
        $this->settings               = $this->loadData('settings');

        $this->clients->get($this->params[0], 'id_client');

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

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $mailTemplate  = $entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates')->findOneBy([
            'type'   => 'completude',
            'locale' => $this->getParameter('locale'),
            'status' => MailTemplates::STATUS_ACTIVE,
            'part'   => MailTemplates::PART_TYPE_CONTENT
        ]);

        echo strtr($mailTemplate->getContent(), $tabVars);
    }

    public function _offres_de_bienvenue()
    {
        /** @var \NumberFormatter $currencyFormatter */
        $this->numberFormatter = $this->get('number_formatter');
        /** @var \NumberFormatter $currencyFormatter */
        $this->currencyFormatter = $this->get('currency_formatter');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\WelcomeOfferManager $welcomeOfferManager */
        $welcomeOfferManager  = $this->get('unilend.service.welcome_offer_manager');
        $paidOutWelcomeOffers = $entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenuesDetails');

        if (isset($_SESSION['create_new_welcome_offer']['errors'])) {
            $this->newWelcomeOfferFormErrors = $_SESSION['create_new_welcome_offer']['errors'];
            unset($_SESSION['create_new_welcome_offer']['errors']);
        }

        if (isset($_SESSION['pay_out_welcome_offer']['errors'])) {
            $this->payOutWelcomeOfferFormErrors = $_SESSION['pay_out_welcome_offer']['errors'];
            unset($_SESSION['pay_out_welcome_offer']['errors']);
        }

        if (isset($_SESSION['pay_out_welcome_offer']['success'])) {
            $this->payOutWelcomeOfferFormSuccess = $_SESSION['pay_out_welcome_offer']['success'];
            unset($_SESSION['pay_out_welcome_offer']['success']);
        }

        if (null !== $this->request->request->get('form_send_new_offer')) {
            $this->createNewWelcomeOffer();
        }

        unset($_SESSION['forms']['rattrapage_offre_bienvenue']);

        if (isset($_POST['spy_search'])) {
            /** @var \clients $clientData */
            $clientData = $this->loadData('clients');

            if (false === empty($_POST['id_client'])) {
                $this->clientsWithoutWelcomeOffer                             = $clientData->getClientsWithNoWelcomeOffer($_POST['id_client']);
                $_SESSION['forms']['rattrapage_offre_bienvenue']['id_client'] = $_POST['id_client'];
            } else {
                $_SESSION['freeow']['title']   = 'Recherche non aboutie. Indiquez la liste des ID clients';
                $_SESSION['freeow']['message'] = 'Il faut séparer les ids par des virgules';
            }
        }

        if (isset($_POST['affect_welcome_offer']) && isset($this->params[0]) && is_numeric($this->params[0])) {
            $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->params[0]);
            if (null !== $client) {
                $this->payOutWelcomeOffer($client);
            }
        }

        $unilendPromotionWalletType          = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendPromotionWallet              = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $unilendPromotionWalletType]);
        $this->sumDispoPourOffres            = $unilendPromotionWallet->getAvailableBalance();
        $this->alreadyPaidOutAllOffers       = $entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenuesDetails')->getSumPaidOutForOffer();
        $this->offerIsDisplayedOnHome        = $welcomeOfferManager->displayOfferOnHome();
        $this->offerIsDisplayedOnLandingPage = $welcomeOfferManager->displayOfferOnLandingPage();

        $this->currentOfferHomepage = $entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenues')->findOneBy([
            'status' => OffresBienvenues::STATUS_ONLINE,
            'type'   => OffresBienvenues::TYPE_HOME
        ]);
        if (null !== $this->currentOfferHomepage) {
            $this->alreadyPaidOutCurrentOfferHomepage  = $paidOutWelcomeOffers->getSumPaidOutForOffer($this->currentOfferHomepage);
            $this->remainingAmountCurrentOfferHomepage = round(bcsub($this->currentOfferHomepage->getMontantLimit(), $this->alreadyPaidOutCurrentOfferHomepage, 4), 2);
        }

        $this->currentOfferLandingPage = $entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenues')->findOneBy([
            'status' => OffresBienvenues::STATUS_ONLINE,
            'type'   => OffresBienvenues::TYPE_LANDING_PAGE
        ]);
        if (null !== $this->currentOfferLandingPage) {
            $this->alreadyPaidOutCurrentOfferLandingPage  = $paidOutWelcomeOffers->getSumPaidOutForOffer($this->currentOfferLandingPage);
            $this->remainingAmountCurrentOfferLandingPage = round(bcsub($this->currentOfferLandingPage->getMontantLimit(), $this->alreadyPaidOutCurrentOfferLandingPage, 4), 2);
        }

        $this->pastOffers = $entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenues')->findBy(['status' => OffresBienvenues::STATUS_OFFLINE]);
    }

    private function createNewWelcomeOffer()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $start     = $this->request->request->get('start');
        $amount    = $this->request->request->getInt('amount');
        $maxAmount = $this->request->request->getInt('max_amount');
        $type      = $this->request->request->get('type_offer');

        $startDate = \DateTime::createFromFormat('d/m/Y', $start);
        if (false === $startDate) {
            $_SESSION['create_new_welcome_offer']['errors'][] = 'Le format de la date n\'nest pas correct';
        }
        if ($amount > $maxAmount) {
            $_SESSION['create_new_welcome_offer']['errors'][] = 'Le montant de l\'offre (' . $amount . ') ne peut pas être inférieur au montant limite (' . $maxAmount . ')';
        }
        if (empty($type)) {
            $_SESSION['create_new_welcome_offer']['errors'][] = 'Il faut choisir le type de page sur laquelle l\'offre va être affiché';
        }
        if (false === empty($_SESSION['create_new_welcome_offer']['errors'])) {
            header('Location :' . $this->lurl . '/preteurs/offres_de_bienvenue');
            die;
        }

        $welcomeOffer = new OffresBienvenues();
        $welcomeOffer->setDebut($startDate);
        $welcomeOffer->setMontant(bcmul($amount, 100));
        $welcomeOffer->setMontantLimit(bcmul($maxAmount, 100));
        $welcomeOffer->setType($type);
        $welcomeOffer->setIdUser($this->userEntity->getIdUser());
        $welcomeOffer->setStatus(OffresBienvenues::STATUS_ONLINE);

        $entityManager->persist($welcomeOffer);
        $entityManager->flush($welcomeOffer);

        header('Location: ' . $this->lurl . '/preteurs/offres_de_bienvenue');
        die;
    }

    /**
     * @param Clients $client
     */
    private function payOutWelcomeOffer(Clients $client)
    {
        $response = $this->get('unilend.service.welcome_offer_manager')->payOutWelcomeOffer($client);

        switch ($response['code']) {
            case 0:
                $_SESSION['pay_out_welcome_offer']['success'][] = 'Offre de bienvenue crédité';
                break;
            default:
                $_SESSION['pay_out_welcome_offer']['errors'][]= 'Offre de bienvenue non crédité';
                $_SESSION['pay_out_welcome_offer']['errors'][] = $response['message'];
                break;
        }

        header('Location: ' . $this->lurl . '/preteurs/offres_de_bienvenue');
        die;
    }

    public function _affect_welcome_offer()
    {
        $this->hideDecoration();
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\WelcomeOfferManager $welcomeOfferManager */
        $welcomeOfferManager = $this->get('unilend.service.welcome_offer_manager');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager      = $this->get('doctrine.orm.entity_manager');
        $this->client       = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->params[0]);
        $welcomeOfferType   = $welcomeOfferManager->getWelcomeOfferTypeForClient($this->client);
        $this->welcomeOffer = $entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenues')->findOneBy(['type' => $welcomeOfferType]);

        if (false === $this->client->isNaturalPerson()) {
            $this->company = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $this->client->getIdClient()]);
        }
    }

    public function _deactivate_welcome_offer()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $welcomeOffer  = $entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenues')->find($this->request->request->getInt('welcome_offer_id'));

        if (
            null !== $welcomeOffer
            && OffresBienvenues::STATUS_ONLINE == $welcomeOffer->getStatus()
            && true == $this->request->request->get('deactivate_welcome_offer')
        ) {
            $welcomeOffer->setStatus(OffresBienvenues::STATUS_OFFLINE);
            $welcomeOffer->setFin(new \DateTime('NOW'));

            $entityManager->flush($welcomeOffer);
        }

        header('Location: ' . $this->lurl . '/preteurs/offres_de_bienvenue');
        die;
    }

    public function _email_history()
    {
        /** @var \Unilend\Bundle\MessagingBundle\Service\MailQueueManager $oMailQueueManager */
        $oMailQueueManager = $this->get('unilend.service.mail_queue');

        $clientNotifications = $this->loadData('clients_gestion_notifications');
        $this->clients       = $this->loadData('clients');

        if (
            $this->clients->get($this->params[0], 'id_client')
        ) {
            if (isset($_POST['send_dates'])) {
                $_SESSION['FilterMails']['StartDate'] = $_POST['debut'];
                $_SESSION['FilterMails']['EndDate']   = $_POST['fin'];

                header('Location:  ' . $this->lurl . '/preteurs/email_history/' . $this->params[0]);
                die;
            }

            $this->aClientsNotifications = $clientNotifications->getNotifs($this->clients->id_client);
            $this->aNotificationPeriode  = \clients_gestion_notifications::getAllPeriod();

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
            $this->aEmailsSentToClient   = $oMailQueueManager->searchSentEmails($this->clients->id_client, null, null, null, $oDateTimeStart, $oDateTimeEnd);
            $this->clientStatusMessage = $this->getMessageAboutClientStatus();
        }
    }

    public function _portefeuille()
    {
        $this->clients          = $this->loadData('clients');
        $this->loans            = $this->loadData('loans');
        $this->projects         = $this->loadData('projects');
        $this->echeanciers      = $this->loadData('echeanciers');
        /** @var underlying_contract contract */
        $this->contract = $this->loadData('underlying_contract');
        /** @var \Symfony\Component\Translation\TranslatorInterface translator */
        $this->translator  = $this->get('translator');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LoanManager loanManager */
        $this->loanManager = $this->get('unilend.service.loan_manager');
        /** @var \loans loan */
        $this->loan = $this->loadData('loans');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LenderManager lenderManager */
        $lenderManager = $this->get('unilend.service.lender_manager');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var LenderStatisticRepository $lenderStatisticsRepository */
        $lenderStatisticsRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:LenderStatistic');

        if (
            $this->params[0]
            && is_numeric($this->params[0])
            && $this->clients->get($this->params[0], 'id_client')
            && null !== $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->params[0], WalletType::LENDER)
        ) {
            $this->wallet          = $wallet;
            $this->lSumLoans       = $this->loans->getSumLoansByProject($wallet->getId());
            $this->aProjectsInDebt = $this->projects->getProjectsInDebt();

            /** @var LenderStatistic $lastIRR */
            $this->IRR = $lenderStatisticsRepository->findOneBy(['idWallet' => $wallet, 'typeStat' => LenderStatistic::TYPE_STAT_IRR], ['added' => 'DESC']);

            $statusOk                = [\projects_status::EN_FUNDING, \projects_status::FUNDE, \projects_status::FUNDING_KO, \projects_status::PRET_REFUSE, \projects_status::REMBOURSEMENT, \projects_status::REMBOURSE, \projects_status::REMBOURSEMENT_ANTICIPE];
            $statusKo                = [\projects_status::PROBLEME, \projects_status::RECOUVREMENT, \projects_status::DEFAUT, \projects_status::PROBLEME_J_X, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE];
            $this->projectsPublished = $this->projects->countProjectsSinceLendersubscription($this->clients->id_client, array_merge($statusOk, $statusKo));
            $this->problProjects     = $this->projects->countProjectsByStatusAndLender($wallet->getId(), $statusKo);
            $this->totalProjects     = $this->loans->getProjectsCount($wallet->getId());

            $this->clientStatusMessage = $this->getMessageAboutClientStatus();

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AutoBidSettingsManager $oAutoBidSettingsManager */
            $oAutoBidSettingsManager   = $this->get('unilend.service.autobid_settings_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ClientManager $oClientManager */
            $oClientManager            = $this->get('unilend.service.client_manager');

            $this->bAutoBidOn          = $oAutoBidSettingsManager->isOn($wallet->getIdClient());
            $this->aSettingsDates      = $oAutoBidSettingsManager->getLastDateOnOff($this->clients->id_client);
            if (0 < count($this->aSettingsDates)) {
                $this->sValidationDate = $oAutoBidSettingsManager->getValidationDate($wallet->getIdClient())->format('d/m/Y');
            }
            $this->fAverageRateUnilend = round($this->projects->getAvgRate(), 1);
            $this->bIsBetaTester       = $oClientManager->isBetaTester($this->clients);

            $this->settings->get('date-premier-projet-tunnel-de-taux', 'type');
            $startingDate           = $this->settings->value;
            $this->aAutoBidSettings = [];
            /** @var autobid $autobid */
            $autobid          = $this->loadData('autobid');
            $aAutoBidSettings = $autobid->getSettings($wallet->getId(), null, null, [\autobid::STATUS_ACTIVE, \autobid::STATUS_INACTIVE]);
            foreach ($aAutoBidSettings as $aSetting) {
                $aSetting['AverageRateUnilend']                                          = $this->projects->getAvgRate($aSetting['evaluation'], $aSetting['period_min'], $aSetting['period_max'], $startingDate);
                $this->aAutoBidSettings[$aSetting['id_period']][$aSetting['evaluation']] = $aSetting;
            }

            $this->hasTransferredLoans = $lenderManager->hasTransferredLoans($wallet->getIdClient());
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\CIPManager $cipManager */
            $cipManager       = $this->get('unilend.service.cip_manager');
            $this->cipEnabled = $cipManager->hasValidEvaluation($wallet->getIdClient());
        }
    }

    public function _control_fiscal_city()
    {
        /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\ClientsRepository $clientRepository */
        $clientRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients');
        $this->aLenders = $clientRepository->getLendersToMatchCity(200);
    }

    public function _control_birth_city()
    {
        /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\ClientsRepository $clientRepository */
        $clientRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients');
        $this->aLenders = $clientRepository->getLendersToMatchBirthCity(200);
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


            header('Location:  ' . $this->lurl . '/preteurs/edit/' . $oClient->id_client);
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
        try {
            $message->setTo($oClient->email);
            $mailer = $this->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->get('logger')->warning(
                'Could not send email: confirmation-fermeture-compte-preteur - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $oClient->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
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
        try {
            $message->setTo($this->clients->email);
            $mailer = $this->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->get('logger')->warning(
                'Could not send email: completude - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $this->clients->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * @return string
     */
    private function getMessageAboutClientStatus()
    {
        /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\ClientStatusRepository $clientStatusRepository */
        $clientStatusRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:ClientsStatus');
        /** @var ClientsStatus $currentStatus */
        $currentStatus       = $clientStatusRepository->getLastClientStatus($this->clients->id_client);
        $creationTime        = strtotime($this->clients->added);
        $clientStatusMessage = '';

        if (null === $currentStatus) {
            return $clientStatusMessage;
        }
        switch ($currentStatus->getStatus()) {
            case ClientsStatus::TO_BE_CHECKED :
                $clientStatusMessage = '<div class="attention">Attention : compte non validé - créé le '. date('d/m/Y', $creationTime) . '</div>';
                break;
            case ClientsStatus::COMPLETENESS :
            case ClientsStatus::COMPLETENESS_REMINDER:
            case ClientsStatus::COMPLETENESS_REPLY:
                $clientStatusMessage = '<div class="attention" style="background-color:#F9B137">Attention : compte en complétude - créé le ' . date('d/m/Y', $creationTime) . ' </div>';
                break;
            case ClientsStatus::MODIFICATION:
                $clientStatusMessage = '<div class="attention" style="background-color:#F2F258">Attention : compte en modification - créé le ' . date('d/m/Y', $creationTime) . '</div>';
                break;
            case ClientsStatus::CLOSED_LENDER_REQUEST:
                $clientStatusMessage = '<div class="attention">Attention : compte clôturé (mis hors ligne) à la demande du prêteur</div>';
                break;
            case ClientsStatus::CLOSED_BY_UNILEND:
                $clientStatusMessage = '<div class="attention">Attention : compte clôturé (mis hors ligne) par Unilend</div>';
                break;
            case ClientsStatus::VALIDATED:
                $clientStatusMessage = '';
                break;
            case ClientsStatus::CLOSED_DEFINITELY:
                $clientStatusMessage = '<div class="attention">Attention : compte définitivement fermé </div>';
                break;
            default:
                if (Clients::SUBSCRIPTION_STEP_PERSONAL_INFORMATION == $this->clients->etape_inscription_preteur) {
                    $clientStatusMessage = '<div class="attention">Attention : Inscription non terminé </div>';
                } else {
                    /** @var \Psr\Log\LoggerInterface $logger */
                    $logger = $this->get('logger');
                    $logger->warning('Unknown client status "' . $currentStatus->getStatus() . '"', ['client' => $this->clients->id_client]);
                }
                break;
        }

        return $clientStatusMessage;
    }

    private function setClientVigilanceStatusData()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $client                       = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->clients->id_client);
        $this->vigilanceStatusHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory')->findBy(['client' => $client], ['id' => 'DESC']);

        if (empty($this->vigilanceStatusHistory)) {
            $this->vigilanceStatus = [
                'status'  => VigilanceRule::VIGILANCE_STATUS_LOW,
                'message' => 'Vigilance standard'
            ];
            $this->userEntity = $entityManager->getRepository('UnilendCoreBusinessBundle:Users');
            return;
        }
        $this->clientAtypicalOperations = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAtypicalOperation')->findBy(['client' => $client], ['added' => 'DESC']);

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
        $this->userEntity                   = $entityManager->getRepository('UnilendCoreBusinessBundle:Users');
        $this->clientVigilanceStatusHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory');
    }

    public function _saveBetaTesterSetting()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $oClientSettingsManager = $this->get('unilend.service.client_settings_manager');
        $oClient                = $this->loadData('clients');

         if(isset($this->params[0]) && is_numeric($this->params[0]) && isset($this->params[1]) && in_array($this->params[1], ['on', 'off'])){
             $oClient->get($this->params[0]);
             $sValue = ('on' == $this->params[1]) ? \client_settings::BETA_TESTER_ON : \client_settings::BETA_TESTER_OFF;
             $oClientSettingsManager->saveClientSetting($oClient, \client_setting_type::TYPE_BETA_TESTER, $sValue);

             header('Location:  ' . $this->lurl . '/preteurs/portefeuille/' . $oClient->id_client);
             die;
         }
    }

    public function _lenderOnlineOffline()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var \clients_status_history $oClientsStatusHistory */
        $oClientsStatusHistory = $this->loadData('clients_status_history');
        /** @var \clients $oClient */
        $oClient = $this->loadData('clients');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager $clientStatusManager */
        $clientStatusManager = $this->get('unilend.service.client_status_manager');

        $oClient->get($this->params[1], 'id_client');

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
            header('Location:  ' . $this->lurl . '/preteurs/edit_preteur/' . $oClient->id_client);
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'deactivate') {
            $this->changeClientStatus($oClient, $this->params[2], 1);
            $this->sendEmailClosedAccount($oClient);
            $clientStatusManager->addClientStatus($oClient, $_SESSION['user']['id_user'], \clients_status::CLOSED_LENDER_REQUEST);
            header('Location:  ' . $this->lurl . '/preteurs/edit_preteur/' . $oClient->id_client);
            die;
        }
    }

    public function _bids()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $this->clients = $this->loadData('clients');
        /** @var \bids $bids */
        $bids = $this->loadData('bids');

        if (
            $this->params[0]
            && is_numeric($this->params[0])
            && $this->clients->get($this->params[0], 'id_client')
            && null !== $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->params[0], WalletType::LENDER)
        ) {
            $this->clientStatusMessage = $this->getMessageAboutClientStatus();

            if (isset($_POST['send_dates'])) {
                $_SESSION['FilterBids']['StartDate'] = $_POST['debut'];
                $_SESSION['FilterBids']['EndDate']   = $_POST['fin'];

                header('Location:  ' . $this->lurl . '/preteurs/bids/' . $this->params[0]);
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


            foreach ($bids->getBidsByLenderAndDates($wallet, $dateTimeStart, $dateTimeEnd) as $key => $value) {
                $this->bidList[$key] = $value;
            }
        }
    }

    public function _extract_bids_csv()
    {
        $this->autoFireView = false;
        $this->hideDecoration();
        /** @var \bids $bids */
        $bids = $this->loadData('bids');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (
            isset($this->params[0])
            && is_numeric($this->params[0])
            && null !== $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->params[0], WalletType::LENDER)
        ){
            $lenderBids = $bids->getBidsByLenderAndDates($wallet);

            PHPExcel_Settings::setCacheStorageMethod(
                PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
                ['memoryCacheSize' => '2048MB', 'cacheTime' => 1200]
            );

            $header      = ['Id projet', 'Id bid', 'Client', 'Date bid', 'Statut bid', 'Montant', 'Taux'];
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
            header('Content-Disposition: attachment;filename=bids_client_' . $wallet->getIdClient()->getIdClient() . '.csv');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Expires: 0');

            /** @var \PHPExcel_Writer_CSV $writer */
            $writer = PHPExcel_IOFactory::createWriter($document, 'CSV');
            $writer->setUseBOM(true);
            $writer->setDelimiter(';');
            $writer->save('php://output');
        }
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
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var BankAccount $currentBankAccount */
        $bankAccount = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->find($idBankAccount);

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

    public function _notifications()
    {
        $this->autoFireHeader = true;
        $this->autoFireHead   = true;
        $this->autoFireFooter = true;

        $this->projectList = [];

        if (isset($_POST['searchProject'])) {
            /** @var \projects $project */
            $project = $this->loadData('projects');

            if (false === empty($_POST['projectId'])) {
                $this->projectList = $project->searchDossiers(null, null, null, null, null, null, null, $_POST['projectId']);
            } elseif (false === empty($_POST['projectTitle'])) {
                $this->projectList = $project->searchDossiers(null, null, null, null, null, null, null, null, filter_var($_POST['projectTitle'], FILTER_SANITIZE_STRING));
            }
            if (isset($this->projectList[0])) {
                array_shift($this->projectList);
            }
        }
    }

    public function _addNotification()
    {
        $this->autoFireView   = false;
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;

        header('Content-Type: application/json');

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (
            false === empty($_POST['notificationSubject'])
            && false === empty($_POST['notificationContent'])
            && false === empty($_POST['selectedProjectId'])
            && $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find(filter_var($_POST['selectedProjectId'], FILTER_SANITIZE_NUMBER_INT))
        ) {
            $projectNotification = new ProjectNotification();
            $projectNotification->setIdProject($project)
                ->setSubject(filter_var($_POST['notificationSubject'], FILTER_SANITIZE_STRING))
                ->setContent($_POST['notificationContent'])
                ->setIdUser($this->userEntity);

            if (isset($_POST['notificationDate']) && ($notificationDate = \DateTime::createFromFormat('d/m/Y', $_POST['notificationDate']))) {
                $projectNotification->setNotificationDate($notificationDate);
            }
            $entityManager->persist($projectNotification);
            $entityManager->flush($projectNotification);
            echo json_encode([
                'message' => 'Notification ajouté avec succès',
                'status'  => 'ok'
            ]);

            return;
        }
        echo json_encode([
            'message' => 'Echec lors de l\'ajout de la notification',
            'status'  => 'ko'
        ]);
    }
}
