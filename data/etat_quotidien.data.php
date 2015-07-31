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

class etat_quotidien extends etat_quotidien_crud
{

	function etat_quotidien($bdd,$params='')
    {
        parent::etat_quotidien($bdd,$params);
    }
    
    function get($id,$field='id_etat_quotidien')
    {
        return parent::get($id,$field);
    }
    
    function update($cs='')
    {
        parent::update($cs);
    }
    
    function delete($id,$field='id_etat_quotidien')
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
		$sql = 'SELECT * FROM `etat_quotidien`'.$where.$order.($nb!='' && $start !=''?' LIMIT '.$start.','.$nb:($nb!=''?' LIMIT '.$nb:''));

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
			
		$sql='SELECT count(*) FROM `etat_quotidien` '.$where;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	function exist($id,$field='id_etat_quotidien')
	{
		$sql = 'SELECT * FROM `etat_quotidien` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		return ($this->bdd->fetch_array($result,0,0)>0);
	}
	
	function createEtat_quotidient($table,$month,$year)
	{
		
		
		foreach($table as $key => $t)
		{
		
			if($this->get($year.'-'.$month,'id = '.$key.' AND date'))$update = true;
			else $update = false;
			
			$this->date = $year.'-'.$month;
			$this->id = $key;
			$this->name = $t['name'];
			$this->val = round($t['val']*100,2);
			if($update == false)$this->create();
			else $this->update();
		}
		
	}
	
	function getTotaux($date)
	{
		$sql = 'SELECT * FROM `etat_quotidien` WHERE date <= "'.$date.'"';
		
		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[$record['name']] += $record['val']/100;
		}
		return $result;
	}
	
	function getTotauxbyMonth($date)
	{
		$sql = 'SELECT * FROM `etat_quotidien` WHERE date = "'.$date.'"';
		
		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[$record['name']] = $record['val']/100;
		}
		return $result;
	}
	
	/*function sum($where='')
	{
		if($where != '')
			$where = ' WHERE '.$where;
			
		$sql='SELECT SUM(*) FROM `etat_quotidien` '.$where;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}*/
	
}