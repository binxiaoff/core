<?php

class fdpController extends bootstrap
{
	var $Command;
	
	function fdpController(&$command,$config,$app)
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
	
	function _chargePays()
	{
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		$this->autoFireView = false;
		
		// Chargement des datas
		$this->pays = $this->loadData('pays');
		
		// On purge les pays en cours
		$this->pays->purgePays();
		
		// Initialisation de la 1ère ligne du csv
		$row = 1;
		
		// Ouverture du fichier en lecture seule
		$fp = fopen ($this->path.'protected/pays.csv', 'r');
		
		$i = 1;
		
		// Traitement du csv
		while($data = fgetcsv ($fp, 1000, ";"))
		{
			// Enregistrement des donnees
			$this->pays->id_langue = trim(strtolower($data[2]));
			$this->pays->fr = trim(utf8_encode(ucfirst(strtolower($data[0]))));
			$this->pays->en = trim(utf8_encode(ucfirst(strtolower($data[1]))));
			$this->pays->zone = 0;
			$this->pays->create();
			
			// Affichage des messages
			echo $this->pays->id_langue.'&nbsp;|&nbsp;'.$this->pays->fr.'&nbsp;|&nbsp;'.$this->pays->en.'&nbsp;|&nbsp;'.$this->pays->zone.'<br />';
			
			$row++;	
			
			$i++;			
		}
	}
	
	function _types()
	{
		// Chargement des datas
		$this->fdp_type = $this->loadData('fdp_type');
		$this->fdp = $this->loadData('fdp');
		
		// Recuperation de la liste des types
		$this->lTypes = $this->fdp_type->select('id_langue = "'.$this->language.'"','ordre ASC');
		
		// Actions sur les types
		if(isset($this->params[0]) && $this->params[0] != '')
		{
			switch($this->params[0])
			{
				case 'up':
					
					$this->fdp_type->moveUp($this->params[1]);
					header('location:'.$this->lurl.'/fdp/types');
					die;
					
				break;
				
				case 'down':
					
					$this->fdp_type->moveDown($this->params[1]);
					header('location:'.$this->lurl.'/fdp/types');
					die;
					
				break;
				
				case 'status':
				
					foreach($this->lLangues as $key => $lng)
					{
						$this->fdp_type->get(array('id_type'=>$this->params[1],'id_langue'=>$key));	
						$this->fdp_type->status = ($this->params[2] == 0?1:0);					
						$this->fdp_type->update(array('id_type'=>$this->params[1],'id_langue'=>$key));
					}
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Statut d\'un type de FDP';
					$_SESSION['freeow']['message'] = 'Le statut du type a bien &eacute;t&eacute; modifi&eacute; !';
					
					header('location:'.$this->lurl.'/fdp/types');
					die;
					
				break;
				
				case 'delete':
					
					// On supprime les FDP qui ont ce type
					$this->fdp->delete($this->params[1],'id_type');
					
					// On supprime le type et on reordonne
					$this->fdp_type->delete(array('id_type'=>$this->params[1]));
					$this->fdp_type->reOrdre();
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Suppression d\'un type de FDP';
					$_SESSION['freeow']['message'] = 'Le type a bien &eacute;t&eacute; supprim&eacute; !';
			
					header('location:'.$this->lurl.'/fdp/types');
					die;
					
				break;							
			}
		}
	}
	
	function _addType()
	{
		// Chargement des datas
		$this->fdp_type = $this->loadData('fdp_type');
		
		// Formulaire d'ajout d'un type
		if(isset($_POST['form_add_type']))
		{
			// Creation de toutes les langues
			foreach($this->lLangues as $key => $lng)
			{
				if($key == $this->dLanguage)
				{
					$this->current_id = $this->fdp_type->getMaxId() + 1;
				}		
						
				$this->fdp_type->id_type = $this->current_id;
				$this->fdp_type->id_langue = $key;
				$this->fdp_type->nom = $_POST['nom_'.$key];
				$this->fdp_type->affichage = $_POST['affichage_'.$key];
				$this->fdp_type->description = $_POST['description_'.$key];
				$this->fdp_type->delais_min = $_POST['delais_min_'.$key];
				$this->fdp_type->delais_max = $_POST['delais_max_'.$key];
				$this->fdp_type->url_suivi = $_POST['url_suivi_'.$key];
				$this->fdp_type->status = 1;
				
				if($key == $this->dLanguage)
				{
					$this->current_ordre = $this->fdp_type->getLastPosition() + 1;	
				}
				
				$this->fdp_type->ordre = $this->current_ordre;
				$this->fdp_type->create(array('id_type'=>$this->fdp_type->id_type,'id_langue'=>$this->fdp_type->id_langue));
			}
			
			// On reordonne les types
			$this->fdp_type->reOrdre();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'un type de FDP';
			$_SESSION['freeow']['message'] = 'Le type a bien &eacute;t&eacute; ajout&eacute; !';
			
			// Renvoi sur la liste des types
			header('Location:'.$this->lurl.'/fdp/types');
			die;
		}
	}
	
