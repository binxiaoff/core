<?php

class templatesController extends bootstrap
{
	var $Command;

	public function initialize()
	{
		parent::initialize();
		
		$this->catchAll = true;
		
		// Controle d'acces � la rubrique
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
		$this->templates = $this->loadData('templates');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation des infos de la personne
		$this->templates->get($this->params[0],'id_template');
	}
	
	function _default()
	{	
		// Chargement des datas
		$this->templates = $this->loadData('templates');
		$this->blocs_templates = $this->loadData('blocs_templates');
		$this->elements = $this->loadData('elements');
		
		// Recuperation de la liste des templates
		$this->lTemplate = $this->templates->select('type = 0','name ASC');
		
		// Formulaire de modification du template
		if(isset($_POST['form_edit_template']))
		{
			// Recuperation des infos du template
			$this->templates->get($this->params[0],'id_template');			
			$this->templates->name = $_POST['name'];
			$this->templates->status = $_POST['status'];
			$this->templates->type = 0;
			$this->templates->update();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Modification d\'un template';
			$_SESSION['freeow']['message'] = 'Le template a bien &eacute;t&eacute; modifi&eacute; !';
    		
    		// Renvoi sur l'edition du template
			header('Location:'.$this->lurl.'/templates');
			die;
		}
		
		// Formulaire d'ajout d'un template
		if(isset($_POST['form_add_template']))
		{
			$this->templates->name = $_POST['name'];
			$this->templates->slug = ($_POST['slug'] != ''?$this->bdd->generateSlug($_POST['slug']):$this->bdd->generateSlug($_POST['name']));
			$this->templates->status = $_POST['status'];
			$this->templates->type = 0;
			$this->templates->id_template = $this->templates->create();
			
			// Si le fichier n'existe pas on le cr��
			if(!file_exists($this->path.'apps/default/views/templates/'.$this->templates->slug.'.php'))
			{	
				// Creation de la vue
				$modifs_elements = "";
				$modifs_elements .= "<strong>Nom du Template : ".$this->templates->name."</strong><br /><br />\r\n\r\n";
				
				$fp = fopen($this->path.'apps/default/views/templates/'.$this->templates->slug.'.php', "wb");
				fputs ($fp, $modifs_elements);
				fclose($fp);
				
				chmod($this->path.'apps/default/views/templates/'.$this->templates->slug.'.php', 0777);	
				
				// Creation du controller
				$modifs_elements = "";
				$modifs_elements .= "<?php\r\n";
				
				$fp = fopen($this->path.'apps/default/controllers/templates/'.$this->templates->slug.'.php', "wb");
				fputs ($fp, $modifs_elements);
				fclose($fp);
				
				chmod($this->path.'apps/default/controllers/templates/'.$this->templates->slug.'.php', 0777);	
    		}
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'un template';
			$_SESSION['freeow']['message'] = 'Le template a bien &eacute;t&eacute; ajout&eacute; !';
    		
    		// Renvoi sur l'edition du template
			header('Location:'.$this->lurl.'/templates/elements/'.$this->templates->id_template);
			die;
		}
		
		// Modification du statut et suppression
		if(isset($this->params[0]) && $this->params[0] != '')
		{
			switch($this->params[0])
			{
				case 'status':
				
					$this->templates->get($this->params[1],'id_template');	
					$this->templates->status = ($this->params[2] == 0?1:0);
					$this->templates->update();
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Statut d\'un template';
					$_SESSION['freeow']['message'] = 'Le statut du template a bien &eacute;t&eacute; modifi&eacute; !';
					
					header('location:'.$this->lurl.'/templates');
					die;
					
				break;
				
				case 'affichage':
				
					$this->templates->get($this->params[1],'id_template');	
					$this->templates->affichage = ($this->params[2] == 0?1:0);
					$this->templates->update();
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Affichage d\'un template';
					$_SESSION['freeow']['message'] = 'L\'affichage du template a bien &eacute;t&eacute; modifi&eacute; !';
					
					header('location:'.$this->lurl.'/templates');
					die;
					
				break;
				
				case 'delete':
					
					$this->templates->get($this->params[1],'id_template');
					
					// On supprime le fichier
					@unlink($this->path.'apps/default/views/templates/'.$this->templates->slug.'.php');
					@unlink($this->path.'apps/default/controllers/templates/'.$this->templates->slug.'.php');
					
					// On supprime les donn�es
					$this->blocs_templates->delete($this->params[1],'id_template');
					$this->elements->delete($this->params[1],'id_template');
					$this->templates->delete($this->params[1],'id_template');
					$this->tree->deleteTemplate($this->params[1]);
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Suppression d\'un template';
					$_SESSION['freeow']['message'] = 'Le template a bien &eacute;t&eacute; supprim&eacute; !';
					
					header('location:'.$this->lurl.'/templates');
					die;
					
				break;		
			}
		}
	}
	
