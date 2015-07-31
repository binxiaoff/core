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
class echeanciers_emprunteur_crud
{
	
	public $id_echeancier_emprunteur;
	public $id_project;
	public $ordre;
	public $montant;
	public $capital;
	public $interets;
	public $commission;
	public $tva;
	public $date_echeance_emprunteur;
	public $date_echeance_emprunteur_reel;
	public $status_emprunteur;
	public $status_ra;
	public $added;
	public $updated;

	
	function echeanciers_emprunteur($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_echeancier_emprunteur = '';
		$this->id_project = '';
		$this->ordre = '';
		$this->montant = '';
		$this->capital = '';
		$this->interets = '';
		$this->commission = '';
		$this->tva = '';
		$this->date_echeance_emprunteur = '';
		$this->date_echeance_emprunteur_reel = '';
		$this->status_emprunteur = '';
		$this->status_ra = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_echeancier_emprunteur')
	{
		$sql = 'SELECT * FROM  `echeanciers_emprunteur` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_echeancier_emprunteur = $record['id_echeancier_emprunteur'];
			$this->id_project = $record['id_project'];
			$this->ordre = $record['ordre'];
			$this->montant = $record['montant'];
			$this->capital = $record['capital'];
			$this->interets = $record['interets'];
			$this->commission = $record['commission'];
			$this->tva = $record['tva'];
			$this->date_echeance_emprunteur = $record['date_echeance_emprunteur'];
			$this->date_echeance_emprunteur_reel = $record['date_echeance_emprunteur_reel'];
			$this->status_emprunteur = $record['status_emprunteur'];
			$this->status_ra = $record['status_ra'];
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
		$this->id_echeancier_emprunteur = $this->bdd->escape_string($this->id_echeancier_emprunteur);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->capital = $this->bdd->escape_string($this->capital);
		$this->interets = $this->bdd->escape_string($this->interets);
		$this->commission = $this->bdd->escape_string($this->commission);
		$this->tva = $this->bdd->escape_string($this->tva);
		$this->date_echeance_emprunteur = $this->bdd->escape_string($this->date_echeance_emprunteur);
		$this->date_echeance_emprunteur_reel = $this->bdd->escape_string($this->date_echeance_emprunteur_reel);
		$this->status_emprunteur = $this->bdd->escape_string($this->status_emprunteur);
		$this->status_ra = $this->bdd->escape_string($this->status_ra);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `echeanciers_emprunteur` SET `id_project`="'.$this->id_project.'",`ordre`="'.$this->ordre.'",`montant`="'.$this->montant.'",`capital`="'.$this->capital.'",`interets`="'.$this->interets.'",`commission`="'.$this->commission.'",`tva`="'.$this->tva.'",`date_echeance_emprunteur`="'.$this->date_echeance_emprunteur.'",`date_echeance_emprunteur_reel`="'.$this->date_echeance_emprunteur_reel.'",`status_emprunteur`="'.$this->status_emprunteur.'",`status_ra`="'.$this->status_ra.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_echeancier_emprunteur="'.$this->id_echeancier_emprunteur.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_echeancier_emprunteur,'id_echeancier_emprunteur');
	}
	
	function delete($id,$field='id_echeancier_emprunteur')
	{
		if($id=='')
			$id = $this->id_echeancier_emprunteur;
		$sql = 'DELETE FROM `echeanciers_emprunteur` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_echeancier_emprunteur = $this->bdd->escape_string($this->id_echeancier_emprunteur);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->capital = $this->bdd->escape_string($this->capital);
		$this->interets = $this->bdd->escape_string($this->interets);
		$this->commission = $this->bdd->escape_string($this->commission);
		$this->tva = $this->bdd->escape_string($this->tva);
		$this->date_echeance_emprunteur = $this->bdd->escape_string($this->date_echeance_emprunteur);
		$this->date_echeance_emprunteur_reel = $this->bdd->escape_string($this->date_echeance_emprunteur_reel);
		$this->status_emprunteur = $this->bdd->escape_string($this->status_emprunteur);
		$this->status_ra = $this->bdd->escape_string($this->status_ra);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `echeanciers_emprunteur`(`id_project`,`ordre`,`montant`,`capital`,`interets`,`commission`,`tva`,`date_echeance_emprunteur`,`date_echeance_emprunteur_reel`,`status_emprunteur`,`status_ra`,`added`,`updated`) VALUES("'.$this->id_project.'","'.$this->ordre.'","'.$this->montant.'","'.$this->capital.'","'.$this->interets.'","'.$this->commission.'","'.$this->tva.'","'.$this->date_echeance_emprunteur.'","'.$this->date_echeance_emprunteur_reel.'","'.$this->status_emprunteur.'","'.$this->status_ra.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_echeancier_emprunteur = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_echeancier_emprunteur,'id_echeancier_emprunteur');
		
		return $this->id_echeancier_emprunteur;
	}
	
	function unsetData()
	{
		$this->id_echeancier_emprunteur = '';
		$this->id_project = '';
		$this->ordre = '';
		$this->montant = '';
		$this->capital = '';
		$this->interets = '';
		$this->commission = '';
		$this->tva = '';
		$this->date_echeance_emprunteur = '';
		$this->date_echeance_emprunteur_reel = '';
		$this->status_emprunteur = '';
		$this->status_ra = '';
		$this->added = '';
		$this->updated = '';

	}
}