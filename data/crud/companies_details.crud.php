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
class companies_details_crud
{
	
	public $id_company_detail;
	public $id_company;
	public $date_dernier_bilan;
	public $date_dernier_bilan_mois;
	public $date_dernier_bilan_annee;
	public $encours_actuel_dette_fianciere;
	public $remb_a_venir_cette_annee;
	public $remb_a_venir_annee_prochaine;
	public $tresorie_dispo_actuellement;
	public $autre_demandes_financements_prevues;
	public $precisions;
	public $decouverts_bancaires;
	public $lignes_de_tresorerie;
	public $affacturage;
	public $escompte;
	public $financement_dailly;
	public $credit_de_tresorerie;
	public $credit_bancaire_investissements_materiels;
	public $credit_bancaire_investissements_immateriels;
	public $rachat_entreprise_ou_titres;
	public $credit_immobilier;
	public $credit_bail_immobilier;
	public $credit_bail;
	public $location_avec_option_achat;
	public $location_financiere;
	public $location_longue_duree;
	public $pret_oseo;
	public $pret_participatif;
	public $fichier_extrait_kbis;
	public $fichier_rib;
	public $fichier_delegation_pouvoir;
	public $fichier_logo_societe;
	public $fichier_photo_dirigeant;
	public $fichier_dernier_bilan_certifie;
	public $fichier_cni_passeport;
	public $fichier_derniere_liasse_fiscale;
	public $fichier_derniers_comptes_approuves;
	public $fichier_derniers_comptes_consolides_groupe;
	public $fichier_annexes_rapport_special_commissaire_compte;
	public $fichier_arret_comptable_recent;
	public $fichier_budget_exercice_en_cours_a_venir;
	public $fichier_notation_banque_france;
	public $fichier_autre_1;
	public $fichier_autre_2;
	public $fichier_autre_3;
	public $added;
	public $updated;

	
	function companies_details($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_company_detail = '';
		$this->id_company = '';
		$this->date_dernier_bilan = '';
		$this->date_dernier_bilan_mois = '';
		$this->date_dernier_bilan_annee = '';
		$this->encours_actuel_dette_fianciere = '';
		$this->remb_a_venir_cette_annee = '';
		$this->remb_a_venir_annee_prochaine = '';
		$this->tresorie_dispo_actuellement = '';
		$this->autre_demandes_financements_prevues = '';
		$this->precisions = '';
		$this->decouverts_bancaires = '';
		$this->lignes_de_tresorerie = '';
		$this->affacturage = '';
		$this->escompte = '';
		$this->financement_dailly = '';
		$this->credit_de_tresorerie = '';
		$this->credit_bancaire_investissements_materiels = '';
		$this->credit_bancaire_investissements_immateriels = '';
		$this->rachat_entreprise_ou_titres = '';
		$this->credit_immobilier = '';
		$this->credit_bail_immobilier = '';
		$this->credit_bail = '';
		$this->location_avec_option_achat = '';
		$this->location_financiere = '';
		$this->location_longue_duree = '';
		$this->pret_oseo = '';
		$this->pret_participatif = '';
		$this->fichier_extrait_kbis = '';
		$this->fichier_rib = '';
		$this->fichier_delegation_pouvoir = '';
		$this->fichier_logo_societe = '';
		$this->fichier_photo_dirigeant = '';
		$this->fichier_dernier_bilan_certifie = '';
		$this->fichier_cni_passeport = '';
		$this->fichier_derniere_liasse_fiscale = '';
		$this->fichier_derniers_comptes_approuves = '';
		$this->fichier_derniers_comptes_consolides_groupe = '';
		$this->fichier_annexes_rapport_special_commissaire_compte = '';
		$this->fichier_arret_comptable_recent = '';
		$this->fichier_budget_exercice_en_cours_a_venir = '';
		$this->fichier_notation_banque_france = '';
		$this->fichier_autre_1 = '';
		$this->fichier_autre_2 = '';
		$this->fichier_autre_3 = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_company_detail')
	{
		$sql = 'SELECT * FROM  `companies_details` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_company_detail = $record['id_company_detail'];
			$this->id_company = $record['id_company'];
			$this->date_dernier_bilan = $record['date_dernier_bilan'];
			$this->date_dernier_bilan_mois = $record['date_dernier_bilan_mois'];
			$this->date_dernier_bilan_annee = $record['date_dernier_bilan_annee'];
			$this->encours_actuel_dette_fianciere = $record['encours_actuel_dette_fianciere'];
			$this->remb_a_venir_cette_annee = $record['remb_a_venir_cette_annee'];
			$this->remb_a_venir_annee_prochaine = $record['remb_a_venir_annee_prochaine'];
			$this->tresorie_dispo_actuellement = $record['tresorie_dispo_actuellement'];
			$this->autre_demandes_financements_prevues = $record['autre_demandes_financements_prevues'];
			$this->precisions = $record['precisions'];
			$this->decouverts_bancaires = $record['decouverts_bancaires'];
			$this->lignes_de_tresorerie = $record['lignes_de_tresorerie'];
			$this->affacturage = $record['affacturage'];
			$this->escompte = $record['escompte'];
			$this->financement_dailly = $record['financement_dailly'];
			$this->credit_de_tresorerie = $record['credit_de_tresorerie'];
			$this->credit_bancaire_investissements_materiels = $record['credit_bancaire_investissements_materiels'];
			$this->credit_bancaire_investissements_immateriels = $record['credit_bancaire_investissements_immateriels'];
			$this->rachat_entreprise_ou_titres = $record['rachat_entreprise_ou_titres'];
			$this->credit_immobilier = $record['credit_immobilier'];
			$this->credit_bail_immobilier = $record['credit_bail_immobilier'];
			$this->credit_bail = $record['credit_bail'];
			$this->location_avec_option_achat = $record['location_avec_option_achat'];
			$this->location_financiere = $record['location_financiere'];
			$this->location_longue_duree = $record['location_longue_duree'];
			$this->pret_oseo = $record['pret_oseo'];
			$this->pret_participatif = $record['pret_participatif'];
			$this->fichier_extrait_kbis = $record['fichier_extrait_kbis'];
			$this->fichier_rib = $record['fichier_rib'];
			$this->fichier_delegation_pouvoir = $record['fichier_delegation_pouvoir'];
			$this->fichier_logo_societe = $record['fichier_logo_societe'];
			$this->fichier_photo_dirigeant = $record['fichier_photo_dirigeant'];
			$this->fichier_dernier_bilan_certifie = $record['fichier_dernier_bilan_certifie'];
			$this->fichier_cni_passeport = $record['fichier_cni_passeport'];
			$this->fichier_derniere_liasse_fiscale = $record['fichier_derniere_liasse_fiscale'];
			$this->fichier_derniers_comptes_approuves = $record['fichier_derniers_comptes_approuves'];
			$this->fichier_derniers_comptes_consolides_groupe = $record['fichier_derniers_comptes_consolides_groupe'];
			$this->fichier_annexes_rapport_special_commissaire_compte = $record['fichier_annexes_rapport_special_commissaire_compte'];
			$this->fichier_arret_comptable_recent = $record['fichier_arret_comptable_recent'];
			$this->fichier_budget_exercice_en_cours_a_venir = $record['fichier_budget_exercice_en_cours_a_venir'];
			$this->fichier_notation_banque_france = $record['fichier_notation_banque_france'];
			$this->fichier_autre_1 = $record['fichier_autre_1'];
			$this->fichier_autre_2 = $record['fichier_autre_2'];
			$this->fichier_autre_3 = $record['fichier_autre_3'];
			$this->added = $record['added'];
			$this->updated = $record['updated'];

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
		$this->id_company_detail = $this->bdd->escape_string($this->id_company_detail);
		$this->id_company = $this->bdd->escape_string($this->id_company);
		$this->date_dernier_bilan = $this->bdd->escape_string($this->date_dernier_bilan);
		$this->date_dernier_bilan_mois = $this->bdd->escape_string($this->date_dernier_bilan_mois);
		$this->date_dernier_bilan_annee = $this->bdd->escape_string($this->date_dernier_bilan_annee);
		$this->encours_actuel_dette_fianciere = $this->bdd->escape_string($this->encours_actuel_dette_fianciere);
		$this->remb_a_venir_cette_annee = $this->bdd->escape_string($this->remb_a_venir_cette_annee);
		$this->remb_a_venir_annee_prochaine = $this->bdd->escape_string($this->remb_a_venir_annee_prochaine);
		$this->tresorie_dispo_actuellement = $this->bdd->escape_string($this->tresorie_dispo_actuellement);
		$this->autre_demandes_financements_prevues = $this->bdd->escape_string($this->autre_demandes_financements_prevues);
		$this->precisions = $this->bdd->escape_string($this->precisions);
		$this->decouverts_bancaires = $this->bdd->escape_string($this->decouverts_bancaires);
		$this->lignes_de_tresorerie = $this->bdd->escape_string($this->lignes_de_tresorerie);
		$this->affacturage = $this->bdd->escape_string($this->affacturage);
		$this->escompte = $this->bdd->escape_string($this->escompte);
		$this->financement_dailly = $this->bdd->escape_string($this->financement_dailly);
		$this->credit_de_tresorerie = $this->bdd->escape_string($this->credit_de_tresorerie);
		$this->credit_bancaire_investissements_materiels = $this->bdd->escape_string($this->credit_bancaire_investissements_materiels);
		$this->credit_bancaire_investissements_immateriels = $this->bdd->escape_string($this->credit_bancaire_investissements_immateriels);
		$this->rachat_entreprise_ou_titres = $this->bdd->escape_string($this->rachat_entreprise_ou_titres);
		$this->credit_immobilier = $this->bdd->escape_string($this->credit_immobilier);
		$this->credit_bail_immobilier = $this->bdd->escape_string($this->credit_bail_immobilier);
		$this->credit_bail = $this->bdd->escape_string($this->credit_bail);
		$this->location_avec_option_achat = $this->bdd->escape_string($this->location_avec_option_achat);
		$this->location_financiere = $this->bdd->escape_string($this->location_financiere);
		$this->location_longue_duree = $this->bdd->escape_string($this->location_longue_duree);
		$this->pret_oseo = $this->bdd->escape_string($this->pret_oseo);
		$this->pret_participatif = $this->bdd->escape_string($this->pret_participatif);
		$this->fichier_extrait_kbis = $this->bdd->escape_string($this->fichier_extrait_kbis);
		$this->fichier_rib = $this->bdd->escape_string($this->fichier_rib);
		$this->fichier_delegation_pouvoir = $this->bdd->escape_string($this->fichier_delegation_pouvoir);
		$this->fichier_logo_societe = $this->bdd->escape_string($this->fichier_logo_societe);
		$this->fichier_photo_dirigeant = $this->bdd->escape_string($this->fichier_photo_dirigeant);
		$this->fichier_dernier_bilan_certifie = $this->bdd->escape_string($this->fichier_dernier_bilan_certifie);
		$this->fichier_cni_passeport = $this->bdd->escape_string($this->fichier_cni_passeport);
		$this->fichier_derniere_liasse_fiscale = $this->bdd->escape_string($this->fichier_derniere_liasse_fiscale);
		$this->fichier_derniers_comptes_approuves = $this->bdd->escape_string($this->fichier_derniers_comptes_approuves);
		$this->fichier_derniers_comptes_consolides_groupe = $this->bdd->escape_string($this->fichier_derniers_comptes_consolides_groupe);
		$this->fichier_annexes_rapport_special_commissaire_compte = $this->bdd->escape_string($this->fichier_annexes_rapport_special_commissaire_compte);
		$this->fichier_arret_comptable_recent = $this->bdd->escape_string($this->fichier_arret_comptable_recent);
		$this->fichier_budget_exercice_en_cours_a_venir = $this->bdd->escape_string($this->fichier_budget_exercice_en_cours_a_venir);
		$this->fichier_notation_banque_france = $this->bdd->escape_string($this->fichier_notation_banque_france);
		$this->fichier_autre_1 = $this->bdd->escape_string($this->fichier_autre_1);
		$this->fichier_autre_2 = $this->bdd->escape_string($this->fichier_autre_2);
		$this->fichier_autre_3 = $this->bdd->escape_string($this->fichier_autre_3);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `companies_details` SET `id_company`="'.$this->id_company.'",`date_dernier_bilan`="'.$this->date_dernier_bilan.'",`date_dernier_bilan_mois`="'.$this->date_dernier_bilan_mois.'",`date_dernier_bilan_annee`="'.$this->date_dernier_bilan_annee.'",`encours_actuel_dette_fianciere`="'.$this->encours_actuel_dette_fianciere.'",`remb_a_venir_cette_annee`="'.$this->remb_a_venir_cette_annee.'",`remb_a_venir_annee_prochaine`="'.$this->remb_a_venir_annee_prochaine.'",`tresorie_dispo_actuellement`="'.$this->tresorie_dispo_actuellement.'",`autre_demandes_financements_prevues`="'.$this->autre_demandes_financements_prevues.'",`precisions`="'.$this->precisions.'",`decouverts_bancaires`="'.$this->decouverts_bancaires.'",`lignes_de_tresorerie`="'.$this->lignes_de_tresorerie.'",`affacturage`="'.$this->affacturage.'",`escompte`="'.$this->escompte.'",`financement_dailly`="'.$this->financement_dailly.'",`credit_de_tresorerie`="'.$this->credit_de_tresorerie.'",`credit_bancaire_investissements_materiels`="'.$this->credit_bancaire_investissements_materiels.'",`credit_bancaire_investissements_immateriels`="'.$this->credit_bancaire_investissements_immateriels.'",`rachat_entreprise_ou_titres`="'.$this->rachat_entreprise_ou_titres.'",`credit_immobilier`="'.$this->credit_immobilier.'",`credit_bail_immobilier`="'.$this->credit_bail_immobilier.'",`credit_bail`="'.$this->credit_bail.'",`location_avec_option_achat`="'.$this->location_avec_option_achat.'",`location_financiere`="'.$this->location_financiere.'",`location_longue_duree`="'.$this->location_longue_duree.'",`pret_oseo`="'.$this->pret_oseo.'",`pret_participatif`="'.$this->pret_participatif.'",`fichier_extrait_kbis`="'.$this->fichier_extrait_kbis.'",`fichier_rib`="'.$this->fichier_rib.'",`fichier_delegation_pouvoir`="'.$this->fichier_delegation_pouvoir.'",`fichier_logo_societe`="'.$this->fichier_logo_societe.'",`fichier_photo_dirigeant`="'.$this->fichier_photo_dirigeant.'",`fichier_dernier_bilan_certifie`="'.$this->fichier_dernier_bilan_certifie.'",`fichier_cni_passeport`="'.$this->fichier_cni_passeport.'",`fichier_derniere_liasse_fiscale`="'.$this->fichier_derniere_liasse_fiscale.'",`fichier_derniers_comptes_approuves`="'.$this->fichier_derniers_comptes_approuves.'",`fichier_derniers_comptes_consolides_groupe`="'.$this->fichier_derniers_comptes_consolides_groupe.'",`fichier_annexes_rapport_special_commissaire_compte`="'.$this->fichier_annexes_rapport_special_commissaire_compte.'",`fichier_arret_comptable_recent`="'.$this->fichier_arret_comptable_recent.'",`fichier_budget_exercice_en_cours_a_venir`="'.$this->fichier_budget_exercice_en_cours_a_venir.'",`fichier_notation_banque_france`="'.$this->fichier_notation_banque_france.'",`fichier_autre_1`="'.$this->fichier_autre_1.'",`fichier_autre_2`="'.$this->fichier_autre_2.'",`fichier_autre_3`="'.$this->fichier_autre_3.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_company_detail="'.$this->id_company_detail.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_company_detail,'id_company_detail');
	}
	
	function delete($id,$field='id_company_detail')
	{
		if($id=='')
			$id = $this->id_company_detail;
		$sql = 'DELETE FROM `companies_details` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_company_detail = $this->bdd->escape_string($this->id_company_detail);
		$this->id_company = $this->bdd->escape_string($this->id_company);
		$this->date_dernier_bilan = $this->bdd->escape_string($this->date_dernier_bilan);
		$this->date_dernier_bilan_mois = $this->bdd->escape_string($this->date_dernier_bilan_mois);
		$this->date_dernier_bilan_annee = $this->bdd->escape_string($this->date_dernier_bilan_annee);
		$this->encours_actuel_dette_fianciere = $this->bdd->escape_string($this->encours_actuel_dette_fianciere);
		$this->remb_a_venir_cette_annee = $this->bdd->escape_string($this->remb_a_venir_cette_annee);
		$this->remb_a_venir_annee_prochaine = $this->bdd->escape_string($this->remb_a_venir_annee_prochaine);
		$this->tresorie_dispo_actuellement = $this->bdd->escape_string($this->tresorie_dispo_actuellement);
		$this->autre_demandes_financements_prevues = $this->bdd->escape_string($this->autre_demandes_financements_prevues);
		$this->precisions = $this->bdd->escape_string($this->precisions);
		$this->decouverts_bancaires = $this->bdd->escape_string($this->decouverts_bancaires);
		$this->lignes_de_tresorerie = $this->bdd->escape_string($this->lignes_de_tresorerie);
		$this->affacturage = $this->bdd->escape_string($this->affacturage);
		$this->escompte = $this->bdd->escape_string($this->escompte);
		$this->financement_dailly = $this->bdd->escape_string($this->financement_dailly);
		$this->credit_de_tresorerie = $this->bdd->escape_string($this->credit_de_tresorerie);
		$this->credit_bancaire_investissements_materiels = $this->bdd->escape_string($this->credit_bancaire_investissements_materiels);
		$this->credit_bancaire_investissements_immateriels = $this->bdd->escape_string($this->credit_bancaire_investissements_immateriels);
		$this->rachat_entreprise_ou_titres = $this->bdd->escape_string($this->rachat_entreprise_ou_titres);
		$this->credit_immobilier = $this->bdd->escape_string($this->credit_immobilier);
		$this->credit_bail_immobilier = $this->bdd->escape_string($this->credit_bail_immobilier);
		$this->credit_bail = $this->bdd->escape_string($this->credit_bail);
		$this->location_avec_option_achat = $this->bdd->escape_string($this->location_avec_option_achat);
		$this->location_financiere = $this->bdd->escape_string($this->location_financiere);
		$this->location_longue_duree = $this->bdd->escape_string($this->location_longue_duree);
		$this->pret_oseo = $this->bdd->escape_string($this->pret_oseo);
		$this->pret_participatif = $this->bdd->escape_string($this->pret_participatif);
		$this->fichier_extrait_kbis = $this->bdd->escape_string($this->fichier_extrait_kbis);
		$this->fichier_rib = $this->bdd->escape_string($this->fichier_rib);
		$this->fichier_delegation_pouvoir = $this->bdd->escape_string($this->fichier_delegation_pouvoir);
		$this->fichier_logo_societe = $this->bdd->escape_string($this->fichier_logo_societe);
		$this->fichier_photo_dirigeant = $this->bdd->escape_string($this->fichier_photo_dirigeant);
		$this->fichier_dernier_bilan_certifie = $this->bdd->escape_string($this->fichier_dernier_bilan_certifie);
		$this->fichier_cni_passeport = $this->bdd->escape_string($this->fichier_cni_passeport);
		$this->fichier_derniere_liasse_fiscale = $this->bdd->escape_string($this->fichier_derniere_liasse_fiscale);
		$this->fichier_derniers_comptes_approuves = $this->bdd->escape_string($this->fichier_derniers_comptes_approuves);
		$this->fichier_derniers_comptes_consolides_groupe = $this->bdd->escape_string($this->fichier_derniers_comptes_consolides_groupe);
		$this->fichier_annexes_rapport_special_commissaire_compte = $this->bdd->escape_string($this->fichier_annexes_rapport_special_commissaire_compte);
		$this->fichier_arret_comptable_recent = $this->bdd->escape_string($this->fichier_arret_comptable_recent);
		$this->fichier_budget_exercice_en_cours_a_venir = $this->bdd->escape_string($this->fichier_budget_exercice_en_cours_a_venir);
		$this->fichier_notation_banque_france = $this->bdd->escape_string($this->fichier_notation_banque_france);
		$this->fichier_autre_1 = $this->bdd->escape_string($this->fichier_autre_1);
		$this->fichier_autre_2 = $this->bdd->escape_string($this->fichier_autre_2);
		$this->fichier_autre_3 = $this->bdd->escape_string($this->fichier_autre_3);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `companies_details`(`id_company`,`date_dernier_bilan`,`date_dernier_bilan_mois`,`date_dernier_bilan_annee`,`encours_actuel_dette_fianciere`,`remb_a_venir_cette_annee`,`remb_a_venir_annee_prochaine`,`tresorie_dispo_actuellement`,`autre_demandes_financements_prevues`,`precisions`,`decouverts_bancaires`,`lignes_de_tresorerie`,`affacturage`,`escompte`,`financement_dailly`,`credit_de_tresorerie`,`credit_bancaire_investissements_materiels`,`credit_bancaire_investissements_immateriels`,`rachat_entreprise_ou_titres`,`credit_immobilier`,`credit_bail_immobilier`,`credit_bail`,`location_avec_option_achat`,`location_financiere`,`location_longue_duree`,`pret_oseo`,`pret_participatif`,`fichier_extrait_kbis`,`fichier_rib`,`fichier_delegation_pouvoir`,`fichier_logo_societe`,`fichier_photo_dirigeant`,`fichier_dernier_bilan_certifie`,`fichier_cni_passeport`,`fichier_derniere_liasse_fiscale`,`fichier_derniers_comptes_approuves`,`fichier_derniers_comptes_consolides_groupe`,`fichier_annexes_rapport_special_commissaire_compte`,`fichier_arret_comptable_recent`,`fichier_budget_exercice_en_cours_a_venir`,`fichier_notation_banque_france`,`fichier_autre_1`,`fichier_autre_2`,`fichier_autre_3`,`added`,`updated`) VALUES("'.$this->id_company.'","'.$this->date_dernier_bilan.'","'.$this->date_dernier_bilan_mois.'","'.$this->date_dernier_bilan_annee.'","'.$this->encours_actuel_dette_fianciere.'","'.$this->remb_a_venir_cette_annee.'","'.$this->remb_a_venir_annee_prochaine.'","'.$this->tresorie_dispo_actuellement.'","'.$this->autre_demandes_financements_prevues.'","'.$this->precisions.'","'.$this->decouverts_bancaires.'","'.$this->lignes_de_tresorerie.'","'.$this->affacturage.'","'.$this->escompte.'","'.$this->financement_dailly.'","'.$this->credit_de_tresorerie.'","'.$this->credit_bancaire_investissements_materiels.'","'.$this->credit_bancaire_investissements_immateriels.'","'.$this->rachat_entreprise_ou_titres.'","'.$this->credit_immobilier.'","'.$this->credit_bail_immobilier.'","'.$this->credit_bail.'","'.$this->location_avec_option_achat.'","'.$this->location_financiere.'","'.$this->location_longue_duree.'","'.$this->pret_oseo.'","'.$this->pret_participatif.'","'.$this->fichier_extrait_kbis.'","'.$this->fichier_rib.'","'.$this->fichier_delegation_pouvoir.'","'.$this->fichier_logo_societe.'","'.$this->fichier_photo_dirigeant.'","'.$this->fichier_dernier_bilan_certifie.'","'.$this->fichier_cni_passeport.'","'.$this->fichier_derniere_liasse_fiscale.'","'.$this->fichier_derniers_comptes_approuves.'","'.$this->fichier_derniers_comptes_consolides_groupe.'","'.$this->fichier_annexes_rapport_special_commissaire_compte.'","'.$this->fichier_arret_comptable_recent.'","'.$this->fichier_budget_exercice_en_cours_a_venir.'","'.$this->fichier_notation_banque_france.'","'.$this->fichier_autre_1.'","'.$this->fichier_autre_2.'","'.$this->fichier_autre_3.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_company_detail = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_company_detail,'id_company_detail');
		
		return $this->id_company_detail;
	}
	
	function unsetData()
	{
		$this->id_company_detail = '';
		$this->id_company = '';
		$this->date_dernier_bilan = '';
		$this->date_dernier_bilan_mois = '';
		$this->date_dernier_bilan_annee = '';
		$this->encours_actuel_dette_fianciere = '';
		$this->remb_a_venir_cette_annee = '';
		$this->remb_a_venir_annee_prochaine = '';
		$this->tresorie_dispo_actuellement = '';
		$this->autre_demandes_financements_prevues = '';
		$this->precisions = '';
		$this->decouverts_bancaires = '';
		$this->lignes_de_tresorerie = '';
		$this->affacturage = '';
		$this->escompte = '';
		$this->financement_dailly = '';
		$this->credit_de_tresorerie = '';
		$this->credit_bancaire_investissements_materiels = '';
		$this->credit_bancaire_investissements_immateriels = '';
		$this->rachat_entreprise_ou_titres = '';
		$this->credit_immobilier = '';
		$this->credit_bail_immobilier = '';
		$this->credit_bail = '';
		$this->location_avec_option_achat = '';
		$this->location_financiere = '';
		$this->location_longue_duree = '';
		$this->pret_oseo = '';
		$this->pret_participatif = '';
		$this->fichier_extrait_kbis = '';
		$this->fichier_rib = '';
		$this->fichier_delegation_pouvoir = '';
		$this->fichier_logo_societe = '';
		$this->fichier_photo_dirigeant = '';
		$this->fichier_dernier_bilan_certifie = '';
		$this->fichier_cni_passeport = '';
		$this->fichier_derniere_liasse_fiscale = '';
		$this->fichier_derniers_comptes_approuves = '';
		$this->fichier_derniers_comptes_consolides_groupe = '';
		$this->fichier_annexes_rapport_special_commissaire_compte = '';
		$this->fichier_arret_comptable_recent = '';
		$this->fichier_budget_exercice_en_cours_a_venir = '';
		$this->fichier_notation_banque_france = '';
		$this->fichier_autre_1 = '';
		$this->fichier_autre_2 = '';
		$this->fichier_autre_3 = '';
		$this->added = '';
		$this->updated = '';

	}
}