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
class acceptations_legal_docs_crud
{
	
	public $id_acceptation;
	public $id_legal_doc;
	public $id_client;
	public $added;
	public $updated;

	
	function acceptations_legal_docs($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_acceptation = '';
		$this->id_legal_doc = '';
		$this->id_client = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_acceptation')
	{
		$sql = 'SELECT * FROM  `acceptations_legal_docs` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_acceptation = $record['id_acceptation'];
			$this->id_legal_doc = $record['id_legal_doc'];
			$this->id_client = $record['id_client'];
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
		$this->id_acceptation = $this->bdd->escape_string($this->id_acceptation);
		$this->id_legal_doc = $this->bdd->escape_string($this->id_legal_doc);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `acceptations_legal_docs` SET `id_legal_doc`="'.$this->id_legal_doc.'",`id_client`="'.$this->id_client.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_acceptation="'.$this->id_acceptation.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_acceptation,'id_acceptation');
	}
	
	function delete($id,$field='id_acceptation')
	{
		if($id=='')
			$id = $this->id_acceptation;
		$sql = 'DELETE FROM `acceptations_legal_docs` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_acceptation = $this->bdd->escape_string($this->id_acceptation);
		$this->id_legal_doc = $this->bdd->escape_string($this->id_legal_doc);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `acceptations_legal_docs`(`id_legal_doc`,`id_client`,`added`,`updated`) VALUES("'.$this->id_legal_doc.'","'.$this->id_client.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_acceptation = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_acceptation,'id_acceptation');
		
		return $this->id_acceptation;
	}
	
	function unsetData()
	{
		$this->id_acceptation = '';
		$this->id_legal_doc = '';
		$this->id_client = '';
		$this->added = '';
		$this->updated = '';

	}
}