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

class partenaires extends partenaires_crud
{

	function partenaires($bdd,$params='')
    {
        parent::partenaires($bdd,$params);
    }
    
    function get($id,$field='id_partenaire')
    {
        return parent::get($id,$field);
    }
    
    function update($cs='')
    {
        parent::update($cs);
    }
    
    function delete($id,$field='id_partenaire')
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
		$sql = 'SELECT * FROM `partenaires`'.$where.$order.($nb!='' && $start !=''?' LIMIT '.$start.','.$nb:($nb!=''?' LIMIT '.$nb:''));

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
			
		$sql='SELECT count(*) FROM `partenaires` '.$where;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	function exist($id,$field='id_partenaire')
	{
		$sql = 'SELECT * FROM `partenaires` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		return ($this->bdd->fetch_array($result,0,0)>0);
	}
	
	// Recuperation du ca d'un partenaire
	function recupCA($id_partenaire)
	{
		$sql='SELECT SUM(montant/100) FROM `transactions` WHERE status = 1 AND etat != 3 AND id_partenaire = '.$id_partenaire;

		$result = $this->bdd->query($sql);
		return $this->bdd->result($result,0,0);
	}
	
	// Recuperation du nb de cmde d'un partenaire
	function recupCmde($id_partenaire)
	{
		$sql='SELECT count(id_transaction) FROM `transactions` WHERE status = 1 AND etat != 3 AND id_partenaire = '.$id_partenaire;

		$result = $this->bdd->query($sql);
		return $this->bdd->result($result,0,0);
	}
	
	// Recuperation du ca d'un partenaire
	function statCA($id_partenaire,$deb_jour, $deb_mois, $deb_annee, $fin_jour, $fin_mois, $fin_annee)
	{
		$deb = str_pad($deb_annee, 4, '0', STR_PAD_LEFT).'-'.str_pad($deb_mois, 2, '0', STR_PAD_LEFT).'-'.str_pad($deb_jour, 2, '0', STR_PAD_LEFT);
		$fin = str_pad($fin_annee, 4, '0', STR_PAD_LEFT).'-'.str_pad($fin_mois, 2, '0', STR_PAD_LEFT).'-'.str_pad($fin_jour, 2, '0', STR_PAD_LEFT);
		
		$sql='SELECT SUM(montant/100) FROM `transactions` WHERE status = 1 AND etat != 3 AND date_transaction >= "' . $deb . ' 00:00:00" AND date_transaction <= "' . $fin . ' 23:59:59" AND id_partenaire = '.$id_partenaire;

		$result = $this->bdd->query($sql);
		return $this->bdd->result($result,0,0);
	}
	
	// Recuperation du ca d'un partenaire
	function statCmde($id_partenaire,$deb_jour, $deb_mois, $deb_annee, $fin_jour, $fin_mois, $fin_annee)
	{
		$deb = str_pad($deb_annee, 4, '0', STR_PAD_LEFT).'-'.str_pad($deb_mois, 2, '0', STR_PAD_LEFT).'-'.str_pad($deb_jour, 2, '0', STR_PAD_LEFT);
		$fin = str_pad($fin_annee, 4, '0', STR_PAD_LEFT).'-'.str_pad($fin_mois, 2, '0', STR_PAD_LEFT).'-'.str_pad($fin_jour, 2, '0', STR_PAD_LEFT);
		
		$sql='SELECT count(id_transaction) FROM `transactions` WHERE status = 1 AND etat != 3 AND date_transaction >= "' . $deb . ' 00:00:00" AND date_transaction <= "' . $fin . ' 23:59:59" AND id_partenaire = '.$id_partenaire;

		$result = $this->bdd->query($sql);
		return $this->bdd->result($result,0,0);
	}
	
	// Recuperation du nombre de clic global
	function nbClicTotal($id_partenaire)
	{
		$sql='SELECT SUM(nb_clics) FROM `partenaires_clics` WHERE id_partenaire = '.$id_partenaire;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	// Recuperation du nombre de clic sur periode
	function nbClic($id_partenaire,$deb_jour, $deb_mois, $deb_annee, $fin_jour, $fin_mois, $fin_annee)
	{
		$deb = str_pad($deb_annee, 4, '0', STR_PAD_LEFT).'-'.str_pad($deb_mois, 2, '0', STR_PAD_LEFT).'-'.str_pad($deb_jour, 2, '0', STR_PAD_LEFT);
		$fin = str_pad($fin_annee, 4, '0', STR_PAD_LEFT).'-'.str_pad($fin_mois, 2, '0', STR_PAD_LEFT).'-'.str_pad($fin_jour, 2, '0', STR_PAD_LEFT);
		
		$sql='SELECT SUM(nb_clics) FROM `partenaires_clics` WHERE date >= "' . $deb . '" AND date <= "' . $fin . '" AND id_partenaire = '.$id_partenaire;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
}