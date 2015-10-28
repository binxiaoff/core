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
class transactions_crud
{
	
	public $id_transaction;
	public $id_panier;
	public $id_backpayline;
	public $id_offre_bienvenue_detail;
	public $id_parrain_filleul;
	public $id_virement;
	public $id_prelevement;
	public $id_echeancier;
	public $id_echeancier_emprunteur;
	public $id_bid_remb;
	public $id_loan_remb;
	public $id_project;
	public $id_client;
	public $id_partenaire;
	public $id_livraison;
	public $id_facturation;
	public $id_type;
	public $fdp;
	public $montant;
	public $montant_unilend;
	public $montant_etat;
	public $montant_reduc;
	public $id_langue;
	public $date_transaction;
	public $type_paiement;
	public $status;
	public $etat;
	public $transaction;
	public $type_transaction;
	public $recouvrement;
	public $display;
	public $ip_client;
	public $serialize_paniers;
	public $serialize_paniers_produits;
	public $serialize_paniers_promos;
	public $serialize_paniers_cadeaux;
	public $serialize_payline;
	public $added;
	public $updated;
	public $civilite_liv;
	public $nom_liv;
	public $prenom_liv;
	public $societe_liv;
	public $adresse1_liv;
	public $adresse2_liv;
	public $adresse3_liv;
	public $cp_liv;
	public $ville_liv;
	public $id_pays_liv;
	public $civilite_fac;
	public $nom_fac;
	public $prenom_fac;
	public $societe_fac;
	public $adresse1_fac;
	public $adresse2_fac;
	public $adresse3_fac;
	public $cp_fac;
	public $ville_fac;
	public $id_pays_fac;
	public $colis;

	
	function transactions($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_transaction = '';
		$this->id_panier = '';
		$this->id_backpayline = '';
		$this->id_offre_bienvenue_detail = '';
		$this->id_parrain_filleul = '';
		$this->id_virement = '';
		$this->id_prelevement = '';
		$this->id_echeancier = '';
		$this->id_echeancier_emprunteur = '';
		$this->id_bid_remb = '';
		$this->id_loan_remb = '';
		$this->id_project = '';
		$this->id_client = '';
		$this->id_partenaire = '';
		$this->id_livraison = '';
		$this->id_facturation = '';
		$this->id_type = '';
		$this->fdp = '';
		$this->montant = '';
		$this->montant_unilend = '';
		$this->montant_etat = '';
		$this->montant_reduc = '';
		$this->id_langue = '';
		$this->date_transaction = '';
		$this->type_paiement = '';
		$this->status = '';
		$this->etat = '';
		$this->transaction = '';
		$this->type_transaction = '';
		$this->recouvrement = '';
		$this->display = '';
		$this->ip_client = '';
		$this->serialize_paniers = '';
		$this->serialize_paniers_produits = '';
		$this->serialize_paniers_promos = '';
		$this->serialize_paniers_cadeaux = '';
		$this->serialize_payline = '';
		$this->added = '';
		$this->updated = '';
		$this->civilite_liv = '';
		$this->nom_liv = '';
		$this->prenom_liv = '';
		$this->societe_liv = '';
		$this->adresse1_liv = '';
		$this->adresse2_liv = '';
		$this->adresse3_liv = '';
		$this->cp_liv = '';
		$this->ville_liv = '';
		$this->id_pays_liv = '';
		$this->civilite_fac = '';
		$this->nom_fac = '';
		$this->prenom_fac = '';
		$this->societe_fac = '';
		$this->adresse1_fac = '';
		$this->adresse2_fac = '';
		$this->adresse3_fac = '';
		$this->cp_fac = '';
		$this->ville_fac = '';
		$this->id_pays_fac = '';
		$this->colis = '';

	}
	
