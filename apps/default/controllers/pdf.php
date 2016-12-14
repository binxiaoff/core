<?php

use Knp\Snappy\Pdf;
use Psr\Log\LoggerInterface;

class pdfController extends bootstrap
{
    /**
     * Path of tmp pdf file
     */
    const TMP_PATH_FILE = '/tmp/pdfUnilend/';

    /**
     * @var Pdf
     */
    private $oSnapPdf;

    /** @var LoggerInterface */
    private $oLogger;

    /**
     * @var projects_pouvoir
     */
    private $oProjectsPouvoir;

    /**
     * @var loans
     */
    public $oLoans;

    /**
     * @var lenders_accounts
     */
    public $oLendersAccounts;

    /**
     * @var echeanciers_emprunteur
     */
    private $oEcheanciersEmprunteur;

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

        $this->catchAll = true;

        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

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
            case 'invoice':
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/pdf/style.css');
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/pdf_facture/style.css');
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

    public function _mandat_preteur()
    {
        if ($this->clients->get($this->params[0], 'hash')) {
            $sFile          = $this->path . 'protected/pdf/mandat/mandat_preteur-' . $this->params[0] . '.pdf';
            $sNamePdfClient = 'MANDAT-UNILEND-' . $this->clients->id_client;

            if (false === file_exists($sFile)) {
                $this->GenerateWarrantyHtml();
                $this->WritePdf($sFile, 'warranty');
            }

            $this->ReadPdf($sFile, $sNamePdfClient);
        } else {
            header('Location: ' . $this->lurl);
            die;
        }
    }

    // mandat emprunteur
    public function _mandat()
    {
        if (false === isset($this->params[0], $this->params[1]) || false === is_numeric($this->params[1])) {
            header('Location: ' . $this->lurl);
            die;
        }

        $this->clients = $this->loadData('clients');
        /** @var \projects $project */
        $project = $this->loadData('projects');
        /** @var \companies $company */
        $company = $this->loadData('companies');

        if (
            $this->clients->get($this->params[0], 'hash')
            && $project->get($this->params[1], 'id_project')
            && $company->get($this->clients->id_client, 'id_client_owner')
            && $project->id_company == $company->id_company
            && $project->status != \projects_status::PRET_REFUSE
        ) {
            $path            = $this->path . 'protected/pdf/mandat/';
            $namePDFClient   = 'MANDAT-UNILEND-' . $project->slug . '-' . $this->clients->id_client;
            $mandates        = $this->loadData('clients_mandats');
            $projectMandates = $mandates->select(
                'id_project = ' . $project->id_project . ' AND id_client = ' . $this->clients->id_client . ' AND status IN (' . \clients_mandats::STATUS_PENDING . ',' . \clients_mandats::STATUS_SIGNED . ')',
                'id_mandat DESC'
            );

            if (false === empty($projectMandates)) {
                $mandate = array_shift($projectMandates);

                foreach ($projectMandates as $mandateToArchive) {
                    $mandates->get($mandateToArchive['id_mandat']);
                    $mandates->status = \clients_mandats::STATUS_ARCHIVED;
                    $mandates->update();
                }

                if (\clients_mandats::STATUS_SIGNED == $mandate['status']) {
                    $this->ReadPdf($path . $mandate['name'], $namePDFClient);
                    die;
                } elseif (\clients_mandats::STATUS_CANCELED == $mandate['status']) {
                    header('Location: ' . $this->lurl . '/espace_emprunteur/operations');
                    die;
                }

                $mandates->get($mandate['id_mandat']);
            } else {
                $mandates->id_client  = $this->clients->id_client;
                $mandates->url_pdf    = '/pdf/mandat/' . $this->params[0] . '/' . $this->params[1];
                $mandates->name       = 'mandat-' . $this->params[0] . '-' . $this->params[1] . '.pdf';
                $mandates->id_project = $project->id_project;
                $mandates->status     = \clients_mandats::STATUS_PENDING;
                $mandates->create();
            }

            if (false === file_exists($path . $mandates->name)) {
                $this->GenerateWarrantyHtml();
                $this->WritePdf($path . $mandates->name, 'warranty');
            }

            header('Location: ' . $this->url . '/universign/mandat/' . $mandates->id_mandat);
            die;
        } else {
            header('Location: ' . $this->lurl);
            die;
        }
    }

