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
class offres_bienvenues_details_crud
{
	
	public $id_offre_bienvenue_detail;
	public $id_offre_bienvenue;
	public $motif;
	public $id_client;
	public $id_bid;
	public $id_bid_remb;
	public $montant;
	public $status;
	public $type;
	public $added;
	public $updated;

	
	function offres_bienvenues_details($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_offre_bienvenue_detail = '';
		$this->id_offre_bienvenue = '';
		$this->motif = '';
		$this->id_client = '';
		$this->id_bid = '';
		$this->id_bid_remb = '';
		$this->montant = '';
		$this->status = '';
		$this->type = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_offre_bienvenue_detail')
	{
		$sql = 'SELECT * FROM  `offres_bienvenues_details` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_offre_bienvenue_detail = $record['id_offre_bienvenue_detail'];
			$this->id_offre_bienvenue = $record['id_offre_bienvenue'];
			$this->motif = $record['motif'];
			$this->id_client = $record['id_client'];
			$this->id_bid = $record['id_bid'];
			$this->id_bid_remb = $record['id_bid_remb'];
			$this->montant = $record['montant'];
			$this->status = $record['status'];
			$this->type = $record['type'];
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
		$this->id_offre_bienvenue_detail = $this->bdd->escape_string($this->id_offre_bienvenue_detail);
		$this->id_offre_bienvenue = $this->bdd->escape_string($this->id_offre_bienvenue);
		$this->motif = $this->bdd->escape_string($this->motif);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_bid = $this->bdd->escape_string($this->id_bid);
		$this->id_bid_remb = $this->bdd->escape_string($this->id_bid_remb);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->status = $this->bdd->escape_string($this->status);
		$this->type = $this->bdd->escape_string($this->type);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `offres_bienvenues_details` SET `id_offre_bienvenue`="'.$this->id_offre_bienvenue.'",`motif`="'.$this->motif.'",`id_client`="'.$this->id_client.'",`id_bid`="'.$this->id_bid.'",`id_bid_remb`="'.$this->id_bid_remb.'",`montant`="'.$this->montant.'",`status`="'.$this->status.'",`type`="'.$this->type.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_offre_bienvenue_detail="'.$this->id_offre_bienvenue_detail.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_offre_bienvenue_detail,'id_offre_bienvenue_detail');
	}
	
	function delete($id,$field='id_offre_bienvenue_detail')
	{
		if($id=='')
			$id = $this->id_offre_bienvenue_detail;
		$sql = 'DELETE FROM `offres_bienvenues_details` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_offre_bienvenue_detail = $this->bdd->escape_string($this->id_offre_bienvenue_detail);
		$this->id_offre_bienvenue = $this->bdd->escape_string($this->id_offre_bienvenue);
		$this->motif = $this->bdd->escape_string($this->motif);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_bid = $this->bdd->escape_string($this->id_bid);
		$this->id_bid_remb = $this->bdd->escape_string($this->id_bid_remb);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->status = $this->bdd->escape_string($this->status);
		$this->type = $this->bdd->escape_string($this->type);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `offres_bienvenues_details`(`id_offre_bienvenue`,`motif`,`id_client`,`id_bid`,`id_bid_remb`,`montant`,`status`,`type`,`added`,`updated`) VALUES("'.$this->id_offre_bienvenue.'","'.$this->motif.'","'.$this->id_client.'","'.$this->id_bid.'","'.$this->id_bid_remb.'","'.$this->montant.'","'.$this->status.'","'.$this->type.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_offre_bienvenue_detail = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_offre_bienvenue_detail,'id_offre_bienvenue_detail');
		
		return $this->id_offre_bienvenue_detail;
	}
	
	function unsetData()
	{
		$this->id_offre_bienvenue_detail = '';
		$this->id_offre_bienvenue = '';
		$this->motif = '';
		$this->id_client = '';
		$this->id_bid = '';
		$this->id_bid_remb = '';
		$this->montant = '';
		$this->status = '';
		$this->type = '';
		$this->added = '';
		$this->updated = '';

	}
}