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
class offres_bienvenues_crud
{
	
	public $id_offre_bienvenue;
	public $montant;
	public $montant_limit;
	public $debut;
	public $fin;
	public $id_user;
	public $status;
	public $added;
	public $updated;

	
	function offres_bienvenues($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_offre_bienvenue = '';
		$this->montant = '';
		$this->montant_limit = '';
		$this->debut = '';
		$this->fin = '';
		$this->id_user = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_offre_bienvenue')
	{
		$sql = 'SELECT * FROM  `offres_bienvenues` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_offre_bienvenue = $record['id_offre_bienvenue'];
			$this->montant = $record['montant'];
			$this->montant_limit = $record['montant_limit'];
			$this->debut = $record['debut'];
			$this->fin = $record['fin'];
			$this->id_user = $record['id_user'];
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
		$this->id_offre_bienvenue = $this->bdd->escape_string($this->id_offre_bienvenue);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->montant_limit = $this->bdd->escape_string($this->montant_limit);
		$this->debut = $this->bdd->escape_string($this->debut);
		$this->fin = $this->bdd->escape_string($this->fin);
		$this->id_user = $this->bdd->escape_string($this->id_user);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `offres_bienvenues` SET `montant`="'.$this->montant.'",`montant_limit`="'.$this->montant_limit.'",`debut`="'.$this->debut.'",`fin`="'.$this->fin.'",`id_user`="'.$this->id_user.'",`status`="'.$this->status.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_offre_bienvenue="'.$this->id_offre_bienvenue.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_offre_bienvenue,'id_offre_bienvenue');
	}
	
	function delete($id,$field='id_offre_bienvenue')
	{
		if($id=='')
			$id = $this->id_offre_bienvenue;
		$sql = 'DELETE FROM `offres_bienvenues` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_offre_bienvenue = $this->bdd->escape_string($this->id_offre_bienvenue);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->montant_limit = $this->bdd->escape_string($this->montant_limit);
		$this->debut = $this->bdd->escape_string($this->debut);
		$this->fin = $this->bdd->escape_string($this->fin);
		$this->id_user = $this->bdd->escape_string($this->id_user);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `offres_bienvenues`(`montant`,`montant_limit`,`debut`,`fin`,`id_user`,`status`,`added`,`updated`) VALUES("'.$this->montant.'","'.$this->montant_limit.'","'.$this->debut.'","'.$this->fin.'","'.$this->id_user.'","'.$this->status.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_offre_bienvenue = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_offre_bienvenue,'id_offre_bienvenue');
		
		return $this->id_offre_bienvenue;
	}
	
	function unsetData()
	{
		$this->id_offre_bienvenue = '';
		$this->montant = '';
		$this->montant_limit = '';
		$this->debut = '';
		$this->fin = '';
		$this->id_user = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
}