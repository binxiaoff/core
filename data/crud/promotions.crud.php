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
class promotions_crud
{
	
	public $id_code;
	public $type;
	public $code;
	public $from;
	public $to;
	public $value;
	public $seuil;
	public $fdp;
	public $id_tree;
	public $id_produit;
	public $id_tree2;
	public $id_produit2;
	public $nb_minimum2;
	public $id_groupe;
	public $id_client;
	public $id_produit_kdo;
	public $nb_utilisations;
	public $nb_minimum;
	public $plus_cher;
	public $moins_cher;
	public $duree;
	public $id_promo;
	public $premiere_cmde;
	public $status;
	public $added;
	public $updated;

	
	function promotions($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_code = '';
		$this->type = '';
		$this->code = '';
		$this->from = '';
		$this->to = '';
		$this->value = '';
		$this->seuil = '';
		$this->fdp = '';
		$this->id_tree = '';
		$this->id_produit = '';
		$this->id_tree2 = '';
		$this->id_produit2 = '';
		$this->nb_minimum2 = '';
		$this->id_groupe = '';
		$this->id_client = '';
		$this->id_produit_kdo = '';
		$this->nb_utilisations = '';
		$this->nb_minimum = '';
		$this->plus_cher = '';
		$this->moins_cher = '';
		$this->duree = '';
		$this->id_promo = '';
		$this->premiere_cmde = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_code')
	{
		$sql = 'SELECT * FROM  `promotions` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_code = $record['id_code'];
			$this->type = $record['type'];
			$this->code = $record['code'];
			$this->from = $record['from'];
			$this->to = $record['to'];
			$this->value = $record['value'];
			$this->seuil = $record['seuil'];
			$this->fdp = $record['fdp'];
			$this->id_tree = $record['id_tree'];
			$this->id_produit = $record['id_produit'];
			$this->id_tree2 = $record['id_tree2'];
			$this->id_produit2 = $record['id_produit2'];
			$this->nb_minimum2 = $record['nb_minimum2'];
			$this->id_groupe = $record['id_groupe'];
			$this->id_client = $record['id_client'];
			$this->id_produit_kdo = $record['id_produit_kdo'];
			$this->nb_utilisations = $record['nb_utilisations'];
			$this->nb_minimum = $record['nb_minimum'];
			$this->plus_cher = $record['plus_cher'];
			$this->moins_cher = $record['moins_cher'];
			$this->duree = $record['duree'];
			$this->id_promo = $record['id_promo'];
			$this->premiere_cmde = $record['premiere_cmde'];
			$this->status = $record['status'];
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
		$this->id_code = $this->bdd->escape_string($this->id_code);
		$this->type = $this->bdd->escape_string($this->type);
		$this->code = $this->bdd->escape_string($this->code);
		$this->from = $this->bdd->escape_string($this->from);
		$this->to = $this->bdd->escape_string($this->to);
		$this->value = $this->bdd->escape_string($this->value);
		$this->seuil = $this->bdd->escape_string($this->seuil);
		$this->fdp = $this->bdd->escape_string($this->fdp);
		$this->id_tree = $this->bdd->escape_string($this->id_tree);
		$this->id_produit = $this->bdd->escape_string($this->id_produit);
		$this->id_tree2 = $this->bdd->escape_string($this->id_tree2);
		$this->id_produit2 = $this->bdd->escape_string($this->id_produit2);
		$this->nb_minimum2 = $this->bdd->escape_string($this->nb_minimum2);
		$this->id_groupe = $this->bdd->escape_string($this->id_groupe);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_produit_kdo = $this->bdd->escape_string($this->id_produit_kdo);
		$this->nb_utilisations = $this->bdd->escape_string($this->nb_utilisations);
		$this->nb_minimum = $this->bdd->escape_string($this->nb_minimum);
		$this->plus_cher = $this->bdd->escape_string($this->plus_cher);
		$this->moins_cher = $this->bdd->escape_string($this->moins_cher);
		$this->duree = $this->bdd->escape_string($this->duree);
		$this->id_promo = $this->bdd->escape_string($this->id_promo);
		$this->premiere_cmde = $this->bdd->escape_string($this->premiere_cmde);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `promotions` SET `type`="'.$this->type.'",`code`="'.$this->code.'",`from`="'.$this->from.'",`to`="'.$this->to.'",`value`="'.$this->value.'",`seuil`="'.$this->seuil.'",`fdp`="'.$this->fdp.'",`id_tree`="'.$this->id_tree.'",`id_produit`="'.$this->id_produit.'",`id_tree2`="'.$this->id_tree2.'",`id_produit2`="'.$this->id_produit2.'",`nb_minimum2`="'.$this->nb_minimum2.'",`id_groupe`="'.$this->id_groupe.'",`id_client`="'.$this->id_client.'",`id_produit_kdo`="'.$this->id_produit_kdo.'",`nb_utilisations`="'.$this->nb_utilisations.'",`nb_minimum`="'.$this->nb_minimum.'",`plus_cher`="'.$this->plus_cher.'",`moins_cher`="'.$this->moins_cher.'",`duree`="'.$this->duree.'",`id_promo`="'.$this->id_promo.'",`premiere_cmde`="'.$this->premiere_cmde.'",`status`="'.$this->status.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_code="'.$this->id_code.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_code,'id_code');
	}
	
	function delete($id,$field='id_code')
	{
		if($id=='')
			$id = $this->id_code;
		$sql = 'DELETE FROM `promotions` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_code = $this->bdd->escape_string($this->id_code);
		$this->type = $this->bdd->escape_string($this->type);
		$this->code = $this->bdd->escape_string($this->code);
		$this->from = $this->bdd->escape_string($this->from);
		$this->to = $this->bdd->escape_string($this->to);
		$this->value = $this->bdd->escape_string($this->value);
		$this->seuil = $this->bdd->escape_string($this->seuil);
		$this->fdp = $this->bdd->escape_string($this->fdp);
		$this->id_tree = $this->bdd->escape_string($this->id_tree);
		$this->id_produit = $this->bdd->escape_string($this->id_produit);
		$this->id_tree2 = $this->bdd->escape_string($this->id_tree2);
		$this->id_produit2 = $this->bdd->escape_string($this->id_produit2);
		$this->nb_minimum2 = $this->bdd->escape_string($this->nb_minimum2);
		$this->id_groupe = $this->bdd->escape_string($this->id_groupe);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->id_produit_kdo = $this->bdd->escape_string($this->id_produit_kdo);
		$this->nb_utilisations = $this->bdd->escape_string($this->nb_utilisations);
		$this->nb_minimum = $this->bdd->escape_string($this->nb_minimum);
		$this->plus_cher = $this->bdd->escape_string($this->plus_cher);
		$this->moins_cher = $this->bdd->escape_string($this->moins_cher);
		$this->duree = $this->bdd->escape_string($this->duree);
		$this->id_promo = $this->bdd->escape_string($this->id_promo);
		$this->premiere_cmde = $this->bdd->escape_string($this->premiere_cmde);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `promotions`(`type`,`code`,`from`,`to`,`value`,`seuil`,`fdp`,`id_tree`,`id_produit`,`id_tree2`,`id_produit2`,`nb_minimum2`,`id_groupe`,`id_client`,`id_produit_kdo`,`nb_utilisations`,`nb_minimum`,`plus_cher`,`moins_cher`,`duree`,`id_promo`,`premiere_cmde`,`status`,`added`,`updated`) VALUES("'.$this->type.'","'.$this->code.'","'.$this->from.'","'.$this->to.'","'.$this->value.'","'.$this->seuil.'","'.$this->fdp.'","'.$this->id_tree.'","'.$this->id_produit.'","'.$this->id_tree2.'","'.$this->id_produit2.'","'.$this->nb_minimum2.'","'.$this->id_groupe.'","'.$this->id_client.'","'.$this->id_produit_kdo.'","'.$this->nb_utilisations.'","'.$this->nb_minimum.'","'.$this->plus_cher.'","'.$this->moins_cher.'","'.$this->duree.'","'.$this->id_promo.'","'.$this->premiere_cmde.'","'.$this->status.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_code = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_code,'id_code');
		
		return $this->id_code;
	}
	
	function unsetData()
	{
		$this->id_code = '';
		$this->type = '';
		$this->code = '';
		$this->from = '';
		$this->to = '';
		$this->value = '';
		$this->seuil = '';
		$this->fdp = '';
		$this->id_tree = '';
		$this->id_produit = '';
		$this->id_tree2 = '';
		$this->id_produit2 = '';
		$this->nb_minimum2 = '';
		$this->id_groupe = '';
		$this->id_client = '';
		$this->id_produit_kdo = '';
		$this->nb_utilisations = '';
		$this->nb_minimum = '';
		$this->plus_cher = '';
		$this->moins_cher = '';
		$this->duree = '';
		$this->id_promo = '';
		$this->premiere_cmde = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
}