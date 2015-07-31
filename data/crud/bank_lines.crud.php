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
class bank_lines_crud
{
	
	public $id_bank_line;
	public $id_wallet_line;
	public $id_lender_account;
	public $id_company;
	public $id_term_for_company;
	public $id_project;
	public $type;
	public $status;
	public $amount;
	public $added;
	public $updated;

	
	function bank_lines($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_bank_line = '';
		$this->id_wallet_line = '';
		$this->id_lender_account = '';
		$this->id_company = '';
		$this->id_term_for_company = '';
		$this->id_project = '';
		$this->type = '';
		$this->status = '';
		$this->amount = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_bank_line')
	{
		$sql = 'SELECT * FROM  `bank_lines` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_bank_line = $record['id_bank_line'];
			$this->id_wallet_line = $record['id_wallet_line'];
			$this->id_lender_account = $record['id_lender_account'];
			$this->id_company = $record['id_company'];
			$this->id_term_for_company = $record['id_term_for_company'];
			$this->id_project = $record['id_project'];
			$this->type = $record['type'];
			$this->status = $record['status'];
			$this->amount = $record['amount'];
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
		$this->id_bank_line = $this->bdd->escape_string($this->id_bank_line);
		$this->id_wallet_line = $this->bdd->escape_string($this->id_wallet_line);
		$this->id_lender_account = $this->bdd->escape_string($this->id_lender_account);
		$this->id_company = $this->bdd->escape_string($this->id_company);
		$this->id_term_for_company = $this->bdd->escape_string($this->id_term_for_company);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->type = $this->bdd->escape_string($this->type);
		$this->status = $this->bdd->escape_string($this->status);
		$this->amount = $this->bdd->escape_string($this->amount);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `bank_lines` SET `id_wallet_line`="'.$this->id_wallet_line.'",`id_lender_account`="'.$this->id_lender_account.'",`id_company`="'.$this->id_company.'",`id_term_for_company`="'.$this->id_term_for_company.'",`id_project`="'.$this->id_project.'",`type`="'.$this->type.'",`status`="'.$this->status.'",`amount`="'.$this->amount.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_bank_line="'.$this->id_bank_line.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_bank_line,'id_bank_line');
	}
	
	function delete($id,$field='id_bank_line')
	{
		if($id=='')
			$id = $this->id_bank_line;
		$sql = 'DELETE FROM `bank_lines` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_bank_line = $this->bdd->escape_string($this->id_bank_line);
		$this->id_wallet_line = $this->bdd->escape_string($this->id_wallet_line);
		$this->id_lender_account = $this->bdd->escape_string($this->id_lender_account);
		$this->id_company = $this->bdd->escape_string($this->id_company);
		$this->id_term_for_company = $this->bdd->escape_string($this->id_term_for_company);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->type = $this->bdd->escape_string($this->type);
		$this->status = $this->bdd->escape_string($this->status);
		$this->amount = $this->bdd->escape_string($this->amount);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `bank_lines`(`id_wallet_line`,`id_lender_account`,`id_company`,`id_term_for_company`,`id_project`,`type`,`status`,`amount`,`added`,`updated`) VALUES("'.$this->id_wallet_line.'","'.$this->id_lender_account.'","'.$this->id_company.'","'.$this->id_term_for_company.'","'.$this->id_project.'","'.$this->type.'","'.$this->status.'","'.$this->amount.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_bank_line = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_bank_line,'id_bank_line');
		
		return $this->id_bank_line;
	}
	
	function unsetData()
	{
		$this->id_bank_line = '';
		$this->id_wallet_line = '';
		$this->id_lender_account = '';
		$this->id_company = '';
		$this->id_term_for_company = '';
		$this->id_project = '';
		$this->type = '';
		$this->status = '';
		$this->amount = '';
		$this->added = '';
		$this->updated = '';

	}
}