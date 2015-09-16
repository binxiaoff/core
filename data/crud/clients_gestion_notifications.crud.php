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
class clients_gestion_notifications_crud
{
	
	public $id_client;
	public $id_notif;
	public $immediatement;
	public $quotidienne;
	public $hebdomadaire;
	public $mensuelle;
	public $uniquement_notif;
	public $added;
	public $updated;

	
	function clients_gestion_notifications($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_client = '';
		$this->id_notif = '';
		$this->immediatement = '';
		$this->quotidienne = '';
		$this->hebdomadaire = '';
		$this->mensuelle = '';
		$this->uniquement_notif = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($list_field_value)
	{
		foreach($list_field_value as $champ => $valeur)
			$list.=' AND `'.$champ.'` = "'.$valeur.'" ';
			
		$sql = 'SELECT * FROM `clients_gestion_notifications` WHERE 1=1 '.$list.' ';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_client = $record['id_client'];
			$this->id_notif = $record['id_notif'];
			$this->immediatement = $record['immediatement'];
			$this->quotidienne = $record['quotidienne'];
			$this->hebdomadaire = $record['hebdomadaire'];
			$this->mensuelle = $record['mensuelle'];
			$this->uniquement_notif = $record['uniquement_notif'];
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
	
	function update($list_field_value)
	{
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_notif = $this->bdd->escape_string($this->id_notif);
		$this->immediatement = $this->bdd->escape_string($this->immediatement);
		$this->quotidienne = $this->bdd->escape_string($this->quotidienne);
		$this->hebdomadaire = $this->bdd->escape_string($this->hebdomadaire);
		$this->mensuelle = $this->bdd->escape_string($this->mensuelle);
		$this->uniquement_notif = $this->bdd->escape_string($this->uniquement_notif);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		foreach($list_field_value as $champ => $valeur)
			$list.=' AND `'.$champ.'` = "'.$valeur.'" ';
		
		$sql = 'UPDATE `clients_gestion_notifications` SET `immediatement`="'.$this->immediatement.'",`quotidienne`="'.$this->quotidienne.'",`hebdomadaire`="'.$this->hebdomadaire.'",`mensuelle`="'.$this->mensuelle.'",`uniquement_notif`="'.$this->uniquement_notif.'",`added`="'.$this->added.'",`updated`=NOW() WHERE 1=1 '.$list.' ';
		$this->bdd->query($sql);
		
		
		
		$this->get($list_field_value);
	}
	
	function delete($list_field_value)
	{
		foreach($list_field_value as $champ => $valeur)
			$list.=' AND `'.$champ.'` = "'.$valeur.'" ';
		
		$sql = 'DELETE FROM `clients_gestion_notifications` WHERE 1=1 '.$list.' ';
		$this->bdd->query($sql);
	}
	
	function create($list_field_value)
	{
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_notif = $this->bdd->escape_string($this->id_notif);
		$this->immediatement = $this->bdd->escape_string($this->immediatement);
		$this->quotidienne = $this->bdd->escape_string($this->quotidienne);
		$this->hebdomadaire = $this->bdd->escape_string($this->hebdomadaire);
		$this->mensuelle = $this->bdd->escape_string($this->mensuelle);
		$this->uniquement_notif = $this->bdd->escape_string($this->uniquement_notif);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `clients_gestion_notifications`(`id_client`,`id_notif`,`immediatement`,`quotidienne`,`hebdomadaire`,`mensuelle`,`uniquement_notif`,`added`,`updated`) VALUES("'.$this->id_client.'","'.$this->id_notif.'","'.$this->immediatement.'","'.$this->quotidienne.'","'.$this->hebdomadaire.'","'.$this->mensuelle.'","'.$this->uniquement_notif.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		
		
		$this->get($list_field_value);
	}
	
	function unsetData()
	{
		$this->id_client = '';
		$this->id_notif = '';
		$this->immediatement = '';
		$this->quotidienne = '';
		$this->hebdomadaire = '';
		$this->mensuelle = '';
		$this->uniquement_notif = '';
		$this->added = '';
		$this->updated = '';

	}
}