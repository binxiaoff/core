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
class convert_api_compteur_crud
{
	
	public $id_convert_api_compteur;
	public $compteur;
	public $added;
	public $updated;

	
	function convert_api_compteur($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_convert_api_compteur = '';
		$this->compteur = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_convert_api_compteur')
	{
		$sql = 'SELECT * FROM  `convert_api_compteur` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_convert_api_compteur = $record['id_convert_api_compteur'];
			$this->compteur = $record['compteur'];
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
		$this->id_convert_api_compteur = $this->bdd->escape_string($this->id_convert_api_compteur);
		$this->compteur = $this->bdd->escape_string($this->compteur);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `convert_api_compteur` SET `compteur`="'.$this->compteur.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_convert_api_compteur="'.$this->id_convert_api_compteur.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_convert_api_compteur,'id_convert_api_compteur');
	}
	
	function delete($id,$field='id_convert_api_compteur')
	{
		if($id=='')
			$id = $this->id_convert_api_compteur;
		$sql = 'DELETE FROM `convert_api_compteur` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_convert_api_compteur = $this->bdd->escape_string($this->id_convert_api_compteur);
		$this->compteur = $this->bdd->escape_string($this->compteur);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `convert_api_compteur`(`compteur`,`added`,`updated`) VALUES("'.$this->compteur.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_convert_api_compteur = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_convert_api_compteur,'id_convert_api_compteur');
		
		return $this->id_convert_api_compteur;
	}
	
	function unsetData()
	{
		$this->id_convert_api_compteur = '';
		$this->compteur = '';
		$this->added = '';
		$this->updated = '';

	}
}