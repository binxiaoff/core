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
$this->lenders_accounts = $this->loadData('lenders_accounts');

//traduction 
$this->lng['landing-page'] = $this->ln->selectFront('landing-page',$this->language,$this->App);

// source
$this->ficelle->source($_GET['utm_source'],$this->lurl.'/'.$this->params[0],$_GET['utm_source2']);

// On récupère le formulaire d'inscription de la page
if(isset($_POST['spy_inscription_landing_page']))
{
	$nom = $_POST['nom'];
	$prenom = $_POST['prenom'];
	$email = $_POST['email'];
	$email2 = $_POST['email-confirm'];
	
	$form_valid = true;
	
	if(!isset($nom) or $nom == $this->lng['landing-page']['nom'])
	{
		$form_valid = false;
		$this->retour_form = $this->lng['landing-page']['champs-obligatoires'];
	}
	
	if(!isset($prenom) or $prenom == $this->lng['landing-page']['prenom'])
	{
		$form_valid = false;
		$this->retour_form = $this->lng['landing-page']['champs-obligatoires'];
	}
	
	
	if(!isset($email) || $email == '' || $email == $this->lng['landing-page']['email'])
	{
		$form_valid = false;
		$this->retour_form = $this->lng['landing-page']['champs-obligatoires'];
	}
	// verif format mail
	elseif(!$this->ficelle->isEmail($email))
	{
		$form_valid = false;
		$this->retour_form = $this->lng['landing-page']['email-erreur-format'];
	}
	// conf email good/pas
	elseif($email != $email2)
	{
		$form_valid = false;
		$this->retour_form = $this->lng['landing-page']['confirmation-email-erreur'];
	}
	// si exite ou pas
	elseif($this->clients->existEmail($email) == false)
	{
		$form_valid = false;
		$this->retour_form = $this->lng['landing-page']['email-existe-deja'];
	}
	
	
	if($form_valid)
	{
		//Ajout CM 06/08/14
		// On ne créé plus le client a ce niveau, on se contente de pré remplir les champs sur la page d'apres
		$_SESSION['landing_client']['prenom'] = $prenom;
		$_SESSION['landing_client']['nom'] = $nom;
		$_SESSION['landing_client']['email'] = $email;
		
		$this->prospects = $this->loadData('prospects');
		$this->prospects->id_langue = 'fr';
		$this->prospects->prenom = $prenom;
		$this->prospects->nom = $nom;
		$this->prospects->email = $email;
		$this->prospects->source = $_SESSION['utm_source'];
		$this->prospects->source2 = $_SESSION['utm_source2'];
		$this->prospects->slug_origine = $this->tree->slug;
		$this->prospects->create();		
		// On créer le client
		/*$this->clients->id_langue = 'fr';
		$this->clients->type = 1;
		$this->clients->status_pre_emp = 1;
		$this->clients->fonction = '';
		$this->clients->prenom = $prenom;
		$this->clients->nom = $nom;
		$this->clients->email = $email;
		$this->clients->slug = $this->bdd->generateSlug($this->clients->prenom.'-'.$this->clients->nom);				
		// On passe a zero le id company dans lender
		$this->lenders_accounts->id_company_owner = 0;
		$this->lenders_accounts->status = 1;
		// On créer le client
		$this->clients->id_client = $this->clients->create();
		
		
		// Ainsi que adresses clients
		$this->clients_adresses->id_client = $this->clients->id_client;
		$this->clients_adresses->meme_adresse_fiscal = 1;
		$this->clients_adresses->create();
		
		// creation du lender
		$this->lenders_accounts->id_client_owner = $this->clients->id_client;
		$this->lenders_accounts->create();*/
		$_SESSION['landing_page'] = true;
		
		header('location:'.$this->lurl.'/inscription_preteur/etape1/'.$this->clients->hash);
		die;
	}
}


	
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


