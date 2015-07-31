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
class companies_bilans_crud
{
	
	public $id_bilan;
	public $id_company;
	public $ca;
	public $resultat_brute_exploitation;
	public $resultat_exploitation;
	public $investissements;
	public $date;
	public $added;
	public $updated;

	
	function companies_bilans($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_bilan = '';
		$this->id_company = '';
		$this->ca = '';
		$this->resultat_brute_exploitation = '';
		$this->resultat_exploitation = '';
		$this->investissements = '';
		$this->date = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_bilan')
	{
		$sql = 'SELECT * FROM  `companies_bilans` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_bilan = $record['id_bilan'];
			$this->id_company = $record['id_company'];
			$this->ca = $record['ca'];
			$this->resultat_brute_exploitation = $record['resultat_brute_exploitation'];
			$this->resultat_exploitation = $record['resultat_exploitation'];
			$this->investissements = $record['investissements'];
			$this->date = $record['date'];
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
	
	function update($cs='')
	{
		$this->id_bilan = $this->bdd->escape_string($this->id_bilan);
		$this->id_company = $this->bdd->escape_string($this->id_company);
		$this->ca = $this->bdd->escape_string($this->ca);
		$this->resultat_brute_exploitation = $this->bdd->escape_string($this->resultat_brute_exploitation);
		$this->resultat_exploitation = $this->bdd->escape_string($this->resultat_exploitation);
		$this->investissements = $this->bdd->escape_string($this->investissements);
		$this->date = $this->bdd->escape_string($this->date);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `companies_bilans` SET `id_company`="'.$this->id_company.'",`ca`="'.$this->ca.'",`resultat_brute_exploitation`="'.$this->resultat_brute_exploitation.'",`resultat_exploitation`="'.$this->resultat_exploitation.'",`investissements`="'.$this->investissements.'",`date`="'.$this->date.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_bilan="'.$this->id_bilan.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_bilan,'id_bilan');
	}
	
	function delete($id,$field='id_bilan')
	{
		if($id=='')
			$id = $this->id_bilan;
		$sql = 'DELETE FROM `companies_bilans` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_bilan = $this->bdd->escape_string($this->id_bilan);
		$this->id_company = $this->bdd->escape_string($this->id_company);
		$this->ca = $this->bdd->escape_string($this->ca);
		$this->resultat_brute_exploitation = $this->bdd->escape_string($this->resultat_brute_exploitation);
		$this->resultat_exploitation = $this->bdd->escape_string($this->resultat_exploitation);
		$this->investissements = $this->bdd->escape_string($this->investissements);
		$this->date = $this->bdd->escape_string($this->date);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `companies_bilans`(`id_company`,`ca`,`resultat_brute_exploitation`,`resultat_exploitation`,`investissements`,`date`,`added`,`updated`) VALUES("'.$this->id_company.'","'.$this->ca.'","'.$this->resultat_brute_exploitation.'","'.$this->resultat_exploitation.'","'.$this->investissements.'","'.$this->date.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_bilan = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_bilan,'id_bilan');
		
		return $this->id_bilan;
	}
	
	function unsetData()
	{
		$this->id_bilan = '';
		$this->id_company = '';
		$this->ca = '';
		$this->resultat_brute_exploitation = '';
		$this->resultat_exploitation = '';
		$this->investissements = '';
		$this->date = '';
		$this->added = '';
		$this->updated = '';

	}
}