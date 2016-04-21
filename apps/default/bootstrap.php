<?php
use Unilend\Bundle\Memcache\Cache\MemcacheInterface;

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

    /**
     * @var array
     */
    public $aDataLayer = array();

    protected function initialize()
    {
        parent::initialize();

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
        $this->projects_status         = $this->loadData('projects_status');

        $this->ficelle = $this->loadLib('ficelle');
        $this->photos  = $this->loadLib('photos', array($this->spath, $this->surl));
        $this->tnmp    = $this->loadLib('tnmp', array($this->nmp, $this->nmp_desabo, $this->Config['env']));
        $this->dates   = $this->loadLib('dates');

        // Recuperation de la liste des langue disponibles
        $this->lLangues = $this->Config['multilanguage']['allowed_languages'];

        // Formulaire de modification d'un texte de traduction
        if (isset($_POST['form_mod_traduction'])) {
            foreach ($this->lLangues as $key => $lng) {
                $values[$key] = $_POST['texte-' . $key];
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

        // XSS protection
        if (false === empty($_POST)) {
            foreach ($_POST as $key => $value) {
                $_POST[$key] = htmlspecialchars(strip_tags($value));
            }
        }

        if (false === empty($_GET)) {
            foreach ($_GET as $key => $value) {
                $_GET[$key] = htmlspecialchars(strip_tags($value));
            }
        }

        $this->setSessionSource();

        $urlParams = explode('/', $_SERVER['REQUEST_URI']);
        $this->handlePartenaire($urlParams);

        $oCachePool  = $this->get('memcache.default');
        $oCachedItem = $oCachePool->getItem('Settings_GoogleTools_Analytics_BaseLine_FB_Twitter_Cookie');

        if (false === $oCachedItem->isHit()) {
            $this->settings->get('Google Webmaster Tools', 'type');
            $this->google_webmaster_tools = $this->settings->value;

            $this->settings->get('Google Analytics', 'type');
            $this->google_analytics = $this->settings->value;

            $this->settings->get('Baseline Title', 'type');
            $this->baseline_title = $this->settings->value;

            $this->settings->get('Facebook', 'type');
            $this->like_fb = $this->settings->value;

            $this->settings->get('Twitter', 'type');
            $this->twitter = $this->settings->value;

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
            $oCachedItem->set($aElements)
                ->expiresAfter(3600);
            $oCachePool->save($oCachedItem);
        } else {
            $aElements   = $oCachedItem->get();
            $this->google_webmaster_tools = $aElements['GoogleTools'];
            $this->google_analytics       = $aElements['GoogleAnalytics'];
            $this->baseline_title         = $aElements['BaselineTitle'];
            $this->like_fb                = $aElements['Facebook'];
            $this->twitter                = $aElements['Twitter'];
            $this->id_tree_cookies        = $aElements['TreeCookies'];
        }

        $this->menuFooter = $this->tree->getMenu('footer', $this->language, $this->lurl);
        $this->navFooter1 = $this->tree->getMenu('footer-nav-1', $this->language, $this->lurl);
        $this->navFooter2 = $this->tree->getMenu('footer-nav-2', $this->language, $this->lurl);
        $this->navFooter3 = $this->tree->getMenu('footer-nav-3', $this->language, $this->lurl);
        $this->navFooter4 = $this->tree->getMenu('footer-nav-4', $this->language, $this->lurl);

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
        $oCachedItem = $oCachePool->getItem('Blocs_Partenaires_' . $this->blocs->id_bloc . '_' . $this->language);
        if (false === $oCachedItem->isHit()) {
            $this->blocs->get('nos-partenaires', 'slug');
            $lElements = $this->blocs_elements->select('id_bloc = ' . $this->blocs->id_bloc . ' AND id_langue = "' . $this->language . '"');
            foreach ($lElements as $b_elt) {
                $this->elements->get($b_elt['id_element']);
                $this->bloc_partenaires[$this->elements->slug]           = $b_elt['value'];
                $this->bloc_partenairesComplement[$this->elements->slug] = $b_elt['complement'];
            }

            $aElements = array(
                'blocPartenaires'           => $this->bloc_partenaires,
                'blocPartenairesComplement' => $this->bloc_partenairesComplement
            );

            $oCachedItem->set($aElements)
                        ->expiresAfter(3600);
            $oCachePool->save($oCachedItem);
        } else {
            $aElements   = $oCachedItem->get();
        }

        $this->bloc_partenaires           = $aElements['blocPartenaires'];
        $this->bloc_partenairesComplement = $aElements['blocPartenairesComplement'];

        //Recuperation des element de traductions
        $oCachedItem = $oCachePool->getItem('Trad_Header_Footer_home');

        if (false === $oCachedItem->isHit()) {
            $aElements = array(
                'TradHeader' => $this->ln->selectFront('header', $this->language, $this->App),
                'TradFooter' => $this->ln->selectFront('footer', $this->language, $this->App),
                'TradHome'   => $this->ln->selectFront('home', $this->language, $this->App)
            );

            $oCachedItem->set($aElements)
                        ->expiresAfter(3600);
            $oCachePool->save($oCachedItem);
        } else {
            $aElements   = $oCachedItem->get();
        }

        $this->lng['header'] = $aElements['TradHeader'];
        $this->lng['footer'] = $aElements['TradFooter'];
        $this->lng['home']   = $aElements['TradHome'];


        //gestion du captcha
        if (isset($_POST["captcha"])) {
            if (isset($_SESSION["securecode"]) && $_SESSION["securecode"] == strtolower($_POST["captcha"])) {
                $bCaptchaOk = true;
            } else {
                $bCaptchaOk                = false;
                $this->displayCaptchaError = true;
            }
        }

        $this->bAccountClosed      = false;
        $bErrorLogin               = false;
        $this->displayCaptchaError = null;

        if (isset($_POST['login']) && isset($_POST['password'])) {
            $this->login     = $_POST['login'];
            $this->passsword = $_POST['password'];

            // SI on a le captcha d'actif, et qu'il est faux, on bloque avant tout pour ne pas laisser de piste sur le couple login/mdp
            if (isset($bCaptchaOk) && $bCaptchaOk === false) {
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
                if ($_POST['login'] == '' || $_POST['password'] == '') {
                    $bErrorLogin = true;
                } elseif ($this->clients->exist($_POST['login'], 'email') == false) {
                    $bErrorLogin = true;
                } elseif ($this->clients->login($_POST['login'], $_POST['password']) == false) {
                    $bErrorLogin = true;
                }

                if ($bErrorLogin === false) {
                    if ($this->clients->handleLogin('connect', 'login', 'password')) {
                        unset($_SESSION['login']);
                        $this->clients->get($_SESSION['client']['id_client'], 'id_client');

                        if (isset($_COOKIE['acceptCookies'])) {
                            $this->create_cookies = false;

                            if ($this->accept_cookies->get($_COOKIE['acceptCookies'], 'id_client = 0 AND id_accept_cookies')) {
                                $this->accept_cookies->id_client = $_SESSION['client']['id_client'];
                                $this->accept_cookies->update();
                            }
                        }
                        $this->clients_history->id_client = $_SESSION['client']['id_client'];
                        $this->clients_history->status    = 1; // statut login
                        $this->clients_history->create();

                        $this->bIsLender            = $this->clients->isLender();
                        $this->bIsBorrower          = $this->clients->isBorrower();
                        $this->bIsBorrowerAndLender = ($this->bIsBorrower && $this->bIsLender);

                        if ($this->bIsLender && false === $this->bIsBorrowerAndLender) {
                            $this->loginLender();
                        } elseif ($this->bIsBorrower && false === $this->bIsBorrowerAndLender) {
                            $this->loginBorrower();
                        } else {
                            header('location: ' . $this->surl);
                            die;
                        }
                    } else {
                        $this->error_login = $this->lng['header']['identifiant-ou-mot-de-passe-inccorect'];
                    }
                } elseif ($aOfflineClient = $this->clients->select('email = "' . $this->login . '" AND password = "' . md5($this->passsword) . '" AND status = 0')) {
                    $this->error_login = $this->lng['header']['message-login-compte-ferme'];
                    $this->bAccountClosed = true;
                } else {
                    $oDateTime           = new \datetime('NOW - 10 minutes');
                    $sNowMinusTenMinutes = $oDateTime->format('Y-m-d H:i:s');

                    $this->login_log      = $this->loadData('login_log');
                    $this->iPreviousTrys  = $this->login_log->counter('IP = "' . $_SERVER["REMOTE_ADDR"] . '" AND date_action >= "' . $sNowMinusTenMinutes . '" AND statut = 0');
                    $this->iWaitingPeriod = 0;
                    $iPreviousResult      = 1;

                    if ($this->iPreviousTrys > 0 && $this->iPreviousTrys < 1000) { // 1000 pour ne pas bloquer le site
                        for ($i = 1; $i <= $this->iPreviousTrys; $i++) {
                            $this->iWaitingPeriod = $iPreviousResult * 2;
                            $iPreviousResult      = $this->iWaitingPeriod;
                        }
                    }

                    $this->error_login = $this->lng['header']['identifiant-ou-mot-de-passe-inccorect'];

                    $_SESSION['login']['duree_waiting']             = $this->iWaitingPeriod;
                    $_SESSION['login']['nb_tentatives_precedentes'] = $this->iPreviousTrys;
                    $_SESSION['login']['displayCaptchaError']       = $this->displayCaptchaError;

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

        if ($this->clients->checkAccess()) {
            $this->clients->get($_SESSION['client']['id_client'], 'id_client');
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            $this->bIsLender                   = $this->clients->isLender();
            $this->bIsBorrower                 = $this->clients->isBorrower();
            $this->bIsBorrowerAndLender        = $this->bIsBorrower && $this->bIsLender;
            $this->bDisplayHeaderLender        = false;
            $this->bDisplayHeaderBorrower      = false;
            $this->bShowChoiceBorrowerOrLender = $this->bIsBorrowerAndLender;

            if ($this->bIsBorrowerAndLender) {
                $this->getDataBorrower();
                $this->getDataLender();

                if (in_array($this->Command->Name, array('espace_emprunteur', 'depot_de_dossier'))) {
                    $this->bDisplayHeaderBorrower      = true;
                    $this->bShowChoiceBorrowerOrLender = false;
                    $this->bDisplayHeaderLender        = false;
                } else {
                    $this->bDisplayHeaderBorrower      = false;
                    $this->bShowChoiceBorrowerOrLender = true;
                    $this->bDisplayHeaderLender        = true;
                }
            } elseif ($this->bIsBorrower) {
                $this->getDataBorrower();

                $this->bDisplayHeaderBorrower      = true;
                $this->bShowChoiceBorrowerOrLender = false;
                $this->bDisplayHeaderLender        = false;
            } elseif ($this->bIsLender) {
                $this->getDataLender();

                $this->bDisplayHeaderLender        = true;
                $this->bShowChoiceBorrowerOrLender = false;
                $this->bDisplayHeaderBorrower      = false;
            }
        }
        $this->setSessionMail();

        false === isset($_SESSION['email']) || $_SESSION['email'] == '' ? $this->addDataLayer('unique_id', '') : $this->addDataLayer('unique_id', md5($_SESSION['email']));

        // page projet tri
        // 1 : terminé bientôt
        // 2 : nouveauté
        $this->tabOrdreProject = array(
            '',
            'lestatut ASC, IF(lestatut = 2, p.date_retrait_full ,"") DESC, IF(lestatut = 1, p.date_retrait_full ,"") ASC, projects_status.status DESC',
            'p.date_publication DESC'
        );

        // Afficher les projets terminés ? (1 : oui | 0 : non)
        $this->settings->get('Afficher les projets termines', 'type');
        if ($this->settings->value == 1) {
            $this->tabProjectDisplay = implode(', ', array(\projects_status::EN_FUNDING, \projects_status::FUNDE, \projects_status::FUNDING_KO, \projects_status::REMBOURSEMENT, \projects_status::REMBOURSE, \projects_status::PROBLEME, \projects_status::RECOUVREMENT, \projects_status::DEFAUT, \projects_status::REMBOURSEMENT_ANTICIPE, \projects_status::PROBLEME_J_X, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE));
        } else {
            $this->tabProjectDisplay = \projects_status::EN_FUNDING;
        }

        $this->create_cookies = true;
        if (isset($_COOKIE['acceptCookies'])) {
            $this->create_cookies = false;
        }

        if ($this->lurl == 'http://prets-entreprises-unilend.capital.fr') {

            if ($this->Command->Name == 'root' && $this->Command->Function == 'capital') {
                //echo 'ok';
            } elseif ($this->Command->Name == 'root' && $this->Command->Function == 'default') {
                //echo 'ok';
            } elseif ($this->Command->Name == 'projects' && $this->Command->Function == 'detail') {
                //echo 'ok';
            } elseif ($this->Command->Name == 'ajax') {
                //echo 'ok';
            } else {
                header('location: http://prets-entreprises-unilend.capital.fr/capital/');
                die;
            }

            //print_r($this->Command);
        } elseif ($this->lurl == 'http://partenaire.unilend.challenges.fr') {

            if ($this->Command->Name == 'root' && $this->Command->Function == 'challenges') {
                //echo 'ok';
            } elseif ($this->Command->Name == 'root' && $this->Command->Function == 'default') {
                //echo 'ok';
            } elseif ($this->Command->Name == 'projects' && $this->Command->Function == 'detail') {
                //echo 'ok';
            } elseif ($this->Command->Name == 'ajax') {
                //echo 'ok';
            } else {
                header('location: http://partenaire.unilend.challenges.fr/challenges/');
                die;
            }

            //print_r($this->Command);
        } elseif ($this->lurl == 'http://lexpress.unilend.fr') {

            if ($this->Command->Name == 'root' && $this->Command->Function == 'lexpress') {
                //echo 'ok';
            } elseif ($this->Command->Name == 'root' && $this->Command->Function == 'default') {
                // voir dans root autres restrictions
            } else {

                header('location: ' . $this->surl);
                die;
            }
        } elseif ($this->lurl == 'http://pret-entreprise.votreargent.lexpress.fr') {

            if ($this->Command->Name == 'root' && $this->Command->Function == 'lexpress') {
                //echo 'ok';
            } elseif ($this->Command->Name == 'root' && $this->Command->Function == 'default') {
                // voir dans root autres restrictions
            } else {

                header('location: ' . $this->surl);
                die;
            }
        } elseif ($this->lurl == 'http://emprunt-entreprise.lentreprise.lexpress.fr') {

            if ($this->Command->Name == 'root' && $this->Command->Function == 'lexpress') {
                //echo 'ok';
            } elseif ($this->Command->Name == 'root' && $this->Command->Function == 'default') {
                // voir dans root autres restrictions
            } else {

                header('location: ' . $this->surl);
                die;
            }
        }
    }

    public function setDatabase()
    {
        $this->bdd = $this->get('unilend.dbal.default_connection');
    }
    public function execute()
    {
        parent::execute();
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
        $this->bDisplayHeaderLender = true;

        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->clients_gestion_type_notif    = $this->loadData('clients_gestion_type_notif');

        $this->lTypeNotifs = $this->clients_gestion_type_notif->select();
        $this->lNotifs     = $this->clients_gestion_notifications->select('id_client = ' . $_SESSION['client']['id_client']);

        if ($this->lNotifs == false) {
            foreach ($this->lTypeNotifs as $n) {
                $this->clients_gestion_notifications->id_client = $_SESSION['client']['id_client'];
                $this->clients_gestion_notifications->id_notif  = $n['id_client_gestion_type_notif'];
                $id_notif                                       = $n['id_client_gestion_type_notif'];

                if (
                    in_array(
                        $id_notif,
                        array(
                            \clients_gestion_type_notif::TYPE_BID_REJECTED,
                            \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT,
                            \clients_gestion_type_notif::TYPE_CREDIT_CARD_CREDIT,
                            \clients_gestion_type_notif::TYPE_DEBIT
                        )
                    )
                ) {
                    $this->clients_gestion_notifications->immediatement = 1;
                } else {
                    $this->clients_gestion_notifications->immediatement = 0;
                }

                if (
                    in_array(
                        $id_notif,
                        array(
                            \clients_gestion_type_notif::TYPE_NEW_PROJECT,
                            \clients_gestion_type_notif::TYPE_BID_PLACED,
                            \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED,
                            \clients_gestion_type_notif::TYPE_REPAYMENT
                        )
                    )
                ) {
                    $this->clients_gestion_notifications->quotidienne = 1;
                } else {
                    $this->clients_gestion_notifications->quotidienne = 0;
                }

                if (
                    in_array(
                        $id_notif,
                        array(
                            \clients_gestion_type_notif::TYPE_NEW_PROJECT,
                            \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED
                        )
                    )
                ) {
                    $this->clients_gestion_notifications->hebdomadaire = 1;
                } else {
                    $this->clients_gestion_notifications->hebdomadaire = 0;
                }

                $this->clients_gestion_notifications->mensuelle = 0;
                $this->clients_gestion_notifications->create();
            }
        }

        if ($_SESSION['client']['etape_inscription_preteur'] < 3) {
            $etape = ($_SESSION['client']['etape_inscription_preteur'] + 1);
            header('Location:' . $this->lurl . '/inscription_preteur/etape' . $etape);
            die;
        } else {
            $this->clients_status->getLastStatut($_SESSION['client']['id_client']);
            if (in_array($this->clients_status->status, array(clients_status::COMPLETENESS, clients_status::COMPLETENESS_REMINDER))) {

                if (in_array($_SESSION['client']['type'], array(clients::TYPE_PERSON, clients::TYPE_PERSON_FOREIGNER))) {
                    $lapage = 'particulier_doc';
                } else {
                    $lapage = 'societe_doc';
                }
                header('Location:' . $this->lurl . '/profile/' . $lapage);
                die;
            } else {

                $this->clients->get($_SESSION['client']['id_client'], 'id_client');
                if (in_array($this->clients->type, array(clients::TYPE_LEGAL_ENTITY, clients::TYPE_LEGAL_ENTITY_FOREIGNER))) {
                    $this->settings->get('Lien conditions generales inscription preteur societe', 'type');
                    $this->lienConditionsGenerales = $this->settings->value;
                }
                else {
                    $this->settings->get('Lien conditions generales inscription preteur particulier', 'type');
                    $this->lienConditionsGenerales = $this->settings->value;
                }

                $listeAccept = $this->acceptations_legal_docs->selectAccepts('id_client = ' . $this->clients->id_client);

                if (!in_array($this->lienConditionsGenerales, $listeAccept)) {
                    header('Location:' . $this->lurl . '/synthese');
                    die;
                } else {
                    if (isset($_SESSION['redirection_url']) &&
                        $_SESSION['redirection_url'] != '' &&
                        $_SESSION['redirection_url'] != 'login' &&
                        $_SESSION['redirection_url'] != 'captcha'
                    ) {
                        $redirect = explode('/', $_SESSION['redirection_url']);

                        if ($redirect[1] == 'projects') {
                            header('Location:' . $_SESSION['redirection_url']);
                            die;
                        } else {
                            header('Location:' . $this->lurl . '/synthese');
                            die;
                        }
                    } else {
                        header('Location:' . $this->lurl . '/synthese');
                        die;
                    }
                }
            }
        }
    }

    private function loginBorrower()
    {
        $this->bDisplayHeaderBorrower = true;
        $this->companies->get($_SESSION['client']['id_client'], 'id_client_owner');

        $aAllCompanyProjects = $this->companies->getProjectsForCompany($this->companies->id_company);

        if ((int)$aAllCompanyProjects[0]['project_status'] >= projects_status::A_TRAITER && (int)$aAllCompanyProjects[0]['project_status'] < projects_status::PREP_FUNDING) {
            header('Location:' . $this->url . '/depot_de_dossier/fichiers/' . $aAllCompanyProjects[0]['hash']);
            die;
        } else {
            header('Location:' . $this->lurl . '/espace_emprunteur');
            die;
        }
    }

    private function getDataLender()
    {
        if ($this->clients->type == clients::TYPE_PERSON) {
            $this->settings->get('Lien conditions generales inscription preteur particulier', 'type');
            $this->lienConditionsGenerales = $this->settings->value;
        } else {
            $this->settings->get('Lien conditions generales inscription preteur societe', 'type');
            $this->lienConditionsGenerales = $this->settings->value;
        }

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

        $this->solde = $this->transactions->getSolde($this->clients->id_client);
    }

    private function getDataBorrower()
    {
        $this->oCompanyDisplay = $this->loadData('companies');
        $this->oCompanyDisplay->get($this->clients->id_client, 'id_client_owner');
    }

    /**
     * @param string $sKey DataLayer parameter name
     * @param mixed $mValue Parameter value
     */
    protected function addDataLayer($sKey, $mValue)
    {
        $this->aDataLayer[$sKey] = $mValue;
    }

    /**
     * Set the source details in session
     */
    private function setSessionSource()
    {
        $aAvailableUtm = $this->getUTM();

        if (false === empty($aAvailableUtm)) {
            $_SESSION['source']                 = $aAvailableUtm;
            $_SESSION['source']['slug_origine'] = $this->getSlug();
        } elseif (true === empty($_SESSION['source'])) {
            $_SESSION['source'] = array(
                'utm_source'   => 'Directe',
                'slug_origine' => $this->getSlug()
            );
        }
    }

    /**
     * This looks for UTMs in GET and POST parameters and returns them
     * @return array
     */
    private function getUTM()
    {
        $aUTM = array();
        if (false === empty($_POST)) {
            foreach ($_POST as $mKey => $mValue) {
                if ('utm_' === strtolower(substr($mKey, 0, 4))) {
                    $aUTM[$mKey] = $this->filterPost($mKey);
                }
            }
        } elseif (false === empty($_GET)) {
            foreach ($_GET as $mKey => $mValue) {
                if ('utm_' === strtolower(substr($mKey, 0, 4))) {
                    $aUTM[$mKey] = $this->filterGet($mKey);
                }
            }
        }
        return $aUTM;
    }

    /**
     * @return string
     */
    private function getSlug()
    {
        if (false === empty($_POST['slug_origine'])) {
            $sSlugOrigine = $this->filterPost('slug_origine');
        } elseif (false === empty($_GET['slug_origine'])) {
            $sSlugOrigine = $this->filterGet('slug_origine');
        } elseif (false === empty($this->tree->slug)) {
            $sSlugOrigine = trim($this->tree->slug);
        } else {
            $sSlugOrigine = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            if ('/' === $sSlugOrigine) {
                $sSlugOrigine = '';
            }
        }
        return $sSlugOrigine;
    }

    /**
     * Filter and sanitize POST field
     * @param string $sFieldName
     * @param int $iFilter
     * @return string
     */
    protected function filterPost($sFieldName, $iFilter = FILTER_SANITIZE_STRING)
    {
        if (false !== ($mValue = filter_input(INPUT_POST, $sFieldName, $iFilter))) {
            return trim($mValue);
        }
        return '';
    }

    /**
     * Filter and sanitize GET field
     * @param string $sFieldName
     * @param int $iFilter
     * @return string
     */
    protected function filterGet($sFieldName, $iFilter = FILTER_SANITIZE_STRING)
    {
        if (false !== ($mValue = filter_input(INPUT_GET, $sFieldName, $iFilter))) {
            return trim($mValue);
        }
        return '';
    }


    /**
     * Set the source keys of the given object : UTMs + slug_origine
     * @param \clients|\prospects $oClient object
     */
    protected function setSource(&$oClient)
    {
        $aSourceColumn = array(
            'source'       => 'utm_source',
            'source2'      => 'utm_source2',
            'source3'      => 'utm_campaign',
            'slug_origine' => 'slug_origine'
        );

        foreach ($aSourceColumn as $sObjectField => $sUtmKey) {
            if (true === isset($_SESSION['source'][$sUtmKey])) {
                $oClient->$sObjectField = $_SESSION['source'][$sUtmKey];
            }
        }
    }


    /**
     * This looks for email address in SESSION, GET and POST parameters then add it to SESSION
     */
    private function setSessionMail()
    {
        if (isset($this->clients->email) && false === empty($this->clients->email)) {
            $_SESSION['email'] = $this->clients->email;
        } elseif (false === empty($_POST['email']) && $this->ficelle->isEmail($_POST['email'])) {
            $_SESSION['email'] = $_POST['email'];
        } elseif (false === empty($_GET['email']) && $this->ficelle->isEmail($_GET['email'])) {
            $_SESSION['email'] = $_GET['email'];
        }
    }
}
