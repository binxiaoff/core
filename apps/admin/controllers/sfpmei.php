<?php

use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    BankAccount, Bids, ClientsStatus, LenderStatistic, Loans, OperationType, ProjectsStatus, Receptions, VigilanceRule, Wallet, WalletType, Zones
};
use Unilend\Bundle\CoreBusinessBundle\Service\LenderOperationsManager;

class sfpmeiController extends bootstrap
{
    /** @var Wallet */
    protected $wallet;

    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_SFPMEI);

        $this->menu_admin = 'sfpmei';
        $this->pagination = 25;
    }

    /**
     * Homepage
     */
    public function _default()
    {

    }

    /**
     * Lender search
     */
    public function _preteurs()
    {
        if (false === empty($_POST)) {
            if (empty($_POST['id']) && empty($_POST['email']) && empty($_POST['lastname']) && empty($_POST['company'])) {
                $_SESSION['error_search'][] = 'Veuillez remplir au moins un champ';
            }

            $clientId = empty($_POST['id']) ? null : filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
            if (false === $clientId) {
                $_SESSION['error_search'][] = 'L\'ID du client doit être un nombre';
            }

            $email = empty($_POST['email']) ? null : filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
            if (false === $email) {
                $_SESSION['error_search'][] = 'Le format de l\'email n\'est pas valide';
            }

            $lastName = empty($_POST['lastname']) ? null : filter_var($_POST['lastname'], FILTER_SANITIZE_STRING);
            if (false === $lastName) {
                $_SESSION['error_search'][] = 'Le format du nom n\'est pas valide';
            }

            $companyName = empty($_POST['company']) ? null : filter_var($_POST['company'], FILTER_SANITIZE_STRING);
            if (false === $companyName) {
                $_SESSION['error_search'][] = 'Le format de la raison sociale n\'est pas valide';
            }

            if (false === empty($_SESSION['error_search'])) {
                header('Location: ' . $this->lurl . '/sfpmei/preteurs');
                die;
            }

            /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\ClientsRepository $clientRepository */
            $clientRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients');
            $this->lenders    = $clientRepository->findLenders($clientId, $email, $lastName, null, $companyName, null, true);

            if (false === empty($this->lenders) && 1 === count($this->lenders)) {
                header('Location: ' . $this->lurl . '/sfpmei/preteur/' . $this->lenders[0]['id_client']);
                die;
            }
        }
    }

    /**
     * Borrower search
     */
    public function _emprunteurs()
    {
        if (false === empty($_POST)) {
            if (empty($_POST['siren']) && empty($_POST['company']) && empty($_POST['lastname']) && empty($_POST['email'])) {
                $_SESSION['error_search'][] = 'Veuillez remplir au moins un champ';
            }

            $siren = empty($_POST['siren']) ? '' : filter_var(str_replace(' ', '', $_POST['siren']), FILTER_SANITIZE_STRING);
            if (false === $siren) {
                $_SESSION['error_search'][] = 'Le format du SIREN n\'est pas valide';
            }

            $companyName = empty($_POST['company']) ? '' : filter_var($_POST['company'], FILTER_SANITIZE_STRING);
            if (false === $companyName) {
                $_SESSION['error_search'][] = 'Le format de la raison sociale n\'est pas valide';
            }

            $lastName = empty($_POST['lastname']) ? '' : filter_var($_POST['lastname'], FILTER_SANITIZE_STRING);
            if (false === $lastName) {
                $_SESSION['error_search'][] = 'Le format du nom n\'est pas valide';
            }

            $email = empty($_POST['email']) ? '' : filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
            if (false === $email) {
                $_SESSION['error_search'][] = 'Le format de l\'email n\'est pas valide';
            }

            if (false === empty($_SESSION['error_search'])) {
                header('Location: ' . $this->lurl . '/sfpmei/emprunteurs');
                die;
            }

            /** @var \clients $clients */
            $clients         = $this->get('unilend.service.entity_manager')->getRepository('clients');
            $this->borrowers = $clients->searchEmprunteurs('AND', $lastName, '', $email, $companyName, $siren);

            if (false === empty($this->borrowers) && 1 === count($this->borrowers)) {
                header('Location: ' . $this->lurl . '/sfpmei/emprunteur/' . $this->borrowers[0]['id_client']);
                die;
            }

            foreach ($this->borrowers as $index => $borrower) {
                $this->borrowers[$index]['total_amount'] = $clients->totalmontantEmprunt($borrower['id_client']);
            }
        }
    }

    /**
     * Projects search
     */
    public function _projets()
    {
        if (false === empty($_POST)) {
            if (empty($_POST['id']) && empty($_POST['siren']) && empty($_POST['company'])) {
                $_SESSION['error_search'][] = 'Veuillez remplir au moins un champ';
            }

            $projectId = empty($_POST['id']) ? '' : filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
            if (false === $projectId) {
                $_SESSION['error_search'][] = 'L\'ID du projet doit être un nombre';
            }

            $siren = empty($_POST['siren']) ? '' : filter_var(str_replace(' ', '', $_POST['siren']), FILTER_SANITIZE_STRING);
            if (false === $siren) {
                $_SESSION['error_search'][] = 'Le format du SIREN n\'est pas valide';
            }

            $companyName = empty($_POST['company']) ? '' : filter_var($_POST['company'], FILTER_SANITIZE_STRING);
            if (false === $companyName) {
                $_SESSION['error_search'][] = 'Le format de la raison sociale n\'est pas valide';
            }

            if (false === empty($_SESSION['error_search'])) {
                header('Location: ' . $this->lurl . '/sfpmei/projets');
                die;
            }

            /** @var \projects $projects */
            $projects       = $this->get('unilend.service.entity_manager')->getRepository('projects');
            $this->projects = $projects->searchDossiers('', '', '', '', '', '', $siren, $projectId, $companyName);

            array_shift($this->projects);

            if (false === empty($this->projects) && 1 === count($this->projects)) {
                header('Location: ' . $this->lurl . '/sfpmei/projet/' . $this->projects[0]['id_project']);
                die;
            }
        }
    }

    /**
     * Lender profile
     */
    public function _preteur()
    {
        $this->bids            = $this->loadData('bids');
        $this->clients         = $this->loadData('clients');
        $this->clients_mandats = $this->loadData('clients_mandats');
        $this->echeanciers     = $this->loadData('echeanciers');
        $this->loans           = $this->loadData('loans');
        $this->projects        = $this->loadData('projects');
        $this->contract        = $this->loadData('underlying_contract');
        /** @var TranslatorInterface translator */
        $this->translator = $this->get('translator');
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (
            empty($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
            || false === $this->clients->get($this->params[0])
        ) {
            header('Location: ' . $this->lurl . '/sfpmei/preteurs');
            die;
        }

        $action       = isset($this->params[1]) ? $this->params[1] : 'default';
        $this->wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->params[0], WalletType::LENDER);

        switch ($action) {
            case 'mouvements':
                $this->hideDecoration();
                $this->setView('preteur/mouvements');

                $this->startDate = new \DateTime('first day of january this year');
                $this->endDate   = new \DateTime('now');

                if (isset($this->params[2]) && 'ajax' === $this->params[2]) {
                    $this->setView('preteur/mouvements_table');
                    $this->startDate = \DateTime::createFromFormat('d/m/Y', $_POST['start']);
                    $this->endDate   = \DateTime::createFromFormat('d/m/Y', $_POST['end']);
                }

                $this->lenderOperations = $this->get('unilend.service.lender_operations_manager')->getLenderOperations($this->wallet, $this->startDate, $this->endDate, null, LenderOperationsManager::ALL_TYPES);
                break;
            case 'portefeuille':
                $this->hideDecoration();
                $this->setView('preteur/portefeuille');

                $statusOK = [ProjectsStatus::EN_FUNDING, ProjectsStatus::FUNDE, ProjectsStatus::FUNDING_KO, ProjectsStatus::PRET_REFUSE, ProjectsStatus::REMBOURSEMENT, ProjectsStatus::REMBOURSE, ProjectsStatus::REMBOURSEMENT_ANTICIPE];
                $statusKO = [ProjectsStatus::PROBLEME, ProjectsStatus::LOSS];

                $this->lenderIRR                = $entityManager->getRepository('UnilendCoreBusinessBundle:LenderStatistic')->findOneBy(['idWallet' => $this->wallet, 'typeStat' => LenderStatistic::TYPE_STAT_IRR], ['added' => 'DESC']);
                $this->projectsCount            = $this->loans->getProjectsCount($this->wallet->getId());
                $this->problematicProjectsCount = $this->projects->countProjectsByStatusAndLender($this->wallet->getId(), $statusKO);
                $this->publishedProjectsCount   = $this->projects->countProjectsSinceLendersubscription($this->clients->id_client, array_merge($statusOK, $statusKO));
                $this->runningBids              = $this->bids->select('id_lender_account = ' . $this->wallet->getId() . ' AND status = ' . Bids::STATUS_PENDING, 'added DESC');
                $this->hasTransferredLoans      = $this->get('unilend.service.lender_manager')->hasTransferredLoans($this->wallet->getIdClient());
                $this->lenderLoans              = $this->loans->getSumLoansByProject($this->wallet->getId());
                $this->projectsInDebt           = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->getProjectsInDebt();
                break;
            case 'bids_csv':
                $this->hideDecoration();
                $this->autoFireView = false;

                PHPExcel_Settings::setCacheStorageMethod(
                    PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
                    ['memoryCacheSize' => '2048MB', 'cacheTime' => 1200]
                );

                $document    = new PHPExcel();
                $activeSheet = $document->setActiveSheetIndex(0);
                $header      = ['ID projet', 'ID bid', 'Date bid', 'Statut bid', 'Montant', 'Taux'];
                $lenderBids  = $this->bids->getBidsByLenderAndDates($this->wallet);

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
                header('Content-Disposition: attachment;filename=bids_client_' . $this->wallet->getIdClient()->getIdClient() . '.csv');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Expires: 0');

                /** @var \PHPExcel_Writer_CSV $writer */
                $writer = PHPExcel_IOFactory::createWriter($document, 'CSV');
                $writer->setUseBOM(true);
                $writer->setDelimiter(';');
                $writer->save('php://output');
                break;
            default:
                $this->clients_adresses = $this->loadData('clients_adresses');
                $this->clients_adresses->get($this->clients->id_client, 'id_client');

                $this->statusHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory')->findBy(
                    ['idClient' => $this->clients->id_client],
                    ['added' => 'DESC', 'id' => 'DESC']
                );

                /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Operation $firstProvision */
                $provisionType    = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneByLabel(OperationType::LENDER_PROVISION);
                $firstProvision   = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->findOneBy(['idWalletCreditor' => $this->wallet, 'idType' => $provisionType], ['id' => 'ASC']);
                $paysV2Repository = $entityManager->getRepository('UnilendCoreBusinessBundle:PaysV2');

                $this->lenderStatusMessage    = $this->getLenderStatusMessage();
                $this->cipEnabled             = $this->get('unilend.service.cip_manager')->hasValidEvaluation($this->wallet->getIdClient());
                $this->birthCountry           = empty($this->clients->id_pays_naissance) ? '' : $paysV2Repository->find($this->clients->id_pays_naissance)->getFr();
                $lenderTaxExemptionRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:LenderTaxExemption');
                $this->exemptionYears         = [];
                foreach ($lenderTaxExemptionRepository->findBy(['idLender' => $this->wallet], ['year' => 'DESC']) as $taxExemption) {
                    $this->exemptionYears[] = $taxExemption->getYear();
                }
                $this->availableBalance               = $this->wallet->getAvailableBalance();
                $this->firstDepositAmount             = null === $firstProvision ? 0 : $firstProvision->getAmount();
                $this->totalDepositsAmount            = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->sumCreditOperationsByTypeAndYear($this->wallet, [OperationType::LENDER_PROVISION]);;
                $this->totalWithdrawsAmount           = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->sumDebitOperationsByTypeAndYear($this->wallet, [OperationType::LENDER_WITHDRAW]);
                $this->totalRepaymentsAmount          = $this->echeanciers->getRepaidAmount(['id_lender' => $this->wallet->getId()]);
                $this->totalGrowthInterestsAmount     = $this->echeanciers->getRepaidInterests(['id_lender' => $this->wallet->getId()]);
                $this->totalRepaymentsNextMonthAmount = $this->echeanciers->getNextRepaymentAmountInDateRange($this->wallet->getId(), (new \DateTime('first day of next month'))->format('Y-m-d 00:00:00'), (new \DateTime('last day of next month'))->format('Y-m-d 23:59:59'));
                $this->totalLoansAmount               = $this->loans->sumPrets($this->wallet->getId());
                $this->totalLoansCount                = $this->loans->counter('id_lender = ' . $this->wallet->getId() . ' AND status = ' . Loans::STATUS_ACCEPTED);
                $this->runningBids                    = $this->bids->select('id_lender_account = ' . $this->wallet->getId() . ' AND status = ' . Bids::STATUS_PENDING, 'added DESC');
                $this->totalRunningBidsAmount         = round(array_sum(array_column($this->runningBids, 'amount')) / 100);
                $this->totalRunningBidsCount          = count($this->runningBids);
                $this->averageBidAmount               = $this->bids->getAvgPreteur($this->wallet->getId(), 'amount', implode(', ', [Bids::STATUS_ACCEPTED, Bids::STATUS_REJECTED]));
                $this->averageLoanRate                = $this->loans->getAvgPrets($this->wallet->getId());
                $this->currentBankAccount             = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getLastModifiedBankAccount($this->clients->id_client);
                $this->isPhysicalPerson               = in_array($this->clients->type, [\Unilend\Bundle\CoreBusinessBundle\Entity\Clients::TYPE_PERSON, \Unilend\Bundle\CoreBusinessBundle\Entity\Clients::TYPE_PERSON_FOREIGNER]);
                $this->attachments                    = $this->wallet->getIdClient()->getAttachments();
                $this->attachmentTypes                = $this->get('unilend.service.attachment_manager')->getAllTypesForLender();
                $this->transfers                      = $entityManager->getRepository('UnilendCoreBusinessBundle:Transfer')->findTransferByClient($this->wallet->getIdClient());
                $this->taxationCountryHistory         = $this->getTaxationHistory($this->wallet->getId());
                $this->taxExemptionHistory            = $this->getTaxExemptionHistory($this->users_history->getTaxExemptionHistoryAction($this->clients->id_client));
                $this->termsOfSalesAcceptation        = $entityManager->getRepository('UnilendCoreBusinessBundle:AcceptationsLegalDocs')->findBy(['idClient' => $this->clients->id_client], ['added' => 'DESC']);
                $this->treeRepository                 = $entityManager->getRepository('UnilendCoreBusinessBundle:Tree');

                if (null === $this->currentBankAccount) {
                    $this->currentBankAccount = new BankAccount();
                }

                if ($this->isPhysicalPerson) {
                    $this->fiscalAddress = [
                        'address'  => $this->clients_adresses->adresse_fiscal,
                        'postCode' => $this->clients_adresses->cp_fiscal,
                        'city'     => $this->clients_adresses->ville_fiscal,
                        'country'  => $paysV2Repository->find($this->clients_adresses->id_pays_fiscal) ? $paysV2Repository->find($this->clients_adresses->id_pays_fiscal)->getFr() : ''
                    ];

                    $this->postalAddress = [
                        'address'  => $this->clients_adresses->adresse1,
                        'postCode' => $this->clients_adresses->cp,
                        'city'     => $this->clients_adresses->ville,
                        'country'  => $paysV2Repository->find($this->clients_adresses->id_pays) ? $paysV2Repository->find($this->clients_adresses->id_pays)->getFr() : ''
                    ];

                    $this->settings->get('Liste deroulante origine des fonds', 'type');
                    $this->fundsOriginList = $this->settings->value;
                    $this->fundsOriginList = explode(';', $this->fundsOriginList);

                } else {
                    $this->companies = $this->loadData('companies');
                    $this->companies->get($this->clients->id_client, 'id_client_owner');
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Companies $company */
                    $company = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $this->clients->id_client]);

                    $this->fiscalAddress = [
                        'address'  => $company->getIdAddress()->getAddress(),
                        'postCode' => $company->getIdAddress()->getZip(),
                        'city'     => $company->getIdAddress()->getCity(),
                        'country'  => $company->getIdAddress()->getIdCountry()->getFr()
                    ];

                    $this->postalAddress = [
                        'address'  => null !== $company->getIdPostalAddress() ? $company->getIdPostalAddress()->getAddress() : '',
                        'postCode' => null !== $company->getIdPostalAddress() ? $company->getIdPostalAddress()->getZip() : '',
                        'city'     => null !== $company->getIdPostalAddress() ? $company->getIdPostalAddress()->getCity() : '',
                        'country'  => null !== $company->getIdPostalAddress() ? $company->getIdPostalAddress()->getIdCountry()->getFr() : ''
                    ];
                }

                $this->setVigilanceStatusData();
                break;
        }
    }

    public function _emprunteur()
    {
        $this->clients          = $this->loadData('clients');
        $this->companies        = $this->loadData('companies');
        $this->projects         = $this->loadData('projects');
        $this->projects_status  = $this->loadData('projects_status');
        $this->clients_mandats  = $this->loadData('clients_mandats');
        $this->projects_pouvoir = $this->loadData('projects_pouvoir');
        /** @var \company_sector $companySector */
        $companySector = $this->loadData('company_sector');
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        /** @var \Symfony\Component\Translation\TranslatorInterface translator */
        $this->translator = $this->get('translator');
        $this->sectors    = $companySector->select();

        if (
            false === empty($this->params[0]) &&
            $this->clients->get($this->params[0], 'id_client') &&
            $this->clients->isBorrower()
        ) {
            $action = isset($this->params[1]) ? $this->params[1] : 'default';

            switch ($action) {
                case 'factures':
                    $this->hideDecoration();
                    $this->setView('emprunteur/factures');

                    if (false === empty($this->params[2])) {
                        $this->factures($this->params[2]);
                    }
                    break;
                default:
                    $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->params[0]);
                    $this->companies->get($this->clients->id_client, 'id_client_owner');

                    $companyEntity       = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $this->clients->id_client]);
                    $this->clientAddress = '';
                    if (null !==  $companyEntity->getIdAddress()) {
                        $this->clientAddress .= $companyEntity->getIdAddress()->getAddress() . '<br>' . $companyEntity->getIdAddress()->getZip() . ' ' . $companyEntity->getIdAddress()->getCity() . '<br>' . $companyEntity->getIdAddress()->getIdCountry()->getFr();
                    }

                    if (false === empty($this->clients->telephone)) {
                        $this->clients->telephone = trim(chunk_split($this->clients->telephone, 2, ' '));
                    }

                    $this->projects = $this->projects->select('id_company = "' . $this->companies->id_company . '"');

                    $this->currentBankAccount   = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($client);
                    $this->bankAccountDocuments = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findBy([
                        'idClient' => $client,
                        'idType'   => \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType::RIB
                    ]);
                    $this->aMoneyOrders         = $this->clients_mandats->getMoneyOrderHistory($this->companies->id_company);
                    break;
            }
        } else {
            header('Location: ' . $this->lurl . '/sfpmei/emprunteurs');
            die;
        }
    }

    public function _transferts()
    {
        if (empty($this->params[0])) {
            header('Location: ' . $this->lurl . '/sfpmei/default');
            die;
        }

        /** @var EntityManager $entityManager */
        $entityManager        = $this->get('doctrine.orm.entity_manager');
        $receptionsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions');

        switch ($this->params[0]) {
            case 'preteurs':
                $this->render('transferts/attributions.html.twig', ['walletType' => WalletType::LENDER, 'readOnly' => true]);
                break;
            case 'emprunteurs':
                $this->render('transferts/attributions.html.twig', ['walletType' => WalletType::BORROWER, 'readOnly' => true]);
                break;
            case 'non_attribues':
                $this->receptions = $receptionsRepository->findBy(['statusBo' => Receptions::STATUS_PENDING], ['added' => 'DESC', 'idReception' => 'DESC']);
                $this->setView('transferts/non_attribues');
                break;
            default:
                header('Location: ' . $this->lurl . '/sfpmei/default');
                die;
        }

        $this->type             = $this->params[0];
        $this->statusOperations = [
            Receptions::STATUS_PENDING         => 'Reçu',
            Receptions::STATUS_ASSIGNED_MANUAL => 'Manu',
            Receptions::STATUS_ASSIGNED_AUTO   => 'Auto',
            Receptions::STATUS_IGNORED_MANUAL  => 'Ignoré manu',
            Receptions::STATUS_IGNORED_AUTO    => 'Ignoré auto'
        ];

        if (isset($this->params[1]) && 'csv' === $this->params[1]) {
            $this->hideDecoration();
            $this->autoFireView = false;

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename=export.csv');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Expires: 0');

            PHPExcel_Settings::setCacheStorageMethod(
                PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
                ['memoryCacheSize' => '2048MB', 'cacheTime' => 1200]
            );

            /** @var \PHPExcel_Writer_CSV $writer */
            $writer = PHPExcel_IOFactory::createWriter($this->operationsCsv(), 'CSV');
            $writer->setDelimiter(';');
            $writer->save('php://output');
        }
    }

    public function _projet()
    {
        $this->projects                = $this->loadData('projects');
        $this->projects_status         = $this->loadData('projects_status');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->projects_notes          = $this->loadData('projects_notes');
        $this->project_cgv             = $this->loadData('project_cgv');
        $this->companies               = $this->loadData('companies');
        $this->targetCompany           = $this->loadData('companies');
        $this->companies_actif_passif  = $this->loadData('companies_actif_passif');
        $this->companies_bilans        = $this->loadData('companies_bilans');
        $this->clients                 = $this->loadData('clients');
        $this->projects_pouvoir        = $this->loadData('projects_pouvoir');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\CompanyBalanceSheetManager $companyBalanceSheetManager */
        $companyBalanceSheetManager = $this->get('unilend.service.company_balance_sheet_manager');
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var TranslatorInterface translator */
        $this->translator         = $this->get('translator');

        if (
            isset($this->params[0]) &&
            $this->projects->get($this->params[0], 'id_project')
        ) {
            $this->projectEntity = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($this->projects->id_project);

            $this->companies->get($this->projects->id_company, 'id_company');
            $this->companyMainAddress   = $this->projectEntity->getIdCompany()->getIdAddress();
            $this->companyPostalAddress = $this->projectEntity->getIdCompany()->getIdPostalAddress();

            $this->clients->get($this->companies->id_client_owner, 'id_client');
            $this->projects_notes->get($this->projects->id_project, 'id_project');
            $this->project_cgv->get($this->projects->id_project, 'id_project');

            $this->projects_status->get($this->projects->status, 'status');
            $this->projects_status_history->loadLastProjectHistory($this->projects->id_project);

            $this->aAnnualAccountsDates = [];

            if (empty($this->projects->id_dernier_bilan)) {
                $this->lbilans = $this->companies_bilans->select('id_company = ' . $this->companies->id_company, 'cloture_exercice_fiscal DESC', 0, 3);
            } else {
                $this->lbilans = $this->companies_bilans->select('id_company = ' . $this->companies->id_company . ' AND cloture_exercice_fiscal <= (SELECT cloture_exercice_fiscal FROM companies_bilans WHERE id_bilan = ' . $this->projects->id_dernier_bilan . ')', 'cloture_exercice_fiscal DESC', 0, 3);
            }

            if (empty($this->lbilans)) {
                $this->lCompanies_actif_passif = [];
                $this->aBalanceSheets          = [];
            } else {
                $aAnnualAccountsIds            = array_column($this->lbilans, 'id_bilan');
                $sAnnualAccountsIds            = implode(', ', $aAnnualAccountsIds);
                $this->lCompanies_actif_passif = $this->companies_actif_passif->select('id_bilan IN (' . $sAnnualAccountsIds . ')', 'FIELD(id_bilan, ' . $sAnnualAccountsIds . ') ASC');
                $this->aBalanceSheets          = $companyBalanceSheetManager->getBalanceSheetsByAnnualAccount($aAnnualAccountsIds);
                foreach ($aAnnualAccountsIds as $balanceId) {
                    $this->companies_bilans->get($balanceId);
                    $this->incomeStatements[$balanceId] = $companyBalanceSheetManager->getIncomeStatement($this->companies_bilans, true);
                }
                if (count($this->lCompanies_actif_passif) < count($this->lbilans)) {
                    $this->lCompanies_actif_passif = $this->companies_actif_passif->select('id_bilan IN (' . $sAnnualAccountsIds . ')', 'FIELD(id_bilan, ' . $sAnnualAccountsIds . ') ASC');
                }

                foreach ($this->lbilans as $aAnnualAccounts) {
                    $oEndDate   = new \DateTime($aAnnualAccounts['cloture_exercice_fiscal']);
                    $oStartDate = new \DateTime($aAnnualAccounts['cloture_exercice_fiscal']);
                    $oStartDate->sub(new \DateInterval('P' . $aAnnualAccounts['duree_exercice_fiscal'] . 'M'))->add(new \DateInterval('P1D'));
                    $this->aAnnualAccountsDates[$aAnnualAccounts['id_bilan']] = [
                        'start' => $oStartDate,
                        'end'   => $oEndDate
                    ];
                }
            }

            /** @var \project_need $projectNeed */
            $projectNeed = $this->loadData('project_need');
            $needs       = $projectNeed->getTree();
            $this->needs = $needs;
            $this->xerfi = $this->loadData('xerfi');

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AttachmentManager $attachmentManager */
            $attachmentManager = $this->get('unilend.service.attachment_manager');

            $this->aAttachments                   = $this->projectEntity->getAttachments();
            $this->aAttachmentTypes               = $attachmentManager->getAllTypesForProjects();
            $this->attachmentTypesForCompleteness = $attachmentManager->getAllTypesForProjects(false);
            $this->lastBalanceSheet               = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findOneBy([
                'idClient' => $this->projectEntity->getIdCompany()->getIdClientOwner(),
                'idType'   => \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType::DERNIERE_LIASSE_FISCAL
            ]);

            $this->treeRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Tree');

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectRatingManager $projectRatingManager */
            $projectRatingManager = $this->get('unilend.service.project_rating_manager');
            /** @var \NumberFormatter $numberFormatter */
            $numberFormatter              = $this->get('number_formatter');
            $this->projectRating          = $numberFormatter->format($projectRatingManager->getRating($this->projectEntity)) . ' étoiles';
            $this->projectCommiteeAvgGrade = $numberFormatter->format($projectRatingManager->calculateCommitteeAverageGrade($this->projectEntity));

        } else {
            header('Location: ' . $this->lurl . '/sfpmei');
            die;
        }
    }

    /**
     * @return PHPExcel
     */
    private function operationsCsv()
    {
        $document    = new PHPExcel();
        $activeSheet = $document->setActiveSheetIndex(0);
        $activeSheet->setCellValueByColumnAndRow(0, 1, 'ID');
        $activeSheet->setCellValueByColumnAndRow(1, 1, 'Motif');
        $activeSheet->setCellValueByColumnAndRow(2, 1, 'Montant');
        $activeSheet->setCellValueByColumnAndRow(3, 1, 'Attribution');
        $activeSheet->setCellValueByColumnAndRow(5, 1, 'Date');

        foreach ($this->receptions as $index => $reception) {
            $activeSheet->setCellValueByColumnAndRow(0, $index + 2, $reception->getIdReception());
            $activeSheet->setCellValueByColumnAndRow(1, $index + 2, str_replace('<br>', "\n", $reception->getMotif()));
            $activeSheet->setCellValueByColumnAndRow(2, $index + 2, str_replace('.', ',', bcdiv($reception->getMontant(), 100, 2)));

            if (1 == $reception->getStatusBo() && $reception->getIdUser()) {
                $activeSheet->setCellValueByColumnAndRow(3, $index + 2, $reception->getIdUser()->getFirstname() . ' ' . $reception->getIdUser()->getName() . ' - ' . $reception->getAssignmentDate()->format('d/m/Y à H:i:s'));
            } else {
                $activeSheet->setCellValueByColumnAndRow(3, $index + 2, $this->statusOperations[$reception->getStatusBo()]);
            }

            if ($reception->getIdProject()) {
                $activeSheet->setCellValueByColumnAndRow(4, 1, 'ID projet');
                $activeSheet->setCellValueByColumnAndRow(4, $index + 2, $reception->getIdProject()->getIdProject());
            } elseif ($reception->getIdClient()) {
                $activeSheet->setCellValueByColumnAndRow(4, 1, 'ID client');
                $activeSheet->setCellValueByColumnAndRow(4, $index + 2, $reception->getIdClient()->getIdClient());
            } else {
                $activeSheet->setCellValueByColumnAndRow(4, 1, 'Code SEPA');
                $activeSheet->setCellValueByColumnAndRow(4, $index + 2, substr($reception->getLigne(), 32, 2));
            }

            $activeSheet->setCellValueByColumnAndRow(5, $index + 2, $reception->getAdded()->format('d/m/Y'));
        }
        return $document;
    }

    /**
     * @param int $projectId
     */
    private function factures($projectId)
    {
        $this->hideDecoration();
        /** @var EntityManager $entityManager */
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $projectRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $invoiceRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Factures');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects $project */
        $project = $projectRepository->find($projectId);

        $this->projectInvoices = [];

        if (false === empty($project)) {
            $invoiceList = $invoiceRepository->findBy(['idProject' => $project->getIdProject()], ['date' => 'DESC']);
            $client      = $project->getIdCompany()->getIdClientOwner();

            foreach ($invoiceList as $invoice) {
                $projectInvoice['num_facture']     = $invoice->getNumFacture();
                $projectInvoice['date']            = $invoice->getDate()->format('d/m/Y');
                $projectInvoice['montant_ht']      = $invoice->getMontantHt();
                $projectInvoice['montant_ttc']     = $invoice->getMontantTtc();
                $projectInvoice['type_commission'] = $invoice->getTypeCommission();

                switch ($invoice->getTypeCommission()) {
                    case \Unilend\Bundle\CoreBusinessBundle\Entity\Factures::TYPE_COMMISSION_FUNDS:
                        $projectInvoice['url'] = $this->furl . '/pdf/facture_EF/' . $client->getHash() . '/' . $invoice->getIdProject()->getIdProject();
                        break;
                    case \Unilend\Bundle\CoreBusinessBundle\Entity\Factures::TYPE_COMMISSION_REPAYMENT:
                        $projectInvoice['url'] = $this->furl . '/pdf/facture_ER/' . $client->getHash() . '/' . $invoice->getIdProject()->getIdProject() . '/' . $invoice->getOrdre();
                        break;
                    default :
                        trigger_error('Commission type for invoice unknown', E_USER_NOTICE);
                        break;
                }
                $this->projectInvoices[] = $projectInvoice;
            }
        }
    }

    /**
     * @return string
     */
    private function getLenderStatusMessage()
    {
        $clientStatusHistory = $this->wallet->getIdClient()->getIdClientStatusHistory();

        if (null === $clientStatusHistory || empty($clientStatusHistory->getId())) {
            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->warning('Lender client has no status ' . $this->clients->id_client, ['client' => $this->clients->id_client]);

            return '';
        }

        switch ($clientStatusHistory->getIdStatus()->getId()) {
            case ClientsStatus::CREATION:
                $clientStatusMessage = '<div class="attention">Inscription non terminée </div>';
                break;
            case ClientsStatus::TO_BE_CHECKED:
                $clientStatusMessage = '<div class="attention">Compte non validé - créé le ' . (new \DateTime($this->clients->added))->format('d/m/Y') . '</div>';
                break;
            case ClientsStatus::COMPLETENESS:
            case ClientsStatus::COMPLETENESS_REMINDER:
            case ClientsStatus::COMPLETENESS_REPLY:
                $clientStatusMessage = '<div class="attention" style="background-color:#F9B137">Compte en complétude - créé le ' . (new \DateTime($this->clients->added))->format('d/m/Y') . ' </div>';
                break;
            case ClientsStatus::MODIFICATION:
                $clientStatusMessage = '<div class="attention" style="background-color:#F2F258">Compte en modification - créé le ' . (new \DateTime($this->clients->added))->format('d/m/Y') . '</div>';
                break;
            case ClientsStatus::CLOSED_LENDER_REQUEST:
                $clientStatusMessage = '<div class="attention">Compte clôturé à la demande du prêteur</div>';
                break;
            case ClientsStatus::CLOSED_BY_UNILEND:
                $clientStatusMessage = '<div class="attention">Compte clôturé par Unilend</div>';
                break;
            case ClientsStatus::VALIDATED:
                $clientStatusMessage = '';
                break;
            case ClientsStatus::CLOSED_DEFINITELY:
                $clientStatusMessage = '<div class="attention">Compte définitivement fermé</div>';
                break;
            default:
                $clientStatusMessage = '';
                /** @var \Psr\Log\LoggerInterface $logger */
                $logger = $this->get('logger');
                $logger->warning('Unknown client status "' . $clientStatusHistory->getIdStatus()->getId() . '"', ['client' => $this->clients->id_client]);
                break;
        }

        return $clientStatusMessage;
    }

    private function setVigilanceStatusData()
    {
        /** @var EntityManager $entityManager */
        $entityManager                = $this->get('doctrine.orm.entity_manager');
        $this->vigilanceStatusHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory')->findBy(['client' => $this->clients->id_client], ['id' => 'DESC']);

        if (empty($this->vigilanceStatusHistory)) {
            $this->vigilanceStatus = [
                'status'  => VigilanceRule::VIGILANCE_STATUS_LOW,
                'message' => 'Vigilance standard'
            ];

            return;
        }

        $this->clientAtypicalOperations = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAtypicalOperation')->findBy(['client' => $this->clients->id_client], ['added' => 'DESC']);

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

        $this->clientVigilanceStatusHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientVigilanceStatusHistory');
        $this->hideEditLink                 = true;
    }

    /**
     * @param int $lenderId
     *
     * @return array
     */
    private function getTaxationHistory($lenderId)
    {
        /** @var \lenders_imposition_history $lendersImpositionHistory */
        $lendersImpositionHistory = $this->loadData('lenders_imposition_history');
        try {
            $taxationHistory = $lendersImpositionHistory->getTaxationHistory($lenderId);
        } catch (Exception $exception) {
            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->error('Could not get lender taxation history (id_lender = ' . $lenderId . ') Exception message : ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_lender' => $lenderId]);
            $taxationHistory = ['error' => 'Impossible de charger l\'historique de changement d\'adresse fiscale'];
        }

        return $taxationHistory;
    }

    /**
     * @param array $history
     *
     * @return array
     */
    private function getTaxExemptionHistory(array $history)
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
     * Ajax for company name autocomplete
     */
    public function _autocompleteCompanyName()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $companies = [];

        if ($search = filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING)) {
            /** @var \companies $company */
            $company   = $this->loadData('companies');
            $companies = $company->searchByName($search);
        }

        echo json_encode($companies);
    }

    public function _exports()
    {
        /** @var EntityManager $entityManager */
        $entityManager  = $this->get('doctrine.orm.entity_manager');
        $settingsEntity = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')
            ->findOneBy([
                'type' => 'Requetes acessibles a SFPMEI'
            ]);

        if (null === $settingsEntity) {
            header('Location: ' . $this->lurl . '/sfpmei');
            die;
        }

        $allowedQueries    = explode(',', str_replace(' ', '', $settingsEntity->getValue()));
        $this->queriesList = [];

        if (isset($this->params[0], $this->params[1]) && in_array($this->params[1], $allowedQueries)) {
            switch ($this->params[0]) {
                case 'csv':
                    $this->hideDecoration();
                    $this->autoFireview = false;

                    $this->exportResult($this->params[1]);
                    break;
                case 'html':
                default:
                    $this->executeQuery($this->params[1]);
                    $this->setView('exports/resultats');
                    break;
            }
        } else {
            /** @var queries $queries */
            $queries = $this->loadData('queries');
            $this->queriesList = $queries->select('id_query IN (' . $settingsEntity->getValue() . ')', 'name ASC');
            $this->setView('exports/liste');
        }
    }

    /**
     * @param int $queryId
     */
    private function executeQuery($queryId)
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 1200);

        $this->queries = $this->loadData('queries');
        if (false === $this->queries->get($queryId, 'id_query')) {
            header('Location: ' . $this->lurl . '/sfpmei/exports');
            die;
        }
        $this->queries->sql = trim(str_replace(
            ['[ID_USER]'],
            [$this->sessionIdUser],
            $this->queries->sql
        ));

        if (
            1 !== preg_match('/^SELECT\s/i', $this->queries->sql)
            || 1 === preg_match('/[^A-Z](ALTER|INSERT|DELETE|DROP|TRUNCATE|UPDATE)[^A-Z]/i', $this->queries->sql)
        ) {
            $this->result    = [];
            $this->sqlParams = [];
            trigger_error('Stat query may be dangerous: ' . $this->queries->sql, E_USER_WARNING);
            return;
        }

        preg_match_all('/@[_a-zA-Z1-9]+@/', $this->queries->sql, $this->sqlParams, PREG_SET_ORDER);

        $this->sqlParams = $this->queries->super_unique($this->sqlParams);

        foreach ($this->sqlParams as $param) {
            $this->queries->sql = str_replace($param[0], $this->bdd->quote($_POST['param_' . str_replace('@', '', $param[0])]), $this->queries->sql);
        }

        $this->result = $this->queries->run($queryId, $this->queries->sql);
    }

    /**
     * @param int $queryId
     */
    private function exportResult($queryId)
    {
        $oDocument = $this->exportDocument($queryId);

        // As long as we use $this->queries in order to name file, headers must be sent after calling $this->export()
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $this->bdd->generateSlug($this->queries->name) . '.csv');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        /** @var \PHPExcel_Writer_CSV $oWriter */
        $oWriter = PHPExcel_IOFactory::createWriter($oDocument, 'CSV');
        $oWriter->setUseBOM(true);
        $oWriter->setDelimiter(';');
        $oWriter->save('php://output');
    }

    /**
     * @param int $queryId
     *
     * @return PHPExcel
     *
     * @throws PHPExcel_Exception
     */
    private function exportDocument($queryId)
    {
        $this->executeQuery($queryId);

        PHPExcel_Settings::setCacheStorageMethod(
            PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
            ['memoryCacheSize' => '2048MB', 'cacheTime' => 1200]
        );

        $oDocument    = new PHPExcel();
        $oActiveSheet = $oDocument->setActiveSheetIndex(0);

        if (is_array($this->result) && count($this->result) > 0) {
            $aHeaders       = array_keys($this->result[0]);
            $sLastColLetter = PHPExcel_Cell::stringFromColumnIndex(count($aHeaders) - 1);
            $oActiveSheet->getStyle('A1:' . $sLastColLetter . '1')
                ->applyFromArray([
                    'fill' => [
                        'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => ['rgb' => '2672A2']
                    ],
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ]
                ])
                ->getAlignment()
                ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            foreach ($aHeaders as $iIndex => $sColumnName) {
                $oActiveSheet->setCellValueByColumnAndRow($iIndex, 1, $sColumnName)
                    ->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($iIndex))
                    ->setAutoSize(true);
            }

            foreach ($this->result as $iRowIndex => $aRow) {
                $iColIndex = 0;
                foreach ($aRow as $sCellValue) {
                    $oActiveSheet->setCellValueByColumnAndRow($iColIndex++, $iRowIndex + 2, $sCellValue);
                }
            }
        }

        return $oDocument;
    }
}
