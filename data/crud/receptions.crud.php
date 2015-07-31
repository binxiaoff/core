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
class receptions_crud
{
	
	public $id_reception;
	public $motif;
	public $montant;
	public $type;
	public $remb_anticipe;
	public $status_virement;
	public $status_prelevement;
	public $status_bo;
	public $remb;
	public $id_client;
	public $id_project;
	public $ligne;
	public $added;
	public $updated;

	
	function receptions($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_reception = '';
		$this->motif = '';
		$this->montant = '';
		$this->type = '';
		$this->remb_anticipe = '';
		$this->status_virement = '';
		$this->status_prelevement = '';
		$this->status_bo = '';
		$this->remb = '';
		$this->id_client = '';
		$this->id_project = '';
		$this->ligne = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_reception')
	{
		$sql = 'SELECT * FROM  `receptions` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_reception = $record['id_reception'];
			$this->motif = $record['motif'];
			$this->montant = $record['montant'];
			$this->type = $record['type'];
			$this->remb_anticipe = $record['remb_anticipe'];
			$this->status_virement = $record['status_virement'];
			$this->status_prelevement = $record['status_prelevement'];
			$this->status_bo = $record['status_bo'];
			$this->remb = $record['remb'];
			$this->id_client = $record['id_client'];
			$this->id_project = $record['id_project'];
			$this->ligne = $record['ligne'];
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
		$this->id_reception = $this->bdd->escape_string($this->id_reception);
		$this->motif = $this->bdd->escape_string($this->motif);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->type = $this->bdd->escape_string($this->type);
		$this->remb_anticipe = $this->bdd->escape_string($this->remb_anticipe);
		$this->status_virement = $this->bdd->escape_string($this->status_virement);
		$this->status_prelevement = $this->bdd->escape_string($this->status_prelevement);
		$this->status_bo = $this->bdd->escape_string($this->status_bo);
		$this->remb = $this->bdd->escape_string($this->remb);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->ligne = $this->bdd->escape_string($this->ligne);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `receptions` SET `motif`="'.$this->motif.'",`montant`="'.$this->montant.'",`type`="'.$this->type.'",`remb_anticipe`="'.$this->remb_anticipe.'",`status_virement`="'.$this->status_virement.'",`status_prelevement`="'.$this->status_prelevement.'",`status_bo`="'.$this->status_bo.'",`remb`="'.$this->remb.'",`id_client`="'.$this->id_client.'",`id_project`="'.$this->id_project.'",`ligne`="'.$this->ligne.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_reception="'.$this->id_reception.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_reception,'id_reception');
	}
	
	function delete($id,$field='id_reception')
	{
		if($id=='')
			$id = $this->id_reception;
		$sql = 'DELETE FROM `receptions` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_reception = $this->bdd->escape_string($this->id_reception);
		$this->motif = $this->bdd->escape_string($this->motif);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->type = $this->bdd->escape_string($this->type);
		$this->remb_anticipe = $this->bdd->escape_string($this->remb_anticipe);
		$this->status_virement = $this->bdd->escape_string($this->status_virement);
		$this->status_prelevement = $this->bdd->escape_string($this->status_prelevement);
		$this->status_bo = $this->bdd->escape_string($this->status_bo);
		$this->remb = $this->bdd->escape_string($this->remb);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->ligne = $this->bdd->escape_string($this->ligne);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `receptions`(`motif`,`montant`,`type`,`remb_anticipe`,`status_virement`,`status_prelevement`,`status_bo`,`remb`,`id_client`,`id_project`,`ligne`,`added`,`updated`) VALUES("'.$this->motif.'","'.$this->montant.'","'.$this->type.'","'.$this->remb_anticipe.'","'.$this->status_virement.'","'.$this->status_prelevement.'","'.$this->status_bo.'","'.$this->remb.'","'.$this->id_client.'","'.$this->id_project.'","'.$this->ligne.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_reception = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_reception,'id_reception');
		
		return $this->id_reception;
	}
	
	function unsetData()
	{
		$this->id_reception = '';
		$this->motif = '';
		$this->montant = '';
		$this->type = '';
		$this->remb_anticipe = '';
		$this->status_virement = '';
		$this->status_prelevement = '';
		$this->status_bo = '';
		$this->remb = '';
		$this->id_client = '';
		$this->id_project = '';
		$this->ligne = '';
		$this->added = '';
		$this->updated = '';

	}
}