<?php 

class thickboxController extends bootstrap
{
	var $Command;
	
	function thickboxController(&$command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
	}
	
	// Traductions
	function _openTraduc()
	{
		$this->ln = $this->loadData('textes');		
		$this->ln->get($this->params[0],'id_texte');
	}
	
	function _pop_up_upload()
	{
		// Chargement des datas
		$this->clients = $this->loadData('clients');
		$this->companies = $this->loadData('companies');
		$this->companies_details = $this->loadData('companies_details');
		
		//Recuperation des element de traductions
		$this->lng['etape5'] = $this->ln->selectFront('depot-de-dossier-etape-5',$this->language,$this->App);
		
		// On recupere les client
		if(isset($this->params[0]) && $this->clients->get($this->params[0],'hash'))
		{
			// recup companie
			$this->companies->get($this->clients->id_client,'id_client_owner');
			
			// On récupere le détaille companie
			$this->companies_details->get($this->companies->id_company,'id_company');
		}
	}
	
	
	function _pop_up_upload_particulier()
	{
		// Chargement des datas
		$this->clients = $this->loadData('clients');
		
		//Recuperation des element de traductions
		$this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2',$this->language,$this->App);
		
		// On recupere les client
		if(isset($this->params[0]) && $this->clients->get($this->params[0],'hash'))
		{
		
		}
	}
	
	function _pop_up_upload_particulier_modif()
	{
		// Chargement des datas
		$this->clients = $this->loadData('clients');
		
		//Recuperation des element de traductions
		$this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2',$this->language,$this->App);
		
		// On recupere les client
		if(isset($this->params[0]) && $this->clients->get($this->params[0],'id_client'))
		{
		
		}
	}
	
	function _pop_up_upload_company()
	{
		// Chargement des datas
		$this->clients = $this->loadData('clients');
		$this->companies = $this->loadData('companies');
		
		//Recuperation des element de traductions
		$this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2',$this->language,$this->App);
		
		// On recupere les client
		if(isset($this->params[0]) && $this->clients->get($this->params[0],'hash'))
		{
			$this->companies->get($this->clients->id_client,'id_client_owner');
		}
	}
	
	function _pop_up_upload_company_modif()
	{
		// Chargement des datas
		$this->clients = $this->loadData('clients');
		$this->companies = $this->loadData('companies');
		
		//Recuperation des element de traductions
		$this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2',$this->language,$this->App);
		
		// On recupere les client
		if(isset($this->params[0]) && $this->clients->get($this->params[0],'id_client'))
		{
			$this->companies->get($this->clients->id_client,'id_client_owner');
		}
	}

	
	function _pop_up_mdp()
	{
		//Recuperation des element de traductions
		$this->lng['pop-up-mdp'] = $this->ln->selectFront('pop-up-mdp',$this->language,$this->App);					
		
	}
	
	function _pop_up_qs()
	{
		//Recuperation des element de traductions
		$this->lng['etape1'] = $this->ln->selectFront('inscription-preteur-etape-1',$this->language,$this->App);
		$this->lng['preteur-synthese'] = $this->ln->selectFront('preteur-synthese',$this->language,$this->App);
		if(!$this->clients->checkAccess())
		{
			header('Location:'.$this->lurl);
			die;
		}
		
	}
	
	function _pop_up_modifier()
	{
		$this->lng['create-project'] = $this->ln->selectFront('emprunteur-create-project',$this->language,$this->App);
		
		// Chargement des datas
		$this->projects = $this->loadData('projects');
		if(isset($this->params[0]) && $this->projects->get($this->params[0],'id_project'))
		{
			
		}
	}
	
	function _pop_up_upload_mandat()
	{
		//Recuperation des element de traductions
		$this->lng['profile'] = $this->ln->selectFront('preteur-profile',$this->language,$this->App);
		
		if(isset($this->params[0]) && $this->params[0] == 2)$this->urlRedirect = $this->lurl.'/unilend_emprunteur/';
		else $this->urlRedirect = $this->lurl.'/profile/2';
		
		
		
	}
	
