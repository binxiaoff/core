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
class clients_gestion_type_notif_crud
{
	
	public $id_client_gestion_type_notif;
	public $nom;
	public $ordre;

	
	function clients_gestion_type_notif($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_client_gestion_type_notif = '';
		$this->nom = '';
		$this->ordre = '';

	}
	
	function get($id,$field='id_client_gestion_type_notif')
	{
		$sql = 'SELECT * FROM  `clients_gestion_type_notif` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_client_gestion_type_notif = $record['id_client_gestion_type_notif'];
			$this->nom = $record['nom'];
			$this->ordre = $record['ordre'];

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
		$this->id_client_gestion_type_notif = $this->bdd->escape_string($this->id_client_gestion_type_notif);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->ordre = $this->bdd->escape_string($this->ordre);

		
		$sql = 'UPDATE `clients_gestion_type_notif` SET `nom`="'.$this->nom.'",`ordre`="'.$this->ordre.'" WHERE id_client_gestion_type_notif="'.$this->id_client_gestion_type_notif.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_client_gestion_type_notif,'id_client_gestion_type_notif');
	}
	
	function delete($id,$field='id_client_gestion_type_notif')
	{
		if($id=='')
			$id = $this->id_client_gestion_type_notif;
		$sql = 'DELETE FROM `clients_gestion_type_notif` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_client_gestion_type_notif = $this->bdd->escape_string($this->id_client_gestion_type_notif);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->ordre = $this->bdd->escape_string($this->ordre);

		
		$sql = 'INSERT INTO `clients_gestion_type_notif`(`nom`,`ordre`) VALUES("'.$this->nom.'","'.$this->ordre.'")';
		$this->bdd->query($sql);
		
		$this->id_client_gestion_type_notif = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_client_gestion_type_notif,'id_client_gestion_type_notif');
		
		return $this->id_client_gestion_type_notif;
	}
	
	function unsetData()
	{
		$this->id_client_gestion_type_notif = '';
		$this->nom = '';
		$this->ordre = '';

	}
}