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
class lenders_imposition_history_crud
{
	
	public $id_lenders_imposition_history;
	public $id_lender;
	public $exonere;
	public $resident_etranger;
	public $id_pays;
	public $id_user;
	public $added;
	public $updated;

	
	function lenders_imposition_history($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_lenders_imposition_history = '';
		$this->id_lender = '';
		$this->exonere = '';
		$this->resident_etranger = '';
		$this->id_pays = '';
		$this->id_user = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_lenders_imposition_history')
	{
		$sql = 'SELECT * FROM  `lenders_imposition_history` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_lenders_imposition_history = $record['id_lenders_imposition_history'];
			$this->id_lender = $record['id_lender'];
			$this->exonere = $record['exonere'];
			$this->resident_etranger = $record['resident_etranger'];
			$this->id_pays = $record['id_pays'];
			$this->id_user = $record['id_user'];
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
		$this->id_lenders_imposition_history = $this->bdd->escape_string($this->id_lenders_imposition_history);
		$this->id_lender = $this->bdd->escape_string($this->id_lender);
		$this->exonere = $this->bdd->escape_string($this->exonere);
		$this->resident_etranger = $this->bdd->escape_string($this->resident_etranger);
		$this->id_pays = $this->bdd->escape_string($this->id_pays);
		$this->id_user = $this->bdd->escape_string($this->id_user);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `lenders_imposition_history` SET `id_lender`="'.$this->id_lender.'",`exonere`="'.$this->exonere.'",`resident_etranger`="'.$this->resident_etranger.'",`id_pays`="'.$this->id_pays.'",`id_user`="'.$this->id_user.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_lenders_imposition_history="'.$this->id_lenders_imposition_history.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_lenders_imposition_history,'id_lenders_imposition_history');
	}
	
	function delete($id,$field='id_lenders_imposition_history')
	{
		if($id=='')
			$id = $this->id_lenders_imposition_history;
		$sql = 'DELETE FROM `lenders_imposition_history` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_lenders_imposition_history = $this->bdd->escape_string($this->id_lenders_imposition_history);
		$this->id_lender = $this->bdd->escape_string($this->id_lender);
		$this->exonere = $this->bdd->escape_string($this->exonere);
		$this->resident_etranger = $this->bdd->escape_string($this->resident_etranger);
		$this->id_pays = $this->bdd->escape_string($this->id_pays);
		$this->id_user = $this->bdd->escape_string($this->id_user);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `lenders_imposition_history`(`id_lender`,`exonere`,`resident_etranger`,`id_pays`,`id_user`,`added`,`updated`) VALUES("'.$this->id_lender.'","'.$this->exonere.'","'.$this->resident_etranger.'","'.$this->id_pays.'","'.$this->id_user.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_lenders_imposition_history = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_lenders_imposition_history,'id_lenders_imposition_history');
		
		return $this->id_lenders_imposition_history;
	}
	
	function unsetData()
	{
		$this->id_lenders_imposition_history = '';
		$this->id_lender = '';
		$this->exonere = '';
		$this->resident_etranger = '';
		$this->id_pays = '';
		$this->id_user = '';
		$this->added = '';
		$this->updated = '';

	}
}