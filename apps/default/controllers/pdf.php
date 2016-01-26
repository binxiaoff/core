<?php

use Unilend\librairies\ULogger;
use Knp\Snappy\Pdf;

class pdfController extends bootstrap
{
    /**
     * File's name for logger
     */
    const NAME_LOG = 'pdf.log';

    /**
     * Path of tmp pdf file
     */
    const TMP_PATH_FILE = '/tmp/pdfUnilend/';

    /**
     * @var Pdf
     */
    private $oSnapPdf;

    /**
     * @var ULogger
     */
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

    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        if (false === isset($this->params)) {
            $this->params = $command->getParameters();
        }

        $this->catchAll = true;

        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $this->oSnapPdf = new Pdf('/usr/bin/wkhtmltopdf');
        $this->oLogger  = new ULogger('PdfManagement', $this->logPath, self::NAME_LOG);
    }

    /**
     * @param string $sView name of view file
     */
    public function setDisplay($sView = '', $sContent = null)
    {
        $this->content = (false === is_null($sContent)) ? $sContent : '';
        $this->view    = $sView;

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

        $this->oLogger->addRecord(ULogger::INFO, 'End generation of ' . $sTypePdf . ' pdf in ' . round($iTimeEndPdf, 2),
            array(__FILE__ . ' on line ' . __LINE__));
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

        header("Content-disposition: attachment; filename=" . $sNamePdf . ".pdf");
        header("Content-Type: application/force-download");
        if (!readfile($sPathPdf)) {
            $this->oLogger->addRecord(ULogger::DEBUG, 'File : ' . $sPathPdf . ' not readable.',
                array(__FILE__ . ' on line ' . __LINE__));
        }
    }

    public function _mandat_preteur()
    {
        if ($this->clients->get($this->params[0], 'hash')) {
            $sFile          = $this->path . 'protected/pdf/mandat/mandat_preteur-' . $this->params[0] . '.pdf';
            $sNamePdfClient = 'MANDAT-UNILEND-' . $this->clients->id_client;

            if (false === file_exists($sFile)) {
                $this->generateWarrantyHtml($this->clients);
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

        $oClients   = $this->loadData('clients');
        $oProjects  = $this->loadData('projects');
        $oCompanies = $this->loadData('companies');

        $sClientHash = $this->params[0];
        $iProjectId  = $this->params[1];

        if ($oClients->get($sClientHash, 'hash')
            && $oProjects->get($iProjectId, 'id_project')
            && $oCompanies->get($oClients->id_client, 'id_client_owner')
            && $oProjects->id_company == $oCompanies->id_company
        ) {
            $sPath           = $this->path . 'protected/pdf/mandat/';
            $sNamePdfClient  = 'MANDAT-UNILEND-' . $oProjects->slug . '-' . $oClients->id_client;
            $oClientsMandats = $this->loadData('clients_mandats');
            $aMandats        = $oClientsMandats->select(
                'id_project = ' . $iProjectId . ' AND id_client = ' . $oClients->id_client . ' AND status in (' . clients_mandats::STATUS_PENDING . ',' . clients_mandats::STATUS_SIGNED . ')',
                'id_mandat DESC'
            );

            if (false === empty($aMandats)) {
                $aMandat = array_shift($aMandats);

                foreach ($aMandats as $aMandatToArchive) {
                    $oClientsMandats->get($aMandatToArchive['id_mandat']);
                    $oClientsMandats->status = clients_mandats::STATUS_ARCHIVED;
                    $oClientsMandats->update();
                }

                $oClientsMandats->get($aMandat['id_mandat']);

                if (clients_mandats::STATUS_SIGNED == $oClientsMandats->status) {
                    $this->ReadPdf($oClientsMandats->name, $sNamePdfClient);
                    return;
                }
            } else {
                $sFileName = 'mandat-' . $sClientHash . '-' . $iProjectId . '.pdf';

                $oClientsMandats->id_client  = $oClients->id_client;
                $oClientsMandats->url_pdf    = '/pdf/mandat/' . $sClientHash . '/' . $iProjectId;
                $oClientsMandats->name       = $sFileName;
                $oClientsMandats->id_project = $oProjects->id_project;
                $oClientsMandats->status     = clients_mandats::STATUS_PENDING;
                $oClientsMandats->create();
            }

            if (false === file_exists($sPath . $oClientsMandats->name)) {
                $this->generateWarrantyHtml($oClients, $oCompanies);
                $this->WritePdf($sPath . $oClientsMandats->name, 'warranty');
            }

            header('Location: ' . $this->url . '/universign/mandat/' . $oClientsMandats->id_mandat);
            return;
        } else {
            header('Location: ' . $this->lurl);
            die;
        }
    }

    private function generateWarrantyHtml($oClients)
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
            $p           = substr($this->ficelle->stripAccents(utf8_decode($this->clients->prenom)), 0, 1);
            $nom         = $this->ficelle->stripAccents(utf8_decode($this->clients->nom));
            $id_project  = str_pad($this->projects->id_project, 6, 0, STR_PAD_LEFT);
            $this->motif = mb_strtoupper($id_project . 'E' . $p . preg_replace('/\s/', '', $nom), 'UTF-8');
            $this->motif = $this->ficelle->str_split_unicode('UNILEND' . $this->motif);
        } else {
            $p           = substr($this->ficelle->stripAccents(utf8_decode($this->clients->prenom)), 0, 1);
            $nom         = $this->ficelle->stripAccents(utf8_decode($this->clients->nom));
            $id_client   = str_pad($this->clients->id_client, 6, 0, STR_PAD_LEFT);
            $this->motif = mb_strtoupper($id_client . 'P' . $p . $nom, 'UTF-8');
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

        $this->clients = $oClients;

        $this->setDisplay('mandat_html');
    }

    public function _pouvoir()
    {
        if (isset($this->params[0], $this->params[1]) && $this->clients->get($this->params[0], 'hash')) {
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            if ($this->projects->get($this->params[1], 'id_company = ' . $this->companies->id_company . ' AND id_project')) {
                $this->oProjectsPouvoir = $this->loadData('projects_pouvoir');

                $bSign          = false;
                $sPath          = $this->path . 'protected/pdf/pouvoir/';
                $sNamePdfClient = 'POUVOIR-UNILEND-' . $this->projects->slug . '-' . $this->clients->id_client;
                $sFileName      = 'pouvoir-' . $this->params[0] . '-' . $this->params[1] . '.pdf';

                $aProjectPouvoir        = $this->oProjectsPouvoir->select('id_project = ' . $this->projects->id_project, 'added ASC');
                $aProjectPouvoirToTreat = (is_array($aProjectPouvoir) && false === empty($aProjectPouvoir)) ? array_shift($aProjectPouvoir) : null;

                // Deleting authority, not necessary (Double authority)
                if (is_array($aProjectPouvoir) && 0 < count($aProjectPouvoir)) {
                    foreach ($aProjectPouvoir as $aProjectPouvoirToDelete) {
                        $this->oLogger->addRecord(ULogger::INFO, 'Deleting Pouvoir id : ' . $aProjectPouvoirToDelete['id_pouvoir'], array(__FILE__ . ' at line ' . __LINE__));
                        $this->oProjectsPouvoir->delete($aProjectPouvoirToDelete['id_pouvoir'], 'id_pouvoir'); // plus de doublons comme ca !
                    }
                }

                if (false === is_null($aProjectPouvoirToTreat)) {
                    // si c'est un upload manuel du BO on affiche directement
                    if ($aProjectPouvoirToTreat['id_universign'] == 'no_universign' && file_exists($sPath . $aProjectPouvoirToTreat['name'])) {
                        $this->ReadPdf($sPath . $aProjectPouvoirToTreat['name'], $sNamePdfClient);
                        die;
                    }

                    // Si pouvoir signé
                    $bSign          = ($aProjectPouvoirToTreat['status'] > 0) ? true : false;
                    $bInstantCreate = false;

                    if (false === file_exists($sPath . $aProjectPouvoirToTreat['name'])) {
                        $this->GenerateAuthorityHtml();
                        $this->WritePdf($sPath . $aProjectPouvoirToTreat['name'], 'authority');
                        $bSign          = false;
                        $bInstantCreate = true;
                    }

                    $this->oProjectsPouvoir->get($aProjectPouvoirToTreat['id_pouvoir'], 'id_pouvoir');

                } else { // Si pas de pouvoir on créer une ligne
                    $this->GenerateAuthorityHtml();
                    $this->WritePdf($sPath . $sFileName, 'authority');

                    $this->oProjectsPouvoir->id_project = $this->projects->id_project;
                    $this->oProjectsPouvoir->url_pdf    = '/pdf/pouvoir/' . $this->params[0] . '/' . (isset($this->params[1]) ? $this->params[1] . '/' : '');
                    $this->oProjectsPouvoir->name       = $sFileName;
                    $this->oProjectsPouvoir->id_pouvoir = $this->oProjectsPouvoir->create();
                    $this->oProjectsPouvoir->get($this->oProjectsPouvoir->id_pouvoir, 'id_pouvoir');
                    $bInstantCreate = true;
                }

                if (false === $bSign) {
                    if (file_exists($sPath . $sFileName) && filesize($sPath . $sFileName) > 0 && date('Y-m-d', filemtime($sPath . $sFileName)) != date('Y-m-d')) {
                        unlink($sPath . $sFileName);
                        $this->oLogger->addRecord(ULogger::INFO, 'File : ' . $sPath . $sFileName . ' deleting.', array(__FILE__ . ' on line ' . __LINE__));

                        $this->GenerateAuthorityHtml();
                        $this->WritePdf($sPath . $sFileName, 'authority');
                        $bInstantCreate = true;
                    }
                    $this->generateAuthorityUniversign($bInstantCreate);
                } else { //Si pouvoir signé
                    $this->ReadPdf($sPath . $sFileName, $sNamePdfClient);
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

    private function generateAuthorityUniversign($bInstantCreate = false)
    {
        /**
        /* On met a jour les dates d'echeances
        /* en se basant sur la date de creation du pouvoir
        */
        if (date('Y-m-d', strtotime($this->oProjectsPouvoir->updated)) == date('Y-m-d') && false === $bInstantCreate) {
            $regenerationUniversign = '/NoUpdateUniversign'; // On crée pas de nouveau universign
        } // Ici on creera un nouveau universign car la date est différente
        else {
            $regenerationUniversign = '';
            // On met a jour la date des echeances en se basant sur la date de signature du pouvoir c'est a dire aujourd'hui
            $this->updateEcheances($this->oProjectsPouvoir->id_project, date('Y-m-d H:i:s'));
            $this->oProjectsPouvoir->update();
        }

        header('Location: ' . $this->url . '/universign/pouvoir/' . $this->oProjectsPouvoir->id_pouvoir . $regenerationUniversign);
    }

    private function GenerateAuthorityHtml()
    {
        $this->lng['pdf-pouvoir'] = $this->ln->selectFront('pdf-pouvoir', $this->language, $this->App);

        $this->blocs->get('pouvoir', 'slug');
        $lElements = $this->blocs_elements->select('id_bloc = ' . $this->blocs->id_bloc . ' AND id_langue = "' . $this->language . '"');
        foreach ($lElements as $b_elt) {
            $this->elements->get($b_elt['id_element']);
            $this->bloc_pouvoir[$this->elements->slug]           = $b_elt['value'];
            $this->bloc_pouvoirComplement[$this->elements->slug] = $b_elt['complement'];
        }

        $this->companies_details      = $this->loadData('companies_details');
        $this->oLoans                 = $this->loadData('loans');
        $this->echeanciers            = $this->loadData('echeanciers');
        $this->oEcheanciersEmprunteur = $this->loadData('echeanciers_emprunteur');
        $this->oLendersAccounts       = $this->loadData('lenders_accounts');
        $this->companies_actif_passif = $this->loadData('companies_actif_passif');

        $this->companies_details->get($this->companies->id_company, 'id_company');

        $date_dernier_bilan             = explode('-', $this->companies_details->date_dernier_bilan);
        $this->date_dernier_bilan_annee = $date_dernier_bilan[0];
        $this->date_dernier_bilan_mois  = $date_dernier_bilan[1];
        $this->date_dernier_bilan_jour  = $date_dernier_bilan[2];

        $this->montantPrete = $this->projects->amount;

        $montantHaut = 0;
        $montantBas  = 0;
        foreach ($this->oLoans->select('id_project = ' . $this->projects->id_project) as $b) {
            $montantHaut += ($b['rate'] * ($b['amount'] / 100));
            $montantBas += ($b['amount'] / 100);
        }

        $this->taux             = (0 < $montantBas) ? ($montantHaut / $montantBas) : 0;
        $this->nbLoansBDC       = $this->oLoans->counter('id_type_contract = 1 AND id_project = ' . $this->projects->id_project);
        $this->nbLoansIFP       = $this->oLoans->counter('id_type_contract = 2 AND id_project = ' . $this->projects->id_project);
        $this->echeanceEmprun   = $this->oEcheanciersEmprunteur->select('id_project = ' . $this->projects->id_project . ' AND ordre = 1');
        $this->rembByMonth      = $this->echeanciers->getMontantRembEmprunteur($this->echeanceEmprun[0]['montant'], $this->echeanceEmprun[0]['commission'], $this->echeanceEmprun[0]['tva']);
        $this->rembByMonth      = ($this->rembByMonth / 100);
        $this->dateLastEcheance = $this->echeanciers->getDateDerniereEcheancePreteur($this->projects->id_project);
        $this->lRemb            = $this->oEcheanciersEmprunteur->select('id_project = ' . $this->projects->id_project, 'ordre ASC');

        $this->capital = 0;
        foreach ($this->lRemb as $r) {
            $this->capital += $r['capital'];
        }

        $this->l_AP        = $this->companies_actif_passif->select('id_company = "' . $this->companies->id_company . '" AND annee = ' . $this->date_dernier_bilan_annee, 'annee DESC');
        $this->totalActif  = ($this->l_AP[0]['immobilisations_corporelles'] + $this->l_AP[0]['immobilisations_incorporelles'] + $this->l_AP[0]['immobilisations_financieres'] + $this->l_AP[0]['stocks'] + $this->l_AP[0]['creances_clients'] + $this->l_AP[0]['disponibilites'] + $this->l_AP[0]['valeurs_mobilieres_de_placement']);
        $this->totalPassif = ($this->l_AP[0]['capitaux_propres'] + $this->l_AP[0]['provisions_pour_risques_et_charges'] + $this->l_AP[0]['amortissement_sur_immo'] + $this->l_AP[0]['dettes_financieres'] + $this->l_AP[0]['dettes_fournisseurs'] + $this->l_AP[0]['autres_dettes']);
        $this->lLenders    = $this->oLoans->select('id_project = ' . $this->projects->id_project, 'rate ASC');
        $this->dateRemb    = date('d/m/Y');

        $this->setDisplay('pouvoir_html');
    }

    public function _contrat()
    {
        if (false === isset($this->params[0], $this->params[1])) {
            header('Location: ' . $this->lurl);
            exit;
        }

        $oClients = $this->loadData('clients');

        if (false === $oClients->get($this->params[0], 'hash') || false === $oClients->checkAccess() && (false === isset($_SESSION['user']['id_user']) || $_SESSION['user']['id_user'] == '')) {
            header('Location: ' . $this->lurl);
            exit;
        }

        $oLoans           = $this->loadData('loans');
        $oLendersAccounts = $this->loadData('lenders_accounts');
        $oProjects        = $this->loadData('projects');

        if (false === $oLendersAccounts->get($oClients->id_client, 'id_client_owner')) {
            header('Location: ' . $this->lurl);
            exit;
        }

        if (false === $oLoans->get($this->params[1], 'id_lender = ' . $oLendersAccounts->id_lender_account . ' AND id_loan')) {
            header('Location: ' . $this->lurl);
            exit;
        }

        if (false === $oProjects->get($oLoans->id_project, 'id_project')) {
            header('Location: ' . $this->lurl);
            exit;
        }

        $sNamePdfClient = 'CONTRAT-UNILEND-' . $oProjects->slug . '-' . $oLoans->id_loan;
        $sFilePath      = $this->path . 'protected/pdf/contrat/contrat-' . $this->params[0] . '-' . $oLoans->id_loan . '.pdf';

        if (false === file_exists($sFilePath)) {
            $this->GenerateContractHtml($oClients, $oLoans, $oProjects);
            $this->WritePdf($sFilePath, 'contract');
        }

        $this->ReadPdf($sFilePath, $sNamePdfClient);
    }

    private function GenerateContractHtml($oClients, $oLoans, $oProjects)
    {
        $this->echeanciers                 = $this->loadData('echeanciers');
        $this->companiesEmprunteur         = $this->loadData('companies');
        $this->companies_detailsEmprunteur = $this->loadData('companies_details');
        $this->companiesPreteur            = $this->loadData('companies');
        $this->emprunteur                  = $this->loadData('clients');
        $this->companies_actif_passif      = $this->loadData('companies_actif_passif');
        $this->projects_status_history     = $this->loadData('projects_status_history');
        $this->oProjectsPouvoir            = $this->loadData('projects_pouvoir');
        $this->clients_adresses            = $this->loadData('clients_adresses');
        $this->oLoans                      = $oLoans;
        $this->clients                     = $oClients;
        $this->projects                    = $oProjects;

        $this->clients_adresses->get($this->clients->id_client, 'id_client');
        $this->companiesEmprunteur->get($oProjects->id_company, 'id_company');
        $this->companies_detailsEmprunteur->get($oProjects->id_company, 'id_company');
        $this->emprunteur->get($this->companiesEmprunteur->id_client_owner, 'id_client');
        // Si preteur morale
        if ($this->clients->type == 2) {
            $this->companiesPreteur->get($this->clients->id_client, 'id_client_owner');
        }
        $this->dateLastEcheance = $this->echeanciers->getDateDerniereEcheancePreteur($oProjects->id_project);

        $date_dernier_bilan             = explode('-', $this->companies_detailsEmprunteur->date_dernier_bilan);
        $this->date_dernier_bilan_annee = $date_dernier_bilan[0];
        $this->date_dernier_bilan_mois  = $date_dernier_bilan[1];
        $this->date_dernier_bilan_jour  = $date_dernier_bilan[2];

        $this->l_AP        = $this->companies_actif_passif->select('id_company = "' . $this->companiesEmprunteur->id_company . '" AND annee = ' . $this->date_dernier_bilan_annee, 'annee DESC');
        $this->totalActif  = ($this->l_AP[0]['immobilisations_corporelles'] + $this->l_AP[0]['immobilisations_incorporelles'] + $this->l_AP[0]['immobilisations_financieres'] + $this->l_AP[0]['stocks'] + $this->l_AP[0]['creances_clients'] + $this->l_AP[0]['disponibilites'] + $this->l_AP[0]['valeurs_mobilieres_de_placement']);
        $this->totalPassif = ($this->l_AP[0]['capitaux_propres'] + $this->l_AP[0]['provisions_pour_risques_et_charges'] + $this->l_AP[0]['amortissement_sur_immo'] + $this->l_AP[0]['dettes_financieres'] + $this->l_AP[0]['dettes_fournisseurs'] + $this->l_AP[0]['autres_dettes']);
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

        $remb = $this->projects_status_history->select('id_project = ' . $oProjects->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::REMBOURSEMENT . ')', 'added ASC', 0, 1);

        if ($remb[0]['added'] != "") {
            $this->dateRemb = date('d/m/Y', strtotime($remb[0]['added']));
        } else {
            $this->dateRemb = date('d/m/Y');
        }

        $this->dateContrat = $this->dateRemb;

        $this->settings->get('Commission remboursement', 'type');
        $fCommissionRate = $this->settings->value;
        $this->settings->get('TVA', 'type');
        $fVat = $this->settings->value;
        $this->settings->get('Part unilend', 'type');
        $fProjectCommisionRate = $this->settings->value;

        $this->aCommissionRepayment = \repayment::getRepaymentCommission($oLoans->amount / 100, $oProjects->period, $fCommissionRate, $fVat);

        $this->fCommissionRepayment = $this->aCommissionRepayment['commission_total'];
        $this->fCommissionProject   = $fProjectCommisionRate * $oLoans->amount / 100 / (1 + $fVat);
        $this->fInterestTotal       = $this->echeanciers->getSumByLoan($oLoans->id_loan, 'interets');

        if (\loans::TYPE_CONTRACT_BDC == $oLoans->id_type_contract) {
            $this->blocs->get('pdf-contrat', 'slug');
            $sTemplate = 'contrat_html';
        } else {
            $this->blocs->get('pdf-contrat-ifp', 'slug');
            $sTemplate = 'contrat_ifp_html';
        }

        $lElements = $this->blocs_elements->select('id_bloc = ' . $this->blocs->id_bloc . ' AND id_langue = "' . $this->language . '"');
        foreach ($lElements as $b_elt) {
            $this->elements->get($b_elt['id_element']);
            $this->bloc_pdf_contrat[$this->elements->slug]           = $b_elt['value'];
            $this->bloc_pdf_contratComplement[$this->elements->slug] = $b_elt['complement'];
        }

        $this->setDisplay($sTemplate);
    }

    public function _declarationContratPret_html($iIdLoan, $sPath)
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

            $this->lEcheances = $this->echeanciers->getSumByAnnee($this->oLoans->id_loan);

            if ($this->preteur->type == 2) {
                $this->preteurCompanie->get($this->lender->id_company_owner, 'id_company');

                $this->nomPreteur     = $this->preteurCompanie->name;
                $this->adressePreteur = $this->preteurCompanie->adresse1;
                $this->cpPreteur      = $this->preteurCompanie->zip;
                $this->villePreteur   = $this->preteurCompanie->city;
            } else {
                $this->nomPreteur     = $this->preteur->prenom . ' ' . $this->preteur->nom;
                $this->adressePreteur = $this->preteur_adresse->adresse1;
                $this->cpPreteur      = $this->preteur_adresse->cp;
                $this->villePreteur   = $this->preteur_adresse->ville;
            }

            $this->setDisplay('declarationContratPret_html');
            $this->WritePdf($sPath . 'Unilend_declarationContratPret_' . $iIdLoan, 'dec_pret');
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

                if (false === file_exists($sFileName)) {
                    $this->GenerateInvoiceEFHtml($iProjectId);
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

    private function GenerateInvoiceEFHtml($iProjectId)
    {
        $this->lng['pdf-facture'] = $this->ln->selectFront('pdf-facture', $this->language, $this->App);

        $this->compteur_factures       = $this->loadData('compteur_factures');
        $this->transactions            = $this->loadData('transactions');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->factures                = $this->loadData('factures');

        $this->settings->get('TVA', 'type');
        $this->tva = $this->settings->value;

        $this->companies->get($this->clients->id_client, 'id_client_owner');

        if ($this->factures->exist($this->projects->id_project, 'type_commission = 1 AND id_company = ' . $this->companies->id_company . ' AND id_project')) {
            $aInvoices                = $this->factures->select('type_commission = 1 AND id_company = ' . $this->companies->id_company . ' AND id_project = ' . $this->projects->id_project);
            $this->num_facture        = $aInvoices[0]['num_facture'];
            $this->ht                 = $aInvoices[0]['montant_ht'] / 100;
            $this->taxes              = $aInvoices[0]['tva'] / 100;
            $this->ttc                = $aInvoices[0]['montant_ttc'] / 100;
            $this->date_echeance_reel = $aInvoices[0]['date'];
        } else {
            if ($this->projects->get($iProjectId, 'id_company = ' . $this->companies->id_company . ' AND id_project')) {
                $histoRemb = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::REMBOURSEMENT . ')', 'added DESC', 0, 1);

                if ($histoRemb != false) {
                    $this->transactions->get($this->projects->id_project, 'type_transaction = 9 AND status = 1 AND etat = 1 AND id_project');

                    $this->dateRemb    = $histoRemb[0]['added'];
                    $this->num_facture = 'FR-E' . date('Ymd', strtotime($this->dateRemb)) . str_pad($this->compteur_factures->compteurJournalier($this->projects->id_project, $this->dateRemb), 5, "0", STR_PAD_LEFT);
                    $this->ttc         = ($this->transactions->montant_unilend / 100);
                    $cm                = ($this->tva + 1); // CM
                    $this->ht          = ($this->ttc / $cm); // HT
                    $this->taxes       = ($this->ttc - $this->ht); // TVA
                    $montant           = ((str_replace('-', '', $this->transactions->montant) + $this->transactions->montant_unilend) / 100); // Montant pret
                    $txCom             = (0 < $montant) ? round(($this->ht / $montant) * 100, 0) : 0; // taux commission

                    $this->factures->num_facture     = $this->num_facture;
                    $this->factures->date            = $this->dateRemb;
                    $this->factures->id_company      = $this->companies->id_company;
                    $this->factures->id_project      = $this->projects->id_project;
                    $this->factures->ordre           = 0;
                    $this->factures->type_commission = 1; // financement
                    $this->factures->commission      = $txCom;
                    $this->factures->montant_ht      = ($this->ht * 100);
                    $this->factures->tva             = ($this->taxes * 100);
                    $this->factures->montant_ttc     = ($this->ttc * 100);
                    $this->factures->create();
                }
            }
        }

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
                        $this->GenerateInvoiceERHtml($iProjectId, $iOrder);
                        $this->WritePdf($sFileName, 'invoice');
                    }

                    if (true === $bRead) {
                        $this->ReadPdf($sFileName, $sNamePdfClient);
                    }
                }
            }
        }
    }

    private function GenerateInvoiceERHtml($iProjectId, $iOrdre)
    {
        $this->lng['pdf-facture'] = $this->ln->selectFront('pdf-facture', $this->language, $this->App);

        $this->compteur_factures = $this->loadData('compteur_factures');
        $this->echeanciers       = $this->loadData('echeanciers');
        $this->factures          = $this->loadData('factures');

        $this->settings->get('TVA', 'type');
        $this->tva = $this->settings->value;

        $this->settings->get('Commission remboursement', 'type');
        $txcom = $this->settings->value;

        $this->companies->get($this->clients->id_client, 'id_client_owner');

        if ($this->factures->exist($this->projects->id_project, 'ordre = ' . $iOrdre . ' AND  type_commission = 2 AND id_company = ' . $this->companies->id_company . ' AND id_project' )) {
            $aInvoices                = $this->factures->select('ordre = ' . $iOrdre . ' AND  type_commission = 2 AND id_company = ' . $this->companies->id_company . ' AND id_project = ' . $this->projects->id_project);
            $this->num_facture        = $aInvoices[0]['num_facture'];
            $this->ht                 = $aInvoices[0]['montant_ht'] / 100;
            $this->taxes              = $aInvoices[0]['tva'] / 100;
            $this->ttc                = $aInvoices[0]['montant_ttc'] / 100;
            $this->date_echeance_reel = $aInvoices[0]['date'];
        } else {
            if ($this->projects->get($iProjectId, 'id_company = ' . $this->companies->id_company . ' AND id_project')) {
                $uneEcheancePreteur       = $this->echeanciers->select('id_project = ' . $this->projects->id_project . ' AND ordre = ' . $iOrdre, '', 0, 1);
                $this->date_echeance_reel = $uneEcheancePreteur[0]['date_echeance_reel'];

                if ($this->oEcheanciersEmprunteur->get($this->projects->id_project, 'ordre = ' . $iOrdre . '  AND id_project')) {
                    $compteur = $this->compteur_factures->compteurJournalier($this->projects->id_project, $this->date_echeance_reel);

                    $this->num_facture = 'FR-E' . date('Ymd', strtotime($this->date_echeance_reel)) . str_pad($compteur, 5, "0", STR_PAD_LEFT);
                    $this->ht          = ($this->oEcheanciersEmprunteur->commission / 100);
                    $this->taxes       = ($this->oEcheanciersEmprunteur->tva / 100);
                    $this->ttc         = ($this->ht + $this->taxes);

                    $this->factures->num_facture     = $this->num_facture;
                    $this->factures->date            = $this->date_echeance_reel;
                    $this->factures->id_company      = $this->companies->id_company;
                    $this->factures->id_project      = $this->projects->id_project;
                    $this->factures->ordre           = $iOrdre;
                    $this->factures->type_commission = 2; // remboursement
                    $this->factures->commission      = ($txcom * 100);
                    $this->factures->montant_ht      = ($this->ht * 100);
                    $this->factures->tva             = ($this->taxes * 100);
                    $this->factures->montant_ttc     = ($this->ttc * 100);
                    $this->factures->create();
                }
            }
        }
        $this->setDisplay('facture_ER_html');
        $sDisplayInvoice = $this->sDisplay;
        $this->GenerateFooterInvoice();
        $this->sDisplay = $sDisplayInvoice . $this->sDisplay;
    }

    // Mise a jour des dates echeances preteurs et emprunteur (utilisé pour se baser sur la date de creation du pouvoir)
    public function updateEcheances($id_project, $dateRemb)
    {
        ini_set('max_execution_time', 300);

        $projects                = $this->loadData('projects');
        $projects_status         = $this->loadData('projects_status');
        $projects_status_history = $this->loadData('projects_status_history');
        $echeanciers             = $this->loadData('echeanciers');
        $echeanciers_emprunteur  = $this->loadData('echeanciers_emprunteur');

        $jo = $this->loadLib('jours_ouvres');

        $this->settings->get('Nombre de mois apres financement pour remboursement', 'type');
        $nb_mois = $this->settings->value;

        $this->settings->get('Nombre de jours apres financement pour remboursement', 'type');
        $nb_jours = $this->settings->value;

        $projects->get($id_project, 'id_project');
        $projects_status->getLastStatut($projects->id_project);

        if ($projects_status->status == \projects_status::FUNDE) {
            for ($ordre = 1; $ordre <= $projects->period; $ordre++) {
                $date_echeance = $this->dates->dateAddMoisJoursV3($dateRemb, $ordre);
                $date_echeance = date('Y-m-d H:i', $date_echeance) . ':00';

                $date_echeance_emprunteur = $this->dates->dateAddMoisJoursV3($dateRemb, $ordre);
                $date_echeance_emprunteur = $jo->display_jours_ouvres($date_echeance_emprunteur, 6);
                $date_echeance_emprunteur = date('Y-m-d H:i', $date_echeance_emprunteur) . ':00';

                $echeanciers->onMetAjourLesDatesEcheances($projects->id_project, $ordre, $date_echeance, $date_echeance_emprunteur);
                $echeanciers_emprunteur->onMetAjourLesDatesEcheancesE($id_project, $ordre, $date_echeance_emprunteur);
            }
        }
    }

    public function _declaration_de_creances()
    {
        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash') && isset($this->params[1])) {
            $this->oLoans           = $this->loadData('loans');
            $this->oLendersAccounts = $this->loadData('lenders_accounts');
            $this->oLendersAccounts->get($this->clients->id_client, 'id_client_owner');

            if ($this->oLoans->get($this->oLendersAccounts->id_lender_account, 'id_loan = ' . $this->params[1] . ' AND id_lender')) {
                $sFilePath      = $this->path . 'protected/pdf/declaration_de_creances/' . $this->oLoans->id_project . '/';
                $sFilePath      = ($this->oLoans->id_project == '1456') ? $sFilePath : $sFilePath . $this->clients->id_client . '/';
                $sFilePath      = $sFilePath . 'declaration-de-creances' . '-' . $this->params[0] . '-' . $this->params[1] . '.pdf';
                $sNamePdfClient = 'DECLARATION-DE-CREANCES-UNILEND-' . $this->clients->hash . '-' . $this->oLoans->id_loan;

                if (false === file_exists($sFilePath)) {
                    $this->GenerateClaimsHtml();
                    $this->WritePdf($sFilePath, 'claims');
                }

                $this->ReadPdf($sFilePath, $sNamePdfClient);
            }
        }
    }

    private function GenerateClaimsHtml()
    {
        $this->oLendersAccounts                = $this->loadData('lenders_accounts');
        $this->oLoans                          = $this->loadData('loans');
        $this->pays                            = $this->loadData('pays_v2');
        $this->echeanciers                     = $this->loadData('echeanciers');
        $this->companiesEmpr                   = $this->loadData('companies');
        $this->projects_status                 = $this->loadData('projects_status');
        $this->projects_last_status_history    = $this->loadData('projects_last_status_history');
        $this->projects_status_history_details = $this->loadData('projects_status_history_details');

        $this->oLendersAccounts->get($this->clients->id_client, 'id_client_owner');

        if ($this->oLoans->get($this->oLendersAccounts->id_lender_account, 'id_loan = ' . $this->params[1] . ' AND id_lender')) {
            $this->projects->get($this->oLoans->id_project, 'id_project');
            $this->companiesEmpr->get($this->projects->id_company, 'id_company');

            $this->projects_status->getLastStatut($this->projects->id_project);

            if (in_array($this->clients->type, array(1, 4))) {
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

            if (in_array($this->projects_status->status, array(\projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE))) {
                $this->projects_last_status_history->get($this->oLoans->id_project, 'id_project');
                $this->projects_status_history_details->get($this->projects_last_status_history->id_project_status_history, 'id_project_status_history');

                $this->mandataires_var = $this->projects_status_history_details->receiver;

                // @todo intl
                switch ($this->projects_status->status) {
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

                $this->date = date('d/m/Y', strtotime($this->projects_status_history_details->date));
            }

            $this->echu         = $this->echeanciers->getSumARemb($this->oLendersAccounts->id_lender_account . ' AND DATE(date_echeance) >= "2015-04-19" AND DATE(date_echeance) <= "' . date('Y-m-d') . '" AND id_loan = ' . $this->oLoans->id_loan, 'montant');
            $this->echoir       = $this->echeanciers->getSumARemb($this->oLendersAccounts->id_lender_account . ' AND DATE(date_echeance) > "' . date('Y-m-d') . '" AND id_loan = ' . $this->oLoans->id_loan, 'capital');
            $this->total        = $this->echu + $this->echoir;
            $lastEcheance       = $this->echeanciers->select('id_lender = ' . $this->oLendersAccounts->id_lender_account . ' AND id_loan = ' . $this->oLoans->id_loan, 'ordre DESC', 0, 1);
            $this->lastEcheance = date('d/m/Y', strtotime($lastEcheance[0]['date_echeance']));

            $this->setDisplay('declaration_de_creances_html');
        } else {
            header('Location: ' . $this->lurl);
        }
    }

    public function _loans()
    {
        $sPath          = '/tmp/' . uniqid() . '/';
        $sNamePdfClient = 'vos_prets_' . date('Y-m-d_H:i:s') . '.pdf';

        $this->lng['preteur-operations-detail'] = $this->ln->selectFront('preteur-operations-detail', $this->language, $this->App);
        $this->lng['preteur-operations-pdf']    = $this->ln->selectFront('preteur-operations-pdf', $this->language, $this->App);

        $this->GenerateLoansHtml();
        $this->WritePdf($sPath . $sNamePdfClient, 'operations');
        $this->ReadPdf($sPath . $sNamePdfClient, $sNamePdfClient);
    }

    private function GenerateLoansHtml()
    {
        $this->echeanciers = $this->loadData('echeanciers');

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
        if (isset($_SESSION['filtre_vos_operations']['id_client'])) {
            $sPath          = $this->path . 'protected/operations_export_pdf/' . $_SESSION['filtre_vos_operations']['id_client'] . '/';
            $sNamePdfClient = 'vos_operations_' . date('Y-m-d') . '.pdf';

            $this->GenerateOperationsHtml();
            $this->WritePdf($sPath . $sNamePdfClient, 'operations');
            $this->ReadPdf($sPath . $sNamePdfClient, $sNamePdfClient);
        }
    }

    private function GenerateOperationsHtml()
    {
        $this->wallets_lines    = $this->loadData('wallets_lines');
        $this->bids             = $this->loadData('bids');
        $this->oLoans           = $this->loadData('loans');
        $this->echeanciers      = $this->loadData('echeanciers');
        $this->oLendersAccounts = $this->loadData('lenders_accounts');

        $this->lng['preteur-operations-vos-operations'] = $this->ln->selectFront('preteur-operations-vos-operations', $this->language, $this->App);
        $this->lng['preteur-operations-pdf']            = $this->ln->selectFront('preteur-operations-pdf', $this->language, $this->App);

        $post_debut            = $_SESSION['filtre_vos_operations']['debut'];
        $post_fin              = $_SESSION['filtre_vos_operations']['fin'];
        $post_nbMois           = $_SESSION['filtre_vos_operations']['nbMois'];
        $post_annee            = $_SESSION['filtre_vos_operations']['annee'];
        $post_tri_type_transac = $_SESSION['filtre_vos_operations']['tri_type_transac'];
        $post_tri_projects     = $_SESSION['filtre_vos_operations']['tri_projects'];
        $post_id_last_action   = $_SESSION['filtre_vos_operations']['id_last_action'];
        $post_order            = $_SESSION['filtre_vos_operations']['order'];
        $post_type             = $_SESSION['filtre_vos_operations']['type'];
        $post_id_client        = $_SESSION['filtre_vos_operations']['id_client'];

        $this->clients->get($post_id_client, 'id_client');
        $this->clients_adresses->get($post_id_client, 'id_client');
        $this->oLendersAccounts->get($post_id_client, 'id_client_owner');

        if (isset($post_id_last_action) && in_array($post_id_last_action, array('debut', 'fin'))) {

            $debutTemp = explode('/', $post_debut);
            $finTemp   = explode('/', $post_fin);

            $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');    // date debut
            $date_fin_time   = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');            // date fin

            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $post_id_last_action;

        } elseif (isset($post_id_last_action) && $post_id_last_action == 'nbMois') {
            $nbMois = $post_nbMois;

            $date_debut_time = mktime(0, 0, 0, date("m") - $nbMois, date("d"), date('Y')); // date debut
            $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date fin

            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $post_id_last_action;
        } elseif (isset($post_id_last_action) && $post_id_last_action == 'annee') {

            $year = $post_annee;

            $date_debut_time = mktime(0, 0, 0, 1, 1, $year);    // date debut

            if (date('Y') == $year) {
                $date_fin_time = mktime(0, 0, 0, date('m'), date('d'), $year);
            } // date fin
            else {
                $date_fin_time = mktime(0, 0, 0, 12, 31, $year);
            } // date fin

            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $post_id_last_action;

        } // si on a une session
        elseif (isset($post_id_last_action)) {

            if ($post_debut != "" && $post_fin != "") {
                //echo 'toto';
                $debutTemp = explode('/', $post_debut);
                $finTemp   = explode('/', $post_fin);

                $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');    // date debut
                $date_fin_time   = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');            // date fin
            } elseif ($post_id_last_action == 'nbMois') {
                //echo 'titi';
                $nbMois = $post_nbMois;

                $date_debut_time = mktime(0, 0, 0, date("m") - $nbMois, date("d"), date('Y')); // date debut
                $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date fin
            } elseif ($post_id_last_action == 'annee') {
                //echo 'tata';
                $year = $post_annee;

                $date_debut_time = mktime(0, 0, 0, 1, 1, $year);    // date debut
                $date_fin_time   = mktime(0, 0, 0, 12, 31, $year); // date fin
            }
        } // Par defaut (on se base sur le 1M)
        else {
            $date_debut_time = mktime(0, 0, 0, date("m") - 1, date("d"), date('Y')); // date debut
            $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date fin
        }

        $this->date_debut = date('Y-m-d', $date_debut_time);
        $this->date_fin   = date('Y-m-d', $date_fin_time);

        $array_type_transactions = array(
            1 => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            2 => array(
                1 => $this->lng['preteur-operations-vos-operations']['offre-en-cours'],
                2 => $this->lng['preteur-operations-vos-operations']['offre-rejetee'],
                3 => $this->lng['preteur-operations-vos-operations']['offre-acceptee']
            ),
            3 => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            4 => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            5 => $this->lng['preteur-operations-vos-operations']['remboursement'],
            7 => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            8 => $this->lng['preteur-operations-vos-operations']['retrait-dargents'],
            16 => $this->lng['preteur-operations-vos-operations']['offre-de-bienvenue'],
            17 => $this->lng['preteur-operations-vos-operations']['retrait-offre'],
            19 => $this->lng['preteur-operations-vos-operations']['gain-filleul'],
            20 => $this->lng['preteur-operations-vos-operations']['gain-parrain'],
            22 => $this->lng['preteur-operations-vos-operations']['remboursement-anticipe'],
            23 => $this->lng['preteur-operations-vos-operations']['remboursement-anticipe-preteur']
        );

        $array_type_transactions_liste_deroulante = array(
            1 => '1,2,3,4,5,7,8,16,17,19,20,23',
            2 => '3,4,7,8',
            3 => '3,4,7',
            4 => '8',
            5 => '2',
            6 => '5,23'
        );

        if (isset($post_tri_type_transac)) {
            $tri_type_transac = $array_type_transactions_liste_deroulante[$post_tri_type_transac];
        }

        if (isset($post_tri_projects)) {
            if (in_array($post_tri_projects, array(0, 1))) {
                $tri_project = '';
            } else {
                $tri_project = ' AND le_id_project = ' . $post_tri_projects;
            }
        }

        $order = 'date_operation DESC, id_transaction DESC';
        if (isset($post_type) && isset($post_order)) {
            $this->type  = $post_type;
            $this->order = $post_order;

            if ($this->type == 'order_operations') {
                if ($this->order == 'asc') {
                    $order = ' type_transaction ASC, id_transaction ASC';
                } else {
                    $order = ' type_transaction DESC, id_transaction DESC';
                }
            } elseif ($this->type == 'order_projects') {
                if ($this->order == 'asc') {
                    $order = ' libelle_projet ASC , id_transaction ASC';
                } else {
                    $order = ' libelle_projet DESC , id_transaction DESC';
                }
            } elseif ($this->type == 'order_date') {
                if ($this->order == 'asc') {
                    $order = ' date_operation ASC, id_transaction ASC';
                } else {
                    $order = ' date_operation DESC, id_transaction DESC';
                }
            } elseif ($this->type == 'order_montant') {
                if ($this->order == 'asc') {
                    $order = ' montant_operation ASC, id_transaction ASC';
                } else {
                    $order = ' montant_operation DESC, id_transaction DESC';
                }
            } elseif ($this->type == 'order_bdc') {
                if ($this->order == 'asc') {
                    $order = ' ABS(bdc) ASC, id_transaction ASC';
                } else {
                    $order = ' ABS(bdc) DESC, id_transaction DESC';
                }
            } else {
                $order = 'date_operation DESC, id_transaction DESC';
            }
        }

        $this->indexage_vos_operations = $this->loadData('indexage_vos_operations');

        $this->lTrans         = $this->indexage_vos_operations->select('type_transaction IN (' . $tri_type_transac . ') AND id_client = ' . $this->clients->id_client . ' AND DATE(date_operation) >= "' . $this->date_debut . '" AND DATE(date_operation) <= "' . $this->date_fin . '"' . $tri_project, $order);
        $this->lProjectsLoans = $this->indexage_vos_operations->get_liste_libelle_projet('type_transaction IN (' . $tri_type_transac . ') AND id_client = ' . $this->clients->id_client . ' AND LEFT(date_operation,10) >= "' . $this->date_debut . '" AND LEFT(date_operation,10) <= "' . $this->date_fin . '"');

        $this->setDisplay('vos_operations_pdf_html_indexation');
    }
}
