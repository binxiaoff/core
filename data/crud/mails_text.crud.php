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
class mails_text_crud
{
	
	public $id_textemail;
	public $type;
	public $lang;
	public $name;
	public $exp_name;
	public $exp_email;
	public $subject;
	public $content;
	public $id_nmp;
	public $nmp_unique;
	public $nmp_secure;
	public $mode;
	public $added;
	public $updated;

	
	function mails_text($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_textemail = '';
		$this->type = '';
		$this->lang = '';
		$this->name = '';
		$this->exp_name = '';
		$this->exp_email = '';
		$this->subject = '';
		$this->content = '';
		$this->id_nmp = '';
		$this->nmp_unique = '';
		$this->nmp_secure = '';
		$this->mode = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_textemail')
	{
		$sql = 'SELECT * FROM  `mails_text` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_textemail = $record['id_textemail'];
			$this->type = $record['type'];
			$this->lang = $record['lang'];
			$this->name = $record['name'];
			$this->exp_name = $record['exp_name'];
			$this->exp_email = $record['exp_email'];
			$this->subject = $record['subject'];
			$this->content = $record['content'];
			$this->id_nmp = $record['id_nmp'];
			$this->nmp_unique = $record['nmp_unique'];
			$this->nmp_secure = $record['nmp_secure'];
			$this->mode = $record['mode'];
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
		$this->id_textemail = $this->bdd->escape_string($this->id_textemail);
		$this->type = $this->bdd->escape_string($this->type);
		$this->lang = $this->bdd->escape_string($this->lang);
		$this->name = $this->bdd->escape_string($this->name);
		$this->exp_name = $this->bdd->escape_string($this->exp_name);
		$this->exp_email = $this->bdd->escape_string($this->exp_email);
		$this->subject = $this->bdd->escape_string($this->subject);
		$this->content = $this->bdd->escape_string($this->content);
		$this->id_nmp = $this->bdd->escape_string($this->id_nmp);
		$this->nmp_unique = $this->bdd->escape_string($this->nmp_unique);
		$this->nmp_secure = $this->bdd->escape_string($this->nmp_secure);
		$this->mode = $this->bdd->escape_string($this->mode);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `mails_text` SET `type`="'.$this->type.'",`lang`="'.$this->lang.'",`name`="'.$this->name.'",`exp_name`="'.$this->exp_name.'",`exp_email`="'.$this->exp_email.'",`subject`="'.$this->subject.'",`content`="'.$this->content.'",`id_nmp`="'.$this->id_nmp.'",`nmp_unique`="'.$this->nmp_unique.'",`nmp_secure`="'.$this->nmp_secure.'",`mode`="'.$this->mode.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_textemail="'.$this->id_textemail.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_textemail,'id_textemail');
	}
	
	function delete($id,$field='id_textemail')
	{
		if($id=='')
			$id = $this->id_textemail;
		$sql = 'DELETE FROM `mails_text` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_textemail = $this->bdd->escape_string($this->id_textemail);
		$this->type = $this->bdd->escape_string($this->type);
		$this->lang = $this->bdd->escape_string($this->lang);
		$this->name = $this->bdd->escape_string($this->name);
		$this->exp_name = $this->bdd->escape_string($this->exp_name);
		$this->exp_email = $this->bdd->escape_string($this->exp_email);
		$this->subject = $this->bdd->escape_string($this->subject);
		$this->content = $this->bdd->escape_string($this->content);
		$this->id_nmp = $this->bdd->escape_string($this->id_nmp);
		$this->nmp_unique = $this->bdd->escape_string($this->nmp_unique);
		$this->nmp_secure = $this->bdd->escape_string($this->nmp_secure);
		$this->mode = $this->bdd->escape_string($this->mode);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `mails_text`(`type`,`lang`,`name`,`exp_name`,`exp_email`,`subject`,`content`,`id_nmp`,`nmp_unique`,`nmp_secure`,`mode`,`added`,`updated`) VALUES("'.$this->type.'","'.$this->lang.'","'.$this->name.'","'.$this->exp_name.'","'.$this->exp_email.'","'.$this->subject.'","'.$this->content.'","'.$this->id_nmp.'","'.$this->nmp_unique.'","'.$this->nmp_secure.'","'.$this->mode.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_textemail = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_textemail,'id_textemail');
		
		return $this->id_textemail;
	}
	
	function unsetData()
	{
		$this->id_textemail = '';
		$this->type = '';
		$this->lang = '';
		$this->name = '';
		$this->exp_name = '';
		$this->exp_email = '';
		$this->subject = '';
		$this->content = '';
		$this->id_nmp = '';
		$this->nmp_unique = '';
		$this->nmp_secure = '';
		$this->mode = '';
		$this->added = '';
		$this->updated = '';

	}
}