    private function GenerateWarrantyHtml()
    {
        $this->pays             = $this->loadData('pays');
        $this->oLendersAccounts = $this->loadData('lenders_accounts');

        $this->oLendersAccounts->get($this->clients->id_client, 'id_client_owner');
        $this->clients_adresses->get($this->clients->id_client, 'id_client');
        $this->pays->get($this->clients->id_langue, 'id_langue');

        if ($this->companies->get($this->clients->id_client, 'id_client_owner')) {
            $this->entreprise = true;

            $this->iban[1] = substr($this->companies->iban, 0, 4);
            $this->iban[2] = substr($this->companies->iban, 4, 4);
            $this->iban[3] = substr($this->companies->iban, 8, 4);
            $this->iban[4] = substr($this->companies->iban, 12, 4);
            $this->iban[5] = substr($this->companies->iban, 16, 4);
            $this->iban[6] = substr($this->companies->iban, 20, 4);
            $this->iban[7] = substr($this->companies->iban, 24, 3);

            $this->leIban = $this->companies->iban;
        } else {
            $this->entreprise = false;

            $this->iban[1] = substr($this->oLendersAccounts->iban, 0, 4);
            $this->iban[2] = substr($this->oLendersAccounts->iban, 4, 4);
            $this->iban[3] = substr($this->oLendersAccounts->iban, 8, 4);
            $this->iban[4] = substr($this->oLendersAccounts->iban, 12, 4);
            $this->iban[5] = substr($this->oLendersAccounts->iban, 16, 4);
            $this->iban[6] = substr($this->oLendersAccounts->iban, 20, 4);
            $this->iban[7] = substr($this->oLendersAccounts->iban, 24, 3);

            $this->leIban = $this->oLendersAccounts->iban;
        }

        // pour savoir si Preteur ou emprunteur
        if (isset($this->params[1]) && $this->projects->get($this->params[1], 'id_project')) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
            $oProjectManager = $this->get('unilend.service.project_manager');
            $this->motif = $oProjectManager->getBorrowerBankTransferLabel($this->projects);
        } else {
            $this->motif = $this->clients->getLenderPattern($this->clients->id_client);
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
        if (isset($this->params[0], $this->params[1]) && $this->clients->get($this->params[0], 'hash')) {
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            if (
                $this->projects->get($this->params[1], 'id_company = ' . $this->companies->id_company . ' AND id_project')
                && $this->projects->status != \projects_status::PRET_REFUSE
            ) {
                $this->oProjectsPouvoir = $this->loadData('projects_pouvoir');

                $signed        = false;
                $path          = $this->path . 'protected/pdf/pouvoir/';
                $namePdfClient = 'POUVOIR-UNILEND-' . $this->projects->slug . '-' . $this->clients->id_client;
                $fileName      = 'pouvoir-' . $this->params[0] . '-' . $this->params[1] . '.pdf';

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
                    if ($this->oProjectsPouvoir->status == \projects_pouvoir::STATUS_CANCELLED) {
                        header('Location: ' . $this->lurl . '/espace_emprunteur/operations');
                        die;
                    }

                    // si c'est un upload manuel du BO on affiche directement
                    if ($projectPouvoirToTreat['id_universign'] == 'no_universign' && file_exists($path . $projectPouvoirToTreat['name'])) {
                        $this->ReadPdf($path . $projectPouvoirToTreat['name'], $namePdfClient);
                        die;
                    }

                    $signed        = $projectPouvoirToTreat['status'] == \projects_pouvoir::STATUS_SIGNED;
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
                    $this->oProjectsPouvoir->url_pdf    = '/pdf/pouvoir/' . $this->params[0] . '/' . (isset($this->params[1]) ? $this->params[1] . '/' : '');
                    $this->oProjectsPouvoir->name       = $fileName;
                    $this->oProjectsPouvoir->id_pouvoir = $this->oProjectsPouvoir->create();
                    $this->oProjectsPouvoir->get($this->oProjectsPouvoir->id_pouvoir, 'id_pouvoir');
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
                    $this->generateProxyUniversign($instantCreate);
                } else {
                    $this->ReadPdf($path . $fileName, $namePdfClient);
                }

            } else {
                header('Location: ' . $this->lurl);
                die;
            }
        } else {
            header('Location: ' . $this->lurl);
            die;
        }
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
        $path           = $this->path . project_cgv::BASE_PATH;