	function get($id,$field='id_transaction')
	{
		$sql = 'SELECT * FROM  `transactions` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_transaction = $record['id_transaction'];
			$this->id_panier = $record['id_panier'];
			$this->id_backpayline = $record['id_backpayline'];
			$this->id_offre_bienvenue_detail = $record['id_offre_bienvenue_detail'];
			$this->id_parrain_filleul = $record['id_parrain_filleul'];
			$this->id_virement = $record['id_virement'];
			$this->id_prelevement = $record['id_prelevement'];
			$this->id_echeancier = $record['id_echeancier'];
			$this->id_echeancier_emprunteur = $record['id_echeancier_emprunteur'];
			$this->id_bid_remb = $record['id_bid_remb'];
			$this->id_loan_remb = $record['id_loan_remb'];
			$this->id_project = $record['id_project'];
			$this->id_client = $record['id_client'];
			$this->id_partenaire = $record['id_partenaire'];
			$this->id_livraison = $record['id_livraison'];
			$this->id_facturation = $record['id_facturation'];
			$this->id_type = $record['id_type'];
			$this->fdp = $record['fdp'];
			$this->montant = $record['montant'];
			$this->montant_unilend = $record['montant_unilend'];
			$this->montant_etat = $record['montant_etat'];
			$this->montant_reduc = $record['montant_reduc'];
			$this->id_langue = $record['id_langue'];
			$this->date_transaction = $record['date_transaction'];
			$this->type_paiement = $record['type_paiement'];
			$this->status = $record['status'];
			$this->etat = $record['etat'];
			$this->transaction = $record['transaction'];
			$this->type_transaction = $record['type_transaction'];
			$this->recouvrement = $record['recouvrement'];
			$this->display = $record['display'];
			$this->ip_client = $record['ip_client'];
			$this->serialize_paniers = $record['serialize_paniers'];
			$this->serialize_paniers_produits = $record['serialize_paniers_produits'];
			$this->serialize_paniers_promos = $record['serialize_paniers_promos'];
			$this->serialize_paniers_cadeaux = $record['serialize_paniers_cadeaux'];
			$this->serialize_payline = $record['serialize_payline'];
			$this->added = $record['added'];
			$this->updated = $record['updated'];
			$this->civilite_liv = $record['civilite_liv'];
			$this->nom_liv = $record['nom_liv'];
			$this->prenom_liv = $record['prenom_liv'];
			$this->societe_liv = $record['societe_liv'];
			$this->adresse1_liv = $record['adresse1_liv'];
			$this->adresse2_liv = $record['adresse2_liv'];
			$this->adresse3_liv = $record['adresse3_liv'];
			$this->cp_liv = $record['cp_liv'];
			$this->ville_liv = $record['ville_liv'];
			$this->id_pays_liv = $record['id_pays_liv'];
			$this->civilite_fac = $record['civilite_fac'];
			$this->nom_fac = $record['nom_fac'];
			$this->prenom_fac = $record['prenom_fac'];
			$this->societe_fac = $record['societe_fac'];
			$this->adresse1_fac = $record['adresse1_fac'];
			$this->adresse2_fac = $record['adresse2_fac'];
			$this->adresse3_fac = $record['adresse3_fac'];
			$this->cp_fac = $record['cp_fac'];
			$this->ville_fac = $record['ville_fac'];
			$this->id_pays_fac = $record['id_pays_fac'];
			$this->colis = $record['colis'];

			return true;
		}
		else
		{
			$this->unsetData();
			return false;
		}
	}
	
	function update($cs='')
	{
		$this->id_transaction = $this->bdd->escape_string($this->id_transaction);
		$this->id_panier = $this->bdd->escape_string($this->id_panier);
		$this->id_backpayline = $this->bdd->escape_string($this->id_backpayline);
		$this->id_offre_bienvenue_detail = $this->bdd->escape_string($this->id_offre_bienvenue_detail);
		$this->id_parrain_filleul = $this->bdd->escape_string($this->id_parrain_filleul);
		$this->id_virement = $this->bdd->escape_string($this->id_virement);
		$this->id_prelevement = $this->bdd->escape_string($this->id_prelevement);
		$this->id_echeancier = $this->bdd->escape_string($this->id_echeancier);
		$this->id_echeancier_emprunteur = $this->bdd->escape_string($this->id_echeancier_emprunteur);
		$this->id_bid_remb = $this->bdd->escape_string($this->id_bid_remb);
		$this->id_loan_remb = $this->bdd->escape_string($this->id_loan_remb);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_partenaire = $this->bdd->escape_string($this->id_partenaire);
		$this->id_livraison = $this->bdd->escape_string($this->id_livraison);
		$this->id_facturation = $this->bdd->escape_string($this->id_facturation);
		$this->id_type = $this->bdd->escape_string($this->id_type);
		$this->fdp = $this->bdd->escape_string($this->fdp);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->montant_unilend = $this->bdd->escape_string($this->montant_unilend);
		$this->montant_etat = $this->bdd->escape_string($this->montant_etat);
		$this->montant_reduc = $this->bdd->escape_string($this->montant_reduc);
		$this->id_langue = $this->bdd->escape_string($this->id_langue);
		$this->date_transaction = $this->bdd->escape_string($this->date_transaction);
		$this->type_paiement = $this->bdd->escape_string($this->type_paiement);
		$this->status = $this->bdd->escape_string($this->status);
		$this->etat = $this->bdd->escape_string($this->etat);
		$this->transaction = $this->bdd->escape_string($this->transaction);
		$this->type_transaction = $this->bdd->escape_string($this->type_transaction);
		$this->recouvrement = $this->bdd->escape_string($this->recouvrement);
		$this->display = $this->bdd->escape_string($this->display);
		$this->ip_client = $this->bdd->escape_string($this->ip_client);
		$this->serialize_paniers = $this->bdd->escape_string($this->serialize_paniers);
		$this->serialize_paniers_produits = $this->bdd->escape_string($this->serialize_paniers_produits);
		$this->serialize_paniers_promos = $this->bdd->escape_string($this->serialize_paniers_promos);
		$this->serialize_paniers_cadeaux = $this->bdd->escape_string($this->serialize_paniers_cadeaux);
		$this->serialize_payline = $this->bdd->escape_string($this->serialize_payline);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->civilite_liv = $this->bdd->escape_string($this->civilite_liv);
		$this->nom_liv = $this->bdd->escape_string($this->nom_liv);
		$this->prenom_liv = $this->bdd->escape_string($this->prenom_liv);
		$this->societe_liv = $this->bdd->escape_string($this->societe_liv);
		$this->adresse1_liv = $this->bdd->escape_string($this->adresse1_liv);
		$this->adresse2_liv = $this->bdd->escape_string($this->adresse2_liv);
		$this->adresse3_liv = $this->bdd->escape_string($this->adresse3_liv);
		$this->cp_liv = $this->bdd->escape_string($this->cp_liv);
		$this->ville_liv = $this->bdd->escape_string($this->ville_liv);
		$this->id_pays_liv = $this->bdd->escape_string($this->id_pays_liv);
		$this->civilite_fac = $this->bdd->escape_string($this->civilite_fac);
		$this->nom_fac = $this->bdd->escape_string($this->nom_fac);
		$this->prenom_fac = $this->bdd->escape_string($this->prenom_fac);
		$this->societe_fac = $this->bdd->escape_string($this->societe_fac);
		$this->adresse1_fac = $this->bdd->escape_string($this->adresse1_fac);
		$this->adresse2_fac = $this->bdd->escape_string($this->adresse2_fac);
		$this->adresse3_fac = $this->bdd->escape_string($this->adresse3_fac);
		$this->cp_fac = $this->bdd->escape_string($this->cp_fac);
		$this->ville_fac = $this->bdd->escape_string($this->ville_fac);
		$this->id_pays_fac = $this->bdd->escape_string($this->id_pays_fac);
		$this->colis = $this->bdd->escape_string($this->colis);

		
<<<<<<< HEAD
		$sql = 'UPDATE `transactions` SET `id_panier`="'.$this->id_panier.'",`id_backpayline`="'.$this->id_backpayline.'",`id_offre_bienvenue_detail`="'.$this->id_offre_bienvenue_detail.'",`id_parrain_filleul`="'.$this->id_parrain_filleul.'",`id_virement`="'.$this->id_virement.'",`id_prelevement`="'.$this->id_prelevement.'",`id_echeancier`="'.$this->id_echeancier.'",`id_echeancier_emprunteur`="'.$this->id_echeancier_emprunteur.'",`id_bid_remb`="'.$this->id_bid_remb.'",`id_loan_remb`="'.$this->id_loan_remb.'",`id_project`="'.$this->id_project.'",`id_client`="'.$this->id_client.'",`id_partenaire`="'.$this->id_partenaire.'",`id_livraison`="'.$this->id_livraison.'",`id_facturation`="'.$this->id_facturation.'",`id_type`="'.$this->id_type.'",`fdp`="'.$this->fdp.'",`montant`="'.$this->montant.'",`montant_unilend`="'.$this->montant_unilend.'",`montant_etat`="'.$this->montant_etat.'",`montant_reduc`="'.$this->montant_reduc.'",`id_langue`="'.$this->id_langue.'",`date_transaction`="'.$this->date_transaction.'",`type_paiement`="'.$this->type_paiement.'",`status`="'.$this->status.'",`etat`="'.$this->etat.'",`transaction`="'.$this->transaction.'",`type_transaction`="'.$this->type_transaction.'",`recouvrement`="'.$this->recouvrement.'",`display`="'.$this->display.'",`ip_client`="'.$this->ip_client.'",`serialize_paniers`="'.$this->serialize_paniers.'",`serialize_paniers_produits`="'.$this->serialize_paniers_produits.'",`serialize_paniers_promos`="'.$this->serialize_paniers_promos.'",`serialize_paniers_cadeaux`="'.$this->serialize_paniers_cadeaux.'",`serialize_payline`="'.$this->serialize_payline.'",`added`="'.$this->added.'",`updated`=NOW(),`civilite_liv`="'.$this->civilite_liv.'",`nom_liv`="'.$this->nom_liv.'",`prenom_liv`="'.$this->prenom_liv.'",`societe_liv`="'.$this->societe_liv.'",`adresse1_liv`="'.$this->adresse1_liv.'",`adresse2_liv`="'.$this->adresse2_liv.'",`adresse3_liv`="'.$this->adresse3_liv.'",`cp_liv`="'.$this->cp_liv.'",`ville_liv`="'.$this->ville_liv.'",`id_pays_liv`="'.$this->id_pays_liv.'",`civilite_fac`="'.$this->civilite_fac.'",`nom_fac`="'.$this->nom_fac.'",`prenom_fac`="'.$this->prenom_fac.'",`societe_fac`="'.$this->societe_fac.'",`adresse1_fac`="'.$this->adresse1_fac.'",`adresse2_fac`="'.$this->adresse2_fac.'",`adresse3_fac`="'.$this->adresse3_fac.'",`cp_fac`="'.$this->cp_fac.'",`ville_fac`="'.$this->ville_fac.'",`id_pays_fac`="'.$this->id_pays_fac.'",`colis`="'.$this->colis.'" WHERE id_transaction="'.$this->id_transaction.'"';
=======
		$sql = 'UPDATE `transactions` SET `id_panier`="'.$this->id_panier.'",`id_backpayline`="'.$this->id_backpayline.'",`id_offre_bienvenue_detail`="'.$this->id_offre_bienvenue_detail.'",`id_parrain_filleul`="'.$this->id_parrain_filleul.'",`id_virement`="'.$this->id_virement.'",`id_prelevement`="'.$this->id_prelevement.'",`id_echeancier`="'.$this->id_echeancier.'",`id_echeancier_emprunteur`="'.$this->id_echeancier_emprunteur.'",`id_bid_remb`="'.$this->id_bid_remb.'",`id_loan_remb`="'.$this->id_loan_remb.'",`id_project`="'.$this->id_project.'",`id_client`="'.$this->id_client.'",`id_partenaire`="'.$this->id_partenaire.'",`id_livraison`="'.$this->id_livraison.'",`id_facturation`="'.$this->id_facturation.'",`id_type`="'.$this->id_type.'",`fdp`="'.$this->fdp.'",`montant`="'.$this->montant.'",`montant_unilend`="'.$this->montant_unilend.'",`montant_etat`="'.$this->montant_etat.'",`montant_reduc`="'.$this->montant_reduc.'",`id_langue`="'.$this->id_langue.'",`date_transaction`="'.$this->date_transaction.'",`type_paiement`="'.$this->type_paiement.'",`status`="'.$this->status.'",`etat`="'.$this->etat.'",`transaction`="'.$this->transaction.'",`type_transaction`="'.$this->type_transaction.'",`display`="'.$this->display.'",`ip_client`="'.$this->ip_client.'",`serialize_paniers`="'.$this->serialize_paniers.'",`serialize_paniers_produits`="'.$this->serialize_paniers_produits.'",`serialize_paniers_promos`="'.$this->serialize_paniers_promos.'",`serialize_paniers_cadeaux`="'.$this->serialize_paniers_cadeaux.'",`serialize_payline`="'.$this->serialize_payline.'",`added`="'.$this->added.'",`updated`=NOW(),`civilite_liv`="'.$this->civilite_liv.'",`nom_liv`="'.$this->nom_liv.'",`prenom_liv`="'.$this->prenom_liv.'",`societe_liv`="'.$this->societe_liv.'",`adresse1_liv`="'.$this->adresse1_liv.'",`adresse2_liv`="'.$this->adresse2_liv.'",`adresse3_liv`="'.$this->adresse3_liv.'",`cp_liv`="'.$this->cp_liv.'",`ville_liv`="'.$this->ville_liv.'",`id_pays_liv`="'.$this->id_pays_liv.'",`civilite_fac`="'.$this->civilite_fac.'",`nom_fac`="'.$this->nom_fac.'",`prenom_fac`="'.$this->prenom_fac.'",`societe_fac`="'.$this->societe_fac.'",`adresse1_fac`="'.$this->adresse1_fac.'",`adresse2_fac`="'.$this->adresse2_fac.'",`adresse3_fac`="'.$this->adresse3_fac.'",`cp_fac`="'.$this->cp_fac.'",`ville_fac`="'.$this->ville_fac.'",`id_pays_fac`="'.$this->id_pays_fac.'",`colis`="'.$this->colis.'" WHERE id_transaction="'.$this->id_transaction.'"';
>>>>>>> statuts-emprunteurs
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_transaction,'id_transaction');
	}
	
	function delete($id,$field='id_transaction')
	{
		if($id=='')
			$id = $this->id_transaction;
		$sql = 'DELETE FROM `transactions` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_transaction = $this->bdd->escape_string($this->id_transaction);
		$this->id_panier = $this->bdd->escape_string($this->id_panier);
		$this->id_backpayline = $this->bdd->escape_string($this->id_backpayline);
		$this->id_offre_bienvenue_detail = $this->bdd->escape_string($this->id_offre_bienvenue_detail);
		$this->id_parrain_filleul = $this->bdd->escape_string($this->id_parrain_filleul);
		$this->id_virement = $this->bdd->escape_string($this->id_virement);
		$this->id_prelevement = $this->bdd->escape_string($this->id_prelevement);
		$this->id_echeancier = $this->bdd->escape_string($this->id_echeancier);
		$this->id_echeancier_emprunteur = $this->bdd->escape_string($this->id_echeancier_emprunteur);
		$this->id_bid_remb = $this->bdd->escape_string($this->id_bid_remb);
		$this->id_loan_remb = $this->bdd->escape_string($this->id_loan_remb);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_partenaire = $this->bdd->escape_string($this->id_partenaire);
		$this->id_livraison = $this->bdd->escape_string($this->id_livraison);
		$this->id_facturation = $this->bdd->escape_string($this->id_facturation);
		$this->id_type = $this->bdd->escape_string($this->id_type);
		$this->fdp = $this->bdd->escape_string($this->fdp);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->montant_unilend = $this->bdd->escape_string($this->montant_unilend);
		$this->montant_etat = $this->bdd->escape_string($this->montant_etat);
		$this->montant_reduc = $this->bdd->escape_string($this->montant_reduc);
		$this->id_langue = $this->bdd->escape_string($this->id_langue);
		$this->date_transaction = $this->bdd->escape_string($this->date_transaction);
		$this->type_paiement = $this->bdd->escape_string($this->type_paiement);
		$this->status = $this->bdd->escape_string($this->status);
		$this->etat = $this->bdd->escape_string($this->etat);
		$this->transaction = $this->bdd->escape_string($this->transaction);
		$this->type_transaction = $this->bdd->escape_string($this->type_transaction);
		$this->recouvrement = $this->bdd->escape_string($this->recouvrement);
		$this->display = $this->bdd->escape_string($this->display);
		$this->ip_client = $this->bdd->escape_string($this->ip_client);
		$this->serialize_paniers = $this->bdd->escape_string($this->serialize_paniers);
		$this->serialize_paniers_produits = $this->bdd->escape_string($this->serialize_paniers_produits);
		$this->serialize_paniers_promos = $this->bdd->escape_string($this->serialize_paniers_promos);
		$this->serialize_paniers_cadeaux = $this->bdd->escape_string($this->serialize_paniers_cadeaux);
		$this->serialize_payline = $this->bdd->escape_string($this->serialize_payline);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->civilite_liv = $this->bdd->escape_string($this->civilite_liv);
		$this->nom_liv = $this->bdd->escape_string($this->nom_liv);
		$this->prenom_liv = $this->bdd->escape_string($this->prenom_liv);
		$this->societe_liv = $this->bdd->escape_string($this->societe_liv);
		$this->adresse1_liv = $this->bdd->escape_string($this->adresse1_liv);
		$this->adresse2_liv = $this->bdd->escape_string($this->adresse2_liv);
		$this->adresse3_liv = $this->bdd->escape_string($this->adresse3_liv);
		$this->cp_liv = $this->bdd->escape_string($this->cp_liv);
		$this->ville_liv = $this->bdd->escape_string($this->ville_liv);
		$this->id_pays_liv = $this->bdd->escape_string($this->id_pays_liv);
		$this->civilite_fac = $this->bdd->escape_string($this->civilite_fac);
		$this->nom_fac = $this->bdd->escape_string($this->nom_fac);
		$this->prenom_fac = $this->bdd->escape_string($this->prenom_fac);
		$this->societe_fac = $this->bdd->escape_string($this->societe_fac);
		$this->adresse1_fac = $this->bdd->escape_string($this->adresse1_fac);
		$this->adresse2_fac = $this->bdd->escape_string($this->adresse2_fac);
		$this->adresse3_fac = $this->bdd->escape_string($this->adresse3_fac);
		$this->cp_fac = $this->bdd->escape_string($this->cp_fac);
		$this->ville_fac = $this->bdd->escape_string($this->ville_fac);
		$this->id_pays_fac = $this->bdd->escape_string($this->id_pays_fac);
		$this->colis = $this->bdd->escape_string($this->colis);

		
<<<<<<< HEAD
		$sql = 'INSERT INTO `transactions`(`id_panier`,`id_backpayline`,`id_offre_bienvenue_detail`,`id_parrain_filleul`,`id_virement`,`id_prelevement`,`id_echeancier`,`id_echeancier_emprunteur`,`id_bid_remb`,`id_loan_remb`,`id_project`,`id_client`,`id_partenaire`,`id_livraison`,`id_facturation`,`id_type`,`fdp`,`montant`,`montant_unilend`,`montant_etat`,`montant_reduc`,`id_langue`,`date_transaction`,`type_paiement`,`status`,`etat`,`transaction`,`type_transaction`,`recouvrement`,`display`,`ip_client`,`serialize_paniers`,`serialize_paniers_produits`,`serialize_paniers_promos`,`serialize_paniers_cadeaux`,`serialize_payline`,`added`,`updated`,`civilite_liv`,`nom_liv`,`prenom_liv`,`societe_liv`,`adresse1_liv`,`adresse2_liv`,`adresse3_liv`,`cp_liv`,`ville_liv`,`id_pays_liv`,`civilite_fac`,`nom_fac`,`prenom_fac`,`societe_fac`,`adresse1_fac`,`adresse2_fac`,`adresse3_fac`,`cp_fac`,`ville_fac`,`id_pays_fac`,`colis`) VALUES("'.$this->id_panier.'","'.$this->id_backpayline.'","'.$this->id_offre_bienvenue_detail.'","'.$this->id_parrain_filleul.'","'.$this->id_virement.'","'.$this->id_prelevement.'","'.$this->id_echeancier.'","'.$this->id_echeancier_emprunteur.'","'.$this->id_bid_remb.'","'.$this->id_loan_remb.'","'.$this->id_project.'","'.$this->id_client.'","'.$this->id_partenaire.'","'.$this->id_livraison.'","'.$this->id_facturation.'","'.$this->id_type.'","'.$this->fdp.'","'.$this->montant.'","'.$this->montant_unilend.'","'.$this->montant_etat.'","'.$this->montant_reduc.'","'.$this->id_langue.'","'.$this->date_transaction.'","'.$this->type_paiement.'","'.$this->status.'","'.$this->etat.'","'.$this->transaction.'","'.$this->type_transaction.'","'.$this->recouvrement.'","'.$this->display.'","'.$this->ip_client.'","'.$this->serialize_paniers.'","'.$this->serialize_paniers_produits.'","'.$this->serialize_paniers_promos.'","'.$this->serialize_paniers_cadeaux.'","'.$this->serialize_payline.'",NOW(),NOW(),"'.$this->civilite_liv.'","'.$this->nom_liv.'","'.$this->prenom_liv.'","'.$this->societe_liv.'","'.$this->adresse1_liv.'","'.$this->adresse2_liv.'","'.$this->adresse3_liv.'","'.$this->cp_liv.'","'.$this->ville_liv.'","'.$this->id_pays_liv.'","'.$this->civilite_fac.'","'.$this->nom_fac.'","'.$this->prenom_fac.'","'.$this->societe_fac.'","'.$this->adresse1_fac.'","'.$this->adresse2_fac.'","'.$this->adresse3_fac.'","'.$this->cp_fac.'","'.$this->ville_fac.'","'.$this->id_pays_fac.'","'.$this->colis.'")';
=======
		$sql = 'INSERT INTO `transactions`(`id_panier`,`id_backpayline`,`id_offre_bienvenue_detail`,`id_parrain_filleul`,`id_virement`,`id_prelevement`,`id_echeancier`,`id_echeancier_emprunteur`,`id_bid_remb`,`id_loan_remb`,`id_project`,`id_client`,`id_partenaire`,`id_livraison`,`id_facturation`,`id_type`,`fdp`,`montant`,`montant_unilend`,`montant_etat`,`montant_reduc`,`id_langue`,`date_transaction`,`type_paiement`,`status`,`etat`,`transaction`,`type_transaction`,`display`,`ip_client`,`serialize_paniers`,`serialize_paniers_produits`,`serialize_paniers_promos`,`serialize_paniers_cadeaux`,`serialize_payline`,`added`,`updated`,`civilite_liv`,`nom_liv`,`prenom_liv`,`societe_liv`,`adresse1_liv`,`adresse2_liv`,`adresse3_liv`,`cp_liv`,`ville_liv`,`id_pays_liv`,`civilite_fac`,`nom_fac`,`prenom_fac`,`societe_fac`,`adresse1_fac`,`adresse2_fac`,`adresse3_fac`,`cp_fac`,`ville_fac`,`id_pays_fac`,`colis`) VALUES("'.$this->id_panier.'","'.$this->id_backpayline.'","'.$this->id_offre_bienvenue_detail.'","'.$this->id_parrain_filleul.'","'.$this->id_virement.'","'.$this->id_prelevement.'","'.$this->id_echeancier.'","'.$this->id_echeancier_emprunteur.'","'.$this->id_bid_remb.'","'.$this->id_loan_remb.'","'.$this->id_project.'","'.$this->id_client.'","'.$this->id_partenaire.'","'.$this->id_livraison.'","'.$this->id_facturation.'","'.$this->id_type.'","'.$this->fdp.'","'.$this->montant.'","'.$this->montant_unilend.'","'.$this->montant_etat.'","'.$this->montant_reduc.'","'.$this->id_langue.'","'.$this->date_transaction.'","'.$this->type_paiement.'","'.$this->status.'","'.$this->etat.'","'.$this->transaction.'","'.$this->type_transaction.'","'.$this->display.'","'.$this->ip_client.'","'.$this->serialize_paniers.'","'.$this->serialize_paniers_produits.'","'.$this->serialize_paniers_promos.'","'.$this->serialize_paniers_cadeaux.'","'.$this->serialize_payline.'",NOW(),NOW(),"'.$this->civilite_liv.'","'.$this->nom_liv.'","'.$this->prenom_liv.'","'.$this->societe_liv.'","'.$this->adresse1_liv.'","'.$this->adresse2_liv.'","'.$this->adresse3_liv.'","'.$this->cp_liv.'","'.$this->ville_liv.'","'.$this->id_pays_liv.'","'.$this->civilite_fac.'","'.$this->nom_fac.'","'.$this->prenom_fac.'","'.$this->societe_fac.'","'.$this->adresse1_fac.'","'.$this->adresse2_fac.'","'.$this->adresse3_fac.'","'.$this->cp_fac.'","'.$this->ville_fac.'","'.$this->id_pays_fac.'","'.$this->colis.'")';
>>>>>>> statuts-emprunteurs
		$this->bdd->query($sql);
		
		$this->id_transaction = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_transaction,'id_transaction');
		
		return $this->id_transaction;
	}
	
	function unsetData()
	{
		$this->id_transaction = '';
		$this->id_panier = '';
		$this->id_backpayline = '';
		$this->id_offre_bienvenue_detail = '';
		$this->id_parrain_filleul = '';
		$this->id_virement = '';
		$this->id_prelevement = '';
		$this->id_echeancier = '';
		$this->id_echeancier_emprunteur = '';
		$this->id_bid_remb = '';
		$this->id_loan_remb = '';
		$this->id_project = '';
		$this->id_client = '';
		$this->id_partenaire = '';
		$this->id_livraison = '';
		$this->id_facturation = '';
		$this->id_type = '';
		$this->fdp = '';
		$this->montant = '';
		$this->montant_unilend = '';
		$this->montant_etat = '';
		$this->montant_reduc = '';
		$this->id_langue = '';
		$this->date_transaction = '';
		$this->type_paiement = '';
		$this->status = '';
		$this->etat = '';
		$this->transaction = '';
		$this->type_transaction = '';
		$this->recouvrement = '';
		$this->display = '';
		$this->ip_client = '';
		$this->serialize_paniers = '';
		$this->serialize_paniers_produits = '';
		$this->serialize_paniers_promos = '';
		$this->serialize_paniers_cadeaux = '';
		$this->serialize_payline = '';
		$this->added = '';
		$this->updated = '';
		$this->civilite_liv = '';
		$this->nom_liv = '';
		$this->prenom_liv = '';
		$this->societe_liv = '';
		$this->adresse1_liv = '';
		$this->adresse2_liv = '';
		$this->adresse3_liv = '';
		$this->cp_liv = '';
		$this->ville_liv = '';
		$this->id_pays_liv = '';
		$this->civilite_fac = '';
		$this->nom_fac = '';
		$this->prenom_fac = '';
		$this->societe_fac = '';
		$this->adresse1_fac = '';
		$this->adresse2_fac = '';
		$this->adresse3_fac = '';
		$this->cp_fac = '';
		$this->ville_fac = '';
		$this->id_pays_fac = '';
		$this->colis = '';

	}
}