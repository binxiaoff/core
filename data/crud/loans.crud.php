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
class loans_crud
{
	
	public $id_loan;
	public $id_bid;
	public $id_lender;
	public $id_project;
	public $id_partenaire;
	public $id_partenaire_subcode;
	public $id_country_juridiction;
	public $type;
	public $number_of_terms;
	public $amount;
	public $rate;
	public $status;
	public $en_attente_mail_rejet_envoye;
	public $fichier_declarationContratPret;
	public $added;
	public $updated;

	
	function loans($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_loan = '';
		$this->id_bid = '';
		$this->id_lender = '';
		$this->id_project = '';
		$this->id_partenaire = '';
		$this->id_partenaire_subcode = '';
		$this->id_country_juridiction = '';
		$this->type = '';
		$this->number_of_terms = '';
		$this->amount = '';
		$this->rate = '';
		$this->status = '';
		$this->en_attente_mail_rejet_envoye = '';
		$this->fichier_declarationContratPret = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_loan')
	{
		$sql = 'SELECT * FROM  `loans` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_loan = $record['id_loan'];
			$this->id_bid = $record['id_bid'];
			$this->id_lender = $record['id_lender'];
			$this->id_project = $record['id_project'];
			$this->id_partenaire = $record['id_partenaire'];
			$this->id_partenaire_subcode = $record['id_partenaire_subcode'];
			$this->id_country_juridiction = $record['id_country_juridiction'];
			$this->type = $record['type'];
			$this->number_of_terms = $record['number_of_terms'];
			$this->amount = $record['amount'];
			$this->rate = $record['rate'];
			$this->status = $record['status'];
			$this->en_attente_mail_rejet_envoye = $record['en_attente_mail_rejet_envoye'];
			$this->fichier_declarationContratPret = $record['fichier_declarationContratPret'];
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
		$this->id_loan = $this->bdd->escape_string($this->id_loan);
		$this->id_bid = $this->bdd->escape_string($this->id_bid);
		$this->id_lender = $this->bdd->escape_string($this->id_lender);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->id_partenaire = $this->bdd->escape_string($this->id_partenaire);
		$this->id_partenaire_subcode = $this->bdd->escape_string($this->id_partenaire_subcode);
		$this->id_country_juridiction = $this->bdd->escape_string($this->id_country_juridiction);
		$this->type = $this->bdd->escape_string($this->type);
		$this->number_of_terms = $this->bdd->escape_string($this->number_of_terms);
		$this->amount = $this->bdd->escape_string($this->amount);
		$this->rate = $this->bdd->escape_string($this->rate);
		$this->status = $this->bdd->escape_string($this->status);
		$this->en_attente_mail_rejet_envoye = $this->bdd->escape_string($this->en_attente_mail_rejet_envoye);
		$this->fichier_declarationContratPret = $this->bdd->escape_string($this->fichier_declarationContratPret);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `loans` SET `id_bid`="'.$this->id_bid.'",`id_lender`="'.$this->id_lender.'",`id_project`="'.$this->id_project.'",`id_partenaire`="'.$this->id_partenaire.'",`id_partenaire_subcode`="'.$this->id_partenaire_subcode.'",`id_country_juridiction`="'.$this->id_country_juridiction.'",`type`="'.$this->type.'",`number_of_terms`="'.$this->number_of_terms.'",`amount`="'.$this->amount.'",`rate`="'.$this->rate.'",`status`="'.$this->status.'",`en_attente_mail_rejet_envoye`="'.$this->en_attente_mail_rejet_envoye.'",`fichier_declarationContratPret`="'.$this->fichier_declarationContratPret.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_loan="'.$this->id_loan.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_loan,'id_loan');
	}
	
	function delete($id,$field='id_loan')
	{
		if($id=='')
			$id = $this->id_loan;
		$sql = 'DELETE FROM `loans` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_loan = $this->bdd->escape_string($this->id_loan);
		$this->id_bid = $this->bdd->escape_string($this->id_bid);
		$this->id_lender = $this->bdd->escape_string($this->id_lender);
		$this->id_project = $this->bdd->escape_string($this->id_project);
		$this->id_partenaire = $this->bdd->escape_string($this->id_partenaire);
		$this->id_partenaire_subcode = $this->bdd->escape_string($this->id_partenaire_subcode);
		$this->id_country_juridiction = $this->bdd->escape_string($this->id_country_juridiction);
		$this->type = $this->bdd->escape_string($this->type);
		$this->number_of_terms = $this->bdd->escape_string($this->number_of_terms);
		$this->amount = $this->bdd->escape_string($this->amount);
		$this->rate = $this->bdd->escape_string($this->rate);
		$this->status = $this->bdd->escape_string($this->status);
		$this->en_attente_mail_rejet_envoye = $this->bdd->escape_string($this->en_attente_mail_rejet_envoye);
		$this->fichier_declarationContratPret = $this->bdd->escape_string($this->fichier_declarationContratPret);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `loans`(`id_bid`,`id_lender`,`id_project`,`id_partenaire`,`id_partenaire_subcode`,`id_country_juridiction`,`type`,`number_of_terms`,`amount`,`rate`,`status`,`en_attente_mail_rejet_envoye`,`fichier_declarationContratPret`,`added`,`updated`) VALUES("'.$this->id_bid.'","'.$this->id_lender.'","'.$this->id_project.'","'.$this->id_partenaire.'","'.$this->id_partenaire_subcode.'","'.$this->id_country_juridiction.'","'.$this->type.'","'.$this->number_of_terms.'","'.$this->amount.'","'.$this->rate.'","'.$this->status.'","'.$this->en_attente_mail_rejet_envoye.'","'.$this->fichier_declarationContratPret.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_loan = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_loan,'id_loan');
		
		return $this->id_loan;
	}
	
	function unsetData()
	{
		$this->id_loan = '';
		$this->id_bid = '';
		$this->id_lender = '';
		$this->id_project = '';
		$this->id_partenaire = '';
		$this->id_partenaire_subcode = '';
		$this->id_country_juridiction = '';
		$this->type = '';
		$this->number_of_terms = '';
		$this->amount = '';
		$this->rate = '';
		$this->status = '';
		$this->en_attente_mail_rejet_envoye = '';
		$this->fichier_declarationContratPret = '';
		$this->added = '';
		$this->updated = '';

	}
}