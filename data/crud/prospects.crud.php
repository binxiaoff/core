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
class prospects_crud
{
	
	public $id_prospect;
	public $nom;
	public $prenom;
	public $email;
	public $id_langue;
	public $source;
	public $source2;
	public $source3;
	public $slug_origine;
	public $added;
	public $updated;

	
	function prospects($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_prospect = '';
		$this->nom = '';
		$this->prenom = '';
		$this->email = '';
		$this->id_langue = '';
		$this->source = '';
		$this->source2 = '';
		$this->source3 = '';
		$this->slug_origine = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_prospect')
	{
		$sql = 'SELECT * FROM  `prospects` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_prospect = $record['id_prospect'];
			$this->nom = $record['nom'];
			$this->prenom = $record['prenom'];
			$this->email = $record['email'];
			$this->id_langue = $record['id_langue'];
			$this->source = $record['source'];
			$this->source2 = $record['source2'];
			$this->source3 = $record['source3'];
			$this->slug_origine = $record['slug_origine'];
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
		$this->id_prospect = $this->bdd->escape_string($this->id_prospect);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->prenom = $this->bdd->escape_string($this->prenom);
		$this->email = $this->bdd->escape_string($this->email);
		$this->id_langue = $this->bdd->escape_string($this->id_langue);
		$this->source = $this->bdd->escape_string($this->source);
		$this->source2 = $this->bdd->escape_string($this->source2);
		$this->source3 = $this->bdd->escape_string($this->source3);
		$this->slug_origine = $this->bdd->escape_string($this->slug_origine);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `prospects` SET `nom`="'.$this->nom.'",`prenom`="'.$this->prenom.'",`email`="'.$this->email.'",`id_langue`="'.$this->id_langue.'",`source`="'.$this->source.'",`source2`="'.$this->source2.'",`source3`="'.$this->source3.'",`slug_origine`="'.$this->slug_origine.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_prospect="'.$this->id_prospect.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_prospect,'id_prospect');
	}
	
	function delete($id,$field='id_prospect')
	{
		if($id=='')
			$id = $this->id_prospect;
		$sql = 'DELETE FROM `prospects` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_prospect = $this->bdd->escape_string($this->id_prospect);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->prenom = $this->bdd->escape_string($this->prenom);
		$this->email = $this->bdd->escape_string($this->email);
		$this->id_langue = $this->bdd->escape_string($this->id_langue);
		$this->source = $this->bdd->escape_string($this->source);
		$this->source2 = $this->bdd->escape_string($this->source2);
		$this->source3 = $this->bdd->escape_string($this->source3);
		$this->slug_origine = $this->bdd->escape_string($this->slug_origine);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `prospects`(`nom`,`prenom`,`email`,`id_langue`,`source`,`source2`,`source3`,`slug_origine`,`added`,`updated`) VALUES("'.$this->nom.'","'.$this->prenom.'","'.$this->email.'","'.$this->id_langue.'","'.$this->source.'","'.$this->source2.'","'.$this->source3.'","'.$this->slug_origine.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_prospect = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_prospect,'id_prospect');
		
		return $this->id_prospect;
	}
	
	function unsetData()
	{
		$this->id_prospect = '';
		$this->nom = '';
		$this->prenom = '';
		$this->email = '';
		$this->id_langue = '';
		$this->source = '';
		$this->source2 = '';
		$this->source3 = '';
		$this->slug_origine = '';
		$this->added = '';
		$this->updated = '';

	}
}