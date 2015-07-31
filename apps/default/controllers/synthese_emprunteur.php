<?php

class synthese_emprunteurController extends bootstrap
{
	var $Command;
	
	function synthese_emprunteurController($command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
		
		// On prend le header account
		$this->setHeader('header_account');
		
		
		// On check si y a un compte
		if(!$this->clients->checkAccess())
		{
			header('Location:'.$this->lurl);
			die;
		}
		else
		{
			// Etape de transition
			$this->settings->get('Etape de transition','type');
			$id_tree = $this->settings->value;
			$slug = $this->tree->getSlug($id_tree,$this->language);
			
			// check preteur ou emprunteur (ou les deux)
			$this->clients->checkStatusPreEmp($this->clients->status_pre_emp,'emprunteur',$this->clients->status_transition,$slug);
		}
		
		
		$this->page = 'synthese';
		
	}
	
	function _default()
	{
		//Recuperation des element de traductions
		$this->lng['synthese'] = $this->ln->selectFront('emprunteur-synthese',$this->language,$this->App);
		
		// Chargement des datas
		$this->companies = $this->loadData('companies');
		$this->projects = $this->loadData('projects');
		$this->projects_status = $this->loadData('projects_status');
		$this->loans = $this->loadData('loans');
		$this->echeanciers = $this->loadData('echeanciers');
		$this->echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
		$this->current_projects_status = $this->loadData('projects_status');
		
		$this->companies->get($this->clients->id_client,'id_client_owner');
		
		
		
		
		
		
		$lprojects = $this->projects->selectProjectsByStatus('50,60,70,75,80,90,100,110,120',' AND p.id_company = '.$this->companies->id_company.' AND p.status = 0');
		
		$this->nbProjets = count($lprojects);
		
		if($this->nbProjets == 1)
		{
			$this->slug = $lprojects[0]['slug'];
		}
		
		
		$this->nbPeteurs = 0;
		$this->sum = 0;
		$this->montant_mensuel = 0;
		
		foreach($lprojects as $p)
		{
			
			$this->current_projects_status->getLastStatut($p['id_project']);
			if($this->current_projects_status->status == 60 || $this->current_projects_status->status == 80)
			{
			
			$this->nbPeteurs += $this->loans->getNbPreteurs($p['id_project']);
			$this->sum += $p['amount'];
			
			
			
			$prochainRemb = $this->echeanciers_emprunteur->select('id_project = '.$r['id_project'].' AND status_emprunteur = 0','ordre ASC');
				
			$montant = $prochainRemb[0]['montant']+$prochainRemb[0]['commission']+$prochainRemb[0]['tva'];
			
			
			$result = $this->echeanciers->getNextRembEmprunteur($p['id_project']);
			$this->montant_mensuel += $montant;
			}
			
		}
		
		
	}
	
}