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

class bids extends bids_crud
{

	function bids($bdd,$params='')
    {
        parent::bids($bdd,$params);
    }
    
    function get($id,$field='id_bid')
    {
        return parent::get($id,$field);
    }
    
    function update($cs='')
    {
        parent::update($cs);
    }
    
    function delete($id,$field='id_bid')
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
		$sql = 'SELECT * FROM `bids`'.$where.$order.($nb!='' && $start !=''?' LIMIT '.$start.','.$nb:($nb!=''?' LIMIT '.$nb:''));

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
			
		$sql='SELECT count(*) FROM `bids` '.$where;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	function exist($id,$field='id_bid')
	{
		$sql = 'SELECT * FROM `bids` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		return ($this->bdd->fetch_array($result,0,0)>0);
	}
	
	
	function getSoldeBid($id_project)
	{
		$sql = 'SELECT SUM(amount) as solde FROM bids WHERE id_project = '.$id_project;
		
		$result = $this->bdd->query($sql);
		$solde = $this->bdd->result($result, 0, 'solde');
		if($solde == '') $solde = 0;
		else $solde = ($solde/100);
		return $solde;
	}
	
	
	
	function getAVG($id_project,$champ='amount',$status='')
	{
		if($status != '')
		{
			$status = ' AND status IN('.$status.')'; 
		}
		
		$sql = 'SELECT AVG('.$champ.') as avg FROM bids WHERE id_project = '.$id_project.$status;
		
		$result = $this->bdd->query($sql);
		$avg = $this->bdd->result($result, 0, 'avg');
		if($avg == '') $avg = 0; 
		return $avg;
	}
	
	function getAvgPreteur($id_lender,$champ='amount',$status='')
	{
		if($status != '')
		{
			$status = ' AND status IN('.$status.')'; 
		}
		
		$sql = 'SELECT AVG('.$champ.') as avg FROM bids WHERE id_lender_account = '.$id_lender.$status;
		
		$result = $this->bdd->query($sql);
		$avg = $this->bdd->result($result, 0, 'avg');
		if($avg == '') $avg = 0;
		else $avg = $avg/100;
		return $avg;
	}
	
	// tri les bids d'un projet par ordre de rate
	function triBid($id_project)
	{
		// Liste des encheres
		$lEnchere = $this->select('id_project = '.$id_project,'added ASC');
		
		$i=1;
		foreach($lEnchere as $e)
		{
			$this->get($e['id_bid'],'id_bid');
			$this->ordre = $i;
			$this->update();
			
			$i++;
		}
	}
	
	// solde des bids d'un preteur
	function getBidsEncours($id_project,$id_lender)
	{
		$nbEncours = $this->counter('id_project = '.$id_project.' AND id_lender_account = '.$id_lender.' AND status = 0');
		
		$sql = 'SELECT SUM(amount) as solde FROM bids WHERE id_project = '.$id_project.' AND id_lender_account = '.$id_lender.' AND status = 0';
		
		$result = $this->bdd->query($sql);
		$solde = $this->bdd->result($result, 0, 'solde');
		if($solde == '') $solde = 0;
		else $solde = ($solde/100);
		
		return array('solde' => $solde,'nbEncours' => $nbEncours);
	}
	
	// retournes les projets avec au moins une enchere en cours
	function getProjetAvecBid($id_lender='')
	{
		if($id_lender!='')$where = ' AND id_lender_account = '.$id_lender;
		else $where = '';
		$sql='SELECT DISTINCT id_project FROM `bids` WHERE status = 0 '.$where;
		
		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	}
	
	function projetsBidsEnCoursPreteur($id_lender)
	{
		$lBisd = $this->select('id_lender_account = '.$id_lender.' AND status = 0');
		$lesBids = '';
		$i=0;
		foreach($lBisd as $b)
		{
			$lesBids .= ($i>0?',':'').$b['id_project'];
			$i++;
		}
		return $lesBids;
	}
	
	function sumBidsEncours($id_lender)
	{

		$sql='SELECT SUM(amount) FROM `bids` WHERE id_lender_account = '.$id_lender.' AND status = 0';

		$result = $this->bdd->query($sql);
		$montant = (int)($this->bdd->result($result,0,0));
		return $montant/100;
	}
	
	function sumBidsMonth($month,$year)
	{

		$sql='SELECT SUM(amount) FROM `bids` WHERE MONTH(added) = '.$month.' AND YEAR(added) = '.$year;

		$result = $this->bdd->query($sql);
		$montant = (int)($this->bdd->result($result,0,0));
		return $montant/100;
	}
	
	function sumBidsMonthEncours($month,$year)
	{

		$sql='SELECT SUM(amount) FROM `bids` WHERE MONTH(added) = '.$month.' AND YEAR(added) = '.$year.' AND status = 0';

		$result = $this->bdd->query($sql);
		$montant = (int)($this->bdd->result($result,0,0));
		return $montant/100;
	}
	
	// sum prêtée d'un lender sur un mois
	function sumPretsByMonths($id_lender,$month,$year)
	{

		$sql='SELECT SUM(amount) FROM `bids` WHERE id_lender_account = '.$id_lender.' AND status = "1" AND LEFT(added,7) = "'.$year.'-'.$month.'"';

		$result = $this->bdd->query($sql);
		$montant = (int)($this->bdd->result($result,0,0));
		if($montant > 0) $montant = $montant/100;
		else $montant = 0;
		return $montant;
	}
	
	function sum($where='',$champ)
	{
		if($where != '')
			$where = ' WHERE '.$where;
			
		$sql='SELECT SUM('.$champ.') FROM `bids` '.$where;

		$result = $this->bdd->query($sql);
		$return = (int)($this->bdd->result($result,0,0));
		
		return $return;
	}
	
	function getNbPreteurs($id_project)
	{
		$sql='SELECT count(DISTINCT id_lender_account) FROM `bids` WHERE id_project = '.$id_project.' AND status = 0';

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
}