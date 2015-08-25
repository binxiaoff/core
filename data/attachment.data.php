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

class attachment extends attachment_crud
{
	const LENDER = 'lenders_accounts';
	const COMPANY = 'companies';

	function attachment($bdd,$params='')
    {
        parent::attachment($bdd,$params);
    }
    
    function get($id,$field='id')
    {
        return parent::get($id,$field);
    }
    
    function update($cs='')
    {
        parent::update($cs);
    }
    
    function delete($id,$field='id')
    {
    	parent::delete($id,$field);
    }
    
    function create($cs='')
    {
        $id = parent::create($cs);
        return $id;
    }
	
	function select($where='',$order='',$start='',$nb='')
	{
		if($where != '')
			$where = ' WHERE '.$where;
		if($order != '')
			$order = ' ORDER BY '.$order;
		$sql = 'SELECT * FROM `attachment`'.$where.$order.($nb!='' && $start !=''?' LIMIT '.$start.','.$nb:($nb!=''?' LIMIT '.$nb:''));

		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	} 
	
	function counter($where='')
	{
		if($where != '')
			$where = ' WHERE '.$where;
			
		$sql='SELECT count(*) FROM `attachment` '.$where;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	function exist($id,$field='id')
	{
		$sql = 'SELECT * FROM `attachment` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		return ($this->bdd->fetch_array($result,0,0)>0);
	}

	function save()
	{
		$this->id = $this->bdd->escape_string($this->id);
		$this->id_type = $this->bdd->escape_string($this->id_type);
		$this->id_owner = $this->bdd->escape_string($this->id_owner);
		$this->type_owner = $this->bdd->escape_string($this->type_owner);
		$this->path = $this->bdd->escape_string($this->path);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->archived = $this->bdd->escape_string($this->archived);

		if('' === $this->archived) {
			$this->archived = 'null';
		} else {
			$this->archived = '"'.$this->archived.'"';
		}

		$sql = 'INSERT INTO `attachment`(`id_type`,`id_owner`,`type_owner`,`path`,`added`,`updated`,`archived`)
				VALUES("'.$this->id_type.'","'.$this->id_owner.'","'.$this->type_owner.'","'.$this->path.'",NOW(),null,'.$this->archived.')
				ON DUPLICATE KEY UPDATE path = "'.$this->path.'", updated = NOW(), archived = '.$this->archived;

		$this->bdd->query($sql);

		$this->id = $this->bdd->insert_id();

		$this->get($this->id,'id');

		return $this->id;
	}
}