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
class bids_crud
{
	
	public $id_bid;
	public $id_lender_account;
	public $id_project;
	public $id_autobid;
	public $id_lender_wallet_line;
	public $amount;
	public $rate;
	public $ordre;
	public $status;
	public $status_email_bid_ko;
	public $checked;
	public $added;
	public $updated;

	
	function bids($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_bid = '';
		$this->id_lender_account = '';
		$this->id_project = '';
		$this->id_autobid = '';
		$this->id_lender_wallet_line = '';
		$this->amount = '';
		$this->rate = '';
		$this->ordre = '';
		$this->status = '';
		$this->status_email_bid_ko = '';
		$this->checked = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_bid')
	{
		$sql = 'SELECT * FROM  `bids` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_bid = $record['id_bid'];
			$this->id_lender_account = $record['id_lender_account'];
			$this->id_project = $record['id_project'];
			$this->id_autobid = $record['id_autobid'];
			$this->id_lender_wallet_line = $record['id_lender_wallet_line'];
			$this->amount = $record['amount'];
			$this->rate = $record['rate'];
			$this->ordre = $record['ordre'];
			$this->status = $record['status'];
			$this->status_email_bid_ko = $record['status_email_bid_ko'];
			$this->checked = $record['checked'];
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
		$this->id_bid = $this->bdd->escape_string($this->id_bid);
		$this->id_lender_account = $this->bdd->escape_string($this->id_lender_account);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->id_autobid = $this->bdd->escape_string($this->id_autobid);
		$this->id_lender_wallet_line = $this->bdd->escape_string($this->id_lender_wallet_line);
		$this->amount = $this->bdd->escape_string($this->amount);
		$this->rate = $this->bdd->escape_string($this->rate);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->status = $this->bdd->escape_string($this->status);
		$this->status_email_bid_ko = $this->bdd->escape_string($this->status_email_bid_ko);
		$this->checked = $this->bdd->escape_string($this->checked);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `bids` SET `id_lender_account`="'.$this->id_lender_account.'",`id_project`="'.$this->id_project.'",`id_autobid`="'.$this->id_autobid.'",`id_lender_wallet_line`="'.$this->id_lender_wallet_line.'",`amount`="'.$this->amount.'",`rate`="'.$this->rate.'",`ordre`="'.$this->ordre.'",`status`="'.$this->status.'",`status_email_bid_ko`="'.$this->status_email_bid_ko.'",`checked`="'.$this->checked.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_bid="'.$this->id_bid.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_bid,'id_bid');
	}
	
	function delete($id,$field='id_bid')
	{
		if($id=='')
			$id = $this->id_bid;
		$sql = 'DELETE FROM `bids` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_bid = $this->bdd->escape_string($this->id_bid);
		$this->id_lender_account = $this->bdd->escape_string($this->id_lender_account);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->id_autobid = $this->bdd->escape_string($this->id_autobid);
		$this->id_lender_wallet_line = $this->bdd->escape_string($this->id_lender_wallet_line);
		$this->amount = $this->bdd->escape_string($this->amount);
		$this->rate = $this->bdd->escape_string($this->rate);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->status = $this->bdd->escape_string($this->status);
		$this->status_email_bid_ko = $this->bdd->escape_string($this->status_email_bid_ko);
		$this->checked = $this->bdd->escape_string($this->checked);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `bids`(`id_lender_account`,`id_project`,`id_autobid`,`id_lender_wallet_line`,`amount`,`rate`,`ordre`,`status`,`status_email_bid_ko`,`checked`,`added`,`updated`) VALUES("'.$this->id_lender_account.'","'.$this->id_project.'","'.$this->id_autobid.'","'.$this->id_lender_wallet_line.'","'.$this->amount.'","'.$this->rate.'","'.$this->ordre.'","'.$this->status.'","'.$this->status_email_bid_ko.'","'.$this->checked.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_bid = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_bid,'id_bid');
		
		return $this->id_bid;
	}
	
	function unsetData()
	{
		$this->id_bid = '';
		$this->id_lender_account = '';
		$this->id_project = '';
		$this->id_autobid = '';
		$this->id_lender_wallet_line = '';
		$this->amount = '';
		$this->rate = '';
		$this->ordre = '';
		$this->status = '';
		$this->status_email_bid_ko = '';
		$this->checked = '';
		$this->added = '';
		$this->updated = '';

	}
}