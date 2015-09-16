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
class clients_gestion_mails_notif_crud
{
	
	public $id_clients_gestion_mails_notif;
	public $id_client;
	public $id_notif;
	public $id_project;
	public $date_notif;
	public $id_notification;
	public $id_transaction;
	public $id_loan;
	public $immediatement;
	public $quotidienne;
	public $status_check_quotidienne;
	public $hebdomadaire;
	public $status_check_hebdomadaire;
	public $mensuelle;
	public $status_check_mensuelle;
	public $added;
	public $updated;

	
	function clients_gestion_mails_notif($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_clients_gestion_mails_notif = '';
		$this->id_client = '';
		$this->id_notif = '';
		$this->id_project = '';
		$this->date_notif = '';
		$this->id_notification = '';
		$this->id_transaction = '';
		$this->id_loan = '';
		$this->immediatement = '';
		$this->quotidienne = '';
		$this->status_check_quotidienne = '';
		$this->hebdomadaire = '';
		$this->status_check_hebdomadaire = '';
		$this->mensuelle = '';
		$this->status_check_mensuelle = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_clients_gestion_mails_notif')
	{
		$sql = 'SELECT * FROM  `clients_gestion_mails_notif` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_clients_gestion_mails_notif = $record['id_clients_gestion_mails_notif'];
			$this->id_client = $record['id_client'];
			$this->id_notif = $record['id_notif'];
			$this->id_project = $record['id_project'];
			$this->date_notif = $record['date_notif'];
			$this->id_notification = $record['id_notification'];
			$this->id_transaction = $record['id_transaction'];
			$this->id_loan = $record['id_loan'];
			$this->immediatement = $record['immediatement'];
			$this->quotidienne = $record['quotidienne'];
			$this->status_check_quotidienne = $record['status_check_quotidienne'];
			$this->hebdomadaire = $record['hebdomadaire'];
			$this->status_check_hebdomadaire = $record['status_check_hebdomadaire'];
			$this->mensuelle = $record['mensuelle'];
			$this->status_check_mensuelle = $record['status_check_mensuelle'];
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
		$this->id_clients_gestion_mails_notif = $this->bdd->escape_string($this->id_clients_gestion_mails_notif);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_notif = $this->bdd->escape_string($this->id_notif);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->date_notif = $this->bdd->escape_string($this->date_notif);
		$this->id_notification = $this->bdd->escape_string($this->id_notification);
		$this->id_transaction = $this->bdd->escape_string($this->id_transaction);
		$this->id_loan = $this->bdd->escape_string($this->id_loan);
		$this->immediatement = $this->bdd->escape_string($this->immediatement);
		$this->quotidienne = $this->bdd->escape_string($this->quotidienne);
		$this->status_check_quotidienne = $this->bdd->escape_string($this->status_check_quotidienne);
		$this->hebdomadaire = $this->bdd->escape_string($this->hebdomadaire);
		$this->status_check_hebdomadaire = $this->bdd->escape_string($this->status_check_hebdomadaire);
		$this->mensuelle = $this->bdd->escape_string($this->mensuelle);
		$this->status_check_mensuelle = $this->bdd->escape_string($this->status_check_mensuelle);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `clients_gestion_mails_notif` SET `id_client`="'.$this->id_client.'",`id_notif`="'.$this->id_notif.'",`id_project`="'.$this->id_project.'",`date_notif`="'.$this->date_notif.'",`id_notification`="'.$this->id_notification.'",`id_transaction`="'.$this->id_transaction.'",`id_loan`="'.$this->id_loan.'",`immediatement`="'.$this->immediatement.'",`quotidienne`="'.$this->quotidienne.'",`status_check_quotidienne`="'.$this->status_check_quotidienne.'",`hebdomadaire`="'.$this->hebdomadaire.'",`status_check_hebdomadaire`="'.$this->status_check_hebdomadaire.'",`mensuelle`="'.$this->mensuelle.'",`status_check_mensuelle`="'.$this->status_check_mensuelle.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_clients_gestion_mails_notif="'.$this->id_clients_gestion_mails_notif.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_clients_gestion_mails_notif,'id_clients_gestion_mails_notif');
	}
	
	function delete($id,$field='id_clients_gestion_mails_notif')
	{
		if($id=='')
			$id = $this->id_clients_gestion_mails_notif;
		$sql = 'DELETE FROM `clients_gestion_mails_notif` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_clients_gestion_mails_notif = $this->bdd->escape_string($this->id_clients_gestion_mails_notif);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_notif = $this->bdd->escape_string($this->id_notif);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->date_notif = $this->bdd->escape_string($this->date_notif);
		$this->id_notification = $this->bdd->escape_string($this->id_notification);
		$this->id_transaction = $this->bdd->escape_string($this->id_transaction);
		$this->id_loan = $this->bdd->escape_string($this->id_loan);
		$this->immediatement = $this->bdd->escape_string($this->immediatement);
		$this->quotidienne = $this->bdd->escape_string($this->quotidienne);
		$this->status_check_quotidienne = $this->bdd->escape_string($this->status_check_quotidienne);
		$this->hebdomadaire = $this->bdd->escape_string($this->hebdomadaire);
		$this->status_check_hebdomadaire = $this->bdd->escape_string($this->status_check_hebdomadaire);
		$this->mensuelle = $this->bdd->escape_string($this->mensuelle);
		$this->status_check_mensuelle = $this->bdd->escape_string($this->status_check_mensuelle);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `clients_gestion_mails_notif`(`id_client`,`id_notif`,`id_project`,`date_notif`,`id_notification`,`id_transaction`,`id_loan`,`immediatement`,`quotidienne`,`status_check_quotidienne`,`hebdomadaire`,`status_check_hebdomadaire`,`mensuelle`,`status_check_mensuelle`,`added`,`updated`) VALUES("'.$this->id_client.'","'.$this->id_notif.'","'.$this->id_project.'","'.$this->date_notif.'","'.$this->id_notification.'","'.$this->id_transaction.'","'.$this->id_loan.'","'.$this->immediatement.'","'.$this->quotidienne.'","'.$this->status_check_quotidienne.'","'.$this->hebdomadaire.'","'.$this->status_check_hebdomadaire.'","'.$this->mensuelle.'","'.$this->status_check_mensuelle.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_clients_gestion_mails_notif = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_clients_gestion_mails_notif,'id_clients_gestion_mails_notif');
		
		return $this->id_clients_gestion_mails_notif;
	}
	
	function unsetData()
	{
		$this->id_clients_gestion_mails_notif = '';
		$this->id_client = '';
		$this->id_notif = '';
		$this->id_project = '';
		$this->date_notif = '';
		$this->id_notification = '';
		$this->id_transaction = '';
		$this->id_loan = '';
		$this->immediatement = '';
		$this->quotidienne = '';
		$this->status_check_quotidienne = '';
		$this->hebdomadaire = '';
		$this->status_check_hebdomadaire = '';
		$this->mensuelle = '';
		$this->status_check_mensuelle = '';
		$this->added = '';
		$this->updated = '';

	}
}