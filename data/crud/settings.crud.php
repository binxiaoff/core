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
class settings_crud
{
	
	public $id_setting;
	public $type;
	public $id_template;
	public $value;
	public $status;
	public $cms;
	public $added;
	public $updated;

	
	function settings($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_setting = '';
		$this->type = '';
		$this->id_template = '';
		$this->value = '';
		$this->status = '';
		$this->cms = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_setting')
	{
		$sql = 'SELECT * FROM  `settings` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_setting = $record['id_setting'];
			$this->type = $record['type'];
			$this->id_template = $record['id_template'];
			$this->value = $record['value'];
			$this->status = $record['status'];
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
		$this->id_setting = $this->bdd->escape_string($this->id_setting);
		$this->type = $this->bdd->escape_string($this->type);
		$this->id_template = $this->bdd->escape_string($this->id_template);
		$this->value = $this->bdd->escape_string($this->value);
		$this->status = $this->bdd->escape_string($this->status);
		$this->cms = $this->bdd->escape_string($this->cms);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `settings` SET `type`="'.$this->type.'",`id_template`="'.$this->id_template.'",`value`="'.$this->value.'",`status`="'.$this->status.'",`cms`="'.$this->cms.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_setting="'.$this->id_setting.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_setting,'id_setting');
	}
	
	function delete($id,$field='id_setting')
	{
		if($id=='')
			$id = $this->id_setting;
		$sql = 'DELETE FROM `settings` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_setting = $this->bdd->escape_string($this->id_setting);
		$this->type = $this->bdd->escape_string($this->type);
		$this->id_template = $this->bdd->escape_string($this->id_template);
		$this->value = $this->bdd->escape_string($this->value);
		$this->status = $this->bdd->escape_string($this->status);
		$this->cms = $this->bdd->escape_string($this->cms);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `settings`(`type`,`id_template`,`value`,`status`,`cms`,`added`,`updated`) VALUES("'.$this->type.'","'.$this->id_template.'","'.$this->value.'","'.$this->status.'","'.$this->cms.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_setting = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_setting,'id_setting');
		
		return $this->id_setting;
	}
	
	function unsetData()
	{
		$this->id_setting = '';
		$this->type = '';
		$this->id_template = '';
		$this->value = '';
		$this->status = '';
		$this->cms = '';
		$this->added = '';
		$this->updated = '';

	}
}