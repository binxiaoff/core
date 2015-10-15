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
        $this->clients = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');

        $this->prescripteurs = $this->loadData('prescripteurs');

        $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        if (isset($this->params[0]) && is_numeric($this->params[0])) {
            $this->aPrescripteurs = $this->clients->searchPrescripteur($this->params[0]);
        }

        if (isset($_POST['form_search_prescripteur'])) {
            // Recuperation de la liste des clients
            $this->aPrescripteurs = $this->clients->searchPrescripteur($_POST['nom'], $_POST['prenom'], $_POST['email']);
            // Mise en session du message
            $_SESSION['freeow']['title'] = 'Recherche d\'un client';
            $_SESSION['freeow']['message'] = 'La recherche est termin&eacute;e !';
        }
    }

    public function _edit()
    {
        // Chargement du data
        $this->clients = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');
        $this->prescripteurs = $this->loadData('prescripteurs');

        if (! isset($this->params[0])
            || ! $this->clients->get($this->params[0], 'id_client')
            || ! $this->prescripteurs->get($this->clients->id_client, 'id_client')
            || ! $this->clients_adresses->get($this->clients->id_client, 'id_client')
        ) {
            header('Location:' . $this->lurl . '/prescripteurs/gestion/');
            return;
        }

        $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        if (isset($_POST['form_edit_prescripteur'])) {
            $this->clients->civilite = $_POST['civilite'];
            $this->clients->nom = $this->ficelle->majNom($_POST['nom']);
            $this->clients->prenom = $this->ficelle->majNom($_POST['prenom']);
            $this->clients->email = trim($_POST['email']);
            $this->clients->telephone = str_replace(' ', '', $_POST['telephone']);

            $this->clients_adresses->adresse1 = $_POST['adresse'];
            $this->clients_adresses->ville = $_POST['ville'];
            $this->clients_adresses->cp = $_POST['cp'];

            $this->clients->id_langue = 'fr';

            // On crée le client
            $this->clients->update();
            $this->clients_adresses->update();

            // Histo user //
            $serialize = serialize(
                array('id_client' => $this->clients->id_client, 'post' => $_POST, 'files' => $_FILES)
            );
            $this->users_history->histo(5, 'edit prescripteur', $_SESSION['user']['id_user'], $serialize);
            ////////////////
            // Mise en session du message
            $_SESSION['freeow']['title'] = 'prescripteur mis a jour';
            $_SESSION['freeow']['message'] = 'le prescripteur a &eacute;t&eacute; mis a jour !';

            header('Location:' . $this->lurl . '/prescripteurs/edit/' . $this->clients->id_client);
        }

    }

    public function _add_client()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug = false;

        // On place le redirect sur la home
        $_SESSION['request_url'] = $this->url;

        // Chargement du data
        $this->clients = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');

        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'id_client')) {
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            $this->adresse = $this->clients_adresses->adresse1;
            $this->ville = $this->clients_adresses->ville;
            $this->cp = $this->clients_adresses->cp;
        }

        $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        if (isset($_POST['send_add_prescripteur'])) {
            $this->autoFireView = false;

            $this->prescripteurs = $this->loadData('prescripteurs');

            $this->clients->civilite = $_POST['civilite'];
            $this->clients->nom = $this->ficelle->majNom($_POST['nom']);
            $this->clients->prenom = $this->ficelle->majNom($_POST['prenom']);
            $this->clients->email = trim($_POST['email']);
            $this->clients->telephone = str_replace(' ', '', $_POST['telephone']);

            $this->clients_adresses->adresse1 = $_POST['adresse'];
            $this->clients_adresses->ville = $_POST['ville'];
            $this->clients_adresses->cp = $_POST['cp'];

            $this->clients->id_langue = 'fr';

            // On crée le client
            $this->clients->id_client = $this->clients->create();

            $this->clients_adresses->id_client = $this->clients->id_client;
            $this->clients_adresses->create();

            $this->prescripteurs->id_client = $this->clients->id_client;

            // On crée le prescripteur
            $this->prescripteurs->id_prescripteur = $this->prescripteurs->create();

            // Histo user //
            $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST, 'files' => $_FILES));
            $this->users_history->histo(5, 'add prescripteur', $_SESSION['user']['id_user'], $serialize);
            ////////////////
            // Mise en session du message
            //$_SESSION['freeow']['title'] = 'prescripteur crt&eacute;t&eacute;';
            //$_SESSION['freeow']['message'] = 'le prescripteur a &eacute;t&eacute; cr&eacute;t&eacute; !';

            echo 'OK';
        }
    }

    public function _search_ajax()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug = false;

        $this->aClients = array();

        if (isset($this->params[0])) {
            $sSearch = $this->params[0];

            $this->clients = $this->loadData('clients');

            $this->aClients = $this->clients->searchPrescripteur('', $sSearch, $sSearch, $sSearch, 0, 30, 'OR');
        }
    }
}
