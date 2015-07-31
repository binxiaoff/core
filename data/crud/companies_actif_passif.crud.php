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
class companies_actif_passif_crud
{
	
	public $id_actif_passif;
	public $id_company;
	public $ordre;
	public $annee;
	public $immobilisations_corporelles;
	public $immobilisations_incorporelles;
	public $immobilisations_financieres;
	public $stocks;
	public $creances_clients;
	public $disponibilites;
	public $valeurs_mobilieres_de_placement;
	public $capitaux_propres;
	public $provisions_pour_risques_et_charges;
	public $amortissement_sur_immo;
	public $dettes_financieres;
	public $dettes_fournisseurs;
	public $autres_dettes;
	public $added;
	public $updated;

	
	function companies_actif_passif($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_actif_passif = '';
		$this->id_company = '';
		$this->ordre = '';
		$this->annee = '';
		$this->immobilisations_corporelles = '';
		$this->immobilisations_incorporelles = '';
		$this->immobilisations_financieres = '';
		$this->stocks = '';
		$this->creances_clients = '';
		$this->disponibilites = '';
		$this->valeurs_mobilieres_de_placement = '';
		$this->capitaux_propres = '';
		$this->provisions_pour_risques_et_charges = '';
		$this->amortissement_sur_immo = '';
		$this->dettes_financieres = '';
		$this->dettes_fournisseurs = '';
		$this->autres_dettes = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_actif_passif')
	{
		$sql = 'SELECT * FROM  `companies_actif_passif` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_actif_passif = $record['id_actif_passif'];
			$this->id_company = $record['id_company'];
			$this->ordre = $record['ordre'];
			$this->annee = $record['annee'];
			$this->immobilisations_corporelles = $record['immobilisations_corporelles'];
			$this->immobilisations_incorporelles = $record['immobilisations_incorporelles'];
			$this->immobilisations_financieres = $record['immobilisations_financieres'];
			$this->stocks = $record['stocks'];
			$this->creances_clients = $record['creances_clients'];
			$this->disponibilites = $record['disponibilites'];
			$this->valeurs_mobilieres_de_placement = $record['valeurs_mobilieres_de_placement'];
			$this->capitaux_propres = $record['capitaux_propres'];
			$this->provisions_pour_risques_et_charges = $record['provisions_pour_risques_et_charges'];
			$this->amortissement_sur_immo = $record['amortissement_sur_immo'];
			$this->dettes_financieres = $record['dettes_financieres'];
			$this->dettes_fournisseurs = $record['dettes_fournisseurs'];
			$this->autres_dettes = $record['autres_dettes'];
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
		$this->id_actif_passif = $this->bdd->escape_string($this->id_actif_passif);
		$this->id_company = $this->bdd->escape_string($this->id_company);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->annee = $this->bdd->escape_string($this->annee);
		$this->immobilisations_corporelles = $this->bdd->escape_string($this->immobilisations_corporelles);
		$this->immobilisations_incorporelles = $this->bdd->escape_string($this->immobilisations_incorporelles);
		$this->immobilisations_financieres = $this->bdd->escape_string($this->immobilisations_financieres);
		$this->stocks = $this->bdd->escape_string($this->stocks);
		$this->creances_clients = $this->bdd->escape_string($this->creances_clients);
		$this->disponibilites = $this->bdd->escape_string($this->disponibilites);
		$this->valeurs_mobilieres_de_placement = $this->bdd->escape_string($this->valeurs_mobilieres_de_placement);
		$this->capitaux_propres = $this->bdd->escape_string($this->capitaux_propres);
		$this->provisions_pour_risques_et_charges = $this->bdd->escape_string($this->provisions_pour_risques_et_charges);
		$this->amortissement_sur_immo = $this->bdd->escape_string($this->amortissement_sur_immo);
		$this->dettes_financieres = $this->bdd->escape_string($this->dettes_financieres);
		$this->dettes_fournisseurs = $this->bdd->escape_string($this->dettes_fournisseurs);
		$this->autres_dettes = $this->bdd->escape_string($this->autres_dettes);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `companies_actif_passif` SET `id_company`="'.$this->id_company.'",`ordre`="'.$this->ordre.'",`annee`="'.$this->annee.'",`immobilisations_corporelles`="'.$this->immobilisations_corporelles.'",`immobilisations_incorporelles`="'.$this->immobilisations_incorporelles.'",`immobilisations_financieres`="'.$this->immobilisations_financieres.'",`stocks`="'.$this->stocks.'",`creances_clients`="'.$this->creances_clients.'",`disponibilites`="'.$this->disponibilites.'",`valeurs_mobilieres_de_placement`="'.$this->valeurs_mobilieres_de_placement.'",`capitaux_propres`="'.$this->capitaux_propres.'",`provisions_pour_risques_et_charges`="'.$this->provisions_pour_risques_et_charges.'",`amortissement_sur_immo`="'.$this->amortissement_sur_immo.'",`dettes_financieres`="'.$this->dettes_financieres.'",`dettes_fournisseurs`="'.$this->dettes_fournisseurs.'",`autres_dettes`="'.$this->autres_dettes.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_actif_passif="'.$this->id_actif_passif.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_actif_passif,'id_actif_passif');
	}
	
	function delete($id,$field='id_actif_passif')
	{
		if($id=='')
			$id = $this->id_actif_passif;
		$sql = 'DELETE FROM `companies_actif_passif` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_actif_passif = $this->bdd->escape_string($this->id_actif_passif);
		$this->id_company = $this->bdd->escape_string($this->id_company);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->annee = $this->bdd->escape_string($this->annee);
		$this->immobilisations_corporelles = $this->bdd->escape_string($this->immobilisations_corporelles);
		$this->immobilisations_incorporelles = $this->bdd->escape_string($this->immobilisations_incorporelles);
		$this->immobilisations_financieres = $this->bdd->escape_string($this->immobilisations_financieres);
		$this->stocks = $this->bdd->escape_string($this->stocks);
		$this->creances_clients = $this->bdd->escape_string($this->creances_clients);
		$this->disponibilites = $this->bdd->escape_string($this->disponibilites);
		$this->valeurs_mobilieres_de_placement = $this->bdd->escape_string($this->valeurs_mobilieres_de_placement);
		$this->capitaux_propres = $this->bdd->escape_string($this->capitaux_propres);
		$this->provisions_pour_risques_et_charges = $this->bdd->escape_string($this->provisions_pour_risques_et_charges);
		$this->amortissement_sur_immo = $this->bdd->escape_string($this->amortissement_sur_immo);
		$this->dettes_financieres = $this->bdd->escape_string($this->dettes_financieres);
		$this->dettes_fournisseurs = $this->bdd->escape_string($this->dettes_fournisseurs);
		$this->autres_dettes = $this->bdd->escape_string($this->autres_dettes);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `companies_actif_passif`(`id_company`,`ordre`,`annee`,`immobilisations_corporelles`,`immobilisations_incorporelles`,`immobilisations_financieres`,`stocks`,`creances_clients`,`disponibilites`,`valeurs_mobilieres_de_placement`,`capitaux_propres`,`provisions_pour_risques_et_charges`,`amortissement_sur_immo`,`dettes_financieres`,`dettes_fournisseurs`,`autres_dettes`,`added`,`updated`) VALUES("'.$this->id_company.'","'.$this->ordre.'","'.$this->annee.'","'.$this->immobilisations_corporelles.'","'.$this->immobilisations_incorporelles.'","'.$this->immobilisations_financieres.'","'.$this->stocks.'","'.$this->creances_clients.'","'.$this->disponibilites.'","'.$this->valeurs_mobilieres_de_placement.'","'.$this->capitaux_propres.'","'.$this->provisions_pour_risques_et_charges.'","'.$this->amortissement_sur_immo.'","'.$this->dettes_financieres.'","'.$this->dettes_fournisseurs.'","'.$this->autres_dettes.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_actif_passif = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_actif_passif,'id_actif_passif');
		
		return $this->id_actif_passif;
	}
	
	function unsetData()
	{
		$this->id_actif_passif = '';
		$this->id_company = '';
		$this->ordre = '';
		$this->annee = '';
		$this->immobilisations_corporelles = '';
		$this->immobilisations_incorporelles = '';
		$this->immobilisations_financieres = '';
		$this->stocks = '';
		$this->creances_clients = '';
		$this->disponibilites = '';
		$this->valeurs_mobilieres_de_placement = '';
		$this->capitaux_propres = '';
		$this->provisions_pour_risques_et_charges = '';
		$this->amortissement_sur_immo = '';
		$this->dettes_financieres = '';
		$this->dettes_fournisseurs = '';
		$this->autres_dettes = '';
		$this->added = '';
		$this->updated = '';

	}
}