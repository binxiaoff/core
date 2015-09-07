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
class attachment_type_crud
{
	
<<<<<<< HEAD
	public $id;
	public $label;
=======
	public $id;
	public $label;
>>>>>>> Bugfix-BT17951

	
	function attachment_type($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
<<<<<<< HEAD
		$this->id = '';
		$this->label = '';
=======
		$this->id = '';
		$this->label = '';
>>>>>>> Bugfix-BT17951

	}
	
	function get($id,$field='id')
	{
		$sql = 'SELECT * FROM  `attachment_type` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
<<<<<<< HEAD
				$this->id = $record['id'];
			$this->label = $record['label'];
=======
				$this->id = $record['id'];
			$this->label = $record['label'];
>>>>>>> Bugfix-BT17951

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
<<<<<<< HEAD
		$this->id = $this->bdd->escape_string($this->id);
		$this->label = $this->bdd->escape_string($this->label);
=======
		$this->id = $this->bdd->escape_string($this->id);
		$this->label = $this->bdd->escape_string($this->label);
>>>>>>> Bugfix-BT17951

		
		$sql = 'UPDATE `attachment_type` SET `label`="'.$this->label.'" WHERE id="'.$this->id.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id,'id');
	}
	
	function delete($id,$field='id')
	{
		if($id=='')
			$id = $this->id;
		$sql = 'DELETE FROM `attachment_type` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
<<<<<<< HEAD
		$this->id = $this->bdd->escape_string($this->id);
		$this->label = $this->bdd->escape_string($this->label);
=======
		$this->id = $this->bdd->escape_string($this->id);
		$this->label = $this->bdd->escape_string($this->label);
>>>>>>> Bugfix-BT17951

		
		$sql = 'INSERT INTO `attachment_type`(`label`) VALUES("'.$this->label.'")';
		$this->bdd->query($sql);
		
		$this->id = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id,'id');
		
		return $this->id;
	}
	
	function unsetData()
	{
<<<<<<< HEAD
		$this->id = '';
		$this->label = '';
=======
		$this->id = '';
		$this->label = '';
>>>>>>> Bugfix-BT17951

	}
}