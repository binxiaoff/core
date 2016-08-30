<?php

$this->projects = $this->loadData('projects');

$this->settings->get('Somme à emprunter min', 'type');
$this->sommeMin = $this->settings->value;

$this->settings->get('Somme à emprunter max', 'type');
$this->sommeMax = $this->settings->value;

$this->lng['etape-1']          = $this->ln->selectFront('depot-de-dossier-etape1', $this->language, $this->App);
$this->lng['depot-de-dossier'] = $this->ln->selectFront('depot-de-dossier', $this->language, $this->App);
$this->lng['landing-page']     = $this->ln->selectFront('landing-page', $this->language, $this->App);

switch (current(explode('/', substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 1)))) {
    case 'emprunter-avec-cacl':
        $this->bShortTunnel = true;
        $_SESSION['depot-de-dossier']['partner'] = 'cacl';
        break;
    default:
        $this->bShortTunnel = false;
        unset($_SESSION['depot-de-dossier']['partner']);
        break;
}

if ($this->bShortTunnel) {
    $_GET['utm_source'] = '';
    $_GET['utm_source2'] = '';
    $_SESSION['utm_source'] = '';
    $_SESSION['utm_source2'] = '';
}

$bProcessForm = false;

if (isset($_POST['spy_inscription_landing_page_depot_dossier'])) {
    $bProcessForm = true;
    $_SESSION['forms']['depot-de-dossier']['values'] = $_POST;
} elseif (isset($_GET['montant'], $_GET['siren'], $_GET['email']) && false === isset($_SESSION['forms']['depot-de-dossier']['values'])) {
    $bProcessForm = true;
    $_SESSION['forms']['depot-de-dossier']['values'] = $_GET;
} elseif (isset($_SESSION['forms']['depot-de-dossier'])) {
    $this->aForm = $_SESSION['forms']['depot-de-dossier'];
    unset($_SESSION['forms']['depot-de-dossier']);
}

/**
 * If borrower is redirected to Unilend
 * We save data to session but don't overwrite posted data
 */
foreach (array('siren', 'montant', 'email', 'prenom', 'nom', 'mobile') as $sFieldName) {
    if (isset($_GET[$sFieldName]) && false === isset($this->aForm['values'][$sFieldName])) {
        $this->aForm['values'][$sFieldName]                             = $_GET[$sFieldName];
        $_SESSION['forms']['depot-de-dossier-2']['values'][$sFieldName] = $_GET[$sFieldName];
    }
}

if ($bProcessForm) {
    if (
        false === $this->bShortTunnel
        && (
            empty($_SESSION['forms']['depot-de-dossier']['values']['email'])
            || false === $this->ficelle->isEmail($_SESSION['forms']['depot-de-dossier']['values']['email'])
        )
    ) {
        $_SESSION['forms']['depot-de-dossier']['response']        = $this->lng['landing-page']['champs-obligatoires'];
        $_SESSION['forms']['depot-de-dossier']['errors']['email'] = true;
    }

    if (empty($_SESSION['forms']['depot-de-dossier']['values']['montant'])) {
        $_SESSION['forms']['depot-de-dossier']['response']          = $this->lng['landing-page']['champs-obligatoires'];
        $_SESSION['forms']['depot-de-dossier']['errors']['montant'] = true;
    }

    if (
        empty($_SESSION['forms']['depot-de-dossier']['values']['siren'])
        || $_SESSION['forms']['depot-de-dossier']['values']['siren'] != (int) $_SESSION['forms']['depot-de-dossier']['values']['siren']
        || strlen($_SESSION['forms']['depot-de-dossier']['values']['siren']) !== 9
    ) {
        $_SESSION['forms']['depot-de-dossier']['response']        = $this->lng['landing-page']['champs-obligatoires'];
        $_SESSION['forms']['depot-de-dossier']['errors']['siren'] = true;
    } else {
        $iAmount                                                    = str_replace(array(',', ' '), array('.', ''), $_SESSION['forms']['depot-de-dossier']['values']['montant']);
        $_SESSION['forms']['depot-de-dossier']['values']['montant'] = $iAmount;

        if ($iAmount != (int) $iAmount) {
            $_SESSION['forms']['depot-de-dossier']['response']          = $this->lng['landing-page']['champs-obligatoires'];
            $_SESSION['forms']['depot-de-dossier']['errors']['montant'] = true;
        } elseif ($iAmount < $this->sommeMin || $iAmount > $this->sommeMax) {
            $_SESSION['forms']['depot-de-dossier']['response']          = $this->lng['depot-de-dossier']['montant-invalide'];
            $_SESSION['forms']['depot-de-dossier']['errors']['montant'] = true;
        }
    }

    if (isset($_SESSION['forms']['depot-de-dossier']['errors'])) {
        header('Location: ' . $_SERVER['REQUEST_URI']);
        die;
    }

    header('Location: ' . $this->lurl . '/depot_de_dossier/etape1');
    die;
}

$this->ordreProject = 1;
$this->type = 0;

$this->lProjetsFunding = $this->projects->selectProjectsByStatus([\projects_status::EN_FUNDING, \projects_status::FUNDE, \projects_status::REMBOURSEMENT], ' AND p.display = 0', $this->tabOrdreProject[$this->ordreProject], 0, 6);
$this->nbProjects      = $this->projects->countSelectProjectsByStatus(implode(', ', array(\projects_status::EN_FUNDING, \projects_status::FUNDE, \projects_status::REMBOURSEMENT)), ' AND p.display = 0');
