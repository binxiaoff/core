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
class tree_menu_crud
{
	
	public $id;
	public $id_langue;
	public $id_menu;
	public $nom;
	public $value;
	public $complement;
	public $target;
	public $ordre;
	public $status;
	public $added;
	public $updated;

	
	function tree_menu($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id = '';
		$this->id_langue = '';
		$this->id_menu = '';
		$this->nom = '';
		$this->value = '';
		$this->complement = '';
		$this->target = '';
		$this->ordre = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($list_field_value)
	{
		foreach($list_field_value as $champ => $valeur)
			$list.=' AND `'.$champ.'` = "'.$valeur.'" ';
			
		$sql = 'SELECT * FROM `tree_menu` WHERE 1=1 '.$list.' ';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id = $record['id'];
			$this->id_langue = $record['id_langue'];
			$this->id_menu = $record['id_menu'];
			$this->nom = $record['nom'];
			$this->value = $record['value'];
			$this->complement = $record['complement'];
			$this->target = $record['target'];
			$this->ordre = $record['ordre'];
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
	
	function update($list_field_value)
	{
		$this->id = $this->bdd->escape_string($this->id);
		$this->id_langue = $this->bdd->escape_string($this->id_langue);
		$this->id_menu = $this->bdd->escape_string($this->id_menu);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->value = $this->bdd->escape_string($this->value);
		$this->complement = $this->bdd->escape_string($this->complement);
		$this->target = $this->bdd->escape_string($this->target);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		foreach($list_field_value as $champ => $valeur)
			$list.=' AND `'.$champ.'` = "'.$valeur.'" ';
		
		$sql = 'UPDATE `tree_menu` SET `id_menu`="'.$this->id_menu.'",`nom`="'.$this->nom.'",`value`="'.$this->value.'",`complement`="'.$this->complement.'",`target`="'.$this->target.'",`ordre`="'.$this->ordre.'",`status`="'.$this->status.'",`added`="'.$this->added.'",`updated`=NOW() WHERE 1=1 '.$list.' ';
		$this->bdd->query($sql);
		
		
		
		$this->get($list_field_value);
	}
	
	function delete($list_field_value)
	{
		foreach($list_field_value as $champ => $valeur)
			$list.=' AND `'.$champ.'` = "'.$valeur.'" ';
		
		$sql = 'DELETE FROM `tree_menu` WHERE 1=1 '.$list.' ';
		$this->bdd->query($sql);
	}
	
	function create($list_field_value)
	{
		$this->id = $this->bdd->escape_string($this->id);
		$this->id_langue = $this->bdd->escape_string($this->id_langue);
		$this->id_menu = $this->bdd->escape_string($this->id_menu);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->value = $this->bdd->escape_string($this->value);
		$this->complement = $this->bdd->escape_string($this->complement);
		$this->target = $this->bdd->escape_string($this->target);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `tree_menu`(`id`,`id_langue`,`id_menu`,`nom`,`value`,`complement`,`target`,`ordre`,`status`,`added`,`updated`) VALUES("'.$this->id.'","'.$this->id_langue.'","'.$this->id_menu.'","'.$this->nom.'","'.$this->value.'","'.$this->complement.'","'.$this->target.'","'.$this->ordre.'","'.$this->status.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		
		
		$this->get($list_field_value);
	}
	
	function unsetData()
	{
		$this->id = '';
		$this->id_langue = '';
		$this->id_menu = '';
		$this->nom = '';
		$this->value = '';
		$this->complement = '';
		$this->target = '';
		$this->ordre = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
}