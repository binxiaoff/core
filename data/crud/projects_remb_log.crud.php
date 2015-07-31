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
class projects_remb_log_crud
{
	
	public $id_project_remb_log;
	public $id_project;
	public $ordre;
	public $debut;
	public $fin;
	public $montant_remb_net;
	public $etat;
	public $nb_pret_remb;
	public $added;
	public $updated;

	
	function projects_remb_log($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_project_remb_log = '';
		$this->id_project = '';
		$this->ordre = '';
		$this->debut = '';
		$this->fin = '';
		$this->montant_remb_net = '';
		$this->etat = '';
		$this->nb_pret_remb = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_project_remb_log')
	{
		$sql = 'SELECT * FROM  `projects_remb_log` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_project_remb_log = $record['id_project_remb_log'];
			$this->id_project = $record['id_project'];
			$this->ordre = $record['ordre'];
			$this->debut = $record['debut'];
			$this->fin = $record['fin'];
			$this->montant_remb_net = $record['montant_remb_net'];
			$this->etat = $record['etat'];
			$this->nb_pret_remb = $record['nb_pret_remb'];
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
		$this->id_project_remb_log = $this->bdd->escape_string($this->id_project_remb_log);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->debut = $this->bdd->escape_string($this->debut);
		$this->fin = $this->bdd->escape_string($this->fin);
		$this->montant_remb_net = $this->bdd->escape_string($this->montant_remb_net);
		$this->etat = $this->bdd->escape_string($this->etat);
		$this->nb_pret_remb = $this->bdd->escape_string($this->nb_pret_remb);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `projects_remb_log` SET `id_project`="'.$this->id_project.'",`ordre`="'.$this->ordre.'",`debut`="'.$this->debut.'",`fin`="'.$this->fin.'",`montant_remb_net`="'.$this->montant_remb_net.'",`etat`="'.$this->etat.'",`nb_pret_remb`="'.$this->nb_pret_remb.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_project_remb_log="'.$this->id_project_remb_log.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_project_remb_log,'id_project_remb_log');
	}
	
	function delete($id,$field='id_project_remb_log')
	{
		if($id=='')
			$id = $this->id_project_remb_log;
		$sql = 'DELETE FROM `projects_remb_log` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_project_remb_log = $this->bdd->escape_string($this->id_project_remb_log);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->debut = $this->bdd->escape_string($this->debut);
		$this->fin = $this->bdd->escape_string($this->fin);
		$this->montant_remb_net = $this->bdd->escape_string($this->montant_remb_net);
		$this->etat = $this->bdd->escape_string($this->etat);
		$this->nb_pret_remb = $this->bdd->escape_string($this->nb_pret_remb);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `projects_remb_log`(`id_project`,`ordre`,`debut`,`fin`,`montant_remb_net`,`etat`,`nb_pret_remb`,`added`,`updated`) VALUES("'.$this->id_project.'","'.$this->ordre.'","'.$this->debut.'","'.$this->fin.'","'.$this->montant_remb_net.'","'.$this->etat.'","'.$this->nb_pret_remb.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_project_remb_log = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_project_remb_log,'id_project_remb_log');
		
		return $this->id_project_remb_log;
	}
	
	function unsetData()
	{
		$this->id_project_remb_log = '';
		$this->id_project = '';
		$this->ordre = '';
		$this->debut = '';
		$this->fin = '';
		$this->montant_remb_net = '';
		$this->etat = '';
		$this->nb_pret_remb = '';
		$this->added = '';
		$this->updated = '';

	}
}