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
class backpayline_crud
{
	
	public $id_backpayline;
	public $id;
	public $date;
	public $amount;
	public $token;
	public $serialize;
	public $code;
	public $added;
	public $updated;

	
	function backpayline($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_backpayline = '';
		$this->id = '';
		$this->date = '';
		$this->amount = '';
		$this->token = '';
		$this->serialize = '';
		$this->code = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_backpayline')
	{
		$sql = 'SELECT * FROM  `backpayline` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_backpayline = $record['id_backpayline'];
			$this->id = $record['id'];
			$this->date = $record['date'];
			$this->amount = $record['amount'];
			$this->token = $record['token'];
			$this->serialize = $record['serialize'];
			$this->code = $record['code'];
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
		$this->id_backpayline = $this->bdd->escape_string($this->id_backpayline);
		$this->id = $this->bdd->escape_string($this->id);
		$this->date = $this->bdd->escape_string($this->date);
		$this->amount = $this->bdd->escape_string($this->amount);
		$this->token = $this->bdd->escape_string($this->token);
		$this->serialize = $this->bdd->escape_string($this->serialize);
		$this->code = $this->bdd->escape_string($this->code);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `backpayline` SET `id`="'.$this->id.'",`date`="'.$this->date.'",`amount`="'.$this->amount.'",`token`="'.$this->token.'",`serialize`="'.$this->serialize.'",`code`="'.$this->code.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_backpayline="'.$this->id_backpayline.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_backpayline,'id_backpayline');
	}
	
	function delete($id,$field='id_backpayline')
	{
		if($id=='')
			$id = $this->id_backpayline;
		$sql = 'DELETE FROM `backpayline` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_backpayline = $this->bdd->escape_string($this->id_backpayline);
		$this->id = $this->bdd->escape_string($this->id);
		$this->date = $this->bdd->escape_string($this->date);
		$this->amount = $this->bdd->escape_string($this->amount);
		$this->token = $this->bdd->escape_string($this->token);
		$this->serialize = $this->bdd->escape_string($this->serialize);
		$this->code = $this->bdd->escape_string($this->code);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `backpayline`(`id`,`date`,`amount`,`token`,`serialize`,`code`,`added`,`updated`) VALUES("'.$this->id.'","'.$this->date.'","'.$this->amount.'","'.$this->token.'","'.$this->serialize.'","'.$this->code.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_backpayline = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_backpayline,'id_backpayline');
		
		return $this->id_backpayline;
	}
	
	function unsetData()
	{
		$this->id_backpayline = '';
		$this->id = '';
		$this->date = '';
		$this->amount = '';
		$this->token = '';
		$this->serialize = '';
		$this->code = '';
		$this->added = '';
		$this->updated = '';

	}
}