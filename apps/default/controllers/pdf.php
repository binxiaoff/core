<?php

use librairies\UnilendLogger;
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
     * @object \Knp\Snappy\Pdf()
     */
    private $oSnapPdf;

    /**
     * @object \Monolog\Logger()
     */
    private $oLogger;

    /**
     * @object data\crud\projects_pouvoir
     */
    private $oProjectsPouvoir;

    /**
     * @object data\crud\loans
     */
    private $oLoans;

    /**
     * @object data\crud\lenders_accounts
     */
    private $oLendersAccounts;

    /**
     * @object data\crud\echeanciers_emprunteur
     */
    private $oEcheanciersEmprunteur;

    /**
     * @desc contains html returns ($this->execute())
     * @var    string $sDisplay
     */
    private $sDisplay;


    public function pdfController($command, $config, $app)
    {
        parent::__construct($command, $config, $app);
        // Recuperation du bloc
        $this->blocs->get('pdf-contrat', 'slug');
        $lElements = $this->blocs_elements->select('id_bloc = ' . $this->blocs->id_bloc . ' AND id_langue = "' . $this->language . '"');
        foreach ($lElements as $b_elt) {
            $this->elements->get($b_elt['id_element']);
            $this->bloc_pdf_contrat[$this->elements->slug] = $b_elt['value'];
            $this->bloc_pdf_contratComplement[$this->elements->slug] = $b_elt['complement'];
        }

        $this->catchAll = true;

        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug = false;

        $this->oSnapPdf = new Pdf($this->path . 'vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64');
        $oUnilendLogger = new UnilendLogger('PdfManagement', $this->logPath, self::NAME_LOG);
        $oUnilendLogger->setStreamHandlerInfo()
            ->setStreamHandlerDebug()
            ->setStreamHandlerError();
        $this->oLogger = $oUnilendLogger->getLogger();
    }


    /**
     * @param string $sPathPdf Pdf file's path
     * @param bool|false $bCheckSign know if sign is necessary or not
     * @param bool|false $bSign know if sign is ok or not
     * @param bool|false $bIsPouvoir if we treat authority or not
     * @return bool|string true if no universign or universign
     */
    private function CheckUniversign($sPathPdf, $bCheckSign = false, $bSign = false, $bIsPouvoir = false)
    {
        // si le PDF est un mandat/pouvoir non signé => faut faire signer
        if (true === $bCheckSign && false === $bSign) {
            // Si Pouvoir pas signé,qu'il y a un fichier
            // et que la date de creation est différente à la date du jour
            // => dans ce cas on supprime le PDF pour en générer un nouveau
            if (true === $bIsPouvoir &&
                file_exists($sPathPdf) &&
                filesize($sPathPdf) > 0 &&
                date("Y-m-d", filemtime($sPathPdf)) != date('Y-m-d')
            ) {
                unlink($sPathPdf);
                $this->oLogger->addInfo('File : ' . $sPathPdf . ' deleting.', array(__FILE__ . ' on line ' . __LINE__));
            }
            return true;
        }

        return false;
    }

    /**
     * @param string $sView name of view file
     */
    private function setDisplay($sView = '')
    {

        //Change view name for generate document
        $this->view = $sView;

        ob_start();

        if ($this->autoFireHead)
            $this->fireHead();
        if ($this->autoFireHeader)
            $this->fireHeader();
        if ($this->autoFireView)
            $this->fireView();
        if ($this->autoFireFooter)
            $this->fireFooter();

        $this->sDisplay = ob_get_contents();
        ob_end_clean();

        //set back view name
        $this->view = '';
    }

    /**
     * @param string $sPathPdf full path with name of pdf
     * @param string $sTypePdf for log and css
     */
    private function WritePdf($sPathPdf, $sTypePdf = 'authority')
    {
        // We check if PathPdf get pdf extension
        $sPathPdf .= (!preg_match('/(\.pdf)$/i', $sPathPdf)) ? '.pdf' : '';

        $iTimeStartPdf = microtime(true);
        $this->oLogger->addInfo('Start generation of ' . $sTypePdf . ' pdf', array(__FILE__ . ' on line ' . __LINE__));

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
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/pdf/styleOperations.css');
                break;
            default:
                $this->oSnapPdf->setOption('user-style-sheet', $this->staticPath . 'styles/default/pdf/style.css');
        }
        $this->oSnapPdf->generateFromHtml($this->sDisplay, $sPathPdf, array(), true);

        $iTimeEndPdf = microtime(true) - $iTimeStartPdf;

        $this->oLogger->addInfo('End generation of ' . $sTypePdf . ' pdf in ' . round($iTimeEndPdf, 2), array(__FILE__ . ' on line ' . __LINE__));
    }

    /**
     * @param string $sPathPdf full path with name of pdf
     * @param string $sNamePdf pdf's name for client
     */
    public function ReadPdf($sPathPdf, $sNamePdf)
    {
        // We check if PathPdf get pdf extension
        $sPathPdf .= (!preg_match('/(\.pdf)$/i', $sPathPdf)) ? '.pdf' : '';

        header("Content-disposition: attachment; filename=" . $sNamePdf . ".pdf");
        header("Content-Type: application/force-download");
        if (!readfile($sPathPdf)) {
            $this->oLogger->addDebug('File : ' . $sPathPdf . ' not readable.', array(__FILE__ . ' on line ' . __LINE__));
        }
    }

    public function _mandat_preteur()
    {
        // chargement des datas
        $this->clients->get($this->params[0], 'hash');

        $sNamePdfClient = 'MANDAT-UNILEND-' . $this->clients->id_client;
        $this->GenerateWarrantyHtml();
        $this->WritePdf(self::TMP_PATH_FILE . $sNamePdfClient, 'warranty');
        $this->ReadPdf(self::TMP_PATH_FILE . $sNamePdfClient, $sNamePdfClient);
    }

    // mandat emprunteur
    public function _mandat()
    {
        // chargement des datas
        $oClientsMandats = $this->loadData('clients_mandats');

        // On recup le client
        if ($this->clients->get($this->params[0], 'hash') && isset($this->params[1])) {
            // si on a un params 1 on check si on a une entreprise et un projet

            // on chek si le projet est bien au client
            if ($this->companies->get($this->clients->id_client, 'id_client_owner') &&
                $this->projects->get($this->params[1], 'id_project') &&
                $this->projects->id_company == $this->companies->id_company
            ) {

                // la c'est good on peut faire le traitement

                $path = $this->path . 'protected/pdf/mandat/';
                $slug = $this->params[0];
                $name = 'mandat';
                $bSign = false;
                //TODO : modifier le nom du fichier ?
                $sNamePdfClient = 'MANDAT-UNILEND-' . $this->projects->slug . '-' . $this->clients->id_client;
                //TODO : modifier le nom du fichier ?
                $nom_fichier = ($this->params[1] != '') ? $name . '-' . $slug . "-" . $this->params[1] . ".pdf" : name . '-' . $slug . ".pdf";


                // on check si y a deja un traitement universign de fait
                if ($oClientsMandats->get($this->clients->id_client, 'id_project = ' . $this->params[1] . ' AND id_client')) {
                    // Si on a affaire a un mandat charger manuelement
                    if ($oClientsMandats->id_universign == 'no_universign') {
                        // on recup directement le pdf
                        $this->ReadPdf($path . $oClientsMandats->name, $oClientsMandats->name);
                        die;
                    }

                    $bSign = ($oClientsMandats->status > 0) ?: false;
                    $oClientsMandats->update();
                } else {
                    $this->GenerateWarrantyHtml();
                    //We generate pdf file
                    $this->WritePdf($path . $nom_fichier, 'warranty');

                    $oClientsMandats->id_client = $this->clients->id_client;
                    $oClientsMandats->url_pdf = '/pdf/mandat/' . $this->params[0] . '/' . (isset($this->params[1]) ? $this->params[1] . '/' : '');
                    $oClientsMandats->name = $nom_fichier;
                    $oClientsMandats->id_project = $this->projects->id_project;
                    $oClientsMandats->id_mandat = $oClientsMandats->create();

                }

                if (true === $this->CheckUniversign($path . $nom_fichier, true, $bSign)) {
                    header("location:" . $this->url . '/universign/mandat/' . $oClientsMandats->id_mandat);
                } else { //Si Mandat Signé
                    $this->ReadPdf($path . $nom_fichier, $sNamePdfClient);
                }
            } else {
                // pas good on redirige
                header("location:" . $this->lurl);
                die;
            }
        } else {
            header("location:" . $this->lurl);
            die;
        }
    }

    private function GenerateWarrantyHtml()
    {
        $this->pays = $this->loadData('pays');
        $this->pays->get($this->clients->id_langue, 'id_langue');
        $this->oLendersAccounts = $this->loadData('lenders_accounts');
        $this->oLendersAccounts->get($this->clients->id_client, 'id_client_owner');
        $this->clients_adresses->get($this->clients->id_client, 'id_client');


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
            // Motif mandat emprunteur
            $p = substr($this->ficelle->stripAccents(utf8_decode($this->clients->prenom)), 0, 1);
            $nom = $this->ficelle->stripAccents(utf8_decode($this->clients->nom));
            $id_project = str_pad($this->projects->id_project, 6, 0, STR_PAD_LEFT);
            $this->motif = mb_strtoupper($id_project . 'E' . $p . $nom, 'UTF-8');
            $this->motif = $this->ficelle->str_split_unicode('UNILEND' . $this->motif);
        } else {
            // Motif mandat preteur
            $p = substr($this->ficelle->stripAccents(utf8_decode($this->clients->prenom)), 0, 1);
            $nom = $this->ficelle->stripAccents(utf8_decode($this->clients->nom));
            $id_client = str_pad($this->clients->id_client, 6, 0, STR_PAD_LEFT);
            $this->motif = mb_strtoupper($id_client . 'P' . $p . $nom, 'UTF-8');
            $this->motif = $this->ficelle->str_split_unicode('UNILEND' . $this->motif);
        }


        // Créancier adresse
        $this->settings->get('Créancier adresse', 'type');
        $this->creancier_adresse = $this->settings->value;
        // Créancier cp
        $this->settings->get('Créancier cp', 'type');
        $this->creancier_cp = $this->settings->value;
        // Créancier identifiant
        $this->settings->get('ICS de SFPMEI', 'type');
        $this->creancier_identifiant = $this->settings->value;
        // Créancier nom
        $this->settings->get('Créancier nom', 'type');
        $this->creancier = $this->settings->value;
        // Créancier pays
        $this->settings->get('Créancier pays', 'type');
        $this->creancier_pays = $this->settings->value;
        // Créancier ville
        $this->settings->get('Créancier ville', 'type');
        $this->creancier_ville = $this->settings->value;
        // Créancier code identifiant
        $this->settings->get('Créancier code identifiant', 'type');
        $this->creancier_code_id = $this->settings->value;


        // Adresse retour
        $this->settings->get('Adresse retour', 'type');
        $this->adresse_retour = $this->settings->value;

        $this->setDisplay('mandat_html');
    }


    public function _pouvoir()
    {
        // si le client existe
        if ($this->clients->get($this->params[0], 'hash') && isset($this->params[1])) {
            $this->oProjectsPouvoir = $this->loadData('projects_pouvoir');

            //on recup l'entreprise
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            if ($this->projects->get($this->params[1], 'id_company = ' . $this->companies->id_company . ' AND id_project')) {

                $path = $this->path . 'protected/pdf/pouvoir/'; // path d'enregistrement
                $slug = $this->params[0]; // hash client
                $name = 'pouvoir'; // nom du pdf (type PDF)
                $bSign = false; // PDF non signé
                //TODO : modifier le nom du fichier ?
                $sNamePdfClient = 'POUVOIR-UNILEND-' . $this->projects->slug . '-' . $this->clients->id_client; // nom du PDF lors de l'enregistrement
                //TODO : modifier le nom du fichier ?
                $nom_fichier = ($this->params[1] != '') ? $name . '-' . $slug . "-" . $this->params[1] . ".pdf" : $name . '-' . $slug . ".pdf";;

                // on check si y a deja un pouvoir
                $aProjectPouvoir = $this->oProjectsPouvoir->select('id_project = ' . $this->projects->id_project, 'added ASC');
                $aProjectPouvoirToTreat = (is_array($aProjectPouvoir) && false === empty($aProjectPouvoir)) ? array_shift($aProjectPouvoir) : null;

                //Deleting authority, not necessary (Double authority)
                if (is_array($aProjectPouvoir) && 0 < count($aProjectPouvoir)) {
                    foreach ($aProjectPouvoir as $aProjectPouvoirToDelete) {
                        $this->oProjectsPouvoir->delete($aProjectPouvoirToDelete['id_pouvoir'], 'id_pouvoir'); // plus de doublons comme ca !
                    }
                }

                // si on a une ligne deja créée
                if (false === is_null($aProjectPouvoirToTreat)) {

                    // si c'est un upload manuel du BO on affiche directement
                    if ($aProjectPouvoirToTreat['id_universign'] == 'no_universign' && file_exists($path . $aProjectPouvoirToTreat['name'])) {
                        $this->ReadPdf($path . $aProjectPouvoirToTreat['name'], $sNamePdfClient);
                        die;
                    }

                    // Si pouvoir signé
                    $bSign = ($aProjectPouvoirToTreat['status'] > 0) ?: false;

                    // On recup le pouvoir
                    $this->oProjectsPouvoir->get($aProjectPouvoirToTreat['id_pouvoir'], 'id_pouvoir');
                    $bInstantCreate = false;
                } else { // Si pas de pouvoir on créer une ligne
                    $this->GenerateAuthorityHtml();
                    //We generate pdf file
                    $this->WritePdf($path . $nom_fichier, 'authority');

                    $this->oProjectsPouvoir->id_project = $this->projects->id_project;
                    $this->oProjectsPouvoir->url_pdf = '/pdf/pouvoir/' . $this->params[0] . '/' . (isset($this->params[1]) ? $this->params[1] . '/' : '');
                    $this->oProjectsPouvoir->name = $nom_fichier;
                    $this->oProjectsPouvoir->id_pouvoir = $this->oProjectsPouvoir->create();

                    $this->oProjectsPouvoir->get($this->oProjectsPouvoir->id_pouvoir, 'id_pouvoir');
                    $bInstantCreate = true;
                }

                if (true === $this->CheckUniversign($path . $nom_fichier, true, $bSign, true)) {
                    $this->generateAuthorityUniversign($bInstantCreate);
                } else { //Si pouvoir signé
                    $this->ReadPdf($path . $nom_fichier, $sNamePdfClient);
                }
            } else {
                header('Location:' . $this->lurl);
                die;
            }
        } else {
            header('Location:' . $this->lurl);
            die;
        }
    }

    private function generateAuthorityUniversign($bInstantCreate = false)
    {
        /////////////////////////////////////////////////////
        //   	  On met a jour les dates d'echeances      //
        // en se basant sur la date de creation du pouvoir //
        /////////////////////////////////////////////////////
        if (date('Y-m-d', strtotime($this->oProjectsPouvoir->updated)) == date('Y-m-d') && false === $bInstantCreate) {
            $regenerationUniversign = '/NoUpdateUniversign'; // On crée pas de nouveau universign
        } // Ici on creera un nouveau universign car la date est différente
        else {
            $regenerationUniversign = '';
            // On met a jour la date des echeances en se basant sur la date de signature du pouvoir c'est a dire aujourd'hui
            $this->updateEcheances($this->oProjectsPouvoir->id_project, date('Y-m-d H:i:s'));

            // On met a jour la ligne pouvoir, pour changer la date update
            $this->oProjectsPouvoir->update();
        }
        ///////////////////////////////
        // FIN mise a jour echeances //
        ///////////////////////////////
        header("location:" . $this->url . '/universign/pouvoir/' . $this->oProjectsPouvoir->id_pouvoir . $regenerationUniversign);
    }

    private function GenerateAuthorityHtml()
    {
        //Recuperation des element de traductions
        $this->lng['pdf-pouvoir'] = $this->ln->selectFront('pdf-pouvoir', $this->language, $this->App);

        // Recuperation du bloc
        $this->blocs->get('pouvoir', 'slug');
        $lElements = $this->blocs_elements->select('id_bloc = ' . $this->blocs->id_bloc . ' AND id_langue = "' . $this->language . '"');
        foreach ($lElements as $b_elt) {
            $this->elements->get($b_elt['id_element']);
            $this->bloc_pouvoir[$this->elements->slug] = $b_elt['value'];
            $this->bloc_pouvoirComplement[$this->elements->slug] = $b_elt['complement'];
        }

        $this->companies_details = $this->loadData('companies_details');
        $this->oLoans = $this->loadData('loans');
        $this->echeanciers = $this->loadData('echeanciers');
        $this->oEcheanciersEmprunteur = $this->loadData('echeanciers_emprunteur');
        $this->oLendersAccounts = $this->loadData('lenders_accounts');
        $this->companies_actif_passif = $this->loadData('companies_actif_passif');

        //on recup l'entreprise

        $this->companies_details->get($this->companies->id_company, 'id_company');


        // date_dernier_bilan_mois
        $date_dernier_bilan = explode('-', $this->companies_details->date_dernier_bilan);
        $this->date_dernier_bilan_annee = $date_dernier_bilan[0];
        $this->date_dernier_bilan_mois = $date_dernier_bilan[1];
        $this->date_dernier_bilan_jour = $date_dernier_bilan[2];

        // Montant prété a l'emprunteur
        $this->montantPrete = $this->projects->amount;

        // moyenne pondéré
        $montantHaut = 0;
        $montantBas = 0;
        // si fundé ou remboursement

        foreach ($this->oLoans->select('id_project = ' . $this->projects->id_project) as $b) {
            $montantHaut += ($b['rate'] * ($b['amount'] / 100));
            $montantBas += ($b['amount'] / 100);
        }
        $this->taux = (0 < $montantBas) ? ($montantHaut / $montantBas) : 0;

        $this->nbLoans = $this->oLoans->counter('id_project = ' . $this->projects->id_project);

        // Remb emprunteur par mois
        $this->echeanceEmprun = $this->oEcheanciersEmprunteur->select('id_project = ' . $this->projects->id_project . ' AND ordre = 1');

        $this->rembByMonth = $this->echeanciers->getMontantRembEmprunteur($this->echeanceEmprun[0]['montant'], $this->echeanceEmprun[0]['commission'], $this->echeanceEmprun[0]['tva']);
        $this->rembByMonth = ($this->rembByMonth / 100);

        // date premiere echance

        //$this->dateLastEcheance = $this->echeanciers->getDateDerniereEcheance($this->projects->id_project);
        $this->dateLastEcheance = $this->echeanciers->getDateDerniereEcheancePreteur($this->projects->id_project);


        // liste des echeances emprunteur par mois

        $this->lRemb = $this->oEcheanciersEmprunteur->select('id_project = ' . $this->projects->id_project, 'ordre ASC');

        $this->capital = 0;
        foreach ($this->lRemb as $r) {
            $this->capital += $r['capital'];
        }
        //echo $this->capital;

        // Liste des actif passif
        $this->l_AP = $this->companies_actif_passif->select('id_company = "' . $this->companies->id_company . '" AND annee = ' . $this->date_dernier_bilan_annee, 'annee DESC');


        $this->totalActif = ($this->l_AP[0]['immobilisations_corporelles'] + $this->l_AP[0]['immobilisations_incorporelles'] + $this->l_AP[0]['immobilisations_financieres'] + $this->l_AP[0]['stocks'] + $this->l_AP[0]['creances_clients'] + $this->l_AP[0]['disponibilites'] + $this->l_AP[0]['valeurs_mobilieres_de_placement']);

        $this->totalPassif = ($this->l_AP[0]['capitaux_propres'] + $this->l_AP[0]['provisions_pour_risques_et_charges'] + $this->l_AP[0]['amortissement_sur_immo'] + $this->l_AP[0]['dettes_financieres'] + $this->l_AP[0]['dettes_fournisseurs'] + $this->l_AP[0]['autres_dettes']);

        // liste des encheres
        $this->lLenders = $this->oLoans->select('id_project = ' . $this->projects->id_project, 'rate ASC');

        //if($this->oProjectsPouvoir->get($this->projects->id_project,'id_project'))
        //$this->dateRemb = date('d/m/Y',strtotime($this->oProjectsPouvoir->updated));
        //else
        $this->dateRemb = date('d/m/Y');

        $this->setDisplay('pouvoir_html');
    }

    public function _contrat()
    {
        $bRedirect = true;
        if (($this->clients->checkAccess() && $this->clients->hash == $this->params[0]) || (isset($_SESSION['user']['id_user']) && $_SESSION['user']['id_user'] != '')) {
            $bRedirect = false;
        }

        if (true === $bRedirect) {
            header('Location:' . $this->lurl);
        }

        $this->oLoans = $this->loadData('loans');
        $this->oLendersAccounts = $this->loadData('lenders_accounts');

        $this->clients->get($this->params[0], 'hash');
        $this->projects->get($this->oLoans->id_project, 'id_project');
        $this->oLendersAccounts->get($this->clients->id_client, 'id_client_owner');
        $this->oLoans->get($this->params[1], 'id_lender = ' . $this->oLendersAccounts->id_lender_account . ' AND id_loan');

        $sNamePdfClient = 'CONTRAT-UNILEND-' . $this->projects->slug . '-' . $this->oLoans->id_loan;
        $this->GenerateContractHtml();
        $this->WritePdf(self::TMP_PATH_FILE . $sNamePdfClient, 'contract');
        $this->ReadPdf(self::TMP_PATH_FILE . $sNamePdfClient, $sNamePdfClient);
    }

    private function GenerateContractHtml()
    {
        $this->echeanciers = $this->loadData('echeanciers');
        $this->companiesEmprunteur = $this->loadData('companies');
        $this->companies_detailsEmprunteur = $this->loadData('companies_details');
        $this->companiesPreteur = $this->loadData('companies');
        $this->emprunteur = $this->loadData('clients');
        $this->companies_actif_passif = $this->loadData('companies_actif_passif');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->oProjectsPouvoir = $this->loadData('projects_pouvoir');
        // on recup adresse preteur
        $this->clients_adresses->get($this->clients->id_client, 'id_client');

        // preteur
        $this->oLendersAccounts->get($this->clients->id_client, 'id_client_owner');

        // si le loan existe
        if ($this->oLoans->get($this->params[1], 'id_lender = ' . $this->oLendersAccounts->id_lender_account . ' AND id_loan')) {
            // On recup le projet
            $this->projects->get($this->oLoans->id_project, 'id_project');
            // On recup l'entreprise
            $this->companiesEmprunteur->get($this->projects->id_company, 'id_company');
            // On recup le detail entreprise emprunteur
            $this->companies_detailsEmprunteur->get($this->projects->id_company, 'id_company');

            // On recup l'emprunteur
            $this->emprunteur->get($this->companiesEmprunteur->id_client_owner, 'id_client');

            // Si preteur morale
            if ($this->clients->type == 2) {
                // entreprise preteur;

                $this->companiesPreteur->get($this->clients->id_client, 'id_client_owner');

            }

            // date premiere echance
            //$this->dateFirstEcheance = $this->echeanciers->getDatePremiereEcheance($this->projects->id_project);
            $this->dateLastEcheance = $this->echeanciers->getDateDerniereEcheancePreteur($this->projects->id_project);

            // date_dernier_bilan_mois
            $date_dernier_bilan = explode('-', $this->companies_detailsEmprunteur->date_dernier_bilan);
            $this->date_dernier_bilan_annee = $date_dernier_bilan[0];
            $this->date_dernier_bilan_mois = $date_dernier_bilan[1];
            $this->date_dernier_bilan_jour = $date_dernier_bilan[2];

            // Liste des actif passif
            //$this->l_AP = $this->companies_actif_passif->select('id_company = "'.$this->companies->id_company.'" AND annee = '.$this->date_dernier_bilan_annee,'annee DESC');

            // Liste des actif passif
            $this->l_AP = $this->companies_actif_passif->select('id_company = "' . $this->companiesEmprunteur->id_company . '" AND annee = ' . $this->date_dernier_bilan_annee, 'annee DESC');

            $this->totalActif = ($this->l_AP[0]['immobilisations_corporelles'] + $this->l_AP[0]['immobilisations_incorporelles'] + $this->l_AP[0]['immobilisations_financieres'] + $this->l_AP[0]['stocks'] + $this->l_AP[0]['creances_clients'] + $this->l_AP[0]['disponibilites'] + $this->l_AP[0]['valeurs_mobilieres_de_placement']);

            $this->totalPassif = ($this->l_AP[0]['capitaux_propres'] + $this->l_AP[0]['provisions_pour_risques_et_charges'] + $this->l_AP[0]['amortissement_sur_immo'] + $this->l_AP[0]['dettes_financieres'] + $this->l_AP[0]['dettes_fournisseurs'] + $this->l_AP[0]['autres_dettes']);


            // les remb d'une enchere
            $this->lRemb = $this->echeanciers->select('id_loan = ' . $this->oLoans->id_loan, 'ordre ASC');

            $this->capital = 0;
            foreach ($this->lRemb as $r) {
                $this->capital += $r['capital'];
            }

            // si on a le pouvoir
            if ($this->oProjectsPouvoir->get($this->projects->id_project, 'id_project')) {
                //$this->dateContrat = date('d/m/Y',strtotime($this->oProjectsPouvoir->updated));
                //$this->dateRemb = date('d/m/Y',strtotime($this->oProjectsPouvoir->updated));
            } else {
                //$this->dateContrat = date('d/m/Y');
                //$this->dateRemb = date('d/m/Y');
            }

            // On recup la date de statut remb
            $remb = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = 8', 'added ASC', 0, 1);

            if ($remb[0]['added'] != "") {
                $this->dateRemb = date('d/m/Y', strtotime($remb[0]['added']));
            } else {
                $this->dateRemb = date('d/m/Y');
            }

            $this->dateContrat = $this->dateRemb;

            $this->setDisplay('contrat_html');

        } else {
            header('Location:' . $this->lurl);
        }
    }

    public function _piedpage()
    {
        // Recuperation du bloc
        $this->blocs->get('pouvoir', 'slug');
        $lElements = $this->blocs_elements->select('id_bloc = ' . $this->blocs->id_bloc . ' AND id_langue = "' . $this->language . '"');
        foreach ($lElements as $b_elt) {
            $this->elements->get($b_elt['id_element']);
            $this->bloc_pouvoir[$this->elements->slug] = $b_elt['value'];
            $this->bloc_pouvoirComplement[$this->elements->slug] = $b_elt['complement'];
        }
    }

    public function _declarationContratPret_html($iIdLoan, $sPath)
    {

        $this->oLoans = $this->loadData('loans');
        $this->companiesEmp = $this->loadData('companies');
        $this->emprunteur = $this->loadData('clients');
        $this->lender = $this->loadData('lenders_accounts');
        $this->preteur = $this->loadData('clients');
        $this->preteurCompanie = $this->loadData('companies');
        $this->preteur_adresse = $this->loadData('clients_adresses');
        $this->echeanciers = $this->loadData('echeanciers');

        if (isset($iIdLoan) && $this->oLoans->get($iIdLoan, 'status = "0" AND id_loan')) {

            $this->settings->get('Declaration contrat pret - adresse', 'type');
            $this->adresse = $this->settings->value;

            $this->settings->get('Declaration contrat pret - raison sociale', 'type');
            $this->raisonSociale = $this->settings->value;

            // Coté emprunteur

            // On recup le projet
            $this->projects->get($this->oLoans->id_project, 'id_project');
            // On recup la companie
            $this->companiesEmp->get($this->projects->id_company, 'id_company');
            // On recup l'emprunteur
            $this->emprunteur->get($this->companiesEmp->id_client_owner, 'id_client');


            // Coté preteur

            // On recup le lender
            $this->lender->get($this->oLoans->id_lender, 'id_lender_account');
            // On recup le preteur
            $this->preteur->get($this->lender->id_client_owner, 'id_client');
            // On recup l'adresse preteur
            $this->preteur_adresse->get($this->lender->id_client_owner, 'id_client');

            $this->lEcheances = $this->echeanciers->getSumByAnnee($this->oLoans->id_loan);

            if ($this->preteur->type == 2) {
                $this->preteurCompanie->get($this->lender->id_company_owner, 'id_company');

                $this->nomPreteur = $this->preteurCompanie->name;
                $this->adressePreteur = $this->preteurCompanie->adresse1;
                $this->cpPreteur = $this->preteurCompanie->zip;
                $this->villePreteur = $this->preteurCompanie->city;
            } else {
                $this->nomPreteur = $this->preteur->prenom . ' ' . $this->preteur->nom;
                $this->adressePreteur = $this->preteur_adresse->adresse1;
                $this->cpPreteur = $this->preteur_adresse->cp;
                $this->villePreteur = $this->preteur_adresse->ville;
            }


            $this->setDisplay('declarationContratPret_html');

            $sNamePdfClient = 'Unilend_declarationContratPret_' . $iIdLoan . '.pdf';
            $this->WritePdf($sPath . $sNamePdfClient, 'dec_pret');
        }
    }

    public function _facture_EF($sHash, $iIdProject)
    {
        // si le client existe
        if ($this->clients->get($sHash, 'hash') && isset($iIdProject)) {

            //on recup l'entreprise
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            // et on recup le projet
            if ($this->projects->get($iIdProject, 'id_company = ' . $this->companies->id_company . ' AND id_project')) {

                $path = $this->path . 'protected/pdf/facture/'; // path d'enregistrement
                $sNamePdfClient = 'FACTURE-UNILEND-' . $this->projects->slug;
                $name = 'facture_EF'; // nom du pdf (type PDF)
                $nom_fichier = ($iIdProject != '') ? $name . '-' . $sHash . "-" . $iIdProject . ".pdf" : $name . '-' . $sHash . ".pdf";

                $this->GenerateInvoiceEFHtml($iIdProject);
                //We generate pdf file
                $this->WritePdf($path . $nom_fichier, 'invoice');
            }
        }
    }

    private function GenerateFooterInvoice()
    {
        $this->lng['pdf-facture'] = $this->ln->selectFront('pdf-facture', $this->language, $this->App);

        // titulaire du compte
        $this->settings->get('titulaire du compte', 'type');
        $this->titreUnilend = mb_strtoupper($this->settings->value, 'UTF-8');

        // Declaration contrat pret - raison sociale
        $this->settings->get('Declaration contrat pret - raison sociale', 'type');
        $this->raisonSociale = mb_strtoupper($this->settings->value, 'UTF-8');

        // Facture - SFF PME
        $this->settings->get('Facture - SFF PME', 'type');
        $this->sffpme = mb_strtoupper($this->settings->value, 'UTF-8');

        // Facture - capital
        $this->settings->get('Facture - capital', 'type');
        $this->capital = mb_strtoupper($this->settings->value, 'UTF-8');

        // Declaration contrat pret - adresse
        $this->settings->get('Declaration contrat pret - adresse', 'type');
        $this->raisonSocialeAdresse = mb_strtoupper($this->settings->value, 'UTF-8');

        // Facture - telephone
        $this->settings->get('Facture - telephone', 'type');
        $this->telephone = mb_strtoupper($this->settings->value, 'UTF-8');

        // Facture - RCS
        $this->settings->get('Facture - RCS', 'type');
        $this->rcs = mb_strtoupper($this->settings->value, 'UTF-8');

        // Facture - TVA INTRACOMMUNAUTAIRE
        $this->settings->get('Facture - TVA INTRACOMMUNAUTAIRE', 'type');
        $this->tvaIntra = mb_strtoupper($this->settings->value, 'UTF-8');


        $this->setDisplay('footer_facture');
    }

    private function GenerateInvoiceEFHtml($iIdProject)
    {
        $this->lng['pdf-facture'] = $this->ln->selectFront('pdf-facture', $this->language, $this->App);

        // si le client existe
        $this->compteur_factures = $this->loadData('compteur_factures');
        $this->transactions = $this->loadData('transactions');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->factures = $this->loadData('factures');

        // TVA
        $this->settings->get('TVA', 'type');
        $this->tva = $this->settings->value;

        //on recup l'entreprise
        $this->companies->get($this->clients->id_client, 'id_client_owner');

        // et on recup le projet
        if ($this->projects->get($iIdProject, 'id_company = ' . $this->companies->id_company . ' AND id_project')) {

            $histoRemb = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = 8', 'added DESC', 0, 1);
            if ($histoRemb != false) {
                $this->dateRemb = $histoRemb[0]['added'];

                $timeDateRemb = strtotime($this->dateRemb);


                $compteur = $this->compteur_factures->compteurJournalier($this->projects->id_project);

                $this->num_facture = 'FR-E' . date('Ymd', $timeDateRemb) . str_pad($compteur, 5, "0", STR_PAD_LEFT);

                $this->transactions->get($this->projects->id_project, 'type_transaction = 9 AND status = 1 AND etat = 1 AND id_project');

                $this->ttc = ($this->transactions->montant_unilend / 100);

                $cm = ($this->tva + 1); // CM
                $this->ht = ($this->ttc / $cm); // HT
                $this->taxes = ($this->ttc - $this->ht); // TVA

                $montant = ((str_replace('-', '', $this->transactions->montant) + $this->transactions->montant_unilend) / 100); // Montant pret
                $txCom = (0 < $montant) ? round(($this->ht / $montant) * 100, 0) : 0; // taux commission

                if (!$this->factures->get($this->projects->id_project, 'type_commission = 1 AND id_company = ' . $this->companies->id_company . ' AND id_project')) {
                    $this->factures->num_facture = $this->num_facture;
                    $this->factures->date = $this->dateRemb;
                    $this->factures->id_company = $this->companies->id_company;
                    $this->factures->id_project = $this->projects->id_project;
                    $this->factures->ordre = 0;
                    $this->factures->type_commission = 1; // financement
                    $this->factures->commission = $txCom;
                    $this->factures->montant_ht = ($this->ht * 100);
                    $this->factures->tva = ($this->taxes * 100);
                    $this->factures->montant_ttc = ($this->ttc * 100);
                    $this->factures->create();

                }
            }

            $this->setDisplay('facture_EF_html');
            $sDisplayInvoice = $this->sDisplay;
            $this->GenerateFooterInvoice();
            $this->sDisplay = $sDisplayInvoice . $this->sDisplay;
        }
    }

    public function _facture_ER($sHash, $iIdProject, $iOrdre)
    {
        // si le client existe
        if ($this->clients->get($sHash, 'hash') && isset($iIdProject)) {
            $this->oEcheanciersEmprunteur = $this->loadData('echeanciers_emprunteur');

            //on recup l'entreprise
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            // et on recup le projet
            if ($this->projects->get($iIdProject, 'id_company = ' . $this->companies->id_company . ' AND id_project')) {
                // on recup l'echeance concernée
                if ($this->oEcheanciersEmprunteur->get($this->projects->id_project, 'ordre = ' . $iOrdre . '  AND id_project')) {
                    $path = $this->path . 'protected/pdf/facture/';
                    $sNamePdfClient = 'FACTURE-UNILEND-' . $sHash;
                    $name = 'facture_ER'; // nom du pdf (type PDF)
                    $nom_fichier = ($iIdProject != '') ? $name . '-' . $sHash . "-" . $iIdProject . "-" . $iOrdre . ".pdf" : $name . '-' . $sHash . ".pdf";

                    $this->GenerateInvoiceERHtml($iIdProject, $iOrdre);
                    //We generate pdf file
                    $this->WritePdf($path . $nom_fichier, 'invoice');
                }
            }
        }
    }

    private function GenerateInvoiceERHtml($iIdProject, $iOrdre)
    {
        $this->lng['pdf-facture'] = $this->ln->selectFront('pdf-facture', $this->language, $this->App);

        // si le client existe
        $this->compteur_factures = $this->loadData('compteur_factures');
        $this->echeanciers = $this->loadData('echeanciers');
        $this->factures = $this->loadData('factures');

        // TVA
        $this->settings->get('TVA', 'type');
        $this->tva = $this->settings->value;

        // Commission remboursement
        $this->settings->get('Commission remboursement', 'type');
        $txcom = $this->settings->value;


        //on recup l'entreprise
        $this->companies->get($this->clients->id_client, 'id_client_owner');

        // et on recup le projet
        if ($this->projects->get($iIdProject, 'id_company = ' . $this->companies->id_company . ' AND id_project')) {
            $uneEcheancePreteur = $this->echeanciers->select('id_project = ' . $this->projects->id_project . ' AND ordre = ' . $iOrdre, '', 0, 1);
            $this->date_echeance_reel = $uneEcheancePreteur[0]['date_echeance_reel'];

            $time_date_echeance_reel = strtotime($this->date_echeance_reel);

            // on recup l'echeance concernée
            if ($this->oEcheanciersEmprunteur->get($this->projects->id_project, 'ordre = ' . $iOrdre . '  AND id_project')) {
                $compteur = $this->compteur_factures->compteurJournalier($this->projects->id_project);

                $this->num_facture = 'FR-E' . date('Ymd', $time_date_echeance_reel) . str_pad($compteur, 5, "0", STR_PAD_LEFT);

                $this->ht = ($this->oEcheanciersEmprunteur->commission / 100);
                $this->taxes = ($this->oEcheanciersEmprunteur->tva / 100);
                $this->ttc = ($this->ht + $this->taxes);


                if (!$this->factures->get($this->projects->id_project, 'ordre = ' . $iOrdre . ' AND  type_commission = 2 AND id_company = ' . $this->companies->id_company . ' AND id_project')) {
                    $this->factures->num_facture = $this->num_facture;
                    $this->factures->date = $this->date_echeance_reel;
                    $this->factures->id_company = $this->companies->id_company;
                    $this->factures->id_project = $this->projects->id_project;
                    $this->factures->ordre = $this->params[2];
                    $this->factures->type_commission = 2; // remboursement
                    $this->factures->commission = ($txcom * 100);
                    $this->factures->montant_ht = ($this->ht * 100);
                    $this->factures->tva = ($this->taxes * 100);
                    $this->factures->montant_ttc = ($this->ttc * 100);
                    $this->factures->create();

                }

            }

            $this->setDisplay('facture_ER_html');
            $sDisplayInvoice = $this->sDisplay;
            $this->GenerateFooterInvoice();
            $this->sDisplay = $sDisplayInvoice . $this->sDisplay;
        }
    }

    function _testupdate()
    {
        $this->updateEcheances(3384, '2014-11-28 17:26:26');
        die;
    }


    // Mise a jour des dates echeances preteurs et emprunteur (utilisé pour se baser sur la date de creation du pouvoir)
    public function updateEcheances($id_project, $dateRemb)
    {

        ini_set('max_execution_time', 300); //300 seconds = 5 minutes
        // chargement des datas
        $projects = $this->loadData('projects');
        $projects_status = $this->loadData('projects_status');
        $projects_status_history = $this->loadData('projects_status_history');
        $echeanciers = $this->loadData('echeanciers');
        $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');

        // Chargement des librairies
        $jo = $this->loadLib('jours_ouvres');

        // On definit le nombre de mois et de jours apres la date de fin pour commencer le remboursement
        $this->settings->get('Nombre de mois apres financement pour remboursement', 'type');
        $nb_mois = $this->settings->value;
        $this->settings->get('Nombre de jours apres financement pour remboursement', 'type');
        $nb_jours = $this->settings->value;

        // ID PROJECT //
        $id_project = $id_project;

        // On recup le projet
        $projects->get($id_project, 'id_project');

        // On recupere le statut
        $projects_status->getLastStatut($projects->id_project);

        // si c'est fundé
        if ($projects_status->status == 60) {
            //echo 'ici';
            // On recup la date de statut remb
            //$remb = $projects_status_history->select('id_project = '.$projects->id_project.' AND id_project_status = 8','added DESC',0,1);
            //$dateRemb = $remb[0]['added'];

            // on parcourt les mois
            for ($ordre = 1; $ordre <= $projects->period; $ordre++) {

                // on prend le nombre de jours dans le mois au lieu du mois
                $nbjourstemp = mktime(0, 0, 0, date("m") + $ordre, 1, date("Y"));
                $nbjoursMois += date('t', $nbjourstemp);

                // Date d'echeance preteur
                $date_echeance = $this->dates->dateAddMoisJours($dateRemb, 0, $nb_jours + $nbjoursMois);
                $date_echeance = date('Y-m-d H:i', $date_echeance) . ':00';

                // Date d'echeance emprunteur
                $date_echeance_emprunteur = $this->dates->dateAddMoisJours($dateRemb, 0, $nbjoursMois);


                // on retire 6 jours ouvrés
                $date_echeance_emprunteur = $jo->display_jours_ouvres($date_echeance_emprunteur, 6);
                $date_echeance_emprunteur = date('Y-m-d H:i', $date_echeance_emprunteur) . ':00';

                /*echo '------------------<br>';
                echo $date_echeance.'<br>';
                echo $date_echeance_emprunteur.'<br>';*/

                // Update echeanciers preteurs
                $echeanciers->onMetAjourLesDatesEcheances($projects->id_project, $ordre, $date_echeance, $date_echeance_emprunteur);

                // Update echeanciers emprunteurs
                $echeanciers_emprunteur->onMetAjourLesDatesEcheancesE($id_project, $ordre, $date_echeance_emprunteur);
            }
        }
    }

    public function _declaration_de_creances()
    {
        // si le client existe
        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash') && isset($this->params[1])) {
            $this->oLendersAccounts = $this->loadData('lenders_accounts');
            $this->oLoans = $this->loadData('loans');

            $this->oLendersAccounts->get($this->clients->id_client, 'id_client_owner');

            if ($this->oLoans->get($this->oLendersAccounts->id_lender_account, 'id_loan = ' . $this->params[1] . ' AND id_lender')) {
                $path = $this->path . 'protected/pdf/declaration_de_creances/' . $this->oLoans->id_project . '/'; // path d'enregistrement
                $path = ($this->oLoans->id_project == '1456') ? $path : $path . $this->clients->id_client . '/';

                $sNamePdfClient = 'DECLARATION-DE-CREANCES-UNILEND-' . $this->clients->hash . '-' . $this->oLoans->id_loan;
                $slug = $this->params[0]; // hash client
                $name = 'declarationDeCreances'; // nom du pdf (type PDF)
                $nom_fichier = ($this->params[1] != '') ? $name . '-' . $slug . "-" . $this->params[1] . ".pdf" : $name . '-' . $slug . ".pdf";

                $this->GenerateClaimsHtml();
                //We generate pdf file
                $this->WritePdf($path . $nom_fichier, 'claims');
                $this->ReadPdf($path . $nom_fichier, $sNamePdfClient);
            }
        }
    }

    private function GenerateClaimsHtml()
    {
        // si le client existe
        $this->oLendersAccounts = $this->loadData('lenders_accounts');
        $this->oLoans = $this->loadData('loans');
        $this->pays = $this->loadData('pays_v2');
        $this->echeanciers = $this->loadData('echeanciers');
        $this->companiesEmpr = $this->loadData('companies');

        $this->oLendersAccounts->get($this->clients->id_client, 'id_client_owner');

        if ($this->oLoans->get($this->oLendersAccounts->id_lender_account, 'id_loan = ' . $this->params[1] . ' AND id_lender')) {

            // particulier
            if (in_array($this->clients->type, array(1, 4))) {

                // client adresse
                $this->clients_adresses->get($this->clients->id_client, 'id_client');

                // pays fiscal
                if ($this->clients_adresses->id_pays_fiscal == 0) $this->clients_adresses->id_pays_fiscal = 1;
                $this->pays->get($this->clients_adresses->id_pays_fiscal, 'id_pays');
                $this->pays_fiscal = $this->pays->fr;

            } // entreprise
            else {
                $this->companies->get($this->clients->id_client, 'id_client_owner');

                // pays fiscal
                if ($this->companies->id_pays == 0) $this->companies->id_pays = 1;
                $this->pays->get($this->companies->id_pays, 'id_pays');
                $this->pays_fiscal = $this->pays->fr;
            }

            // projet
            $this->projects->get($this->oLoans->id_project, 'id_project');

            // entreprise de l'emprunteur
            $this->companiesEmpr->get($this->projects->id_company, 'id_company');

            // Nature
            $this->nature_var = "Procédure de sauvegarde";

            // mandataire personalisé
            $this->mandataires_var = "";


            $this->arrayDeclarationCreance = array(1456 => '27/11/2014',
                1009 => '15/04/2015',
                1614 => '27/05/2015',
                3089 => '29/06/2015');

            if ($this->oLoans->id_project == 1614) {
                //plus de mandataire dans le pdf, on l'aura que dans le mail (Note BT: 17793)
                //$this->mandataires_var = "
                //    Me ROUSSEL Bernard
                //    <br />
                //    850, rue Etienne Lenoir. Km Delta
                //    <br />
                //    30 900 Nîmes
                //    ";


                // Nature
                $this->nature_var = "Liquidation judiciaire";
            }
            if ($this->oLoans->id_project == 3089)
                $this->nature_var = "Procédure de sauvegarde";


            // echu
            $this->echu = $this->echeanciers->getSumARemb($this->oLendersAccounts->id_lender_account . ' AND LEFT(date_echeance,10) >= "2015-04-19" AND LEFT(date_echeance,10) <= "' . date('Y-m-d') . '" AND id_loan = ' . $this->oLoans->id_loan, 'montant');

            // echoir
            $this->echoir = $this->echeanciers->getSumARemb($this->oLendersAccounts->id_lender_account . ' AND LEFT(date_echeance,10) > "' . date('Y-m-d') . '" AND id_loan = ' . $this->oLoans->id_loan, 'capital');

            // total
            $this->total = ($this->echu + $this->echoir);

            // last echeance
            $lastEcheance = $this->echeanciers->select('id_lender = ' . $this->oLendersAccounts->id_lender_account . ' AND id_loan = ' . $this->oLoans->id_loan, 'ordre DESC', 0, 1);
            $this->lastEcheance = date('d/m/Y', strtotime($lastEcheance[0]['date_echeance']));

            $this->setDisplay('declaration_de_creances_html');

        } else {
            header('Location:' . $this->lurl);
        }
    }


    public function _vos_operations_pdf_indexation()
    {
        $post_id_client = $_SESSION['filtre_vos_operations']['id_client'];


        $filtre_vos_operations = serialize($_SESSION['filtre_vos_operations']);
        $filtre_vos_operations = $this->ficelle->base64url_encode($filtre_vos_operations);

        $path = $this->path . 'protected/operations_export_pdf/' . $post_id_client . '/'; // path d'enregistrement
        $sNamePdfClient = 'vos_operations_' . date('Y-m-d') . '.pdf';

        $this->GenerateOperationsHtml($filtre_vos_operations);
        //We generate pdf file
        $this->WritePdf($path . $sNamePdfClient, 'operations');
        $this->ReadPdf($path . $sNamePdfClient, $sNamePdfClient);
    }

    private function GenerateOperationsHtml($sFiltreOp)
    {
        if (isset($sFiltreOp)) {

            $this->wallets_lines = $this->loadData('wallets_lines');
            $this->bids = $this->loadData('bids');
            $this->oLoans = $this->loadData('loans');
            $this->echeanciers = $this->loadData('echeanciers');
            $this->oLendersAccounts = $this->loadData('lenders_accounts');

            $this->lng['preteur-operations-vos-operations'] = $this->ln->selectFront('preteur-operations-vos-operations', $this->language, $this->App);
            $this->lng['preteur-operations-pdf'] = $this->ln->selectFront('preteur-operations-pdf', $this->language, $this->App);


            $filtre_vos_operations = $this->ficelle->base64url_decode($sFiltreOp);
            $filtre_vos_operations = unserialize($filtre_vos_operations);

            $post_debut = $filtre_vos_operations['debut'];
            $post_fin = $filtre_vos_operations['fin'];
            $post_nbMois = $filtre_vos_operations['nbMois'];
            $post_annee = $filtre_vos_operations['annee'];
            $post_tri_type_transac = $filtre_vos_operations['tri_type_transac'];
            $post_tri_projects = $filtre_vos_operations['tri_projects'];
            $post_id_last_action = $filtre_vos_operations['id_last_action'];
            $post_order = $filtre_vos_operations['order'];
            $post_type = $filtre_vos_operations['type'];
            $post_id_client = $filtre_vos_operations['id_client'];

            $this->clients->get($post_id_client, 'id_client');
            $this->clients_adresses->get($post_id_client, 'id_client');
            $this->oLendersAccounts->get($post_id_client, 'id_client_owner');

            if (isset($post_id_last_action) && in_array($post_id_last_action, array('debut', 'fin'))) {

                $debutTemp = explode('/', $post_debut);
                $finTemp = explode('/', $post_fin);

                $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');    // date debut
                $date_fin_time = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');            // date fin

                // On sauvegarde la derniere action
                $_SESSION['id_last_action'] = $post_id_last_action;

            } // NB mois
            elseif (isset($post_id_last_action) && $post_id_last_action == 'nbMois') {

                $nbMois = $post_nbMois;

                $date_debut_time = mktime(0, 0, 0, date("m") - $nbMois, date("d"), date('Y')); // date debut
                $date_fin_time = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date fin

                // On sauvegarde la derniere action
                $_SESSION['id_last_action'] = $post_id_last_action;
            } // Annee
            elseif (isset($post_id_last_action) && $post_id_last_action == 'annee') {

                $year = $post_annee;

                $date_debut_time = mktime(0, 0, 0, 1, 1, $year);    // date debut

                if (date('Y') == $year)
                    $date_fin_time = mktime(0, 0, 0, date('m'), date('d'), $year); // date fin
                else
                    $date_fin_time = mktime(0, 0, 0, 12, 31, $year); // date fin

                // On sauvegarde la derniere action
                $_SESSION['id_last_action'] = $post_id_last_action;

            } // si on a une session
            elseif (isset($post_id_last_action)) {

                if ($post_debut != "" && $post_fin != "") {
                    //echo 'toto';
                    $debutTemp = explode('/', $post_debut);
                    $finTemp = explode('/', $post_fin);

                    $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');    // date debut
                    $date_fin_time = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');            // date fin
                } elseif ($post_id_last_action == 'nbMois') {
                    //echo 'titi';
                    $nbMois = $post_nbMois;

                    $date_debut_time = mktime(0, 0, 0, date("m") - $nbMois, date("d"), date('Y')); // date debut
                    $date_fin_time = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date fin
                } elseif ($post_id_last_action == 'annee') {
                    //echo 'tata';
                    $year = $post_annee;

                    $date_debut_time = mktime(0, 0, 0, 1, 1, $year);    // date debut
                    $date_fin_time = mktime(0, 0, 0, 12, 31, $year); // date fin
                }
            } // Par defaut (on se base sur le 1M)
            else {
                //echo 'cc';
                $date_debut_time = mktime(0, 0, 0, date("m") - 1, date("d"), date('Y')); // date debut
                $date_fin_time = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date fin
            }

            // on recup au format sql
            $this->date_debut = date('Y-m-d', $date_debut_time);
            $this->date_fin = date('Y-m-d', $date_fin_time);
            //////////// FIN PARTIE DATES //////////////


            $array_type_transactions = array(
                1 => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
                2 => array(1 => $this->lng['preteur-operations-vos-operations']['offre-en-cours'], 2 => $this->lng['preteur-operations-vos-operations']['offre-rejetee'], 3 => $this->lng['preteur-operations-vos-operations']['offre-acceptee']),
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
                23 => $this->lng['preteur-operations-vos-operations']['remboursement-anticipe-preteur']);
            ////////// DEBUT PARTIE TRI TYPE TRANSAC /////////////

            $array_type_transactions_liste_deroulante = array(
                1 => '1,2,3,4,5,7,8,16,17,19,20,23',
                2 => '3,4,7,8',
                3 => '3,4,7',
                4 => '8',
                5 => '2',
                6 => '5,23');

            if (isset($post_tri_type_transac)) {

                $tri_type_transac = $array_type_transactions_liste_deroulante[$post_tri_type_transac];
            }

            ////////// FIN PARTIE TRI TYPE TRANSAC /////////////


            ////////// DEBUT TRI PAR PROJET /////////////
            if (isset($post_tri_projects)) {
                if (in_array($post_tri_projects, array(0, 1))) {
                    $tri_project = '';
                } else {
                    //$tri_project = ' HAVING le_id_project = '.$post_tri_projects;
                    $tri_project = ' AND le_id_project = ' . $post_tri_projects;
                }
            }
            ////////// FIN TRI PAR PROJET /////////////


            $order = 'date_operation DESC, id_transaction DESC';
            if (isset($post_type) && isset($post_order)) {

                $this->type = $post_type;
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


            // On va chercher ce qu'on a dans la table d'indexage
            $this->lTrans = $this->indexage_vos_operations->select('type_transaction IN (' . $tri_type_transac . ') AND id_client = ' . $this->clients->id_client . ' AND LEFT(date_operation,10) >= "' . $this->date_debut . '" AND LEFT(date_operation,10) <= "' . $this->date_fin . '"' . $tri_project, $order);

            // filtre secondaire
            $this->lProjectsLoans = $this->indexage_vos_operations->get_liste_libelle_projet('type_transaction IN (' . $tri_type_transac . ') AND id_client = ' . $this->clients->id_client . ' AND LEFT(date_operation,10) >= "' . $this->date_debut . '" AND LEFT(date_operation,10) <= "' . $this->date_fin . '"');

            $this->setDisplay('vos_operations_pdf_html_indexation');
        }
    }
}