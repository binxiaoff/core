<?php 

class blocsController extends bootstrap
{
	var $Command;
	
	function blocsController(&$command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;

		// Controle d'acces à la rubrique
		$this->users->checkAccess('edition');
		
		// Activation du menu
		$this->menu_admin = 'edition';
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
		$this->blocs = $this->loadData('blocs');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation des infos de la personne
		$this->blocs->get($this->params[0],'id_bloc');
	}
	
	function _default()
	{
		// Chargement des datas
		$this->blocs = $this->loadData('blocs');
		$this->blocs_templates = $this->loadData('blocs_templates');
		$this->elements = $this->loadData('elements');
		
		// Recuperation de la liste des blocs
		$this->lBlocs = $this->blocs->select('','name ASC');
		
		// Formulaire edition d'un bloc
		if(isset($_POST['form_edit_bloc']))
		{
			// Recuperation des infos du bloc
			$this->blocs->get($this->params[0],'id_bloc');		
			$this->blocs->name = $_POST['name'];
			$this->blocs->status = $_POST['status'];
			$this->blocs->update();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Modification d\'un bloc';
			$_SESSION['freeow']['message'] = 'Le bloc a bien &eacute;t&eacute; modifi&eacute; !';
    		
    		// Renvoi sur l'edition du template
			header('Location:'.$this->lurl.'/blocs');
			die;
		}
		
		// Formulaire d'ajout d'un bloc
		if(isset($_POST['form_add_bloc']))
		{
			$this->blocs->name = $_POST['name'];	
			$this->blocs->slug = ($_POST['slug'] != ''?$this->bdd->generateSlug($_POST['slug']):$this->bdd->generateSlug($_POST['name']));
			$this->blocs->status = $_POST['status'];
			$this->blocs->id_bloc = $this->blocs->create();
			
			// Si le fichier n'existe pas on le créé
			if(!file_exists($this->path.'apps/default/views/blocs/'.$this->blocs->slug.'.php'))
			{
				// Remplissage du bloc				
    			$modifs_elements = "";
				$modifs_elements .= "<strong>Nom du Bloc : ".$this->blocs->name."</strong><br /><br />\r\n\r\n";
				
				$fp = fopen($this->path.'apps/default/views/blocs/'.$this->blocs->slug.'.php', "wb");
				fputs ($fp, $modifs_elements);
				fclose($fp);
				
				chmod($this->path.'apps/default/views/blocs/'.$this->blocs->slug.'.php', 0777);
			}
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'un bloc';
			$_SESSION['freeow']['message'] = 'Le bloc a bien &eacute;t&eacute; ajout&eacute; !';
			
			// Renvoi sur la home des blocs
			header('Location:'.$this->lurl.'/blocs');
			die;
		}
		
		// Modification du statut et suppression
		if(isset($this->params[0]) && $this->params[0] != '')
		{
			switch($this->params[0])
			{
				case 'status':
				
					$this->blocs->get($this->params[1],'id_bloc');	
					$this->blocs->status = ($this->params[2] == 0?1:0);					
					$this->blocs->update();
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Statut d\'un bloc';
					$_SESSION['freeow']['message'] = 'Le statut du bloc a bien &eacute;t&eacute; modifi&eacute; !';
					
					header('location:'.$this->lurl.'/blocs');
					die;
					
				break;
				
				case 'delete':
				
					$this->blocs->get($this->params[1],'id_bloc');
					
					// On supprime le fichier
					@unlink($this->path.'apps/default/views/blocs/'.$this->blocs->slug.'.php');
					
					// On supprime les données
					$this->blocs_templates->delete($this->params[1],'id_bloc');
					$this->blocs_elements->delete($this->params[1],'id_bloc');
					$this->elements->delete($this->params[1],'id_bloc');
					$this->blocs->delete($this->params[1],'id_bloc');
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Suppression d\'un bloc';
					$_SESSION['freeow']['message'] = 'Le bloc a bien &eacute;t&eacute; supprim&eacute; !';
					
					header('location:'.$this->lurl.'/blocs');
					die;
					
				break;		
			}
		}
	}
	
	function _editElement()
	{
		// Chargement des datas
		$this->elements = $this->loadData('elements');
		$this->blocs = $this->loadData('blocs');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation des infos de la personne
		$this->elements->get($this->params[0],'id_element');
		
		// Recuperation des infos du bloc
		$this->blocs->get($this->params[1],'id_bloc');
	}
	
	function _addElement()
	{
		// Chargement des datas
		$this->blocs = $this->loadData('blocs');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation des infos du bloc
		$this->blocs->get($this->params[0],'id_bloc');
	}
	