	function _editElement()
	{		
		// Chargement des datas
		$this->elements = $this->loadData('elements');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation des infos de la personne
		$this->elements->get($this->params[0],'id_element');
	}
	
	function _addElement()
	{		
		// Chargement des datas
		$this->templates = $this->loadData('templates');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation des infos du template
		$this->templates->get($this->params[0],'id_template');
	}
	
	function _addBloc()
	{		
		// Chargement des datas
		$this->blocs = $this->loadData('blocs');
		$this->templates = $this->loadData('templates');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation de la liste des blocs disponible
		$this->lBlocsOnline = $this->blocs->select('status = 1','name ASC');
		
		// Recuperation des infos du template
		$this->templates->get($this->params[0],'id_template');
	}
	
	function _elements()
	{		
		// Chargement des datas
		$this->templates = $this->loadData('templates');
		$this->elements = $this->loadData('elements');
		$this->blocs = $this->loadData('blocs');
		$this->blocs_templates = $this->loadData('blocs_templates');
		
		// Recuperation des infos du template
		$this->templates->get($this->params[0],'id_template');
		
		// Recuperation de la liste des elements du template
		$this->lElements = $this->elements->select('id_template = "'.$this->params[0].'" AND id_template != 0','ordre ASC');
		
		// Recuperation de la liste des positions possible pour les blocs
		$this->lPositions = $this->bdd->getEnum('blocs_templates','position');
		
		// Formulaire d'ajout d'un bloc
		if(isset($_POST['form_add_bloc']))
		{
			$this->blocs_templates->id_bloc = $_POST['id_bloc'];
			$this->blocs_templates->id_template = $_POST['id_template'];
			$this->blocs_templates->position = $_POST['position'];
			$this->blocs_templates->ordre = $this->blocs_templates->getLastPosition($this->blocs_templates->position,$_POST['id_template']);
			$this->blocs_templates->status = $_POST['status'];
			$this->blocs_templates->create();
			
			// On reordonne le template pour la position
			$this->blocs_templates->reordre($_POST['id_template'],$this->blocs_templates->position);
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'un bloc';
			$_SESSION['freeow']['message'] = 'Le bloc a bien &eacute;t&eacute; ajout&eacute; !';
			
			// Renvoi sur l'edition du template
			header('Location:'.$this->lurl.'/templates/elements/'.$_POST['id_template']);
			die;
		}
		
		// Formulaire d'ajout d'un element
		if(isset($_POST['form_add_element']))
		{
			$this->elements->id_template = $_POST['id_template'];
			$this->elements->name = $_POST['name'];
			$this->elements->slug = ($_POST['slug'] != ''?$this->bdd->generateSlug($_POST['slug']):$this->bdd->generateSlug($_POST['name']));			
			$this->elements->ordre = $this->elements->getLastPosition($_POST['id_template'],'id_template');
			$this->elements->type_element = $_POST['type_element'];
			$this->elements->status = $_POST['status'];
			$this->elements->create();
			
			// On reordonne le template
			$this->elements->reordre($_POST['id_template'],'id_template');
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'un &eacute;l&eacute;ment';
			$_SESSION['freeow']['message'] = 'L\'&eacute;l&eacute;ment a bien &eacute;t&eacute; ajout&eacute; !';
			
			// Renvoi sur l'edition du template
			header('Location:'.$this->lurl.'/templates/elements/'.$_POST['id_template']);
			die;
		}
		
		// Formulaire d'edition d'un element
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
			
			header('location:'.$this->lurl.'/templates/elements/'.$this->elements->id_template);
			die;
		}
		
