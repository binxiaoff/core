<?php

class brandsController extends bootstrap
{
	var $Command;
	
	function brandsController(&$command,$config,$app)
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
	
	function _edit()
	{
		// Chargement des datas
		$this->brands = $this->loadData('brands');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url;
		
		// Recuperation des infos de la marque
		$this->brands->get($this->params[0],'id_brand');
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
	
	function _default()
	{
		// Chargement des datas
		$this->brands = $this->loadData('brands');
		
		// Recuperation de la liste des marques
		$this->lBrands = $this->brands->select('','name ASC');
		
		// Formulaire d'ajout d'une marque
		if(isset($_POST['form_add_brands']))
		{
			$this->brands->name = $_POST['name'];
			$this->brands->slug = $this->bdd->generateSlug($_POST['name']);
			$this->brands->status = $_POST['status'];
			
			// Upload de l'image de la marque
			if(isset($_FILES['image']) && $_FILES['image']['name'] != '')
			{
				$this->upload->setUploadDir($this->spath,'images/marques/');
				
				if($this->upload->doUpload('image',$this->brands->slug))
				{
					$this->brands->image = $this->upload->getName();
				}
				else
				{
					$this->brands->image = '';
				}
			}
			else
			{
				$this->brands->image = '';
			}
			
			$this->brands->id_brand = $this->brands->create();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'une marque';
			$_SESSION['freeow']['message'] = 'La marque a bien &eacute;t&eacute; ajout&eacute;e !';
			
			// Renvoi sur la liste des settings
			header('Location:'.$this->lurl.'/brands');
			die;
		}
		
		// Formulaire de modification d'une marque
		if(isset($_POST['form_edit_brands']))
		{
			// Recuperation des infos de la marque
			$this->brands->get($this->params[0],'id_brand');
		
			$this->brands->name = $_POST['name'];
			$this->brands->slug = $this->bdd->generateSlug($_POST['name']);
			$this->brands->status = $_POST['status'];
			
			// Upload de l'image de la marque
			if(isset($_FILES['image']) && $_FILES['image']['name'] != '')
			{
				$this->upload->setUploadDir($this->spath,'images/marques/');
			
				if($this->upload->doUpload('image',$this->brands->slug))
				{
					$this->brands->image = $this->upload->getName();
				}
				else
				{
					$this->brands->image = $_POST['image-old'];
				}
			}
			else
			{
				$this->brands->image = $_POST['image-old'];
			}
			
			$this->brands->update();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Modification d\'une marque';
			$_SESSION['freeow']['message'] = 'La marque a bien &eacute;t&eacute; modifi&eacute;e !';
			
			// Renvoi sur la liste des settings
			header('Location:'.$this->lurl.'/brands');
			die;
		}
		
		// Suppression d'une marque
		if(isset($this->params[0]) && $this->params[0] == 'delete')
		{
			// Recuperation des infos de la marque
			$this->brands->get($this->params[1],'id_brand');
			
			// On supprime le fichier sur le serveur
			@unlink($this->spath.'images/marques/'.$this->brands->image);
			
			// On supprime la marque
			$this->brands->delete($this->params[1],'id_brand');	
			
			// On supprime la marque des produits
			$this->brands->deleteBranOnProducts($this->params[1]);
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Suppression d\'une marque';
			$_SESSION['freeow']['message'] = 'La marque a bien &eacute;t&eacute; supprim&eacute;e !';
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/brands');
			die;
		}
		
		// Modification du status d'une marque
		if(isset($this->params[0]) && $this->params[0] == 'status')
		{
			$this->brands->get($this->params[1],'id_brand');
			$this->brands->status = ($this->params[2]==1?0:1);
			$this->brands->update();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Statut d\'une marque';
			$_SESSION['freeow']['message'] = 'Le statut a bien &eacute;t&eacute; modifi&eacute; !';
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/brands');
			die;
		}
	}
}