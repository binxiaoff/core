<?php 

class settingsController extends bootstrap
{
	var $Command;
	
	function settingsController(&$command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
	}
	
	function _default()
	{
		// Controle d'acces à la rubrique
		$this->users->checkAccess('configuration');
		
		// Activation du menu
		$this->menu_admin = 'configuration';
		
		// Chargement des datas
		$this->templates = $this->loadData('templates');
		
		// Formulaire d'ajout d'un settings
		if(isset($_POST['form_add_settings']))
		{
			$this->settings->type = $_POST['type'];
			$this->settings->value = $_POST['value'];
			$this->settings->id_template = $_POST['id_template'];
			$this->settings->status = $_POST['status'];
			$this->settings->id_setting = $this->settings->create();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'un param&egrave;tre';
			$_SESSION['freeow']['message'] = 'Le param&egrave;tre a bien &eacute;t&eacute; ajout&eacute; !';
			
			// Renvoi sur la liste des settings
			header('Location:'.$this->lurl.'/settings');
			die;
		}
		
		// Formulaire de modification d'un setting
		if(isset($_POST['form_edit_settings']))
		{
			// Recuperation des infos du setting
			$this->settings->get($this->params[0],'id_setting');
		
			$this->settings->type = $_POST['type'];
			$this->settings->value = $_POST['value'];	
			$this->settings->id_template = $_POST['id_template'];		
			$this->settings->status = ($this->settings->status == 2?2:$_POST['status']);
			$this->settings->update();
			
			
			
			// En cas de changement de tva qui passe a 20%
			if($this->settings->id_setting == 9)
			{
				$echeanciers = $this->loadData('echeanciers');
				$echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
				
				$echeanciers->onMetAjourTVA($this->settings->value);
				$echeanciers_emprunteur->onMetAjourTVA($this->settings->value);
				
				/*$lecheances = $echeanciers->select('status_emprunteur = 0');
				$lecheanciers_emprunteur = $echeanciers_emprunteur->select('status_emprunteur = 0');
				
				foreach($lecheances as $e)
				{
					$tva = ($e['commission']/100)*$this->settings->value;
					
					$echeanciers->get($e['id_echeancier'],'id_echeancier');
					$echeanciers->tva = round($tva*100,2);
					$echeanciers->update();
				}
				foreach($lecheanciers_emprunteur as $e)
				{
					$tva = ($e['commission']/100)*$this->settings->value;
					
					$echeanciers_emprunteur->get($e['id_echeancier_emprunteur'],'id_echeancier_emprunteur');
					$echeanciers_emprunteur->tva = round($tva*100,2);
					$echeanciers_emprunteur->update();
				}*/
				
			}
			
			// EQ-CRDS
			if($this->settings->id_setting == 62)
			{
				$echeanciers = $this->loadData('echeanciers');
				$preteur = $this->loadData('clients');
				$lender = $this->loadData('lenders_accounts');
				
				$lecheances = $echeanciers->select();
				
				foreach($lecheances as $e)
				{
					$lender->get($e['id_lender'],'id_lender_account');
					$preteur->get($lender->id_client_owner,'id_client');
					
					if($preteur->type == 1)
					{
						$montant_crds = ($e['interets']/100)*$this->settings->value;
						
						$echeanciers->get($e['id_echeancier'],'id_echeancier');
						$echeanciers->crds = round($montant_crds,2);
						$echeanciers->update();
					}
					
					
				}
				
			}
			
			
			
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Modification d\'un param&egrave;tre';
			$_SESSION['freeow']['message'] = 'Le param&egrave;tre a bien &eacute;t&eacute; modifi&eacute; !';
			
			// Renvoi sur la liste des zones
			header('Location:'.$this->lurl.'/settings');
			die;
		}
		
		// Suppression d'un settings
		if(isset($this->params[0]) && $this->params[0] == 'delete')
		{
			// Recuperation des infos du setting
			$this->settings->get($this->params[1],'id_setting');
			
			if($this->settings->status != 2)
			{
				$this->settings->delete($this->params[1],'id_setting');	
			}
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Suppression d\'un param&egrave;tre';
			$_SESSION['freeow']['message'] = 'Le param&egrave;tre a bien &eacute;t&eacute; supprim&eacute; !';
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/settings');
			die;
		}
		
		// Modification du status d'un settings
		if(isset($this->params[0]) && $this->params[0] == 'status')
		{
			$this->settings->get($this->params[1],'id_setting');
			
			if($this->settings->status != 2)
			{
				$this->settings->status = ($this->params[2]==1?0:1);
				$this->settings->update();
			}
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Statut d\'un param&egrave;tre';
			$_SESSION['freeow']['message'] = 'Le statut a bien &eacute;t&eacute; modifi&eacute; !';
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/settings');
			die;
		}		
		
		// Recuperation de la liste des settings
		$this->lSettings = $this->settings->select(($this->cms == 'iZinoa'?'cms = "iZinoa" || cms = ""':''),'type ASC');
	}
	
	function _edit()
	{
		// Controle d'acces à la rubrique
		$this->users->checkAccess('configuration');
		
		// Activation du menu
		$this->menu_admin = 'configuration';
		
		// Chargement des datas
		$this->templates = $this->loadData('templates');
		
		// Recuperation de la liste des templates
		$this->lTemplates = $this->templates->select('status = 1','name ASC');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation des infos de la personne
		$this->settings->get($this->params[0],'id_setting');
	}
	
	function _add()
	{
		// Controle d'acces à la rubrique
		$this->users->checkAccess('configuration');
		
		// Activation du menu
		$this->menu_admin = 'configuration';
		
		// Chargement des datas
		$this->templates = $this->loadData('templates');
		
		// Recuperation de la liste des templates
		$this->lTemplates = $this->templates->select('status = 1','name ASC');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;		
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
	}
	
	function _cache()
	{
		// Controle d'acces à la rubrique
		$this->users->checkAccess('edition');
		
		// Activation du menu
		$this->menu_admin = 'edition';
		
		// Vidage du cache
		$this->clearCache();
		
		// Mise en session du message
		$_SESSION['freeow']['title'] = 'Vider le cache';
		$_SESSION['freeow']['message'] = 'Le cache du site a bien &eacute;t&eacute; vid&eacute; !';
		
		// Renvoi sur la page arbo
		header('Location:'.$this->lurl.'/tree');
		die;
	}
	
	function _crud()
	{
		// Vidage du crud
		$handle = opendir($this->path.'data/crud/');
		
		while (false !== ($fichier = readdir($handle)))
		{
			if(($fichier != ".") && ($fichier != ".."))
			{
				unlink($this->path.'data/crud/'.$fichier);
			} 
		}
		
		// Mise en session du message
		$_SESSION['freeow']['title'] = 'Vider le CRUD';
		$_SESSION['freeow']['message'] = 'Le CRUD a bien &eacute;t&eacute; vid&eacute; !';
		
		// Renvoi sur la page arbo
		header('Location:'.$this->lurl.'/tree');
		die;
	}
}