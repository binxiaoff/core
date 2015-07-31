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

class clients_adresses extends clients_adresses_crud
{

	function clients_adresses($bdd,$params='')
    {
        parent::clients_adresses($bdd,$params);
    }
    
    function get($id,$field='id_adresse')
    {
        return parent::get($id,$field);
    }
    
    function update($cs='')
    {
        parent::update($cs);
    }
    
    function delete($id,$field='id_adresse')
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
		$sql = 'SELECT * FROM `clients_adresses`'.$where.$order.($nb!='' && $start !=''?' LIMIT '.$start.','.$nb:($nb!=''?' LIMIT '.$nb:''));

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
			
		$sql='SELECT count(*) FROM `clients_adresses` '.$where;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	function exist($id,$field='id_adresse')
	{
		$sql = 'SELECT * FROM `clients_adresses` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		return ($this->bdd->fetch_array($result,0,0)>0);
	}
	
	// MODIFICATIONS
	
	function getPays($id_pays,$langue='fr')
	{
		$sql='SELECT `'.$langue.'` FROM `pays` WHERE id_pays = '.$id_pays;

		$result = $this->bdd->query($sql);
		return $this->bdd->result($result,0,0);
	}
	
	function getCodePays($id_pays)
	{
		$sql='SELECT id_langue FROM `pays` WHERE id_pays = '.$id_pays;

		$result = $this->bdd->query($sql);
		return $this->bdd->result($result,0,0);
	}
	
	function getDefaultAdresse($id_client,$type)
	{
		$sql = 'SELECT id_adresse FROM clients_adresses WHERE id_client = "'.$id_client.'" AND type = "'.$type.'" AND defaut = 1 AND status = 1';
		$result = $this->bdd->query($sql);
		return $this->bdd->result($result,0,0);
	}
	
	function removeDefaut($type,$id_client,$id)
	{
		$sql = 'UPDATE clients_adresses SET defaut = 0 WHERE id_client = "'.$id_client.'" AND type = "'.$type.'" AND id_adresse != "'.$id.'"';
		$result = $this->bdd->query($sql);
	}
}