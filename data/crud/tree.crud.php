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
class tree_crud
{
	
	public $id_tree;
	public $id_langue;
	public $id_parent;
	public $id_template;
	public $id_user;
	public $arbo;
	public $title;
	public $slug;
	public $img_menu;
	public $video;
	public $menu_title;
	public $meta_title;
	public $meta_description;
	public $meta_keywords;
	public $ordre;
	public $status;
	public $status_menu;
	public $prive;
	public $indexation;
	public $added;
	public $updated;
	public $canceled;

	
	function tree($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_tree = '';
		$this->id_langue = '';
		$this->id_parent = '';
		$this->id_template = '';
		$this->id_user = '';
		$this->arbo = '';
		$this->title = '';
		$this->slug = '';
		$this->img_menu = '';
		$this->video = '';
		$this->menu_title = '';
		$this->meta_title = '';
		$this->meta_description = '';
		$this->meta_keywords = '';
		$this->ordre = '';
		$this->status = '';
		$this->status_menu = '';
		$this->prive = '';
		$this->indexation = '';
		$this->added = '';
		$this->updated = '';
		$this->canceled = '';

	}
	
	function get($list_field_value)
	{
		foreach($list_field_value as $champ => $valeur)
			$list.=' AND `'.$champ.'` = "'.$valeur.'" ';
			
		$sql = 'SELECT * FROM `tree` WHERE 1=1 '.$list.' ';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_tree = $record['id_tree'];
			$this->id_langue = $record['id_langue'];
			$this->id_parent = $record['id_parent'];
			$this->id_template = $record['id_template'];
			$this->id_user = $record['id_user'];
			$this->arbo = $record['arbo'];
			$this->title = $record['title'];
			$this->slug = $record['slug'];
			$this->img_menu = $record['img_menu'];
			$this->video = $record['video'];
			$this->menu_title = $record['menu_title'];
			$this->meta_title = $record['meta_title'];
			$this->meta_description = $record['meta_description'];
			$this->meta_keywords = $record['meta_keywords'];
			$this->ordre = $record['ordre'];
			$this->status = $record['status'];
			$this->status_menu = $record['status_menu'];
			$this->prive = $record['prive'];
			$this->indexation = $record['indexation'];
			$this->added = $record['added'];
			$this->updated = $record['updated'];
			$this->canceled = $record['canceled'];

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
		$this->id_tree = $this->bdd->escape_string($this->id_tree);
		$this->id_langue = $this->bdd->escape_string($this->id_langue);
		$this->id_parent = $this->bdd->escape_string($this->id_parent);
		$this->id_template = $this->bdd->escape_string($this->id_template);
		$this->id_user = $this->bdd->escape_string($this->id_user);
		$this->arbo = $this->bdd->escape_string($this->arbo);
		$this->title = $this->bdd->escape_string($this->title);
		$this->slug = $this->bdd->escape_string($this->slug);
		$this->img_menu = $this->bdd->escape_string($this->img_menu);
		$this->video = $this->bdd->escape_string($this->video);
		$this->menu_title = $this->bdd->escape_string($this->menu_title);
		$this->meta_title = $this->bdd->escape_string($this->meta_title);
		$this->meta_description = $this->bdd->escape_string($this->meta_description);
		$this->meta_keywords = $this->bdd->escape_string($this->meta_keywords);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->status = $this->bdd->escape_string($this->status);
		$this->status_menu = $this->bdd->escape_string($this->status_menu);
		$this->prive = $this->bdd->escape_string($this->prive);
		$this->indexation = $this->bdd->escape_string($this->indexation);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->canceled = $this->bdd->escape_string($this->canceled);

		
		foreach($list_field_value as $champ => $valeur)
			$list.=' AND `'.$champ.'` = "'.$valeur.'" ';
		
		$sql = 'UPDATE `tree` SET `id_parent`="'.$this->id_parent.'",`id_template`="'.$this->id_template.'",`id_user`="'.$this->id_user.'",`arbo`="'.$this->arbo.'",`title`="'.$this->title.'",`slug`="'.$this->slug.'",`img_menu`="'.$this->img_menu.'",`video`="'.$this->video.'",`menu_title`="'.$this->menu_title.'",`meta_title`="'.$this->meta_title.'",`meta_description`="'.$this->meta_description.'",`meta_keywords`="'.$this->meta_keywords.'",`ordre`="'.$this->ordre.'",`status`="'.$this->status.'",`status_menu`="'.$this->status_menu.'",`prive`="'.$this->prive.'",`indexation`="'.$this->indexation.'",`added`="'.$this->added.'",`updated`=NOW(),`canceled`="'.$this->canceled.'" WHERE 1=1 '.$list.' ';
		$this->bdd->query($sql);
		
		$this->bdd->controlSlugMultiLn('tree',$this->slug,$this->id_tree,$list_field_value,$this->id_langue);
		
		$this->get($list_field_value);
	}
	
