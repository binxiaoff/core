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
class elements_crud
{
	
	public $id_element;
	public $id_template;
	public $id_bloc;
	public $name;
	public $slug;
	public $ordre;
	public $type_element;
	public $status;
	public $added;
	public $updated;

	
	function elements($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_element = '';
		$this->id_template = '';
		$this->id_bloc = '';
		$this->name = '';
		$this->slug = '';
		$this->ordre = '';
		$this->type_element = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_element')
	{
		$sql = 'SELECT * FROM  `elements` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_element = $record['id_element'];
			$this->id_template = $record['id_template'];
			$this->id_bloc = $record['id_bloc'];
			$this->name = $record['name'];
			$this->slug = $record['slug'];
			$this->ordre = $record['ordre'];
			$this->type_element = $record['type_element'];
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
		$this->id_element = $this->bdd->escape_string($this->id_element);
		$this->id_template = $this->bdd->escape_string($this->id_template);
		$this->id_bloc = $this->bdd->escape_string($this->id_bloc);
		$this->name = $this->bdd->escape_string($this->name);
		$this->slug = $this->bdd->escape_string($this->slug);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->type_element = $this->bdd->escape_string($this->type_element);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `elements` SET `id_template`="'.$this->id_template.'",`id_bloc`="'.$this->id_bloc.'",`name`="'.$this->name.'",`slug`="'.$this->slug.'",`ordre`="'.$this->ordre.'",`type_element`="'.$this->type_element.'",`status`="'.$this->status.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_element="'.$this->id_element.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	$this->bdd->controlSlug('elements',$this->slug,'id_element',$this->id_element);
		}
		else
		{
	$this->bdd->controlSlugMultiLn('elements',$this->slug,$this->id_element,$list_field_value,$this->id_langue);	
		}
		
		$this->get($this->id_element,'id_element');
	}
	
	function delete($id,$field='id_element')
	{
		if($id=='')
			$id = $this->id_element;
		$sql = 'DELETE FROM `elements` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_element = $this->bdd->escape_string($this->id_element);
		$this->id_template = $this->bdd->escape_string($this->id_template);
		$this->id_bloc = $this->bdd->escape_string($this->id_bloc);
		$this->name = $this->bdd->escape_string($this->name);
		$this->slug = $this->bdd->escape_string($this->slug);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->type_element = $this->bdd->escape_string($this->type_element);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `elements`(`id_template`,`id_bloc`,`name`,`slug`,`ordre`,`type_element`,`status`,`added`,`updated`) VALUES("'.$this->id_template.'","'.$this->id_bloc.'","'.$this->name.'","'.$this->slug.'","'.$this->ordre.'","'.$this->type_element.'","'.$this->status.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_element = $this->bdd->insert_id();
		
		if($cs=='')
		{
	$this->bdd->controlSlug('elements',$this->slug,'id_element',$this->id_element);
		}
		else
		{
	$this->bdd->controlSlugMultiLn('elements',$this->slug,$this->id_element,$list_field_value,$this->id_langue);	
		}
		
		$this->get($this->id_element,'id_element');
		
		return $this->id_element;
	}
	
	function unsetData()
	{
		$this->id_element = '';
		$this->id_template = '';
		$this->id_bloc = '';
		$this->name = '';
		$this->slug = '';
		$this->ordre = '';
		$this->type_element = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
}