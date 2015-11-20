<?php

class bootstrap extends Controller
{
    /**
     * @object data\crud\companies
     * @desc object for Companies infos
     */
    public $companies;

    /**
     * @object data\crud\projects
     * @des obecjt for Projects infos
     */
    public $projects;

    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        if ($this->current_function != 'login') {
            $_SESSION['redirection_url'] = $_SERVER['REQUEST_URI'];
        }

        $this->upload = $this->loadLib('upload');

        $this->settings                = $this->loadData('settings');
        $this->tree_elements           = $this->loadData('tree_elements');
        $this->blocs_elements          = $this->loadData('blocs_elements');
        $this->tree                    = $this->loadData('tree', array('url' => $this->url, 'surl' => $this->surl, 'tree_elements' => $this->tree_elements, 'blocs_elements' => $this->blocs_elements, 'upload' => $this->upload, 'spath' => $this->spath));
        $this->templates               = $this->loadData('templates');
        $this->elements                = $this->loadData('elements');
        $this->blocs_templates         = $this->loadData('blocs_templates');
        $this->blocs                   = $this->loadData('blocs');
        $this->mails_filer             = $this->loadData('mails_filer');
        $this->mails_text              = $this->loadData('mails_text');
        $this->ln                      = $this->loadData('textes');
        $this->routages                = $this->loadData('routages', array('url' => $this->lurl, 'route' => $this->Config['route_url']));
        $this->nmp                     = $this->loadData('nmp');
        $this->nmp_desabo              = $this->loadData('nmp_desabo');
        $this->clients                 = $this->loadData('clients');
        $this->clients_adresses        = $this->loadData('clients_adresses');
        $this->clients_history         = $this->loadData('clients_history');
        $this->villes                  = $this->loadData('villes');
        $this->transactions            = $this->loadData('transactions');
        $this->clients_history_actions = $this->loadData('clients_history_actions');
        $this->clients_status          = $this->loadData('clients_status');
        $this->login_log               = $this->loadData('login_log');
        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
        $this->convert_api_compteur    = $this->loadData('convert_api_compteur');
        $this->accept_cookies          = $this->loadData('accept_cookies');
        $this->companies               = $this->loadData('companies');
        $this->projects                = $this->loadData('projects');
        $this->lenders_accounts        = $this->loadData('lenders_accounts');


        $this->ficelle = $this->loadLib('ficelle');
        $this->photos  = $this->loadLib('photos', array($this->spath, $this->surl));
        $this->tnmp    = $this->loadLib('tnmp', array($this->nmp, $this->nmp_desabo, $this->Config['env']));
        $this->dates   = $this->loadLib('dates');

        // Recuperation de la liste des langue disponibles
        $this->lLangues = $this->Config['multilanguage']['allowed_languages'];

        // Formulaire de modification d'un texte de traduction
        if (isset($_POST['form_mod_traduction'])) {
            foreach ($this->lLangues as $key => $lng) {
                $values[ $key ] = $_POST[ 'texte-' . $key ];
            }

            $this->ln->updateTextTranslations($_POST['section'], $_POST['nom'], $values);
        }

        $this->loadCss('default/izicom');
        $this->loadCss('default/colorbox');
        $this->loadCss('default/fonts');
        $this->loadCss('default/jquery.c2selectbox');
        $this->loadCss('default/jquery-ui-1.10.3.custom');
        $this->loadCss('default/custom-theme/jquery-ui-1.10.3.custom');//datepicker
        $this->loadCss('default/style', 0, 'all', 'css', date('Ymd')); // permet d'avoir un nouveau cache de js par jour chez l'utilisateur
        $this->loadCss('default/style-edit', 0, 'all', 'css', date('Ymd'));

