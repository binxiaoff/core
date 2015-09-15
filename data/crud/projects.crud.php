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
class projects_crud
{
	
	public $id_project;
	public $slug;
	public $id_company;
	public $id_partenaire;
	public $id_partenaire_subcode;
	public $amount;
	public $status_solde;
	public $period;
	public $title;
	public $title_bo;
	public $photo_projet;
	public $lien_video;
	public $comments;
	public $nature_project;
	public $objectif_loan;
	public $presentation_company;
	public $means_repayment;
	public $type;
	public $target_rate;
	public $stand_by;
	public $id_analyste;
	public $date_publication;
	public $date_publication_full;
	public $date_retrait;
	public $date_retrait_full;
	public $date_fin;
	public $create_bo;
	public $risk;
	public $status;
	public $display;
	public $remb_auto;
	public $added;
	public $updated;

	
	function projects($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_project = '';
		$this->slug = '';
		$this->id_company = '';
		$this->id_partenaire = '';
		$this->id_partenaire_subcode = '';
		$this->amount = '';
		$this->status_solde = '';
		$this->period = '';
		$this->title = '';
		$this->title_bo = '';
		$this->photo_projet = '';
		$this->lien_video = '';
		$this->comments = '';
		$this->nature_project = '';
		$this->objectif_loan = '';
		$this->presentation_company = '';
		$this->means_repayment = '';
		$this->type = '';
		$this->target_rate = '';
		$this->stand_by = '';
		$this->id_analyste = '';
		$this->date_publication = '';
		$this->date_publication_full = '';
		$this->date_retrait = '';
		$this->date_retrait_full = '';
		$this->date_fin = '';
		$this->create_bo = '';
		$this->risk = '';
		$this->status = '';
		$this->display = '';
		$this->remb_auto = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_project')
	{
		$sql = 'SELECT * FROM  `projects` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_project = $record['id_project'];
			$this->slug = $record['slug'];
			$this->id_company = $record['id_company'];
			$this->id_partenaire = $record['id_partenaire'];
			$this->id_partenaire_subcode = $record['id_partenaire_subcode'];
			$this->amount = $record['amount'];
			$this->status_solde = $record['status_solde'];
			$this->period = $record['period'];
			$this->title = $record['title'];
			$this->title_bo = $record['title_bo'];
			$this->photo_projet = $record['photo_projet'];
			$this->lien_video = $record['lien_video'];
			$this->comments = $record['comments'];
			$this->nature_project = $record['nature_project'];
			$this->objectif_loan = $record['objectif_loan'];
			$this->presentation_company = $record['presentation_company'];
			$this->means_repayment = $record['means_repayment'];
			$this->type = $record['type'];
			$this->target_rate = $record['target_rate'];
			$this->stand_by = $record['stand_by'];
			$this->id_analyste = $record['id_analyste'];
			$this->date_publication = $record['date_publication'];
			$this->date_publication_full = $record['date_publication_full'];
			$this->date_retrait = $record['date_retrait'];
			$this->date_retrait_full = $record['date_retrait_full'];
			$this->date_fin = $record['date_fin'];
			$this->create_bo = $record['create_bo'];
			$this->risk = $record['risk'];
			$this->status = $record['status'];
			$this->display = $record['display'];
			$this->remb_auto = $record['remb_auto'];
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
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->slug = $this->bdd->escape_string($this->slug);
		$this->id_company = $this->bdd->escape_string($this->id_company);
		$this->id_partenaire = $this->bdd->escape_string($this->id_partenaire);
		$this->id_partenaire_subcode = $this->bdd->escape_string($this->id_partenaire_subcode);
		$this->amount = $this->bdd->escape_string($this->amount);
		$this->status_solde = $this->bdd->escape_string($this->status_solde);
		$this->period = $this->bdd->escape_string($this->period);
		$this->title = $this->bdd->escape_string($this->title);
		$this->title_bo = $this->bdd->escape_string($this->title_bo);
		$this->photo_projet = $this->bdd->escape_string($this->photo_projet);
		$this->lien_video = $this->bdd->escape_string($this->lien_video);
		$this->comments = $this->bdd->escape_string($this->comments);
		$this->nature_project = $this->bdd->escape_string($this->nature_project);
		$this->objectif_loan = $this->bdd->escape_string($this->objectif_loan);
		$this->presentation_company = $this->bdd->escape_string($this->presentation_company);
		$this->means_repayment = $this->bdd->escape_string($this->means_repayment);
		$this->type = $this->bdd->escape_string($this->type);
		$this->target_rate = $this->bdd->escape_string($this->target_rate);
		$this->stand_by = $this->bdd->escape_string($this->stand_by);
		$this->id_analyste = $this->bdd->escape_string($this->id_analyste);
		$this->date_publication = $this->bdd->escape_string($this->date_publication);
		$this->date_publication_full = $this->bdd->escape_string($this->date_publication_full);
		$this->date_retrait = $this->bdd->escape_string($this->date_retrait);
		$this->date_retrait_full = $this->bdd->escape_string($this->date_retrait_full);
		$this->date_fin = $this->bdd->escape_string($this->date_fin);
		$this->create_bo = $this->bdd->escape_string($this->create_bo);
		$this->risk = $this->bdd->escape_string($this->risk);
		$this->status = $this->bdd->escape_string($this->status);
		$this->display = $this->bdd->escape_string($this->display);
		$this->remb_auto = $this->bdd->escape_string($this->remb_auto);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `projects` SET `slug`="'.$this->slug.'",`id_company`="'.$this->id_company.'",`id_partenaire`="'.$this->id_partenaire.'",`id_partenaire_subcode`="'.$this->id_partenaire_subcode.'",`amount`="'.$this->amount.'",`status_solde`="'.$this->status_solde.'",`period`="'.$this->period.'",`title`="'.$this->title.'",`title_bo`="'.$this->title_bo.'",`photo_projet`="'.$this->photo_projet.'",`lien_video`="'.$this->lien_video.'",`comments`="'.$this->comments.'",`nature_project`="'.$this->nature_project.'",`objectif_loan`="'.$this->objectif_loan.'",`presentation_company`="'.$this->presentation_company.'",`means_repayment`="'.$this->means_repayment.'",`type`="'.$this->type.'",`target_rate`="'.$this->target_rate.'",`stand_by`="'.$this->stand_by.'",`id_analyste`="'.$this->id_analyste.'",`date_publication`="'.$this->date_publication.'",`date_publication_full`="'.$this->date_publication_full.'",`date_retrait`="'.$this->date_retrait.'",`date_retrait_full`="'.$this->date_retrait_full.'",`date_fin`="'.$this->date_fin.'",`create_bo`="'.$this->create_bo.'",`risk`="'.$this->risk.'",`status`="'.$this->status.'",`display`="'.$this->display.'",`remb_auto`="'.$this->remb_auto.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_project="'.$this->id_project.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	$this->bdd->controlSlug('projects',$this->slug,'id_project',$this->id_project);
		}
		else
		{
	$this->bdd->controlSlugMultiLn('projects',$this->slug,$this->id_project,$list_field_value,$this->id_langue);	
		}
		
		$this->get($this->id_project,'id_project');
	}
	
