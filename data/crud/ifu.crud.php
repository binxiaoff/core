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
class ifu_crud
{
	
	public $id_ifu;
	public $id_client;
	public $annee;
	public $nom;
	public $chemin;
	public $statut;
	public $updated;
	public $added;

	
	function ifu($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_ifu = '';
		$this->id_client = '';
		$this->annee = '';
		$this->nom = '';
		$this->chemin = '';
		$this->statut = '';
		$this->updated = '';
		$this->added = '';

	}
	
	function get($id,$field='id_ifu')
	{
		$sql = 'SELECT * FROM  `ifu` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_ifu = $record['id_ifu'];
			$this->id_client = $record['id_client'];
			$this->annee = $record['annee'];
			$this->nom = $record['nom'];
			$this->chemin = $record['chemin'];
			$this->statut = $record['statut'];
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
		$this->id_ifu = $this->bdd->escape_string($this->id_ifu);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->annee = $this->bdd->escape_string($this->annee);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->chemin = $this->bdd->escape_string($this->chemin);
		$this->statut = $this->bdd->escape_string($this->statut);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->added = $this->bdd->escape_string($this->added);

		
		$sql = 'UPDATE `ifu` SET `id_client`="'.$this->id_client.'",`annee`="'.$this->annee.'",`nom`="'.$this->nom.'",`chemin`="'.$this->chemin.'",`statut`="'.$this->statut.'",`updated`=NOW(),`added`="'.$this->added.'" WHERE id_ifu="'.$this->id_ifu.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_ifu,'id_ifu');
	}
	
	function delete($id,$field='id_ifu')
	{
		if($id=='')
			$id = $this->id_ifu;
		$sql = 'DELETE FROM `ifu` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_ifu = $this->bdd->escape_string($this->id_ifu);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->annee = $this->bdd->escape_string($this->annee);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->chemin = $this->bdd->escape_string($this->chemin);
		$this->statut = $this->bdd->escape_string($this->statut);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->added = $this->bdd->escape_string($this->added);

		
		$sql = 'INSERT INTO `ifu`(`id_client`,`annee`,`nom`,`chemin`,`statut`,`updated`,`added`) VALUES("'.$this->id_client.'","'.$this->annee.'","'.$this->nom.'","'.$this->chemin.'","'.$this->statut.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_ifu = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_ifu,'id_ifu');
		
		return $this->id_ifu;
	}
	
	function unsetData()
	{
		$this->id_ifu = '';
		$this->id_client = '';
		$this->annee = '';
		$this->nom = '';
		$this->chemin = '';
		$this->statut = '';
		$this->updated = '';
		$this->added = '';

	}
}