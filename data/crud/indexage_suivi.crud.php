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
class indexage_suivi_crud
{
	
	public $id_indexage_suivi;
	public $id_client;
	public $date_derniere_indexation;
	public $deja_indexe;
	public $nb_entrees;
	public $updated;
	public $added;

	
	function indexage_suivi($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_indexage_suivi = '';
		$this->id_client = '';
		$this->date_derniere_indexation = '';
		$this->deja_indexe = '';
		$this->nb_entrees = '';
		$this->updated = '';
		$this->added = '';

	}
	
	function get($id,$field='id_indexage_suivi')
	{
		$sql = 'SELECT * FROM  `indexage_suivi` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_indexage_suivi = $record['id_indexage_suivi'];
			$this->id_client = $record['id_client'];
			$this->date_derniere_indexation = $record['date_derniere_indexation'];
			$this->deja_indexe = $record['deja_indexe'];
			$this->nb_entrees = $record['nb_entrees'];
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
		$this->id_indexage_suivi = $this->bdd->escape_string($this->id_indexage_suivi);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->date_derniere_indexation = $this->bdd->escape_string($this->date_derniere_indexation);
		$this->deja_indexe = $this->bdd->escape_string($this->deja_indexe);
		$this->nb_entrees = $this->bdd->escape_string($this->nb_entrees);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->added = $this->bdd->escape_string($this->added);

		
		$sql = 'UPDATE `indexage_suivi` SET `id_client`="'.$this->id_client.'",`date_derniere_indexation`="'.$this->date_derniere_indexation.'",`deja_indexe`="'.$this->deja_indexe.'",`nb_entrees`="'.$this->nb_entrees.'",`updated`=NOW(),`added`="'.$this->added.'" WHERE id_indexage_suivi="'.$this->id_indexage_suivi.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_indexage_suivi,'id_indexage_suivi');
	}
	
	function delete($id,$field='id_indexage_suivi')
	{
		if($id=='')
			$id = $this->id_indexage_suivi;
		$sql = 'DELETE FROM `indexage_suivi` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_indexage_suivi = $this->bdd->escape_string($this->id_indexage_suivi);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->date_derniere_indexation = $this->bdd->escape_string($this->date_derniere_indexation);
		$this->deja_indexe = $this->bdd->escape_string($this->deja_indexe);
		$this->nb_entrees = $this->bdd->escape_string($this->nb_entrees);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->added = $this->bdd->escape_string($this->added);

		
		$sql = 'INSERT INTO `indexage_suivi`(`id_client`,`date_derniere_indexation`,`deja_indexe`,`nb_entrees`,`updated`,`added`) VALUES("'.$this->id_client.'","'.$this->date_derniere_indexation.'","'.$this->deja_indexe.'","'.$this->nb_entrees.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_indexage_suivi = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_indexage_suivi,'id_indexage_suivi');
		
		return $this->id_indexage_suivi;
	}
	
	function unsetData()
	{
		$this->id_indexage_suivi = '';
		$this->id_client = '';
		$this->date_derniere_indexation = '';
		$this->deja_indexe = '';
		$this->nb_entrees = '';
		$this->updated = '';
		$this->added = '';

	}
}