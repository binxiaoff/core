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
class wallets_lines_crud
{
	
	public $id_wallet_line;
	public $id_lender;
	public $id_company;
	public $type_financial_operation;
	public $id_transaction;
	public $id_bid_remb;
	public $id_term_of_loan;
	public $id_loan;
	public $id_project;
	public $id_term_for_company;
	public $type;
	public $amount;
	public $display;
	public $status;
	public $added;
	public $updated;

	
	function wallets_lines($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_wallet_line = '';
		$this->id_lender = '';
		$this->id_company = '';
		$this->type_financial_operation = '';
		$this->id_transaction = '';
		$this->id_bid_remb = '';
		$this->id_term_of_loan = '';
		$this->id_loan = '';
		$this->id_project = '';
		$this->id_term_for_company = '';
		$this->type = '';
		$this->amount = '';
		$this->display = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_wallet_line')
	{
		$sql = 'SELECT * FROM  `wallets_lines` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_wallet_line = $record['id_wallet_line'];
			$this->id_lender = $record['id_lender'];
			$this->id_company = $record['id_company'];
			$this->type_financial_operation = $record['type_financial_operation'];
			$this->id_transaction = $record['id_transaction'];
			$this->id_bid_remb = $record['id_bid_remb'];
			$this->id_term_of_loan = $record['id_term_of_loan'];
			$this->id_loan = $record['id_loan'];
			$this->id_project = $record['id_project'];
			$this->id_term_for_company = $record['id_term_for_company'];
			$this->type = $record['type'];
			$this->amount = $record['amount'];
			$this->display = $record['display'];
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
		$this->id_wallet_line = $this->bdd->escape_string($this->id_wallet_line);
		$this->id_lender = $this->bdd->escape_string($this->id_lender);
		$this->id_company = $this->bdd->escape_string($this->id_company);
		$this->type_financial_operation = $this->bdd->escape_string($this->type_financial_operation);
		$this->id_transaction = $this->bdd->escape_string($this->id_transaction);
		$this->id_bid_remb = $this->bdd->escape_string($this->id_bid_remb);
		$this->id_term_of_loan = $this->bdd->escape_string($this->id_term_of_loan);
		$this->id_loan = $this->bdd->escape_string($this->id_loan);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->id_term_for_company = $this->bdd->escape_string($this->id_term_for_company);
		$this->type = $this->bdd->escape_string($this->type);
		$this->amount = $this->bdd->escape_string($this->amount);
		$this->display = $this->bdd->escape_string($this->display);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `wallets_lines` SET `id_lender`="'.$this->id_lender.'",`id_company`="'.$this->id_company.'",`type_financial_operation`="'.$this->type_financial_operation.'",`id_transaction`="'.$this->id_transaction.'",`id_bid_remb`="'.$this->id_bid_remb.'",`id_term_of_loan`="'.$this->id_term_of_loan.'",`id_loan`="'.$this->id_loan.'",`id_project`="'.$this->id_project.'",`id_term_for_company`="'.$this->id_term_for_company.'",`type`="'.$this->type.'",`amount`="'.$this->amount.'",`display`="'.$this->display.'",`status`="'.$this->status.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_wallet_line="'.$this->id_wallet_line.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_wallet_line,'id_wallet_line');
	}
	
	function delete($id,$field='id_wallet_line')
	{
		if($id=='')
			$id = $this->id_wallet_line;
		$sql = 'DELETE FROM `wallets_lines` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_wallet_line = $this->bdd->escape_string($this->id_wallet_line);
		$this->id_lender = $this->bdd->escape_string($this->id_lender);
		$this->id_company = $this->bdd->escape_string($this->id_company);
		$this->type_financial_operation = $this->bdd->escape_string($this->type_financial_operation);
		$this->id_transaction = $this->bdd->escape_string($this->id_transaction);
		$this->id_bid_remb = $this->bdd->escape_string($this->id_bid_remb);
		$this->id_term_of_loan = $this->bdd->escape_string($this->id_term_of_loan);
		$this->id_loan = $this->bdd->escape_string($this->id_loan);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->id_term_for_company = $this->bdd->escape_string($this->id_term_for_company);
		$this->type = $this->bdd->escape_string($this->type);
		$this->amount = $this->bdd->escape_string($this->amount);
		$this->display = $this->bdd->escape_string($this->display);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `wallets_lines`(`id_lender`,`id_company`,`type_financial_operation`,`id_transaction`,`id_bid_remb`,`id_term_of_loan`,`id_loan`,`id_project`,`id_term_for_company`,`type`,`amount`,`display`,`status`,`added`,`updated`) VALUES("'.$this->id_lender.'","'.$this->id_company.'","'.$this->type_financial_operation.'","'.$this->id_transaction.'","'.$this->id_bid_remb.'","'.$this->id_term_of_loan.'","'.$this->id_loan.'","'.$this->id_project.'","'.$this->id_term_for_company.'","'.$this->type.'","'.$this->amount.'","'.$this->display.'","'.$this->status.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_wallet_line = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_wallet_line,'id_wallet_line');
		
		return $this->id_wallet_line;
	}
	
	function unsetData()
	{
		$this->id_wallet_line = '';
		$this->id_lender = '';
		$this->id_company = '';
		$this->type_financial_operation = '';
		$this->id_transaction = '';
		$this->id_bid_remb = '';
		$this->id_term_of_loan = '';
		$this->id_loan = '';
		$this->id_project = '';
		$this->id_term_for_company = '';
		$this->type = '';
		$this->amount = '';
		$this->display = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
}