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
class lenders_accounts_crud
{
	
	public $id_lender_account;
	public $id_client_owner;
	public $id_company_owner;
	public $exonere;
	public $debut_exoneration;
	public $fin_exoneration;
	public $iban;
	public $bic;
	public $origine_des_fonds;
	public $precision;
	public $id_partenaire;
	public $id_partenaire_subcode;
	public $status;
	public $type_transfert;
	public $motif;
	public $fonds;
	public $cni_passeport;
	public $added;
	public $updated;

	
	function lenders_accounts($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_lender_account = '';
		$this->id_client_owner = '';
		$this->id_company_owner = '';
		$this->exonere = '';
		$this->debut_exoneration = '';
		$this->fin_exoneration = '';
		$this->iban = '';
		$this->bic = '';
		$this->origine_des_fonds = '';
		$this->precision = '';
		$this->id_partenaire = '';
		$this->id_partenaire_subcode = '';
		$this->status = '';
		$this->type_transfert = '';
		$this->motif = '';
		$this->fonds = '';
		$this->cni_passeport = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_lender_account')
	{
		$sql = 'SELECT * FROM  `lenders_accounts` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_lender_account = $record['id_lender_account'];
			$this->id_client_owner = $record['id_client_owner'];
			$this->id_company_owner = $record['id_company_owner'];
			$this->exonere = $record['exonere'];
			$this->debut_exoneration = $record['debut_exoneration'];
			$this->fin_exoneration = $record['fin_exoneration'];
			$this->iban = $record['iban'];
			$this->bic = $record['bic'];
			$this->origine_des_fonds = $record['origine_des_fonds'];
			$this->precision = $record['precision'];
			$this->id_partenaire = $record['id_partenaire'];
			$this->id_partenaire_subcode = $record['id_partenaire_subcode'];
			$this->status = $record['status'];
			$this->type_transfert = $record['type_transfert'];
			$this->motif = $record['motif'];
			$this->fonds = $record['fonds'];
			$this->cni_passeport = $record['cni_passeport'];
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
		$this->id_lender_account = $this->bdd->escape_string($this->id_lender_account);
		$this->id_client_owner = $this->bdd->escape_string($this->id_client_owner);
		$this->id_company_owner = $this->bdd->escape_string($this->id_company_owner);
		$this->exonere = $this->bdd->escape_string($this->exonere);
		$this->debut_exoneration = $this->bdd->escape_string($this->debut_exoneration);
		$this->fin_exoneration = $this->bdd->escape_string($this->fin_exoneration);
		$this->iban = $this->bdd->escape_string($this->iban);
		$this->bic = $this->bdd->escape_string($this->bic);
		$this->origine_des_fonds = $this->bdd->escape_string($this->origine_des_fonds);
		$this->precision = $this->bdd->escape_string($this->precision);
		$this->id_partenaire = $this->bdd->escape_string($this->id_partenaire);
		$this->id_partenaire_subcode = $this->bdd->escape_string($this->id_partenaire_subcode);
		$this->status = $this->bdd->escape_string($this->status);
		$this->type_transfert = $this->bdd->escape_string($this->type_transfert);
		$this->motif = $this->bdd->escape_string($this->motif);
		$this->fonds = $this->bdd->escape_string($this->fonds);
		$this->cni_passeport = $this->bdd->escape_string($this->cni_passeport);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `lenders_accounts` SET `id_client_owner`="'.$this->id_client_owner.'",`id_company_owner`="'.$this->id_company_owner.'",`exonere`="'.$this->exonere.'",`debut_exoneration`="'.$this->debut_exoneration.'",`fin_exoneration`="'.$this->fin_exoneration.'",`iban`="'.$this->iban.'",`bic`="'.$this->bic.'",`origine_des_fonds`="'.$this->origine_des_fonds.'",`precision`="'.$this->precision.'",`id_partenaire`="'.$this->id_partenaire.'",`id_partenaire_subcode`="'.$this->id_partenaire_subcode.'",`status`="'.$this->status.'",`type_transfert`="'.$this->type_transfert.'",`motif`="'.$this->motif.'",`fonds`="'.$this->fonds.'",`cni_passeport`="'.$this->cni_passeport.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_lender_account="'.$this->id_lender_account.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_lender_account,'id_lender_account');
	}
	
	function delete($id,$field='id_lender_account')
	{
		if($id=='')
			$id = $this->id_lender_account;
		$sql = 'DELETE FROM `lenders_accounts` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_lender_account = $this->bdd->escape_string($this->id_lender_account);
		$this->id_client_owner = $this->bdd->escape_string($this->id_client_owner);
		$this->id_company_owner = $this->bdd->escape_string($this->id_company_owner);
		$this->exonere = $this->bdd->escape_string($this->exonere);
		$this->debut_exoneration = $this->bdd->escape_string($this->debut_exoneration);
		$this->fin_exoneration = $this->bdd->escape_string($this->fin_exoneration);
		$this->iban = $this->bdd->escape_string($this->iban);
		$this->bic = $this->bdd->escape_string($this->bic);
		$this->origine_des_fonds = $this->bdd->escape_string($this->origine_des_fonds);
		$this->precision = $this->bdd->escape_string($this->precision);
		$this->id_partenaire = $this->bdd->escape_string($this->id_partenaire);
		$this->id_partenaire_subcode = $this->bdd->escape_string($this->id_partenaire_subcode);
		$this->status = $this->bdd->escape_string($this->status);
		$this->type_transfert = $this->bdd->escape_string($this->type_transfert);
		$this->motif = $this->bdd->escape_string($this->motif);
		$this->fonds = $this->bdd->escape_string($this->fonds);
		$this->cni_passeport = $this->bdd->escape_string($this->cni_passeport);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `lenders_accounts`(`id_client_owner`,`id_company_owner`,`exonere`,`debut_exoneration`,`fin_exoneration`,`iban`,`bic`,`origine_des_fonds`,`precision`,`id_partenaire`,`id_partenaire_subcode`,`status`,`type_transfert`,`motif`,`fonds`,`cni_passeport`,`added`,`updated`) VALUES("'.$this->id_client_owner.'","'.$this->id_company_owner.'","'.$this->exonere.'","'.$this->debut_exoneration.'","'.$this->fin_exoneration.'","'.$this->iban.'","'.$this->bic.'","'.$this->origine_des_fonds.'","'.$this->precision.'","'.$this->id_partenaire.'","'.$this->id_partenaire_subcode.'","'.$this->status.'","'.$this->type_transfert.'","'.$this->motif.'","'.$this->fonds.'","'.$this->cni_passeport.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_lender_account = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_lender_account,'id_lender_account');
		
		return $this->id_lender_account;
	}
	
	function unsetData()
	{
		$this->id_lender_account = '';
		$this->id_client_owner = '';
		$this->id_company_owner = '';
		$this->exonere = '';
		$this->debut_exoneration = '';
		$this->fin_exoneration = '';
		$this->iban = '';
		$this->bic = '';
		$this->origine_des_fonds = '';
		$this->precision = '';
		$this->id_partenaire = '';
		$this->id_partenaire_subcode = '';
		$this->status = '';
		$this->type_transfert = '';
		$this->motif = '';
		$this->fonds = '';
		$this->cni_passeport = '';
		$this->added = '';
		$this->updated = '';

	}
}