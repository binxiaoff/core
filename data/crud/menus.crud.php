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
class menus_crud
{
	
	public $id_menu;
	public $nom;
	public $slug;
	public $status;
	public $added;
	public $updated;

	
	function menus($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_menu = '';
		$this->nom = '';
		$this->slug = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_menu')
	{
		$sql = 'SELECT * FROM  `menus` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_menu = $record['id_menu'];
			$this->nom = $record['nom'];
			$this->slug = $record['slug'];
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
		$this->id_menu = $this->bdd->escape_string($this->id_menu);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->slug = $this->bdd->escape_string($this->slug);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `menus` SET `nom`="'.$this->nom.'",`slug`="'.$this->slug.'",`status`="'.$this->status.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_menu="'.$this->id_menu.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	$this->bdd->controlSlug('menus',$this->slug,'id_menu',$this->id_menu);
		}
		else
		{
	$this->bdd->controlSlugMultiLn('menus',$this->slug,$this->id_menu,$list_field_value,$this->id_langue);	
		}
		
		$this->get($this->id_menu,'id_menu');
	}
	
	function delete($id,$field='id_menu')
	{
		if($id=='')
			$id = $this->id_menu;
		$sql = 'DELETE FROM `menus` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_menu = $this->bdd->escape_string($this->id_menu);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->slug = $this->bdd->escape_string($this->slug);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `menus`(`nom`,`slug`,`status`,`added`,`updated`) VALUES("'.$this->nom.'","'.$this->slug.'","'.$this->status.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_menu = $this->bdd->insert_id();
		
		if($cs=='')
		{
	$this->bdd->controlSlug('menus',$this->slug,'id_menu',$this->id_menu);
		}
		else
		{
	$this->bdd->controlSlugMultiLn('menus',$this->slug,$this->id_menu,$list_field_value,$this->id_langue);	
		}
		
		$this->get($this->id_menu,'id_menu');
		
		return $this->id_menu;
	}
	
	function unsetData()
	{
		$this->id_menu = '';
		$this->nom = '';
		$this->slug = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
}