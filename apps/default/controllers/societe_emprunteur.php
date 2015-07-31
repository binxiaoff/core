<?php

class societe_emprunteurController extends bootstrap
{
	var $Command;
	
	function societe_emprunteurController($command,$config,$app)
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
		
		
		$this->page = 'societe';
		
	}
	
	function _default()
	{
		//Recuperation des element de traductions
		$this->lng['etape1'] = $this->ln->selectFront('inscription-preteur-etape-1',$this->language,$this->App);
		$this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2',$this->language,$this->App);
		$this->lng['emprunteur-societe'] = $this->ln->selectFront('emprunteur-societe',$this->language,$this->App);
		
		// Chargement des datas
		$this->companies = $this->loadData('companies');
		
		$this->companies->get($this->clients->id_client,'id_client_owner');
		
		// Liste deroulante conseil externe de l'entreprise
		$this->settings->get("Liste deroulante conseil externe de l'entreprise",'type');
		$this->conseil_externe = $this->settings->value;
		$this->conseil_externe = explode(';',$this->conseil_externe);
		
		
		if(isset($_POST['send_form_etape1']))
		{
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
			$this->clients_history_actions->histo(10,'info perso emprunteur',$this->clients->id_client,$serialize);
			////////////////
			
			$this->form_ok = true;
			
			$this->companies->name = $_POST['raison_sociale_inscription'];
			$this->companies->forme = $_POST['forme_juridique_inscription'];
			$this->companies->capital = str_replace(' ','',$_POST['capital_social_inscription']);
			$this->companies->siren = $_POST['siren_inscription'];
			$this->companies->phone = $_POST['phone_inscription'];
			
			////////////////////////////////////
			// On verifie meme adresse ou pas //
			////////////////////////////////////
			if($_POST['mon-addresse'] != false)
			$this->companies->status_adresse_correspondance = '1'; // la meme
			else
			$this->companies->status_adresse_correspondance = '0'; // pas la meme
			
			// adresse fiscal (siege de l'entreprise)
			$this->companies->adresse1 = $_POST['adresse_inscription'];
			$this->companies->city = $_POST['ville_inscription'];
			$this->companies->zip = $_POST['postal'];
			
			// pas la meme
			if($this->companies->status_adresse_correspondance == 0)
			{
				// adresse client
				$this->clients_adresses->adresse1 = $_POST['adress2'];
				$this->clients_adresses->ville = $_POST['ville2'];
				$this->clients_adresses->cp = $_POST['postal2'];
			}
			// la meme
			else
			{
				// adresse client
				$this->clients_adresses->adresse1 = $_POST['adresse_inscription'];
				$this->clients_adresses->ville = $_POST['ville_inscription'];
				$this->clients_adresses->cp = $_POST['postal'];
			}
			////////////////////////////////////////
			
			$this->companies->status_client = $_POST['enterprise']; // radio 1 dirigeant 2 pas dirigeant 3 externe
			
			
			//extern ou non dirigeant
			if($this->companies->status_client == 2 || $this->companies->status_client == 3)
			{
				$this->companies->civilite_dirigeant = $_POST['genre2'];
				$this->companies->nom_dirigeant = ucfirst(strtolower($_POST['nom2_inscription']));
				$this->companies->prenom_dirigeant = ucfirst(strtolower($_POST['prenom2_inscription']));
				$this->companies->fonction_dirigeant = $_POST['fonction2_inscription'];
				$this->companies->email_dirigeant = $_POST['email2_inscription'];
				$this->companies->phone_dirigeant = $_POST['phone_new2_inscription'];
				
				// externe
				if($this->companies->status_client == 3)
				{
					$this->companies->status_conseil_externe_entreprise = $_POST['external-consultant'];
					$this->companies->preciser_conseil_externe_entreprise = $_POST['autre_inscription'];
				}
			}
			else
			{
				$this->companies->civilite_dirigeant = '';
				$this->companies->nom_dirigeant = '';
				$this->companies->prenom_dirigeant = '';
				$this->companies->fonction_dirigeant = '';
				$this->companies->email_dirigeant = '';
				$this->companies->phone_dirigeant = '';
				
				$this->companies->status_conseil_externe_entreprise = '';
				$this->companies->preciser_conseil_externe_entreprise = '';
			}
			
			//raison_sociale_inscription
			if(!isset($_POST['raison_sociale_inscription']) || $_POST['raison_sociale_inscription'] == $this->lng['etape1']['raison-sociale'])
			{
				$this->form_ok = false;
			}
			//forme_juridique_inscription
			if(!isset($_POST['forme_juridique_inscription']) || $_POST['forme_juridique_inscription'] == $this->lng['etape1']['forme-juridique'])
			{
				$this->form_ok = false;
			}
			//capital_social_inscription
			if(!isset($_POST['capital_social_inscription']) || $_POST['capital_social_inscription'] == $this->lng['etape1']['capital-sociale'])
			{
				$this->form_ok = false;
			}
			//siren_inscription
			if(!isset($_POST['siren_inscription']) || $_POST['siren_inscription'] == $this->lng['etape1']['siren'])
			{
				$this->form_ok = false;
			}
			//phone_inscription
			if(!isset($_POST['phone_inscription']) || $_POST['phone_inscription'] == $this->lng['etape1']['telephone'])
			{
				$this->form_ok = false;
			}
			//adresse_inscription
			if(!isset($_POST['adresse_inscription']) || $_POST['adresse_inscription'] == $this->lng['etape1']['adresse'])
			{
				$this->form_ok = false;
			}
			//ville_inscription
			if(!isset($_POST['ville_inscription']) || $_POST['ville_inscription'] == $this->lng['etape1']['ville'])
			{
				$this->form_ok = false;
			}
			//postal
			if(!isset($_POST['postal']) || $_POST['postal'] == $this->lng['etape1']['code-postal'])
			{
				$this->form_ok = false;
			}
			
			// pas la meme
			if($this->companies->status_adresse_correspondance == 0)
			{
				// adresse client
				if(!isset($_POST['adress2']) || $_POST['adress2'] == $this->lng['etape1']['adresse'])
				{
					$this->form_ok = false;
				}
				if(!isset($_POST['ville2']) || $_POST['ville2'] == $this->lng['etape1']['ville'])
				{
					$this->form_ok = false;
				}
				if(!isset($_POST['postal2']) || $_POST['postal2'] == $this->lng['etape1']['postal'])
				{
					$this->form_ok = false;
				}
			}
			
			//extern ou non dirigeant
			if($this->companies->status_client == 2 || $this->companies->status_client == 3)
			{
				
				if(!isset($_POST['nom2_inscription']) || $_POST['nom2_inscription'] == $this->lng['etape1']['nom'])
				{
					$this->form_ok = false;
				}
				if(!isset($_POST['prenom2_inscription']) || $_POST['prenom2_inscription'] == $this->lng['etape1']['prenom'])
				{
					$this->form_ok = false;
				}
				if(!isset($_POST['fonction2_inscription']) || $_POST['fonction2_inscription'] == $this->lng['etape1']['fonction'])
				{
					$this->form_ok = false;
				}
				if(!isset($_POST['email2_inscription']) || $_POST['email2_inscription'] == $this->lng['etape1']['email'])
				{
					$this->form_ok = false;
				}
				elseif(isset($_POST['email2_inscription']) && $this->ficelle->isEmail($_POST['email2_inscription']) == false)
				{
					$this->form_ok = false;
				}
				if(!isset($_POST['phone_new2_inscription']) || $_POST['phone_new2_inscription'] == $this->lng['etape1']['telephone'])
				{
					$this->form_ok = false;
				}
				
				// externe
				if($this->companies->status_client == 3)
				{
					if(!isset($_POST['external-consultant']) || $_POST['external-consultant'] == '')
					{
						$this->form_ok = false;
					}
					if(!isset($_POST['autre_inscription']) || $_POST['autre_inscription'] == $this->lng['etape1']['autre'])
					{
						$this->form_ok = false;
					}
					
				}
			}
			
			// Si form societe ok
			if($this->form_ok == true)
			{

				// on met a jour l'entreprise
				$this->companies->update();
				
				// On met a jour l'adresse client
				$this->clients_adresses->update();
				
				

			}
		}
		
	}
	
}