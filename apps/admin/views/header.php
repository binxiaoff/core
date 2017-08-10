<?php

/*** NAVIGATION ***/

// Menu items
$menuItems = [
    [
        'title'    => 'Dashboard',
        'handle'   => 'dashboard',
        'children' => ''
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
                'handle' => '/mails/emailhistory'
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
            ],
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
                'handle' => '/stats/requete_infosben'
            ],
            [
                'title'  => 'Toutes les enchères',
                'handle' => '/stats/requete_encheres'
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
                'handle' => 'évaluation dossiers'
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
            ],
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
    ]
];

// Generate menu function
function generateMenu($menuItems, $base, $theme, $currentPage, $zoneHeader)
{
    $menuHtml = '';

    foreach ($menuItems as $item) {

        $itemHandle = $item['handle'];
        $itemTitle  = $item['title'];

        // Item visibility
        if (in_array($itemHandle, $zoneHeader)) {

            // Check user and adjust title for Dashboard item
            if ($itemTitle === 'Dashboard') {
                if (in_array($_SESSION['user']['id_user_type'], [\users_types::TYPE_RISK, \users_types::TYPE_COMMERCIAL]) || in_array($_SESSION['user']['id_user'], [23, 26])) {
                    $itemTitle = 'Mon flux';
                }
            }

            // Check theme and adjust menu html
            if ($theme !== 'oneui') {
                $active = '';
                if ($currentPage === $itemHandle) {
                    $active = ' class="active"';
                }
                $menuHtml .= '<li><a href="' . $base . '/' . $itemHandle . '"' . $active . '>' . $itemTitle . '</a>';
                if (! empty($item['children'])) {
                    $menuHtml .= '<ul class="sous_menu">';
                    foreach ($item['children'] as $subItem) {
                        $menuHtml .= '<li><a href="' . $base . '/' . $subItem['handle'] . '">' . $subItem['title'] . '</a><li>';
                    }
                    $menuHtml .= '</ul>';
                }
                $menuHtml .= '</li>';
            } else {
                $active = '';
                if ($currentPage === $itemHandle) {
                    $active = ' active';
                }

                if (empty($item['children'])) {
                    $menuHtml .= '<li class="' . $active . '"><a href="' . $base . '/' . $itemHandle . '">' . $item['title'] . '</a></li>';
                } else {
                    $menuHtml .= '<li class="dropdown' . $active . '"><a href="' . $base . '/' . $itemHandle . '">' . $item['title'] . '</a>';
                    $menuHtml .= '<ul class="dropdown-menu">';
                    foreach ($item['children'] as $subItem) {
                        $menuHtml .= '<li><a href="' . $base . '/' . $subItem['handle'] . '">' . $subItem['title'] . '</a><li>';
                    }
                    $menuHtml .= '</ul>';
                    $menuHtml .= '</li>';
                }
            }

        }
    }

    echo $menuHtml;
}

?>

<?php if (false === $this->useOneUi) : ?>
    <div id="header">
        <div class="row">
            <div class="col-md-6">
                <div class="logo_header">
                    <a href="<?= $this->lurl ?>"><img src="<?= $this->surl ?>/styles/default/images/logo.png" alt="Unilend"/></a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="bloc_info_header">
                    <div>
                        <a href="<?= $this->lurl ?>/users/edit_perso/<?= $_SESSION['user']['id_user'] ?>" class="thickbox">
                            <?= $_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['name'] ?>
                        </a>
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        <a href="<?= $this->lurl ?>/logout" title="Se déconnecter"><strong>Se déconnecter</strong></a>
                    </div>
                    <form id="quick_search" method="post">
                        <?php if (in_array('emprunteurs', $this->lZonesHeader)) : ?>
                            <input type="text" name="projectName" title="Raison sociale" placeholder="Raison sociale" size="20"/>
                            <input type="text" name="siren" title="SIREN" placeholder="SIREN" size="10"/>
                            <input type="text" name="projectId" title="ID projet" placeholder="ID projet" size="10"/>
                        <?php endif; ?>
                        <?php if (in_array('preteurs', $this->lZonesHeader)) : ?>
                            <input type="text" name="lender" title="ID client" placeholder="ID client" size="10"/>
                        <?php endif; ?>
                        <!-- Trick for enabling submitting form in Safari and IE -->
                        <input type="submit" style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1"/>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div id="navigation">
        <ul id="menu_deroulant">
            <?php generateMenu($menuItems, $this->lurl, 'oldbo', $this->menu_admin, $this->lZonesHeader) ?>
        </ul>
    </div>
    <div id="freeow-tr" class="freeow freeow-top-right"></div>
<?php else : ?>
<div id="page-container" class="header-navbar-fixed">
    <header id="header-navbar">
        <div class="content-mini content-mini-full content-boxed">
            <div class="nav-header pull-right">
                <div class="collapse navbar-collapse remove-padding" id="sub-header-nav">
                    <ul class="nav nav-pills nav-sub-header">
                        <?php generateMenu($menuItems, $this->lurl, 'oneui', $this->menu_admin, $this->lZonesHeader) ?>
                    </ul>
                </div>
            </div>
            <ul class="nav-header pull-left">
                <li class="header-content">
                    <a class="logo" href="/">
                        <img src="oneui/img/logo-and-type-unilend-209x44-purple@2x.png" width="209" height="44" alt="Unilend">
                    </a>
                </li>
            </ul>
        </div>
    </header>
    <main id="main-container">
        <div class="content content-boxed">
<?php endif; ?>
