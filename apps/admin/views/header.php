<?php

/*** NAVIGATION ***/

// Menu items
$menuItems = Array(
    0 => Array(
        'title' => 'Dashboard',
        'handle' => 'dashboard',
        'children' => ''
    ),
    1 => Array(
        'title' => 'Edition',
        'handle' => 'edition',
        'children' => Array(
            0 => Array(
                'title' => 'Arborescence',
                'handle' => 'tree',
            ),
            1 => Array(
                'title' => 'Blocs',
                'handle' => 'blocs'
            ),
            2 => Array(
                'title' => 'Menus',
                'handle' => 'menus'
            ),
            3 => Array(
                'title' => 'Templates',
                'handle' => 'templates'
            ),
            4 => Array(
                'title' => 'Traductions',
                'handle' => 'traductions'
            ),
            5 => Array(
                'title' => 'Mails',
                'handle' => 'mails'
            )
        )
    ),
    2 => Array(
        'title' => 'Configuration',
        'handle' => 'configuration',
        'children' => Array(
            0 => Array(
                'title' => 'Paramètres',
                'handle' => 'settings',
            ),
            1 => Array(
                'title' => 'Historique des Mails',
                'handle' => '/mails/emailhistory'
            ),
            2 => Array(
                'title' => 'Campagnes',
                'handle' => 'partenaires'
            ),
            3 => Array(
                'title' => 'Types de campagnes',
                'handle' => 'partenaires/types'
            ),
            4 => Array(
                'title' => 'Medias de campagnes',
                'handle' => 'partenaires/medias'
            ),
            5 => Array(
                'title' => 'Grille de taux',
                'handle' => 'project_rate_settings'
            ),
        )
    ),
    3 => Array(
        'title' => 'Statistiques',
        'handle' => 'stats',
        'children' => Array(
            0 => Array(
                'title' => 'Requêtes',
                'handle' => 'queries',
            ),
            1 => Array(
                'title' => 'Etape d\'inscription',
                'handle' => 'stats/etape_inscription'
            ),
            2 => Array(
                'title' => 'Sources emprunteurs',
                'handle' => 'stats/requete_source_emprunteurs'
            ),
            3 => Array(
                'title' => 'Revenus',
                'handle' => 'stats/requete_revenus_download'
            ),
            4 => Array(
                'title' => 'Bénéficiaires',
                'handle' => 'stats/requete_beneficiaires_csv'
            ),
            5 => Array(
                'title' => 'Infosben',
                'handle' => '/stats/requete_infosben'
            ),
            6 => Array(
                'title' => 'Toutes les enchères',
                'handle' => '/stats/requete_encheres'
            ),
            7 => Array(
                'title' => 'Echeanciers projet',
                'handle' => 'stats/tous_echeanciers_pour_projet'
            ),
            8 => Array(
                'title' => 'Statistiques Autolend',
                'handle' => 'stats/autobid_statistic'
            ),
            9 => Array(
                'title' => 'Déclarations BDF',
                'handle' => 'stats/declarations_bdf'
            ),
            10 => Array(
                'title' => 'CRS CAC',
                'handle' => 'stats/requete_crs_cac'
            ),
            11 => Array(
                'title' => 'Extraction des évaluations d\'éligibilité des dossiers',
                'handle' => 'évaluation dossiers'
            ),
            12 => Array(
                'title' => 'Logs webservices',
                'handle' => 'stats/logs_webservices'
            )
        )
    ),
    4 => Array(
        'title' => 'Prêteurs',
        'handle' => 'preteurs',
        'children' => Array(
            0 => Array(
                'title' => 'Recherche prêteurs',
                'handle' => 'preteurs/search',
            ),
            1 => Array(
                'title' => 'Activation prêteurs',
                'handle' => 'preteurs/activation '
            ),
            2 => Array(
                'title' => 'Offre de bienvenue',
                'handle' => 'preteurs/offres_de_bienvenue'
            ),
            3 => Array(
                'title' => 'Matching ville fiscale',
                'handle' => 'preteurs/control_fiscal_city'
            ),
            4 => Array(
                'title' => 'Matching ville de naissance',
                'handle' => 'preteurs/control_birth_city'
            ),
            5 => Array(
                'title' => 'Notifications',
                'handle' => 'preteurs/notifications'
            ),
        )
    ),
    5 => Array(
        'title' => 'Emprunteurs',
        'handle' => 'emprunteurs',
        'children' => Array(
            0 => Array(
                'title' => 'Dossiers',
                'handle' => 'dossiers',
            ),
            1 => Array(
                'title' => 'Emprunteurs',
                'handle' => 'emprunteurs/gestion'
            ),
            2 => Array(
                'title' => 'Prescripteurs',
                'handle' => 'prescripteurs/gestion'
            ),
            3 => Array(
                'title' => 'Dossiers en funding',
                'handle' => 'dossiers/funding '
            ),
            4 => Array(
                'title' => 'Remboursements',
                'handle' => 'dossiers/remboursements'
            ),
            5 => Array(
                'title' => 'Erreurs remboursements',
                'handle' => 'dossiers/no_remb'
            ),
            6 => Array(
                'title' => 'Suivi statuts projets',
                'handle' => 'dossiers/status'
            ),
            7 => Array(
                'title' => 'Erreurs remboursements',
                'handle' => 'dossiers/no_remb'
            ),
            8 => Array(
                'title' => 'Produits',
                'handle' => 'product'
            ),
            9 => Array(
                'title' => 'Sociétés',
                'handle' => 'company'
            ),
            10 => Array(
                'title' => 'Partenaires',
                'handle' => 'partner'
            )
        )
    ),
    6 => Array(
        'title' => 'Dépôt de fonds',
        'handle' => 'transferts',
        'children' => Array(
            0 => Array(
                'title' => 'Prêteurs',
                'handle' => 'transferts/preteurs',
            ),
            1 => Array(
                'title' => 'Emprunteurs',
                'handle' => 'transferts/emprunteurs'
            ),
            2 => Array(
                'title' => 'Non attribués',
                'handle' => 'transferts/non_attribues'
            ),
            3 => Array(
                'title' => 'Rattrapage offre de bienvenue',
                'handle' => 'transferts/rattrapage_offre_bienvenue'
            ),
            4 => Array(
                'title' => 'Déblocage des fonds',
                'handle' => 'transferts/deblocage'
            ),
            5 => Array(
                'title' => 'Succession (Transfert de solde et prêts)',
                'handle' => 'transferts/succession'
            ),
            6 => Array(
                'title' => 'Opérations atypiques',
                'handle' => 'client_atypical_operation'
            ),
            7 => Array(
                'title' => 'Transfert des fonds',
                'handle' => 'transferts/virement_emprunteur'
            )
        )
    ),
    7 => Array(
        'title' => 'Administration',
        'handle' => 'admin',
        'children' => Array(
            0 => Array(
                'title' => 'Utilisateurs',
                'handle' => 'users',
            ),
            1 => Array(
                'title' => 'Droits d\'accès',
                'handle' => 'zones'
            ),
            2 => Array(
                'title' => 'Logs connexions',
                'handle' => 'users/logs'
            )
        )
    )
);

