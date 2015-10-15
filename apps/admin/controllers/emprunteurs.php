<?php

class emprunteursController extends bootstrap
{

    var $Command;

    function emprunteursController($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        // Controle d'acces à la rubrique
        $this->users->checkAccess('emprunteurs');

        // Activation du menu
        $this->menu_admin = 'emprunteurs';
    }

    function _default()
    {
        // On remonte la page dans l'arborescence
        if (isset($this->params[0]) && $this->params[0] == 'up')
        {
            $this->tree->moveUp($this->params[1]);

            header('Location:' . $this->lurl . '/emprunteurs');
            die;
        }

        // On descend la page dans l'arborescence
        if (isset($this->params[0]) && $this->params[0] == 'down')
        {
            $this->tree->moveDown($this->params[1]);

            header('Location:' . $this->lurl . '/emprunteurs');
            die;
        }

        // On supprime la page et ses dependances
        if (isset($this->params[0]) && $this->params[0] == 'delete')
        {
            $this->tree->deleteCascade($this->params[1]);

            // Mise en session du message
            $_SESSION['freeow']['title'] = 'Suppression d\'une page';
            $_SESSION['freeow']['message'] = 'La page et ses enfants ont bien &eacute;t&eacute; supprim&eacute;s !';

            header('Location:' . $this->lurl . '/emprunteurs');
            die;
        }
    }

    function _gestion()
    {
        // Chargement du data
        $this->clients = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');
        $this->companies = $this->loadData('companies');
        $this->companies_details = $this->loadData('companies_details');
        $this->companies_bilans = $this->loadData('companies_bilans');

        if ($this->clients->telephone != '')
            $this->clients->telephone = trim(chunk_split($this->clients->telephone, 2, ' '));

        if (isset($_POST['form_search_emprunteur']))
        {
            if ($_POST['status'] == 'choisir')
                $statut = '';
            else
                $statut = $_POST['status'];

            // Recuperation de la liste des clients
            $this->lClients = $this->clients->searchEmprunteurs('', $_POST['nom'], $_POST['email'], $_POST['prenom'], $_POST['societe'], $_POST['siret'], $statut);
            // Mise en session du message
            $_SESSION['freeow']['title'] = 'Recherche d\'un client';
            $_SESSION['freeow']['message'] = 'La recherche est termin&eacute;e !';
        }

        if (isset($_POST['form_add_emprunteur']))
        {


            $this->clients->nom = $this->ficelle->majNom($_POST['nom']);
            $this->clients->prenom = $this->ficelle->majNom($_POST['prenom']);
            $this->clients->email = trim($_POST['email']);
            $this->companies->email_facture = trim($_POST['email']);
            $this->clients->telephone = str_replace(' ', '', $_POST['telephone']);

            // On precise que c'est un emprunteur
            $this->clients->status_pre_emp = 2;

            $this->companies->name = $_POST['societe'];
            $this->companies->sector = $_POST['secteur'];

            $this->clients_adresses->adresse1 = $_POST['adresse'];
            $this->clients_adresses->ville = $_POST['ville'];
            $this->clients_adresses->cp = $_POST['cp'];

            // cni/passeport
            if (isset($_FILES['cni_passeport']) && $_FILES['cni_passeport']['name'] != '')
            {
                $this->upload->setUploadDir($this->path, 'protected/clients/cni_passeport/');
                if ($this->upload->doUpload('cni_passeport'))
                {
                    if ($this->clients->cni_passeport != '')
                        @unlink($this->path . 'protected/clients/cni_passeport/' . $this->clients->cni_passeport);
                    $this->clients->cni_passeport = $this->upload->getName();
                }
            }
            // fichier_rib 
            if (isset($_FILES['signature']) && $_FILES['signature']['name'] != '')
            {
                $this->upload->setUploadDir($this->path, 'protected/clients/signature/');
                if ($this->upload->doUpload('signature'))
                {
                    if ($this->clients->signature != '')
                        @unlink($this->path . 'protected/clients/signature/' . $this->clients->signature);
                    $this->clients->signature = $this->upload->getName();
                }
            }

            $this->clients->id_langue = 'fr';

            // On crée le client
            $this->clients->id_client = $this->clients->create();

            $this->clients_adresses->id_client = $this->clients->id_client;
            $this->clients_adresses->create();

            $this->companies->id_client_owner = $this->clients->id_client;

            // On crée l'entreprise
            $this->companies->id_company = $this->companies->create();

            $this->companies_details->id_company = $this->companies->id_company;
            $this->companies_details->create();

            // Creation companie bilans
            $tablAnneesBilans = array(date('Y') - 3, date('Y') - 2, date('Y') - 1, date('Y'), date('Y') + 1);
            foreach ($tablAnneesBilans as $a)
            {
                $this->companies_bilans->id_company = $this->companies->id_company;
                $this->companies_bilans->date = $a;
                $this->companies_bilans->create();
            }

            // Histo user //
            $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST, 'files' => $_FILES));
            $this->users_history->histo(5, 'add emprunteur', $_SESSION['user']['id_user'], $serialize);
            ////////////////
            // Mise en session du message
            $_SESSION['freeow']['title'] = 'emprunteur crt&eacute;t&eacute;';
            $_SESSION['freeow']['message'] = 'l\'emprunteur a &eacute;t&eacute; crt&eacute;t&eacute; !';

