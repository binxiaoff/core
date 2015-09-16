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
class partenaires_clics_crud
{
	
	public $id_partenaire;
	public $date;
	public $ip_adress;
	public $nb_clics;
	public $added;
	public $updated;

	
	function partenaires_clics($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_partenaire = '';
		$this->date = '';
		$this->ip_adress = '';
		$this->nb_clics = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($list_field_value)
	{
		foreach($list_field_value as $champ => $valeur)
			$list.=' AND `'.$champ.'` = "'.$valeur.'" ';
			
		$sql = 'SELECT * FROM `partenaires_clics` WHERE 1=1 '.$list.' ';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_partenaire = $record['id_partenaire'];
			$this->date = $record['date'];
			$this->ip_adress = $record['ip_adress'];
			$this->nb_clics = $record['nb_clics'];
			$this->added = $record['added'];
			$this->updated = $record['updated'];

			return true;
		}
		else
		{
			$this->unsetData();
			return false;
		}
	}
	
	function update($list_field_value)
	{
		$this->id_partenaire = $this->bdd->escape_string($this->id_partenaire);
		$this->date = $this->bdd->escape_string($this->date);
		$this->ip_adress = $this->bdd->escape_string($this->ip_adress);
		$this->nb_clics = $this->bdd->escape_string($this->nb_clics);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		foreach($list_field_value as $champ => $valeur)
			$list.=' AND `'.$champ.'` = "'.$valeur.'" ';
		
		$sql = 'UPDATE `partenaires_clics` SET `ip_adress`="'.$this->ip_adress.'",`nb_clics`="'.$this->nb_clics.'",`added`="'.$this->added.'",`updated`=NOW() WHERE 1=1 '.$list.' ';
		$this->bdd->query($sql);
		
		
		
		$this->get($list_field_value);
	}
	
	function delete($list_field_value)
	{
		foreach($list_field_value as $champ => $valeur)
			$list.=' AND `'.$champ.'` = "'.$valeur.'" ';
		
		$sql = 'DELETE FROM `partenaires_clics` WHERE 1=1 '.$list.' ';
		$this->bdd->query($sql);
	}
	
	function create($list_field_value)
	{
		$this->id_partenaire = $this->bdd->escape_string($this->id_partenaire);
		$this->date = $this->bdd->escape_string($this->date);
		$this->ip_adress = $this->bdd->escape_string($this->ip_adress);
		$this->nb_clics = $this->bdd->escape_string($this->nb_clics);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `partenaires_clics`(`id_partenaire`,`date`,`ip_adress`,`nb_clics`,`added`,`updated`) VALUES("'.$this->id_partenaire.'","'.$this->date.'","'.$this->ip_adress.'","'.$this->nb_clics.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		
		
		$this->get($list_field_value);
	}
	
	function unsetData()
	{
		$this->id_partenaire = '';
		$this->date = '';
		$this->ip_adress = '';
		$this->nb_clics = '';
		$this->added = '';
		$this->updated = '';

	}
}