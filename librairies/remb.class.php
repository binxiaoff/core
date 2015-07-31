<?
class remb {
	
	function echeancier($montant,$nbMois,$tauxAnnuel,$commissionAnnuelle,$tva=0.196)
	{
		// On divise le le taux annuel du preteur pour avoir le taux mensuel
		$txMensuel = ($tauxAnnuel / 12);
		// On fait la meme chose avec la commission annuelle
		$txComMensuelle = ($commissionAnnuelle / 12);
		
		//echo 'Taux périodique = '.$txMensuel.'<br />';
		
		 
		
		// Calcule pour avoir le montant a verser chaque mois (capital + interets)
		$echeance = round(( $montant * $txMensuel ) / ( 1 - pow( ( 1 + $txMensuel), -$nbMois ) ),2);
		
		
		// Tableau qu'on va remplir
		$ArrayEcheancier = array();
		
		// on va initialiser la variable $cap, qui est le montant restant à rembourser à un moment spécifique.
		$cap = $montant;
	
		// On parcour les mensualités
		$i = 0;
		while($i < $nbMois)
		{
			// les interets
			$interets = round($txMensuel*$cap,2);
			
			// le capital
			$capital = round(($echeance-$interets),2);

			// Chaque mois on retire le montant persue
			$cap -= $capital;
			
			// Pour le dernier mois on regularise 
			if($i==$nbMois-1)
			{
				// On regarde ce qui reste comme montant et on l'ajout dans le dernier versement
				$echeance = $echeance+$cap;
				$capital = $capital+$cap;
				// On vide le montant a remb
				$cap = 0;
			}
			
			$a = $i+1;
			
			$ArrayEcheancier[$a]['echeance'] = $echeance;
			$ArrayEcheancier[$a]['capital'] = $capital; 					// montant sans les interets
			$ArrayEcheancier[$a]['interets'] = $interets; 					// interets			
			$ArrayEcheancier[$a]['cap'] = $cap; 							// montant qui reste a remboursser
			
			
			$i++;
		}
		
		// com //
		
		// echeance com
		$echeanceCom = round(( $montant * $txComMensuelle ) / ( 1 - pow( ( 1 + $txComMensuelle), -$nbMois ) ),2);
		
		$capCom = $montant;
		$i = 0;
		while($i < $nbMois)
		{
			$com = round($txComMensuelle*$capCom,2);
			
			$capitalCom = round(($echeanceCom-$com),2);

			// Chaque mois on retire le montant persue
			$capCom -= $capitalCom;
			
			if($i==$nbMois-1)
			{
				// On regarde ce qui reste comme montant et on l'ajout dans le dernier versement
				$echeanceCom = $echeanceCom+$capCom;
				
				// On vide le montant a remb
				$capCom = 0;
			}
			
			// Total des com
			$totalCom += $com;
			
			$i++;
		}
		
		// fin com //
		
		
		// Commission par mois
		$comParMois = $totalCom/$nbMois;
		
		// Commission TTC par mois (com par mois + tva)
		$comParMoisTTC = $comParMois*(1+$tva);
		
		// tva de la com par mois
		$tvaCom = ($comParMois*$tva);
		
		// tva total de la commission total 
		$totalTvaCom = $tvaCom*$nbMois;
		

		$array1['totalCom'] = $totalCom;
		$array1['comParMois'] = round($comParMois,2);
		$array1['tvaCom'] = round($tvaCom,2);
		$array1['comParMoisTTC'] = round($comParMoisTTC,2);
		$array1['totalTvaCom'] = round($totalTvaCom,2);
		
		
		foreach($ArrayEcheancier as $k => $e)
		{
			$ArrayE[$k]['echeance'] = $e['echeance']; 					// montant a remb
			$ArrayE[$k]['capital'] = $e['capital']; 					// montant sans les interets
			$ArrayE[$k]['interets'] = $e['interets']; 					// interets
			$ArrayE[$k]['commission'] = round($comParMois,2);					// com
			$ArrayE[$k]['commissionTTC'] = round($comParMoisTTC,2);				// com ttc (com +tva)
			$ArrayE[$k]['echeancePlusComTTC'] = round($e['echeance']+$comParMoisTTC,2); // montant + com ttc
			$ArrayE[$k]['tva'] = round($tvaCom,2);	// tva		
			$ArrayE[$k]['cap'] = $e['cap'];							// montant qui reste a remboursser
			
		}
		
		return array(1 => $array1, 2 => $ArrayE);
		
	}
	
