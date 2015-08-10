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
class prelevements_crud
{
	
	public $id_prelevement;

	public $id_client;

	public $id_transaction;

	public $id_project;

	public $motif;

	public $montant;

	public $bic;

	public $iban;

	public $type_prelevement;

	public $jour_prelevement;

	public $type;

	public $num_prelevement;

	public $status;

	public $date_execution_demande_prelevement;

	public $date_echeance_emprunteur;

	public $added_xml;

	public $added;

	public $updated;


	
	function prelevements($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_prelevement = '';

		$this->id_client = '';

		$this->id_transaction = '';

		$this->id_project = '';

		$this->motif = '';

		$this->montant = '';

		$this->bic = '';

		$this->iban = '';

		$this->type_prelevement = '';

		$this->jour_prelevement = '';

		$this->type = '';

		$this->num_prelevement = '';

		$this->status = '';

		$this->date_execution_demande_prelevement = '';

		$this->date_echeance_emprunteur = '';

		$this->added_xml = '';

		$this->added = '';

		$this->updated = '';


	}
	
	function get($id,$field='id_prelevement')
	{
		$sql = 'SELECT * FROM  `prelevements` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_prelevement = $record['id_prelevement'];

			$this->id_client = $record['id_client'];

			$this->id_transaction = $record['id_transaction'];

			$this->id_project = $record['id_project'];

			$this->motif = $record['motif'];

			$this->montant = $record['montant'];

			$this->bic = $record['bic'];

			$this->iban = $record['iban'];

			$this->type_prelevement = $record['type_prelevement'];

			$this->jour_prelevement = $record['jour_prelevement'];

			$this->type = $record['type'];

			$this->num_prelevement = $record['num_prelevement'];

			$this->status = $record['status'];

			$this->date_execution_demande_prelevement = $record['date_execution_demande_prelevement'];

			$this->date_echeance_emprunteur = $record['date_echeance_emprunteur'];

			$this->added_xml = $record['added_xml'];

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
		$this->id_prelevement = $this->bdd->escape_string($this->id_prelevement);

		$this->id_client = $this->bdd->escape_string($this->id_client);

		$this->id_transaction = $this->bdd->escape_string($this->id_transaction);

		$this->id_project = $this->bdd->escape_string($this->id_project);

		$this->motif = $this->bdd->escape_string($this->motif);

		$this->montant = $this->bdd->escape_string($this->montant);

		$this->bic = $this->bdd->escape_string($this->bic);

		$this->iban = $this->bdd->escape_string($this->iban);

		$this->type_prelevement = $this->bdd->escape_string($this->type_prelevement);

		$this->jour_prelevement = $this->bdd->escape_string($this->jour_prelevement);

		$this->type = $this->bdd->escape_string($this->type);

		$this->num_prelevement = $this->bdd->escape_string($this->num_prelevement);

		$this->status = $this->bdd->escape_string($this->status);

		$this->date_execution_demande_prelevement = $this->bdd->escape_string($this->date_execution_demande_prelevement);

		$this->date_echeance_emprunteur = $this->bdd->escape_string($this->date_echeance_emprunteur);

		$this->added_xml = $this->bdd->escape_string($this->added_xml);

		$this->added = $this->bdd->escape_string($this->added);

		$this->updated = $this->bdd->escape_string($this->updated);


		
		$sql = 'UPDATE `prelevements` SET `id_client`="'.$this->id_client.'",`id_transaction`="'.$this->id_transaction.'",`id_project`="'.$this->id_project.'",`motif`="'.$this->motif.'",`montant`="'.$this->montant.'",`bic`="'.$this->bic.'",`iban`="'.$this->iban.'",`type_prelevement`="'.$this->type_prelevement.'",`jour_prelevement`="'.$this->jour_prelevement.'",`type`="'.$this->type.'",`num_prelevement`="'.$this->num_prelevement.'",`status`="'.$this->status.'",`date_execution_demande_prelevement`="'.$this->date_execution_demande_prelevement.'",`date_echeance_emprunteur`="'.$this->date_echeance_emprunteur.'",`added_xml`="'.$this->added_xml.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_prelevement="'.$this->id_prelevement.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_prelevement,'id_prelevement');
	}
	
	function delete($id,$field='id_prelevement')
	{
		if($id=='')
			$id = $this->id_prelevement;
		$sql = 'DELETE FROM `prelevements` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_prelevement = $this->bdd->escape_string($this->id_prelevement);

		$this->id_client = $this->bdd->escape_string($this->id_client);

		$this->id_transaction = $this->bdd->escape_string($this->id_transaction);

		$this->id_project = $this->bdd->escape_string($this->id_project);

		$this->motif = $this->bdd->escape_string($this->motif);

		$this->montant = $this->bdd->escape_string($this->montant);

		$this->bic = $this->bdd->escape_string($this->bic);

		$this->iban = $this->bdd->escape_string($this->iban);

		$this->type_prelevement = $this->bdd->escape_string($this->type_prelevement);

		$this->jour_prelevement = $this->bdd->escape_string($this->jour_prelevement);

		$this->type = $this->bdd->escape_string($this->type);

		$this->num_prelevement = $this->bdd->escape_string($this->num_prelevement);

		$this->status = $this->bdd->escape_string($this->status);

		$this->date_execution_demande_prelevement = $this->bdd->escape_string($this->date_execution_demande_prelevement);

		$this->date_echeance_emprunteur = $this->bdd->escape_string($this->date_echeance_emprunteur);

		$this->added_xml = $this->bdd->escape_string($this->added_xml);

		$this->added = $this->bdd->escape_string($this->added);

		$this->updated = $this->bdd->escape_string($this->updated);


		
		$sql = 'INSERT INTO `prelevements`(`id_client`,`id_transaction`,`id_project`,`motif`,`montant`,`bic`,`iban`,`type_prelevement`,`jour_prelevement`,`type`,`num_prelevement`,`status`,`date_execution_demande_prelevement`,`date_echeance_emprunteur`,`added_xml`,`added`,`updated`) VALUES("'.$this->id_client.'","'.$this->id_transaction.'","'.$this->id_project.'","'.$this->motif.'","'.$this->montant.'","'.$this->bic.'","'.$this->iban.'","'.$this->type_prelevement.'","'.$this->jour_prelevement.'","'.$this->type.'","'.$this->num_prelevement.'","'.$this->status.'","'.$this->date_execution_demande_prelevement.'","'.$this->date_echeance_emprunteur.'","'.$this->added_xml.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_prelevement = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_prelevement,'id_prelevement');
		
		return $this->id_prelevement;
	}
	
	function unsetData()
	{
		$this->id_prelevement = '';

		$this->id_client = '';

		$this->id_transaction = '';

		$this->id_project = '';

		$this->motif = '';

		$this->montant = '';

		$this->bic = '';

		$this->iban = '';

		$this->type_prelevement = '';

		$this->jour_prelevement = '';

		$this->type = '';

		$this->num_prelevement = '';

		$this->status = '';

		$this->date_execution_demande_prelevement = '';

		$this->date_echeance_emprunteur = '';

		$this->added_xml = '';

		$this->added = '';

		$this->updated = '';


	}
}