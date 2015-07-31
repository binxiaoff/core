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

class settings extends settings_crud
{

	function settings($bdd,$params='')
    {
        parent::settings($bdd,$params);
    }
    
    function get($id,$field='id_setting')
    {
        return parent::get($id,$field);
    }
    
    function update($cs='')
    {
        parent::update($cs);
    }
    
    function delete($id,$field='id_setting')
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
		$sql = 'SELECT * FROM `settings`'.$where.$order.($nb!='' && $start !=''?' LIMIT '.$start.','.$nb:($nb!=''?' LIMIT '.$nb:''));

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
			
		$sql='SELECT count(*) FROM `settings` '.$where;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	function exist($id,$field='id_setting')
	{
		$sql = 'SELECT * FROM `settings` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		return ($this->bdd->fetch_array($result,0,0)>0);
	}
	
	function getConstTemplate($id_template,$type)
	{
		$sql = 'SELECT value FROM  `settings` WHERE id_template = '.$id_template.' AND type = "'.$type.'" ';
		$result = $this->bdd->query($sql);
		$record = $this->bdd->fetch_array($result);
		
		return $record['value'];
	}
	
	function GenerationCle($Texte,$CleDEncryptage)
  	{
  		$CleDEncryptage = md5($CleDEncryptage);
 		$Compteur=0;
  		$VariableTemp = "";
  		for ($Ctr=0;$Ctr<strlen($Texte);$Ctr++)
    	{
    		if ($Compteur==strlen($CleDEncryptage))
      			$Compteur=0;
    		$VariableTemp.= substr($Texte,$Ctr,1) ^ substr($CleDEncryptage,$Compteur,1);
    		$Compteur++;
    	}
  		return $VariableTemp;
  	}
	
	function Crypte($Texte,$Cle)
    {
		srand((double)microtime()*1000000);
		$CleDEncryptage = md5(rand(0,32000) );
		$Compteur=0;
		$VariableTemp = "";
		for ($Ctr=0;$Ctr<strlen($Texte);$Ctr++)
		{
			if ($Compteur==strlen($CleDEncryptage))
			$Compteur=0;
			$VariableTemp.= substr($CleDEncryptage,$Compteur,1).(substr($Texte,$Ctr,1) ^ substr($CleDEncryptage,$Compteur,1) );
			$Compteur++;
		}
		return base64_encode($this->GenerationCle($VariableTemp,$Cle) );
    }

	function Decrypte($Texte,$Cle)
	{
		$Texte = $this->GenerationCle(base64_decode($Texte),$Cle);
	  	$VariableTemp = "";
	  	for ($Ctr=0;$Ctr<strlen($Texte);$Ctr++)
		{
			$md5 = substr($Texte,$Ctr,1);
			$Ctr++;
			$VariableTemp.= (substr($Texte,$Ctr,1) ^ $md5);
		}
	  	return $VariableTemp;
	}
}