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
class favoris_crud
{
	
	public $id_favori;
	public $id_client;
	public $id_project;
	public $added;
	public $updated;

	
	function favoris($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_favori = '';
		$this->id_client = '';
		$this->id_project = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_favori')
	{
		$sql = 'SELECT * FROM  `favoris` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_favori = $record['id_favori'];
			$this->id_client = $record['id_client'];
			$this->id_project = $record['id_project'];
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
		$this->id_favori = $this->bdd->escape_string($this->id_favori);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `favoris` SET `id_client`="'.$this->id_client.'",`id_project`="'.$this->id_project.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_favori="'.$this->id_favori.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_favori,'id_favori');
	}
	
	function delete($id,$field='id_favori')
	{
		if($id=='')
			$id = $this->id_favori;
		$sql = 'DELETE FROM `favoris` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_favori = $this->bdd->escape_string($this->id_favori);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `favoris`(`id_client`,`id_project`,`added`,`updated`) VALUES("'.$this->id_client.'","'.$this->id_project.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_favori = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_favori,'id_favori');
		
		return $this->id_favori;
	}
	
	function unsetData()
	{
		$this->id_favori = '';
		$this->id_client = '';
		$this->id_project = '';
		$this->added = '';
		$this->updated = '';

	}
}