	function _elements()
	{
		// Chargement des datas
		$this->blocs = $this->loadData('blocs');
		$this->elements = $this->loadData('elements');
		
		// Recuperation des infos du bloc
		$this->blocs->get($this->params[0],'id_bloc');
		
		// Recuperation de la liste des elements du bloc
		$this->lElements = $this->elements->select('id_bloc = "'.$this->params[0].'" AND id_bloc != 0','ordre ASC');
		
		if(isset($_POST['form_add_element']))
		{
			$this->elements->id_bloc = $_POST['id_bloc'];
			$this->elements->name = $_POST['name'];
			$this->elements->slug = ($_POST['slug'] != ''?$this->bdd->generateSlug($_POST['slug']):$this->bdd->generateSlug($_POST['name']));			
			$this->elements->ordre = $this->elements->getLastPosition($_POST['id_bloc'],'id_bloc') + 1;
			$this->elements->type_element = $_POST['type_element'];
			$this->elements->status = $_POST['status'];
			$this->elements->create();
			
			// On reordonne le bloc
			$this->elements->reordre($_POST['id_bloc'],'id_bloc');
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'un &eacute;l&eacute;ment';
			$_SESSION['freeow']['message'] = 'L\'&eacute;l&eacute;ment a bien &eacute;t&eacute; ajout&eacute; !';
			
			// Renvoi sur l'edition du bloc
			header('Location:'.$this->lurl.'/blocs/elements/'.$_POST['id_bloc']);
			die;
		}
		
		if(isset($_POST['form_edit_element']))
		{
			$this->elements->get($_POST['id_element'],'id_element');			
			$this->elements->name = $_POST['name'];
			$this->elements->type_element = $_POST['type_element'];
			$this->elements->status = $_POST['status'];
			$this->elements->update();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Modification d\'un &eacute;l&eacute;ment';
			$_SESSION['freeow']['message'] = 'L\'&eacute;l&eacute;ment a bien &eacute;t&eacute; modifi&eacute; !';
			
			header('location:'.$this->lurl.'/blocs/elements/'.$this->elements->id_bloc);
			die;
		}
		
		if(isset($this->params[1]) && $this->params[1] != '')
		{
			switch($this->params[1])
			{
				case 'up':
					
					$this->elements->moveUp($this->params[2],$this->params[0],'id_bloc');
					header('location:'.$this->lurl.'/blocs/elements/'.$this->params[0]);
					die;
					
				break;
				
				case 'down':
					
					$this->elements->moveDown($this->params[2],$this->params[0],'id_bloc');
					header('location:'.$this->lurl.'/blocs/elements/'.$this->params[0]);
					die;
					
				break;
				
				case 'status':
				
					$this->elements->get($this->params[2],'id_element');	
					$this->elements->status = ($this->params[3] == 0?1:0);					
					$this->elements->update();
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Statut d\'un &eacute;l&eacute;ment';
					$_SESSION['freeow']['message'] = 'Le statut de l\'&eacute;l&eacute;ment a bien &eacute;t&eacute; modifi&eacute; !';
					
					header('location:'.$this->lurl.'/blocs/elements/'.$this->params[0]);
					die;
					
				break;
				
				case 'delete':
					
					$this->elements->delete($this->params[2],'id_element');
					$this->tree_elements->delete($this->params[2],'id_element');
					$this->blocs_elements->delete($this->params[2],'id_element');
					$this->elements->reordre($this->params[0],'id_bloc');
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Suppression d\'un &eacute;l&eacute;ment';
					$_SESSION['freeow']['message'] = 'L\'&eacute;l&eacute;ment a bien &eacute;t&eacute; supprim&eacute; !';
			
					header('location:'.$this->lurl.'/blocs/elements/'.$this->params[0]);
					die;
					
				break;							
			}
		}
	}
	
	function _edition()
	{
		// Chargement des datas
		$this->blocs = $this->loadData('blocs');
		
		// Recuperation des infos du bloc
		$this->blocs->get($this->params[0],'slug');
		
		// Preparation de la source
		$this->edit = implode('',file($this->path.'apps/default/views/blocs/'.$this->params[0].'.php'));
		$this->edit = htmlentities($this->edit);
		
		if(isset($_POST['form_edition_bloc']))
		{
			$filecontent = stripslashes($_POST["filecontent"]);

        	if(is_writeable($this->path.'apps/default/views/blocs/'.$this->params[0].'.php'))
			{
				$fp = fopen($this->path.'apps/default/views/blocs/'.$this->params[0].'.php', "wb");
				fputs ($fp, $filecontent);
				fclose($fp);
			}
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Edition d\'un bloc';
			$_SESSION['freeow']['message'] = 'Le bloc a bien &eacute;t&eacute; &eacute;dit&eacute; !';
			
			header('location:'.$this->lurl.'/blocs');
			die;
		}		
	}
	
	function _modifier()
	{
		// Chargement des datas
		$this->blocs = $this->loadData('blocs');
		$this->elements = $this->loadData('elements');
		
		// Recuperation des infos du bloc
		$this->blocs->get($this->params[0],'id_bloc');
		
		// Recuperation de l'arbo pour select
		$this->lTree = $this->tree->listChilds(0,'-',array(),$this->language);	
		
		if(isset($_POST['form_edit_bloc']))
		{
			foreach($this->lLangues as $key => $lng)
			{
				// Enregistrement des values des elements du template
				$this->blocs_elements->delete($this->blocs->id_bloc,'id_langue = "'.$key.'" AND id_bloc');
				
				// Recuperation des elements du template
				$this->lElements = $this->elements->select('status = 1 AND id_bloc != 0 AND id_bloc = '.$this->blocs->id_bloc,'ordre ASC');
			
				foreach($this->lElements as $element)
				{
					$this->tree->handleFormElement($this->blocs->id_bloc,$element,'bloc',$key);
				}
			}
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Modification d\'un bloc';
			$_SESSION['freeow']['message'] = 'Le bloc a bien &eacute;t&eacute; modifi&eacute; !';
    		
    		// Renvoi sur l'edition du template
			header('Location:'.$this->lurl.'/blocs');
			die;
		}
	}
}