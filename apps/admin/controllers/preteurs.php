<?php

use Box\Spout\{Common\Type, Writer\WriterFactory};
use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\{AddressType, Attachment, AttachmentType, Autobid, Bids, Clients, ClientsGestionNotifications, ClientsGestionTypeNotif, ClientsStatus, Companies,
    LenderStatistic, LenderTaxExemption, Loans, MailTemplates, OffresBienvenues, OperationType, ProjectNotification, ProjectsStatus, UsersHistory, VigilanceRule, Wallet, WalletType, Zones};
use Unilend\Bundle\CoreBusinessBundle\Repository\LenderStatisticRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\{AttachmentManager, ClientAuditer, ClientDataHistoryManager, ClientStatusManager, LenderOperationsManager};

class preteursController extends bootstrap
{
    /** @var Wallet */
    protected $wallet;
    /** @var Clients */
    protected $client;

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
        if ($this->request->request->has('form_search_preteur')) {
            $clientId    = $this->request->request->get('id');
            $email       = $this->request->request->get('email');
            $lastName    = $this->request->request->get('nom');
            $firstName   = $this->request->request->get('prenom');
            $companyName = $this->request->request->get('raison_sociale');
            $siren       = $this->request->request->get('siren');

            if (empty($clientId) && empty($lastName) && empty($email) && empty($firstName) && empty($companyName) && empty($siren)) {
                $_SESSION['error_search'][] = 'Veuillez remplir au moins un champ';
            }

            $email = empty($email) ? null : filter_var($email, FILTER_SANITIZE_EMAIL);
            if (false === $email) {
                $_SESSION['error_search'][] = 'Format de l\'email est non valide';
            }

            $clientId = empty($clientId) ? null : filter_var($clientId, FILTER_VALIDATE_INT);
            if (false === $clientId) {
                $_SESSION['error_search'][] = 'L\'id du client doit être numérique';
            }

            $lastName = empty($lastName) ? null : filter_var($lastName, FILTER_SANITIZE_STRING);
            if (false === $lastName) {
                $_SESSION['error_search'][] = 'Le format du nom n\'est pas valide';
            }

            $firstName = empty($firstName) ? null : filter_var($firstName, FILTER_SANITIZE_STRING);
            if (false === $firstName) {
                $_SESSION['error_search'][] = 'Le format du prenom n\'est pas valide';
            }

            $companyName = empty($companyName) ? null : filter_var($companyName, FILTER_SANITIZE_STRING);
            if (false === $companyName) {
                $_SESSION['error_search'][] = 'Le format de la raison sociale n\'est pas valide';
            }

            $siren = empty($siren) ? null : trim(filter_var($siren, FILTER_SANITIZE_STRING));
            if (false === $siren) {
                $_SESSION['error_search'][] = 'Le format du siren n\'est pas valide';
            }

            if (false === empty($_SESSION['error_search'])) {
                header('Location: ' . $this->lurl . '/preteurs/search');
                die;
            }

            /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\ClientsRepository $clientRepository */
            $clientRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients');
            try {
                $this->lPreteurs = $clientRepository->findLenders($clientId, $email, $lastName, $firstName, $companyName, $siren);

                if (false === empty($this->lPreteurs) && false === empty($_POST['id']) && 1 == count($this->lPreteurs)) {
                    header('Location: ' . $this->lurl . '/preteurs/edit/' . $this->lPreteurs[0]['id_client']);
                    die;
                }
            } catch (\Doctrine\DBAL\DBALException $exception) {
                $this->get('logger')->error('Could not search for lenders using given parameters. Exception message: ' . $exception->getMessage(), [
                    'post'          => $this->request->request->all(),
                    'filtered_post' => ['id' => $clientId, 'email' => $email, 'nom' => $lastName, 'prenom' => $firstName, 'raison_sociale' => $companyName, 'siren' => $siren],
                    'class'         => __CLASS__,
                    'method'        => __METHOD__,
                    'file'          => $exception->getFile(),
                    'line'          => $exception->getLine()
                ]);
            }
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
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var LenderOperationsManager $lenderOperationsManager */
        $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');
        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = $this->get('logger');

        $this->clients          = $this->loadData('clients');
        $this->echeanciers      = $this->loadData('echeanciers');
        $this->projects         = $this->loadData('projects');
        $this->clients_mandats  = $this->loadData('clients_mandats');
        $this->companies        = $this->loadData('companies');
        /** @var \loans $loans */
        $loans = $this->loadData('loans');
        /** @var \bids $bids */
        $bids = $this->loadData('bids');

        if (
            $this->params[0]
            && false !== filter_var($this->params[0], FILTER_VALIDATE_INT)
            && $this->clients->get($this->params[0], 'id_client')
            && null !== $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->params[0], WalletType::LENDER)
        ) {
            $this->wallet              = $wallet;
            $this->client              = $wallet->getIdClient();
            $this->validatedAddress    = null;
            $this->lastModifiedAddress = null;

            try {
                if ($this->client->isNaturalPerson()) {
                    $this->validatedAddress    = $this->client->getIdAddress();
                    $this->lastModifiedAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')
                        ->findLastModifiedNotArchivedAddressByType($this->client, AddressType::TYPE_MAIN_ADDRESS);
                } else {
                    $this->companies->get($this->client->getIdClient(), 'id_client_owner');
                    $this->companyEntity       = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->companies->id_company);
                    $this->validatedAddress    = $this->companyEntity->getIdAddress();
                    $this->lastModifiedAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')
                        ->findLastModifiedNotArchivedAddressByType($this->companyEntity, AddressType::TYPE_MAIN_ADDRESS);
                }
            } catch (\Exception $exception) {
                $logger->error('An exception occurred while getting lender address. Message: ' . $exception->getMessage(), [
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'id_client' => $this->client->getIdClient()
                ]);
            }

            $this->nb_pret  = $loans->counter('id_wallet = ' . $wallet->getId() . ' AND status = ' . Loans::STATUS_ACCEPTED);
            $this->txMoyen  = $loans->getAvgPrets($wallet->getId());
            $this->sumPrets = $loans->sumPrets($wallet->getId());

            if (isset($this->params[1])) {
                $this->lEncheres = $loans->select('id_wallet = ' . $wallet->getId() . ' AND YEAR(added) = ' . $this->params[1] . ' AND status = ' . Loans::STATUS_ACCEPTED);
            } else {
                $this->lEncheres = $loans->select('id_wallet = ' . $wallet->getId() . ' AND YEAR(added) = YEAR(CURDATE()) AND status = ' . Loans::STATUS_ACCEPTED);
            }

            $this->SumDepot = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->sumCreditOperationsByTypeAndYear($wallet, [OperationType::LENDER_PROVISION]);
            $provisionType  = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneByLabel(OperationType::LENDER_PROVISION);
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Operation $firstProvision */
            $firstProvision       = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->findOneBy(['idWalletCreditor' => $wallet, 'idType' => $provisionType], ['id' => 'ASC']);
            $this->SumInscription = null !== $firstProvision ? $firstProvision->getAmount() : 0;
            $this->sumRembInte    = $this->echeanciers->getRepaidInterests(['id_lender' => $wallet->getId()]);