	function delete($id,$field='id_project')
	{
		if($id=='')
			$id = $this->id_project;
		$sql = 'DELETE FROM `projects` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->slug = $this->bdd->escape_string($this->slug);
		$this->id_company = $this->bdd->escape_string($this->id_company);
		$this->id_partenaire = $this->bdd->escape_string($this->id_partenaire);
		$this->id_partenaire_subcode = $this->bdd->escape_string($this->id_partenaire_subcode);
		$this->amount = $this->bdd->escape_string($this->amount);
		$this->status_solde = $this->bdd->escape_string($this->status_solde);
		$this->period = $this->bdd->escape_string($this->period);
		$this->title = $this->bdd->escape_string($this->title);
		$this->title_bo = $this->bdd->escape_string($this->title_bo);
		$this->photo_projet = $this->bdd->escape_string($this->photo_projet);
		$this->lien_video = $this->bdd->escape_string($this->lien_video);
		$this->comments = $this->bdd->escape_string($this->comments);
		$this->nature_project = $this->bdd->escape_string($this->nature_project);
		$this->objectif_loan = $this->bdd->escape_string($this->objectif_loan);
		$this->presentation_company = $this->bdd->escape_string($this->presentation_company);
		$this->means_repayment = $this->bdd->escape_string($this->means_repayment);
		$this->type = $this->bdd->escape_string($this->type);
		$this->target_rate = $this->bdd->escape_string($this->target_rate);
		$this->stand_by = $this->bdd->escape_string($this->stand_by);
		$this->id_analyste = $this->bdd->escape_string($this->id_analyste);
		$this->date_publication = $this->bdd->escape_string($this->date_publication);
		$this->date_publication_full = $this->bdd->escape_string($this->date_publication_full);
		$this->date_retrait = $this->bdd->escape_string($this->date_retrait);
		$this->date_retrait_full = $this->bdd->escape_string($this->date_retrait_full);
		$this->date_fin = $this->bdd->escape_string($this->date_fin);
		$this->create_bo = $this->bdd->escape_string($this->create_bo);
		$this->risk = $this->bdd->escape_string($this->risk);
		$this->status = $this->bdd->escape_string($this->status);
		$this->display = $this->bdd->escape_string($this->display);
		$this->remb_auto = $this->bdd->escape_string($this->remb_auto);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `projects`(`slug`,`id_company`,`id_partenaire`,`id_partenaire_subcode`,`amount`,`status_solde`,`period`,`title`,`title_bo`,`photo_projet`,`lien_video`,`comments`,`nature_project`,`objectif_loan`,`presentation_company`,`means_repayment`,`type`,`target_rate`,`stand_by`,`id_analyste`,`date_publication`,`date_publication_full`,`date_retrait`,`date_retrait_full`,`date_fin`,`create_bo`,`risk`,`status`,`display`,`remb_auto`,`added`,`updated`) VALUES("'.$this->slug.'","'.$this->id_company.'","'.$this->id_partenaire.'","'.$this->id_partenaire_subcode.'","'.$this->amount.'","'.$this->status_solde.'","'.$this->period.'","'.$this->title.'","'.$this->title_bo.'","'.$this->photo_projet.'","'.$this->lien_video.'","'.$this->comments.'","'.$this->nature_project.'","'.$this->objectif_loan.'","'.$this->presentation_company.'","'.$this->means_repayment.'","'.$this->type.'","'.$this->target_rate.'","'.$this->stand_by.'","'.$this->id_analyste.'","'.$this->date_publication.'","'.$this->date_publication_full.'","'.$this->date_retrait.'","'.$this->date_retrait_full.'","'.$this->date_fin.'","'.$this->create_bo.'","'.$this->risk.'","'.$this->status.'","'.$this->display.'","'.$this->remb_auto.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_project = $this->bdd->insert_id();
		
		if($cs=='')
		{
	$this->bdd->controlSlug('projects',$this->slug,'id_project',$this->id_project);
		}
		else
		{
	$this->bdd->controlSlugMultiLn('projects',$this->slug,$this->id_project,$list_field_value,$this->id_langue);	
		}
		
		$this->get($this->id_project,'id_project');
		
		return $this->id_project;
	}
	
	function unsetData()
	{
		$this->id_project = '';
		$this->slug = '';
		$this->id_company = '';
		$this->id_partenaire = '';
		$this->id_partenaire_subcode = '';
		$this->amount = '';
		$this->status_solde = '';
		$this->period = '';
		$this->title = '';
		$this->title_bo = '';
		$this->photo_projet = '';
		$this->lien_video = '';
		$this->comments = '';
		$this->nature_project = '';
		$this->objectif_loan = '';
		$this->presentation_company = '';
		$this->means_repayment = '';
		$this->type = '';
		$this->target_rate = '';
		$this->stand_by = '';
		$this->id_analyste = '';
		$this->date_publication = '';
		$this->date_publication_full = '';
		$this->date_retrait = '';
		$this->date_retrait_full = '';
		$this->date_fin = '';
		$this->create_bo = '';
		$this->risk = '';
		$this->status = '';
		$this->display = '';
		$this->remb_auto = '';
		$this->added = '';
		$this->updated = '';

	}
}