        if ($oProjectCgv->get($iProjectId, 'id_project') && false === empty($oProjectCgv->name) && false === empty($oProjectCgv->id_tree)) {
            if ($sFileName !== $oProjectCgv->name) {
                header('Location: ' . $this->lurl);
                return;
            }

            // and if it's signed
            if (in_array($oProjectCgv->status, array(project_cgv::STATUS_SIGN_FO, project_cgv::STATUS_SIGN_UNIVERSIGN)) && file_exists($path . $oProjectCgv->name)) {
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
            $elements = $this->tree_elements->select('id_tree = "' . $oProjectCgv->id_tree . '" AND id_element = ' . elements::TYPE_PDF_CGU . ' AND id_langue = "' . $this->language . '"');

            if (false === isset($elements[0]['value']) || '' == $elements[0]['value']) {
                header('Location: ' . $this->lurl);
                return;
            }

            $sPdfPath = $this->path . 'public/default/var/fichiers/' . $elements[0]['value'];

            if (false === file_exists($sPdfPath)) {
                header('Location: ' . $this->lurl);
                return;
            }
            if (false === is_dir($this->path . project_cgv::BASE_PATH)) {
                mkdir($this->path . project_cgv::BASE_PATH, 0777, true);
            }
            if (false === file_exists($this->path . project_cgv::BASE_PATH . $oProjectCgv->name)) {
                copy($sPdfPath, $this->path . project_cgv::BASE_PATH . $oProjectCgv->name);
            }
        }

        header('Location: ' . $this->url . '/universign/cgv_emprunteurs/' . $oProjectCgv->id . '/' . $oProjectCgv->name);
    }

    private function generateProxyUniversign($bInstantCreate = false)
    {
        if (date('Y-m-d', strtotime($this->oProjectsPouvoir->updated)) == date('Y-m-d') && false === $bInstantCreate) {
            $regenerationUniversign = '/NoUpdateUniversign';
        } else {
            $regenerationUniversign = '';
            $this->oProjectsPouvoir->update();
        }

        header('Location: ' . $this->url . '/universign/pouvoir/' . $this->oProjectsPouvoir->id_pouvoir . $regenerationUniversign);
        exit;
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
        $this->oEcheanciersEmprunteur = $this->loadData('echeanciers_emprunteur');
        $this->oLendersAccounts       = $this->loadData('lenders_accounts');
        $this->oLoans                 = $this->loadData('loans');
        /** @var underlying_contract $contract */
        $contract                     = $this->loadData('underlying_contract');

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
        $this->lRemb            = $this->oEcheanciersEmprunteur->select('id_project = ' . $this->projects->id_project, 'ordre ASC');
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

        if (false === $clients->get($this->params[0], 'hash') || $user->getClientId() !== $clients->id_client && empty($_SESSION['user']['id_user'])) {
            header('Location: ' . $this->lurl);
            exit;
        }

        /** @var \loans $loans */
        $loans           = $this->loadData('loans');
        /** @var \lenders_accounts $lendersAccounts */
        $lendersAccounts = $this->loadData('lenders_accounts');
        /** @var \projects $projects */
        $projects        = $this->loadData('projects');

        if (false === $loans->get($this->params[1], 'id_loan')) {
            header('Location: ' . $this->lurl);
            exit;
        }

        if (false === $lendersAccounts->get($loans->id_lender, 'id_lender_account')) {
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
                /** @var \lenders_accounts $formerOwner */
                $formerOwner = $loanManager->getFirstOwner($loans);
                $clients->get($formerOwner->id_client_owner, 'id_client');
            }
            $this->GenerateContractHtml($clients, $loans, $projects);
            $this->WritePdf($filePath, 'contract');
        }

