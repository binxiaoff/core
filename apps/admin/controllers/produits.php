<?php 

class produitsController extends bootstrap
{
	var $Command;
	
	function produitsController(&$command,$config,$app)
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
	
	function _avis()
	{
		// Chargement des datas
		$this->produits_avis = $this->loadData('produits_avis');
		$this->produits_votes = $this->loadData('produits_votes');
		$this->produits_elements = $this->loadData('produits_elements');
		$this->produits = $this->loadData('produits',array('url'=>$this->url,'surl'=>$this->surl,'produits_elements'=>$this->produits_elements,'upload'=>$this->upload,'spath'=>$this->spath));
		
		// Validation de l'avis
		if(isset($this->params[0]) && $this->params[0] == 'valide')
		{
			// Recuperation des infos de l'avis
			$this->produits_avis->get($this->params[1],'id_avis');
			$this->produits_avis->status = 1;
			$this->produits_avis->update();
			
			// On enregistre la note
			if($this->produits_avis->note > 0)
			{
				$this->produits_votes->id_produit = $this->produits_avis->id_produit;		
				$this->produits_votes->vote = $this->produits_avis->note;	
				$this->produits_votes->ip = $this->produits_avis->ip;	
				$this->produits_votes->create();
			}
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Validation d\'un avis';
			$_SESSION['freeow']['message'] = 'L\'avis a bien &eacute;t&eacute; valid&eacute; !';
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/produits/avis');
			die;
		}
		
		// Suppression de l'avis
		if(isset($this->params[0]) && $this->params[0] == 'delete')
		{
			// Recuperation des infos de l'avis
			$this->produits_avis->delete($this->params[1],'id_avis');
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Suppression d\'un avis';
			$_SESSION['freeow']['message'] = 'L\'avis a bien &eacute;t&eacute; supprim&eacute; !';
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/produits/avis');
			die;
		}
		
		// Recuperation de la liste des avis en attente de moderation
		$this->lAvis = $this->produits_avis->select('status = 0','added DESC');
		
		// Recuperation de la liste des avis validés
		$this->lAvisOK = $this->produits_avis->select('status = 1','added DESC');
	}
	
	function _default()
	{
		// Chargement des datas
		$this->produits_elements = $this->loadData('produits_elements');
		$this->produits = $this->loadData('produits',array('url'=>$this->url,'surl'=>$this->surl,'produits_elements'=>$this->produits_elements,'upload'=>$this->upload,'spath'=>$this->spath));
		$this->produits_images = $this->loadData('produits_images');
		$this->produits_crosseling = $this->loadData('produits_crosseling');
		$this->produits_tree = $this->loadData('produits_tree');
		$this->produits_details = $this->loadData('produits_details');		
		
		// Suppression d'un produit
		if(isset($this->params[0]) && $this->params[0] == 'delete')
		{
			// Suppressions des images
			$this->lImages = $this->produits_images->select('id_produit = "'.$this->params[1].'"');
			
			// Pour chaque image on supprime de la base et le fichier du serveur
			foreach($this->lImages as $img)
			{
				// On supprime le fichier sur le serveur
				@unlink($this->spath.'images/produits/'.$img['fichier']);
				
				// On supprime le fichier de la base
				$this->produits_images->delete($img['id_image'],'id_image');
			}
			
			// Suppression des produit complementaire
			$this->produits_crosseling->delete(array('id_produit'=>$this->params[1]));
			
			// Suppression des elements
			$this->produits_elements->delete($this->params[1],'id_produit');
			
			// Suppression des categories
			$this->produits_tree->delete(array('id_produit'=>$this->params[1]));
			
			// Suppression des details
			$this->produits_details->delete($this->params[1],'id_produit');
			
			// Suppression du produit
			$this->produits->delete($this->params[1],'id_produit');	
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Suppression d\'un produit';
			$_SESSION['freeow']['message'] = 'Le produit a bien &eacute;t&eacute; supprim&eacute; !';
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/produits');
			die;
		}
		
		// Modification du status d'un produit
		if(isset($this->params[0]) && $this->params[0] == 'status')
		{
			// Recuperation des infos du produit
			$this->produits->get($this->params[1],'id_produit');
			$this->produits->status = ($this->params[2] == 1?0:1);
			$this->produits->update();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Statut d\'un produit';
			$_SESSION['freeow']['message'] = 'Le statut du produit a bien &eacute;t&eacute; modifi&eacute; !';
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/produits');
			die;
		}
		
		// Recuperation de la liste des produits		
		if(isset($_POST['form_search_produit']))
		{
			// Recuperation de la liste des produits
			$this->lProduits = $this->produits->selectListeProduits($this->language,$_POST['s_name'],$_POST['s_reference'],$_POST['s_id_brand'],'','nom ASC');
		
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Recherche de produits';
			$_SESSION['freeow']['message'] = 'Il y a '.$this->nb_produits.' produits pour votre recherche !';
		}
		else
		{		
			// Recuperation de la liste des produits
			$this->lProduits = $this->produits->selectListeProduits($this->language,'','','','','nom ASC');
		}
		
		//On découpe par types
		foreach($this->lProduits as $produit)
		{
			switch($produit['type'])
			{
				case 'Produit':
					$this->lTypeProduit[] = $produit;
				break;
				case 'Echantillon':
					$this->lTypeEchantillon[] = $produit;
				break;
				case 'Cadeau':
					$this->lTypeCadeau[] = $produit;
				break;
			}
		}
	}
	
