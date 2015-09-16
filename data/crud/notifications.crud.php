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
class notifications_crud
{
	
	public $id_notification;
	public $id_lender;
	public $type;
	public $id_project;
	public $id_bid;
	public $amount;
	public $status;
	public $added;
	public $updated;

	
	function notifications($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_notification = '';
		$this->id_lender = '';
		$this->type = '';
		$this->id_project = '';
		$this->id_bid = '';
		$this->amount = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_notification')
	{
		$sql = 'SELECT * FROM  `notifications` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_notification = $record['id_notification'];
			$this->id_lender = $record['id_lender'];
			$this->type = $record['type'];
			$this->id_project = $record['id_project'];
			$this->id_bid = $record['id_bid'];
			$this->amount = $record['amount'];
			$this->status = $record['status'];
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
		$this->id_notification = $this->bdd->escape_string($this->id_notification);
		$this->id_lender = $this->bdd->escape_string($this->id_lender);
		$this->type = $this->bdd->escape_string($this->type);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->id_bid = $this->bdd->escape_string($this->id_bid);
		$this->amount = $this->bdd->escape_string($this->amount);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `notifications` SET `id_lender`="'.$this->id_lender.'",`type`="'.$this->type.'",`id_project`="'.$this->id_project.'",`id_bid`="'.$this->id_bid.'",`amount`="'.$this->amount.'",`status`="'.$this->status.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_notification="'.$this->id_notification.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_notification,'id_notification');
	}
	
	function delete($id,$field='id_notification')
	{
		if($id=='')
			$id = $this->id_notification;
		$sql = 'DELETE FROM `notifications` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_notification = $this->bdd->escape_string($this->id_notification);
		$this->id_lender = $this->bdd->escape_string($this->id_lender);
		$this->type = $this->bdd->escape_string($this->type);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->id_bid = $this->bdd->escape_string($this->id_bid);
		$this->amount = $this->bdd->escape_string($this->amount);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `notifications`(`id_lender`,`type`,`id_project`,`id_bid`,`amount`,`status`,`added`,`updated`) VALUES("'.$this->id_lender.'","'.$this->type.'","'.$this->id_project.'","'.$this->id_bid.'","'.$this->amount.'","'.$this->status.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_notification = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_notification,'id_notification');
		
		return $this->id_notification;
	}
	
	function unsetData()
	{
		$this->id_notification = '';
		$this->id_lender = '';
		$this->type = '';
		$this->id_project = '';
		$this->id_bid = '';
		$this->amount = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
}