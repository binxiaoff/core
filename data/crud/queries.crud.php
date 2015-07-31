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
class queries_crud
{
	
	public $id_query;
	public $name;
	public $sql;
	public $paging;
	public $executions;
	public $executed;
	public $cms;
	public $added;
	public $updated;

	
	function queries($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_query = '';
		$this->name = '';
		$this->sql = '';
		$this->paging = '';
		$this->executions = '';
		$this->executed = '';
		$this->cms = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_query')
	{
		$sql = 'SELECT * FROM  `queries` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_query = $record['id_query'];
			$this->name = $record['name'];
			$this->sql = $record['sql'];
			$this->paging = $record['paging'];
			$this->executions = $record['executions'];
			$this->executed = $record['executed'];
			$this->cms = $record['cms'];
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
		$this->id_query = $this->bdd->escape_string($this->id_query);
		$this->name = $this->bdd->escape_string($this->name);
		$this->sql = $this->bdd->escape_string($this->sql);
		$this->paging = $this->bdd->escape_string($this->paging);
		$this->executions = $this->bdd->escape_string($this->executions);
		$this->executed = $this->bdd->escape_string($this->executed);
		$this->cms = $this->bdd->escape_string($this->cms);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `queries` SET `name`="'.$this->name.'",`sql`="'.$this->sql.'",`paging`="'.$this->paging.'",`executions`="'.$this->executions.'",`executed`="'.$this->executed.'",`cms`="'.$this->cms.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_query="'.$this->id_query.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_query,'id_query');
	}
	
	function delete($id,$field='id_query')
	{
		if($id=='')
			$id = $this->id_query;
		$sql = 'DELETE FROM `queries` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_query = $this->bdd->escape_string($this->id_query);
		$this->name = $this->bdd->escape_string($this->name);
		$this->sql = $this->bdd->escape_string($this->sql);
		$this->paging = $this->bdd->escape_string($this->paging);
		$this->executions = $this->bdd->escape_string($this->executions);
		$this->executed = $this->bdd->escape_string($this->executed);
		$this->cms = $this->bdd->escape_string($this->cms);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `queries`(`name`,`sql`,`paging`,`executions`,`executed`,`cms`,`added`,`updated`) VALUES("'.$this->name.'","'.$this->sql.'","'.$this->paging.'","'.$this->executions.'","'.$this->executed.'","'.$this->cms.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_query = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_query,'id_query');
		
		return $this->id_query;
	}
	
	function unsetData()
	{
		$this->id_query = '';
		$this->name = '';
		$this->sql = '';
		$this->paging = '';
		$this->executions = '';
		$this->executed = '';
		$this->cms = '';
		$this->added = '';
		$this->updated = '';

	}
}