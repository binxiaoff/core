<?php

use Knp\Snappy\Pdf;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Elements;
use \Unilend\Bundle\CoreBusinessBundle\Service\LenderOperationsManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectCgv;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class pdfController extends bootstrap
{
    /**
     * Path of tmp pdf file
     */
    const TMP_PATH_FILE = '/tmp/pdfUnilend/';

    /** @var Pdf */
    private $oSnapPdf;
    /** @var LoggerInterface */
    private $oLogger;
    /** @var projects_pouvoir */
    private $oProjectsPouvoir;
    /** @var loans */
    public $oLoans;
    /**
     * $clients may also be used in common methods so use this instance for document related client
     * @var \clients
     */
    public $pdfClient;

    /**
     * @desc contains html returns ($this->execute())
     * @var    string $sDisplay
     */
    public $sDisplay;

    public function initialize()
    {
        parent::initialize();

        if (false === isset($this->params)) {
            $this->params = $this->Command->getParameters();
        }

        $this->hideDecoration();

        $this->oSnapPdf = new Pdf('/usr/local/bin/wkhtmltopdf');
        $this->oLogger  = $this->get('logger');
    }

    /**
     * @param string $sView name of view file
     */
    public function setDisplay($sView = '', $sContent = null)
    {
        $this->content = (false === is_null($sContent)) ? $sContent : '';
        $this->setView($sView);

        ob_start();
        if ($this->autoFireHead) {
            $this->fireHead();
        }
        if ($this->autoFireHeader) {
            $this->fireHeader();
        }
        if ($this->autoFireView) {
            $this->fireView();
        }
        if ($this->autoFireFooter) {
            $this->fireFooter();
        }

        $this->sDisplay = ob_get_contents();
        ob_end_clean();

        $this->view = '';
    }

    /**
     * @param string $sPathPdf full path with name of pdf
     * @param string $sTypePdf for log and css
     */
    public function WritePdf($sPathPdf, $sTypePdf = 'authority')
    {
        if (1 !== preg_match('/\.pdf$/i', $sPathPdf)) {
            $sPathPdf .= '.pdf';
        }

        $iTimeStartPdf = microtime(true);

        switch ($sTypePdf) {
            case 'authority':
            case 'warranty':
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/pdf/style.css');
                break;
            case 'claims':
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/pdf/style.css');
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/pdf/styleClaims.css');
                break;
            case 'operations':
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/style.css');
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/style-edit.css');
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/pdf/styleOperations.css');
                break;
            case 'dec_pret':
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/declarationContratPret/print.css');
                break;
            default:
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/pdf/style.css');
                break;
        }
        $this->oSnapPdf->generateFromHtml($this->sDisplay, $sPathPdf, array(), true);

        $iTimeEndPdf = microtime(true) - $iTimeStartPdf;

        $this->oLogger->info($sTypePdf . ' PDF successfully generated in ' . round($iTimeEndPdf, 2) . ' seconds', array('class' => __CLASS__, 'function' => __FUNCTION__));
    }

    /**
     * @param string $sPathPdf full path with name of pdf
     * @param string $sNamePdf pdf's name for client
     */
    public function ReadPdf($sPathPdf, $sNamePdf)
    {
        if (1 !== preg_match('/\.pdf$/i', $sPathPdf)) {
            $sPathPdf .= '.pdf';
        }

        header('Content-disposition: attachment; filename=' . $sNamePdf . '.pdf');
        header('Content-Type: application/force-download');

        if (false === readfile($sPathPdf)) {
            $this->oLogger->error('File "' . $sPathPdf . '"" not readable', array('class' => __CLASS__, 'function' => __FUNCTION__));
        }
    }

    public function _projet()
    {
        $this->pdfClient = $this->loadData('clients');

        if (
            isset($this->params[0], $this->params[1])
            && 1 === preg_match('/^[0-9a-f-]{32,36}$/', $this->params[0])
            && false !== filter_var($this->params[1], FILTER_VALIDATE_INT)
            && $this->pdfClient->get($this->params[0], 'hash')
            && $this->companies->get($this->pdfClient->id_client, 'id_client_owner')
            && $this->projects->get($this->params[1], 'id_company = ' . $this->companies->id_company . ' AND id_project')
            && $this->projects->status != ProjectsStatus::PRET_REFUSE
        ) {
            $proxy   = $this->commonProxy();
            $mandate = $this->commonMandate();

            if ('read' === $proxy['action'] && 'read' === $mandate['action']) {
                /** @var \Symfony\Component\Routing\RouterInterface $router */
                $router = $this->get('router');
                header('Location: ' . $router->generate('universign_signature_status', ['signatureType' => \Unilend\Bundle\FrontBundle\Controller\UniversignController::SIGNATURE_TYPE_PROJECT, 'signatureId' => $this->projects->id_project, 'clientHash' => $this->pdfClient->hash]));
                exit;
            } elseif ('redirect' === $proxy['action'] && 'redirect' === $mandate['action'] && $proxy['url'] === $mandate['url']) {
                header('Location: ' . $proxy['url']);
                exit;
            } elseif ('sign' === $proxy['action'] || 'sign' === $mandate['action']) {
                /** @var \Symfony\Component\Routing\RouterInterface $router */
                $router = $this->get('router');
                header('Location: ' . $router->generate('universign_project_generation', ['projectId' => $this->projects->id_project]));
                exit;
            }
        }

        header('Location: ' . $this->lurl);
        exit;
    }

    // mandat emprunteur
    public function _mandat()
    {
        $this->pdfClient = $this->loadData('clients');

        if (
            isset($this->params[0], $this->params[1])
            && 1 === preg_match('/^[0-9a-f-]{32,36}$/', $this->params[0])
            && false !== filter_var($this->params[1], FILTER_VALIDATE_INT)
            && $this->pdfClient->get($this->params[0], 'hash')
            && $this->companies->get($this->pdfClient->id_client, 'id_client_owner')
            && $this->projects->get($this->params[1], 'id_company = ' . $this->companies->id_company . ' AND id_project')
            && $this->projects->status != ProjectsStatus::PRET_REFUSE
        ) {
            $return = $this->commonMandate();

            switch ($return['action']) {
                case 'redirect':
                    header('Location: ' . $return['url']);
                    exit;
                case 'read':
                    $this->ReadPdf($return['path'], $return['name']);
                    exit;
                case 'sign':
                    header('Location: ' . $this->url . '/universign/mandat/' . $return['mandate']->id_mandat);
                    exit;
            }
        }

        header('Location: ' . $this->lurl);
        exit;
    }

    /**
     * @return array
     */
    private function commonMandate()
    {
        /** @var \clients_mandats $mandates */
        $mandates        = $this->loadData('clients_mandats');
        $path            = $this->path . 'protected/pdf/mandat/';
        $namePDFClient   = 'MANDAT-UNILEND-' . $this->projects->slug . '-' . $this->pdfClient->id_client;
        $projectMandates = $mandates->select(
            'id_project = ' . $this->projects->id_project . ' AND id_client = ' . $this->pdfClient->id_client . ' AND status IN (' . UniversignEntityInterface::STATUS_PENDING . ',' . UniversignEntityInterface::STATUS_SIGNED . ')',
            'id_mandat DESC'
        );

        if (false === empty($projectMandates)) {
            $mandate = array_shift($projectMandates);

            foreach ($projectMandates as $mandateToArchive) {
                $mandates->get($mandateToArchive['id_mandat']);
                $mandates->status = UniversignEntityInterface::STATUS_ARCHIVED;
                $mandates->update();
            }

            if (UniversignEntityInterface::STATUS_SIGNED == $mandate['status']) {
                return [
                    'action' => 'read',
                    'path'   => $path . $mandate['name'],
                    'name'   => $namePDFClient
                ];
            } elseif (UniversignEntityInterface::STATUS_CANCELED == $mandate['status']) {
                return [
                    'action' => 'redirect',
                    'url'    => $this->lurl . '/espace_emprunteur/operations'
                ];
            }

            $mandates->get($mandate['id_mandat']);
        } else {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $bankAccount   = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($this->pdfClient->id_client);
            if (null === $bankAccount) {
                return [
                    'action' => 'redirect',
                    'url'    => $this->lurl
                ];
            }

            $mandates->id_client  = $this->pdfClient->id_client;
            $mandates->url_pdf    = '/pdf/mandat/' . $this->pdfClient->hash . '/' . $this->projects->id_project;
            $mandates->name       = 'mandat-' . $this->pdfClient->hash . '-' . $this->projects->id_project . '.pdf';
            $mandates->id_project = $this->projects->id_project;
            $mandates->status     = UniversignEntityInterface::STATUS_PENDING;
            $mandates->iban       = $bankAccount->getIban();
            $mandates->bic        = $bankAccount->getBic();
            $mandates->create();
        }

        if (false === file_exists($path . $mandates->name)) {
            $this->GenerateWarrantyHtml($mandates);
            $this->WritePdf($path . $mandates->name, 'warranty');
        }

        return [
            'action'  => 'sign',
            'mandate' => $mandates
        ];
    }

    private function GenerateWarrantyHtml($mandates)
    {
        $this->pays = $this->loadData('pays');
        $this->clients_adresses->get($this->pdfClient->id_client, 'id_client');
        $this->pays->get($this->pdfClient->id_langue, 'id_langue');

        if ($this->companies->get($this->pdfClient->id_client, 'id_client_owner')) {
            $this->entreprise = true;
        } else {
            $this->entreprise = false;
        }

        $this->iban  = $mandates->iban;
        $this->bic   = $mandates->bic;

        // pour savoir si Preteur ou emprunteur
        if (isset($this->params[1]) && $this->projects->get($this->params[1], 'id_project')) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BorrowerManager $borrowerManager */
            $borrowerManager = $this->get('unilend.service.borrower_manager');
            $this->motif = $borrowerManager->getBorrowerBankTransferLabel($this->projects);
        } else {
            $this->motif = $this->pdfClient->getLenderPattern($this->pdfClient->id_client);
            $this->motif = $this->ficelle->str_split_unicode('UNILEND' . $this->motif);
        }

        $this->settings->get('Créancier adresse', 'type');
        $this->creancier_adresse = $this->settings->value;

        $this->settings->get('Créancier cp', 'type');
        $this->creancier_cp = $this->settings->value;

        $this->settings->get('ICS de SFPMEI', 'type');
        $this->creancier_identifiant = $this->settings->value;

        $this->settings->get('Créancier nom', 'type');
        $this->creancier = $this->settings->value;

        $this->settings->get('Créancier pays', 'type');
        $this->creancier_pays = $this->settings->value;

        $this->settings->get('Créancier ville', 'type');
        $this->creancier_ville = $this->settings->value;

        $this->settings->get('Créancier code identifiant', 'type');
        $this->creancier_code_id = $this->settings->value;

        $this->settings->get('Adresse retour', 'type');
        $this->adresse_retour = $this->settings->value;

        $this->setDisplay('mandat_html');
    }

    public function _pouvoir()
    {
        $this->pdfClient = $this->loadData('clients');

        if (
            isset($this->params[0], $this->params[1])
            && 1 === preg_match('/^[0-9a-f-]{32,36}$/', $this->params[0])
            && false !== filter_var($this->params[1], FILTER_VALIDATE_INT)
            && $this->pdfClient->get($this->params[0], 'hash')
            && $this->companies->get($this->pdfClient->id_client, 'id_client_owner')
            && $this->projects->get($this->params[1], 'id_company = ' . $this->companies->id_company . ' AND id_project')
            && $this->projects->status != \projects_status::PRET_REFUSE
        ) {
            $return = $this->commonProxy();

            switch ($return['action']) {
                case 'redirect':
                    header('Location: ' . $return['url']);
                    exit;
                case 'read':
                    $this->ReadPdf($return['path'], $return['name']);
                    exit;
                case 'sign':
                    $regenerationUniversign = $return['regenerate'] ? '' : '/NoUpdateUniversign';
                    header('Location: ' . $this->url . '/universign/pouvoir/' . $return['proxy']->id_pouvoir . $regenerationUniversign);
                    exit;
            }
        }

        header('Location: ' . $this->lurl);
        exit;
    }

    /**
     * @return array
     */
    private function commonProxy()
    {
        $this->oProjectsPouvoir = $this->loadData('projects_pouvoir');

        $signed        = false;
        $path          = $this->path . 'protected/pdf/pouvoir/';
        $namePdfClient = 'POUVOIR-UNILEND-' . $this->projects->slug . '-' . $this->pdfClient->id_client;
        $fileName      = 'pouvoir-' . $this->pdfClient->hash . '-' . $this->projects->id_project . '.pdf';

        $projectPouvoir        = $this->oProjectsPouvoir->select('id_project = ' . $this->projects->id_project, 'added ASC');
        $projectPouvoirToTreat = (is_array($projectPouvoir) && false === empty($projectPouvoir)) ? array_shift($projectPouvoir) : null;

        if (is_array($projectPouvoir) && 0 < count($projectPouvoir)) {
            foreach ($projectPouvoir as $projectPouvoirToDelete) {
                $this->oLogger->info('Deleting proxy (' . $projectPouvoirToDelete['id_pouvoir'] . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $this->projects->id_project));
                $this->oProjectsPouvoir->delete($projectPouvoirToDelete['id_pouvoir'], 'id_pouvoir');
            }
        }

        if (false === is_null($projectPouvoirToTreat)) {
            $this->oProjectsPouvoir->get($projectPouvoirToTreat['id_pouvoir'], 'id_pouvoir');
            if ($this->oProjectsPouvoir->status == UniversignEntityInterface::STATUS_CANCELED) {
                return [
                    'action' => 'redirect',
                    'url'    => $this->lurl . '/espace_emprunteur/operations'
                ];
            }

            // si c'est un upload manuel du BO on affiche directement
            if ($projectPouvoirToTreat['id_universign'] == 'no_universign' && file_exists($path . $projectPouvoirToTreat['name'])) {
                return [
                    'action' => 'read',
                    'path'   => $path . $projectPouvoirToTreat['name'],
                    'name'   => $namePdfClient
                ];
            }

            $signed        = $projectPouvoirToTreat['status'] == UniversignEntityInterface::STATUS_SIGNED;
            $instantCreate = false;

            if (false === file_exists($path . $projectPouvoirToTreat['name'])) {
                $this->GenerateProxyHtml();
                $this->WritePdf($path . $projectPouvoirToTreat['name'], 'authority');
                $signed        = false;
                $instantCreate = true;
            }
        } else {
            $this->GenerateProxyHtml();
            $this->WritePdf($path . $fileName, 'authority');

            $this->oProjectsPouvoir->id_project = $this->projects->id_project;
            $this->oProjectsPouvoir->url_pdf    = '/pdf/pouvoir/' . $this->pdfClient->hash . '/' . $this->projects->id_project . '/';
            $this->oProjectsPouvoir->name       = $fileName;
            $this->oProjectsPouvoir->create();

            $instantCreate = true;
        }

        if (false === $signed) {
            if (file_exists($path . $fileName) && filesize($path . $fileName) > 0 && date('Y-m-d', filemtime($path . $fileName)) != date('Y-m-d')) {
                unlink($path . $fileName);

                $this->oLogger->info('File "' . $path . $fileName . '" deleted', array('class' => __CLASS__, 'function' => __FUNCTION__));

                $this->GenerateProxyHtml();
                $this->WritePdf($path . $fileName, 'authority');
                $instantCreate = true;
            }

            if (date('Y-m-d', strtotime($this->oProjectsPouvoir->updated)) == date('Y-m-d') && false === $instantCreate && false === empty($this->oProjectsPouvoir->url_universign)) {
                $regenerationUniversign = false;
            } else {
                $regenerationUniversign = true;
                $this->oProjectsPouvoir->update();
            }

            return [
                'action'     => 'sign',
                'proxy'      => $this->oProjectsPouvoir,
                'regenerate' => $regenerationUniversign
            ];
        } else {
            return [
                'action' => 'read',
                'path'   => $path . $fileName,
                'name'   => $namePdfClient
            ];
        }
    }

    private function GenerateProxyHtml()
    {
        $this->lng['pdf-pouvoir'] = $this->ln->selectFront('pdf-pouvoir', $this->language, $this->App);

        // Update repayment schedule dates based on proxy generation date
        // Once proxy has been generated, do not update repayment schedule anymore
        $this->updateRepaymentSchedules();

        /** @var product $product */
        $product = $this->loadData('product');
        $product->get($this->projects->id_product);
        $template = $product->proxy_template;

        if (false === empty($product->proxy_block_slug)) {
            $this->blocs->get($product->proxy_block_slug, 'slug');
            $lElements = $this->blocs_elements->select('id_bloc = ' . $this->blocs->id_bloc . ' AND id_langue = "' . $this->language . '"');
            foreach ($lElements as $b_elt) {
                $this->elements->get($b_elt['id_element']);
                $this->bloc_pouvoir[$this->elements->slug]           = $b_elt['value'];
                $this->bloc_pouvoirComplement[$this->elements->slug] = $b_elt['complement'];
            }
        }

        $this->companies_actif_passif = $this->loadData('companies_actif_passif');
        $this->companies_bilans       = $this->loadData('companies_bilans');
        $this->echeanciers            = $this->loadData('echeanciers');
        $this->oLoans                 = $this->loadData('loans');
        /** @var underlying_contract $contract */
        $contract                     = $this->loadData('underlying_contract');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager          = $this->get('doctrine.orm.entity_manager');
        $this->walletRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');

        $contract->get(\underlying_contract::CONTRACT_BDC, 'label');
        $BDCContractId = $contract->id_contract;

        $contract->get(\underlying_contract::CONTRACT_IFP, 'label');
        $IFPContractId = $contract->id_contract;

        $contract->get(\underlying_contract::CONTRACT_MINIBON, 'label');
        $minibonContractId = $contract->id_contract;

        $this->montantPrete     = $this->projects->amount;
        $this->taux             = $this->projects->getAverageInterestRate();
        $this->nbLoansBDC       = $this->oLoans->counter('id_type_contract = ' . $BDCContractId . ' AND id_project = ' . $this->projects->id_project);
        $this->nbLoansIFP       = $this->oLoans->counter('id_type_contract = ' . $IFPContractId . ' AND id_project = ' . $this->projects->id_project);
        $this->nbLoansMinibon   = $this->oLoans->counter('id_type_contract = ' . $minibonContractId . ' AND id_project = ' . $this->projects->id_project);
        $this->lRemb            = $this->loadData('echeanciers_emprunteur')->select('id_project = ' . $this->projects->id_project, 'ordre ASC');
        $this->rembByMonth      = bcdiv($this->lRemb[0]['montant'] + $this->lRemb[0]['commission'] + $this->lRemb[0]['tva'], 100, 2);
        $this->dateLastEcheance = $this->echeanciers->getDateDerniereEcheancePreteur($this->projects->id_project);

        $this->capital = 0;
        foreach ($this->lRemb as $r) {
            $this->capital += $r['capital'];
        }

        $this->companies_bilans->get($this->projects->id_dernier_bilan, 'id_bilan');
        $this->l_AP             = $this->companies_actif_passif->select('id_bilan = ' . $this->projects->id_dernier_bilan);
        $this->totalActif       = $this->l_AP[0]['immobilisations_corporelles'] + $this->l_AP[0]['immobilisations_incorporelles'] + $this->l_AP[0]['immobilisations_financieres'] + $this->l_AP[0]['stocks'] + $this->l_AP[0]['creances_clients'] + $this->l_AP[0]['disponibilites'] + $this->l_AP[0]['valeurs_mobilieres_de_placement'] + $this->l_AP[0]['comptes_regularisation_actif'];
        $this->totalPassif      = $this->l_AP[0]['capitaux_propres'] + $this->l_AP[0]['provisions_pour_risques_et_charges'] + $this->l_AP[0]['amortissement_sur_immo'] + $this->l_AP[0]['dettes_financieres'] + $this->l_AP[0]['dettes_fournisseurs'] + $this->l_AP[0]['autres_dettes'] + $this->l_AP[0]['comptes_regularisation_passif'];
        $this->lLenders         = $this->oLoans->select('id_project = ' . $this->projects->id_project, 'rate ASC');
        $this->dateRemb         = date('d/m/Y');
        $this->dateDernierBilan = date('d/m/Y', strtotime($this->companies_bilans->cloture_exercice_fiscal)); // @todo Intl

        $this->setDisplay($template);
    }

    public function _cgv_emprunteurs()
    {
        if (false === isset($this->params[0], $this->params[1]) || false === is_numeric($this->params[0])) {
            header('Location:' . $this->lurl);
            return;
        }
        $iProjectId     = $this->params[0];
        $sFileName      = $this->params[1];
        $sNamePdfClient = 'CGV-UNILEND-' . $iProjectId;
        $oProjectCgv    = $this->loadData('project_cgv');
        $path           = $this->path . ProjectCgv::BASE_PATH;

        if ($oProjectCgv->get($iProjectId, 'id_project') && false === empty($oProjectCgv->name) && false === empty($oProjectCgv->id_tree)) {
            if ($sFileName !== $oProjectCgv->name) {
                header('Location: ' . $this->lurl);
                return;
            }

            // and if it's signed
            if ($oProjectCgv->status == UniversignEntityInterface::STATUS_SIGNED && file_exists($path . $oProjectCgv->name)) {
                $this->ReadPdf($path . $oProjectCgv->name, $sNamePdfClient);
                return;
            }

            if ('' != $oProjectCgv->url_universign) {
                header('Location: ' . $oProjectCgv->url_universign);
                return;
            }
        } else {
            header('Location: ' . $this->lurl);
            return;
        }

        if (false === file_exists($path . $oProjectCgv->name)) {
            // Recuperation du pdf du tree
            $elements = $this->tree_elements->select('id_tree = "' . $oProjectCgv->id_tree . '" AND id_element = ' . Elements::TYPE_PDF_TERMS_OF_SALE . ' AND id_langue = "' . $this->language . '"');

            if (false === isset($elements[0]['value']) || '' == $elements[0]['value']) {
                header('Location: ' . $this->lurl);
                return;
            }

            $sPdfPath = $this->path . 'public/default/var/fichiers/' . $elements[0]['value'];

            if (false === file_exists($sPdfPath)) {
                header('Location: ' . $this->lurl);
                return;
            }
            if (false === is_dir($this->path . ProjectCgv::BASE_PATH)) {
                mkdir($this->path . ProjectCgv::BASE_PATH, 0777, true);
            }
            if (false === file_exists($this->path . ProjectCgv::BASE_PATH . $oProjectCgv->name)) {
                copy($sPdfPath, $this->path . ProjectCgv::BASE_PATH . $oProjectCgv->name);
            }
        }

        header('Location: ' . $this->url . '/universign/cgv_emprunteurs/' . $oProjectCgv->id . '/' . $oProjectCgv->name);
    }

    // Mise a jour des dates echeances preteurs et emprunteur (utilisé pour se baser sur la date de creation du pouvoir)
    private function updateRepaymentSchedules()
    {
        ini_set('max_execution_time', 300);

        if ($this->projects->status == \projects_status::FUNDE) {
            /** @var \echeanciers $lenderRepaymentSchedule */
            $lenderRepaymentSchedule = $this->loadData('echeanciers');
            /** @var \echeanciers_emprunteur $borrowerRepaymentSchedule */
            $borrowerRepaymentSchedule = $this->loadData('echeanciers_emprunteur');
            /** @var \jours_ouvres $jo */
            $jo = $this->loadLib('jours_ouvres');

            $this->settings->get('Nombre jours avant remboursement pour envoyer une demande de prelevement', 'type');
            $daysOffset        = $this->settings->value;
            $repaymentBaseDate = date('Y-m-d H:i:00');

            for ($order = 1; $order <= $this->projects->period; $order++) {
                $currentLenderRepaymentDates   = $lenderRepaymentSchedule->select('id_project = ' . $this->projects->id_project . ' AND ordre = ' . $order, '', 0, 1)[0];
                $currentBorrowerRepaymentDates = $borrowerRepaymentSchedule->select('id_project = ' . $this->projects->id_project . ' AND ordre = ' . $order, '', 0, 1)[0];

                $lenderRepaymentDate   = date('Y-m-d H:i:s', $this->dates->dateAddMoisJoursV3($repaymentBaseDate, $order));
                $borrowerRepaymentDate = $this->dates->dateAddMoisJoursV3($repaymentBaseDate, $order);
                $borrowerRepaymentDate = date('Y-m-d H:i:s', $jo->display_jours_ouvres($borrowerRepaymentDate, $daysOffset));

                if (
                    substr($currentLenderRepaymentDates['date_echeance'], 0, 10) !== substr($lenderRepaymentDate, 0, 10)
                    || substr($currentLenderRepaymentDates['date_echeance_emprunteur'], 0, 10) !== substr($borrowerRepaymentDate, 0, 10)
                ) {
                    $lenderRepaymentSchedule->onMetAjourLesDatesEcheances($this->projects->id_project, $order, $lenderRepaymentDate, $borrowerRepaymentDate);
                }

                if (substr($currentBorrowerRepaymentDates['date_echeance_emprunteur'], 0, 10) !== substr($borrowerRepaymentDate, 0, 10)) {
                    $borrowerRepaymentSchedule->onMetAjourLesDatesEcheancesE($this->projects->id_project, $order, $borrowerRepaymentDate);
                }
            }
        }
    }

    public function _contrat()
    {
        if (false === isset($this->params[0], $this->params[1])) {
            header('Location: ' . $this->lurl);
            exit;
        }

        /** @var \clients $clients */
        $clients = $this->loadData('clients');

        // hack the symfony guard token
        $session = $this->get('session');

        /** @var \Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken $token */
        $token =  unserialize($session->get('_security_default'));
        if (!$token instanceof \Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken) {
            header('Location: ' . $this->lurl);
            exit;
        }
        /** @var \Unilend\Bundle\FrontBundle\Security\User\UserLender $user */
        $user = $token->getUser();
        if (!$user instanceof \Unilend\Bundle\FrontBundle\Security\User\UserLender) {
            header('Location: ' . $this->lurl);
            exit;
        }

        if (false === $clients->get($this->params[0], 'hash') || $user->getClientId() != $clients->id_client && empty($_SESSION['user']['id_user'])) {
            header('Location: ' . $this->lurl);
            exit;
        }

        /** @var \loans $loans */
        $loans           = $this->loadData('loans');
        /** @var \projects $projects */
        $projects        = $this->loadData('projects');

        if (false === $loans->get($this->params[1], 'id_loan')) {
            header('Location: ' . $this->lurl);
            exit;
        }

        if (false === $projects->get($loans->id_project, 'id_project')) {
            header('Location: ' . $this->lurl);
            exit;
        }

        $namePdfClient = 'CONTRAT-UNILEND-' . $projects->slug . '-' . $loans->id_loan;
        $filePath      = $this->path . 'protected/pdf/contrat/contrat-' . $clients->hash . '-' . $loans->id_loan . '.pdf';

        if (false === file_exists($filePath)) {
            if (false === empty($loans->id_transfer)) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LoanManager $loanManager */
                $loanManager = $this->get('unilend.service.loan_manager');
                /** @var Clients $formerOwner */
                $formerOwner = $loanManager->getFirstOwner($loans);
                $clients->get($formerOwner->getIdClient(), 'id_client');
            }
            $this->GenerateContractHtml($clients, $loans, $projects);
            $this->WritePdf($filePath, 'contract');
        }

        $this->ReadPdf($filePath, $namePdfClient);
    }

    /**
     * @param \clients $oClients
     * @param $oLoans
     * @param projects $oProjects
     */
    private function GenerateContractHtml(\clients $oClients, \loans $oLoans, \projects $oProjects)
    {
        $this->emprunteur              = $this->loadData('clients');
        $this->companiesEmprunteur     = $this->loadData('companies');
        $this->companiesPreteur        = $this->loadData('companies');
        $this->companies_actif_passif  = $this->loadData('companies_actif_passif');
        $this->companies_bilans        = $this->loadData('companies_bilans');
        $this->echeanciers             = $this->loadData('echeanciers');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->oProjectsPouvoir        = $this->loadData('projects_pouvoir');
        $this->clients_adresses        = $this->loadData('clients_adresses');
        $this->oLoans                  = $oLoans;
        $this->clients                 = $oClients;
        $this->projects                = $oProjects;
        /** @var underlying_contract $contract */
        $contract                      = $this->loadData('underlying_contract');

        $this->clients_adresses->get($oClients->id_client, 'id_client');
        $this->companiesEmprunteur->get($oProjects->id_company, 'id_company');
        $this->emprunteur->get($this->companiesEmprunteur->id_client_owner, 'id_client');

        // Si preteur morale
        if ($oClients->type == 2) {
            $this->companiesPreteur->get($oClients->id_client, 'id_client_owner');
        }

        $this->companies_bilans->get($this->projects->id_dernier_bilan, 'id_bilan');

        $this->dateLastEcheance = $this->echeanciers->getDateDerniereEcheancePreteur($oProjects->id_project);
        $this->dateDernierBilan = date('d/m/Y', strtotime($this->companies_bilans->cloture_exercice_fiscal)); // @todo Intl

        $this->l_AP        = $this->companies_actif_passif->select('id_bilan = ' . $oProjects->id_dernier_bilan);
        $this->totalActif       = $this->l_AP[0]['immobilisations_corporelles'] + $this->l_AP[0]['immobilisations_incorporelles'] + $this->l_AP[0]['immobilisations_financieres'] + $this->l_AP[0]['stocks'] + $this->l_AP[0]['creances_clients'] + $this->l_AP[0]['disponibilites'] + $this->l_AP[0]['valeurs_mobilieres_de_placement'] + $this->l_AP[0]['comptes_regularisation_actif'];
        $this->totalPassif      = $this->l_AP[0]['capitaux_propres'] + $this->l_AP[0]['provisions_pour_risques_et_charges'] + $this->l_AP[0]['amortissement_sur_immo'] + $this->l_AP[0]['dettes_financieres'] + $this->l_AP[0]['dettes_fournisseurs'] + $this->l_AP[0]['autres_dettes'] + $this->l_AP[0]['comptes_regularisation_passif'];
        $this->lRemb       = $this->echeanciers->select('id_loan = ' . $oLoans->id_loan, 'ordre ASC');

        $this->capital = 0;
        foreach ($this->lRemb as $r) {
            $this->capital += $r['capital'];
        }

        if ($this->oProjectsPouvoir->get($oProjects->id_project, 'id_project')) {
            $this->dateContrat = date('d/m/Y', strtotime($this->oProjectsPouvoir->updated));
            $this->dateRemb    = date('d/m/Y', strtotime($this->oProjectsPouvoir->updated));
        } else {
            $this->dateContrat = date('d/m/Y');
            $this->dateRemb    = date('d/m/Y');
        }

        $remb = $this->projects_status_history->select('id_project = ' . $oProjects->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::REMBOURSEMENT . ')', 'added ASC, id_project_status_history ASC', 0, 1);

        if ($remb[0]['added'] != "") {
            $this->dateRemb = date('d/m/Y', strtotime($remb[0]['added']));
        } else {
            $this->dateRemb = date('d/m/Y');
        }

        $this->dateContrat = $this->dateRemb;

        /** @var \tax_type $taxType */
        $taxType = $this->loadData('tax_type');

        $taxRate = $taxType->getTaxRateByCountry('fr');
        $fVat    = $taxRate[\Unilend\Bundle\CoreBusinessBundle\Entity\TaxType::TYPE_VAT] / 100;

        $this->aCommissionRepayment = \repayment::getRepaymentCommission($oLoans->amount / 100, $oProjects->period, round(bcdiv($oProjects->commission_rate_repayment, 100, 4), 2), $fVat);
        $this->fCommissionRepayment = $this->aCommissionRepayment['commission_total'];

        $fundReleasingCommissionRate = bcdiv($this->projects->commission_rate_funds, 100, 5);

        $this->fCommissionProject = $fundReleasingCommissionRate * $oLoans->amount / 100;
        $this->fInterestTotal     = $this->echeanciers->getTotalInterests(array('id_loan' => $oLoans->id_loan));

        $contract->get($oLoans->id_type_contract);

        $sTemplate = $contract->document_template;

        if ($this->blocs->get($contract->block_slug, 'slug')) {
            $lElements = $this->blocs_elements->select('id_bloc = ' . $this->blocs->id_bloc . ' AND id_langue = "' . $this->language . '"');
            foreach ($lElements as $b_elt) {
                $this->elements->get($b_elt['id_element']);
                $this->bloc_pdf_contrat[$this->elements->slug]           = $b_elt['value'];
                $this->bloc_pdf_contratComplement[$this->elements->slug] = $b_elt['complement'];
            }
        }

        $this->setDisplay($sTemplate);
    }

    public function _declarationContratPret_html($iIdLoan)
    {
        $this->oLoans          = $this->loadData('loans');
        $this->companiesEmp    = $this->loadData('companies');
        $this->emprunteur      = $this->loadData('clients');
        $this->preteur         = $this->loadData('clients');
        $this->preteurCompanie = $this->loadData('companies');
        $this->preteur_adresse = $this->loadData('clients_adresses');
        $this->echeanciers     = $this->loadData('echeanciers');

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (isset($iIdLoan) && $this->oLoans->get($iIdLoan, 'status = "0" AND id_loan')) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $wallet */
            $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($this->oLoans->id_lender);

            $this->settings->get('Declaration contrat pret - adresse', 'type');
            $this->adresse = $this->settings->value;

            $this->settings->get('Declaration contrat pret - raison sociale', 'type');
            $this->raisonSociale = $this->settings->value;

            $this->projects->get($this->oLoans->id_project, 'id_project');
            $this->companiesEmp->get($this->projects->id_company, 'id_company');
            $this->emprunteur->get($this->companiesEmp->id_client_owner, 'id_client');
            $this->preteur->get($wallet->getIdClient()->getIdClient(), 'id_client');
            $this->preteur_adresse->get($this->preteur->id_client, 'id_client');

            $this->lEcheances = array_values($this->echeanciers->getYearlySchedule(array('id_loan' => $this->oLoans->id_loan)));
            $this->lenderCountry = '';

            if (false === $wallet->getIdClient()->isNaturalPerson()) {
                $this->preteurCompanie->get($this->preteur->id_client, 'id_client_owner');

                $this->nomPreteur     = $this->preteurCompanie->name;
                $this->adressePreteur = $this->preteurCompanie->adresse1;
                $this->cpPreteur      = $this->preteurCompanie->zip;
                $this->villePreteur   = $this->preteurCompanie->city;
            } else {
                if ($this->preteur_adresse->id_pays > \Unilend\Bundle\CoreBusinessBundle\Entity\PaysV2::COUNTRY_FRANCE) {
                    /** @var \pays_v2 $country */
                    $country = $this->loadData('pays_v2');
                    $country->get($this->preteur_adresse->id_pays, 'id_pays');

                    $this->lenderCountry = $country->fr;
                }

                $this->nomPreteur     = $this->preteur->prenom . ' ' . $this->preteur->nom;
                $this->adressePreteur = $this->preteur_adresse->adresse1;
                $this->cpPreteur      = $this->preteur_adresse->cp;
                $this->villePreteur   = $this->preteur_adresse->ville;
            }

            $this->setDisplay('declarationContratPret_html');
        }
    }

    public function _declaration_de_creances()
    {
        if (false === isset($this->params[0], $this->params[1])) {
            header('Location: ' . $this->lurl);
            exit;
        }

        /** @var \clients $clients */
        $clients = $this->loadData('clients');
        /** @var \loans $loans */
        $loans = $this->loadData('loans');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (false === $loans->get($this->params[1], 'id_loan')) {
            header('Location: ' . $this->lurl);
            exit;
        }

        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $wallet */
        $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($loans->id_lender);

        if (false === $wallet->getIdClient()->isLender()) {
            header('Location: ' . $this->lurl);
            exit;
        }

        $clients->get($wallet->getIdClient()->getIdClient(), 'id_client');

        $filePath      = $this->path . 'protected/pdf/declaration_de_creances/' . $loans->id_project . '/';
        $filePath      = ($loans->id_project == '1456') ? $filePath : $filePath . $clients->id_client . '/';
        $filePath      = $filePath . 'declaration-de-creances' . '-' . $clients->hash . '-' . $loans->id_loan . '.pdf';
        $namePdfClient = 'DECLARATION-DE-CREANCES-UNILEND-' . $clients->hash . '-' . $loans->id_loan;

        if (false === file_exists($filePath)) {
            $this->GenerateClaimsHtml($clients, $loans);
            $this->WritePdf($filePath, 'claims');
        }

        $this->ReadPdf($filePath, $namePdfClient);
    }

    private function GenerateClaimsHtml(\clients $client, \loans $loan)
    {
        /** @var \loans oLoans */
        $this->oLoans = $loan;
        /** @var \clients clients */
        $this->clients = $client;
        /** @var \projects projects */
        $this->projects = $this->loadData('projects');

        $this->projects->get($loan->id_project);
        /** @var \pays_v2 pays */
        $this->pays = $this->loadData('pays_v2');
        /** @var \echeanciers echeanciers */
        $this->echeanciers = $this->loadData('echeanciers');
        /** @var \projects_status_history projects_status_history */
        $this->projects_status_history = $this->loadData('projects_status_history');
        /** @var \projects_status_history_details projects_status_history_details */
        $this->projects_status_history_details = $this->loadData('projects_status_history_details');
        /** @var underlying_contract contract */
        $this->contract = $this->loadData('underlying_contract');
        /** @var \Symfony\Component\Translation\TranslatorInterface translator */
        $this->translator = $this->get('translator');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $wallet */
        $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($this->oLoans->id_lender);
        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Companies borrowerCompany */
        $this->borrowerCompany = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')
            ->find($this->projects->id_company);

        $status = [
            CompanyStatus::STATUS_PRECAUTIONARY_PROCESS,
            CompanyStatus::STATUS_RECEIVERSHIP,
            CompanyStatus::STATUS_COMPULSORY_LIQUIDATION
        ];

        if (in_array($this->borrowerCompany->getIdStatus()->getLabel(), $status)
        ) {
            if (in_array($this->clients->type, [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) {
                $this->clients_adresses->get($this->clients->id_client, 'id_client');
                $countryId = $this->clients_adresses->id_pays_fiscal;
            } else {
                $this->companies->get($this->clients->id_client, 'id_client_owner');
                $countryId = $this->companies->id_pays;
            }

            if ($countryId == 0) {
                $countryId = 1;
            }

            $this->pays->get($countryId, 'id_pays');
            $this->pays_fiscal = $this->pays->fr;

            $companyStatusHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyStatusHistory');
            $companyStatusHistory           = $companyStatusHistoryRepository->findFirstHistoryByCompanyAndStatus($this->borrowerCompany->getIdCompany(), $status);

            $this->date            = $companyStatusHistory->getChangedOn();
            $this->mandataires_var = $companyStatusHistory->getReceiver();

            /** @var projects_status $projectStatusType */
            $projectStatusType = $this->loadData('projects_status');
            $projectStatusType->get(ProjectsStatus::RECOUVREMENT, 'status');
            /** @todo utiliser la date de déchéance du terme à la place de celle ci */
            $debtCollectionStatus = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory')
                ->findOneBy(['idProject' => $this->projects->id_project, 'idProjectStatus' => $projectStatusType->id_project_status]);
            if ($debtCollectionStatus) {
                $expiration = $debtCollectionStatus->getAdded();
            } else {
                $expiration = $this->date;
            }

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\CompanyManager $companyManager */
            $companyManager   = $this->get('unilend.service.company_manager');
            $this->nature_var = $companyManager->getCompanyStatusNameByLabel($companyStatusHistory->getIdStatus()->getLabel());

            /** @var \echeanciers $repaymentSchedule */
            $repaymentSchedule = $this->loadData('echeanciers');
            $this->echu        = $repaymentSchedule->getNonRepaidAmountInDateRange($wallet->getId(), new \DateTime($this->oLoans->added), $expiration, $this->oLoans->id_loan);
            $this->echoir      = $repaymentSchedule->getTotalComingCapital($wallet->getId(), $this->oLoans->id_loan, $expiration);

            if ($debtCollectionStatus) {
                $clients = [$wallet->getIdClient()];

                if (false === empty($this->oLoans->id_transfer)) {
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LoanManager $loanManager */
                    $loanManager = $this->get('unilend.service.loan_manager');
                    $clients[]   = $loanManager->getFirstOwner($this->oLoans);
                }

                $loanRepository                    = $entityManager->getRepository('UnilendCoreBusinessBundle:Loans');
                $totalGrossDebtCollectionRepayment = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->getTotalGrossDebtCollectionRepayment($this->projects->id_project, $clients);
                $allLoans                          = $loanRepository->findLoansByClients($this->projects->id_project, $clients);
                $totalLoans                        = $loanRepository->getLoansSumByClients($this->projects->id_project, $clients);

                $debtCollectionGrossAmounts = [];
                foreach ($allLoans as $loan) {
                    $proportionDebtCollection                       = round(bcdiv(bcmul(bcdiv($loan->getAmount(), 100, 3), $totalGrossDebtCollectionRepayment, 3), $totalLoans, 3), 2);
                    $debtCollectionGrossAmounts[$loan->getIdLoan()] = $proportionDebtCollection;
                }

                $roundDifference = round(bcsub(array_sum($debtCollectionGrossAmounts), $totalGrossDebtCollectionRepayment, 3), 2);

                if (abs($roundDifference) > 0) {
                    $maxAmountLoanId                              = array_keys($debtCollectionGrossAmounts, max($debtCollectionGrossAmounts))[0];
                    $debtCollectionGrossAmounts[$maxAmountLoanId] = bcsub($debtCollectionGrossAmounts[$maxAmountLoanId], $roundDifference, 2);
                }

                $debtCollectionTaxIncl = $debtCollectionGrossAmounts[$this->oLoans->id_loan];
                $this->echu            = bcsub(bcadd($this->echu, $this->echoir, 2), $debtCollectionTaxIncl, 2);
                $this->echoir          = 0;
            }

            $this->total        = bcadd($this->echu, $this->echoir, 2);
            $lastEcheance       = $this->echeanciers->select('id_lender = ' . $wallet->getId() . ' AND id_loan = ' . $this->oLoans->id_loan, 'ordre DESC', 0, 1);
            $this->lastEcheance = date('d/m/Y', strtotime($lastEcheance[0]['date_echeance']));

            $this->contract->get($this->oLoans->id_type_contract);

            $this->setDisplay('declaration_de_creances_html');
        } else {
            header('Location: ' . $this->lurl);
        }
    }

    public function _loans()
    {
        if (false === isset($this->params[0])) {
            header('Location: ' . $this->lurl);
            exit;
        }

        /** @var \clients $oClients */
        $oClients = $this->loadData('clients');

        // hack the symfony guard token
        $session = $this->get('session');

        /** @var \Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken $token */
        $token =  unserialize($session->get('_security_default'));
        if (!$token instanceof \Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken) {
            header('Location: ' . $this->lurl);
            exit;
        }
        /** @var \Unilend\Bundle\FrontBundle\Security\User\UserLender $user */
        $user = $token->getUser();
        if (!$user instanceof \Unilend\Bundle\FrontBundle\Security\User\UserLender) {
            header('Location: ' . $this->lurl);
            exit;
        }

        if (false === $oClients->get($this->params[0], 'hash') || $user->getClientId() != $oClients->id_client) {
            header('Location: ' . $this->lurl);
            exit;
        }

        $sPath          = '/tmp/' . uniqid() . '/';
        $sNamePdfClient = 'vos_prets_' . date('Y-m-d_H:i:s');

        $this->lng['preteur-operations-detail'] = $this->ln->selectFront('preteur-operations-detail', $this->language, $this->App);
        $this->lng['preteur-operations-pdf']    = $this->ln->selectFront('preteur-operations-pdf', $this->language, $this->App);

        $this->GenerateLoansHtml($oClients->id_client);
        $this->WritePdf($sPath . $sNamePdfClient, 'operations');
        $this->ReadPdf($sPath . $sNamePdfClient, $sNamePdfClient);
    }

    private function GenerateLoansHtml($clientId)
    {
        $this->echeanciers = $this->loadData('echeanciers');
        $this->loans       = $this->loadData('loans');
        $this->clients     = $this->loadData('clients');
        $this->companies   = $this->loadData('companies');
        $this->clients->get($clientId);
        if (in_array($this->clients->type, [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
            $this->companies->get($this->clients->id_client, 'id_client_owner');
        }
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $wallet */
        $wallet            = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($clientId, WalletType::LENDER);
        $projectRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');

        $this->aProjectsInDebt = $this->projects->getProjectsInDebt();
        $this->lSumLoans       = $this->loans->getSumLoansByProject($wallet->getId(), 'debut DESC, p.title ASC');

        $this->aLoansStatuses = [
            'no-problem'            => 0,
            'late-repayment'        => 0,
            'recovery'              => 0,
            'collective-proceeding' => 0,
            'default'               => 0,
            'refund-finished'       => 0,
        ];
        /** @var LenderOperationsManager $lenderOperationManager */
        $lenderOperationManager = $this->get('unilend.service.lender_operations_manager');

        foreach ($this->lSumLoans as $iLoandIndex => $aProjectLoans) {
            $loanStatus = $lenderOperationManager->getLenderLoanStatusToDisplay($projectRepository->find($aProjectLoans['id_project']));
            $this->lSumLoans[$iLoandIndex]['statusLabel'] = $loanStatus['statusLabel'];
            $this->lSumLoans[$iLoandIndex]['loanStatus']  = $loanStatus;
        }

        $this->setDisplay('loans');
    }
}
