<?php
/*if($_SERVER["REMOTE_ADDR"] != "93.26.42.99" && $_SERVER["HTTP_X_FORWARDED_FOR"] != "93.26.42.99")
{
	header('location:'.$this->lurl,true,301);
    die;
}*/

// Chargement des datas
$this->projects = $this->loadData('projects');
$this->clients = $this->loadData('clients');
$this->clients_adresses = $this->loadData('clients_adresses');
$this->companies = $this->loadData('companies');
$this->companies_bilans = $this->loadData('companies_bilans');
$this->companies_details = $this->loadData('companies_details');
$this->companies_actif_passif = $this->loadData('companies_actif_passif');
$this->projects_status_history = $this->loadData('projects_status_history');
$this->projects = $this->loadData('projects');

// load des durée des prêts autorisées
$this->settings->get('Durée des prêts autorisées','type');
$this->dureePossible = explode(',',$this->settings->value);

if (empty($this->settings->value)) {
	$this->dureePossible = array(24,36,48,60);
}
//traduction 
$this->lng['landing-page'] = $this->ln->selectFront('landing-page',$this->language,$this->App);

	
// page projet tri
// 1 : terminé bientot
// 2 : nouveauté
//$this->tabOrdreProject[....] <--- dans le bootstrap pour etre accessible partout (page default et ajax)

$this->ordreProject = 1; 
$this->type = 0;		

// Liste des projets en funding
$this->lProjetsFunding = $this->projects->selectProjectsByStatus('50,60,80',' AND p.status = 0 AND p.display = 0',$this->tabOrdreProject[$this->ordreProject],0,6);

// Nb projets en funding
$this->nbProjects = $this->projects->countSelectProjectsByStatus('50,60,80',' AND p.status = 0 AND p.display = 0');


