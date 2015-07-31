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
class insee_pays_crud
{
	
	public $COG;
	public $ACTUAL;
	public $CAPAY;
	public $CRPAY;
	public $ANI;
	public $LIBCOG;
	public $LIBENR;
	public $ANCNOM;

	
	function insee_pays($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->COG = '';
		$this->ACTUAL = '';
		$this->CAPAY = '';
		$this->CRPAY = '';
		$this->ANI = '';
		$this->LIBCOG = '';
		$this->LIBENR = '';
		$this->ANCNOM = '';

	}
	
	function get($list_field_value)
	{
		foreach($list_field_value as $champ => $valeur)
			$list.=' AND `'.$champ.'` = "'.$valeur.'" ';
			
		$sql = 'SELECT * FROM `insee_pays` WHERE 1=1 '.$list.' ';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->COG = $record['COG'];
			$this->ACTUAL = $record['ACTUAL'];
			$this->CAPAY = $record['CAPAY'];
			$this->CRPAY = $record['CRPAY'];
			$this->ANI = $record['ANI'];
			$this->LIBCOG = $record['LIBCOG'];
			$this->LIBENR = $record['LIBENR'];
			$this->ANCNOM = $record['ANCNOM'];

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
		$this->COG = $this->bdd->escape_string($this->COG);
		$this->ACTUAL = $this->bdd->escape_string($this->ACTUAL);
		$this->CAPAY = $this->bdd->escape_string($this->CAPAY);
		$this->CRPAY = $this->bdd->escape_string($this->CRPAY);
		$this->ANI = $this->bdd->escape_string($this->ANI);
		$this->LIBCOG = $this->bdd->escape_string($this->LIBCOG);
		$this->LIBENR = $this->bdd->escape_string($this->LIBENR);
		$this->ANCNOM = $this->bdd->escape_string($this->ANCNOM);

		
		foreach($list_field_value as $champ => $valeur)
			$list.=' AND `'.$champ.'` = "'.$valeur.'" ';
		
		$sql = 'UPDATE `insee_pays` SET `COG`="'.$this->COG.'",`ACTUAL`="'.$this->ACTUAL.'",`CAPAY`="'.$this->CAPAY.'",`CRPAY`="'.$this->CRPAY.'",`ANI`="'.$this->ANI.'",`LIBCOG`="'.$this->LIBCOG.'",`LIBENR`="'.$this->LIBENR.'",`ANCNOM`="'.$this->ANCNOM.'" WHERE 1=1 '.$list.' ';
		$this->bdd->query($sql);
		
		
		
		$this->get($list_field_value);
	}
	
	function delete($list_field_value)
	{
		foreach($list_field_value as $champ => $valeur)
			$list.=' AND `'.$champ.'` = "'.$valeur.'" ';
		
		$sql = 'DELETE FROM `insee_pays` WHERE 1=1 '.$list.' ';
		$this->bdd->query($sql);
	}
	
	function create($list_field_value)
	{
		$this->COG = $this->bdd->escape_string($this->COG);
		$this->ACTUAL = $this->bdd->escape_string($this->ACTUAL);
		$this->CAPAY = $this->bdd->escape_string($this->CAPAY);
		$this->CRPAY = $this->bdd->escape_string($this->CRPAY);
		$this->ANI = $this->bdd->escape_string($this->ANI);
		$this->LIBCOG = $this->bdd->escape_string($this->LIBCOG);
		$this->LIBENR = $this->bdd->escape_string($this->LIBENR);
		$this->ANCNOM = $this->bdd->escape_string($this->ANCNOM);

		
		$sql = 'INSERT INTO `insee_pays`(`COG`,`ACTUAL`,`CAPAY`,`CRPAY`,`ANI`,`LIBCOG`,`LIBENR`,`ANCNOM`) VALUES("'.$this->COG.'","'.$this->ACTUAL.'","'.$this->CAPAY.'","'.$this->CRPAY.'","'.$this->ANI.'","'.$this->LIBCOG.'","'.$this->LIBENR.'","'.$this->ANCNOM.'")';
		$this->bdd->query($sql);
		
		
		
		$this->get($list_field_value);
	}
	
	function unsetData()
	{
		$this->COG = '';
		$this->ACTUAL = '';
		$this->CAPAY = '';
		$this->CRPAY = '';
		$this->ANI = '';
		$this->LIBCOG = '';
		$this->LIBENR = '';
		$this->ANCNOM = '';

	}
}