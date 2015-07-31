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
class routages_crud
{
	
	public $id_routage;
	public $id_langue;
	public $ctrl_url;
	public $fct_url;
	public $ctrl_projet;
	public $fct_projet;
	public $statut;
	public $added;
	public $updated;

	
	function routages($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_routage = '';
		$this->id_langue = '';
		$this->ctrl_url = '';
		$this->fct_url = '';
		$this->ctrl_projet = '';
		$this->fct_projet = '';
		$this->statut = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_routage')
	{
		$sql = 'SELECT * FROM  `routages` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_routage = $record['id_routage'];
			$this->id_langue = $record['id_langue'];
			$this->ctrl_url = $record['ctrl_url'];
			$this->fct_url = $record['fct_url'];
			$this->ctrl_projet = $record['ctrl_projet'];
			$this->fct_projet = $record['fct_projet'];
			$this->statut = $record['statut'];
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
		$this->id_routage = $this->bdd->escape_string($this->id_routage);
		$this->id_langue = $this->bdd->escape_string($this->id_langue);
		$this->ctrl_url = $this->bdd->escape_string($this->ctrl_url);
		$this->fct_url = $this->bdd->escape_string($this->fct_url);
		$this->ctrl_projet = $this->bdd->escape_string($this->ctrl_projet);
		$this->fct_projet = $this->bdd->escape_string($this->fct_projet);
		$this->statut = $this->bdd->escape_string($this->statut);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `routages` SET `id_langue`="'.$this->id_langue.'",`ctrl_url`="'.$this->ctrl_url.'",`fct_url`="'.$this->fct_url.'",`ctrl_projet`="'.$this->ctrl_projet.'",`fct_projet`="'.$this->fct_projet.'",`statut`="'.$this->statut.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_routage="'.$this->id_routage.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_routage,'id_routage');
	}
	
	function delete($id,$field='id_routage')
	{
		if($id=='')
			$id = $this->id_routage;
		$sql = 'DELETE FROM `routages` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_routage = $this->bdd->escape_string($this->id_routage);
		$this->id_langue = $this->bdd->escape_string($this->id_langue);
		$this->ctrl_url = $this->bdd->escape_string($this->ctrl_url);
		$this->fct_url = $this->bdd->escape_string($this->fct_url);
		$this->ctrl_projet = $this->bdd->escape_string($this->ctrl_projet);
		$this->fct_projet = $this->bdd->escape_string($this->fct_projet);
		$this->statut = $this->bdd->escape_string($this->statut);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `routages`(`id_langue`,`ctrl_url`,`fct_url`,`ctrl_projet`,`fct_projet`,`statut`,`added`,`updated`) VALUES("'.$this->id_langue.'","'.$this->ctrl_url.'","'.$this->fct_url.'","'.$this->ctrl_projet.'","'.$this->fct_projet.'","'.$this->statut.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_routage = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_routage,'id_routage');
		
		return $this->id_routage;
	}
	
	function unsetData()
	{
		$this->id_routage = '';
		$this->id_langue = '';
		$this->ctrl_url = '';
		$this->fct_url = '';
		$this->ctrl_projet = '';
		$this->fct_projet = '';
		$this->statut = '';
		$this->added = '';
		$this->updated = '';

	}
}