	function _recherche()
	{
		// Chargement des datas
		$this->brands = $this->loadData('brands');
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->lurl;
		
		// Recuperation de la liste des marques
		$this->lBrands = $this->brands->select('status = 1','name ASC');
	}
	
	function _upload()
	{
		// Chargement des datas
		$this->produits_images = $this->loadData('produits_images');
		$this->produits_elements = $this->loadData('produits_elements');
		$this->produits = $this->loadData('produits',array('url'=>$this->url,'surl'=>$this->surl,'produits_elements'=>$this->produits_elements,'upload'=>$this->upload,'spath'=>$this->spath));
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->lurl;
		
		// Formulaire d'ajout d'une image
		if(isset($_POST['send_image']) && isset($_FILES['image_produit']) && $_FILES['image_produit']['name'] != '')
		{
			// Upload de l'image
			$this->upload->setUploadDir($this->spath,'images/produits/');
				
			// Si c'est ok on enregistre en base
			if($this->upload->doUpload('image_produit'))
			{
				$this->produits_images->id_produit = $this->params[0];
				$this->produits_images->fichier = $this->upload->getName();				
				
				// Recuperation du plus grand ordre pour ce produit
				$this->produits_images->ordre = $this->produits->getMaxOrdre($this->params[0]);							
				$this->produits_images->create();
			}
		}
		
		// Recuperation de la liste des images pour le produit
		$this->lImages = $this->produits_images->select('id_produit = "'.$this->params[0].'"','ordre ASC');
	}
	
