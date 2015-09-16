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
class mails_filer_crud
{
	
	public $id_filermails;
	public $id_textemail;
	public $desabo;
	public $email_nmp;
	public $from;
	public $to;
	public $subject;
	public $content;
	public $headers;
	public $added;
	public $updated;

	
	function mails_filer($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_filermails = '';
		$this->id_textemail = '';
		$this->desabo = '';
		$this->email_nmp = '';
		$this->from = '';
		$this->to = '';
		$this->subject = '';
		$this->content = '';
		$this->headers = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_filermails')
	{
		$sql = 'SELECT * FROM  `mails_filer` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_filermails = $record['id_filermails'];
			$this->id_textemail = $record['id_textemail'];
			$this->desabo = $record['desabo'];
			$this->email_nmp = $record['email_nmp'];
			$this->from = $record['from'];
			$this->to = $record['to'];
			$this->subject = $record['subject'];
			$this->content = $record['content'];
			$this->headers = $record['headers'];
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
		$this->id_filermails = $this->bdd->escape_string($this->id_filermails);
		$this->id_textemail = $this->bdd->escape_string($this->id_textemail);
		$this->desabo = $this->bdd->escape_string($this->desabo);
		$this->email_nmp = $this->bdd->escape_string($this->email_nmp);
		$this->from = $this->bdd->escape_string($this->from);
		$this->to = $this->bdd->escape_string($this->to);
		$this->subject = $this->bdd->escape_string($this->subject);
		$this->content = $this->bdd->escape_string($this->content);
		$this->headers = $this->bdd->escape_string($this->headers);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `mails_filer` SET `id_textemail`="'.$this->id_textemail.'",`desabo`="'.$this->desabo.'",`email_nmp`="'.$this->email_nmp.'",`from`="'.$this->from.'",`to`="'.$this->to.'",`subject`="'.$this->subject.'",`content`="'.$this->content.'",`headers`="'.$this->headers.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_filermails="'.$this->id_filermails.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_filermails,'id_filermails');
	}
	
	function delete($id,$field='id_filermails')
	{
		if($id=='')
			$id = $this->id_filermails;
		$sql = 'DELETE FROM `mails_filer` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_filermails = $this->bdd->escape_string($this->id_filermails);
		$this->id_textemail = $this->bdd->escape_string($this->id_textemail);
		$this->desabo = $this->bdd->escape_string($this->desabo);
		$this->email_nmp = $this->bdd->escape_string($this->email_nmp);
		$this->from = $this->bdd->escape_string($this->from);
		$this->to = $this->bdd->escape_string($this->to);
		$this->subject = $this->bdd->escape_string($this->subject);
		$this->content = $this->bdd->escape_string($this->content);
		$this->headers = $this->bdd->escape_string($this->headers);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `mails_filer`(`id_textemail`,`desabo`,`email_nmp`,`from`,`to`,`subject`,`content`,`headers`,`added`,`updated`) VALUES("'.$this->id_textemail.'","'.$this->desabo.'","'.$this->email_nmp.'","'.$this->from.'","'.$this->to.'","'.$this->subject.'","'.$this->content.'","'.$this->headers.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_filermails = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_filermails,'id_filermails');
		
		return $this->id_filermails;
	}
	
	function unsetData()
	{
		$this->id_filermails = '';
		$this->id_textemail = '';
		$this->desabo = '';
		$this->email_nmp = '';
		$this->from = '';
		$this->to = '';
		$this->subject = '';
		$this->content = '';
		$this->headers = '';
		$this->added = '';
		$this->updated = '';

	}
}