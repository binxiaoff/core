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
class demande_contact_crud
{
	
	public $id_demande_contact;
	public $demande;
	public $preciser;
	public $nom;
	public $prenom;
	public $email;
	public $telephone;
	public $message;
	public $societe;
	public $added;
	public $updated;

	
	function demande_contact($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_demande_contact = '';
		$this->demande = '';
		$this->preciser = '';
		$this->nom = '';
		$this->prenom = '';
		$this->email = '';
		$this->telephone = '';
		$this->message = '';
		$this->societe = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_demande_contact')
	{
		$sql = 'SELECT * FROM  `demande_contact` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_demande_contact = $record['id_demande_contact'];
			$this->demande = $record['demande'];
			$this->preciser = $record['preciser'];
			$this->nom = $record['nom'];
			$this->prenom = $record['prenom'];
			$this->email = $record['email'];
			$this->telephone = $record['telephone'];
			$this->message = $record['message'];
			$this->societe = $record['societe'];
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
		$this->id_demande_contact = $this->bdd->escape_string($this->id_demande_contact);
		$this->demande = $this->bdd->escape_string($this->demande);
		$this->preciser = $this->bdd->escape_string($this->preciser);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->prenom = $this->bdd->escape_string($this->prenom);
		$this->email = $this->bdd->escape_string($this->email);
		$this->telephone = $this->bdd->escape_string($this->telephone);
		$this->message = $this->bdd->escape_string($this->message);
		$this->societe = $this->bdd->escape_string($this->societe);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `demande_contact` SET `demande`="'.$this->demande.'",`preciser`="'.$this->preciser.'",`nom`="'.$this->nom.'",`prenom`="'.$this->prenom.'",`email`="'.$this->email.'",`telephone`="'.$this->telephone.'",`message`="'.$this->message.'",`societe`="'.$this->societe.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_demande_contact="'.$this->id_demande_contact.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_demande_contact,'id_demande_contact');
	}
	
	function delete($id,$field='id_demande_contact')
	{
		if($id=='')
			$id = $this->id_demande_contact;
		$sql = 'DELETE FROM `demande_contact` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_demande_contact = $this->bdd->escape_string($this->id_demande_contact);
		$this->demande = $this->bdd->escape_string($this->demande);
		$this->preciser = $this->bdd->escape_string($this->preciser);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->prenom = $this->bdd->escape_string($this->prenom);
		$this->email = $this->bdd->escape_string($this->email);
		$this->telephone = $this->bdd->escape_string($this->telephone);
		$this->message = $this->bdd->escape_string($this->message);
		$this->societe = $this->bdd->escape_string($this->societe);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `demande_contact`(`demande`,`preciser`,`nom`,`prenom`,`email`,`telephone`,`message`,`societe`,`added`,`updated`) VALUES("'.$this->demande.'","'.$this->preciser.'","'.$this->nom.'","'.$this->prenom.'","'.$this->email.'","'.$this->telephone.'","'.$this->message.'","'.$this->societe.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_demande_contact = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_demande_contact,'id_demande_contact');
		
		return $this->id_demande_contact;
	}
	
	function unsetData()
	{
		$this->id_demande_contact = '';
		$this->demande = '';
		$this->preciser = '';
		$this->nom = '';
		$this->prenom = '';
		$this->email = '';
		$this->telephone = '';
		$this->message = '';
		$this->societe = '';
		$this->added = '';
		$this->updated = '';

	}
}