            try {
                $this->nextRemb = $this->echeanciers->getNextRepaymentAmountInDateRange(
                    $wallet->getId(),
                    (new \DateTime('first day of next month'))->format('Y-m-d 00:00:00'),
                    (new \DateTime('last day of next month'))->format('Y-m-d 23:59:59')
                );
            } catch (\Exception $exception) {
                $logger->error('Could not get next repayment amount (id_client = ' . $this->client->getIdClient() . ')', [
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'id_wallet' => $wallet->getId()
                ]);
                $this->nextRemb = 0;
            }

            $this->sumRembMontant = $this->echeanciers->getRepaidAmount(['id_lender' => $wallet->getId()]);
            $this->avgPreteur     = $bids->getAvgPreteur($wallet->getId(), 'amount', implode(', ', [Bids::STATUS_ACCEPTED, Bids::STATUS_REJECTED]));
            $this->sumBidsEncours = $bids->sumBidsEncours($wallet->getId());
            $this->lBids          = $bids->select('id_wallet = ' . $wallet->getId() . ' AND status = ' . Bids::STATUS_PENDING, 'added DESC');
            $this->NbBids         = count($this->lBids);

            /** @var AttachmentManager $attachmentManager */
            $attachmentManager     = $this->get('unilend.service.attachment_manager');
            $this->attachmentTypes = $attachmentManager->getAllTypesForLender();
            $this->attachments     = $wallet->getIdClient()->getAttachments();

            $this->exemptionYears         = [];
            $lenderTaxExemptionRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:LenderTaxExemption');
            foreach ($lenderTaxExemptionRepository->findBy(['idLender' => $wallet], ['year' => 'DESC']) as $taxExemption) {
                $this->exemptionYears[] = $taxExemption->getYear();
            }

            $this->solde        = $wallet->getAvailableBalance();
            $this->soldeRetrait = $lenderOperationsManager->getTotalWithdrawalAmount($wallet);

            $start                  = new \DateTime('First day of january this year');
            $end                    = new \DateTime('NOW');
            $this->lenderOperations = $lenderOperationsManager->getLenderOperations($wallet, $start, $end, null, LenderOperationsManager::ALL_TYPES);
            $this->transfers        = $entityManager->getRepository('UnilendCoreBusinessBundle:Transfer')->findTransferByClient($wallet->getIdClient());

            $this->clientStatusMessage = $this->getMessageAboutClientStatus();
            $this->firstValidation     = $entityManager
                ->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory')
                ->findOneBy(
                    ['idClient' => $this->client, 'idStatus' => ClientsStatus::STATUS_VALIDATED],
                    ['added' => 'ASC', 'id' => 'ASC']
                );
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
        $this->nationalites = $this->loadData('nationalites_v2');
        $this->settings     = $this->loadData('settings');
        $this->lNatio       = $this->nationalites->select('', 'ordre ASC');

        /** @var EntityManager $entityManager */
        $entityManager                = $this->get('doctrine.orm.entity_manager');
        $lenderTaxExemptionRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:LenderTaxExemption');

        $this->countries = $entityManager->getRepository('UnilendCoreBusinessBundle:Pays')->findBy([], ['ordre' => 'ASC']);

        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = $this->get('logger');

        /** @var \Unilend\Bundle\TranslationBundle\Service\TranslationManager $translationManager */
        $translationManager       = $this->get('unilend.service.translation_manager');
        $this->completude_wording = $translationManager->getAllTranslationsForSection('lender-completeness');

        $this->settings->get("Liste deroulante conseil externe de l'entreprise", 'type');
        $this->conseil_externe = json_decode($this->settings->value, true);
        $this->exemptionYears  = [];

