<?php

class emprunteursController extends bootstrap
{
    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        $this->users->checkAccess('emprunteurs');

        $this->menu_admin = 'emprunteurs';
    }

    public function _default()
    {
        // On remonte la page dans l'arborescence
        if (isset($this->params[0]) && $this->params[0] == 'up') {
            $this->tree->moveUp($this->params[1]);

            header('Location: ' . $this->lurl . '/emprunteurs');
            die;
        }

        // On descend la page dans l'arborescence
        if (isset($this->params[0]) && $this->params[0] == 'down') {
            $this->tree->moveDown($this->params[1]);

            header('Location: ' . $this->lurl . '/emprunteurs');
            die;
        }

        // On supprime la page et ses dependances
        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            $this->tree->deleteCascade($this->params[1]);

            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Suppression d\'une page';
            $_SESSION['freeow']['message'] = 'La page et ses enfants ont bien &eacute;t&eacute; supprim&eacute;s !';

            header('Location: ' . $this->lurl . '/emprunteurs');
            die;
        }
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
            if ($_POST['status'] == 'choisir') {
                $statut = '';
            } else {
                $statut = $_POST['status'];
            }

            // Recuperation de la liste des clients
            $this->lClients = $this->clients->searchEmprunteurs('', $_POST['nom'], $_POST['email'], $_POST['prenom'], $_POST['societe'], $_POST['siret'], $statut);
            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Recherche d\'un client';
            $_SESSION['freeow']['message'] = 'La recherche est termin&eacute;e !';
        }

        if (isset($_POST['form_add_emprunteur'])) {
            $this->clients->nom             = $this->ficelle->majNom($_POST['nom']);
            $this->clients->prenom          = $this->ficelle->majNom($_POST['prenom']);
            $this->clients->email           = trim($_POST['email']);
            $this->companies->email_facture = trim($_POST['email']);
            $this->clients->telephone       = str_replace(' ', '', $_POST['telephone']);
            $this->clients->id_langue       = 'fr';

            // cni/passeport
            if (isset($_FILES['cni_passeport']) && $_FILES['cni_passeport']['name'] != '') {
                $this->upload->setUploadDir($this->path, 'protected/clients/cni_passeport/');
                if ($this->upload->doUpload('cni_passeport')) {
                    if ($this->clients->cni_passeport != '') {
                        @unlink($this->path . 'protected/clients/cni_passeport/' . $this->clients->cni_passeport);
                    }
                    $this->clients->cni_passeport = $this->upload->getName();
                }
            }
            // fichier_rib
            if (isset($_FILES['signature']) && $_FILES['signature']['name'] != '') {
                $this->upload->setUploadDir($this->path, 'protected/clients/signature/');
                if ($this->upload->doUpload('signature')) {
                    if ($this->clients->signature != '') {
                        @unlink($this->path . 'protected/clients/signature/' . $this->clients->signature);
                    }
                    $this->clients->signature = $this->upload->getName();
                }
            }

            $this->clients->id_client = $this->clients->create();

            $this->clients_adresses->adresse1 = $_POST['adresse'];
            $this->clients_adresses->ville    = $_POST['ville'];
            $this->clients_adresses->cp       = $_POST['cp'];
            $this->clients_adresses->id_client = $this->clients->id_client;
            $this->clients_adresses->create();

            $this->companies->name   = $_POST['societe'];
            $this->companies->sector = $_POST['secteur'];
            $this->companies->id_client_owner = $this->clients->id_client;
            $this->companies->id_company = $this->companies->create();

            $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST, 'files' => $_FILES));
            $this->users_history->histo(5, 'add emprunteur', $_SESSION['user']['id_user'], $serialize);

            $_SESSION['freeow']['title']   = 'emprunteur crt&eacute;t&eacute;';
            $_SESSION['freeow']['message'] = 'l\'emprunteur a &eacute;t&eacute; crt&eacute;t&eacute; !';

            header('Location: ' . $this->lurl . '/emprunteurs/gestion/' . $this->clients->id_client);
            die;
        }
    }

    public function _edit_client()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $_SESSION['request_url'] = $this->url;

        $this->clients          = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');
        $this->companies        = $this->loadData('companies');

        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'id_client')) {
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            // meme adresse que le siege
            if ($this->companies->status_adresse_correspondance == 1) {
                $this->adresse = $this->companies->adresse1;
                $this->ville   = $this->companies->city;
                $this->cp      = $this->companies->zip;
            } else {
                $this->adresse = $this->clients_adresses->adresse1;
                $this->ville   = $this->clients_adresses->ville;
                $this->cp      = $this->clients_adresses->cp;
            }
        }
    }

    public function _add_client()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $_SESSION['request_url'] = $this->url;

        $this->clients          = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');
        $this->companies        = $this->loadData('companies');

        $this->settings->get('Liste deroulante secteurs', 'type');
        $this->lSecteurs = explode(';', $this->settings->value);

        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'id_client')) {
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            $this->companies->get($this->clients->id_client, 'id_client_owner');

            // meme adresse que le siege
            if ($this->companies->status_adresse_correspondance == 1) {
                $this->adresse = $this->companies->adresse1;
                $this->ville   = $this->companies->city;
                $this->cp      = $this->companies->zip;
            } else {
                $this->adresse = $this->clients_adresses->adresse1;
                $this->ville   = $this->clients_adresses->ville;
                $this->cp      = $this->clients_adresses->cp;
            }
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
        $this->clients->history = '';

        $this->settings->get('Liste deroulante secteurs', 'type');
        $this->lSecteurs = explode(';', $this->settings->value);

        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'id_client') && $this->clients->isBorrower()) {
            $this->clients_adresses->get($this->clients->id_client, 'id_client');
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            $this->lprojects = $this->projects->select('id_company = "' . $this->companies->id_company . '"');

            if ($this->clients->telephone != '') {
                $this->clients->telephone = trim(chunk_split($this->clients->telephone, 2, ' '));
            }

            if (isset($_POST['form_edit_emprunteur'])) {
                $this->clients->nom    = $this->ficelle->majNom($_POST['nom']);
                $this->clients->prenom = $this->ficelle->majNom($_POST['prenom']);

                $checkEmailExistant = $this->clients->select('email = "' . $_POST['email'] . '" AND id_client != ' . $this->clients->id_client);
                if (count($checkEmailExistant) > 0) {
                    $les_id_client_email_exist = '';
                    foreach ($checkEmailExistant as $checkEmailEx) {
                        $les_id_client_email_exist .= ' ' . $checkEmailEx['id_client'];
                    }

                    $_SESSION['error_email_exist'] = 'Impossible de modifier l\'adresse email. Cette adresse est déjà utilisé par le compte id ' . $les_id_client_email_exist;
                } else {
                    $this->clients->email = $_POST['email'];
                }

                $this->clients->telephone = str_replace(' ', '', $_POST['telephone']);
                $this->companies->name    = $_POST['societe'];
                $this->companies->sector  = $_POST['secteur'];
                $edited_rib               = false;
                $sCurrentIban             = $this->companies->iban;
                $sCurrentBic              = $this->companies->bic;
                $sNewIban                 = str_replace(' ', '', strtoupper($_POST['iban1'] . $_POST['iban2'] . $_POST['iban3'] . $_POST['iban4'] . $_POST['iban5'] . $_POST['iban6'] . $_POST['iban7']));
                $sNewBic = str_replace(' ', '', strtoupper($_POST['bic']));

                if ($sCurrentBic != $sNewBic || $sCurrentIban != $sNewIban) {
                    $this->clients->history .= "<tr><td><b>RIB modifi&eacute; par Unilend</b> (" . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['name'] . "<!-- User ID: " . $_SESSION['user']['id_user'] . "-->) le " . date('d/m/Y') . " &agrave; " . date('H:i') . "<br><u>Ancienne valeur:</u> " . $this->companies->iban . " / " . $this->companies->bic . "<br><u>Nouvelle valeur:</u> " . $sNewIban . " / " . $sNewBic . "</tr></td>";
                    $edited_rib = true;
                }

                $this->companies->bic           = $sNewBic;
                $this->companies->iban          = $sNewIban;
                $this->companies->email_facture = trim($_POST['email_facture']);

                // on verif si le bic est good
                if ($this->companies->bic != '' && $this->ficelle->swift_validate(trim($this->companies->bic)) == false) {
                    $_SESSION['erreurBic'] = '';

                    $_SESSION['freeow']['title']   = 'Erreur BIC';
                    $_SESSION['freeow']['message'] = 'Le BIC est invalide';

                    header('Location: ' . $this->lurl . '/emprunteurs/edit/' . $this->clients->id_client);
                    die;
                }

                if ($this->companies->iban != '' && $this->ficelle->isIBAN($this->companies->iban) != 1) {
                    $_SESSION['erreurIban'] = '';

                    $_SESSION['freeow']['title']   = 'Erreur IBAN';
                    $_SESSION['freeow']['message'] = 'L\'IBAN est invalide';

                    header('Location: ' . $this->lurl . '/emprunteurs/edit/' . $this->clients->id_client);
                    die;
                }

                if ($this->companies->status_adresse_correspondance == 1) {
                    $this->companies->adresse1 = $_POST['adresse'];
                    $this->companies->city     = $_POST['ville'];
                    $this->companies->zip      = $_POST['cp'];
                }

                $this->clients_adresses->adresse1 = $_POST['adresse'];
                $this->clients_adresses->ville    = $_POST['ville'];
                $this->clients_adresses->cp       = $_POST['cp'];

                // cni/passeport
                if (isset($_FILES['cni_passeport']) && $_FILES['cni_passeport']['name'] != '') {
                    $this->upload->setUploadDir($this->path, 'protected/clients/cni_passeport/');
                    if ($this->upload->doUpload('cni_passeport')) {
                        if ($this->clients->cni_passeport != '') {
                            @unlink($this->path . 'protected/clients/cni_passeport/' . $this->clients->cni_passeport);
                        }
                        $this->clients->cni_passeport = $this->upload->getName();
                    }
                }
                // fichier_rib
                if (isset($_FILES['signature']) && $_FILES['signature']['name'] != '') {
                    $this->upload->setUploadDir($this->path, 'protected/clients/signature/');
                    if ($this->upload->doUpload('signature')) {
                        if ($this->clients->signature != '') {
                            @unlink($this->path . 'protected/clients/signature/' . $this->clients->signature);
                        }
                        $this->clients->signature = $this->upload->getName();
                    }
                }
                $this->companies->update();
                $this->clients->update();
                $this->clients_adresses->update();

                if ($edited_rib) {
                    $this->sendRibUpdateEmail($this->clients);
                }

                if ($sCurrentIban !== $sNewIban) {
                    /** @var \Unilend\Service\MailerManager $oMailerManager */
                    $oMailerManager = $this->get('MailerManager');
                    $oMailerManager->sendIbanUpdateToStaff($this->clients->id_client, $sCurrentIban, $sNewIban);
                }
                $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST, 'files' => $_FILES));
                $this->users_history->histo(6, 'edit emprunteur', $_SESSION['user']['id_user'], $serialize);

                $_SESSION['freeow']['title']   = 'emprunteur mis à jour';
                $_SESSION['freeow']['message'] = 'L\'emprunteur a été mis à jour';

                header('Location: ' . $this->lurl . '/emprunteurs/edit/' . $this->clients->id_client);
                die;
            }
            $this->aMoneyOrders = $this->clients_mandats->getMoneyOrderHistory($this->companies->id_company);
        } else {
            header('Location: ' . $this->lurl . '/emprunteurs/gestion/');
            die;
        }
    }

    /**
     * @param \clients $client
     */
    private function sendRibUpdateEmail($client)
    {
        /** @var \projects $project */
        $project = $this->loadData('projects');

        /** @var \companies $company */
        $company = $this->loadData('companies');

        /** @var \echeanciers_emprunteur $echeanciers_emprunteur */
        $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');

        foreach ($company->select('id_client_owner = ' . $client->id_client) as $currentCompany) {
            foreach ($project->select('id_company = ' . $currentCompany['id_company']) as $projects) {
                $aMandats = $this->clients_mandats->select('id_project = ' . $projects['id_project'] . ' AND id_client = ' . $client->id_client . ' AND status != ' . \clients_mandats::STATUS_ARCHIVED);

                if (false === empty($aMandats)) {
                    foreach ($aMandats as $aMandatToArchive) {
                        $this->clients_mandats->get($aMandatToArchive['id_mandat']);

                        if (\clients_mandats::STATUS_SIGNED == $this->clients_mandats->status) {
                            $nouveauNom    = str_replace('mandat', 'mandat-' . $this->clients_mandats->id_mandat, $this->clients_mandats->name);
                            $chemin        = $this->path . 'protected/pdf/mandat/' . $this->clients_mandats->name;
                            $nouveauChemin = $this->path . 'protected/pdf/mandat/' . $nouveauNom;

                            rename($chemin, $nouveauChemin);

                            $this->clients_mandats->name = $nouveauNom;
                        }
                        $this->clients_mandats->status = \clients_mandats::STATUS_ARCHIVED;
                        $this->clients_mandats->update();
                    }

                    // No need to create the new mandat, it will be created in pdf::_mandat()

                    //**********************************************//
                    //*** ENVOI DU MAIL FUNDE EMPRUNTEUR TERMINE ***//
                    //**********************************************//
                    $project->get($projects['id_project'], 'id_project');
                    $company->get($project->id_company, 'id_company');
                    $client->get($company->id_client_owner, 'id_client');
                    $this->mails_text->get('changement-de-rib', 'lang = "' . $this->language . '" AND type');
                    $echeanciers_emprunteur->get($project->id_project, 'ordre = 1 AND id_project');
                    $mensualite = $echeanciers_emprunteur->montant + $echeanciers_emprunteur->commission + $echeanciers_emprunteur->tva;
                    $mensualite = ($mensualite / 100);

                    $this->settings->get('Facebook', 'type');
                    $lien_fb = $this->settings->value;

                    $this->settings->get('Twitter', 'type');
                    $lien_tw = $this->settings->value;

                    /** @var \prelevements $directDebit */
                    $directDebit        = $this->loadData('prelevements');
                    $this->nextEcheance = $directDebit->select('status = 0 AND id_project = ' . $projects['id_project']);

                    $varMail = array(
                        'surl'                   => $this->surl,
                        'url'                    => $this->lurl,
                        'prenom_e'               => $client->prenom,
                        'nom_e'                  => $company->name,
                        'mensualite'             => $this->ficelle->formatNumber($mensualite),
                        'montant'                => $this->ficelle->formatNumber($project->amount, 0),
                        'link_compte_emprunteur' => $this->lurl . '/projects/detail/' . $project->id_project,
                        'link_mandat'            => $this->urlfront . '/pdf/mandat/' . $client->hash . '/' . $project->id_project,
                        'link_pouvoir'           => $this->urlfront . '/pdf/pouvoir/' . $client->hash . '/' . $project->id_project,
                        'projet'                 => $project->title,
                        'lien_fb'                => $lien_fb,
                        'lien_tw'                => $lien_tw,
                        'date_echeance'          => date('d/m/Y', strtotime($this->nextEcheance[0]['date_echeance_emprunteur']))
                    );

                    $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                    /** @var \Email email */
                    $email = $this->loadLib('email');
                    $email->setFrom($this->mails_text->exp_email, strtr(utf8_decode($this->mails_text->exp_name), $tabVars));
                    $email->setSubject(stripslashes($this->mails_text->subject));
                    $email->setHTMLBody(stripslashes(strtr(utf8_decode($this->mails_text->content), $tabVars)));

                    if ($this->Config['env'] === 'prod') {
                        Mailer::sendNMP($email, $this->mails_filer, $this->mails_text->id_textemail, $client->email, $tabFiler);
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                    } else {
                        $email->addRecipient(trim($client->email));
                        Mailer::send($email, $this->mails_filer, $this->mails_text->id_textemail);
                    }
                }
            }
        }
    }


    public function _RIBlightbox()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $_SESSION['request_url'] = $this->url;

        if (isset($this->params[0]) && $this->params[0] != '') {
            /** @var \companies $company */
            $company = $this->loadData('companies');
            $company->get($this->params[0], 'id_client_owner');

            /** @var \projects $project */
            $project         = $this->loadData('projects');
            $this->aProjects = $project->selectProjectsByStatus(implode(',', \projects_status::$runningRepayment), ' AND id_company = ' . $company->id_company);
        }
    }

    public function _RIBlightbox_no_prelev()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $_SESSION['request_url'] = $this->url;

        $this->date_activation = date('d/m/Y');
    }

    public function _RIB_iban_existant()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $_SESSION['request_url'] = $this->url;

        //recuperation de la liste des compagnies avec le même iban
        $list_comp       = explode('-', $this->params[0]);
        $this->list_comp = $sep = "";

        foreach ($list_comp as $company) {
            //recuperation du nom de la compagnie
            $companies = $this->loadData('companies');
            if ($companies->get($company)) {
                $this->list_comp .= $company . ': ' . $companies->name . ' <br />';
            }
        }
    }

    public function _error_iban_lightbox()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;
    }

    public function _error_bic_lightbox()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;
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
                case \factures::TYPE_COMMISSION_FINANCEMENT :
                    $aProjectInvoices[$iKey]['url'] = $this->furl . '/pdf/facture_EF/' . $oClient->hash . '/' . $aInvoice['id_project'];
                    break;
                case \factures::TYPE_COMMISSION_REMBOURSEMENT:
                    $aProjectInvoices[$iKey]['url'] = $this->furl . '/pdf/facture_ER/' . $oClient->hash . '/' . $aInvoice['id_project'] . '/' . $aInvoice['ordre'];
                    break;
                default :
                    trigger_error('Commission type for invoice unknown', E_USER_NOTICE);
                    break;
            }
        }
        $this->aProjectInvoices = $aProjectInvoices;
    }
}
