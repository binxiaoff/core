<?php

class zonesController extends bootstrap
{
	var $Command;
	
	function zonesController($command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
		
		// Controle d'acces à la rubrique
		$this->users->checkAccess('admin');
		
		// Activation du menu
		$this->menu_admin = 'admin';
	}
	
	function _default()
	{
		// Chargement des datas
		$this->zones = $this->loadData('zones');
		
		// Formulaire d'ajout d'une zone
		if(isset($_POST['form_add_zones']))
		{
			$this->zones->name = $_POST['name'];
			
			if($_POST['slug'] != '')
			{
				$this->zones->slug = $this->bdd->generateSlug($_POST['slug']);
			}
			else
			{
				$this->zones->slug = $this->bdd->generateSlug($_POST['name']);
			}
						
			$this->zones->status = $_POST['status'];
			$this->zones->id_zone = $this->zones->create();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'une zone';
			$_SESSION['freeow']['message'] = 'La zone a bien &eacute;t&eacute; ajout&eacute;e !';
			
			// Renvoi sur la liste des zones
			header('Location:'.$this->lurl.'/zones');
			die;
		}
		
		// Formulaire modification d'une zone	
		if(isset($_POST['form_mod_zones']))
		{
			// Recuperation des infos de la zone
			$this->zones->get($this->params[0],'id_zone');
		
			$this->zones->name = $_POST['name'];			
			$this->zones->status = $_POST['status'];
			$this->zones->update();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Modification d\'une zone';
			$_SESSION['freeow']['message'] = 'La zone a bien &eacute;t&eacute; modifi&eacute;e !';
    		
    		// Renvoi sur la liste des zones
			header('Location:'.$this->lurl.'/zones');
			die;
		}	
		
		// Suppression d'une zone
		if(isset($this->params[0]) && $this->params[0] == 'delete')
		{
			$this->zones->delete($this->params[1],'id_zone');
			$this->users_zones->delete($this->params[1],'id_zone');	
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Suppression d\'une zone';
			$_SESSION['freeow']['message'] = 'La zone a bien &eacute;t&eacute; supprim&eacute;e !';
			
			// Renvoi sur la liste des zones
			header('Location:'.$this->lurl.'/zones');
			die;
		}
		
		// Recuperation de la liste des utilisateurs
		$this->lUsers = $this->users->select('id_user != 1','name ASC');
		
		// Recuperation de la liste des zones actives
		$this->lZones = $this->zones->select(($this->cms == 'iZinoa'?'(cms = "iZinoa" || cms = "") AND status > 0':'status > 0'),'name ASC');
	}
	
	function _edit()
	{
		// Chargement des datas
		$this->zones = $this->loadData('zones');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation des infos de la personne
		$this->zones->get($this->params[0],'id_zone');
	}
	
	function _add()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
	}
}