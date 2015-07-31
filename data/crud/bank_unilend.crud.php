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
class bank_unilend_crud
{
	
	public $id_unilend;
	public $id_transaction;
	public $id_echeance_emprunteur;
	public $id_project;
	public $montant;
	public $etat;
	public $type;
	public $status;
	public $retrait_fiscale;
	public $added;
	public $updated;

	
	function bank_unilend($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_unilend = '';
		$this->id_transaction = '';
		$this->id_echeance_emprunteur = '';
		$this->id_project = '';
		$this->montant = '';
		$this->etat = '';
		$this->type = '';
		$this->status = '';
		$this->retrait_fiscale = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_unilend')
	{
		$sql = 'SELECT * FROM  `bank_unilend` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_unilend = $record['id_unilend'];
			$this->id_transaction = $record['id_transaction'];
			$this->id_echeance_emprunteur = $record['id_echeance_emprunteur'];
			$this->id_project = $record['id_project'];
			$this->montant = $record['montant'];
			$this->etat = $record['etat'];
			$this->type = $record['type'];
			$this->status = $record['status'];
			$this->retrait_fiscale = $record['retrait_fiscale'];
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
		$this->id_unilend = $this->bdd->escape_string($this->id_unilend);
		$this->id_transaction = $this->bdd->escape_string($this->id_transaction);
		$this->id_echeance_emprunteur = $this->bdd->escape_string($this->id_echeance_emprunteur);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->etat = $this->bdd->escape_string($this->etat);
		$this->type = $this->bdd->escape_string($this->type);
		$this->status = $this->bdd->escape_string($this->status);
		$this->retrait_fiscale = $this->bdd->escape_string($this->retrait_fiscale);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `bank_unilend` SET `id_transaction`="'.$this->id_transaction.'",`id_echeance_emprunteur`="'.$this->id_echeance_emprunteur.'",`id_project`="'.$this->id_project.'",`montant`="'.$this->montant.'",`etat`="'.$this->etat.'",`type`="'.$this->type.'",`status`="'.$this->status.'",`retrait_fiscale`="'.$this->retrait_fiscale.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_unilend="'.$this->id_unilend.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_unilend,'id_unilend');
	}
	
	function delete($id,$field='id_unilend')
	{
		if($id=='')
			$id = $this->id_unilend;
		$sql = 'DELETE FROM `bank_unilend` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_unilend = $this->bdd->escape_string($this->id_unilend);
		$this->id_transaction = $this->bdd->escape_string($this->id_transaction);
		$this->id_echeance_emprunteur = $this->bdd->escape_string($this->id_echeance_emprunteur);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->etat = $this->bdd->escape_string($this->etat);
		$this->type = $this->bdd->escape_string($this->type);
		$this->status = $this->bdd->escape_string($this->status);
		$this->retrait_fiscale = $this->bdd->escape_string($this->retrait_fiscale);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `bank_unilend`(`id_transaction`,`id_echeance_emprunteur`,`id_project`,`montant`,`etat`,`type`,`status`,`retrait_fiscale`,`added`,`updated`) VALUES("'.$this->id_transaction.'","'.$this->id_echeance_emprunteur.'","'.$this->id_project.'","'.$this->montant.'","'.$this->etat.'","'.$this->type.'","'.$this->status.'","'.$this->retrait_fiscale.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_unilend = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_unilend,'id_unilend');
		
		return $this->id_unilend;
	}
	
	function unsetData()
	{
		$this->id_unilend = '';
		$this->id_transaction = '';
		$this->id_echeance_emprunteur = '';
		$this->id_project = '';
		$this->montant = '';
		$this->etat = '';
		$this->type = '';
		$this->status = '';
		$this->retrait_fiscale = '';
		$this->added = '';
		$this->updated = '';

	}
}