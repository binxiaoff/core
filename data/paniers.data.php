<?php
// **************************************************************************************************** //
// ***************************************    ASPARTAM    ********************************************* //
// **************************************************************************************************** //
//
// Copyright (c) 2008-2011, equinoa
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and 
// associated documentation files (the "Software"), to deal in the Software without restriction, 
// including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, 
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, 
// subject to the following conditions:
// The above copyright notice and this permission notice shall be included in all copies 
// or substantial portions of the Software.
// The Software is provided "as is", without warranty of any kind, express or implied, including but 
// not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement. 
// In no event shall the authors or copyright holders equinoa be liable for any claim, 
// damages or other liability, whether in an action of contract, tort or otherwise, arising from, 
// out of or in connection with the software or the use or other dealings in the Software.
// Except as contained in this notice, the name of equinoa shall not be used in advertising 
// or otherwise to promote the sale, use or other dealings in this Software without  
// prior written authorization from equinoa.
//
//  Version : 2.4.0
//  Date : 21/03/2011
//  Coupable : CM
//                                                                                   
// **************************************************************************************************** //

class paniers extends paniers_crud
{

	function paniers($bdd,$params='')
    {
        parent::paniers($bdd,$params);
    }
    
    function get($id,$field='id_panier')
    {
        return parent::get($id,$field);
    }
    
    function update($cs='')
    {
        parent::update($cs);
    }
    
    function delete($id,$field='id_panier')
    {
    	parent::delete($id,$field);
    }
    
    function create($cs='')
    {
        $id = parent::create($cs);
        return $id;
    }
	
	function select($where='',$order='',$start='',$nb='')
	{
		if($where != '')
			$where = ' WHERE '.$where;
		if($order != '')
			$order = ' ORDER BY '.$order;
		$sql = 'SELECT * FROM `paniers`'.$where.$order.($nb!='' && $start !=''?' LIMIT '.$start.','.$nb:($nb!=''?' LIMIT '.$nb:''));

		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	} 
	
	function counter($where='')
	{
		if($where != '')
			$where = ' WHERE '.$where;
			
		$sql='SELECT count(*) FROM `paniers` '.$where;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	function exist($id,$field='id_panier')
	{
		$sql = 'SELECT * FROM `paniers` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		return ($this->bdd->fetch_array($result,0,0)>0);
	}
	
	//*********************//
	//*** MODIFICATIONS ***//
	//*********************//
	
	// Liste des produits du panier
	function listeProduitsPanier($id_panier)
	{
		$sql = 'SELECT * FROM paniers_produits WHERE id_panier = "'.$id_panier.'"';
		$resultat = $this->bdd->query($sql);
		$result = array();
		
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		
		return $result;
	}
	
	// Liste des ID produits du panier
	function listeIDProduitsPanier($id_panier)
	{
		$sql = 'SELECT id_produit FROM paniers_produits WHERE id_panier = "'.$id_panier.'"';
		$resultat = $this->bdd->query($sql);
		$result = array();
		
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record['id_produit'];
		}
		
		return $result;
	}
	
	// Liste des promos du panier
	function listePromosPanier($id_panier)
	{
		$sql = 'SELECT * FROM paniers_promos WHERE id_panier = "'.$id_panier.'"';
		$resultat = $this->bdd->query($sql);
		$result = array();
		
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		
		return $result;
	}  
	
