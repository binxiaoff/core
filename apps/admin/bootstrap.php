<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\UserAccess;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;

class bootstrap extends Controller
{
    const MENU = [
        [
            'title'    => 'Dashboard',
            'uri'      => 'dashboard',
            'zone'     => 'dashboard',
            'children' => []
        ],
        [
            'title'    => 'Edition',
            'uri'      => 'tree',
            'zone'     => 'edition',
            'children' => [
                [
                    'title' => 'Arborescence',
                    'uri'   => 'tree',
                ],
                [
                    'title' => 'Blocs',
                    'uri'   => 'blocs'
                ],
                [
                    'title' => 'Menus',
                    'uri'   => 'menus'
                ],
                [
                    'title' => 'Templates',
                    'uri'   => 'templates'
                ],
                [
                    'title' => 'Traductions',
                    'uri'   => 'traductions'
                ],
                [
                    'title' => 'Mails',
                    'uri'   => 'mails'
                ]
            ]
        ],
        [
            'title'    => 'Configuration',
            'uri'      => 'settings',
            'zone'     => 'configuration',
            'children' => [
                [
                    'title' => 'Paramètres',
                    'uri'   => 'settings',
                ],
                [
                    'title' => 'Historique des Mails',
                    'uri'   => 'mails/emailhistory'
                ],
                [
                    'title' => 'Campagnes',
                    'uri'   => 'partenaires'
                ],
                [
                    'title' => 'Types de campagnes',
                    'uri'   => 'partenaires/types'
                ],
                [
                    'title' => 'Medias de campagnes',
                    'uri'   => 'partenaires/medias'
                ],
                [
                    'title' => 'Grille de taux',
                    'uri'   => 'project_rate_settings',
                    'zone'  => 'admin'
                ]
            ]
        ],
        [
            'title'    => 'Statistiques',
            'uri'      => 'queries',
            'zone'     => 'stats',
            'children' => [
                [
                    'title' => 'Requêtes',
                    'uri'   => 'queries',
                ],
                [
                    'title' => 'Etape d\'inscription',
                    'uri'   => 'stats/etape_inscription'
                ],
                [
                    'title' => 'Sources emprunteurs',
                    'uri'   => 'stats/requete_source_emprunteurs'
                ],
                [
                    'title' => 'Revenus',
                    'uri'   => 'stats/requete_revenus_download'
                ],
                [
                    'title' => 'Bénéficiaires',
                    'uri'   => 'stats/requete_beneficiaires_csv'
                ],
                [
                    'title' => 'Infosben',
                    'uri'   => 'stats/requete_infosben'
                ],
                [
                    'title' => 'Toutes les enchères',
                    'uri'   => 'stats/requete_encheres'
                ],
                [
                    'title' => 'Echeanciers projet',
                    'uri'   => 'stats/tous_echeanciers_pour_projet'
                ],
                [
                    'title' => 'Statistiques Autolend',
                    'uri'   => 'stats/autobid_statistic'
                ],
                [
                    'title' => 'Déclarations BDF',
                    'uri'   => 'stats/declarations_bdf'
                ],
                [
                    'title' => 'CRS CAC',
                    'uri'   => 'stats/requete_crs_cac'
                ],
                [
                    'title' => 'Extraction des évaluations d\'éligibilité des dossiers',
                    'uri'   => 'stats/extraction_b_lend'
                ],
                [
                    'title' => 'Logs webservices',
                    'uri'   => 'stats/logs_webservices'
                ]
            ]
        ],
        [
            'title'    => 'Prêteurs',
            'uri'      => 'preteurs/search',
            'zone'     => 'preteurs',
            'children' => [
                [
                    'title' => 'Recherche prêteurs',
                    'uri'   => 'preteurs/search'
                ],
                [
                    'title' => 'Activation prêteurs',
                    'uri'   => 'preteurs/activation '
                ],
                [
                    'title' => 'Offre de bienvenue',
                    'uri'   => 'preteurs/offres_de_bienvenue'
                ],
                [
                    'title' => 'Matching ville fiscale',
                    'uri'   => 'preteurs/control_fiscal_city'
                ],
                [
                    'title' => 'Matching ville de naissance',
                    'uri'   => 'preteurs/control_birth_city'
                ],
                [
                    'title' => 'Notifications',
                    'uri'   => 'preteurs/notifications'
                ]
            ]
        ],
        [
            'title'    => 'Emprunteurs',
            'zone'     => 'emprunteurs',
            'children' => [
                [
                    'title' => 'Dossiers',
                    'uri'   => 'dossiers',
                ],
                [
                    'title' => 'Emprunteurs',
                    'uri'   => 'emprunteurs/gestion'
                ],
                [
                    'title' => 'Prescripteurs',
                    'uri'   => 'prescripteurs/gestion'
                ],
                [
                    'title' => 'Dossiers en funding',
                    'uri'   => 'dossiers/funding '
                ],
                [
                    'title' => 'Remboursements',
                    'uri'   => 'dossiers/remboursements'
                ],
                [
                    'title' => 'Erreurs remboursements',
                    'uri'   => 'dossiers/no_remb'
                ],
                [
                    'title' => 'Suivi statuts projets',
                    'uri'   => 'dossiers/status'
                ],
                [
                    'title' => 'Erreurs remboursements',
                    'uri'   => 'dossiers/no_remb'
                ],
                [
                    'title' => 'Produits',
                    'uri'   => 'product'
                ],
                [
                    'title' => 'Sociétés',
                    'uri'   => 'company'
                ],
                [
                    'title' => 'Partenaires',
                    'uri'   => 'partner'
                ],
                [
                    'title' => 'Monitoring données risque',
                    'uri'   => 'risk_monitoring'
                ]
            ]
        ],
        [
            'title'    => 'Dépôt de fonds',
            'uri'      => 'transferts',
            'zone'     => 'transferts',
            'children' => [
                [
                    'title' => 'Prêteurs',
                    'uri'   => 'transferts/preteurs',
                ],
                [
                    'title' => 'Emprunteurs',
                    'uri'   => 'transferts/emprunteurs'
                ],
                [
                    'title' => 'Non attribués',
                    'uri'   => 'transferts/non_attribues'
                ],
                [
                    'title' => 'Rattrapage offre de bienvenue',
                    'uri'   => 'transferts/rattrapage_offre_bienvenue'
                ],
                [
                    'title' => 'Déblocage des fonds',
                    'uri'   => 'transferts/deblocage'
                ],
                [
                    'title' => 'Succession (Transfert de solde et prêts)',
                    'uri'   => 'transferts/succession'
                ],
                [
                    'title' => 'Opérations atypiques',
                    'uri'   => 'client_atypical_operation'
                ],
                [
                    'title' => 'Transfert des fonds',
                    'uri'   => 'transferts/virement_emprunteur'
                ]
            ]
        ],
        [
            'title'    => 'Administration',
            'uri'      => 'admin',
            'zone'     => 'admin',
            'children' => [
                [
                    'title' => 'Utilisateurs',
                    'uri'   => 'users',
                ],
                [
                    'title' => 'Droits d\'accès',
                    'uri'   => 'zones'
                ],
                [
                    'title' => 'Logs connexions',
                    'uri'   => 'users/logs'
                ]
            ]
        ],
        [
            'title'    => 'SFPMEI',
            'uri'      => 'sfpmei',
            'zone'     => 'sfpmei',
            'children' => [
                [
                    'title' => 'Prêteurs',
                    'uri'   => 'sfpmei/preteurs',
                ],
                [
                    'title' => 'Emprunteurs',
                    'uri'   => 'sfpmei/emprunteurs',
                ],
                [
                    'title' => 'Projets',
                    'uri'   => 'sfpmei/projets',
                ],
                [
                    'title' => 'Transferts de fonds prêteurs',
                    'uri'   => 'sfpmei/transferts/preteurs',
                ],
                [
                    'title' => 'Transferts de fonds prêteurs',
                    'uri'   => 'sfpmei/transferts/emprunteurs',
                ],
                [
                    'title' => 'Requêtes',
                    'uri'   => 'sfpmei/requetes',
                ]
            ]
        ]
    ];

