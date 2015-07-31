<?php 

class promotionsController extends bootstrap
{
	var $Command;
	
	function promotionsController(&$command,$config,$app)
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
		$this->users->checkAccess('boutique');
		
		// Activation du menu
		$this->menu_admin = 'boutique';
	}
	
	function _add()
	{
		// Chargement des datas
		$this->groupes = $this->loadData('groupes');
		$this->produits_elements = $this->loadData('produits_elements');
		$this->produits = $this->loadData('produits',array('url'=>$this->url,'surl'=>$this->surl,'produits_elements'=>$this->produits_elements,'upload'=>$this->upload,'spath'=>$this->spath));		
		$this->promotions = $this->loadData('promotions');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation de l'id parent des categories
		$this->settings->get('Categories','type');
		$this->id_categorie = $this->settings->value;
		
		// Recuperation de la liste des categories
		$this->lTree = $this->tree->listChilds($this->id_categorie,'-',array(),$this->language);
		
		// Recuperation de la liste des produits
		$this->lProduits = $this->produits->selectProducts($this->language);
		
		// Recuperer la liste des groupes
		$this->lGroupes = $this->groupes->select('status = 1','nom ASC');
		
		// Recuperer la liste des codes temoins
		$this->lCodes = $this->promotions->select('status = 2','code ASC');
	}
	
	function _edit()
	{		
		// Chargement des datas
		$this->promotions = $this->loadData('promotions');
		$this->groupes = $this->loadData('groupes');
		$this->produits_elements = $this->loadData('produits_elements');
		$this->produits = $this->loadData('produits',array('url'=>$this->url,'surl'=>$this->surl,'produits_elements'=>$this->produits_elements,'upload'=>$this->upload,'spath'=>$this->spath));
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation de l'id parent des categories
		$this->settings->get('Categories','type');
		$this->id_categorie = $this->settings->value;
		
		// Recuperation de la liste des categories
		$this->lTree = $this->tree->listChilds($this->id_categorie,'-',array(),$this->language);
		
		// Recuperation de la liste des produits
		$this->lProduits = $this->produits->selectProducts($this->language);
		
		// Recuperer la liste des groupes
		$this->lGroupes = $this->groupes->select('status = 1','nom ASC');
		
		// Recuperation des infos du code promo
		$this->promotions->get($this->params[0],'id_code');
		
		// Recuperer la liste des codes temoins
		$this->lCodes = $this->promotions->select('status = 2','code ASC');
		
		// Recuperation des Categorie du code
		$this->lTreeOn = explode(',',$this->promotions->id_tree);
		
		// Recuperation des produits du code
		$this->lProduitsOn = explode(',',$this->promotions->id_produit);
		
		// Recuperation des Categorie du code
		$this->lTree2On = explode(',',$this->promotions->id_tree2);
		
		// Recuperation des produits du code
		$this->lProduits2On = explode(',',$this->promotions->id_produit2);
	}
	
	function _default()
	{
		// Chargement des datas
		$this->promotions = $this->loadData('promotions');
		$this->spare_promos = $this->loadData('spare_promos');
		
		// Recuperation de la liste des code promo
		$this->lCodes = $this->promotions->select('','code ASC');		
		
		// Formulaire d'ajout d'un code promo
		if(isset($_POST['form_add_promo']))
		{
			$this->promotions->type = $_POST['type'];
			$this->promotions->code = $_POST['code'];
			$this->promotions->from = $this->dates->formatDateFrToMysql($_POST['from']);
			$this->promotions->to = $this->dates->formatDateFrToMysql($_POST['to']);
			$this->promotions->value = $_POST['value'];
			$this->promotions->seuil = $_POST['seuil'];
			$this->promotions->fdp = $_POST['fdp'];
			$this->promotions->id_tree = (($_POST['id_tree'] != '' && count($_POST['id_tree'])>0)?implode(',',$_POST['id_tree']):'');
			$this->promotions->id_produit = (($_POST['id_produit'] != '' && count($_POST['id_produit'])>0)?implode(',',$_POST['id_produit']):'');
			$this->promotions->id_groupe = $_POST['id_groupe'];
			$this->promotions->id_produit_kdo = $_POST['id_produit_kdo'];
			$this->promotions->nb_utilisations = $_POST['nb_utilisations'];
			$this->promotions->nb_minimum = $_POST['nb_minimum'];
			$this->promotions->plus_cher = $_POST['plus_cher'];
			$this->promotions->moins_cher = $_POST['moins_cher'];
			$this->promotions->duree = $_POST['duree'];
			$this->promotions->status = $_POST['status'];
			$this->promotions->id_tree2 = (($_POST['id_tree2'] != '' && count($_POST['id_tree2'])>0)?implode(',',$_POST['id_tree2']):'');
			$this->promotions->id_produit2 = (($_POST['id_produit2'] != '' && count($_POST['id_produit2'])>0)?implode(',',$_POST['id_produit2']):'');
			$this->promotions->nb_minimum2 = $_POST['nb_minimum2'];
			$this->promotions->id_promo = $_POST['id_promo'];
			$this->promotions->premiere_cmde = $_POST['premiere_cmde'];
			$this->promotions->create();
			
			// On vide la table des codes auto
			$this->spare_promos->jeFaisLeMenache();
			
			// On liste les codes promos qui sont en auto
			$lCodes = $this->promotions->select('status = 3');
			
			// Pour cette liste on enregistre les codes auto dans le spare
			foreach($lCodes as $c)
			{
				$this->spare_promos->id_code = $c['id_code'];
				$this->spare_promos->code_promo = $c['code'];
				$this->spare_promos->create();
			}			
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'un code promo';
			$_SESSION['freeow']['message'] = 'Le code promo a bien &eacute;t&eacute; ajout&eacute; !';
			
			// Renvoi sur la liste des promotions
			header('Location:'.$this->lurl.'/promotions');
			die;
		}
		
		// Formulaire de modification d'un code promo
		if(isset($_POST['form_edit_promo']))
		{
			// Recuperation des infos du code promo
			$this->promotions->get($this->params[0],'id_code');
			
			$this->promotions->type = $_POST['type'];
			$this->promotions->code = $_POST['code'];
			$this->promotions->from = $this->dates->formatDateFrToMysql($_POST['from']);
			$this->promotions->to = $this->dates->formatDateFrToMysql($_POST['to']);
			$this->promotions->value = $_POST['value'];
			$this->promotions->seuil = $_POST['seuil'];
			$this->promotions->fdp = $_POST['fdp'];
			$this->promotions->id_tree = (($_POST['id_tree'] != '' && count($_POST['id_tree'])>0)?implode(',',$_POST['id_tree']):'');
			$this->promotions->id_produit = (($_POST['id_produit'] != '' && count($_POST['id_produit'])>0)?implode(',',$_POST['id_produit']):'');
			$this->promotions->id_groupe = $_POST['id_groupe'];
			$this->promotions->id_produit_kdo = $_POST['id_produit_kdo'];
			$this->promotions->nb_utilisations = $_POST['nb_utilisations'];
			$this->promotions->nb_minimum = $_POST['nb_minimum'];
			$this->promotions->plus_cher = $_POST['plus_cher'];
			$this->promotions->moins_cher = $_POST['moins_cher'];
			$this->promotions->duree = $_POST['duree'];
			$this->promotions->status = $_POST['status'];	
			$this->promotions->id_tree2 = (($_POST['id_tree2'] != '' && count($_POST['id_tree2'])>0)?implode(',',$_POST['id_tree2']):'');
			$this->promotions->id_produit2 = (($_POST['id_produit2'] != '' && count($_POST['id_produit2'])>0)?implode(',',$_POST['id_produit2']):'');
			$this->promotions->nb_minimum2 = $_POST['nb_minimum2'];
			$this->promotions->id_promo = $_POST['id_promo'];
			$this->promotions->premiere_cmde = $_POST['premiere_cmde'];	
			$this->promotions->update();
			
			// On vide la table des codes auto
			$this->spare_promos->jeFaisLeMenache();
			
			// On liste les codes promos qui sont en auto
			$lCodes = $this->promotions->select('status = 3');
			
			// Pour cette liste on enregistre les codes auto dans le spare
			foreach($lCodes as $c)
			{
				$this->spare_promos->id_code = $c['id_code'];
				$this->spare_promos->code_promo = $c['code'];
				$this->spare_promos->create();
			}
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Modification d\'un code promo';
			$_SESSION['freeow']['message'] = 'Le code promo a bien &eacute;t&eacute; modifi&eacute; !';
			
			// Renvoi sur la liste des settings
			header('Location:'.$this->lurl.'/promotions');
			die;
		}
		
		// Suppression d'un code promo
		if(isset($this->params[0]) && $this->params[0] == 'delete')
		{
			// Recuperation des infos du code promo
			$this->promotions->get($this->params[1],'id_code');
			
			if($this->promotions->status != 2)
			{
				$this->promotions->delete($this->params[1],'id_code');
			}
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Suppression d\'un code promo';
			$_SESSION['freeow']['message'] = 'Le code promo a bien &eacute;t&eacute; supprim&eacute; !';	
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/promotions');
			die;
		}
		
		// Modification du status d'un code promo
		if(isset($this->params[0]) && $this->params[0] == 'status')
		{
			$this->promotions->get($this->params[1],'id_code');
			
			if($this->promotions->status != 2)
			{
				$this->promotions->status = ($this->params[2]==1?0:1);
				$this->promotions->update();
			}
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Statut d\'un code promo';
			$_SESSION['freeow']['message'] = 'Le statut a bien &eacute;t&eacute; modifi&eacute; !';
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/promotions');
			die;
		}		
	}	
}