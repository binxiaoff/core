<?php

use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\LenderStatistic;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderOperationsManager;

class sfpmeiController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll   = true;
        $this->menu_admin = 'sfpmei';
        $this->pagination = 25;


        $this->users->checkAccess('sfpmei');
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
                $_SESSION['error_search'][]  = 'Veuillez remplir au moins un champ';
            }

            $clientId = empty($_POST['id']) ? '' : filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
            if (false === $clientId) {
                $_SESSION['error_search'][]  = 'L\'ID du client doit être un nombre';
            }

            $email = empty($_POST['email']) ? '' : filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
            if (false === $email) {
                $_SESSION['error_search'][]  = 'Le format de l\'email n\'est pas valide';
            }

            $lastName = empty($_POST['lastname']) ? '' : filter_var($_POST['lastname'], FILTER_SANITIZE_STRING);
            if (false === $lastName) {
                $_SESSION['error_search'][]  = 'Le format du nom n\'est pas valide';
            }

            $companyName = empty($_POST['company']) ? '' : filter_var($_POST['company'], FILTER_SANITIZE_STRING);
            if (false === $companyName) {
                $_SESSION['error_search'][]  = 'Le format de la raison sociale n\'est pas valide';
            }

            if (false === empty($_SESSION['error_search'])) {
                header('Location: ' . $this->lurl . '/sfpmei/preteurs');
                die;
            }

            /** @var \clients $clients */
            $clients       = $this->get('unilend.service.entity_manager')->getRepository('clients');
            $this->lenders = $clients->searchPreteurs($clientId, $lastName, $email, '', $companyName, 3);

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
                $_SESSION['error_search'][]  = 'Veuillez remplir au moins un champ';
            }

            $siren = empty($_POST['siren']) ? '' : filter_var(str_replace(' ', '', $_POST['siren']), FILTER_SANITIZE_STRING);
            if (false === $siren) {
                $_SESSION['error_search'][]  = 'L\'ID du client doit être un nombre';
            }

            $companyName = empty($_POST['company']) ? '' : filter_var($_POST['company'], FILTER_SANITIZE_STRING);
            if (false === $companyName) {
                $_SESSION['error_search'][]  = 'Le format de la raison sociale n\'est pas valide';
            }

            $lastName = empty($_POST['lastname']) ? '' : filter_var($_POST['lastname'], FILTER_SANITIZE_STRING);
            if (false === $lastName) {
                $_SESSION['error_search'][]  = 'Le format du nom n\'est pas valide';
            }

            $email = empty($_POST['email']) ? '' : filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
            if (false === $email) {
                $_SESSION['error_search'][]  = 'Le format de l\'email n\'est pas valide';
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
                $_SESSION['error_search'][]  = 'Veuillez remplir au moins un champ';
            }

            $projectId = empty($_POST['id']) ? '' : filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
            if (false === $projectId) {
                $_SESSION['error_search'][]  = 'L\'ID du client doit être un nombre';
            }

            $siren = empty($_POST['siren']) ? '' : filter_var(str_replace(' ', '', $_POST['siren']), FILTER_SANITIZE_STRING);
            if (false === $siren) {
                $_SESSION['error_search'][]  = 'L\'ID du client doit être un nombre';
            }

            $companyName = empty($_POST['company']) ? '' : filter_var($_POST['company'], FILTER_SANITIZE_STRING);
            if (false === $companyName) {
                $_SESSION['error_search'][]  = 'Le format de la raison sociale n\'est pas valide';
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
        $this->bids        = $this->loadData('bids');
        $this->clients     = $this->loadData('clients');
        $this->echeanciers = $this->loadData('echeanciers');
        $this->loans       = $this->loadData('loans');
        $this->projects    = $this->loadData('projects');
        /** @var underlying_contract contract */
        $this->contract = $this->loadData('underlying_contract');
        /** @var TranslatorInterface translator */
        $this->translator = $this->get('translator');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
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

                $this->startDate        = new \DateTime('first day of january this year');
                $this->endDate          = new \DateTime('now');

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
                $statusKO = [ProjectsStatus::PROBLEME, ProjectsStatus::RECOUVREMENT, ProjectsStatus::DEFAUT, ProjectsStatus::PROBLEME_J_X, ProjectsStatus::PROCEDURE_SAUVEGARDE, ProjectsStatus::REDRESSEMENT_JUDICIAIRE, ProjectsStatus::LIQUIDATION_JUDICIAIRE];

                $this->lenderIRR                = $entityManager->getRepository('UnilendCoreBusinessBundle:LenderStatistic')->findOneBy(['idWallet' => $this->wallet, 'typeStat' => LenderStatistic::TYPE_STAT_IRR], ['added' => 'DESC']);
                $this->projectsCount            = $this->loans->getProjectsCount($this->wallet->getId());
                $this->problematicProjectsCount = $this->projects->countProjectsByStatusAndLender($this->wallet->getId(), $statusKO);
                $this->publishedProjectsCount   = $this->projects->countProjectsSinceLendersubscription($this->clients->id_client, array_merge($statusOK, $statusKO));
                $this->runningBids              = $this->bids->select('id_lender_account = ' . $this->wallet->getId() . ' AND status = ' . Bids::STATUS_BID_PENDING, 'added DESC');
                $this->hasTransferredLoans      = $this->get('unilend.service.lender_manager')->hasTransferredLoans($this->wallet->getIdClient());
                $this->lenderLoans              = $this->loans->getSumLoansByProject($this->wallet->getId());
                $this->projectsInDebt           = $this->projects->getProjectsInDebt();
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
                /** @var \clients_adresses clients_adresses */
                $this->clients_adresses = $this->loadData('clients_adresses');
                $this->clients_adresses->get($this->clients->id_client, 'id_client');

                /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Operation $firstProvision */
                $provisionType  = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneByLabel(OperationType::LENDER_PROVISION);
                $firstProvision = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->findOneBy(['idWalletCreditor' => $this->wallet, 'idType' => $provisionType], ['id' => 'ASC']);

                $this->lenderStatusMessage            = $this->getLenderStatusMessage();
                $this->availableBalance               = $this->wallet->getAvailableBalance();
                $this->firstDepositAmount             = null === $firstProvision ? 0 : $firstProvision->getAmount();
                $this->totalDepositsAmount            = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->sumCreditOperationsByTypeAndYear($this->wallet, [OperationType::LENDER_PROVISION]);;
                $this->totalWithdrawsAmount           = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->sumDebitOperationsByTypeAndYear($this->wallet, [OperationType::LENDER_WITHDRAW]);
                $this->totalRepaymentsAmount          = $this->echeanciers->getRepaidAmount(['id_lender' => $this->wallet->getId()]);
                $this->totalGrowthInterestsAmount     = $this->echeanciers->getRepaidInterests(['id_lender' => $this->wallet->getId()]);
                $this->totalRepaymentsNextMonthAmount = $this->echeanciers->getNextRepaymentAmountInDateRange($this->wallet->getId(), (new \DateTime('first day of next month'))->format('Y-m-d 00:00:00'), (new \DateTime('last day of next month'))->format('Y-m-d 23:59:59'));
                $this->totalLoansAmount               = $this->loans->sumPrets($this->wallet->getId());
                $this->totalLoansCount                = $this->loans->counter('id_lender = ' . $this->wallet->getId() . ' AND status = ' . Loans::STATUS_ACCEPTED);
                $this->runningBids                    = $this->bids->select('id_lender_account = ' . $this->wallet->getId() . ' AND status = ' . Bids::STATUS_BID_PENDING, 'added DESC');
                $this->totalRunningBidsAmount         = round(array_sum(array_column($this->runningBids, 'amount')) / 100);
                $this->totalRunningBidsCount          = count($this->runningBids);
                $this->averageBidAmount               = $this->bids->getAvgPreteur($this->wallet->getId(), 'amount', implode(', ', [Bids::STATUS_BID_ACCEPTED, Bids::STATUS_BID_REJECTED]));
                $this->averageLoanRate                = $this->loans->getAvgPrets($this->wallet->getId());
                break;
        }
    }

    /**
     * @return string
     */
    private function getLenderStatusMessage()
    {
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager $clientStatusManager */
        $clientStatusManager = $this->get('unilend.service.client_status_manager');
        $currentStatus       = $clientStatusManager->getLastClientStatus($this->clients);
        $creationTime        = strtotime($this->clients->added);
        $clientStatusMessage = '';

        switch ($currentStatus) {
            case \clients_status::TO_BE_CHECKED:
                $clientStatusMessage = '<div class="attention">Attention : compte non validé - créé le '. date('d/m/Y', $creationTime) . '</div>';
                break;
            case \clients_status::COMPLETENESS:
            case \clients_status::COMPLETENESS_REMINDER:
            case \clients_status::COMPLETENESS_REPLY:
                $clientStatusMessage = '<div class="attention" style="background-color:#F9B137">Attention : compte en complétude - créé le ' . date('d/m/Y', $creationTime) . ' </div>';
                break;
            case \clients_status::MODIFICATION:
                $clientStatusMessage = '<div class="attention" style="background-color:#F2F258">Attention : compte en modification - créé le ' . date('d/m/Y', $creationTime) . '</div>';
                break;
            case \clients_status::CLOSED_LENDER_REQUEST:
                $clientStatusMessage = '<div class="attention">Attention : compte clôturé (mis hors ligne) à la demande du prêteur</div>';
                break;
            case \clients_status::CLOSED_BY_UNILEND:
                $clientStatusMessage = '<div class="attention">Attention : compte clôturé (mis hors ligne) par Unilend</div>';
                break;
            case \clients_status::VALIDATED:
                $clientStatusMessage = '';
                break;
            case \clients_status::CLOSED_DEFINITELY:
                $clientStatusMessage = '<div class="attention">Attention : compte définitivement fermé </div>';
                break;
            default:
                if (Clients::SUBSCRIPTION_STEP_PERSONAL_INFORMATION == $this->clients->etape_inscription_preteur) {
                    $clientStatusMessage = '<div class="attention">Attention : Inscription non terminé </div>';
                }
                break;
        }

        return $clientStatusMessage;
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
}