        if (
            isset($this->params[0])
            && false !== filter_var($this->params[0], FILTER_VALIDATE_INT)
            && null !== $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->params[0], WalletType::LENDER)
        ) {
            $this->wallet              = $wallet;
            $this->client              = $wallet->getIdClient();
            $this->validatedAddress    = null;
            $this->lastModifiedAddress = null;
            $this->samePostalAddress   = true;

            try {
                if ($this->client->isNaturalPerson()) {
                    $this->lastModifiedAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')
                        ->findLastModifiedNotArchivedAddressByType($this->client, AddressType::TYPE_MAIN_ADDRESS);
                    $this->samePostalAddress   = null === $this->client->getIdPostalAddress();
                } else {
                    /** @var Companies companyEntity */
                    $this->companyEntity       = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $this->client]);
                    $this->lastModifiedAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')
                        ->findLastModifiedNotArchivedAddressByType($this->companyEntity, AddressType::TYPE_MAIN_ADDRESS);
                    $this->samePostalAddress   = null === $this->companyEntity->getIdPostalAddress();
                }
            } catch (\Exception $exception) {
                $logger->error('An exception occurred while getting lender address. Message: ' . $exception->getMessage(), [
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'id_client' => $this->client->getIdClient()
                ]);
            }

            if ($this->client->isNaturalPerson()) {
                foreach ($lenderTaxExemptionRepository->findBy(['idLender' => $wallet], ['year' => 'DESC']) as $taxExemption) {
                    $this->exemptionYears[] = $taxExemption->getYear();
                }
                $this->nextYear = date('Y') + 1;

                $this->settings->get("Liste deroulante origine des fonds", 'type');
                $this->origine_fonds                 = $this->settings->value;
                $this->origine_fonds                 = explode(';', $this->origine_fonds);
                $this->taxExemptionUserHistoryAction = $this->getTaxExemptionHistoryActionDetails($this->users_history->getTaxExemptionHistoryAction($this->client->getIdClient()));
            } else {
                $this->settings->get("Liste deroulante origine des fonds societe", 'type');
                $this->origine_fonds = $this->settings->value;
                $this->origine_fonds = explode(';', $this->origine_fonds);
            }

            $this->taxationCountryHistory = $this->getTaxationHistory($wallet->getId());
            $this->clientStatusMessage    = $this->getMessageAboutClientStatus();
            $this->dataHistory            = $this->get(ClientDataHistoryManager::class)->getDataHistory($this->client);
            $this->statusHistory          = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory')->findBy(
                ['idClient' => $this->client, 'idStatus' => [ClientsStatus::STATUS_CREATION, ClientsStatus::STATUS_TO_BE_CHECKED, ClientsStatus::STATUS_COMPLETENESS, ClientsStatus::STATUS_COMPLETENESS_REPLY, ClientsStatus::STATUS_MODIFICATION, ClientsStatus::STATUS_VALIDATED, ClientsStatus::STATUS_SUSPENDED, ClientsStatus::STATUS_DISABLED, ClientsStatus::STATUS_CLOSED_LENDER_REQUEST, ClientsStatus::STATUS_CLOSED_BY_UNILEND, ClientsStatus::STATUS_CLOSED_DEFINITELY]], // All but "Complétude (Relance)" which is only a "technical" status
                ['added' => 'DESC', 'id' => 'DESC']
            );

            /** @var AttachmentManager $attachmentManager */
            $attachmentManager = $this->get('unilend.service.attachment_manager');
            $attachments       = $this->client->getAttachments();
            $attachmentTypes   = $attachmentManager->getAllTypesForLender();
            $this->setAttachments($attachments, $attachmentTypes);

            $this->currentBankAccount = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getLastModifiedBankAccount($this->client);
            $this->treeRepository     = $entityManager->getRepository('UnilendCoreBusinessBundle:Tree');
            $this->legalDocuments     = $entityManager->getRepository('UnilendCoreBusinessBundle:AcceptationsLegalDocs')->findBy(['idClient' => $this->client->getIdClient()]);

            $identityDocument = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findOneClientAttachmentByType($this->client, AttachmentType::CNI_PASSPORTE);
            if ($identityDocument && $identityDocument->getGreenpointAttachment()) {
                $this->lenderIdentityMRZData = $identityDocument->getGreenpointAttachment()->getGreenpointAttachmentDetail();
            }

            $hostIdentityDocument = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findOneClientAttachmentByType($this->client, AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT);
            if ($hostIdentityDocument && $hostIdentityDocument->getGreenpointAttachment()) {
                $this->hostIdentityMRZData = $hostIdentityDocument->getGreenpointAttachment()->getGreenpointAttachmentDetail();
            }

            $this->setClientVigilanceStatusData();

            try {
                $this->duplicateAccounts = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->getDuplicatesByName($this->client->getNom(), $this->client->getPrenom(), $this->client->getNaissance());
            } catch (\Doctrine\DBAL\DBALException $exception) {
                $this->duplicateAccounts = [];
                $logger->error('An exception occurred while trying to look for a duplicated client accounts. id_client: ' . $this->client->getIdClient() . ' Exception message: ' . $exception->getMessage(), [
                    'id_client' => $this->client->getIdClient(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine()
                ]);
            }

            if (isset($_POST['send_completude'])) {
                $this->sendCompletenessRequest($this->client);

                /** @var ClientStatusManager $clientStatusManager */
                $clientStatusManager = $this->get('unilend.service.client_status_manager');
                $clientStatusManager->addClientStatus(
                    $this->client,
                    $this->userEntity->getIdUser(),
                    ClientsStatus::STATUS_COMPLETENESS,
                    $_SESSION['content_email_completude'][$this->client->getIdClient()]
                );

                unset($_SESSION['content_email_completude'][$this->client->getIdClient()]);
                $_SESSION['email_completude_confirm'] = true;

                header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $this->client->getIdClient());
                die;
            }

            if (isset($_POST['send_edit_preteur'])) {
                if ($this->client->isNaturalPerson()) {
                    $birthCountry = $this->request->request->getInt('id_pays_naissance');
                    $type         = (false !== $birthCountry && $birthCountry == \Unilend\Bundle\CoreBusinessBundle\Entity\NationalitesV2::NATIONALITY_FRENCH) ? Clients::TYPE_PERSON : Clients::TYPE_PERSON_FOREIGNER;
                    $email        = $this->request->request->filter('email', FILTER_VALIDATE_EMAIL);
                    $birthday     = $this->request->request->filter('naissance', FILTER_SANITIZE_STRING);

                    if (false === $this->checkEmail($email, $this->client)) {
                        header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $this->client->getIdClient());
                        die;
                    }

                    if (null === $birthday) {
                        $_SESSION['freeow']['title']   = 'Erreur de données clients';
                        $_SESSION['freeow']['message'] = 'Le format de la date de naissance n\'est pas correct';
                        header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $this->client->getIdClient());
                        die;
                    }

                    if (false !== $birthday && 1 === preg_match("#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#", $birthday)) {
                        $birthday = \DateTime::createFromFormat('d/m/Y', $birthday)->setTime(0, 0, 0);
                    }

                    $entityManager->beginTransaction();

                    try {
                        $this->client
                            ->setCivilite($_POST['civilite'])
                            ->setNom($this->ficelle->majNom($_POST['nom-famille']))
                            ->setNomUsage($this->ficelle->majNom($_POST['nom-usage']))
                            ->setPrenom($this->ficelle->majNom($_POST['prenom']))
                            ->setEmail($email)
                            ->setTelephone(str_replace(' ', '', $_POST['phone']))
                            ->setMobile(str_replace(' ', '', $_POST['mobile']))
                            ->setVilleNaissance($_POST['com-naissance'])
                            ->setInseeBirth($_POST['insee_birth'])
                            ->setNaissance($birthday)
                            ->setIdPaysNaissance($_POST['id_pays_naissance'])
                            ->setIdNationalite($_POST['nationalite'])
                            ->setIdLangue('fr')
                            ->setType($type);

                        /** @var ClientAuditer $clientAuditer */
                        $clientAuditer = $this->get(ClientAuditer::class);
                        $clientAuditer->logChanges($this->client, $this->userEntity, true);

                        $entityManager->flush($this->client);

                        $this->saveUserHistory($this->client->getIdClient());

                        $entityManager->commit();
                    } catch (\Exception $exception) {
                        $entityManager->rollback();
                        $logger->error('An exception occurred while updating client in the backoffice. Message: ' . $exception->getMessage(), [
                            'file'      => $exception->getFile(),
                            'line'      => $exception->getLine(),
                            'class'     => __CLASS__,
                            'function'  => __FUNCTION__,
                            'id_client' => $this->client->getIdClient()
                        ]);
                    }
                } else {
                    $email = trim($_POST['email_e']);
                    if (false === $this->checkEmail($email, $this->client)) {
                        header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $this->client->getIdClient());
                        die;
                    }

                    $entityManager->beginTransaction();

                    try {
                        $this->companyEntity
                            ->setName($_POST['raison-sociale'])
                            ->setForme($_POST['form-juridique'])
                            ->setCapital(str_replace(' ', '', $_POST['capital-sociale']))
                            ->setSiren($_POST['siren'])
                            ->setSiret($_POST['siret'])
                            ->setPhone(str_replace(' ', '', $_POST['phone-societe']))
                            ->setTribunalCom($_POST['tribunal_com'])
                            ->setStatusClient($_POST['enterprise']);

                        if (in_array($_POST['enterprise'], [Companies::CLIENT_STATUS_DELEGATION_OF_POWER, Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT])) {
                            $this->companyEntity
                                ->setCiviliteDirigeant($_POST['civilite2_e'])
                                ->setNomDirigeant($this->ficelle->majNom($_POST['nom2_e']))
                                ->setPrenomDirigeant($this->ficelle->majNom($_POST['prenom2_e']))
                                ->setFonctionDirigeant($_POST['fonction2_e'])
                                ->setEmailDirigeant($_POST['email2_e'])
                                ->setPhoneDirigeant(str_replace(' ', '', $_POST['phone2_e']));

                            if ($_POST['enterprise'] == Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT) {
                                $this->companyEntity
                                    ->setStatusConseilExterneEntreprise($_POST['status_conseil_externe_entreprise'])
                                    ->setPreciserConseilExterneEntreprise($_POST['preciser_conseil_externe_entreprise']);
                            }
                        } else {
                            $this->companyEntity
                                ->setCiviliteDirigeant(null)
                                ->setNomDirigeant(null)
                                ->setPrenomDirigeant(null)
                                ->setFonctionDirigeant(null)
                                ->setEmailDirigeant(null)
                                ->setPhoneDirigeant(null);
                        }

                        $this->client
                            ->setCivilite($_POST['civilite_e'])
                            ->setNom($this->ficelle->majNom($_POST['nom_e']))
                            ->setPrenom($this->ficelle->majNom($_POST['prenom_e']))
                            ->setFonction($_POST['fonction_e'])
                            ->setEmail($email)
                            ->setMobile(str_replace(' ', '', $_POST['phone_e']))
                            ->setIdLangue('fr')
                            ->setType(Clients::TYPE_LEGAL_ENTITY);

                        /** @var ClientAuditer $clientAuditer */
                        $clientAuditer = $this->get(ClientAuditer::class);
                        $clientAuditer->logChanges($this->client, $this->userEntity, true);

                        $entityManager->flush([$this->companyEntity, $this->client]);

                        $this->saveUserHistory($this->client->getIdClient());

                        $entityManager->commit();
                    } catch (\Exception $exception) {
                        $entityManager->rollback();
                        $logger->error('An exception occurred while updating client in the backoffice. Message: ' . $exception->getMessage(), [
                            'file'      => $exception->getFile(),
                            'line'      => $exception->getLine(),
                            'class'     => __CLASS__,
                            'function'  => __FUNCTION__,
                            'id_client' => $this->client->getIdClient()
                        ]);
                    }
                }

                header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $this->client->getIdClient());
                die;
            }

            if (isset($_POST['send_tax_exemption'])) {
                if (isset($_POST['tax_exemption']) && is_array($_POST['tax_exemption'])) {
                    foreach ($_POST['tax_exemption'] as $exemptionYear => $exemptionValue) {
                        if (false === in_array($exemptionYear, $this->exemptionYears)) {
                            try {
                                $lenderTaxExemptionEntity = new LenderTaxExemption();
                                $lenderTaxExemptionEntity
                                    ->setIdLender($wallet)
                                    ->setIsoCountry('FR')
                                    ->setYear($exemptionYear)
                                    ->setIdUser($this->userEntity);
                                $entityManager->persist($lenderTaxExemptionEntity);
                                $entityManager->flush($lenderTaxExemptionEntity);

                                $taxExemptionHistory[] = ['year' => $exemptionYear, 'action' => 'adding'];
                            } catch (\Exception $exception) {
                                $logger->error(
                                    'Could not save tax exemption request for lender: ' . $wallet->getId() . ' Error: ' . $exception->getMessage(),
                                    ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'id_client' => $wallet->getIdClient()->getIdClient()]
                                );
                            }
                        }
                    }
                }

                if (in_array($this->nextYear, $this->exemptionYears) && false === isset($_POST['tax_exemption'][$this->nextYear])) {
                    $taxExemptionToRemove = $lenderTaxExemptionRepository->findOneBy(['idLender' => $wallet, 'year' => $this->nextYear, 'isoCountry' => 'FR']);
                    if (null !== $taxExemptionToRemove) {
                        try {
                            $entityManager->remove($taxExemptionToRemove);
                            $entityManager->flush();
                            $taxExemptionHistory[] = ['year' => $this->nextYear, 'action' => 'deletion'];
                        } catch (\Exception $exception) {
                            $logger->error(
                                'Could not remove the tax exemption entry (year: ' . $this->nextYear . ') for lender : ' . $wallet->getId() . ' Error: ' . $exception->getMessage(),
                                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                            );
                        }
                    }
                }

                if (false === empty($taxExemptionHistory)) {
                    $this->users_history->histo(
                        UsersHistory::FORM_ID_LENDER,
                        UsersHistory::FORM_NAME_TAX_EXEMPTION,
                        $this->userEntity->getIdUser(),
                        serialize(['id_client' => $this->client->getIdClient(), 'modifications' => $taxExemptionHistory])
                    );
                }
            }
        }
    }

    public function _valider_preteur()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $_SESSION['freeow']['title'] = 'Validation client';
        $idClient                    = $this->request->request->getInt('id_client_to_validate');
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $client        = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($idClient);

        if (null !== $client) {
            $addressId     = null;
            $bankAccountId = null;

            try {
                if ($client->isNaturalPerson()) {
                    $lastModifiedAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')
                        ->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
                } else {
                    $company             = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
                    $lastModifiedAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')
                        ->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
                }
                $addressId = (null !== $lastModifiedAddress) ? $lastModifiedAddress->getId() : null;
            } catch (\Exception $exception) {
                $this->get('logger')->error('An exception occurred while getting lender address. Message: ' . $exception->getMessage(), [
                    'id_client' => $client->getIdClient(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine()
                ]);
            }

            try {
                $currentBankAccount = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getLastModifiedBankAccount($client);
                $bankAccountId      = (null !== $currentBankAccount) ? $currentBankAccount->getId() : null;
            } catch (\Doctrine\ORM\NonUniqueResultException $exception) {
                $this->get('logger')->error('An exception occurred while getting lender last modified bank account. Message: ' . $exception->getMessage(), [
                    'id_client' => $client->getIdClient(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine()
                ]);
            }

            try {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LenderValidationManager $lenderValidationManager */
                $lenderValidationManager = $this->get('unilend.service.lender_validation_manager');
                $duplicates              = [];
                $clientIsValidated       = $lenderValidationManager->validateClient($client, $this->userEntity, $duplicates, $bankAccountId, $addressId);

                if (false === $clientIsValidated) {
                    $message = 'Erreur, le client n\'a pas pu être validé.';
                } else {
                    $_SESSION['compte_valide'] = true;
                    $message = 'Le client est validé.';
                }
            } catch (\Exception $exception) {
                $message = 'Erreur, le client n\'a pas pu être validé.';
                $this->get('logger')->error('An exception occurred during lender validation process. Lender could not be validated. Message: ' . $exception->getMessage(), [
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'id_client' => $client->getIdClient()
                ]);
            }
            $_SESSION['freeow']['message'] = $message;

            header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $client->getIdClient());
            die;
        }
    }

    /**
     * @param int $clientId
     */
    private function saveUserHistory(int $clientId)
    {
        /** @var \users_history $userHistory */
        $userHistory = $this->loadData('users_history');
        $serialize = serialize(['id_client' => $clientId, 'post' => $_POST, 'files' => $_FILES]);
        $userHistory->histo(UsersHistory::FORM_ID_LENDER, 'modif info preteur', $this->userEntity->getIdUser(), $serialize);
    }

    /**
     * @param $lenderId
     *
     * @return array
     */
    private function getTaxationHistory($lenderId)
    {
        /** @var \lenders_imposition_history $lendersImpositionHistory */
        $lendersImpositionHistory = $this->loadData('lenders_imposition_history');
        try {
            $aResult = $lendersImpositionHistory->getTaxationHistory($lenderId);
        } catch (\Exception $exception) {
            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->error('Could not get lender taxation history (id_lender = ' . $lenderId . ') Exception message : ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_lender' => $lenderId]);
            $aResult = ['error' => 'Impossible de charger l\'historique de changement d\'adresse fiscale'];
        }

        return $aResult;
    }

    public function _activation()
    {
        $this->clients   = $this->loadData('clients');
        $this->companies = $this->loadData('companies');

        $statusOrderedByPriority = [
            ClientsStatus::STATUS_TO_BE_CHECKED,
            ClientsStatus::STATUS_MODIFICATION,
            ClientsStatus::STATUS_COMPLETENESS_REPLY,
            ClientsStatus::STATUS_COMPLETENESS,
            ClientsStatus::STATUS_COMPLETENESS_REMINDER,
            ClientsStatus::STATUS_SUSPENDED
        ];

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\ClientsRepository $clientsRepository */
        $clientsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $this->lPreteurs   = $clientsRepository->getClientsToValidate($statusOrderedByPriority);

        if (false === empty($this->lPreteurs)) {
            $greenpointKycRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:GreenpointKyc');

            /** @var array aGreenPointStatus */
            $this->aGreenPointStatus = [];

            foreach ($this->lPreteurs as $lender) {
                $clientKyc = $greenpointKycRepository->findOneBy(['idClient' => $lender['id_client']]);
                if (null !== $clientKyc) {
                    $this->aGreenPointStatus[$lender['id_client']] = $clientKyc->getStatus();
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

        /** @var EntityManager $entityManager */
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

        $this->clients = $this->loadData('clients');
        $this->clients->get($this->params[0], 'id_client');

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $timeCreate    = \DateTime::createFromFormat('Y-m-d H:i:s', $this->clients->added);

        if (false === empty($this->clients->id_client_status_history)) {
            $statusHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory')->find($this->clients->id_client_status_history);
            $timeCreate    = $statusHistory->getAdded();
        }

        $dateFormatter = new \IntlDateFormatter($this->getParameter('locale'), \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);
        $keywords      = [
            'firstName'        => $this->clients->prenom,
            'modificationDate' => $dateFormatter->format($timeCreate),
            'content'          => $_SESSION['content_email_completude'][$this->clients->id_client],
            'uploadLink'       => $this->furl . '/profile/documents',
            'lenderPattern'    => $this->clients->getLenderPattern($this->clients->id_client),
            'frontUrl'         => $this->furl,
            'staticUrl'        => $this->surl,
            'year'             => date('Y')
        ];

        $tabVars = [];
        foreach ($keywords as $key => $value) {
            $tabVars['[EMV DYN]' . $key . '[EMV /DYN]'] = $value;
        }

        $mailTemplateRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates');
        $mailTemplate           = $mailTemplateRepository->findOneBy([
            'type'   => 'completude',
            'locale' => $this->getParameter('locale'),
            'status' => MailTemplates::STATUS_ACTIVE,
            'part'   => MailTemplates::PART_TYPE_CONTENT
        ]);

        $content = $mailTemplate->getContent();

        if ($mailTemplate->getIdHeader()) {
            $content = $mailTemplate->getIdHeader()->getContent() . $content;
        }

        if ($mailTemplate->getIdFooter()) {
            $content = $content . $mailTemplate->getIdFooter()->getContent();
        }

        echo strtr($content, $tabVars);
    }

    public function _offres_de_bienvenue()
    {
        /** @var \NumberFormatter $currencyFormatter */
        $this->numberFormatter = $this->get('number_formatter');
        /** @var \NumberFormatter $currencyFormatter */
        $this->currencyFormatter = $this->get('currency_formatter');
        /** @var EntityManager $entityManager */
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
            $clientIds  = empty($_POST['id_client']) ? null : filter_var($_POST['id_client'], FILTER_SANITIZE_STRING);

            if (null !== $clientIds && 0 !== preg_match('/^[0-9]+(\s*,\s*[0-9]+\s*)*$/', $clientIds)) {
                $this->clientsWithoutWelcomeOffer = $clientData->getClientsWithNoWelcomeOffer($clientIds);
                $_SESSION['forms']['rattrapage_offre_bienvenue']['id_client'] = $_POST['id_client'];

                if (empty($this->clientsWithoutWelcomeOffer)) {
                    $this->errorMessage = 'Il n\'y a aucun utilisateur pour le moment.';
                }
            } else {
                $this->errorMessage = 'Recherche non aboutie. Indiquez la liste des ID clients <br />Il faut séparer les ids par des virgules';
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
        /** @var EntityManager $entityManager */
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
                $_SESSION['pay_out_welcome_offer']['errors'][] = 'Offre de bienvenue non crédité';
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
        /** @var EntityManager $entityManager */
        $entityManager      = $this->get('doctrine.orm.entity_manager');
        $this->client       = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->params[0]);
        $welcomeOfferType   = $welcomeOfferManager->getWelcomeOfferTypeForClient($this->client);
        $this->welcomeOffer = $entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenues')->findOneBy(['type' => $welcomeOfferType]);

        if (false === $this->client->isNaturalPerson()) {
            $this->company = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $this->client]);
        }
    }

    public function _deactivate_welcome_offer()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var EntityManager $entityManager */
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
            isset($this->params[0])
            && filter_var($this->params[0], FILTER_VALIDATE_INT)
            && $this->clients->get($this->params[0], 'id_client')
        ) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $this->wallet  = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->clients->id_client, WalletType::LENDER);
            $this->client  = $this->wallet->getIdClient();

            if (isset($_POST['send_dates'])) {
                $_SESSION['FilterMails']['StartDate'] = $_POST['debut'];
                $_SESSION['FilterMails']['EndDate']   = $_POST['fin'];

                header('Location:  ' . $this->lurl . '/preteurs/email_history/' . $this->params[0]);
                die;
            }

            $this->aClientsNotifications = $clientNotifications->getNotifs($this->clients->id_client);
            $this->aNotificationPeriode  = ClientsGestionNotifications::ALL_PERIOD;

            $this->aInfosNotifications['vos-offres-et-vos-projets']['title']           = 'Offres et Projets';
            $this->aInfosNotifications['vos-offres-et-vos-projets']['notifications']   = [
                ClientsGestionTypeNotif::TYPE_NEW_PROJECT                   => [
                    'title'           => 'Annonce des nouveaux projets',
                    'available_types' => [
                        ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_DAILY,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_WEEKLY,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ],
                ClientsGestionTypeNotif::TYPE_BID_PLACED                    => [
                    'title'           => 'Offres réalisées',
                    'available_types' => [
                        ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_DAILY,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ],
                ClientsGestionTypeNotif::TYPE_BID_REJECTED                  => [
                    'title'           => 'Offres refusées',
                    'available_types' => [
                        ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_DAILY,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ],
                ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED                 => [
                    'title'           => 'Offres acceptées',
                    'available_types' => [
                        ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_DAILY,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_WEEKLY,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_MONTHLY,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ],
                ClientsGestionTypeNotif::TYPE_PROJECT_PROBLEM               => [
                    'title'           => 'Problème sur un projet',
                    'available_types' => [
                        ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ],
                ClientsGestionTypeNotif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID => [
                    'title'           => 'Autolend : offre réalisée ou rejetée',
                    'available_types' => [
                        ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ]
            ];
            $this->aInfosNotifications['vos-remboursements']['title']                  = 'Offres et Projets';
            $this->aInfosNotifications['vos-remboursements']['notifications']          = [
                ClientsGestionTypeNotif::TYPE_REPAYMENT => [
                    'title'           => 'Remboursement(s)',
                    'available_types' => [
                        ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_DAILY,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_WEEKLY,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_MONTHLY,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ]
            ];
            $this->aInfosNotifications['mouvements-sur-votre-compte']['title']         = 'Mouvements sur le compte';
            $this->aInfosNotifications['mouvements-sur-votre-compte']['notifications'] = [
                ClientsGestionTypeNotif::TYPE_BANK_TRANSFER_CREDIT => [
                    'title'           => 'Alimentation de votre compte par virement',
                    'available_types' => [
                        ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ],
                ClientsGestionTypeNotif::TYPE_CREDIT_CARD_CREDIT   => [
                    'title'           => 'Alimentation de votre compte par carte bancaire',
                    'available_types' => [
                        ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_NO_MAIL
                    ]
                ],
                ClientsGestionTypeNotif::TYPE_DEBIT                => [
                    'title'           => 'retrait',
                    'available_types' => [
                        ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE,
                        ClientsGestionNotifications::TYPE_NOTIFICATION_NO_MAIL
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
            $this->clientStatusMessage   = $this->getMessageAboutClientStatus();
        }
    }

    public function _portefeuille()
    {
        $this->clients     = $this->loadData('clients');
        $this->loans       = $this->loadData('loans');
        $this->projects    = $this->loadData('projects');
        $this->echeanciers = $this->loadData('echeanciers');
        /** @var underlying_contract contract */
        $this->contract = $this->loadData('underlying_contract');
        /** @var \Symfony\Component\Translation\TranslatorInterface translator */
        $this->translator = $this->get('translator');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LoanManager loanManager */
        $this->loanManager = $this->get('unilend.service.loan_manager');
        /** @var \loans loan */
        $this->loan = $this->loadData('loans');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LenderManager lenderManager */
        $lenderManager = $this->get('unilend.service.lender_manager');
        /** @var EntityManager $entityManager */
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
            $this->client          = $wallet->getIdClient();
            $this->lSumLoans       = $this->loans->getSumLoansByProject($wallet->getId());
            $this->aProjectsInDebt = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->getProjectsInDebt();

            /** @var LenderStatistic $lastIRR */
            $this->IRR = $lenderStatisticsRepository->findOneBy(['idWallet' => $wallet, 'typeStat' => LenderStatistic::TYPE_STAT_IRR], ['added' => 'DESC']);

            $statusOk                = [ProjectsStatus::STATUS_ONLINE, ProjectsStatus::STATUS_FUNDED, ProjectsStatus::STATUS_REPAYMENT, ProjectsStatus::STATUS_REPAID];
            $statusKo                = [ProjectsStatus::STATUS_LOSS, ProjectsStatus::STATUS_CANCELLED];
            $this->projectsPublished = $this->projects->countProjectsSinceLendersubscription($this->clients->id_client, array_merge($statusOk, $statusKo));
            $this->problProjects     = $this->projects->countProjectsByStatusAndLender($wallet->getId(), $statusKo);
            $this->totalProjects     = $this->loans->getProjectsCount($wallet->getId());

            $this->clientStatusMessage = $this->getMessageAboutClientStatus();

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AutoBidSettingsManager $oAutoBidSettingsManager */
            $oAutoBidSettingsManager = $this->get('unilend.service.autobid_settings_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ClientManager $oClientManager */
            $oClientManager = $this->get('unilend.service.client_manager');

            $this->bAutoBidOn     = $oAutoBidSettingsManager->isOn($wallet->getIdClient());
            $this->aSettingsDates = $oAutoBidSettingsManager->getLastDateOnOff($this->clients->id_client);
            if (0 < count($this->aSettingsDates)) {
                try {
                    $this->sValidationDate = $oAutoBidSettingsManager->getValidationDate($wallet->getIdClient())->format('d/m/Y');
                } catch (\Exception $exception) {
                    $this->sValidationDate = '';
                    $this->get('logger')->error(
                        'Could not get the last autobid settings validation date for the client: ' . $wallet->getIdClient() . '. Error: ' . $exception->getMessage(),
                        ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                    );
                }
            }
            $this->fAverageRateUnilend = round($this->projects->getAvgRate(), 1);
            $this->bIsBetaTester       = $oClientManager->isBetaTester($wallet->getIdClient());

            $this->settings->get('date-premier-projet-tunnel-de-taux', 'type');
            $startingDate           = $this->settings->value;
            $this->aAutoBidSettings = [];
            $autobidRepository      = $entityManager->getRepository('UnilendCoreBusinessBundle:Autobid');
            $aAutoBidSettings       = $autobidRepository->getSettings($wallet, null, null, [Autobid::STATUS_ACTIVE, Autobid::STATUS_INACTIVE]);
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

    /**
     * @param Clients $client
     */
    private function sendEmailClosedAccount(Clients $client)
    {
        $keywords = [
            'firstName' => $client->getPrenom()
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('confirmation-fermeture-compte-preteur', $keywords);

        try {
            $message->setTo($client->getEmail());
            $mailer = $this->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->get('logger')->warning(
                'Could not send email: confirmation-fermeture-compte-preteur - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * @param Clients $client
     */
    private function sendCompletenessRequest(Clients $client): void
    {
        $wallet        = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        $timeCreate    = empty($this->statusHistory[0]) ? $client->getAdded() : $this->statusHistory[0]->getAdded();
        $dateFormatter = new \IntlDateFormatter($this->getParameter('locale'), \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);
        $keywords      = [
            'firstName'        => $client->getPrenom(),
            'modificationDate' => $dateFormatter->format($timeCreate),
            'content'          => $_SESSION['content_email_completude'][$client->getIdClient()],
            'uploadLink'       => $this->furl . '/profile/documents',
            'lenderPattern'    => $wallet->getWireTransferPattern()
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('completude', $keywords);

        try {
            $message->setTo($client->getEmail());
            $mailer = $this->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->get('logger')->warning('Could not send email "completude" - Exception: ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'id_client'        => $client->getIdClient(),
                'class'            => __CLASS__,
                'function'         => __FUNCTION__
            ]);
        }
    }

    /**
     * @return string
     */
    private function getMessageAboutClientStatus(): string
    {
        $clientStatusHistory = $this->client->getIdClientStatusHistory();

        if (null === $clientStatusHistory || empty($clientStatusHistory->getId())) {
            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->warning('Lender client has no status ' . $this->client->getIdClient(), ['id_client' => $this->client->getIdClient()]);

            return '';
        }

        switch ($clientStatusHistory->getIdStatus()->getId()) {
            case ClientsStatus::STATUS_CREATION:
                $clientStatusMessage = '<div class="attention">Inscription non terminée </div>';
                break;
            case ClientsStatus::STATUS_TO_BE_CHECKED:
                $clientStatusMessage = '<div class="attention">Compte non validé - créé le ' . $this->client->getAdded()->format('d/m/Y') . '</div>';
                break;
            case ClientsStatus::STATUS_COMPLETENESS:
            case ClientsStatus::STATUS_COMPLETENESS_REMINDER:
            case ClientsStatus::STATUS_COMPLETENESS_REPLY:
                $clientStatusMessage = '<div class="attention" style="background-color:#F9B137">Compte en complétude - créé le ' . $this->client->getAdded()->format('d/m/Y') . ' </div>';
                break;
            case ClientsStatus::STATUS_MODIFICATION:
                $clientStatusMessage = '<div class="attention" style="background-color:#F2F258">Compte en modification - créé le ' . $this->client->getAdded()->format('d/m/Y') . '</div>';
                break;
            case ClientsStatus::STATUS_VALIDATED:
                $clientStatusMessage = '';
                break;
            case ClientsStatus::STATUS_SUSPENDED:
                $clientStatusMessage = '<div class="attention">Compte suspendu</div>';
                break;
            case ClientsStatus::STATUS_DISABLED:
                $clientStatusMessage = '<div class="attention">Compte désactivé</div>';
                break;
            case ClientsStatus::STATUS_CLOSED_LENDER_REQUEST:
                $clientStatusMessage = '<div class="attention">Compte clôturé à la demande du prêteur</div>';
                break;
            case ClientsStatus::STATUS_CLOSED_BY_UNILEND:
                $clientStatusMessage = '<div class="attention">Compte clôturé par Unilend</div>';
                break;
            case ClientsStatus::STATUS_CLOSED_DEFINITELY:
                $clientStatusMessage = '<div class="attention">Compte définitivement fermé</div>';
                break;
            default:
                $clientStatusMessage = '';
                /** @var \Psr\Log\LoggerInterface $logger */
                $logger = $this->get('logger');
                $logger->warning('Unknown client status "' . $clientStatusHistory->getIdStatus()->getId() . '"', ['id_client' => $this->client->getIdClient()]);
                break;
        }

        return $clientStatusMessage;
    }

    private function setClientVigilanceStatusData()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $this->vigilanceStatusHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory')->findBy(['client' => $this->client], ['id' => 'DESC']);

        if (empty($this->vigilanceStatusHistory)) {
            $this->vigilanceStatus = [
                'status'            => VigilanceRule::VIGILANCE_STATUS_LOW,
                'message'           => 'Vigilance standard',
                'checkOnValidation' => false
            ];
            $this->userRepository  = $entityManager->getRepository('UnilendCoreBusinessBundle:Users');
            return;
        }

        $this->clientAtypicalOperations = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAtypicalOperation')->findBy(['client' => $this->client], ['added' => 'DESC']);

        switch ($this->vigilanceStatusHistory[0]->getVigilanceStatus()) {
            case VigilanceRule::VIGILANCE_STATUS_LOW:
                $this->vigilanceStatus = [
                    'status'            => VigilanceRule::VIGILANCE_STATUS_LOW,
                    'message'           => 'Vigilance standard. Dernière MAJ le ' . $this->vigilanceStatusHistory[0]->getAdded()->format('d/m/Y H\hi'),
                    'checkOnValidation' => false
                ];
                break;
            case VigilanceRule::VIGILANCE_STATUS_MEDIUM:
                $this->vigilanceStatus = [
                    'status'            => VigilanceRule::VIGILANCE_STATUS_MEDIUM,
                    'message'           => 'Vigilance intermédiaire. Dernière MAJ le ' . $this->vigilanceStatusHistory[0]->getAdded()->format('d/m/Y H\hi'),
                    'checkOnValidation' => true
                ];
                break;
            case VigilanceRule::VIGILANCE_STATUS_HIGH:
                $this->vigilanceStatus = [
                    'status'            => VigilanceRule::VIGILANCE_STATUS_HIGH,
                    'message'           => 'Vigilance Renforcée. Dernière MAJ le ' . $this->vigilanceStatusHistory[0]->getAdded()->format('d/m/Y H\hi'),
                    'checkOnValidation' => true
                ];
                break;
            case VigilanceRule::VIGILANCE_STATUS_REFUSE:
                $this->vigilanceStatus = [
                    'status'            => VigilanceRule::VIGILANCE_STATUS_REFUSE,
                    'message'           => 'Vigilance Refus. Dernière MAJ le ' . $this->vigilanceStatusHistory[0]->getAdded()->format('d/m/Y H\hi'),
                    'checkOnValidation' => false
                ];
                break;
            default:
                trigger_error('Unknown vigilance status :' . $this->vigilanceStatusHistory[0]->getVigilanceStatus(), E_USER_NOTICE);
        }

        /** @var \Symfony\Component\Translation\Translator translator */
        $this->translator                   = $this->get('translator');
        $this->userRepository               = $entityManager->getRepository('UnilendCoreBusinessBundle:Users');
        $this->clientVigilanceStatusHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory');
    }

    public function _saveBetaTesterSetting()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var EntityManager $entityManager */
        $entityManager    = $this->get('doctrine.orm.entity_manager');
        $clientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');

        if (
            isset($this->params[0], $this->params[1])
            && is_numeric($this->params[0])
            && in_array($this->params[1], ['on', 'off'])
            && ($client = $clientRepository->find($this->params[0]))
        ) {
            $value = ('on' == $this->params[1]) ? \client_settings::BETA_TESTER_ON : \client_settings::BETA_TESTER_OFF;

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ClientSettingsManager $clientSettingsManager */
            $clientSettingsManager = $this->get('unilend.service.client_settings_manager');
            $clientSettingsManager->saveClientSetting($client, ClientSettingType::TYPE_BETA_TESTER, $value);

            header('Location: ' . $this->lurl . '/preteurs/portefeuille/' . $client->getIdClient());
            die;
        }
    }

    public function _status()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $clientId = isset($this->params[0]) ? filter_var($this->params[0], FILTER_SANITIZE_NUMBER_INT) : null;
        if (empty($clientId)) {
            header('Location: ' . $this->lurl . '/preteurs/search');
            die;
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var Clients $client */
        $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($clientId);

        if (null === $client) {
            header('Location: ' . $this->lurl . '/preteurs/search');
            die;
        }

        $action = isset($this->params[1]) ? filter_var($this->params[1], FILTER_SANITIZE_STRING) : null;
        if (empty($action)) {
            header('Location: ' . $this->lurl . '/preteurs/search');
            die;
        }

        switch ($action) {
            case 'close_lender':
                /** @var ClientStatusManager $clientStatusManager */
                $clientStatusManager = $this->get('unilend.service.client_status_manager');
                $clientStatusManager->addClientStatus($client, $_SESSION['user']['id_user'], ClientsStatus::STATUS_CLOSED_LENDER_REQUEST);

                $this->sendEmailClosedAccount($client);
                break;
            case 'close_unilend':
                /** @var ClientStatusManager $clientStatusManager */
                $clientStatusManager = $this->get('unilend.service.client_status_manager');
                $clientStatusManager->addClientStatus($client, $_SESSION['user']['id_user'], ClientsStatus::STATUS_CLOSED_BY_UNILEND);
                break;
            case 'online':
                if (false === $this->switchOnline($client)) {
                    header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $client->getIdClient());
                    die;
                }
                break;
            default:
                /** @var \Psr\Log\LoggerInterface $logger */
                $logger = $this->get('logger');
                $logger->error(
                    'Unknown lender status modification action: ' . $action,
                    ['id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                );

                $_SESSION['freeow']['title']   = 'Statut prêteur';
                $_SESSION['freeow']['message'] = 'Le statut du prêteur n’a pas été modifié en raison d’une erreur technique';

                header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $client->getIdClient());
                die;
        }

        $this->users_history->histo(
            UsersHistory::FORM_ID_LENDER_STATUS,
            UsersHistory::FORM_NAME_LENDER_STATUS,
            $this->userEntity->getIdUser(),
            serialize(['id_client' => $client->getIdClient(), 'status' => $client->getIdClientStatusHistory()->getIdStatus()->getId()])
        );

        $_SESSION['freeow']['title']   = 'Statut prêteur';
        $_SESSION['freeow']['message'] = 'Le statut du prêteur a été modifié avec succès';

        header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $client->getIdClient());
        die;
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    private function switchOnline(Clients $client): bool
    {
        /** @var EntityManager $entityManager */
        $entityManager    = $this->get('doctrine.orm.entity_manager');
        $clientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $duplicates       = $clientRepository->findGrantedLoginAccountsByEmail($client->getEmail());

        if (false === empty($duplicates)) {
            $_SESSION['freeow']['title']   = 'Statut prêteur';
            $_SESSION['freeow']['message'] = 'Le statut du prêteur n’a pas pu être modifié car un compte en ligne existe déjà avec cette adresse email';

            return false;
        }

        $lastTwoStatus = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory')->findLastTwoClientStatus($client->getIdClient());

        if (empty($lastTwoStatus[1])) {
            $status = ClientsStatus::STATUS_TO_BE_CHECKED;
        } else {
            $status = $lastTwoStatus[1]->getIdStatus()->getId();
        }

        /** @var ClientStatusManager $clientStatusManager */
        $clientStatusManager = $this->get('unilend.service.client_status_manager');
        $clientStatusManager->addClientStatus($client, $this->userEntity->getIdUser(), $status, 'Compte remis en ligne par Unilend');

        return true;
    }

    public function _bids()
    {
        /** @var EntityManager $entityManager */
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
            $this->wallet              = $wallet;
            $this->client              = $wallet->getIdClient();
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
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (
            isset($this->params[0])
            && is_numeric($this->params[0])
            && null !== $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->params[0], WalletType::LENDER)
        ) {
            try {
                $lenderBids = $bids->getBidsByLenderAndDates($wallet);
                $header     = ['ID projet', 'ID bid', 'Client', 'Date bid', 'Statut bid', 'Montant', 'Taux'];
                $filename   = 'bids_client_' . $wallet->getIdClient()->getIdClient() . '.xlsx';

                $writer = WriterFactory::create(Type::XLSX);
                $writer
                    ->openToBrowser($filename)
                    ->addRow($header)
                    ->addRows($lenderBids)
                    ->close();

                die;
            } catch (\Exception $exception) {
                $this->get('logger')->error('Un  exception occurred during export of lender bids for client ' . $this->params[0] . '. Message: ' . $exception->getMessage(), [
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                    'id_client' => $this->params[0]
                ]);

                echo 'Une erreur est survenue. ';
            }
        }
    }

    /**
     * @param string  $email
     * @param Clients $client
     *
     * @return bool
     */
    private function checkEmail(string $email, Clients $client): bool
    {
        if ($email === $client->getEmail()) {
            return true;
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $emailRegex    = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Regex validation email'])->getValue();

        if (1 !== preg_match($emailRegex, $email)) {
            $_SESSION['error_email_exist'] = 'Impossible de modifier l‘adresse email. Le format est incorrect';
            return false;
        }

        $duplicates = $entityManager
            ->getRepository('UnilendCoreBusinessBundle:Clients')
            ->findGrantedLoginAccountsByEmail($email);

        if (count($duplicates) > 0) {
            $_SESSION['error_email_exist'] = 'Impossible de modifier l‘adresse email. Cette adresse est déjà utilisée par un autre compte.';
            return false;
        }

        return true;
    }

    /**
     * @param array $history
     *
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

    public function _operations_export()
    {
        if (
            isset($_POST['dateStart']) && 1 === preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $_POST['dateStart'])
            && isset($_POST['dateEnd']) && 1 === preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $_POST['dateEnd'])
        ) {
            $this->autoFireView = false;
            $this->hideDecoration();

            /** @var EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var LenderOperationsManager $lenderOperationsManager */
            $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');
            $wallet                  = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->params[0], WalletType::LENDER);
            $start                   = \DateTime::createFromFormat('m/d/Y', $_POST['dateStart']);
            $end                     = \DateTime::createFromFormat('m/d/Y', $_POST['dateEnd']);
            $fileName                = 'operations_' . date('Y-m-d_H:i:s') . '.xlsx';
            $writer                  = $lenderOperationsManager->getOperationsExcelFile($wallet, $start, $end, null, LenderOperationsManager::ALL_TYPES, $fileName);
            $writer->close();
        }
    }

    public function _notifications()
    {
        $this->autoFireHeader = true;
        $this->autoFireHead   = true;
        $this->autoFireFooter = true;

        if (isset($_POST['searchProject'])) {
            /** @var EntityManager $entityManager */
            $entityManager                 = $this->get('doctrine.orm.entity_manager');
            $projectRepository             = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
            $this->projectStatusRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus');
            $this->projectList             = [];

            if (isset($_POST['projectId']) && filter_var($_POST['projectId'], FILTER_VALIDATE_INT)) {
                $this->projectList = $projectRepository->findBy(['idProject' => $_POST['projectId']]);
            } elseif (false === empty($_POST['projectTitle'])) {
                $this->projectList = $projectRepository->search(null, null, filter_var($_POST['projectTitle'], FILTER_SANITIZE_STRING));
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

        /** @var EntityManager $entityManager */
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