	function delete($list_field_value)
	{
		foreach($list_field_value as $champ => $valeur)
			$list.=' AND `'.$champ.'` = "'.$valeur.'" ';
		
		$sql = 'DELETE FROM `tree` WHERE 1=1 '.$list.' ';
		$this->bdd->query($sql);
	}
	
	function create($list_field_value)
	{
		$this->id_tree = $this->bdd->escape_string($this->id_tree);
		$this->id_langue = $this->bdd->escape_string($this->id_langue);
		$this->id_parent = $this->bdd->escape_string($this->id_parent);
		$this->id_template = $this->bdd->escape_string($this->id_template);
		$this->id_user = $this->bdd->escape_string($this->id_user);
		$this->arbo = $this->bdd->escape_string($this->arbo);
		$this->title = $this->bdd->escape_string($this->title);
		$this->slug = $this->bdd->escape_string($this->slug);
		$this->img_menu = $this->bdd->escape_string($this->img_menu);
		$this->video = $this->bdd->escape_string($this->video);
		$this->menu_title = $this->bdd->escape_string($this->menu_title);
		$this->meta_title = $this->bdd->escape_string($this->meta_title);
		$this->meta_description = $this->bdd->escape_string($this->meta_description);
		$this->meta_keywords = $this->bdd->escape_string($this->meta_keywords);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->status = $this->bdd->escape_string($this->status);
		$this->status_menu = $this->bdd->escape_string($this->status_menu);
		$this->prive = $this->bdd->escape_string($this->prive);
		$this->indexation = $this->bdd->escape_string($this->indexation);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->canceled = $this->bdd->escape_string($this->canceled);

		
		$sql = 'INSERT INTO `tree`(`id_tree`,`id_langue`,`id_parent`,`id_template`,`id_user`,`arbo`,`title`,`slug`,`img_menu`,`video`,`menu_title`,`meta_title`,`meta_description`,`meta_keywords`,`ordre`,`status`,`status_menu`,`prive`,`indexation`,`added`,`updated`,`canceled`) VALUES("'.$this->id_tree.'","'.$this->id_langue.'","'.$this->id_parent.'","'.$this->id_template.'","'.$this->id_user.'","'.$this->arbo.'","'.$this->title.'","'.$this->slug.'","'.$this->img_menu.'","'.$this->video.'","'.$this->menu_title.'","'.$this->meta_title.'","'.$this->meta_description.'","'.$this->meta_keywords.'","'.$this->ordre.'","'.$this->status.'","'.$this->status_menu.'","'.$this->prive.'","'.$this->indexation.'",NOW(),NOW(),"'.$this->canceled.'")';
		$this->bdd->query($sql);
		
		$this->bdd->controlSlugMultiLn('tree',$this->slug,$this->id_tree,$list_field_value,$this->id_langue);
		
		$this->get($list_field_value);
	}
	
	function unsetData()
	{
		$this->id_tree = '';
		$this->id_langue = '';
		$this->id_parent = '';
		$this->id_template = '';
		$this->id_user = '';
		$this->arbo = '';
		$this->title = '';
		$this->slug = '';
		$this->img_menu = '';
		$this->video = '';
		$this->menu_title = '';
		$this->meta_title = '';
		$this->meta_description = '';
		$this->meta_keywords = '';
		$this->ordre = '';
		$this->status = '';
		$this->status_menu = '';
		$this->prive = '';
		$this->indexation = '';
		$this->added = '';
		$this->updated = '';
		$this->canceled = '';

	}
}