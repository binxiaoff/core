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
class nmp_crud
{
	
	public $id_nmp;
	public $serialize_content;
	public $date;
	public $mailto;
	public $reponse;
	public $erreur;
	public $status;
	public $date_sent;
	public $added;
	public $updated;

	
	function nmp($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_nmp = '';
		$this->serialize_content = '';
		$this->date = '';
		$this->mailto = '';
		$this->reponse = '';
		$this->erreur = '';
		$this->status = '';
		$this->date_sent = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_nmp')
	{
		$sql = 'SELECT * FROM  `nmp` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_nmp = $record['id_nmp'];
			$this->serialize_content = $record['serialize_content'];
			$this->date = $record['date'];
			$this->mailto = $record['mailto'];
			$this->reponse = $record['reponse'];
			$this->erreur = $record['erreur'];
			$this->status = $record['status'];
			$this->date_sent = $record['date_sent'];
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
		$this->id_nmp = $this->bdd->escape_string($this->id_nmp);
		$this->serialize_content = $this->bdd->escape_string($this->serialize_content);
		$this->date = $this->bdd->escape_string($this->date);
		$this->mailto = $this->bdd->escape_string($this->mailto);
		$this->reponse = $this->bdd->escape_string($this->reponse);
		$this->erreur = $this->bdd->escape_string($this->erreur);
		$this->status = $this->bdd->escape_string($this->status);
		$this->date_sent = $this->bdd->escape_string($this->date_sent);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `nmp` SET `serialize_content`="'.$this->serialize_content.'",`date`="'.$this->date.'",`mailto`="'.$this->mailto.'",`reponse`="'.$this->reponse.'",`erreur`="'.$this->erreur.'",`status`="'.$this->status.'",`date_sent`="'.$this->date_sent.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_nmp="'.$this->id_nmp.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_nmp,'id_nmp');
	}
	
	function delete($id,$field='id_nmp')
	{
		if($id=='')
			$id = $this->id_nmp;
		$sql = 'DELETE FROM `nmp` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_nmp = $this->bdd->escape_string($this->id_nmp);
		$this->serialize_content = $this->bdd->escape_string($this->serialize_content);
		$this->date = $this->bdd->escape_string($this->date);
		$this->mailto = $this->bdd->escape_string($this->mailto);
		$this->reponse = $this->bdd->escape_string($this->reponse);
		$this->erreur = $this->bdd->escape_string($this->erreur);
		$this->status = $this->bdd->escape_string($this->status);
		$this->date_sent = $this->bdd->escape_string($this->date_sent);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `nmp`(`serialize_content`,`date`,`mailto`,`reponse`,`erreur`,`status`,`date_sent`,`added`,`updated`) VALUES("'.$this->serialize_content.'","'.$this->date.'","'.$this->mailto.'","'.$this->reponse.'","'.$this->erreur.'","'.$this->status.'","'.$this->date_sent.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_nmp = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_nmp,'id_nmp');
		
		return $this->id_nmp;
	}
	
	function unsetData()
	{
		$this->id_nmp = '';
		$this->serialize_content = '';
		$this->date = '';
		$this->mailto = '';
		$this->reponse = '';
		$this->erreur = '';
		$this->status = '';
		$this->date_sent = '';
		$this->added = '';
		$this->updated = '';

	}
}