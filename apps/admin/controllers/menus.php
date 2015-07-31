<?php

class menusController extends bootstrap
{
	var $Command;
	
	function menusController(&$command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;

		// Controle d'acces à la rubrique
		$this->users->checkAccess('edition');
		
		// Activation du menu
		$this->menu_admin = 'edition';
		
		// Definition des types d'éléments
		$this->typesElements = array('L'=>'Lien Interne','LX'=>'Lien Externe');
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
	
	function _edit()
	{
		// Chargement des datas
		$this->menus = $this->loadData('menus');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation des infos de la personne
		$this->menus->get($this->params[0],'id_menu');
	}
	
	function _default()
	{	
		// Chargement des datas
		$this->menus = $this->loadData('menus');
		$this->tree_menu = $this->loadData('tree_menu');
		
		// Recuperation de la liste des menus
		$this->lMenus = $this->menus->select('','nom ASC');
		
		// Formulaire edition d'un menu
		if(isset($_POST['form_edit_menu']))
		{
			// Recuperation des infos du menu
			$this->menus->get($this->params[0],'id_menu');		
			$this->menus->nom = $_POST['nom'];
			$this->menus->status = $_POST['status'];
			$this->menus->update();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Modification d\'un menu';
			$_SESSION['freeow']['message'] = 'Le menu a bien &eacute;t&eacute; modifi&eacute; !';
    		
    		// Renvoi sur l'edition du template
			header('Location:'.$this->lurl.'/menus');
			die;
		}
		
		// Formulaire d'ajout d'un menu
		if(isset($_POST['form_add_menu']))
		{
			$this->menus->nom = $_POST['nom'];	
			$this->menus->slug = ($_POST['slug'] != ''?$this->bdd->generateSlug($_POST['slug']):$this->bdd->generateSlug($_POST['nom']));
			$this->menus->status = $_POST['status'];
			$this->menus->id_menu = $this->menus->create();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'un menu';
			$_SESSION['freeow']['message'] = 'Le menu a bien &eacute;t&eacute; ajout&eacute; !';
			
			// Renvoi sur la home des blocs
			header('Location:'.$this->lurl.'/menus');
			die;
		}
		
		// Modification du statut et suppression
		if(isset($this->params[0]) && $this->params[0] != '')
		{
			switch($this->params[0])
			{
				case 'status':
				
					$this->menus->get($this->params[1],'id_menu');	
					$this->menus->status = ($this->params[2] == 0?1:0);					
					$this->menus->update();
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Statut d\'un menu';
					$_SESSION['freeow']['message'] = 'Le statut du menu a bien &eacute;t&eacute; modifi&eacute; !';
					
					header('location:'.$this->lurl.'/menus');
					die;
					
				break;
				
				case 'delete':
				
					$this->menus->get($this->params[1],'id_menu');
					
					// On supprime les données
					$this->tree_menu->delete(array('id_menu'=>$this->params[1]));
					$this->menus->delete($this->params[1],'id_menu');
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Suppression d\'un menu';
					$_SESSION['freeow']['message'] = 'Le menu a bien &eacute;t&eacute; supprim&eacute; !';
					
					header('location:'.$this->lurl.'/menus');
					die;
					
				break;		
			}
		}
	}
	
	function _editElement()
	{
		// Chargement des datas
		$this->tree_menu = $this->loadData('tree_menu');
		$this->menus = $this->loadData('menus');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation des infos du lien
		$this->tree_menu->get(array('id'=>$this->params[0],'id_langue'=>$this->dLanguage));
		
		// Recuperation des infos du menu
		$this->menus->get($this->params[1],'id_menu');
	}
	
	function _addElement()
	{
		// Chargement des datas
		$this->menus = $this->loadData('menus');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation des infos du menu
		$this->menus->get($this->params[0],'id_menu');
	}
	
	function _elements()
	{
		// Chargement des datas
		$this->tree_menu = $this->loadData('tree_menu');
		$this->menus = $this->loadData('menus');
		
		// Recuperation des infos du menu
		$this->menus->get($this->params[0],'id_menu');
		
		// Recuperation de la liste des elements du menu
		$this->lElements = $this->tree_menu->select('id_menu = "'.$this->params[0].'" AND id_menu != 0 AND id_langue = "'.$this->dLanguage.'"','ordre ASC');
		
		if(isset($this->params[1]) && $this->params[1] != '')
		{
			switch($this->params[1])
			{
				case 'up':
					
					$this->tree_menu->moveUp($this->params[2],$this->params[0]);
					header('location:'.$this->lurl.'/menus/elements/'.$this->params[0]);
					die;
					
				break;
				
				case 'down':
					
					$this->tree_menu->moveDown($this->params[2],$this->params[0]);
					header('location:'.$this->lurl.'/menus/elements/'.$this->params[0]);
					die;
					
				break;
				
				case 'status':
				
					foreach($this->lLangues as $key => $lng)
					{
						$this->tree_menu->get(array('id'=>$this->params[2],'id_langue'=>$key));	
						$this->tree_menu->status = ($this->params[3] == 0?1:0);					
						$this->tree_menu->update(array('id'=>$this->params[2],'id_langue'=>$key));
					}
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Statut d\'un &eacute;l&eacute;ment';
					$_SESSION['freeow']['message'] = 'Le statut de l\'&eacute;l&eacute;ment a bien &eacute;t&eacute; modifi&eacute; !';
					
					header('location:'.$this->lurl.'/menus/elements/'.$this->params[0]);
					die;
					
				break;
				
				case 'delete':
					
					$this->tree_menu->delete(array('id'=>$this->params[2]));
					$this->tree_menu->reordre($this->params[0]);
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Suppression d\'un &eacute;l&eacute;ment';
					$_SESSION['freeow']['message'] = 'L\'&eacute;l&eacute;ment a bien &eacute;t&eacute; supprim&eacute; !';
			
					header('location:'.$this->lurl.'/menus/elements/'.$this->params[0]);
					die;
					
				break;							
			}
		}
		
		if(isset($_POST['form_add_element']) && $_POST['complement'] != '')
		{
			foreach($this->lLangues as $key => $lng)
			{
				$this->tree_menu->id_menu = $_POST['id_menu'];
				$this->tree_menu->id_langue = $key;
				$this->tree_menu->nom = $_POST['nom_'.$key];
				$this->tree_menu->value = $_POST['value_'.$_POST['complement'].'_'.$key];
				$this->tree_menu->complement = $_POST['complement'];	
				$this->tree_menu->target = $_POST['target'];
				
				if($key == $this->dLanguage)
				{
					$this->current_ordre = $this->tree_menu->getLastPosition($_POST['id_menu']);
				}
					
				$this->tree_menu->ordre = $this->current_ordre;
				$this->tree_menu->status = $_POST['status_'.$key];
				
				if($key == $this->dLanguage)
				{
					$this->current_id = $this->tree_menu->getMaxId() + 1;
				}
				
				$this->tree_menu->id = $this->current_id;
				$this->tree_menu->create(array('id'=>$this->current_id,'id_langue'=>$key));
			}
			
			// On reordonne le bloc
			$this->tree_menu->reordre($_POST['id_menu']);
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'un &eacute;l&eacute;ment';
			$_SESSION['freeow']['message'] = 'L\'&eacute;l&eacute;ment a bien &eacute;t&eacute; ajout&eacute; !';
			
			// Renvoi sur l'edition du bloc
			header('Location:'.$this->lurl.'/menus/elements/'.$_POST['id_menu']);
			die;
		}
		
		if(isset($_POST['form_edit_element']) && $_POST['complement'] != '')
		{
			foreach($this->lLangues as $key => $lng)
			{
				$this->tree_menu->get(array('id'=>$_POST['id'],'id_langue'=>$key));
				$this->tree_menu->nom = $_POST['nom_'.$key];			
				$this->tree_menu->value = $_POST['value_'.$_POST['complement'].'_'.$key];
				$this->tree_menu->complement = $_POST['complement'];
				$this->tree_menu->target = $_POST['target'];	
				$this->tree_menu->status = $_POST['status_'.$key];
				$this->tree_menu->update(array('id'=>$_POST['id'],'id_langue'=>$key));
			}
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Modification d\'un &eacute;l&eacute;ment';
			$_SESSION['freeow']['message'] = 'L\'&eacute;l&eacute;ment a bien &eacute;t&eacute; modifi&eacute; !';
			
			header('location:'.$this->lurl.'/menus/elements/'.$this->tree_menu->id_menu);
			die;
		}
	}
}