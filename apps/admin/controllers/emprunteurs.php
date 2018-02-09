<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AttachmentType, Clients, Companies, CompanyStatus, Zones
};

class emprunteursController extends bootstrap
{
    /** @var \clients_adresses */
    public $clients_adresses;

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
        $this->clients_adresses = $this->loadData('clients_adresses');
        $this->companies        = $this->loadData('companies');
        $this->companies_bilans = $this->loadData('companies_bilans');

        if ($this->clients->telephone != '') {
            $this->clients->telephone = trim(chunk_split($this->clients->telephone, 2, ' '));
        }

        if (isset($_POST['form_search_emprunteur'])) {
            $this->lClients = $this->clients->searchEmprunteurs('AND', $_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['societe'], $_POST['siren']);

            $_SESSION['freeow']['title']   = 'Recherche d\'un client';
            $_SESSION['freeow']['message'] = 'La recherche est termin&eacute;e !';
        }
    }

    public function _edit()
    {
        $this->clients          = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');
        $this->companies        = $this->loadData('companies');
        $this->companies_bilans = $this->loadData('companies_bilans');
        $this->projects         = $this->loadData('projects');
        $this->projects_status  = $this->loadData('projects_status');
        $this->clients_mandats  = $this->loadData('clients_mandats');
        $this->projects_pouvoir = $this->loadData('projects_pouvoir');
        $this->settings         = $this->loadData('settings');
        /** @var \company_sector $companySector */
        $companySector = $this->loadData('company_sector');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $this->currencyFormatter = $this->get('currency_formatter');

        /** @var \Symfony\Component\Translation\TranslatorInterface translator */
        $this->translator = $this->get('translator');
        $this->sectors    = $companySector->select();

        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'id_client') && $this->clients->isBorrower()) {
            $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->params[0]);
            $this->clients_adresses->get($this->clients->id_client, 'id_client');
            $this->companies->get($this->clients->id_client, 'id_client_owner');
            $walletType     = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => \Unilend\Bundle\CoreBusinessBundle\Entity\WalletType::BORROWER]);
            $borrowerWallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')
                ->findOneBy(['idClient' => $client->getIdClient(), 'idType' => $walletType]);
            if ($borrowerWallet) {
                $this->availableBalance = $borrowerWallet->getAvailableBalance();
            } else {
                $this->availableBalance = 0;
            }
            $this->lprojects = $this->projects->select('id_company = "' . $this->companies->id_company . '"');

            if ($this->clients->telephone != '') {
                $this->clients->telephone = trim(chunk_split($this->clients->telephone, 2, ' '));
            }

            $this->bankAccount          = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($client);
            $this->bankAccountDocuments = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findBy([
                'idClient' => $client,
                'idType'   => AttachmentType::RIB
            ]);

            if (isset($_POST['form_edit_emprunteur'])) {
                $emailRegex = $entityManager
                    ->getRepository('UnilendCoreBusinessBundle:Settings')
                    ->findOneBy(['type' => 'Regex validation email'])
                    ->getValue();

                $email = trim($_POST['email']);
                if (1 !== preg_match($emailRegex, $email)) {
                    $_SESSION['error_email_exist'] = 'Le format de l\'adresse email est invalide';
                } elseif ($email !== $this->clients->email) {
                    $clientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
                    $duplicates       = $clientRepository->findBy(['email' => $email, 'status' => Clients::STATUS_ONLINE]);

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

                if ($this->companies->status_adresse_correspondance == 1) {
                    $this->companies->adresse1 = $_POST['adresse'];
                    $this->companies->city     = $_POST['ville'];
                    $this->companies->zip      = $_POST['cp'];
                }

                $this->companies->name   = $_POST['societe'];
                $this->companies->sector = isset($_POST['sector']) ? $_POST['sector'] : $this->companies->sector;
                $this->companies->update();

                $this->clients_adresses->adresse1 = $_POST['adresse'];
                $this->clients_adresses->ville    = $_POST['ville'];
                $this->clients_adresses->cp       = $_POST['cp'];
                $this->clients_adresses->update();

                $serialize = serialize(['id_client' => $this->clients->id_client, 'post' => $_POST]);
                $this->users_history->histo(6, 'edit emprunteur', $_SESSION['user']['id_user'], $serialize);

                $_SESSION['freeow']['title']   = 'Mise à jour emprunteur';
                $_SESSION['freeow']['message'] = 'L\'emprunteur a été mis à jour';

                header('Location: ' . $this->lurl . '/emprunteurs/edit/' . $this->clients->id_client);
                exit;
            }
            $this->companyEntity = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->companies->id_company);

            if (false === empty($_POST['problematic_status']) && $_POST['problematic_status'] != $this->companyEntity->getIdStatus()->getId()) {
                $this->updateCompanyStatus($this->companyEntity);
            }

            $this->aMoneyOrders = $this->clients_mandats->getMoneyOrderHistory($this->companies->id_company);
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BorrowerOperationsManager $borrowerOperationsManager */
            $borrowerOperationsManager = $this->get('unilend.service.borrower_operations_manager');
            $start                     = new \DateTime('First day of january this year');
            $end                       = new \DateTime('NOW');
            $this->operations          = $borrowerOperationsManager->getBorrowerOperations($this->clients, $start, $end);
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\CompanyManager companyManager */
            $this->companyManager = $this->get('unilend.service.company_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus[] possibleCompanyStatus */
            $this->possibleCompanyStatus = $this->companyManager->getPossibleStatus($this->companyEntity);
            $this->companyStatusInBonis  = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyStatus')->findOneBy(['label' => CompanyStatus::STATUS_IN_BONIS]);
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
                case \Unilend\Bundle\CoreBusinessBundle\Entity\Factures::TYPE_COMMISSION_FUNDS:
                    $aProjectInvoices[$iKey]['url'] = $this->furl . '/pdf/facture_EF/' . $oClient->hash . '/' . $aInvoice['id_project'];
                    break;
                case \Unilend\Bundle\CoreBusinessBundle\Entity\Factures::TYPE_COMMISSION_REPAYMENT:
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
            $project       = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);

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
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BorrowerOperationsManager $borrowerOperationsManager */
            $borrowerOperationsManager = $this->get('unilend.service.borrower_operations_manager');

            $year     = filter_var($_POST['year'], FILTER_VALIDATE_INT);
            $idClient = filter_var($_POST['id_client'], FILTER_VALIDATE_INT);
            /** @var \clients $clientData */
            $clientData = $this->loadData('clients');
            $clientData->get($idClient);
            $start = new \DateTime();
            $start->setDate($year, 1, 1);
            $end = new \DateTime();
            $end->setDate($year, 12, 31);
            $this->operations = $borrowerOperationsManager->getBorrowerOperations($clientData, $start, $end);
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

        $user        = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);
        $changedOn   = isset($_POST['decision_date']) ? \DateTime::createFromFormat('d/m/Y', $_POST['decision_date']) : null;
        $receiver    = isset($_POST['receiver']) ? $_POST['receiver'] : null;
        $siteContent = isset($_POST['site_content']) ? $_POST['site_content'] : null;
        $mailContent = isset($_POST['mail_content']) ? $_POST['mail_content'] : null;
        $status      = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyStatus')->find($_POST['problematic_status']);
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\CompanyManager $companyManager */
        $companyManager = $this->get('unilend.service.company_manager');
        $companyManager->addCompanyStatus($company, $status, $user, $changedOn, $receiver, $siteContent, $mailContent);

        if (in_array($company->getIdStatus()->getLabel(), [CompanyStatus::STATUS_PRECAUTIONARY_PROCESS, CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION])) {
            $projectsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
            $companyProjects    = $projectsRepository->findFundedButNotRepaidProjectsByCompany($company);
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusNotificationSender $projectStatusNotificationSender */
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
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BulkCompanyCheckManager $bulkCompanyCheckManager */
        $bulkCompanyCheckManager = $this->get('unilend.service.eligibility.bulk_company_check_manager');

        if ($userManager->isGrantedRisk($this->userEntity)) {
            $success = '';
            $error   = '';
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile */
            $uploadedFile = $this->request->files->get('siren_list');

            if (false === empty($uploadedFile)) {
                $uploadDir = $bulkCompanyCheckManager->getEligibilityInputPendingDir();
                try {
                    $bulkCompanyCheckManager->uploadFile($uploadDir, $uploadedFile, $this->userEntity);
                    $success = 'Le fichier a été pris en compte. Une notification vous sera envoyé dès qu\'il sera traité';
                } catch (\Exception $exception) {
                    /** @var \Psr\Log\LoggerInterface $logger */
                    $logger = $this->get('logger');
                    $logger->error(
                        'Could not upload the file into ' . $uploadDir . ' Error: ' . $exception->getMessage(),
                        ['method', __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                    );
                    $error = 'Le fichier n\'a pas été pris en compte. Veuillez rééssayer ou contacter l\'équipe technique.';
                }
            }
            $this->render(null, ['success' => $success, 'error' => $error]);
        } else {
            header('Location: ' . $this->lurl);
            die;
        }
    }
}
