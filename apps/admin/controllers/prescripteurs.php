<?php

class prescripteursController extends bootstrap
{
    public function prescripteursController($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        // Controle d'acces à la rubrique
        $this->users->checkAccess('emprunteurs');

        // Activation du menu
        $this->menu_admin = 'emprunteurs';
    }

    public function _default()
    {
        header('Location:' . $this->lurl . '/prescripteur/gestion');
    }

    public function _gestion()
    {
        // Chargement du data
        /** @var clients $oClients */
        $oClients = $this->loadData('clients');

        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        if (isset($this->params[0]) && is_numeric($this->params[0])) {
            $this->aPrescripteurs = $oClients->searchPrescripteur($this->params[0]);
        }

        if (isset($_POST['form_search_prescripteur'])) {
            $this->aPrescripteurs = $oClients->searchPrescripteur('', $_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['company_name'], $_POST['siren']);

            $_SESSION['freeow']['title']   = 'Recherche d\'un prescripteur';
            $_SESSION['freeow']['message'] = 'La recherche est termin&eacute;e !';
        }
    }

    public function _edit()
    {
        $this->clients          = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');
        $this->prescripteurs    = $this->loadData('prescripteurs');
        $this->companies        = $this->loadData('companies');
        $this->projects         = $this->loadData('projects');

        if (
            ! isset($this->params[0])
            || ! $this->prescripteurs->get($this->params[0], 'id_prescripteur')
            || ! $this->clients->get($this->prescripteurs->id_client, 'id_client')
            || ! $this->clients_adresses->get($this->clients->id_client, 'id_client')
            || ! $this->companies->get($this->prescripteurs->id_entite, 'id_company')
        ) {
            header('Location:' . $this->lurl . '/prescripteurs/gestion/');
            return;
        }

        $this->aProjects      = $this->projects->searchDossiers('', '', '', '', '', '', '', '', '', $this->params[0]);
        $this->iProjectsCount = array_shift($this->aProjects);

        if (isset($_POST['form_edit_prescripteur'])) {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $this->clients->civilite  = $_POST['civilite'];
            $this->clients->nom       = $this->ficelle->majNom($_POST['nom']);
            $this->clients->prenom    = $this->ficelle->majNom($_POST['prenom']);
            $this->clients->email     = trim($_POST['email']);
            $this->clients->telephone = str_replace(' ', '', $_POST['telephone']);
            $this->clients->id_langue = 'fr';
            $this->clients->update();

            $this->clients_adresses->adresse1 = $_POST['adresse'];
            $this->clients_adresses->ville    = $_POST['ville'];
            $this->clients_adresses->cp       = $_POST['cp'];
            $this->clients_adresses->update();

            $this->companies->siren = $_POST['siren'];
            $this->companies->name  = $_POST['company_name'];
            $this->companies->iban  = $_POST['iban'];
            $this->companies->bic   = $_POST['bic'];
            $this->companies->update();

            $serialize = serialize(array('id_prescripteur' => $this->prescripteurs->id_prescripteur, 'post' => $_POST));
            $this->users_history->histo(5, 'edit prescripteur', $_SESSION['user']['id_user'], $serialize);

            $_SESSION['freeow']['title']   = 'prescripteur mis a jour';
            $_SESSION['freeow']['message'] = 'le prescripteur a &eacute;t&eacute; mis a jour !';

            header('Location: ' . $this->lurl . '/prescripteurs/edit/' . $this->prescripteurs->id_prescripteur);
        }
    }

    public function _add_client()
    {
        $this->hideDecoration();

        if (isset($_POST['send_add_prescripteur'])) {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $this->autoFireView = false;
            /** @var clients $oClients */
            $oClients = $this->loadData('clients');
            /** @var clients_adresse $oClientsAdresses */
            $oClientsAdresses = $this->loadData('clients_adresses');
            /** @var prescripteurs $oPrescripteurs */
            $oPrescripteurs = $this->loadData('prescripteurs');
            /** @var companies $oCompanies */
            $oCompanies = $this->loadData('companies');

            $oClients->civilite  = $_POST['civilite'];
            $oClients->nom       = $this->ficelle->majNom($_POST['nom']);
            $oClients->prenom    = $this->ficelle->majNom($_POST['prenom']);
            $oClients->email     = trim($_POST['email']);
            $oClients->telephone = str_replace(' ', '', $_POST['telephone']);
            $oClients->id_langue = 'fr';

            $oClientsAdresses->adresse1 = $_POST['adresse'];
            $oClientsAdresses->ville    = $_POST['ville'];
            $oClientsAdresses->cp       = $_POST['cp'];

            $aCompany = $oCompanies->select('siren = ' . $_POST['siren'], 'added ASC', 0, 1);
            if ($aCompany) {
                $iCompanyId = $aCompany[0]['id_company'];
            } else {
                $oCompanies->siren = $_POST['siren'];
                $oCompanies->name  = $_POST['company_name'];
                $oCompanies->iban  = $_POST['iban'];
                $oCompanies->bic   = $_POST['bic'];
                $iCompanyId        = $oCompanies->create();
            }

            $oClients->id_client = $oClients->create();

            $oClientsAdresses->id_client = $oClients->id_client;
            $oClientsAdresses->create();

            $oPrescripteurs->id_client = $oClients->id_client;
            $oPrescripteurs->id_entite = $iCompanyId;

            $oPrescripteurs->id_prescripteur = $oPrescripteurs->create();

            $serialize = serialize(array('id_prescripteur' => $oPrescripteurs->id_prescripteur, 'post' => $_POST, 'files' => $_FILES));
            $this->users_history->histo(5, 'add prescripteur', $_SESSION['user']['id_user'], $serialize);

            echo 'OK';
        }
    }

    public function _search_ajax()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $this->aClients = array();

        if (isset($this->params[0])) {
            $sSearch = $this->params[0];

            $this->clients = $this->loadData('clients');

            $this->aClients = $this->clients->searchPrescripteur('', $sSearch, $sSearch, $sSearch, $sSearch, $sSearch, 0, 30, 'OR');
        }
    }
}
