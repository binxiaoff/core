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
class attachment_crud
{
	
	public $id;
	public $id_type;
	public $id_owner;
	public $type_owner;
	public $path;
	public $added;
	public $updated;
	public $archived;

	
	function attachment($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id = '';
		$this->id_type = '';
		$this->id_owner = '';
		$this->type_owner = '';
		$this->path = '';
		$this->added = '';
		$this->updated = '';
		$this->archived = '';

	}
	
	function get($id,$field='id')
	{
		$sql = 'SELECT * FROM  `attachment` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id = $record['id'];
			$this->id_type = $record['id_type'];
			$this->id_owner = $record['id_owner'];
			$this->type_owner = $record['type_owner'];
			$this->path = $record['path'];
			$this->added = $record['added'];
			$this->updated = $record['updated'];
			$this->archived = $record['archived'];

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
		$this->id = $this->bdd->escape_string($this->id);
		$this->id_type = $this->bdd->escape_string($this->id_type);
		$this->id_owner = $this->bdd->escape_string($this->id_owner);
		$this->type_owner = $this->bdd->escape_string($this->type_owner);
		$this->path = $this->bdd->escape_string($this->path);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->archived = $this->bdd->escape_string($this->archived);

		
		$sql = 'UPDATE `attachment` SET `id_type`="'.$this->id_type.'",`id_owner`="'.$this->id_owner.'",`type_owner`="'.$this->type_owner.'",`path`="'.$this->path.'",`added`="'.$this->added.'",`updated`=NOW(),`archived`="'.$this->archived.'" WHERE id="'.$this->id.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id,'id');
	}
	
	function delete($id,$field='id')
	{
		if($id=='')
			$id = $this->id;
		$sql = 'DELETE FROM `attachment` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id = $this->bdd->escape_string($this->id);
		$this->id_type = $this->bdd->escape_string($this->id_type);
		$this->id_owner = $this->bdd->escape_string($this->id_owner);
		$this->type_owner = $this->bdd->escape_string($this->type_owner);
		$this->path = $this->bdd->escape_string($this->path);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->archived = $this->bdd->escape_string($this->archived);

		
		$sql = 'INSERT INTO `attachment`(`id_type`,`id_owner`,`type_owner`,`path`,`added`,`updated`,`archived`) VALUES("'.$this->id_type.'","'.$this->id_owner.'","'.$this->type_owner.'","'.$this->path.'",NOW(),NOW(),"'.$this->archived.'")';
		$this->bdd->query($sql);
		
		$this->id = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id,'id');
		
		return $this->id;
	}
	
	function unsetData()
	{
		$this->id = '';
		$this->id_type = '';
		$this->id_owner = '';
		$this->type_owner = '';
		$this->path = '';
		$this->added = '';
		$this->updated = '';
		$this->archived = '';

	}
}