        $this->loadJs('default/jquery/jquery-1.10.2.min');
        $this->loadJs('default/bootstrap-tooltip');
        $this->loadJs('default/jquery.carouFredSel-6.2.1-packed');
        $this->loadJs('default/jquery.c2selectbox');
        $this->loadJs('default/livevalidation_standalone.compressed');
        $this->loadJs('default/jquery.colorbox-min');
        $this->loadJs('default/jquery-ui-1.10.3.custom.min');
        $this->loadJs('default/jquery-ui-1.10.3.custom2');
        $this->loadJs('default/ui.datepicker-fr');
        $this->loadJs('default/highcharts.src');
        $this->loadJs('default/functions', 0, date('Ymd'));
        $this->loadJs('default/main', 0, date('YmdH'));
        $this->loadJs('default/ajax', 0, date('Ymd'));

        $this->meta_title       = '';
        $this->meta_description = '';
        $this->meta_keywords    = '';

        // Lutte contre le XSS
        if (is_array($_POST)) {
            foreach ($_POST as $key => $value) {
                $_POST[ $key ] = htmlspecialchars(strip_tags($value));
            }
        }

        // Mise en tableau de l'url
        $urlParams = explode('/', $_SERVER['REQUEST_URI']);

        // On sniff le partenaire
        $this->handlePartenaire($urlParams);

        $sKey      = $this->oCache->makeKey('Settings_GoogleTools_Analytics_BaseLine_FB_Twitter_Cookie');
        $aElements = $this->oCache->get($sKey);
        if (false === $aElements) {
            $this->settings->get('Google Webmaster Tools', 'type');
            $this->google_webmaster_tools = $this->settings->value;

            // Recuperation du code Google Analytics
            $this->settings->get('Google Analytics', 'type');
            $this->google_analytics = $this->settings->value;

            // Recuperation de la Baseline Title
            $this->settings->get('Baseline Title', 'type');
            $this->baseline_title = $this->settings->value;


            // Récup du lien fb
            $this->settings->get('Facebook', 'type');
            $this->like_fb = $this->settings->value;

            // Récup du lien Twitter
            $this->settings->get('Twitter', 'type');
            $this->twitter = $this->settings->value;

            // lien page cookies (id_tree)
            $this->settings->get('id page cookies', 'type');
            $this->id_tree_cookies = $this->settings->value;

            $aElements = array(
                'GoogleTools'     => $this->google_webmaster_tools,
                'GoogleAnalytics' => $this->google_analytics,
                'BaselineTitle'   => $this->baseline_title,
                'Facebook'        => $this->like_fb,
                'Twitter'         => $this->twitter,
                'TreeCookies'     => $this->id_tree_cookies
            );

            $this->oCache->set($sKey, $aElements, \Unilend\librairies\Cache::LONG_TIME);
        } else {
            $this->google_webmaster_tools = $aElements['GoogleTools'];
            $this->google_analytics       = $aElements['GoogleAnalytics'];
            $this->baseline_title         = $aElements['BaselineTitle'];
            $this->like_fb                = $aElements['Facebook'];
            $this->twitter                = $aElements['Twitter'];
            $this->id_tree_cookies        = $aElements['TreeCookies'];
        }
        // super login //

        /////////////////

        // Récuperation du menu footer
        $this->menuFooter = $this->tree->getMenu('footer', $this->language, $this->lurl);

        $this->navFooter1 = $this->tree->getMenu('footer-nav-1', $this->language, $this->lurl);
        $this->navFooter2 = $this->tree->getMenu('footer-nav-2', $this->language, $this->lurl);
        $this->navFooter3 = $this->tree->getMenu('footer-nav-3', $this->language, $this->lurl);
        $this->navFooter4 = $this->tree->getMenu('footer-nav-4', $this->language, $this->lurl);

        // Notes
        $this->lNotes = array(
            'A' => 'etoile1',
            'B' => 'etoile2',
            'C' => 'etoile3',
            'D' => 'etoile4',
            'E' => 'etoile5',
            'F' => 'etoile6',
            'G' => 'etoile7',
            'H' => 'etoile8',
            'I' => 'etoile9',
            'J' => 'etoile10'
        );

