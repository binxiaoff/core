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

class echeanciers extends echeanciers_crud
{

	function echeanciers($bdd,$params='')
    {
        parent::echeanciers($bdd,$params);
    }
    
    function get($id,$field='id_echeancier')
    {
        return parent::get($id,$field);
    }
    
    function update($cs='')
    {
        parent::update($cs);
    }
    
    function delete($id,$field='id_echeancier')
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
		$sql = 'SELECT * FROM `echeanciers`'.$where.$order.($nb!='' && $start !=''?' LIMIT '.$start.','.$nb:($nb!=''?' LIMIT '.$nb:''));

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
			
		$sql='SELECT count(*) FROM `echeanciers` '.$where;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	function exist($id,$field='id_echeancier')
	{
		$sql = 'SELECT * FROM `echeanciers` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		return ($this->bdd->fetch_array($result,0,0)>0);
	}
	
	// retourne la sum total d'un emprunt
	function getSum($id_loan,$champ='montant')
	{
		$sql='SELECT SUM('.$champ.') FROM `echeanciers` WHERE id_loan = '.$id_loan;

		$result = $this->bdd->query($sql);
		$sum = (int)($this->bdd->result($result,0,0));
		return ($sum/100);
	}
	
	// retourne la sum total d'un emprunt
	function sum($where,$champ='montant')
	{
		$sql='SELECT SUM('.$champ.') FROM `echeanciers` WHERE '.$where;

		$result = $this->bdd->query($sql);
		$sum = (int)($this->bdd->result($result,0,0));
		return ($sum/100);
	}
	
	// retourne la sum total d'un emprunt par année
	function getSumByAnnee($id_loan)
	{
		$sql='SELECT SUM(montant) as montant,SUM(capital) as capital, SUM(interets) as interets, LEFT(date_echeance,4) as annee FROM `echeanciers` WHERE id_loan = '.$id_loan.' GROUP BY LEFT(date_echeance,4)';

		$result = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	}
	
	// retourne la somme des echeances deja remboursé d'un preteur
	function getSumRemb($id_lender,$champ='montant')
	{
		$sql='SELECT SUM('.$champ.') FROM `echeanciers` WHERE status = 1 AND id_lender = '.$id_lender;

		$result = $this->bdd->query($sql);
		$sum = (int)($this->bdd->result($result,0,0));
		return ($sum/100);
	}
	// retourne la somme des echeances a rembourser d'un preteur
	function getSumARemb($id_lender,$champ='montant')
	{
		$sql='SELECT SUM('.$champ.') FROM `echeanciers` WHERE status = 0 AND id_lender = '.$id_lender;

		$result = $this->bdd->query($sql);
		$sum = (int)($this->bdd->result($result,0,0));
		return ($sum/100);
	}
	
	// retourne la somme des revenues fiscale des echeances deja remboursés d'un preteur
	function getSumRevenuesFiscalesRemb($id_lender)
	{
		$sql='SELECT SUM(prelevements_obligatoires) as prelevements_obligatoires,SUM(retenues_source) as retenues_source,SUM(csg) as csg,SUM(prelevements_sociaux) as prelevements_sociaux,SUM(contributions_additionnelles) as contributions_additionnelles,SUM(prelevements_solidarite) as prelevements_solidarite,SUM(crds) as crds FROM `echeanciers` WHERE status = 1 AND id_lender = '.$id_lender;

		$result = $this->bdd->query($sql);
		$retenues = 0;
		while($record = $this->bdd->fetch_array($resultat))
		{
			$retenues += $record['prelevements_obligatoires']+$record['retenues_source']+$record['csg']+$record['prelevements_sociaux']+$record['contributions_additionnelles']+$record['prelevements_solidarite']+$record['crds'];
		}
		return $retenues;
	}
	
	// retourne la somme des revenues fiscale des echeances deja remboursés d'un preteur
	function getSumRevenuesFiscalesARemb($id_lender)
	{
		$sql='SELECT SUM(prelevements_obligatoires) as prelevements_obligatoires,SUM(retenues_source) as retenues_source,SUM(csg) as csg,SUM(prelevements_sociaux) as prelevements_sociaux,SUM(contributions_additionnelles) as contributions_additionnelles,SUM(prelevements_solidarite) as prelevements_solidarite,SUM(crds) as crds FROM `echeanciers` WHERE status = 0 AND id_lender = '.$id_lender;

		$result = $this->bdd->query($sql);
		$retenues = 0;
		while($record = $this->bdd->fetch_array($resultat))
		{
			$retenues += $record['prelevements_obligatoires']+$record['retenues_source']+$record['csg']+$record['prelevements_sociaux']+$record['contributions_additionnelles']+$record['prelevements_solidarite']+$record['crds'];
		}
		return $retenues;
	}
	
	function getSumRembV2($id_lender)
	{
		$sql='SELECT SUM(montant) as montant,SUM(interets) as interets,SUM(prelevements_obligatoires) as prelevements_obligatoires,SUM(retenues_source) as retenues_source,SUM(csg) as csg,SUM(prelevements_sociaux) as prelevements_sociaux,SUM(contributions_additionnelles) as contributions_additionnelles,SUM(prelevements_solidarite) as prelevements_solidarite,SUM(crds) as crds FROM `echeanciers` WHERE status = 1 AND id_lender = '.$id_lender;

		$resultat = $this->bdd->query($sql);
		$montant = 0;
		$interets = 0;
		while($record = $this->bdd->fetch_array($resultat))
		{
			$retenues = $record['prelevements_obligatoires']+$record['retenues_source']+$record['csg']+$record['prelevements_sociaux']+$record['contributions_additionnelles']+$record['prelevements_solidarite']+$record['crds'];
			
			$lemontant =($record['montant']/100);
			$linterets = ($record['interets']/100);
			
			$montant += ($lemontant-$retenues);
			$interets += ($linterets-$retenues);
		}
		return array('montant' => $montant,'interets' => $interets);
	}
	