	function _add()
	{
		// Chargement des datas
		$this->produits_elements = $this->loadData('produits_elements');
		$this->produits = $this->loadData('produits',array('url'=>$this->url,'surl'=>$this->surl,'produits_elements'=>$this->produits_elements,'upload'=>$this->upload,'spath'=>$this->spath));
		$this->produits_images = $this->loadData('produits_images');
		$this->produits_crosseling = $this->loadData('produits_crosseling');
		$this->produits_tree = $this->loadData('produits_tree');
		$this->produits_details = $this->loadData('produits_details');
		$this->templates = $this->loadData('templates');		
		$this->elements = $this->loadData('elements');
		$this->brands = $this->loadData('brands');
		
		// Recuperation de la liste des marques
		$this->lBrands = $this->brands->select('status = 1','name ASC');
		
		// Creation d'un id produit temporaire
		$this->id_produit_temp = md5(mktime().rand());
		
		// Recuperation de l'id parent des categories
		$this->settings->get('Categories','type');
		$this->id_categorie = $this->settings->value;
		
		// Recuperation de la liste des id parent des categories secondaires
		$this->settings->get('Categories secondaires','type');
		$this->lCategoriesSec = $this->settings->value;
		
		// Recuperation de la TVA par default
		$this->settings->get('TVA','type');
		$this->tva = $this->settings->value;
		
		// Recuperation de la liste des categories
		$this->lCategories = $this->tree->listChilds($this->id_categorie,'-',array(),$this->language);
		
		// Recuperation de la liste des pages du site
		$this->lTree = $this->tree->listChildsBis($this->lCategoriesSec,$this->language);
		
		// Recuperation de la liste des template produit
		$this->lTemplates = $this->templates->select('status > 0 AND type = 1','name ASC');
		
		// Recuperation de la liste des produits
		$this->lProduits = $this->produits->selectProducts($this->language);
		
		// Recuperation de la liste des produits cross seling
		$this->lProduitCrosseling = $this->produits_crosseling->select('id_produit = "'.$this->id_produit_temp.'"','ordre ASC');
				
		// Formulaire ajout produit
		if(isset($_POST['form_add_produit']))
		{
			// Enregistrement des informations du produit
			$this->produits->type = $_POST['type'];
			$this->produits->id_template = $_POST['id_template'];
			$this->produits->tva = $_POST['tva'];
			$this->produits->id_brand = $_POST['id_brand'];
			$this->produits->status = $_POST['status'];
			
			// Creation du produit
			$this->produits->id_produit = $this->produits->create();
			
			// Compteur d'ordre
			$y = 1;
			
			// Enregistrement des details du produit
			for($i = 1; $i <= $_POST['nbdetails']; $i++)
			{
				if($_POST['todelete'.$i] == 0)
				{
					$this->produits_details->id_produit = $this->produits->id_produit;
					$this->produits_details->reference = $_POST['reference'.$i];
					$this->produits_details->poids = $_POST['poids'.$i];
					$this->produits_details->prix = $_POST['prix'.$i];
					$this->produits_details->prix_ht = number_format(($_POST['prix'.$i] / (1 + ($this->produits->tva / 100))),2,'.','');
					$this->produits_details->promo = $_POST['promo'.$i];
					$this->produits_details->montant_promo = $_POST['montant_promo'.$i];
					$this->produits_details->prix_promo = ($_POST['promo'.$i] == 0?($_POST['prix'.$i] - $_POST['montant_promo'.$i]):($_POST['prix'.$i] - ($_POST['prix'.$i] * ((100 - $_POST['montant_promo'.$i]) / 100))));
					$this->produits_details->prix_promo_ht = number_format(($this->produits_details->prix_promo / (1 + ($this->produits->tva / 100))),2,'.','');
					$this->produits_details->debut_promo = $this->dates->formatDateFrToMysql($_POST['debut_promo'.$i]);
					$this->produits_details->fin_promo = $this->dates->formatDateFrToMysql($_POST['fin_promo'.$i]);
					$this->produits_details->type_detail = $_POST['type_detail'.$i];
					$this->produits_details->detail = $_POST['detail'.$i];
					$this->produits_details->ordre = $y;
					$this->produits_details->stock = $_POST['stock'.$i];
					$this->produits_details->status = $_POST['status_details'.$i];
					
					// Creation du details
					$this->produits_details->create();
					
					// incrementation de l'ordre
					$y++;
				}
			}
			
			// Enregistrement de la categorie principale du produit
			$this->produits_tree->id_produit = $this->produits->id_produit;
			$this->produits_tree->id_tree = $_POST['id_cat'];
			$this->produits_tree->ordre_tree = 1;
			$this->produits_tree->ordre_produit = $_POST['ordre_produit'];
			$this->produits_tree->create();
			
			// Enregistrements des autres categories
			$i = 2;
			
			if(is_array($_POST['id_tree']))
			{			
				foreach($_POST['id_tree'] as $id_tree)
				{
					if($id_tree != 0)
					{
						$this->produits_tree->id_produit = $this->produits->id_produit;
						$this->produits_tree->id_tree = $id_tree;
						$this->produits_tree->ordre_tree = $i;
						$this->produits_tree->create();
						
						$i++;
					}
				}
			}
			
			// Mise a jour de l'id produit pour les images et produits comp
			$this->produits->newIDProduit($this->produits->id_produit,$_POST['id_produit_temp']);
			
			// On enregistre les données pour toutes les langues
			foreach($this->lLangues as $key => $lng)
			{
				// Suppression des anciennes valeurs des elements
				$this->produits_elements->delete($this->produits->id_produit,'id_langue = "'.$key.'" AND id_produit');
				
				// Recuperation des elements du template de produit
				$this->lElements = $this->elements->select('status > 0 AND id_template != 0 AND id_template = '.$this->produits->id_template,'ordre ASC');
				
				// Enregistrement des values des elements du template
				foreach($this->lElements as $element)
				{
					$this->produits->handleFormElement($this->produits->id_produit,$element,$key);
				}
			}
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'un produit';
			$_SESSION['freeow']['message'] = 'Le produit a bien &eacute;t&eacute; ajout&eacute; !';	
			
			// Renvoi sur l'accueil des produits
			header('Location:'.$this->lurl.'/produits');
			die;
		}
	}
	
