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
class parrains_filleuls_mouvements_crud
{
	
	public $id_parrain_filleul_mouvement;
	public $id_parrain_filleul;
	public $id_client;
	public $type_preteur;
	public $montant;
	public $id_bid;
	public $id_bid_remb;
	public $status;
	public $type;
	public $added;
	public $updated;

	
	function parrains_filleuls_mouvements($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_parrain_filleul_mouvement = '';
		$this->id_parrain_filleul = '';
		$this->id_client = '';
		$this->type_preteur = '';
		$this->montant = '';
		$this->id_bid = '';
		$this->id_bid_remb = '';
		$this->status = '';
		$this->type = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_parrain_filleul_mouvement')
	{
		$sql = 'SELECT * FROM  `parrains_filleuls_mouvements` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_parrain_filleul_mouvement = $record['id_parrain_filleul_mouvement'];
			$this->id_parrain_filleul = $record['id_parrain_filleul'];
			$this->id_client = $record['id_client'];
			$this->type_preteur = $record['type_preteur'];
			$this->montant = $record['montant'];
			$this->id_bid = $record['id_bid'];
			$this->id_bid_remb = $record['id_bid_remb'];
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
		$this->id_parrain_filleul_mouvement = $this->bdd->escape_string($this->id_parrain_filleul_mouvement);
		$this->id_parrain_filleul = $this->bdd->escape_string($this->id_parrain_filleul);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->type_preteur = $this->bdd->escape_string($this->type_preteur);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->id_bid = $this->bdd->escape_string($this->id_bid);
		$this->id_bid_remb = $this->bdd->escape_string($this->id_bid_remb);
		$this->status = $this->bdd->escape_string($this->status);
		$this->type = $this->bdd->escape_string($this->type);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `parrains_filleuls_mouvements` SET `id_parrain_filleul`="'.$this->id_parrain_filleul.'",`id_client`="'.$this->id_client.'",`type_preteur`="'.$this->type_preteur.'",`montant`="'.$this->montant.'",`id_bid`="'.$this->id_bid.'",`id_bid_remb`="'.$this->id_bid_remb.'",`status`="'.$this->status.'",`type`="'.$this->type.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_parrain_filleul_mouvement="'.$this->id_parrain_filleul_mouvement.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_parrain_filleul_mouvement,'id_parrain_filleul_mouvement');
	}
	
	function delete($id,$field='id_parrain_filleul_mouvement')
	{
		if($id=='')
			$id = $this->id_parrain_filleul_mouvement;
		$sql = 'DELETE FROM `parrains_filleuls_mouvements` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_parrain_filleul_mouvement = $this->bdd->escape_string($this->id_parrain_filleul_mouvement);
		$this->id_parrain_filleul = $this->bdd->escape_string($this->id_parrain_filleul);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->type_preteur = $this->bdd->escape_string($this->type_preteur);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->id_bid = $this->bdd->escape_string($this->id_bid);
		$this->id_bid_remb = $this->bdd->escape_string($this->id_bid_remb);
		$this->status = $this->bdd->escape_string($this->status);
		$this->type = $this->bdd->escape_string($this->type);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `parrains_filleuls_mouvements`(`id_parrain_filleul`,`id_client`,`type_preteur`,`montant`,`id_bid`,`id_bid_remb`,`status`,`type`,`added`,`updated`) VALUES("'.$this->id_parrain_filleul.'","'.$this->id_client.'","'.$this->type_preteur.'","'.$this->montant.'","'.$this->id_bid.'","'.$this->id_bid_remb.'","'.$this->status.'","'.$this->type.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_parrain_filleul_mouvement = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_parrain_filleul_mouvement,'id_parrain_filleul_mouvement');
		
		return $this->id_parrain_filleul_mouvement;
	}
	
	function unsetData()
	{
		$this->id_parrain_filleul_mouvement = '';
		$this->id_parrain_filleul = '';
		$this->id_client = '';
		$this->type_preteur = '';
		$this->montant = '';
		$this->id_bid = '';
		$this->id_bid_remb = '';
		$this->status = '';
		$this->type = '';
		$this->added = '';
		$this->updated = '';

	}
}