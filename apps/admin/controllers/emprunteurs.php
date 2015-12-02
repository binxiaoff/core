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
            $this->clients->status_pre_emp  = 2;
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
        $this->clients           = $this->loadData('clients');
        $this->clients_adresses  = $this->loadData('clients_adresses');
        $this->companies         = $this->loadData('companies');
        $this->companies_bilans  = $this->loadData('companies_bilans');
        $this->projects          = $this->loadData('projects');
        $this->projects_status   = $this->loadData('projects_status');
        $this->clients_mandats   = $this->loadData('clients_mandats');
        $this->projects_pouvoir  = $this->loadData('projects_pouvoir');
        $prelevements            = $this->loadData('prelevements');
        $this->clients->history  = '';

        $this->settings->get('Liste deroulante secteurs', 'type');
        $this->lSecteurs = explode(';', $this->settings->value);

        // On recup les infos du client
        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'id_client') && $this->clients->status_pre_emp >= 2) {
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            // Companies
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            // liste des projets
            $this->lprojects = $this->projects->select('id_company = "' . $this->companies->id_company . '"');

            // liste des mandat uploadépar le client
            //$this->lMandats = $this->clients_mandats->select('id_client = '.$this->clients->id_client);
            //$this->clients_mandats->get($this->clients->id_client,'id_client');

            if ($this->clients->telephone != '') {
                $this->clients->telephone = trim(chunk_split($this->clients->telephone, 2, ' '));
            }

            if (isset($_POST['form_edit_emprunteur'])) {
                $this->clients->nom    = $this->ficelle->majNom($_POST['nom']);
                $this->clients->prenom = $this->ficelle->majNom($_POST['prenom']);


                //// check doublon mail ////
                $checkEmailExistant = $this->clients->select('email = "' . $_POST['email'] . '" AND id_client != ' . $this->clients->id_client . ' AND status_pre_emp > 1');
                if (count($checkEmailExistant) > 0) {
                    $les_id_client_email_exist = '';
                    foreach ($checkEmailExistant as $checkEmailEx) {
                        $les_id_client_email_exist .= ' ' . $checkEmailEx['id_client'];
                    }

                    $_SESSION['error_email_exist'] = 'Impossible de modifier l\'adresse email. Cette adresse est déjà utilisé par le compte id ' . $les_id_client_email_exist;
                } else {
                    $this->clients->email = $_POST['email'];
                }

                ////////////////////////////

                $this->clients->telephone = str_replace(' ', '', $_POST['telephone']);

                $this->companies->name   = $_POST['societe'];
                $this->companies->sector = $_POST['secteur'];
                //Log modification de RIP par Unilend
                $edited_rib = false;
                if ($this->companies->bic != str_replace(' ', '', strtoupper($_POST['bic'])) || $this->companies->iban != str_replace(' ', '', strtoupper($_POST['iban1'] . $_POST['iban2'] . $_POST['iban3'] . $_POST['iban4'] . $_POST['iban5'] . $_POST['iban6'] . $_POST['iban7']))) {
                    $this->clients->history .= "<tr><td><b>RIB modifi&eacute; par Unilend</b> (" . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['name'] . "<!-- User ID: " . $_SESSION['user']['id'] . "-->) le " . date('d/m/Y') . " &agrave; " . date('H:i') . "<br><u>Ancienne valeur:</u> " . $this->companies->iban . " / " . $this->companies->bic . "<br><u>Nouvelle valeur:</u> " . str_replace(' ', '', strtoupper($_POST['iban1'] . $_POST['iban2'] . $_POST['iban3'] . $_POST['iban4'] . $_POST['iban5'] . $_POST['iban6'] . $_POST['iban7'])) . " / " . str_replace(' ', '', strtoupper($_POST['bic'])) . "</tr></td>";
                    $edited_rib = true;
                }

                $this->companies->bic  = str_replace(' ', '', strtoupper($_POST['bic']));
                $this->companies->iban = str_replace(' ', '', strtoupper($_POST['iban1'] . $_POST['iban2'] . $_POST['iban3'] . $_POST['iban4'] . $_POST['iban5'] . $_POST['iban6'] . $_POST['iban7']));
                $this->companies->email_facture = trim($_POST['email_facture']);

                // on verif si le bic est good
                if ($this->companies->bic != '' && $this->ficelle->swift_validate(trim($this->companies->bic)) == false) {
                    //if(strlen($this->companies->bic) < 8 || strlen($this->companies->bic) > 11)
                    $_SESSION['erreurBic'] = '';

                    // Mise en session du message
                    $_SESSION['freeow']['title']   = 'Erreur BIC';
                    $_SESSION['freeow']['message'] = 'Le BIC est invalide';
                    //$_SESSION['freeow']['message'] = 'Le BIC doit contenir entre 8 et 11 caractères';

                    header('Location: ' . $this->lurl . '/emprunteurs/edit/' . $this->clients->id_client);
                    die;
                }

                if ($this->companies->iban != '' && $this->ficelle->isIBAN($this->companies->iban) != 1) {
                    $_SESSION['erreurIban'] = '';

                    // Mise en session du message
                    $_SESSION['freeow']['title']   = 'Erreur IBAN';
                    $_SESSION['freeow']['message'] = 'L\'IBAN est invalide';

                    header('Location: ' . $this->lurl . '/emprunteurs/edit/' . $this->clients->id_client);
                    die;
                }

                // on met a jour les prelevement en cours si y en a.
                foreach ($this->lprojects as $p) {
                    $prelevements->updateIbanBic($p['id_project'], $this->companies->bic, $this->companies->iban);
                }


                // meme adresse que le siege
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
                    $e                      = $this->loadData('clients');
                    $loan                   = $this->loadData('loans');
                    $project                = $this->loadData('projects');
                    $companie               = $this->loadData('companies');
                    $echeancier             = $this->loadData('echeanciers');
                    $clients_mandats        = $this->loadData('clients_mandats');
                    $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
                    foreach ($companie->select('id_client_owner = ' . $this->clients->id_client) as $company2) {
                        foreach ($project->select('id_company = ' . $company2['id_company']) as $projects) {
                            if ($clients_mandats->get($this->params[0], 'status <> 4 AND id_project = "' . $projects['id_project'] . '" AND id_client')) {
                                $clients_mandats->status = 4;

                                // Rename old mandate (prefix name with mandate ID)
                                $oldname       = $clients_mandats->name;
                                $nouveauNom    = str_replace('mandat', 'mandat-' . $clients_mandats->id_mandat, $clients_mandats->name);
                                $chemin        = $this->path . 'protected/pdf/mandat/' . $clients_mandats->name;
                                $nouveauChemin = $this->path . 'protected/pdf/mandat/' . $nouveauNom;

                                rename($chemin, $nouveauChemin);

                                $clients_mandats->name = $nouveauNom;
                                $clients_mandats->update();

                                $clients_mandats->status         = 0;
                                $clients_mandats->name           = $oldname;
                                $clients_mandats->id_universign  = '';
                                $clients_mandats->url_universign = '';
                                $clients_mandats->create();

                                //**********************************************//
                                //*** ENVOI DU MAIL FUNDE EMPRUNTEUR TERMINE ***//
                                //**********************************************//
                                // On recup le projet
                                $project->get($projects['id_project'], 'id_project');

                                // On recup la companie
                                $companie->get($project->id_company, 'id_company');

                                // Recuperation du modele de mail
                                $this->mails_text->get('changement-de-rib', 'lang = "' . $this->language . '" AND type');

                                // emprunteur
                                $e->get($companie->id_client_owner, 'id_client');


                                $echeanciers_emprunteur->get($project->id_project, 'ordre = 1 AND id_project');
                                $mensualite = $echeanciers_emprunteur->montant + $echeanciers_emprunteur->commission + $echeanciers_emprunteur->tva;
                                $mensualite = ($mensualite / 100);

                                $surl         = $this->surl;
                                $url          = $this->lurl;
                                $projet       = $project->title;
                                $link_mandat  = $this->urlfront . '/pdf/mandat/' . $e->hash . '/' . $project->id_project;
                                $link_pouvoir = $this->urlfront . '/pdf/pouvoir/' . $e->hash . '/' . $project->id_project;

                                $this->nextEcheance = $prelevements->select('status = 0 AND id_project = ' . $projects['id_project']);

                                $varMail = array(
                                    'surl'                   => $surl,
                                    'url'                    => $url,
                                    'prenom_e'               => $e->prenom,
                                    'nom_e'                  => $companie->name,
                                    'mensualite'             => $this->ficelle->formatNumber($mensualite),
                                    'montant'                => $this->ficelle->formatNumber($project->amount, 0),
                                    'taux_moyen'             => $this->ficelle->formatNumber($taux_moyen),
                                    'link_compte_emprunteur' => $this->lurl . '/projects/detail/' . $project->id_project,
                                    'link_mandat'            => $link_mandat,
                                    'link_pouvoir'           => $link_pouvoir,
                                    'projet'                 => $projet,
                                    'lien_fb'                => $lien_fb,
                                    'lien_tw'                => $lien_tw,
                                    'date_echeance'          => date('d/m/Y', strtotime($this->nextEcheance[0]['date_echeance_emprunteur']))
                                );

                                $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                                $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                                $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                                $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                                $this->email = $this->loadLib('email');
                                $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                                $this->email->setSubject(stripslashes($sujetMail));
                                $this->email->setHTMLBody(stripslashes($texteMail));

                                if ($this->Config['env'] == 'prod') {
                                    Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $e->email, $tabFiler);
                                    $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                                } else {
                                    $this->email->addRecipient(trim($e->email));
                                    Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                                }
                            }
                        }
                    }
                }
                // Histo user //
                $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST, 'files' => $_FILES));
                $this->users_history->histo(6, 'edit emprunteur', $_SESSION['user']['id_user'], $serialize);
                ////////////////
                // Mise en session du message
                $_SESSION['freeow']['title']   = 'emprunteur mis a jour';
                $_SESSION['freeow']['message'] = 'l\'emprunteur a &eacute;t&eacute; mis a jour !';

                header('Location: ' . $this->lurl . '/emprunteurs/edit/' . $this->clients->id_client);
                die;
            }
        } else {
            header('Location: ' . $this->lurl . '/emprunteurs/gestion/');
            die;
        }
    }

    public function _RIBlightbox()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $_SESSION['request_url'] = $this->url;

        $prelevements       = $this->loadData('prelevements');
        $this->nextEcheance = $prelevements->select('status = 0 AND id_client = ' . $this->bdd->escape_string($this->params[0]));
        $this->nextEcheance = $this->nextEcheance[0]['date_echeance_emprunteur'];

        $this->sendedEcheance = $prelevements->select('status = 1 AND date_echeance_emprunteur > CURRENT_DATE AND id_client = ' . $this->bdd->escape_string($this->params[0]));
        $this->alreadySended  = count($this->sendedEcheance);
        $this->sendedEcheance = $this->sendedEcheance[0]['date_echeance_emprunteur'];
    }

    public function _RIBlightbox_no_prelev()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $_SESSION['request_url'] = $this->url;

        $prelevements          = $this->loadData('prelevements');
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
}
