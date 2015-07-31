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
class loggin_connection_admin_crud
{
	
	public $id_loggin_connection_admin;
	public $id_user;
	public $nom_user;
	public $email;
	public $date_connexion;
	public $ip;
	public $pays;
	public $updated;
	public $added;

	
	function loggin_connection_admin($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_loggin_connection_admin = '';
		$this->id_user = '';
		$this->nom_user = '';
		$this->email = '';
		$this->date_connexion = '';
		$this->ip = '';
		$this->pays = '';
		$this->updated = '';
		$this->added = '';

	}
	
	function get($id,$field='id_loggin_connection_admin')
	{
		$sql = 'SELECT * FROM  `loggin_connection_admin` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_loggin_connection_admin = $record['id_loggin_connection_admin'];
			$this->id_user = $record['id_user'];
			$this->nom_user = $record['nom_user'];
			$this->email = $record['email'];
			$this->date_connexion = $record['date_connexion'];
			$this->ip = $record['ip'];
			$this->pays = $record['pays'];
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
		$this->id_loggin_connection_admin = $this->bdd->escape_string($this->id_loggin_connection_admin);
		$this->id_user = $this->bdd->escape_string($this->id_user);
		$this->nom_user = $this->bdd->escape_string($this->nom_user);
		$this->email = $this->bdd->escape_string($this->email);
		$this->date_connexion = $this->bdd->escape_string($this->date_connexion);
		$this->ip = $this->bdd->escape_string($this->ip);
		$this->pays = $this->bdd->escape_string($this->pays);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->added = $this->bdd->escape_string($this->added);

		
		$sql = 'UPDATE `loggin_connection_admin` SET `id_user`="'.$this->id_user.'",`nom_user`="'.$this->nom_user.'",`email`="'.$this->email.'",`date_connexion`="'.$this->date_connexion.'",`ip`="'.$this->ip.'",`pays`="'.$this->pays.'",`updated`=NOW(),`added`="'.$this->added.'" WHERE id_loggin_connection_admin="'.$this->id_loggin_connection_admin.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_loggin_connection_admin,'id_loggin_connection_admin');
	}
	
	function delete($id,$field='id_loggin_connection_admin')
	{
		if($id=='')
			$id = $this->id_loggin_connection_admin;
		$sql = 'DELETE FROM `loggin_connection_admin` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_loggin_connection_admin = $this->bdd->escape_string($this->id_loggin_connection_admin);
		$this->id_user = $this->bdd->escape_string($this->id_user);
		$this->nom_user = $this->bdd->escape_string($this->nom_user);
		$this->email = $this->bdd->escape_string($this->email);
		$this->date_connexion = $this->bdd->escape_string($this->date_connexion);
		$this->ip = $this->bdd->escape_string($this->ip);
		$this->pays = $this->bdd->escape_string($this->pays);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->added = $this->bdd->escape_string($this->added);

		
		$sql = 'INSERT INTO `loggin_connection_admin`(`id_user`,`nom_user`,`email`,`date_connexion`,`ip`,`pays`,`updated`,`added`) VALUES("'.$this->id_user.'","'.$this->nom_user.'","'.$this->email.'","'.$this->date_connexion.'","'.$this->ip.'","'.$this->pays.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_loggin_connection_admin = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_loggin_connection_admin,'id_loggin_connection_admin');
		
		return $this->id_loggin_connection_admin;
	}
	
	function unsetData()
	{
		$this->id_loggin_connection_admin = '';
		$this->id_user = '';
		$this->nom_user = '';
		$this->email = '';
		$this->date_connexion = '';
		$this->ip = '';
		$this->pays = '';
		$this->updated = '';
		$this->added = '';

	}
}