    /**
     * Helpers
     */
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

    /**
     * Data
     */
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
    /** @var \echeanciers */
    protected $echeanciers;

    /**
     * Doctrine entities
     */
    /** @var Users */
    protected $userEntity;

    /**
     * Config
     */
    /** @var string */
    protected $spath;
    /** @var string */
    protected $furl;
    /** @var string */
    protected $lurl;
    /** @var string */
    protected $surl;
    /** @var string */
    protected $url;

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
        $this->users            = $this->loadData('users', array('lurl' => $this->lurl));
        $this->users_zones      = $this->loadData('users_zones');
        $this->users_history    = $this->loadData('users_history');
        $this->mail_template    = $this->loadData('mail_templates');

        if (! empty($_POST['connect']) && ! empty($_POST['password'])) {
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

                $this->error_login = "Le couple d'identifiant n'est pas correct";

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

        $this->loadCss('admin/bootstrap');
        $this->loadCss('../scripts/admin/freeow/freeow');
        $this->loadCss('../scripts/admin/external/jquery/plugin/colorbox/colorbox');
        $this->loadCss('../scripts/admin/treeview/jquery.treeview');
        $this->loadCss('../scripts/admin/tablesorter/style');
        $this->loadCss('../scripts/admin/external/jquery/plugin/jquery-ui/jquery-ui.min');
        $this->loadCss('admin/main');

        $this->settings->get('Paging Tableaux', 'type');
        $this->nb_lignes = $this->settings->value;

        $this->lLangues  = ['fr' => 'Francais'];
        $this->dLanguage = 'fr';

        if (isset($_SESSION['user']) && !empty($_SESSION['user']['id_user'])) {
            $this->sessionIdUser = $_SESSION['user']['id_user'];
            $this->lZonesHeader  = $this->users_zones->selectZonesUser($_SESSION['user']['id_user']);

            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');

            $this->userEntity = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);

            $userAccessEntity = new UserAccess();
            $userAccessEntity->setAction($this->current_function);
            $userAccessEntity->setController($this->current_controller);
            $userAccessEntity->setIdUser($this->userEntity);
            $userAccessEntity->setIp($_SERVER['REMOTE_ADDR']);

            $entityManager->persist($userAccessEntity);
            $entityManager->flush($userAccessEntity);
        }
    }
}
