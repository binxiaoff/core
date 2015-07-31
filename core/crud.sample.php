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
class --classe--
{
	
--declaration--
	
	function --table--($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
--initialisation--
	}
	
	function get($id,$field='--id--')
	{
		$sql = 'SELECT * FROM  `--table--` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
	--remplissage--
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
--escapestring--
		
		$sql = 'UPDATE `--table--` SET --updatefields-- WHERE --id--="'.$this->--id--.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	--controleslug--
		}
		else
		{
	--controleslugmulti--	
		}
		
		$this->get($this->--id--,'--id--');
	}
	
	function delete($id,$field='--id--')
	{
		if($id=='')
			$id = $this->--id--;
		$sql = 'DELETE FROM `--table--` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
--escapestring--
		
		$sql = 'INSERT INTO `--table--`(--clist--) VALUES(--cvalues--)';
		$this->bdd->query($sql);
		
		$this->--id-- = $this->bdd->insert_id();
		
		if($cs=='')
		{
	--controleslug--
		}
		else
		{
	--controleslugmulti--	
		}
		
		$this->get($this->--id--,'--id--');
		
		return $this->--id--;
	}
	
	function unsetData()
	{
--initialisation--
	}
}