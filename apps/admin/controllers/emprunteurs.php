<?php

use Unilend\Entity\{AddressType, Attachment, AttachmentType, BankAccount, Companies, CompanyStatus, Pays, Wallet, WalletType, Zones};

class emprunteursController extends bootstrap
{

    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);

        $this->menu_admin = 'emprunteurs';
    }

    public function _default()
    {
        header('Location: ' . $this->lurl . '/dossiers');
        die;
    }

    public function _gestion()
    {
        $this->clients          = $this->loadData('clients');
        $this->companies        = $this->loadData('companies');
        $this->companies_bilans = $this->loadData('companies_bilans');

        if ($this->clients->telephone != '') {
            $this->clients->telephone = trim(chunk_split($this->clients->telephone, 2, ' '));
        }

        if (isset($_POST['form_search_emprunteur'])) {
            if (empty($_POST['nom']) && empty($_POST['email']) && empty($_POST['prenom']) && empty($_POST['societe']) && empty($_POST['siren'])) {
                $_SESSION['error_search_borrower'][] = 'Veuillez remplir au moins un champ';
            }

            $email = empty($_POST['email']) ? null : filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
            if (false === $email) {
                $_SESSION['error_search_borrower'][] = 'Format de l\'email est non valide';
            }

            $lastName = empty($_POST['nom']) ? null : filter_var($_POST['nom'], FILTER_SANITIZE_STRING);
            if (false === $lastName) {
                $_SESSION['error_search_borrower'][] = 'Le format du nom n\'est pas valide';
            }

            $firstName = empty($_POST['prenom']) ? null : filter_var($_POST['prenom'], FILTER_SANITIZE_STRING);
            if (false === $firstName) {
                $_SESSION['error_search_borrower'][] = 'Le format du prenom n\'est pas valide';
            }

            $companyName = empty($_POST['societe']) ? null : filter_var($_POST['societe'], FILTER_SANITIZE_STRING);
            if (false === $companyName) {
                $_SESSION['error_search_borrower'][] = 'Le format du nom de la societe n\'est pas valide';
            }

            $siren = empty($_POST['siren']) ? null : trim(filter_var($_POST['siren'], FILTER_SANITIZE_STRING));
            if (false === $siren) {
                $_SESSION['error_search_borrower'][] = 'Le format du siren n\'est pas valide';
            }

            if (false === empty($_SESSION['error_search_borrower'])) {
                header('Location: ' . $this->lurl . '/emprunteurs/gestion');
                die;
            }

            $this->lClients = $this->clients->searchEmprunteurs('AND', $lastName, $firstName, $email, $companyName, $siren);

            $_SESSION['freeow']['title']   = 'Recherche d\'un client';
            $_SESSION['freeow']['message'] = 'La recherche est termin&eacute;e !';
        }
    }

    public function _edit()
    {
        $this->clients          = $this->loadData('clients');
        $this->companies        = $this->loadData('companies');
        $this->companies_bilans = $this->loadData('companies_bilans');
        $this->projects         = $this->loadData('projects');
        $this->projects_status  = $this->loadData('projects_status');
        $this->clients_mandats  = $this->loadData('clients_mandats');
        $this->projects_pouvoir = $this->loadData('projects_pouvoir');
        $this->settings         = $this->loadData('settings');
        /** @var \company_sector $companySector */
        $companySector = $this->loadData('company_sector');

        /** @var \Symfony\Component\Translation\TranslatorInterface translator */
        $this->translator = $this->get('translator');
        $this->sectors    = $companySector->select();

        if (
            isset($this->params[0])
            && false !== filter_var($this->params[0], FILTER_VALIDATE_INT)
            && $this->clients->get($this->params[0], 'id_client')
            && $this->clients->isBorrower()
        ) {
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var NumberFormatter currencyFormatter */
            $this->currencyFormatter = $this->get('currency_formatter');
            /** @var \Unilend\Service\BorrowerManager $borrowerManager */
            $borrowerManager = $this->get('unilend.service.borrower_manager');

            $walletType       = $entityManager->getRepository(WalletType::class)->findOneBy(['label' => WalletType::BORROWER]);
            $borrowerWallet   = $entityManager->getRepository(Wallet::class)->findOneBy(['idClient' => $this->clients->id_client, 'idType' => $walletType]);

            if ($borrowerWallet) {
                $this->availableBalance = $borrowerWallet->getAvailableBalance();
                $this->restFunds        = $borrowerManager->getRestOfFundsToRelease($borrowerWallet);
            } else {
                $this->availableBalance = 0;
                $this->restFunds        = 0;
            }

            if ($this->clients->telephone != '') {
                $this->clients->telephone = trim(chunk_split($this->clients->telephone, 2, ' '));
            }

            $this->lprojects             = $this->projects->select('id_company = ' . $this->companies->id_company);
            $this->wireTransferOuts      = $entityManager->getRepository(Virements::class)->findBy(['idClient' => $this->clients->id_client]);
            $this->bankAccountRepository = $entityManager->getRepository(BankAccount::class);
            $this->companyRepository     = $entityManager->getRepository(Companies::class); // used in included template
            $this->clientEntity          = $borrowerWallet->getIdClient();
            $this->companyEntity         = $this->companyRepository->find($this->companies->id_company);
            $this->companyAddress        = $this->companyEntity->getIdAddress();
            $this->bankAccount           = $this->bankAccountRepository->getClientValidatedBankAccount($this->clientEntity);
            $this->bankAccountDocuments  = $entityManager->getRepository(Attachment::class)->findBy([
                'idClient' => $this->clientEntity,
                'idType'   => AttachmentType::RIB
            ]);

            if (isset($_POST['form_edit_emprunteur'])) {
                $emailRegex = $entityManager
                    ->getRepository(Settings::class)
                    ->findOneBy(['type' => 'Regex validation email'])
                    ->getValue();

                $email = trim($_POST['email']);
                if (false === empty($email) && 1 !== preg_match($emailRegex, $email)) {
                    $_SESSION['error_email_exist'] = 'Le format de l\'adresse email est invalide';
                } elseif (false === empty($email) && $email !== $this->clients->email) {
                    $clientRepository = $entityManager->getRepository(Clients::class);
                    $duplicates       = $clientRepository->findGrantedLoginAccountsByEmail($email);

                    if (false === empty($duplicates)) {
                        $_SESSION['error_email_exist'] = 'Cette adresse email est déjà utilisée par un autre compte';
                    }
                }

                if (empty($_SESSION['error_email_exist'])) {
                    $this->clients->email = $email;
                }

                $this->clients->nom       = $this->ficelle->majNom($_POST['nom']);
                $this->clients->prenom    = $this->ficelle->majNom($_POST['prenom']);
                $this->clients->telephone = str_replace([' ', '.', ','], '', $_POST['telephone']);
                $this->clients->update();

                $billingEmail = trim($_POST['email_facture']);
                if (1 !== preg_match($emailRegex, $billingEmail)) {
                    $_SESSION['error_email_exist'] = 'Le format de l\'adresse email de facturation est invalide';
                } else {
                    $this->companies->email_facture = $billingEmail;
                }

                $this->companies->name   = $_POST['societe'];
                $this->companies->sector = isset($_POST['sector']) ? $_POST['sector'] : $this->companies->sector;
                $this->companies->update();

                if (empty($_POST['adresse']) || empty($_POST['ville']) || empty($_POST['cp'])) {
                    $_SESSION['error_company_address'] = 'L\'adresse de l\'entreprise doit être complète pour être enregistrée.';

                    header('Location: ' . $this->lurl . '/emprunteurs/edit/' . $this->clients->id_client);
                    exit;
                }

                $this->get('unilend.service.address_manager')
                    ->saveCompanyAddress(
                        $_POST['adresse'],
                        $_POST['cp'],
                        $_POST['ville'],
                        Pays::COUNTRY_FRANCE,
                        $this->companyEntity,
                        AddressType::TYPE_MAIN_ADDRESS
                    );

                $serialize = serialize(['id_client' => $this->clients->id_client, 'post' => $_POST]);
                $this->users_history->histo(6, 'edit emprunteur', $_SESSION['user']['id_user'], $serialize);

                $_SESSION['freeow']['title']   = 'Mise à jour emprunteur';
                $_SESSION['freeow']['message'] = 'L\'emprunteur a été mis à jour';

                header('Location: ' . $this->lurl . '/emprunteurs/edit/' . $this->clients->id_client);
                exit;
            }

            if (false === empty($_POST['problematic_status']) && $_POST['problematic_status'] != $this->companyEntity->getIdStatus()->getId()) {
                $this->updateCompanyStatus($this->companyEntity);
            }

            $this->aMoneyOrders = $this->clients_mandats->getMoneyOrderHistory($this->companies->id_company);
            /** @var \Unilend\Service\BorrowerOperationsManager $borrowerOperationsManager */
            $borrowerOperationsManager = $this->get('unilend.service.borrower_operations_manager');
            $start                     = new \DateTime('First day of january this year');
            $end                       = new \DateTime('NOW');
            $this->operations          = $borrowerOperationsManager->getBorrowerOperations($borrowerWallet, $start, $end);
            /** @var \Unilend\Service\CompanyManager companyManager */
            $this->companyManager        = $this->get('unilend.service.company_manager');
            $companyStatusRepository     = $entityManager->getRepository(CompanyStatus::class);
            $this->possibleCompanyStatus = $this->companyManager->getPossibleStatus($this->companyEntity);
            $this->companyStatusInBonis  = $companyStatusRepository->findOneBy(['label' => CompanyStatus::STATUS_IN_BONIS]);

            /** @var \Unilend\Service\BackOfficeUserManager $backOfficeUserManager */
            $backOfficeUserManager    = $this->get('unilend.service.back_office_user_manager');
            $this->hasRepaymentAccess = $backOfficeUserManager->isGrantedZone($this->userEntity, Zones::ZONE_LABEL_REPAYMENT);
        } else {
            header('Location: ' . $this->lurl . '/emprunteurs/gestion');
            exit;
        }
    }

    public function _factures()
    {
        $this->hideDecoration();

        $oProject  = $this->loadData('projects');
        $oCompany  = $this->loadData('companies');
        $oClient   = $this->loadData('clients');
        $oInvoices = $this->loadData('factures');

        $oProject->get($this->params[0]);
        $oCompany->get($oProject->id_company);
        $oClient->get($oCompany->id_client_owner);

        $aProjectInvoices = $oInvoices->select('id_project = ' . $oProject->id_project, 'date DESC');

        foreach ($aProjectInvoices as $iKey => $aInvoice) {
            switch ($aInvoice['type_commission']) {
                case \Unilend\Entity\Factures::TYPE_COMMISSION_FUNDS:
                    $aProjectInvoices[$iKey]['url'] = $this->furl . '/pdf/facture_EF/' . $oClient->hash . '/' . $aInvoice['id_project'];
                    break;
                case \Unilend\Entity\Factures::TYPE_COMMISSION_REPAYMENT:
                    $aProjectInvoices[$iKey]['url'] = $this->furl . '/pdf/facture_ER/' . $oClient->hash . '/' . $aInvoice['id_project'] . '/' . $aInvoice['ordre'];
                    break;
                default :
                    trigger_error('Commission type for invoice unknown', E_USER_NOTICE);
                    break;
            }
        }
        $this->aProjectInvoices = $aProjectInvoices;
    }

    public function _link_ligthbox()
    {
        $this->hideDecoration();
        $this->link = '';
        if (false === empty($this->params[0]) && false === empty($this->params[1])) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $projectId     = filter_var($this->params[1], FILTER_VALIDATE_INT);
            $project       = $entityManager->getRepository(Projects::class)->find($projectId);

            if ($project) {
                $client = $project->getIdCompany()->getIdClientOwner();

                switch ($this->params[0]) {
                    case 'pouvoir' :
                        $this->link = $this->furl . '/pdf/pouvoir/' . $client->getHash() . '/' . $projectId;
                        break;
                    case 'mandat' :
                        $this->link = $this->furl . '/pdf/mandat/' . $client->getHash() . '/' . $projectId;
                        break;
                    default :
                        $this->link = '';
                        break;
                }
            }
        }
    }

    public function _loadBorrowerOperationAjax()
    {
        $this->hideDecoration();
        if (isset($_POST['year'], $_POST['id_client'])) {
            $this->currencyFormatter = $this->get('currency_formatter');
            /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
            $this->translator = $this->get('translator');
            /** @var \Unilend\Service\BorrowerOperationsManager $borrowerOperationsManager */
            $borrowerOperationsManager = $this->get('unilend.service.borrower_operations_manager');
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');

            $year     = filter_var($_POST['year'], FILTER_VALIDATE_INT);
            $idClient = filter_var($_POST['id_client'], FILTER_VALIDATE_INT);
            $borrowerWallet = $entityManager->getRepository(Wallet::class)->getWalletByType($idClient, WalletType::BORROWER);
            $start = new \DateTime();
            $start->setDate($year, 1, 1);
            $end = new \DateTime();
            $end->setDate($year, 12, 31);
            $this->operations = $borrowerOperationsManager->getBorrowerOperations($borrowerWallet, $start, $end);
        }
        $this->setView('../emprunteurs/operations');
    }

    /**
     * @param Companies $company
     */
    private function updateCompanyStatus(Companies $company)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $user        = $entityManager->getRepository(Users::class)->find($_SESSION['user']['id_user']);
        $changedOn   = isset($_POST['decision_date']) ? \DateTime::createFromFormat('d/m/Y', $_POST['decision_date']) : null;
        $receiver    = isset($_POST['receiver']) ? $_POST['receiver'] : null;
        $siteContent = isset($_POST['site_content']) ? $_POST['site_content'] : null;
        $mailContent = isset($_POST['mail_content']) ? $_POST['mail_content'] : null;
        $status      = $entityManager->getRepository(CompanyStatus::class)->find($_POST['problematic_status']);
        /** @var \Unilend\Service\CompanyManager $companyManager */
        $companyManager = $this->get('unilend.service.company_manager');
        $companyManager->addCompanyStatus($company, $status, $user, $changedOn, $receiver, $siteContent, $mailContent);

        if (in_array($company->getIdStatus()->getLabel(), [CompanyStatus::STATUS_PRECAUTIONARY_PROCESS, CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION])) {
            $projectsRepository = $entityManager->getRepository(Projects::class);
            $companyProjects    = $projectsRepository->findFundedButNotRepaidProjectsByCompany($company);
            /** @var \Unilend\Service\ProjectStatusNotificationSender $projectStatusNotificationSender */
            $projectStatusNotificationSender = $this->get('unilend.service.project_status_notification_sender');
            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->get('logger');

            foreach ($companyProjects as $project) {
                try {
                    $projectStatusNotificationSender->sendCollectiveProceedingStatusNotificationsToLenders($project);
                } catch (\Exception $exception) {
                    $logger->warning(
                        'Collective proceeding email was not sent to lenders. Error : ' . $exception->getMessage(),
                        ['id_project' => $project->getIdProject(), 'method' => __METHOD__]
                    );
                }
            }
        }

        header('Location: ' . $this->lurl . '/emprunteurs/edit/' . $company->getIdClientOwner()->getIdClient());
        die;
    }

    public function _test_eligibilite()
    {
        /** @var \Unilend\Service\BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');
        /** @var \Unilend\Service\BulkCompanyCheckManager $bulkCompanyCheckManager */
        $bulkCompanyCheckManager = $this->get('unilend.service.eligibility.bulk_company_check_manager');

        if ($userManager->isGrantedRisk($this->userEntity)) {
            $result              = $this->uploadSirenFile($bulkCompanyCheckManager->getEligibilityInputPendingDir());
            $result['pageTitle'] = $this->get('translator')->trans('upload-siren-file-page_company-eligibility-checker');

            $this->render('emprunteurs/upload_siren_file.html.twig', $result);
        } else {
            header('Location: ' . $this->lurl);
            die;
        }
    }

    public function _donnees_externes()
    {
        /** @var \Unilend\Service\BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');
        /** @var \Unilend\Service\BulkCompanyCheckManager $bulkCompanyCheckManager */
        $bulkCompanyCheckManager = $this->get('unilend.service.eligibility.bulk_company_check_manager');

        if ($userManager->isGrantedRisk($this->userEntity)) {
            $result              = $this->uploadSirenFile($bulkCompanyCheckManager->getCompanyDataInputPendingDir());
            $result['pageTitle'] = $this->get('translator')->trans('upload-siren-file-page_company-external-data');


            $this->render('emprunteurs/upload_siren_file.html.twig', $result);
        } else {
            header('Location: ' . $this->lurl);
            die;
        }
    }

    /**
     * @param string $uploadDir
     *
     * @return string[]
     */
    private function uploadSirenFile(string $uploadDir): array
    {
        /** @var \Unilend\Service\BulkCompanyCheckManager $bulkCompanyCheckManager */
        $bulkCompanyCheckManager = $this->get('unilend.service.eligibility.bulk_company_check_manager');

        $success = '';
        $error   = '';
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile */
        $uploadedFile = $this->request->files->get('siren_list');

        if (false === empty($uploadedFile)) {
            try {
                $bulkCompanyCheckManager->uploadFile($uploadDir, $uploadedFile, $this->userEntity);
                $success = 'Le fichier a été pris en compte. Une notification vous sera envoyée dès qu\'il sera traité.';
            } catch (\Exception $exception) {
                /** @var \Psr\Log\LoggerInterface $logger */
                $logger = $this->get('logger');
                $logger->error(
                    'Could not upload the file into ' . $uploadDir . ' Error: ' . $exception->getMessage(), [
                        'method',
                        __METHOD__,
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine()
                    ]
                );
                $error = 'Le fichier n\'a pas été pris en compte. Veuillez rééssayer ou contacter l\'équipe technique.';
            }
        }

        return ['success' => $success, 'error' => $error];
    }
}
