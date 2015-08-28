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

class lenders_accounts extends lenders_accounts_crud
{

	function lenders_accounts($bdd,$params='')
    {
        parent::lenders_accounts($bdd,$params);
    }
    
    function get($id,$field='id_lender_account')
    {
        return parent::get($id,$field);
    }
    
    function update($cs='')
    {
        parent::update($cs);
    }
    
    function delete($id,$field='id_lender_account')
    {
    	parent::delete($id,$field);
    }
    
    function create($cs='')
    {
        $id = parent::create($cs);
        return $id;
    }
	
	function select($where='',$order='',$start='',$nb='')
	{
		if($where != '')
			$where = ' WHERE '.$where;
		if($order != '')
			$order = ' ORDER BY '.$order;
		$sql = 'SELECT * FROM `lenders_accounts`'.$where.$order.($nb!='' && $start !=''?' LIMIT '.$start.','.$nb:($nb!=''?' LIMIT '.$nb:''));

		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	} 
	
	function counter($where='')
	{
		if($where != '')
			$where = ' WHERE '.$where;
			
		$sql='SELECT count(*) FROM `lenders_accounts` '.$where;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	function exist($id,$field='id_lender_account')
	{
		$sql = 'SELECT * FROM `lenders_accounts` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		return ($this->bdd->fetch_array($result,0,0)>0);
	}

	public function getValuesforTRI($lender, $status = array(projects_status::REMBOURSE, projects_status::REMBOURSEMENT, projects_status::REMBOURSEMENT_ANTICIPE)){

		if(is_array($status)){
			$statusString = implode(",", $status);
		}
		//get loans values as negativ values
		$sql = 'SELECT (l.amount *-1) FROM loans l
				LEFT JOIN projects p ON l.id_project = p.id_project
				WHERE l.id_lender = '.$lender.'
				AND ( SELECT ps.status FROM projects_status ps
				LEFT JOIN projects_status_history psh
				ON ( ps.id_project_status = psh.id_project_status )
				WHERE psh.id_project = p.id_project ORDER BY psh.added DESC LIMIT 1 )
				IN ('.$statusString.');';

		$result = $this->bdd->query($sql);
		$loansValues = array();
		while ($record = $this->bdd->fetch_array($result)) {
			$loansValues[] = intval($record["(l.amount *-1)"]);
		}

		//get echeancier values
		$sql = 'SELECT e.montant FROM echeanciers e
				LEFT JOIN projects p ON e.id_project = p.id_project
				INNER JOIN loans l ON e.id_loan = l.id_loan WHERE e.id_lender = '.$lender.'
				AND ( SELECT ps.status FROM projects_status ps
				LEFT JOIN projects_status_history psh ON ( ps.id_project_status = psh.id_project_status )
				WHERE psh.id_project = p.id_project ORDER BY psh.added DESC LIMIT 1 )
				IN ('.$statusString.');';

		$result = $this->bdd->query($sql);
		$echeancesValues = array();

		while ($record = $this->bdd->fetch_array($result)) {
			$echeancesValues[] = intval($record["montant"]);
		}

		//merge arrays into one
		$values = array_merge($loansValues, $echeancesValues);

		return $values;
	}

	public function getDatesforTRI($lender, $status = array(projects_status::REMBOURSE, projects_status::REMBOURSEMENT, projects_status::REMBOURSEMENT_ANTICIPE)){

		if(is_array($status)){
			$statusString = implode(",", $status);
		}
		//get loans dates

		$sql = 'SELECT l.added FROM loans l
				LEFT JOIN projects p ON l.id_project = p.id_project
				WHERE l.id_lender = '.$lender.'
				AND ( SELECT ps.status FROM projects_status ps
				LEFT JOIN projects_status_history psh
				ON ( ps.id_project_status = psh.id_project_status )
				WHERE psh.id_project = p.id_project ORDER BY psh.added DESC LIMIT 1 )
				IN ('.$statusString.');';

		$result = $this->bdd->query($sql);
		$loansDates = array();
		while ($record = $this->bdd->fetch_array($result)) {
			$loansDates[] = strtotime($record["added"]);
		}

		//get echeancier dates

		$sql = 'SELECT e.date_echeance FROM echeanciers e
				LEFT JOIN projects p ON e.id_project = p.id_project
				INNER JOIN loans l ON e.id_loan = l.id_loan WHERE e.id_lender = '.$lender.'
				AND ( SELECT ps.status FROM projects_status ps
				LEFT JOIN projects_status_history psh ON ( ps.id_project_status = psh.id_project_status )
				WHERE psh.id_project = p.id_project ORDER BY psh.added DESC LIMIT 1 )
				IN ('.$statusString.');';

		$result = $this->bdd->query($sql);
		$echeancesDates = array();

		while ($record = $this->bdd->fetch_array($result)) {
			$echeancesDates[] = strtotime($record["added"]);
		}

		$dates = array_merge($loansDates, $echeancesDates);

		return $dates;

	}




}