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

class loans extends loans_crud
{

	function loans($bdd,$params='')
    {
        parent::loans($bdd,$params);
    }
    
    function get($id,$field='id_loan')
    {
        return parent::get($id,$field);
    }
    
    function update($cs='')
    {
        parent::update($cs);
    }
    
    function delete($id,$field='id_loan')
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
		$sql = 'SELECT * FROM `loans`'.$where.$order.($nb!='' && $start !=''?' LIMIT '.$start.','.$nb:($nb!=''?' LIMIT '.$nb:''));

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
			
		$sql='SELECT count(*) FROM `loans` '.$where;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	function exist($id,$field='id_loan')
	{
		$sql = 'SELECT * FROM `loans` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		return ($this->bdd->fetch_array($result,0,0)>0);
	}
	
	function getBidsValid($id_project,$id_lender)
	{
		$nbValid = $this->counter('id_project = '.$id_project.' AND id_lender = '.$id_lender.' AND status = 0');
		
		$sql = 'SELECT SUM(amount) as solde FROM loans WHERE id_project = '.$id_project.' AND id_lender = '.$id_lender.' AND status = 0';
		
		$result = $this->bdd->query($sql);
		$solde = $this->bdd->result($result, 0, 'solde');
		if($solde == '') $solde = 0;
		else $solde = ($solde/100);
		
		return array('solde' => $solde,'nbValid' => $nbValid);
	}
	