	function _editType()
	{
		// Chargement des datas
		$this->fdp_type = $this->loadData('fdp_type');
		
		if(isset($this->params[0]) && $this->params[0] != '')
		{
			// Formulaire de modification d'un type
			if(isset($_POST['form_edit_type']))
			{
				// Creation de toutes les langues
				foreach($this->lLangues as $key => $lng)
				{
					// Recuperation des infos du type
					if($this->fdp_type->get(array('id_type'=>$this->params[0],'id_langue'=>$key)))
					{
						$create = false;
					}
					else
					{
						$create = true;
					}	
							
					$this->fdp_type->id_langue = $key;
					$this->fdp_type->nom = $_POST['nom_'.$key];
					$this->fdp_type->affichage = $_POST['affichage_'.$key];
					$this->fdp_type->description = $_POST['description_'.$key];
					$this->fdp_type->delais_min = $_POST['delais_min_'.$key];
					$this->fdp_type->delais_max = $_POST['delais_max_'.$key];
					$this->fdp_type->url_suivi = $_POST['url_suivi_'.$key];
					
					// On modifie ou on créé si le type n'existe pas
					if(!$create)
					{
						$this->fdp_type->update(array('id_type'=>$this->fdp_type->id_type,'id_langue'=>$this->fdp_type->id_langue));
					}
					else
					{
						$this->fdp_type->create(array('id_type'=>$this->fdp_type->id_type,'id_langue'=>$this->fdp_type->id_langue));	
					}
				}
				
				// On reordonne les types
				$this->fdp_type->reOrdre();
				
				// Mise en session du message
				$_SESSION['freeow']['title'] = 'Modification d\'un type de FDP';
				$_SESSION['freeow']['message'] = 'Le type a bien &eacute;t&eacute; modifi&eacute; !';
				
				// Renvoi sur la liste des eamils
				header('Location:'.$this->lurl.'/fdp/types');
				die;
			}	
		}
		else
		{
			// Renvoi sur la liste des types
			header('Location:'.$this->lurl.'/fdp/types');
			die;
		}	
	}
	
	function _addZone()
	{
		// Chargement des datas
		$this->fdp = $this->loadData('fdp');
		$this->fdp_type = $this->loadData('fdp_type');
		$this->pays = $this->loadData('pays');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation de la liste des types
		$this->lTypes = $this->fdp_type->select('status = 1 AND id_langue = "'.$this->language.'"','nom ASC');
		
		// Recuperation de la liste des pays
		$this->lPays = $this->pays->select('id_zone = 0',$this->language.' ASC');
	}
	
	function _editZone()
	{
		// Chargement des datas
		$this->pays = $this->loadData('pays');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Liste des pays dispo et oqp
		$this->liste_pays_oqp = $this->pays->select('id_zone = '.$this->params[0],$this->language.' ASC');
		$this->liste_pays_dispo = $this->pays->select('id_zone = 0',$this->language.' ASC');
	}
	
	function _addFDP()
	{
		// Chargement des datas
		$this->fdp_type = $this->loadData('fdp_type');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation de la liste des types
		$this->lTypes = $this->fdp_type->select('status = 1 AND id_langue = "'.$this->language.'"','nom ASC');
	}
	
	function _editFDP()
	{
		// Chargement des datas
		$this->fdp = $this->loadData('fdp');
		$this->fdp_type = $this->loadData('fdp_type');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation des infos du fdp
		$this->fdp->get($this->params[0],'id_fdp');
		
		// Recuperation de la liste des types
		$this->lTypes = $this->fdp_type->select('status = 1 AND id_langue = "'.$this->language.'"','nom ASC');
	}
	