	// retourne la somme des echeances deja remboursé d'une enchere
	function getSumRembByloan($id_loan,$champ='montant')
	{
		$sql='SELECT SUM('.$champ.') FROM `echeanciers` WHERE status = 1 AND id_loan = '.$id_loan;

		$result = $this->bdd->query($sql);
		$sum = (int)($this->bdd->result($result,0,0));
		return ($sum/100);
	}
	
	
	
	// retourne la somme des echeances deja remboursé
	function getTotalSumRembByMonth($month,$year)
	{
		$sql='SELECT SUM(capital) FROM `echeanciers` WHERE MONTH(date_echeance_emprunteur) = '.$month.' AND YEAR(date_echeance_emprunteur) = '.$year.' AND status_emprunteur = 0';

		$result = $this->bdd->query($sql);
		$sum = (int)($this->bdd->result($result,0,0));
		return ($sum/100);
	}
	
	
	// retourne la somme des echeances deja remboursé d'un preteur par projet
	function getSumArembByProject($id_lender,$id_project,$champ='montant')
	{
		$sql='SELECT SUM('.$champ.') as montant, ordre FROM `echeanciers` WHERE id_lender = '.$id_lender.' AND id_project = '.$id_project.' GROUP BY ordre';

		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[$record['ordre']] = $record['montant'];
		}
		return $result;
	}
	
	// remboursé capital seulement (add 17/07/2015)
	function sumARembByProjectCapital($id_lender,$id_project='')
	{
		if($id_project != '') $id_project = ' AND id_project = '.$id_project;
		$sql='SELECT SUM(capital) FROM `echeanciers` WHERE status = 1 AND id_lender = '.$id_lender.$id_project;

		$result = $this->bdd->query($sql);
		$sum = (int)($this->bdd->result($result,0,0));
		return ($sum/100);
	}
	
	// retourne la somme total a rembourser a un preteur
	function getSumRestanteARembByProject($id_lender,$id_project='')
	{
		if($id_project != '') $id_project = ' AND id_project = '.$id_project;
		$sql='SELECT SUM(montant) FROM `echeanciers` WHERE status = 0 AND id_lender = '.$id_lender.$id_project;

		$result = $this->bdd->query($sql);
		$sum = (int)($this->bdd->result($result,0,0));
		return ($sum/100);
	}
	
	// retourne la somme total a rembourser a un preteur
	function getSumRestanteARembByProject_capital($where = "")
	{
		$sql='SELECT SUM(capital) FROM `echeanciers` WHERE 1=1 '.$where;

		$result = $this->bdd->query($sql);
		$sum = (int)($this->bdd->result($result,0,0));
		return ($sum/100);
	}
	// remboursé
	function sumARembByProject($id_lender,$id_project='')
	{
		if($id_project != '') $id_project = ' AND id_project = '.$id_project;
		$sql='SELECT SUM(montant) FROM `echeanciers` WHERE status = 1 AND id_lender = '.$id_lender.$id_project;

		$result = $this->bdd->query($sql);
		$sum = (int)($this->bdd->result($result,0,0));
		return ($sum/100);
	}
	
	
	// Nb period restantes
	function counterPeriodRestantes($id_lender,$id_project)
	{		
		$sql='SELECT count(DISTINCT(ordre)) FROM `echeanciers` WHERE id_lender = '.$id_lender.' AND id_project = '.$id_project.' AND status = 0';

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	// retourne la sommes des remb du prochain mois d'un preteur
	function getNextRemb($id_lender)
	{
		$laDate = mktime(0,0,0, date("m")+1, date("d"), date("Y"));
		$laDate = date('Y-m',$laDate);
		
		$sql='SELECT SUM(montant) FROM `echeanciers` WHERE status = 0 AND id_lender = '.$id_lender.' AND LEFT(date_echeance,7) = "'.$laDate.'"';

		$result = $this->bdd->query($sql);
		$sum = ($this->bdd->result($result,0,0));
		if($sum == 0) $sum = 0;
		else $sum = ($sum/100);
		return $sum;
	}
	
	// Retourne les sommes remboursées chaque mois d'un preteur sur une année
	function getSumRembByMonths($id_lender,$year)
	{
		$sql = 'SELECT SUM(montant) AS montant, LEFT(date_echeance_reel,7) AS date FROM echeanciers WHERE YEAR(date_echeance_reel) = '.$year.' AND id_lender = '.$id_lender.' AND status = 1 GROUP BY LEFT(date_echeance_reel,7)';
		$req = $this->bdd->query($sql);
		$res = array();
		while($rec = $this->bdd->fetch_array($req))
        {
			$d = explode('-',$rec['date']);
            $res[$d[1]] = ($rec['montant']>0?($rec['montant']/100):0);
        }
		return $res;
	}
	
	// Retourne les sommes remboursées chaque mois d'un preteur sur une année (capital)
	function getSumRembByMonthsCapital($id_lender,$year)
	{
		$sql = 'SELECT SUM(capital) AS capital, LEFT(date_echeance_reel,7) AS date FROM echeanciers WHERE YEAR(date_echeance_reel) = '.$year.' AND id_lender = '.$id_lender.' AND status = 1 GROUP BY LEFT(date_echeance_reel,7)';
		$req = $this->bdd->query($sql);
		$res = array();
		while($rec = $this->bdd->fetch_array($req))
        {
			$d = explode('-',$rec['date']);
            $res[$d[1]] = ($rec['capital']>0?($rec['capital']/100):0);
        }
		return $res;
	}
	
	// Retourne la somme des interets par mois d'un preteur
	function getSumIntByMonths($id_lender,$year)
	{
		$sql = 'SELECT SUM(interets) AS interets, LEFT(date_echeance_reel,7) AS date FROM echeanciers WHERE YEAR(date_echeance_reel) = '.$year.' AND id_lender = '.$id_lender.' AND status = 1 GROUP BY LEFT(date_echeance_reel,7)';
		$req = $this->bdd->query($sql);
		$res = array();
		while($rec = $this->bdd->fetch_array($req))
        {
			$d = explode('-',$rec['date']);
            $res[$d[1]] = ($rec['interets']>0?($rec['interets']/100):0);
        }
		return $res;
	}
	// modif date_echeance_reel par date_echeance 02/09/2014
	function getSumRevenuesFiscalesByMonths($id_lender,$year)
	{
		$sql='SELECT 
		SUM(prelevements_obligatoires) as prelevements_obligatoires,
		SUM(retenues_source) as retenues_source,
		SUM(csg) as csg,
		SUM(prelevements_sociaux) as prelevements_sociaux,
		SUM(contributions_additionnelles) as contributions_additionnelles,
		SUM(prelevements_solidarite) as prelevements_solidarite,
		SUM(crds) as crds,
		LEFT(date_echeance_reel,7) AS date 
		FROM `echeanciers` 
		WHERE status = 1 
		AND id_lender = '.$id_lender.' 
		AND YEAR(date_echeance_reel) = '.$year.' 
		GROUP BY LEFT(date_echeance_reel,7)';

		$result = $this->bdd->query($sql);
		$res = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$d = explode('-',$record['date']);
			$retenues = $record['prelevements_obligatoires']+$record['retenues_source']+$record['csg']+$record['prelevements_sociaux']+$record['contributions_additionnelles']+$record['prelevements_solidarite']+$record['crds'];
			$res[$d[1]] = $retenues;
		}
		return $res;
	}
	
	// Retourne les sommes remboursées chaque annee d'un preteur(capital)
	function getSumRembByYearCapital($id_lender,$debut,$fin)
	{
		$sql = 'SELECT SUM(capital) AS capital, YEAR(date_echeance_reel) AS date FROM echeanciers WHERE YEAR(date_echeance_reel) >= '.$debut.' AND YEAR(date_echeance_reel) <= '.$fin.' AND id_lender = '.$id_lender.' AND status = 1 GROUP BY YEAR(date_echeance_reel)';
		$req = $this->bdd->query($sql);
		$res = array();
		while($rec = $this->bdd->fetch_array($req))
        {
            $res[$rec['date']] = ($rec['capital']>0?($rec['capital']/100):0);
        }
		
		for($i=$debut;$i<=$fin;$i++){
			$resultat[$i] = number_format(($res[$i] != ''?$res[$i]:0),2,'.','');
		}

		return $resultat;
	}
	
	// Retourne la somme des interets par annee d'un preteur
	function getSumIntByYear($id_lender,$debut,$fin)
	{
		$sql = 'SELECT SUM(interets) AS interets, YEAR(date_echeance_reel) AS date FROM echeanciers WHERE YEAR(date_echeance_reel) >= '.$debut.' AND YEAR(date_echeance_reel) <= '.$fin.' AND id_lender = '.$id_lender.' AND status = 1 GROUP BY YEAR(date_echeance_reel)';
		$req = $this->bdd->query($sql);
		$res = array();
		while($rec = $this->bdd->fetch_array($req))
        {
            $res[$rec['date']] = ($rec['interets']>0?($rec['interets']/100):0);	
        }
		
		for($i=$debut;$i<=$fin;$i++){
			$resultat[$i] = number_format(($res[$i] != ''?$res[$i]:0),2,'.','');
		}

		return $resultat;
	}
	// prelevements fiscaux chaque annee 02/09/2014
	function getSumRevenuesFiscalesByYear($id_lender,$debut,$fin)
	{
		$sql='SELECT 
		SUM(prelevements_obligatoires) as prelevements_obligatoires,
		SUM(retenues_source) as retenues_source,
		SUM(csg) as csg,
		SUM(prelevements_sociaux) as prelevements_sociaux,
		SUM(contributions_additionnelles) as contributions_additionnelles,
		SUM(prelevements_solidarite) as prelevements_solidarite,
		SUM(crds) as crds,
		YEAR(date_echeance_reel) AS date 
		FROM `echeanciers` 
		WHERE status = 1 
		AND id_lender = '.$id_lender.' 
		AND YEAR(date_echeance_reel) >= '.$debut.' AND YEAR(date_echeance_reel) <= '.$fin.'
		GROUP BY YEAR(date_echeance_reel)';

		$result = $this->bdd->query($sql);
		$res = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$retenues = $record['prelevements_obligatoires']+$record['retenues_source']+$record['csg']+$record['prelevements_sociaux']+$record['contributions_additionnelles']+$record['prelevements_solidarite']+$record['crds'];
			$res[$record['date']] = $retenues;
		}
		for($i=$debut;$i<=$fin;$i++){
			$resultat[$i] = number_format(($res[$i] != ''?$res[$i]:0),2,'.','');
		}

		return $resultat;
	}
	
	// listes des echeance (goupe par lender et par date)
	function getEcheancesProject($id_project)
	{
		$sql = 'SELECT ordre, id_lender, status_emprunteur,status,id_project, SUM(montant) AS montant, SUM(capital) AS capital, SUM(interets) AS interets, SUM(commission) AS commission, SUM(tva) AS tva, LEFT(date_echeance_emprunteur,16) as date_echeance_emprunteur, LEFT(date_echeance,16) as date_echeance FROM echeanciers GROUP BY id_lender,ordre having id_project = '.$id_project.' order by date_echeance';
		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	}
	
	
	
	// Retourne un tableau avec les sommes des echeances par mois d'un projet
	function getSumRembEmpruntByMonths($id_project='',$ordre='',$status_emprunteur='',$month='',$year='',$order='')
	{
		if($id_project!='')
		{
			$id_project = ' AND id_project = '.$id_project;
		}
		if($ordre!='')
		{
			$ordre = ' AND ordre = '.$ordre;
		}
		if($status_emprunteur!='')
		{
			$status_emprunteur = ' AND status_emprunteur = '.$status_emprunteur;
		}
		
		if($month!='')
		{
			$month = ' AND MONTH(added) = '.$month;
		}
		if($year!='')
		{
			$year = ' AND YEAR(added) = '.$year;
		}
		if($order!='')
		{
			$order = ' ORDER BY '.$order;
		}
		
		$sql = 'SELECT ordre, status_emprunteur,id_project,id_echeancier, SUM(montant) AS montant, SUM(capital) AS capital, SUM(interets) AS interets, SUM(commission) AS commission, SUM(tva) AS tva, LEFT(date_echeance_emprunteur,16) as date_echeance_emprunteur, LEFT(date_echeance,16) as date_echeance, status_emprunteur FROM echeanciers WHERE 1 = 1 '.$id_project.$ordre.$status_emprunteur.$month.$year.' GROUP BY ordre '.$order;
		$req = $this->bdd->query($sql);
		$res = array();
		while($rec = $this->bdd->fetch_array($req))
        {
			$res[$rec['ordre']]['ordre'] = $rec['ordre'];
			$res[$rec['ordre']]['id_project'] = $rec['id_project'];
			$res[$rec['ordre']]['status_emprunteur'] = $rec['status_emprunteur'];
            $res[$rec['ordre']]['montant'] = $rec['montant']/100;
			$res[$rec['ordre']]['capital'] = $rec['capital']/100;
			$res[$rec['ordre']]['interets'] = $rec['interets']/100;
			$res[$rec['ordre']]['commission'] = $rec['commission']/100;
			$res[$rec['ordre']]['tva'] = $rec['tva']/100;
			$res[$rec['ordre']]['date_echeance_emprunteur'] = $rec['date_echeance_emprunteur'];
			$res[$rec['ordre']]['date_echeance'] = $rec['date_echeance'];
        }
		return $res;
	}
	
	// mise a jour des statuts emprunteur pour les remb d'un projet
	// id_project : projet
	// $ordre : periode de remb
	function updateStatusEmprunteur_old($id_project,$ordre,$annuler='')
	{
		$sql = 'SELECT * FROM echeanciers WHERE id_project = '.$id_project.' AND ordre = '.$ordre;
		$req = $this->bdd->query($sql);
		$res = array();
		while($rec = $this->bdd->fetch_array($req))
        {
			$this->get($rec['id_echeancier'],'id_echeancier');
			if($annuler != '')
			{
				$this->status_emprunteur = 0;
				$this->date_echeance_emprunteur_reel = '0000-00-00 00:00:00';
			}
			else
			{
				$this->status_emprunteur = 1;
				$this->date_echeance_emprunteur_reel = date('Y-m-d H:i:s');
			}
			$this->update();
		}
	}
	
	// mise a jour des statuts emprunteur pour les remb d'un projet
	// id_project : projet
	// $ordre : periode de remb
	function updateStatusEmprunteur($id_project,$ordre,$annuler='')
	{
		//$sql = 'SELECT * FROM echeanciers WHERE id_project = '.$id_project.' AND ordre = '.$ordre;
		
		if($annuler != '')
			$sql = 'UPDATE echeanciers SET status_emprunteur = 0, date_echeance_emprunteur_reel = "0000-00-00 00:00:00", updated = "'.date('Y-m-d H:i:s').'" WHERE id_project = '.$id_project.' AND ordre = '.$ordre;
		else
			$sql = 'UPDATE echeanciers SET status_emprunteur = 1, date_echeance_emprunteur_reel = "'.date('Y-m-d H:i:s').'", updated = "'.date('Y-m-d H:i:s').'" WHERE id_project = '.$id_project.' AND ordre = '.$ordre;
		
		$this->bdd->query($sql);	
	}
	
	// Montant que l'emprunteur doit rembourser
	function getMontantRembEmprunteur($montant,$commission,$tva)
	{
		return round($montant+$tva+$commission,2);
	}
	
	
	
	// somme du remb d'un emprunteur sur son projet
	function getRembTotalEmprunteur($id_project,$tva='')
	{
		$lRemb = $this->getSumRembEmpruntByMonths($id_project);
		$total = 0;
		
		
		
		foreach($lRemb as $key => $r)
		{
			// On recup le montant a remb par l'emprunteur
			$total += $this->getMontantRembEmprunteur($r['montant'],$r['commission'],$r['tva']);
			
		}
		return $total;	
	}
	
	// premiere echance emprunteur
	function getPremiereEcheancePreteur($id_project,$id_lender)
	{
		// premiere echeance
		$PremiereEcheance = $this->select('ordre = 1 AND id_project = '.$id_project.' AND id_lender = '.$id_lender,'',0,1);
		return $PremiereEcheance[0];
	}
	
	// on recup la premiere echeance d'un pret d'un preteur
	function getPremiereEcheancePreteurByLoans($id_project,$id_lender,$id_loan)
	{
		// premiere echeance
		$PremiereEcheance = $this->select('ordre = 1 AND id_project = '.$id_project.' AND id_lender = '.$id_lender.' AND id_loan = '.$id_loan,'',0,1);
		return $PremiereEcheance[0];
	}
	
	// premiere echance emprunteur
	function getDatePremiereEcheance($id_project)
	{
		// premiere echeance
		$PremiereEcheance = $this->select('ordre = 1 AND id_project = '.$id_project,'',0,1);
		return $PremiereEcheance[0]['date_echeance_emprunteur'];
	}
	function getDateDerniereEcheance($id_project)
	{
		// premiere echeance
		$derniereEcheance = $this->select('id_project = '.$id_project,'ordre DESC',0,1);
		return $derniereEcheance[0]['date_echeance_emprunteur'];
	}
	function getDateDerniereEcheancePreteur($id_project)
	{
		// premiere echeance
		$derniereEcheance = $this->select('id_project = '.$id_project,'ordre DESC',0,1);
		return $derniereEcheance[0]['date_echeance'];
	}
	
	// retourne la sommes des remb du prochain mois d'un emprunteur
	function getNextRembEmprunteur($id_project)
	{
		$sql='SELECT DISTINCT(ordre) FROM `echeanciers` WHERE status_emprunteur = 0 AND id_project = '.$id_project.' ORDER BY ordre LIMIT 0,1';
		
		$result = $this->bdd->query($sql);
		$ordre = (int)($this->bdd->result($result,0,0));
		
		$Remb = $this->getSumRembEmpruntByMonths($id_project,$ordre);
		
		$montantRembEmprunteur = $this->getMontantRembEmprunteur($Remb[$ordre]['montant'],$Remb[$ordre]['commission'],$Remb[$ordre]['tva']);
		
		$retourne['date_echeance_emprunteur'] = $Remb[$ordre]['date_echeance_emprunteur'];
		$retourne['montant'] = $montantRembEmprunteur;
		return $retourne;
	}
	
	// retourne la sum des echeance d'une journée
	// $date : yyyy-mm-dd
	function getEcheanceByDay($date,$val='montant',$statutEmprunteur='0')
	{
		$sql='SELECT SUM('.$val.') FROM `echeanciers` WHERE status_emprunteur = '.$statutEmprunteur.' AND LEFT(date_echeance_emprunteur,10) = "'.$date.'" GROUP BY  LEFT(date_echeance_emprunteur,10)';
		
		$result = $this->bdd->query($sql);
		$montant = ($this->bdd->result($result,0,0));
		return $montant;
	}
	
	function getEcheanceByDayAll_old($date,$statutEmprunteur='0')
	{
		$sql='SELECT 
		SUM(montant) as montant,
		SUM(capital) as capital,
		SUM(interets) as interets,
		SUM(commission) as commission,
		SUM(tva) as tva,
		SUM(prelevements_obligatoires) as prelevements_obligatoires,
		SUM(retenues_source) as retenues_source,
		SUM(csg) as csg,
		SUM(prelevements_sociaux) as prelevements_sociaux,
		SUM(contributions_additionnelles) as contributions_additionnelles,
		SUM(prelevements_solidarite) as prelevements_solidarite,
		SUM(crds) as crds 
		FROM `echeanciers` WHERE status_emprunteur = '.$statutEmprunteur.' AND LEFT(date_echeance_emprunteur_reel,10) = "'.$date.'" GROUP BY  LEFT(date_echeance_emprunteur_reel,10)';
		
		
		
		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result[0];
	}
	
	function getEcheanceByDayAll($date,$statutEmprunteur='0')
	{
		$sql='SELECT 
		SUM(montant) as montant,
		SUM(capital) as capital,
		SUM(interets) as interets,
		SUM(commission) as commission,
		SUM(tva) as tva,
		SUM(prelevements_obligatoires) as prelevements_obligatoires,
		SUM(retenues_source) as retenues_source,
		SUM(csg) as csg,
		SUM(prelevements_sociaux) as prelevements_sociaux,
		SUM(contributions_additionnelles) as contributions_additionnelles,
		SUM(prelevements_solidarite) as prelevements_solidarite,
		SUM(crds) as crds 
		FROM `echeanciers` WHERE status_emprunteur = '.$statutEmprunteur.' AND LEFT(date_echeance_reel,10) = "'.$date.'" GROUP BY  LEFT(date_echeance_reel,10)';
		
		
		
		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result[0];
	}
	
	
	/// en place
	function getEcheanceBetweenDates_exonere_mais_pas_dans_les_dates($date1,$date2,$exonere='',$morale='')
	{
		$anneemois = explode('-',$date1);
		$anneemois = $anneemois[0].'-'.$anneemois[1];

		$sql='SELECT 
		SUM(montant) as montant,
		SUM(capital) as capital,
		SUM(interets) as interets,
		SUM(commission) as commission,
		SUM(tva) as tva,
		SUM(prelevements_obligatoires) as prelevements_obligatoires,
		SUM(retenues_source) as retenues_source,
		SUM(csg) as csg,
		SUM(prelevements_sociaux) as prelevements_sociaux,
		SUM(contributions_additionnelles) as contributions_additionnelles,
		SUM(prelevements_solidarite) as prelevements_solidarite,
		SUM(crds) as crds 
		FROM echeanciers e 
		LEFT JOIN lenders_accounts l ON e.id_lender = l.id_lender_account
		LEFT JOIN clients c ON l.id_client_owner = c.id_client
		WHERE e.status_emprunteur = 1 
                AND e.status_ra = 0 /*on ne veut pas de remb anticipe */
		'.($morale != ''?' AND c.type = '.$morale:'');
		if($exonere == 1){
			$sql .= '
			 AND l.exonere = 1 
			 AND "'.$anneemois.'" NOT BETWEEN LEFT(l.debut_exoneration,7) AND LEFT(l.fin_exoneration,7)';
		}
		$sql .= ' 
		 AND LEFT(date_echeance_reel,10) BETWEEN "'.$date1.'" AND "'.$date2.'"';
		
		//echo $sql;
			
		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result[0];
	}
	
	
	function getEcheanceBetweenDatesEtranger($date1,$date2)
	{
		$anneemois = explode('-',$date1);
		$anneemois = $anneemois[0].'-'.$anneemois[1];
			
			$sql='SELECT 
			SUM(montant) as montant,
			SUM(capital) as capital,
			SUM(interets) as interets,
			SUM(commission) as commission,
			SUM(tva) as tva,
			SUM(prelevements_obligatoires) as prelevements_obligatoires,
			SUM(retenues_source) as retenues_source,
			SUM(csg) as csg,
			SUM(prelevements_sociaux) as prelevements_sociaux,
			SUM(contributions_additionnelles) as contributions_additionnelles,
			SUM(prelevements_solidarite) as prelevements_solidarite,
			SUM(crds) as crds 
			FROM echeanciers e 
			LEFT JOIN lenders_accounts l ON e.id_lender = l.id_lender_account
			LEFT JOIN clients c ON l.id_client_owner = c.id_client
			WHERE e.status_emprunteur = 1 
                        AND e.status_ra = 0 /*on ne veut pas de remb anticipe */
			AND c.type = 1 
			AND (SELECT resident_etranger FROM lenders_imposition_history lih WHERE lih.id_lender = l.id_lender_account AND LEFT(lih.added,10) <= e.date_echeance_reel ORDER BY added DESC LIMIT 1) > 0 
			AND LEFT(date_echeance_reel,10) BETWEEN "'.$date1.'" AND "'.$date2.'"';
			
		
		
		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result[0];
	}
	
	function getEcheanceBetweenDates($date1,$date2,$exonere='',$morale='',$etranger='')
	{
		$anneemois = explode('-',$date1);
		$anneemois = $anneemois[0].'-'.$anneemois[1];
			
			$sql='SELECT 
			SUM(montant) as montant,
			SUM(capital) as capital,
			SUM(interets) as interets,
			SUM(commission) as commission,
			SUM(tva) as tva,
			SUM(prelevements_obligatoires) as prelevements_obligatoires,
			SUM(retenues_source) as retenues_source,
			SUM(csg) as csg,
			SUM(prelevements_sociaux) as prelevements_sociaux,
			SUM(contributions_additionnelles) as contributions_additionnelles,
			SUM(prelevements_solidarite) as prelevements_solidarite,
			SUM(crds) as crds 
			FROM echeanciers e 
			LEFT JOIN lenders_accounts l ON e.id_lender = l.id_lender_account
			LEFT JOIN clients c ON l.id_client_owner = c.id_client
			WHERE e.status_emprunteur = 1 
                        AND e.status_ra = 0 /*on ne veut pas de remb anticipe */
			'.($morale != ''?' AND c.type = '.$morale:'');
			
			if($exonere != ''){
				if($exonere == '1'){
					$sql .= '
					 AND l.exonere = 1 
					 AND "'.$anneemois.'" BETWEEN LEFT(l.debut_exoneration,7) AND LEFT(l.fin_exoneration,7)';
				}else{
					$sql .= ' AND l.exonere = '.$exonere;
				}
			}
			
			$sql .= '
			AND LEFT(date_echeance_reel,10) BETWEEN "'.$date1.'" AND "'.$date2.'"';
			
			
			
			
		/*}
			else{
			$sql='SELECT 
			SUM(montant) as montant,
			SUM(capital) as capital,
			SUM(interets) as interets,
			SUM(commission) as commission,
			SUM(tva) as tva,
			SUM(prelevements_obligatoires) as prelevements_obligatoires,
			SUM(retenues_source) as retenues_source,
			SUM(csg) as csg,
			SUM(prelevements_sociaux) as prelevements_sociaux,
			SUM(contributions_additionnelles) as contributions_additionnelles,
			SUM(prelevements_solidarite) as prelevements_solidarite,
			SUM(crds) as crds 
			FROM echeanciers e 
			LEFT JOIN lenders_accounts l ON e.id_lender = l.id_lender_account
			LEFT JOIN clients c ON l.id_client_owner = c.id_client
			WHERE e.status_emprunteur = 1'.($exonere != ''?' AND l.exonere = '.$exonere:'').($morale != ''?' AND c.type = '.$morale:'').' AND LEFT(date_echeance_reel,10) BETWEEN "'.$date1.'" AND "'.$date2.'"';
		}*/
		
		
		
		
		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result[0];
	}
	
	function onMetAjourTVA($taux)
	{
		$sql='UPDATE echeanciers SET tva = ROUND(commission * '.$taux.') WHERE status_emprunteur = 0';
		$this->bdd->query($sql);
	}
	
	function onMetAjourLesDatesEcheances($id_project,$ordre,$date_echeance,$date_echeance_emprunteur)
	{
		$sql='UPDATE echeanciers SET date_echeance = "'.$date_echeance.'", date_echeance_emprunteur = "'.$date_echeance_emprunteur.'", updated = "'.date('Y-m-d H:i:s').'" WHERE status_emprunteur = 0 AND id_project = "'.$id_project.'" AND ordre = "'.$ordre.'" ';
		$this->bdd->query($sql);
	}
	
	/*// mise à jour remb exoneration preteur
	function update_prelevements_obligatoires($id_lender,$exonere,$prelevements_obligatoires='')
	{
		if($exonere == 1){
			$sql='UPDATE echeanciers SET prelevements_obligatoires = 0, updated = "'.date('Y-m-d H:i:s').'" WHERE id_lender = '.$id_lender.' AND status = 0';
		}
		elseif($exonere == 0 && $prelevements_obligatoires != ''){
			$sql='UPDATE echeanciers SET prelevements_obligatoires = ROUND((interets/100) * '.$prelevements_obligatoires.',2), updated = "'.date('Y-m-d H:i:s').'" WHERE id_lender = '.$id_lender.' AND status = 0';
		}
		$this->bdd->query($sql);
	}*/
	
	// mise à jour remb exoneration preteur
	function update_prelevements_obligatoires($id_lender,$exonere,$prelevements_obligatoires='',$debut='',$fin='')
	{
		if($exonere == 1){
			
			if($debut!='' && $fin !='')$debutfin = ' AND LEFT(date_echeance,10) >= "'.$debut.'" AND LEFT(date_echeance,10) <= "'.$fin.'"';
			else $debutfin = '';
			
			$sql='UPDATE echeanciers SET prelevements_obligatoires = 0, updated = "'.date('Y-m-d H:i:s').'" WHERE id_lender = '.$id_lender.' AND status = 0 '.$debutfin;
		}
		elseif($exonere == 0 && $prelevements_obligatoires != ''){
			$sql='UPDATE echeanciers SET prelevements_obligatoires = ROUND((interets/100) * '.$prelevements_obligatoires.',2), updated = "'.date('Y-m-d H:i:s').'" WHERE id_lender = '.$id_lender.' AND status = 0';
		}
		$this->bdd->query($sql);
	}
	
	// Mise à jour impositions etranger ou non
	function update_imposition_etranger($id_lender,$etranger,$tabImpo=array(),$exonere,$debut='',$fin=''){
		
		// 0 : fr/fr
		// 1 : fr/resident etranger
		// 2 : no fr/resident etranger
		
		if($etranger > 0){
			$sql='
			UPDATE echeanciers SET 
				prelevements_obligatoires = 0,
				retenues_source = ROUND((interets/100) * '.$tabImpo['retenues_source'].',2),
				csg = 0,
				prelevements_sociaux = 0,
				contributions_additionnelles = 0,
				prelevements_solidarite = 0,
				crds = 0,
				updated = "'.date('Y-m-d H:i:s').'"
			WHERE id_lender = '.$id_lender.' AND status = 0';
			
			$this->bdd->query($sql);
		}
		else{

			/*if($exonere == 1){
				$prelevements_obligatoires = 0;
				
				//if($debut!='' && $fin !='')$debutfin = ' AND LEFT(date_echeance,10) >= "'.$debut.'" AND LEFT(date_echeance,10) <= "'.$fin.'"';
				//else $debutfin = '';
			}
			else $prelevements_obligatoires = 'ROUND((interets/100) * '.$tabImpo['prelevements_obligatoires'].',2)';*/
			
			$prelevements_obligatoires = 'ROUND((interets/100) * '.$tabImpo['prelevements_obligatoires'].',2)';
			
			$sql='
			UPDATE echeanciers SET 
				prelevements_obligatoires = '.$prelevements_obligatoires.',
				retenues_source = 0,
				csg = ROUND((interets/100) * '.$tabImpo['csg'].',2),
				prelevements_sociaux = ROUND((interets/100) * '.$tabImpo['prelevements_sociaux'].',2),
				contributions_additionnelles = ROUND((interets/100) * '.$tabImpo['contributions_additionnelles'].',2),
				prelevements_solidarite = ROUND((interets/100) * '.$tabImpo['prelevements_solidarite'].',2),
				crds = ROUND((interets/100) * '.$tabImpo['crds'].',2),
				updated = "'.date('Y-m-d H:i:s').'"
			WHERE id_lender = '.$id_lender.' AND status = 0';
			
			$this->bdd->query($sql);
			
			
			// On met a jour l'exoneration ici
			if($debut!='' && $fin !='' && $exonere == 1){
				$this->update_prelevements_obligatoires($id_lender,$exonere,$tabImpo['prelevements_obligatoires'],$debut,$fin);
			}
		}
		
		
	}
	
	
	// Mise à jour impositions etranger ou non
	function update_imposition_etranger_old($id_lender,$etranger,$tabImpo=array(),$exonere){
		// 0 : fr/fr
		// 1 : fr/resident etranger
		// 2 : no fr/resident etranger
		
		if($etranger > 0){
			$sql='
			UPDATE echeanciers SET 
				prelevements_obligatoires = 0,
				retenues_source = ROUND((interets/100) * '.$tabImpo['retenues_source'].',2),
				csg = 0,
				prelevements_sociaux = 0,
				contributions_additionnelles = 0,
				prelevements_solidarite = 0,
				crds = 0,
				updated = "'.date('Y-m-d H:i:s').'"
			WHERE id_lender = '.$id_lender.' AND status = 0';
		}
		else{
			if($exonere == 1) $prelevements_obligatoires = 0;
			else $prelevements_obligatoires = 'ROUND((interets/100) * '.$tabImpo['prelevements_obligatoires'].',2)';
			
			$sql='
			UPDATE echeanciers SET 
				prelevements_obligatoires = '.$prelevements_obligatoires.',
				retenues_source = 0,
				csg = ROUND((interets/100) * '.$tabImpo['csg'].',2),
				prelevements_sociaux = ROUND((interets/100) * '.$tabImpo['prelevements_sociaux'].',2),
				contributions_additionnelles = ROUND((interets/100) * '.$tabImpo['contributions_additionnelles'].',2),
				prelevements_solidarite = ROUND((interets/100) * '.$tabImpo['prelevements_solidarite'].',2),
				crds = ROUND((interets/100) * '.$tabImpo['crds'].',2),
				updated = "'.date('Y-m-d H:i:s').'"
			WHERE id_lender = '.$id_lender.' AND status = 0';
		}
		$this->bdd->query($sql);
	}
	
	// Utilisé dans le cron remb auto
	function selectEcheances_a_remb($where='',$order='',$start='',$nb='')
	{
		if($where != '')
			$where = ' WHERE '.$where;
		if($order != '')
			$order = ' ORDER BY '.$order;
		
		$sql = '
		SELECT 
			id_echeancier,
			id_lender,
			id_project,
			ROUND(((ROUND((montant/100),2)) - prelevements_obligatoires - retenues_source - csg - prelevements_sociaux - contributions_additionnelles - prelevements_solidarite - crds),2) as rembNet,
		ROUND((prelevements_obligatoires + retenues_source + csg + prelevements_sociaux + contributions_additionnelles + prelevements_solidarite + crds),2) as etat,
		status_email_remb,
		status
		FROM `echeanciers`'.$where.$order.($nb!='' && $start !=''?' LIMIT '.$start.','.$nb:($nb!=''?' LIMIT '.$nb:''));

		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	}
	
	function requete_revenus($id_project)
	{
		$sql = '
			SELECT 
				e.id_lender,
				le.id_client_owner,
				le.id_company_owner,
				e.capital,
				e.interets,
				e.retenues_source,
				e.prelevements_obligatoires,
				(SELECT lih.resident_etranger FROM lenders_imposition_history lih WHERE lih.added <= e.date_echeance_reel AND lih.id_lender = e.id_lender ORDER BY lih.added DESC LIMIT 1) as resident,
				e.date_echeance_reel
			FROM echeanciers e 
			LEFT JOIN lenders_accounts le ON le.id_lender_account = e.id_lender
			WHERE e.status = 1 AND e.id_project = '.$id_project.'
			ORDER BY e.date_echeance';
		
		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	}
	
	// Utilisé dans le cron remb auto
	function selectfirstEcheanceByproject($date)
	{
		
		$sql = '
		SELECT 
			e.id_project,
			e.ordre,
			e.date_echeance,
			e.status,
			e.date_echeance_emprunteur,
			e.status_emprunteur,
			(SELECT ROUND(SUM(ee.montant+ee.commission+ee.tva)/100,2) FROM echeanciers_emprunteur ee WHERE e.id_project = ee.id_project AND e.ordre = ee.ordre) as montant_emprunteur
		FROM echeanciers e 
		WHERE LEFT(e.date_echeance,10) = "'.$date.'" AND status_emprunteur = 0
		GROUP BY e.id_project
		ORDER BY e.ordre';

		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	}	

	// debug
	function requeteGetecheancePrelevement(){
		$sql = "SELECT
				la.id_client_owner as id_client,
				la.id_lender_account,
				la.updated,
				la.debut_exoneration,
				la.fin_exoneration,
				e.id_echeancier,
				e.date_echeance,
				e.date_echeance_reel,
				e.status,
				e.interets,
				e.prelevements_obligatoires,
				e.id_project
			FROM lenders_accounts la
			LEFT JOIN echeanciers e ON e.id_lender = la.id_lender_account
			WHERE LEFT(la.updated,10) >= '2014-11-28' AND la.exonere = 1 AND la.debut_exoneration >= '2014-11-28' AND e.status = 1 AND LEFT(e.updated,10) >= '2014-11-28' AND la.debut_exoneration = '2015-01-01' AND LEFT(e.date_echeance_reel,10) >= '2014-12-01' ORDER BY e.date_echeance_reel";
		
		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	}
        
        // Utilisé dans cron check remb preteurs (27/04/2015)
	function selectEcheanciersByprojetEtOrdre()
	{
		$laDate = mktime(0,0,0, date("m"), date("d"), date("Y"));
		$laDate = date('Y-m-d',$laDate);
		
		$sql = 'SELECT id_project,ordre,status, LEFT(date_echeance,10) as date_echeance, LEFT(date_echeance_emprunteur,10) as date_echeance_emprunteur, LEFT(date_echeance_emprunteur_reel,10) as date_echeance_emprunteur_reel,status_emprunteur FROM echeanciers WHERE LEFT(date_echeance,10) = "'.$laDate.'" AND status = 0 group by id_project,ordre';
	
		
		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;	
	}
	
	 // retourne la somme total a rembourser pour un porjet
	function getSumRestanteARembByProject_only($id_project='', $date_debut = "")
	{
		$sql='SELECT SUM(capital) FROM `echeanciers` 
                        WHERE status = 0 
                        AND LEFT(date_echeance,10) > "'.$date_debut.'"
                        AND id_project = '.$id_project;
                
		$result = $this->bdd->query($sql);
		$sum = (int)($this->bdd->result($result,0,0));
		return ($sum/100);
	}
        
        
        // retourne la somme total a rembourser pour un porjet
	function get_liste_preteur_on_project($id_project='')
	{
		$sql='SELECT * FROM `echeanciers` 
                      WHERE id_project = '.$id_project.'
                      GROUP BY id_loan';
                
		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	}
        
        // retourne la somme total a rembourser pour un porjet
	function get_liste_preteur_on_project_old($id_project='')
	{
		$sql='SELECT id_lender FROM `echeanciers` 
                      WHERE id_project = '.$id_project.'
                      GROUP BY id_lender';
                
		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	}

 	// retourne la somme total a rembourser pour un porjet
	function reste_a_payer_ra($id_project='', $ordre='')
	{
		$sql='SELECT SUM(capital) FROM `echeanciers` 
                        WHERE status = 0 
                        AND ordre >= "'.$ordre.'"
                        AND id_project = '.$id_project;
                
		$result = $this->bdd->query($sql);
		$sum = (int)($this->bdd->result($result,0,0));
		return ($sum/100);
	}
        
        
        // retourne la somme des echeances deja remboursé d'une enchere
	function getSumRembByloan_remb_ra($id_loan,$champ='montant')
	{
		$sql='SELECT SUM('.$champ.') FROM `echeanciers` WHERE status = 1 AND id_loan = '.$id_loan.' AND status_ra = 0';

		$result = $this->bdd->query($sql);
		$sum = (int)($this->bdd->result($result,0,0));
		return ($sum/100);
	}
}
