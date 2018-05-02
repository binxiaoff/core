<?php

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    LoginConnectionAdmin, ProjectsStatus, UserAccess, Users, Zones
};

class bootstrap extends Controller
{
    const MENU = [
        [
            'title'    => 'Accueil',
            'uri'   => 'root',
            'zone'     => Zones::ZONE_LABEL_DASHBOARD,
            'children' => [
                [
                    'title' => 'Accueil',
                    'uri'   => ''
                ],
                [
                    'title' => 'Activité commercial',
                    'uri'   => 'dashboard/activite'
                ]
            ]
        ],
        [
            'title'    => 'Edition',
            'uri'      => 'tree',
            'zone'     => Zones::ZONE_LABEL_EDITION,
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
            'zone'     => Zones::ZONE_LABEL_CONFIGURATION,
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
                    'title' => 'Grille de taux',
                    'uri'   => 'project_rate_settings',
                    'zone'  => Zones::ZONE_LABEL_ADMINISTRATION
                ]
            ]
        ],
        [
            'title'    => 'Statistiques',
            'uri'      => 'queries',
            'zone'     => Zones::ZONE_LABEL_STATISTICS,
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
                    'uri'   => 'stats/requete_beneficiaires_download'
                ],
                [
                    'title' => 'Infosben',
                    'uri'   => 'stats/requete_infosben_download'
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
                    'title' => 'CRS DAC',
                    'uri'   => 'stats/requete_crs_dac'
                ],
                [
                    'title' => 'Éligibilité des projets',
                    'uri'   => 'stats/projects_eligibility'
                ],
                [
                    'title' => 'Logs webservices',
                    'uri'   => 'stats/logs_webservices'
                ],
                [
                    'title' => 'Reporting Mensuel SFPMEI',
                    'uri'   => 'stats/reporting_sfpmei'
                ],
                [
                    'title' => 'Loi Eckert',
                    'uri'   => 'stats/loi_eckert'
                ],
            ]
        ],
        [
            'title'    => 'Prêteurs',
            'uri'      => 'preteurs/search',
            'zone'     => Zones::ZONE_LABEL_LENDERS,
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
                    'title' => 'Parrainage',
                    'uri'   => 'parrainage'
                ],
                [
                    'title' => 'Notifications',
                    'uri'   => 'preteurs/notifications'
                ],
                [
                    'title' => 'Opérations atypiques',
                    'uri'   => 'client_atypical_operation'
                ],
                [
                    'title' => 'Comptes doublons',
                    'uri'   => 'comptes/doublons'
                ]
            ]
        ],
        [
            'title'    => 'Emprunteurs',
            'zone'     => Zones::ZONE_LABEL_BORROWERS,
            'children' => [
                [
                    'title' => 'Projets',
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
                    'title' => 'Projets en funding',
                    'uri'   => 'dossiers/funding '
                ],
                [
                    'title' => 'Remboursements',
                    'uri'   => 'dossiers/' . ProjectsStatus::FUNDE . ',' . ProjectsStatus::REMBOURSEMENT . ',' . ProjectsStatus::REMBOURSE . ',' . ProjectsStatus::REMBOURSEMENT_ANTICIPE
                ],
                [
                    'title' => 'Erreurs remboursements',
                    'uri'   => 'dossiers/' . ProjectsStatus::PROBLEME . ',' . ProjectsStatus::LOSS
                ],
                [
                    'title' => 'Suivi statuts projets',
                    'uri'   => 'dossiers/status'
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
                    'uri'   => 'partenaires'
                ],
                [
                    'title' => 'Comptes doublons',
                    'uri'   => 'comptes/doublons'
                ],
                [
                    'title' => 'Surveillance données risque',
                    'uri'   => 'surveillance_risque'
                ],
                [
                    'title' => 'Simulateur WS risque',
                    'uri'   => 'simulation/webservices_risque',
                    'zone'  => Zones::ZONE_LABEL_SIMULATOR
                ],
                [
                    'title' => 'Test éligibilité',
                    'uri'   => 'emprunteurs/test_eligibilite',
                    'zone'  => Zones::ZONE_LABEL_RISK
                ],
                [
                    'title' => 'Dépôt liste projets',
                    'uri'   => 'projets/depot_liste',
                    'zone'  => Zones::ZONE_LABEL_RISK
                ]
            ]
        ],
        [
            'title' => 'Remboursements',
            'uri'   => 'remboursement/validation',
            'zone'  => Zones::ZONE_LABEL_REPAYMENT,
            'children' => [
                [
                    'title' => 'Projets avec retard',
                    'uri'   => 'dossiers/projets_avec_retard'
                ],
                [
                    'title' => 'Remboursements à valider',
                    'uri'   => 'remboursement/validation'
                ],
                [
                    'title' => 'Recouvreurs',
                    'uri'   => 'recouvreur/liste',
                    'zone'  => Zones::ZONE_LABEL_DEBT_COLLECTOR
                ],
            ]
        ],
        [
            'title'    => 'Dépôt de fonds',
            'uri'      => 'transferts',
            'zone'     => Zones::ZONE_LABEL_TRANSFERS,
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
            'uri'      => 'users',
            'zone'     => Zones::ZONE_LABEL_ADMINISTRATION,
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
            'zone'     => Zones::ZONE_LABEL_SFPMEI,
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
                    'title' => 'Transferts de fonds emprunteurs',
                    'uri'   => 'sfpmei/transferts/emprunteurs',
                ],
                [
                    'title' => 'Opérations non attribuées',
                    'uri'   => 'sfpmei/transferts/non_attribues',
                ],
                [
                    'title' => 'Exports',
                    'uri'   => 'sfpmei/exports',
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
    /** @var string */
    protected $staticsKey;

    public function initialize()
    {
        parent::initialize();

        if ($this->current_function != 'login') {
            $_SESSION['request_url'] = $_SERVER['REQUEST_URI'];
        }

        $this->staticsKey = filemtime(__FILE__);

        $this->dates   = $this->loadLib('dates');
        $this->ficelle = $this->loadLib('ficelle');
        $this->upload  = $this->loadLib('upload');
        $this->photos  = $this->loadLib('photos', array($this->spath, $this->surl));

        $this->ln             = $this->loadData('translations');
        $this->settings       = $this->loadData('settings');
        $this->tree_elements  = $this->loadData('tree_elements');
        $this->blocs          = $this->loadData('blocs');
        $this->blocs_elements = $this->loadData('blocs_elements');
        $this->elements       = $this->loadData('elements');
        $this->tree           = $this->loadData('tree', ['url' => $this->lurl, 'front' => $this->furl, 'surl' => $this->surl, 'tree_elements' => $this->tree_elements, 'blocs_elements' => $this->blocs_elements, 'upload' => $this->upload, 'spath' => $this->spath, 'path' => $this->path]);
        $this->users          = $this->loadData('users', ['lurl' => $this->lurl]);
        $this->users_zones    = $this->loadData('users_zones');
        $this->users_history  = $this->loadData('users_history');

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (false === empty($_POST['connect']) && false === empty($_POST['password'])) {
            $loginLog = new LoginConnectionAdmin();
            $loginLog->setEmail($_POST['login']);
            $loginLog->setDateConnexion(new \DateTime('now'));
            $loginLog->setIp($_SERVER['REMOTE_ADDR']);

            $isAuthorizedIp = true;
            $user           = $this->users->login($_POST['login'], $_POST['password']);

            if ($user !== false && false === empty($user['ip'])) {
                $ip            = ip2long($_SERVER['REMOTE_ADDR']);
                $authorizedIps = explode(';', $user['ip']);

                foreach ($authorizedIps as $authorizedIp) {
                    $min            = null;
                    $max            = null;
                    $isAuthorizedIp = false;
                    $authorizedIp   = trim($authorizedIp);

                    if (false === strpos($authorizedIp, '-')) {
                        if (false !== filter_var($authorizedIp, FILTER_VALIDATE_IP)) {
                            $min = ip2long($authorizedIp);
                            $max = ip2long($authorizedIp);
                        }
                    } else {
                        $range = explode('-', $authorizedIp);

                        if (
                            2 === count($range)
                            && false !== filter_var($range[0], FILTER_VALIDATE_IP)
                            && false !== filter_var($range[1], FILTER_VALIDATE_IP)
                        ) {
                            $min = ip2long($range[0]);
                            $max = ip2long($range[1]);
                        }
                    }

                    if (isset($min, $max) && $ip >= $min && $ip <= $max) {
                        $isAuthorizedIp = true;
                        break;
                    }
                }
            }

            if ($user !== false && $isAuthorizedIp) {
                $loginLog->setIdUser($user['id_user']);
                $loginLog->setNomUser($user['firstname'] . ' ' . $user['name']);

                $entityManager->persist($loginLog);
                $entityManager->flush();

                unset($_SESSION['login_user']);

                $this->users->handleLogin($_POST['login'], $_POST['password']);
            } elseif (false === $isAuthorizedIp) {
                $this->error_login = 'Vous n\'êtes pas autorisé à vous connecter depuis cette adresse IP';
            } else {
                $this->duree_waiting             = 1;
                $this->nb_tentatives_precedentes = $entityManager->getRepository('UnilendCoreBusinessBundle:LoginConnectionAdmin')->countFailedAttemptsSince($_SERVER['REMOTE_ADDR'], new \DateTime('10 minutes ago'));

                if ($this->nb_tentatives_precedentes > 0 && $this->nb_tentatives_precedentes < 100) {
                    for ($i = 1; $i <= $this->nb_tentatives_precedentes; $i++) {
                        $this->duree_waiting *= 2;
                    }
                }

                $this->error_login = 'Identifiant ou mot de passe incorrect';
            }

            $entityManager->persist($loginLog);
            $entityManager->flush();
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
        $this->loadJs('admin/ajax', $this->staticsKey);
        $this->loadJs('admin/main', $this->staticsKey);

        $this->loadCss('admin/bootstrap');
        $this->loadCss('../scripts/admin/freeow/freeow');
        $this->loadCss('../scripts/admin/external/jquery/plugin/colorbox/colorbox');
        $this->loadCss('../scripts/admin/treeview/jquery.treeview');
        $this->loadCss('../scripts/admin/tablesorter/style');
        $this->loadCss('../scripts/admin/external/jquery/plugin/jquery-ui/jquery-ui.min');
        $this->loadCss('admin/main', $this->staticsKey);

        $this->settings->get('Paging Tableaux', 'type');
        $this->nb_lignes = $this->settings->value;

        $this->lLangues  = ['fr' => 'Francais'];
        $this->dLanguage = 'fr';

        if (isset($_SESSION['user']) && false === empty($_SESSION['user']['id_user'])) {
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

    /**
     * @param string $template
     * @param array  $context
     * @param bool   $return
     *
     * @return string
     */
    public function render($template = null, array $context = [], $return = false)
    {
        $context['app'] = [
            'staticsKey' => $this->staticsKey,
            'navigation' => self::MENU,
            'activeMenu' => isset($this->menu_admin) ? $this->menu_admin : '',
            'session'    => $_SESSION,
            'user'       => $this->userEntity,
            'userZones'  => isset($this->lZonesHeader) ? $this->lZonesHeader : []
        ];

        return parent::render($template, $context, $return);
    }
}
