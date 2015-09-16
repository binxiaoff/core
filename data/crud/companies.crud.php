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
class companies_crud
{
	
	public $id_company;
	public $id_client_owner;
	public $id_partenaire;
	public $id_partenaire_subcode;
	public $email_facture;
	public $name;
	public $forme;
	public $siren;
	public $siret;
	public $iban;
	public $bic;
	public $execices_comptables;
	public $rcs;
	public $tribunal_com;
	public $activite;
	public $lieu_exploi;
	public $tva;
	public $capital;
	public $date_creation;
	public $adresse1;
	public $adresse2;
	public $zip;
	public $city;
	public $id_pays;
	public $phone;
	public $status_adresse_correspondance;
	public $status_client;
	public $status_conseil_externe_entreprise;
	public $preciser_conseil_externe_entreprise;
	public $civilite_dirigeant;
	public $nom_dirigeant;
	public $prenom_dirigeant;
	public $fonction_dirigeant;
	public $email_dirigeant;
	public $phone_dirigeant;
	public $sector;
	public $risk;
	public $altares_eligibility;
	public $altares_dateValeur;
	public $altares_niveauRisque;
	public $altares_scoreVingt;
	public $added;
	public $updated;

	
	function companies($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_company = '';
		$this->id_client_owner = '';
		$this->id_partenaire = '';
		$this->id_partenaire_subcode = '';
		$this->email_facture = '';
		$this->name = '';
		$this->forme = '';
		$this->siren = '';
		$this->siret = '';
		$this->iban = '';
		$this->bic = '';
		$this->execices_comptables = '';
		$this->rcs = '';
		$this->tribunal_com = '';
		$this->activite = '';
		$this->lieu_exploi = '';
		$this->tva = '';
		$this->capital = '';
		$this->date_creation = '';
		$this->adresse1 = '';
		$this->adresse2 = '';
		$this->zip = '';
		$this->city = '';
		$this->id_pays = '';
		$this->phone = '';
		$this->status_adresse_correspondance = '';
		$this->status_client = '';
		$this->status_conseil_externe_entreprise = '';
		$this->preciser_conseil_externe_entreprise = '';
		$this->civilite_dirigeant = '';
		$this->nom_dirigeant = '';
		$this->prenom_dirigeant = '';
		$this->fonction_dirigeant = '';
		$this->email_dirigeant = '';
		$this->phone_dirigeant = '';
		$this->sector = '';
		$this->risk = '';
		$this->altares_eligibility = '';
		$this->altares_dateValeur = '';
		$this->altares_niveauRisque = '';
		$this->altares_scoreVingt = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_company')
	{
		$sql = 'SELECT * FROM  `companies` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_company = $record['id_company'];
			$this->id_client_owner = $record['id_client_owner'];
			$this->id_partenaire = $record['id_partenaire'];
			$this->id_partenaire_subcode = $record['id_partenaire_subcode'];
			$this->email_facture = $record['email_facture'];
			$this->name = $record['name'];
			$this->forme = $record['forme'];
			$this->siren = $record['siren'];
			$this->siret = $record['siret'];
			$this->iban = $record['iban'];
			$this->bic = $record['bic'];
			$this->execices_comptables = $record['execices_comptables'];
			$this->rcs = $record['rcs'];
			$this->tribunal_com = $record['tribunal_com'];
			$this->activite = $record['activite'];
			$this->lieu_exploi = $record['lieu_exploi'];
			$this->tva = $record['tva'];
			$this->capital = $record['capital'];
			$this->date_creation = $record['date_creation'];
			$this->adresse1 = $record['adresse1'];
			$this->adresse2 = $record['adresse2'];
			$this->zip = $record['zip'];
			$this->city = $record['city'];
			$this->id_pays = $record['id_pays'];
			$this->phone = $record['phone'];
			$this->status_adresse_correspondance = $record['status_adresse_correspondance'];
			$this->status_client = $record['status_client'];
			$this->status_conseil_externe_entreprise = $record['status_conseil_externe_entreprise'];
			$this->preciser_conseil_externe_entreprise = $record['preciser_conseil_externe_entreprise'];
			$this->civilite_dirigeant = $record['civilite_dirigeant'];
			$this->nom_dirigeant = $record['nom_dirigeant'];
			$this->prenom_dirigeant = $record['prenom_dirigeant'];
			$this->fonction_dirigeant = $record['fonction_dirigeant'];
			$this->email_dirigeant = $record['email_dirigeant'];
			$this->phone_dirigeant = $record['phone_dirigeant'];
			$this->sector = $record['sector'];
			$this->risk = $record['risk'];
			$this->altares_eligibility = $record['altares_eligibility'];
			$this->altares_dateValeur = $record['altares_dateValeur'];
			$this->altares_niveauRisque = $record['altares_niveauRisque'];
			$this->altares_scoreVingt = $record['altares_scoreVingt'];
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
		$this->id_company = $this->bdd->escape_string($this->id_company);
		$this->id_client_owner = $this->bdd->escape_string($this->id_client_owner);
		$this->id_partenaire = $this->bdd->escape_string($this->id_partenaire);
		$this->id_partenaire_subcode = $this->bdd->escape_string($this->id_partenaire_subcode);
		$this->email_facture = $this->bdd->escape_string($this->email_facture);
		$this->name = $this->bdd->escape_string($this->name);
		$this->forme = $this->bdd->escape_string($this->forme);
		$this->siren = $this->bdd->escape_string($this->siren);
		$this->siret = $this->bdd->escape_string($this->siret);
		$this->iban = $this->bdd->escape_string($this->iban);
		$this->bic = $this->bdd->escape_string($this->bic);
		$this->execices_comptables = $this->bdd->escape_string($this->execices_comptables);
		$this->rcs = $this->bdd->escape_string($this->rcs);
		$this->tribunal_com = $this->bdd->escape_string($this->tribunal_com);
		$this->activite = $this->bdd->escape_string($this->activite);
		$this->lieu_exploi = $this->bdd->escape_string($this->lieu_exploi);
		$this->tva = $this->bdd->escape_string($this->tva);
		$this->capital = $this->bdd->escape_string($this->capital);
		$this->date_creation = $this->bdd->escape_string($this->date_creation);
		$this->adresse1 = $this->bdd->escape_string($this->adresse1);
		$this->adresse2 = $this->bdd->escape_string($this->adresse2);
		$this->zip = $this->bdd->escape_string($this->zip);
		$this->city = $this->bdd->escape_string($this->city);
		$this->id_pays = $this->bdd->escape_string($this->id_pays);
		$this->phone = $this->bdd->escape_string($this->phone);
		$this->status_adresse_correspondance = $this->bdd->escape_string($this->status_adresse_correspondance);
		$this->status_client = $this->bdd->escape_string($this->status_client);
		$this->status_conseil_externe_entreprise = $this->bdd->escape_string($this->status_conseil_externe_entreprise);
		$this->preciser_conseil_externe_entreprise = $this->bdd->escape_string($this->preciser_conseil_externe_entreprise);
		$this->civilite_dirigeant = $this->bdd->escape_string($this->civilite_dirigeant);
		$this->nom_dirigeant = $this->bdd->escape_string($this->nom_dirigeant);
		$this->prenom_dirigeant = $this->bdd->escape_string($this->prenom_dirigeant);
		$this->fonction_dirigeant = $this->bdd->escape_string($this->fonction_dirigeant);
		$this->email_dirigeant = $this->bdd->escape_string($this->email_dirigeant);
		$this->phone_dirigeant = $this->bdd->escape_string($this->phone_dirigeant);
		$this->sector = $this->bdd->escape_string($this->sector);
		$this->risk = $this->bdd->escape_string($this->risk);
		$this->altares_eligibility = $this->bdd->escape_string($this->altares_eligibility);
		$this->altares_dateValeur = $this->bdd->escape_string($this->altares_dateValeur);
		$this->altares_niveauRisque = $this->bdd->escape_string($this->altares_niveauRisque);
		$this->altares_scoreVingt = $this->bdd->escape_string($this->altares_scoreVingt);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `companies` SET `id_client_owner`="'.$this->id_client_owner.'",`id_partenaire`="'.$this->id_partenaire.'",`id_partenaire_subcode`="'.$this->id_partenaire_subcode.'",`email_facture`="'.$this->email_facture.'",`name`="'.$this->name.'",`forme`="'.$this->forme.'",`siren`="'.$this->siren.'",`siret`="'.$this->siret.'",`iban`="'.$this->iban.'",`bic`="'.$this->bic.'",`execices_comptables`="'.$this->execices_comptables.'",`rcs`="'.$this->rcs.'",`tribunal_com`="'.$this->tribunal_com.'",`activite`="'.$this->activite.'",`lieu_exploi`="'.$this->lieu_exploi.'",`tva`="'.$this->tva.'",`capital`="'.$this->capital.'",`date_creation`="'.$this->date_creation.'",`adresse1`="'.$this->adresse1.'",`adresse2`="'.$this->adresse2.'",`zip`="'.$this->zip.'",`city`="'.$this->city.'",`id_pays`="'.$this->id_pays.'",`phone`="'.$this->phone.'",`status_adresse_correspondance`="'.$this->status_adresse_correspondance.'",`status_client`="'.$this->status_client.'",`status_conseil_externe_entreprise`="'.$this->status_conseil_externe_entreprise.'",`preciser_conseil_externe_entreprise`="'.$this->preciser_conseil_externe_entreprise.'",`civilite_dirigeant`="'.$this->civilite_dirigeant.'",`nom_dirigeant`="'.$this->nom_dirigeant.'",`prenom_dirigeant`="'.$this->prenom_dirigeant.'",`fonction_dirigeant`="'.$this->fonction_dirigeant.'",`email_dirigeant`="'.$this->email_dirigeant.'",`phone_dirigeant`="'.$this->phone_dirigeant.'",`sector`="'.$this->sector.'",`risk`="'.$this->risk.'",`altares_eligibility`="'.$this->altares_eligibility.'",`altares_dateValeur`="'.$this->altares_dateValeur.'",`altares_niveauRisque`="'.$this->altares_niveauRisque.'",`altares_scoreVingt`="'.$this->altares_scoreVingt.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_company="'.$this->id_company.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_company,'id_company');
	}
	
	function delete($id,$field='id_company')
	{
		if($id=='')
			$id = $this->id_company;
		$sql = 'DELETE FROM `companies` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_company = $this->bdd->escape_string($this->id_company);
		$this->id_client_owner = $this->bdd->escape_string($this->id_client_owner);
		$this->id_partenaire = $this->bdd->escape_string($this->id_partenaire);
		$this->id_partenaire_subcode = $this->bdd->escape_string($this->id_partenaire_subcode);
		$this->email_facture = $this->bdd->escape_string($this->email_facture);
		$this->name = $this->bdd->escape_string($this->name);
		$this->forme = $this->bdd->escape_string($this->forme);
		$this->siren = $this->bdd->escape_string($this->siren);
		$this->siret = $this->bdd->escape_string($this->siret);
		$this->iban = $this->bdd->escape_string($this->iban);
		$this->bic = $this->bdd->escape_string($this->bic);
		$this->execices_comptables = $this->bdd->escape_string($this->execices_comptables);
		$this->rcs = $this->bdd->escape_string($this->rcs);
		$this->tribunal_com = $this->bdd->escape_string($this->tribunal_com);
		$this->activite = $this->bdd->escape_string($this->activite);
		$this->lieu_exploi = $this->bdd->escape_string($this->lieu_exploi);
		$this->tva = $this->bdd->escape_string($this->tva);
		$this->capital = $this->bdd->escape_string($this->capital);
		$this->date_creation = $this->bdd->escape_string($this->date_creation);
		$this->adresse1 = $this->bdd->escape_string($this->adresse1);
		$this->adresse2 = $this->bdd->escape_string($this->adresse2);
		$this->zip = $this->bdd->escape_string($this->zip);
		$this->city = $this->bdd->escape_string($this->city);
		$this->id_pays = $this->bdd->escape_string($this->id_pays);
		$this->phone = $this->bdd->escape_string($this->phone);
		$this->status_adresse_correspondance = $this->bdd->escape_string($this->status_adresse_correspondance);
		$this->status_client = $this->bdd->escape_string($this->status_client);
		$this->status_conseil_externe_entreprise = $this->bdd->escape_string($this->status_conseil_externe_entreprise);
		$this->preciser_conseil_externe_entreprise = $this->bdd->escape_string($this->preciser_conseil_externe_entreprise);
		$this->civilite_dirigeant = $this->bdd->escape_string($this->civilite_dirigeant);
		$this->nom_dirigeant = $this->bdd->escape_string($this->nom_dirigeant);
		$this->prenom_dirigeant = $this->bdd->escape_string($this->prenom_dirigeant);
		$this->fonction_dirigeant = $this->bdd->escape_string($this->fonction_dirigeant);
		$this->email_dirigeant = $this->bdd->escape_string($this->email_dirigeant);
		$this->phone_dirigeant = $this->bdd->escape_string($this->phone_dirigeant);
		$this->sector = $this->bdd->escape_string($this->sector);
		$this->risk = $this->bdd->escape_string($this->risk);
		$this->altares_eligibility = $this->bdd->escape_string($this->altares_eligibility);
		$this->altares_dateValeur = $this->bdd->escape_string($this->altares_dateValeur);
		$this->altares_niveauRisque = $this->bdd->escape_string($this->altares_niveauRisque);
		$this->altares_scoreVingt = $this->bdd->escape_string($this->altares_scoreVingt);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `companies`(`id_client_owner`,`id_partenaire`,`id_partenaire_subcode`,`email_facture`,`name`,`forme`,`siren`,`siret`,`iban`,`bic`,`execices_comptables`,`rcs`,`tribunal_com`,`activite`,`lieu_exploi`,`tva`,`capital`,`date_creation`,`adresse1`,`adresse2`,`zip`,`city`,`id_pays`,`phone`,`status_adresse_correspondance`,`status_client`,`status_conseil_externe_entreprise`,`preciser_conseil_externe_entreprise`,`civilite_dirigeant`,`nom_dirigeant`,`prenom_dirigeant`,`fonction_dirigeant`,`email_dirigeant`,`phone_dirigeant`,`sector`,`risk`,`altares_eligibility`,`altares_dateValeur`,`altares_niveauRisque`,`altares_scoreVingt`,`added`,`updated`) VALUES("'.$this->id_client_owner.'","'.$this->id_partenaire.'","'.$this->id_partenaire_subcode.'","'.$this->email_facture.'","'.$this->name.'","'.$this->forme.'","'.$this->siren.'","'.$this->siret.'","'.$this->iban.'","'.$this->bic.'","'.$this->execices_comptables.'","'.$this->rcs.'","'.$this->tribunal_com.'","'.$this->activite.'","'.$this->lieu_exploi.'","'.$this->tva.'","'.$this->capital.'","'.$this->date_creation.'","'.$this->adresse1.'","'.$this->adresse2.'","'.$this->zip.'","'.$this->city.'","'.$this->id_pays.'","'.$this->phone.'","'.$this->status_adresse_correspondance.'","'.$this->status_client.'","'.$this->status_conseil_externe_entreprise.'","'.$this->preciser_conseil_externe_entreprise.'","'.$this->civilite_dirigeant.'","'.$this->nom_dirigeant.'","'.$this->prenom_dirigeant.'","'.$this->fonction_dirigeant.'","'.$this->email_dirigeant.'","'.$this->phone_dirigeant.'","'.$this->sector.'","'.$this->risk.'","'.$this->altares_eligibility.'","'.$this->altares_dateValeur.'","'.$this->altares_niveauRisque.'","'.$this->altares_scoreVingt.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_company = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_company,'id_company');
		
		return $this->id_company;
	}
	
	function unsetData()
	{
		$this->id_company = '';
		$this->id_client_owner = '';
		$this->id_partenaire = '';
		$this->id_partenaire_subcode = '';
		$this->email_facture = '';
		$this->name = '';
		$this->forme = '';
		$this->siren = '';
		$this->siret = '';
		$this->iban = '';
		$this->bic = '';
		$this->execices_comptables = '';
		$this->rcs = '';
		$this->tribunal_com = '';
		$this->activite = '';
		$this->lieu_exploi = '';
		$this->tva = '';
		$this->capital = '';
		$this->date_creation = '';
		$this->adresse1 = '';
		$this->adresse2 = '';
		$this->zip = '';
		$this->city = '';
		$this->id_pays = '';
		$this->phone = '';
		$this->status_adresse_correspondance = '';
		$this->status_client = '';
		$this->status_conseil_externe_entreprise = '';
		$this->preciser_conseil_externe_entreprise = '';
		$this->civilite_dirigeant = '';
		$this->nom_dirigeant = '';
		$this->prenom_dirigeant = '';
		$this->fonction_dirigeant = '';
		$this->email_dirigeant = '';
		$this->phone_dirigeant = '';
		$this->sector = '';
		$this->risk = '';
		$this->altares_eligibility = '';
		$this->altares_dateValeur = '';
		$this->altares_niveauRisque = '';
		$this->altares_scoreVingt = '';
		$this->added = '';
		$this->updated = '';

	}
}