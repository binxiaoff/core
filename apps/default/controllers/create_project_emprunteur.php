<?php

class create_project_emprunteurController extends bootstrap
{
	var $Command;
	
	function create_project_emprunteurController($command,$config,$app)
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
		
		
		$this->page = 'create_project_emprunteur';
		
	}
	
	function _default()
	{
		$this->lng['create-project'] = $this->ln->selectFront('emprunteur-create-project',$this->language,$this->App);
		
		// Chargement des datas
		$this->companies = $this->loadData('companies');
		$this->projects = $this->loadData('projects');
		
		$this->companies->get($this->clients->id_client,'id_client_owner');
		
		
		if(isset($_POST['send_form_create_project']))
		{
			// enregistrement des informations
			$this->projects->id_company = $this->companies->id_company;
			$this->projects->amount = str_replace(',','.',str_replace(' ','',$_POST['montant']));
			$this->projects->period = $_POST['duree'];
			$this->projects->title = $_POST['project-title'];
			$this->projects->objectif_loan = $_POST['credit-objective'];
			$this->projects->presentation_company = $_POST['presentation'];
			$this->projects->means_repayment = $_POST['moyen'];
			
			$this->form_ok = true;
			
			if(!isset($_POST['montant']) || $_POST['montant'] == '' || $_POST['montant'] == $this->lng['etape3']['montant'])
			{
				$this->form_ok = false;
			}
			if(!isset($_POST['duree']) || $_POST['duree'] == '' || $_POST['duree'] == 0)
			{
				$this->form_ok = false;
			}
			if(!isset($_POST['project-title']) || $_POST['project-title'] == '' || $_POST['project-title'] == $this->lng['etape3']['titre-projet'])
			{
				$this->form_ok = false;
			}
			if(!isset($_POST['credit-objective']) || $_POST['credit-objective'] == '' || $_POST['credit-objective'] == $this->lng['etape3']['objectif-du-credit'])
			{
				$this->form_ok = false;
			}
			if(!isset($_POST['presentation']) || $_POST['presentation'] == '' || $_POST['presentation'] == $this->lng['etape3']['presentation-de-la-societe'])
			{
				$this->form_ok = false;
			}
			if(!isset($_POST['moyen']) || $_POST['moyen'] == '' || $_POST['moyen'] == $this->lng['etape3']['moyen-de-remboursement-prevu'])
			{
				$this->form_ok = false;
			}
			
			// Si form ok
			if($this->form_ok == true)
			{	
				$this->projects->stand_by = 0;
				
				// On recupere l'analyste par defaut
				$this->users = $this->loadData('users');
				$this->users->get(1,'default_analyst');
				
				$this->projects->id_analyste = $this->users->id_user;
				$this->projects->create();
				
				// ajout du statut dans l'historique : statut 10 (non lu)
				$this->projects_status_history = $this->loadData('projects_status_history');
				$this->projects_status_history->addStatus(-2,10,$this->projects->id_project);

			}
			
		}

	}
	
}