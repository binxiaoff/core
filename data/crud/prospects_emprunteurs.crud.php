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
class prospects_emprunteurs_crud
{
	
	public $id_prospect;
	public $nom;
	public $prenom;
	public $email;
	public $source;
	public $id_compagny;
	public $id_project;
	public $id_client;
	public $updated;
	public $added;

	
	function prospects_emprunteurs($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_prospect = '';
		$this->nom = '';
		$this->prenom = '';
		$this->email = '';
		$this->source = '';
		$this->id_compagny = '';
		$this->id_project = '';
		$this->id_client = '';
		$this->updated = '';
		$this->added = '';

	}
	
	function get($id,$field='id_prospect')
	{
		$sql = 'SELECT * FROM  `prospects_emprunteurs` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_prospect = $record['id_prospect'];
			$this->nom = $record['nom'];
			$this->prenom = $record['prenom'];
			$this->email = $record['email'];
			$this->source = $record['source'];
			$this->id_compagny = $record['id_compagny'];
			$this->id_project = $record['id_project'];
			$this->id_client = $record['id_client'];
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
		$this->id_prospect = $this->bdd->escape_string($this->id_prospect);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->prenom = $this->bdd->escape_string($this->prenom);
		$this->email = $this->bdd->escape_string($this->email);
		$this->source = $this->bdd->escape_string($this->source);
		$this->id_compagny = $this->bdd->escape_string($this->id_compagny);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->added = $this->bdd->escape_string($this->added);

		
		$sql = 'UPDATE `prospects_emprunteurs` SET `nom`="'.$this->nom.'",`prenom`="'.$this->prenom.'",`email`="'.$this->email.'",`source`="'.$this->source.'",`id_compagny`="'.$this->id_compagny.'",`id_project`="'.$this->id_project.'",`id_client`="'.$this->id_client.'",`updated`=NOW(),`added`="'.$this->added.'" WHERE id_prospect="'.$this->id_prospect.'"';
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
		$sql = 'DELETE FROM `prospects_emprunteurs` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_prospect = $this->bdd->escape_string($this->id_prospect);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->prenom = $this->bdd->escape_string($this->prenom);
		$this->email = $this->bdd->escape_string($this->email);
		$this->source = $this->bdd->escape_string($this->source);
		$this->id_compagny = $this->bdd->escape_string($this->id_compagny);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->added = $this->bdd->escape_string($this->added);

		
		$sql = 'INSERT INTO `prospects_emprunteurs`(`nom`,`prenom`,`email`,`source`,`id_compagny`,`id_project`,`id_client`,`updated`,`added`) VALUES("'.$this->nom.'","'.$this->prenom.'","'.$this->email.'","'.$this->source.'","'.$this->id_compagny.'","'.$this->id_project.'","'.$this->id_client.'",NOW(),NOW())';
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
		$this->source = '';
		$this->id_compagny = '';
		$this->id_project = '';
		$this->id_client = '';
		$this->updated = '';
		$this->added = '';

	}
}