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

class queries extends queries_crud
{

	function queries($bdd,$params='')
    {
        parent::queries($bdd,$params);
    }
    
    function get($id,$field='id_query')
    {
        return parent::get($id,$field);
    }
    
    function update($cs='')
    {
        parent::update($cs);
    }
    
    function delete($id,$field='id_query')
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
		$sql = 'SELECT * FROM `queries`'.$where.$order.($nb!='' && $start !=''?' LIMIT '.$start.','.$nb:($nb!=''?' LIMIT '.$nb:''));

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
			
		$sql='SELECT count(*) FROM `queries` '.$where;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	function exist($id,$field='id_query')
	{
		$sql = 'SELECT * FROM `queries` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		return ($this->bdd->fetch_array($result,0,0)>0);
	}
	
	//******************************************************************************************//
	//**************************************** AJOUTS ******************************************//
	//******************************************************************************************//
	
	function run($id,$sequel)
	{
		$sql = 'UPDATE queries SET executed = NOW(), executions = executions+1 WHERE id_query = '.$id;
		$this->bdd->query($sql);
		
		
		$sql = $sequel;
		
		$resultat = $this->bdd->query($sql);
        $result = array();
		
        while($record = $this->bdd->fetch_array($resultat))
        {
            $result[] = $record;
        }
		
        return $result;		
	}
	
	function runV2($id,$sequel)
	{
		
		$sql = 'UPDATE queries SET executed = NOW(), executions = executions+1 WHERE id_query = '.$id;
		$this->bdd->query($sql);
		
		$sql = $sequel;
		
		
		$resultat = $this->bdd->query($sql);
		
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename=export.csv');
		
		$row = mysql_fetch_assoc($resultat);
		if ($row) {
			$this->echocsv(array_keys($row));
		}
		
		while ($row) {
			$this->echocsv($row);
			$row = mysql_fetch_assoc($resultat);
		}
		
       die;		
	}
	
	function echocsv($fields)
	{
		$separator = '';
		foreach ($fields as $field) {
			if (preg_match('/\\r|\\n|,|"/', $field)) {
				$field = '"' . str_replace('"', '""', $field) . '"';
			}
			echo $separator . $field;
			$separator = ';';
		}
		echo "\r\n";
	}
	
	function super_unique($array)
	{
		$result = array_map("unserialize", array_unique(array_map("serialize", $array)));
		
		foreach ($result as $key => $value)
		{
			if ( is_array($value) )
			{
				 $result[$key] = $this->super_unique($value);
			}
		}
	
	  	return $result;
	}
}