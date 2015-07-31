<?php

class commandesController extends bootstrap
{
	var $Command;
	
	function commandesController(&$command,$config,$app)
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
		$this->users->checkAccess('commandes');
		
		// Activation du menu
		$this->menu_admin = 'commandes';
	}
	
	function _boxSearch()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->lurl;
	}
	
	function _details()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->lurl;
		
		// Declaration des classes
		$this->transactions = $this->loadData('transactions');
		$this->transactions_produits = $this->loadData('transactions_produits');
		$this->transactions_cadeaux = $this->loadData('transactions_cadeaux');
		$this->fdp_type = $this->loadData('fdp_type');
		$this->clients_adresses = $this->loadData('clients_adresses');
		
		// Recuperation des infos de la commandes
		$this->transactions->get($this->params[0],'id_transaction');
		
		// Recuperation de la liste des produits de la transaction
		$this->lProduits = $this->transactions_produits->select('id_transaction = '.$this->transactions->id_transaction);
		
		// Recuperation de la liste des cadeaux et ou echantillons de la transaction
		$this->lKdos = $this->transactions_cadeaux->select('id_transaction = '.$this->transactions->id_transaction);
	}
	
	function _search()
	{
		// Declaration des classes
		$this->transactions = $this->loadData('transactions');
		$this->clients = $this->loadData('clients');
		
		// Resultat de la recherche
		if(isset($_POST['form_search_cmd']))
		{		
			$this->lCommandes = $this->transactions->searchCommandes($_POST['reference'],$_POST['nom'],$_POST['email'],$_POST['prenom'],$this->dates->formatDateFrToMysql($_POST['from']),$this->dates->formatDateFrToMysql($_POST['to']));
		}
		else
		{
			// Renvoi sur la liste des commandes
			header('Location:'.$this->lurl.'/commandes');
			die;
		}
	}
	
	function _default()
	{
		// Declaration des classes
		$this->transactions = $this->loadData('transactions');
		$this->clients = $this->loadData('clients');
		
		// Recuperation des commandes en cours de traitement
		$this->lCommandes = $this->transactions->select('etat < 2 AND status = 1','etat ASC,date_transaction DESC');
		
		// Traitements
		if(isset($this->params[0]) && $this->params[0] == 'aCommande')
		{
			// Recuperation des infos du transaction
			$this->transactions->get($this->params[1],'id_transaction');			
			$this->transactions->etat = 3;
			$this->transactions->update();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Annulation d\'une commande';
			$_SESSION['freeow']['message'] = 'La commande a bien &eacute;t&eacute; annul&eacute;e !';
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/commandes');
			die;
		}
	}
}