	function _edit()
	{
		if(isset($this->params[0]) && $this->params[0] != '')
		{
			// Chargement des datas
			$this->produits_elements = $this->loadData('produits_elements');
			$this->produits = $this->loadData('produits',array('url'=>$this->url,'surl'=>$this->surl,'produits_elements'=>$this->produits_elements,'upload'=>$this->upload,'spath'=>$this->spath));
			$this->produits_images = $this->loadData('produits_images');
			$this->produits_crosseling = $this->loadData('produits_crosseling');
			$this->produits_tree = $this->loadData('produits_tree');
			$this->produits_details = $this->loadData('produits_details');
			$this->templates = $this->loadData('templates');		
			$this->elements = $this->loadData('elements');
			$this->brands = $this->loadData('brands');			
		
			// Recuperation de la liste des marques
			$this->lBrands = $this->brands->select('status = 1','name ASC');
			
			// Recuperation du produit
			$this->produits->get($this->params[0],'id_produit');
			
			// Recuperation des infos du produit
			$this->prod = $this->produits->getInfosProduit($this->produits->id_produit);
			
			// Recuperation des details
			$this->lDetails = $this->produits_details->select('id_produit = "'.$this->produits->id_produit.'"','ordre ASC');
			
			// Recuperation de la categorie mere
			$this->produits_tree->get(array('id_produit'=>$this->produits->id_produit,'ordre_tree'=>1));
			
			// Recuperation des autres categories / pages
			$lCategories = $this->produits_tree->select('ordre_tree != 1 AND id_produit = "'.$this->produits->id_produit.'"');
			$this->lIdTree = array();
			foreach($lCategories as $c)
			{
				$this->lIdTree[] = $c['id_tree'];
			}
			
			// Recuperation de la liste des template produit
			$this->lTemplates = $this->templates->select('status > 0 AND type = 1','name ASC');
			
			// Recuperation de l'id parent des categories
			$this->settings->get('Categories','type');
			$this->id_categorie = $this->settings->value;
			
			// Recuperation de la liste des id parent des categories secondaires
			$this->settings->get('Categories secondaires','type');
			$this->lCategoriesSec = $this->settings->value;
			
			// Recuperation de la liste des categories
			$this->lCategories = $this->tree->listChilds($this->id_categorie,'-',array(),$this->language);
			
			// Recuperation de la liste des pages du site
			$this->lTree = $this->tree->listChildsBis($this->lCategoriesSec,$this->language);
			
			// Recuperation de la liste des template produit
			$this->lTemplates = $this->templates->select('status > 0 AND type = 1','name ASC');
			
			// Recuperation de la liste des produits
			$this->lProduits = $this->produits->selectProducts($this->language);
			
			// Recuperation de la liste des produits cross seling
			$this->lProduitCrosseling = $this->produits_crosseling->select('id_produit = "'.$this->produits->id_produit.'"','ordre ASC');
			
			// Formulaire edition produit
			if(isset($_POST['form_mod_produit']))
			{
				// Recuperation des infos du produit
				$this->produits->get($_POST['id_produit_temp'],'id_produit');
				
				// Enregistrement des informations du produit
				$this->produits->type = $_POST['type'];
				$this->produits->id_template = $_POST['id_template'];
				$this->produits->tva = $_POST['tva'];
				$this->produits->id_brand = $_POST['id_brand'];
				$this->produits->status = $_POST['status'];
				
				// update du produit
				$this->produits->update();
				
				// Compteur d'ordre
				$y = 1;
				
				// Enregistrement des details du produit
				for($i = 1; $i <= $_POST['nbdetails']; $i++)
				{
					if($_POST['todelete'.$i] == 0)
					{
						// On regarde si on a un detail en stock
						if($this->produits_details->get($_POST['id_detail'.$i],'id_detail'))
						{
							$this->produits_details->id_produit = $this->produits->id_produit;
							$this->produits_details->reference = $_POST['reference'.$i];
							$this->produits_details->poids = $_POST['poids'.$i];
							$this->produits_details->prix = $_POST['prix'.$i];
							$this->produits_details->prix_ht = number_format(($_POST['prix'.$i] / (1 + ($this->produits->tva / 100))),2,'.','');
							$this->produits_details->promo = $_POST['promo'.$i];
							$this->produits_details->montant_promo = $_POST['montant_promo'.$i];
							$this->produits_details->prix_promo = ($_POST['promo'.$i] == 0?($_POST['prix'.$i] - $_POST['montant_promo'.$i]):($_POST['prix'.$i] - ($_POST['prix'.$i] * ((100 - $_POST['montant_promo'.$i]) / 100))));
							$this->produits_details->prix_promo_ht = number_format(($this->produits_details->prix_promo / (1 + ($this->produits->tva / 100))),2,'.','');
							$this->produits_details->debut_promo = $this->dates->formatDateFrToMysql($_POST['debut_promo'.$i]);
							$this->produits_details->fin_promo = $this->dates->formatDateFrToMysql($_POST['fin_promo'.$i]);
							$this->produits_details->type_detail = $_POST['type_detail'.$i];
							$this->produits_details->detail = $_POST['detail'.$i];
							$this->produits_details->ordre = $y;
							$this->produits_details->stock = $_POST['stock'.$i];
							$this->produits_details->status = $_POST['status_details'.$i];
							
							// Creation du details
							$this->produits_details->update();
						}
						else
						{
							$this->produits_details->id_produit = $this->produits->id_produit;
							$this->produits_details->reference = $_POST['reference'.$i];
							$this->produits_details->poids = $_POST['poids'.$i];
							$this->produits_details->prix = $_POST['prix'.$i];
							$this->produits_details->prix_ht = number_format(($_POST['prix'.$i] / (1 + ($this->produits->tva / 100))),2,'.','');
							$this->produits_details->promo = $_POST['promo'.$i];
							$this->produits_details->montant_promo = $_POST['montant_promo'.$i];
							$this->produits_details->prix_promo = ($_POST['promo'.$i] == 0?($_POST['prix'.$i] - $_POST['montant_promo'.$i]):($_POST['prix'.$i] - ($_POST['prix'.$i] * ((100 - $_POST['montant_promo'.$i]) / 100))));
							$this->produits_details->prix_promo_ht = number_format(($this->produits_details->prix_promo / (1 + ($this->produits->tva / 100))),2,'.','');
							$this->produits_details->debut_promo = $this->dates->formatDateFrToMysql($_POST['debut_promo'.$i]);
							$this->produits_details->fin_promo = $this->dates->formatDateFrToMysql($_POST['fin_promo'.$i]);
							$this->produits_details->type_detail = $_POST['type_detail'.$i];
							$this->produits_details->detail = $_POST['detail'.$i];
							$this->produits_details->ordre = $y;
							$this->produits_details->stock = $_POST['stock'.$i];
							$this->produits_details->status = $_POST['status_details'.$i];
							
							// Creation du details
							$this->produits_details->create();	
						}
						
						// Incrementation ordre
						$y++;
					}
					else
					{
						// On regarde si on a un detail en stock
						if($this->produits_details->get($_POST['id_detail'.$i],'id_detail'))
						{
							// Suppression du details
							$this->produits_details->delete($_POST['id_detail'.$i],'id_detail');
						}
					}
				}
				
				// Suppression des categories
				$this->produits_tree->delete(array('id_produit'=>$this->produits->id_produit));
				
				// Enregistrement de la categorie principale du produit
				$this->produits_tree->id_produit = $this->produits->id_produit;
				$this->produits_tree->id_tree = $_POST['id_cat'];
				$this->produits_tree->ordre_tree = 1;
				$this->produits_tree->ordre_produit = $_POST['ordre_produit'];
				$this->produits_tree->create();
				
				// Enregistrements des autres categories / pages
				$i = 2;
				
				if(is_array($_POST['id_tree']))
				{			
					foreach($_POST['id_tree'] as $id_tree)
					{
						if($id_tree != 0)
						{
							$this->produits_tree->id_produit = $this->produits->id_produit;
							$this->produits_tree->id_tree = $id_tree;
							$this->produits_tree->ordre_tree = $i;
							$this->produits_tree->create();
							
							$i++;
						}
					}
				}
				
				// On enregistre les données pour toutes les langues
				foreach($this->lLangues as $key => $lng)
				{
					// Suppression des anciennes valeurs des elements
					$this->produits_elements->delete($this->produits->id_produit,'id_langue = "'.$key.'" AND id_produit');
					
					// Recuperation des elements du template de produit
					$this->lElements = $this->elements->select('status > 0 AND id_template != 0 AND id_template = '.$this->produits->id_template,'ordre ASC');
					
					// Enregistrement des values des elements du template
					foreach($this->lElements as $element)
					{
						$this->produits->handleFormElement($this->produits->id_produit,$element,$key);
					}
				}
				
				// Mise en session du message
				$_SESSION['freeow']['title'] = 'Modification d\'un produit';
				$_SESSION['freeow']['message'] = 'Le produit a bien &eacute;t&eacute; modifi&eacute; !';	
				
				// Renvoi sur l'accueil des produits
				header('Location:'.$this->lurl.'/produits');
				die;
			}
		}
		else
		{
			// Renvoi sur l'accueil des produits
			header('Location:'.$this->lurl.'/produits');
			die;
		}
	}
}