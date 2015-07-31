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
class projects_pouvoir_crud
{
	
	public $id_pouvoir;
	public $id_project;
	public $name;
	public $id_universign;
	public $url_universign;
	public $url_pdf;
	public $status;
	public $status_remb;
	public $updated;
	public $added;

	
	function projects_pouvoir($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_pouvoir = '';
		$this->id_project = '';
		$this->name = '';
		$this->id_universign = '';
		$this->url_universign = '';
		$this->url_pdf = '';
		$this->status = '';
		$this->status_remb = '';
		$this->updated = '';
		$this->added = '';

	}
	
	function get($id,$field='id_pouvoir')
	{
		$sql = 'SELECT * FROM  `projects_pouvoir` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_pouvoir = $record['id_pouvoir'];
			$this->id_project = $record['id_project'];
			$this->name = $record['name'];
			$this->id_universign = $record['id_universign'];
			$this->url_universign = $record['url_universign'];
			$this->url_pdf = $record['url_pdf'];
			$this->status = $record['status'];
			$this->status_remb = $record['status_remb'];
			$this->updated = $record['updated'];
			$this->added = $record['added'];

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
		$this->id_pouvoir = $this->bdd->escape_string($this->id_pouvoir);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->name = $this->bdd->escape_string($this->name);
		$this->id_universign = $this->bdd->escape_string($this->id_universign);
		$this->url_universign = $this->bdd->escape_string($this->url_universign);
		$this->url_pdf = $this->bdd->escape_string($this->url_pdf);
		$this->status = $this->bdd->escape_string($this->status);
		$this->status_remb = $this->bdd->escape_string($this->status_remb);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->added = $this->bdd->escape_string($this->added);

		
		$sql = 'UPDATE `projects_pouvoir` SET `id_project`="'.$this->id_project.'",`name`="'.$this->name.'",`id_universign`="'.$this->id_universign.'",`url_universign`="'.$this->url_universign.'",`url_pdf`="'.$this->url_pdf.'",`status`="'.$this->status.'",`status_remb`="'.$this->status_remb.'",`updated`=NOW(),`added`="'.$this->added.'" WHERE id_pouvoir="'.$this->id_pouvoir.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_pouvoir,'id_pouvoir');
	}
	
	function delete($id,$field='id_pouvoir')
	{
		if($id=='')
			$id = $this->id_pouvoir;
		$sql = 'DELETE FROM `projects_pouvoir` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_pouvoir = $this->bdd->escape_string($this->id_pouvoir);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->name = $this->bdd->escape_string($this->name);
		$this->id_universign = $this->bdd->escape_string($this->id_universign);
		$this->url_universign = $this->bdd->escape_string($this->url_universign);
		$this->url_pdf = $this->bdd->escape_string($this->url_pdf);
		$this->status = $this->bdd->escape_string($this->status);
		$this->status_remb = $this->bdd->escape_string($this->status_remb);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->added = $this->bdd->escape_string($this->added);

		
		$sql = 'INSERT INTO `projects_pouvoir`(`id_project`,`name`,`id_universign`,`url_universign`,`url_pdf`,`status`,`status_remb`,`updated`,`added`) VALUES("'.$this->id_project.'","'.$this->name.'","'.$this->id_universign.'","'.$this->url_universign.'","'.$this->url_pdf.'","'.$this->status.'","'.$this->status_remb.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_pouvoir = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_pouvoir,'id_pouvoir');
		
		return $this->id_pouvoir;
	}
	
	function unsetData()
	{
		$this->id_pouvoir = '';
		$this->id_project = '';
		$this->name = '';
		$this->id_universign = '';
		$this->url_universign = '';
		$this->url_pdf = '';
		$this->status = '';
		$this->status_remb = '';
		$this->updated = '';
		$this->added = '';

	}
}