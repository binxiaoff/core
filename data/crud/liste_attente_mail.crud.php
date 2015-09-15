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
class liste_attente_mail_crud
{
	
	public $id;
	public $type_mail;
	public $language;
	public $variables;
	public $to;
	public $statut;
	public $added;
	public $updated;

	
	function liste_attente_mail($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id = '';
		$this->type_mail = '';
		$this->language = '';
		$this->variables = '';
		$this->to = '';
		$this->statut = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id')
	{
		$sql = 'SELECT * FROM  `liste_attente_mail` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id = $record['id'];
			$this->type_mail = $record['type_mail'];
			$this->language = $record['language'];
			$this->variables = $record['variables'];
			$this->to = $record['to'];
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
		$this->id = $this->bdd->escape_string($this->id);
		$this->type_mail = $this->bdd->escape_string($this->type_mail);
		$this->language = $this->bdd->escape_string($this->language);
		$this->variables = $this->bdd->escape_string($this->variables);
		$this->to = $this->bdd->escape_string($this->to);
		$this->statut = $this->bdd->escape_string($this->statut);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `liste_attente_mail` SET `type_mail`="'.$this->type_mail.'",`language`="'.$this->language.'",`variables`="'.$this->variables.'",`to`="'.$this->to.'",`statut`="'.$this->statut.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id="'.$this->id.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id,'id');
	}
	
	function delete($id,$field='id')
	{
		if($id=='')
			$id = $this->id;
		$sql = 'DELETE FROM `liste_attente_mail` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id = $this->bdd->escape_string($this->id);
		$this->type_mail = $this->bdd->escape_string($this->type_mail);
		$this->language = $this->bdd->escape_string($this->language);
		$this->variables = $this->bdd->escape_string($this->variables);
		$this->to = $this->bdd->escape_string($this->to);
		$this->statut = $this->bdd->escape_string($this->statut);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `liste_attente_mail`(`type_mail`,`language`,`variables`,`to`,`statut`,`added`,`updated`) VALUES("'.$this->type_mail.'","'.$this->language.'","'.$this->variables.'","'.$this->to.'","'.$this->statut.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id,'id');
		
		return $this->id;
	}
	
	function unsetData()
	{
		$this->id = '';
		$this->type_mail = '';
		$this->language = '';
		$this->variables = '';
		$this->to = '';
		$this->statut = '';
		$this->added = '';
		$this->updated = '';

	}
}