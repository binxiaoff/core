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
class nmp_desabo_crud
{
	
	public $id_desabo;
	public $id_client;
	public $email;
	public $id_textemail;
	public $raison;
	public $commentaire;
	public $added;
	public $updated;

	
	function nmp_desabo($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_desabo = '';
		$this->id_client = '';
		$this->email = '';
		$this->id_textemail = '';
		$this->raison = '';
		$this->commentaire = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_desabo')
	{
		$sql = 'SELECT * FROM  `nmp_desabo` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_desabo = $record['id_desabo'];
			$this->id_client = $record['id_client'];
			$this->email = $record['email'];
			$this->id_textemail = $record['id_textemail'];
			$this->raison = $record['raison'];
			$this->commentaire = $record['commentaire'];
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
		$this->id_desabo = $this->bdd->escape_string($this->id_desabo);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->email = $this->bdd->escape_string($this->email);
		$this->id_textemail = $this->bdd->escape_string($this->id_textemail);
		$this->raison = $this->bdd->escape_string($this->raison);
		$this->commentaire = $this->bdd->escape_string($this->commentaire);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `nmp_desabo` SET `id_client`="'.$this->id_client.'",`email`="'.$this->email.'",`id_textemail`="'.$this->id_textemail.'",`raison`="'.$this->raison.'",`commentaire`="'.$this->commentaire.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_desabo="'.$this->id_desabo.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_desabo,'id_desabo');
	}
	
	function delete($id,$field='id_desabo')
	{
		if($id=='')
			$id = $this->id_desabo;
		$sql = 'DELETE FROM `nmp_desabo` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_desabo = $this->bdd->escape_string($this->id_desabo);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->email = $this->bdd->escape_string($this->email);
		$this->id_textemail = $this->bdd->escape_string($this->id_textemail);
		$this->raison = $this->bdd->escape_string($this->raison);
		$this->commentaire = $this->bdd->escape_string($this->commentaire);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `nmp_desabo`(`id_client`,`email`,`id_textemail`,`raison`,`commentaire`,`added`,`updated`) VALUES("'.$this->id_client.'","'.$this->email.'","'.$this->id_textemail.'","'.$this->raison.'","'.$this->commentaire.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_desabo = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_desabo,'id_desabo');
		
		return $this->id_desabo;
	}
	
	function unsetData()
	{
		$this->id_desabo = '';
		$this->id_client = '';
		$this->email = '';
		$this->id_textemail = '';
		$this->raison = '';
		$this->commentaire = '';
		$this->added = '';
		$this->updated = '';

	}
}