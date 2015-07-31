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

class tree_menu extends tree_menu_crud
{

	function tree_menu($bdd,$params='')
    {
        parent::tree_menu($bdd,$params);
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
		$sql = 'SELECT * FROM tree_menu'.$where.$order.($nb!='' && $start !=''?' LIMIT '.$start.','.$nb:($nb!=''?' LIMIT '.$nb:''));

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
			
		$sql='SELECT count(*) FROM tree_menu '.$where;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	function exist($list_field_value)
	{
		foreach($list_field_value as $champ => $valeur)
			$list.=' AND '.$champ.' = "'.$valeur.'" ';
			
		$sql = 'SELECT * FROM tree_menu WHERE 1=1 '.$list.' ';
		$result = $this->bdd->query($sql);
		return ($this->bdd->fetch_array($result,0,0)>0);
	}
	
	//******************************************************************************************//
	//**************************************** AJOUTS ******************************************//
	//******************************************************************************************//
	
	// Recuperation de l'id max pour la création d'une page (clé primaire multiple, pas d'auto incremente)
	function getMaxId()
	{
		$sql = 'SELECT MAX(id) as id FROM tree_menu';
		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	// Récupération de la derniere position des liens d'un menu
	function getLastPosition($id_menu)
	{
		$sql = 'SELECT ordre FROM tree_menu WHERE id_menu = "'.$id_menu.'" ORDER BY ordre DESC LIMIT 1';
		$result = $this->bdd->query($sql);
		
		return (int)($this->bdd->result($result,0,0));
	}
	
	// Récupération de la position du lien
	function getPosition($id)
	{
		$sql = 'SELECT ordre FROM tree_menu WHERE id = "'.$id.'"';
		$result = $this->bdd->query($sql);
		
		return (int)($this->bdd->result($result,0,0));
	}
	
	// Monter un lien dans le menu
	function moveUp($id,$id_menu)
	{
		$position = $this->getPosition($id);
		
		$sql = 'SELECT id FROM tree_menu WHERE id_menu = "'.$id_menu.'" AND ordre < '.$position.' ORDER BY ordre DESC LIMIT 1';
		$result = $this->bdd->query($sql);
		
		$sql = 'UPDATE tree_menu SET ordre = ordre + 1 WHERE id = "'.(int)$this->bdd->result($result,0,0).'"';
		$this->bdd->query($sql);
		
		$sql = 'UPDATE tree_menu SET ordre = ordre - 1 WHERE id = "'.$id.'"';
		$this->bdd->query($sql);
		$this->reordre($id_menu);
	}
	
	// Descendre un lien dans le menu
	function moveDown($id,$id_menu)
	{
		$position = $this->getPosition($id);
		
		$sql = 'SELECT id FROM tree_menu WHERE id_menu = "'.$id_menu.'" AND ordre > '.$position.' ORDER BY ordre ASC LIMIT 1';
		$result = $this->bdd->query($sql);
		
		$sql = 'UPDATE tree_menu SET ordre = ordre - 1  WHERE id = "'.(int)$this->bdd->result($result,0,0).'"';
		$this->bdd->query($sql);
		
		$sql = 'UPDATE tree_menu SET ordre = ordre + 1 WHERE id = "'.$id.'"';
		$this->bdd->query($sql);
		$this->reordre($id_menu);
	}
	
	// Reordonner un menu
	function reordre($id_menu)
	{
		$sql = 'SELECT DISTINCT(id) FROM tree_menu WHERE id_menu = "'.$id_menu.'" ORDER BY ordre ASC';
		$result = $this->bdd->query($sql);
		
		$i = 0;
		while($record = $this->bdd->fetch_array($result))
		{
			$sql1 = 'UPDATE tree_menu SET ordre = '.$i.' WHERE id = '.$record['id'];
			$this->bdd->query($sql1);
			$i++;
		}
	}
}