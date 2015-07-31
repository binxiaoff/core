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

class blocs_templates extends blocs_templates_crud
{

	function blocs_templates($bdd,$params='')
    {
        parent::blocs_templates($bdd,$params);
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
		$sql = 'SELECT * FROM `blocs_templates`'.$where.$order.($nb!='' && $start !=''?' LIMIT '.$start.','.$nb:($nb!=''?' LIMIT '.$nb:''));

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
			
		$sql='SELECT count(*) FROM `blocs_templates` '.$where;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	function exist($id,$field='id')
	{
		$sql = 'SELECT * FROM `blocs_templates` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		return ($this->bdd->fetch_array($result,0,0)>0);
	}
	
	//******************************************************************************************//
	//**************************************** AJOUTS ******************************************//
	//******************************************************************************************//
	
	// Selection des blocs
	function selectBlocs($where='',$order='')
    {
        if($where != '')
            $where = ' WHERE '.$where;
        if($order != '')
            $order = ' ORDER BY '.$order;
    	
    	$sql = 'SELECT blocs.slug, blocs_templates.* 
        		FROM blocs_templates 
        		LEFT JOIN blocs ON blocs.id_bloc = blocs_templates.id_bloc'.$where.$order;

        $resultat = $this->bdd->query($sql);
        $result = array();
        while($record = $this->bdd->fetch_array($resultat))
        {
            $result[] = $record;
        }
        return $result;
    } 
	
	// Récupération du dernier ordre des blocs d'un template pour une position
	function getLastPosition($position,$id_template)
	{
		$sql = 'SELECT ordre FROM blocs_templates WHERE id_template = "'.$id_template.'" AND position = "'.$position.'" ORDER BY ordre DESC LIMIT 1';
		$result = $this->bdd->query($sql);
		
		return (int)($this->bdd->result($result,0,0));
	}
	
	// Reordonner la position du template
	function reordre($id_template,$position)
	{
		$sql = 'SELECT * FROM blocs_templates WHERE id_template = "'.$id_template.'" AND position = "'.$position.'" ORDER BY ordre ASC';
		$result = $this->bdd->query($sql);
		
		$i = 0;
		while($record = $this->bdd->fetch_array($result))
		{
			$sql1 = 'UPDATE blocs_templates SET ordre = '.$i.' WHERE id = '.$record['id'];
			$this->bdd->query($sql1);
			$i++;
		}
	}
	
	// Récupération de l'ordre du bloc pour une position sur le template
	function getPosition($id_bloc,$position,$id_template)
	{
		$sql = 'SELECT ordre FROM blocs_templates WHERE position = "'.$position.'" AND id_template = "'.$id_template.'" AND id_bloc = '.$id_bloc;
		$result = $this->bdd->query($sql);
		
		return (int)($this->bdd->result($result,0,0));
	}
	
	// Monter un bloc sur la position du template
	function moveUp($id_bloc,$id_template,$position)
	{
		$laposition = $this->getPosition($id_bloc,$position,$id_template);
		
		$sql = 'UPDATE blocs_templates SET ordre = ordre + 1 WHERE id_template = "'.$id_template.'" AND position = "'.$position.'" AND ordre < '.$laposition.' ORDER BY ordre DESC LIMIT 1';
		$this->bdd->query($sql);
		
		$sql = 'UPDATE blocs_templates SET ordre = ordre - 1 WHERE id_template = "'.$id_template.'" AND position = "'.$position.'" AND id_bloc = '.$id_bloc;
		$this->bdd->query($sql);
		$this->reordre($id_template,$position);
	}
	
	// Descendre un bloc sur la position du template
	function moveDown($id_bloc,$id_template,$position)
	{
		$laposition = $this->getPosition($id_bloc,$position,$id_template);
		
		$sql = 'UPDATE blocs_templates SET ordre = ordre - 1 WHERE id_template = "'.$id_template.'" AND position = "'.$position.'" AND ordre > '.$laposition.' ORDER BY ordre ASC LIMIT 1';
		$this->bdd->query($sql);
		
		$sql = 'UPDATE blocs_templates SET ordre = ordre + 1 WHERE id_template = "'.$id_template.'" AND position = "'.$position.'" AND id_bloc = '.$id_bloc;
		$this->bdd->query($sql);
		$this->reordre($id_template,$position);
	}
}