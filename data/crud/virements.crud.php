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
class virements_crud
{
	
	public $id_virement;
	public $id_client;
	public $id_project;
	public $id_transaction;
	public $montant;
	public $motif;
	public $type;
	public $status;
	public $added_xml;
	public $added;
	public $updated;

	
	function virements($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_virement = '';
		$this->id_client = '';
		$this->id_project = '';
		$this->id_transaction = '';
		$this->montant = '';
		$this->motif = '';
		$this->type = '';
		$this->status = '';
		$this->added_xml = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_virement')
	{
		$sql = 'SELECT * FROM  `virements` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_virement = $record['id_virement'];
			$this->id_client = $record['id_client'];
			$this->id_project = $record['id_project'];
			$this->id_transaction = $record['id_transaction'];
			$this->montant = $record['montant'];
			$this->motif = $record['motif'];
			$this->type = $record['type'];
			$this->status = $record['status'];
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
		$this->id_virement = $this->bdd->escape_string($this->id_virement);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->id_transaction = $this->bdd->escape_string($this->id_transaction);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->motif = $this->bdd->escape_string($this->motif);
		$this->type = $this->bdd->escape_string($this->type);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added_xml = $this->bdd->escape_string($this->added_xml);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `virements` SET `id_client`="'.$this->id_client.'",`id_project`="'.$this->id_project.'",`id_transaction`="'.$this->id_transaction.'",`montant`="'.$this->montant.'",`motif`="'.$this->motif.'",`type`="'.$this->type.'",`status`="'.$this->status.'",`added_xml`="'.$this->added_xml.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_virement="'.$this->id_virement.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_virement,'id_virement');
	}
	
	function delete($id,$field='id_virement')
	{
		if($id=='')
			$id = $this->id_virement;
		$sql = 'DELETE FROM `virements` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_virement = $this->bdd->escape_string($this->id_virement);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->id_transaction = $this->bdd->escape_string($this->id_transaction);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->motif = $this->bdd->escape_string($this->motif);
		$this->type = $this->bdd->escape_string($this->type);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added_xml = $this->bdd->escape_string($this->added_xml);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `virements`(`id_client`,`id_project`,`id_transaction`,`montant`,`motif`,`type`,`status`,`added_xml`,`added`,`updated`) VALUES("'.$this->id_client.'","'.$this->id_project.'","'.$this->id_transaction.'","'.$this->montant.'","'.$this->motif.'","'.$this->type.'","'.$this->status.'","'.$this->added_xml.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_virement = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_virement,'id_virement');
		
		return $this->id_virement;
	}
	
	function unsetData()
	{
		$this->id_virement = '';
		$this->id_client = '';
		$this->id_project = '';
		$this->id_transaction = '';
		$this->montant = '';
		$this->motif = '';
		$this->type = '';
		$this->status = '';
		$this->added_xml = '';
		$this->added = '';
		$this->updated = '';

	}
}