	function _pop_up_upload_pouvoir()
	{
		//Recuperation des element de traductions
		$this->lng['emprunteur-projects'] = $this->ln->selectFront('emprunteur-projects',$this->language,$this->App);
	}
	
	function _pop_up_fast_pret()
	{
		//Recuperation des element de traductions
		$this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets',$this->language,$this->App);
		
		// Chargement des datas
		$this->projects = $this->loadData('projects');
		$this->bids = $this->loadData('bids');
		
		
		if(isset($this->params[0]) && $this->projects->get($this->params[0],'id_project'))
		{
			// Pret min
			$this->settings->get('Pret min','type');
			$this->pretMin = $this->settings->value;
			
			// la sum des encheres
			$this->soldeBid = $this->bids->getSoldeBid($this->projects->id_project);
			
			$this->txLenderMax = '10.10';
			if($this->soldeBid >= $this->projects->amount)
			{
				$this->lEnchereRate = $this->bids->select('id_project = '.$this->projects->id_project,'rate ASC,added ASC');
				$leSoldeE = 0;
				foreach($this->lEnchereRate as $k => $e)
				{
					// on parcour les encheres jusqu'au montant de l'emprunt
					if($leSoldeE < $this->projects->amount)
					{
						// le montant preteur (x100)
						$amount = $e['amount'];
						
						// le solde total des encheres
						$leSoldeE += ($e['amount']/100);
						$this->txLenderMax = $e['rate'];
					}
				}
			}
			
			// on génère un token
			$this->tokenBid = sha1('tokenBid-'.time().'-'.$this->clients->id_client);
			$_SESSION['tokenBid'] = $this->tokenBid;
		}
	}
	
	function _pop_valid_pret()
	{
		//Recuperation des element de traductions
		$this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets',$this->language,$this->App);
		
		// Chargement des datas
		$this->projects = $this->loadData('projects');
		if(isset($this->params[0]) && $this->projects->get($this->params[0],'id_project'))
		{
			// Pret min
			$this->settings->get('Pret min','type');
			$this->pretMin = $this->settings->value;
			
			// on génère un token
			$this->tokenBid = sha1('tokenBid-'.time().'-'.$this->clients->id_client);
			$_SESSION['tokenBid'] = $this->tokenBid;
			
			
			
			
			
		}
	}
	
	function _pop_up_alerte_retrait()
	{
		if(!$this->clients->checkAccess()){
			header('Location:'.$this->lurl);
			die;
		}
		
		//Recuperation des element de traductions
		$this->lng['preteur-alimentation'] = $this->ln->selectFront('preteur-alimentation',$this->language,$this->App);
		$this->clients_status = $this->loadData('clients_status');
		
		// statut client
		$this->clients_status->getLastStatut($this->clients->id_client);
		
	}
	
	
	function _pop_up_cgv()
	{
		// Recuperation du bloc nos-partenaires
		$this->blocs->get('cgv','slug');
		$lElements = $this->blocs_elements->select('id_bloc = '.$this->blocs->id_bloc.' AND id_langue = "'.$this->language.'"');
		foreach($lElements as $b_elt)
		{
			$this->elements->get($b_elt['id_element']);
			$this->bloc_cgv[$this->elements->slug] = $b_elt['value'];
			$this->bloc_cgvComplement[$this->elements->slug] = $b_elt['complement'];	
		}
		
		$this->lng['preteur-profile'] = $this->ln->selectFront('preteur-profile',$this->language,$this->App);
		
		//Affichage de la popup de CGV si on a pas encore valide 	

		// cgu societe
		if(in_array($this->clients->type,array(2,4))){
			$this->settings->get('Lien conditions generales inscription preteur societe','type');
			$this->lienConditionsGenerales_header = $this->settings->value;
		}
		// cgu particulier
		else{
			$this->settings->get('Lien conditions generales inscription preteur particulier','type');
			$this->lienConditionsGenerales_header = $this->settings->value;
		}		
		
	}
}