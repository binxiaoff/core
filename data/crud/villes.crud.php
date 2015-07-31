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
class villes_crud
{
	
	public $id_ville;
	public $ville;
	public $insee;
	public $cp;
	public $num_departement;
	public $departement;
	public $added;
	public $updated;

	
	function villes($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_ville = '';
		$this->ville = '';
		$this->insee = '';
		$this->cp = '';
		$this->num_departement = '';
		$this->departement = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_ville')
	{
		$sql = 'SELECT * FROM  `villes` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_ville = $record['id_ville'];
			$this->ville = $record['ville'];
			$this->insee = $record['insee'];
			$this->cp = $record['cp'];
			$this->num_departement = $record['num_departement'];
			$this->departement = $record['departement'];
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
		$this->id_ville = $this->bdd->escape_string($this->id_ville);
		$this->ville = $this->bdd->escape_string($this->ville);
		$this->insee = $this->bdd->escape_string($this->insee);
		$this->cp = $this->bdd->escape_string($this->cp);
		$this->num_departement = $this->bdd->escape_string($this->num_departement);
		$this->departement = $this->bdd->escape_string($this->departement);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `villes` SET `ville`="'.$this->ville.'",`insee`="'.$this->insee.'",`cp`="'.$this->cp.'",`num_departement`="'.$this->num_departement.'",`departement`="'.$this->departement.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_ville="'.$this->id_ville.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_ville,'id_ville');
	}
	
	function delete($id,$field='id_ville')
	{
		if($id=='')
			$id = $this->id_ville;
		$sql = 'DELETE FROM `villes` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_ville = $this->bdd->escape_string($this->id_ville);
		$this->ville = $this->bdd->escape_string($this->ville);
		$this->insee = $this->bdd->escape_string($this->insee);
		$this->cp = $this->bdd->escape_string($this->cp);
		$this->num_departement = $this->bdd->escape_string($this->num_departement);
		$this->departement = $this->bdd->escape_string($this->departement);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `villes`(`ville`,`insee`,`cp`,`num_departement`,`departement`,`added`,`updated`) VALUES("'.$this->ville.'","'.$this->insee.'","'.$this->cp.'","'.$this->num_departement.'","'.$this->departement.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_ville = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_ville,'id_ville');
		
		return $this->id_ville;
	}
	
	function unsetData()
	{
		$this->id_ville = '';
		$this->ville = '';
		$this->insee = '';
		$this->cp = '';
		$this->num_departement = '';
		$this->departement = '';
		$this->added = '';
		$this->updated = '';

	}
}