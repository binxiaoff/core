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
class login_log_crud
{
	
	public $id_log_login;
	public $pseudo;
	public $IP;
	public $date_action;
	public $statut;
	public $retour;
	public $added;
	public $updated;

	
	function login_log($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_log_login = '';
		$this->pseudo = '';
		$this->IP = '';
		$this->date_action = '';
		$this->statut = '';
		$this->retour = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_log_login')
	{
		$sql = 'SELECT * FROM  `login_log` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_log_login = $record['id_log_login'];
			$this->pseudo = $record['pseudo'];
			$this->IP = $record['IP'];
			$this->date_action = $record['date_action'];
			$this->statut = $record['statut'];
			$this->retour = $record['retour'];
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
		$this->id_log_login = $this->bdd->escape_string($this->id_log_login);
		$this->pseudo = $this->bdd->escape_string($this->pseudo);
		$this->IP = $this->bdd->escape_string($this->IP);
		$this->date_action = $this->bdd->escape_string($this->date_action);
		$this->statut = $this->bdd->escape_string($this->statut);
		$this->retour = $this->bdd->escape_string($this->retour);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `login_log` SET `pseudo`="'.$this->pseudo.'",`IP`="'.$this->IP.'",`date_action`="'.$this->date_action.'",`statut`="'.$this->statut.'",`retour`="'.$this->retour.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_log_login="'.$this->id_log_login.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_log_login,'id_log_login');
	}
	
	function delete($id,$field='id_log_login')
	{
		if($id=='')
			$id = $this->id_log_login;
		$sql = 'DELETE FROM `login_log` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_log_login = $this->bdd->escape_string($this->id_log_login);
		$this->pseudo = $this->bdd->escape_string($this->pseudo);
		$this->IP = $this->bdd->escape_string($this->IP);
		$this->date_action = $this->bdd->escape_string($this->date_action);
		$this->statut = $this->bdd->escape_string($this->statut);
		$this->retour = $this->bdd->escape_string($this->retour);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `login_log`(`pseudo`,`IP`,`date_action`,`statut`,`retour`,`added`,`updated`) VALUES("'.$this->pseudo.'","'.$this->IP.'","'.$this->date_action.'","'.$this->statut.'","'.$this->retour.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_log_login = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_log_login,'id_log_login');
		
		return $this->id_log_login;
	}
	
	function unsetData()
	{
		$this->id_log_login = '';
		$this->pseudo = '';
		$this->IP = '';
		$this->date_action = '';
		$this->statut = '';
		$this->retour = '';
		$this->added = '';
		$this->updated = '';

	}
}