<?php

class partenairesController extends bootstrap
{
	var $Command;
	
	function partenairesController(&$command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
		
		// Check de la plateforme
		if($this->cms == 'iZinoa')
		{
			// Renvoi sur la page de gestion de l'arbo
			header('Location:'.$this->lurl.'/tree');
			die;
		}
		
		// Controle d'acces à la rubrique
		$this->users->checkAccess('configuration');
		
		// Activation du menu
		$this->menu_admin = 'configuration';
	}
	
	function _types()
	{
		// Chargement des datas
		$this->partenaires_types = $this->loadData('partenaires_types');
		
		// Formulaire d'ajout d'un type de partenaire
		if(isset($_POST['form_add_type']))
		{
			$this->partenaires_types->nom = $_POST['nom'];
			$this->partenaires_types->status = $_POST['status'];
			$this->partenaires_types->id_type = $this->partenaires_types->create();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'un type de campagne';
			$_SESSION['freeow']['message'] = 'Le type a bien &eacute;t&eacute; ajout&eacute; !';
			
			// Renvoi sur la liste des types
			header('Location:'.$this->lurl.'/partenaires/types');
			die;
		}
		
		// Formulaire de modification d'un type de partenaire
		if(isset($_POST['form_edit_type']))
		{
			// Recuperation des infos du type de partenaire
			$this->partenaires_types->get($this->params[0],'id_type');
		
			$this->partenaires_types->nom = $_POST['nom'];	
			$this->partenaires_types->status = $_POST['status'];
			$this->partenaires_types->update();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Modification d\'un type de campagne';
			$_SESSION['freeow']['message'] = 'Le type a bien &eacute;t&eacute; modifi&eacute; !';
			
			// Renvoi sur la liste des types
			header('Location:'.$this->lurl.'/partenaires/types');
			die;
		}
		
		// Suppression d'un type de partenaire
		if(isset($this->params[0]) && $this->params[0] == 'delete')
		{
			// Recuperation des infos du type de partenaire
			$this->partenaires_types->delete($this->params[1],'id_type');
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Suppression d\'un type de campagne';
			$_SESSION['freeow']['message'] = 'Le type a bien &eacute;t&eacute; supprim&eacute; !';
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/partenaires/types');
			die;
		}
		
		// Modification du status d'un type de partenaire
		if(isset($this->params[0]) && $this->params[0] == 'status')
		{
			$this->partenaires_types->get($this->params[1],'id_type');
			
			$this->partenaires_types->status = ($this->params[2]==1?0:1);
			$this->partenaires_types->update();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Statut d\'un type de campagne';
			$_SESSION['freeow']['message'] = 'Le statut a bien &eacute;t&eacute; modifi&eacute; !';
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/partenaires/types');
			die;
		}		
		
		// Recuperation de la liste des type de partenaire
		$this->lTypes = $this->partenaires_types->select('','nom ASC');
	}
	
	function _editType()
	{
		// Chargement des datas
		$this->partenaires_types = $this->loadData('partenaires_types');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation des infos du type
		$this->partenaires_types->get($this->params[0],'id_type');
	}
	
	function _addType()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
	}
	
	function _default()
	{
		// Chargement des datas
		$this->partenaires = $this->loadData('partenaires');
		$this->partenaires_types = $this->loadData('partenaires_types');
		$this->promotions = $this->loadData('promotions');
		
		// Formulaire d'ajout d'un partenaire
		if(isset($_POST['form_add_part']))
		{
			$this->partenaires->nom = $_POST['nom'];
			$this->partenaires->slug = $this->bdd->generateSlug($_POST['nom']);
			$this->partenaires->id_type = $_POST['id_type'];
			$this->partenaires->id_code = $_POST['id_code'];
			$this->partenaires->status = $_POST['status'];
			$this->partenaires->id_type = $this->partenaires->create();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'une campagne';
			$_SESSION['freeow']['message'] = 'La campagne a bien &eacute;t&eacute; ajout&eacute;e !';
			
			// Renvoi sur la liste des partenaires
			header('Location:'.$this->lurl.'/partenaires');
			die;
		}
		
		// Formulaire de modification d'un partenaire
		if(isset($_POST['form_edit_part']))
		{
			// Recuperation des infos du partenaire
			$this->partenaires->get($this->params[0],'id_partenaire');
		
			$this->partenaires->nom = $_POST['nom'];
			$this->partenaires->slug = $this->bdd->generateSlug($_POST['nom']);
			$this->partenaires->id_type = $_POST['id_type'];
			$this->partenaires->id_code = $_POST['id_code'];
			$this->partenaires->status = $_POST['status'];
			$this->partenaires->update();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Modification d\'une campagne';
			$_SESSION['freeow']['message'] = 'La campagne a bien &eacute;t&eacute; modifi&eacute;e !';
			
			// Renvoi sur la liste des types
			header('Location:'.$this->lurl.'/partenaires');
			die;
		}
		
		// Suppression d'un partenaire
		if(isset($this->params[0]) && $this->params[0] == 'delete')
		{
			// Recuperation des infos du partenaire
			$this->partenaires->delete($this->params[1],'id_partenaire');
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Suppression d\'une campagne';
			$_SESSION['freeow']['message'] = 'La campagne a bien &eacute;t&eacute; supprim&eacute;e !';
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/partenaires');
			die;
		}
		
		// Modification du status d'un partenaire
		if(isset($this->params[0]) && $this->params[0] == 'status')
		{
			$this->partenaires->get($this->params[1],'id_partenaire');
			
			$this->partenaires->status = ($this->params[2]==1?0:1);
			$this->partenaires->update();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Statut d\'une campagne';
			$_SESSION['freeow']['message'] = 'Le statut a bien &eacute;t&eacute; modifi&eacute; !';
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/partenaires');
			die;
		}		
		
		// Recuperation de la liste des partenaire
		$this->lPartenaires = $this->partenaires->select('','nom ASC');
	}	
	
	function _edit()
	{
		// Chargement des datas
		$this->partenaires = $this->loadData('partenaires');
		$this->partenaires_types = $this->loadData('partenaires_types');
		$this->promotions = $this->loadData('promotions');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation des infos du type
		$this->partenaires->get($this->params[0],'id_partenaire');
		
		// Liste des types
		$this->lTypes = $this->partenaires_types->select('status = 1','nom ASC');
		
		// Liste des promotions
		$this->lPromotions	= $this->promotions->select('status = 1','code ASC');
	}
	
	function _add()
	{
		// Chargement des datas
		$this->partenaires_types = $this->loadData('partenaires_types');
		$this->promotions = $this->loadData('promotions');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Liste des types
		$this->lTypes = $this->partenaires_types->select('status = 1','nom ASC');
		
		// Liste des promotions
		$this->lPromotions	= $this->promotions->select('status = 1','code ASC');
	}
}