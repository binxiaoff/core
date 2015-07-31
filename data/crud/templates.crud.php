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
class templates_crud
{
	
	public $id_template;
	public $name;
	public $slug;
	public $type;
	public $status;
	public $affichage;
	public $added;
	public $updated;

	
	function templates($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_template = '';
		$this->name = '';
		$this->slug = '';
		$this->type = '';
		$this->status = '';
		$this->affichage = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_template')
	{
		$sql = 'SELECT * FROM  `templates` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_template = $record['id_template'];
			$this->name = $record['name'];
			$this->slug = $record['slug'];
			$this->type = $record['type'];
			$this->status = $record['status'];
			$this->affichage = $record['affichage'];
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
		$this->id_template = $this->bdd->escape_string($this->id_template);
		$this->name = $this->bdd->escape_string($this->name);
		$this->slug = $this->bdd->escape_string($this->slug);
		$this->type = $this->bdd->escape_string($this->type);
		$this->status = $this->bdd->escape_string($this->status);
		$this->affichage = $this->bdd->escape_string($this->affichage);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `templates` SET `name`="'.$this->name.'",`slug`="'.$this->slug.'",`type`="'.$this->type.'",`status`="'.$this->status.'",`affichage`="'.$this->affichage.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_template="'.$this->id_template.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	$this->bdd->controlSlug('templates',$this->slug,'id_template',$this->id_template);
		}
		else
		{
	$this->bdd->controlSlugMultiLn('templates',$this->slug,$this->id_template,$list_field_value,$this->id_langue);	
		}
		
		$this->get($this->id_template,'id_template');
	}
	
	function delete($id,$field='id_template')
	{
		if($id=='')
			$id = $this->id_template;
		$sql = 'DELETE FROM `templates` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_template = $this->bdd->escape_string($this->id_template);
		$this->name = $this->bdd->escape_string($this->name);
		$this->slug = $this->bdd->escape_string($this->slug);
		$this->type = $this->bdd->escape_string($this->type);
		$this->status = $this->bdd->escape_string($this->status);
		$this->affichage = $this->bdd->escape_string($this->affichage);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `templates`(`name`,`slug`,`type`,`status`,`affichage`,`added`,`updated`) VALUES("'.$this->name.'","'.$this->slug.'","'.$this->type.'","'.$this->status.'","'.$this->affichage.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_template = $this->bdd->insert_id();
		
		if($cs=='')
		{
	$this->bdd->controlSlug('templates',$this->slug,'id_template',$this->id_template);
		}
		else
		{
	$this->bdd->controlSlugMultiLn('templates',$this->slug,$this->id_template,$list_field_value,$this->id_langue);	
		}
		
		$this->get($this->id_template,'id_template');
		
		return $this->id_template;
	}
	
	function unsetData()
	{
		$this->id_template = '';
		$this->name = '';
		$this->slug = '';
		$this->type = '';
		$this->status = '';
		$this->affichage = '';
		$this->added = '';
		$this->updated = '';

	}
}