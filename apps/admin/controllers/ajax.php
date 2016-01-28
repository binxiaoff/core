<?php

class ajaxController extends bootstrap
{
    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $_SESSION['request_url'] = $this->url;

        $this->hideDecoration();
    }

    /* Fonction AJAX delete image ELEMENT */
    public function _deleteImageElement()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->tree_elements->get($this->params[0], 'id');

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'images/' . $this->tree_elements->value);

            // On supprime le fichier de la base
            $this->tree_elements->value      = '';
            $this->tree_elements->complement = '';
            $this->tree_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    /* Fonction AJAX delete fichier ELEMENT */
    public function _deleteFichierElement()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->tree_elements->get($this->params[0], 'id');

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'fichiers/' . $this->tree_elements->value);

            // On supprime le fichier de la base
            $this->tree_elements->value      = '';
            $this->tree_elements->complement = '';
            $this->tree_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    /* Fonction AJAX delete fichier protected ELEMENT */
    public function _deleteFichierProtectedElement()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->tree_elements->get($this->params[0], 'id');

            // On supprime le fichier sur le serveur
            @unlink($this->path . 'protected/templates/' . $this->tree_elements->value);

            // On supprime le fichier de la base
            $this->tree_elements->value      = '';
            $this->tree_elements->complement = '';
            $this->tree_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    /* Fonction AJAX delete image ELEMENT BLOC */
    public function _deleteImageElementBloc()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->blocs_elements->get($this->params[0], 'id');

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'images/' . $this->blocs_elements->value);

            // On supprime le fichier de la base
            $this->blocs_elements->value      = '';
            $this->blocs_elements->complement = '';
            $this->blocs_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    /* Fonction AJAX delete fichier ELEMENT BLOC */
    public function _deleteFichierElementBloc()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->blocs_elements->get($this->params[0], 'id');

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'fichiers/' . $this->blocs_elements->value);

            // On supprime le fichier de la base
            $this->blocs_elements->value      = '';
            $this->blocs_elements->complement = '';
            $this->blocs_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    /* Fonction AJAX delete fichier protected ELEMENT BLOC */
    public function _deleteFichierProtectedElementBloc()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->blocs_elements->get($this->params[0], 'id');

            // On supprime le fichier sur le serveur
            @unlink($this->path . 'protected/templates/' . $this->blocs_elements->value);

            // On supprime le fichier de la base
            $this->blocs_elements->value      = '';
            $this->blocs_elements->complement = '';
            $this->blocs_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    /* Fonction AJAX delete image TREE */
    public function _deleteImageTree()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->tree->get(array('id_tree' => $this->params[0], 'id_langue' => $this->params[1]));

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'images/' . $this->tree->img_menu);

            // On supprime le fichier de la base
            $this->tree->img_menu = '';
            $this->tree->update(array('id_tree' => $this->params[0], 'id_langue' => $this->params[1]));
        }
    }

    /* Fonction AJAX delete video TREE */
    public function _deleteVideoTree()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->tree->get(array('id_tree' => $this->params[0], 'id_langue' => $this->params[1]));

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'videos/' . $this->tree->video);

            // On supprime le fichier de la base
            $this->tree->video = '';
            $this->tree->update(array('id_tree' => $this->params[0], 'id_langue' => $this->params[1]));
        }
    }

    /* Fonction AJAX chargement des noms de la section de traduction */
    public function _loadNomTexte()
    {
        if (isset($this->params[0]) && $this->params[0] != '') {
            // Recuperation de la liste des noms de la section
            $this->lNoms = $this->ln->selectTexts($this->params[0]);
        }
    }

    /* Fonction AJAX chargement des traductions de la section de traduction */
    public function _loadTradTexte()
    {
        if (isset($this->params[0]) && $this->params[0] != '') {
            // Recuperation de la liste traductions
            $this->lTranslations = $this->ln->selectTranslations($this->params[1], $this->params[0]);
        }
    }

    /* Activer un utilisateur sur une zone */
    public function _activeUserZone()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            // Recuperation du statut actuel de l'user
            $this->users_zones->get($this->params[0], 'id_zone = ' . $this->params[1] . ' AND id_user');

            if ($this->users_zones->id != '') {
                $this->users_zones->delete($this->users_zones->id);
                echo $this->surl . '/images/admin/check_off.png';
            } else {
                $this->users_zones->id_user = $this->params[0];
                $this->users_zones->id_zone = $this->params[1];
                $this->users_zones->create();
                echo $this->surl . '/images/admin/check_on.png';
            }
        }
    }

    /* Fonction AJAX change le statut d'un dossier*/
    public function _status_dossier()
    {
        $this->autoFireView = true;

        $this->projects                = $this->loadData('projects');
        $this->current_projects_status = $this->loadData('projects_status');


        $this->projects->get($this->params[0], 'id_project');
        $this->current_projects_status->getLastStatut($this->projects->id_project);
    }

    /* Fonction AJAX change le statut d'un dossier*/
    public function _date_publication()
    {
        $this->autoFireView = true;

        $this->projects                = $this->loadData('projects');
        $this->current_projects_status = $this->loadData('projects_status');


        $this->projects->get($this->params[0], 'id_project');
        $this->current_projects_status->getLastStatut($this->projects->id_project);
    }

    /* Fonction AJAX change le statut d'un dossier*/
    public function _check_status_dossier()
    {
        $this->autoFireView = true;

        $this->projects                = $this->loadData('projects');
        $this->projects_status         = $this->loadData('projects_status');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->companies               = $this->loadData('companies');
        $this->clients                 = $this->loadData('clients');
        $this->clients_history         = $this->loadData('clients_history');

        if (isset($this->params[0]) && isset($this->params[1])) {
            if ($this->projects->get($this->params[1], 'id_project') &&
                $this->projects->amount > 0 &&
                $this->projects->target_rate != '0' &&
                $this->companies->get($this->projects->id_company, 'id_company') &&
                $this->companies->risk != '' && $this->projects->period > 0 &&
                $this->params[2] != '' &&
                $this->projects->date_publication != '0000-00-00' &&
                $this->projects->date_retrait != '0000-00-00'
                ||
                $this->projects->get($this->params[1], 'id_project') &&
                $this->companies->get($this->projects->id_company, 'id_company') &&
                $this->params[0] == 30
            ) {
                $this->projects_status_history->addStatus($_SESSION['user']['id_user'], $this->params[0], $this->projects->id_project);
                $this->clients->get($this->companies->id_client_owner, 'id_client');

                //*****************************************//
                //*** ENVOI DU MAIL Validation ou rejet ***//
                //*****************************************//

                // Recuperation du modele de mail
                // validé
                if ($this->params[0] == 40) {

                    // Si statut a funder, en funding ou fundé
                    if (in_array($this->params[0], array(40, 50, 60))) {
                        /////////////////////////////////////
                        // Partie check données manquantes //
                        /////////////////////////////////////

                        $companies        = $this->loadData('companies');
                        $clients          = $this->loadData('clients');
                        $clients_adresses = $this->loadData('clients_adresses');

                        // on recup la companie
                        $companies->get($this->projects->id_company, 'id_company');
                        // et l'emprunteur
                        $clients->get($companies->id_client_owner, 'id_client');
                        // son adresse
                        $clients_adresses->get($companies->id_client_owner, 'id_client');


                        $mess = '<ul>';

                        if ($this->projects->title == '') {
                            $mess .= '<li>Titre projet</li>';
                        }
                        if ($this->projects->title_bo == '') {
                            $mess .= '<li>Titre projet BO</li>';
                        }
                        if ($this->projects->period == '0') {
                            $mess .= '<li>Periode projet</li>';
                        }
                        if ($this->projects->amount == '0') {
                            $mess .= '<li>Montant projet</li>';
                        }

                        if ($companies->name == '') {
                            $mess .= '<li>Nom entreprise</li>';
                        }
                        if ($companies->forme == '') {
                            $mess .= '<li>Forme juridique</li>';
                        }
                        if ($companies->siren == '') {
                            $mess .= '<li>SIREN entreprise</li>';
                        }
                        if ($companies->iban == '') {
                            $mess .= '<li>IBAN entreprise</li>';
                        }
                        if ($companies->bic == '') {
                            $mess .= '<li>BIC entreprise</li>';
                        }
                        if ($companies->tribunal_com == '') {
                            $mess .= '<li>Tribunal de commerce entreprise</li>';
                        }
                        if ($companies->capital == '0') {
                            $mess .= '<li>Capital entreprise</li>';
                        }
                        if ($companies->date_creation == '0000-00-00') {
                            $mess .= '<li>Date creation entreprise</li>';
                        }
                        if ($companies->sector == 0) {
                            $mess .= '<li>Secteur entreprise</li>';
                        }

                        if ($clients->nom == '') {
                            $mess .= '<li>Nom emprunteur</li>';
                        }
                        if ($clients->prenom == '') {
                            $mess .= '<li>Prenom emprunteur</li>';
                        }
                        if ($clients->fonction == '') {
                            $mess .= '<li>Fonction emprunteur</li>';
                        }
                        if ($clients->telephone == '') {
                            $mess .= '<li>Telephone emprunteur</li>';
                        }
                        if ($clients->email == '') {
                            $mess .= '<li>Email emprunteur</li>';
                        }

                        if ($clients_adresses->adresse1 == '') {
                            $mess .= '<li>Adresse emprunteur</li>';
                        }
                        if ($clients_adresses->cp == '') {
                            $mess .= '<li>CP emprunteur</li>';
                        }
                        if ($clients_adresses->ville == '') {
                            $mess .= '<li>Ville emprunteur</li>';
                        }

                        $mess .= '</ul>';

                        if (strlen($mess) > 9) {
                            $to = implode(',', $this->Config['DebugAlertesBusiness']);
                            $to .= ($this->Config['env'] == 'prod') ? ', nicolas.lesur@unilend.fr' : '';
                            // subject
                            $subject = '[Rappel] Donnees projet manquantes';
                            // message
                            $message = '
                            <html>
                            <head>
                              <title>[Rappel] Donnees projet manquantes</title>
                            </head>
                            <body>
                                <p>Un projet qui vient d\'etre publie ne dispose pas de toutes les donnees necessaires</p>
                                <p>Listes des informations manquantes sur le projet ' . $this->projects->id_project . ' : </p>
                                ' . $mess . '
                            </body>
                            </html>
                            ';

                            // To send HTML mail, the Content-type header must be set
                            $headers = 'MIME-Version: 1.0' . "\r\n";
                            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

                            // Additional headers

                            $headers .= 'From: Unilend <equipeit@unilend.fr>' . "\r\n";
                            //$headers .= 'From: Unilend <courtier.damien@gmail.com>' . "\r\n";

                            // Mail it
                            mail($to, $subject, $message, $headers);
                        }
                    }
                    // si inscription
                    if ($this->clients->status_transition == 1) {
                        $this->clients_history->id_client = $this->clients->id_client;
                        $this->clients_history->type      = $this->clients->status_pre_emp;
                        $this->clients_history->status    = 2; // statut inscription
                        $this->clients_history->create();
                    }

                    $this->clients_history->id_client = $this->clients->id_client;
                    $this->clients_history->type      = $this->clients->status_pre_emp;
                    $this->clients_history->status    = 3; // statut depot de dossier validé
                    $this->clients_history->create();

                    // statut emprunteur online
                    $this->clients->status = 1;
                    // on retire l'etape de transition
                    $this->clients->status_transition = 0;

                    $this->mails_text->get('emprunteur-dossier-valide', 'lang = "' . $this->language . '" AND type');
                } elseif ($this->params[0] == 30) {
                    $this->mails_text->get('emprunteur-dossier-rejete', 'lang = "' . $this->language . '" AND type');
                }

                // FB
                $this->settings->get('Facebook', 'type');
                $lien_fb = $this->settings->value;

                // Twitter
                $this->settings->get('Twitter', 'type');
                $lien_tw = $this->settings->value;

                $timeDatedebut  = strtotime($this->projects->date_publication);
                $monthDatedebut = $this->dates->tableauMois['fr'][date('n', $timeDatedebut)];
                $datedebut      = date('d', $timeDatedebut) . ' ' . $monthDatedebut . ' ' . date('Y', $timeDatedebut);

                $timeDateretrait  = strtotime($this->projects->date_retrait);
                $monthDateretrait = $this->dates->tableauMois['fr'][date('n', $timeDateretrait)];
                $date_retrait     = date('d', $timeDateretrait) . ' ' . $monthDateretrait . ' ' . date('Y', $timeDateretrait);

                $varMail = array(
                    'surl'                             => $this->surl,
                    'url'                              => $this->furl,
                    'prenom_e'                         => $this->clients->prenom,
                    'link_compte_emprunteur'           => $this->furl . '/synthese_emprunteur',
                    'date_presentation_dossier_debut'  => $datedebut,
                    'heure_presentation_dossier_debut' => date('H', $timeDatedebut),
                    'date_presentation_dossier_fin'    => $date_retrait,
                    'heure_presentation_dossier_fin'   => date('H', $timeDateretrait),
                    'lien_fb'                          => $lien_fb,
                    'lien_tw'                          => $lien_tw
                );

                $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                $this->email = $this->loadLib('email');
                $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                $this->email->setSubject(stripslashes($sujetMail));
                $this->email->setHTMLBody(stripslashes($texteMail));

                if ($this->Config['env'] === 'prod') {
                    Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                    $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                } else {
                    $this->email->addRecipient(trim($this->clients->email));
                    Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                }

                //on recup le statut courant
                $this->current_projects_status = $this->loadData('projects_status');
                $this->current_projects_status->getLastStatut($this->projects->id_project);

                //on charge la liste des statut dispo
                if ($this->current_projects_status->status == \projects_status::EN_ATTENTE_PIECES) {
                    $this->lProjects_status = $this->projects_status->select(' status <= ' . \projects_status::EN_ATTENTE_PIECES, ' status ASC ');
                } elseif ($this->current_projects_status->status >= \projects_status::REMBOURSEMENT) {
                    $this->lProjects_status = $this->projects_status->select(' status >= ' . \projects_status::REMBOURSEMENT, ' status ASC ');
                } else {
                    $this->lProjects_status = array();
                }
                // on met a jour le statut de l'emprunteur
                $this->clients->update();
                $this->bloc_statut = 'ok';
            } else {
                echo 'nok';
            }
        } else {
            echo 'nok';
        }
    }

    public function _addMemo()
    {
        $this->autoFireView = true;

        if (isset($_POST['content_memo']) && isset($_POST['id']) && isset($_POST['type'])) {
            $this->projects_comments = $this->loadData('projects_comments');

            if ($_POST['type'] == 'edit') {
                $this->projects_comments->get($_POST['id'], 'id_project_comment');
                $this->projects_comments->content = $_POST['content_memo'];
                $this->projects_comments->update();
                $this->lProjects_comments = $this->projects_comments->select('id_project = ' . $this->projects_comments->id_project, 'added ASC');
            } else {
                $this->projects_comments->id_project = $_POST['id'];
                $this->projects_comments->content    = $_POST['content_memo'];
                $this->projects_comments->status     = 1;
                $this->projects_comments->create();
                $this->lProjects_comments = $this->projects_comments->select('id_project = ' . $_POST['id'], 'added ASC');
            }

        }

    }

    public function _deleteMemo()
    {
        $this->autoFireView = false;

        if (isset($_POST['id_project_comment']) && isset($_POST['id_project'])) {
            $this->projects_comments = $this->loadData('projects_comments');

            // on supprime le memo
            $this->projects_comments->delete($_POST['id_project_comment'], 'id_project_comment');


            // On raffiche le tableau
            $this->lProjects_comments = $this->projects_comments->select('id_project = ' . $_POST['id_project'], 'added ASC');
        }
    }

    public function _valid_etapes()
    {
        $this->autoFireView = false;

        if (isset($_POST['id_project']) && isset($_POST['etape'])) {
            $this->projects         = $this->loadData('projects');
            $this->companies        = $this->loadData('companies');
            $this->clients          = $this->loadData('clients');
            $this->clients_adresses = $this->loadData('clients_adresses');

            // Histo user //
            $serialize = serialize($_POST);
            $this->users_history->histo(8, 'dossier edit etapes', $_SESSION['user']['id_user'], $serialize);
            ////////////////

            if ($_POST['etape'] == 1) {
                // on recup le projet
                $this->projects->get($_POST['id_project'], 'id_project');

                // on recup l'entreprise
                $this->companies->get($this->projects->id_company, 'id_company');

                $this->projects->amount = str_replace(' ', '', str_replace(',', '.', $_POST['montant_etape1']));
                $this->projects->period = (0 < (int) $_POST['duree_etape1']) ? (int) $_POST['duree_etape1'] : $this->projects->period;
                $this->companies->siren = $_POST['siren_etape1'];

                // on enregistre les modifs
                $this->projects->update();
                $this->companies->update();

            } elseif ($_POST['etape'] == 2) {
                // on recup le projet
                $this->projects->get($_POST['id_project'], 'id_project');

                // on recup l'entreprise
                $this->companies->get($this->projects->id_company, 'id_company');

                // on recup le client
                $this->clients->get($this->companies->id_client_owner, 'id_client');

                // on recup le client
                $this->clients_adresses->get($this->companies->id_client_owner, 'id_client');

                $iIdPrescripteur = 'true' === $_POST['has_prescripteur'] ? $_POST['id_prescripteur'] : 0;

                $this->projects->id_prescripteur = $iIdPrescripteur;
                $this->projects->update();

                $this->companies->name    = $_POST['raison_sociale_etape2'];
                $this->companies->forme   = $_POST['forme_juridique_etape2'];
                $this->companies->capital = str_replace(' ', '', str_replace(',', '.', $_POST['capital_social_etape2']));

                $creation_date_etape2           = explode('/', $_POST['creation_date_etape2']);
                $this->companies->date_creation = $creation_date_etape2[2] . '-' . $creation_date_etape2[1] . '-' . $creation_date_etape2[0];
                $this->companies->adresse1      = $_POST['address_etape2'];
                $this->companies->city          = $_POST['ville_etape2'];
                $this->companies->zip           = $_POST['postal_etape2'];
                $this->companies->phone         = $_POST['phone_etape2'];

                $this->companies->status_adresse_correspondance = $_POST['same_address_etape2'];
                if ($this->companies->status_adresse_correspondance == 0) {
                    // adresse client
                    $this->clients_adresses->adresse1  = $_POST['adresse_correspondance_etape2'];
                    $this->clients_adresses->ville     = $_POST['city_correspondance_etape2'];
                    $this->clients_adresses->cp        = $_POST['zip_correspondance_etape2'];
                    $this->clients_adresses->telephone = $_POST['phone_correspondance_etape2'];
                } else {
                    // adresse client
                    $this->clients_adresses->adresse1  = $_POST['address_etape2'];
                    $this->clients_adresses->ville     = $_POST['ville_etape2'];
                    $this->clients_adresses->cp        = $_POST['postal_etape2'];
                    $this->clients_adresses->telephone = $_POST['phone_etape2'];
                }


                $this->companies->status_client = $_POST['enterprise_etape2'];

                $this->clients->civilite = $_POST['civilite_etape2'];
                $this->clients->nom      = $this->ficelle->majNom($_POST['nom_etape2']);
                $this->clients->prenom   = $this->ficelle->majNom($_POST['prenom_etape2']);
                $this->clients->fonction = $_POST['fonction_etape2'];

                ///////// on check si mail existe deja si c'est le cas on rajoute l'id projet
                //$clients = $this->loadData('clients');
                /* if($clients->get($_POST['email_etape2'],'email') && strpos($_POST['email_etape2'], $this->projects->id_project) === false){ */

                if ($this->clients->counter('email = "' . $_POST['email_etape2'] . '" AND id_client <> ' . $this->clients->id_client) > 0) {

                    $this->clients->email = $_POST['email_etape2'];
                    $this->clients->email .= '-' . $this->projects->id_project;
                } elseif ($this->clients->email != $_POST['email_etape2']) {
                    $this->clients->email = $_POST['email_etape2'];
                }
                //////////

                $this->clients->telephone = $_POST['phone_new_etape2'];

                if ($this->companies->status_client == 2 || $this->companies->status_client == 3) {
                    $this->companies->civilite_dirigeant = $_POST['civilite2_etape2'];
                    $this->companies->nom_dirigeant      = $this->ficelle->majNom($_POST['nom2_etape2']);
                    $this->companies->prenom_dirigeant   = $this->ficelle->majNom($_POST['prenom2_etape2']);
                    $this->companies->fonction_dirigeant = $_POST['fonction2_etape2'];
                    $this->companies->email_dirigeant    = $_POST['email2_etape2'];
                    $this->companies->phone_dirigeant    = $_POST['phone_new2_etape2'];

                    if ($this->companies->status_client == 3) {
                        $this->companies->status_conseil_externe_entreprise   = $_POST['status_conseil_externe_entreprise_etape2'];
                        $this->companies->preciser_conseil_externe_entreprise = $_POST['preciser_conseil_externe_entreprise_etape2'];
                    } else {
                        $this->companies->status_conseil_externe_entreprise   = '';
                        $this->companies->preciser_conseil_externe_entreprise = '';
                    }
                } else {
                    $this->companies->civilite_dirigeant                  = '';
                    $this->companies->nom_dirigeant                       = '';
                    $this->companies->prenom_dirigeant                    = '';
                    $this->companies->fonction_dirigeant                  = '';
                    $this->companies->email_dirigeant                     = '';
                    $this->companies->phone_dirigeant                     = '';
                    $this->companies->status_conseil_externe_entreprise   = '';
                    $this->companies->preciser_conseil_externe_entreprise = '';
                }

                // on enregistre les modifs
                $this->companies->update();
                $this->clients->update();
                $this->clients_adresses->update();
            } elseif ($_POST['etape'] == 3) {
                $this->projects = $this->loadData('projects');
                $this->projects->get($_POST['id_project'], 'id_project');
                $this->projects->amount               = str_replace(array(' ', ','), array('', '.'), $_POST['montant_etape3']);
                $this->projects->period               = $_POST['duree_etape3'];
                $this->projects->title                = $_POST['titre_etape3'];
                $this->projects->objectif_loan        = $_POST['objectif_etape3'];
                $this->projects->presentation_company = $_POST['presentation_etape3'];
                $this->projects->means_repayment      = $_POST['moyen_etape3'];
                $this->projects->comments             = $_POST['comments_etape3'];
                $this->projects->update();
            } elseif ($_POST['etape'] == 4) {
                $this->projects          = $this->loadData('projects');
                $this->companies_bilans  = $this->loadData('companies_bilans');
                $this->companies_details = $this->loadData('companies_details');
                $this->companies_ap      = $this->loadData('companies_actif_passif');

                for ($i = 0; $i < 5; $i++) {
                    $this->companies_bilans->get($_POST['ca_id_' . $i], 'id_bilan');
                    $this->companies_bilans->ca = str_replace(array(' ', ','), array('', '.'), $_POST['ca_' . $i]);
                    $this->companies_bilans->update();

                    $this->companies_bilans->get($_POST['resultat_brute_exploitation_id_' . $i], 'id_bilan');
                    $this->companies_bilans->resultat_brute_exploitation = str_replace(array(' ', ','), array('', '.'), $_POST['resultat_brute_exploitation_' . $i]);
                    $this->companies_bilans->update();

                    $this->companies_bilans->get($_POST['resultat_exploitation_id_' . $i], 'id_bilan');
                    $this->companies_bilans->resultat_exploitation = str_replace(array(' ', ','), array('', '.'), $_POST['resultat_exploitation_' . $i]);
                    $this->companies_bilans->update();

                    $this->companies_bilans->get($_POST['investissements_id_' . $i], 'id_bilan');
                    $this->companies_bilans->investissements = str_replace(array(' ', ','), array('', '.'), $_POST['investissements_' . $i]);
                    $this->companies_bilans->update();
                }

                // On recup le projet
                $this->projects->get($_POST['id_project'], 'id_project');
                $this->projects->ca_declara_client                    = str_replace(array(' ', ','), array('', '.'), $_POST['ca_declara_client']);
                $this->projects->resultat_exploitation_declara_client = str_replace(array(' ', ','), array('', '.'), $_POST['resultat_exploitation_declara_client']);
                $this->projects->fonds_propres_declara_client         = str_replace(array(' ', ','), array('', '.'), $_POST['fonds_propres_declara_client']);
                $this->projects->update();

                // On recup le detail de l'entreprise
                $this->companies_details->get($this->projects->id_company, 'id_company');

                $old_date_dernier_bilan                                               = $this->companies_details->date_dernier_bilan;
                $this->companies_details->date_dernier_bilan                          = $_POST['annee_etape4'] . '-' . $_POST['mois_etape4'] . '-' . $_POST['jour_etape4'];
                $this->companies_details->encours_actuel_dette_fianciere              = $_POST['encours_actuel_dette_fianciere'];
                $this->companies_details->remb_a_venir_cette_annee                    = $_POST['remb_a_venir_cette_annee'];
                $this->companies_details->remb_a_venir_annee_prochaine                = $_POST['remb_a_venir_annee_prochaine'];
                $this->companies_details->tresorie_dispo_actuellement                 = $_POST['tresorie_dispo_actuellement'];
                $this->companies_details->autre_demandes_financements_prevues         = $_POST['autre_demandes_financements_prevues'];
                $this->companies_details->precisions                                  = $_POST['precisions'];
                $this->companies_details->decouverts_bancaires                        = $_POST['decouverts_bancaires'];
                $this->companies_details->lignes_de_tresorerie                        = $_POST['lignes_de_tresorerie'];
                $this->companies_details->affacturage                                 = $_POST['affacturage'];
                $this->companies_details->escompte                                    = $_POST['escompte'];
                $this->companies_details->financement_dailly                          = $_POST['financement_dailly'];
                $this->companies_details->credit_de_tresorerie                        = $_POST['credit_de_tresorerie'];
                $this->companies_details->credit_bancaire_investissements_materiels   = $_POST['credit_bancaire_investissements_materiels'];
                $this->companies_details->credit_bancaire_investissements_immateriels = $_POST['credit_bancaire_investissements_immateriels'];
                $this->companies_details->rachat_entreprise_ou_titres                 = $_POST['rachat_entreprise_ou_titres'];
                $this->companies_details->credit_immobilier                           = $_POST['credit_immobilier'];
                $this->companies_details->credit_bail_immobilier                      = $_POST['credit_bail_immobilier'];
                $this->companies_details->credit_bail                                 = $_POST['credit_bail'];
                $this->companies_details->location_avec_option_achat                  = $_POST['location_avec_option_achat'];
                $this->companies_details->location_financiere                         = $_POST['location_financiere'];
                $this->companies_details->location_longue_duree                       = $_POST['location_longue_duree'];
                $this->companies_details->pret_oseo                                   = $_POST['pret_oseo'];
                $this->companies_details->pret_participatif                           = $_POST['pret_participatif'];
                $this->companies_details->update();

                if ($old_date_dernier_bilan != '0000-00-00') {
                    $dernierBilan = explode('-', $old_date_dernier_bilan);
                    $dernierBilan = $dernierBilan[0];
                } else {
                    $dernierBilan = date('Y');
                }

                // On recup les actif passif
                $this->lCompanies_actif_passif = $this->companies_ap->select('id_company = "' . $this->projects->id_company . '" AND annee <= "' . $dernierBilan . '"', 'annee DESC');
                if ($this->lCompanies_actif_passif != false) {
                    $i = 1;
                    foreach ($this->lCompanies_actif_passif as $ap) {
                        if ($i <= 3) {
                            $this->companies_ap->get($ap['id_actif_passif'], 'ordre = ' . $ap['ordre'] . ' AND id_actif_passif');

                            $this->companies_ap->immobilisations_corporelles        = $_POST['immobilisations_corporelles_' . $ap['ordre']];
                            $this->companies_ap->immobilisations_incorporelles      = $_POST['immobilisations_incorporelles_' . $ap['ordre']];
                            $this->companies_ap->immobilisations_financieres        = $_POST['immobilisations_financieres_' . $ap['ordre']];
                            $this->companies_ap->stocks                             = $_POST['stocks_' . $ap['ordre']];
                            $this->companies_ap->creances_clients                   = $_POST['creances_clients_' . $ap['ordre']];
                            $this->companies_ap->disponibilites                     = $_POST['disponibilites_' . $ap['ordre']];
                            $this->companies_ap->valeurs_mobilieres_de_placement    = $_POST['valeurs_mobilieres_de_placement_' . $ap['ordre']];
                            $this->companies_ap->capitaux_propres                   = $_POST['capitaux_propres_' . $ap['ordre']];
                            $this->companies_ap->provisions_pour_risques_et_charges = $_POST['provisions_pour_risques_et_charges_' . $ap['ordre']];
                            $this->companies_ap->amortissement_sur_immo             = $_POST['amortissement_sur_immo_' . $ap['ordre']];
                            $this->companies_ap->dettes_financieres                 = $_POST['dettes_financieres_' . $ap['ordre']];
                            $this->companies_ap->dettes_fournisseurs                = $_POST['dettes_fournisseurs_' . $ap['ordre']];
                            $this->companies_ap->autres_dettes                      = $_POST['autres_dettes_' . $ap['ordre']];

                            $this->companies_ap->update();
                        }
                        $i++;
                    }
                }


            } elseif ($_POST['etape'] == 5) {

            } elseif ($_POST['etape'] == 6) {
                $this->projects = $this->loadData('projects');

                // On recup le projet
                $this->projects->get($_POST['id_project'], 'id_project');

                $this->projects->question1 = $_POST['question1'];
                $this->projects->question2 = $_POST['question2'];
                $this->projects->question3 = $_POST['question3'];

                $this->projects->update();
            }

        }

    }

    public function _create_client()
    {
        $this->autoFireView = false;

        $this->projects         = $this->loadData('projects');
        $this->companies        = $this->loadData('companies');
        $this->clients          = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');

        if (isset($_POST['id_project']) && $this->projects->get($_POST['id_project'], 'id_project')) {
            // On verifie que ce soit bien un mail
            if ($_POST['email'] != '') //if($this->ficelle->isEmail($_POST['email']))
            {

                // si client existe deja
                if ($this->clients->get($_POST['id_client'], 'id_client')) {
                    if ($this->clients->counter('email = "' . $_POST['email'] . '" AND id_client <> ' . $this->clients->id_client) > 0) {

                        // a mettre
                        $this->clients->email = $_POST['email'] . '-' . $_POST['id_project'];

                        // on fait rien
                        //$error = true;
                    } else {
                        $this->clients->email = $_POST['email'];

                        $this->companies->get($this->projects->id_company, 'id_company');
                        $this->companies->email_facture = $this->clients->email;

                        $this->companies->update();
                        $this->clients->update();
                    }

                } // Si il existe pas on le créer
                else {
                    // On créer le client
                    $this->clients->id_langue = 'fr';

                    // Si le mail existe deja on enregistre pas le mail
                    if ($this->clients->counter('email = "' . $_POST['email'] . '"') > 0) {

                        // a mettre
                        $this->clients->email = $_POST['email'] . '-' . $_POST['id_project'];

                        // on fait rien
                        //$error = true;
                    } else {
                        $this->clients->email = $_POST['email'];
                    }
                    // On precise que c'est un emprunteur
                    $this->clients->status_pre_emp = 2;

                    $this->clients->id_client = $this->clients->create();

                    $this->clients_adresses->id_client = $this->clients->id_client;
                    $this->clients_adresses->create();

                    // On recup l'entreprise et on attribut le client a celle ci
                    $this->companies->get($this->projects->id_company, 'id_company');
                    $this->companies->id_client_owner = $this->clients->id_client;
                    $this->companies->email_facture   = $this->clients->email;
                    $this->companies->update();
                    //}
                }
                //echo ($error == true?'nok':'ok');
                //echo $this->clients->id_client;
                echo json_encode(array(
                    'id_client' => $this->clients->id_client,
                    'error'     => ($error == true ? 'nok' : 'ok')
                ));
            } else {
                echo json_encode(array('id_client' => '0', 'error' => 'nok'));
            }
        }
    }


    public function _valid_create()
    {
        $this->autoFireView = false;

        $this->projects                = $this->loadData('projects');
        $this->companies               = $this->loadData('companies');
        $this->clients                 = $this->loadData('clients');
        $this->projects_status_history = $this->loadData('projects_status_history');

        if (isset($_POST['id_project']) && $this->projects->get($_POST['id_project'], 'id_project')) {
            $this->companies->get($this->projects->id_company, 'id_company');
            $this->clients->get($this->companies->id_client_owner, 'id_client');

            $this->projects_status_history->addStatus($_SESSION['user']['id_user'], \projects_status::A_TRAITER, $this->projects->id_project);

            //**********************************************//
            //*** ENVOI DU MAIL CONFIRMATION INSCRIPTION ***//
            //**********************************************//

            $this->mails_text->get('confirmation-depot-de-dossier', 'lang = "' . $this->language . '" AND type');

            $this->settings->get('Facebook', 'type');
            $lien_fb = $this->settings->value;

            $this->settings->get('Twitter', 'type');
            $lien_tw = $this->settings->value;

            $varMail = array(
                'prenom'               => $this->clients->prenom,
                'raison_sociale'       => $this->companies->name,
                'lien_reprise_dossier' => $this->surl . '/depot_de_dossier/reprise/' . $this->projects->hash,
                'lien_fb'              => $lien_fb,
                'lien_tw'              => $lien_tw,
                'sujet'                => htmlentities($this->mails_text->subject, null, 'UTF-8'),
                'surl'                 => $this->surl,
                'url'                  => $this->url,
            );

            $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

            $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
            $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
            $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

            $this->email = $this->loadLib('email');
            $this->email->setFrom($this->mails_text->exp_email, $exp_name);
            $this->email->setSubject(stripslashes($sujetMail));
            $this->email->setHTMLBody(stripslashes($texteMail));

            if ($this->Config['env'] === 'prod') {
                Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
            } else {
                $this->email->addRecipient(trim($this->clients->email));
                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
            }

            $this->clients->password = md5($this->ficelle->generatePassword(8));
            $this->clients->status   = 1;
            $this->clients->update();

            $this->users->get(1, 'default_analyst');
            $this->projects->id_analyste = $this->users->id_user;
            $this->projects->update();
        }
    }

    public function _refeshEtape4()
    {
        $this->autoFireView = true;

        $this->projects               = $this->loadData('projects');
        $this->companies              = $this->loadData('companies');
        $this->companies_bilans       = $this->loadData('companies_bilans');
        $this->companies_details      = $this->loadData('companies_details');
        $this->companies_actif_passif = $this->loadData('companies_actif_passif');
        $this->clients                = $this->loadData('clients');

        if (isset($_POST['id_project']) && $this->projects->get($_POST['id_project'], 'id_project')) {
            $this->companies->get($this->projects->id_company, 'id_company');
            $this->companies_details->get($this->projects->id_company, 'id_company');
            $this->clients->get($this->companies->id_client_owner, 'id_client');

            $this->lCompanies_actif_passif = $this->companies_actif_passif->select('id_company = "' . $this->companies->id_company . '"');

            if ($this->lCompanies_actif_passif == false) {
                for ($i = 1; $i <= 3; $i++) {
                    $this->companies_actif_passif->ordre      = $i;
                    $this->companies_actif_passif->id_company = $this->companies->id_company;
                    $this->companies_actif_passif->create();
                }

                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->params[0]);
                die;
            }

            $this->lbilans = $this->companies_bilans->select('id_company = ' . $this->companies->id_company, 'date ASC');
        }
    }

    // email creation nouveau mot de passe (mis a jour le 09/07/2014)
    public function _generer_mdp_new()
    {
        $this->autoFireView = false;

        $clients = $this->loadData('clients');

        if (isset($_POST['id_client']) && $clients->get($_POST['id_client'], 'id_client')) {

            //*************************//
            //*** ENVOI DU MAIL MDP ***//
            //*************************//

            // Recuperation du modele de mail
            $this->mails_text->get('mot-de-passe-oublie', 'lang = "' . $this->language . '" AND type');

            // FB
            $this->settings->get('Facebook', 'type');
            $lien_fb = $this->settings->value;

            // Twitter
            $this->settings->get('Twitter', 'type');
            $lien_tw = $this->settings->value;

            $varMail = array(
                'surl'          => $this->surl,
                'url'           => $this->lurl,
                'prenom'        => $clients->prenom,
                'login'         => $clients->email,
                'link_password' => $this->lurl . '/' . $this->tree->getSlug(119, $this->language) . '/' . $clients->hash,
                'lien_fb'       => $lien_fb,
                'lien_tw'       => $lien_tw
            );

            $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

            $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
            $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
            $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

            $this->email = $this->loadLib('email');
            $this->email->setFrom($this->mails_text->exp_email, $exp_name);
            $this->email->setSubject(stripslashes($sujetMail));
            $this->email->setHTMLBody(stripslashes($texteMail));

            if ($this->Config['env'] === 'prod') {
                Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $clients->email, $tabFiler);
                $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
            } else {
                $this->email->addRecipient(trim($clients->email));
                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
            }

            echo 'ok';
        } else {
            echo 'nok';
        }
    }

    // old version (mis a jour le 09/07/2014) remis en place le 10/07/14
    public function _generer_mdp()
    {
        $this->autoFireView = false;

        $this->clients = $this->loadData('clients');

        if (isset($_POST['id_client']) && $this->clients->get($_POST['id_client'], 'id_client')) {
            $pass = $this->ficelle->generatePassword(8);

            $this->clients->changePassword($this->clients->email, $pass);

            //************************************//
            //*** ENVOI DU MAIL GENERATION MDP ***//
            //************************************//

            // Recuperation du modele de mail
            $this->mails_text->get('generation-mot-de-passe', 'lang = "' . $this->language . '" AND type');

            $surl  = $this->surl;
            $url   = $this->furl;
            $login = $this->clients->email;
            $mdp   = $pass;

            $this->settings->get('Facebook', 'type');
            $lien_fb = $this->settings->value;

            $this->settings->get('Twitter', 'type');
            $lien_tw = $this->settings->value;

            $varMail = array(
                'surl'     => $surl,
                'url'      => $url,
                'login'    => $login,
                'prenom_p' => $this->clients->prenom,
                'mdp'      => 'Mot de passe : ' . $mdp,
                'lien_fb'  => $lien_fb,
                'lien_tw'  => $lien_tw
            );

            $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

            $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
            $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
            $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

            $this->email = $this->loadLib('email');
            $this->email->setFrom($this->mails_text->exp_email, $exp_name);
            $this->email->setSubject(stripslashes($sujetMail));
            $this->email->setHTMLBody(stripslashes($texteMail));

            if ($this->Config['env'] === 'prod') {
                Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
            } else {
                $this->email->addRecipient(trim($this->clients->email));
                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
            }
        }
    }

    public function _recapdashboard()
    {
        $this->autoFireView = true;

        $this->transactions      = $this->loadData('transactions');
        $this->partenaires_types = $this->loadData('partenaires_types');
        $this->clients_history   = $this->loadData('clients_history');
        $this->bids              = $this->loadData('bids');
        $this->echeanciers       = $this->loadData('echeanciers');

        if (isset($_POST['month'])) {
            if (strlen($_POST['month']) < 2) {
                $month = '0' . $_POST['month'];
            } else {
                $month = $_POST['month'];
            }
            $this->month = $_POST['month'];
            $year        = $_POST['annee'];
            $this->year  = $year;

            // Recuperation du chiffre d'affaire sur les mois de l'année
            $lCaParMois = $this->transactions->recupCAByMonthForAYear($this->year);
            for ($i = 1; $i <= 12; $i++) {
                $i                   = ($i < 10 ? '0' . $i : $i);
                $this->caParmois[$i] = number_format(($lCaParMois[$i] != '' ? $lCaParMois[$i] : 0), 2, '.', '');
            }

            // nb preteurs connect
            $this->nbPreteurLogin = $this->clients_history->getNb($month, $year, 'type = 1 AND status = 1', 1);

            // nb emprunteur connect
            $this->nbEmprunteurLogin = $this->clients_history->getNb($month, $year, 'type > 1 AND status = 1', 1);

            // nb depot dossier
            $this->nbDepotDossier = $this->clients_history->getNb($month, $year, 'type > 1 AND status = 3');

            // nb inscription preteur
            $this->nbInscriptionPreteur = $this->clients_history->getNb($month, $year, 'type = 1 AND status = 2', 1);

            // nb inscription emprunteur
            $this->nbInscriptionEmprunteur = $this->clients_history->getNb($month, $year, 'type > 1 AND status = 2', 1);

            // fonds deposés
            $this->nbFondsDeposes = $this->caParmois[$month];

            // Fonds pretes
            $this->nbFondsPretes = $this->bids->sumBidsMonth($month, $year);

            // Total capital restant du mois
            $this->TotalCapitalRestant = $this->echeanciers->getTotalSumRembByMonth($month, $year);
        }
    }


    public function _ratioDashboard()
    {
        $this->autoFireView = true;

        $this->transactions      = $this->loadData('transactions');
        $this->partenaires_types = $this->loadData('partenaires_types');
        $this->clients_history   = $this->loadData('clients_history');
        $this->bids              = $this->loadData('bids');
        $this->echeanciers       = $this->loadData('echeanciers');
        $this->projects_status   = $this->loadData('projects_status');
        $this->projects          = $this->loadData('projects');

        if (isset($_POST['month'])) {
            if (strlen($_POST['month']) < 2) {
                $month = '0' . $_POST['month'];
            } else {
                $month = $_POST['month'];
            }
            $this->month = $_POST['month'];
            $year        = $_POST['annee'];
            $this->year  = $year;


            // Tous les projets du mois
            $nbProjects = $this->projects->counter('MONTH(added) = ' . $month . ' AND YEAR(added) = ' . $year);

            $lProjects = $this->projects->select('MONTH(added) = ' . $month . ' AND YEAR(added) = ' . $year);

            // On recupere les projets valides
            $nbProjetValid = 0;
            foreach ($lProjects as $p) {
                $this->projects_status->getLastStatutByMonth($p['id_project'], $month, $year);
                if ($this->projects_status->status > \projects_status::A_FUNDER) {
                    $nbProjetValid += 1;
                }
            }

            // ratio Projets
            if ($nbProjetValid > 0 && $nbProjects > 0) {
                $this->ratioProjects = ($nbProjetValid / $nbProjects) * 100;
            } else {
                $this->ratioProjects = 0;
            }

            // moyenne des depots de fonds preteur
            $this->moyenneDepotsFonds = $this->transactions->avgDepotPreteurByMonth($month, $year);

            // total retrait argent
            $TotalRetrait = $this->transactions->sumByMonth(8, $month, $year);
            $TotalRetrait = str_replace('-', '', $TotalRetrait);

            // total remboursement preteur
            $TotalrembPreteur = $this->transactions->sumByMonth(5, $month, $year);

            // tauxRepret
            if ($TotalRetrait > 0 && $TotalrembPreteur > 0) {
                $this->tauxRepret = ($TotalRetrait / $TotalrembPreteur) * 100;
            } else {
                $this->tauxRepret = 0;
            }

            // sum Remb par emprunteur
            $lSumRemb = $this->echeanciers->getSumRembEmpruntByMonths('', '', '0', $month, $year);

            // Capital
            $capital = 0;
            foreach ($lSumRemb as $r) {
                $capital += ($r['montant'] - $r['interets']);
            }

            // fonds gelés
            $sumGel = $this->bids->sumBidsMonthEncours($month, $year);

            // fonds dispo
            $dispo = $this->transactions->getDispo($month, $year);

            if ($TotalRetrait > 0) {
                $this->tauxAttrition = ($TotalRetrait / ($capital + $dispo + $sumGel)) * 100;
            } else {
                $this->tauxAttrition = 0;
            }

        }
    }

    // supprime le bid dans la gestion du preteur et raffiche sa liste de bid mis a jour
    public function _deleteBidPreteur()
    {
        $this->autoFireView = true;

        $lender         = $this->loadData('lenders_accounts');
        $preteur        = $this->loadData('clients');
        $bids           = $this->loadData('bids');
        $transactions   = $this->loadData('transactions');
        $wallets_lines  = $this->loadData('wallets_lines');
        $this->projects = $this->loadData('projects');

        if (isset($_POST['id_lender'], $_POST['id_bid']) && $bids->get($_POST['id_bid'], 'id_bid') && $lender->get($_POST['id_lender'], 'id_lender_account')) {
            $serialize = serialize($_POST);
            $this->users_history->histo(4, 'Bid en cours delete', $_SESSION['user']['id_user'], $serialize);

            $wallets_lines->get($bids->id_lender_wallet_line, 'id_wallet_line');
            $transactions->get($wallets_lines->id_transaction, 'id_transaction');

            $transactions->delete($transactions->id_transaction, 'id_transaction');
            $wallets_lines->delete($wallets_lines->id_wallet_line, 'id_wallet_line');
            $bids->delete($bids->id_bid, 'id_bid');

            // on recharge l'affichage
            $this->lBids = $bids->select('id_lender_account = ' . $_POST['id_lender'] . ' AND status = 0', 'added DESC');
        }
    }

    public function _loadMouvTransac()
    {

        $this->autoFireView = true;

        $this->transactions = $this->loadData('transactions');
        $this->clients      = $this->loadData('clients');
        $this->echeanciers  = $this->loadData('echeanciers');
        $this->projects     = $this->loadData('projects');
        $this->companies    = $this->loadData('companies');

        if (isset($_POST['year'], $_POST['id_client']) && $this->clients->get($_POST['id_client'], 'id_client')) {

            $this->lng['profile'] = $this->ln->selectFront('preteur-profile', $this->language, $this->App);

            $year = $_POST['year'];

            $this->lTrans     = $this->transactions->select('type_transaction IN (1,3,4,5,7,8,14,16,17) AND status = 1 AND etat = 1 AND id_client = ' . $this->clients->id_client . ' AND YEAR(date_transaction) = ' . $year, 'added DESC');
            $this->lesStatuts = array(
                1  => $this->lng['profile']['versement-initial'],
                3  => $this->lng['profile']['alimentation-cb'],
                4  => $this->lng['profile']['alimentation-virement'],
                5  => 'Remboursement',
                7  => $this->lng['profile']['alimentation-prelevement'],
                8  => $this->lng['profile']['retrait'],
                14 => 'Régularisation prêteur',
                16 => 'Offre de bienvenue',
                17 => 'Retrait offre de bienvenue'
            );
        }
    }

    public function _loadDashYear()
    {
        $this->autoFireView = true;

        // Chargement des fichiers JS
        $this->loadJs('admin/chart/highcharts');

        $this->transactions      = $this->loadData('transactions');
        $this->partenaires_types = $this->loadData('partenaires_types');
        $this->clients_history   = $this->loadData('clients_history');
        $this->bids              = $this->loadData('bids');
        $this->echeanciers       = $this->loadData('echeanciers');
        $this->projects_status   = $this->loadData('projects_status');
        $this->projects          = $this->loadData('projects');

        // Recuperation de la liste des type de partenaires
        $this->lTypes = $this->partenaires_types->select('status = 1');


        if (isset($_POST['annee'])) {
            $this->year = $_POST['annee'];
        } else {
            $this->year = date('Y');
        }

        $lCaParMois          = $this->transactions->recupCAByMonthForAYear($this->year);
        $lVirementsParMois   = $this->transactions->recupVirmentEmprByMonthForAYear($this->year);
        $lRembParMois        = $this->transactions->recupRembEmprByMonthForAYear($this->year);
        $this->caParmoisPart = $this->transactions->recupMonthlyPartnershipTurnoverByYear($this->year);

        for ($i = 1; $i <= 12; $i++) {
            $i                          = ($i < 10 ? '0' . $i : $i);
            $this->caParmois[$i]        = number_format(($lCaParMois[$i] != '' ? $lCaParMois[$i] : 0), 2, '.', '');
            $this->VirementsParmois[$i] = number_format(str_replace('-', '', ($lVirementsParMois[$i] != '' ? $lVirementsParMois[$i] : 0)), 2, '.', '');
            $this->RembEmprParMois[$i]  = number_format(($lRembParMois[$i] != '' ? $lRembParMois[$i] : 0), 2, '.', '');
        }

        $this->month                = date('m');
        $this->nbPreteurLogin       = $this->clients_history->getNb($this->month, $this->year, 'type = 1 AND status = 1', 1);
        $this->nbInscriptionPreteur = $this->clients_history->getNb($this->month, $this->year, 'type = 1 AND status = 2', 1);
        $this->nbFondsDeposes       = $this->caParmois[$this->month];
        $this->nbFondsPretes        = $this->bids->sumBidsMonth($this->month, $this->year);
        $this->TotalCapitalRestant  = $this->echeanciers->getTotalSumRembByMonth($this->month, $this->year);

        $nbProjects = $this->projects->counter('MONTH(added) = ' . $this->month . ' AND YEAR(added) = ' . $this->year);
        $lProjects  = $this->projects->select('MONTH(added) = ' . $this->month . ' AND YEAR(added) = ' . $this->year);

        // On recupere les projets valides
        $nbProjetValid = 0;
        foreach ($lProjects as $p) {
            $this->projects_status->getLastStatutByMonth($p['id_project'], $this->month, $this->year);
            if ($this->projects_status->status >= \projects_status::A_FUNDER) {
                $nbProjetValid += 1;
            }
        }

        $this->ratioProjects      = @($nbProjetValid / $nbProjects) * 100;
        $this->moyenneDepotsFonds = $this->transactions->avgDepotPreteurByMonth($this->month, $this->year);

        $TotalRetrait = $this->transactions->sumByMonth(8, $this->month, $this->year);
        $TotalRetrait = str_replace('-', '', $TotalRetrait);

        // total remboursement preteur
        $TotalrembPreteur = $this->transactions->sumByMonth(5, $this->month, $this->year);

        // tauxRepret
        if ($TotalRetrait > 0 && $TotalrembPreteur > 0) {
            $this->tauxRepret = ($TotalRetrait / $TotalrembPreteur) * 100;
        } else {
            $this->tauxRepret = 0;
        }

        // sum Remb par emprunteur
        $lSumRemb = $this->echeanciers->getSumRembEmpruntByMonths('', '', '0', $this->month, $this->year);

        // Capital
        $capital = 0;
        foreach ($lSumRemb as $r) {
            $capital += ($r['montant'] - $r['interets']);
        }

        // fonds gelés
        $sumGel = $this->bids->sumBidsMonthEncours($this->month, $this->year);

        // fonds dispo
        $dispo = $this->transactions->getDispo($this->month, $this->year);

        if ($TotalRetrait > 0) {
            $this->tauxAttrition = ($TotalRetrait / ($capital + $dispo + $sumGel)) * 100;
        } else {
            $this->tauxAttrition = 0;
        }
    }

    public function _check_status_dossierV2()
    {
        $this->autoFireView = false;

        $this->projects                = $this->loadData('projects');
        $this->projects_notes          = $this->loadData('projects_notes');
        $this->projects_status         = $this->loadData('projects_status');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->companies               = $this->loadData('companies');
        $this->clients                 = $this->loadData('clients');
        $this->clients_history         = $this->loadData('clients_history');

        // on check si on a les posts
        if (isset($_POST['status'], $_POST['id_project']) && $this->projects->get($_POST['id_project'], 'id_project')) {
            $form_ok = true;

            // on verifie que les infos sont good
            if ($this->projects->amount <= 0 || $this->projects->period <= 0) {
                $form_ok = false;
            }
            if (! $this->companies->get($this->projects->id_company, 'id_company')) {
                $form_ok = false;
            }
            if (! $this->clients->get($this->companies->id_client_owner, 'id_client')) {
                $form_ok = false;
            }

            if ($form_ok == true) {
                // on check si existe deja
                if ($this->projects_notes->get($_POST['id_project'], 'id_project')) {
                    $update = true;
                } else {
                    $update = false;
                }
                // On recup le title du projet
                $title = $this->projects->title;

                // on maj le statut
                $this->projects_status_history->addStatus($_SESSION['user']['id_user'], $_POST['status'], $this->projects->id_project);

                //on recup le statut courant
                $this->current_projects_status = $this->loadData('projects_status');
                $this->current_projects_status->getLastStatut($this->projects->id_project);

                $this->lProjects_status = $this->current_projects_status->getPossibleStatus($this->projects->id_project, $this->projects_status_history);

                if (count($this->lProjects_status) > 0) {
                    $select = '<select name="status" id="status" class="select">';
                    foreach ($this->lProjects_status as $s) {
                        $select .= '<option ' . ($this->current_projects_status->status == $s['status'] ? 'selected' : '') . ' value="' . $s['status'] . '">' . $s['label'] . '</option>';
                    }
                    $select .= '</select>';
                } else {
                    $select = '<input type="hidden" name="status" id="status" value="' . $this->current_projects_status->status . '" />';
                    $select .= $this->current_projects_status->label;
                }

                if ($this->current_projects_status->status != \projects_status::REJETE) {
                    $moyenne1 = (($this->projects_notes->performance_fianciere * 0.4) + ($this->projects_notes->marche_opere * 0.3) + ($this->projects_notes->qualite_moyen_infos_financieres * 0.2) + ($this->projects_notes->notation_externe * 0.1));
                    $moyenne  = round($moyenne1, 1);
                    $etape_6  = '
                    <div id="title_etape6">Etape 6</div>
                    <div id="etape6">
                        <form method="post" name="dossier_etape6" id="dossier_etape6" action="" target="_parent">
                            <table class="form tableNotes" style="width: 100%;">
                                <tr>
                                    <th style="vertical-align:top;"><label for="performance_fianciere">Performance financière</label></th>
                                    <td>
                                        <span id="performance_fianciere">' . $this->projects_notes->performance_fianciere . '</span> /10
                                    </td>
                                    <th style="vertical-align:top;"><label for="marche_opere">Marché opéré</label></th>
                                    <td style="vertical-align:top;">
                                        <span id="marche_opere">' . $this->projects_notes->marche_opere . '</span> /10
                                    </td>
                                    <th><label for="qualite_moyen_infos_financieres">Qualité des moyens & infos financières</label></th>
                                    <td><input tabindex="6" id="qualite_moyen_infos_financieres" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->qualite_moyen_infos_financieres . '" name="qualite_moyen_infos_financieres" maxlength="4" onkeyup="nodizaines(this.value,this.id);"> /10</td>
                                    <th><label for="notation_externe">Notation externe</label></th>
                                    <td><input tabindex="7" id="notation_externe" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->notation_externe . '" name="notation_externe" maxlength="4" onkeyup="nodizaines(this.value,this.id);"> /10</td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="vertical-align:top;">
                                        <table>
                                            <tr>
                                                <th><label for="structure">Structure</label></th>
                                                <td><input tabindex="1" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->structure . '" name="structure" id="structure" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> /10</td>
                                            </tr>
                                            <tr>
                                                <th><label for="rentabilite">Rentabilité</label></th>
                                                <td><input tabindex="2" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->rentabilite . '" name="rentabilite" id="rentabilite" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> /10</td>
                                            </tr>
                                            <tr>
                                                <th><label for="tresorerie">Trésorerie</label></th>
                                                <td><input tabindex="3" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->tresorerie . '" name="tresorerie" id="tresorerie" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> /10</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td colspan="2" style="vertical-align:top;">
                                        <table>
                                            <tr>
                                                <th><label for="global">Global</label></th>
                                                <td><input tabindex="4" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->global . '" name="global" id="global" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> /10</td>
                                            </tr>
                                            <tr>
                                                <th><label for="individuel">Individuel</label></th>
                                                <td><input tabindex="5" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->individuel . '" name="individuel" id="individuel" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> /10</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td colspan="4"></td>
                                </tr>
                                <tr class="lanote">
                                    <th colspan="8" style="text-align:center;" >Note : <span class="moyenneNote" onkeyup="nodizaines(this.value,this.id);">' . $moyenne . '/10</span></th>
                                </tr>
                                <tr>
                                    <td colspan="8" style="text-align:center;">
                                        <label for="avis" style="text-align:left;display: block;">Avis :</label><br />
                                        <textarea name="avis" tabindex="8" style="height:700px;" id="avis" class="textarea_large avis" />' . $this->projects_notes->avis . '</textarea>
                                        <script type="text/javascript">var ckedAvis = CKEDITOR.replace(\'avis\',{ height: 700});</script>
                                    </td>
                                </tr>
                            </table>
                            <br /><br />
                            <div id="valid_etape6">Données sauvegardées</div>';

                    if ($this->current_projects_status->status == \projects_status::REVUE_ANALYSTE) {
                        $etape_6 .= '
                            <div class="btnDroite listBtn_etape6">
                                <input type="button" onclick="valid_rejete_etape6(3,' . $this->projects->id_project . ')" class="btn btnValid_rejet_etape6"  value="Sauvegarder">
                                <input type="button" onclick="valid_rejete_etape6(1,' . $this->projects->id_project . ')" class="btn btnValid_rejet_etape6" style="background:#009933;border-color:#009933;" value="Valider">
                                <input type="button" onclick="valid_rejete_etape6(2,' . $this->projects->id_project . ')" class="btn btnValid_rejet_etape6" style="background:#CC0000;border-color:#CC0000;" value="Rejeter">
                            </div>';
                    }

                    $etape_6 .= '
                        </form>
                    </div>

                    <script type="text/javascript">
                    $("#title_etape6").click(function() {
                        $("#etape6").slideToggle();
                    });

                    $(".cal_moyen").keyup(function() {
                        // --- Chiffre et marché ---
                        var structure = parseFloat($("#structure").val().replace(",","."));
                        var rentabilite = parseFloat($("#rentabilite").val().replace(",","."));
                        var tresorerie = parseFloat($("#tresorerie").val().replace(",","."));
                        var global = parseFloat($("#global").val().replace(",","."));
                        var individuel = parseFloat($("#individuel").val().replace(",","."));

                        structure = (Math.round(structure*10)/10);
                        rentabilite = (Math.round(rentabilite*10)/10);
                        tresorerie = (Math.round(tresorerie*10)/10);
                        global = (Math.round(global*10)/10);
                        individuel = (Math.round(individuel*10)/10);

                        var performance_fianciere = ((structure+rentabilite+tresorerie)/3);
                        performance_fianciere = (Math.round(performance_fianciere*10)/10);

                        var marche_opere = ((global+individuel)/2);
                        marche_opere = (Math.round(marche_opere*10)/10);

                        // --- Fin chiffre et marché ---

                        var qualite_moyen_infos_financieres = parseFloat($("#qualite_moyen_infos_financieres").val().replace(",","."));
                        var notation_externe = parseFloat($("#notation_externe").val().replace(",","."));

                        qualite_moyen_infos_financieres = (Math.round(qualite_moyen_infos_financieres*10)/10);
                        notation_externe = (Math.round(notation_externe*10)/10);

                        var moyenne1 = (((performance_fianciere*0.4)+(marche_opere*0.3)+(qualite_moyen_infos_financieres*0.2)+(notation_externe*0.1)));

                        moyenne = (Math.round(moyenne1*10)/10);

                        $("#marche_opere").html(marche_opere);
                        $("#performance_fianciere").html(performance_fianciere);
                        $(".moyenneNote").html(moyenne+"/10");
                    });
                    </script>';
                } else {
                    $etape_6 = '';

                    //////////////////////////////////////
                    /// MAIL emprunteur-dossier-rejete ///
                    //////////////////////////////////////

                    $this->mails_text->get('emprunteur-dossier-rejete', 'lang = "' . $this->language . '" AND type');


                    // FB
                    $this->settings->get('Facebook', 'type');
                    $lien_fb = $this->settings->value;

                    // Twitter
                    $this->settings->get('Twitter', 'type');
                    $lien_tw = $this->settings->value;

                    $varMail = array(
                        'surl'                   => $this->surl,
                        'url'                    => $this->furl,
                        'prenom_e'               => $this->clients->prenom,
                        'link_compte_emprunteur' => $this->furl,
                        'lien_fb'                => $lien_fb,
                        'lien_tw'                => $lien_tw
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
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                    } else {
                        $this->email->addRecipient(trim($this->clients->email));
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                    }
                    // on passe l'emprunteur en offline
                    $this->clients->update();
                }

                echo json_encode(array('liste' => $select, 'etape_6' => $etape_6));
            } else {
                echo 'nok';
            }
        } else {
            echo 'nok';
        }
    }

    public function _valid_rejete_etape6()
    {
        $this->autoFireView = false;

        $this->projects                = $this->loadData('projects');
        $this->projects_status         = $this->loadData('projects_status');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->projects_notes          = $this->loadData('projects_notes');
        $this->companies               = $this->loadData('companies');
        $this->clients                 = $this->loadData('clients');
        $this->clients_history         = $this->loadData('clients_history');

        if (isset($_POST['status']) && isset($_POST['id_project']) && $this->projects->get($_POST['id_project'], 'id_project')) {
            $form_ok = true;
            if ($_POST['status'] == 1) {
                if (! isset($_POST['structure']) || $_POST['structure'] == 0 || $_POST['structure'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['rentabilite']) || $_POST['rentabilite'] == 0 || $_POST['rentabilite'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['tresorerie']) || $_POST['tresorerie'] == 0 || $_POST['tresorerie'] > 10) {
                    $form_ok = false;
                }

                if (! isset($_POST['performance_fianciere']) || $_POST['performance_fianciere'] == 0 || $_POST['performance_fianciere'] > 10) {
                    $form_ok = false;
                }

                if (! isset($_POST['individuel']) || $_POST['individuel'] == 0 || $_POST['individuel'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['global']) || $_POST['global'] == 0 || $_POST['global'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['marche_opere']) || $_POST['marche_opere'] == 0 || $_POST['marche_opere'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['qualite_moyen_infos_financieres']) || $_POST['qualite_moyen_infos_financieres'] == 0 || $_POST['qualite_moyen_infos_financieres'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['notation_externe']) || $_POST['notation_externe'] == 0 || $_POST['notation_externe'] > 10) {
                    $form_ok = false;
                }
            }
            if (! isset($_POST['avis']) && $_POST['status'] == 1 || strlen($_POST['avis']) < 50 && $_POST['status'] == 1) {
                $form_ok = false;
            }
            if (! $this->companies->get($this->projects->id_company, 'id_company')) {
                $form_ok = false;
            }
            if (! $this->clients->get($this->companies->id_client_owner, 'id_client')) {
                $form_ok = false;
            }

            if ($form_ok == true) {
                // on check si existe deja
                if ($this->projects_notes->get($_POST['id_project'], 'id_project')) {
                    $update = true;
                } else {
                    $update = false;
                }

                $this->projects_notes->structure                       = number_format($_POST['structure'], 1, '.', '');
                $this->projects_notes->rentabilite                     = number_format($_POST['rentabilite'], 1, '.', '');
                $this->projects_notes->tresorerie                      = number_format($_POST['tresorerie'], 1, '.', '');
                $this->projects_notes->individuel                      = number_format($_POST['individuel'], 1, '.', '');
                $this->projects_notes->global                          = number_format($_POST['global'], 1, '.', '');
                $this->projects_notes->performance_fianciere           = number_format($_POST['performance_fianciere'], 1, '.', '');
                $this->projects_notes->marche_opere                    = number_format($_POST['marche_opere'], 1, '.', '');
                $this->projects_notes->qualite_moyen_infos_financieres = number_format($_POST['qualite_moyen_infos_financieres'], 1, '.', '');
                $this->projects_notes->notation_externe                = number_format($_POST['notation_externe'], 1, '.', '');
                $this->projects_notes->note                            = round(($this->projects_notes->performance_fianciere * 0.4) + ($this->projects_notes->marche_opere * 0.3) + ($this->projects_notes->qualite_moyen_infos_financieres * 0.2) + ($this->projects_notes->notation_externe * 0.1), 1);
                $this->projects_notes->avis                            = $_POST['avis'];

                if ($update == true) {
                    $this->projects_notes->update();
                } else {
                    $this->projects_notes->id_project = $this->projects->id_project;
                    $this->projects_notes->create();
                }

                if ($_POST['status'] == 1) {
                    $this->projects_status_history->addStatus($_SESSION['user']['id_user'], \projects_status::COMITE, $this->projects->id_project);
                } elseif ($_POST['status'] == 2) {
                    $this->projects_status_history->addStatus($_SESSION['user']['id_user'], \projects_status::REJET_ANALYSTE, $this->projects->id_project);

                    //////////////////////////////////////
                    /// MAIL emprunteur-dossier-rejete ///
                    //////////////////////////////////////

                    $this->mails_text->get('emprunteur-dossier-rejete', 'lang = "' . $this->language . '" AND type');

                    $this->settings->get('Facebook', 'type');
                    $lien_fb = $this->settings->value;

                    $this->settings->get('Twitter', 'type');
                    $lien_tw = $this->settings->value;

                    $varMail = array(
                        'surl'                   => $this->surl,
                        'url'                    => $this->furl,
                        'prenom_e'               => $this->clients->prenom,
                        'link_compte_emprunteur' => $this->furl,
                        'lien_fb'                => $lien_fb,
                        'lien_tw'                => $lien_tw
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
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                    } else {
                        $this->email->addRecipient(trim($this->clients->email));
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                    }

                    // on passe l'emprunteur en offline
                    $this->clients->update();
                }

                //on recup le statut courant
                $this->current_projects_status = $this->loadData('projects_status');
                $this->current_projects_status->getLastStatut($this->projects->id_project);

                $select = '<input type="hidden" name="status" id="status" value="' . $this->current_projects_status->status . '" />';
                $select .= $this->current_projects_status->label;

                // pas encore fait
                if ($this->current_projects_status->status != \projects_status::REJET_ANALYSTE) {
                    $etape_7 = '
                    <div id="title_etape7">Etape 7</div>
                    <div id="etape7">
                        <table class="form tableNotes" style="width: 100%;">
                            <tr>
                                <th><label for="performance_fianciere2">Performance financière</label></th>
                                <td><span id="performance_fianciere2">' . $this->projects_notes->performance_fianciere . '</span> /10</td>
                                <th><label for="marche_opere">Marché opéré</label></th>
                                <td><span id="marche_opere2">' . $this->projects_notes->marche_opere . '</span> /10</td>

                                <th><label for="qualite_moyen_infos_financieres2">Qualité des moyens & infos financières</label></th>
                                <td><input tabindex="14" id="qualite_moyen_infos_financieres2" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->qualite_moyen_infos_financieres . '" name="qualite_moyen_infos_financieres" maxlength="4" onkeyup="nodizaines(this.value,this.id);"> /10</td>
                                <th><label for="notation_externe2">Notation externe</label></th>
                                <td><input tabindex="15" id="notation_externe2" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->notation_externe . '" name="notation_externe" maxlength="4" onkeyup="nodizaines(this.value,this.id);"> /10</td>
                            </tr>

                            <tr>
                                <td colspan="2" style="vertical-align:top;">
                                    <table>
                                        <tr>
                                            <th><label for="structure2">Structure</label></th>
                                            <td><input tabindex="9" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->structure . '" name="structure2" id="structure2" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> /10</td>
                                        </tr>
                                        <tr>
                                            <th><label for="rentabilite2">Rentabilité</label></th>
                                            <td><input tabindex="10" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->rentabilite . '" name="rentabilite2" id="rentabilite2" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> /10</td>
                                        </tr>
                                        <tr>
                                            <th><label for="tresorerie2">Trésorerie</label></th>
                                            <td><input tabindex="11" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->tresorerie . '" name="tresorerie2" id="tresorerie2" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> /10</td>
                                        </tr>

                                    </table>
                                </td>
                                <td colspan="2" style="vertical-align:top;">
                                    <table>
                                        <tr>
                                            <th><label for="global2">Global</label></th>
                                            <td><input tabindex="12" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->global . '" name="global2" id="global2" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> /10</td>
                                        </tr>
                                        <tr>
                                            <th><label for="individuel">Individuel</label></th>
                                            <td><input tabindex="13" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->individuel . '" name="individuel2" id="individuel2" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> /10</td>
                                        </tr>

                                    </table>
                                </td>
                                <td colspan="4"></td>
                            </tr>

                            <tr class="lanote">
                                <th colspan="8" style="text-align:center;" >Note : <span class="moyenneNote2">' . $this->projects_notes->note . '/10</span></th>
                            </tr>

                            <tr>
                                <td colspan="8" style="text-align:center;">
                                <label for="avis_comite" style="text-align:left;display: block;">Avis comité:</label><br />
                                <textarea name="avis_comite" tabindex="16" style="height:700px;" id="avis_comite" class="textarea_large avis_comite">' . $this->projects_notes->avis_comite . '</textarea>
                                 <script type="text/javascript">var ckedAvis_comite = CKEDITOR.replace(\'avis_comite\',{ height: 700});</script>
                                </td>
                            </tr>
                        </table>

                        <br /><br />
                        <div id="valid_etape7">Données sauvegardées</div>
                        <div class="btnDroite">
                            <input type="button" onclick="valid_rejete_etape7(3,' . $this->projects->id_project . ')" class="btn"  value="Sauvegarder">
                        ';
                    if ($this->current_projects_status->status == \projects_status::COMITE) {
                        $etape_7 .= '
                            <input type="button" onclick="valid_rejete_etape7(1,' . $this->projects->id_project . ')" class="btn btnValid_rejet_etape7" style="background:#009933;border-color:#009933;" value="Valider">
                            <input type="button" onclick="valid_rejete_etape7(2,' . $this->projects->id_project . ')" class="btn btnValid_rejet_etape7" style="background:#CC0000;border-color:#CC0000;" value="Rejeter">
                            <input type="button" onclick="valid_rejete_etape7(4,' . $this->projects->id_project . ')" class="btn btnValid_rejet_etape7" value="Plus d\'informations">';
                    }

                    $etape_7 .=
                        '</div>

                    </div>
                    <script type="text/javascript">
                        $("#title_etape7").click(function() {
                            $("#etape7").slideToggle();
                        });
                        $(".cal_moyen").keyup(function() {
                            // --- Chiffre et marché ---

                            // Variables
                            var structure = parseFloat($("#structure2").val().replace(",","."));
                            var rentabilite = parseFloat($("#rentabilite2").val().replace(",","."));
                            var tresorerie = parseFloat($("#tresorerie2").val().replace(",","."));

                            var global = parseFloat($("#global2").val().replace(",","."));
                            var individuel = parseFloat($("#individuel2").val().replace(",","."));

                            // Arrondis
                            structure = (Math.round(structure*10)/10);
                            rentabilite = (Math.round(rentabilite*10)/10);
                            tresorerie = (Math.round(tresorerie*10)/10);

                            global = (Math.round(global*10)/10);
                            individuel = (Math.round(individuel*10)/10);

                            // Calcules
                            var performance_fianciere = ((structure+rentabilite+tresorerie)/3);
                            performance_fianciere = (Math.round(performance_fianciere*10)/10);

                            // Arrondis
                            var marche_opere = ((global+individuel)/2);
                            marche_opere = (Math.round(marche_opere*10)/10);

                            // --- Fin chiffre et marché ---

                            // Variables
                            var qualite_moyen_infos_financieres = parseFloat($("#qualite_moyen_infos_financieres2").val().replace(",","."));
                            var notation_externe = parseFloat($("#notation_externe2").val().replace(",","."));

                            // Arrondis
                            qualite_moyen_infos_financieres = (Math.round(qualite_moyen_infos_financieres*10)/10);
                            notation_externe = (Math.round(notation_externe*10)/10);

                            // Calcules
                            var moyenne1 = (((performance_fianciere*0.4)+(marche_opere*0.3)+(qualite_moyen_infos_financieres*0.2)+(notation_externe*0.1)));

                            // Arrondis
                            moyenne = (Math.round(moyenne1*10)/10);

                            // Affichage
                            $("#marche_opere2").html(marche_opere);
                            $("#performance_fianciere2").html(performance_fianciere);
                            $(".moyenneNote2").html(moyenne+"/10");
                        });
                    </script>
                    ';
                } else {
                    $etape_7 = '';
                }

                echo json_encode(array('liste' => $select, 'etape_7' => $etape_7));
            } else {
                echo 'nok';
            }
        } else {
            echo 'nok';
        }
    }

    public function _valid_rejete_etape7()
    {
        $this->autoFireView = false;

        $this->projects                = $this->loadData('projects');
        $this->projects_notes          = $this->loadData('projects_notes');
        $this->projects_status         = $this->loadData('projects_status');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->companies               = $this->loadData('companies');
        $this->clients                 = $this->loadData('clients');
        $this->clients_history         = $this->loadData('clients_history');

        // on check si on a les posts
        if (isset($_POST['status']) && isset($_POST['id_project']) && $this->projects->get($_POST['id_project'], 'id_project')) {
            $form_ok = true;

            if ($_POST['status'] == 1) {
                if (! isset($_POST['structure']) || $_POST['structure'] == 0 || $_POST['structure'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['rentabilite']) || $_POST['rentabilite'] == 0 || $_POST['rentabilite'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['tresorerie']) || $_POST['tresorerie'] == 0 || $_POST['tresorerie'] > 10) {
                    $form_ok = false;
                }

                if (! isset($_POST['performance_fianciere']) || $_POST['performance_fianciere'] == 0 || $_POST['performance_fianciere'] > 10) {
                    $form_ok = false;
                }

                if (! isset($_POST['individuel']) || $_POST['individuel'] == 0 || $_POST['individuel'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['global']) || $_POST['global'] == 0 || $_POST['global'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['marche_opere']) || $_POST['marche_opere'] == 0 || $_POST['marche_opere'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['qualite_moyen_infos_financieres']) || $_POST['qualite_moyen_infos_financieres'] == 0 || $_POST['qualite_moyen_infos_financieres'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['notation_externe']) || $_POST['notation_externe'] == 0 || $_POST['notation_externe'] > 10) {
                    $form_ok = false;
                }
            }

            if (! isset($_POST['avis_comite']) && $_POST['status'] == 1 || strlen($_POST['avis_comite']) < 50 && $_POST['status'] == 1) {
                $form_ok = false;
            }

            if (! $this->companies->get($this->projects->id_company, 'id_company')) {
                $form_ok = false;
            }
            if (! $this->clients->get($this->companies->id_client_owner, 'id_client')) {
                $form_ok = false;
            }

            if ($form_ok == true) {
                // on check si existe deja
                if ($this->projects_notes->get($_POST['id_project'], 'id_project')) {
                    $update = true;
                } else {
                    $update = false;
                }

                $this->projects_notes->structure                       = number_format($_POST['structure'], 1, '.', '');
                $this->projects_notes->rentabilite                     = number_format($_POST['rentabilite'], 1, '.', '');
                $this->projects_notes->tresorerie                      = number_format($_POST['tresorerie'], 1, '.', '');
                $this->projects_notes->individuel                      = number_format($_POST['individuel'], 1, '.', '');
                $this->projects_notes->global                          = number_format($_POST['global'], 1, '.', '');
                $this->projects_notes->performance_fianciere           = number_format($_POST['performance_fianciere'], 1, '.', '');
                $this->projects_notes->marche_opere                    = number_format($_POST['marche_opere'], 1, '.', '');
                $this->projects_notes->qualite_moyen_infos_financieres = number_format($_POST['qualite_moyen_infos_financieres'], 1, '.', '');
                $this->projects_notes->notation_externe                = number_format($_POST['notation_externe'], 1, '.', '');
                $this->projects_notes->note                            = round(($this->projects_notes->performance_fianciere * 0.4) + ($this->projects_notes->marche_opere * 0.3) + ($this->projects_notes->qualite_moyen_infos_financieres * 0.2) + ($this->projects_notes->notation_externe * 0.1), 1);
                $this->projects_notes->avis_comite                     = $_POST['avis_comite'];

                // on enregistre
                if ($update == true) {
                    $this->projects_notes->update();
                } else {
                    $this->projects_notes->id_project = $this->projects->id_project;
                    $this->projects_notes->create();
                }

                // etoiles
                // A = 5
                // B = 4.5
                // C = 4
                // D = 3.5
                // E = 3
                // F = 2.5
                // G = 2
                // H = 1.5
                // I = 1
                // J = 0

                if ($this->projects_notes->note >= 0 && $this->projects_notes->note < 2) {
                    $lettre = 'I';
                } elseif ($this->projects_notes->note >= 2 && $this->projects_notes->note < 4) {
                    $lettre = 'G';
                } elseif ($this->projects_notes->note >= 4 && $this->projects_notes->note < 5.5) {
                    $lettre = 'E';
                } elseif ($this->projects_notes->note >= 5.5 && $this->projects_notes->note < 6.5) {
                    $lettre = 'D';
                } elseif ($this->projects_notes->note >= 6.5 && $this->projects_notes->note < 7.5) {
                    $lettre = 'C';
                } elseif ($this->projects_notes->note >= 7.5 && $this->projects_notes->note < 8.5) {
                    $lettre = 'B';
                } elseif ($this->projects_notes->note >= 8.5 && $this->projects_notes->note <= 10) {
                    $lettre = 'A';
                }

                $this->projects->risk = $lettre;
                $this->projects->update();

                $btn_etape6 = '';

                if ($_POST['status'] == 1) {
                    $this->projects_status_history->addStatus($_SESSION['user']['id_user'], \projects_status::PREP_FUNDING, $this->projects->id_project);

                    $aExistingStatus = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = ' . projects_status::PREP_FUNDING);
                    if (empty($aExistingStatus)) {
                        $this->sendEmailBorrowerArea('ouverture-espace-emprunteur-plein', $this->clients);
                    }

                    $content_risk = '
                        <th><label for="risk">Niveau de risque* :</label></th>
                        <td>
                            <select name="risk" id="risk" class="select" style="width:160px;background-color:#AAACAC;">
                                <option value="">Choisir</option>
                                <option ' . ($this->projects->risk == 'A' ? 'selected' : '') . ' value="A">5 étoiles</option>
                                <option ' . ($this->projects->risk == 'B' ? 'selected' : '') . ' value="B">4,5 étoiles</option>
                                <option ' . ($this->projects->risk == 'C' ? 'selected' : '') . ' value="C">4 étoiles</option>
                                <option ' . ($this->projects->risk == 'D' ? 'selected' : '') . ' value="D">3,5 étoiles</option>
                                <option ' . ($this->projects->risk == 'E' ? 'selected' : '') . ' value="E">3 étoiles</option>
                                <option ' . ($this->projects->risk == 'F' ? 'selected' : '') . ' value="F">2,5 étoiles</option>
                                <option ' . ($this->projects->risk == 'G' ? 'selected' : '') . ' value="G">2 étoiles</option>
                                <option ' . ($this->projects->risk == 'H' ? 'selected' : '') . ' value="H">1,5 étoiles</option>
                            </select>
                        </td>
                    ';
                } elseif ($_POST['status'] == 2) {
                    $this->projects_status_history->addStatus($_SESSION['user']['id_user'], \projects_status::REJET_COMITE, $this->projects->id_project);

                    //////////////////////////////////////
                    /// MAIL emprunteur-dossier-rejete ///
                    //////////////////////////////////////

                    $this->mails_text->get('emprunteur-dossier-rejete', 'lang = "' . $this->language . '" AND type');

                    $this->settings->get('Facebook', 'type');
                    $lien_fb = $this->settings->value;

                    $this->settings->get('Twitter', 'type');
                    $lien_tw = $this->settings->value;

                    $varMail = array(
                        'surl'                   => $this->surl,
                        'url'                    => $this->furl,
                        'prenom_e'               => $this->clients->prenom,
                        'link_compte_emprunteur' => $this->furl,
                        'lien_fb'                => $lien_fb,
                        'lien_tw'                => $lien_tw
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
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                    } else {
                        $this->email->addRecipient(trim($this->clients->email));
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                    }

                    // on passe l'emprunteur en offline
                    $this->clients->update();
                } elseif ($_POST['status'] == 4) {
                    $this->projects_status_history->addStatus($_SESSION['user']['id_user'], \projects_status::REVUE_ANALYSTE, $this->projects->id_project);

                    $btn_etape6 = '
                        <input type="button" onclick="valid_rejete_etape6(3,' . $this->projects->id_project . ')" class="btn"  value="Sauvegarder">
                        <input type="button" onclick="valid_rejete_etape6(1,' . $this->projects->id_project . ')" class="btn btnValid_rejet_etape6" style="background:#009933;border-color:#009933;" value="Valider">
                        <input type="button" onclick="valid_rejete_etape6(2,' . $this->projects->id_project . ')" class="btn btnValid_rejet_etape6" style="background:#CC0000;border-color:#CC0000;" value="Rejeter">
                    ';
                }

                //on recup le statut courant
                $this->current_projects_status = $this->loadData('projects_status');
                $this->current_projects_status->getLastStatut($this->projects->id_project);

                if ($this->current_projects_status->status == \projects_status::PREP_FUNDING) {
                    $this->lProjects_status = $this->projects_status->select(' status IN (35,40) ', ' status ASC ');
                } else {
                    $this->lProjects_status = array();
                }

                if (count($this->lProjects_status) > 0) {
                    $select = '<select name="status" id="status" class="select">';
                    foreach ($this->lProjects_status as $s) {
                        $select .= '<option ' . ($this->current_projects_status->status == $s['status'] ? 'selected' : '') . ' value="' . $s['status'] . '">' . $s['label'] . '</option>';
                    }
                    $select .= '</select>';

                } else {
                    $select = '<input type="hidden" name="status" id="status" value="' . $this->current_projects_status->status . '" />';
                    $select .= $this->current_projects_status->label;
                }

                echo json_encode(array('liste' => $select, 'btn_etape6' => $btn_etape6, 'content_risk' => $content_risk));
            } else {
                echo 'nok';
            }
        } else {
            echo 'nok';
        }
    }

    public function _session_content_email_completude()
    {
        $this->autoFireView = false;

        if (isset($_POST['id_client']) && isset($_POST['content']) && isset($_POST['liste'])) {
            $_SESSION['content_email_completude'][$_POST['id_client']] = utf8_decode($_POST['liste']) . ($_POST['content'] != '' ? '<br>' : '') . nl2br(htmlentities(utf8_decode($_POST['content'])));
            echo 'ok';
        } else {
            echo 'nok';
        }
    }

    public function _session_project_completude()
    {
        $this->autoFireView = false;

        if (isset($_POST['id_project']) && isset($_POST['content']) && isset($_POST['list'])) {
            $_SESSION['project_submission_files_list'][$_POST['id_project']] = '<ul>' . utf8_decode($this->ficelle->speChar2HtmlEntities($_POST['list'])) . '</ul>' . nl2br(htmlentities(utf8_decode($_POST['content'])));
            echo 'ok';
        } else {
            echo 'nok';
        }
    }

    public function _check_force_pass()
    {
        $this->autoFireView = false;
        $this->tab_result   = $this->ficelle->testpassword($_POST['pass']);

        if ($this->tab_result['score'] < 50) {
            $this->couleur = "red";
            $this->wording = "Faible";
        } elseif ($this->tab_result['score'] < 100) {
            $this->couleur = "orange";
            $this->wording = "Moyen";
        } elseif ($this->tab_result['score'] <= 500) {
            $this->couleur = "green";
            $this->wording = "Fort";
        } elseif ($this->tab_result['score'] > 500) {
            $this->couleur = "green";
            $this->wording = "Tr&egrave;s fort";
        }

        echo '
            <p class="password-info" >Indicateur de protection :
                <span style="color:' . $this->couleur . '">' . $this->wording . '</span>
            </p>
        ';
        die;
    }

    public function _ibanExist()
    {

        $companies = $this->loadData('companies');
        $list      = array();
        foreach ($companies->select('id_client_owner != "' . $this->bdd->escape_string($_POST['id']) . '" AND iban = "' . $this->bdd->escape_string($_POST['iban']) . '"') as $company) {
            $list[] = $company['id_company'] . ': ' . $company['name'];
        }
        if (count($list) != 0) {
            echo implode(' / ', $list);
        } else {
            echo "none";
        }
        die;
    }

    public function _ibanExistV2()
    {
        $this->autoFireView = false;

        $companies = $this->loadData('companies');
        $list      = array();
        foreach (
            $companies->select('
                id_client_owner != "' . $this->bdd->escape_string($_POST['id']) . '"
                AND iban = "' . $this->bdd->escape_string($_POST['iban']) . '"
                AND bic = "' . $this->bdd->escape_string($_POST['bic']) . '"'
            ) as $company
        ) {
            $list[] = $company['id_company'];
        }
        if (count($list) != 0) {
            echo implode('-', $list);
        } else {
            echo "none";
        }
        die;
    }

    public function _recouvrement()
    {
        $this->projects                = $this->loadData('projects');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->echeanciers             = $this->loadData('echeanciers');
        $this->receptions              = $this->loadData('receptions');

        if (isset($_POST['id_reception']) && $this->receptions->get($_POST['id_reception'], 'type = 1 AND type_remb = 3 AND id_reception')) {
            $this->projects->get($this->receptions->id_project, 'id_project');

            $retour = $_POST['date'];
            if ($retour != false) {
                $retour = explode('/',$retour);
                $retour = $retour[2].'-'.$retour[1].'-'.$retour[0];
                $this->lastDateRecouvrement = date('d/m/Y', strtotime($retour));
                $this->lastFormatSql = date('Y-m-d', strtotime($retour));
                $_SESSION['DER'] = $this->lastFormatSql;
            } else {
                $this->lastDateRecouvrement = date('d/m/Y');
                $this->lastFormatSql = date('Y-m-d');
                $_SESSION['DER'] = $this->lastFormatSql;
            }

            $this->CapitalEchu      = $this->echeanciers->sum('id_project = ' . $this->projects->id_project . ' AND status = 0 AND LEFT(date_echeance,10) <= "' . $this->lastFormatSql . '"', 'capital');
            $this->InteretsEchu     = $this->echeanciers->sum('id_project = ' . $this->projects->id_project . ' AND status = 0 AND LEFT(date_echeance,10) <= "' . $this->lastFormatSql . '"', 'interets');
            $this->CapitalRestantDu = $this->echeanciers->sum('id_project = ' . $this->projects->id_project . ' AND status = 0 AND LEFT(date_echeance,10) > "' . $this->lastFormatSql . '"', 'capital');
            $lastEcheanceImpaye     = $this->echeanciers->select('id_project = ' . $this->projects->id_project . ' AND status = 0 AND LEFT(date_echeance,10) <=  "' . $this->lastFormatSql . '"', 'date_echeance DESC', 0, 1);
            $dateLastEcheanceImpaye = date('Y-m-d', strtotime($lastEcheanceImpaye[0]['date_echeance']));
            $echeanceMoisDER        = $this->echeanciers->select('id_project = ' . $this->projects->id_project . ' AND ordre = ' . ($lastEcheanceImpaye[0]['ordre'] + 1), 'date_echeance DESC', 0, 1);
            $interetsMoisDER        = $this->echeanciers->sum('id_project = ' . $this->projects->id_project . ' AND ordre = ' . ($lastEcheanceImpaye[0]['ordre'] + 1), 'interets');
            $nbJourMoisDER          = date('t', strtotime($echeanceMoisDER[0]['date_echeance']));
            $diff                   = $this->dates->nbJours($dateLastEcheanceImpaye, $this->lastFormatSql);
            $this->interetsCourus   = round(($diff / $nbJourMoisDER) * $interetsMoisDER, 2);
            $this->montantRecouvre  = $this->receptions->sum('type_remb = 3 AND type = 1 AND remb = 1 AND id_project = ' . $this->projects->id_project);
            $this->montantRecouvre  = $this->montantRecouvre / 100;
        } else {
            die;
        }
    }

    public function _get_cities()
    {
        $this->autoFireView = false;
        $aCities = array();
        if (isset($_GET['term']) && '' !== trim($_GET['term'])) {
            $_GET  = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
            $oVilles = $this->loadData('villes');

            $bBirthPlace = false;
            if (isset($this->params[0]) && 'birthplace' === $this->params[0]) {
                $bBirthPlace = true;
            }

            if ($bBirthPlace) {
                $aResults = $oVilles->lookupCities($_GET['term'], array('ville', 'cp'), true);
            } else {
                $aResults = $oVilles->lookupCities($_GET['term']);
            }
            if (false === empty($aResults)) {
                if ($bBirthPlace) {
                    foreach ($aResults as $aItem) {

                        // unique insee code
                        $aCities[$aItem['insee']] = array(
                            'label' => $aItem['ville'] . ' (' . $aItem['num_departement'] . ')',
                            'value' => $aItem['insee']
                        );
                    }
                    $aCities = array_values($aCities);
                } else {
                    foreach ($aResults as $aItem) {
                        $aCities[] = array(
                            'label' => $aItem['ville'] . ' (' . $aItem['cp'] . ')',
                            'value' => $aItem['insee']
                        );
                    }
                }
            }
        }

        echo json_encode($aCities);
    }

    public function _updateClientFiscalAddress()
    {
        $this->autoFireView = false;

        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        $sResult = 'nok';

        if (isset($this->params[0]) && isset($this->params[1])) {
            if ('0' == $this->params[1]) {
                /** @var clients_adresses $oClientAddress */
                $oClientAddress = $this->loadData('clients_adresses');

                if ($oClientAddress->get($this->params[0], 'id_client')) {

                    $oClientAddress->cp_fiscal    = $_POST['zip'];
                    $oClientAddress->ville_fiscal = $_POST['city'];
                    $oClientAddress->update();
                    $sResult = 'ok';
                }
            } elseif ('1' == $this->params[1]) {
                /** @var companies $oCompanies */
                $oCompanies = $this->loadData('companies');
                if ($oCompanies->get($this->params[0], 'id_client_owner')) {

                    $oCompanies->zip = $_POST['zip'];
                    $oCompanies->city = $_POST['city'];
                    $oCompanies->update();
                    $sResult = 'ok';
                }
            }
        }

        echo $sResult;
    }

    public function _patchClient()
    {
        $this->autoFireView = false;
        $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        /** @var clients $oClient */
        $oClient = $this->loadData('clients');

        $sResult = 'nok';

        if (isset($this->params[0]) && $oClient->get($this->params[0])) {
            foreach ($_POST as $item => $value) {
                $oClient->$item = $value;
            }
            $oClient->update();
            $sResult = 'ok';
        }

        echo $sResult;
    }

    public function _send_email_borrower_area()
    {
        $this->autoFireView = false;

        if (isset($_POST['id_client'], $_POST['type'])) {
            $oClients = $this->loadData('clients');
            $oClients->get($_POST['id_client'], 'id_client');

            switch ($_POST['type']) {
                case 'open':
                    $sTypeEmail = 'ouverture-espace-emprunteur';
                    break;
                case 'initialize':
                    $sTypeEmail = 'mot-de-passe-oublie-emprunteur';
                    break;
            }
            $this->sendEmailBorrowerArea($sTypeEmail, $oClients);
        }
    }

    private function sendEmailBorrowerArea($sTypeEmail, clients $oClients)
    {
        $oMailsText = $this->loadData('mails_text');
        $oMailsText->get($sTypeEmail, 'lang = "fr" AND type');

        $this->settings->get('Facebook', 'type');
        $sFacebookURL = $this->settings->value;

        $this->settings->get('Twitter', 'type');
        $sTwitterURL = $this->settings->value;

        $oTemporaryLink = $this->loadData('temporary_links_login');
        $sTemporaryLink = $this->surl . '/espace_emprunteur/securite/' . $oTemporaryLink->generateTemporaryLink($oClients->id_client);

        $aVariables = array(
            'surl'                   => $this->surl,
            'url'                    => $this->url,
            'link_compte_emprunteur' => $sTemporaryLink,
            'lien_fb'                => $sFacebookURL,
            'lien_tw'                => $sTwitterURL,
            'prenom'                 => $oClients->prenom
        );

        $sRecipient = $oClients->email;

        $this->email = $this->loadLib('email');
        $this->email->setFrom($oMailsText->exp_email, utf8_decode($oMailsText->exp_name));
        $this->email->setSubject(stripslashes(utf8_decode($oMailsText->subject)));
        $this->email->setHTMLBody(stripslashes(strtr(utf8_decode($oMailsText->content),

        $this->tnmp->constructionVariablesServeur($aVariables))));

        if ($this->Config['env'] == 'prod') {
            Mailer::sendNMP($this->email, $this->mails_filer, $oMailsText->id_textemail, $sRecipient, $aNMPResponse);
            $this->tnmp->sendMailNMP($aNMPResponse, $aVariables, $oMailsText->nmp_secure, $oMailsText->id_nmp, $oMailsText->nmp_unique, $oMailsText->mode);
        } else {
            $this->email->addRecipient($sRecipient);
            Mailer::send($this->email, $this->mails_filer, $oMailsText->id_textemail);
        }
    }
}
