<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\UserAccess;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;

class bootstrap extends Controller
{
    const MENU = [
        [
            'title'    => 'Dashboard',
            'handle'   => 'dashboard',
            'children' => []
        ],
        [
            'title'    => 'Edition',
            'handle'   => 'edition',
            'children' => [
                [
                    'title'  => 'Arborescence',
                    'handle' => 'tree',
                ],
                [
                    'title'  => 'Blocs',
                    'handle' => 'blocs'
                ],
                [
                    'title'  => 'Menus',
                    'handle' => 'menus'
                ],
                [
                    'title'  => 'Templates',
                    'handle' => 'templates'
                ],
                [
                    'title'  => 'Traductions',
                    'handle' => 'traductions'
                ],
                [
                    'title'  => 'Mails',
                    'handle' => 'mails'
                ]
            ]
        ],
        [
            'title'    => 'Configuration',
            'handle'   => 'configuration',
            'children' => [
                [
                    'title'  => 'Paramètres',
                    'handle' => 'settings',
                ],
                [
                    'title'  => 'Historique des Mails',
                    'handle' => 'mails/emailhistory'
                ],
                [
                    'title'  => 'Campagnes',
                    'handle' => 'partenaires'
                ],
                [
                    'title'  => 'Types de campagnes',
                    'handle' => 'partenaires/types'
                ],
                [
                    'title'  => 'Medias de campagnes',
                    'handle' => 'partenaires/medias'
                ],
                [
                    'title'  => 'Grille de taux',
                    'handle' => 'project_rate_settings'
                ]
            ]
        ],
        [
            'title'    => 'Statistiques',
            'handle'   => 'stats',
            'children' => [
                [
                    'title'  => 'Requêtes',
                    'handle' => 'queries',
                ],
                [
                    'title'  => 'Etape d\'inscription',
                    'handle' => 'stats/etape_inscription'
                ],
                [
                    'title'  => 'Sources emprunteurs',
                    'handle' => 'stats/requete_source_emprunteurs'
                ],
                [
                    'title'  => 'Revenus',
                    'handle' => 'stats/requete_revenus_download'
                ],
                [
                    'title'  => 'Bénéficiaires',
                    'handle' => 'stats/requete_beneficiaires_csv'
                ],
                [
                    'title'  => 'Infosben',
                    'handle' => 'stats/requete_infosben'
                ],
                [
                    'title'  => 'Toutes les enchères',
                    'handle' => 'stats/requete_encheres'
                ],
                [
                    'title'  => 'Echeanciers projet',
                    'handle' => 'stats/tous_echeanciers_pour_projet'
                ],
                [
                    'title'  => 'Statistiques Autolend',
                    'handle' => 'stats/autobid_statistic'
                ],
                [
                    'title'  => 'Déclarations BDF',
                    'handle' => 'stats/declarations_bdf'
                ],
                [
                    'title'  => 'CRS CAC',
                    'handle' => 'stats/requete_crs_cac'
                ],
                [
                    'title'  => 'Extraction des évaluations d\'éligibilité des dossiers',
                    'handle' => 'stats/extraction_b_lend'
                ],
                [
                    'title'  => 'Logs webservices',
                    'handle' => 'stats/logs_webservices'
                ]
            ]
        ],
        [
            'title'    => 'Prêteurs',
            'handle'   => 'preteurs',
            'children' => [
                [
                    'title'  => 'Recherche prêteurs',
                    'handle' => 'preteurs/search',
                ],
                [
                    'title'  => 'Activation prêteurs',
                    'handle' => 'preteurs/activation '
                ],
                [
                    'title'  => 'Offre de bienvenue',
                    'handle' => 'preteurs/offres_de_bienvenue'
                ],
                [
                    'title'  => 'Matching ville fiscale',
                    'handle' => 'preteurs/control_fiscal_city'
                ],
                [
                    'title'  => 'Matching ville de naissance',
                    'handle' => 'preteurs/control_birth_city'
                ],
                [
                    'title'  => 'Notifications',
                    'handle' => 'preteurs/notifications'
                ]
            ]
        ],
        [
            'title'    => 'Emprunteurs',
            'handle'   => 'emprunteurs',
            'children' => [
                [
                    'title'  => 'Dossiers',
                    'handle' => 'dossiers',
                ],
                [
                    'title'  => 'Emprunteurs',
                    'handle' => 'emprunteurs/gestion'
                ],
                [
                    'title'  => 'Prescripteurs',
                    'handle' => 'prescripteurs/gestion'
                ],
                [
                    'title'  => 'Dossiers en funding',
                    'handle' => 'dossiers/funding '
                ],
                [
                    'title'  => 'Remboursements',
                    'handle' => 'dossiers/remboursements'
                ],
                [
                    'title'  => 'Erreurs remboursements',
                    'handle' => 'dossiers/no_remb'
                ],
                [
                    'title'  => 'Suivi statuts projets',
                    'handle' => 'dossiers/status'
                ],
                [
                    'title'  => 'Erreurs remboursements',
                    'handle' => 'dossiers/no_remb'
                ],
                [
                    'title'  => 'Produits',
                    'handle' => 'product'
                ],
                [
                    'title'  => 'Sociétés',
                    'handle' => 'company'
                ],
                [
                    'title'  => 'Partenaires',
                    'handle' => 'partner'
                ],
                [
                    'title'  => 'Monitoring données risque',
                    'handle' => 'risk_monitoring'
                ]
            ]
        ],
        [
            'title'    => 'Dépôt de fonds',
            'handle'   => 'transferts',
            'children' => [
                [
                    'title'  => 'Prêteurs',
                    'handle' => 'transferts/preteurs',
                ],
                [
                    'title'  => 'Emprunteurs',
                    'handle' => 'transferts/emprunteurs'
                ],
                [
                    'title'  => 'Non attribués',
                    'handle' => 'transferts/non_attribues'
                ],
                [
                    'title'  => 'Rattrapage offre de bienvenue',
                    'handle' => 'transferts/rattrapage_offre_bienvenue'
                ],
                [
                    'title'  => 'Déblocage des fonds',
                    'handle' => 'transferts/deblocage'
                ],
                [
                    'title'  => 'Succession (Transfert de solde et prêts)',
                    'handle' => 'transferts/succession'
                ],
                [
                    'title'  => 'Opérations atypiques',
                    'handle' => 'client_atypical_operation'
                ],
                [
                    'title'  => 'Transfert des fonds',
                    'handle' => 'transferts/virement_emprunteur'
                ]
            ]
        ],
        [
            'title'    => 'Administration',
            'handle'   => 'admin',
            'children' => [
                [
                    'title'  => 'Utilisateurs',
                    'handle' => 'users',
                ],
                [
                    'title'  => 'Droits d\'accès',
                    'handle' => 'zones'
                ],
                [
                    'title'  => 'Logs connexions',
                    'handle' => 'users/logs'
                ]
            ]
        ],
        [
            'title'    => 'SFPMEI',
            'handle'   => 'sfpmei',
            'children' => [
                [
                    'title'  => 'Prêteurs',
                    'handle' => 'sfpmei/preteurs',
                ],
                [
                    'title'  => 'Prêteurs',
                    'handle' => 'sfpmei/emprunteurs',
                ],
                [
                    'title'  => 'Emprunteurs',
                    'handle' => 'sfpmei/projets',
                ],
                [
                    'title'  => 'Projets',
                    'handle' => 'sfpmei/transferts/preteurs',
                ],
                [
                    'title'  => 'Transferts de fonds prêteurs',
                    'handle' => 'sfpmei/transferts/emprunteurs',
                ],
                [
                    'title'  => 'Transferts de fonds emprunteurs',
                    'handle' => 'sfpmei/requetes',
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