        // Recuperation du bloc nos-partenaires
        $sKey      = $this->oCache->makeKey('Blocs_Partenaires', $this->blocs->id_bloc, $this->language);
        $aElements = $this->oCache->get($sKey);
        if (false === $aElements) {
            $this->blocs->get('nos-partenaires', 'slug');
            $lElements = $this->blocs_elements->select('id_bloc = ' . $this->blocs->id_bloc . ' AND id_langue = "' . $this->language . '"');
            foreach ($lElements as $b_elt) {
                $this->elements->get($b_elt['id_element']);
                $this->bloc_partenaires[ $this->elements->slug ]           = $b_elt['value'];
                $this->bloc_partenairesComplement[ $this->elements->slug ] = $b_elt['complement'];
            }

            $aElements = array(
                'blocPartenaires'           => $this->bloc_partenaires,
                'blocPartenairesComplement' => $this->bloc_partenairesComplement
            );

            $this->oCache->set($sKey, $aElements, \Unilend\librairies\Cache::MEDIUM_TIME);
        }

        $this->bloc_partenaires           = $aElements['blocPartenaires'];
        $this->bloc_partenairesComplement = $aElements['blocPartenairesComplement'];

        //Recuperation des element de traductions
        $sKey      = $this->oCache->makeKey('Trad_Header_Footer_home');
        $aElements = $this->oCache->get($sKey);
        if (false === $aElements) {
            $aElements = array(
                'TradHeader' => $this->ln->selectFront('header', $this->language, $this->App),
                'TradFooter' => $this->ln->selectFront('footer', $this->language, $this->App),
                'TradHome'   => $this->ln->selectFront('home', $this->language, $this->App)
            );

            $this->oCache->set($sKey, $aElements, \Unilend\librairies\Cache::LONG_TIME);
        }

        $this->lng['header'] = $aElements['TradHeader'];
        $this->lng['footer'] = $aElements['TradFooter'];
        $this->lng['home']   = $aElements['TradHome'];


        //gestion du captcha
        if (isset($_POST["captcha"])) {
            if (isset($_SESSION["securecode"]) && $_SESSION["securecode"] == strtolower($_POST["captcha"])) {
                $content_captcha = 'ok';
            } else {
                $content_captcha           = 'ko';
                $this->displayCaptchaError = true;
            }
        }

