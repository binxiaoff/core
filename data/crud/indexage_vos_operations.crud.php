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
class indexage_vos_operations_crud
{
	
	public $id;
	public $id_client;
	public $id_transaction;
	public $id_echeancier;
	public $id_projet;
	public $type_transaction;
	public $libelle_operation;
	public $bdc;
	public $libelle_projet;
	public $date_operation;
	public $solde;
	public $montant_operation;
	public $montant_capital;
	public $montant_interet;
	public $libelle_prelevement;
	public $montant_prelevement;
	public $updated;
	public $added;

	
	function indexage_vos_operations($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id = '';
		$this->id_client = '';
		$this->id_transaction = '';
		$this->id_echeancier = '';
		$this->id_projet = '';
		$this->type_transaction = '';
		$this->libelle_operation = '';
		$this->bdc = '';
		$this->libelle_projet = '';
		$this->date_operation = '';
		$this->solde = '';
		$this->montant_operation = '';
		$this->montant_capital = '';
		$this->montant_interet = '';
		$this->libelle_prelevement = '';
		$this->montant_prelevement = '';
		$this->updated = '';
		$this->added = '';

	}
	
	function get($id,$field='id')
	{
		$sql = 'SELECT * FROM  `indexage_vos_operations` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id = $record['id'];
			$this->id_client = $record['id_client'];
			$this->id_transaction = $record['id_transaction'];
			$this->id_echeancier = $record['id_echeancier'];
			$this->id_projet = $record['id_projet'];
			$this->type_transaction = $record['type_transaction'];
			$this->libelle_operation = $record['libelle_operation'];
			$this->bdc = $record['bdc'];
			$this->libelle_projet = $record['libelle_projet'];
			$this->date_operation = $record['date_operation'];
			$this->solde = $record['solde'];
			$this->montant_operation = $record['montant_operation'];
			$this->montant_capital = $record['montant_capital'];
			$this->montant_interet = $record['montant_interet'];
			$this->libelle_prelevement = $record['libelle_prelevement'];
			$this->montant_prelevement = $record['montant_prelevement'];
			$this->updated = $record['updated'];
			$this->added = $record['added'];

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
		$this->id = $this->bdd->escape_string($this->id);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_transaction = $this->bdd->escape_string($this->id_transaction);
		$this->id_echeancier = $this->bdd->escape_string($this->id_echeancier);
		$this->id_projet = $this->bdd->escape_string($this->id_projet);
		$this->type_transaction = $this->bdd->escape_string($this->type_transaction);
		$this->libelle_operation = $this->bdd->escape_string($this->libelle_operation);
		$this->bdc = $this->bdd->escape_string($this->bdc);
		$this->libelle_projet = $this->bdd->escape_string($this->libelle_projet);
		$this->date_operation = $this->bdd->escape_string($this->date_operation);
		$this->solde = $this->bdd->escape_string($this->solde);
		$this->montant_operation = $this->bdd->escape_string($this->montant_operation);
		$this->montant_capital = $this->bdd->escape_string($this->montant_capital);
		$this->montant_interet = $this->bdd->escape_string($this->montant_interet);
		$this->libelle_prelevement = $this->bdd->escape_string($this->libelle_prelevement);
		$this->montant_prelevement = $this->bdd->escape_string($this->montant_prelevement);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->added = $this->bdd->escape_string($this->added);

		
		$sql = 'UPDATE `indexage_vos_operations` SET `id_client`="'.$this->id_client.'",`id_transaction`="'.$this->id_transaction.'",`id_echeancier`="'.$this->id_echeancier.'",`id_projet`="'.$this->id_projet.'",`type_transaction`="'.$this->type_transaction.'",`libelle_operation`="'.$this->libelle_operation.'",`bdc`="'.$this->bdc.'",`libelle_projet`="'.$this->libelle_projet.'",`date_operation`="'.$this->date_operation.'",`solde`="'.$this->solde.'",`montant_operation`="'.$this->montant_operation.'",`montant_capital`="'.$this->montant_capital.'",`montant_interet`="'.$this->montant_interet.'",`libelle_prelevement`="'.$this->libelle_prelevement.'",`montant_prelevement`="'.$this->montant_prelevement.'",`updated`=NOW(),`added`="'.$this->added.'" WHERE id="'.$this->id.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id,'id');
	}
	
	function delete($id,$field='id')
	{
		if($id=='')
			$id = $this->id;
		$sql = 'DELETE FROM `indexage_vos_operations` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id = $this->bdd->escape_string($this->id);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_transaction = $this->bdd->escape_string($this->id_transaction);
		$this->id_echeancier = $this->bdd->escape_string($this->id_echeancier);
		$this->id_projet = $this->bdd->escape_string($this->id_projet);
		$this->type_transaction = $this->bdd->escape_string($this->type_transaction);
		$this->libelle_operation = $this->bdd->escape_string($this->libelle_operation);
		$this->bdc = $this->bdd->escape_string($this->bdc);
		$this->libelle_projet = $this->bdd->escape_string($this->libelle_projet);
		$this->date_operation = $this->bdd->escape_string($this->date_operation);
		$this->solde = $this->bdd->escape_string($this->solde);
		$this->montant_operation = $this->bdd->escape_string($this->montant_operation);
		$this->montant_capital = $this->bdd->escape_string($this->montant_capital);
		$this->montant_interet = $this->bdd->escape_string($this->montant_interet);
		$this->libelle_prelevement = $this->bdd->escape_string($this->libelle_prelevement);
		$this->montant_prelevement = $this->bdd->escape_string($this->montant_prelevement);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->added = $this->bdd->escape_string($this->added);

		
		$sql = 'INSERT INTO `indexage_vos_operations`(`id_client`,`id_transaction`,`id_echeancier`,`id_projet`,`type_transaction`,`libelle_operation`,`bdc`,`libelle_projet`,`date_operation`,`solde`,`montant_operation`,`montant_capital`,`montant_interet`,`libelle_prelevement`,`montant_prelevement`,`updated`,`added`) VALUES("'.$this->id_client.'","'.$this->id_transaction.'","'.$this->id_echeancier.'","'.$this->id_projet.'","'.$this->type_transaction.'","'.$this->libelle_operation.'","'.$this->bdc.'","'.$this->libelle_projet.'","'.$this->date_operation.'","'.$this->solde.'","'.$this->montant_operation.'","'.$this->montant_capital.'","'.$this->montant_interet.'","'.$this->libelle_prelevement.'","'.$this->montant_prelevement.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id,'id');
		
		return $this->id;
	}
	
	function unsetData()
	{
		$this->id = '';
		$this->id_client = '';
		$this->id_transaction = '';
		$this->id_echeancier = '';
		$this->id_projet = '';
		$this->type_transaction = '';
		$this->libelle_operation = '';
		$this->bdc = '';
		$this->libelle_projet = '';
		$this->date_operation = '';
		$this->solde = '';
		$this->montant_operation = '';
		$this->montant_capital = '';
		$this->montant_interet = '';
		$this->libelle_prelevement = '';
		$this->montant_prelevement = '';
		$this->updated = '';
		$this->added = '';

	}
}