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

class fdp_type extends fdp_type_crud
{

	function fdp_type($bdd,$params='')
    {
        parent::fdp_type($bdd,$params);
    }
    
    function get($list_field_value)
    {
        return parent::get($list_field_value);
    }
    
    function update($list_field_value)
    {
        parent::update($list_field_value);
    }
    
    function delete($list_field_value)
    {
    	parent::delete($list_field_value);
    }
    
    function create($list_field_value=array())
    {
        parent::create($list_field_value);
    }
	
	function select($where='',$order='',$start='',$nb='')
	{
		if($where != '')
			$where = ' WHERE '.$where;
		if($order != '')
			$order = ' ORDER BY '.$order;
		$sql = 'SELECT * FROM fdp_type'.$where.$order.($nb!='' && $start !=''?' LIMIT '.$start.','.$nb:($nb!=''?' LIMIT '.$nb:''));

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
			
		$sql='SELECT count(*) FROM fdp_type '.$where;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	function exist($list_field_value)
	{
		foreach($list_field_value as $champ => $valeur)
			$list.=' AND '.$champ.' = "'.$valeur.'" ';
			
		$sql = 'SELECT * FROM fdp_type WHERE 1=1 '.$list.' ';
		$result = $this->bdd->query($sql);
		return ($this->bdd->fetch_array($result,0,0)>0);
	}
	
	//******************************************************************************************//
	//**************************************** AJOUTS ******************************************//
	//******************************************************************************************//	
	
	// Recuperation de l'id par default
	function getDefault($id_langue='fr')
	{
		$sql = 'SELECT * FROM fdp_type WHERE id_langue = "'.$id_langue.'" ORDER BY ordre ASC LIMIT 1';
		$result = $this->bdd->query($sql);
		$record = $this->bdd->fetch_array($result);
		return $record;
	}
	
	// Recuperation de l'id max
	function getMaxId()
	{
		$sql = 'SELECT MAX(id_type) as id FROM fdp_type';
		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	// Récupération de la derniere position
	function getLastPosition()
	{
		$sql = 'SELECT ordre FROM fdp_type ORDER BY ordre DESC LIMIT 1';
		$result = $this->bdd->query($sql);
		
		return (int)($this->bdd->result($result,0,0));
	}
	
	// Récupération de la position du type
	function getPosition($id_type)
	{
		$sql = 'SELECT ordre FROM fdp_type WHERE id_type = "'.$id_type.'"';
		$result = $this->bdd->query($sql);
		
		return (int)($this->bdd->result($result,0,0));
	}
	
	// Monter un type
	function moveUp($id_type)
	{
		$position = $this->getPosition($id_type);
		
		$sql = 'SELECT id_type FROM fdp_type WHERE ordre < '.$position.' ORDER BY ordre DESC LIMIT 1';
		$result = $this->bdd->query($sql);
		
		$sql = 'UPDATE fdp_type SET ordre = ordre + 1 WHERE id_type = "'.(int)$this->bdd->result($result,0,0).'"';
		$this->bdd->query($sql);
		
		$sql = 'UPDATE fdp_type SET ordre = ordre - 1 WHERE id_type = "'.$id_type.'"';
		$this->bdd->query($sql);
		$this->reOrdre();
	}
	
	// Descendre un type
	function moveDown($id_type)
	{
		$position = $this->getPosition($id_type);
		
		$sql = 'SELECT id_type FROM fdp_type WHERE ordre > '.$position.' ORDER BY ordre ASC LIMIT 1';
		$result = $this->bdd->query($sql);
		
		$sql = 'UPDATE fdp_type SET ordre = ordre - 1  WHERE id_type = "'.(int)$this->bdd->result($result,0,0).'"';
		$this->bdd->query($sql);
		
		$sql = 'UPDATE fdp_type SET ordre = ordre + 1 WHERE id_type = "'.$id_type.'"';
		$this->bdd->query($sql);
		$this->reOrdre();
	}
	
	// Reordre des types
	function reOrdre()
	{
		$sql = 'SELECT DISTINCT(id_type) FROM fdp_type ORDER BY ordre ASC';
		$result = $this->bdd->query($sql);
		
		$i = 0;
		while($record = $this->bdd->fetch_array($result))
		{
			$sql1 = 'UPDATE fdp_type SET ordre = '.$i.' WHERE id_type = '.$record['id_type'];
			$this->bdd->query($sql1);
			$i++;
		}
	}
}