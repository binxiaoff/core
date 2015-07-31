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

class textes extends textes_crud
{

	function textes($bdd,$params='')
    {
        parent::textes($bdd,$params);
    }
    
    function get($id,$field='id_texte')
    {
        return parent::get($id,$field);
    }
    
    function update($cs='')
    {
        parent::update($cs);
    }
    
    function delete($id,$field='id_texte')
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
		$sql = 'SELECT * FROM `textes`'.$where.$order.($nb!='' && $start !=''?' LIMIT '.$start.','.$nb:($nb!=''?' LIMIT '.$nb:''));

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
			
		$sql='SELECT count(*) FROM `textes` '.$where;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	function exist($id,$field='id_texte')
	{
		$sql = 'SELECT * FROM `textes` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		return ($this->bdd->fetch_array($result,0,0)>0);
	}
	
	//******************************************************************************************//
	//**************************************** AJOUTS ******************************************//
	//******************************************************************************************//
	
	function selectSections($langue='fr')
	{
		$sql = 'SELECT DISTINCT section,(SELECT COUNT(*) FROM textes t2 WHERE t2.section = t1.section AND id_langue = "'.$langue.'") FROM textes t1 ORDER BY section ASC';	
		$resultat = $this->bdd->query($sql);
		$result = array();
		
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	}
	
	function selectTexts($section)
	{
		$sql = 'SELECT DISTINCT nom FROM textes WHERE section = "'.$section.'" ORDER BY nom ASC';	
		$resultat = $this->bdd->query($sql);
		$result = array();
		
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	}
	
	function selectTranslations($section,$text)
	{
		$sql= 'SELECT id_langue,texte FROM textes WHERE section = "'.$section.'" AND nom = "'.$text.'"';	
		$resultat = $this->bdd->query($sql);
		$result = array();
		
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[$record['id_langue']] = $record['texte'];
		}
		return $result;
	}
	
	function updateTextTranslations($section,$text,$values)
	{
		foreach($values as $language=>$value)
		{
			$sql = 'SELECT COUNT(texte) FROM textes WHERE section = "'.$section.'" AND nom = "'.$text.'" AND id_langue = "'.$language.'"';
			$result = $this->bdd->query($sql);
			
			if($this->bdd->result($result)>0)
			{
				$sql = 'UPDATE textes SET texte = "'.$value.'", updated = NOW() WHERE section = "'.$section.'" AND nom = "'.$text.'" AND id_langue = "'.$language.'"';
			}
			else
			{
				$sql = 'INSERT INTO textes(section,nom,id_langue,texte,added,updated) VALUES("'.$section.'","'.$text.'","'.$language.'","'.$value.'",NOW(),NOW())';	
			}
			$this->bdd->query($sql);
		}
	}
	
	function selectFront($section,$id_langue)
	{
		$sql = 'SELECT * FROM textes WHERE section = "'.$section.'" AND id_langue = "'.$id_langue.'"';
		$resultat = $this->bdd->query($sql);
		$result = array();
		
		while($record = $this->bdd->fetch_array($resultat))
		{
			$start = (isset($_SESSION['user']['id_user']) && $_SESSION['user']['id_user'] != "" && $_SESSION['modification'] == 1?"<trad onclick='openTraduc(".$record['id_texte']."); return false;'>":"");
			$end = (isset($_SESSION['user']['id_user']) && $_SESSION['user']['id_user'] != "" && $_SESSION['modification'] == 1?"</trad>":"");
			$result[$record['nom']] = $start.$record['texte'].$end;
		}
		
		return $result;
	}
	
	function purgeTrad()
	{
		$sql = 'TRUNCATE TABLE `textes`';
		$resultat = $this->bdd->query($sql);
	}
}