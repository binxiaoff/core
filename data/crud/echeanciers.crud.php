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
class echeanciers_crud
{
	
	public $id_echeancier;
	public $id_lender;
	public $id_project;
	public $id_loan;
	public $ordre;
	public $montant;
	public $capital;
	public $interets;
	public $commission;
	public $tva;
	public $prelevements_obligatoires;
	public $retenues_source;
	public $csg;
	public $prelevements_sociaux;
	public $contributions_additionnelles;
	public $prelevements_solidarite;
	public $crds;
	public $date_echeance;
	public $date_echeance_reel;
	public $status;
	public $status_email_remb;
	public $date_echeance_emprunteur;
	public $date_echeance_emprunteur_reel;
	public $status_emprunteur;
	public $status_ra;
	public $added;
	public $updated;

	
	function echeanciers($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_echeancier = '';
		$this->id_lender = '';
		$this->id_project = '';
		$this->id_loan = '';
		$this->ordre = '';
		$this->montant = '';
		$this->capital = '';
		$this->interets = '';
		$this->commission = '';
		$this->tva = '';
		$this->prelevements_obligatoires = '';
		$this->retenues_source = '';
		$this->csg = '';
		$this->prelevements_sociaux = '';
		$this->contributions_additionnelles = '';
		$this->prelevements_solidarite = '';
		$this->crds = '';
		$this->date_echeance = '';
		$this->date_echeance_reel = '';
		$this->status = '';
		$this->status_email_remb = '';
		$this->date_echeance_emprunteur = '';
		$this->date_echeance_emprunteur_reel = '';
		$this->status_emprunteur = '';
		$this->status_ra = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_echeancier')
	{
		$sql = 'SELECT * FROM  `echeanciers` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_echeancier = $record['id_echeancier'];
			$this->id_lender = $record['id_lender'];
			$this->id_project = $record['id_project'];
			$this->id_loan = $record['id_loan'];
			$this->ordre = $record['ordre'];
			$this->montant = $record['montant'];
			$this->capital = $record['capital'];
			$this->interets = $record['interets'];
			$this->commission = $record['commission'];
			$this->tva = $record['tva'];
			$this->prelevements_obligatoires = $record['prelevements_obligatoires'];
			$this->retenues_source = $record['retenues_source'];
			$this->csg = $record['csg'];
			$this->prelevements_sociaux = $record['prelevements_sociaux'];
			$this->contributions_additionnelles = $record['contributions_additionnelles'];
			$this->prelevements_solidarite = $record['prelevements_solidarite'];
			$this->crds = $record['crds'];
			$this->date_echeance = $record['date_echeance'];
			$this->date_echeance_reel = $record['date_echeance_reel'];
			$this->status = $record['status'];
			$this->status_email_remb = $record['status_email_remb'];
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
		$this->id_echeancier = $this->bdd->escape_string($this->id_echeancier);
		$this->id_lender = $this->bdd->escape_string($this->id_lender);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->id_loan = $this->bdd->escape_string($this->id_loan);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->capital = $this->bdd->escape_string($this->capital);
		$this->interets = $this->bdd->escape_string($this->interets);
		$this->commission = $this->bdd->escape_string($this->commission);
		$this->tva = $this->bdd->escape_string($this->tva);
		$this->prelevements_obligatoires = $this->bdd->escape_string($this->prelevements_obligatoires);
		$this->retenues_source = $this->bdd->escape_string($this->retenues_source);
		$this->csg = $this->bdd->escape_string($this->csg);
		$this->prelevements_sociaux = $this->bdd->escape_string($this->prelevements_sociaux);
		$this->contributions_additionnelles = $this->bdd->escape_string($this->contributions_additionnelles);
		$this->prelevements_solidarite = $this->bdd->escape_string($this->prelevements_solidarite);
		$this->crds = $this->bdd->escape_string($this->crds);
		$this->date_echeance = $this->bdd->escape_string($this->date_echeance);
		$this->date_echeance_reel = $this->bdd->escape_string($this->date_echeance_reel);
		$this->status = $this->bdd->escape_string($this->status);
		$this->status_email_remb = $this->bdd->escape_string($this->status_email_remb);
		$this->date_echeance_emprunteur = $this->bdd->escape_string($this->date_echeance_emprunteur);
		$this->date_echeance_emprunteur_reel = $this->bdd->escape_string($this->date_echeance_emprunteur_reel);
		$this->status_emprunteur = $this->bdd->escape_string($this->status_emprunteur);
		$this->status_ra = $this->bdd->escape_string($this->status_ra);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `echeanciers` SET `id_lender`="'.$this->id_lender.'",`id_project`="'.$this->id_project.'",`id_loan`="'.$this->id_loan.'",`ordre`="'.$this->ordre.'",`montant`="'.$this->montant.'",`capital`="'.$this->capital.'",`interets`="'.$this->interets.'",`commission`="'.$this->commission.'",`tva`="'.$this->tva.'",`prelevements_obligatoires`="'.$this->prelevements_obligatoires.'",`retenues_source`="'.$this->retenues_source.'",`csg`="'.$this->csg.'",`prelevements_sociaux`="'.$this->prelevements_sociaux.'",`contributions_additionnelles`="'.$this->contributions_additionnelles.'",`prelevements_solidarite`="'.$this->prelevements_solidarite.'",`crds`="'.$this->crds.'",`date_echeance`="'.$this->date_echeance.'",`date_echeance_reel`="'.$this->date_echeance_reel.'",`status`="'.$this->status.'",`status_email_remb`="'.$this->status_email_remb.'",`date_echeance_emprunteur`="'.$this->date_echeance_emprunteur.'",`date_echeance_emprunteur_reel`="'.$this->date_echeance_emprunteur_reel.'",`status_emprunteur`="'.$this->status_emprunteur.'",`status_ra`="'.$this->status_ra.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_echeancier="'.$this->id_echeancier.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_echeancier,'id_echeancier');
	}
	
	function delete($id,$field='id_echeancier')
	{
		if($id=='')
			$id = $this->id_echeancier;
		$sql = 'DELETE FROM `echeanciers` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_echeancier = $this->bdd->escape_string($this->id_echeancier);
		$this->id_lender = $this->bdd->escape_string($this->id_lender);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->id_loan = $this->bdd->escape_string($this->id_loan);
		$this->ordre = $this->bdd->escape_string($this->ordre);
		$this->montant = $this->bdd->escape_string($this->montant);
		$this->capital = $this->bdd->escape_string($this->capital);
		$this->interets = $this->bdd->escape_string($this->interets);
		$this->commission = $this->bdd->escape_string($this->commission);
		$this->tva = $this->bdd->escape_string($this->tva);
		$this->prelevements_obligatoires = $this->bdd->escape_string($this->prelevements_obligatoires);
		$this->retenues_source = $this->bdd->escape_string($this->retenues_source);
		$this->csg = $this->bdd->escape_string($this->csg);
		$this->prelevements_sociaux = $this->bdd->escape_string($this->prelevements_sociaux);
		$this->contributions_additionnelles = $this->bdd->escape_string($this->contributions_additionnelles);
		$this->prelevements_solidarite = $this->bdd->escape_string($this->prelevements_solidarite);
		$this->crds = $this->bdd->escape_string($this->crds);
		$this->date_echeance = $this->bdd->escape_string($this->date_echeance);
		$this->date_echeance_reel = $this->bdd->escape_string($this->date_echeance_reel);
		$this->status = $this->bdd->escape_string($this->status);
		$this->status_email_remb = $this->bdd->escape_string($this->status_email_remb);
		$this->date_echeance_emprunteur = $this->bdd->escape_string($this->date_echeance_emprunteur);
		$this->date_echeance_emprunteur_reel = $this->bdd->escape_string($this->date_echeance_emprunteur_reel);
		$this->status_emprunteur = $this->bdd->escape_string($this->status_emprunteur);
		$this->status_ra = $this->bdd->escape_string($this->status_ra);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `echeanciers`(`id_lender`,`id_project`,`id_loan`,`ordre`,`montant`,`capital`,`interets`,`commission`,`tva`,`prelevements_obligatoires`,`retenues_source`,`csg`,`prelevements_sociaux`,`contributions_additionnelles`,`prelevements_solidarite`,`crds`,`date_echeance`,`date_echeance_reel`,`status`,`status_email_remb`,`date_echeance_emprunteur`,`date_echeance_emprunteur_reel`,`status_emprunteur`,`status_ra`,`added`,`updated`) VALUES("'.$this->id_lender.'","'.$this->id_project.'","'.$this->id_loan.'","'.$this->ordre.'","'.$this->montant.'","'.$this->capital.'","'.$this->interets.'","'.$this->commission.'","'.$this->tva.'","'.$this->prelevements_obligatoires.'","'.$this->retenues_source.'","'.$this->csg.'","'.$this->prelevements_sociaux.'","'.$this->contributions_additionnelles.'","'.$this->prelevements_solidarite.'","'.$this->crds.'","'.$this->date_echeance.'","'.$this->date_echeance_reel.'","'.$this->status.'","'.$this->status_email_remb.'","'.$this->date_echeance_emprunteur.'","'.$this->date_echeance_emprunteur_reel.'","'.$this->status_emprunteur.'","'.$this->status_ra.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_echeancier = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_echeancier,'id_echeancier');
		
		return $this->id_echeancier;
	}
	
	function unsetData()
	{
		$this->id_echeancier = '';
		$this->id_lender = '';
		$this->id_project = '';
		$this->id_loan = '';
		$this->ordre = '';
		$this->montant = '';
		$this->capital = '';
		$this->interets = '';
		$this->commission = '';
		$this->tva = '';
		$this->prelevements_obligatoires = '';
		$this->retenues_source = '';
		$this->csg = '';
		$this->prelevements_sociaux = '';
		$this->contributions_additionnelles = '';
		$this->prelevements_solidarite = '';
		$this->crds = '';
		$this->date_echeance = '';
		$this->date_echeance_reel = '';
		$this->status = '';
		$this->status_email_remb = '';
		$this->date_echeance_emprunteur = '';
		$this->date_echeance_emprunteur_reel = '';
		$this->status_emprunteur = '';
		$this->status_ra = '';
		$this->added = '';
		$this->updated = '';

	}
}