	function echeancierold2($montant,$nbMois,$tauxAnnuel,$commissionAnnuelle,$tva=0.196)
	{
		// On divise le le taux annuel du preteur pour avoir le taux mensuel
		$txMensuel = ($tauxAnnuel / 12);
		// On fait la meme chose avec la commission annuelle
		$txComMensuelle = ($commissionAnnuelle / 12);
		
		//echo 'Taux périodique = '.$txMensuel.'<br />';
		
		$tvaDelaCom = round($tva*($txComMensuelle),2); 
		
		// Calcule pour avoir le montant a verser chaque mois (capital + interets)
		$echeance = round(( $montant * ($txMensuel+$tvaDelaCom+$txComMensuelle) ) / ( 1 - pow( ( 1 + $txMensuel+$tvaDelaCom+$txComMensuelle ), -$nbMois ) ),2);
		
		//echo 'Echéances = '.$echeance.'<br />';
		
		// Tableau qu'on va remplir
		$ArrayEcheancier = array();
		
		// on va initialiser la variable $cap, qui est le montant restant à rembourser à un moment spécifique.
		$cap = $montant;
	
		// On parcour les mensualités
		while($i < $nbMois)
		{
			// les interets + com + tva
			$interets = round(($txMensuel+$tvaDelaCom+$txComMensuelle)*$cap,2);
			
			// le capital
			$capital = round((round($echeance,2)-$interets),2);
			
			// La com avec la tva
			$laCom = round(($tvaDelaCom+$txComMensuelle)*$cap,2);
			
			// interets
			$interetsSansCom = $interets-$laCom;
			
			// Montant preteur
			$montantPreteur = $capital+$interetsSansCom;
			
			// On recup les interets avec la commision mensuelle en plus
			//$interetsAvecCom = round($txMensuel+$txComMensuelle*$cap,2);
			// TVA sur com
			//$tvaDelaCom = round($tva*($interetsAvecCom),2);
			
			// On déduit le montant de la commission 
			$montantCom = $interets - $interetsAvecCom;
			
			
			
			// Montant remboursé par l'emprunteur
			$MontantRemb = round($capital+$interets+$tvaDelaCom+$interetsAvecCom,2);
			
			// Chaque mois on retire le montant persue
			$cap -= $capital;
			
			// Pour le dernier mois on regularise 
			if($i==$nbMois-1)
			{
				// On regarde ce qui reste comme montant et on l'ajout dans le dernier versement
				$capital = $capital+$cap;
				
				// On vide le montant a remb
				$cap = 0;
			}
			
			$a = $i+1;
			
			$ArrayEcheancier[$a]['montant'] = $montantPreteur; 					// montant a remb
			$ArrayEcheancier[$a]['capital'] = $capital; 					// montant sans les interets
			$ArrayEcheancier[$a]['interets'] = $interetsSansCom; 					// interets
			$ArrayEcheancier[$a]['commission'] = $laCom;			// interet + commission unilend
			$ArrayEcheancier[$a]['tva'] = $tvaDelaCom;			
			$ArrayEcheancier[$a]['com'] = $montantCom;
			$ArrayEcheancier[$a]['cap'] = $cap; 							// montant qui reste a remboursser
			$ArrayEcheancier[$a]['MontantRemb'] = $echeance;
			
			$i++;
		}
		return $ArrayEcheancier;
		
	}
	
	function echeancierold($montant,$nbMois,$tauxAnnuel,$commissionAnnuelle,$tva=0.196)
	{
		// On divise le le taux annuel du preteur pour avoir le taux mensuel
		$txMensuel = ($tauxAnnuel / 12);
		// On fait la meme chose avec la commission annuelle
		$txComMensuelle = ($commissionAnnuelle / 12);
		 
		//echo 'Taux périodique = '.$txMensuel.'<br />';
		
		// Calcule pour avoir le montant a verser chaque mois (capital + interets)
		$echeance = round(( $montant * $txMensuel ) / ( 1 - pow( ( 1 + $txMensuel ), -$nbMois ) ),2);
		
		//echo 'Echéances = '.$echeance.'<br />';
		
		// Tableau qu'on va remplir
		$ArrayEcheancier = array();
		
		// on va initialiser la variable $cap, qui est le montant restant à rembourser à un moment spécifique.
		$cap = $montant;
	
		// On parcour les mensualités
		while($i < $nbMois)
		{
			// On recup les interets (tx*montant)
			$interets = round($txMensuel*$cap,2);
			
			// On recup les interets avec la commision mensuelle en plus
			$interetsAvecCom = round($txMensuel+$txComMensuelle*$cap,2);
			
			// TVA sur com
			$tvaDelaCom = round($tva*($interetsAvecCom),2);
			
			// On déduit le montant de la commission 
			$montantCom = $interets - $interetsAvecCom;
			
			// Montant du mois a verser - les interets
			$capital = round((round($echeance,2)-$interets),2);
			
			// Montant remboursé par l'emprunteur
			$MontantRemb = round($capital+$interets+$tvaDelaCom+$interetsAvecCom,2);
			
			// Chaque mois on retire le montant persue
			$cap -= $capital;
			
			// Pour le dernier mois on regularise 
			if($i==$nbMois-1)
			{
				// On regarde ce qui reste comme montant et on l'ajout dans le dernier versement
				$capital = $capital+$cap;
				
				// On vide le montant a remb
				$cap = 0;
			}
			
			$a = $i+1;
			
			$ArrayEcheancier[$a]['montant'] = $echeance; 					// montant a remb
			$ArrayEcheancier[$a]['capital'] = $capital; 					// montant sans les interets
			$ArrayEcheancier[$a]['interets'] = $interets; 					// interets
			$ArrayEcheancier[$a]['commission'] = $interetsAvecCom;			// interet + commission unilend
			$ArrayEcheancier[$a]['tva'] = $tvaDelaCom;			
			$ArrayEcheancier[$a]['com'] = $montantCom;
			$ArrayEcheancier[$a]['cap'] = $cap; 							// montant qui reste a remboursser
			$ArrayEcheancier[$a]['MontantRemb'] = $MontantRemb;
			
			$i++;
		}
		return $ArrayEcheancier;
		
	}
	
}