	function getNbPreteurs($id_project)
	{
		$sql='SELECT count(DISTINCT id_lender) FROM `loans` WHERE id_project = '.$id_project.' AND status = 0';

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	function getPreteurs($id_project)
	{
		$sql='SELECT DISTINCT id_lender FROM `loans` WHERE id_project = '.$id_project.' AND status = 0';

		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	}
	
	function getNbPprojet($id_lender)
	{
		$sql='SELECT count(DISTINCT id_project) FROM `loans` WHERE id_lender = '.$id_lender.' AND status = 0';

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	// retourne la moyenne des prets validés d'un projet
	function getAvgLoans($id_project,$champ='amount')
	{
		$sql = 'SELECT AVG('.$champ.') as avg FROM loans WHERE id_project = '.$id_project.' AND status = 0';
		
		$result = $this->bdd->query($sql);
		$avg = $this->bdd->result($result, 0, 'avg');
		if($avg == '') $avg = 0;
		
		return $avg;
	}
	
	// retourne la moyenne des prets validés d'un preteur sur un projet 
	function getAvgLoansPreteur($id_project,$id_lender,$champ='amount')
	{
		$sql = 'SELECT AVG('.$champ.') as avg FROM loans WHERE id_project = '.$id_project.' AND id_lender = '.$id_lender.' AND status = 0';
		
		$result = $this->bdd->query($sql);
		$avg = $this->bdd->result($result, 0, 'avg');
		if($avg == '') $avg = 0;
		
		return $avg;
	}
	
	// retourne la moyenne des prets validés d'un preteur
	function getAvgPrets($id_lender)
	{
		$sql = 'SELECT AVG(rate) as avg FROM loans WHERE id_lender = '.$id_lender.' AND status = 0';
		
		$result = $this->bdd->query($sql);
		$avg = $this->bdd->result($result, 0, 'avg');
		if($avg == '') $avg = 0;
		
		return $avg;
	}
	
	// sum prêtée d'un lender
	function sumPrets($id_lender)
	{

		$sql='SELECT SUM(amount) FROM `loans` WHERE id_lender = '.$id_lender.' AND status = "0"';

		$result = $this->bdd->query($sql);
		$montant = (int)($this->bdd->result($result,0,0));
		if($montant > 0) $montant = $montant/100;
		else $montant = 0;
		return $montant;
	}
	
	// sum prêtée d'un lender sur un mois
	function sumPretsByMonths($id_lender,$month,$year)
	{

		$sql='SELECT SUM(amount) FROM `loans` WHERE id_lender = '.$id_lender.' AND status = "0" AND LEFT(added,7) = "'.$year.'-'.$month.'"';

		$result = $this->bdd->query($sql);
		$montant = (int)($this->bdd->result($result,0,0));
		if($montant > 0) $montant = $montant/100;
		else $montant = 0;
		return $montant;
	}
	
	// sum prêtée d'un du projet
	function sumPretsProjet($id_project)
	{

		$sql='SELECT SUM(amount) FROM `loans` WHERE id_project = '.$id_project;

		$result = $this->bdd->query($sql);
		$montant = (int)($this->bdd->result($result,0,0));
		if($montant > 0) $montant = $montant/100;
		else $montant = 0;
		return $montant;
	}
	
	function sumPretsByProject($id_lender,$year,$order='')
	{
		if($order != '')$order = ' ORDER BY '.$order;
		
		$sql='SELECT SUM(amount) as montant,AVG(rate) as rate, id_project FROM `loans` WHERE id_lender = '.$id_lender.' AND YEAR(added) = '.$year.' AND status = 0 GROUP BY id_project'.$order;

		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	}
	
	
	function getSumPretsByMonths($id_lender,$year)
	{
		$sql = 'SELECT SUM(amount/100) AS montant, LEFT(added,7) AS date FROM loans WHERE YEAR(added) = '.$year.' AND id_lender = '.$id_lender.' AND status = 0 GROUP BY LEFT(added,7)';
		$req = $this->bdd->query($sql);
		$res = array();
		while($rec = $this->bdd->fetch_array($req))
        {
			$d = explode('-',$rec['date']);
            $res[$d[1]] = $rec['montant'];
        }
		return $res;
	}
	
	function sumLoansbyDay($date)
	{

		$sql='SELECT SUM(amount) FROM `loans` WHERE LEFT(added,10) = "'.$date.'" AND status = 0';

		$result = $this->bdd->query($sql);
		$montant = (int)($this->bdd->result($result,0,0));
		return $montant/100;
	}
	
	function sum($where='',$champ)
	{
		if($where != '')
			$where = ' WHERE '.$where;
			
		$sql='SELECT SUM('.$champ.') FROM `loans` '.$where;

		$result = $this->bdd->query($sql);
		$return = (int)($this->bdd->result($result,0,0));
		
		return $return;
	}
	
	// On recup les projet dont le preteur a un loan valide
	function getProjectsPreteurLoans($id_lender)
	{

		$sql='
			SELECT 
				l.id_project,
				p.title
			FROM loans l 
			LEFT JOIN projects p ON l.id_project = p.id_project
			WHERE id_lender = '.$id_lender.' AND l.status = 0
			GROUP BY l.id_project
			ORDER BY p.title ASC';

		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	}
	
	// On recup la liste des loans d'un preteur en les regoupant par projet
	function getSumLoansByProject($id_lender,$year='',$order='')
	{
		if($order == '') $order = 'l.added DESC';
		
		if($year != '') $year = ' AND YEAR(l.added) = "'.$year.'"';
		
		$sql='
			SELECT 
				l.id_project,
				p.title,
				p.slug,
				/*c.name,*/
				p.title as name,
				c.city,
				c.zip,
				p.risk,
				SUM(ROUND(l.amount/100,2)) as amount,
				ROUND(AVG(rate),2) as rate,
				COUNT(l.id_loan) as nb_loan,
				l.id_loan as id_loan_if_one_loan,
				LEFT((SELECT e.date_echeance FROM echeanciers e WHERE e.id_loan = l.id_loan AND e.ordre = 1),10) as debut,
				LEFT((SELECT e1.date_echeance FROM echeanciers e1 WHERE e1.id_loan = l.id_loan ORDER BY e1.date_echeance DESC LIMIT 1),10) as fin,
				LEFT((SELECT e2.date_echeance FROM echeanciers e2 WHERE e2.id_loan = l.id_loan AND e2.status = 0 ORDER BY e2.date_echeance ASC LIMIT 1),10) as next_echeance,
				SUM((SELECT ((ROUND(e3.montant/100,2))-(ROUND(e3.prelevements_obligatoires+e3.retenues_source+e3.csg+e3.prelevements_sociaux+e3.contributions_additionnelles+e3.prelevements_solidarite+e3.crds,2))) FROM echeanciers e3 WHERE e3.id_loan = l.id_loan AND e3.status = 0 ORDER BY e3.date_echeance ASC LIMIT 1)) as mensuel				
			FROM loans l 
			LEFT JOIN projects p ON l.id_project = p.id_project
			LEFT JOIN companies c ON p.id_company = c.id_company
			WHERE id_lender = '.$id_lender.' AND l.status = 0 '.$year.'
			GROUP BY l.id_project
			ORDER BY '.$order;
		//mail('k1@david.equinoa.net','AJAX',$sql);
		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	}
}