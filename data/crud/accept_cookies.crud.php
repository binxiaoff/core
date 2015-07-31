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
class accept_cookies_crud
{
	
	public $id_accept_cookies;
	public $ip;
	public $id_client;
	public $added;
	public $updated;

	
	function accept_cookies($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_accept_cookies = '';
		$this->ip = '';
		$this->id_client = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_accept_cookies')
	{
		$sql = 'SELECT * FROM  `accept_cookies` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_accept_cookies = $record['id_accept_cookies'];
			$this->ip = $record['ip'];
			$this->id_client = $record['id_client'];
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
		$this->id_accept_cookies = $this->bdd->escape_string($this->id_accept_cookies);
		$this->ip = $this->bdd->escape_string($this->ip);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `accept_cookies` SET `ip`="'.$this->ip.'",`id_client`="'.$this->id_client.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_accept_cookies="'.$this->id_accept_cookies.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_accept_cookies,'id_accept_cookies');
	}
	
	function delete($id,$field='id_accept_cookies')
	{
		if($id=='')
			$id = $this->id_accept_cookies;
		$sql = 'DELETE FROM `accept_cookies` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_accept_cookies = $this->bdd->escape_string($this->id_accept_cookies);
		$this->ip = $this->bdd->escape_string($this->ip);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `accept_cookies`(`ip`,`id_client`,`added`,`updated`) VALUES("'.$this->ip.'","'.$this->id_client.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_accept_cookies = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_accept_cookies,'id_accept_cookies');
		
		return $this->id_accept_cookies;
	}
	
	function unsetData()
	{
		$this->id_accept_cookies = '';
		$this->ip = '';
		$this->id_client = '';
		$this->added = '';
		$this->updated = '';

	}
}