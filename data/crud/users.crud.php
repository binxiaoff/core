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
class users_crud
{
	
	public $id_user;
	public $id_user_type;
	public $firstname;
	public $name;
	public $phone;
	public $mobile;
	public $email;
	public $password;
	public $password_edited;
	public $id_tree;
	public $status;
	public $default_analyst;
	public $added;
	public $updated;
	public $lastlogin;

	
	function users($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_user = '';
		$this->id_user_type = '';
		$this->firstname = '';
		$this->name = '';
		$this->phone = '';
		$this->mobile = '';
		$this->email = '';
		$this->password = '';
		$this->password_edited = '';
		$this->id_tree = '';
		$this->status = '';
		$this->default_analyst = '';
		$this->added = '';
		$this->updated = '';
		$this->lastlogin = '';

	}
	
	function get($id,$field='id_user')
	{
		$sql = 'SELECT * FROM  `users` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_user = $record['id_user'];
			$this->id_user_type = $record['id_user_type'];
			$this->firstname = $record['firstname'];
			$this->name = $record['name'];
			$this->phone = $record['phone'];
			$this->mobile = $record['mobile'];
			$this->email = $record['email'];
			$this->password = $record['password'];
			$this->password_edited = $record['password_edited'];
			$this->id_tree = $record['id_tree'];
			$this->status = $record['status'];
			$this->default_analyst = $record['default_analyst'];
			$this->added = $record['added'];
			$this->updated = $record['updated'];
			$this->lastlogin = $record['lastlogin'];

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
		$this->id_user = $this->bdd->escape_string($this->id_user);
		$this->id_user_type = $this->bdd->escape_string($this->id_user_type);
		$this->firstname = $this->bdd->escape_string($this->firstname);
		$this->name = $this->bdd->escape_string($this->name);
		$this->phone = $this->bdd->escape_string($this->phone);
		$this->mobile = $this->bdd->escape_string($this->mobile);
		$this->email = $this->bdd->escape_string($this->email);
		$this->password = $this->bdd->escape_string($this->password);
		$this->password_edited = $this->bdd->escape_string($this->password_edited);
		$this->id_tree = $this->bdd->escape_string($this->id_tree);
		$this->status = $this->bdd->escape_string($this->status);
		$this->default_analyst = $this->bdd->escape_string($this->default_analyst);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->lastlogin = $this->bdd->escape_string($this->lastlogin);

		
		$sql = 'UPDATE `users` SET `id_user_type`="'.$this->id_user_type.'",`firstname`="'.$this->firstname.'",`name`="'.$this->name.'",`phone`="'.$this->phone.'",`mobile`="'.$this->mobile.'",`email`="'.$this->email.'",`password`="'.$this->password.'",`password_edited`="'.$this->password_edited.'",`id_tree`="'.$this->id_tree.'",`status`="'.$this->status.'",`default_analyst`="'.$this->default_analyst.'",`added`="'.$this->added.'",`updated`=NOW(),`lastlogin`="'.$this->lastlogin.'" WHERE id_user="'.$this->id_user.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_user,'id_user');
	}
	
	function delete($id,$field='id_user')
	{
		if($id=='')
			$id = $this->id_user;
		$sql = 'DELETE FROM `users` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_user = $this->bdd->escape_string($this->id_user);
		$this->id_user_type = $this->bdd->escape_string($this->id_user_type);
		$this->firstname = $this->bdd->escape_string($this->firstname);
		$this->name = $this->bdd->escape_string($this->name);
		$this->phone = $this->bdd->escape_string($this->phone);
		$this->mobile = $this->bdd->escape_string($this->mobile);
		$this->email = $this->bdd->escape_string($this->email);
		$this->password = $this->bdd->escape_string($this->password);
		$this->password_edited = $this->bdd->escape_string($this->password_edited);
		$this->id_tree = $this->bdd->escape_string($this->id_tree);
		$this->status = $this->bdd->escape_string($this->status);
		$this->default_analyst = $this->bdd->escape_string($this->default_analyst);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->lastlogin = $this->bdd->escape_string($this->lastlogin);

		
		$sql = 'INSERT INTO `users`(`id_user_type`,`firstname`,`name`,`phone`,`mobile`,`email`,`password`,`password_edited`,`id_tree`,`status`,`default_analyst`,`added`,`updated`,`lastlogin`) VALUES("'.$this->id_user_type.'","'.$this->firstname.'","'.$this->name.'","'.$this->phone.'","'.$this->mobile.'","'.$this->email.'","'.$this->password.'","'.$this->password_edited.'","'.$this->id_tree.'","'.$this->status.'","'.$this->default_analyst.'",NOW(),NOW(),"'.$this->lastlogin.'")';
		$this->bdd->query($sql);
		
		$this->id_user = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_user,'id_user');
		
		return $this->id_user;
	}
	
	function unsetData()
	{
		$this->id_user = '';
		$this->id_user_type = '';
		$this->firstname = '';
		$this->name = '';
		$this->phone = '';
		$this->mobile = '';
		$this->email = '';
		$this->password = '';
		$this->password_edited = '';
		$this->id_tree = '';
		$this->status = '';
		$this->default_analyst = '';
		$this->added = '';
		$this->updated = '';
		$this->lastlogin = '';

	}
}