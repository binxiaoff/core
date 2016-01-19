<?php
use Unilend\librairies\ULogger;

class clientsController extends bootstrap
{
    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        // Check de la plateforme
        if ($this->cms == 'iZinoa') {
            // Renvoi sur la page de gestion de l'arbo
            header('Location:' . $this->lurl . '/tree');
            die;
        }

        // Controle d'acces � la rubrique
        $this->users->checkAccess('clients');

        // Activation du menu
        $this->menu_admin = 'clients';
    }

    public function _exportNews()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        // On place le redirect sur la home
        $_SESSION['request_url'] = $this->url;

        // Requete de l'export des traductions
        $this->requete        = 'SELECT nom AS Nom,prenom AS Prenom,email AS Email,id_langue AS Langue FROM newsletters WHERE status = 1 ORDER BY added DESC';
        $this->requete_result = $this->bdd->query($this->requete);
    }

    public function _newsletter()
    {
        // Chargement du data
        $this->newsletters = $this->loadData('newsletters');

        // Recuperation de la liste des inscrits
        $this->lInscrits = $this->newsletters->select('', 'added DESC LIMIT 50');

        // Suppression d'un inscrit
        if (isset($this->params[0]) && $this->params[0] == 'quit') {
            // Recuperation des infos du gars
            $this->newsletters->get($this->params[1], 'id_newsletter');

            // On le desabonne
            $this->newsletters->status = 0;

            // MAJ
            $this->newsletters->update();

            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Abonnement client';
            $_SESSION['freeow']['message'] = 'Le client est d&eacute;sabonn&eacute; !';

            // Renvoi sur la page de gestion
            header('Location:' . $this->lurl . '/clients/newsletter');
            die;
        }

        // ajout d'un inscrit
        if (isset($this->params[0]) && $this->params[0] == 'join') {
            // Recuperation des infos du gars
            $this->newsletters->get($this->params[1], 'id_newsletter');

            // On le desabonne
            $this->newsletters->status = 1;

            // MAJ
            $this->newsletters->update();

            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Abonnement client';
            $_SESSION['freeow']['message'] = 'Le client est abonn&eacute; !';

            // Renvoi sur la page de gestion
            header('Location:' . $this->lurl . '/clients/newsletter');
            die;
        }
    }

    public function _addGroupe()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        // On place le redirect sur la home
        $_SESSION['request_url'] = $this->url;
    }

    public function _editgroupe()
    {
        // Chargement du data
        $this->groupes = $this->loadData('groupes');

        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        // On place le redirect sur la home
        $_SESSION['request_url'] = $this->url;

        // Recuperation des infos du groupe
        $this->groupes->get($this->params[0], 'id_groupe');
    }

    public function _groupes()
    {
        // Chargement du data
        $this->groupes         = $this->loadData('groupes');
        $this->clients_groupes = $this->loadData('clients_groupes');

        // Recuperation de la liste des groupes
        $this->lGroupes = $this->groupes->select('', 'nom ASC');

        // Formulaire d'ajout d'un groupes
        if (isset($_POST['form_add_groupes'])) {
            $this->groupes->nom    = $_POST['nom'];
            $this->groupes->slug   = $this->bdd->generateSlug($_POST['nom']);
            $this->groupes->status = $_POST['status'];
            $this->groupes->create();

            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Ajout d\'un groupe';
            $_SESSION['freeow']['message'] = 'Le groupe a bien &eacute;t&eacute; ajout&eacute; !';

            // Renvoi sur la liste des groupes
            header('Location:' . $this->lurl . '/clients/groupes');
            die;
        }

        // Formulaire de modification d'un groupes
        if (isset($_POST['form_edit_groupes'])) {
            // Recuperation des infos du groupe
            $this->groupes->get($this->params[0], 'id_groupe');

            $this->groupes->nom    = $_POST['nom'];
            $this->groupes->slug   = $this->bdd->generateSlug($_POST['nom']);
            $this->groupes->status = $_POST['status'];
            $this->groupes->update();

            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Modification d\'un groupe';
            $_SESSION['freeow']['message'] = 'Le groupe a bien &eacute;t&eacute; modifi&eacute; !';

            // Renvoi sur la liste des zones
            header('Location:' . $this->lurl . '/clients/groupes');
            die;
        }

        // Suppression d'un groupe
        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            // Recuperation des infos du groupe
            $this->groupes->get($this->params[1], 'id_groupe');

            // Suppression des associaitons de clients de ce groupe (ca veut rien dire mais je le dit quand m�me)
            $this->clients_groupes->delete(array('id_groupe' => $this->groupes->id_groupe));

            // Suppression du groupe
            $this->groupes->delete($this->params[1], 'id_groupe');

            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Suppression d\'un groupe';
            $_SESSION['freeow']['message'] = 'Le groupe a bien &eacute;t&eacute; supprim&eacute; !';

            // Renvoi sur la page de gestion
            header('Location:' . $this->lurl . '/clients/groupes');
            die;
        }
    }

    public function _detailsGroupe()
    {
        // Chargement du data
        $this->groupes         = $this->loadData('groupes');
        $this->clients_groupes = $this->loadData('clients_groupes');
        $this->clients         = $this->loadData('clients');

        // Recuperation des infos du groupe
        $this->groupes->get($this->params[0], 'id_groupe');

        // Recuperation de la liste des groupes
        $this->lClients = $this->clients_groupes->select('id_groupe = ' . $this->params[0]);

        // Suppression d'un client
        if (isset($this->params[1]) && $this->params[1] == 'delete') {
            // Suppression des associaitons de clients de ce groupe (ca veut rien dire mais je le dit quand m�me)
            $this->clients_groupes->delete(array('id_groupe' => $this->params[0], 'id_client' => $this->params[2]));

            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Suppression d\'un client';
            $_SESSION['freeow']['message'] = 'Le client a bien &eacute;t&eacute; supprim&eacute; !';

            // Renvoi sur la page de gestion
            header('Location:' . $this->lurl . '/clients/detailsGroupe/' . $this->params[0]);
            die;
        }
    }

    public function _default()
    {
        // Chargement du data
        $this->clients          = $this->loadData('clients');
        $this->transactions     = $this->loadData('transactions');
        $this->clients_adresses = $this->loadData('clients_adresses');

        // Formulaire d'ajout d'un client
        if (isset($_POST['form_add_client'])) {
            if ($_POST['email'] == '') {
                // Mise en session du message
                $_SESSION['freeow']['title']   = 'Erreur';
                $_SESSION['freeow']['message'] = 'L\'adresse email du client est obligatoire !';
            } elseif (!$this->ficelle->isEmail(trim($_POST['email']))) {
                // Mise en session du message
                $_SESSION['freeow']['title']   = 'Erreur';
                $_SESSION['freeow']['message'] = 'L\'adresse email du client n\'est pas valide !';
            } elseif (!$this->clients->existEmail(trim($_POST['email']))) {
                // Mise en session du message
                $_SESSION['freeow']['title']   = 'Erreur';
                $_SESSION['freeow']['message'] = 'L\'adresse email du client est d&eacute;j&agrave; enregistr&eacute;e !';
            } else {
                // On ajoute le client
                $this->clients->id_langue = $this->language;
                $this->clients->civilite  = $_POST['civilite'];
                $this->clients->prenom    = $_POST['prenom'];
                $this->clients->nom       = $_POST['nom'];
                $this->clients->slug      = $this->bdd->generateSlug($_POST['nom'] . ' ' . $_POST['prenom']);
                $this->clients->telephone = $_POST['telephone'];
                $this->clients->email     = $_POST['email'];
                $this->new_password       = $this->ficelle->generatePassword(8);
                $this->clients->password  = md5($this->new_password);
                $this->clients->optin1    = 0;
                $this->clients->optin2    = 0;
                $this->clients->status    = 1;
                $this->clients->id_client = $this->clients->create();

                // On ajoute son adresse de facturation par defaut
                $this->clients_adresses->id_client   = $this->clients->id_client;
                $this->clients_adresses->defaut      = 1;
                $this->clients_adresses->type        = 1;
                $this->clients_adresses->nom_adresse = $_POST['nom'] . ' ' . $_POST['prenom'];
                $this->clients_adresses->civilite    = $_POST['civilite'];
                $this->clients_adresses->nom         = $_POST['nom'];
                $this->clients_adresses->prenom      = $_POST['prenom'];
                $this->clients_adresses->societe     = '';
                $this->clients_adresses->adresse1    = $_POST['adresse1'];
                $this->clients_adresses->adresse2    = $_POST['adresse2'];
                $this->clients_adresses->adresse3    = $_POST['adresse3'];
                $this->clients_adresses->cp          = $_POST['cp'];
                $this->clients_adresses->ville       = $_POST['ville'];
                $this->clients_adresses->id_pays     = $_POST['id_pays'];
                $this->clients_adresses->telephone   = $_POST['telephone'];
                $this->clients_adresses->status      = 1;
                $this->clients_adresses->id_adresse  = $this->clients_adresses->create();

                //*********************************************************//
                //*** ENVOI DU MAIL DE CONFIRMATION INSCRIPTION NON EMT ***//
                //*********************************************************//

                // Recuperation du modele de mail
                $this->mails_text->get('admin-confirmation-inscription-client', 'lang = "' . $this->language . '" AND type');

                // Variables du mailing
                $aVarEmail = array (
                    '$cms'      => $this->cms,
                    '$surl'     => $this->surl,
                    '$url'      => $this->urlfront . '/' . $this->language,
                    '$prenom'   => trim($_POST['prenom']),
                    '$email'    => trim($_POST['email']),
                    '$password' => $this->new_password,
                );

                /** @var unilend_email $oUnilendEmail */
                $oUnilendEmail = $this->loadLib('unilend_email');

                try {
                    $oUnilendEmail->addVariables($aVarEmail);
                    $oUnilendEmail->setTemplate('admin-confirmation-inscription-client', $this->language);
                    $oUnilendEmail->addRecipient($_POST['email']);
                    $oUnilendEmail->sendToStaff();
                } catch (\Exception $oException) {
                    $oMailLogger = new ULogger('mail', $this->logPath, 'mail.log');
                    $oMailLogger->addRecord(ULogger::CRITICAL, 'Caught Exception: ' . $oException->getMessage() . ' ' . $oException->getTraceAsString());
                }

                // Mise en session du message
                $_SESSION['freeow']['title']   = 'Ajout d\'un client';
                $_SESSION['freeow']['message'] = 'Le client a bien &eacute;t&eacute; ajout&eacute; !';
            }
        }

        // Formulaire d'edition d'un client
        if (isset($_POST['form_edit_client'])) {
            // Recuperation des infos du client
            $this->clients->get($this->params[0], 'id_client');
            $this->clients_adresses->get($this->params[0] . ' AND defaut = 1 AND type = 0', 'id_client');

            if ($_POST['email'] == '') {
                // Mise en session du message
                $_SESSION['freeow']['title']   = 'Erreur';
                $_SESSION['freeow']['message'] = 'L\'adresse email du client est obligatoire !';
            } elseif (!$this->ficelle->isEmail(trim($_POST['email']))) {
                // Mise en session du message
                $_SESSION['freeow']['title']   = 'Erreur';
                $_SESSION['freeow']['message'] = 'L\'adresse email du client n\'est pas valide !';
            } elseif (!$this->clients->existEmail(trim($_POST['email'])) && $this->clients->email != $_POST['email']) {
                // Mise en session du message
                $_SESSION['freeow']['title']   = 'Erreur';
                $_SESSION['freeow']['message'] = 'L\'adresse email du client est d&eacute;j&agrave; enregistr&eacute;e !';
            } else {
                // On modifie le client
                $this->clients->civilite  = $_POST['civilite'];
                $this->clients->prenom    = $_POST['prenom'];
                $this->clients->nom       = $_POST['nom'];
                $this->clients->slug      = $this->bdd->generateSlug($_POST['nom'] . ' ' . $_POST['prenom']);
                $this->clients->telephone = $_POST['telephone'];
                $this->clients->email     = $_POST['email'];
                $this->clients->naissance = $_POST['naissance'];
                $this->clients->optin1    = 0;
                $this->clients->optin2    = 0;
                $this->clients->status    = 1;
                $this->clients->update();

                // On modifie son adresse de facturation par defaut
                $this->clients_adresses->nom_adresse = $_POST['nom'] . ' ' . $_POST['prenom'];
                $this->clients_adresses->civilite    = $_POST['civilite'];
                $this->clients_adresses->nom         = $_POST['nom'];
                $this->clients_adresses->prenom      = $_POST['prenom'];
                $this->clients_adresses->adresse1    = $_POST['adresse1'];
                $this->clients_adresses->adresse2    = $_POST['adresse2'];
                $this->clients_adresses->adresse3    = $_POST['adresse3'];
                $this->clients_adresses->cp          = $_POST['cp'];
                $this->clients_adresses->ville       = $_POST['ville'];
                $this->clients_adresses->id_pays     = $_POST['id_pays'];
                $this->clients_adresses->telephone   = $_POST['telephone'];
                $this->clients_adresses->update();

                // Mise en session du message
                $_SESSION['freeow']['title']   = 'Modification d\'un client';
                $_SESSION['freeow']['message'] = 'Le client a bien &eacute;t&eacute; modifi&eacute; !';

                // Renvoi sur la liste des groupes
                header('Location:' . $this->lurl . '/clients');
                die;
            }
        }

        if (isset($_POST['form_search_client'])) {
            // Recuperation de la liste des clients
            $this->lClients = $this->clients->searchClients($_POST['reference'], $_POST['nom'], $_POST['email'], $_POST['prenom']);

            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Recherche d\'un client';
            $_SESSION['freeow']['message'] = 'La recherche est termin&eacute;e !';
        } else {
            // On recupera les 10 derniers clients
            $this->lClients = $this->clients->select('status = 1', 'added DESC', 0, 25);
        }
    }

    public function _search()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        // On place le redirect sur la home
        $_SESSION['request_url'] = $this->url;
    }

    public function _add()
    {
        // Chargement du data
        $this->pays    = $this->loadData('pays');
        $this->clients = $this->loadData('clients');

        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        // On place le redirect sur la home
        $_SESSION['request_url'] = $this->url;

        // Recuperation de la liste des pays
        $this->lPays = $this->pays->select('', $this->language . ' ASC');
    }

    public function _edit()
    {
        // Chargement du data
        $this->pays             = $this->loadData('pays');
        $this->clients          = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');

        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        // On place le redirect sur la home
        $_SESSION['request_url'] = $this->url;

        // Recuperation de la liste des pays
        $this->lPays = $this->pays->select('', $this->language . ' ASC');

        // Recuperations des infos du client
        $this->clients->get($this->params[0], 'id_client');

        // Recuperation de son adresse de facturation par defaut
        $this->clients_adresses->get($this->params[0] . ' AND defaut = 1 AND type = 1', 'id_client');
    }

    public function _detailsClient()
    {
        // Declaration des classes
        $this->transactions = $this->loadData('transactions');
        $this->clients      = $this->loadData('clients');

        // Recuperation des infoq du client
        $this->clients->get($this->params[0]);

        // Recuperation des commandes en cours de traitement
        $this->lCommandes = $this->transactions->select('status = 1 AND id_client = ' . $this->params[0], 'etat ASC,date_transaction DESC');

        // Traitements
        if (isset($this->params[0]) && $this->params[0] == 'aCommande') {
            // Recuperation des infos du transaction
            $this->transactions->get($this->params[1], 'id_transaction');
            $this->transactions->etat = 3;
            $this->transactions->update();

            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Annulation d\'une commande';
            $_SESSION['freeow']['message'] = 'La commande a bien &eacute;t&eacute; annul&eacute;e !';

            // Renvoi sur la page de gestion
            header('Location:' . $this->lurl . '/clients/detailsClient/' . $this->params[2]);
            die;
        }
    }

    public function _detailsCommande()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        // On place le redirect sur la home
        $_SESSION['request_url'] = $this->lurl;

        // Declaration des classes
        $this->transactions          = $this->loadData('transactions');
        $this->transactions_produits = $this->loadData('transactions_produits');
        $this->transactions_cadeaux  = $this->loadData('transactions_cadeaux');
        $this->fdp_type              = $this->loadData('fdp_type');
        $this->clients_adresses      = $this->loadData('clients_adresses');

        // Recuperation des infos de la commandes
        $this->transactions->get($this->params[0], 'id_transaction');

        // Recuperation de la liste des produits de la transaction
        $this->lProduits = $this->transactions_produits->select('id_transaction = ' . $this->transactions->id_transaction);

        // Recuperation de la liste des cadeaux et ou echantillons de la transaction
        $this->lKdos = $this->transactions_cadeaux->select('id_transaction = ' . $this->transactions->id_transaction);
    }
}