        // Connexion
        if (isset($_POST['login']) && isset($_POST['password'])) {
            $this->login     = $_POST['login'];
            $this->passsword = $_POST['password'];


            // SI on a le captcha d'actif, et qu'il est faux, on bloque avant tout pour ne pas laisser de piste sur le couple login/mdp
            if (isset($_POST["captcha"]) && $content_captcha == "ko") {
                //on trace la tentative
                $this->login_log              = $this->loadData('login_log');
                $this->login_log->pseudo      = $_POST['login'];
                $this->login_log->IP          = $_SERVER["REMOTE_ADDR"];
                $this->login_log->date_action = date('Y-m-d H:i:s');
                $this->login_log->statut      = 0;
                $this->login_log->retour      = 'erreur captcha';
                $this->login_log->create();

                $_SESSION['login']['displayCaptchaError'] = $this->displayCaptchaError;
            } else {
                $no_error = true;

                if ($_POST['login'] == '' || $_POST['password'] == '') {
                    $no_error = false;
                } elseif ($this->clients->exist($_POST['login'], 'email') == false) {
                    $no_error = false;
                } elseif ($this->clients->login($_POST['login'], $_POST['password']) == false) {
                    $no_error = false;
                }

                // Si erreur on affiche le message
                if ($no_error) {
                    // On recupere le formulaire de connexion s'il est passé
                    if ($this->clients->handleLogin('connect', 'login', 'password')) {
                        //vidage des trackeurs d'echec en session
                        unset($_SESSION['login']);

                        $this->clients_history->id_client = $_SESSION['client']['id_client'];
                        $this->clients_history->type      = $_SESSION['client']['status_pre_emp'];
                        $this->clients_history->status    = 1; // statut login
                        $this->clients_history->create();

                        // TODO @Antoine: on continue à loger le stauts_pret_empr dans la table clients history?
                        if (isset($_COOKIE['acceptCookies'])) {
                            $this->create_cookies = false;

                            if ($this->accept_cookies->get($_COOKIE['acceptCookies'], 'id_client = 0 AND id_accept_cookies')) {
                                $this->accept_cookies->id_client = $_SESSION['client']['id_client'];
                                $this->accept_cookies->update();
                            }
                        }

                        $this->bIsLender = $this->clients->isLender($this->lenders_accounts,$_SESSION['client']['id_client']);
                        $this->bIsBorrower = $this->clients->isBorrower($this->projects, $_SESSION['client']['id_client']);

                        if ($this->bIsLender === true) {
                            $this->loginLender();

                        } elseif ($this->bIsBorrower === true) {
                            $this->loginBorrower();

                        } else {
                            $this->error_login = $this->lng['header']['identifiant-ou-mot-de-passe-inccorect'];
                        }
                    } else {
                        /* A chaque tentative on double le temps d'attente entre 2 demande.

                        - tentative 2 = 1seconde d'attente
                        - tentative 3 = 2 sec
                        - tentative 4 = 4 sec
                        - etc...

                        Au bout de 10 demandes (avec la même IP) DANS LES 10 min
                        - Ajout d'un captcha + @ admin

                        */

                        // H - 10min
                        $h_moins_dix_min = date('Y-m-d H:i:s', mktime(date('H'), date('i') - 10, 0, date('m'), date('d'), date('Y')));

                        //on récupère le nombre de tentative déjà faite avec l'ip du user
                        $this->login_log                 = $this->loadData('login_log');
                        $this->nb_tentatives_precedentes = $this->login_log->counter('IP = "' . $_SERVER["REMOTE_ADDR"] . '" AND date_action >= "' . $h_moins_dix_min . '" AND statut = 0');

                        $this->duree_waiting = 0;

                        //parametrage de la boucle de temps
                        $coef_multiplicateur = 2;
                        $resultat_precedent  = 1;

                        if ($this->nb_tentatives_precedentes > 0 && $this->nb_tentatives_precedentes < 1000) // 1000 pour ne pas bloquer le site
                        {
                            for ($i = 1; $i <= $this->nb_tentatives_precedentes; $i++) {
                                $this->duree_waiting = $resultat_precedent * $coef_multiplicateur;
                                $resultat_precedent  = $this->duree_waiting;
                            }
                        }

                        // DEBUG
                        //$this->duree_waiting = 1;

                        //retour
                        $this->error_login = $this->lng['header']['identifiant-ou-mot-de-passe-inccorect'];

                        //mise en session
                        $_SESSION['login']['duree_waiting']             = $this->duree_waiting;
                        $_SESSION['login']['nb_tentatives_precedentes'] = $this->nb_tentatives_precedentes;
                        $_SESSION['login']['displayCaptchaError']       = $this->displayCaptchaError;


                        //on trace la tentative
                        $this->login_log              = $this->loadData('login_log');
                        $this->login_log->pseudo      = $_POST['login'];
                        $this->login_log->IP          = $_SERVER["REMOTE_ADDR"];
                        $this->login_log->date_action = date('Y-m-d H:i:s');
                        $this->login_log->statut      = 0;
                        $this->login_log->retour      = $this->error_login;
                        $this->login_log->create();
                    }
                }
            }
        }

