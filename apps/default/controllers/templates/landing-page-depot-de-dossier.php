<?php

$this->projects                = $this->loadData('projects');
$this->clients                 = $this->loadData('clients');
$this->clients_adresses        = $this->loadData('clients_adresses');
$this->companies               = $this->loadData('companies');
$this->companies_bilans        = $this->loadData('companies_bilans');
$this->companies_details       = $this->loadData('companies_details');
$this->companies_actif_passif  = $this->loadData('companies_actif_passif');
$this->projects_status_history = $this->loadData('projects_status_history');
$this->projects                = $this->loadData('projects');

$this->settings->get('Somme à emprunter min','type');
$this->sommeMin = $this->settings->value;

$this->settings->get('Somme à emprunter max','type');
$this->sommeMax = $this->settings->value;

$this->lng['etape-1']          = $this->ln->selectFront('depot-de-dossier-etape1', $this->language, $this->App);
$this->lng['depot-de-dossier'] = $this->ln->selectFront('depot-de-dossier', $this->language, $this->App);
$this->lng['landing-page']     = $this->ln->selectFront('landing-page', $this->language, $this->App);

$this->ficelle->source(
    isset($_GET['utm_source']) ? $_GET['utm_source'] : '',
    $this->lurl . (isset($this->params[0]) ? '/' . $this->params[0] : ''),
    isset($_GET['utm_source2']) ? $_GET['utm_source2'] : ''
);

if (isset($_SESSION['forms']['depot-de-dossier'])) {
    $this->aForm = $_SESSION['forms']['depot-de-dossier'];
    unset($_SESSION['forms']['depot-de-dossier']);
}

if (
    isset($_POST['spy_inscription_landing_page_depot_dossier'])
    || isset($_GET['montant'], $_GET['siren']) && ! empty($_GET['montant']) && ! empty($_GET['siren'])
) {
    $aForm = isset($_POST['spy_inscription_landing_page_depot_dossier']) ? $_POST : $_GET;
    $_SESSION['forms']['depot-de-dossier']['values'] = $aForm;

    if (false === empty($aForm['email']) && false === filter_var($aForm['email'], FILTER_VALIDATE_EMAIL)) {
        $_SESSION['forms']['depot-de-dossier']['response'] = $this->lng['landing-page']['champs-obligatoires'];
        $_SESSION['forms']['depot-de-dossier']['errors']['email'] = true;
    }

    if (empty($aForm['montant'])) {
        $_SESSION['forms']['depot-de-dossier']['response'] = $this->lng['landing-page']['champs-obligatoires'];
        $_SESSION['forms']['depot-de-dossier']['errors']['montant'] = true;
    }

    if (empty($aForm['siren']) || $aForm['siren'] != (int) $aForm['siren'] || strlen($aForm['siren']) !== 9) {
        $_SESSION['forms']['depot-de-dossier']['response'] = $this->lng['landing-page']['champs-obligatoires'];
        $_SESSION['forms']['depot-de-dossier']['errors']['siren'] = true;
    } else {
        $iAmount = str_replace(array(',', ' '), array('.', ''), $aForm['montant']);

        if ($iAmount != (int) $iAmount) {
            $_SESSION['forms']['depot-de-dossier']['response'] = $this->lng['landing-page']['champs-obligatoires'];
            $_SESSION['forms']['depot-de-dossier']['errors']['montant'] = true;
        } elseif ($iAmount < $this->sommeMin || $iAmount > $this->sommeMax) {
            $_SESSION['forms']['depot-de-dossier']['response'] = $this->lng['depot-de-dossier']['montant-invalide'];
            $_SESSION['forms']['depot-de-dossier']['errors']['montant'] = true;
        }
    }

    if (isset($_SESSION['forms']['depot-de-dossier']['errors'])) {
        header('Location: ' . $this->lurl . '/lp-depot-de-dossier');
        die;
    }

    header('Location: ' . $this->lurl . '/depot_de_dossier/etape1');
    die;
}

$this->ordreProject = 1;
$this->type         = 0;

// Liste des projets en funding
$this->lProjetsFunding = $this->projects->selectProjectsByStatus('50, 60, 80', ' AND p.status = 0 AND p.display = 0', $this->tabOrdreProject[$this->ordreProject], 0, 6);
$this->nbProjects      = $this->projects->countSelectProjectsByStatus('50, 60, 80', ' AND p.status = 0 AND p.display = 0');