	// Details du produit du panier
	function detailsProduitPanier($id_produit,$id_detail,$id_langue='fr')
	{
		$sql = 'SELECT 
					p.id_produit AS id_produit, 
					p.tva AS tva, 
					p.id_brand AS id_brand, 			
					p.id_template AS id_template, 
					p.status AS status, 
					(SELECT b.name FROM brands b WHERE b.id_brand = p.id_brand) AS brand, 
					(SELECT pd.reference FROM produits_details pd WHERE pd.id_detail = "'.$id_detail.'") AS reference, 
					(SELECT pd.poids FROM produits_details pd WHERE pd.id_detail = "'.$id_detail.'") AS poids, 
					(SELECT pd.prix FROM produits_details pd WHERE pd.id_detail = "'.$id_detail.'") AS prix, 
					(SELECT pd.prix_promo FROM produits_details pd WHERE pd.id_detail = "'.$id_detail.'") AS prix_promo, 
					(SELECT pd.promo FROM produits_details pd WHERE pd.id_detail = "'.$id_detail.'") AS promo, 
					(SELECT pd.type_detail FROM produits_details pd WHERE pd.id_detail = "'.$id_detail.'") AS type_detail, 
					(SELECT pd.detail FROM produits_details pd WHERE pd.id_detail = "'.$id_detail.'") AS detail, 
					(SELECT pd.stock FROM produits_details pd WHERE pd.id_detail = "'.$id_detail.'") AS stock, 			
					(SELECT t.title FROM tree t JOIN produits_tree pt ON t.id_tree = pt.id_tree WHERE pt.id_produit = p.id_produit AND t.id_langue = "'.$id_langue.'" AND pt.ordre_tree = 1) AS categorie, 
					(SELECT t.slug FROM tree t JOIN produits_tree pt ON t.id_tree = pt.id_tree WHERE pt.id_produit = p.id_produit AND t.id_langue = "'.$id_langue.'" AND pt.ordre_tree = 1) AS slug_categorie, 			
					(SELECT pi.fichier FROM produits_images pi WHERE pi.id_produit = p.id_produit AND pi.fichier != "" ORDER BY pi.ordre DESC LIMIT 1) AS image, 		
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 3) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS nom, 
					(SELECT pe.complement FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 3) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS slug, 
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 4) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS desc_courte, 
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 5) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS description,
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 0) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS meta_title, 
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 1) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS meta_description, 
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 2) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS meta_keywords
				FROM 
					produits p 
				WHERE 
					p.id_produit = "'.$id_produit.'" 
				AND 
					p.status = 1';

		$resultat = $this->bdd->query($sql);
		$record = $this->bdd->fetch_array($resultat);
		return $record;
	}
	
	// Recuperation d'un params
	function recupParams($type)
	{
		$sql = 'SELECT value FROM settings WHERE type = "'.$type.'"';
		$result = $this->bdd->query($sql);
		return $this->bdd->result($result,0,0);
	}
	
	// Recuperation du montant des FDP et du montant a partir duquel on est gratuit pour le type
	function getFDP($id_type,$id_zone)
	{
		$sql = 'SELECT fdp, fdp_reduit, montant_free FROM `fdp` WHERE id_zone = "'.$id_zone.'" AND id_type = "'.$id_type.'" LIMIT 1';
		$res = $this->bdd->query($sql);
		$rec = $this->bdd->fetch_array($res);
		return array('montant'=>$rec['fdp'],'montant_reduit'=>$rec['fdp_reduit'],'seuil'=>$rec['montant_free']);
	}
	
	// Recuperation de la liste des id des echantillon du panier
	function listeEchantillonsPanier($id_panier)
	{
		// Recuperation des produits
		$sql = 'SELECT id_produit FROM `paniers_cadeaux` WHERE id_panier = "'.$id_panier.'" AND type = 0';
		$res = $this->bdd->query($sql);
		$list = array();
		
		while($rec = $this->bdd->fetch_array($res))
		{
			$list[] = $rec['id_produit'];
		}
		
		// Renvoi de la liste des id de produit
		return $list;
	}
	
	// Recuperation des informations d'un code promo
	function getCode($id_code)
	{
		$sql = 'SELECT * FROM `promotions` WHERE id_code = "'.$id_code.'"';
		$res = $this->bdd->query($sql);
		$rec = $this->bdd->fetch_array($res);
		return $rec;
	}
	
	// Check presence d'un client dans un groupe
	function checkGroupeClient($id_groupe)
	{
		$sql = 'SELECT id_client FROM `clients_groupes` WHERE id_groupe = "'.$id_groupe.'"';
		$res = $this->bdd->query($sql);
		$list = array();
		
		while($rec = $this->bdd->fetch_array($res))
		{
			$list[] = $rec['id_client'];
		}
		
		if(in_array($_SESSION['client']['id_client'],$list))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	// Recuperation des gammes des produit
	function recupGammesProduits($id_panier)
	{
		// Recuperation des produits
		$sql = 'SELECT id_produit FROM `paniers_produits` WHERE id_panier = "'.$id_panier.'"';
		$res = $this->bdd->query($sql);
		$list = array();
		
		while($rec = $this->bdd->fetch_array($res))
		{
			$list[] = $rec['id_produit'];
		}
		
		// Initialisation de la liste des categories
		$cats = array();
		
		// Recuperation des categories du produit
		foreach($list as $p)
		{
			$sql = 'SELECT id_tree FROM `produits_tree` WHERE id_produit = "'.$p.'"';
			$res = $this->bdd->query($sql);
			
			while($rec = $this->bdd->fetch_array($res))
			{
				$cats[] = $rec['id_tree'];
			}
		}
		
		// Renvoi de la liste des id de categorie
		return $cats;
	}
	
	// Recuperation des produits dans le panier appertenant aux gammes du tableau
	function recupProduitsGammes($id_panier,$tab_gammes)
	{
		$tab_gammes = implode('","',$tab_gammes);
		
		$sql = 'SELECT pp.* FROM paniers_produits pp WHERE pp.id_panier = "'.$id_panier.'" AND pp.id_produit IN (SELECT pt.id_produit FROM produits_tree pt WHERE pt.id_tree IN ("'.$tab_gammes.'"))';
		$res = $this->bdd->query($sql);
		$list = array();
		
		while($rec = $this->bdd->fetch_array($res))
		{
			$list[] = $rec;
		}
		
		return $list;
	}
	
	// Recuperation du detail principal d'un produit
	function getInfosProduit($id_produit,$id_langue='fr')
	{
		$sql = '
		SELECT
			p.status AS status,
			(SELECT pd.id_detail FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS id_detail,
			(SELECT pd.stock FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS stock 
		FROM 
			produits p 
		WHERE 
			p.id_produit = "'.$id_produit.'"';
		
		$res = $this->bdd->query($sql);
		$rec = $this->bdd->fetch_array($res);
		
		return $rec;
	}
	
	// Ajout d'un produit dans la tableau paniers_kdos
	function addProduitKdoPanier($id_panier,$id_produit,$qte,$id_detail,$type)
	{
		$sql = 'INSERT INTO `paniers_cadeaux`(`id_panier`,`id_produit`,`quantite`,`id_details`,`type`,`added`,`updated`) VALUES("'.$id_panier.'","'.$id_produit.'","'.$qte.'","'.$id_detail.'","'.$type.'",NOW(),NOW())';
		$this->bdd->query($sql);
	}
	
	// On replace à 0 tous les produits offerts du panier
	function resetOffertPanierProduit($id_panier)
	{
		$sql = 'UPDATE `paniers_produits` SET offert = 0 WHERE id_panier = "'.$id_panier.'"';
		$this->bdd->query($sql);
	}
	
	// On place les produit selectionné en offert
	function setProduitOffertPanier($table,$id_produit,$id_details,$id_panier)
	{
		$sql = 'UPDATE '.$table.' SET offert = 1 WHERE id_produit = "'.$id_produit.'" AND id_details = "'.$id_details.'" AND id_panier = "'.$id_panier.'"';
		$this->bdd->query($sql);
	}
	
	// Recherche de la premiere commande d'un client
	function clientADejaCommander($id_client)
	{
		$sql = 'SELECT count(id_transaction) AS nb FROM transactions WHERE id_client = "'.$id_client.'" AND status = 1 AND etat > 0';
		$res = $this->bdd->query($sql);
		$rec = $this->bdd->fetch_array($res);
		
		if($rec['nb'] > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	// On reset les kdos dans la table panier kdo
	function resetListeKdosPanier($id_panier)
	{
		$sql = 'DELETE FROM `paniers_cadeaux` WHERE type = 1 AND id_panier = "'.$id_panier.'"';
		$this->bdd->query($sql);
	}
	
	// On reset les echantillons dans la table panier kdo
	function resetEchantillonsPanier($id_panier)
	{
		$sql = 'DELETE FROM `paniers_cadeaux` WHERE type = 0 AND id_panier = "'.$id_panier.'"';
		$this->bdd->query($sql);
	}
	
	function setCodeActif($id_code,$id_panier)
	{
		$sql = 'UPDATE `paniers_promos` SET actif = 1 WHERE id_panier = "'.$id_panier.'" AND id_code = "'.$id_code.'"';
		$this->bdd->query($sql);
	}
	
	function setCodeInactif($id_code,$id_panier)
	{
		$sql = 'UPDATE `paniers_promos` SET actif = 0 WHERE id_panier = "'.$id_panier.'" AND id_code = "'.$id_code.'"';
		$this->bdd->query($sql);
	}
	
	// Fonction qui nous donne toutes les infos sur un panier
	function getTotaux($id_panier,$id_langue='fr')
	{
		// Initialisation des variables
		$tab_prod_panier = array();
		$code_fdp = 0;
		$montant_total_hors_fdp = 0;
		$quantite_total_produits = 0;
		$poids_total = 0;
		$maxprice['prix_produit'] = 0;
		$minprice['prix_produit'] = 0;
		
		// Recuperation des infos du panier
		$this->get($id_panier,'id_panier');
		
		// On recupere la liste des produits
		$lProduits = $this->listeProduitsPanier($id_panier);
		
		// On recupere la liste des code promos
		$lPromos = $this->listePromosPanier($id_panier);
		
		// On replace à 0 tous les produits offerts du panier
		$this->resetOffertPanierProduit($id_panier);
		
		// On reset les kdos dans la table panier kdo il seront rajouté par le traitement suivant
		$this->resetListeKdosPanier($id_panier);
		
		// On check si on a un echantillon et pas de produit
		if(count($lProduits) == 0)
		{
			$this->resetEchantillonsPanier($id_panier);
		}
		
		//********************************************//
		//*** Calcul du poids et du total hors FDP ***//
		//********************************************//
		
			// Boucle sur la liste des produits
			foreach($lProduits as $key=>$p)
			{
				// Recupration des infos du produit
				$produit = $this->detailsProduitPanier($p['id_produit'],$p['id_details'],$id_langue);
				
				// On regarde si on prend le prix promo ou le normal
				if(date('Y-m-d') >= $produit['debut_promo'] && date('Y-m-d') <= $produit['fin_promo'])
				{
					// Calcul du prix du produit
					$prix_produit = $p['quantite'] * $produit['prix_promo'];
				}
				else
				{
					// Calcul du prix du produit
					$prix_produit = $p['quantite'] * $produit['prix'];
				}
				
				// On regarde si le produit est offert
				if($p['offert'] == 1)
				{
					$prix_produit = 0;
				}
				
				// On regarde si le produit est le plus cher pour les promos
				if($prix_produit != 0 && $prix_produit > $maxprice['prix_produit'])
				{
					$maxprice['id_produit'] = $p['id_produit'];
					$maxprice['id_details'] = $p['id_details'];
					$maxprice['table'] = 'paniers_produits';
					$maxprice['prix_produit'] = $prix_produit;
				}
				
				// On regarde si le produit est le moins cher pour les promos
				if($prix_produit != 0 && $prix_produit < $minprice['prix_produit'])
				{
					$minprice['id_produit'] = $p['id_produit'];
					$minprice['id_details'] = $p['id_details'];
					$minprice['table'] = 'paniers_produits';
					$minprice['prix_produit'] = $prix_produit;
				}
				
				// Calcul du total sans les frais de poort
				$montant_total_hors_fdp = $montant_total_hors_fdp + $prix_produit;
				
				// Calcul de la quantite totale de produits dans le panier
				$quantite_total_produits = $quantite_total_produits + $p['quantite'];
				
				// Calcul du poids total des produits
				$poids_total = $poids_total + ($p['quantite'] * $produit['poids']);
				
				// Tableau des produits du panier
				$tab_prod_panier[$key]['id_produit'] = $p['id_produit'];
				$tab_prod_panier[$key]['id_details'] = $p['id_details'];
				$tab_prod_panier[$key]['quantite'] = $p['quantite'];
			}
			
		//*****************************//
		//*** Calcul des reductions ***//
		//*****************************//
		
			// On initialise le montant de la reduction à 0
			$montant_reduc = 0;
		
			// On regarde si on a des codes promos en stock
			if(count($lPromos) > 0)
			{
				// Pour chaque code on va faire toutes les verification d'usage et sortir le montant de la reduction
				foreach($lPromos as $cp)
				{
					// Recuperation des infos du code promo
					$promo = $this->getCode($cp['id_code']);
					
					// Variable breaking vu que ca existe pas en php
					$break = false;
					
					// On check que le code promo est toujours online
					if($promo['status'] == 0 || $promo['status'] == 2)
					{
						$break = true;
					}
					
					// On regarde pour la premiere commande
					if($promo['premiere_cmde'] == 1)
					{
						// On regarde si on a un client connecté
						if(isset($_SESSION['client']) && $_SESSION['client']['id_client'] != '')
						{
							// On regarde si le client a déjà effectué une commande validée
							if($this->clientADejaCommander($_SESSION['client']['id_client']))
							{
								$break = true;
							}
						}
						else
						{
							$break = true;	
						}						
					}
					
					// On check que le nb d'utilisations n'est pas à 0
					if($promo['nb_utilisations'] == 0) 
					{
						$break = true;
					}
					
					// On check que l'on est bien dans la date d'utilisation du code
					if($promo['from'] > date('Y-m-d') || $promo['to'] < date('Y-m-d'))
					{
						$break = true;
					}
					
					// Si on a une restriction au niveau du groupe
					if($promo['id_groupe'] != 0)
					{
						// On check que le client est bien dans ce groupe
						if(!$this->checkGroupeClient($promo['id_groupe']))
						{
							$break = true;
						}
					}
					
					// Si il y a une restriction sur les categories
					if($promo['id_tree'] != '')
					{
						// Recuperation du tableau des categories restreintes
						$tab_gammes_autorize = explode(',',$promo['id_tree']);
						
						// Recuperation du tableau des categories présentes dans le panier
						$tab_gammes_panier = $this->recupGammesProduits($id_panier);
						
						// Recuperation du tableau des produits contenu dans le panier appartenant aux categories restreintes
						$tab_produits_autorize = $this->recupProduitsGammes($id_panier,$tab_gammes_autorize);
						$nb_produits_panier_autorize = 0;
						
						foreach($tab_produits_autorize as $key=>$v)
						{
							$nb_produits_panier_autorize = $nb_produits_panier_autorize + $v['quantite'];
						}
						
						// On check si au moins une categorie est presente dans le panier
						if($nb_produits_panier_autorize == 0)
						{
							$break = true;
						}
						
						// On check si on a un nb minimum renseigné pour le code
						if($promo['nb_minimum'] > 0)
						{
							// On check si le nombre de produits dans le panier est superieur au minimum
							if($nb_produits_panier_autorize < $promo['nb_minimum'])
							{
								$break = true;
							}
						}
						
						// On check si on a un seuil renseigné pour le code
						if($promo['seuil'] > 0)
						{
							// Initialisation du montant
							$montant_total_produits_autorize = 0;
							
							// Calcul du montant des produits autorisés dans le panier
							foreach($tab_produits_autorize as $p)
							{
								// Recupration des infos du produit
								$produit = $this->detailsProduitPanier($p['id_produit'],$p['id_details'],$id_langue);
								
								// On regarde si on prend le prix promo ou le normal
								if(date('Y-m-d') >= $produit['debut_promo'] && date('Y-m-d') <= $produit['fin_promo'])
								{
									// Calcul du prix du produit
									$prix_produit = $p['quantite'] * $produit['prix_promo'];
								}
								else
								{
									// Calcul du prix du produit
									$prix_produit = $p['quantite'] * $produit['prix'];
								}
								
								// Calcul du total sans les frais de poort
								$montant_total_produits_autorize = $montant_total_produits_autorize + $prix_produit;
							}
							
							// On check le montant avec le seuil
							if($montant_total_produits_autorize < $promo['seuil'])
							{
								$break = true;
							}
						}
					}
					
					// Si il y a une restriction sur les categories en plus
					if($promo['id_tree2'] != '')
					{
						// Recuperation du tableau des categories restreintes
						$tab_gammes_autorize = explode(',',$promo['id_tree2']);
						
						// Recuperation du tableau des categories présentes dans le panier
						$tab_gammes_panier = $this->recupGammesProduits($id_panier);
						
						// Recuperation du tableau des produits contenu dans le panier appartenant aux categories restreintes
						$tab_produits_autorize = $this->recupProduitsGammes($id_panier,$tab_gammes_autorize);
						$nb_produits_panier_autorize = 0;
						
						foreach($tab_produits_autorize as $key=>$v)
						{
							$nb_produits_panier_autorize = $nb_produits_panier_autorize + $v['quantite'];
						}
						
						// On check si au moins une categorie est presente dans le panier
						if($nb_produits_panier_autorize == 0)
						{
							$break = true;
						}
						
						// On check si on a un nb minimum renseigné pour le code
						if($promo['nb_minimum2'] > 0)
						{
							// On check si le nombre de produits dans le panier est superieur au minimum
							if($nb_produits_panier_autorize < $promo['nb_minimum2'])
							{
								$break = true;
							}
						}
						
						// On check si on a un seuil renseigné pour le code
						if($promo['seuil'] > 0)
						{
							// Initialisation du montant
							$montant_total_produits_autorize = 0;
							
							// Calcul du montant des produits autorisés dans le panier
							foreach($tab_produits_autorize as $p)
							{
								// Recupration des infos du produit
								$produit = $this->detailsProduitPanier($p['id_produit'],$p['id_details'],$id_langue);
								
								// On regarde si on prend le prix promo ou le normal
								if(date('Y-m-d') >= $produit['debut_promo'] && date('Y-m-d') <= $produit['fin_promo'])
								{
									// Calcul du prix du produit
									$prix_produit = $p['quantite'] * $produit['prix_promo'];
								}
								else
								{
									// Calcul du prix du produit
									$prix_produit = $p['quantite'] * $produit['prix'];
								}
								
								// Calcul du total sans les frais de poort
								$montant_total_produits_autorize = $montant_total_produits_autorize + $prix_produit;
							}
							
							// On check le montant avec le seuil
							if($montant_total_produits_autorize < $promo['seuil'])
							{
								$break = true;
							}
						}
					}
					
					// Si on a une restriction sur les produits
					if($promo['id_produit'] != '')
					{						
						// Recuperation du tableau des produits restreint
						$tab_produits_autorize = explode(',',$promo['id_produit']);
						
						// Recuperation du tableau des produits presents dans le panier
						$tab_produits_panier = $tab_prod_panier;
						
						// Init tableau
						$tab_produits_panier_autorize = array();
						$nb_produits_panier_autorize = 0;
						
						// Recuperation du tableau des produits autorisés dans le panier
						foreach($tab_produits_panier as $key=>$v)
						{
							if(in_array($v['id_produit'],$tab_produits_autorize))
							{
								$tab_produits_panier_autorize[$key]['id_produit'] = $v['id_produit'];
								$tab_produits_panier_autorize[$key]['id_details'] = $v['id_details'];
								$tab_produits_panier_autorize[$key]['quantite'] = $v['quantite'];
								$nb_produits_panier_autorize = $nb_produits_panier_autorize + $v['quantite'];
							}
						}
						
						// On check si au moins un  produit est present dans le panier
						if($nb_produits_panier_autorize == 0)
						{
							$break = true;
						}
						
						// On check si on a un nb minimum renseigné pour le code
						if($promo['nb_minimum'] > 0)
						{				
							// On check si le nombre de produits dans le panier est superieur au minimum
							if($nb_produits_panier_autorize < $promo['nb_minimum'])
							{
								$break = true;
							}
						}
						
						// On check si on a un seuil renseigné pour le code
						if($promo['seuil'] > 0)
						{
							// Initialisation du montant
							$montant_total_produits_autorize = 0;
												
							// Calcul du montant des produits autorisés dans le panier
							foreach($tab_produits_panier_autorize as $p)
							{
								// Recupration des infos du produit
								$produit = $this->detailsProduitPanier($p['id_produit'],$p['id_details'],$id_langue);
								
								// On regarde si on prend le prix promo ou le normal
								if(date('Y-m-d') >= $produit['debut_promo'] && date('Y-m-d') <= $produit['fin_promo'])
								{
									// Calcul du prix du produit
									$prix_produit = $p['quantite'] * $produit['prix_promo'];
								}
								else
								{
									// Calcul du prix du produit
									$prix_produit = $p['quantite'] * $produit['prix'];
								}
								
								// Calcul du total sans les frais de poort
								$montant_total_produits_autorize = $montant_total_produits_autorize + $prix_produit;
							}
							
							// On check le montant avec le seuil
							if($montant_total_produits_autorize < $promo['seuil'])
							{
								$break = true;
							}
						}
					}
					
					// Si on a une restriction sur les produits en plus
					if($promo['id_produit2'] != '')
					{
						// Recuperation du tableau des produits restreint
						$tab_produits_autorize = explode(',',$promo['id_produit2']);
						
						// Recuperation du tableau des produits presents dans le panier
						$tab_produits_panier = $tab_prod_panier;
						
						// Init tableau
						$tab_produits_panier_autorize = array();
						$nb_produits_panier_autorize = 0;
						
						// Recuperation du tableau des produits autorisés dans le panier
						foreach($tab_produits_panier as $key=>$v)
						{
							if(in_array($v['id_produit'],$tab_produits_autorize))
							{
								$tab_produits_panier_autorize[$key]['id_produit'] = $v['id_produit'];
								$tab_produits_panier_autorize[$key]['id_details'] = $v['id_details'];
								$tab_produits_panier_autorize[$key]['quantite'] = $v['quantite'];
								$nb_produits_panier_autorize = $nb_produits_panier_autorize + $v['quantite'];
							}
						}
						
						// On check si au moins un  produit est present dans le panier
						if($nb_produits_panier_autorize == 0)
						{
							$break = true;
						}
						
						// On check si on a un nb minimum renseigné pour le code
						if($promo['nb_minimum2'] > 0)
						{				
							// On check si le nombre de produits dans le panier est superieur au minimum
							if($nb_produits_panier_autorize < $promo['nb_minimum2'])
							{
								$break = true;
							}
						}
						
						// On check si on a un seuil renseigné pour le code
						if($promo['seuil'] > 0)
						{
							// Initialisation du montant
							$montant_total_produits_autorize = 0;
												
							// Calcul du montant des produits autorisés dans le panier
							foreach($tab_produits_panier_autorize as $p)
							{
								// Recupration des infos du produit
								$produit = $this->detailsProduitPanier($p['id_produit'],$p['id_details'],$id_langue);
								
								// On regarde si on prend le prix promo ou le normal
								if(date('Y-m-d') >= $produit['debut_promo'] && date('Y-m-d') <= $produit['fin_promo'])
								{
									// Calcul du prix du produit
									$prix_produit = $p['quantite'] * $produit['prix_promo'];
								}
								else
								{
									// Calcul du prix du produit
									$prix_produit = $p['quantite'] * $produit['prix'];
								}
								
								// Calcul du total sans les frais de poort
								$montant_total_produits_autorize = $montant_total_produits_autorize + $prix_produit;
							}
							
							// On check le montant avec le seuil
							if($montant_total_produits_autorize < $promo['seuil'])
							{
								$break = true;
							}
						}
					}
					
					// On regarde si le montant sans les frais de port respect le seuil du code promo
					if($montant_total_hors_fdp < $promo['seuil'])
					{
						$break = true;
					}
					
					//*******************************************************************************************************//
					//*** On commence le traitement apres tous ces checks en bois si Queen et son break true n'est pas la ***//
					//*******************************************************************************************************//
					if(!$break)
					{
						// On regarde deja si le code promo donne droit au fdp gratuit
						if($promo['fdp'] == 1)
						{
							// Si on a pas de type dans le panier on prend celui par defaut
							$id_type = ($this->id_type == 0?$this->recupParams('Type FDP'):$this->id_type);
				
							// Recuperation du montant des frais de port et du seuil de gratuité
							$fdp = $this->getFDP($id_type,1);
							
							// On regarde si le montant du panier dépasse le seuil dans ce cas les FDP ont un montant reduit
							$montant_fdp = $fdp['montant_reduit'];
							
							// On indique qu'un code nous donne les FDP
							$code_fdp = 1;
						}
						
						// On regarde si ya des criteres categories et/ou produit pour le calcul de la reduction
						if($promo['id_tree'] != '' || $promo['id_produit'] != '')
						{
							// Initialisation des tableaux
							$tab_produits_id_tree = array();
							$tab_produits_id_produit = array();
							$tab_produits_id_tree2 = array();
							$tab_produits_id_produit2 = array();
							
							if($promo['id_tree'] != '')
							{
								$tab_gammes_autorize = explode(',',$promo['id_tree']);
								$tab_produits_id_tree = $this->recupProduitsGammes($id_panier,$tab_gammes_autorize);
							}
							
							if($promo['id_produit'] != '')
							{
								$tab_produits_id_produit = explode(',',$promo['id_produit']);	
							}
							
							if($promo['id_tree2'] != '')
							{
								$tab_gammes_autorize = explode(',',$promo['id_tree2']);
								$tab_produits_id_tree2 = $this->recupProduitsGammes($id_panier,$tab_gammes_autorize);
							}
							
							if($promo['id_produit2'] != '')
							{
								$tab_produits_id_produit2 = explode(',',$promo['id_produit2']);	
							}
							
							// Recuperation du tableau final des produits beneficiant des reductions
							$tab_produits_autorize = $tab_produits_id_tree + $tab_produits_id_produit + $tab_produits_id_tree2+ $tab_produits_id_produit2;
							
							// Init total produit autorize
							$total_produits_aurize = 0;
							
							// Calcul du montant de la reduction sur les produits
							foreach($lProduits as $p)
							{
								if(in_array($p['id_produit'],$tab_produits_autorize))
								{
									// Recupration des infos du produit
									$produit = $this->detailsProduitPanier($p['id_produit'],$p['id_details'],$id_langue);
									
									// On regarde si le produit est offert dans ce cas pas de reduc dessus
									if($p['offert'] == 0)
									{									
										// On regarde si on prend le prix promo ou le normal
										if(date('Y-m-d') >= $produit['debut_promo'] && date('Y-m-d') <= $produit['fin_promo'])
										{
											// Calcul du prix du produit
											$prix_produit = $produit['prix_promo'];
										}
										else
										{
											// Calcul du prix du produit
											$prix_produit = $produit['prix'];
										}
										
										// On ajoute au total
										$total_produits_aurize = $total_produits_aurize + ($p['quantite'] * $prix_produit);
									}
								}
							}
														
							// On regarde le type de promo et on calcul le montant de la reduction sur les produits autorize
							if($promo['type'] == 'Remise')
							{
								$reduc_app = $promo['value'];
								$montant_reduc = $montant_reduc + $reduc_app;
							}
							else
							{
								$reduc_app = $total_produits_aurize - ($total_produits_aurize * ((100 - $promo['value']) / 100));
								$montant_reduc = $montant_reduc + $reduc_app;
							}
						}
						else
						{
							// On regarde le type de promo et on calcul le montant de la reduction
							if($promo['type'] == 'Remise')
							{
								$montant_reduc = $montant_reduc + $promo['value'];
							}
							else
							{
								$montant_reduc = $montant_reduc + ($montant_total_hors_fdp - ($montant_total_hors_fdp * ((100-$promo['value'])/100)));
							}	
						}
						
						// On regarde si on a un produit cadeau dans le code
						if($promo['id_produit_kdo'] > 0)
						{
							// On recupere le details principal du produit
							$prod = $this->getInfosProduit($promo['id_produit_kdo'],$id_langue);
							
							// On check que le produit est bien en stock et en ligne
							if($prod['status'] == 1 && $prod['stock'] > 0)
							{							
								// On ajoute le produit dans la table kdo
								$this->addProduitKdoPanier($id_panier,$promo['id_produit_kdo'],1,$prod['id_detail'],1);
							}
						}
						
						// On regarde si le moins cher des produits est offert
						if($promo['moins_cher'] == 1)
						{
							// On place le produit comme offert dans le panier
							$this->setProduitOffertPanier($minprice['table'],$minprice['id_produit'],$minprice['id_details'],$id_panier);
						}
						
						// Onregarde si le plus cher des articles est offert
						if($promo['plus_cher'] == 1)
						{
							// On place le produit comme offert dans le panier
							$this->setProduitOffertPanier($maxprice['table'],$maxprice['id_produit'],$maxprice['id_details'],$id_panier);
						}
						
						// On indique que le code est actif
						$this->setCodeActif($cp['id_code'],$id_panier);
					}
					else
					{
						$montant_reduc = $montant_reduc + 0;
						
						// On indique que le code est inactif
						$this->setCodeInactif($cp['id_code'],$id_panier);
					}
				}
			}
			else
			{
				// Pas de promos, pas de reducs (comme pas de bras, pas de chocolat !
				$montant_reduc = 0;
			}
			
		//********************************//
		//*** Calcul des frais de port ***//
		//********************************//
		
			// On regarde si les promotions n'ont pas offert les FDP
			if(!isset($montant_fdp))
			{		
				// Si on a pas de type dans le panier on prend celui par defaut
				$id_type = ($this->id_type == 0?$this->recupParams('Type FDP'):$this->id_type);
				
				// Recuperation du montant des frais de port et du seuil de gratuité
				$fdp = $this->getFDP($id_type,1);
				
				// On regarde si le montant du panier dépasse le seuil dans ce cas les FDP ont un montant reduit
				$montant_fdp = (($montant_total_hors_fdp - $montant_reduc) >= $fdp['seuil']?$fdp['montant_reduit']:$fdp['montant']);
				
				// Recuperation du montant restant pour les frais de port gratuit
				$montant_restant_fdp = $fdp['seuil'] - ($montant_total_hors_fdp - $montant_reduc);
			}
		
		//************************************//
		//*** Calcul du total avec les FDP ***//
		//************************************//
			
			$montant_total = $montant_total_hors_fdp - $montant_reduc + $montant_fdp + $montant_emballage;
			
			// Cas d'un montant nul ou negatif
			if($montant_total <= 0)
			{
				$montant_total = 0;
			}
			
		//***********************************//
		//*** Calcul du montant de la tva ***//
		//***********************************//
		
			// Recupertion du taux de tva
			$taux = $this->recupParams('TVA');
			
			// Cas d'un montant nul ou negatif
			if($montant_total <= 0)
			{
				// Calcul du montant total HT
				$montant_total_ht = 0;
				
				// Calcul du montant de la tva
				$montant_tva = 0;
			}
			else
			{			
				// Calcul du montant total HT
				$montant_total_ht = ($montant_total/(1+($taux/100)));
				
				// Calcul du montant de la tva
				$montant_tva = $montant_total - $montant_total_ht;
			}
			
		//*************************************//
		//*** On range tout dans le placard ***//
		//*************************************//	
		
			$result = array(
						'montant_total_hors_fdp'=>$montant_total_hors_fdp,
						'quantite_total_produits'=>$quantite_total_produits,
						'poids_total'=>$poids_total,
						'montant_fdp'=>$montant_fdp,
						'montant_reduc'=>$montant_reduc,
						'montant_total'=>$montant_total,
						'montant_total_ht'=>$montant_total_ht,
						'montant_tva'=>$montant_tva,
						'montant_restant_fdp'=>$montant_restant_fdp,
						'code_fdp'=>$code_fdp);						

		return $result; 
	}
}