        $this->connect_ok = false;
        if ($this->clients->checkAccess()) {

            $this->connect_ok = true;

            $this->clients->get($_SESSION['client']['id_client'], 'id_client');
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            $this->bIsLender = $this->clients->isLender($this->lenders_accounts, $_SESSION['client']['id_client']);
            $this->bIsBorrower = $this->clients->isBorrower($this->projects, $_SESSION['client']['id_client']);

            if ($this->bIsBorrower === true) {
                $this->getDataBorrower();
            }

            if ($this->bIsLender === true ) {
                $this->getDataLender();
            }
        }

        // page projet tri
        // 1 : terminé bientôt
        // 2 : nouveauté

        $this->tabOrdreProject = array(
            '',
            'lestatut ASC, IF(lestatut = 2, p.date_retrait ,"") DESC, IF(lestatut = 1, p.date_retrait ,"") ASC, projects_status.status DESC',
            'p.date_publication DESC'
        );

        // Afficher les projets terminés ? (1 : oui | 0 : non)
        $this->settings->get('Afficher les projets termines', 'type');
        if ($this->settings->value == 1) {
            $this->tabProjectDisplay = '50,60,70,80,90,100,110,130';
        } else {
            $this->tabProjectDisplay = '50';
        }

        $this->create_cookies = true;
        if (isset($_COOKIE['acceptCookies'])) {
            $this->create_cookies = false;
        }