	function _default()
	{
		// Chargement des datas
		$this->fdp = $this->loadData('fdp');
		$this->pays = $this->loadData('pays');
		
		// Recuperation de la liste des zones
		$this->lZones = $this->fdp->recupListeZone();
		
		// Formulaire d'ajout d'une zone et de son prmier fdp
		if(isset($_POST['form_add_zone']))
		{
			$this->fdp->id_zone = $_POST['id_zone'];
			$this->fdp->id_type = $_POST['id_type'];
			$this->fdp->poids = $_POST['poids'];
			$this->fdp->fdp = $_POST['fdp'];
			$this->fdp->fdp_reduit = $_POST['fdp_reduit'];
			$this->fdp->montant_free = $_POST['montant_free'];			
			$this->fdp->create();
			
			if(isset($_POST['id_pays']) && !empty($_POST['id_pays']))
			{
				foreach($_POST['id_pays'] as $new_pays)
				{
					$this->pays->get($new_pays);
					$this->pays->id_zone = $_POST['id_zone'];
					$this->pays->update();
				}
			}
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'une zone';
			$_SESSION['freeow']['message'] = 'La zone a bien &eacute;t&eacute; ajout&eacute; !';
			
			// Renvoi sur la liste des fdp
			header('Location:'.$this->lurl.'/fdp');
			die;
		}
		
		// Formulaire d'edition d'une zone
		if(isset($_POST['form_edit_zone']))
		{
			$this->pays->razIDzone($this->params[0]);
			
			foreach($_POST['id_pays'] as $new_pays)
			{
				$this->pays->get($new_pays);
				$this->pays->id_zone = $this->params[0];
				$this->pays->update();
			}
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Modification d\'une zone';
			$_SESSION['freeow']['message'] = 'La zone a bien &eacute;t&eacute; modifi&eacute; !';
			
			// Renvoi sur la liste des settings
			header('Location:'.$this->lurl.'/fdp');
			die;
		}
		
		// Formulaire d'ajout d'un nouveau montant dans une zone
		if(isset($_POST['form_add_fdp']))
		{
			$this->fdp->id_zone = $_POST['id_zone'];
			$this->fdp->id_type = $_POST['id_type'];
			$this->fdp->poids = $_POST['poids'];
			$this->fdp->fdp = $_POST['fdp'];
			$this->fdp->fdp_reduit = $_POST['fdp_reduit'];
			$this->fdp->montant_free = $_POST['montant_free'];			
			$this->fdp->create();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'un FDP';
			$_SESSION['freeow']['message'] = 'Le montant a bien &eacute;t&eacute; ajout&eacute; !';
			
			// Renvoi sur la liste des settings
			header('Location:'.$this->lurl.'/fdp');
			die;
		}
		
		// Formulaire d'edition d'un fdp
		if(isset($_POST['form_edit_fdp']))
		{
			$this->fdp->get($this->params[0],'id_fdp');			
			$this->fdp->id_zone = $_POST['id_zone'];
			$this->fdp->id_type = $_POST['id_type'];
			$this->fdp->poids = $_POST['poids'];
			$this->fdp->fdp = $_POST['fdp'];
			$this->fdp->fdp_reduit = $_POST['fdp_reduit'];
			$this->fdp->montant_free = $_POST['montant_free'];			
			$this->fdp->update();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Modification d\'un FDP';
			$_SESSION['freeow']['message'] = 'Le montant a bien &eacute;t&eacute; modifi&eacute; !';
			
			// Renvoi sur la liste des settings
			header('Location:'.$this->lurl.'/fdp');
			die;
		}
		
		// Suppression d'un fdp
		if(isset($this->params[0]) && $this->params[0] == 'delete')
		{
			$this->fdp->delete($this->params[1],'id_fdp');
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Suppression d\'un FDP';
			$_SESSION['freeow']['message'] = 'Le montant a bien &eacute;t&eacute; supprim&eacute; !';	
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/fdp');
			die;
		}
		
		// Suppression d'une zone
		if(isset($this->params[0]) && $this->params[0] == 'deleteZone')
		{
			$this->pays->razIDzone($this->params[1]);
			$this->fdp->delete($this->params[1],'id_zone');
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Suppression d\'une Zone';
			$_SESSION['freeow']['message'] = 'La zone a bien &eacute;t&eacute; supprim&eacute; !';	
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/fdp');
			die;
		}
	}
}