        $this->ReadPdf($filePath, $namePdfClient);
    }

    private function GenerateContractHtml($oClients, $oLoans, $oProjects)
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

        $this->settings->get('Commission remboursement', 'type');
        $fCommissionRate = $this->settings->value;

        /** @var \tax_type $taxType */
        $taxType = $this->loadData('tax_type');

        $taxRate = $taxType->getTaxRateByCountry('fr');
        $fVat    = $taxRate[\tax_type::TYPE_VAT] / 100;

        $this->aCommissionRepayment = \repayment::getRepaymentCommission($oLoans->amount / 100, $oProjects->period, $fCommissionRate, $fVat);
        $this->fCommissionRepayment = $this->aCommissionRepayment['commission_total'];

        /** @var \transactions $transaction */
        $transaction = $this->loadData('transactions');
        $transaction->get($oProjects->id_project, 'type_transaction = ' . \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT . ' AND id_project');

        $this->fCommissionProject = $transaction->montant_unilend;
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
        $this->lender          = $this->loadData('lenders_accounts');
        $this->preteur         = $this->loadData('clients');
        $this->preteurCompanie = $this->loadData('companies');
        $this->preteur_adresse = $this->loadData('clients_adresses');
        $this->echeanciers     = $this->loadData('echeanciers');

        if (isset($iIdLoan) && $this->oLoans->get($iIdLoan, 'status = "0" AND id_loan')) {
            $this->settings->get('Declaration contrat pret - adresse', 'type');
            $this->adresse = $this->settings->value;

            $this->settings->get('Declaration contrat pret - raison sociale', 'type');
            $this->raisonSociale = $this->settings->value;

            $this->projects->get($this->oLoans->id_project, 'id_project');
            $this->companiesEmp->get($this->projects->id_company, 'id_company');
            $this->emprunteur->get($this->companiesEmp->id_client_owner, 'id_client');
            $this->lender->get($this->oLoans->id_lender, 'id_lender_account');
            $this->preteur->get($this->lender->id_client_owner, 'id_client');
            $this->preteur_adresse->get($this->lender->id_client_owner, 'id_client');

            $this->lEcheances = array_values($this->echeanciers->getYearlySchedule(array('id_loan' => $this->oLoans->id_loan)));
            $this->lenderCountry = '';

            if ($this->preteur->type == \clients::TYPE_LEGAL_ENTITY) {
                $this->preteurCompanie->get($this->lender->id_company_owner, 'id_company');

                $this->nomPreteur     = $this->preteurCompanie->name;
                $this->adressePreteur = $this->preteurCompanie->adresse1;
                $this->cpPreteur      = $this->preteurCompanie->zip;
                $this->villePreteur   = $this->preteurCompanie->city;
            } else {
                if ($this->preteur_adresse->id_pays > 1) {
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

    public function _facture_EF($sHash = null, $iProjectId = null, $bRead = true)
    {
        $sHash      = (false === is_null($sHash)) ? $sHash : $this->params[0];
        $iProjectId = (false === is_null($iProjectId)) ? $iProjectId : $this->params[1];

        if ($this->clients->get($sHash, 'hash') && isset($iProjectId)) {
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            if ($this->projects->get($iProjectId, 'id_company = ' . $this->companies->id_company . ' AND id_project')) {
                $sNamePdfClient = 'FACTURE-UNILEND-' . $this->projects->slug;
                $sFileName      = $this->path . 'protected/pdf/facture/facture_EF-' . $sHash . '-' . $iProjectId . '.pdf';

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
                $projectManager = $this->get('unilend.service.project_manager');
                $this->commissionPercentage = $projectManager->getUnilendCommissionPercentage($this->projects);

                if (false === file_exists($sFileName)) {
                    $this->GenerateInvoiceEFHtml();
                    $this->WritePdf($sFileName, 'invoice');
                }

                if (true === $bRead) {
                    $this->ReadPdf($sFileName, $sNamePdfClient);
                }
            }
        }
    }

    private function GenerateFooterInvoice()
    {
        $this->lng['pdf-facture'] = $this->ln->selectFront('pdf-facture', $this->language, $this->App);

        $this->settings->get('titulaire du compte', 'type');
        $this->titreUnilend = mb_strtoupper($this->settings->value, 'UTF-8');

        $this->settings->get('Declaration contrat pret - raison sociale', 'type');
        $this->raisonSociale = mb_strtoupper($this->settings->value, 'UTF-8');

        $this->settings->get('Facture - SFF PME', 'type');
        $this->sffpme = mb_strtoupper($this->settings->value, 'UTF-8');

        $this->settings->get('Facture - capital', 'type');
        $this->capital = mb_strtoupper($this->settings->value, 'UTF-8');

        $this->settings->get('Declaration contrat pret - adresse', 'type');
        $this->raisonSocialeAdresse = mb_strtoupper($this->settings->value, 'UTF-8');

        $this->settings->get('Facture - telephone', 'type');
        $this->telephone = mb_strtoupper($this->settings->value, 'UTF-8');

        $this->settings->get('Facture - RCS', 'type');
        $this->rcs = mb_strtoupper($this->settings->value, 'UTF-8');

        $this->settings->get('Facture - TVA INTRACOMMUNAUTAIRE', 'type');
        $this->tvaIntra = mb_strtoupper($this->settings->value, 'UTF-8');


        $this->setDisplay('footer_facture');
    }

    private function GenerateInvoiceEFHtml()
    {
        $this->lng['pdf-facture'] = $this->ln->selectFront('pdf-facture', $this->language, $this->App);

        $this->factures                = $this->loadData('factures');
        $this->projects_status_history = $this->loadData('projects_status_history');

        $this->companies->get($this->clients->id_client, 'id_client_owner');

        $aInvoices = $this->factures->select('type_commission = ' . \factures::TYPE_COMMISSION_FINANCEMENT . ' AND id_company = ' . $this->companies->id_company . ' AND id_project = ' . $this->projects->id_project);

        if (empty($aInvoices)) {
            header('Location: ' . $this->lurl);
            die;
        }

        /** @var \tax_type $taxType */
        $taxType = $this->loadData('tax_type');

        $taxRate   = $taxType->getTaxRateByCountry('fr');
        $this->tva = $taxRate[\tax_type::TYPE_VAT] / 100;

        $aRepaymentDate           = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::REMBOURSEMENT . ')', 'added DESC, id_project_status_history DESC', 0, 1);
        $this->dateRemb           = $aRepaymentDate[0]['added'];
        $this->num_facture        = $aInvoices[0]['num_facture'];
        $this->ht                 = $aInvoices[0]['montant_ht'] / 100;
        $this->taxes              = $aInvoices[0]['tva'] / 100;
        $this->ttc                = $aInvoices[0]['montant_ttc'] / 100;
        $this->date_echeance_reel = $aInvoices[0]['date'];

        $this->setDisplay('facture_EF_html');
        $sDisplayInvoice = $this->sDisplay;
        $this->GenerateFooterInvoice();
        $this->sDisplay = $sDisplayInvoice . $this->sDisplay;
    }

    public function _facture_ER($sHash = null, $iProjectId = null, $iOrder = null, $bRead = true)
    {
        $sHash      = (false === is_null($sHash)) ? $sHash : $this->params[0];
        $iProjectId = (false === is_null($iProjectId)) ? $iProjectId : $this->params[1];
        $iOrder     = (false === is_null($iOrder)) ? $iOrder : $this->params[2];

        if ($this->clients->get($sHash, 'hash') && isset($iProjectId)) {
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            if ($this->projects->get($iProjectId, 'id_company = ' . $this->companies->id_company . ' AND id_project')) {
                $this->oEcheanciersEmprunteur = $this->loadData('echeanciers_emprunteur');

                if ($this->oEcheanciersEmprunteur->get($this->projects->id_project, 'ordre = ' . $iOrder . ' AND status_ra = 0  AND id_project')) {
                    $sNamePdfClient = 'FACTURE-UNILEND-' . $this->projects->slug . '-' . $iOrder;
                    $sFileName      = $this->path . 'protected/pdf/facture/facture_ER-' . $sHash . '-' . $iProjectId . '-' . $iOrder . '.pdf';

                    if (false === file_exists($sFileName)) {
                        $this->GenerateInvoiceERHtml($iOrder);
                        $this->WritePdf($sFileName, 'invoice');
                    }

                    if (true === $bRead) {
                        $this->ReadPdf($sFileName, $sNamePdfClient);
                    }
                }
            }
        }
    }

    private function GenerateInvoiceERHtml($iOrdre)
    {
        $this->lng['pdf-facture'] = $this->ln->selectFront('pdf-facture', $this->language, $this->App);

        $this->factures = $this->loadData('factures');

        $this->companies->get($this->clients->id_client, 'id_client_owner');

        $aInvoices = $this->factures->select('ordre = ' . $iOrdre . ' AND  type_commission = ' . \factures::TYPE_COMMISSION_REMBOURSEMENT . ' AND id_company = ' . $this->companies->id_company . ' AND id_project = ' . $this->projects->id_project);

        if (empty($aInvoices)) {
            header('Location: ' . $this->lurl);
            die;
        }

        /** @var \tax_type $taxType */
        $taxType = $this->loadData('tax_type');

        $taxRate   = $taxType->getTaxRateByCountry('fr');
        $this->tva = $taxRate[\tax_type::TYPE_VAT] / 100;

        $this->num_facture        = $aInvoices[0]['num_facture'];
        $this->ht                 = $aInvoices[0]['montant_ht'] / 100;
        $this->taxes              = $aInvoices[0]['tva'] / 100;
        $this->ttc                = $aInvoices[0]['montant_ttc'] / 100;
        $this->date_echeance_reel = $aInvoices[0]['date'];

        $this->setDisplay('facture_ER_html');
        $sDisplayInvoice = $this->sDisplay;
        $this->GenerateFooterInvoice();
        $this->sDisplay = $sDisplayInvoice . $this->sDisplay;
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
        /** @var \lenders_accounts $lendersAccounts */
        $lendersAccounts = $this->loadData('lenders_accounts');
        /** @var \projects $projects */
        $projects = $this->loadData('projects');

        if (false === $loans->get($this->params[1], 'id_loan')) {
            header('Location: ' . $this->lurl);
            exit;
        }

        if (false === $lendersAccounts->get($loans->id_lender, 'id_lender_account')) {
            header('Location: ' . $this->lurl);
            exit;
        }

        if (false === $projects->get($loans->id_project, 'id_project')) {
            header('Location: ' . $this->lurl);
            exit;
        }

        $clients->get($lendersAccounts->id_client_owner, 'id_client');

        $filePath      = $this->path . 'protected/pdf/declaration_de_creances/' . $loans->id_project . '/';
        $filePath      = ($loans->id_project == '1456') ? $filePath : $filePath . $clients->id_client . '/';
        $filePath      = $filePath . 'declaration-de-creances' . '-' . $clients->hash . '-' . $loans->id_loan . '.pdf';
        $namePdfClient = 'DECLARATION-DE-CREANCES-UNILEND-' . $clients->hash . '-' . $loans->id_loan;

        if (false === file_exists($filePath)) {
            $this->GenerateClaimsHtml($clients, $loans, $projects);
            $this->WritePdf($filePath, 'claims');
        }

        $this->ReadPdf($filePath, $namePdfClient);
    }

    private function GenerateClaimsHtml(\clients $client, \loans $loan, \projects $projects)
    {
        /** @var \loans oLoans */
        $this->oLoans = $loan;
        /** @var \clients clients */
        $this->clients = $client;
        /** @var \projects projects */
        $this->projects = $projects;

        /** @var \pays_v2 pays */
        $this->pays = $this->loadData('pays_v2');
        /** @var \echeanciers echeanciers */
        $this->echeanciers = $this->loadData('echeanciers');
        /** @var \companies companiesEmpr */
        $this->companiesEmpr = $this->loadData('companies');
        /** @var \projects_status_history projects_status_history */
        $this->projects_status_history = $this->loadData('projects_status_history');
        /** @var \projects_status_history_details projects_status_history_details */
        $this->projects_status_history_details = $this->loadData('projects_status_history_details');
        /** @var underlying_contract contract */
        $this->contract = $this->loadData('underlying_contract');
        /** @var \Symfony\Component\Translation\TranslatorInterface translator */
        $this->translator = $this->get('translator');
        /** @var \lenders_accounts oLendersAccounts */
        $this->oLendersAccounts = $this->loadData('lenders_accounts');
        $this->oLendersAccounts->get($this->oLoans->id_lender, 'id_lender_account');

        $status = [
            \projects_status::PROCEDURE_SAUVEGARDE,
            \projects_status::REDRESSEMENT_JUDICIAIRE,
            \projects_status::LIQUIDATION_JUDICIAIRE
        ];

        if (in_array($this->projects->status, $status)
        ) {
            $this->companiesEmpr->get($this->projects->id_company, 'id_company');

            if (in_array($this->clients->type, [\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER])) {
                $this->clients_adresses->get($this->clients->id_client, 'id_client');
                $iCountryId = $this->clients_adresses->id_pays_fiscal;
            } else {
                $this->companies->get($this->clients->id_client, 'id_client_owner');
                $iCountryId = $this->companies->id_pays;
            }

            if ($iCountryId == 0) {
                $iCountryId = 1;
            }

            $this->pays->get($iCountryId, 'id_pays');
            $this->pays_fiscal = $this->pays->fr;

            /** @var \projects_status_history $projectStatusHistory */
            $projectStatusHistory = $this->loadData('projects_status_history');
            $projectStatusHistory->loadStatusForJudgementDate($this->projects->id_project, $status);

            /** @var \projects_status_history_details $projectStatusHistoryDetails */
            $projectStatusHistoryDetails = $this->loadData('projects_status_history_details');
            $projectStatusHistoryDetails->get($projectStatusHistory->id_project_status_history, 'id_project_status_history');

            $this->date            = \DateTime::createFromFormat('Y-m-d', $projectStatusHistoryDetails->date);
            $this->mandataires_var = $projectStatusHistoryDetails->receiver;

            /** @var projects_status $projectStatusType */
            $projectStatusType = $this->loadData('projects_status');
            $projectStatusType->get(\projects_status::RECOUVREMENT, 'status');

            $recoveryStatus = $projectStatusHistory->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = ' . $projectStatusType->id_project_status, '', '', 1);
            if (false === empty($recoveryStatus)) {
                $expiration = \DateTime::createFromFormat('Y-m-d H:i:s', $recoveryStatus[0]['added']);
            } else {
                $expiration = $this->date;
            }

            $projectStatusType->get($projectStatusHistory->id_project_status);
            // @todo intl
            $this->nature_var = '';
            switch ($projectStatusType->status) {
                case \projects_status::PROCEDURE_SAUVEGARDE:
                    $this->nature_var = 'Procédure de sauvegarde';
                    break;
                case \projects_status::REDRESSEMENT_JUDICIAIRE:
                    $this->nature_var = 'Redressement judiciaire';
                    break;
                case \projects_status::LIQUIDATION_JUDICIAIRE:
                    $this->nature_var = 'Liquidation judiciaire';
                    break;
            }

            /** @var \echeanciers $repaymentSchedule */
            $repaymentSchedule = $this->loadData('echeanciers');
            $this->echu   = $repaymentSchedule->getNonRepaidAmountInDateRange($this->oLendersAccounts->id_lender_account, new \DateTime($this->oLoans->added), $expiration, $this->oLoans->id_loan);
            $this->echoir = $repaymentSchedule->getTotalComingCapital($this->oLendersAccounts->id_lender_account, $this->oLoans->id_loan, $expiration);

            if (false === empty($recoveryStatus)) {
                /** @var \transactions $transaction */
                $transaction     = $this->loadData('transactions');
                $where           = 'id_client = ' . $this->oLendersAccounts->id_client_owner . ' AND id_project = ' . $this->projects->id_project . ' AND type_transaction = ' . \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT;

                if (false === empty($this->oLoans->id_transfer)) {
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LoanManager $loanManager */
                    $loanManager = $this->get('unilend.service.loan_manager');
                    /** @var \lenders_accounts $formerOwner */
                    $formerOwner = $loanManager->getFirstOwner($this->oLoans);
                    $where           = 'id_client IN (' . implode(',', [$this->oLendersAccounts->id_client_owner, $formerOwner->id_client_owner]) . ') AND id_project = ' . $this->projects->id_project . ' AND type_transaction = ' . \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT;
                }

                $totalAmountRecovered = bcdiv($transaction->sum($where, 'montant'), 100, 2);
                $allLoans             = $this->oLoans->select('id_lender = ' . $this->oLendersAccounts->id_lender_account . ' AND id_project = ' . $this->projects->id_project);
                $totalLoans           = $this->oLoans->sumPretsProjet($this->projects->id_project . ' AND id_lender = ' . $this->oLendersAccounts->id_lender_account);

                $recoveryAmountsTaxExcl = [];
                foreach ($allLoans as $index => $loan) {
                    $prorataRecovery                          = round(bcdiv(bcmul(bcdiv($loan['amount'], 100, 3), $totalAmountRecovered), $totalLoans, 3), 2);
                    $recoveryAmountsTaxExcl[$loan['id_loan']] = $prorataRecovery;
                }

                $roundDifference = round(bcsub(array_sum($recoveryAmountsTaxExcl), $totalAmountRecovered, 3), 2);
                if (abs($roundDifference) > 0) {
                    $maxAmountLoanId                          = array_keys($recoveryAmountsTaxExcl, max($recoveryAmountsTaxExcl))[0];
                    $recoveryAmountsTaxExcl[$maxAmountLoanId] = bcsub($recoveryAmountsTaxExcl[$maxAmountLoanId], $roundDifference, 2);
                }

                $recoveryTaxExcl = $recoveryAmountsTaxExcl[$this->oLoans->id_loan];
                // 0.844 is the rate for getting the total amount including the MCS commission and tax. Todo: replace it when doing the Recovery project
                $recoveryTaxIncl = bcdiv($recoveryTaxExcl, 0.844, 5);
                $this->echu      = bcsub(bcadd($this->echu, $this->echoir, 2), $recoveryTaxIncl, 2);
                $this->echoir    = 0;
            }

            $this->total        = bcadd($this->echu, $this->echoir, 2);
            $lastEcheance       = $this->echeanciers->select('id_lender = ' . $this->oLendersAccounts->id_lender_account . ' AND id_loan = ' . $this->oLoans->id_loan, 'ordre DESC', 0, 1);
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

        if (false === $oClients->get($this->params[0], 'hash') || $user->getClientId() !== $oClients->id_client) {
            header('Location: ' . $this->lurl);
            exit;
        }

        $sPath          = '/tmp/' . uniqid() . '/';
        $sNamePdfClient = 'vos_prets_' . date('Y-m-d_H:i:s') . '.pdf';

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
        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $this->lenders_accounts->get($clientId, 'id_client_owner');

        $this->aProjectsInDebt = $this->projects->getProjectsInDebt();
        $this->lSumLoans       = $this->loans->getSumLoansByProject($this->lenders_accounts->id_lender_account, 'debut DESC, p.title ASC');

        $this->aLoansStatuses = array(
            'no-problem'            => 0,
            'late-repayment'        => 0,
            'recovery'              => 0,
            'collective-proceeding' => 0,
            'default'               => 0,
            'refund-finished'       => 0,
        );

        foreach ($this->lSumLoans as $iLoandIndex => $aProjectLoans) {
            switch ($aProjectLoans['project_status']) {
                case \projects_status::PROBLEME:
                case \projects_status::PROBLEME_J_X:
                    $this->lSumLoans[$iLoandIndex]['status-color'] = 'warning';
                    ++$this->aLoansStatuses['late-repayment'];
                    break;
                case \projects_status::RECOUVREMENT:
                    $this->lSumLoans[$iLoandIndex]['status-color'] = 'problem';
                    ++$this->aLoansStatuses['recovery'];
                    break;
                case \projects_status::PROCEDURE_SAUVEGARDE:
                case \projects_status::REDRESSEMENT_JUDICIAIRE:
                case \projects_status::LIQUIDATION_JUDICIAIRE:
                    $this->lSumLoans[$iLoandIndex]['status-color'] = 'problem';
                    ++$this->aLoansStatuses['collective-proceeding'];
                    break;
                case \projects_status::DEFAUT:
                    $this->lSumLoans[$iLoandIndex]['status-color'] = 'default';
                    ++$this->aLoansStatuses['default'];
                    break;
                case \projects_status::REMBOURSE:
                case \projects_status::REMBOURSEMENT_ANTICIPE:
                    $this->lSumLoans[$iLoandIndex]['status-color'] = '';
                    ++$this->aLoansStatuses['refund-finished'];
                    break;
                case \projects_status::REMBOURSEMENT:
                default:
                    $this->lSumLoans[$iLoandIndex]['status-color'] = '';
                    ++$this->aLoansStatuses['no-problem'];
                    break;
            }
        }

        $this->setDisplay('loans');
    }

    public function _vos_operations_pdf_indexation()
    {
        /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
        $session = $this->get('session');

        if ($session->has('lenderOperationsFilters')) {
            $savedFilters   = $session->get('lenderOperationsFilters');
            $sPath          = $this->path . 'protected/operations_export_pdf/' . $savedFilters['id_client'] . '/';
            $sNamePdfClient = 'vos_operations_' . date('Y-m-d') . '.pdf';

            $this->GenerateOperationsHtml($savedFilters);
            $this->WritePdf($sPath . $sNamePdfClient, 'operations');
            $this->ReadPdf($sPath . $sNamePdfClient, $sNamePdfClient);
        }
    }

    private function GenerateOperationsHtml(array $savedFilters)
    {
        /** @var $this->recoveryManager recoveryManager */
        $this->recoveryManager         = $this->get('unilend.service.recovery_manager');
        $this->echeanciers             = $this->loadData('echeanciers');
        $this->oLendersAccounts        = $this->loadData('lenders_accounts');
        $this->indexage_vos_operations = $this->loadData('indexage_vos_operations');

        $this->lng['preteur-operations-vos-operations'] = $this->ln->selectFront('preteur-operations-vos-operations', $this->language, $this->App);
        $this->lng['preteur-operations-pdf']            = $this->ln->selectFront('preteur-operations-pdf', $this->language, $this->App);

        $tri_type_transac = \Unilend\Bundle\FrontBundle\Controller\LenderOperationsController::$transactionTypeList[$savedFilters['operation']];
        $tri_project      = empty($savedFilters['project']) ? '' : ' AND id_projet = ' . $savedFilters['project'];
        $id_client        = $savedFilters['id_client'];

        $this->clients->get($id_client, 'id_client');
        $this->clients_adresses->get($id_client, 'id_client');
        $this->oLendersAccounts->get($id_client, 'id_client_owner');

        $this->date_debut     = $savedFilters['startDate']->format('Y-m-d');
        $this->date_fin       = $savedFilters['endDate']->format('Y-m-d');
        $this->lTrans         = $this->indexage_vos_operations->select('type_transaction IN (' . implode(', ', $tri_type_transac) . ') AND id_client = ' . $this->clients->id_client . ' AND DATE(date_operation) >= "' . $this->date_debut . '" AND DATE(date_operation) <= "' . $this->date_fin . '"' . $tri_project, 'date_operation DESC, id_transaction DESC');
        $this->lProjectsLoans = $this->indexage_vos_operations->get_liste_libelle_projet('type_transaction IN (' . implode(', ', $tri_type_transac) . ') AND id_client = ' . $this->clients->id_client . ' AND LEFT(date_operation,10) >= "' . $this->date_debut . '" AND LEFT(date_operation,10) <= "' . $this->date_fin . '"');

        $this->setDisplay('vos_operations_pdf_html_indexation');
    }
}
