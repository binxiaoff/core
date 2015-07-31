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
class factures_crud
{
	
	public $id_facture;
	public $num_facture;
	public $date;
	public $id_company;
	public $id_project;
	public $ordre;
	public $type_commission;
	public $commission;
	public $montant_ht;
	public $tva;
	public $montant_ttc;
	public $added;
	public $updated;

	
	function factures($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_facture = '';
		$this->num_facture = '';
		$this->date = '';
		$this->id_company = '';
		$this->id_project = '';
		$this->ordre = '';
		$this->type_commission = '';
		$this->commission = '';
		$this->montant_ht = '';
		$this->tva = '';
		$this->montant_ttc = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_facture')
	{
		$sql = 'SELECT * FROM  `factures` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_facture = $record['id_facture'];
			$this->num_facture = $record['num_facture'];
			$this->date = $record['date'];
			$this->id_company = $record['id_company'];
			$this->id_project = $record['id_project'];
			$this->ordre = $record['ordre'];
			$this->type_commission = $record['type_commission'];
			$this->commission = $record['commission'];
			$this->montant_ht = $record['montant_ht'];
			$this->tva = $record['tva'];
			$this->montant_ttc = $record['montant_ttc'];
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
		$this->id_facture = $this->bdd->escape_string($this->id_facture);
		$this->num_facture = $this->bdd->escape_string($this->num_facture);
		$this->date = $this->bdd->escape_string($this->date);
		$this->id_company = $this->bdd->escape_string($this->id_company);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->type_commission = $this->bdd->escape_string($this->type_commission);
		$this->commission = $this->bdd->escape_string($this->commission);
		$this->montant_ht = $this->bdd->escape_string($this->montant_ht);
		$this->tva = $this->bdd->escape_string($this->tva);
		$this->montant_ttc = $this->bdd->escape_string($this->montant_ttc);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `factures` SET `num_facture`="'.$this->num_facture.'",`date`="'.$this->date.'",`id_company`="'.$this->id_company.'",`id_project`="'.$this->id_project.'",`ordre`="'.$this->ordre.'",`type_commission`="'.$this->type_commission.'",`commission`="'.$this->commission.'",`montant_ht`="'.$this->montant_ht.'",`tva`="'.$this->tva.'",`montant_ttc`="'.$this->montant_ttc.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_facture="'.$this->id_facture.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_facture,'id_facture');
	}
	
	function delete($id,$field='id_facture')
	{
		if($id=='')
			$id = $this->id_facture;
		$sql = 'DELETE FROM `factures` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_facture = $this->bdd->escape_string($this->id_facture);
		$this->num_facture = $this->bdd->escape_string($this->num_facture);
		$this->date = $this->bdd->escape_string($this->date);
		$this->id_company = $this->bdd->escape_string($this->id_company);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->type_commission = $this->bdd->escape_string($this->type_commission);
		$this->commission = $this->bdd->escape_string($this->commission);
		$this->montant_ht = $this->bdd->escape_string($this->montant_ht);
		$this->tva = $this->bdd->escape_string($this->tva);
		$this->montant_ttc = $this->bdd->escape_string($this->montant_ttc);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `factures`(`num_facture`,`date`,`id_company`,`id_project`,`ordre`,`type_commission`,`commission`,`montant_ht`,`tva`,`montant_ttc`,`added`,`updated`) VALUES("'.$this->num_facture.'","'.$this->date.'","'.$this->id_company.'","'.$this->id_project.'","'.$this->ordre.'","'.$this->type_commission.'","'.$this->commission.'","'.$this->montant_ht.'","'.$this->tva.'","'.$this->montant_ttc.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_facture = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_facture,'id_facture');
		
		return $this->id_facture;
	}
	
	function unsetData()
	{
		$this->id_facture = '';
		$this->num_facture = '';
		$this->date = '';
		$this->id_company = '';
		$this->id_project = '';
		$this->ordre = '';
		$this->type_commission = '';
		$this->commission = '';
		$this->montant_ht = '';
		$this->tva = '';
		$this->montant_ttc = '';
		$this->added = '';
		$this->updated = '';

	}
}