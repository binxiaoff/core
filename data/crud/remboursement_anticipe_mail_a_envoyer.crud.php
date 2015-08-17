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
class remboursement_anticipe_mail_a_envoyer_crud
{
	
	public $id_remboursement_anticipe_mail_a_envoyer;
	public $id_reception;
	public $statut;
	public $date_envoi;
	public $added;
	public $updated;

	
	function remboursement_anticipe_mail_a_envoyer($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_remboursement_anticipe_mail_a_envoyer = '';
		$this->id_reception = '';
		$this->statut = '';
		$this->date_envoi = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_remboursement_anticipe_mail_a_envoyer')
	{
		$sql = 'SELECT * FROM  `remboursement_anticipe_mail_a_envoyer` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_remboursement_anticipe_mail_a_envoyer = $record['id_remboursement_anticipe_mail_a_envoyer'];
			$this->id_reception = $record['id_reception'];
			$this->statut = $record['statut'];
			$this->date_envoi = $record['date_envoi'];
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
		$this->id_remboursement_anticipe_mail_a_envoyer = $this->bdd->escape_string($this->id_remboursement_anticipe_mail_a_envoyer);
		$this->id_reception = $this->bdd->escape_string($this->id_reception);
		$this->statut = $this->bdd->escape_string($this->statut);
		$this->date_envoi = $this->bdd->escape_string($this->date_envoi);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `remboursement_anticipe_mail_a_envoyer` SET `id_reception`="'.$this->id_reception.'",`statut`="'.$this->statut.'",`date_envoi`="'.$this->date_envoi.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_remboursement_anticipe_mail_a_envoyer="'.$this->id_remboursement_anticipe_mail_a_envoyer.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_remboursement_anticipe_mail_a_envoyer,'id_remboursement_anticipe_mail_a_envoyer');
	}
	
	function delete($id,$field='id_remboursement_anticipe_mail_a_envoyer')
	{
		if($id=='')
			$id = $this->id_remboursement_anticipe_mail_a_envoyer;
		$sql = 'DELETE FROM `remboursement_anticipe_mail_a_envoyer` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_remboursement_anticipe_mail_a_envoyer = $this->bdd->escape_string($this->id_remboursement_anticipe_mail_a_envoyer);
		$this->id_reception = $this->bdd->escape_string($this->id_reception);
		$this->statut = $this->bdd->escape_string($this->statut);
		$this->date_envoi = $this->bdd->escape_string($this->date_envoi);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `remboursement_anticipe_mail_a_envoyer`(`id_reception`,`statut`,`date_envoi`,`added`,`updated`) VALUES("'.$this->id_reception.'","'.$this->statut.'","'.$this->date_envoi.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_remboursement_anticipe_mail_a_envoyer = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_remboursement_anticipe_mail_a_envoyer,'id_remboursement_anticipe_mail_a_envoyer');
		
		return $this->id_remboursement_anticipe_mail_a_envoyer;
	}
	
	function unsetData()
	{
		$this->id_remboursement_anticipe_mail_a_envoyer = '';
		$this->id_reception = '';
		$this->statut = '';
		$this->date_envoi = '';
		$this->added = '';
		$this->updated = '';

	}
}