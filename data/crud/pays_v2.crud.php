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
class pays_v2_crud
{
	
	public $id_pays;
	public $fr;
	public $iso;
	public $ordre;

	
	function pays_v2($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_pays = '';
		$this->fr = '';
		$this->iso = '';
		$this->ordre = '';

	}
	
	function get($id,$field='id_pays')
	{
		$sql = 'SELECT * FROM  `pays_v2` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_pays = $record['id_pays'];
			$this->fr = $record['fr'];
			$this->iso = $record['iso'];
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
		$this->id_pays = $this->bdd->escape_string($this->id_pays);
		$this->fr = $this->bdd->escape_string($this->fr);
		$this->iso = $this->bdd->escape_string($this->iso);
		$this->ordre = $this->bdd->escape_string($this->ordre);

		
		$sql = 'UPDATE `pays_v2` SET `fr`="'.$this->fr.'",`iso`="'.$this->iso.'",`ordre`="'.$this->ordre.'" WHERE id_pays="'.$this->id_pays.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_pays,'id_pays');
	}
	
	function delete($id,$field='id_pays')
	{
		if($id=='')
			$id = $this->id_pays;
		$sql = 'DELETE FROM `pays_v2` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_pays = $this->bdd->escape_string($this->id_pays);
		$this->fr = $this->bdd->escape_string($this->fr);
		$this->iso = $this->bdd->escape_string($this->iso);
		$this->ordre = $this->bdd->escape_string($this->ordre);

		
		$sql = 'INSERT INTO `pays_v2`(`fr`,`iso`,`ordre`) VALUES("'.$this->fr.'","'.$this->iso.'","'.$this->ordre.'")';
		$this->bdd->query($sql);
		
		$this->id_pays = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_pays,'id_pays');
		
		return $this->id_pays;
	}
	
	function unsetData()
	{
		$this->id_pays = '';
		$this->fr = '';
		$this->iso = '';
		$this->ordre = '';

	}
}