        if ($this->lurl == 'http://prets-entreprises-unilend.capital.fr') {

            if ($command->Name == 'root' && $command->Function == 'capital') {
                //echo 'ok';
            } elseif ($command->Name == 'root' && $command->Function == 'default') {
                //echo 'ok';
            } elseif ($command->Name == 'projects' && $command->Function == 'detail') {
                //echo 'ok';
            } elseif ($command->Name == 'ajax') {
                //echo 'ok';
            } else {
                header('location: http://prets-entreprises-unilend.capital.fr/capital/');
                die;
            }

            //print_r($command);
        } elseif ($this->lurl == 'http://partenaire.unilend.challenges.fr') {

            if ($command->Name == 'root' && $command->Function == 'challenges') {
                //echo 'ok';
            } elseif ($command->Name == 'root' && $command->Function == 'default') {
                //echo 'ok';
            } elseif ($command->Name == 'projects' && $command->Function == 'detail') {
                //echo 'ok';
            } elseif ($command->Name == 'ajax') {
                //echo 'ok';
            } else {
                header('location: http://partenaire.unilend.challenges.fr/challenges/');
                die;
            }

            //print_r($command);
        } elseif ($this->lurl == 'http://lexpress.unilend.fr') {

            if ($command->Name == 'root' && $command->Function == 'lexpress') {
                //echo 'ok';
            } elseif ($command->Name == 'root' && $command->Function == 'default') {
                // voir dans root autres restrictions
            } else {

                header('location: ' . $this->surl);
                die;
            }
        } elseif ($this->lurl == 'http://pret-entreprise.votreargent.lexpress.fr') {

            if ($command->Name == 'root' && $command->Function == 'lexpress') {
                //echo 'ok';
            } elseif ($command->Name == 'root' && $command->Function == 'default') {
                // voir dans root autres restrictions
            } else {

                header('location: ' . $this->surl);
                die;
            }
        } elseif ($this->lurl == 'http://emprunt-entreprise.lentreprise.lexpress.fr') {

            if ($command->Name == 'root' && $command->Function == 'lexpress') {
                //echo 'ok';
            } elseif ($command->Name == 'root' && $command->Function == 'default') {
                // voir dans root autres restrictions
            } else {

                header('location: ' . $this->surl);
                die;
            }
        }
    }


    public function handlePartenaire($params)
    {
        // Chargement des datas
        $partenaires       = $this->loadData('partenaires');
        $promotions        = $this->loadData('promotions');
        $partenaires_clics = $this->loadData('partenaires_clics');

        // On check les params pour voir si on a un partenaire
        if (count($params) > 0) {

            // Variable pour savoir s'il a trouvé un p
            $getta = false;

            $i = 0;
            foreach ($params as $p) {

                // Si on detecte un p en params
                if ($p == 'p') {

                    // Youpi il a trouvé
                    $getta = true;

                    $indexPart = $i + 1;


                    // On regarde si on trouve un partenaire
                    if ($partenaires->get($params[ $indexPart ], 'hash')) {
                        // on controle qu'on a pas un double clique
                        if (!isset($_SESSION['partenaire_click'][ $partenaires->id_partenaire ]) || $_SESSION['partenaire_click'][ $partenaires->id_partenaire ] != $partenaires->id_partenaire) {
                            $_SESSION['partenaire_click'][ $partenaires->id_partenaire ] = $partenaires->id_partenaire;

                            // On ajoute un clic
                            if ($partenaires_clics->get(array('id_partenaire' => $partenaires->id_partenaire, 'date' => date('Y-m-d')))) {
                                $partenaires_clics->nb_clics = $partenaires_clics->nb_clics + 1;
                                $partenaires_clics->update(array('id_partenaire' => $partenaires->id_partenaire, 'date' => date('Y-m-d')));
                            } else {
                                $partenaires_clics->id_partenaire = $partenaires->id_partenaire;
                                $partenaires_clics->date          = date('Y-m-d');
                                $partenaires_clics->ip_adress     = $_SERVER['REMOTE_ADDR'];
                                $partenaires_clics->nb_clics      = 1;
                                $partenaires_clics->create(array('id_partenaire' => $partenaires->id_partenaire, 'date' => date('Y-m-d')));
                            }
                        }

                        // On met le partenaire en session
                        $_SESSION['partenaire']['id_partenaire'] = $partenaires->id_partenaire;

                        // On regarde si on a un code promo actif
                        if ($promotions->get($partenaires->id_code, 'id_code')) {
                            // On ajoute le code en session
                            $_SESSION['partenaire']['code_promo'] = $promotions->code;
                            $_SESSION['partenaire']['id_promo']   = $promotions->id_code;
                        } else {
                            unset($_SESSION['partenaire']['code_promo']);
                            unset($_SESSION['partenaire']['id_promo']);
                        }


                        // On enregistre le partenaire en cookie
                        setcookie('izicom_partenaire', $partenaires->hash, time() + 3153600, '/');

                        // On regarde si le dernier param commence par ?
                        if (substr($params[ count($params) - 1 ], 0, 1) == '?') {
                            $gogole = $params[ count($params) - 1 ];
                        }

                        // On rebidouille l'url
                        $params = array_slice($params, 0, count($params) - 3);
                        $reeurl = implode('/', $params);

                        // On renvoi
                        header('Location:' . $this->url . $reeurl . ($gogole != '' ? '/' . $gogole : ''));
                        die;
                    }
                }

                $i++;
            }

            // Si il a rien trouvé on regarde si on a un cookie et pas de session
            if (!isset($_SESSION['partenaire']['id_partenaire']) && isset($_COOKIE['izicom_partenaire']) && !$getta) {
                // On regarde si on trouve toujours un partenaire
                if ($partenaires->get($_COOKIE['izicom_partenaire'], 'hash')) {
                    // On met le partenaire en session
                    $_SESSION['partenaire']['id_partenaire'] = $partenaires->id_partenaire;
                }
            }
        }
    }


    private function loginLender()
    {

        /// creation de champs en bdd pour la gestion des mails de notifiaction ////

        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->clients_gestion_type_notif    = $this->loadData('clients_gestion_type_notif');

        ////// Liste des notifs //////
        $this->lTypeNotifs = $this->clients_gestion_type_notif->select();
        $this->lNotifs     = $this->clients_gestion_notifications->select('id_client = ' . $_SESSION['client']['id_client']);

        if ($this->lNotifs == false) {

            foreach ($this->lTypeNotifs as $n) {
                $this->clients_gestion_notifications->id_client = $_SESSION['client']['id_client'];
                $this->clients_gestion_notifications->id_notif  = $n['id_client_gestion_type_notif'];
                $id_notif                                       = $n['id_client_gestion_type_notif'];
                // immediatement
                if (in_array($id_notif, array(3, 6, 7, 8))) {
                    $this->clients_gestion_notifications->immediatement = 1;
                } else {
                    $this->clients_gestion_notifications->immediatement = 0;
                }
                // quotidienne
                if (in_array($id_notif, array(1, 2, 4, 5))) {
                    $this->clients_gestion_notifications->quotidienne = 1;
                } else {
                    $this->clients_gestion_notifications->quotidienne = 0;
                }
                // hebdomadaire
                if (in_array($id_notif, array(1, 4))) {
                    $this->clients_gestion_notifications->hebdomadaire = 1;
                } else {
                    $this->clients_gestion_notifications->hebdomadaire = 0;
                }
                // mensuelle
                $this->clients_gestion_notifications->mensuelle = 0;
                $this->clients_gestion_notifications->create();
            }
        }

        ////////////////////////////////////////////////////////////////////////////

        // Si on est en cours d'inscription on redirige sur le formulaire d'inscription
        if ($_SESSION['client']['etape_inscription_preteur'] < 3) {
            $etape = ($_SESSION['client']['etape_inscription_preteur'] + 1);
            header('Location:' . $this->lurl . '/inscription_preteur/etape' . $etape);
            die;

        } else {

            // on check le statut du preteur pour voir si on doit le rediriger sur la page des doc à uploader
            $this->clients_status->getLastStatut($_SESSION['client']['id_client']);
            if (in_array($this->clients_status->status, array(20, 30))) {

                if (in_array($_SESSION['client']['type'], array(1, 3))) {
                    $lapage = 'particulier_doc';
                } else {
                    $lapage = 'societe_doc';
                }
                header('Location:' . $this->lurl . '/profile/' . $lapage);
                die;

            } else {

                // On regarde le CGU du preteur pour voir si il est signé ou pas (cgv particulier et entreprise ont le mm contenu)
                $this->clients->get($_SESSION['client']['id_client'], 'id_client');
                // cgu societe
                if (in_array($this->clients->type, array(2, 4))) {
                    $this->settings->get('Lien conditions generales inscription preteur societe', 'type');
                    $this->lienConditionsGenerales = $this->settings->value;
                } // cgu particulier
                else {
                    $this->settings->get('Lien conditions generales inscription preteur particulier', 'type');
                    $this->lienConditionsGenerales = $this->settings->value;
                }

                // liste des cgv accepté
                $listeAccept = $this->acceptations_legal_docs->selectAccepts('id_client = ' . $this->clients->id_client);
                // On cherche si on a déjà le cgv (si pas signé on redirige sur la page synthèse pour qu'il signe)
                if (!in_array($this->lienConditionsGenerales, $listeAccept)) {
                    header('Location:' . $this->lurl . '/synthese');
                    die;

                } else {

                    // On va sur la session de redirection
                    if (isset($_SESSION['redirection_url']) &&
                        $_SESSION['redirection_url'] != '' &&
                        $_SESSION['redirection_url'] != 'login' &&
                        $_SESSION['redirection_url'] != 'captcha'
                    ) {

                        // on redirige que si on vient de projects
                        $redirect = explode('/', $_SESSION['redirection_url']);

                        if ($redirect[1] == 'projects') {
                            header('location:' . $_SESSION['redirection_url']);
                            die;
                        } else {
                            header('Location:' . $this->lurl . '/synthese');
                            die;
                        }

                    } else {
                        // Sinon page synthese si pas de session
                        header('Location:' . $this->lurl . '/synthese');
                        die;
                    }
                }

            }

        }
    }

    private function loginBorrower()
    {
        $this->settings->get('Lien conditions generales depot dossier', 'type');
        $this->cguDepotDossier = $this->settings->value;

        // Recuperation du contenu de la page
        $contenu = $this->tree_elements->select('id_tree = "' . $this->cguDepotDossier . '" AND id_langue = "' . $this->language . '"');
        foreach ($contenu as $elt) {
            $this->elements->get($elt['id_element']);
            $this->contentCGUDepotDossier[ $this->elements->slug ]    = $elt['value'];
            $this->complementCGUDepotDossier[ $this->elements->slug ] = $elt['complement'];
        }

        $aAllCompanyProjects = $this->companies->getProjectsForCompany($this->company->id_company);

        if ((int)$aAllCompanyProjects[0]['project_status'] >= projects_status::A_TRAITER && (int)$aAllCompanyProjects[0]['project_status'] <= projects_status::PREP_FUNDING) {
            header('Location:' . $this->url . 'depot_de_dossier/fichiers/' . $aAllCompanyProjects[0]['hash']);
            die;
        } else {
            header('Location:' . $this->lurl . '/espace_emprunteur');
            die;
        }
    }

    private function getDataLender()
    {
        // particulier
        if ($this->clients->type == 1) {
            // cgu particulier
            $this->settings->get('Lien conditions generales inscription preteur particulier', 'type');
            $this->lienConditionsGenerales = $this->settings->value;
        } // morale
        else {
            // cgu societe
            $this->settings->get('Lien conditions generales inscription preteur societe', 'type');
            $this->lienConditionsGenerales = $this->settings->value;
        }

        // Recuperation du contenu de la page
        $contenu = $this->tree_elements->select('id_tree = "' . $this->lienConditionsGenerales . '" AND id_langue = "' . $this->language . '"');
        foreach ($contenu as $elt) {
            $this->elements->get($elt['id_element']);
            $this->contentCGU[ $this->elements->slug ]    = $elt['value'];
            $this->complementCGU[ $this->elements->slug ] = $elt['complement'];
        }

        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $this->notifications    = $this->loadData('notifications');
        $this->bids             = $this->loadData('bids');
        $this->projects_notifs  = $this->loadData('projects');
        $this->companies_notifs = $this->loadData('companies');
        $this->loans            = $this->loadData('loans');

        $this->lng['preteur-synthese'] = $this->ln->selectFront('preteur-synthese', $this->language, $this->App);
        $this->lng['notifications']    = $this->ln->selectFront('preteur-notifications', $this->language, $this->App);


        $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

        $this->nbNotifdisplay      = 10;
        $this->lNotifHeader        = $this->notifications->select('id_lender = ' . $this->lenders_accounts->id_lender_account, 'added DESC', 0, $this->nbNotifdisplay);
        $this->NbNotifHeader       = $this->notifications->counter('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND status = 0');
        $this->NbNotifHeaderEnTout = $this->notifications->counter('id_lender = ' . $this->lenders_accounts->id_lender_account);


        // Solde du compte preteur
        $this->solde = $this->transactions->getSolde($this->clients->id_client);

    }

    private function getDataBorrower()
    {

        $this->companies->get($this->clients->id_client, 'id_client_owner');

        // Lien conditions generales depot dossier
        $this->settings->get('Lien conditions generales depot dossier', 'type');
        $this->cguDepotDossier = $this->settings->value;

//        // Recuperation du contenu de la page
//        $contenu = $this->tree_elements->select('id_tree = "' . $this->cguDepotDossier . '" AND id_langue = "' . $this->language . '"');
//        foreach ($contenu as $elt) {
//            $this->elements->get($elt['id_element']);
//            $this->contentCGUDepotDossier[ $this->elements->slug ]    = $elt['value'];
//            $this->complementCGUDepotDossier[ $this->elements->slug ] = $elt['complement'];
//        }

    }

}



