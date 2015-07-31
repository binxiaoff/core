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
class bids_logs_crud
{
	
	public $id_bid_log;
	public $id_project;
	public $debut;
	public $fin;
	public $nb_bids_encours;
	public $nb_bids_ko;
	public $total_bids_ko;
	public $total_bids;
	public $added;
	public $updated;

	
	function bids_logs($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_bid_log = '';
		$this->id_project = '';
		$this->debut = '';
		$this->fin = '';
		$this->nb_bids_encours = '';
		$this->nb_bids_ko = '';
		$this->total_bids_ko = '';
		$this->total_bids = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_bid_log')
	{
		$sql = 'SELECT * FROM  `bids_logs` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_bid_log = $record['id_bid_log'];
			$this->id_project = $record['id_project'];
			$this->debut = $record['debut'];
			$this->fin = $record['fin'];
			$this->nb_bids_encours = $record['nb_bids_encours'];
			$this->nb_bids_ko = $record['nb_bids_ko'];
			$this->total_bids_ko = $record['total_bids_ko'];
			$this->total_bids = $record['total_bids'];
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
		$this->id_bid_log = $this->bdd->escape_string($this->id_bid_log);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->debut = $this->bdd->escape_string($this->debut);
		$this->fin = $this->bdd->escape_string($this->fin);
		$this->nb_bids_encours = $this->bdd->escape_string($this->nb_bids_encours);
		$this->nb_bids_ko = $this->bdd->escape_string($this->nb_bids_ko);
		$this->total_bids_ko = $this->bdd->escape_string($this->total_bids_ko);
		$this->total_bids = $this->bdd->escape_string($this->total_bids);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `bids_logs` SET `id_project`="'.$this->id_project.'",`debut`="'.$this->debut.'",`fin`="'.$this->fin.'",`nb_bids_encours`="'.$this->nb_bids_encours.'",`nb_bids_ko`="'.$this->nb_bids_ko.'",`total_bids_ko`="'.$this->total_bids_ko.'",`total_bids`="'.$this->total_bids.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_bid_log="'.$this->id_bid_log.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_bid_log,'id_bid_log');
	}
	
	function delete($id,$field='id_bid_log')
	{
		if($id=='')
			$id = $this->id_bid_log;
		$sql = 'DELETE FROM `bids_logs` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_bid_log = $this->bdd->escape_string($this->id_bid_log);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->debut = $this->bdd->escape_string($this->debut);
		$this->fin = $this->bdd->escape_string($this->fin);
		$this->nb_bids_encours = $this->bdd->escape_string($this->nb_bids_encours);
		$this->nb_bids_ko = $this->bdd->escape_string($this->nb_bids_ko);
		$this->total_bids_ko = $this->bdd->escape_string($this->total_bids_ko);
		$this->total_bids = $this->bdd->escape_string($this->total_bids);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `bids_logs`(`id_project`,`debut`,`fin`,`nb_bids_encours`,`nb_bids_ko`,`total_bids_ko`,`total_bids`,`added`,`updated`) VALUES("'.$this->id_project.'","'.$this->debut.'","'.$this->fin.'","'.$this->nb_bids_encours.'","'.$this->nb_bids_ko.'","'.$this->total_bids_ko.'","'.$this->total_bids.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_bid_log = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_bid_log,'id_bid_log');
		
		return $this->id_bid_log;
	}
	
	function unsetData()
	{
		$this->id_bid_log = '';
		$this->id_project = '';
		$this->debut = '';
		$this->fin = '';
		$this->nb_bids_encours = '';
		$this->nb_bids_ko = '';
		$this->total_bids_ko = '';
		$this->total_bids = '';
		$this->added = '';
		$this->updated = '';

	}
}