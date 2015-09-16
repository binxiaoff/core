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
class tree_elements_crud
{
	
	public $id;
	public $id_tree;
	public $id_element;
	public $id_langue;
	public $value;
	public $complement;
	public $status;
	public $added;
	public $updated;

	
	function tree_elements($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id = '';
		$this->id_tree = '';
		$this->id_element = '';
		$this->id_langue = '';
		$this->value = '';
		$this->complement = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id')
	{
		$sql = 'SELECT * FROM  `tree_elements` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id = $record['id'];
			$this->id_tree = $record['id_tree'];
			$this->id_element = $record['id_element'];
			$this->id_langue = $record['id_langue'];
			$this->value = $record['value'];
			$this->complement = $record['complement'];
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
		$this->id = $this->bdd->escape_string($this->id);
		$this->id_tree = $this->bdd->escape_string($this->id_tree);
		$this->id_element = $this->bdd->escape_string($this->id_element);
		$this->id_langue = $this->bdd->escape_string($this->id_langue);
		$this->value = $this->bdd->escape_string($this->value);
		$this->complement = $this->bdd->escape_string($this->complement);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `tree_elements` SET `id_tree`="'.$this->id_tree.'",`id_element`="'.$this->id_element.'",`id_langue`="'.$this->id_langue.'",`value`="'.$this->value.'",`complement`="'.$this->complement.'",`status`="'.$this->status.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id="'.$this->id.'"';
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
		$sql = 'DELETE FROM `tree_elements` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id = $this->bdd->escape_string($this->id);
		$this->id_tree = $this->bdd->escape_string($this->id_tree);
		$this->id_element = $this->bdd->escape_string($this->id_element);
		$this->id_langue = $this->bdd->escape_string($this->id_langue);
		$this->value = $this->bdd->escape_string($this->value);
		$this->complement = $this->bdd->escape_string($this->complement);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `tree_elements`(`id_tree`,`id_element`,`id_langue`,`value`,`complement`,`status`,`added`,`updated`) VALUES("'.$this->id_tree.'","'.$this->id_element.'","'.$this->id_langue.'","'.$this->value.'","'.$this->complement.'","'.$this->status.'",NOW(),NOW())';
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
		$this->id_tree = '';
		$this->id_element = '';
		$this->id_langue = '';
		$this->value = '';
		$this->complement = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
}