// Generate menu function
function generateMenu($menuItems, $theme, $currentPage, $zoneHeader)
{
    $menuHtml = '';
    foreach ($menuItems as $item) {

        $itemHandle = $item['handle'];
        $itemTitle = $item['title'];

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
                $menuHtml .= '<li><a href="' . $itemHandle . '"' . $active . '>' . $itemTitle . '</a>';
                if (!empty($item['children'])) {
                    $menuHtml .= '<ul class="sous_menu">';
                    foreach ($item['children'] as $subItem) {
                        $menuHtml .= '<li><a href="' . $subItem['handle'] . '">' . $subItem['title'] . '</a><li>';
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
                    $menuHtml .= '<li class="' . $active . '"><a href="' . $itemHandle . '">' . $item['title'] . '</a></li>';
                } else {
                    $menuHtml .= '<li class="dropdown' . $active . '"><a href="' . $itemHandle . '" data-toggle="dropdown" data-hover="dropdown" data-delay="200" data-close-others="false" aria-expanded="false">' . $item['title'] . '</a>';
                    $menuHtml .= '<ul class="dropdown-menu">';
                    foreach ($item['children'] as $subItem) {
                        $menuHtml .= '<li><a href="' . $subItem['handle'] . '">' . $subItem['title'] . '</a><li>';
                    }
                    $menuHtml .= '</ul>';
                    $menuHtml .= '</li>';
                }
            }

        }
    }
    // print the menu
    echo $menuHtml;
}
?>

<?php if ($this->Command->getControllerName() !== 'oneui') : ?>
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
                        <input type="text" name="projectName" title="Raison sociale" placeholder="Raison sociale" size="20" />
                        <input type="text" name="siren" title="SIREN" placeholder="SIREN" size="10" />
                        <input type="text" name="projectId" title="ID projet" placeholder="ID projet" size="10" />
                    <?php endif; ?>
                    <?php if (in_array('preteurs', $this->lZonesHeader)) : ?>
                        <input type="text" name="lender" title="ID client" placeholder="ID client" size="10" />
                    <?php endif; ?>
                    <!-- Trick for enabling submitting form in Safari and IE -->
                    <input type="submit" style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1" />
                </form>
            </div>
        </div>
    </div>
</div>
<div id="navigation">
    <ul id="menu_deroulant">
        <?php generateMenu($menuItems, 'oldbo', $this->menu_admin, $this->lZonesHeader) ?>
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
                        <?php generateMenu($menuItems, 'oneui', $this->menu_admin, $this->lZonesHeader) ?>
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
