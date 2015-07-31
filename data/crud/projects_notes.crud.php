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
class projects_notes_crud
{
	
	public $id_project_notes;
	public $id_project;
	public $performance_fianciere;
	public $structure;
	public $rentabilite;
	public $tresorerie;
	public $marche_opere;
	public $global;
	public $individuel;
	public $qualite_moyen_infos_financieres;
	public $notation_externe;
	public $avis;
	public $note;
	public $avis_comite;
	public $added;
	public $updated;

	
	function projects_notes($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_project_notes = '';
		$this->id_project = '';
		$this->performance_fianciere = '';
		$this->structure = '';
		$this->rentabilite = '';
		$this->tresorerie = '';
		$this->marche_opere = '';
		$this->global = '';
		$this->individuel = '';
		$this->qualite_moyen_infos_financieres = '';
		$this->notation_externe = '';
		$this->avis = '';
		$this->note = '';
		$this->avis_comite = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_project_notes')
	{
		$sql = 'SELECT * FROM  `projects_notes` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_project_notes = $record['id_project_notes'];
			$this->id_project = $record['id_project'];
			$this->performance_fianciere = $record['performance_fianciere'];
			$this->structure = $record['structure'];
			$this->rentabilite = $record['rentabilite'];
			$this->tresorerie = $record['tresorerie'];
			$this->marche_opere = $record['marche_opere'];
			$this->global = $record['global'];
			$this->individuel = $record['individuel'];
			$this->qualite_moyen_infos_financieres = $record['qualite_moyen_infos_financieres'];
			$this->notation_externe = $record['notation_externe'];
			$this->avis = $record['avis'];
			$this->note = $record['note'];
			$this->avis_comite = $record['avis_comite'];
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
		$this->id_project_notes = $this->bdd->escape_string($this->id_project_notes);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->performance_fianciere = $this->bdd->escape_string($this->performance_fianciere);
		$this->structure = $this->bdd->escape_string($this->structure);
		$this->rentabilite = $this->bdd->escape_string($this->rentabilite);
		$this->tresorerie = $this->bdd->escape_string($this->tresorerie);
		$this->marche_opere = $this->bdd->escape_string($this->marche_opere);
		$this->global = $this->bdd->escape_string($this->global);
		$this->individuel = $this->bdd->escape_string($this->individuel);
		$this->qualite_moyen_infos_financieres = $this->bdd->escape_string($this->qualite_moyen_infos_financieres);
		$this->notation_externe = $this->bdd->escape_string($this->notation_externe);
		$this->avis = $this->bdd->escape_string($this->avis);
		$this->note = $this->bdd->escape_string($this->note);
		$this->avis_comite = $this->bdd->escape_string($this->avis_comite);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `projects_notes` SET `id_project`="'.$this->id_project.'",`performance_fianciere`="'.$this->performance_fianciere.'",`structure`="'.$this->structure.'",`rentabilite`="'.$this->rentabilite.'",`tresorerie`="'.$this->tresorerie.'",`marche_opere`="'.$this->marche_opere.'",`global`="'.$this->global.'",`individuel`="'.$this->individuel.'",`qualite_moyen_infos_financieres`="'.$this->qualite_moyen_infos_financieres.'",`notation_externe`="'.$this->notation_externe.'",`avis`="'.$this->avis.'",`note`="'.$this->note.'",`avis_comite`="'.$this->avis_comite.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_project_notes="'.$this->id_project_notes.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_project_notes,'id_project_notes');
	}
	
	function delete($id,$field='id_project_notes')
	{
		if($id=='')
			$id = $this->id_project_notes;
		$sql = 'DELETE FROM `projects_notes` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_project_notes = $this->bdd->escape_string($this->id_project_notes);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->performance_fianciere = $this->bdd->escape_string($this->performance_fianciere);
		$this->structure = $this->bdd->escape_string($this->structure);
		$this->rentabilite = $this->bdd->escape_string($this->rentabilite);
		$this->tresorerie = $this->bdd->escape_string($this->tresorerie);
		$this->marche_opere = $this->bdd->escape_string($this->marche_opere);
		$this->global = $this->bdd->escape_string($this->global);
		$this->individuel = $this->bdd->escape_string($this->individuel);
		$this->qualite_moyen_infos_financieres = $this->bdd->escape_string($this->qualite_moyen_infos_financieres);
		$this->notation_externe = $this->bdd->escape_string($this->notation_externe);
		$this->avis = $this->bdd->escape_string($this->avis);
		$this->note = $this->bdd->escape_string($this->note);
		$this->avis_comite = $this->bdd->escape_string($this->avis_comite);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `projects_notes`(`id_project`,`performance_fianciere`,`structure`,`rentabilite`,`tresorerie`,`marche_opere`,`global`,`individuel`,`qualite_moyen_infos_financieres`,`notation_externe`,`avis`,`note`,`avis_comite`,`added`,`updated`) VALUES("'.$this->id_project.'","'.$this->performance_fianciere.'","'.$this->structure.'","'.$this->rentabilite.'","'.$this->tresorerie.'","'.$this->marche_opere.'","'.$this->global.'","'.$this->individuel.'","'.$this->qualite_moyen_infos_financieres.'","'.$this->notation_externe.'","'.$this->avis.'","'.$this->note.'","'.$this->avis_comite.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_project_notes = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_project_notes,'id_project_notes');
		
		return $this->id_project_notes;
	}
	
	function unsetData()
	{
		$this->id_project_notes = '';
		$this->id_project = '';
		$this->performance_fianciere = '';
		$this->structure = '';
		$this->rentabilite = '';
		$this->tresorerie = '';
		$this->marche_opere = '';
		$this->global = '';
		$this->individuel = '';
		$this->qualite_moyen_infos_financieres = '';
		$this->notation_externe = '';
		$this->avis = '';
		$this->note = '';
		$this->avis_comite = '';
		$this->added = '';
		$this->updated = '';

	}
}