            header('Location:' . $this->lurl . '/emprunteurs/gestion/' . $this->clients->id_client);
            die;
        }
    }

    function _edit_client()
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
        $this->companies = $this->loadData('companies');

        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'id_client'))
        {
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            // meme adresse que le siege
            if ($this->companies->status_adresse_correspondance == 1)
            {
                $this->adresse = $this->companies->adresse1;
                $this->ville = $this->companies->city;
                $this->cp = $this->companies->zip;
            }
            else
            {
                $this->adresse = $this->clients_adresses->adresse1;
                $this->ville = $this->clients_adresses->ville;
                $this->cp = $this->clients_adresses->cp;
            }
        }
    }

    function _add_client()
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
        $this->companies = $this->loadData('companies');


        // Liste deroulante secteurs
        $this->settings->get('Liste deroulante secteurs', 'type');
        $this->lSecteurs = explode(';', $this->settings->value);

        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'id_client'))
        {
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            $this->companies->get($this->clients->id_client, 'id_client_owner');

            // meme adresse que le siege
            if ($this->companies->status_adresse_correspondance == 1)
            {
                $this->adresse = $this->companies->adresse1;
                $this->ville = $this->companies->city;
                $this->cp = $this->companies->zip;
            }
            else
            {
                $this->adresse = $this->clients_adresses->adresse1;
                $this->ville = $this->clients_adresses->ville;
                $this->cp = $this->clients_adresses->cp;
            }
        }
    }

    function _edit()
    {
        // Chargement du data
        $this->clients = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');
        $this->companies = $this->loadData('companies');
        $this->companies_bilans = $this->loadData('companies_bilans');
        $this->companies_details = $this->loadData('companies_details');
        $this->projects = $this->loadData('projects');
        $this->projects_status = $this->loadData('projects_status');
        $this->clients_mandats = $this->loadData('clients_mandats');
        $this->projects_pouvoir = $this->loadData('projects_pouvoir');
        $prelevements = $this->loadData('prelevements');

        // Liste deroulante secteurs
        $this->settings->get('Liste deroulante secteurs', 'type');
        $this->lSecteurs = explode(';', $this->settings->value);

        // On recup les infos du client
        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'id_client') && $this->clients->status_pre_emp >= 2)
        {
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            // Companies
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            // liste des projets
            $this->lprojects = $this->projects->select('id_company = "' . $this->companies->id_company . '"');

            // liste des mandat uploadépar le client
            //$this->lMandats = $this->clients_mandats->select('id_client = '.$this->clients->id_client);
            //$this->clients_mandats->get($this->clients->id_client,'id_client');

            if ($this->clients->telephone != '')
                $this->clients->telephone = trim(chunk_split($this->clients->telephone, 2, ' '));

            if (isset($_POST['form_edit_emprunteur']))
            {
                $this->clients->nom = $this->ficelle->majNom($_POST['nom']);
                $this->clients->prenom = $this->ficelle->majNom($_POST['prenom']);


                //// check doublon mail ////
                $checkEmailExistant = $this->clients->select('email = "' . $_POST['email'] . '" AND id_client != ' . $this->clients->id_client . ' AND status_pre_emp > 1');
                if (count($checkEmailExistant) > 0)
                {
                    $les_id_client_email_exist = '';
                    foreach ($checkEmailExistant as $checkEmailEx)
                    {
                        $les_id_client_email_exist .= ' ' . $checkEmailEx['id_client'];
                    }

                    $_SESSION['error_email_exist'] = 'Impossible de modifier l\'adresse email. Cette adresse est déjà utilisé par le compte id ' . $les_id_client_email_exist;
                }
                else
                    $this->clients->email = $_POST['email'];

                ////////////////////////////

                $this->clients->telephone = str_replace(' ', '', $_POST['telephone']);

                $this->companies->name = $_POST['societe'];
                $this->companies->sector = $_POST['secteur'];
                $this->companies->bic = str_replace(' ','',strtoupper($_POST['bic']));
                $this->companies->iban = str_replace(' ','',strtoupper($_POST['iban']));
                $this->companies->email_facture = trim($_POST['email_facture']);
                // on verif si le bic est good
                if($this->companies->bic != '' && isset($this->companies->bic) && $this->ficelle->swift_validate(trim($this->companies->bic)) == false)
                //if(strlen($this->companies->bic) < 8 || strlen($this->companies->bic) > 11)
                {
                    $_SESSION['erreurBic'] = '';

                    // Mise en session du message
                    $_SESSION['freeow']['title'] = 'Erreur BIC';
                    $_SESSION['freeow']['message'] = 'Le BIC doit contenir entre 8 et 11 caractères';

                    header('Location:' . $this->lurl . '/emprunteurs/edit/' . $this->clients->id_client);
                    die;
                }

                if ($this->companies->iban != '' && $this->ficelle->isIBAN($this->companies->iban) != 1)
                {
                    $_SESSION['erreurIban'] = '';

                    // Mise en session du message
                    $_SESSION['freeow']['title'] = 'Erreur IBAN';
                    $_SESSION['freeow']['message'] = 'L\'IBAN doit contenir entre 8 et 11 caractères';

                    header('Location:' . $this->lurl . '/emprunteurs/edit/' . $this->clients->id_client);
                    die;
                }

                // on met a jour les prelevement en cours si y en a.
                foreach ($this->lprojects as $p)
                {
                    $prelevements->updateIbanBic($p['id_project'], $this->companies->bic, $this->companies->iban);
                }


                // meme adresse que le siege
                if ($this->companies->status_adresse_correspondance == 1)
                {
                    $this->companies->adresse1 = $_POST['adresse'];
                    $this->companies->city = $_POST['ville'];
                    $this->companies->zip = $_POST['cp'];
                }

                $this->clients_adresses->adresse1 = $_POST['adresse'];
                $this->clients_adresses->ville = $_POST['ville'];
                $this->clients_adresses->cp = $_POST['cp'];

                // mandat
                /* if(isset($_FILES['mandat']) && $_FILES['mandat']['name'] != '')
                  {
                  if($this->clients_mandats->get($this->clients->id_client,'id_client'))$create = false;
                  else $create = true;

                  $this->upload->setUploadDir($this->path,'protected/pdf/mandat/');
                  if($this->upload->doUpload('mandat'))
                  {
                  if($this->clients_mandats->name != '')@unlink($this->path.'protected/pdf/mandat/'.$this->clients_mandats->name);
                  $this->clients_mandats->name = $this->upload->getName();
                  }

                  $this->clients_mandats->id_client = $this->clients->id_client;
                  $this->clients_mandats->id_universign = 'no_universign';
                  $this->clients_mandats->url_pdf = '/pdf/mandat/'.$this->clients->hash.'/';
                  $this->clients_mandats->status = 1;

                  if($create == true)$this->clients_mandats->create();
                  else $this->clients_mandats->update();

                  $this->upload_mandat = true;

                  } */


                // cni/passeport
                if (isset($_FILES['cni_passeport']) && $_FILES['cni_passeport']['name'] != '')
                {
                    $this->upload->setUploadDir($this->path, 'protected/clients/cni_passeport/');
                    if ($this->upload->doUpload('cni_passeport'))
                    {
                        if ($this->clients->cni_passeport != '')
                            @unlink($this->path . 'protected/clients/cni_passeport/' . $this->clients->cni_passeport);
                        $this->clients->cni_passeport = $this->upload->getName();
                    }
                }
                // fichier_rib 
                if (isset($_FILES['signature']) && $_FILES['signature']['name'] != '')
                {
                    $this->upload->setUploadDir($this->path, 'protected/clients/signature/');
                    if ($this->upload->doUpload('signature'))
                    {
                        if ($this->clients->signature != '')
                            @unlink($this->path . 'protected/clients/signature/' . $this->clients->signature);
                        $this->clients->signature = $this->upload->getName();
                    }
                }

                // On met a jour les infos
                $this->companies->update();
                $this->clients->update();
                $this->clients_adresses->update();

                
                // Histo user //
                $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST, 'files' => $_FILES));
                $this->users_history->histo(6, 'edit emprunteur', $_SESSION['user']['id_user'], $serialize);
                ////////////////
                // Mise en session du message
                $_SESSION['freeow']['title'] = 'emprunteur mis a jour';
                $_SESSION['freeow']['message'] = 'l\'emprunteur a &eacute;t&eacute; mis a jour !';

                header('Location:' . $this->lurl . '/emprunteurs/edit/' . $this->clients->id_client);
                die;
            }
        }
        else
        {
            header('Location:' . $this->lurl . '/emprunteurs/gestion/');
            die;
        }
    }
    
    function _RIBlightbox_no_prelev()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug = false;

        // On place le redirect sur la home
        $_SESSION['request_url'] = $this->url;

        $prelevements = $this->loadData('prelevements');
        $this->date_activation = date('d/m/Y');
    }

    function _RIB_iban_existant()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug = false;

        // On place le redirect sur la home
        $_SESSION['request_url'] = $this->url;

        //recuperation de la liste des compagnies avec le même iban
        $list_comp = explode('-',  $this->params[0]);
        $this->list_comp = $sep = "";
    
        foreach($list_comp as $company)
        {
            //recuperation du nom de la compagnie
            $companies = $this->loadData('companies');
            if($companies->get($company))
            {            
                $this->list_comp .= $company.': '.$companies->name.' <br />';
            }
        }
        
    }
}
