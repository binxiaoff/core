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
class insee_crud
{
	
	public $id_insee;
	public $CDC;
	public $CHEFLIEU;
	public $REG;
	public $DEP;
	public $COM;
	public $AR;
	public $CT;
	public $TNCC;
	public $ARTMAJ;
	public $NCC;
	public $ARTMIN;
	public $NCCENR;

	
	function insee($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_insee = '';
		$this->CDC = '';
		$this->CHEFLIEU = '';
		$this->REG = '';
		$this->DEP = '';
		$this->COM = '';
		$this->AR = '';
		$this->CT = '';
		$this->TNCC = '';
		$this->ARTMAJ = '';
		$this->NCC = '';
		$this->ARTMIN = '';
		$this->NCCENR = '';

	}
	
	function get($id,$field='id_insee')
	{
		$sql = 'SELECT * FROM  `insee` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_insee = $record['id_insee'];
			$this->CDC = $record['CDC'];
			$this->CHEFLIEU = $record['CHEFLIEU'];
			$this->REG = $record['REG'];
			$this->DEP = $record['DEP'];
			$this->COM = $record['COM'];
			$this->AR = $record['AR'];
			$this->CT = $record['CT'];
			$this->TNCC = $record['TNCC'];
			$this->ARTMAJ = $record['ARTMAJ'];
			$this->NCC = $record['NCC'];
			$this->ARTMIN = $record['ARTMIN'];
			$this->NCCENR = $record['NCCENR'];

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
		$this->id_insee = $this->bdd->escape_string($this->id_insee);
		$this->CDC = $this->bdd->escape_string($this->CDC);
		$this->CHEFLIEU = $this->bdd->escape_string($this->CHEFLIEU);
		$this->REG = $this->bdd->escape_string($this->REG);
		$this->DEP = $this->bdd->escape_string($this->DEP);
		$this->COM = $this->bdd->escape_string($this->COM);
		$this->AR = $this->bdd->escape_string($this->AR);
		$this->CT = $this->bdd->escape_string($this->CT);
		$this->TNCC = $this->bdd->escape_string($this->TNCC);
		$this->ARTMAJ = $this->bdd->escape_string($this->ARTMAJ);
		$this->NCC = $this->bdd->escape_string($this->NCC);
		$this->ARTMIN = $this->bdd->escape_string($this->ARTMIN);
		$this->NCCENR = $this->bdd->escape_string($this->NCCENR);

		
		$sql = 'UPDATE `insee` SET `CDC`="'.$this->CDC.'",`CHEFLIEU`="'.$this->CHEFLIEU.'",`REG`="'.$this->REG.'",`DEP`="'.$this->DEP.'",`COM`="'.$this->COM.'",`AR`="'.$this->AR.'",`CT`="'.$this->CT.'",`TNCC`="'.$this->TNCC.'",`ARTMAJ`="'.$this->ARTMAJ.'",`NCC`="'.$this->NCC.'",`ARTMIN`="'.$this->ARTMIN.'",`NCCENR`="'.$this->NCCENR.'" WHERE id_insee="'.$this->id_insee.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_insee,'id_insee');
	}
	
	function delete($id,$field='id_insee')
	{
		if($id=='')
			$id = $this->id_insee;
		$sql = 'DELETE FROM `insee` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_insee = $this->bdd->escape_string($this->id_insee);
		$this->CDC = $this->bdd->escape_string($this->CDC);
		$this->CHEFLIEU = $this->bdd->escape_string($this->CHEFLIEU);
		$this->REG = $this->bdd->escape_string($this->REG);
		$this->DEP = $this->bdd->escape_string($this->DEP);
		$this->COM = $this->bdd->escape_string($this->COM);
		$this->AR = $this->bdd->escape_string($this->AR);
		$this->CT = $this->bdd->escape_string($this->CT);
		$this->TNCC = $this->bdd->escape_string($this->TNCC);
		$this->ARTMAJ = $this->bdd->escape_string($this->ARTMAJ);
		$this->NCC = $this->bdd->escape_string($this->NCC);
		$this->ARTMIN = $this->bdd->escape_string($this->ARTMIN);
		$this->NCCENR = $this->bdd->escape_string($this->NCCENR);

		
		$sql = 'INSERT INTO `insee`(`CDC`,`CHEFLIEU`,`REG`,`DEP`,`COM`,`AR`,`CT`,`TNCC`,`ARTMAJ`,`NCC`,`ARTMIN`,`NCCENR`) VALUES("'.$this->CDC.'","'.$this->CHEFLIEU.'","'.$this->REG.'","'.$this->DEP.'","'.$this->COM.'","'.$this->AR.'","'.$this->CT.'","'.$this->TNCC.'","'.$this->ARTMAJ.'","'.$this->NCC.'","'.$this->ARTMIN.'","'.$this->NCCENR.'")';
		$this->bdd->query($sql);
		
		$this->id_insee = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_insee,'id_insee');
		
		return $this->id_insee;
	}
	
	function unsetData()
	{
		$this->id_insee = '';
		$this->CDC = '';
		$this->CHEFLIEU = '';
		$this->REG = '';
		$this->DEP = '';
		$this->COM = '';
		$this->AR = '';
		$this->CT = '';
		$this->TNCC = '';
		$this->ARTMAJ = '';
		$this->NCC = '';
		$this->ARTMIN = '';
		$this->NCCENR = '';

	}
}