		if(isset($this->params[1]) && $this->params[1] != '')
		{
			switch($this->params[1])
			{
				case 'up':
					
					$this->elements->moveUp($this->params[2],$this->params[0],'id_template');
					header('location:'.$this->lurl.'/templates/elements/'.$this->params[0]);
					die;
					
				break;
				
				case 'down':
					
					$this->elements->moveDown($this->params[2],$this->params[0],'id_template');
					header('location:'.$this->lurl.'/templates/elements/'.$this->params[0]);
					die;
					
				break;
				
				case 'status':
				
					$this->elements->get($this->params[2],'id_element');	
					$this->elements->status = ($this->params[3] == 0?1:0);					
					$this->elements->update();
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Statut d\'un &eacute;l&eacute;ment';
					$_SESSION['freeow']['message'] = 'Le statut de l\'&eacute;l&eacute;ment a bien &eacute;t&eacute; modifi&eacute; !';
					
					header('location:'.$this->lurl.'/templates/elements/'.$this->params[0]);
					die;
					
				break;
				
				case 'delete':
				
					$this->elements->delete($this->params[2],'id_element');
					$this->tree_elements->delete($this->params[2],'id_element');
					$this->blocs_elements->delete($this->params[2],'id_element');
					$this->elements->reordre($this->params[0],'id_template');
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Suppression d\'un &eacute;l&eacute;ment';
					$_SESSION['freeow']['message'] = 'L\'&eacute;l&eacute;ment a bien &eacute;t&eacute; supprim&eacute; !';
			
					header('location:'.$this->lurl.'/templates/elements/'.$this->params[0]);
					die;
					
				break;
				
				case 'upBloc':
					
					$this->blocs_templates->moveUp($this->params[3],$this->params[0],$this->params[2]);
					header('location:'.$this->lurl.'/templates/elements/'.$this->params[0]);
					die;
					
				break;
				
				case 'downBloc':
					
					$this->blocs_templates->moveDown($this->params[3],$this->params[0],$this->params[2]);
					header('location:'.$this->lurl.'/templates/elements/'.$this->params[0]);
					die;
					
				break;
				
				case 'statusBloc':
				
					$this->blocs_templates->get($this->params[2]);	
					$this->blocs_templates->status = ($this->params[3] == 0?1:0);					
					$this->blocs_templates->update();
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Statut d\'un bloc';
					$_SESSION['freeow']['message'] = 'Le statut du bloc a bien &eacute;t&eacute; modifi&eacute; !';
					
					header('location:'.$this->lurl.'/templates/elements/'.$this->params[0]);
					die;
					
				break;
				
				case 'deleteBloc':
				
					$this->blocs_templates->delete($this->params[2]);
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Suppression d\'un bloc';
					$_SESSION['freeow']['message'] = 'Le bloc a bien &eacute;t&eacute; supprim&eacute; !';
			
					header('location:'.$this->lurl.'/templates/elements/'.$this->params[0]);
					die;
					
				break;
			}
		}
	}
	
	function _edition()
	{		
		// Chargement des datas
		$this->templates = $this->loadData('templates');
		
		// Recuperation des infos du template
		$this->templates->get($this->params[0],'slug');
		
		// Preparation de la source
		$this->edit = implode('',file($this->path.'apps/default/views/templates/'.$this->params[0].'.php'));
		$this->edit = htmlentities($this->edit);
		
		if(isset($_POST['form_edition_template']))
		{
			$filecontent = stripslashes($_POST["filecontent"]);

        	if(is_writeable($this->path.'apps/default/views/templates/'.$this->params[0].'.php'))
			{
				$fp = fopen($this->path.'apps/default/views/templates/'.$this->params[0].'.php', "wb");
				fputs ($fp, $filecontent);
				fclose($fp);
			}
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Edition d\'un template';
			$_SESSION['freeow']['message'] = 'Le template a bien &eacute;t&eacute; &eacute;dit&eacute; !';
			
			header('location:'.$this->lurl.'/templates');
			die;
		}		
	}
}