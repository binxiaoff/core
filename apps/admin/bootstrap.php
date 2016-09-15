<?php

class bootstrap extends Controller
{
    /** @var \dates */
    protected $dates;
    /** @var \ficelle */
    protected $ficelle;
    /** @var \upload */
    protected $upload;
    /** @var \photos */
    protected $photos;
    /** @var \translations */
    protected $ln;
    /** @var \settings */
    protected $settings;
    /** @var \tree_elements */
    protected $tree_elements;
    /** @var \blocs */
    protected $blocs;
    /** @var \blocs_elements */
    protected $blocs_elements;
    /** @var \elements */
    protected $elements;
    /** @var \tree */
    protected $tree;
    /** @var \users */
    protected $users;
    /** @var \users_zones */
    protected $users_zones;
    /** @var \users_history */
    protected $users_history;
    /** @var \mail_templates */
    protected $mail_template;
    /** @var \projects */
    protected $projects;
    /** @var \clients */
    protected $clients;
    /** @var \companies */
    protected $companies;
    /** @var \bids */
    protected $bids;
    /** @var \loans */
    protected $loans;
    /** @var \lenders_accounts */
    protected $lenders_accounts;
    /** @var \echeanciers */
    protected $echeanciers;

    public function initialize()
    {
        parent::initialize();

        if ($this->current_function != 'login') {
            $_SESSION['request_url'] = $_SERVER['REQUEST_URI'];
        }

        $this->dates   = $this->loadLib('dates');
        $this->ficelle = $this->loadLib('ficelle');
        $this->upload  = $this->loadLib('upload');
        $this->photos  = $this->loadLib('photos', array($this->spath, $this->surl));

        $this->ln               = $this->loadData('translations');
        $this->settings         = $this->loadData('settings');
        $this->tree_elements    = $this->loadData('tree_elements');
        $this->blocs            = $this->loadData('blocs');
        $this->blocs_elements   = $this->loadData('blocs_elements');
        $this->elements         = $this->loadData('elements');
        $this->tree             = $this->loadData('tree', array('url' => $this->lurl, 'front' => $this->furl, 'surl' => $this->surl, 'tree_elements' => $this->tree_elements, 'blocs_elements' => $this->blocs_elements, 'upload' => $this->upload, 'spath' => $this->spath, 'path' => $this->path));
        $this->users            = $this->loadData('users', array('config' => $this->Config, 'lurl' => $this->lurl));
        $this->users_zones      = $this->loadData('users_zones');
        $this->users_history    = $this->loadData('users_history');
        $this->mail_template    = $this->loadData('mail_templates');

        if (isset($_POST['captcha'])) {
            if (isset($_SESSION['captcha']) && $_SESSION['captcha'] == $_POST['captcha']) {
                $content_captcha = 'ok';
            } else {
                $content_captcha           = 'ko';
                $this->displayCaptchaError = 'Captcha incorrecte';
            }
        }

        if (!empty($_POST['connect']) && !empty($_POST['password'])) {
            if (isset($_POST['captcha']) && $content_captcha == 'ko') {
                $_SESSION['login_user']['displayCaptchaError'] = $this->displayCaptchaError;
            } else {
                $this->loggin_connection_admin = $this->loadData('loggin_connection_admin');
                $user                          = $this->users->login($_POST['login'], $_POST['password']);

                if ($user != false) {
                    $this->loggin_connection_admin->id_user        = $user['id_user'];
                    $this->loggin_connection_admin->nom_user       = $user['firstname'] . ' ' . $user['name'];
                    $this->loggin_connection_admin->email          = $user['email'];
                    $this->loggin_connection_admin->date_connexion = date('Y-m-d H:i:s');
                    $this->loggin_connection_admin->ip             = $_SERVER['REMOTE_ADDR'];

                    if (function_exists('geoip_country_code_by_name')) {
                        $country_code = strtolower(geoip_country_code_by_name($_SERVER['REMOTE_ADDR']));
                    } else {
                        $country_code = 'fr';
                    }

                    $this->loggin_connection_admin->pays = $country_code;
                    $this->loggin_connection_admin->create();

                    unset($_SESSION['login_user']);

                    $this->users->handleLogin('connect', 'login', 'password');
                    die;
                } else {
                    /*
                     * À chaque tentative on double le temps d'attente entre 2 demandes
                     * - tentative 2 = 2 secondes d'attente
                     * - tentative 3 = 4 secondes
                     * - tentative 4 = 8 secondes
                     * - etc...
                     *
                     * Au bout de 10 demandes (avec la même IP) DANS LES 10min
                     * - Ajout d'un captcha + @ admin
                     */

                    // H - 10min
                    $this->duree_waiting             = 0;
                    $coef_multiplicateur             = 2;
                    $resultat_precedent              = 1;
                    $h_moins_dix_min                 = date('Y-m-d H:i:s', mktime(date('H'), date('i') - 10, 0, date('m'), date('d'), date('Y')));
                    $this->nb_tentatives_precedentes = $this->loggin_connection_admin->counter('ip = "' . $_SERVER["REMOTE_ADDR"] . '" AND date_connexion >= "' . $h_moins_dix_min . '" AND id_user = 0');

                    if ($this->nb_tentatives_precedentes > 0 && $this->nb_tentatives_precedentes < 1000) { // 1000 pour ne pas bloquer le site
                        for ($i = 1; $i <= $this->nb_tentatives_precedentes; $i++) {
                            $this->duree_waiting = $resultat_precedent * $coef_multiplicateur;
                            $resultat_precedent  = $this->duree_waiting;
                        }
                    }

                    // DEBUG
                    //$this->duree_waiting = 1;

                    $this->error_login = "Le couple d'identifiant n'est pas correct";

                    $_SESSION['login_user']['duree_waiting']             = $this->duree_waiting;
                    $_SESSION['login_user']['nb_tentatives_precedentes'] = $this->nb_tentatives_precedentes;
                    $_SESSION['login_user']['displayCaptchaError']       = (isset($this->displayCaptchaError)) ? $this->displayCaptchaError : '';

                    $this->loggin_connection_admin        = $this->loadData('loggin_connection_admin');
                    $this->loggin_connection_admin->email = $_POST['login'];
                    $this->loggin_connection_admin->ip    = $_SERVER['REMOTE_ADDR'];

                    if (function_exists('geoip_country_code_by_name')) {
                        $country_code = strtolower(geoip_country_code_by_name($_SERVER['REMOTE_ADDR']));
                    } else {
                        $country_code = 'fr';
                    }
                    $this->loggin_connection_admin->pays           = $country_code;
                    $this->loggin_connection_admin->date_connexion = date('Y-m-d H:i:s');
                    $this->loggin_connection_admin->statut         = 1;
                    $this->loggin_connection_admin->create();
                }
            }
        }

        $this->loadJs('admin/external/jquery/jquery');
        $this->loadJs('admin/external/jquery/plugin/jquery-ui/jquery-ui.min');
        $this->loadJs('admin/external/jquery/plugin/jquery-ui/jquery-ui.datepicker-fr');
        $this->loadJs('admin/freeow/jquery.freeow.min');
        $this->loadJs('admin/external/jquery/plugin/colorbox/jquery.colorbox-min');
        $this->loadJs('admin/treeview/jquery.treeview');
        $this->loadJs('admin/treeview/jquery.cookie');
        $this->loadJs('admin/treeview/tree');
        $this->loadJs('admin/tablesorter/jquery.tablesorter.min');
        $this->loadJs('admin/tablesorter/jquery.tablesorter.pager');
        $this->loadJs('admin/ajax');
        $this->loadJs('admin/main');

        $this->loadCss('../scripts/admin/freeow/freeow');
        $this->loadCss('../scripts/admin/external/jquery/plugin/colorbox/colorbox');
        $this->loadCss('../scripts/admin/treeview/jquery.treeview');
        $this->loadCss('../scripts/admin/tablesorter/style');
        $this->loadCss('../scripts/admin/external/jquery/plugin/jquery-ui/jquery-ui.min');
        $this->loadCss('admin/main');

        // Recuperation du code Google Analytics
        $this->settings->get('Google Analytics', 'type');
        $this->google_analytics = $this->settings->value;

        // Recuperation du mail du compte Google Analytics
        $this->settings->get('Google Mail', 'type');
        $this->google_mail = $this->settings->value;

        // Recuperation du password cu compte Google Analytics
        $this->settings->get('Google Password', 'type');
        $this->google_password = $this->settings->value;

        // Recuperation du paging des tableaux
        $this->settings->get('Paging Tableaux', 'type');
        $this->nb_lignes = $this->settings->value;

        // Recuperation de la liste des langues disponibles
        $this->lLangues = $this->Config['multilanguage']['allowed_languages'];

        // Recuperation de la langue par défaut
        $array           = array_keys($this->Config['multilanguage']['allowed_languages']);
        $this->dLanguage = $array[0];

        if (isset($_SESSION['user']) && !empty($_SESSION['user']['id_user'])) {
            $this->sessionIdUser = $_SESSION['user']['id_user'];
            $this->lZonesHeader  = $this->users_zones->selectZonesUser($_SESSION['user']['id_user']);
        }

        // On vérifie ici si le mot de passe du user date de moins de 3 mois sinon on le redirige sur la page d'édition de mot de passe
        if (
            $this->current_function != 'edit_password'
            && $this->current_function != 'login'
            && $this->current_function != 'logout'
            && $this->current_controller != 'thickbox'
            && $this->current_controller != 'ajax'
            && false === empty($_SESSION['user']['id_user'])
        ) {
            $ilya3mois             = mktime(0, 0, 0, date('m') - 3, date('d'), date('Y'));
            $tab_date_pass         = explode(' ', $_SESSION['user']['password_edited']);
            $date_pass_edited      = $tab_date_pass[0];
            $tab_date_pass2        = explode('-', $date_pass_edited);
            $derniere_edition_pass = mktime(0, 0, 0, $tab_date_pass2[1], $tab_date_pass2[2], $tab_date_pass2[0]);

            if ($derniere_edition_pass < $ilya3mois) {
                $_SESSION['freeow']['title']   = 'Modification de votre mot de passe';
                $_SESSION['freeow']['message'] = 'Votre mot de passe doit &ecirc;tre mis &agrave; jour afin de conserver un niveau de s&eacute;curit&eacute; optimal!';

                header('Location:' . $this->lurl . '/edit_password/');
                die;
            }
        }
    }
}
